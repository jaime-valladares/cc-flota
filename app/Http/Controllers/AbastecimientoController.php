<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAbastecimientoRequest;
use App\Models\Abastecimiento;
use App\Models\Empresa;
use App\Models\Gasolinera;
use App\Models\GasolineraExterna;
use App\Models\Motorista;
use App\Models\PuntoSeguridadUnidad;
use App\Models\Ruta;
use App\Models\Unidad;
use App\Services\AbastecimientoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AbastecimientoController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Registro de abastecimientos
    |--------------------------------------------------------------------------
    */

    /**
     * Consulta de unidades disponibles para abastecimiento.
     */
    public function index(Request $request): View
    {
        return view(
            'abastecimientos.index',
            $this->prepararConsulta($request)
        );
    }

    /**
     * Consulta de unidades dentro de ventana independiente.
     */
    public function indexVentana(Request $request): View
    {
        return view(
            'abastecimientos.index-ventana',
            $this->prepararConsulta($request)
        );
    }

    /**
     * Formulario normal de abastecimiento.
     */
    public function create(
        Unidad $unidad
    ): View {
        return view(
            'abastecimientos.create',
            $this->prepararFormulario($unidad)
        );
    }

    /**
     * Formulario dentro de ventana independiente.
     */
    public function createVentana(
        Unidad $unidad
    ): View {
        return view(
            'abastecimientos.create-ventana',
            $this->prepararFormulario($unidad)
        );
    }

    /**
     * Registra el abastecimiento completo.
     */
    public function store(
        StoreAbastecimientoRequest $request,
        Unidad $unidad,
        AbastecimientoService $service
    ): RedirectResponse {
        $this->autorizarAccesoUnidad(
            $unidad
        );

        $datos = $request->validated();

        /*
         * La empresa y la unidad se toman desde la ruta y el
         * modelo cargado. No se confía en valores manipulables
         * enviados desde el formulario.
         */
        $datos['empresa_id'] =
            (int) $unidad->empresa_id;

        $datos['unidad_id'] =
            (int) $unidad->id;

        $abastecimiento = $service->registrar(
            $datos,
            (int) Auth::id()
        );

        $esVentana =
            $request->input('return_to')
            === 'ventana';

        $parametrosNavegacion = collect(
            $request->input(
                'navegacion',
                []
            )
        )
            ->filter(
                fn ($valor) =>
                    ! is_null($valor)
                    && $valor !== ''
            )
            ->all();

        return redirect()
            ->route(
                $esVentana
                    ? 'abastecimientos.create.ventana'
                    : 'abastecimientos.create',
                array_merge(
                    [
                        'unidad' =>
                            $abastecimiento->unidad_id,
                    ],
                    $parametrosNavegacion
                )
            )
            ->with(
                'success',
                'Abastecimiento registrado correctamente.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Consulta histórica
    |--------------------------------------------------------------------------
    */

    /**
     * Consulta histórica de abastecimientos.
     *
     * Incluye registros vigentes y anulados.
     */
    public function consulta(
        Request $request
    ): View {
        return view(
            'abastecimientos.consulta',
            $this->prepararListadoAbastecimientos(
                $request,
                false
            )
        );
    }

    /**
     * Consulta histórica en ventana independiente.
     */
    public function consultaVentana(
        Request $request
    ): View {
        return view(
            'abastecimientos.consulta-ventana',
            $this->prepararListadoAbastecimientos(
                $request,
                false
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Administración
    |--------------------------------------------------------------------------
    */

    /**
     * Administración de abastecimientos vigentes.
     */
    public function administrar(
        Request $request
    ): View {
        return view(
            'abastecimientos.administrar',
            $this->prepararListadoAbastecimientos(
                $request,
                true
            )
        );
    }

    /**
     * Administración en ventana independiente.
     */
    public function administrarVentana(
        Request $request
    ): View {
        return view(
            'abastecimientos.administrar-ventana',
            $this->prepararListadoAbastecimientos(
                $request,
                true
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Ficha
    |--------------------------------------------------------------------------
    */

    /**
     * Ficha administrativa del abastecimiento.
     */
    public function show(
        Request $request,
        Abastecimiento $abastecimiento
    ): View {
        $this->autorizarAccesoAbastecimiento(
            $abastecimiento
        );

        return view(
            'abastecimientos.show',
            $this->prepararFichaAbastecimiento(
                $request,
                $abastecimiento
            )
        );
    }

    /**
     * Ficha en ventana independiente.
     */
    public function showVentana(
        Request $request,
        Abastecimiento $abastecimiento
    ): View {
        $this->autorizarAccesoAbastecimiento(
            $abastecimiento
        );

        return view(
            'abastecimientos.show-ventana',
            $this->prepararFichaAbastecimiento(
                $request,
                $abastecimiento
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Preparación de Consulta y Administrar
    |--------------------------------------------------------------------------
    */

    /**
     * Prepara el listado de abastecimientos.
     *
     * Cuando $soloAdministrables es true:
     * - limita a abastecimientos registrados;
     * - limita a empresas activas;
     * - calcula cuáles son el último registro vigente por unidad.
     */
    private function prepararListadoAbastecimientos(
        Request $request,
        bool $soloAdministrables
    ): array {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null(
            $user->empresa_id
        );

        $empresaUsuario = $esUsuarioDieselCop
            ? null
            : Empresa::find(
                $user->empresa_id
            );

        $validated = $request->validate(
            [
                'empresa_ids' => [
                    'nullable',
                    'array',
                ],

                'empresa_ids.*' => [
                    'integer',
                    'distinct',
                    'exists:empresas,id',
                ],

                'unidad_ids' => [
                    'nullable',
                    'array',
                ],

                'unidad_ids.*' => [
                    'integer',
                    'distinct',
                    'exists:unidades,id',
                ],

                'motorista_ids' => [
                    'nullable',
                    'array',
                ],

                'motorista_ids.*' => [
                    'integer',
                    'distinct',
                    'exists:motoristas,id',
                ],

                'tipo_origen' => [
                    'nullable',
                    Rule::in([
                        Abastecimiento::ORIGEN_INTERNO,
                        Abastecimiento::ORIGEN_EXTERNO,
                    ]),
                ],

                'modelo_medicion' => [
                    'nullable',
                    Rule::in([
                        Abastecimiento::MODELO_GALONES_KILOMETRO,
                        Abastecimiento::MODELO_GALONES_HORA,
                        Abastecimiento::MODELO_GALONES_VIAJE,
                    ]),
                ],

                'estado' => [
                    'nullable',
                    Rule::in([
                        Abastecimiento::ESTADO_REGISTRADO,
                        Abastecimiento::ESTADO_ANULADO,
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

                /*
                 * Compatibilidad con parámetros individuales.
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
            ],
            [
                'empresa_ids.array' =>
                    'La selección de empresas no es válida.',

                'empresa_ids.*.integer' =>
                    'Una de las empresas seleccionadas no es válida.',

                'empresa_ids.*.distinct' =>
                    'No debe seleccionar una empresa más de una vez.',

                'empresa_ids.*.exists' =>
                    'Una de las empresas seleccionadas no existe.',

                'unidad_ids.array' =>
                    'La selección de unidades no es válida.',

                'unidad_ids.*.integer' =>
                    'Una de las unidades seleccionadas no es válida.',

                'unidad_ids.*.distinct' =>
                    'No debe seleccionar una unidad más de una vez.',

                'unidad_ids.*.exists' =>
                    'Una de las unidades seleccionadas no existe.',

                'motorista_ids.array' =>
                    'La selección de motoristas no es válida.',

                'motorista_ids.*.integer' =>
                    'Uno de los motoristas seleccionados no es válido.',

                'motorista_ids.*.distinct' =>
                    'No debe seleccionar un motorista más de una vez.',

                'motorista_ids.*.exists' =>
                    'Uno de los motoristas seleccionados no existe.',

                'tipo_origen.in' =>
                    'El origen seleccionado no es válido.',

                'modelo_medicion.in' =>
                    'El modelo de medición seleccionado no es válido.',

                'estado.in' =>
                    'El estado seleccionado no es válido.',

                'fecha_desde.date_format' =>
                    'La fecha inicial no posee un formato válido.',

                'fecha_hasta.date_format' =>
                    'La fecha final no posee un formato válido.',

                'fecha_hasta.after_or_equal' =>
                    'La fecha final no puede ser anterior a la fecha inicial.',
            ]
        );

        $empresaIds =
            $this->normalizarIdsSeleccionados(
                $validated['empresa_ids']
                    ?? [],
                $validated['empresa_id']
                    ?? null
            );

        $unidadIds =
            $this->normalizarIdsSeleccionados(
                $validated['unidad_ids']
                    ?? [],
                $validated['unidad_id']
                    ?? null
            );

        $motoristaIds =
            $this->normalizarIdsSeleccionados(
                $validated['motorista_ids']
                    ?? [],
                $validated['motorista_id']
                    ?? null
            );

        if (! $esUsuarioDieselCop) {
            $empresaIds = [
                (int) $user->empresa_id,
            ];
        }

        $tipoOrigen =
            $validated['tipo_origen']
            ?? null;

        $modeloMedicion =
            $validated['modelo_medicion']
            ?? null;

        $estado =
            $soloAdministrables
                ? Abastecimiento::ESTADO_REGISTRADO
                : (
                    $validated['estado']
                    ?? null
                );

        $fechaDesde =
            $validated['fecha_desde']
            ?? null;

        $fechaHasta =
            $validated['fecha_hasta']
            ?? null;

        $hayFiltros =
            ! $esUsuarioDieselCop
            || $request->hasAny([
                'empresa_ids',
                'empresa_id',
                'unidad_ids',
                'unidad_id',
                'motorista_ids',
                'motorista_id',
                'tipo_origen',
                'modelo_medicion',
                'estado',
                'fecha_desde',
                'fecha_hasta',
                'consultar',
            ]);

        $query = Abastecimiento::query()
            ->with([
                'empresa',
                'unidad',
                'motorista',
                'gasolineraInterna',
                'gasolineraExterna',
            ]);

        if ($soloAdministrables) {
            $query
                ->where(
                    'estado',
                    Abastecimiento::ESTADO_REGISTRADO
                )
                ->whereHas(
                    'empresa',
                    fn (Builder $empresaQuery) =>
                        $empresaQuery->where(
                            'estado',
                            'activa'
                        )
                );
        }

        if (! $esUsuarioDieselCop) {
            $query->where(
                'empresa_id',
                $user->empresa_id
            );
        }

        if (! $hayFiltros) {
            $query->whereRaw(
                '1 = 0'
            );
        } else {
            $this->aplicarFiltrosAbastecimientos(
                $query,
                $empresaIds,
                $unidadIds,
                $motoristaIds,
                $tipoOrigen,
                $modeloMedicion,
                $estado,
                $fechaDesde,
                $fechaHasta,
                $soloAdministrables
            );
        }

        $abastecimientos = $query
            ->orderByDesc(
                'fecha_hora_abastecimiento'
            )
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $ultimosIdsPorUnidad =
            $soloAdministrables
                ? $this->obtenerUltimosIdsRegistradosPorUnidad(
                    $abastecimientos
                        ->getCollection()
                        ->pluck('unidad_id')
                        ->map(
                            fn ($id): int =>
                                (int) $id
                        )
                        ->unique()
                        ->values()
                        ->all()
                )
                : collect();

        $empresasSelector =
            $this->obtenerEmpresasSelectorAbastecimientos(
                $esUsuarioDieselCop,
                $empresaUsuario,
                $soloAdministrables
            );

        $unidadesSelector =
            $this->obtenerUnidadesSelectorAbastecimientos(
                $esUsuarioDieselCop,
                $user->empresa_id,
                $empresaIds,
                $soloAdministrables
            );

        $motoristasSelector =
            $this->obtenerMotoristasSelectorAbastecimientos(
                $esUsuarioDieselCop,
                $user->empresa_id,
                $empresaIds,
                $soloAdministrables
            );

        $unidadIds =
            $this->filtrarIdsPermitidos(
                $unidadIds,
                $unidadesSelector->pluck('id')
            );

        $motoristaIds =
            $this->filtrarIdsPermitidos(
                $motoristaIds,
                $motoristasSelector->pluck('id')
            );

        $resumen =
            $this->obtenerResumenAbastecimientos(
                $esUsuarioDieselCop,
                $user->empresa_id,
                $empresaIds,
                $soloAdministrables
            );

        return [
            'abastecimientos' =>
                $abastecimientos,

            'empresasSelector' =>
                $empresasSelector,

            'unidadesSelector' =>
                $unidadesSelector,

            'motoristasSelector' =>
                $motoristasSelector,

            'empresaIds' =>
                $empresaIds,

            'unidadIds' =>
                $unidadIds,

            'motoristaIds' =>
                $motoristaIds,

            'tipoOrigen' =>
                $tipoOrigen,

            'modeloMedicion' =>
                $modeloMedicion,

            'estado' =>
                $estado,

            'fechaDesde' =>
                $fechaDesde,

            'fechaHasta' =>
                $fechaHasta,

            'hayFiltros' =>
                $hayFiltros,

            'esUsuarioDieselCop' =>
                $esUsuarioDieselCop,

            'empresaUsuario' =>
                $empresaUsuario,

            'soloAdministrables' =>
                $soloAdministrables,

            'ultimosIdsPorUnidad' =>
                $ultimosIdsPorUnidad,

            'totalAbastecimientos' =>
                $resumen['total'],

            'totalRegistrados' =>
                $resumen['registrados'],

            'totalAnulados' =>
                $resumen['anulados'],

            'totalInternos' =>
                $resumen['internos'],

            'totalExternos' =>
                $resumen['externos'],
        ];
    }

    /**
     * Aplica los filtros seleccionados al listado.
     */
    private function aplicarFiltrosAbastecimientos(
        Builder $query,
        array $empresaIds,
        array $unidadIds,
        array $motoristaIds,
        ?string $tipoOrigen,
        ?string $modeloMedicion,
        ?string $estado,
        ?string $fechaDesde,
        ?string $fechaHasta,
        bool $soloAdministrables
    ): void {
        if (! empty($empresaIds)) {
            $query->whereIn(
                'empresa_id',
                $empresaIds
            );
        }

        if (! empty($unidadIds)) {
            $query->whereIn(
                'unidad_id',
                $unidadIds
            );
        }

        if (! empty($motoristaIds)) {
            $query->whereIn(
                'motorista_id',
                $motoristaIds
            );
        }

        if (
            in_array(
                $tipoOrigen,
                [
                    Abastecimiento::ORIGEN_INTERNO,
                    Abastecimiento::ORIGEN_EXTERNO,
                ],
                true
            )
        ) {
            $query->where(
                'tipo_origen',
                $tipoOrigen
            );
        }

        if (
            in_array(
                $modeloMedicion,
                [
                    Abastecimiento::MODELO_GALONES_KILOMETRO,
                    Abastecimiento::MODELO_GALONES_HORA,
                    Abastecimiento::MODELO_GALONES_VIAJE,
                ],
                true
            )
        ) {
            $query->where(
                'modelo_medicion',
                $modeloMedicion
            );
        }

        if (
            ! $soloAdministrables
            && in_array(
                $estado,
                [
                    Abastecimiento::ESTADO_REGISTRADO,
                    Abastecimiento::ESTADO_ANULADO,
                ],
                true
            )
        ) {
            $query->where(
                'estado',
                $estado
            );
        }

        if ($fechaDesde) {
            $query->whereDate(
                'fecha_hora_abastecimiento',
                '>=',
                $fechaDesde
            );
        }

        if ($fechaHasta) {
            $query->whereDate(
                'fecha_hora_abastecimiento',
                '<=',
                $fechaHasta
            );
        }
    }

    /**
     * Obtiene los identificadores del último abastecimiento
     * registrado de cada unidad presente en la página.
     */
    private function obtenerUltimosIdsRegistradosPorUnidad(
        array $unidadIds
    ): Collection {
        if (empty($unidadIds)) {
            return collect();
        }

        return collect($unidadIds)
            ->mapWithKeys(
                function (int $unidadId): array {
                    $ultimoId =
                        Abastecimiento::query()
                            ->where(
                                'unidad_id',
                                $unidadId
                            )
                            ->where(
                                'estado',
                                Abastecimiento::ESTADO_REGISTRADO
                            )
                            ->orderByDesc(
                                'fecha_hora_abastecimiento'
                            )
                            ->orderByDesc('id')
                            ->value('id');

                    return [
                        $unidadId =>
                            $ultimoId
                                ? (int) $ultimoId
                                : null,
                    ];
                }
            )
            ->filter();
    }

    /**
     * Resumen general del módulo.
     */
    private function obtenerResumenAbastecimientos(
        bool $esUsuarioDieselCop,
        int|string|null $empresaUsuarioId,
        array $empresaIds,
        bool $soloAdministrables
    ): array {
        $base = Abastecimiento::query();

        if (! $esUsuarioDieselCop) {
            $base->where(
                'empresa_id',
                $empresaUsuarioId
            );
        }

        if (! empty($empresaIds)) {
            $base->whereIn(
                'empresa_id',
                $empresaIds
            );
        }

        if ($soloAdministrables) {
            $base
                ->where(
                    'estado',
                    Abastecimiento::ESTADO_REGISTRADO
                )
                ->whereHas(
                    'empresa',
                    fn (Builder $query) =>
                        $query->where(
                            'estado',
                            'activa'
                        )
                );
        }

        return [
            'total' =>
                (clone $base)->count(),

            'registrados' =>
                (clone $base)
                    ->where(
                        'estado',
                        Abastecimiento::ESTADO_REGISTRADO
                    )
                    ->count(),

            'anulados' =>
                (clone $base)
                    ->where(
                        'estado',
                        Abastecimiento::ESTADO_ANULADO
                    )
                    ->count(),

            'internos' =>
                (clone $base)
                    ->where(
                        'tipo_origen',
                        Abastecimiento::ORIGEN_INTERNO
                    )
                    ->count(),

            'externos' =>
                (clone $base)
                    ->where(
                        'tipo_origen',
                        Abastecimiento::ORIGEN_EXTERNO
                    )
                    ->count(),
        ];
    }

    /**
     * Empresas disponibles en los filtros.
     */
    private function obtenerEmpresasSelectorAbastecimientos(
        bool $esUsuarioDieselCop,
        ?Empresa $empresaUsuario,
        bool $soloAdministrables
    ): Collection {
        if (! $esUsuarioDieselCop) {
            return collect([
                $empresaUsuario,
            ])
                ->filter(
                    fn (?Empresa $empresa): bool =>
                        ! is_null($empresa)
                        && (
                            ! $soloAdministrables
                            || $empresa->estado === 'activa'
                        )
                )
                ->values();
        }

        return Empresa::query()
            ->when(
                $soloAdministrables,
                fn (Builder $query) =>
                    $query->where(
                        'estado',
                        'activa'
                    )
            )
            ->whereHas(
                'abastecimientos'
            )
            ->orderBy('nombre_comercial')
            ->orderBy('nombre_legal')
            ->get();
    }

    /**
     * Unidades disponibles en los filtros.
     */
    private function obtenerUnidadesSelectorAbastecimientos(
        bool $esUsuarioDieselCop,
        int|string|null $empresaUsuarioId,
        array $empresaIds,
        bool $soloAdministrables
    ): Collection {
        return Unidad::query()
            ->with('empresa')
            ->whereHas(
                'abastecimientos',
                function (Builder $query) use (
                    $soloAdministrables
                ) {
                    if ($soloAdministrables) {
                        $query->where(
                            'estado',
                            Abastecimiento::ESTADO_REGISTRADO
                        );
                    }
                }
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
                ! empty($empresaIds),
                fn (Builder $query) =>
                    $query->whereIn(
                        'empresa_id',
                        $empresaIds
                    )
            )
            ->when(
                $soloAdministrables,
                fn (Builder $query) =>
                    $query->whereHas(
                        'empresa',
                        fn (Builder $empresaQuery) =>
                            $empresaQuery->where(
                                'estado',
                                'activa'
                            )
                    )
            )
            ->orderBy('empresa_id')
            ->orderBy('placa')
            ->get();
    }

    /**
     * Motoristas disponibles en los filtros.
     */
    private function obtenerMotoristasSelectorAbastecimientos(
        bool $esUsuarioDieselCop,
        int|string|null $empresaUsuarioId,
        array $empresaIds,
        bool $soloAdministrables
    ): Collection {
        return Motorista::query()
            ->with('empresa')
            ->whereHas(
                'abastecimientos',
                function (Builder $query) use (
                    $soloAdministrables
                ) {
                    if ($soloAdministrables) {
                        $query->where(
                            'estado',
                            Abastecimiento::ESTADO_REGISTRADO
                        );
                    }
                }
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
                ! empty($empresaIds),
                fn (Builder $query) =>
                    $query->whereIn(
                        'empresa_id',
                        $empresaIds
                    )
            )
            ->when(
                $soloAdministrables,
                fn (Builder $query) =>
                    $query->whereHas(
                        'empresa',
                        fn (Builder $empresaQuery) =>
                            $empresaQuery->where(
                                'estado',
                                'activa'
                            )
                    )
            )
            ->orderBy('empresa_id')
            ->orderBy('nombres')
            ->orderBy('apellidos')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Preparación de ficha
    |--------------------------------------------------------------------------
    */

    /**
     * Prepara la ficha completa del abastecimiento.
     */
    private function prepararFichaAbastecimiento(
        Request $request,
        Abastecimiento $abastecimiento
    ): array {
        $abastecimiento->load([
            'empresa',
            'unidad',
            'motorista',
            'registradoPor',
            'anuladoPor',
            'abastecimientoAnterior',
            'abastecimientosSiguientes',
            'gasolineraInterna',
            'gasolineraExterna',
            'tanques.tanque',
            'rutas.ruta',
            'rutas.puntoOrigen',
            'rutas.puntoDestino',
            'movimientosInventario',
            'reemplazoMarchamoEvento.detalles',
        ]);

        $ultimoAbastecimientoRegistrado =
            Abastecimiento::query()
                ->where(
                    'unidad_id',
                    $abastecimiento->unidad_id
                )
                ->where(
                    'estado',
                    Abastecimiento::ESTADO_REGISTRADO
                )
                ->orderByDesc(
                    'fecha_hora_abastecimiento'
                )
                ->orderByDesc('id')
                ->first();

        $empresaActiva =
            $abastecimiento->empresa
            && $abastecimiento->empresa->estado
                === 'activa';

        $esUltimoRegistrado =
            $abastecimiento->estaRegistrado()
            && $ultimoAbastecimientoRegistrado
            && (int) $ultimoAbastecimientoRegistrado->id
                === (int) $abastecimiento->id;

        $puedeModificarse =
            $empresaActiva
            && $esUltimoRegistrado;

        $parametrosRetorno =
            $this->obtenerParametrosRetorno(
                $request
            );

        $origenRetorno =
            $request->query(
                'origen_retorno',
                'administrar'
            );

        if (
            ! in_array(
                $origenRetorno,
                [
                    'consulta',
                    'administrar',
                ],
                true
            )
        ) {
            $origenRetorno = 'administrar';
        }

        return [
            'abastecimiento' =>
                $abastecimiento,

            'ultimoAbastecimientoRegistrado' =>
                $ultimoAbastecimientoRegistrado,

            'esUltimoRegistrado' =>
                $esUltimoRegistrado,

            'puedeModificarse' =>
                $puedeModificarse,

            'empresaActiva' =>
                $empresaActiva,

            'parametrosRetorno' =>
                $parametrosRetorno,

            'origenRetorno' =>
                $origenRetorno,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Registro: consulta de unidades operables
    |--------------------------------------------------------------------------
    */

    /**
     * Prepara la consulta de unidades operables.
     */
    private function prepararConsulta(
        Request $request
    ): array {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null(
            $user->empresa_id
        );

        $validated = $request->validate(
            [
                'busqueda_empresa' => [
                    'nullable',
                    'string',
                    'max:150',
                ],

                'busqueda_placa' => [
                    'nullable',
                    'string',
                    'max:50',
                ],

                'empresa_ids' => [
                    'nullable',
                    'array',
                ],

                'empresa_ids.*' => [
                    'integer',
                    'distinct',
                    'exists:empresas,id',
                ],

                'placas' => [
                    'nullable',
                    'array',
                ],

                'placas.*' => [
                    'string',
                    'distinct',
                    'max:50',
                ],
            ],
            [
                'empresa_ids.array' =>
                    'La selección de empresas no es válida.',

                'empresa_ids.*.exists' =>
                    'Una de las empresas seleccionadas no existe.',

                'empresa_ids.*.distinct' =>
                    'No debe seleccionar una empresa más de una vez.',

                'placas.array' =>
                    'La selección de placas no es válida.',

                'placas.*.distinct' =>
                    'No debe seleccionar una placa más de una vez.',
            ]
        );

        $busquedaEmpresa = trim(
            (string) (
                $validated['busqueda_empresa']
                ?? ''
            )
        );

        $busquedaPlaca = trim(
            (string) (
                $validated['busqueda_placa']
                ?? ''
            )
        );

        $empresaIds = collect(
            $validated['empresa_ids']
            ?? []
        )
            ->filter(
                fn ($id): bool =>
                    filled($id)
            )
            ->map(
                fn ($id): int =>
                    (int) $id
            )
            ->unique()
            ->values();

        $placas = collect(
            $validated['placas']
            ?? []
        )
            ->filter(
                fn ($placa): bool =>
                    filled($placa)
            )
            ->map(
                fn ($placa): string =>
                    trim((string) $placa)
            )
            ->unique()
            ->values();

        if (! $esUsuarioDieselCop) {
            $empresaIds = collect([
                (int) $user->empresa_id,
            ]);
        }

        $consultaEjecutada =
            $request->boolean('consultar');

        $hayFiltros =
            $consultaEjecutada
            || filled($busquedaEmpresa)
            || filled($busquedaPlaca)
            || $empresaIds->isNotEmpty()
            || $placas->isNotEmpty();

        $empresas = Empresa::query()
            ->where(
                'estado',
                'activa'
            )
            ->when(
                ! $esUsuarioDieselCop,
                function ($query) use ($user) {
                    $query->where(
                        'id',
                        $user->empresa_id
                    );
                }
            )
            ->orderByRaw(
                'COALESCE(nombre_comercial, nombre_legal)'
            )
            ->get();

        $baseUnidades =
            $this->consultaBaseUnidadesOperables(
                $esUsuarioDieselCop,
                $user->empresa_id
            );

        $placasSelector =
            (clone $baseUnidades)
                ->when(
                    $empresaIds->isNotEmpty(),
                    function ($query) use (
                        $empresaIds
                    ) {
                        $query->whereIn(
                            'empresa_id',
                            $empresaIds->all()
                        );
                    }
                )
                ->orderBy('placa')
                ->pluck('placa')
                ->filter()
                ->unique()
                ->values();

        $unidadesQuery =
            (clone $baseUnidades)
                ->with([
                    'empresa',
                    'licencia',
                ]);

        if (! $hayFiltros) {
            $unidadesQuery->whereRaw(
                '1 = 0'
            );
        } else {
            $unidadesQuery
                ->when(
                    filled($busquedaEmpresa),
                    function ($query) use (
                        $busquedaEmpresa
                    ) {
                        $query->whereHas(
                            'empresa',
                            function ($empresaQuery) use (
                                $busquedaEmpresa
                            ) {
                                $empresaQuery->where(
                                    function ($subquery) use (
                                        $busquedaEmpresa
                                    ) {
                                        $subquery
                                            ->where(
                                                'nombre_legal',
                                                'like',
                                                '%'
                                                . $busquedaEmpresa
                                                . '%'
                                            )
                                            ->orWhere(
                                                'nombre_comercial',
                                                'like',
                                                '%'
                                                . $busquedaEmpresa
                                                . '%'
                                            );
                                    }
                                );
                            }
                        );
                    }
                )
                ->when(
                    $empresaIds->isNotEmpty(),
                    function ($query) use (
                        $empresaIds
                    ) {
                        $query->whereIn(
                            'empresa_id',
                            $empresaIds->all()
                        );
                    }
                )
                ->when(
                    filled($busquedaPlaca),
                    function ($query) use (
                        $busquedaPlaca
                    ) {
                        $query->where(
                            'placa',
                            'like',
                            '%'
                            . $busquedaPlaca
                            . '%'
                        );
                    }
                )
                ->when(
                    $placas->isNotEmpty(),
                    function ($query) use (
                        $placas
                    ) {
                        $query->whereIn(
                            'placa',
                            $placas->all()
                        );
                    }
                );
        }

        $unidades = $unidadesQuery
            ->orderBy('placa')
            ->paginate(15)
            ->withQueryString();

        return [
            'empresas' =>
                $empresas,

            'placasSelector' =>
                $placasSelector,

            'unidades' =>
                $unidades,

            'busquedaEmpresa' =>
                $busquedaEmpresa,

            'busquedaPlaca' =>
                $busquedaPlaca,

            'empresaIds' =>
                $empresaIds->all(),

            'placas' =>
                $placas->all(),

            'hayFiltros' =>
                $hayFiltros,

            'consultaEjecutada' =>
                $consultaEjecutada,

            'esUsuarioDieselCop' =>
                $esUsuarioDieselCop,
        ];
    }

    /**
     * Consulta base de unidades con disponibilidad total.
     */
    private function consultaBaseUnidadesOperables(
        bool $esUsuarioDieselCop,
        int|string|null $empresaUsuarioId
    ) {
        $hoy = Carbon::today()
            ->toDateString();

        return Unidad::query()
            ->where(
                'estado',
                'activa'
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
            ->whereHas(
                'licencia',
                function ($query) use ($hoy) {
                    $query
                        ->where(
                            'estado',
                            'activa'
                        )
                        ->whereDate(
                            'fecha_activacion',
                            '<=',
                            $hoy
                        )
                        ->whereDate(
                            'fecha_vencimiento',
                            '>=',
                            $hoy
                        );
                }
            )
            ->whereHas(
                'puntosSeguridad',
                function ($query) {
                    $query
                        ->where(
                            'estado',
                            'activo'
                        )
                        ->where(
                            'requiere_marchamo',
                            true
                        );
                }
            )
            ->whereDoesntHave(
                'puntosSeguridad',
                function ($query) {
                    $query
                        ->where(
                            'estado',
                            'activo'
                        )
                        ->where(
                            'requiere_marchamo',
                            true
                        )
                        ->where(
                            function ($puntoQuery) {
                                $puntoQuery
                                    ->whereNull(
                                        'marchamo_actual_id'
                                    )
                                    ->orWhereDoesntHave(
                                        'marchamoActual',
                                        function (
                                            $marchamoQuery
                                        ) {
                                            $marchamoQuery
                                                ->where(
                                                    'estado',
                                                    'activo'
                                                )
                                                ->where(
                                                    'activo_actual',
                                                    true
                                                );
                                        }
                                    );
                            }
                        );
                }
            )
            ->when(
                ! $esUsuarioDieselCop,
                function ($query) use (
                    $empresaUsuarioId
                ) {
                    $query->where(
                        'empresa_id',
                        $empresaUsuarioId
                    );
                }
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Registro: formulario
    |--------------------------------------------------------------------------
    */

    /**
     * Prepara todos los catálogos del formulario.
     */
    private function prepararFormulario(
        Unidad $unidad
    ): array {
        $this->autorizarAccesoUnidad(
            $unidad
        );

        $unidad->load([
            'empresa',
            'licencia',
            'puntosSeguridad.marchamoActual',
        ]);

        $this->validarUnidadOperable(
            $unidad
        );

        $ultimoAbastecimiento =
            Abastecimiento::query()
                ->where(
                    'unidad_id',
                    $unidad->id
                )
                ->where(
                    'estado',
                    Abastecimiento::ESTADO_REGISTRADO
                )
                ->latest(
                    'fecha_hora_abastecimiento'
                )
                ->latest('id')
                ->first();

        $motoristas = Motorista::query()
            ->where(
                'empresa_id',
                $unidad->empresa_id
            )
            ->where(
                'estado',
                'activo'
            )
            ->orderBy('nombres')
            ->orderBy('apellidos')
            ->get();

        $gasolinerasInternas =
            Gasolinera::query()
                ->with([
                    'tanques' =>
                        function ($query) {
                            $query
                                ->where(
                                    'estado',
                                    'activo'
                                )
                                ->where(
                                    'volumen_actual',
                                    '>',
                                    0
                                )
                                ->orderBy('nombre');
                        },
                ])
                ->where(
                    'empresa_id',
                    $unidad->empresa_id
                )
                ->where(
                    'estado',
                    'activa'
                )
                ->whereHas(
                    'tanques',
                    function ($query) {
                        $query
                            ->where(
                                'estado',
                                'activo'
                            )
                            ->where(
                                'volumen_actual',
                                '>',
                                0
                            );
                    }
                )
                ->orderBy('nombre')
                ->get();

        $gasolinerasExternas =
            GasolineraExterna::query()
                ->where(
                    'empresa_id',
                    $unidad->empresa_id
                )
                ->where(
                    'estado',
                    'activa'
                )
                ->orderBy('compania')
                ->get();

        $rutas = collect();

        if (
            $unidad->modelo_medicion
            === Abastecimiento::MODELO_GALONES_VIAJE
            && $ultimoAbastecimiento
        ) {
            $rutas = Ruta::query()
                ->with([
                    'puntoOrigen',
                    'puntoDestino',
                ])
                ->where(
                    'empresa_id',
                    $unidad->empresa_id
                )
                ->where(
                    'estado',
                    'activo'
                )
                ->whereHas(
                    'puntoOrigen',
                    function ($query) {
                        $query->where(
                            'estado',
                            'activo'
                        );
                    }
                )
                ->whereHas(
                    'puntoDestino',
                    function ($query) {
                        $query->where(
                            'estado',
                            'activo'
                        );
                    }
                )
                ->orderBy('ruta')
                ->get();
        }

        $tapones = $this->obtenerTaponesAbastecimiento(
            $unidad
        );

        return [
            'unidad' =>
                $unidad,

            'ultimoAbastecimiento' =>
                $ultimoAbastecimiento,

            'esPrimerAbastecimiento' =>
                is_null($ultimoAbastecimiento),

            'motoristas' =>
                $motoristas,

            'gasolinerasInternas' =>
                $gasolinerasInternas,

            'gasolinerasExternas' =>
                $gasolinerasExternas,

            'rutas' =>
                $rutas,

            'tapones' =>
                $tapones,

            'requiereRutas' =>
                $unidad->modelo_medicion
                === Abastecimiento::MODELO_GALONES_VIAJE
                && ! is_null(
                    $ultimoAbastecimiento
                ),

            'tiposOrigen' => [
                Abastecimiento::ORIGEN_INTERNO =>
                    'Gasolinera interna',

                Abastecimiento::ORIGEN_EXTERNO =>
                    'Gasolinera externa',
            ],
        ];
    }

    /**
     * Obtiene únicamente los tapones que pueden abrirse durante
     * un abastecimiento.
     */
    private function obtenerTaponesAbastecimiento(
        Unidad $unidad
    ): Collection {
        return PuntoSeguridadUnidad::query()
            ->with('marchamoActual')
            ->where(
                'unidad_id',
                $unidad->id
            )
            ->where(
                'estado',
                'activo'
            )
            ->where(
                'requiere_marchamo',
                true
            )
            ->where(
                'tipo_punto',
                'tapón'
            )
            ->where(
                'subgrupo',
                'Depósito'
            )
            ->whereNotNull(
                'marchamo_actual_id'
            )
            ->whereHas(
                'marchamoActual',
                function ($query) {
                    $query
                        ->where(
                            'estado',
                            'activo'
                        )
                        ->where(
                            'activo_actual',
                            true
                        );
                }
            )
            ->orderBy('orden')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Autorización y validaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Aislamiento multiempresa para unidades.
     */
    private function autorizarAccesoUnidad(
        Unidad $unidad
    ): void {
        $user = Auth::user();

        if (
            ! is_null($user->empresa_id)
            && (int) $user->empresa_id
                !== (int) $unidad->empresa_id
        ) {
            abort(
                403,
                'No tiene autorización para acceder a esta unidad.'
            );
        }
    }

    /**
     * Aislamiento multiempresa para abastecimientos.
     */
    private function autorizarAccesoAbastecimiento(
        Abastecimiento $abastecimiento
    ): void {
        $user = Auth::user();

        if (
            ! is_null($user->empresa_id)
            && (int) $user->empresa_id
                !== (int) $abastecimiento->empresa_id
        ) {
            abort(
                403,
                'No tiene autorización para acceder a este abastecimiento.'
            );
        }
    }

    /**
     * Verifica disponibilidad operativa completa.
     */
    private function validarUnidadOperable(
        Unidad $unidad
    ): void {
        if (! $unidad->es_operable) {
            abort(
                403,
                'La unidad no cumple actualmente todas las condiciones necesarias para recibir combustible: '
                . $unidad
                    ->disponibilidad_operativa_texto
                . '.'
            );
        }

        $taponesDisponibles =
            PuntoSeguridadUnidad::query()
                ->where(
                    'unidad_id',
                    $unidad->id
                )
                ->where(
                    'estado',
                    'activo'
                )
                ->where(
                    'requiere_marchamo',
                    true
                )
                ->where(
                    'tipo_punto',
                    'tapón'
                )
                ->where(
                    'subgrupo',
                    'Depósito'
                )
                ->whereNotNull(
                    'marchamo_actual_id'
                )
                ->whereHas(
                    'marchamoActual',
                    function ($query) {
                        $query
                            ->where(
                                'estado',
                                'activo'
                            )
                            ->where(
                                'activo_actual',
                                true
                            );
                    }
                )
                ->exists();

        if (! $taponesDisponibles) {
            abort(
                403,
                'La unidad no posee tapones de depósito disponibles para realizar el abastecimiento.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Utilidades
    |--------------------------------------------------------------------------
    */

    /**
     * Normaliza filtros individuales y multiselección.
     */
    private function normalizarIdsSeleccionados(
        array $ids,
        mixed $idIndividual = null
    ): array {
        if (
            empty($ids)
            && ! is_null($idIndividual)
            && $idIndividual !== ''
        ) {
            $ids = [
                $idIndividual,
            ];
        }

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

    /**
     * Conserva únicamente IDs presentes en un catálogo permitido.
     */
    private function filtrarIdsPermitidos(
        array $ids,
        Collection $idsPermitidos
    ): array {
        $permitidos = $idsPermitidos
            ->map(
                fn ($id): int =>
                    (int) $id
            )
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
            ->map(
                fn ($id): int =>
                    (int) $id
            )
            ->values()
            ->all();
    }

    /**
     * Recupera los filtros que deben conservarse entre vistas.
     */
    private function obtenerParametrosRetorno(
        Request $request
    ): array {
        $permitidos = [
            'empresa_ids',
            'unidad_ids',
            'motorista_ids',
            'tipo_origen',
            'modelo_medicion',
            'estado',
            'fecha_desde',
            'fecha_hasta',
            'consultar',
            'page',
            'origen_retorno',
        ];

        $parametros = collect(
            $request->query()
        )
            ->only(
                $permitidos
            )
            ->all();

        $returnQuery =
            $request->input(
                'return_query'
            );

        if (
            is_string($returnQuery)
            && $returnQuery !== ''
        ) {
            parse_str(
                $returnQuery,
                $parametrosDecodificados
            );

            $parametros = collect(
                $parametrosDecodificados
            )
                ->only(
                    $permitidos
                )
                ->all();
        }

        return $parametros;
    }
}