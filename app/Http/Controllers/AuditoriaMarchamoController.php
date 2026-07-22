<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\ReemplazoMarchamoEvento;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditoriaMarchamoController extends Controller
{
    private const MOTIVOS = [
        'dano' => 'Daño',
        'desgaste' => 'Desgaste',
        'perdida' => 'Pérdida',
        'manipulacion_detectada' => 'Manipulación detectada',
        'correccion_instalacion' => 'Corrección de instalación',
        ReemplazoMarchamoEvento::MOTIVO_APERTURA_ABASTECIMIENTO =>
            'Apertura por abastecimiento',
        'motivos_multiples' => 'Motivos múltiples',
    ];

    private const ESTADOS = [
        'registrado' => 'Registrado',
        'anulado' => 'Anulado',
    ];

    private const ORIGENES = [
        ReemplazoMarchamoEvento::ORIGEN_REEMPLAZO_GENERAL =>
            'Reemplazo general',
        ReemplazoMarchamoEvento::ORIGEN_ABASTECIMIENTO =>
            'Abastecimiento',
    ];

    public function index(Request $request): View
    {
        return view(
            'auditoria-marchamos.index',
            $this->prepararAnalisis($request)
        );
    }

    public function indexVentana(Request $request): View
    {
        return view(
            'auditoria-marchamos.index-ventana',
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

                'origenes' => ['nullable', 'array'],
                'origenes.*' => [
                    'string',
                    'distinct',
                    Rule::in(array_keys(self::ORIGENES)),
                ],

                'motivos' => ['nullable', 'array'],
                'motivos.*' => [
                    'string',
                    'distinct',
                    Rule::in(array_keys(self::MOTIVOS)),
                ],

                'estados' => ['nullable', 'array'],
                'estados.*' => [
                    'string',
                    'distinct',
                    Rule::in(array_keys(self::ESTADOS)),
                ],

                'usuario_ids' => ['nullable', 'array'],
                'usuario_ids.*' => [
                    'integer',
                    'distinct',
                    'exists:users,id',
                ],

                'cantidades' => ['nullable', 'array'],
                'cantidades.*' => [
                    'string',
                    'distinct',
                    Rule::in([
                        '1',
                        '2',
                        '3_mas',
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
                        'origen',
                        'clasificacion',
                        'cantidad',
                        'usuario',
                        'reincidencia',
                        'estado',
                    ]),
                ],

                'direction' => [
                    'nullable',
                    Rule::in(['asc', 'desc']),
                ],
            ],
            [
                'fecha_hasta.after_or_equal' =>
                    'La fecha final no puede ser anterior a la fecha inicial.',
            ]
        );

        $empresaIds = $this->normalizarIds(
            $validated['empresa_ids'] ?? []
        );

        $unidadIds = $this->normalizarIds(
            $validated['unidad_ids'] ?? []
        );

        $usuarioIds = $this->normalizarIds(
            $validated['usuario_ids'] ?? []
        );

        $origenes = $this->normalizarValores(
            $validated['origenes'] ?? [],
            array_keys(self::ORIGENES)
        );

        $motivos = $this->normalizarValores(
            $validated['motivos'] ?? [],
            array_keys(self::MOTIVOS)
        );

        $estados = $this->normalizarValores(
            $validated['estados'] ?? [],
            array_keys(self::ESTADOS)
        );

        $cantidades = $this->normalizarValores(
            $validated['cantidades'] ?? [],
            [
                '1',
                '2',
                '3_mas',
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

        $hayFiltros = $request->hasAny([
            'empresa_ids',
            'unidad_ids',
            'origenes',
            'motivos',
            'estados',
            'usuario_ids',
            'cantidades',
            'fecha_desde',
            'fecha_hasta',
            'busqueda',
            'consultar',
            'sort',
            'direction',
            'page',
        ]);

        $query = ReemplazoMarchamoEvento::query()
            ->with([
                'empresa',
                'unidad',
                'registradoPor',
                'anuladoPor',
                'abastecimiento',
                'detalles.puntoSeguridad',
                'detalles.marchamoAnterior',
                'detalles.marchamoNuevo',
            ]);

        if (! $esUsuarioDieselCop) {
            $query->where(
                'empresa_id',
                $user->empresa_id
            );
        }

        if (! $hayFiltros) {
            $eventos = collect();
        } else {
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

            if ($origenes !== []) {
                $query->whereIn(
                    'origen_evento',
                    $origenes
                );
            }

            if ($estados !== []) {
                $query->whereIn(
                    'estado',
                    $estados
                );
            }

            if ($usuarioIds !== []) {
                $query->whereIn(
                    'registrado_por',
                    $usuarioIds
                );
            }

            if ($fechaDesde) {
                $query->where(
                    'fecha_registro',
                    '>=',
                    Carbon::createFromFormat(
                        'Y-m-d',
                        $fechaDesde
                    )->startOfDay()
                );
            }

            if ($fechaHasta) {
                $query->where(
                    'fecha_registro',
                    '<',
                    Carbon::createFromFormat(
                        'Y-m-d',
                        $fechaHasta
                    )
                        ->addDay()
                        ->startOfDay()
                );
            }

            if ($cantidades !== []) {
                $query->where(
                    function ($cantidadQuery) use (
                        $cantidades
                    ): void {
                        foreach ($cantidades as $cantidad) {
                            $cantidadQuery->orWhere(
                                function ($caso) use (
                                    $cantidad
                                ): void {
                                    match ($cantidad) {
                                        '1' =>
                                            $caso->where(
                                                'cantidad_reemplazos',
                                                1
                                            ),

                                        '2' =>
                                            $caso->where(
                                                'cantidad_reemplazos',
                                                2
                                            ),

                                        '3_mas' =>
                                            $caso->where(
                                                'cantidad_reemplazos',
                                                '>=',
                                                3
                                            ),

                                        default => null,
                                    };
                                }
                            );
                        }
                    }
                );
            }

            if ($busqueda !== '') {
                $query->where(
                    function ($busquedaQuery) use (
                        $busqueda
                    ): void {
                        $termino = '%' . $busqueda . '%';

                        $busquedaQuery
                            ->whereHas(
                                'empresa',
                                function ($empresaQuery) use (
                                    $termino
                                ): void {
                                    $empresaQuery
                                        ->where(
                                            'nombre_legal',
                                            'like',
                                            $termino
                                        )
                                        ->orWhere(
                                            'nombre_comercial',
                                            'like',
                                            $termino
                                        );
                                }
                            )
                            ->orWhereHas(
                                'unidad',
                                fn ($unidadQuery) =>
                                    $unidadQuery->where(
                                        'placa',
                                        'like',
                                        $termino
                                    )
                            )
                            ->orWhereHas(
                                'registradoPor',
                                function ($usuarioQuery) use (
                                    $termino
                                ): void {
                                    $usuarioQuery
                                        ->where(
                                            'name',
                                            'like',
                                            $termino
                                        )
                                        ->orWhere(
                                            'email',
                                            'like',
                                            $termino
                                        );
                                }
                            )
                            ->orWhereHas(
                                'detalles.marchamoAnterior',
                                fn ($marchamoQuery) =>
                                    $marchamoQuery->where(
                                        'codigo_marchamo',
                                        'like',
                                        $termino
                                    )
                            )
                            ->orWhereHas(
                                'detalles.marchamoNuevo',
                                fn ($marchamoQuery) =>
                                    $marchamoQuery->where(
                                        'codigo_marchamo',
                                        'like',
                                        $termino
                                    )
                            )
                            ->orWhereHas(
                                'detalles.puntoSeguridad',
                                function ($puntoQuery) use (
                                    $termino
                                ): void {
                                    $puntoQuery
                                        ->where(
                                            'nombre_punto',
                                            'like',
                                            $termino
                                        )
                                        ->orWhere(
                                            'codigo_punto',
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

            $eventos = $query
                ->orderBy('fecha_registro')
                ->orderBy('id')
                ->get();

            if ($motivos !== []) {
                $eventos = $eventos
                    ->filter(
                        fn (
                            ReemplazoMarchamoEvento $evento
                        ): bool =>
                            $this->eventoCoincideConMotivos(
                                $evento,
                                $motivos
                            )
                    )
                    ->values();
            }
        }

        $eventosPreparados = $this->prepararEventos(
            $eventos
        );

        $eventosOrdenados = $this->ordenarEventos(
            $eventosPreparados,
            $sort,
            $direction
        );

        $paginaActual = max(
            (int) $request->input('page', 1),
            1
        );

        $porPagina = 20;

        $eventosPaginados = new LengthAwarePaginator(
            $eventosOrdenados
                ->forPage(
                    $paginaActual,
                    $porPagina
                )
                ->values(),
            $eventosOrdenados->count(),
            $porPagina,
            $paginaActual,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $empresasSelector = $this->obtenerEmpresasSelector(
            $esUsuarioDieselCop,
            $user->empresa_id
        );

        $unidadesSelector = $this->obtenerUnidadesSelector(
            $esUsuarioDieselCop,
            $user->empresa_id,
            $empresaIds
        );

        $usuariosSelector = $this->obtenerUsuariosSelector(
            $esUsuarioDieselCop,
            $user->empresa_id,
            $empresaIds
        );

        $resumen = $this->obtenerResumen(
            $eventosPreparados
        );

        $graficoAuditoria =
            $this->obtenerGraficoAuditoria(
                $eventosPreparados
            );

        return [
            'eventos' => $eventosPaginados,

            'empresasSelector' => $empresasSelector,
            'unidadesSelector' => $unidadesSelector,
            'usuariosSelector' => $usuariosSelector,

            'empresaIds' => $empresaIds,
            'unidadIds' => $unidadIds,
            'usuarioIds' => $usuarioIds,
            'origenes' => $origenes,
            'motivos' => $motivos,
            'estados' => $estados,
            'cantidades' => $cantidades,

            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'busqueda' => $busqueda,
            'sort' => $sort,
            'direction' => $direction,

            'hayFiltros' => $hayFiltros,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,

            'resumen' => $resumen,
            'graficoAuditoria' => $graficoAuditoria,

            'opcionesOrigenes' => self::ORIGENES,
            'opcionesMotivos' => self::MOTIVOS,
            'opcionesEstados' => self::ESTADOS,
            'opcionesCantidades' => [
                '1' => '1 marchamo',
                '2' => '2 marchamos',
                '3_mas' => '3 o más marchamos',
            ],
        ];
    }

    private function prepararEventos(
        Collection $eventos
    ): Collection {
        if ($eventos->isEmpty()) {
            return collect();
        }

        $conteoPorUnidad = $eventos
            ->groupBy('unidad_id')
            ->map(
                fn (Collection $grupo): int =>
                    $grupo->count()
            );

        $frecuenciaElevadaPorUnidad = $eventos
            ->groupBy('unidad_id')
            ->map(
                fn (Collection $grupo): bool =>
                    $this->tieneFrecuenciaElevada(
                        $grupo
                    )
            );

        return $eventos
            ->map(
                function (
                    ReemplazoMarchamoEvento $evento
                ) use (
                    $conteoPorUnidad,
                    $frecuenciaElevadaPorUnidad
                ): array {
                    $cantidadEventosUnidad = (int) (
                        $conteoPorUnidad->get(
                            $evento->unidad_id
                        ) ?? 0
                    );

                    $detalle = $evento->detalles
                        ->map(
                            function ($item): array {
                                return [
                                    'punto' =>
                                        $item
                                            ->punto_seguridad_texto,

                                    'codigo_anterior' =>
                                        $item
                                            ->codigo_anterior
                                        ?: 'No disponible',

                                    'codigo_nuevo' =>
                                        $item
                                            ->codigo_nuevo
                                        ?: 'No disponible',

                                    'motivo' =>
                                        $item
                                            ->motivo_reemplazo_texto,

                                    'fecha' =>
                                        optional(
                                            $item->fecha_registro
                                        )->format(
                                            'd/m/Y H:i'
                                        )
                                        ?: 'No disponible',
                                ];
                            }
                        )
                        ->values()
                        ->all();

                    $nombreEmpresa = $evento->empresa
                        ? (
                            $evento->empresa
                                ->nombre_comercial
                            ?: $evento->empresa
                                ->nombre_legal
                        )
                        : 'No disponible';

                    $nombreUsuario = $evento
                        ->registradoPor
                        ? (
                            $evento
                                ->registradoPor
                                ->name
                            ?: $evento
                                ->registradoPor
                                ->email
                        )
                        : 'No disponible';

                    return [
                        'modelo' => $evento,
                        'id' => (int) $evento->id,
                        'fecha' => $evento->fecha_registro,
                        'empresa' => $nombreEmpresa,
                        'empresa_id' => (int) $evento->empresa_id,
                        'unidad' => $evento->unidad?->placa
                            ?: 'No disponible',
                        'unidad_id' => (int) $evento->unidad_id,
                        'origen' => $evento->origen_evento_texto,
                        'origen_clave' => $evento->origen_evento,
                        'clasificacion' =>
                            $evento
                                ->clasificacion_motivo_texto,
                        'cantidad' => (int) $evento
                            ->cantidad_reemplazos,
                        'usuario' => $nombreUsuario,
                        'usuario_id' => (int) $evento
                            ->registrado_por,
                        'estado' => $evento->estado_texto,
                        'estado_clave' => $evento->estado,
                        'eventos_unidad' =>
                            $cantidadEventosUnidad,
                        'reincidente' =>
                            $cantidadEventosUnidad >= 2,
                        'frecuencia_elevada' => (bool) (
                            $frecuenciaElevadaPorUnidad
                                ->get(
                                    $evento->unidad_id
                                )
                            ?? false
                        ),
                        'cantidad_consistente' =>
                            $evento
                                ->cantidad_consistente,
                        'detalle' => $detalle,
                    ];
                }
            )
            ->values();
    }

    private function tieneFrecuenciaElevada(
        Collection $eventosUnidad
    ): bool {
        $fechas = $eventosUnidad
            ->pluck('fecha_registro')
            ->filter()
            ->map(
                fn ($fecha): Carbon =>
                    Carbon::parse($fecha)
            )
            ->sort()
            ->values();

        for (
            $inicio = 0;
            $inicio < $fechas->count();
            $inicio++
        ) {
            $finVentana = $fechas[$inicio]
                ->copy()
                ->addDays(90);

            $cantidad = $fechas
                ->filter(
                    fn (Carbon $fecha): bool =>
                        $fecha->betweenIncluded(
                            $fechas[$inicio],
                            $finVentana
                        )
                )
                ->count();

            if ($cantidad >= 3) {
                return true;
            }
        }

        return false;
    }

    private function eventoCoincideConMotivos(
        ReemplazoMarchamoEvento $evento,
        array $motivos
    ): bool {
        if (
            in_array(
                'motivos_multiples',
                $motivos,
                true
            )
            && $evento->tiene_motivos_multiples
        ) {
            return true;
        }

        if (
            in_array(
                $evento->motivo_reemplazo,
                $motivos,
                true
            )
        ) {
            return true;
        }

        $textosSeleccionados = collect($motivos)
            ->map(
                fn (string $motivo): ?string =>
                    self::MOTIVOS[$motivo] ?? null
            )
            ->filter()
            ->values();

        return $evento
            ->detalles
            ->contains(
                fn ($detalle): bool =>
                    $textosSeleccionados->contains(
                        $detalle
                            ->motivo_reemplazo_texto
                    )
            );
    }

    private function ordenarEventos(
        Collection $eventos,
        string $sort,
        string $direction
    ): Collection {
        $descendente = $direction === 'desc';

        return $eventos
            ->sortBy(
                function (array $evento) use (
                    $sort
                ): mixed {
                    return match ($sort) {
                        'empresa' =>
                            mb_strtolower(
                                $evento['empresa']
                            ),

                        'unidad' =>
                            mb_strtolower(
                                $evento['unidad']
                            ),

                        'origen' =>
                            mb_strtolower(
                                $evento['origen']
                            ),

                        'clasificacion' =>
                            mb_strtolower(
                                $evento['clasificacion']
                            ),

                        'cantidad' =>
                            $evento['cantidad'],

                        'usuario' =>
                            mb_strtolower(
                                $evento['usuario']
                            ),

                        'reincidencia' =>
                            $evento['eventos_unidad'],

                        'estado' =>
                            mb_strtolower(
                                $evento['estado']
                            ),

                        default =>
                            optional(
                                $evento['fecha']
                            )?->timestamp
                            ?? 0,
                    };
                },
                SORT_NATURAL | SORT_FLAG_CASE,
                $descendente
            )
            ->values();
    }

    private function obtenerResumen(
        Collection $eventos
    ): array {
        $totalEventos = $eventos->count();
        $totalMarchamos = (int) $eventos->sum(
            'cantidad'
        );

        $promedio = $totalEventos > 0
            ? $totalMarchamos / $totalEventos
            : null;

        $motivos = $eventos
            ->groupBy('clasificacion')
            ->map(
                fn (Collection $grupo): int =>
                    $grupo->count()
            )
            ->sortDesc();

        $marchamosAbastecimiento = (int) $eventos
            ->where(
                'origen_clave',
                ReemplazoMarchamoEvento::ORIGEN_ABASTECIMIENTO
            )
            ->sum('cantidad');

        $marchamosManuales = (int) $eventos
            ->where(
                'origen_clave',
                ReemplazoMarchamoEvento::ORIGEN_REEMPLAZO_GENERAL
            )
            ->sum('cantidad');

        $porcentajeAbastecimiento = $totalMarchamos > 0
            ? round(
                (
                    $marchamosAbastecimiento
                    / $totalMarchamos
                ) * 100,
                2
            )
            : 0.0;

        $porcentajeManual = $totalMarchamos > 0
            ? round(
                (
                    $marchamosManuales
                    / $totalMarchamos
                ) * 100,
                2
            )
            : 0.0;

        return [
            'marchamos' => $totalMarchamos,

            'unidades_auditadas' => $eventos
                ->pluck('unidad_id')
                ->unique()
                ->count(),

            'marchamos_abastecimiento' =>
                $marchamosAbastecimiento,

            'porcentaje_abastecimiento' =>
                $porcentajeAbastecimiento,

            'marchamos_manuales' =>
                $marchamosManuales,

            'porcentaje_manual' =>
                $porcentajeManual,
        ];
    }

    private function obtenerGraficoAuditoria(
        Collection $eventos
    ): array {
        $registros = collect();

        foreach ($eventos as $evento) {
            $fecha = $evento['fecha']
                ? Carbon::parse($evento['fecha'])
                : null;

            if (! $fecha) {
                continue;
            }

            $detalles = collect(
                $evento['detalle'] ?? []
            );

            if ($detalles->isNotEmpty()) {
                foreach ($detalles as $detalle) {
                    $registros->push([
                        'fecha' => $fecha->copy(),
                        'categoria' =>
                            $detalle['motivo']
                            ?: $evento['clasificacion'],
                        'evento_id' => $evento['id'],
                        'unidad_id' => $evento['unidad_id'],
                        'cantidad' => 1,
                    ]);
                }

                continue;
            }

            $registros->push([
                'fecha' => $fecha->copy(),
                'categoria' =>
                    $evento['clasificacion']
                    ?: 'No definido',
                'evento_id' => $evento['id'],
                'unidad_id' => $evento['unidad_id'],
                'cantidad' => max(
                    (int) $evento['cantidad'],
                    1
                ),
            ]);
        }

        $categoriasOrden = [
            'Daño',
            'Desgaste',
            'Pérdida',
            'Manipulación detectada',
            'Corrección de instalación',
            'Apertura por abastecimiento',
            'Motivos múltiples',
            'No definido',
        ];

        $categoriasEncontradas = $registros
            ->pluck('categoria')
            ->filter()
            ->unique()
            ->sortBy(
                function (string $categoria) use (
                    $categoriasOrden
                ): int {
                    $posicion = array_search(
                        $categoria,
                        $categoriasOrden,
                        true
                    );

                    return $posicion === false
                        ? 999
                        : $posicion;
                }
            )
            ->values()
            ->all();

        return [
            'categorias' => $categoriasEncontradas,

            'resumen' => $this->agruparGraficoAuditoria(
                $registros,
                'resumen',
                $categoriasEncontradas
            ),

            'mes' => $this->agruparGraficoAuditoria(
                $registros,
                'mes',
                $categoriasEncontradas
            ),

            'semana' => $this->agruparGraficoAuditoria(
                $registros,
                'semana',
                $categoriasEncontradas
            ),

            'dia' => $this->agruparGraficoAuditoria(
                $registros,
                'dia',
                $categoriasEncontradas
            ),
        ];
    }

    private function agruparGraficoAuditoria(
        Collection $registros,
        string $agrupacion,
        array $categorias
    ): array {
        if ($registros->isEmpty()) {
            return [];
        }

        $grupos = $registros->groupBy(
            function (array $registro) use (
                $agrupacion
            ): string {
                /** @var Carbon $fecha */
                $fecha = $registro['fecha'];

                return match ($agrupacion) {
                    'mes' =>
                        $fecha->format('Y-m'),

                    'semana' =>
                        $fecha
                            ->copy()
                            ->startOfWeek(
                                Carbon::MONDAY
                            )
                            ->format('Y-m-d'),

                    'dia' =>
                        $fecha->format('Y-m-d'),

                    default =>
                        'resumen',
                };
            }
        );

        return $grupos
            ->map(
                function (
                    Collection $grupo,
                    string $clave
                ) use (
                    $agrupacion,
                    $categorias
                ): array {
                    $fechaReferencia = $grupo
                        ->first()['fecha']
                        ->copy();

                    $etiqueta = match ($agrupacion) {
                        'mes' =>
                            $this->etiquetaMes(
                                $fechaReferencia
                            ),

                        'semana' =>
                            $fechaReferencia
                                ->copy()
                                ->startOfWeek(
                                    Carbon::MONDAY
                                )
                                ->format('d/m/Y')
                            . ' – '
                            . $fechaReferencia
                                ->copy()
                                ->startOfWeek(
                                    Carbon::MONDAY
                                )
                                ->addDays(6)
                                ->format('d/m/Y'),

                        'dia' =>
                            $fechaReferencia
                                ->format('d/m/Y'),

                        default =>
                            'Período consultado',
                    };

                    $total = (int) $grupo->sum(
                        'cantidad'
                    );

                    $filasCategorias = collect(
                        $categorias
                    )
                        ->map(
                            function (
                                string $categoria
                            ) use (
                                $grupo,
                                $total
                            ): array {
                                $registrosCategoria =
                                    $grupo->where(
                                        'categoria',
                                        $categoria
                                    );

                                $cantidad = (int)
                                    $registrosCategoria
                                        ->sum('cantidad');

                                return [
                                    'categoria' =>
                                        $categoria,

                                    'marchamos' =>
                                        $cantidad,

                                    'porcentaje' =>
                                        $total > 0
                                            ? round(
                                                (
                                                    $cantidad
                                                    / $total
                                                ) * 100,
                                                2
                                            )
                                            : 0.0,

                                    'eventos' =>
                                        $registrosCategoria
                                            ->pluck(
                                                'evento_id'
                                            )
                                            ->unique()
                                            ->count(),

                                    'unidades' =>
                                        $registrosCategoria
                                            ->pluck(
                                                'unidad_id'
                                            )
                                            ->unique()
                                            ->count(),
                                ];
                            }
                        )
                        ->filter(
                            fn (array $fila): bool =>
                                $fila['marchamos'] > 0
                        )
                        ->values()
                        ->all();

                    return [
                        'clave' => $clave,
                        'etiqueta' => $etiqueta,
                        'total' => $total,
                        'categorias' => $filasCategorias,
                    ];
                }
            )
            ->sortBy('clave')
            ->values()
            ->all();
    }

    private function etiquetaMes(
        Carbon $fecha
    ): string {
        $meses = [
            1 => 'Ene',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Abr',
            5 => 'May',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Ago',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dic',
        ];

        return (
            $meses[(int) $fecha->format('n')]
            ?? $fecha->format('m')
        )
            . ' '
            . $fecha->format('Y');
    }

    private function obtenerEmpresasSelector(
        bool $esUsuarioDieselCop,
        int|string|null $empresaUsuarioId
    ): Collection {
        $empresaIdsConEventos =
            ReemplazoMarchamoEvento::query()
                ->when(
                    ! $esUsuarioDieselCop,
                    fn ($query) =>
                        $query->where(
                            'empresa_id',
                            $empresaUsuarioId
                        )
                )
                ->whereNotNull('empresa_id')
                ->distinct()
                ->pluck('empresa_id');

        return Empresa::query()
            ->whereIn(
                'id',
                $empresaIdsConEventos
            )
            ->when(
                ! $esUsuarioDieselCop,
                fn ($query) =>
                    $query->where(
                        'id',
                        $empresaUsuarioId
                    )
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
        $unidadIdsConEventos =
            ReemplazoMarchamoEvento::query()
                ->when(
                    ! $esUsuarioDieselCop,
                    fn ($query) =>
                        $query->where(
                            'empresa_id',
                            $empresaUsuarioId
                        )
                )
                ->when(
                    $empresaIds !== [],
                    fn ($query) =>
                        $query->whereIn(
                            'empresa_id',
                            $empresaIds
                        )
                )
                ->whereNotNull('unidad_id')
                ->distinct()
                ->pluck('unidad_id');

        return Unidad::query()
            ->with('empresa')
            ->whereIn(
                'id',
                $unidadIdsConEventos
            )
            ->when(
                ! $esUsuarioDieselCop,
                fn ($query) =>
                    $query->where(
                        'empresa_id',
                        $empresaUsuarioId
                    )
            )
            ->when(
                $empresaIds !== [],
                fn ($query) =>
                    $query->whereIn(
                        'empresa_id',
                        $empresaIds
                    )
            )
            ->orderBy('empresa_id')
            ->orderBy('placa')
            ->get();
    }

    private function obtenerUsuariosSelector(
        bool $esUsuarioDieselCop,
        int|string|null $empresaUsuarioId,
        array $empresaIds
    ): Collection {
        $ids = ReemplazoMarchamoEvento::query()
            ->when(
                ! $esUsuarioDieselCop,
                fn ($query) =>
                    $query->where(
                        'empresa_id',
                        $empresaUsuarioId
                    )
            )
            ->when(
                $empresaIds !== [],
                fn ($query) =>
                    $query->whereIn(
                        'empresa_id',
                        $empresaIds
                    )
            )
            ->whereNotNull('registrado_por')
            ->distinct()
            ->pluck('registrado_por');

        return User::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->orderBy('email')
            ->get();
    }

    private function normalizarIds(
        array $ids
    ): array {
        return collect($ids)
            ->filter(
                fn ($id): bool =>
                    filter_var(
                        $id,
                        FILTER_VALIDATE_INT
                    ) !== false
            )
            ->map(
                fn ($id): int =>
                    (int) $id
            )
            ->unique()
            ->values()
            ->all();
    }

    private function normalizarValores(
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
}