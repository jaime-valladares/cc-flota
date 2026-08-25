<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAbastecimientoRequest;
use App\Http\Requests\UpdateAbastecimientoRequest;
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
    | Modificación
    |--------------------------------------------------------------------------
    */

    /**
     * Formulario normal de modificación.
     */
    public function edit(
        Request $request,
        Abastecimiento $abastecimiento,
        AbastecimientoService $service
    ): View {
        $this->autorizarAccesoAbastecimiento(
            $abastecimiento
        );

        return view(
            'abastecimientos.edit',
            $this->prepararFormularioEdicion(
                $request,
                $abastecimiento,
                $service
            )
        );
    }

    /**
     * Formulario de modificación en ventana independiente.
     */
    public function editVentana(
        Request $request,
        Abastecimiento $abastecimiento,
        AbastecimientoService $service
    ): View {
        $this->autorizarAccesoAbastecimiento(
            $abastecimiento
        );

        return view(
            'abastecimientos.edit-ventana',
            $this->prepararFormularioEdicion(
                $request,
                $abastecimiento,
                $service
            )
        );
    }

    /**
     * Actualiza de forma atómica el último abastecimiento vigente.
     */
    public function update(
        UpdateAbastecimientoRequest $request,
        Abastecimiento $abastecimiento,
        AbastecimientoService $service
    ): RedirectResponse {
        $this->autorizarAccesoAbastecimiento(
            $abastecimiento
        );

        $datos = $request->validated();

        /*
         * Empresa y unidad pertenecen al abastecimiento original.
         * No se confía en valores manipulables enviados desde el formulario.
         */
        $datos['empresa_id'] =
            (int) $abastecimiento->empresa_id;

        $datos['unidad_id'] =
            (int) $abastecimiento->unidad_id;

        $abastecimientoActualizado =
            $service->modificar(
                $abastecimiento,
                $datos,
                (int) Auth::id()
            );

        $esVentana =
            $request->input('return_to')
            === 'ventana';

        $parametrosRetorno =
            $this->obtenerParametrosRetorno(
                $request
            );

        return redirect()
            ->route(
                $esVentana
                    ? 'abastecimientos.show.ventana'
                    : 'abastecimientos.show',
                array_merge(
                    [
                        'abastecimiento' =>
                            $abastecimientoActualizado->id,

                        'origen_retorno' =>
                            'administrar',
                    ],
                    $parametrosRetorno
                )
            )
            ->with(
                'success',
                'Abastecimiento actualizado correctamente.'
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
     * - muestra únicamente el último abastecimiento registrado
     *   de cada unidad;
     * - limita el resultado a empresas activas;
     * - exige que los tapones abiertos por la operación conserven
     *   los marchamos instalados por ese abastecimiento;
     * - excluye por completo los registros históricos o bloqueados.
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
                        Abastecimiento::MODELO_KILOMETROS_GALON,
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

        $consultaEjecutada =
            $request->boolean('consultar');

        /* La empresa obligatoria limita el alcance, pero no ejecuta la búsqueda. */
        $hayFiltros =
            $consultaEjecutada
            || ! empty($unidadIds)
            || ! empty($motoristaIds)
            || filled($tipoOrigen)
            || filled($modeloMedicion)
            || filled($fechaDesde)
            || filled($fechaHasta)
            || (! $soloAdministrables && filled($estado))
            || ($esUsuarioDieselCop && ! empty($empresaIds));

        $query = Abastecimiento::query()
            ->with([
                'empresa',
                'unidad',
                'motorista',
                'gasolineraInterna',
                'gasolineraExterna',
            ]);

        if ($soloAdministrables) {
            $this->aplicarSoloAbastecimientosAdministrables(
                $query
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
                    Abastecimiento::MODELO_KILOMETROS_GALON,
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
     * Limita una consulta a abastecimientos realmente administrables.
     *
     * Un abastecimiento es administrable únicamente cuando:
     *
     * - permanece registrado;
     * - es el último registro vigente de su unidad;
     * - su empresa está activa;
     * - posee un evento vigente de marchamos originado por abastecimiento;
     * - todos los tapones abiertos por esa operación conservan exactamente
     *   el marchamo que fue instalado por ese mismo abastecimiento.
     */
    private function aplicarSoloAbastecimientosAdministrables(
        Builder $query
    ): void {
        $query
            ->where(
                'abastecimientos.estado',
                Abastecimiento::ESTADO_REGISTRADO
            )
            ->whereHas(
                'empresa',
                fn (Builder $empresaQuery) =>
                    $empresaQuery->where(
                        'estado',
                        'activa'
                    )
            )
            ->whereNotExists(
                function ($subquery) {
                    $subquery
                        ->selectRaw('1')
                        ->from(
                            'abastecimientos as abastecimientos_mas_recientes'
                        )
                        ->whereColumn(
                            'abastecimientos_mas_recientes.unidad_id',
                            'abastecimientos.unidad_id'
                        )
                        ->where(
                            'abastecimientos_mas_recientes.estado',
                            Abastecimiento::ESTADO_REGISTRADO
                        )
                        ->where(
                            function ($comparacion) {
                                $comparacion
                                    ->whereColumn(
                                        'abastecimientos_mas_recientes.fecha_hora_abastecimiento',
                                        '>',
                                        'abastecimientos.fecha_hora_abastecimiento'
                                    )
                                    ->orWhere(
                                        function ($empate) {
                                            $empate
                                                ->whereColumn(
                                                    'abastecimientos_mas_recientes.fecha_hora_abastecimiento',
                                                    '=',
                                                    'abastecimientos.fecha_hora_abastecimiento'
                                                )
                                                ->whereColumn(
                                                    'abastecimientos_mas_recientes.id',
                                                    '>',
                                                    'abastecimientos.id'
                                                );
                                        }
                                    );
                            }
                        );
                }
            )
            ->whereExists(
                function ($subquery) {
                    $subquery
                        ->selectRaw('1')
                        ->from(
                            'reemplazo_marchamos_eventos as evento_marchamos'
                        )
                        ->join(
                            'reemplazo_marchamos_detalle as detalle_marchamos',
                            'detalle_marchamos.reemplazo_evento_id',
                            '=',
                            'evento_marchamos.id'
                        )
                        ->whereColumn(
                            'evento_marchamos.abastecimiento_id',
                            'abastecimientos.id'
                        )
                        ->where(
                            'evento_marchamos.origen_evento',
                            'abastecimiento'
                        )
                        ->where(
                            'evento_marchamos.estado',
                            'registrado'
                        );
                }
            )
            ->whereNotExists(
                function ($subquery) {
                    $subquery
                        ->selectRaw('1')
                        ->from(
                            'reemplazo_marchamos_eventos as evento_validacion'
                        )
                        ->join(
                            'reemplazo_marchamos_detalle as detalle_validacion',
                            'detalle_validacion.reemplazo_evento_id',
                            '=',
                            'evento_validacion.id'
                        )
                        ->leftJoin(
                            'puntos_seguridad_unidad as punto_validacion',
                            'punto_validacion.id',
                            '=',
                            'detalle_validacion.punto_seguridad_id'
                        )
                        ->leftJoin(
                            'marchamos as marchamo_validacion',
                            'marchamo_validacion.id',
                            '=',
                            'detalle_validacion.marchamo_nuevo_id'
                        )
                        ->whereColumn(
                            'evento_validacion.abastecimiento_id',
                            'abastecimientos.id'
                        )
                        ->where(
                            'evento_validacion.origen_evento',
                            'abastecimiento'
                        )
                        ->where(
                            'evento_validacion.estado',
                            'registrado'
                        )
                        ->where(
                            function ($invalido) {
                                $invalido
                                    ->whereNull(
                                        'punto_validacion.id'
                                    )
                                    ->orWhereNull(
                                        'marchamo_validacion.id'
                                    )
                                    ->orWhereNull(
                                        'punto_validacion.marchamo_actual_id'
                                    )
                                    ->orWhereColumn(
                                        'punto_validacion.marchamo_actual_id',
                                        '!=',
                                        'detalle_validacion.marchamo_nuevo_id'
                                    )
                                    ->orWhere(
                                        'marchamo_validacion.estado',
                                        '!=',
                                        'activo'
                                    )
                                    ->orWhereNull(
                                        'marchamo_validacion.activo_actual'
                                    )
                                    ->orWhere(
                                        'marchamo_validacion.activo_actual',
                                        '!=',
                                        1
                                    );
                            }
                        );
                }
            );
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
            $this->aplicarSoloAbastecimientosAdministrables(
                $base
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
                'abastecimientos',
                function (Builder $query) use (
                    $soloAdministrables
                ) {
                    if ($soloAdministrables) {
                        $this
                            ->aplicarSoloAbastecimientosAdministrables(
                                $query
                            );
                    }
                }
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
                        $this
                            ->aplicarSoloAbastecimientosAdministrables(
                                $query
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
                        $this
                            ->aplicarSoloAbastecimientosAdministrables(
                                $query
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
    | Preparación de modificación
    |--------------------------------------------------------------------------
    */

    /**
     * Prepara el formulario para modificar el último abastecimiento vigente.
     */
    private function prepararFormularioEdicion(
        Request $request,
        Abastecimiento $abastecimiento,
        AbastecimientoService $service
    ): array {
        $abastecimiento->load([
            'empresa',
            'unidad.empresa',
            'unidad.licencia',
            'motorista',
            'abastecimientoAnterior',
            'gasolineraInterna',
            'gasolineraExterna',
            'tanques.tanque',
            'rutas.ruta.puntoOrigen',
            'rutas.ruta.puntoDestino',
            'reemplazoMarchamoEvento.detalles.puntoSeguridad',
            'reemplazoMarchamoEvento.detalles.marchamoAnterior',
            'reemplazoMarchamoEvento.detalles.marchamoNuevo',
        ]);

        $this->validarAbastecimientoEditable(
            $abastecimiento
        );

        $unidad = $abastecimiento->unidad;

        if (! $unidad) {
            abort(
                404,
                'La unidad asociada al abastecimiento ya no está disponible.'
            );
        }

        /*
         * Solo se validan los puntos de seguridad que fueron abiertos
         * específicamente por este abastecimiento.
         */
        $eventoMarchamos =
            $abastecimiento->reemplazoMarchamoEvento;

        if (
            ! $eventoMarchamos
            || $eventoMarchamos->estado
                !== 'registrado'
            || $eventoMarchamos->detalles->isEmpty()
        ) {
            abort(
                403,
                'El abastecimiento no posee un evento vigente de marchamos y no puede modificarse.'
            );
        }

        foreach (
            $eventoMarchamos->detalles
            as $detalle
        ) {
            $punto =
                $detalle->puntoSeguridad;

            $marchamoInstalado =
                $detalle->marchamoNuevo;

            if (
                ! $punto
                || ! $marchamoInstalado
                || (int) $punto->marchamo_actual_id
                    !== (int) $marchamoInstalado->id
                || $marchamoInstalado->estado
                    !== 'activo'
                || ! (bool) $marchamoInstalado
                    ->activo_actual
            ) {
                abort(
                    403,
                    'Uno de los tapones abiertos por este abastecimiento fue intervenido posteriormente. La operación ya no puede modificarse.'
                );
            }
        }

        $motoristas = Motorista::query()
            ->where(
                'empresa_id',
                $abastecimiento->empresa_id
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
                        function ($query) use (
                            $abastecimiento
                        ) {
                            $tanquesOriginales =
                                $abastecimiento
                                    ->tanques
                                    ->pluck('tanque_id')
                                    ->map(
                                        fn ($id): int =>
                                            (int) $id
                                    )
                                    ->all();

                            $query
                                ->where(
                                    function ($tanqueQuery) use (
                                        $tanquesOriginales
                                    ) {
                                        $tanqueQuery
                                            ->where(
                                                'estado',
                                                'activo'
                                            )
                                            ->where(
                                                'volumen_actual',
                                                '>',
                                                0
                                            );

                                        if (
                                            ! empty(
                                                $tanquesOriginales
                                            )
                                        ) {
                                            $tanqueQuery
                                                ->orWhereIn(
                                                    'id',
                                                    $tanquesOriginales
                                                );
                                        }
                                    }
                                )
                                ->orderBy('nombre');
                        },
                ])
                ->where(
                    'empresa_id',
                    $abastecimiento->empresa_id
                )
                ->where(
                    'estado',
                    'activa'
                )
                ->whereHas(
                    'tanques',
                    function ($query) use (
                        $abastecimiento
                    ) {
                        $tanquesOriginales =
                            $abastecimiento
                                ->tanques
                                ->pluck('tanque_id')
                                ->map(
                                    fn ($id): int =>
                                        (int) $id
                                )
                                ->all();

                        $query->where(
                            function ($tanqueQuery) use (
                                $tanquesOriginales
                            ) {
                                $tanqueQuery
                                    ->where(
                                        function ($activos) {
                                            $activos
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
                                    );

                                if (
                                    ! empty(
                                        $tanquesOriginales
                                    )
                                ) {
                                    $tanqueQuery
                                        ->orWhereIn(
                                            'id',
                                            $tanquesOriginales
                                        );
                                }
                            }
                        );
                    }
                )
                ->orderBy('nombre')
                ->get();

        $gasolinerasExternas =
            GasolineraExterna::query()
                ->where(
                    'empresa_id',
                    $abastecimiento->empresa_id
                )
                ->where(
                    'estado',
                    'activa'
                )
                ->orderBy('compania')
                ->get();

        $rutas = collect();

        if (
            $abastecimiento->modelo_medicion
            === Abastecimiento::MODELO_GALONES_VIAJE
            && $abastecimiento
                ->abastecimiento_anterior_id
        ) {
            $rutas = Ruta::query()
                ->with([
                    'puntoOrigen',
                    'puntoDestino',
                ])
                ->where(
                    'empresa_id',
                    $abastecimiento->empresa_id
                )
                ->where(
                    'estado',
                    'activo'
                )
                ->whereHas(
                    'puntoOrigen',
                    fn (Builder $query) =>
                        $query->where(
                            'estado',
                            'activo'
                        )
                )
                ->whereHas(
                    'puntoDestino',
                    fn (Builder $query) =>
                        $query->where(
                            'estado',
                            'activo'
                        )
                )
                ->orderBy('ruta')
                ->get();
        }

        $parametrosRetorno =
            $this->obtenerParametrosRetorno(
                $request
            );

        return [
            'abastecimiento' =>
                $abastecimiento,

            'unidad' =>
                $unidad,

            'motoristas' =>
                $motoristas,

            'gasolinerasInternas' =>
                $gasolinerasInternas,

            'gasolinerasExternas' =>
                $gasolinerasExternas,

            'rutas' =>
                $rutas,

            'eventoMarchamos' =>
                $eventoMarchamos,

            'detallesMarchamos' =>
                $eventoMarchamos
                    ->detalles
                    ->values(),

            'requiereRutas' =>
                $abastecimiento->modelo_medicion
                === Abastecimiento::MODELO_GALONES_VIAJE
                && ! is_null(
                    $abastecimiento
                        ->abastecimiento_anterior_id
                ),

            'tiposOrigen' => [
                Abastecimiento::ORIGEN_INTERNO =>
                    'Gasolinera interna',

                Abastecimiento::ORIGEN_EXTERNO =>
                    'Gasolinera externa',
            ],

            'abastecimientoVersion' =>
                $service->versionAbastecimiento(
                    $abastecimiento
                ),

            'parametrosRetorno' =>
                $parametrosRetorno,
        ];
    }

    /**
     * Confirma que el abastecimiento todavía puede abrirse en edición.
     */
    private function validarAbastecimientoEditable(
        Abastecimiento $abastecimiento
    ): void {
        if (! $abastecimiento->estaRegistrado()) {
            abort(
                403,
                'El abastecimiento no se encuentra registrado y no puede modificarse.'
            );
        }

        if (
            ! $abastecimiento->empresa
            || $abastecimiento->empresa->estado
                !== 'activa'
        ) {
            abort(
                403,
                'La empresa del abastecimiento está inactiva.'
            );
        }

        $ultimoAbastecimiento =
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

        if (
            ! $ultimoAbastecimiento
            || (int) $ultimoAbastecimiento->id
                !== (int) $abastecimiento->id
        ) {
            abort(
                403,
                'Solo el último abastecimiento vigente de la unidad puede modificarse.'
            );
        }
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

        /* La empresa obligatoria limita el alcance, pero no ejecuta la búsqueda. */
        $hayFiltros =
            $consultaEjecutada
            || filled($busquedaEmpresa)
            || filled($busquedaPlaca)
            || $placas->isNotEmpty()
            || ($esUsuarioDieselCop && $empresaIds->isNotEmpty());

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