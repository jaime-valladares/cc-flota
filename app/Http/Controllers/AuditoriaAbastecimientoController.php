<?php

namespace App\Http\Controllers;

use App\Models\Abastecimiento;
use App\Models\Empresa;
use App\Models\Motorista;
use App\Models\Unidad;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditoriaAbastecimientoController extends Controller
{
    public function index(Request $request): View
    {
        return view('auditoria-abastecimientos.index', $this->prepararAuditoria($request));
    }

    public function indexVentana(Request $request): View
    {
        return view('auditoria-abastecimientos.index-ventana', $this->prepararAuditoria($request));
    }

    private function prepararAuditoria(Request $request): array
    {
        $user = Auth::user();
        $esUsuarioDieselCop = is_null($user->empresa_id);

        $validated = $request->validate([
            'empresa_ids' => ['nullable', 'array'],
            'empresa_ids.*' => ['integer', 'distinct', 'exists:empresas,id'],
            'unidad_ids' => ['nullable', 'array'],
            'unidad_ids.*' => ['integer', 'distinct', 'exists:unidades,id'],
            'motorista_ids' => ['nullable', 'array'],
            'motorista_ids.*' => ['integer', 'distinct', 'exists:motoristas,id'],
            'tipo_origen' => ['nullable', Rule::in([
                Abastecimiento::ORIGEN_INTERNO,
                Abastecimiento::ORIGEN_EXTERNO,
            ])],
            'estado' => ['nullable', Rule::in([
                Abastecimiento::ESTADO_REGISTRADO,
                Abastecimiento::ESTADO_ANULADO,
            ])],
            'fecha_desde' => ['nullable', 'date_format:Y-m-d'],
            'fecha_hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:fecha_desde'],
            'busqueda' => ['nullable', 'string', 'max:150'],
            'sort' => ['nullable', Rule::in([
                'fecha',
                'empresa',
                'unidad',
                'motorista',
                'origen',
                'galones',
                'hallazgos',
                'estado',
                'usuario',
            ])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ], [
            'empresa_ids.array' => 'La selección de empresas no es válida.',
            'empresa_ids.*.exists' => 'Una de las empresas seleccionadas no existe.',
            'unidad_ids.array' => 'La selección de unidades no es válida.',
            'unidad_ids.*.exists' => 'Una de las unidades seleccionadas no existe.',
            'motorista_ids.array' => 'La selección de motoristas no es válida.',
            'motorista_ids.*.exists' => 'Uno de los motoristas seleccionados no existe.',
            'tipo_origen.in' => 'El origen seleccionado no es válido.',
            'estado.in' => 'El estado seleccionado no es válido.',
            'fecha_hasta.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
        ]);

        $empresaIds = $this->normalizarIds($validated['empresa_ids'] ?? []);
        $unidadIds = $this->normalizarIds($validated['unidad_ids'] ?? []);
        $motoristaIds = $this->normalizarIds($validated['motorista_ids'] ?? []);

        if (! $esUsuarioDieselCop) {
            $empresaIds = [(int) $user->empresa_id];
        }

        $tipoOrigen = $validated['tipo_origen'] ?? null;
        $estado = $validated['estado'] ?? null;
        $fechaDesde = $validated['fecha_desde'] ?? null;
        $fechaHasta = $validated['fecha_hasta'] ?? null;
        $busqueda = trim((string) ($validated['busqueda'] ?? ''));
        $sort = (string) ($validated['sort'] ?? 'fecha');
        $direction = (string) ($validated['direction'] ?? 'desc');

        $hayFiltros = ! $esUsuarioDieselCop || $request->hasAny([
            'empresa_ids',
            'unidad_ids',
            'motorista_ids',
            'tipo_origen',
            'estado',
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

        $motoristasSelector = $this->obtenerMotoristasSelector(
            $esUsuarioDieselCop,
            $user->empresa_id,
            $empresaIds
        );

        $unidadIds = $this->filtrarIdsPermitidos($unidadIds, $unidadesSelector->pluck('id'));
        $motoristaIds = $this->filtrarIdsPermitidos($motoristaIds, $motoristasSelector->pluck('id'));

        $filas = collect();

        if ($hayFiltros) {
            $query = Abastecimiento::query()->with([
                'empresa',
                'unidad',
                'motorista',
                'registradoPor',
                'anuladoPor',
                'gasolineraInterna',
                'gasolineraExterna',
                'tanques',
                'rutas',
                'movimientosInventario.usuarioRegistra',
                'reemplazoMarchamoEvento.detalles',
                'abastecimientoAnterior',
            ]);

            if (! $esUsuarioDieselCop) {
                $query->where('empresa_id', $user->empresa_id);
            }

            if ($empresaIds !== []) {
                $query->whereIn('empresa_id', $empresaIds);
            }

            if ($unidadIds !== []) {
                $query->whereIn('unidad_id', $unidadIds);
            }

            if ($motoristaIds !== []) {
                $query->whereIn('motorista_id', $motoristaIds);
            }

            if ($tipoOrigen) {
                $query->where('tipo_origen', $tipoOrigen);
            }

            if ($estado) {
                $query->where('estado', $estado);
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
                    Carbon::createFromFormat('Y-m-d', $fechaHasta)->addDay()->startOfDay()
                );
            }

            if ($busqueda !== '') {
                $termino = '%' . $busqueda . '%';

                $query->where(function ($busquedaQuery) use ($termino): void {
                    $busquedaQuery
                        ->where('empresa_nombre_snapshot', 'like', $termino)
                        ->orWhere('unidad_placa_snapshot', 'like', $termino)
                        ->orWhere('unidad_marca_snapshot', 'like', $termino)
                        ->orWhere('unidad_modelo_snapshot', 'like', $termino)
                        ->orWhere('motorista_nombre_snapshot', 'like', $termino)
                        ->orWhere('origen_nombre_snapshot', 'like', $termino)
                        ->orWhere('motivo_anulacion', 'like', $termino);
                });
            }

            $filas = $query
                ->orderByDesc('fecha_hora_abastecimiento')
                ->orderByDesc('id')
                ->get()
                ->map(
                    fn (Abastecimiento $abastecimiento): array =>
                        $this->prepararFilaAuditoria($abastecimiento)
                );

        }

        $filas = $this->ordenarResultados($filas, $sort, $direction);
        $paginador = $this->paginarColeccion($filas, $request, 20);

        return [
            'abastecimientosAuditados' => $paginador,
            'empresasSelector' => $empresasSelector,
            'unidadesSelector' => $unidadesSelector,
            'motoristasSelector' => $motoristasSelector,
            'empresaIds' => $empresaIds,
            'unidadIds' => $unidadIds,
            'motoristaIds' => $motoristaIds,
            'tipoOrigen' => $tipoOrigen,
            'estado' => $estado,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'busqueda' => $busqueda,
            'sort' => $sort,
            'direction' => $direction,
            'hayFiltros' => $hayFiltros,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'resumen' => $this->obtenerResumen($filas),
            'graficos' => $this->obtenerGraficos($filas),
        ];
    }

    private function prepararFilaAuditoria(Abastecimiento $abastecimiento): array
    {
        $empresa = $abastecimiento->empresa_nombre_snapshot
            ?: $abastecimiento->empresa?->nombre_comercial
            ?: $abastecimiento->empresa?->nombre_legal
            ?: 'No disponible';

        $placa = $abastecimiento->unidad_placa_snapshot
            ?: $abastecimiento->unidad?->placa
            ?: 'No disponible';

        $motorista = $abastecimiento->motorista_nombre_snapshot
            ?: $abastecimiento->motorista?->nombre_completo
            ?: 'No disponible';

        $origenNombre = $abastecimiento->origen_nombre_snapshot
            ?: ($abastecimiento->esOrigenInterno()
                ? $abastecimiento->gasolineraInterna?->nombre
                : $abastecimiento->gasolineraExterna?->compania)
            ?: 'No disponible';

        $clasificaciones = collect();
        $tolerancia = 0.01;

        if (
            ! is_null($abastecimiento->combustible_adicional_no_explicado)
            && abs((float) $abastecimiento->combustible_adicional_no_explicado) > $tolerancia
        ) {
            $clasificaciones->push('discrepancia_combustible');
        }

        $inconsistenciaInventario = false;

        if ($abastecimiento->esOrigenInterno()) {
            if (
                $abastecimiento->tanques->isEmpty()
                || $abastecimiento->movimientosInventario->isEmpty()
            ) {
                $inconsistenciaInventario = true;
            }

            foreach ($abastecimiento->tanques as $detalleTanque) {
                $resultadoEsperado =
                    (float) $detalleTanque->inventario_anterior
                    - (float) $detalleTanque->galones_retirados;

                if (
                    abs($resultadoEsperado - (float) $detalleTanque->inventario_resultante) > $tolerancia
                    || (float) $detalleTanque->inventario_resultante < 0
                ) {
                    $inconsistenciaInventario = true;
                    break;
                }
            }

            $totalRetirado = (float) $abastecimiento->tanques->sum('galones_retirados');

            if (abs($totalRetirado - (float) $abastecimiento->volumen_cargado) > $tolerancia) {
                $inconsistenciaInventario = true;
            }

            $totalMovimientos = (float) $abastecimiento
                ->movimientosInventario
                ->where('tipo_movimiento', 'salida_abastecimiento')
                ->sum('volumen_movimiento');

            if (abs($totalMovimientos - (float) $abastecimiento->volumen_cargado) > $tolerancia) {
                $inconsistenciaInventario = true;
            }
        }

        if ($inconsistenciaInventario) {
            $clasificaciones->push('inconsistencia_inventario');
        }

        if (
            (! is_null($abastecimiento->diferencia_galones_teoricos)
                && abs((float) $abastecimiento->diferencia_galones_teoricos) > $tolerancia)
            || (! is_null($abastecimiento->diferencia_kilometros_teoricos)
                && abs((float) $abastecimiento->diferencia_kilometros_teoricos) > $tolerancia)
        ) {
            $clasificaciones->push('diferencia_ruta');
        }

        if ($abastecimiento->tanques->contains(
            fn ($tanque): bool => (bool) $tanque->quedo_bajo_minimo
        )) {
            $clasificaciones->push('bajo_minimo');
        }

        if (
            (int) ($abastecimiento->total_marchamos_reemplazados ?? 0) > 0
            || ! is_null($abastecimiento->reemplazoMarchamoEvento)
        ) {
            $clasificaciones->push('reemplazo_marchamos');
        }

        if ($abastecimiento->estaAnulado()) {
            $clasificaciones->push('anulado');
        }

        if (
            is_null($abastecimiento->fecha_hora_abastecimiento)
            || is_null($abastecimiento->empresa_id)
            || is_null($abastecimiento->unidad_id)
            || is_null($abastecimiento->motorista_id)
            || is_null($abastecimiento->tipo_origen)
            || is_null($abastecimiento->volumen_cargado)
        ) {
            $clasificaciones->push('informacion_incompleta');
        }

        if ($clasificaciones->isEmpty()) {
            $clasificaciones->push('correcto');
        }

        $usuario = $abastecimiento->registradoPor?->name ?: 'No disponible';

        return [
            'id' => (int) $abastecimiento->id,
            'modelo' => $abastecimiento,
            'fecha' => $abastecimiento->fecha_hora_abastecimiento,
            'empresa' => $empresa,
            'unidad' => $placa,
            'motorista' => $motorista,
            'tipo_origen' => $abastecimiento->tipo_origen,
            'origen' => $origenNombre,
            'galones' => round(
                (float) (
                    $abastecimiento->volumen_cargado
                    ?? 0
                ),
                2
            ),
            'volumen_inicial' => round(
                (float) (
                    $abastecimiento->volumen_inicial
                    ?? 0
                ),
                2
            ),
            'volumen_final' => round(
                (float) (
                    $abastecimiento->volumen_final
                    ?? 0
                ),
                2
            ),
            'combustible_consumido' => is_null(
                $abastecimiento->combustible_consumido_ciclo
            )
                ? null
                : round(
                    (float) $abastecimiento
                        ->combustible_consumido_ciclo,
                    2
                ),
            'estado' => $abastecimiento->estado,
            'usuario' => $usuario,
            'clasificaciones' => $clasificaciones->unique()->values()->all(),
            'hallazgos' => $clasificaciones
                ->reject(fn (string $valor): bool => $valor === 'correcto')
                ->count(),
            'detalle' => [
                'tanques' => $abastecimiento->tanques,
                'rutas' => $abastecimiento->rutas,
                'movimientos' => $abastecimiento->movimientosInventario,
                'marchamos' => $abastecimiento->reemplazoMarchamoEvento,
                'anulacion' => [
                    'fecha' => $abastecimiento->fecha_anulacion,
                    'usuario' => $abastecimiento->anuladoPor?->name,
                    'motivo' => $abastecimiento->motivo_anulacion,
                ],
            ],
        ];
    }

    private function obtenerResumen(Collection $filas): array
    {
        $internos = $filas->where(
            'tipo_origen',
            Abastecimiento::ORIGEN_INTERNO
        );

        $externos = $filas->where(
            'tipo_origen',
            Abastecimiento::ORIGEN_EXTERNO
        );

        return [
            'abastecimientos' => $filas->count(),
            'galones' => round(
                (float) $filas->sum('galones'),
                2
            ),
            'galones_internos' => round(
                (float) $internos->sum('galones'),
                2
            ),
            'galones_externos' => round(
                (float) $externos->sum('galones'),
                2
            ),
        ];
    }


private function obtenerGraficos(
    Collection $filas
): array {
    $tendencia = $filas
        ->filter(
            fn (array $fila): bool =>
                ! is_null($fila['fecha'])
        )
        ->groupBy(
            fn (array $fila): string =>
                $fila['fecha']->format('Y-m-d')
        )
        ->map(
            function (
                Collection $grupo,
                string $fecha
            ): array {
                $internos = $grupo->where(
                    'tipo_origen',
                    Abastecimiento::ORIGEN_INTERNO
                );

                $externos = $grupo->where(
                    'tipo_origen',
                    Abastecimiento::ORIGEN_EXTERNO
                );

                return [
                    'fecha' => $fecha,
                    'etiqueta' => Carbon::parse(
                        $fecha
                    )->format('d/m'),
                    'interno' => round(
                        (float) $internos->sum('galones'),
                        2
                    ),
                    'externo' => round(
                        (float) $externos->sum('galones'),
                        2
                    ),
                    'total' => round(
                        (float) $grupo->sum('galones'),
                        2
                    ),
                    'operaciones' => $grupo->count(),
                ];
            }
        )
        ->sortBy('fecha')
        ->values()
        ->all();

    $unidades = $filas
        ->groupBy('unidad')
        ->map(
            fn (
                Collection $grupo,
                string $unidad
            ): array => [
                'etiqueta' => $unidad,
                'galones' => round(
                    (float) $grupo->sum('galones'),
                    2
                ),
                'operaciones' => $grupo->count(),
                'promedio' => round(
                    (float) $grupo->avg('galones'),
                    2
                ),
            ]
        )
        ->sortByDesc('galones')
        ->take(12)
        ->values()
        ->all();

    $empresas = $filas
        ->groupBy('empresa')
        ->map(
            fn (
                Collection $grupo,
                string $empresa
            ): array => [
                'etiqueta' => $empresa,
                'galones' => round(
                    (float) $grupo->sum('galones'),
                    2
                ),
                'operaciones' => $grupo->count(),
                'hallazgos' => $grupo
                    ->filter(
                        fn (array $fila): bool =>
                            $fila['hallazgos'] > 0
                    )
                    ->count(),
            ]
        )
        ->sortByDesc('galones')
        ->take(10)
        ->values()
        ->all();

    $origenes = collect([
        Abastecimiento::ORIGEN_INTERNO,
        Abastecimiento::ORIGEN_EXTERNO,
    ])
        ->map(
            function (string $origen) use ($filas): array {
                $grupo = $filas->where(
                    'tipo_origen',
                    $origen
                );

                return [
                    'clave' => $origen,
                    'etiqueta' =>
                        $origen ===
                        Abastecimiento::ORIGEN_INTERNO
                            ? 'Origen interno'
                            : 'Origen externo',
                    'operaciones' => $grupo->count(),
                    'galones' => round(
                        (float) $grupo->sum('galones'),
                        2
                    ),
                ];
            }
        )
        ->all();

    return [
        'tendencia' => $tendencia,
        'unidades' => $unidades,
        'empresas' => $empresas,
        'origenes' => $origenes,
    ];
}

    private function ordenarResultados(
        Collection $filas,
        string $sort,
        string $direction
    ): Collection {
        $desc = $direction === 'desc';

        $campo = match ($sort) {
            'empresa' => 'empresa',
            'unidad' => 'unidad',
            'motorista' => 'motorista',
            'origen' => 'origen',
            'galones' => 'galones',
            'hallazgos' => 'hallazgos',
            'estado' => 'estado',
            'usuario' => 'usuario',
            default => 'fecha',
        };

        return $filas
            ->sortBy($campo, SORT_NATURAL | SORT_FLAG_CASE, $desc)
            ->values();
    }

    private function paginarColeccion(
        Collection $resultados,
        Request $request,
        int $porPagina
    ): LengthAwarePaginator {
        $pagina = max(1, (int) $request->integer('page', 1));

        return new LengthAwarePaginator(
            $resultados->forPage($pagina, $porPagina)->values(),
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
            return Empresa::query()->whereKey($empresaUsuarioId)->get();
        }

        return Empresa::query()
            ->whereHas('abastecimientos')
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
            ->whereHas('abastecimientos')
            ->when(
                ! $esUsuarioDieselCop,
                fn ($query) => $query->where('empresa_id', $empresaUsuarioId)
            )
            ->when(
                $empresaIds !== [],
                fn ($query) => $query->whereIn('empresa_id', $empresaIds)
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
            ->whereHas('abastecimientos')
            ->when(
                ! $esUsuarioDieselCop,
                fn ($query) => $query->where('empresa_id', $empresaUsuarioId)
            )
            ->when(
                $empresaIds !== [],
                fn ($query) => $query->whereIn('empresa_id', $empresaIds)
            )
            ->orderBy('empresa_id')
            ->orderBy('nombres')
            ->orderBy('apellidos')
            ->get();
    }

    private function normalizarIds(array $ids): array
    {
        return collect($ids)
            ->filter(
                fn ($id): bool =>
                    filter_var($id, FILTER_VALIDATE_INT) !== false
            )
            ->map(fn ($id): int => (int) $id)
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
                    in_array((int) $id, $permitidos, true)
            )
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }
}