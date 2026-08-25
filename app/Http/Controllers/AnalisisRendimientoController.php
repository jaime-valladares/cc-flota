<?php

namespace App\Http\Controllers;

use App\Models\Abastecimiento;
use App\Models\Empresa;
use App\Models\Motorista;
use App\Models\Unidad;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnalisisRendimientoController extends Controller
{
    public function index(Request $request): View
    {
        return view(
            'analisis-rendimientos.index',
            $this->prepararAnalisis($request)
        );
    }

    public function indexVentana(Request $request): View
    {
        return view(
            'analisis-rendimientos.index-ventana',
            $this->prepararAnalisis($request)
        );
    }

    private function prepararAnalisis(Request $request): array
    {
        $user = Auth::user();
        $esUsuarioDieselCop = is_null($user->empresa_id);
        $empresaUsuario = $esUsuarioDieselCop
            ? null
            : Empresa::find($user->empresa_id);

        $validated = $request->validate(
            [
                'empresa_ids' => ['nullable', 'array'],
                'empresa_ids.*' => ['integer', 'distinct', 'exists:empresas,id'],
                'unidad_ids' => ['nullable', 'array'],
                'unidad_ids.*' => ['integer', 'distinct', 'exists:unidades,id'],
                'motorista_ids' => ['nullable', 'array'],
                'motorista_ids.*' => ['integer', 'distinct', 'exists:motoristas,id'],
                'modelos_medicion' => ['nullable', 'array'],
                'modelos_medicion.*' => [
                    'string',
                    'distinct',
                    Rule::in([
                        Abastecimiento::MODELO_KILOMETROS_GALON,
                        Abastecimiento::MODELO_GALONES_HORA,
                        Abastecimiento::MODELO_GALONES_VIAJE,
                    ]),
                ],
                'fecha_desde' => ['nullable', 'date_format:Y-m-d'],
                'fecha_hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:fecha_desde'],
                'busqueda' => ['nullable', 'string', 'max:150'],
                'sort' => [
                    'nullable',
                    Rule::in([
                        'numero_ciclo',
                        'fecha_ciclo',
                        'unidad',
                        'motorista',
                        'kilometros_recorridos',
                        'galones_abastecidos',
                        'galones_utilizados',
                        'kilometros_por_galon',
                    ]),
                ],
                'direction' => ['nullable', Rule::in(['asc', 'desc'])],
                'empresa_id' => ['nullable', 'integer', 'exists:empresas,id'],
                'unidad_id' => ['nullable', 'integer', 'exists:unidades,id'],
                'motorista_id' => ['nullable', 'integer', 'exists:motoristas,id'],
                'modelo_medicion' => [
                    'nullable',
                    Rule::in([
                        Abastecimiento::MODELO_KILOMETROS_GALON,
                        Abastecimiento::MODELO_GALONES_HORA,
                        Abastecimiento::MODELO_GALONES_VIAJE,
                    ]),
                ],
            ],
            [
                'empresa_ids.array' => 'La selección de empresas no es válida.',
                'empresa_ids.*.exists' => 'Una de las empresas seleccionadas no existe.',
                'unidad_ids.array' => 'La selección de unidades no es válida.',
                'unidad_ids.*.exists' => 'Una de las unidades seleccionadas no existe.',
                'motorista_ids.array' => 'La selección de motoristas no es válida.',
                'motorista_ids.*.exists' => 'Uno de los motoristas seleccionados no existe.',
                'modelos_medicion.array' => 'La selección de modelos no es válida.',
                'modelos_medicion.*.in' => 'Uno de los modelos seleccionados no es válido.',
                'fecha_hasta.after_or_equal' => 'La fecha final no puede ser anterior a la fecha inicial.',
            ]
        );

        $empresaIds = $this->normalizarIdsSeleccionados(
            $validated['empresa_ids'] ?? [],
            $validated['empresa_id'] ?? null
        );

        $unidadIds = $this->normalizarIdsSeleccionados(
            $validated['unidad_ids'] ?? [],
            $validated['unidad_id'] ?? null
        );

        $motoristaIds = $this->normalizarIdsSeleccionados(
            $validated['motorista_ids'] ?? [],
            $validated['motorista_id'] ?? null
        );

        $modelosMedicion = $this->normalizarModelosSeleccionados(
            $validated['modelos_medicion'] ?? [],
            $validated['modelo_medicion'] ?? null
        );

        if (! $esUsuarioDieselCop) {
            $empresaIds = [(int) $user->empresa_id];
        }

        $fechaDesde = $validated['fecha_desde'] ?? null;
        $fechaHasta = $validated['fecha_hasta'] ?? null;
        $busqueda = trim((string) ($validated['busqueda'] ?? ''));
        $sort = (string) ($validated['sort'] ?? 'fecha_ciclo');
        $direction = (string) ($validated['direction'] ?? 'desc');

        $consultaEjecutada = $request->boolean('consultar');

        /*
         * La empresa obligatoria restringe el alcance empresarial,
         * pero no ejecuta automáticamente el análisis.
         */
        $hayFiltros = $consultaEjecutada
            || $unidadIds !== []
            || $motoristaIds !== []
            || $modelosMedicion !== []
            || filled($fechaDesde)
            || filled($fechaHasta)
            || $busqueda !== ''
            || (
                $esUsuarioDieselCop
                && $empresaIds !== []
            );

        $consultaBase = Abastecimiento::query()->registrados();

        if (! $esUsuarioDieselCop) {
            $consultaBase->where('empresa_id', $user->empresa_id);
        }

        if (! $hayFiltros) {
            $consultaBase->whereRaw('1 = 0');
        } else {
            $this->aplicarFiltrosAnaliticos(
                $consultaBase,
                $empresaIds,
                $unidadIds,
                $motoristaIds,
                $modelosMedicion,
                $fechaDesde,
                $fechaHasta,
                $busqueda
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Tabla analítica: una fila por ciclo completado
        |--------------------------------------------------------------------------
        |
        | El abastecimiento actual cierra el ciclo formado con su
        | abastecimiento anterior. Las líneas base no se presentan como filas.
        |
        */

        $queryResultados = clone $consultaBase;

        $queryResultados
            ->whereNotNull('abastecimiento_anterior_id')
            ->select('abastecimientos.*')
            ->selectSub(
                function ($subquery): void {
                    $subquery
                        ->from('abastecimientos as ciclo_historico')
                        ->selectRaw('COUNT(*)')
                        ->whereColumn(
                            'ciclo_historico.unidad_id',
                            'abastecimientos.unidad_id'
                        )
                        ->where(
                            'ciclo_historico.estado',
                            Abastecimiento::ESTADO_REGISTRADO
                        )
                        ->whereNotNull(
                            'ciclo_historico.abastecimiento_anterior_id'
                        )
                        ->where(function ($secuencia): void {
                            $secuencia
                                ->whereColumn(
                                    'ciclo_historico.fecha_hora_abastecimiento',
                                    '<',
                                    'abastecimientos.fecha_hora_abastecimiento'
                                )
                                ->orWhere(function ($empate): void {
                                    $empate
                                        ->whereColumn(
                                            'ciclo_historico.fecha_hora_abastecimiento',
                                            '=',
                                            'abastecimientos.fecha_hora_abastecimiento'
                                        )
                                        ->whereColumn(
                                            'ciclo_historico.id',
                                            '<=',
                                            'abastecimientos.id'
                                        );
                                });
                        });
                },
                'numero_ciclo_historico'
            )
            ->selectSub(
                function ($subquery): void {
                    $subquery
                        ->from('abastecimientos as abastecimiento_apertura')
                        ->select('abastecimiento_apertura.volumen_cargado')
                        ->whereColumn(
                            'abastecimiento_apertura.id',
                            'abastecimientos.abastecimiento_anterior_id'
                        )
                        ->limit(1);
                },
                'galones_abastecidos_ciclo'
            )
            ->with([
                'empresa',
                'unidad',
                'motorista',
                'abastecimientoAnterior',
            ]);

        $this->aplicarOrdenAnalitico(
            $queryResultados,
            $sort,
            $direction
        );

        $abastecimientos = $queryResultados
            ->paginate(10)
            ->withQueryString();

        $abastecimientos->getCollection()->transform(
            fn (Abastecimiento $abastecimiento): Abastecimiento =>
                $this->prepararFilaAnalitica($abastecimiento)
        );

        $empresasSelector = $this->obtenerEmpresasSelector(
            $esUsuarioDieselCop,
            $empresaUsuario
        );

        $unidadesSelector = $this->obtenerUnidadesSelector(
            $esUsuarioDieselCop,
            $user->empresa_id,
            $empresaIds
        );

        $motoristasSelector = $this->obtenerMotoristasSelector(
            $esUsuarioDieselCop,
            $user->empresa_id,
            $empresaIds
        );

        $unidadIds = $this->filtrarIdsPermitidos(
            $unidadIds,
            $unidadesSelector->pluck('id')
        );

        $motoristaIds = $this->filtrarIdsPermitidos(
            $motoristaIds,
            $motoristasSelector->pluck('id')
        );

        $resumen = $this->obtenerResumenAnalitico(clone $consultaBase);

        $graficos = $this->obtenerDatosGraficos(
            clone $consultaBase,
            $unidadIds,
            $unidadesSelector
        );

        return [
            'abastecimientos' => $abastecimientos,
            'empresasSelector' => $empresasSelector,
            'unidadesSelector' => $unidadesSelector,
            'motoristasSelector' => $motoristasSelector,
            'empresaIds' => $empresaIds,
            'unidadIds' => $unidadIds,
            'motoristaIds' => $motoristaIds,
            'modelosMedicion' => $modelosMedicion,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'busqueda' => $busqueda,
            'sort' => $sort,
            'direction' => $direction,
            'hayFiltros' => $hayFiltros,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'empresaUsuario' => $empresaUsuario,
            'resumen' => $resumen,
            'graficos' => $graficos,
            'tipoResumen' => $this->determinarTipoResumen($modelosMedicion),
            'opcionesModelos' => [
                Abastecimiento::MODELO_GALONES_VIAJE => 'Galones por viaje',
                Abastecimiento::MODELO_KILOMETROS_GALON => 'Kilómetros por galón',
                Abastecimiento::MODELO_GALONES_HORA => 'Galones por hora',
            ],
        ];
    }

    private function aplicarFiltrosAnaliticos(
        Builder $query,
        array $empresaIds,
        array $unidadIds,
        array $motoristaIds,
        array $modelosMedicion,
        ?string $fechaDesde,
        ?string $fechaHasta,
        string $busqueda
    ): void {
        if ($empresaIds !== []) {
            $query->whereIn('empresa_id', $empresaIds);
        }

        if ($unidadIds !== []) {
            $query->whereIn('unidad_id', $unidadIds);
        }

        if ($motoristaIds !== []) {
            $query->whereIn('motorista_id', $motoristaIds);
        }

        if ($modelosMedicion !== []) {
            $query->whereIn('modelo_medicion', $modelosMedicion);
        }

        if ($fechaDesde) {
            $query->where(
                'fecha_hora_abastecimiento',
                '>=',
                Carbon::createFromFormat('Y-m-d', $fechaDesde)->startOfDay()
            );
        }

        if ($fechaHasta) {
            $query->where(
                'fecha_hora_abastecimiento',
                '<',
                Carbon::createFromFormat('Y-m-d', $fechaHasta)
                    ->addDay()
                    ->startOfDay()
            );
        }

        if ($busqueda !== '') {
            $query->where(function (Builder $busquedaQuery) use ($busqueda): void {
                $termino = '%' . $busqueda . '%';

                $busquedaQuery
                    ->where('empresa_nombre_snapshot', 'like', $termino)
                    ->orWhere('unidad_placa_snapshot', 'like', $termino)
                    ->orWhere('unidad_marca_snapshot', 'like', $termino)
                    ->orWhere('unidad_modelo_snapshot', 'like', $termino)
                    ->orWhere('motorista_nombre_snapshot', 'like', $termino)
                    ->orWhere('motorista_licencia_snapshot', 'like', $termino)
                    ->orWhere('origen_nombre_snapshot', 'like', $termino)
                    ->orWhere('modelo_medicion', 'like', $termino)
                    ->orWhereHas('rutas', function (Builder $rutaQuery) use ($termino): void {
                        $rutaQuery
                            ->where('ruta_nombre_snapshot', 'like', $termino)
                            ->orWhere('punto_origen_nombre_snapshot', 'like', $termino)
                            ->orWhere('punto_destino_nombre_snapshot', 'like', $termino);
                    });

                if (ctype_digit($busqueda)) {
                    $busquedaQuery->orWhere('id', (int) $busqueda);
                }
            });
        }
    }

    private function prepararFilaAnalitica(
        Abastecimiento $abastecimiento
    ): Abastecimiento {
        $placa = $abastecimiento->unidad_placa_snapshot
            ?: ($abastecimiento->unidad?->placa ?: 'No disponible');

        $marca = $abastecimiento->unidad_marca_snapshot
            ?: ($abastecimiento->unidad?->marca ?: null);

        $abastecimiento->setAttribute(
            'numero_ciclo_analitico',
            (int) $abastecimiento->numero_ciclo_historico
        );

        $abastecimiento->setAttribute(
            'unidad_texto_analitico',
            collect([$placa, $marca])
                ->filter()
                ->implode(' · ')
        );

        $abastecimiento->setAttribute(
            'motorista_texto_analitico',
            $abastecimiento->motorista_nombre_snapshot
                ?: (
                    $abastecimiento->motorista
                        ? trim(
                            $abastecimiento->motorista->nombres
                            . ' '
                            . $abastecimiento->motorista->apellidos
                        )
                        : 'No disponible'
                )
        );

        $abastecimiento->setAttribute(
            'kilometraje_anterior_analitico',
            is_null($abastecimiento->kilometraje_anterior)
                ? null
                : (float) $abastecimiento->kilometraje_anterior
        );

        $abastecimiento->setAttribute(
            'kilometraje_actual_analitico',
            is_null($abastecimiento->kilometraje_actual)
                ? null
                : (float) $abastecimiento->kilometraje_actual
        );

        $kilometrosRecorridos = is_null(
            $abastecimiento->diferencia_kilometraje
        )
            ? null
            : (float) $abastecimiento->diferencia_kilometraje;

        $galonesAbastecidos = ! is_null(
            $abastecimiento->galones_abastecidos_ciclo
        )
            ? (float) $abastecimiento->galones_abastecidos_ciclo
            : (
                ! is_null(
                    $abastecimiento
                        ->abastecimientoAnterior
                        ?->volumen_cargado
                )
                    ? (float) $abastecimiento
                        ->abastecimientoAnterior
                        ->volumen_cargado
                    : null
            );

        $galonesUtilizados = is_null(
            $abastecimiento->combustible_consumido_ciclo
        )
            ? null
            : (float) $abastecimiento->combustible_consumido_ciclo;

        $kilometrosPorGalon = (
            ! is_null($kilometrosRecorridos)
            && ! is_null($galonesUtilizados)
            && $galonesUtilizados > 0
        )
            ? $kilometrosRecorridos / $galonesUtilizados
            : null;

        $abastecimiento->setAttribute(
            'kilometros_recorridos_analitico',
            $kilometrosRecorridos
        );

        $abastecimiento->setAttribute(
            'galones_abastecidos_analitico',
            $galonesAbastecidos
        );

        $abastecimiento->setAttribute(
            'galones_utilizados_analitico',
            $galonesUtilizados
        );

        $abastecimiento->setAttribute(
            'kilometros_por_galon_analitico',
            is_null($kilometrosPorGalon)
                ? null
                : round($kilometrosPorGalon, 2)
        );

        return $abastecimiento;
    }

    private function aplicarOrdenAnalitico(
        Builder $query,
        string $sort,
        string $direction
    ): void {
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        match ($sort) {
            'numero_ciclo' => $query
                ->orderBy('numero_ciclo_historico', $direction)
                ->orderBy('unidad_placa_snapshot')
                ->orderBy('id'),

            'unidad' => $query
                ->orderByRaw(
                    'COALESCE(unidad_placa_snapshot, "") '
                    . $direction
                )
                ->orderByRaw(
                    'COALESCE(unidad_marca_snapshot, "") '
                    . $direction
                )
                ->orderByDesc('fecha_hora_abastecimiento')
                ->orderByDesc('id'),

            'motorista' => $query
                ->orderByRaw(
                    'COALESCE(motorista_nombre_snapshot, "") '
                    . $direction
                )
                ->orderByDesc('fecha_hora_abastecimiento')
                ->orderByDesc('id'),

            'kilometros_recorridos' => $query
                ->orderBy('diferencia_kilometraje', $direction)
                ->orderByDesc('fecha_hora_abastecimiento')
                ->orderByDesc('id'),

            'galones_abastecidos' => $query
                ->orderBy('galones_abastecidos_ciclo', $direction)
                ->orderByDesc('fecha_hora_abastecimiento')
                ->orderByDesc('id'),

            'galones_utilizados' => $query
                ->orderBy('combustible_consumido_ciclo', $direction)
                ->orderByDesc('fecha_hora_abastecimiento')
                ->orderByDesc('id'),

            'kilometros_por_galon' => $query
                ->orderByRaw(
                    'CASE '
                    . 'WHEN combustible_consumido_ciclo IS NULL '
                    . 'OR combustible_consumido_ciclo <= 0 '
                    . 'THEN NULL '
                    . 'ELSE diferencia_kilometraje '
                    . '/ combustible_consumido_ciclo '
                    . 'END '
                    . $direction
                )
                ->orderByDesc('fecha_hora_abastecimiento')
                ->orderByDesc('id'),

            default => $query
                ->orderBy('fecha_hora_abastecimiento', $direction)
                ->orderBy('id', $direction),
        };
    }

    private function obtenerResumenAnalitico(Builder $base): array
    {
        $ciclos = (clone $base)
            ->whereNotNull('abastecimiento_anterior_id');

        $abastecimientos = (clone $base)->count();
        $ciclosCompletados = (clone $ciclos)->count();
        $galonesAbastecidos = (float) (
            (clone $base)->sum('volumen_cargado') ?? 0
        );
        $kilometrosRecorridos = (float) (
            (clone $ciclos)->sum('diferencia_kilometraje') ?? 0
        );
        $galonesConsumidos = (float) (
            (clone $ciclos)->sum('combustible_consumido_ciclo') ?? 0
        );

        $kilometrosPorGalon = $galonesConsumidos > 0
            ? $kilometrosRecorridos / $galonesConsumidos
            : null;

        return [
            'abastecimientos' => $abastecimientos,
            'ciclos_completados' => $ciclosCompletados,
            'galones_abastecidos' => $galonesAbastecidos,
            'kilometros_recorridos' => $kilometrosRecorridos,
            'galones_consumidos' => $galonesConsumidos,
            'kilometros_por_galon' => is_null($kilometrosPorGalon)
                ? null
                : round($kilometrosPorGalon, 2),

            /* Compatibilidad temporal con la vista anterior. */
            'comun' => [
                'registros' => $abastecimientos,
                'ciclos' => $ciclosCompletados,
                'lineas_base' => (clone $base)
                    ->whereNull('abastecimiento_anterior_id')
                    ->count(),
                'unidades' => (clone $base)
                    ->distinct()
                    ->count('unidad_id'),
                'motoristas' => (clone $base)
                    ->distinct()
                    ->count('motorista_id'),
                'galones_consumidos' => $galonesConsumidos,
                'ciclos_incompletos' => (clone $ciclos)
                    ->where(function (Builder $query): void {
                        $query
                            ->whereNull('combustible_consumido_ciclo')
                            ->orWhere(
                                'combustible_consumido_ciclo',
                                '<=',
                                0
                            );
                    })
                    ->count(),
            ],
            'viaje' => [],
            'kilometro' => [],
            'hora' => [],
        ];
    }


    private function obtenerDatosGraficos(
        Builder $base,
        array $unidadIds,
        Collection $unidadesSelector
    ): array {
        $ciclos = (clone $base)
            ->whereNotNull('abastecimiento_anterior_id')
            ->whereNotNull('diferencia_kilometraje')
            ->where('combustible_consumido_ciclo', '>', 0)
            ->orderBy('fecha_hora_abastecimiento')
            ->orderBy('id')
            ->get([
                'id',
                'unidad_id',
                'fecha_hora_abastecimiento',
                'diferencia_kilometraje',
                'combustible_consumido_ciclo',
            ]);

        if ($ciclos->isEmpty()) {
            return [
                'tiempo' => [
                    'agrupacion' => 'Sin datos',
                    'puntos' => [],
                ],
                'ciclos' => [
                    'disponible' => false,
                    'unidad' => null,
                    'mensaje' => 'No hay ciclos con información suficiente para graficar.',
                    'puntos' => [],
                ],
            ];
        }

        $primeraFecha = Carbon::parse(
            $ciclos->first()->fecha_hora_abastecimiento
        )->startOfDay();

        $ultimaFecha = Carbon::parse(
            $ciclos->last()->fecha_hora_abastecimiento
        )->startOfDay();

        $dias = $primeraFecha->diffInDays($ultimaFecha);

        $agrupacion = match (true) {
            $dias <= 45 => 'Día',
            $dias <= 180 => 'Semana',
            default => 'Mes',
        };

        $puntosTiempo = $ciclos
            ->groupBy(function (Abastecimiento $ciclo) use ($agrupacion): string {
                $fecha = Carbon::parse(
                    $ciclo->fecha_hora_abastecimiento
                );

                return match ($agrupacion) {
                    'Día' => $fecha->format('Y-m-d'),
                    'Semana' => $fecha
                        ->copy()
                        ->startOfWeek(Carbon::MONDAY)
                        ->format('Y-m-d'),
                    default => $fecha->format('Y-m-01'),
                };
            })
            ->sortKeys()
            ->map(function (Collection $grupo, string $clave) use ($agrupacion): array {
                $kilometros = (float) $grupo->sum('diferencia_kilometraje');
                $galones = (float) $grupo->sum('combustible_consumido_ciclo');
                $fecha = Carbon::parse($clave);

                $etiqueta = match ($agrupacion) {
                    'Día' => $fecha->format('d/m/Y'),
                    'Semana' => 'Sem. ' . $fecha->format('d/m/Y'),
                    default => $fecha->format('m/Y'),
                };

                return [
                    'etiqueta' => $etiqueta,
                    'valor' => $galones > 0
                        ? round($kilometros / $galones, 2)
                        : null,
                    'detalle' => number_format($kilometros, 1)
                        . ' km · '
                        . number_format($galones, 2)
                        . ' gal',
                ];
            })
            ->filter(
                fn (array $punto): bool => ! is_null($punto['valor'])
            )
            ->values()
            ->all();

        $graficoCiclos = [
            'disponible' => false,
            'unidad' => null,
            'mensaje' => 'Seleccione una sola unidad para visualizar su progreso por ciclo.',
            'puntos' => [],
        ];

        if (count($unidadIds) === 1) {
            $unidadId = (int) $unidadIds[0];

            $unidad = $unidadesSelector->first(
                fn (Unidad $unidad): bool =>
                    (int) $unidad->id === $unidadId
            );

            $secuenciaHistorica = Abastecimiento::query()
                ->registrados()
                ->where('unidad_id', $unidadId)
                ->whereNotNull('abastecimiento_anterior_id')
                ->orderBy('fecha_hora_abastecimiento')
                ->orderBy('id')
                ->pluck('id')
                ->values();

            $numeroPorId = $secuenciaHistorica
                ->flip()
                ->map(
                    fn (int $indice): int => $indice + 1
                );

            $puntosCiclo = $ciclos
                ->where('unidad_id', $unidadId)
                ->map(function (Abastecimiento $ciclo) use ($numeroPorId): array {
                    $galones = (float) $ciclo->combustible_consumido_ciclo;
                    $kilometros = (float) $ciclo->diferencia_kilometraje;
                    $numero = $numeroPorId->get($ciclo->id);

                    return [
                        'etiqueta' => is_null($numero)
                            ? 'Ciclo'
                            : 'Ciclo ' . $numero,
                        'valor' => $galones > 0
                            ? round($kilometros / $galones, 2)
                            : null,
                        'detalle' => Carbon::parse(
                            $ciclo->fecha_hora_abastecimiento
                        )->format('d/m/Y')
                            . ' · '
                            . number_format($kilometros, 1)
                            . ' km · '
                            . number_format($galones, 2)
                            . ' gal',
                    ];
                })
                ->filter(
                    fn (array $punto): bool => ! is_null($punto['valor'])
                )
                ->values()
                ->all();

            $nombreUnidad = $unidad
                ? collect([$unidad->placa, $unidad->marca])
                    ->filter()
                    ->implode(' · ')
                : 'Unidad seleccionada';

            $graficoCiclos = [
                'disponible' => true,
                'unidad' => $nombreUnidad,
                'mensaje' => $puntosCiclo === []
                    ? 'La unidad seleccionada no tiene ciclos con información suficiente.'
                    : null,
                'puntos' => $puntosCiclo,
            ];
        }

        return [
            'tiempo' => [
                'agrupacion' => $agrupacion,
                'puntos' => $puntosTiempo,
            ],
            'ciclos' => $graficoCiclos,
        ];
    }

    private function determinarTipoResumen(array $modelosMedicion): string
    {
        if (count($modelosMedicion) !== 1) {
            return 'mixto';
        }

        return match ($modelosMedicion[0]) {
            Abastecimiento::MODELO_GALONES_VIAJE => 'viaje',
            Abastecimiento::MODELO_KILOMETROS_GALON => 'kilometro',
            Abastecimiento::MODELO_GALONES_HORA => 'hora',
            default => 'mixto',
        };
    }

    private function obtenerEmpresasSelector(
        bool $esUsuarioDieselCop,
        ?Empresa $empresaUsuario
    ): Collection {
        if (! $esUsuarioDieselCop) {
            return collect([$empresaUsuario])->filter();
        }

        return Empresa::query()
            ->whereHas('abastecimientos', fn (Builder $query) => $query->registrados())
            ->orderByRaw('COALESCE(nombre_comercial, nombre_legal)')
            ->get();
    }

    private function obtenerUnidadesSelector(
        bool $esUsuarioDieselCop,
        int|string|null $empresaUsuarioId,
        array $empresaIds
    ): Collection {
        return Unidad::query()
            ->with('empresa')
            ->whereHas('abastecimientos', fn (Builder $query) => $query->registrados())
            ->when(
                ! $esUsuarioDieselCop,
                fn (Builder $query) => $query->where('empresa_id', $empresaUsuarioId)
            )
            ->when(
                $empresaIds !== [],
                fn (Builder $query) => $query->whereIn('empresa_id', $empresaIds)
            )
            ->orderBy('empresa_id')
            ->orderBy('placa')
            ->get();
    }

    private function obtenerMotoristasSelector(
        bool $esUsuarioDieselCop,
        int|string|null $empresaUsuarioId,
        array $empresaIds
    ): Collection {
        return Motorista::query()
            ->with('empresa')
            ->whereHas('abastecimientos', fn (Builder $query) => $query->registrados())
            ->when(
                ! $esUsuarioDieselCop,
                fn (Builder $query) => $query->where('empresa_id', $empresaUsuarioId)
            )
            ->when(
                $empresaIds !== [],
                fn (Builder $query) => $query->whereIn('empresa_id', $empresaIds)
            )
            ->orderBy('empresa_id')
            ->orderBy('nombres')
            ->orderBy('apellidos')
            ->get();
    }

    private function normalizarIdsSeleccionados(
        array $ids,
        mixed $idIndividual = null
    ): array {
        if ($ids === [] && ! is_null($idIndividual) && $idIndividual !== '') {
            $ids = [$idIndividual];
        }

        return collect($ids)
            ->filter(fn ($id): bool => filter_var($id, FILTER_VALIDATE_INT) !== false)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function normalizarModelosSeleccionados(
        array $modelos,
        mixed $modeloIndividual = null
    ): array {
        if ($modelos === [] && is_string($modeloIndividual) && $modeloIndividual !== '') {
            $modelos = [$modeloIndividual];
        }

        $permitidos = [
            Abastecimiento::MODELO_KILOMETROS_GALON,
            Abastecimiento::MODELO_GALONES_HORA,
            Abastecimiento::MODELO_GALONES_VIAJE,
        ];

        return collect($modelos)
            ->filter(
                fn ($modelo): bool => is_string($modelo)
                    && in_array($modelo, $permitidos, true)
            )
            ->unique()
            ->values()
            ->all();
    }

    private function filtrarIdsPermitidos(
        array $ids,
        Collection $idsPermitidos
    ): array {
        $permitidos = $idsPermitidos
            ->map(fn ($id): int => (int) $id)
            ->all();

        return collect($ids)
            ->filter(fn ($id): bool => in_array((int) $id, $permitidos, true))
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }
}