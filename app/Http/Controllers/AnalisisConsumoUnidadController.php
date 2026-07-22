<?php

namespace App\Http\Controllers;

use App\Models\Abastecimiento;
use App\Models\Empresa;
use App\Models\Unidad;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnalisisConsumoUnidadController extends Controller
{
    public function index(Request $request): View
    {
        return view(
            'analisis-consumo-unidades.index',
            $this->prepararAnalisis($request)
        );
    }

    public function indexVentana(Request $request): View
    {
        return view(
            'analisis-consumo-unidades.index-ventana',
            $this->prepararAnalisis($request)
        );
    }

    private function prepararAnalisis(Request $request): array
    {
        $user = Auth::user();
        $esUsuarioDieselCop = is_null($user->empresa_id);

        $validated = $request->validate(
            [
                'empresa_ids' => ['nullable', 'array'],
                'empresa_ids.*' => [
                    'integer',
                    'distinct',
                    'exists:empresas,id',
                ],
                'unidad_ids' => ['nullable', 'array'],
                'unidad_ids.*' => [
                    'integer',
                    'distinct',
                    'exists:unidades,id',
                ],
                'modelos_medicion' => ['nullable', 'array'],
                'modelos_medicion.*' => [
                    'string',
                    'distinct',
                    Rule::in($this->modelosPermitidos()),
                ],
                'fecha_desde' => [
                    'nullable',
                    'date_format:Y-m-d',
                ],
                'fecha_hasta' => [
                    'nullable',
                    'date_format:Y-m-d',
                    'after_or_equal:fecha_desde',
                ],
                'busqueda' => [
                    'nullable',
                    'string',
                    'max:150',
                ],
                'sort' => [
                    'nullable',
                    Rule::in([
                        'empresa',
                        'unidad',
                        'modelo',
                        'ciclos',
                        'galones',
                        'actividad',
                        'rendimiento',
                        'ultimo_ciclo',
                    ]),
                ],
                'direction' => [
                    'nullable',
                    Rule::in(['asc', 'desc']),
                ],
                'page' => [
                    'nullable',
                    'integer',
                    'min:1',
                ],
            ],
            [
                'empresa_ids.array' =>
                    'La selección de empresas no es válida.',
                'empresa_ids.*.exists' =>
                    'Una de las empresas seleccionadas no existe.',
                'unidad_ids.array' =>
                    'La selección de unidades no es válida.',
                'unidad_ids.*.exists' =>
                    'Una de las unidades seleccionadas no existe.',
                'modelos_medicion.array' =>
                    'La selección de modelos no es válida.',
                'modelos_medicion.*.in' =>
                    'Uno de los modelos seleccionados no es válido.',
                'fecha_hasta.after_or_equal' =>
                    'La fecha final no puede ser anterior a la inicial.',
            ]
        );

        $empresaIds = $this->normalizarIds(
            $validated['empresa_ids'] ?? []
        );

        $unidadIds = $this->normalizarIds(
            $validated['unidad_ids'] ?? []
        );

        $modelosMedicion = $this->normalizarModelos(
            $validated['modelos_medicion'] ?? []
        );

        if (! $esUsuarioDieselCop) {
            $empresaIds = [(int) $user->empresa_id];
        }

        $fechaDesde = $validated['fecha_desde'] ?? null;
        $fechaHasta = $validated['fecha_hasta'] ?? null;
        $busqueda = trim(
            (string) ($validated['busqueda'] ?? '')
        );
        $sort = (string) ($validated['sort'] ?? 'unidad');
        $direction = (string) (
            $validated['direction'] ?? 'asc'
        );

        $hayFiltros = ! $esUsuarioDieselCop
            || $request->hasAny([
                'empresa_ids',
                'unidad_ids',
                'modelos_medicion',
                'fecha_desde',
                'fecha_hasta',
                'busqueda',
                'consultar',
            ]);

        $empresasSelector = $this->obtenerEmpresasSelector(
            $esUsuarioDieselCop,
            $user->empresa_id
        );

        $unidadesSelector = $this->obtenerUnidadesSelector(
            $esUsuarioDieselCop,
            $user->empresa_id,
            $empresaIds
        );

        $unidadIds = $this->filtrarIdsPermitidos(
            $unidadIds,
            $unidadesSelector->pluck('id')
        );

        $ciclos = collect();

        if ($hayFiltros) {
            $query = Abastecimiento::query()
                ->registrados()
                ->whereNotNull('abastecimiento_anterior_id')
                ->with([
                    'empresa',
                    'unidad',
                ]);

            if (! $esUsuarioDieselCop) {
                $query->where(
                    'empresa_id',
                    $user->empresa_id
                );
            }

            if ($empresaIds !== []) {
                $query->whereIn(
                    'empresa_id',
                    $empresaIds
                );
            }

            if ($unidadIds !== []) {
                $query->whereIn(
                    'unidad_id',
                    $unidadIds
                );
            }

            if ($modelosMedicion !== []) {
                $query->whereIn(
                    'modelo_medicion',
                    $modelosMedicion
                );
            }

            if ($fechaDesde) {
                $query->where(
                    'fecha_hora_abastecimiento',
                    '>=',
                    Carbon::createFromFormat(
                        'Y-m-d',
                        $fechaDesde
                    )->startOfDay()
                );
            }

            if ($fechaHasta) {
                $query->where(
                    'fecha_hora_abastecimiento',
                    '<',
                    Carbon::createFromFormat(
                        'Y-m-d',
                        $fechaHasta
                    )->addDay()->startOfDay()
                );
            }

            if ($busqueda !== '') {
                $termino = '%' . $busqueda . '%';

                $query->where(
                    function ($busquedaQuery) use ($termino): void {
                        $busquedaQuery
                            ->where(
                                'empresa_nombre_snapshot',
                                'like',
                                $termino
                            )
                            ->orWhere(
                                'unidad_placa_snapshot',
                                'like',
                                $termino
                            )
                            ->orWhere(
                                'unidad_marca_snapshot',
                                'like',
                                $termino
                            )
                            ->orWhere(
                                'unidad_modelo_snapshot',
                                'like',
                                $termino
                            )
                            ->orWhere(
                                'modelo_medicion',
                                'like',
                                $termino
                            );
                    }
                );
            }

            $ciclos = $query
                ->orderBy('fecha_hora_abastecimiento')
                ->orderBy('id')
                ->get();
        }

        $unidadesAnalizadas = $this->consolidarPorUnidad(
            $ciclos
        );

        $unidadesAnalizadas = $this->ordenarResultados(
            $unidadesAnalizadas,
            $sort,
            $direction
        );

        $paginador = $this->paginarColeccion(
            $unidadesAnalizadas,
            $request,
            20
        );

        $resumen = $this->obtenerResumen(
            $unidadesAnalizadas
        );

        $graficos = $this->obtenerGraficos(
            $unidadesAnalizadas
        );

        return [
            'unidadesAnalizadas' => $paginador,
            'empresasSelector' => $empresasSelector,
            'unidadesSelector' => $unidadesSelector,
            'empresaIds' => $empresaIds,
            'unidadIds' => $unidadIds,
            'modelosMedicion' => $modelosMedicion,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'busqueda' => $busqueda,
            'sort' => $sort,
            'direction' => $direction,
            'hayFiltros' => $hayFiltros,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'resumen' => $resumen,
            'graficos' => $graficos,
            'opcionesModelos' => [
                Abastecimiento::MODELO_GALONES_KILOMETRO =>
                    'Kilómetros por galón',
                Abastecimiento::MODELO_GALONES_HORA =>
                    'Galones por hora',
                Abastecimiento::MODELO_GALONES_VIAJE =>
                    'Galones por viaje',
            ],
        ];
    }

    private function consolidarPorUnidad(
        Collection $ciclos
    ): Collection {
        return $ciclos
            ->groupBy('unidad_id')
            ->map(
                function (
                    Collection $grupo,
                    int|string $unidadId
                ): array {
                    $ordenados = $grupo
                        ->sortBy([
                            ['fecha_hora_abastecimiento', 'asc'],
                            ['id', 'asc'],
                        ])
                        ->values();

                    $primero = $ordenados->first();
                    $ultimo = $ordenados->last();

                    $modelo = (string) (
                        $ultimo->modelo_medicion
                        ?: $primero->modelo_medicion
                    );

                    $galones = (float) $ordenados
                        ->sum('combustible_consumido_ciclo');

                    $actividad = match ($modelo) {
                        Abastecimiento::MODELO_GALONES_HORA =>
                            (float) $ordenados
                                ->sum('diferencia_horometro'),

                        Abastecimiento::MODELO_GALONES_VIAJE =>
                            (float) $ordenados
                                ->sum('total_rutas'),

                        default =>
                            (float) $ordenados
                                ->sum('diferencia_kilometraje'),
                    };

                    $resultadosCiclo = $ordenados
                        ->map(
                            fn (
                                Abastecimiento $ciclo
                            ): ?float =>
                                $this->calcularResultadoCiclo(
                                    $ciclo,
                                    $modelo
                                )
                        )
                        ->filter(
                            fn (?float $valor): bool =>
                                ! is_null($valor)
                        )
                        ->values();

                    $rendimiento = $this
                        ->calcularRendimientoConsolidado(
                            $modelo,
                            $actividad,
                            $galones
                        );

                    $placa = $ultimo
                        ->unidad_placa_snapshot
                        ?: $ultimo->unidad?->placa
                        ?: 'No disponible';

                    $marca = $ultimo
                        ->unidad_marca_snapshot
                        ?: $ultimo->unidad?->marca;

                    $modeloUnidad = $ultimo
                        ->unidad_modelo_snapshot
                        ?: $ultimo->unidad?->modelo;

                    $empresa = $ultimo
                        ->empresa_nombre_snapshot
                        ?: $ultimo->empresa?->nombre_comercial
                        ?: $ultimo->empresa?->nombre_legal
                        ?: 'No disponible';

                    return [
                        'unidad_id' => (int) $unidadId,
                        'empresa_id' => (int) $ultimo->empresa_id,
                        'empresa' => $empresa,
                        'placa' => $placa,
                        'unidad' => collect([
                            $placa,
                            $marca,
                            $modeloUnidad,
                        ])->filter()->implode(' · '),
                        'modelo_medicion' => $modelo,
                        'modelo_etiqueta' =>
                            $this->etiquetaModelo($modelo),
                        'unidad_actividad' =>
                            $this->unidadActividad($modelo),
                        'unidad_resultado' =>
                            $this->unidadResultado($modelo),
                        'ciclos' => $ordenados->count(),
                        'galones_consumidos' => round(
                            $galones,
                            2
                        ),
                        'actividad_total' => round(
                            $actividad,
                            2
                        ),
                        'rendimiento_promedio' => is_null(
                            $rendimiento
                        )
                            ? null
                            : round($rendimiento, 2),
                        'mejor_resultado' =>
                            $resultadosCiclo->isEmpty()
                                ? null
                                : round(
                                    (float) $this
                                        ->mejorResultado(
                                            $resultadosCiclo,
                                            $modelo
                                        ),
                                    2
                                ),
                        'peor_resultado' =>
                            $resultadosCiclo->isEmpty()
                                ? null
                                : round(
                                    (float) $this
                                        ->peorResultado(
                                            $resultadosCiclo,
                                            $modelo
                                        ),
                                    2
                                ),
                        'ultimo_resultado' =>
                            $this->calcularResultadoCiclo(
                                $ultimo,
                                $modelo
                            ),
                        'fecha_ultimo_ciclo' =>
                            $ultimo
                                ->fecha_hora_abastecimiento,
                        'tendencia' =>
                            $this->determinarTendencia(
                                $resultadosCiclo,
                                $modelo
                            ),
                    ];
                }
            )
            ->values();
    }

    private function calcularResultadoCiclo(
        Abastecimiento $ciclo,
        string $modelo
    ): ?float {
        $galones = (float) (
            $ciclo->combustible_consumido_ciclo ?? 0
        );

        if ($galones <= 0) {
            return null;
        }

        return match ($modelo) {
            Abastecimiento::MODELO_GALONES_HORA =>
                (float) ($ciclo->diferencia_horometro ?? 0) > 0
                    ? round(
                        $galones
                        / (float) $ciclo->diferencia_horometro,
                        6
                    )
                    : null,

            Abastecimiento::MODELO_GALONES_VIAJE =>
                (int) ($ciclo->total_rutas ?? 0) > 0
                    ? round(
                        $galones
                        / (int) $ciclo->total_rutas,
                        6
                    )
                    : null,

            default =>
                (float) ($ciclo->diferencia_kilometraje ?? 0) > 0
                    ? round(
                        (float) $ciclo->diferencia_kilometraje
                        / $galones,
                        6
                    )
                    : null,
        };
    }

    private function calcularRendimientoConsolidado(
        string $modelo,
        float $actividad,
        float $galones
    ): ?float {
        if ($actividad <= 0 || $galones <= 0) {
            return null;
        }

        return match ($modelo) {
            Abastecimiento::MODELO_GALONES_HORA,
            Abastecimiento::MODELO_GALONES_VIAJE =>
                $galones / $actividad,

            default =>
                $actividad / $galones,
        };
    }

    private function mejorResultado(
        Collection $resultados,
        string $modelo
    ): float {
        return $modelo
            === Abastecimiento::MODELO_GALONES_KILOMETRO
                ? (float) $resultados->max()
                : (float) $resultados->min();
    }

    private function peorResultado(
        Collection $resultados,
        string $modelo
    ): float {
        return $modelo
            === Abastecimiento::MODELO_GALONES_KILOMETRO
                ? (float) $resultados->min()
                : (float) $resultados->max();
    }

    private function determinarTendencia(
        Collection $resultados,
        string $modelo
    ): string {
        if ($resultados->count() < 2) {
            return 'sin_historial';
        }

        $anterior = (float) $resultados[
            $resultados->count() - 2
        ];

        $actual = (float) $resultados->last();

        if (abs($actual - $anterior) < 0.01) {
            return 'estable';
        }

        $mejora = $modelo
            === Abastecimiento::MODELO_GALONES_KILOMETRO
                ? $actual > $anterior
                : $actual < $anterior;

        return $mejora ? 'mejora' : 'deterioro';
    }

    private function obtenerResumen(
        Collection $unidades
    ): array {
        $galones = (float) $unidades
            ->sum('galones_consumidos');

        return [
            'unidades' => $unidades->count(),
            'ciclos' => (int) $unidades->sum('ciclos'),
            'galones_consumidos' => round($galones, 2),
            'unidades_mejora' => $unidades
                ->where('tendencia', 'mejora')
                ->count(),
            'unidades_estables' => $unidades
                ->where('tendencia', 'estable')
                ->count(),
            'unidades_deterioro' => $unidades
                ->where('tendencia', 'deterioro')
                ->count(),
        ];
    }

    private function obtenerGraficos(
        Collection $unidades
    ): array {
        return [
            'comparacion' => $unidades
                ->filter(
                    fn (array $fila): bool =>
                        ! is_null(
                            $fila['rendimiento_promedio']
                        )
                )
                ->sortByDesc('galones_consumidos')
                ->take(12)
                ->map(
                    fn (array $fila): array => [
                        'etiqueta' => $fila['placa'],
                        'valor' =>
                            $fila['rendimiento_promedio'],
                        'unidad' =>
                            $fila['unidad_resultado'],
                        'modelo' =>
                            $fila['modelo_etiqueta'],
                    ]
                )
                ->values()
                ->all(),
            'consumo' => $unidades
                ->sortByDesc('galones_consumidos')
                ->take(12)
                ->map(
                    fn (array $fila): array => [
                        'etiqueta' => $fila['placa'],
                        'valor' =>
                            $fila['galones_consumidos'],
                        'unidad' => 'gal',
                    ]
                )
                ->values()
                ->all(),
        ];
    }

    private function ordenarResultados(
        Collection $resultados,
        string $sort,
        string $direction
    ): Collection {
        $desc = $direction === 'desc';

        $ordenados = $resultados->sortBy(
            match ($sort) {
                'empresa' => 'empresa',
                'modelo' => 'modelo_etiqueta',
                'ciclos' => 'ciclos',
                'galones' => 'galones_consumidos',
                'actividad' => 'actividad_total',
                'rendimiento' => 'rendimiento_promedio',
                'ultimo_ciclo' => 'fecha_ultimo_ciclo',
                default => 'unidad',
            },
            SORT_NATURAL | SORT_FLAG_CASE,
            $desc
        );

        return $ordenados->values();
    }

    private function paginarColeccion(
        Collection $resultados,
        Request $request,
        int $porPagina
    ): LengthAwarePaginator {
        $pagina = max(
            1,
            (int) $request->integer('page', 1)
        );

        return new LengthAwarePaginator(
            $resultados
                ->forPage($pagina, $porPagina)
                ->values(),
            $resultados->count(),
            $porPagina,
            $pagina,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    private function obtenerEmpresasSelector(
        bool $esUsuarioDieselCop,
        int|string|null $empresaUsuarioId
    ): Collection {
        if (! $esUsuarioDieselCop) {
            return Empresa::query()
                ->whereKey($empresaUsuarioId)
                ->get();
        }

        return Empresa::query()
            ->whereHas(
                'abastecimientos',
                fn ($query) => $query->registrados()
            )
            ->orderByRaw(
                'COALESCE(nombre_comercial, nombre_legal)'
            )
            ->get();
    }

    private function obtenerUnidadesSelector(
        bool $esUsuarioDieselCop,
        int|string|null $empresaUsuarioId,
        array $empresaIds
    ): Collection {
        return Unidad::query()
            ->with('empresa')
            ->whereHas(
                'abastecimientos',
                fn ($query) => $query->registrados()
            )
            ->when(
                ! $esUsuarioDieselCop,
                fn ($query) => $query->where(
                    'empresa_id',
                    $empresaUsuarioId
                )
            )
            ->when(
                $empresaIds !== [],
                fn ($query) => $query->whereIn(
                    'empresa_id',
                    $empresaIds
                )
            )
            ->orderBy('empresa_id')
            ->orderBy('placa')
            ->get();
    }

    private function modelosPermitidos(): array
    {
        return [
            Abastecimiento::MODELO_GALONES_KILOMETRO,
            Abastecimiento::MODELO_GALONES_HORA,
            Abastecimiento::MODELO_GALONES_VIAJE,
        ];
    }

    private function normalizarIds(array $ids): array
    {
        return collect($ids)
            ->filter(
                fn ($id): bool =>
                    filter_var(
                        $id,
                        FILTER_VALIDATE_INT
                    ) !== false
            )
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function normalizarModelos(
        array $modelos
    ): array {
        $permitidos = $this->modelosPermitidos();

        return collect($modelos)
            ->filter(
                fn ($modelo): bool =>
                    is_string($modelo)
                    && in_array(
                        $modelo,
                        $permitidos,
                        true
                    )
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
            ->filter(
                fn ($id): bool =>
                    in_array(
                        (int) $id,
                        $permitidos,
                        true
                    )
            )
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    private function etiquetaModelo(
        string $modelo
    ): string {
        return match ($modelo) {
            Abastecimiento::MODELO_GALONES_HORA =>
                'Galones por hora',
            Abastecimiento::MODELO_GALONES_VIAJE =>
                'Galones por viaje',
            default =>
                'Kilómetros por galón',
        };
    }

    private function unidadActividad(
        string $modelo
    ): string {
        return match ($modelo) {
            Abastecimiento::MODELO_GALONES_HORA => 'h',
            Abastecimiento::MODELO_GALONES_VIAJE =>
                'viajes',
            default => 'km',
        };
    }

    private function unidadResultado(
        string $modelo
    ): string {
        return match ($modelo) {
            Abastecimiento::MODELO_GALONES_HORA =>
                'gal/h',
            Abastecimiento::MODELO_GALONES_VIAJE =>
                'gal/viaje',
            default =>
                'km/gal',
        };
    }
}