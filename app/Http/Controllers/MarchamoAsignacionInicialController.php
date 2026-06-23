<?php

namespace App\Http\Controllers;

use App\Models\Marchamo;
use App\Models\PuntoSeguridadUnidad;
use App\Models\Unidad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MarchamoAsignacionInicialController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $unidades = Unidad::query()
            ->with(['empresa', 'licencia'])
            ->withCount([
                'puntosSeguridad as total_puntos' => fn ($query) => $query->where('estado', 'activo'),
                'puntosSeguridad as puntos_asignados' => fn ($query) => $query
                    ->where('estado', 'activo')
                    ->whereNotNull('marchamo_actual_id'),
            ])
            ->where('estado', 'registrada')
            ->whereHas('licencia', function ($query) {
                $query->where('estado', 'activa');
            })
            ->whereHas('puntosSeguridad')
            ->when(! is_null($user->empresa_id), function ($query) use ($user) {
                $query->where('empresa_id', $user->empresa_id);
            })
            ->orderBy('placa')
            ->paginate(15);

        return view('marchamos.asignacion-inicial.index', [
            'unidades' => $unidades,
        ]);
    }

    /**
     * Muestra la pantalla de asignación inicial de marchamos para una unidad.
     */
    public function show(Unidad $unidad): View|RedirectResponse
    {
        $this->autorizarAccesoUnidad($unidad);

        $unidad->load([
            'empresa',
            'licencia',
            'puntosSeguridad.marchamoActual',
        ]);

        if ($unidad->estado === 'activa') {
            return redirect()
                ->route('marchamos.detalle-unidad', $unidad)
                ->with('success', 'La asignación inicial de esta unidad ya fue completada. Use Consulta de marchamos o Administración de marchamos para continuar.');
        }

        $this->validarUnidadAsignable($unidad);

        $totalPuntos = $unidad->puntosSeguridad()
            ->where('estado', 'activo')
            ->count();

        $puntosAsignados = $unidad->puntosSeguridad()
            ->where('estado', 'activo')
            ->whereNotNull('marchamo_actual_id')
            ->count();

        $puntosPendientes = $totalPuntos - $puntosAsignados;

        $porcentajeAvance = $totalPuntos > 0
            ? round(($puntosAsignados / $totalPuntos) * 100)
            : 0;

        return view('marchamos.asignacion-inicial.show', [
            'unidad' => $unidad,
            'totalPuntos' => $totalPuntos,
            'puntosAsignados' => $puntosAsignados,
            'puntosPendientes' => $puntosPendientes,
            'porcentajeAvance' => $porcentajeAvance,
        ]);
    }

    /**
     * Guarda parcialmente códigos de marchamo para puntos pendientes.
     */
    public function guardarAvance(Request $request, Unidad $unidad): RedirectResponse
    {
        $this->autorizarAccesoUnidad($unidad);

        $unidad->load([
            'empresa',
            'licencia',
            'puntosSeguridad.marchamoActual',
        ]);

        $this->validarUnidadAsignable($unidad);

        $validated = $request->validate([
            'marchamos' => ['nullable', 'array'],
            'marchamos.*' => ['nullable', 'string', 'regex:/^\d{7}$/'],
        ], [
            'marchamos.*.regex' => 'Cada código de marchamo debe contener exactamente 7 dígitos. Ejemplo: 0006387.',
        ]);

        $codigosPorPunto = collect($validated['marchamos'] ?? [])
            ->map(fn ($codigo) => is_string($codigo) ? trim($codigo) : null)
            ->filter(fn ($codigo) => filled($codigo));

        if ($codigosPorPunto->isEmpty()) {
            return back()
                ->with('success', 'No se ingresaron nuevos marchamos para guardar.');
        }

        $codigosDuplicadosEnFormulario = $codigosPorPunto
            ->duplicates()
            ->values();

        if ($codigosDuplicadosEnFormulario->isNotEmpty()) {
            throw ValidationException::withMessages([
                'marchamos' => 'Hay códigos de marchamo repetidos en el formulario: ' . $codigosDuplicadosEnFormulario->implode(', '),
            ]);
        }

        DB::transaction(function () use ($unidad, $codigosPorPunto): void {
            foreach ($codigosPorPunto as $puntoId => $codigoMarchamo) {
                $punto = PuntoSeguridadUnidad::query()
                    ->where('id', $puntoId)
                    ->where('unidad_id', $unidad->id)
                    ->lockForUpdate()
                    ->first();

                if (! $punto) {
                    throw ValidationException::withMessages([
                        'marchamos' => 'Uno de los puntos enviados no pertenece a esta unidad.',
                    ]);
                }

                if ($punto->estado !== 'activo') {
                    throw ValidationException::withMessages([
                        "marchamos.$puntoId" => 'No se puede asignar marchamo a un punto inactivo.',
                    ]);
                }

                if (! is_null($punto->marchamo_actual_id)) {
                    throw ValidationException::withMessages([
                        "marchamos.$puntoId" => 'Este punto ya tiene un marchamo asignado.',
                    ]);
                }

                $codigoExiste = Marchamo::query()
                    ->where('codigo_marchamo', $codigoMarchamo)
                    ->exists();

                if ($codigoExiste) {
                    throw ValidationException::withMessages([
                        "marchamos.$puntoId" => "El marchamo {$codigoMarchamo} ya existe en el sistema.",
                    ]);
                }

                $marchamo = Marchamo::create([
                    'empresa_id' => $unidad->empresa_id,
                    'unidad_id' => $unidad->id,
                    'punto_seguridad_id' => $punto->id,
                    'codigo_marchamo' => $codigoMarchamo,
                    'fecha_activacion' => now(),
                    'estado' => 'activo',
                    'activo_actual' => 1,
                    'fecha_desactivacion' => null,
                    'motivo_desactivacion' => null,
                    'origen_creacion' => 'asignacion_inicial',
                    'creado_por' => Auth::id(),
                    'actualizado_por' => Auth::id(),
                ]);

                $punto->update([
                    'marchamo_actual_id' => $marchamo->id,
                    'estado_asignacion' => 'asignado',
                    'actualizado_por' => Auth::id(),
                ]);
            }
        });

        return redirect()
            ->route('marchamos.asignacion-inicial.show', $unidad)
            ->with('success', 'Avance de asignación inicial guardado correctamente.');
    }

    /**
     * Finaliza la asignación inicial y activa la unidad.
     */
    public function finalizar(Unidad $unidad): RedirectResponse
    {
        $this->autorizarAccesoUnidad($unidad);

        $unidad->load([
            'empresa',
            'licencia',
            'puntosSeguridad',
        ]);

        $this->validarUnidadAsignable($unidad);

        $totalPuntos = $unidad->puntosSeguridad()
            ->where('estado', 'activo')
            ->count();

        $puntosPendientes = $unidad->puntosSeguridad()
            ->where('estado', 'activo')
            ->whereNull('marchamo_actual_id')
            ->count();

        if ($totalPuntos === 0) {
            return back()
                ->withErrors([
                    'finalizar' => 'La unidad no tiene puntos de seguridad generados.',
                ]);
        }

        if ($puntosPendientes > 0) {
            return back()
                ->withErrors([
                    'finalizar' => "No se puede finalizar la asignación inicial. Aún hay {$puntosPendientes} puntos pendientes.",
                ]);
        }

        DB::transaction(function () use ($unidad): void {
            $unidad->update([
                'estado' => 'activa',
                'fecha_inactivacion' => null,
                'inactivado_por' => null,
                'motivo_inactivacion' => null,
                'actualizado_por' => Auth::id(),
            ]);
        });

        return redirect()
            ->route('unidades.show', $unidad)
            ->with('success', 'Asignación inicial finalizada correctamente. La unidad ahora está activa.');
    }

    /**
     * Valida que la unidad pueda entrar al flujo de asignación inicial.
     */
    private function validarUnidadAsignable(Unidad $unidad): void
    {
        if (! $unidad->empresa || $unidad->empresa->estado !== 'activa') {
            abort(422, 'La empresa asociada a la unidad debe estar activa.');
        }

        if (! $unidad->licencia) {
            abort(422, 'La unidad debe tener una licencia registrada antes de asignar marchamos.');
        }

        if ($unidad->licencia->estado !== 'activa') {
            abort(422, 'La licencia de la unidad debe estar activa.');
        }

        if ($unidad->estado !== 'registrada') {
            abort(422, 'La unidad debe estar registrada para completar la asignación inicial.');
        }

        if ($unidad->puntosSeguridad()->count() === 0) {
            abort(422, 'La unidad no tiene puntos de seguridad generados.');
        }
    }

    /**
     * Control básico de acceso multiempresa.
     */
    private function autorizarAccesoUnidad(Unidad $unidad): void
    {
        $user = Auth::user();

        if (! is_null($user->empresa_id) && (int) $unidad->empresa_id !== (int) $user->empresa_id) {
            abort(403, 'No tiene autorización para acceder a esta unidad.');
        }
    }
}