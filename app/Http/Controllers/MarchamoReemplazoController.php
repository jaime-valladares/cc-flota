<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Marchamo;
use App\Models\PuntoSeguridadUnidad;
use App\Models\ReemplazoMarchamoDetalle;
use App\Models\ReemplazoMarchamoEvento;
use App\Models\Unidad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MarchamoReemplazoController extends Controller
{
    private const MOTIVOS_REEMPLAZO = [
        'dano' => 'Daño',
        'desgaste' => 'Desgaste',
        'perdida' => 'Pérdida',
        'manipulacion_detectada' => 'Manipulación detectada',
        'correccion_instalacion' => 'Corrección de instalación',
    ];

    public function index(Request $request): View
    {
        $user = Auth::user();

        $empresaId = $request->input('empresa_id');
        $unidadId = $request->input('unidad_id');

        $consultaEjecutada = $request->boolean('consultar');

        $hayFiltros = $consultaEjecutada
            || filled($empresaId)
            || filled($unidadId);

        $empresas = Empresa::query()
            ->when(! is_null($user->empresa_id), function ($query) use ($user) {
                $query->where('id', $user->empresa_id);
            })
            ->orderBy('nombre_legal')
            ->get();

        $unidades = Unidad::query()
            ->when(! is_null($user->empresa_id), function ($query) use ($user) {
                $query->where('empresa_id', $user->empresa_id);
            })
            ->when(is_null($user->empresa_id) && filled($empresaId), function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->where('estado', 'activa')
            ->whereHas('licencia')
            ->whereHas('puntosSeguridad')
            ->orderBy('placa')
            ->get();

        $unidadesDisponibles = Unidad::query()
            ->with(['empresa', 'licencia'])
            ->withCount([
                'puntosSeguridad as total_puntos' => function ($query) {
                    $query->where('estado', 'activo');
                },
                'puntosSeguridad as puntos_asignados' => function ($query) {
                    $query->where('estado', 'activo')
                        ->whereNotNull('marchamo_actual_id');
                },
                'marchamos as marchamos_activos' => function ($query) {
                    $query->where('estado', 'activo');
                },
                'marchamos as marchamos_historicos' => function ($query) {
                    $query->whereIn('estado', ['reemplazado', 'anulado']);
                },
            ])
            ->when(! is_null($user->empresa_id), function ($query) use ($user) {
                $query->where('empresa_id', $user->empresa_id);
            })
            ->when(is_null($user->empresa_id) && filled($empresaId), function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->when(filled($unidadId), function ($query) use ($unidadId) {
                $query->where('id', $unidadId);
            })
            ->where('estado', 'activa')
            ->whereHas('licencia')
            ->whereHas('puntosSeguridad')
            ->orderBy('placa')
            ->get()
            ->filter(function ($unidad) {
                $totalPuntos = (int) ($unidad->total_puntos ?? 0);
                $puntosAsignados = (int) ($unidad->puntos_asignados ?? 0);

                return $totalPuntos > 0 && $totalPuntos === $puntosAsignados;
            })
            ->values();

        return view('marchamos.reemplazos.index', [
            'empresas' => $empresas,
            'unidades' => $unidades,
            'unidadesDisponibles' => $unidadesDisponibles,

            'empresaId' => $empresaId,
            'unidadId' => $unidadId,

            'hayFiltros' => $hayFiltros,
            'consultaEjecutada' => $consultaEjecutada,
        ]);
    }

    public function show(Unidad $unidad): View
    {
        $this->validarAccesoUnidad($unidad);
        $this->validarUnidadReemplazable($unidad);

        $unidad->load(['empresa', 'licencia']);

        $puntos = PuntoSeguridadUnidad::query()
            ->with(['marchamoActual'])
            ->where('unidad_id', $unidad->id)
            ->where('estado', 'activo')
            ->where('requiere_marchamo', true)
            ->orderBy('orden')
            ->get();

        return view('marchamos.reemplazos.show', [
            'unidad' => $unidad,
            'puntos' => $puntos,
            'motivosReemplazo' => self::MOTIVOS_REEMPLAZO,
        ]);
    }

    public function store(Request $request, Unidad $unidad): RedirectResponse
    {
        $this->validarAccesoUnidad($unidad);
        $this->validarUnidadReemplazable($unidad);

        $validated = $request->validate([
            'reemplazos' => ['required', 'array', 'min:1'],
            'reemplazos.*.seleccionado' => ['nullable', 'boolean'],
            'reemplazos.*.punto_seguridad_id' => ['required', 'integer', 'exists:puntos_seguridad_unidad,id'],
            'reemplazos.*.nuevo_codigo_marchamo' => ['nullable', 'regex:/^\d{7}$/'],
            'reemplazos.*.motivo_reemplazo' => ['nullable', Rule::in(array_keys(self::MOTIVOS_REEMPLAZO))],
        ], [
            'reemplazos.required' => 'Debe seleccionar al menos un marchamo para reemplazar.',
            'reemplazos.array' => 'La información de reemplazos no tiene el formato esperado.',
            'reemplazos.*.punto_seguridad_id.required' => 'Cada reemplazo debe estar asociado a un punto de seguridad.',
            'reemplazos.*.nuevo_codigo_marchamo.regex' => 'El nuevo código de marchamo debe tener exactamente 7 dígitos.',
            'reemplazos.*.motivo_reemplazo.in' => 'Seleccione un motivo de reemplazo válido.',
        ]);

        $reemplazosSeleccionados = collect($validated['reemplazos'])
            ->filter(fn ($item) => isset($item['seleccionado']) && (bool) $item['seleccionado'])
            ->values();

        if ($reemplazosSeleccionados->isEmpty()) {
            return back()
                ->withErrors(['reemplazos' => 'Debe seleccionar al menos un marchamo para reemplazar.'])
                ->withInput();
        }

        foreach ($reemplazosSeleccionados as $index => $reemplazo) {
            if (blank($reemplazo['nuevo_codigo_marchamo'] ?? null)) {
                return back()
                    ->withErrors(["reemplazos.{$index}.nuevo_codigo_marchamo" => 'Ingrese el nuevo código de marchamo para cada punto seleccionado.'])
                    ->withInput();
            }

            if (blank($reemplazo['motivo_reemplazo'] ?? null)) {
                return back()
                    ->withErrors(["reemplazos.{$index}.motivo_reemplazo" => 'Seleccione el motivo de reemplazo para cada punto seleccionado.'])
                    ->withInput();
            }
        }

        $codigosNuevos = $reemplazosSeleccionados
            ->pluck('nuevo_codigo_marchamo')
            ->map(fn ($codigo) => trim((string) $codigo))
            ->values();

        if ($codigosNuevos->count() !== $codigosNuevos->unique()->count()) {
            return back()
                ->withErrors(['reemplazos' => 'No puede repetir el mismo código de marchamo dentro de la operación.'])
                ->withInput();
        }

        $codigosExistentes = Marchamo::query()
            ->whereIn('codigo_marchamo', $codigosNuevos)
            ->pluck('codigo_marchamo')
            ->values();

        if ($codigosExistentes->isNotEmpty()) {
            return back()
                ->withErrors([
                    'reemplazos' => 'Los siguientes códigos ya existen en el sistema: ' . $codigosExistentes->implode(', '),
                ])
                ->withInput();
        }

        DB::transaction(function () use ($unidad, $reemplazosSeleccionados) {
            $user = Auth::user();

            $motivoPrincipal = $reemplazosSeleccionados->first()['motivo_reemplazo'];

            $evento = ReemplazoMarchamoEvento::create([
                'empresa_id' => $unidad->empresa_id,
                'unidad_id' => $unidad->id,
                'motivo_reemplazo' => $motivoPrincipal,
                'cantidad_reemplazos' => $reemplazosSeleccionados->count(),
                'origen_evento' => 'reemplazo_general',
                'estado' => 'registrado',
                'fecha_registro' => now(),
                'registrado_por' => $user?->id,
            ]);

            foreach ($reemplazosSeleccionados as $reemplazo) {
                $punto = PuntoSeguridadUnidad::query()
                    ->where('id', $reemplazo['punto_seguridad_id'])
                    ->where('unidad_id', $unidad->id)
                    ->where('estado', 'activo')
                    ->where('requiere_marchamo', true)
                    ->lockForUpdate()
                    ->firstOrFail();

                $marchamoAnterior = Marchamo::query()
                    ->where('id', $punto->marchamo_actual_id)
                    ->where('unidad_id', $unidad->id)
                    ->where('punto_seguridad_id', $punto->id)
                    ->where('estado', 'activo')
                    ->where('activo_actual', true)
                    ->lockForUpdate()
                    ->firstOrFail();

                $marchamoAnterior->update([
                    'estado' => 'reemplazado',
                    'activo_actual' => false,
                    'fecha_desactivacion' => now(),
                    'motivo_desactivacion' => $this->motivoTexto($reemplazo['motivo_reemplazo']),
                    'actualizado_por' => Auth::id(),
                ]);

                $marchamoNuevo = Marchamo::create([
                    'empresa_id' => $unidad->empresa_id,
                    'unidad_id' => $unidad->id,
                    'punto_seguridad_id' => $punto->id,
                    'codigo_marchamo' => trim((string) $reemplazo['nuevo_codigo_marchamo']),
                    'fecha_activacion' => now(),
                    'estado' => 'activo',
                    'activo_actual' => true,
                    'origen_creacion' => 'reemplazo_dano_desgaste',
                    'creado_por' => Auth::id(),
                ]);

                $punto->update([
                    'marchamo_actual_id' => $marchamoNuevo->id,
                    'estado_asignacion' => 'asignado',
                    'actualizado_por' => Auth::id(),
                ]);

                ReemplazoMarchamoDetalle::create([
                    'reemplazo_evento_id' => $evento->id,
                    'punto_seguridad_id' => $punto->id,
                    'marchamo_anterior_id' => $marchamoAnterior->id,
                    'marchamo_nuevo_id' => $marchamoNuevo->id,
                    'fecha_registro' => now(),
                ]);
            }
        });

        return redirect()
            ->route('marchamos.reemplazos.show', $unidad)
            ->with('success', 'Reemplazos de marchamos registrados correctamente.');
    }

    private function validarAccesoUnidad(Unidad $unidad): void
    {
        $user = Auth::user();

        if (! is_null($user->empresa_id) && (int) $unidad->empresa_id !== (int) $user->empresa_id) {
            abort(403);
        }
    }

    private function validarUnidadReemplazable(Unidad $unidad): void
    {
        $unidad->loadCount([
            'puntosSeguridad as total_puntos' => function ($query) {
                $query->where('estado', 'activo');
            },
            'puntosSeguridad as puntos_asignados' => function ($query) {
                $query->where('estado', 'activo')
                    ->whereNotNull('marchamo_actual_id');
            },
        ]);

        if ($unidad->estado !== 'activa') {
            abort(403, 'La unidad debe estar activa para reemplazar marchamos.');
        }

        if ((int) $unidad->total_puntos === 0 || (int) $unidad->total_puntos !== (int) $unidad->puntos_asignados) {
            abort(403, 'La unidad debe tener cobertura completa para reemplazar marchamos.');
        }
    }

    private function motivoTexto(string $motivo): string
    {
        return self::MOTIVOS_REEMPLAZO[$motivo] ?? 'No definido';
    }
}