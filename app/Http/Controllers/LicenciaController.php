<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Licencia;
use App\Models\PuntoSeguridadUnidad;
use App\Models\Unidad;
use App\Models\UnidadTanque;
use App\Support\PlantillasPuntosSeguridad;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LicenciaController extends Controller
{
    /**
     * Consulta informativa de licencias dentro del sistema.
     *
     * Disponible para Diesel Cop y usuarios empresariales.
     */
    public function index(Request $request): View
    {
        $data = $this->prepararConsultaLicencias(
            request: $request,
            soloEmpresasActivas: false
        );

        return view('licencias.index', $data);
    }

    /**
     * Consulta informativa de licencias en ventana independiente.
     *
     * Disponible para Diesel Cop y usuarios empresariales.
     */
    public function consultaVentana(Request $request): View
    {
        $data = $this->prepararConsultaLicencias(
            request: $request,
            soloEmpresasActivas: false
        );

        return view('licencias.index-ventana', $data);
    }

    /**
     * Búsqueda administrativa de licencias.
     *
     * Disponible únicamente para Diesel Cop.
     */
    public function administrar(Request $request): View
    {
        $data = $this->prepararConsultaLicencias(
            request: $request,
            soloEmpresasActivas: true
        );

        return view('licencias.administrar', $data);
    }

    /**
     * Búsqueda administrativa de licencias en ventana independiente.
     *
     * Disponible únicamente para Diesel Cop.
     */
    public function administrarVentana(Request $request): View
    {
        $data = $this->prepararConsultaLicencias(
            request: $request,
            soloEmpresasActivas: true
        );

        return view('licencias.administrar-ventana', $data);
    }

    /**
     * Prepara filtros, catálogos, resúmenes y resultados.
     */
    private function prepararConsultaLicencias(
        Request $request,
        bool $soloEmpresasActivas
    ): array {
        $validated = $request->validate([
            'consultar' => [
                'nullable',
                'boolean',
            ],
            'busqueda' => [
                'nullable',
                'string',
                'max:150',
            ],
            'empresa_ids' => [
                'nullable',
                'array',
            ],
            'empresa_ids.*' => [
                'integer',
                Rule::exists('empresas', 'id'),
            ],
            'empresa_id' => [
                'nullable',
                'integer',
                Rule::exists('empresas', 'id'),
            ],
            'unidad_ids' => [
                'nullable',
                'array',
            ],
            'unidad_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('unidades', 'id'),
            ],
            'unidad_id' => [
                'nullable',
                'integer',
                Rule::exists('unidades', 'id'),
            ],
            'periodos_vigencia' => [
                'nullable',
                'array',
            ],
            'periodos_vigencia.*' => [
                'integer',
                Rule::in(array_keys($this->periodosVigencia())),
            ],
            'periodo_vigencia_meses' => [
                'nullable',
                'integer',
                Rule::in(array_keys($this->periodosVigencia())),
            ],
            'estado' => [
                'nullable',
                'string',
                Rule::in([
                    'activa',
                    'inactiva',
                ]),
            ],
        ]);

        $user = Auth::user();
        $esUsuarioDieselCop = $this->esUsuarioDieselCop();

        $busqueda = trim(
            (string) ($validated['busqueda'] ?? '')
        );

        /*
         * Compatibilidad entre filtros múltiples y filtros simples.
         */
        $empresaIds = collect($validated['empresa_ids'] ?? [])
            ->when(
                filled($validated['empresa_id'] ?? null),
                function ($collection) use ($validated) {
                    return $collection->push(
                        $validated['empresa_id']
                    );
                }
            )
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $unidadIds = collect($validated['unidad_ids'] ?? [])
            ->when(
                filled($validated['unidad_id'] ?? null),
                function ($collection) use ($validated) {
                    return $collection->push(
                        $validated['unidad_id']
                    );
                }
            )
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $periodosVigenciaSeleccionados = collect(
            $validated['periodos_vigencia'] ?? []
        )
            ->when(
                filled($validated['periodo_vigencia_meses'] ?? null),
                function ($collection) use ($validated) {
                    return $collection->push(
                        $validated['periodo_vigencia_meses']
                    );
                }
            )
            ->filter(fn ($periodo) => filled($periodo))
            ->map(fn ($periodo) => (int) $periodo)
            ->filter(
                fn ($periodo) => array_key_exists(
                    $periodo,
                    $this->periodosVigencia()
                )
            )
            ->unique()
            ->values()
            ->all();

        $estado = $validated['estado'] ?? null;

        /*
         * El usuario empresarial solo puede consultar registros
         * pertenecientes a su propia empresa.
         */
        if (! $esUsuarioDieselCop) {
            $empresaIds = [
                (int) $user->empresa_id,
            ];
        }

        /*
         * Variables simples conservadas para las vistas administrativas
         * y para compatibilidad con los formularios actuales.
         */
        $empresaId = $empresaIds[0] ?? null;
        $unidadId = $unidadIds[0] ?? null;
        $periodoVigencia = $periodosVigenciaSeleccionados[0] ?? null;

        /*
         * La empresa obligatoria del usuario empresarial define alcance,
         * pero no ejecuta automáticamente la consulta.
         */
        $hayFiltros = $request->boolean('consultar')
            || filled($busqueda)
            || count($unidadIds) > 0
            || count($periodosVigenciaSeleccionados) > 0
            || filled($estado)
            || (
                $esUsuarioDieselCop
                && count($empresaIds) > 0
            );

        $empresas = Empresa::query()
            ->when(
                ! $esUsuarioDieselCop,
                function ($query) use ($user) {
                    $query->where(
                        'id',
                        $user->empresa_id
                    );
                }
            )
            ->when(
                $soloEmpresasActivas,
                function ($query) {
                    $query->where(
                        'estado',
                        'activa'
                    );
                }
            )
            ->orderBy('nombre_comercial')
            ->orderBy('nombre_legal')
            ->get();

        $baseQuery = Licencia::query()
            ->with([
                'empresa',
                'unidad',
            ])
            ->when(
                ! $esUsuarioDieselCop,
                function ($query) use ($user) {
                    $query->where(
                        'empresa_id',
                        $user->empresa_id
                    );
                }
            )
            ->when(
                $soloEmpresasActivas,
                function ($query) {
                    $query->whereHas(
                        'empresa',
                        function ($empresaQuery) {
                            $empresaQuery->where(
                                'estado',
                                'activa'
                            );
                        }
                    );
                }
            );

        /*
         * El selector se construye desde una consulta propia para evitar
         * ambigüedad de columnas al unir licencias y unidades. El alcance
         * empresarial debe calificarse explícitamente como licencias.empresa_id.
         */
        $unidadesSelector = Licencia::query()
            ->join(
                'unidades',
                'licencias.unidad_id',
                '=',
                'unidades.id'
            )
            ->join(
                'empresas',
                'licencias.empresa_id',
                '=',
                'empresas.id'
            )
            ->when(
                ! $esUsuarioDieselCop,
                function ($query) use ($user) {
                    $query->where(
                        'licencias.empresa_id',
                        $user->empresa_id
                    );
                }
            )
            ->when(
                $soloEmpresasActivas,
                function ($query) {
                    $query->where(
                        'empresas.estado',
                        'activa'
                    );
                }
            )
            ->select([
                'unidades.id',
                'unidades.placa',
                DB::raw(
                    'COALESCE(empresas.nombre_comercial, '
                    .'empresas.nombre_legal) as empresa_nombre'
                ),
            ])
            ->orderBy('empresa_nombre')
            ->orderBy('unidades.placa')
            ->distinct()
            ->get();

        $hoy = now()->toDateString();

        $totalLicencias = (clone $baseQuery)->count();

        $totalActivas = (clone $baseQuery)
            ->where('estado', 'activa')
            ->count();

        $totalInactivas = (clone $baseQuery)
            ->where('estado', 'inactiva')
            ->count();

        $totalVigentes = (clone $baseQuery)
            ->where('estado', 'activa')
            ->whereDate(
                'fecha_activacion',
                '<=',
                $hoy
            )
            ->whereDate(
                'fecha_vencimiento',
                '>=',
                $hoy
            )
            ->count();

        $totalPendientesActivacion = (clone $baseQuery)
            ->where('estado', 'activa')
            ->whereDate(
                'fecha_activacion',
                '>',
                $hoy
            )
            ->count();

        $totalVencidas = (clone $baseQuery)
            ->where('estado', 'activa')
            ->whereDate(
                'fecha_vencimiento',
                '<',
                $hoy
            )
            ->count();

        $licenciasQuery = Licencia::query()
            ->with([
                'empresa',
                'unidad',
            ])
            ->join(
                'unidades',
                'licencias.unidad_id',
                '=',
                'unidades.id'
            )
            ->join(
                'empresas',
                'licencias.empresa_id',
                '=',
                'empresas.id'
            )
            ->select('licencias.*')
            ->when(
                ! $esUsuarioDieselCop,
                function ($query) use ($user) {
                    $query->where(
                        'licencias.empresa_id',
                        $user->empresa_id
                    );
                }
            )
            ->when(
                $soloEmpresasActivas,
                function ($query) {
                    $query->where(
                        'empresas.estado',
                        'activa'
                    );
                }
            )
            ->when(
                $hayFiltros && filled($busqueda),
                function ($query) use ($busqueda) {
                    $query->where(
                        function ($subQuery) use ($busqueda) {
                            $subQuery
                                ->where(
                                    'unidades.placa',
                                    'like',
                                    '%'.$busqueda.'%'
                                )
                                ->orWhere(
                                    'empresas.nombre_legal',
                                    'like',
                                    '%'.$busqueda.'%'
                                )
                                ->orWhere(
                                    'empresas.nombre_comercial',
                                    'like',
                                    '%'.$busqueda.'%'
                                );
                        }
                    );
                }
            )
            ->when(
                $hayFiltros && count($empresaIds) > 0,
                function ($query) use ($empresaIds) {
                    $query->whereIn(
                        'licencias.empresa_id',
                        $empresaIds
                    );
                }
            )
            ->when(
                $hayFiltros && count($unidadIds) > 0,
                function ($query) use ($unidadIds) {
                    $query->whereIn(
                        'unidades.id',
                        $unidadIds
                    );
                }
            )
            ->when(
                $hayFiltros
                    && count($periodosVigenciaSeleccionados) > 0,
                function ($query) use (
                    $periodosVigenciaSeleccionados
                ) {
                    $query->whereIn(
                        'licencias.periodo_vigencia_meses',
                        $periodosVigenciaSeleccionados
                    );
                }
            )
            ->when(
                $hayFiltros && filled($estado),
                function ($query) use ($estado) {
                    $query->where(
                        'licencias.estado',
                        $estado
                    );
                }
            )
            ->when(
                ! $hayFiltros,
                function ($query) {
                    $query->whereRaw('1 = 0');
                }
            );

        $resumenLicencias = [
            'total' => $hayFiltros
                ? (clone $licenciasQuery)
                    ->count('licencias.id')
                : $totalLicencias,

            'activas' => $hayFiltros
                ? (clone $licenciasQuery)
                    ->where(
                        'licencias.estado',
                        'activa'
                    )
                    ->count('licencias.id')
                : $totalActivas,

            'inactivas' => $hayFiltros
                ? (clone $licenciasQuery)
                    ->where(
                        'licencias.estado',
                        'inactiva'
                    )
                    ->count('licencias.id')
                : $totalInactivas,

            'vigentes' => $hayFiltros
                ? (clone $licenciasQuery)
                    ->where(
                        'licencias.estado',
                        'activa'
                    )
                    ->whereDate(
                        'licencias.fecha_activacion',
                        '<=',
                        $hoy
                    )
                    ->whereDate(
                        'licencias.fecha_vencimiento',
                        '>=',
                        $hoy
                    )
                    ->count('licencias.id')
                : $totalVigentes,

            'pendientes_activacion' => $hayFiltros
                ? (clone $licenciasQuery)
                    ->where(
                        'licencias.estado',
                        'activa'
                    )
                    ->whereDate(
                        'licencias.fecha_activacion',
                        '>',
                        $hoy
                    )
                    ->count('licencias.id')
                : $totalPendientesActivacion,

            'vencidas' => $hayFiltros
                ? (clone $licenciasQuery)
                    ->where(
                        'licencias.estado',
                        'activa'
                    )
                    ->whereDate(
                        'licencias.fecha_vencimiento',
                        '<',
                        $hoy
                    )
                    ->count('licencias.id')
                : $totalVencidas,
        ];

        $licencias = $licenciasQuery
            ->orderBy('unidades.placa')
            ->paginate(10)
            ->withQueryString();

        return [
            'licencias' => $licencias,
            'empresas' => $empresas,

            /*
             * Variables múltiples.
             */
            'empresaIds' => $empresaIds,
            'unidadIds' => $unidadIds,
            'periodosVigenciaSeleccionados' => $periodosVigenciaSeleccionados,

            /*
             * Variables simples.
             */
            'empresaId' => $empresaId,
            'unidadId' => $unidadId,
            'periodoVigencia' => $periodoVigencia,
            'estado' => $estado,

            'unidadesSelector' => $unidadesSelector,
            'busqueda' => $busqueda,
            'hayFiltros' => $hayFiltros,

            'totalLicencias' => $totalLicencias,
            'totalActivas' => $totalActivas,
            'totalInactivas' => $totalInactivas,
            'totalVigentes' => $totalVigentes,
            'totalPendientesActivacion' => $totalPendientesActivacion,
            'totalVencidas' => $totalVencidas,

            'resumenLicencias' => $resumenLicencias,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'periodosVigencia' => $this->periodosVigencia(),
        ];
    }

    /**
     * Formulario de creación de licencia.
     *
     * Disponible únicamente para Diesel Cop.
     */
    public function create(Request $request): View
    {
        $data = $this->prepararFormularioLicencia($request);

        return view('licencias.create', $data);
    }

    /**
     * Formulario de creación de licencia en ventana independiente.
     *
     * Disponible únicamente para Diesel Cop.
     */
    public function createVentana(Request $request): View
    {
        $data = $this->prepararFormularioLicencia($request);

        return view('licencias.create-ventana', $data);
    }

    /**
     * Prepara empresas y unidades elegibles para una licencia nueva.
     */
    private function prepararFormularioLicencia(
        Request $request
    ): array {
        $empresaSeleccionadaId = $request->input(
            'empresa_id'
        );

        $empresas = Empresa::query()
            ->where(
                'estado',
                'activa'
            )
            ->orderBy('nombre_comercial')
            ->orderBy('nombre_legal')
            ->get();

        $empresaSeleccionadaValida =
            filled($empresaSeleccionadaId)
            && $empresas->contains(
                'id',
                (int) $empresaSeleccionadaId
            );

        if (! $empresaSeleccionadaValida) {
            $empresaSeleccionadaId = null;
        }

        $unidades = Unidad::query()
            ->with([
                'empresa',
                'licencia',
                'tanquesUnidad',
            ])
            ->where(
                'estado',
                'registrada'
            )
            ->whereDoesntHave('licencia')
            ->when(
                filled($empresaSeleccionadaId),
                function ($query) use (
                    $empresaSeleccionadaId
                ) {
                    $query->where(
                        'empresa_id',
                        $empresaSeleccionadaId
                    );
                }
            )
            ->when(
                ! filled($empresaSeleccionadaId),
                function ($query) {
                    $query->whereRaw('1 = 0');
                }
            )
            ->whereHas(
                'empresa',
                function ($query) {
                    $query->where(
                        'estado',
                        'activa'
                    );
                }
            )
            ->orderBy('placa')
            ->get();

        return [
            'empresas' => $empresas,
            'unidades' => $unidades,
            'empresaSeleccionadaId' => $empresaSeleccionadaId,
            'esUsuarioDieselCop' => true,
            'periodosVigencia' => $this->periodosVigencia(),
        ];
    }

    /**
     * Guarda una nueva licencia y genera los puntos de seguridad iniciales.
     *
     * La unidad permanece en estado registrada hasta completar
     * la asignación inicial de marchamos.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            $this->reglasValidacionCrearLicencia($request)
        );

        $unidad = Unidad::query()
            ->with([
                'empresa',
                'licencia',
                'puntosSeguridad',
            ])
            ->findOrFail(
                $validated['unidad_id']
            );

        if (
            (int) $unidad->empresa_id
            !== (int) $validated['empresa_id']
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'unidad_id' => 'La unidad seleccionada no pertenece a la empresa indicada.',
                ]);
        }

        if ($unidad->licencia) {
            return back()
                ->withInput()
                ->withErrors([
                    'unidad_id' => 'Esta unidad ya tiene una licencia registrada.',
                ]);
        }

        if ($unidad->estado !== 'registrada') {
            return back()
                ->withInput()
                ->withErrors([
                    'unidad_id' => 'Solo se puede crear una licencia para unidades registradas pendientes de configuración.',
                ]);
        }

        if (
            ! $unidad->empresa
            || $unidad->empresa->estado !== 'activa'
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'unidad_id' => 'La empresa asociada a la unidad debe estar activa.',
                ]);
        }

        if ($unidad->puntosSeguridad()->exists()) {
            return back()
                ->withInput()
                ->withErrors([
                    'unidad_id' => 'Esta unidad ya tiene puntos de seguridad generados.',
                ]);
        }

        $tanqueIds = collect($validated['tanques_cubiertos'])
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $tanquesCubiertos = $unidad->tanquesUnidad()
            ->whereIn('id', $tanqueIds)
            ->orderBy('numero')
            ->get();

        if (
            $tanquesCubiertos->isEmpty()
            || $tanquesCubiertos->count() !== $tanqueIds->count()
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'tanques_cubiertos' => 'Todos los tanques seleccionados deben pertenecer a la unidad indicada.',
                ]);
        }

        $fechaActivacion = Carbon::parse(
            $validated['fecha_activacion']
        )->startOfDay();

        $periodoVigencia = (int) $validated[
            'periodo_vigencia_meses'
        ];

        $plantilla = $this->plantillaDesdeTanquesProtegidos(
            $tanquesCubiertos->count()
        );

        $licencia = DB::transaction(
            function () use (
                $unidad,
                $fechaActivacion,
                $periodoVigencia,
                $plantilla,
                $tanqueIds
            ): Licencia {
                $unidadBloqueada = Unidad::query()
                    ->with('empresa')
                    ->lockForUpdate()
                    ->findOrFail($unidad->id);

                if (
                    $unidadBloqueada->estado !== 'registrada'
                    || ! $unidadBloqueada->empresa
                    || $unidadBloqueada->empresa->estado !== 'activa'
                    || Licencia::query()->where('unidad_id', $unidadBloqueada->id)->exists()
                ) {
                    throw ValidationException::withMessages([
                        'unidad_id' => 'La unidad dejó de ser elegible para registrar una licencia.',
                    ]);
                }

                $tanquesBloqueados = UnidadTanque::query()
                    ->where('unidad_id', $unidadBloqueada->id)
                    ->whereIn('id', $tanqueIds)
                    ->orderBy('numero')
                    ->lockForUpdate()
                    ->get();

                if ($tanquesBloqueados->count() !== $tanqueIds->count()) {
                    throw ValidationException::withMessages([
                        'tanques_cubiertos' => 'La selección de tanques cambió o no pertenece a la unidad.',
                    ]);
                }

                if ($unidadBloqueada->puntosSeguridad()->exists()) {
                    throw ValidationException::withMessages([
                        'unidad_id' => 'Esta unidad ya tiene puntos de seguridad generados.',
                    ]);
                }

                $capacidadCubierta = round(
                    (float) $tanquesBloqueados->sum('capacidad'),
                    2
                );

                $licenciaCreada = Licencia::create([
                    'empresa_id' => $unidadBloqueada->empresa_id,
                    'unidad_id' => $unidadBloqueada->id,
                    'periodo_vigencia_meses' => $periodoVigencia,
                    'fecha_activacion' => $fechaActivacion->toDateString(),
                    'fecha_vencimiento' => $fechaActivacion
                        ->copy()
                        ->addMonthsNoOverflow(
                            $periodoVigencia
                        )
                        ->toDateString(),
                    'estado' => 'activa',
                    'plantilla_puntos_seguridad' => $plantilla,
                    'creado_por' => Auth::id(),
                    'actualizado_por' => Auth::id(),
                ]);

                $licenciaCreada->tanquesCubiertos()->createMany(
                    $tanquesBloqueados->map(fn (UnidadTanque $tanque): array => [
                        'unidad_tanque_id' => $tanque->id,
                        'numero_tanque_snapshot' => $tanque->numero,
                        'capacidad_snapshot' => $tanque->capacidad,
                    ])->all()
                );

                // Caché legacy para M4 durante la transición; no es fuente contractual.
                $unidadBloqueada->update([
                    'cantidad_tanques_con_licencia' => $tanquesBloqueados->count(),
                    'capacidad_cubierta' => $capacidadCubierta,
                    'actualizado_por' => Auth::id(),
                ]);
                $unidadBloqueada->tanquesUnidad()->update([
                    'cubierto_por_licencia' => false,
                ]);
                UnidadTanque::query()
                    ->whereIn('id', $tanqueIds)
                    ->update(['cubierto_por_licencia' => true]);

                foreach (
                    PlantillasPuntosSeguridad::porPlantilla(
                        $plantilla
                    ) as $punto
                ) {
                    PuntoSeguridadUnidad::create([
                        'unidad_id' => $unidadBloqueada->id,
                        'orden' => $punto['orden_visual']
                            ?? $punto['orden']
                            ?? null,
                        'codigo_punto' => $punto['codigo_punto']
                            ?? null,
                        'grupo' => $punto['grupo']
                            ?? null,
                        'subgrupo' => $punto['subgrupo']
                            ?? null,
                        'nombre_punto' => $punto['nombre_punto']
                            ?? $punto['nombre']
                            ?? 'Punto sin nombre',
                        'descripcion' => null,
                        'posicion_tanque' => $punto['posicion_tanque']
                            ?? null,
                        'tipo_punto' => $punto['tipo_punto']
                            ?? null,
                        'requiere_marchamo' => (bool) (
                            $punto['requiere_marchamo']
                            ?? true
                        ),
                        'plantilla_origen' => $plantilla,
                        'criterio_origen' => $punto['criterio_origen']
                            ?? null,
                        'estado_asignacion' => 'pendiente',
                        'marchamo_actual_id' => null,
                        'estado' => 'activo',
                        'creado_por' => Auth::id(),
                        'actualizado_por' => Auth::id(),
                    ]);
                }

                return $licenciaCreada;
            }
        );

        $queryParams = $this->parametrosRetorno($request);

        if (
            $request->input('return_to')
            === 'ventana'
        ) {
            return redirect()
                ->route(
                    'licencias.show.ventana',
                    array_merge(
                        $queryParams,
                        [
                            'licencia' => $licencia,
                        ]
                    )
                )
                ->with(
                    'success',
                    'Licencia creada correctamente. Los puntos de seguridad fueron generados.'
                );
        }

        return redirect()
            ->route(
                'licencias.show',
                array_merge(
                    $queryParams,
                    [
                        'licencia' => $licencia,
                    ]
                )
            )
            ->with(
                'success',
                'Licencia creada correctamente. Los puntos de seguridad fueron generados.'
            );
    }

    /**
     * Ficha administrativa de la licencia.
     *
     * Disponible únicamente para Diesel Cop.
     */
    public function show(
        Request $request,
        Licencia $licencia
    ): View {
        $this->validarEmpresaActivaLicencia($licencia);

        $this->cargarRelacionesFicha($licencia);

        return view(
            'licencias.show',
            compact('licencia')
        );
    }

    /**
     * Ficha administrativa en ventana independiente.
     *
     * Disponible únicamente para Diesel Cop.
     */
    public function showVentana(
        Request $request,
        Licencia $licencia
    ): View {
        $this->validarEmpresaActivaLicencia($licencia);

        $this->cargarRelacionesFicha($licencia);

        return view(
            'licencias.show-ventana',
            compact('licencia')
        );
    }

    /**
     * Formulario de edición.
     *
     * Solo puede editarse una licencia activa, no vencida
     * y perteneciente a una empresa activa.
     */
    public function edit(
        Request $request,
        Licencia $licencia
    ): View {
        $this->validarLicenciaEditable($licencia);

        $licencia->load([
            'empresa',
            'unidad',
        ]);

        return view('licencias.edit', [
            'licencia' => $licencia,
            'periodosVigencia' => $this->periodosVigencia(),
            'esUsuarioDieselCop' => true,
        ]);
    }

    /**
     * Formulario de edición en ventana independiente.
     */
    public function editVentana(
        Request $request,
        Licencia $licencia
    ): View {
        $this->validarLicenciaEditable($licencia);

        $licencia->load([
            'empresa',
            'unidad',
        ]);

        return view('licencias.edit-ventana', [
            'licencia' => $licencia,
            'periodosVigencia' => $this->periodosVigencia(),
            'esUsuarioDieselCop' => true,
        ]);
    }

    /**
     * Actualiza una licencia activa y no vencida.
     */
    public function update(
        Request $request,
        Licencia $licencia
    ): RedirectResponse {
        $this->validarLicenciaEditable($licencia);

        $validated = $request->validate(
            $this->reglasValidacionActualizarLicencia()
        );

        $fechaActivacion = Carbon::parse(
            $validated['fecha_activacion']
        )->startOfDay();

        $periodoVigencia = (int) $validated[
            'periodo_vigencia_meses'
        ];

        $licencia->update([
            'periodo_vigencia_meses' => $periodoVigencia,
            'fecha_activacion' => $fechaActivacion->toDateString(),
            'fecha_vencimiento' => $fechaActivacion
                ->copy()
                ->addMonthsNoOverflow(
                    $periodoVigencia
                )
                ->toDateString(),
            'actualizado_por' => Auth::id(),
        ]);

        $queryParams = $this->parametrosRetorno($request);

        if (
            $request->input('return_to')
            === 'ventana'
        ) {
            return redirect()
                ->route(
                    'licencias.show.ventana',
                    array_merge(
                        $queryParams,
                        [
                            'licencia' => $licencia,
                        ]
                    )
                )
                ->with(
                    'success',
                    'Licencia actualizada correctamente.'
                );
        }

        return redirect()
            ->route(
                'licencias.show',
                array_merge(
                    $queryParams,
                    [
                        'licencia' => $licencia,
                    ]
                )
            )
            ->with(
                'success',
                'Licencia actualizada correctamente.'
            );
    }

    /**
     * Inactiva administrativamente una licencia.
     */
    public function inactivar(
        Request $request,
        Licencia $licencia
    ): RedirectResponse {
        $this->validarEmpresaActivaLicencia($licencia);

        if ($licencia->estado === 'inactiva') {
            return back()->withErrors([
                'motivo_inactivacion' => 'La licencia ya se encuentra inactiva.',
            ]);
        }

        $validated = $request->validate([
            'motivo_inactivacion' => [
                'required',
                'string',
                'max:150',
                Rule::in(
                    $this->motivosInactivacion()
                ),
            ],
        ]);

        $licencia->update([
            'estado' => 'inactiva',
            'fecha_inactivacion' => now(),
            'inactivado_por' => Auth::id(),
            'motivo_inactivacion' => $validated['motivo_inactivacion'],
            'actualizado_por' => Auth::id(),
        ]);

        $queryParams = $this->parametrosRetorno($request);

        if (
            $request->input('return_to')
            === 'ventana'
        ) {
            return redirect()
                ->route(
                    'licencias.show.ventana',
                    array_merge(
                        $queryParams,
                        [
                            'licencia' => $licencia,
                        ]
                    )
                )
                ->with(
                    'success',
                    'Licencia inactivada correctamente. La unidad quedó bloqueada operativamente.'
                );
        }

        return redirect()
            ->route(
                'licencias.show',
                array_merge(
                    $queryParams,
                    [
                        'licencia' => $licencia,
                    ]
                )
            )
            ->with(
                'success',
                'Licencia inactivada correctamente. La unidad quedó bloqueada operativamente.'
            );
    }

    /**
     * Reactiva una licencia inactiva o renueva una licencia vencida.
     *
     * No modifica el estado administrativo de la unidad
     * ni regenera puntos de seguridad.
     */
    public function reactivar(
        Request $request,
        Licencia $licencia
    ): RedirectResponse {
        $this->validarEmpresaActivaLicencia($licencia);

        if (
            $licencia->estado === 'activa'
            && ! $this->licenciaEstaVencida($licencia)
        ) {
            return back()->withErrors([
                'fecha_activacion' => 'La licencia se encuentra activa y no está vencida.',
            ]);
        }

        $validated = $request->validate([
            'periodo_vigencia_meses' => [
                'required',
                'integer',
                Rule::in(
                    array_keys(
                        $this->periodosVigencia()
                    )
                ),
            ],
            'fecha_activacion' => [
                'required',
                'date',
            ],
        ]);

        $fechaActivacion = Carbon::parse(
            $validated['fecha_activacion']
        )->startOfDay();

        $periodoVigencia = (int) $validated[
            'periodo_vigencia_meses'
        ];

        $licencia->update([
            'periodo_vigencia_meses' => $periodoVigencia,
            'fecha_activacion' => $fechaActivacion->toDateString(),
            'fecha_vencimiento' => $fechaActivacion
                ->copy()
                ->addMonthsNoOverflow(
                    $periodoVigencia
                )
                ->toDateString(),
            'estado' => 'activa',
            'fecha_inactivacion' => null,
            'inactivado_por' => null,
            'motivo_inactivacion' => null,
            'actualizado_por' => Auth::id(),
        ]);

        $queryParams = $this->parametrosRetorno($request);

        if (
            $request->input('return_to')
            === 'ventana'
        ) {
            return redirect()
                ->route(
                    'licencias.show.ventana',
                    array_merge(
                        $queryParams,
                        [
                            'licencia' => $licencia,
                        ]
                    )
                )
                ->with(
                    'success',
                    'Licencia reactivada o renovada correctamente.'
                );
        }

        return redirect()
            ->route(
                'licencias.show',
                array_merge(
                    $queryParams,
                    [
                        'licencia' => $licencia,
                    ]
                )
            )
            ->with(
                'success',
                'Licencia reactivada o renovada correctamente.'
            );
    }

    /**
     * Reglas para crear una licencia.
     */
    private function reglasValidacionCrearLicencia(
        Request $request
    ): array {
        $empresaId = $request->input(
            'empresa_id'
        );

        return [
            'empresa_id' => [
                'required',
                'integer',
                Rule::exists(
                    'empresas',
                    'id'
                )->where(
                    'estado',
                    'activa'
                ),
            ],
            'unidad_id' => [
                'required',
                'integer',
                Rule::exists(
                    'unidades',
                    'id'
                )
                    ->where(
                        'empresa_id',
                        $empresaId
                    )
                    ->where(
                        'estado',
                        'registrada'
                    ),
                Rule::unique(
                    'licencias',
                    'unidad_id'
                ),
            ],
            'periodo_vigencia_meses' => [
                'required',
                'integer',
                Rule::in(
                    array_keys(
                        $this->periodosVigencia()
                    )
                ),
            ],
            'fecha_activacion' => [
                'required',
                'date',
            ],
            'tanques_cubiertos' => [
                'required',
                'array',
                'min:1',
                'max:3',
            ],
            'tanques_cubiertos.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('unidad_tanques', 'id')->where(
                    'unidad_id',
                    (int) $request->input('unidad_id')
                ),
            ],
        ];
    }

    /**
     * Reglas para actualizar una licencia.
     */
    private function reglasValidacionActualizarLicencia(): array
    {
        return [
            'periodo_vigencia_meses' => [
                'required',
                'integer',
                Rule::in(
                    array_keys(
                        $this->periodosVigencia()
                    )
                ),
            ],
            'fecha_activacion' => [
                'required',
                'date',
            ],
        ];
    }

    /**
     * Períodos permitidos.
     */
    private function periodosVigencia(): array
    {
        return [
            3 => '3 meses',
            6 => '6 meses',
            12 => '12 meses',
        ];
    }

    /**
     * Motivos manuales permitidos para inactivación.
     */
    private function motivosInactivacion(): array
    {
        return [
            'Fin de cobertura',
            'Falta de pago',
            'Solicitud administrativa',
            'Cambio operativo',
            'Unidad fuera de servicio',
            'Corrección de registro',
        ];
    }

    /**
     * Determina la plantilla por cantidad de tanques protegidos.
     */
    private function plantillaDesdeTanquesProtegidos(
        int $cantidadTanquesProtegidos
    ): string {
        return match ($cantidadTanquesProtegidos) {
            1 => 'plantilla_1_tanque',
            2 => 'plantilla_2_tanques',
            3 => 'plantilla_3_tanques',
            default => abort(
                422,
                'La cantidad de tanques protegidos debe ser 1, 2 o 3.'
            ),
        };
    }

    /**
     * Determina si el usuario autenticado pertenece a Diesel Cop.
     */
    private function esUsuarioDieselCop(): bool
    {
        return is_null(
            Auth::user()->empresa_id
        );
    }

    /**
     * Bloquea operaciones cuando la empresa está inactiva.
     */
    private function validarEmpresaActivaLicencia(
        Licencia $licencia
    ): void {
        $licencia->loadMissing([
            'empresa',
            'unidad',
        ]);

        if (
            ! $licencia->empresa
            || $licencia->empresa->estado !== 'activa'
        ) {
            abort(
                403,
                'No se puede administrar esta licencia porque la empresa está inactiva.'
            );
        }

        if (! $licencia->unidad) {
            abort(
                422,
                'La licencia no tiene una unidad válida asociada.'
            );
        }

        if (
            (int) $licencia->empresa_id
            !== (int) $licencia->unidad->empresa_id
        ) {
            abort(
                422,
                'La empresa de la licencia no coincide con la empresa propietaria de la unidad.'
            );
        }
    }

    /**
     * Valida que una licencia pueda editarse.
     */
    private function validarLicenciaEditable(
        Licencia $licencia
    ): void {
        $this->validarEmpresaActivaLicencia(
            $licencia
        );

        if ($licencia->estado !== 'activa') {
            abort(
                403,
                'La licencia está inactiva y no puede editarse. Debe reactivarse desde su ficha.'
            );
        }

        if ($this->licenciaEstaVencida($licencia)) {
            abort(
                403,
                'La licencia está vencida y no puede editarse. Debe renovarse desde su ficha.'
            );
        }
    }

    /**
     * Determina si la licencia venció.
     *
     * El día de vencimiento todavía se considera vigente.
     */
    private function licenciaEstaVencida(
        Licencia $licencia
    ): bool {
        if (! $licencia->fecha_vencimiento) {
            return true;
        }

        return $licencia
            ->fecha_vencimiento
            ->startOfDay()
            ->lt(
                now()->startOfDay()
            );
    }

    /**
     * Carga las relaciones requeridas en la ficha.
     */
    private function cargarRelacionesFicha(
        Licencia $licencia
    ): void {
        $licencia->load([
            'empresa',
            'unidad.puntosSeguridad.marchamoActual',
            'creadoPor',
            'actualizadoPor',
            'inactivadoPor',
        ]);
    }

    /**
     * Conserva parámetros de navegación y filtros.
     */
    private function parametrosRetorno(
        Request $request
    ): array {
        return collect($request->query())
            ->except([
                'licencia',
            ])
            ->all();
    }
}
