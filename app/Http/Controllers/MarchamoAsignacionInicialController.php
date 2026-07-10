<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
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
    public function index(Request $request): View
    {
        return view('marchamos.asignacion-inicial.index', $this->datosIndex($request));
    }

    public function indexVentana(Request $request): View
    {
        return view('marchamos.asignacion-inicial.index-ventana', $this->datosIndex($request));
    }

    /**
     * Muestra la pantalla de asignación inicial de marchamos para una unidad.
     */
    public function show(Unidad $unidad): View|RedirectResponse
    {
        $this->autorizarAccesoUnidad($unidad);

        $unidad->loadMissing(['empresa', 'licencia']);

        $this->validarEmpresaActivaUnidad($unidad);

        if ($unidad->estado === 'activa') {
            return redirect()
                ->route('marchamos.detalle-unidad', $unidad)
                ->with('success', 'La asignación inicial de esta unidad ya fue completada. Use Consulta de marchamos o Administración de marchamos para continuar.');
        }

        return view('marchamos.asignacion-inicial.show', $this->datosShow($unidad));
    }

    public function showVentana(Unidad $unidad): View|RedirectResponse
    {
        $this->autorizarAccesoUnidad($unidad);

        $unidad->loadMissing(['empresa', 'licencia']);

        $this->validarEmpresaActivaUnidad($unidad);

        if ($unidad->estado === 'activa') {
            return redirect()
                ->route('marchamos.detalle-unidad.ventana', $unidad)
                ->with('success', 'La asignación inicial de esta unidad ya fue completada. Use Consulta de marchamos o Administración de marchamos para continuar.');
        }

        return view('marchamos.asignacion-inicial.show-ventana', $this->datosShow($unidad));
    }

    /**
     * Guarda o corrige códigos de marchamo durante la asignación inicial.
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
            'return_to' => ['nullable', 'string', 'in:ventana'],
        ], [
            'marchamos.*.regex' => 'Cada código de marchamo debe contener exactamente 7 dígitos. Ejemplo: 0006387.',
        ]);

        $marchamosFormulario = collect($validated['marchamos'] ?? [])
            ->mapWithKeys(function ($codigo, $puntoId) {
                $codigoNormalizado = is_string($codigo) ? trim($codigo) : null;

                return [
                    (int) $puntoId => filled($codigoNormalizado) ? $codigoNormalizado : null,
                ];
            });

        if ($marchamosFormulario->isEmpty()) {
            return back()
                ->with('success', 'No se recibieron cambios de marchamos para guardar.');
        }

        $codigosIngresados = $marchamosFormulario
            ->filter(fn ($codigo) => filled($codigo))
            ->values();

        $codigosDuplicadosEnFormulario = $codigosIngresados
            ->duplicates()
            ->values();

        if ($codigosDuplicadosEnFormulario->isNotEmpty()) {
            throw ValidationException::withMessages([
                'marchamos' => 'Hay códigos de marchamo repetidos en el formulario: ' . $codigosDuplicadosEnFormulario->implode(', '),
            ]);
        }

        DB::transaction(function () use ($unidad, $marchamosFormulario, $codigosIngresados): void {
            $puntoIdsFormulario = $marchamosFormulario
                ->keys()
                ->map(fn ($puntoId) => (int) $puntoId)
                ->values();

            $puntos = PuntoSeguridadUnidad::query()
                ->where('unidad_id', $unidad->id)
                ->whereIn('id', $puntoIdsFormulario)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($puntos->count() !== $puntoIdsFormulario->count()) {
                throw ValidationException::withMessages([
                    'marchamos' => 'Uno o más puntos enviados no pertenecen a esta unidad.',
                ]);
            }

            foreach ($puntos as $punto) {
                if ($punto->estado !== 'activo') {
                    throw ValidationException::withMessages([
                        "marchamos.{$punto->id}" => 'No se puede asignar marchamo a un punto inactivo.',
                    ]);
                }
            }

            if ($codigosIngresados->isNotEmpty()) {
                $codigosNoDisponibles = Marchamo::query()
                    ->whereIn('codigo_marchamo', $codigosIngresados)
                    ->where(function ($query) use ($unidad, $puntoIdsFormulario) {
                        $query->whereNull('unidad_id')
                            ->orWhere('unidad_id', '!=', $unidad->id)
                            ->orWhere('origen_creacion', '!=', 'asignacion_inicial')
                            ->orWhere(function ($sameUnidadQuery) use ($unidad, $puntoIdsFormulario) {
                                $sameUnidadQuery
                                    ->where('unidad_id', $unidad->id)
                                    ->whereNotIn('punto_seguridad_id', $puntoIdsFormulario);
                            });
                    })
                    ->pluck('codigo_marchamo')
                    ->unique()
                    ->values();

                if ($codigosNoDisponibles->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'marchamos' => 'Los siguientes marchamos no están disponibles para esta asignación inicial: ' . $codigosNoDisponibles->implode(', '),
                    ]);
                }
            }

            $marchamosProvisionales = Marchamo::query()
                ->where('unidad_id', $unidad->id)
                ->where('origen_creacion', 'asignacion_inicial')
                ->where(function ($query) use ($puntoIdsFormulario, $codigosIngresados) {
                    $query->whereIn('punto_seguridad_id', $puntoIdsFormulario);

                    if ($codigosIngresados->isNotEmpty()) {
                        $query->orWhereIn('codigo_marchamo', $codigosIngresados);
                    }
                })
                ->lockForUpdate()
                ->get();

            $marchamosPorCodigo = $marchamosProvisionales
                ->keyBy('codigo_marchamo');

            foreach ($puntos as $punto) {
                $punto->update([
                    'marchamo_actual_id' => null,
                    'estado_asignacion' => 'pendiente',
                    'actualizado_por' => Auth::id(),
                ]);
            }

            $codigosDeseados = $codigosIngresados
                ->unique()
                ->values();

            $marchamosAEliminar = $marchamosProvisionales
                ->filter(function ($marchamo) use ($puntoIdsFormulario, $codigosDeseados) {
                    return $puntoIdsFormulario->contains((int) $marchamo->punto_seguridad_id)
                        && ! $codigosDeseados->contains($marchamo->codigo_marchamo);
                });

            foreach ($marchamosAEliminar as $marchamo) {
                $marchamo->delete();
            }

            foreach ($marchamosFormulario as $puntoId => $codigoMarchamo) {
                if (blank($codigoMarchamo)) {
                    continue;
                }

                $punto = $puntos->get((int) $puntoId);

                $marchamo = $marchamosPorCodigo->get($codigoMarchamo);

                if ($marchamo) {
                    $marchamo->update([
                        'empresa_id' => $unidad->empresa_id,
                        'unidad_id' => $unidad->id,
                        'punto_seguridad_id' => $punto->id,
                        'fecha_activacion' => $marchamo->fecha_activacion ?: now(),
                        'estado' => 'activo',
                        'activo_actual' => 1,
                        'fecha_desactivacion' => null,
                        'motivo_desactivacion' => null,
                        'actualizado_por' => Auth::id(),
                    ]);
                } else {
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
                }

                $punto->update([
                    'marchamo_actual_id' => $marchamo->id,
                    'estado_asignacion' => 'asignado',
                    'actualizado_por' => Auth::id(),
                ]);
            }
        });

        return back()
            ->with('success', 'Avance de asignación inicial guardado correctamente.');
    }

    /**
     * Finaliza la asignación inicial y activa la unidad.
     */
    public function finalizar(Request $request, Unidad $unidad): RedirectResponse
    {
        $this->autorizarAccesoUnidad($unidad);

        $unidad->load([
            'empresa',
            'licencia',
            'puntosSeguridad',
        ]);

        $this->validarUnidadAsignable($unidad);

        $validated = $request->validate([
            'return_to' => ['nullable', 'string', 'in:ventana'],
        ]);

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
                    'finalizar' => "La asignación de marchamos no está completa. Aún hay {$puntosPendientes} puntos pendientes por asignar o corregir.",
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

        $ruta = ($validated['return_to'] ?? null) === 'ventana'
            ? 'marchamos.asignacion-inicial.index.ventana'
            : 'marchamos.asignacion-inicial.index';

        return redirect()
            ->route($ruta, [
                'empresa_id' => $unidad->empresa_id,
                'unidad_id' => $unidad->id,
                'consultar' => 1,
            ])
            ->with('success', 'Asignación inicial finalizada correctamente. La unidad ahora está activa.');
    }

    private function datosIndex(Request $request): array
    {
        $user = Auth::user();
        $esUsuarioDieselCop = is_null($user->empresa_id);

        $busquedaEmpresa = trim((string) $request->input('busqueda_empresa', ''));
        $busquedaPlaca = trim((string) $request->input('busqueda_placa', ''));

        $empresaId = $request->input('empresa_id');
        $unidadId = $request->input('unidad_id');

        if (! $esUsuarioDieselCop) {
            $empresaId = $user->empresa_id;
        }

        $consultaEjecutada = $request->boolean('consultar');

        $hayFiltros = $consultaEjecutada
            || filled($busquedaEmpresa)
            || filled($busquedaPlaca)
            || filled($empresaId)
            || filled($unidadId);

        $empresas = Empresa::query()
            ->when(! $esUsuarioDieselCop, function ($query) use ($user) {
                $query->where('id', $user->empresa_id);
            })
            ->where('estado', 'activa')
            ->orderBy('nombre_comercial')
            ->orderBy('nombre_legal')
            ->get();

        $baseUnidadesQuery = Unidad::query()
            ->with(['empresa', 'licencia'])
            ->withCount([
                'puntosSeguridad as total_puntos' => function ($query) {
                    $query->where('estado', 'activo');
                },
                'puntosSeguridad as puntos_asignados' => function ($query) {
                    $query->where('estado', 'activo')
                        ->whereNotNull('marchamo_actual_id');
                },
            ])
            ->where('estado', 'registrada')
            ->whereHas('empresa', function ($query) {
                $query->where('estado', 'activa');
            })
            ->whereHas('licencia', function ($query) {
                $query->where('estado', 'activa');
            })
            ->whereHas('puntosSeguridad', function ($query) {
                $query->where('estado', 'activo');
            })
            ->when(! $esUsuarioDieselCop, function ($query) use ($user) {
                $query->where('empresa_id', $user->empresa_id);
            });

        $unidades = (clone $baseUnidadesQuery)
            ->when($esUsuarioDieselCop && filled($empresaId), function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->orderBy('placa')
            ->get();

        $unidadesDisponibles = (clone $baseUnidadesQuery)
            ->when($hayFiltros && filled($busquedaEmpresa), function ($query) use ($busquedaEmpresa) {
                $query->whereHas('empresa', function ($empresaQuery) use ($busquedaEmpresa) {
                    $empresaQuery
                        ->where('nombre_legal', 'like', '%' . $busquedaEmpresa . '%')
                        ->orWhere('nombre_comercial', 'like', '%' . $busquedaEmpresa . '%');
                });
            })
            ->when($hayFiltros && $esUsuarioDieselCop && filled($empresaId), function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->when($hayFiltros && filled($busquedaPlaca), function ($query) use ($busquedaPlaca) {
                $query->where('placa', 'like', '%' . $busquedaPlaca . '%');
            })
            ->when($hayFiltros && filled($unidadId), function ($query) use ($unidadId) {
                $query->where('id', $unidadId);
            })
            ->when(! $hayFiltros, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->orderBy('placa')
            ->paginate(15)
            ->withQueryString();

        return [
            'unidades' => $unidades,
            'unidadesDisponibles' => $unidadesDisponibles,
            'empresas' => $empresas,

            'busquedaEmpresa' => $busquedaEmpresa,
            'busquedaPlaca' => $busquedaPlaca,

            'empresaId' => $empresaId,
            'unidadId' => $unidadId,

            'hayFiltros' => $hayFiltros,
            'consultaEjecutada' => $consultaEjecutada,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
        ];
    }

    private function datosShow(Unidad $unidad): array
    {
        $unidad->load([
            'empresa',
            'licencia',
            'puntosSeguridad.marchamoActual',
        ]);

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

        return [
            'unidad' => $unidad,
            'totalPuntos' => $totalPuntos,
            'puntosAsignados' => $puntosAsignados,
            'puntosPendientes' => $puntosPendientes,
            'porcentajeAvance' => $porcentajeAvance,
        ];
    }

    /**
     * Valida que la unidad pueda entrar al flujo de asignación inicial.
     */
    private function validarUnidadAsignable(Unidad $unidad): void
    {
        $this->validarEmpresaActivaUnidad($unidad);

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

    private function validarEmpresaActivaUnidad(Unidad $unidad): void
    {
        $unidad->loadMissing('empresa');

        if (! $unidad->empresa || $unidad->empresa->estado !== 'activa') {
            abort(403, 'No se puede operar sobre marchamos porque la empresa está inactiva.');
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