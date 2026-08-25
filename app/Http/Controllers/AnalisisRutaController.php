<?php

namespace App\Http\Controllers;

use App\Models\Abastecimiento;
use App\Models\AbastecimientoRuta;
use App\Models\Empresa;
use App\Models\Motorista;
use App\Models\PuntoRuta;
use App\Models\Ruta;
use App\Models\Unidad;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnalisisRutaController extends Controller
{
    private const TOLERANCIA_PORCENTAJE = 5.0;

    private const ESTADO_DENTRO =
        'dentro_estimacion';

    private const ESTADO_SUPERIOR =
        'consumo_superior';

    private const ESTADO_INFERIOR =
        'consumo_inferior';

    private const ESTADO_SIN_COMPARACION =
        'sin_comparacion';

    public function index(Request $request): View
    {
        return view(
            'analisis-rutas.index',
            $this->prepararAnalisis($request)
        );
    }

    public function indexVentana(Request $request): View
    {
        return view(
            'analisis-rutas.index-ventana',
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

                'motorista_ids' => ['nullable', 'array'],
                'motorista_ids.*' => [
                    'integer',
                    'distinct',
                    'exists:motoristas,id',
                ],

                'ruta_ids' => ['nullable', 'array'],
                'ruta_ids.*' => [
                    'integer',
                    'distinct',
                    'exists:rutas,id',
                ],

                'punto_origen_ids' => ['nullable', 'array'],
                'punto_origen_ids.*' => [
                    'integer',
                    'distinct',
                    'exists:puntos_ruta,id',
                ],

                'punto_destino_ids' => ['nullable', 'array'],
                'punto_destino_ids.*' => [
                    'integer',
                    'distinct',
                    'exists:puntos_ruta,id',
                ],

                'tipos_recorrido' => ['nullable', 'array'],
                'tipos_recorrido.*' => [
                    'string',
                    'distinct',
                    Rule::in([
                        AbastecimientoRuta::TIPO_IDA,
                        AbastecimientoRuta::TIPO_IDA_VUELTA,
                    ]),
                ],

                'estados_comparacion' => ['nullable', 'array'],
                'estados_comparacion.*' => [
                    'string',
                    'distinct',
                    Rule::in([
                        self::ESTADO_DENTRO,
                        self::ESTADO_SUPERIOR,
                        self::ESTADO_INFERIOR,
                        self::ESTADO_SIN_COMPARACION,
                    ]),
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
                        'fecha',
                        'empresa',
                        'unidad',
                        'motorista',
                        'rutas',
                        'kilometros_teoricos',
                        'galones_teoricos',
                        'galones_consumidos',
                        'variacion_galones',
                        'variacion_porcentaje',
                        'diagnostico',
                    ]),
                ],

                'direction' => [
                    'nullable',
                    Rule::in(['asc', 'desc']),
                ],

                /*
                |--------------------------------------------------------------------------
                | Compatibilidad con filtros individuales
                |--------------------------------------------------------------------------
                */

                'empresa_id' => [
                    'nullable',
                    'integer',
                    'exists:empresas,id',
                ],

                'unidad_id' => [
                    'nullable',
                    'integer',
                    'exists:unidades,id',
                ],

                'motorista_id' => [
                    'nullable',
                    'integer',
                    'exists:motoristas,id',
                ],

                'ruta_id' => [
                    'nullable',
                    'integer',
                    'exists:rutas,id',
                ],
            ],
            [
                'fecha_hasta.after_or_equal' =>
                    'La fecha final no puede ser anterior a la fecha inicial.',

                'empresa_ids.*.exists' =>
                    'Una de las empresas seleccionadas no existe.',

                'unidad_ids.*.exists' =>
                    'Una de las unidades seleccionadas no existe.',

                'motorista_ids.*.exists' =>
                    'Uno de los motoristas seleccionados no existe.',

                'ruta_ids.*.exists' =>
                    'Una de las rutas seleccionadas no existe.',

                'tipos_recorrido.*.in' =>
                    'Uno de los tipos de recorrido seleccionados no es válido.',

                'estados_comparacion.*.in' =>
                    'Uno de los estados de comparación seleccionados no es válido.',
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

        $rutaIds = $this->normalizarIdsSeleccionados(
            $validated['ruta_ids'] ?? [],
            $validated['ruta_id'] ?? null
        );

        $puntoOrigenIds = $this->normalizarIdsSeleccionados(
            $validated['punto_origen_ids'] ?? []
        );

        $puntoDestinoIds = $this->normalizarIdsSeleccionados(
            $validated['punto_destino_ids'] ?? []
        );

        $tiposRecorrido = $this->normalizarValoresSeleccionados(
            $validated['tipos_recorrido'] ?? [],
            [
                AbastecimientoRuta::TIPO_IDA,
                AbastecimientoRuta::TIPO_IDA_VUELTA,
            ]
        );

        $estadosComparacion = $this->normalizarValoresSeleccionados(
            $validated['estados_comparacion'] ?? [],
            [
                self::ESTADO_DENTRO,
                self::ESTADO_SUPERIOR,
                self::ESTADO_INFERIOR,
                self::ESTADO_SIN_COMPARACION,
            ]
        );

        if (! $esUsuarioDieselCop) {
            $empresaIds = [(int) $user->empresa_id];
        }

        $fechaDesde = $validated['fecha_desde'] ?? null;
        $fechaHasta = $validated['fecha_hasta'] ?? null;
        $busqueda = trim((string) ($validated['busqueda'] ?? ''));

        $sort = (string) ($validated['sort'] ?? 'fecha');
        $direction = (string) ($validated['direction'] ?? 'desc');

        /*
        |--------------------------------------------------------------------------
        | Estado inicial
        |--------------------------------------------------------------------------
        |
        | La página no ejecuta el análisis hasta que el usuario presiona
        | Consultar o llega mediante una URL que ya contiene filtros.
        |
        */

        $hayFiltros = $request->hasAny([
            'empresa_ids',
            'empresa_id',
            'unidad_ids',
            'unidad_id',
            'motorista_ids',
            'motorista_id',
            'ruta_ids',
            'ruta_id',
            'punto_origen_ids',
            'punto_destino_ids',
            'tipos_recorrido',
            'estados_comparacion',
            'fecha_desde',
            'fecha_hasta',
            'busqueda',
            'consultar',
            'sort',
            'direction',
            'page',
        ]);

        $consultaBase = Abastecimiento::query()
            ->registrados()
            ->whereNotNull('abastecimiento_anterior_id')
            ->where('total_rutas', '>', 0)
            ->whereHas('rutas');

        if (! $esUsuarioDieselCop) {
            $consultaBase->where(
                'empresa_id',
                $user->empresa_id
            );
        }

        if (! $hayFiltros) {
            $consultaBase->whereRaw('1 = 0');
        } else {
            $this->aplicarFiltrosAnaliticos(
                $consultaBase,
                $empresaIds,
                $unidadIds,
                $motoristaIds,
                $rutaIds,
                $puntoOrigenIds,
                $puntoDestinoIds,
                $tiposRecorrido,
                $estadosComparacion,
                $fechaDesde,
                $fechaHasta,
                $busqueda
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Resultados
        |--------------------------------------------------------------------------
        */

        $queryResultados = clone $consultaBase;

        $queryResultados
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
                        ->from('abastecimiento_rutas')
                        ->select('ruta_nombre_snapshot')
                        ->whereColumn(
                            'abastecimiento_rutas.abastecimiento_id',
                            'abastecimientos.id'
                        )
                        ->orderBy('orden')
                        ->limit(1);
                },
                'primera_ruta_nombre'
            )
            ->with([
                'empresa',
                'unidad',
                'motorista',
                'rutas',
            ]);

        $this->aplicarOrdenAnalitico(
            $queryResultados,
            $sort,
            $direction
        );

        $abastecimientos = $queryResultados
            ->paginate(10)
            ->withQueryString();

        $abastecimientos
            ->getCollection()
            ->transform(
                fn (Abastecimiento $abastecimiento): Abastecimiento =>
                    $this->prepararFilaAnalitica($abastecimiento)
            );

        /*
        |--------------------------------------------------------------------------
        | Selectores
        |--------------------------------------------------------------------------
        */

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

        $rutasSelector = $this->obtenerRutasSelector(
            $esUsuarioDieselCop,
            $user->empresa_id,
            $empresaIds
        );

        $puntosSelector = $this->obtenerPuntosSelector(
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

        $rutaIds = $this->filtrarIdsPermitidos(
            $rutaIds,
            $rutasSelector->pluck('id')
        );

        $puntoOrigenIds = $this->filtrarIdsPermitidos(
            $puntoOrigenIds,
            $puntosSelector->pluck('id')
        );

        $puntoDestinoIds = $this->filtrarIdsPermitidos(
            $puntoDestinoIds,
            $puntosSelector->pluck('id')
        );

        /*
        |--------------------------------------------------------------------------
        | Resumen y gráficos
        |--------------------------------------------------------------------------
        */

        $resumen = $this->obtenerResumenAnalitico(
            clone $consultaBase
        );

        $graficos = $this->obtenerDatosGraficos(
            clone $consultaBase
        );

        return [
            'abastecimientos' => $abastecimientos,

            'empresasSelector' => $empresasSelector,
            'unidadesSelector' => $unidadesSelector,
            'motoristasSelector' => $motoristasSelector,
            'rutasSelector' => $rutasSelector,
            'puntosSelector' => $puntosSelector,

            'empresaIds' => $empresaIds,
            'unidadIds' => $unidadIds,
            'motoristaIds' => $motoristaIds,
            'rutaIds' => $rutaIds,
            'puntoOrigenIds' => $puntoOrigenIds,
            'puntoDestinoIds' => $puntoDestinoIds,
            'tiposRecorrido' => $tiposRecorrido,
            'estadosComparacion' => $estadosComparacion,

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

            'toleranciaPorcentaje' =>
                self::TOLERANCIA_PORCENTAJE,

            'opcionesTiposRecorrido' => [
                AbastecimientoRuta::TIPO_IDA =>
                    'Ida',

                AbastecimientoRuta::TIPO_IDA_VUELTA =>
                    'Ida y vuelta',
            ],

            'opcionesEstadosComparacion' => [
                self::ESTADO_DENTRO =>
                    'Dentro de estimación',

                self::ESTADO_SUPERIOR =>
                    'Consumo superior',

                self::ESTADO_INFERIOR =>
                    'Consumo inferior',

                self::ESTADO_SIN_COMPARACION =>
                    'Sin comparación',
            ],
        ];
    }

    private function aplicarFiltrosAnaliticos(
        Builder $query,
        array $empresaIds,
        array $unidadIds,
        array $motoristaIds,
        array $rutaIds,
        array $puntoOrigenIds,
        array $puntoDestinoIds,
        array $tiposRecorrido,
        array $estadosComparacion,
        ?string $fechaDesde,
        ?string $fechaHasta,
        string $busqueda
    ): void {
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

        if ($motoristaIds !== []) {
            $query->whereIn(
                'motorista_id',
                $motoristaIds
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
                )
                    ->addDay()
                    ->startOfDay()
            );
        }

        if (
            $rutaIds !== []
            || $puntoOrigenIds !== []
            || $puntoDestinoIds !== []
            || $tiposRecorrido !== []
        ) {
            $query->whereHas(
                'rutas',
                function (Builder $rutaQuery) use (
                    $rutaIds,
                    $puntoOrigenIds,
                    $puntoDestinoIds,
                    $tiposRecorrido
                ): void {
                    if ($rutaIds !== []) {
                        $rutaQuery->whereIn(
                            'ruta_id',
                            $rutaIds
                        );
                    }

                    if ($puntoOrigenIds !== []) {
                        $rutaQuery->whereIn(
                            'punto_origen_id',
                            $puntoOrigenIds
                        );
                    }

                    if ($puntoDestinoIds !== []) {
                        $rutaQuery->whereIn(
                            'punto_destino_id',
                            $puntoDestinoIds
                        );
                    }

                    if ($tiposRecorrido !== []) {
                        $rutaQuery->whereIn(
                            'tipo_recorrido',
                            $tiposRecorrido
                        );
                    }
                }
            );
        }

        if ($estadosComparacion !== []) {
            $this->aplicarFiltroEstadosComparacion(
                $query,
                $estadosComparacion
            );
        }

        if ($busqueda !== '') {
            $query->where(
                function (Builder $busquedaQuery) use (
                    $busqueda
                ): void {
                    $termino = '%' . $busqueda . '%';

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
                            'motorista_nombre_snapshot',
                            'like',
                            $termino
                        )
                        ->orWhere(
                            'origen_nombre_snapshot',
                            'like',
                            $termino
                        )
                        ->orWhereHas(
                            'rutas',
                            function (
                                Builder $rutaQuery
                            ) use ($termino): void {
                                $rutaQuery
                                    ->where(
                                        'ruta_nombre_snapshot',
                                        'like',
                                        $termino
                                    )
                                    ->orWhere(
                                        'punto_origen_nombre_snapshot',
                                        'like',
                                        $termino
                                    )
                                    ->orWhere(
                                        'punto_destino_nombre_snapshot',
                                        'like',
                                        $termino
                                    );
                            }
                        );

                    if (ctype_digit($busqueda)) {
                        $busquedaQuery->orWhere(
                            'id',
                            (int) $busqueda
                        );
                    }
                }
            );
        }
    }

    private function aplicarFiltroEstadosComparacion(
        Builder $query,
        array $estadosComparacion
    ): void {
        $tolerancia = self::TOLERANCIA_PORCENTAJE;

        $query->where(
            function (Builder $estadoQuery) use (
                $estadosComparacion,
                $tolerancia
            ): void {
                foreach (
                    $estadosComparacion
                    as $estado
                ) {
                    $estadoQuery->orWhere(
                        function (Builder $caso) use (
                            $estado,
                            $tolerancia
                        ): void {
                            match ($estado) {
                                self::ESTADO_DENTRO =>
                                    $caso
                                        ->where(
                                            'galones_teoricos',
                                            '>',
                                            0
                                        )
                                        ->whereNotNull(
                                            'combustible_consumido_ciclo'
                                        )
                                        ->whereRaw(
                                            '((combustible_consumido_ciclo '
                                            . '- galones_teoricos) '
                                            . '/ galones_teoricos) * 100 '
                                            . 'BETWEEN ? AND ?',
                                            [
                                                -$tolerancia,
                                                $tolerancia,
                                            ]
                                        ),

                                self::ESTADO_SUPERIOR =>
                                    $caso
                                        ->where(
                                            'galones_teoricos',
                                            '>',
                                            0
                                        )
                                        ->whereNotNull(
                                            'combustible_consumido_ciclo'
                                        )
                                        ->whereRaw(
                                            '((combustible_consumido_ciclo '
                                            . '- galones_teoricos) '
                                            . '/ galones_teoricos) * 100 > ?',
                                            [$tolerancia]
                                        ),

                                self::ESTADO_INFERIOR =>
                                    $caso
                                        ->where(
                                            'galones_teoricos',
                                            '>',
                                            0
                                        )
                                        ->whereNotNull(
                                            'combustible_consumido_ciclo'
                                        )
                                        ->whereRaw(
                                            '((combustible_consumido_ciclo '
                                            . '- galones_teoricos) '
                                            . '/ galones_teoricos) * 100 < ?',
                                            [-$tolerancia]
                                        ),

                                self::ESTADO_SIN_COMPARACION =>
                                    $caso->where(
                                        function (
                                            Builder $sinComparacion
                                        ): void {
                                            $sinComparacion
                                                ->whereNull(
                                                    'galones_teoricos'
                                                )
                                                ->orWhere(
                                                    'galones_teoricos',
                                                    '<=',
                                                    0
                                                )
                                                ->orWhereNull(
                                                    'combustible_consumido_ciclo'
                                                );
                                        }
                                    ),

                                default => null,
                            };
                        }
                    );
                }
            }
        );
    }

    private function prepararFilaAnalitica(
        Abastecimiento $abastecimiento
    ): Abastecimiento {
        $rutas = $abastecimiento->rutas;

        $kilometrosTeoricos = is_null(
            $abastecimiento->kilometros_teoricos
        )
            ? null
            : (float) $abastecimiento->kilometros_teoricos;

        $galonesTeoricos = is_null(
            $abastecimiento->galones_teoricos
        )
            ? null
            : (float) $abastecimiento->galones_teoricos;

        $kilometrosReales = is_null(
            $abastecimiento->diferencia_kilometraje
        )
            ? null
            : (float) $abastecimiento->diferencia_kilometraje;

        $galonesConsumidos = is_null(
            $abastecimiento->combustible_consumido_ciclo
        )
            ? null
            : (float) $abastecimiento
                ->combustible_consumido_ciclo;

        $variacionGalones = (
            ! is_null($galonesTeoricos)
            && ! is_null($galonesConsumidos)
        )
            ? $galonesConsumidos - $galonesTeoricos
            : null;

        $variacionPorcentaje = (
            ! is_null($variacionGalones)
            && ! is_null($galonesTeoricos)
            && $galonesTeoricos > 0
        )
            ? ($variacionGalones / $galonesTeoricos) * 100
            : null;

        $eficienciaCiclo = (
            ! is_null($kilometrosReales)
            && ! is_null($galonesConsumidos)
            && $galonesConsumidos > 0
        )
            ? $kilometrosReales / $galonesConsumidos
            : null;

        $diagnostico = $this->clasificarComparacion(
            $galonesTeoricos,
            $galonesConsumidos,
            $variacionPorcentaje
        );

        /*
        |--------------------------------------------------------------------------
        | Distribución analítica por línea de ruta
        |--------------------------------------------------------------------------
        |
        | El abastecimiento conserva los valores reales al nivel del ciclo.
        | Para presentar un detalle por ruta, los kilómetros reales se
        | distribuyen según la participación de kilómetros teóricos de cada
        | línea, y el consumo real según la participación de galones teóricos.
        | Estos valores son estimaciones analíticas, no mediciones físicas
        | independientes de cada tramo.
        |
        */

        $totalKilometrosLineas = (float) $rutas->sum(
            fn (AbastecimientoRuta $ruta): float =>
                (float) ($ruta->kilometros_aplicados ?? 0)
        );

        $totalGalonesLineas = (float) $rutas->sum(
            fn (AbastecimientoRuta $ruta): float =>
                (float) ($ruta->galones_aplicados ?? 0)
        );

        $detalleRutas = $rutas
            ->map(
                function (
                    AbastecimientoRuta $ruta
                ) use (
                    $kilometrosReales,
                    $galonesConsumidos,
                    $totalKilometrosLineas,
                    $totalGalonesLineas
                ): array {
                    $kmTeoricos = (float) (
                        $ruta->kilometros_aplicados ?? 0
                    );

                    $galTeoricos = (float) (
                        $ruta->galones_aplicados ?? 0
                    );

                    $kmRealesDistribuidos = (
                        ! is_null($kilometrosReales)
                        && $totalKilometrosLineas > 0
                    )
                        ? (
                            $kilometrosReales
                            * $kmTeoricos
                            / $totalKilometrosLineas
                        )
                        : null;

                    $galonesRealesDistribuidos = (
                        ! is_null($galonesConsumidos)
                        && $totalGalonesLineas > 0
                    )
                        ? (
                            $galonesConsumidos
                            * $galTeoricos
                            / $totalGalonesLineas
                        )
                        : null;

                    $eficiencia = (
                        ! is_null($kmRealesDistribuidos)
                        && ! is_null($galonesRealesDistribuidos)
                        && $galonesRealesDistribuidos > 0
                    )
                        ? (
                            $kmRealesDistribuidos
                            / $galonesRealesDistribuidos
                        )
                        : null;

                    return [
                        'orden' => (int) $ruta->orden,
                        'ruta' => $ruta->ruta_nombre_snapshot
                            ?: 'Ruta sin nombre',
                        'recorrido' => collect([
                            $ruta->punto_origen_nombre_snapshot,
                            $ruta->punto_destino_nombre_snapshot,
                        ])
                            ->filter()
                            ->implode(' → '),
                        'tipo_recorrido' =>
                            $ruta->tipo_recorrido_texto,
                        'kilometros_teoricos' =>
                            round($kmTeoricos, 2),
                        'kilometros_reales' =>
                            is_null($kmRealesDistribuidos)
                                ? null
                                : round(
                                    $kmRealesDistribuidos,
                                    2
                                ),
                        'galones_teoricos' =>
                            round($galTeoricos, 2),
                        'galones_reales' =>
                            is_null($galonesRealesDistribuidos)
                                ? null
                                : round(
                                    $galonesRealesDistribuidos,
                                    2
                                ),
                        'eficiencia' =>
                            is_null($eficiencia)
                                ? null
                                : round($eficiencia, 2),
                    ];
                }
            )
            ->values()
            ->all();

        $placa = $abastecimiento->unidad_placa_snapshot
            ?: ($abastecimiento->unidad?->placa ?: 'No disponible');

        $marca = $abastecimiento->unidad_marca_snapshot
            ?: ($abastecimiento->unidad?->marca ?: null);

        $motorista = $abastecimiento->motorista_nombre_snapshot
            ?: (
                $abastecimiento->motorista
                    ? trim(
                        $abastecimiento->motorista->nombres
                        . ' '
                        . $abastecimiento->motorista->apellidos
                    )
                    : 'No disponible'
            );

        $abastecimiento->setAttribute(
            'numero_ciclo_analitico',
            (int) $abastecimiento->numero_ciclo_historico
        );

        $abastecimiento->setAttribute(
            'empresa_texto_analitico',
            $abastecimiento->empresa_nombre_snapshot
                ?: (
                    $abastecimiento->empresa
                        ? (
                            $abastecimiento->empresa
                                ->nombre_comercial
                            ?: $abastecimiento->empresa
                                ->nombre_legal
                        )
                        : 'No disponible'
                )
        );

        $abastecimiento->setAttribute(
            'unidad_texto_analitico',
            collect([$placa, $marca])
                ->filter()
                ->implode(' · ')
        );

        $abastecimiento->setAttribute(
            'motorista_texto_analitico',
            $motorista
        );

        $abastecimiento->setAttribute(
            'total_rutas_analitico',
            $rutas->count()
        );

        $abastecimiento->setAttribute(
            'detalle_rutas_analitico',
            $detalleRutas
        );

        $abastecimiento->setAttribute(
            'kilometros_teoricos_analitico',
            $kilometrosTeoricos
        );

        $abastecimiento->setAttribute(
            'kilometros_reales_analitico',
            $kilometrosReales
        );

        $abastecimiento->setAttribute(
            'galones_teoricos_analitico',
            $galonesTeoricos
        );

        $abastecimiento->setAttribute(
            'galones_consumidos_analitico',
            $galonesConsumidos
        );

        $abastecimiento->setAttribute(
            'eficiencia_ciclo_analitico',
            is_null($eficienciaCiclo)
                ? null
                : round($eficienciaCiclo, 2)
        );

        $abastecimiento->setAttribute(
            'variacion_galones_analitico',
            is_null($variacionGalones)
                ? null
                : round($variacionGalones, 2)
        );

        $abastecimiento->setAttribute(
            'variacion_porcentaje_analitico',
            is_null($variacionPorcentaje)
                ? null
                : round($variacionPorcentaje, 2)
        );

        $abastecimiento->setAttribute(
            'diagnostico_analitico',
            $diagnostico['texto']
        );

        $abastecimiento->setAttribute(
            'diagnostico_clave_analitico',
            $diagnostico['clave']
        );

        return $abastecimiento;
    }

    private function clasificarComparacion(
        ?float $galonesTeoricos,
        ?float $galonesConsumidos,
        ?float $variacionPorcentaje
    ): array {
        if (
            is_null($galonesTeoricos)
            || $galonesTeoricos <= 0
            || is_null($galonesConsumidos)
            || is_null($variacionPorcentaje)
        ) {
            return [
                'clave' => self::ESTADO_SIN_COMPARACION,
                'texto' => 'Sin comparación',
            ];
        }

        if (
            $variacionPorcentaje
            > self::TOLERANCIA_PORCENTAJE
        ) {
            return [
                'clave' => self::ESTADO_SUPERIOR,
                'texto' => 'Consumo superior',
            ];
        }

        if (
            $variacionPorcentaje
            < -self::TOLERANCIA_PORCENTAJE
        ) {
            return [
                'clave' => self::ESTADO_INFERIOR,
                'texto' => 'Consumo inferior',
            ];
        }

        return [
            'clave' => self::ESTADO_DENTRO,
            'texto' => 'Dentro de estimación',
        ];
    }

    private function aplicarOrdenAnalitico(
        Builder $query,
        string $sort,
        string $direction
    ): void {
        $direction = $direction === 'asc'
            ? 'asc'
            : 'desc';

        $expresionVariacion =
            '(combustible_consumido_ciclo - galones_teoricos)';

        $expresionPorcentaje =
            'CASE '
            . 'WHEN galones_teoricos IS NULL '
            . 'OR galones_teoricos <= 0 '
            . 'OR combustible_consumido_ciclo IS NULL '
            . 'THEN NULL '
            . 'ELSE (('
            . $expresionVariacion
            . ') / galones_teoricos) * 100 '
            . 'END';

        match ($sort) {
            'empresa' => $query
                ->orderByRaw(
                    'COALESCE(empresa_nombre_snapshot, "") '
                    . $direction
                )
                ->orderByDesc('fecha_hora_abastecimiento')
                ->orderByDesc('id'),

            'unidad' => $query
                ->orderByRaw(
                    'COALESCE(unidad_placa_snapshot, "") '
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

            'rutas' => $query
                ->orderBy('primera_ruta_nombre', $direction)
                ->orderByDesc('fecha_hora_abastecimiento')
                ->orderByDesc('id'),

            'kilometros_teoricos' => $query
                ->orderBy(
                    'kilometros_teoricos',
                    $direction
                )
                ->orderByDesc('fecha_hora_abastecimiento')
                ->orderByDesc('id'),

            'galones_teoricos' => $query
                ->orderBy(
                    'galones_teoricos',
                    $direction
                )
                ->orderByDesc('fecha_hora_abastecimiento')
                ->orderByDesc('id'),

            'galones_consumidos' => $query
                ->orderBy(
                    'combustible_consumido_ciclo',
                    $direction
                )
                ->orderByDesc('fecha_hora_abastecimiento')
                ->orderByDesc('id'),

            'variacion_galones' => $query
                ->orderByRaw(
                    $expresionVariacion
                    . ' '
                    . $direction
                )
                ->orderByDesc('fecha_hora_abastecimiento')
                ->orderByDesc('id'),

            'variacion_porcentaje' => $query
                ->orderByRaw(
                    $expresionPorcentaje
                    . ' '
                    . $direction
                )
                ->orderByDesc('fecha_hora_abastecimiento')
                ->orderByDesc('id'),

            'diagnostico' => $query
                ->orderByRaw(
                    'CASE '
                    . 'WHEN galones_teoricos IS NULL '
                    . 'OR galones_teoricos <= 0 '
                    . 'OR combustible_consumido_ciclo IS NULL '
                    . 'THEN 4 '
                    . 'WHEN '
                    . $expresionPorcentaje
                    . ' > '
                    . self::TOLERANCIA_PORCENTAJE
                    . ' THEN 1 '
                    . 'WHEN '
                    . $expresionPorcentaje
                    . ' < -'
                    . self::TOLERANCIA_PORCENTAJE
                    . ' THEN 2 '
                    . 'ELSE 3 END '
                    . $direction
                )
                ->orderByDesc('fecha_hora_abastecimiento')
                ->orderByDesc('id'),

            default => $query
                ->orderBy(
                    'fecha_hora_abastecimiento',
                    $direction
                )
                ->orderBy(
                    'id',
                    $direction
                ),
        };
    }

    private function obtenerResumenAnalitico(
        Builder $base
    ): array {
        $registros = (clone $base)->count();

        $recorridos = (int) (
            (clone $base)->sum('total_rutas') ?? 0
        );

        $kilometrosTeoricos = (float) (
            (clone $base)->sum('kilometros_teoricos') ?? 0
        );

        $kilometrosMedidos = (float) (
            (clone $base)->sum('diferencia_kilometraje') ?? 0
        );

        $galonesTeoricos = (float) (
            (clone $base)->sum('galones_teoricos') ?? 0
        );

        $galonesConsumidos = (float) (
            (clone $base)->sum(
                'combustible_consumido_ciclo'
            ) ?? 0
        );

        $variacionGalones =
            $galonesConsumidos - $galonesTeoricos;

        $variacionPorcentaje = $galonesTeoricos > 0
            ? (
                $variacionGalones
                / $galonesTeoricos
            ) * 100
            : null;

        $eficienciaReal = $galonesConsumidos > 0
            ? $kilometrosMedidos / $galonesConsumidos
            : null;

        return [
            'abastecimientos_con_ruta' => $registros,
            'recorridos_registrados' => $recorridos,
            'kilometros_teoricos' => $kilometrosTeoricos,
            'kilometros_medidos' => $kilometrosMedidos,
            'galones_teoricos' => $galonesTeoricos,
            'galones_consumidos' => $galonesConsumidos,
            'eficiencia_real' => is_null($eficienciaReal)
                ? null
                : round($eficienciaReal, 2),
            'variacion_galones' => $variacionGalones,
            'variacion_porcentaje' => is_null(
                $variacionPorcentaje
            )
                ? null
                : round($variacionPorcentaje, 2),

            'diagnosticos' => [
                self::ESTADO_DENTRO =>
                    $this->contarEstadoComparacion(
                        clone $base,
                        self::ESTADO_DENTRO
                    ),

                self::ESTADO_SUPERIOR =>
                    $this->contarEstadoComparacion(
                        clone $base,
                        self::ESTADO_SUPERIOR
                    ),

                self::ESTADO_INFERIOR =>
                    $this->contarEstadoComparacion(
                        clone $base,
                        self::ESTADO_INFERIOR
                    ),

                self::ESTADO_SIN_COMPARACION =>
                    $this->contarEstadoComparacion(
                        clone $base,
                        self::ESTADO_SIN_COMPARACION
                    ),
            ],
        ];
    }

    private function contarEstadoComparacion(
        Builder $base,
        string $estado
    ): int {
        $this->aplicarFiltroEstadosComparacion(
            $base,
            [$estado]
        );

        return $base->count();
    }

    private function obtenerDatosGraficos(
        Builder $base
    ): array {
        $registros = (clone $base)
            ->orderBy('fecha_hora_abastecimiento')
            ->orderBy('id')
            ->get([
                'id',
                'unidad_placa_snapshot',
                'fecha_hora_abastecimiento',
                'galones_teoricos',
                'combustible_consumido_ciclo',
                'diferencia_kilometraje',
            ]);

        if ($registros->isEmpty()) {
            return [
                'ciclos' => [
                    'puntos' => [],
                ],
                'rutas' => [
                    'filas' => [],
                ],
            ];
        }

        $puntosCiclos = $registros
            ->values()
            ->map(
                function (
                    Abastecimiento $registro,
                    int $indice
                ): array {
                    return [
                        'etiqueta' =>
                            'Ciclo ' . ($indice + 1),
                        'teorico' => round(
                            (float) (
                                $registro->galones_teoricos
                                ?? 0
                            ),
                            2
                        ),
                        'real' => round(
                            (float) (
                                $registro
                                    ->combustible_consumido_ciclo
                                ?? 0
                            ),
                            2
                        ),
                        'detalle' => collect([
                            $registro->unidad_placa_snapshot,
                            optional(
                                $registro
                                    ->fecha_hora_abastecimiento
                            )->format('d/m/Y'),
                        ])
                            ->filter()
                            ->implode(' · '),
                    ];
                }
            )
            ->all();

        $idsAbastecimientos = $registros
            ->pluck('id');

        $lineas = AbastecimientoRuta::query()
            ->whereIn(
                'abastecimiento_id',
                $idsAbastecimientos
            )
            ->with([
                'abastecimiento:id,diferencia_kilometraje,combustible_consumido_ciclo',
            ])
            ->orderBy('abastecimiento_id')
            ->orderBy('orden')
            ->get([
                'id',
                'abastecimiento_id',
                'ruta_id',
                'ruta_nombre_snapshot',
                'punto_origen_nombre_snapshot',
                'punto_destino_nombre_snapshot',
                'kilometros_aplicados',
                'galones_aplicados',
            ]);

        $contribuciones = collect();

        $lineas
            ->groupBy('abastecimiento_id')
            ->each(
                function (Collection $grupo) use (
                    $contribuciones
                ): void {
                    $abastecimiento =
                        $grupo->first()?->abastecimiento;

                    $kmReales = is_null(
                        $abastecimiento
                            ?->diferencia_kilometraje
                    )
                        ? null
                        : (float) $abastecimiento
                            ->diferencia_kilometraje;

                    $galReales = is_null(
                        $abastecimiento
                            ?->combustible_consumido_ciclo
                    )
                        ? null
                        : (float) $abastecimiento
                            ->combustible_consumido_ciclo;

                    $totalKmTeoricos = (float) $grupo->sum(
                        fn (
                            AbastecimientoRuta $linea
                        ): float =>
                            (float) (
                                $linea
                                    ->kilometros_aplicados
                                ?? 0
                            )
                    );

                    $totalGalTeoricos = (float) $grupo->sum(
                        fn (
                            AbastecimientoRuta $linea
                        ): float =>
                            (float) (
                                $linea
                                    ->galones_aplicados
                                ?? 0
                            )
                    );

                    foreach ($grupo as $linea) {
                        $kmTeoricos = (float) (
                            $linea->kilometros_aplicados
                            ?? 0
                        );

                        $galTeoricos = (float) (
                            $linea->galones_aplicados
                            ?? 0
                        );

                        $kmRealDistribuido = (
                            ! is_null($kmReales)
                            && $totalKmTeoricos > 0
                        )
                            ? (
                                $kmReales
                                * $kmTeoricos
                                / $totalKmTeoricos
                            )
                            : 0;

                        $galRealDistribuido = (
                            ! is_null($galReales)
                            && $totalGalTeoricos > 0
                        )
                            ? (
                                $galReales
                                * $galTeoricos
                                / $totalGalTeoricos
                            )
                            : 0;

                        $contribuciones->push([
                            'clave' => $linea->ruta_id
                                ?: (
                                    $linea
                                        ->ruta_nombre_snapshot
                                    ?: 'sin-ruta'
                                ),
                            'ruta' =>
                                $linea
                                    ->ruta_nombre_snapshot
                                ?: 'Ruta sin nombre',
                            'recorrido' => collect([
                                $linea
                                    ->punto_origen_nombre_snapshot,
                                $linea
                                    ->punto_destino_nombre_snapshot,
                            ])
                                ->filter()
                                ->implode(' → '),
                            'kilometros' => $kmTeoricos,
                            'kilometros_reales' =>
                                $kmRealDistribuido,
                            'galones_reales' =>
                                $galRealDistribuido,
                        ]);
                    }
                }
            );

        $topRutas = $contribuciones
            ->groupBy('clave')
            ->map(
                function (
                    Collection $grupo
                ): array {
                    $kilometros = (float) $grupo->sum(
                        'kilometros'
                    );

                    $kilometrosReales = (float) $grupo->sum(
                        'kilometros_reales'
                    );

                    $galonesReales = (float) $grupo->sum(
                        'galones_reales'
                    );

                    return [
                        'ruta' => (string) $grupo
                            ->first()['ruta'],
                        'recorrido' => (string) $grupo
                            ->pluck('recorrido')
                            ->filter()
                            ->unique()
                            ->implode(' · '),
                        'ejecuciones' => $grupo->count(),
                        'kilometros' => round(
                            $kilometros,
                            1
                        ),
                        'consumo_promedio_km' =>
                            $kilometrosReales > 0
                                ? round(
                                    $galonesReales
                                    / $kilometrosReales,
                                    4
                                )
                                : null,
                    ];
                }
            )
            ->sortByDesc('kilometros')
            ->take(10)
            ->values()
            ->all();

        return [
            'ciclos' => [
                'puntos' => $puntosCiclos,
            ],
            'rutas' => [
                'filas' => $topRutas,
            ],
        ];
    }

    private function obtenerEmpresasSelector(
        bool $esUsuarioDieselCop,
        ?Empresa $empresaUsuario
    ): Collection {
        if (! $esUsuarioDieselCop) {
            return collect([$empresaUsuario])
                ->filter();
        }

        return Empresa::query()
            ->whereHas(
                'abastecimientos',
                fn (Builder $query) =>
                    $query
                        ->registrados()
                        ->whereNotNull(
                            'abastecimiento_anterior_id'
                        )
                        ->whereHas('rutas')
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
                fn (Builder $query) =>
                    $query
                        ->registrados()
                        ->whereNotNull(
                            'abastecimiento_anterior_id'
                        )
                        ->whereHas('rutas')
            )
            ->when(
                ! $esUsuarioDieselCop,
                fn (Builder $query) =>
                    $query->where(
                        'empresa_id',
                        $empresaUsuarioId
                    )
            )
            ->when(
                $empresaIds !== [],
                fn (Builder $query) =>
                    $query->whereIn(
                        'empresa_id',
                        $empresaIds
                    )
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
            ->whereHas(
                'abastecimientos',
                fn (Builder $query) =>
                    $query
                        ->registrados()
                        ->whereNotNull(
                            'abastecimiento_anterior_id'
                        )
                        ->whereHas('rutas')
            )
            ->when(
                ! $esUsuarioDieselCop,
                fn (Builder $query) =>
                    $query->where(
                        'empresa_id',
                        $empresaUsuarioId
                    )
            )
            ->when(
                $empresaIds !== [],
                fn (Builder $query) =>
                    $query->whereIn(
                        'empresa_id',
                        $empresaIds
                    )
            )
            ->orderBy('empresa_id')
            ->orderBy('nombres')
            ->orderBy('apellidos')
            ->get();
    }

    private function obtenerRutasSelector(
        bool $esUsuarioDieselCop,
        int|string|null $empresaUsuarioId,
        array $empresaIds
    ): Collection {
        $rutaIds = AbastecimientoRuta::query()
            ->whereNotNull('ruta_id')
            ->whereHas(
                'abastecimiento',
                function (Builder $query) use (
                    $esUsuarioDieselCop,
                    $empresaUsuarioId,
                    $empresaIds
                ): void {
                    $query
                        ->registrados()
                        ->whereNotNull(
                            'abastecimiento_anterior_id'
                        );

                    if (! $esUsuarioDieselCop) {
                        $query->where(
                            'empresa_id',
                            $empresaUsuarioId
                        );
                    }

                    if ($empresaIds !== []) {
                        $query->whereIn(
                            'empresa_id',
                            $empresaIds
                        );
                    }
                }
            )
            ->distinct()
            ->pluck('ruta_id');

        return Ruta::query()
            ->with([
                'empresa',
                'puntoOrigen',
                'puntoDestino',
            ])
            ->whereIn('id', $rutaIds)
            ->orderBy('empresa_id')
            ->orderBy('ruta')
            ->get();
    }

    private function obtenerPuntosSelector(
        bool $esUsuarioDieselCop,
        int|string|null $empresaUsuarioId,
        array $empresaIds
    ): Collection {
        $lineas = AbastecimientoRuta::query()
            ->whereHas(
                'abastecimiento',
                function (Builder $query) use (
                    $esUsuarioDieselCop,
                    $empresaUsuarioId,
                    $empresaIds
                ): void {
                    $query
                        ->registrados()
                        ->whereNotNull(
                            'abastecimiento_anterior_id'
                        );

                    if (! $esUsuarioDieselCop) {
                        $query->where(
                            'empresa_id',
                            $empresaUsuarioId
                        );
                    }

                    if ($empresaIds !== []) {
                        $query->whereIn(
                            'empresa_id',
                            $empresaIds
                        );
                    }
                }
            )
            ->get([
                'punto_origen_id',
                'punto_destino_id',
            ]);

        $puntoIds = $lineas
            ->pluck('punto_origen_id')
            ->merge(
                $lineas->pluck('punto_destino_id')
            )
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        return PuntoRuta::query()
            ->with('empresa')
            ->whereIn('id', $puntoIds)
            ->orderBy('empresa_id')
            ->orderBy('nombre')
            ->get();
    }

    private function normalizarIdsSeleccionados(
        array $ids,
        mixed $idIndividual = null
    ): array {
        if (
            $ids === []
            && ! is_null($idIndividual)
            && $idIndividual !== ''
        ) {
            $ids = [$idIndividual];
        }

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

    private function normalizarValoresSeleccionados(
        array $valores,
        array $permitidos
    ): array {
        return collect($valores)
            ->filter(
                fn ($valor): bool =>
                    is_string($valor)
                    && in_array(
                        $valor,
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
}