<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Unidad;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UnidadController extends Controller
{
    /**
     * Consulta informativa de unidades.
     */
    public function index(Request $request): View
    {
        $data = $this->prepararConsultaUnidades($request);

        return view('unidades.index', $data);
    }

    /**
     * Consulta informativa de unidades en ventana independiente.
     */
    public function consultaVentana(Request $request): View
    {
        $data = $this->prepararConsultaUnidades($request);

        return view('unidades.index-ventana', $data);
    }

    /**
     * Consulta administrativa de unidades.
     */
    public function administrar(Request $request): View
    {
        $data = $this->prepararConsultaUnidades(
            $request,
            true
        );

        return view('unidades.administrar', $data);
    }

    /**
     * Consulta administrativa en ventana independiente.
     */
    public function administrarVentana(Request $request): View
    {
        $data = $this->prepararConsultaUnidades(
            $request,
            true
        );

        return view('unidades.administrar-ventana', $data);
    }

    /**
     * Prepara los datos comunes de Consulta y Administración.
     *
     * La disponibilidad operativa se calcula mediante:
     *
     * - estado de la empresa;
     * - estado administrativo de la unidad;
     * - condición de la licencia;
     * - asignación inicial de marchamos.
     */
    private function prepararConsultaUnidades(
        Request $request,
        bool $modoAdministracion = false
    ): array
    {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);

        $empresaUsuario = $esUsuarioDieselCop
            ? null
            : Empresa::find($user->empresa_id);

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
                'nullable',
                'integer',
                Rule::exists('empresas', 'id'),
            ],

            'unidad_ids' => [
                'nullable',
                'array',
            ],

            'unidad_ids.*' => [
                'nullable',
                'integer',
                Rule::exists('unidades', 'id'),
            ],

            'modelos_medicion' => [
                'nullable',
                'array',
            ],

            'modelos_medicion.*' => [
                'nullable',
                Rule::in(
                    array_keys($this->modelosMedicion())
                ),
            ],

            /*
             * Parámetros simples conservados por compatibilidad.
             */
            'empresa_id' => [
                'nullable',
                'integer',
                Rule::exists('empresas', 'id'),
            ],

            'unidad_id' => [
                'nullable',
                'integer',
                Rule::exists('unidades', 'id'),
            ],

            'modelo_medicion' => [
                'nullable',
                Rule::in(
                    array_keys($this->modelosMedicion())
                ),
            ],

            'estado' => [
                'nullable',
                Rule::in(
                    array_keys($this->estadosUnidad())
                ),
            ],
        ], [
            'busqueda.max' =>
                'La búsqueda no debe exceder 150 caracteres.',

            'empresa_ids.array' =>
                'La selección de empresas no es válida.',

            'empresa_ids.*.exists' =>
                'Una de las empresas seleccionadas no es válida.',

            'empresa_id.exists' =>
                'La empresa seleccionada no es válida.',

            'unidad_ids.array' =>
                'La selección de unidades no es válida.',

            'unidad_ids.*.exists' =>
                'Una de las unidades seleccionadas no es válida.',

            'modelos_medicion.array' =>
                'La selección de modelos de medición no es válida.',

            'modelos_medicion.*.in' =>
                'Uno de los modelos de medición seleccionados no es válido.',

            'modelo_medicion.in' =>
                'El modelo de medición seleccionado no es válido.',

            'estado.in' =>
                'El estado seleccionado no es válido.',
        ]);

        $busqueda = trim(
            (string) ($validated['busqueda'] ?? '')
        );

        $empresaIds = collect(
            $validated['empresa_ids'] ?? []
        )
            ->filter()
            ->map(fn ($id) => (int) $id);

        if (! empty($validated['empresa_id'])) {
            $empresaIds->push(
                (int) $validated['empresa_id']
            );
        }

        $empresaIds = $empresaIds
            ->unique()
            ->values()
            ->all();

        $unidadIds = collect(
            $validated['unidad_ids'] ?? []
        )
            ->filter()
            ->map(fn ($id) => (int) $id);

        if (! empty($validated['unidad_id'])) {
            $unidadIds->push(
                (int) $validated['unidad_id']
            );
        }

        $unidadIds = $unidadIds
            ->unique()
            ->values()
            ->all();

        $modelosMedicionSeleccionados = collect(
            $validated['modelos_medicion'] ?? []
        )->filter();

        $modeloMedicion =
            $validated['modelo_medicion'] ?? null;

        if ($modeloMedicion) {
            $modelosMedicionSeleccionados->push(
                $modeloMedicion
            );
        }

        $modelosMedicionSeleccionados =
            $modelosMedicionSeleccionados
                ->unique()
                ->values()
                ->all();

        $estado = $validated['estado'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | Alcance multiempresa
        |--------------------------------------------------------------------------
        */

        if (! $esUsuarioDieselCop) {
            $empresaIds = [
                (int) $user->empresa_id,
            ];
        }

        $empresaId = $empresaIds[0] ?? null;

        /*
         * La empresa obligatoria del usuario empresarial define alcance,
         * pero no debe ejecutar automáticamente la consulta.
         */
        $hayFiltros = $request->boolean('consultar')
            || $busqueda !== ''
            || count($unidadIds) > 0
            || count($modelosMedicionSeleccionados) > 0
            || in_array(
                $estado,
                array_keys($this->estadosUnidad()),
                true
            )
            || (
                $esUsuarioDieselCop
                && count($empresaIds) > 0
            );

        /*
        |--------------------------------------------------------------------------
        | Empresas disponibles
        |--------------------------------------------------------------------------
        */

        $empresas = Empresa::query()
            ->when(
                $modoAdministracion,
                fn (Builder $query) =>
                    $query->where('estado', 'activa')
            )
            ->when(
                ! $esUsuarioDieselCop,
                function (Builder $query) use ($user) {
                    $query->where(
                        'id',
                        $user->empresa_id
                    );
                }
            )
            ->orderBy('nombre_comercial')
            ->orderBy('nombre_legal')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Consulta base
        |--------------------------------------------------------------------------
        |
        | Se cargan las relaciones utilizadas para calcular la disponibilidad
        | operativa y evitar consultas repetidas por cada unidad.
        |
        */

        $baseQuery = Unidad::query()
            ->with([
                'empresa',
                'licencia',
                'puntosSeguridad',
            ])
            ->when(
                $modoAdministracion,
                function (Builder $query) {
                    $query->whereHas(
                        'empresa',
                        function (Builder $empresaQuery) {
                            $empresaQuery->where(
                                'estado',
                                'activa'
                            );
                        }
                    );
                }
            )
            ->when(
                ! $esUsuarioDieselCop,
                function (Builder $query) use ($user) {
                    $query->where(
                        'empresa_id',
                        $user->empresa_id
                    );
                }
            );

        /*
        |--------------------------------------------------------------------------
        | Selector de unidades
        |--------------------------------------------------------------------------
        */

        $unidadesSelectorQuery = clone $baseQuery;

        if (count($empresaIds) > 0) {
            $unidadesSelectorQuery->whereIn(
                'empresa_id',
                $empresaIds
            );
        }

        $unidadesSelector = $unidadesSelectorQuery
            ->with('empresa')
            ->whereNotNull('placa')
            ->orderBy('empresa_id')
            ->orderBy('placa')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Resultados
        |--------------------------------------------------------------------------
        */

        $unidadesQuery = clone $baseQuery;

        if ($hayFiltros) {
            $this->aplicarFiltrosUnidad(
                query: $unidadesQuery,
                busqueda: $busqueda,
                empresaIds: $empresaIds,
                unidadIds: $unidadIds,
                modelosMedicionSeleccionados:
                    $modelosMedicionSeleccionados,
                estado: $estado
            );
        } else {
            $unidadesQuery->whereRaw('1 = 0');
        }

        /*
        |--------------------------------------------------------------------------
        | Resumen
        |--------------------------------------------------------------------------
        */

        $baseResumen = clone $baseQuery;

        if ($hayFiltros) {
            $this->aplicarFiltrosUnidad(
                query: $baseResumen,
                busqueda: $busqueda,
                empresaIds: $empresaIds,
                unidadIds: $unidadIds,
                modelosMedicionSeleccionados:
                    $modelosMedicionSeleccionados,
                estado: $estado
            );
        }

        $totalUnidades = (clone $baseQuery)->count();

        $totalRegistradas = (clone $baseQuery)
            ->where('estado', 'registrada')
            ->count();

        $totalActivas = (clone $baseQuery)
            ->where('estado', 'activa')
            ->count();

        $totalInactivas = (clone $baseQuery)
            ->where('estado', 'inactiva')
            ->count();

        $resumenUnidades = [
            'total' => $hayFiltros
                ? (clone $baseResumen)->count()
                : $totalUnidades,

            'registradas' => $hayFiltros
                ? (clone $baseResumen)
                    ->where('estado', 'registrada')
                    ->count()
                : $totalRegistradas,

            'activas' => $hayFiltros
                ? (clone $baseResumen)
                    ->where('estado', 'activa')
                    ->count()
                : $totalActivas,

            'inactivas' => $hayFiltros
                ? (clone $baseResumen)
                    ->where('estado', 'inactiva')
                    ->count()
                : $totalInactivas,
        ];

        $unidades = $unidadesQuery
            ->orderBy('placa')
            ->paginate(10)
            ->withQueryString();

        return [
            'unidades' => $unidades,
            'empresas' => $empresas,

            'empresaId' => $empresaId,
            'empresaIds' => $empresaIds,

            'estado' => $estado,

            'modeloMedicion' => $modeloMedicion,
            'unidadId' => $unidadIds[0] ?? null,

            'busqueda' => $busqueda,

            'unidadIds' => $unidadIds,
            'unidadesSelector' => $unidadesSelector,

            'modelosMedicionSeleccionados' =>
                $modelosMedicionSeleccionados,

            'hayFiltros' => $hayFiltros,

            'totalUnidades' => $totalUnidades,
            'totalRegistradas' => $totalRegistradas,
            'totalActivas' => $totalActivas,
            'totalInactivas' => $totalInactivas,

            'resumenUnidades' => $resumenUnidades,

            'esUsuarioDieselCop' =>
                $esUsuarioDieselCop,

            'empresaUsuario' =>
                $empresaUsuario,

            'modelosMedicion' =>
                $this->modelosMedicion(),

            'estadosUnidad' =>
                $this->estadosUnidad(),
        ];
    }

    /**
     * Aplica los filtros comunes.
     */
    private function aplicarFiltrosUnidad(
        Builder $query,
        string $busqueda,
        array $empresaIds,
        array $unidadIds,
        array $modelosMedicionSeleccionados,
        ?string $estado
    ): void {
        if (count($empresaIds) > 0) {
            $query->whereIn(
                'empresa_id',
                $empresaIds
            );
        }

        if ($busqueda !== '') {
            $query->where(
                function (Builder $subquery) use ($busqueda) {
                    $subquery
                        ->where(
                            'placa',
                            'like',
                            '%' . $busqueda . '%'
                        )
                        ->orWhereHas(
                            'empresa',
                            function (
                                Builder $empresaQuery
                            ) use ($busqueda) {
                                $empresaQuery
                                    ->where(
                                        'nombre_legal',
                                        'like',
                                        '%' . $busqueda . '%'
                                    )
                                    ->orWhere(
                                        'nombre_comercial',
                                        'like',
                                        '%' . $busqueda . '%'
                                    );
                            }
                        );
                }
            );
        }

        if (count($unidadIds) > 0) {
            $query->whereIn(
                'id',
                $unidadIds
            );
        }

        if (
            count($modelosMedicionSeleccionados) > 0
        ) {
            $query->whereIn(
                'modelo_medicion',
                $modelosMedicionSeleccionados
            );
        }

        if (
            in_array(
                $estado,
                array_keys($this->estadosUnidad()),
                true
            )
        ) {
            $query->where(
                'estado',
                $estado
            );
        }
    }

    /**
     * Formulario de registro.
     */
    public function create(): View
    {
        $data = $this->prepararFormularioUnidad();

        return view('unidades.create', $data);
    }

    /**
     * Formulario de registro en ventana independiente.
     */
    public function createVentana(): View
    {
        $data = $this->prepararFormularioUnidad();

        return view('unidades.create-ventana', $data);
    }

    /**
     * Prepara el formulario de registro.
     */
    private function prepararFormularioUnidad(): array
    {
        $user = Auth::user();

        $esUsuarioDieselCop =
            is_null($user->empresa_id);

        $empresaUsuario = $esUsuarioDieselCop
            ? null
            : Empresa::find($user->empresa_id);

        if (
            ! $esUsuarioDieselCop
            && (
                ! $empresaUsuario
                || $empresaUsuario->estado !== 'activa'
            )
        ) {
            abort(
                403,
                'No se puede registrar una unidad porque la empresa está inactiva.'
            );
        }

        $empresas = $esUsuarioDieselCop
            ? Empresa::query()
                ->where('estado', 'activa')
                ->orderBy('nombre_comercial')
                ->orderBy('nombre_legal')
                ->get()
            : collect([
                $empresaUsuario,
            ])
                ->filter()
                ->values();

        return [
            'empresas' => $empresas,
            'empresaUsuario' => $empresaUsuario,
            'esUsuarioDieselCop' =>
                $esUsuarioDieselCop,
            'modelosMedicion' =>
                $this->modelosMedicion(),
            'estadosUnidad' =>
                $this->estadosUnidad(),
        ];
    }

    /**
     * Guarda una unidad nueva.
     *
     * La unidad se registra sin licencia y permanece pendiente
     * de configuración inicial.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $esUsuarioDieselCop =
            is_null($user->empresa_id);

        $empresaId = $esUsuarioDieselCop
            ? (int) $request->validate([
                'empresa_id' => [
                    'required',
                    'integer',
                    Rule::exists('empresas', 'id')
                        ->where('estado', 'activa'),
                ],
            ])['empresa_id']
            : (int) $user->empresa_id;

        $validated = $request->validate(
            $this->reglasValidacionUnidad(
                unidad: null,
                empresaId: $empresaId,
                esUsuarioDieselCop:
                    $esUsuarioDieselCop
            ),
            $this->mensajesValidacionUnidad()
        );

        $this->validarEmpresaActivaPorId(
            $empresaId
        );

        $unidad = Unidad::create([
            'empresa_id' => $empresaId,
            'placa' => trim($validated['placa']),
            'marca' => $validated['marca'] ?? null,
            'total_tanques' =>
                $validated['total_tanques'],
            'cantidad_tanques_con_licencia' =>
                $validated[
                    'cantidad_tanques_con_licencia'
                ],
            'capacidad_total' =>
                $validated['capacidad_total'],
            'capacidad_cubierta' =>
                $validated['capacidad_cubierta'],
            'modelo_medicion' =>
                $validated['modelo_medicion'],
            'estado' => 'registrada',
            'creado_por' => $user->id,
            'actualizado_por' => $user->id,
        ]);

        $queryParams = $this->parametrosRetorno(
            $request
        );

        if (
            $request->input('return_to')
            === 'ventana'
        ) {
            return redirect()
                ->route(
                    'unidades.show.ventana',
                    array_merge(
                        $queryParams,
                        [
                            'unidad' => $unidad,
                        ]
                    )
                )
                ->with(
                    'success',
                    'Unidad creada correctamente. Permanece registrada y pendiente de licencia.'
                );
        }

        return redirect()
            ->route(
                'unidades.show',
                array_merge(
                    $queryParams,
                    [
                        'unidad' => $unidad,
                    ]
                )
            )
            ->with(
                'success',
                'Unidad creada correctamente. Permanece registrada y pendiente de licencia.'
            );
    }

    /**
     * Ficha administrativa.
     *
     * La ficha permanece accesible aunque la unidad esté bloqueada
     * por licencia, porque debe explicar su disponibilidad.
     */
    public function show(Unidad $unidad): View
    {
        $this->autorizarAccesoUnidad($unidad);
        $this->validarEmpresaActivaUnidad($unidad);

        $this->cargarRelacionesFicha($unidad);

        return view(
            'unidades.show',
            compact('unidad')
        );
    }

    /**
     * Ficha administrativa en ventana independiente.
     */
    public function showVentana(Unidad $unidad): View
    {
        $this->autorizarAccesoUnidad($unidad);
        $this->validarEmpresaActivaUnidad($unidad);

        $this->cargarRelacionesFicha($unidad);

        return view(
            'unidades.show-ventana',
            compact('unidad')
        );
    }

    /**
     * Formulario de edición.
     */
    public function edit(Unidad $unidad): View
    {
        $this->autorizarAccesoUnidad($unidad);
        $this->validarEmpresaActivaUnidad($unidad);
        $this->validarUnidadEditable($unidad);

        $data = $this->prepararEdicionUnidad(
            $unidad
        );

        return view(
            'unidades.edit',
            $data
        );
    }

    /**
     * Formulario de edición en ventana independiente.
     */
    public function editVentana(Unidad $unidad): View
    {
        $this->autorizarAccesoUnidad($unidad);
        $this->validarEmpresaActivaUnidad($unidad);
        $this->validarUnidadEditable($unidad);

        $data = $this->prepararEdicionUnidad(
            $unidad
        );

        return view(
            'unidades.edit-ventana',
            $data
        );
    }

    /**
     * Prepara los datos comunes de edición.
     */
    private function prepararEdicionUnidad(
        Unidad $unidad
    ): array {
        $user = Auth::user();

        $esUsuarioDieselCop =
            is_null($user->empresa_id);

        $unidad->loadMissing([
            'empresa',
            'licencia',
            'puntosSeguridad',
        ]);

        return [
            'unidad' => $unidad,

            /*
             * La empresa permanece bloqueada.
             */
            'empresas' => collect([
                $unidad->empresa,
            ])
                ->filter()
                ->values(),

            'empresaUsuario' =>
                $unidad->empresa,

            'esUsuarioDieselCop' =>
                $esUsuarioDieselCop,

            'modelosMedicion' =>
                $this->modelosMedicion(),

            'estadosUnidad' =>
                $this->estadosUnidad(),
        ];
    }

    /**
     * Actualiza una unidad.
     */
    public function update(
        Request $request,
        Unidad $unidad
    ): RedirectResponse {
        $this->autorizarAccesoUnidad($unidad);
        $this->validarEmpresaActivaUnidad($unidad);
        $this->validarUnidadEditable($unidad);

        $user = Auth::user();

        $validated = $request->validate(
            $this->reglasValidacionUnidad(
                unidad: $unidad,
                empresaId: (int) $unidad->empresa_id,
                esUsuarioDieselCop:
                    is_null($user->empresa_id)
            ),
            $this->mensajesValidacionUnidad()
        );

        /*
         * Empresa y estado no pueden cambiarse desde edición.
         */
        $unidad->update([
            'placa' => trim($validated['placa']),
            'marca' => $validated['marca'] ?? null,
            'total_tanques' =>
                $validated['total_tanques'],
            'cantidad_tanques_con_licencia' =>
                $validated[
                    'cantidad_tanques_con_licencia'
                ],
            'capacidad_total' =>
                $validated['capacidad_total'],
            'capacidad_cubierta' =>
                $validated['capacidad_cubierta'],
            'modelo_medicion' =>
                $validated['modelo_medicion'],
            'actualizado_por' => $user->id,
        ]);

        $queryParams = $this->parametrosRetorno(
            $request
        );

        if (
            $request->input('return_to')
            === 'ventana'
        ) {
            return redirect()
                ->route(
                    'unidades.show.ventana',
                    array_merge(
                        $queryParams,
                        [
                            'unidad' => $unidad,
                        ]
                    )
                )
                ->with(
                    'success',
                    'Unidad actualizada correctamente.'
                );
        }

        return redirect()
            ->route(
                'unidades.show',
                array_merge(
                    $queryParams,
                    [
                        'unidad' => $unidad,
                    ]
                )
            )
            ->with(
                'success',
                'Unidad actualizada correctamente.'
            );
    }

    /**
     * Inactiva administrativamente una unidad.
     */
    public function inactivar(
        Request $request,
        Unidad $unidad
    ): RedirectResponse {
        $this->autorizarAccesoUnidad($unidad);
        $this->validarEmpresaActivaUnidad($unidad);
        $this->validarUnidadNoInactiva($unidad);
        $this->validarUnidadPuedeInactivarse($unidad);

        $validated = $request->validate([
            'motivo_inactivacion' => [
                'required',
                'string',
                'max:150',
                Rule::in(
                    $this->motivosInactivacion()
                ),
            ],
        ], [
            'motivo_inactivacion.required' =>
                'Debe seleccionar el motivo de inactivación.',

            'motivo_inactivacion.in' =>
                'El motivo de inactivación seleccionado no es válido.',

            'motivo_inactivacion.max' =>
                'El motivo de inactivación no debe exceder 150 caracteres.',
        ]);

        $unidad->update([
            'estado' => 'inactiva',
            'fecha_inactivacion' => now(),
            'inactivado_por' => Auth::id(),
            'motivo_inactivacion' =>
                $validated['motivo_inactivacion'],
            'actualizado_por' => Auth::id(),
        ]);

        $queryParams = $this->parametrosRetorno(
            $request
        );

        if (
            $request->input('return_to')
            === 'ventana'
        ) {
            return redirect()
                ->route(
                    'unidades.show.ventana',
                    array_merge(
                        $queryParams,
                        [
                            'unidad' => $unidad,
                        ]
                    )
                )
                ->with(
                    'success',
                    'Unidad inactivada correctamente.'
                );
        }

        return redirect()
            ->route(
                'unidades.show',
                array_merge(
                    $queryParams,
                    [
                        'unidad' => $unidad,
                    ]
                )
            )
            ->with(
                'success',
                'Unidad inactivada correctamente.'
            );
    }

    /**
     * Reactiva administrativamente una unidad.
     *
     * La licencia no se modifica. La unidad regresa a registrada
     * y su disponibilidad se recalcula de manera independiente.
     */
    public function reactivar(
        Request $request,
        Unidad $unidad
    ): RedirectResponse {
        $this->autorizarAccesoUnidad($unidad);
        $this->validarEmpresaActivaUnidad($unidad);
        $this->validarUnidadInactivaParaReactivacion(
            $unidad
        );

        $unidad->update([
            'estado' => 'registrada',
            'fecha_inactivacion' => null,
            'inactivado_por' => null,
            'motivo_inactivacion' => null,
            'actualizado_por' => Auth::id(),
        ]);

        $unidad->refresh();
        $unidad->load([
            'empresa',
            'licencia',
            'puntosSeguridad',
        ]);

        $queryParams = $this->parametrosRetorno(
            $request
        );

        $mensaje = sprintf(
            'Unidad reactivada correctamente. Queda registrada. Disponibilidad: %s.',
            $unidad->disponibilidad_operativa_texto
        );

        if (
            $request->input('return_to')
            === 'ventana'
        ) {
            return redirect()
                ->route(
                    'unidades.show.ventana',
                    array_merge(
                        $queryParams,
                        [
                            'unidad' => $unidad,
                        ]
                    )
                )
                ->with(
                    'success',
                    $mensaje
                );
        }

        return redirect()
            ->route(
                'unidades.show',
                array_merge(
                    $queryParams,
                    [
                        'unidad' => $unidad,
                    ]
                )
            )
            ->with(
                'success',
                $mensaje
            );
    }

    /**
     * Reglas del formulario de unidad.
     */
    private function reglasValidacionUnidad(
        ?Unidad $unidad,
        int $empresaId,
        bool $esUsuarioDieselCop
    ): array {
        return [
            'empresa_id' => [
                is_null($unidad)
                    && $esUsuarioDieselCop
                        ? 'required'
                        : 'nullable',

                'integer',

                Rule::exists(
                    'empresas',
                    'id'
                )->where(
                    'estado',
                    'activa'
                ),
            ],

            'placa' => [
                'required',
                'string',
                'max:30',

                Rule::unique(
                    'unidades',
                    'placa'
                )
                    ->where('empresa_id', $empresaId)
                    ->ignore($unidad?->id),
            ],

            'marca' => [
                'nullable',
                'string',
                'max:100',
            ],

            'total_tanques' => [
                'required',
                'integer',
                'min:1',
                'max:3',
            ],

            'cantidad_tanques_con_licencia' => [
                'required',
                'integer',
                'min:1',
                'max:3',
                'lte:total_tanques',
            ],

            'capacidad_total' => [
                'required',
                'numeric',
                'gt:0',
                'max:99999999.99',
            ],

            'capacidad_cubierta' => [
                'required',
                'numeric',
                'gt:0',
                'lte:capacidad_total',
                'max:99999999.99',
            ],

            'modelo_medicion' => [
                'required',
                Rule::in(
                    array_keys(
                        $this->modelosMedicion()
                    )
                ),
            ],
        ];
    }

    /**
     * Mensajes de validación.
     */
    private function mensajesValidacionUnidad(): array
    {
        return [
            'empresa_id.required' =>
                'Debe seleccionar una empresa.',

            'empresa_id.exists' =>
                'La empresa seleccionada no es válida o no está activa.',

            'placa.required' =>
                'Debe ingresar el Nombre / Placa de la unidad.',

            'placa.max' =>
                'El Nombre / Placa no debe exceder 30 caracteres.',

            'placa.unique' =>
                'Ya existe una unidad con este Nombre / Placa dentro de la empresa.',

            'marca.max' =>
                'La marca no debe exceder 100 caracteres.',

            'total_tanques.required' =>
                'Debe indicar el total de tanques de la unidad.',

            'total_tanques.integer' =>
                'El total de tanques debe ser un número entero.',

            'total_tanques.min' =>
                'La unidad debe tener al menos un tanque.',

            'total_tanques.max' =>
                'La unidad puede tener como máximo tres tanques.',

            'cantidad_tanques_con_licencia.required' =>
                'Debe indicar la cantidad de tanques cubiertos por la licencia.',

            'cantidad_tanques_con_licencia.integer' =>
                'La cantidad de tanques cubiertos debe ser un número entero.',

            'cantidad_tanques_con_licencia.min' =>
                'La licencia debe cubrir al menos un tanque.',

            'cantidad_tanques_con_licencia.max' =>
                'La licencia puede cubrir como máximo tres tanques.',

            'cantidad_tanques_con_licencia.lte' =>
                'La cantidad de tanques cubiertos no puede superar el total de tanques.',

            'capacidad_total.required' =>
                'Debe ingresar la capacidad total de la unidad.',

            'capacidad_total.numeric' =>
                'La capacidad total debe ser un valor numérico.',

            'capacidad_total.gt' =>
                'La capacidad total debe ser mayor que cero.',

            'capacidad_cubierta.required' =>
                'Debe ingresar la capacidad cubierta por la licencia.',

            'capacidad_cubierta.numeric' =>
                'La capacidad cubierta debe ser un valor numérico.',

            'capacidad_cubierta.gt' =>
                'La capacidad cubierta debe ser mayor que cero.',

            'capacidad_cubierta.lte' =>
                'La capacidad cubierta no puede superar la capacidad total.',

            'modelo_medicion.required' =>
                'Debe seleccionar un modelo de medición.',

            'modelo_medicion.in' =>
                'El modelo de medición seleccionado no es válido.',
        ];
    }

    /**
     * Catálogo de modelos de medición.
     */
    private function modelosMedicion(): array
    {
        return [
            'galones_hora' =>
                'Galones por hora',

            'kilometros_galon' =>
                'Kilómetros por galón',

            'galones_viaje' =>
                'Galones por viaje',
        ];
    }

    /**
     * Catálogo de estados administrativos.
     */
    private function estadosUnidad(): array
    {
        return [
            'registrada' =>
                'Registrada',

            'activa' =>
                'Activa',

            'inactiva' =>
                'Inactiva',
        ];
    }

    /**
     * Catálogo de motivos de inactivación de unidad.
     *
     * Estos motivos son independientes de la licencia.
     */
    private function motivosInactivacion(): array
    {
        return [
            'Falta de uso',
            'Unidad vendida',
            'Unidad fuera de operación',
            'Unidad reemplazada',
            'Datos incorrectos en registro',
            'Solicitud administrativa',
            'Suspensión temporal',
            'Otro',
        ];
    }

    /**
     * Control de acceso por empresa.
     */
    private function autorizarAccesoUnidad(
        Unidad $unidad
    ): void {
        $user = Auth::user();

        if (
            ! is_null($user->empresa_id)
            && (int) $unidad->empresa_id
                !== (int) $user->empresa_id
        ) {
            abort(
                403,
                'No tiene autorización para acceder a esta unidad.'
            );
        }
    }

    /**
     * Bloquea acciones administrativas cuando la empresa está inactiva.
     */
    private function validarEmpresaActivaUnidad(
        Unidad $unidad
    ): void {
        $unidad->loadMissing('empresa');

        if (
            ! $unidad->empresa
            || $unidad->empresa->estado !== 'activa'
        ) {
            abort(
                403,
                'No se puede operar sobre esta unidad porque la empresa está inactiva.'
            );
        }
    }

    /**
     * Valida una empresa activa por su identificador.
     */
    private function validarEmpresaActivaPorId(
        int $empresaId
    ): void {
        $empresaActiva = Empresa::query()
            ->where('id', $empresaId)
            ->where('estado', 'activa')
            ->exists();

        if (! $empresaActiva) {
            abort(
                403,
                'No se puede registrar la unidad porque la empresa está inactiva.'
            );
        }
    }

    /**
     * Valida que una unidad pueda editarse.
     *
     * Casos permitidos:
     *
     * - unidad registrada sin licencia, durante preparación inicial;
     * - unidad no inactiva con licencia vigente.
     */
    private function validarUnidadEditable(
        Unidad $unidad
    ): void {
        $unidad->loadMissing([
            'empresa',
            'licencia',
            'puntosSeguridad',
        ]);

        if ($unidad->estado === 'inactiva') {
            abort(
                403,
                'No se puede modificar esta unidad porque está inactiva. Debe reactivarla desde la ficha.'
            );
        }

        /*
         * Antes de crear la licencia, Diesel Cop todavía puede corregir
         * la información de una unidad registrada.
         */
        if (
            $unidad->estado === 'registrada'
            && ! $unidad->licencia
        ) {
            return;
        }

        if (! $unidad->licencia) {
            abort(
                403,
                'La unidad no tiene una licencia registrada y no puede modificarse en su estado actual.'
            );
        }

        if ($unidad->licencia->estado === 'inactiva') {
            abort(
                403,
                'No se puede modificar esta unidad porque su licencia está inactiva. Debe reactivar la licencia desde su ficha.'
            );
        }

        if (
            $unidad->licencia
                ->esta_pendiente_activacion
        ) {
            abort(
                403,
                'No se puede modificar esta unidad porque su licencia todavía no ha iniciado.'
            );
        }

        if ($unidad->licencia->esta_vencida) {
            abort(
                403,
                'No se puede modificar esta unidad porque su licencia está vencida. Debe renovar la licencia desde su ficha.'
            );
        }

        if (! $unidad->licencia->esta_vigente) {
            abort(
                403,
                'La licencia no habilita actualmente la modificación de esta unidad.'
            );
        }
    }

    /**
     * Evita repetir la inactivación.
     */
    private function validarUnidadNoInactiva(
        Unidad $unidad
    ): void {
        if ($unidad->estado === 'inactiva') {
            abort(
                403,
                'No se puede inactivar esta unidad porque ya se encuentra inactiva.'
            );
        }
    }

    /**
     * Valida que una unidad pueda inactivarse administrativamente.
     *
     * Una unidad registrada sin licencia puede inactivarse.
     * Cuando ya tiene licencia, esta debe estar vigente.
     */
    private function validarUnidadPuedeInactivarse(
        Unidad $unidad
    ): void {
        $unidad->loadMissing([
            'licencia',
            'puntosSeguridad',
        ]);

        if (
            $unidad->estado === 'registrada'
            && ! $unidad->licencia
        ) {
            return;
        }

        if (! $unidad->licencia) {
            abort(
                403,
                'No se puede inactivar esta unidad porque no tiene una licencia válida para su estado actual.'
            );
        }

        if ($unidad->licencia->estado === 'inactiva') {
            abort(
                403,
                'No se puede ejecutar esta acción porque la licencia está inactiva.'
            );
        }

        if (
            $unidad->licencia
                ->esta_pendiente_activacion
        ) {
            abort(
                403,
                'No se puede ejecutar esta acción porque la licencia todavía no ha iniciado.'
            );
        }

        if ($unidad->licencia->esta_vencida) {
            abort(
                403,
                'No se puede ejecutar esta acción porque la licencia está vencida.'
            );
        }

        if (! $unidad->licencia->esta_vigente) {
            abort(
                403,
                'La licencia no habilita actualmente esta acción sobre la unidad.'
            );
        }
    }

    /**
     * Evita reactivar una unidad que no esté inactiva.
     *
     * La reactivación administrativa no exige una licencia vigente.
     * La unidad puede continuar bloqueada después de regresar a registrada.
     */
    private function validarUnidadInactivaParaReactivacion(
        Unidad $unidad
    ): void {
        if ($unidad->estado !== 'inactiva') {
            abort(
                403,
                'No se puede reactivar esta unidad porque no se encuentra inactiva.'
            );
        }
    }

    /**
     * Carga las relaciones utilizadas por la ficha.
     */
    private function cargarRelacionesFicha(
        Unidad $unidad
    ): void {
        $unidad->load([
            'empresa',
            'licencia',
            'puntosSeguridad.marchamoActual',
            'creadoPor',
            'actualizadoPor',
            'inactivadoPor',
        ]);
    }

    /**
     * Conserva filtros y parámetros de navegación.
     */
    private function parametrosRetorno(
        Request $request
    ): array {
        return collect($request->query())
            ->except([
                'unidad',
            ])
            ->all();
    }
}
