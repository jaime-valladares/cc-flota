<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\PuntoRuta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PuntoRutaController extends Controller
{
    /**
     * Consulta general dentro del sistema.
     */
    public function index(Request $request)
    {
        return view(
            'puntos-ruta.index',
            $this->prepararConsultaPuntosRuta(
                $request,
                modoAdministracion: false
            )
        );
    }

    /**
     * Consulta general en ventana independiente.
     */
    public function consultaVentana(Request $request)
    {
        return view(
            'puntos-ruta.index-ventana',
            $this->prepararConsultaPuntosRuta(
                $request,
                modoAdministracion: false
            )
        );
    }

    /**
     * Administración dentro del sistema.
     */
    public function administrar(Request $request)
    {
        return view(
            'puntos-ruta.administrar',
            $this->prepararConsultaPuntosRuta(
                $request,
                modoAdministracion: true
            )
        );
    }

    /**
     * Administración en ventana independiente.
     */
    public function administrarVentana(Request $request)
    {
        return view(
            'puntos-ruta.administrar-ventana',
            $this->prepararConsultaPuntosRuta(
                $request,
                modoAdministracion: true
            )
        );
    }

    /**
     * Preparar filtros, resultados, selectores y resúmenes.
     */
    private function prepararConsultaPuntosRuta(
        Request $request,
        bool $modoAdministracion
    ): array {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);

        $empresaUsuario = $esUsuarioDieselCop
            ? null
            : Empresa::find($user->empresa_id);

        $validated = $request->validate([
            'empresa_ids' => [
                'nullable',
                'array',
            ],
            'empresa_ids.*' => [
                'integer',
                'distinct',
                'exists:empresas,id',
            ],

            'punto_ruta_ids' => [
                'nullable',
                'array',
            ],
            'punto_ruta_ids.*' => [
                'integer',
                'distinct',
                'exists:puntos_ruta,id',
            ],

            /*
             * Compatibilidad temporal con enlaces anteriores.
             */
            'empresa_id' => [
                'nullable',
                'integer',
                'exists:empresas,id',
            ],
            'punto_ruta_id' => [
                'nullable',
                'integer',
                'exists:puntos_ruta,id',
            ],

            'estado' => [
                'nullable',
                Rule::in([
                    'activo',
                    'inactivo',
                ]),
            ],
            'consultar' => [
                'nullable',
                'boolean',
            ],
            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ], [
            'empresa_ids.array' =>
                'La selección de empresas no es válida.',
            'empresa_ids.*.integer' =>
                'Una de las empresas seleccionadas no es válida.',
            'empresa_ids.*.distinct' =>
                'No debe seleccionar una misma empresa más de una vez.',
            'empresa_ids.*.exists' =>
                'Una de las empresas seleccionadas no existe.',

            'punto_ruta_ids.array' =>
                'La selección de puntos de ruta no es válida.',
            'punto_ruta_ids.*.integer' =>
                'Uno de los puntos de ruta seleccionados no es válido.',
            'punto_ruta_ids.*.distinct' =>
                'No debe seleccionar un mismo punto de ruta más de una vez.',
            'punto_ruta_ids.*.exists' =>
                'Uno de los puntos de ruta seleccionados no existe.',

            'empresa_id.exists' =>
                'La empresa seleccionada no existe.',
            'punto_ruta_id.exists' =>
                'El punto de ruta seleccionado no existe.',

            'estado.in' =>
                'El estado seleccionado no es válido.',

            'consultar.boolean' =>
                'La solicitud de consulta no es válida.',

            'page.integer' =>
                'La página solicitada no es válida.',
            'page.min' =>
                'La página solicitada no es válida.',
        ]);

        $empresaIds = $this->normalizarIdsSeleccionados(
            $validated['empresa_ids'] ?? [],
            $validated['empresa_id'] ?? null
        );

        $puntoRutaIds = $this->normalizarIdsSeleccionados(
            $validated['punto_ruta_ids'] ?? [],
            $validated['punto_ruta_id'] ?? null
        );

        $estado = $validated['estado'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | Alcance empresarial
        |--------------------------------------------------------------------------
        */

        if (! $esUsuarioDieselCop) {
            $empresaIds = collect([
                (int) $user->empresa_id,
            ]);
        }

        $consultaEjecutada = $request->boolean('consultar');

        /*
         * La empresa obligatoria limita el alcance del usuario empresarial,
         * pero no ejecuta automáticamente Consulta ni Administrar.
         */
        $hayFiltros = $consultaEjecutada
            || $puntoRutaIds->isNotEmpty()
            || filled($estado)
            || (
                $esUsuarioDieselCop
                && $empresaIds->isNotEmpty()
            );

        /*
        |--------------------------------------------------------------------------
        | Validación Empresa–Punto de Ruta
        |--------------------------------------------------------------------------
        |
        | Los puntos seleccionados deben pertenecer a una de las empresas
        | seleccionadas y al alcance empresarial del usuario.
        |
        */

        $puntosSeleccionadosValidos =
            $this->obtenerPuntosSeleccionadosValidos(
                $puntoRutaIds,
                $empresaIds,
                $modoAdministracion,
                $esUsuarioDieselCop,
                $user->empresa_id
            );

        $combinacionEmpresaPuntoInvalida =
            $puntoRutaIds->isNotEmpty()
            && $puntosSeleccionadosValidos->count()
                !== $puntoRutaIds->count();

        /*
        |--------------------------------------------------------------------------
        | Resultados
        |--------------------------------------------------------------------------
        */

        $query = $this->consultaBasePuntosRuta(
            $modoAdministracion,
            $esUsuarioDieselCop,
            $user->empresa_id
        );

        if (! $hayFiltros || $combinacionEmpresaPuntoInvalida) {
            $query->whereRaw('1 = 0');
        } else {
            $this->aplicarFiltrosPuntosRuta(
                $query,
                $empresaIds,
                $puntoRutaIds,
                $estado
            );
        }

        $puntosRuta = $query
            ->with('empresa')
            ->join(
                'empresas',
                'puntos_ruta.empresa_id',
                '=',
                'empresas.id'
            )
            ->select('puntos_ruta.*')
            ->orderByRaw(
                'COALESCE(empresas.nombre_comercial, empresas.nombre_legal)'
            )
            ->orderBy('puntos_ruta.nombre')
            ->paginate(10)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Resumen filtrado
        |--------------------------------------------------------------------------
        */

        $baseResumen = $this->consultaBasePuntosRuta(
            $modoAdministracion,
            $esUsuarioDieselCop,
            $user->empresa_id
        );

        if (! $hayFiltros || $combinacionEmpresaPuntoInvalida) {
            $baseResumen->whereRaw('1 = 0');
        } else {
            $this->aplicarFiltrosPuntosRuta(
                $baseResumen,
                $empresaIds,
                $puntoRutaIds,
                $estado
            );
        }

        $totalPuntosRuta = (clone $baseResumen)->count();

        $puntosRutaActivos = (clone $baseResumen)
            ->where('puntos_ruta.estado', 'activo')
            ->count();

        $puntosRutaInactivos = (clone $baseResumen)
            ->where('puntos_ruta.estado', 'inactivo')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Selector de empresas
        |--------------------------------------------------------------------------
        */

        if ($esUsuarioDieselCop) {
            $empresasSelector = Empresa::query()
                ->when(
                    $modoAdministracion,
                    function ($query) {
                        $query->where('estado', 'activa');
                    }
                )
                ->orderByRaw(
                    'COALESCE(nombre_comercial, nombre_legal)'
                )
                ->orderBy('nombre_legal')
                ->get();
        } else {
            $empresasSelector = collect([
                $empresaUsuario,
            ])
                ->filter(function ($empresa) use (
                    $modoAdministracion
                ) {
                    if (! $empresa) {
                        return false;
                    }

                    if (
                        $modoAdministracion
                        && $empresa->estado !== 'activa'
                    ) {
                        return false;
                    }

                    return true;
                })
                ->values();
        }

        /*
        |--------------------------------------------------------------------------
        | Selector de puntos de ruta
        |--------------------------------------------------------------------------
        |
        | Se limita a las empresas seleccionadas. Si no hay empresa seleccionada,
        | Diesel Cop puede ver todos los puntos permitidos por la pantalla.
        |
        */

        $puntosRutaSelector = $this->consultaBasePuntosRuta(
            $modoAdministracion,
            $esUsuarioDieselCop,
            $user->empresa_id
        )
            ->with('empresa')
            ->when(
                $empresaIds->isNotEmpty(),
                function ($query) use ($empresaIds) {
                    $query->whereIn(
                        'puntos_ruta.empresa_id',
                        $empresaIds->all()
                    );
                }
            )
            ->join(
                'empresas',
                'puntos_ruta.empresa_id',
                '=',
                'empresas.id'
            )
            ->select('puntos_ruta.*')
            ->orderByRaw(
                'COALESCE(empresas.nombre_comercial, empresas.nombre_legal)'
            )
            ->orderBy('puntos_ruta.nombre')
            ->get();

        return [
            'puntosRuta' => $puntosRuta,
            'puntosRutaSelector' => $puntosRutaSelector,
            'empresasSelector' => $empresasSelector,

            'empresaIds' => $empresaIds->all(),
            'puntoRutaIds' => $puntoRutaIds->all(),

            /*
             * Compatibilidad temporal con vistas anteriores.
             */
            'empresaId' => $empresaIds->first(),
            'puntoRutaId' => $puntoRutaIds->first(),

            'estado' => $estado,

            'hayFiltros' => $hayFiltros,
            'consultaEjecutada' => $consultaEjecutada,

            'totalPuntosRuta' => $totalPuntosRuta,
            'puntosRutaActivos' => $puntosRutaActivos,
            'puntosRutaInactivos' => $puntosRutaInactivos,

            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'empresaUsuario' => $empresaUsuario,
            'modoAdministracion' => $modoAdministracion,
        ];
    }

    /**
     * Consulta base según pantalla y alcance empresarial.
     */
    private function consultaBasePuntosRuta(
        bool $modoAdministracion,
        bool $esUsuarioDieselCop,
        int|string|null $empresaUsuarioId
    ): Builder {
        return PuntoRuta::query()
            ->when(
                $modoAdministracion,
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
            )
            ->when(
                ! $esUsuarioDieselCop,
                function ($query) use ($empresaUsuarioId) {
                    $query->where(
                        'puntos_ruta.empresa_id',
                        $empresaUsuarioId
                    );
                }
            );
    }

    /**
     * Aplicar filtros a resultados y resúmenes.
     */
    private function aplicarFiltrosPuntosRuta(
        Builder $query,
        Collection $empresaIds,
        Collection $puntoRutaIds,
        ?string $estado
    ): void {
        $query
            ->when(
                $empresaIds->isNotEmpty(),
                function ($query) use ($empresaIds) {
                    $query->whereIn(
                        'puntos_ruta.empresa_id',
                        $empresaIds->all()
                    );
                }
            )
            ->when(
                $puntoRutaIds->isNotEmpty(),
                function ($query) use ($puntoRutaIds) {
                    $query->whereIn(
                        'puntos_ruta.id',
                        $puntoRutaIds->all()
                    );
                }
            )
            ->when(
                in_array(
                    $estado,
                    [
                        'activo',
                        'inactivo',
                    ],
                    true
                ),
                function ($query) use ($estado) {
                    $query->where(
                        'puntos_ruta.estado',
                        $estado
                    );
                }
            );
    }

    /**
     * Normalizar parámetros múltiples y simples heredados.
     */
    private function normalizarIdsSeleccionados(
        array $ids,
        int|string|null $idAnterior = null
    ): Collection {
        $normalizados = collect($ids)
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id);

        if (filled($idAnterior)) {
            $normalizados->push((int) $idAnterior);
        }

        return $normalizados
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();
    }

    /**
     * Obtener puntos válidos según empresas, pantalla y usuario.
     */
    private function obtenerPuntosSeleccionadosValidos(
        Collection $puntoRutaIds,
        Collection $empresaIds,
        bool $modoAdministracion,
        bool $esUsuarioDieselCop,
        int|string|null $empresaUsuarioId
    ): Collection {
        if ($puntoRutaIds->isEmpty()) {
            return collect();
        }

        return $this->consultaBasePuntosRuta(
            $modoAdministracion,
            $esUsuarioDieselCop,
            $empresaUsuarioId
        )
            ->whereIn(
                'puntos_ruta.id',
                $puntoRutaIds->all()
            )
            ->when(
                $empresaIds->isNotEmpty(),
                function ($query) use ($empresaIds) {
                    $query->whereIn(
                        'puntos_ruta.empresa_id',
                        $empresaIds->all()
                    );
                }
            )
            ->get();
    }

    /**
     * Formulario de registro interno.
     */
    public function create()
    {
        return view(
            'puntos-ruta.create',
            $this->prepararFormularioPuntoRuta()
        );
    }

    /**
     * Formulario de registro externo.
     */
    public function createVentana()
    {
        return view(
            'puntos-ruta.create-ventana',
            $this->prepararFormularioPuntoRuta()
        );
    }

    /**
     * Preparar creación y edición.
     */
    private function prepararFormularioPuntoRuta(): array
    {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);

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
                'No se puede operar sobre puntos de ruta porque la empresa está inactiva.'
            );
        }

        $empresasSelector = $esUsuarioDieselCop
            ? Empresa::query()
                ->where('estado', 'activa')
                ->orderByRaw(
                    'COALESCE(nombre_comercial, nombre_legal)'
                )
                ->orderBy('nombre_legal')
                ->get()
            : collect([
                $empresaUsuario,
            ])->filter()->values();

        return [
            'empresasSelector' => $empresasSelector,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'empresaUsuario' => $empresaUsuario,
        ];
    }

    /**
     * Registrar punto de ruta.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);

        $rules = [
            'nombre' => [
                'required',
                'string',
                'max:150',
            ],
            'direccion' => [
                'required',
                'string',
                'max:255',
            ],
            'return_to' => [
                'nullable',
                Rule::in([
                    'ventana',
                ]),
            ],
        ];

        if ($esUsuarioDieselCop) {
            $rules['empresa_id'] = [
                'required',
                'integer',
                Rule::exists('empresas', 'id')
                    ->where('estado', 'activa'),
            ];
        } else {
            $rules['empresa_id'] = [
                'nullable',
            ];
        }

        $validated = $request->validate($rules, [
            'empresa_id.required' =>
                'Debe seleccionar una empresa.',
            'empresa_id.integer' =>
                'La empresa seleccionada no es válida.',
            'empresa_id.exists' =>
                'La empresa seleccionada no existe o está inactiva.',

            'nombre.required' =>
                'Debe ingresar el nombre del punto de ruta.',
            'nombre.max' =>
                'El nombre del punto de ruta no debe exceder 150 caracteres.',

            'direccion.required' =>
                'Debe ingresar la dirección del punto de ruta.',
            'direccion.max' =>
                'La dirección del punto de ruta no debe exceder 255 caracteres.',

            'return_to.in' =>
                'El destino de retorno no es válido.',
        ]);

        $empresaId = $esUsuarioDieselCop
            ? (int) $validated['empresa_id']
            : (int) $user->empresa_id;

        $this->validarEmpresaActivaPorId($empresaId);

        $request->validate([
            'nombre' => [
                Rule::unique(
                    'puntos_ruta',
                    'nombre'
                )->where(
                    'empresa_id',
                    $empresaId
                ),
            ],
        ], [
            'nombre.unique' =>
                'Ya existe un punto de ruta con ese nombre para la empresa seleccionada.',
        ]);

        PuntoRuta::create([
            'empresa_id' => $empresaId,
            'nombre' => trim($validated['nombre']),
            'direccion' => trim($validated['direccion']),
            'estado' => 'activo',
            'fecha_creacion' => now(),
            'creado_por' => Auth::id(),
        ]);

        $ruta = $request->input('return_to') === 'ventana'
            ? 'puntos-ruta.create.ventana'
            : 'puntos-ruta.create';

        return redirect()
            ->route($ruta)
            ->with(
                'success',
                'Punto de ruta guardado correctamente.'
            );
    }

    /**
     * Ficha interna.
     */
    public function show(
        Request $request,
        PuntoRuta $puntoRuta
    ) {
        $this->autorizarAccesoPuntoRuta($puntoRuta);
        $this->validarEmpresaActivaPuntoRuta($puntoRuta);

        return view(
            'puntos-ruta.show',
            $this->prepararFichaPuntoRuta(
                $request,
                $puntoRuta
            )
        );
    }

    /**
     * Ficha externa.
     */
    public function showVentana(
        Request $request,
        PuntoRuta $puntoRuta
    ) {
        $this->autorizarAccesoPuntoRuta($puntoRuta);
        $this->validarEmpresaActivaPuntoRuta($puntoRuta);

        return view(
            'puntos-ruta.show-ventana',
            $this->prepararFichaPuntoRuta(
                $request,
                $puntoRuta
            )
        );
    }

    /**
     * Preparar ficha.
     */
    private function prepararFichaPuntoRuta(
        Request $request,
        PuntoRuta $puntoRuta
    ): array {
        $puntoRuta->load([
            'empresa',
            'creadoPor',
            'actualizadoPor',
            'inactivadoPor',
        ]);

        $cantidadRutasActivas = $this
            ->consultaRutasActivasDelPunto($puntoRuta)
            ->count();

        return [
            'puntoRuta' => $puntoRuta,
            'cantidadRutasActivas' => $cantidadRutasActivas,
            'tieneRutasActivas' => $cantidadRutasActivas > 0,
            'parametrosRetorno' =>
                $this->obtenerParametrosRetornoDesdeConsulta(
                    $request->query()
                ),
        ];
    }

    /**
     * Editar internamente.
     */
    public function edit(
        Request $request,
        PuntoRuta $puntoRuta
    ) {
        $this->autorizarAccesoPuntoRuta($puntoRuta);
        $this->validarEmpresaActivaPuntoRuta($puntoRuta);
        $this->validarPuntoRutaActivoParaEdicion($puntoRuta);

        $data = $this->prepararFormularioPuntoRuta();

        $data['puntoRuta'] = $puntoRuta;

        $data['parametrosRetorno'] =
            $this->obtenerParametrosRetornoDesdeConsulta(
                $request->query()
            );

        return view(
            'puntos-ruta.edit',
            $data
        );
    }

    /**
     * Editar en ventana externa.
     */
    public function editVentana(
        Request $request,
        PuntoRuta $puntoRuta
    ) {
        $this->autorizarAccesoPuntoRuta($puntoRuta);
        $this->validarEmpresaActivaPuntoRuta($puntoRuta);
        $this->validarPuntoRutaActivoParaEdicion($puntoRuta);

        $data = $this->prepararFormularioPuntoRuta();

        $data['puntoRuta'] = $puntoRuta;

        $data['parametrosRetorno'] =
            $this->obtenerParametrosRetornoDesdeConsulta(
                $request->query()
            );

        return view(
            'puntos-ruta.edit-ventana',
            $data
        );
    }

    /**
     * Actualizar punto activo.
     */
    public function update(
        Request $request,
        PuntoRuta $puntoRuta
    ) {
        $this->autorizarAccesoPuntoRuta($puntoRuta);
        $this->validarEmpresaActivaPuntoRuta($puntoRuta);
        $this->validarPuntoRutaActivoParaEdicion($puntoRuta);

        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:150',
                Rule::unique(
                    'puntos_ruta',
                    'nombre'
                )
                    ->where(
                        'empresa_id',
                        $puntoRuta->empresa_id
                    )
                    ->ignore($puntoRuta->id),
            ],
            'direccion' => [
                'required',
                'string',
                'max:255',
            ],
            'return_to' => [
                'nullable',
                Rule::in([
                    'ventana',
                ]),
            ],
            'return_query' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ], [
            'nombre.required' =>
                'Debe ingresar el nombre del punto de ruta.',
            'nombre.max' =>
                'El nombre del punto de ruta no debe exceder 150 caracteres.',
            'nombre.unique' =>
                'Ya existe un punto de ruta con ese nombre para la empresa actual.',

            'direccion.required' =>
                'Debe ingresar la dirección del punto de ruta.',
            'direccion.max' =>
                'La dirección del punto de ruta no debe exceder 255 caracteres.',

            'return_to.in' =>
                'El destino de retorno no es válido.',
            'return_query.max' =>
                'Los parámetros de retorno no son válidos.',
        ]);

        $puntoRuta->update([
            'nombre' => trim($validated['nombre']),
            'direccion' => trim($validated['direccion']),
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        return $this->redirigirFichaPuntoRuta(
            $request,
            $puntoRuta,
            'Punto de ruta actualizado correctamente.'
        );
    }

    /**
     * Inactivar punto activo.
     */
    public function inactivar(
        Request $request,
        PuntoRuta $puntoRuta
    ) {
        $this->autorizarAccesoPuntoRuta($puntoRuta);
        $this->validarEmpresaActivaPuntoRuta($puntoRuta);

        if ($puntoRuta->estado !== 'activo') {
            return back()->withErrors([
                'punto_ruta' =>
                    'El punto de ruta ya está inactivo.',
            ]);
        }

        $validated = $request->validate([
            'motivo_inactivacion' => [
                'required',
                'string',
                'max:255',
                Rule::in([
                    'No continúa en uso',
                    'Cambio operativo',
                    'Datos incorrectos en registro',
                    'Solicitud del cliente',
                    'Suspensión administrativa',
                    'Otro',
                ]),
            ],
            'return_to' => [
                'nullable',
                Rule::in([
                    'ventana',
                ]),
            ],
            'return_query' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ], [
            'motivo_inactivacion.required' =>
                'Debe seleccionar el motivo de inactivación.',
            'motivo_inactivacion.in' =>
                'El motivo de inactivación seleccionado no es válido.',
            'motivo_inactivacion.max' =>
                'El motivo de inactivación no debe exceder 255 caracteres.',

            'return_to.in' =>
                'El destino de retorno no es válido.',
            'return_query.max' =>
                'Los parámetros de retorno no son válidos.',
        ]);

        $cantidadRutasActivas = $this
            ->consultaRutasActivasDelPunto($puntoRuta)
            ->count();

        if ($cantidadRutasActivas > 0) {
            return back()
                ->withErrors([
                    'punto_ruta' =>
                        $cantidadRutasActivas === 1
                            ? 'No se puede inactivar este punto de ruta porque forma parte de una ruta activa. Primero debe inactivar la ruta relacionada.'
                            : "No se puede inactivar este punto de ruta porque forma parte de {$cantidadRutasActivas} rutas activas. Primero debe inactivar las rutas relacionadas.",
                ])
                ->withInput();
        }

        $fechaActualizacion = now();
        $usuarioId = Auth::id();

        $puntoRuta->update([
            'estado' => 'inactivo',
            'fecha_inactivacion' => $fechaActualizacion,
            'inactivado_por' => $usuarioId,
            'motivo_inactivacion' =>
                $validated['motivo_inactivacion'],
            'fecha_actualizacion' => $fechaActualizacion,
            'actualizado_por' => $usuarioId,
        ]);

        return $this->redirigirFichaPuntoRuta(
            $request,
            $puntoRuta,
            'Punto de ruta inactivado correctamente.'
        );
    }

    /**
     * Reactivar punto inactivo.
     */
    public function reactivar(
        Request $request,
        PuntoRuta $puntoRuta
    ) {
        $this->autorizarAccesoPuntoRuta($puntoRuta);
        $this->validarEmpresaActivaPuntoRuta($puntoRuta);

        $request->validate([
            'return_to' => [
                'nullable',
                Rule::in([
                    'ventana',
                ]),
            ],
            'return_query' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ], [
            'return_to.in' =>
                'El destino de retorno no es válido.',
            'return_query.max' =>
                'Los parámetros de retorno no son válidos.',
        ]);

        if ($puntoRuta->estado !== 'inactivo') {
            return back()->withErrors([
                'punto_ruta' =>
                    'El punto de ruta ya está activo.',
            ]);
        }

        $puntoRuta->update([
            'estado' => 'activo',
            'fecha_inactivacion' => null,
            'inactivado_por' => null,
            'motivo_inactivacion' => null,
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        return $this->redirigirFichaPuntoRuta(
            $request,
            $puntoRuta,
            'Punto de ruta reactivado correctamente.'
        );
    }

    /**
     * Consultar rutas activas relacionadas.
     */
    private function consultaRutasActivasDelPunto(
        PuntoRuta $puntoRuta
    ) {
        return DB::table('rutas')
            ->where(
                'empresa_id',
                $puntoRuta->empresa_id
            )
            ->where('estado', 'activo')
            ->where(function ($query) use ($puntoRuta) {
                $query
                    ->where(
                        'punto_origen_id',
                        $puntoRuta->id
                    )
                    ->orWhere(
                        'punto_destino_id',
                        $puntoRuta->id
                    );
            });
    }

    /**
     * Redirigir conservando los filtros originales.
     */
    private function redirigirFichaPuntoRuta(
        Request $request,
        PuntoRuta $puntoRuta,
        string $mensaje
    ) {
        $parametrosRetorno =
            $this->obtenerParametrosRetornoDesdeCadena(
                (string) $request->input(
                    'return_query',
                    ''
                )
            );

        $parametrosRuta = array_merge(
            [
                'puntoRuta' => $puntoRuta,
            ],
            $parametrosRetorno
        );

        $ruta = $request->input('return_to') === 'ventana'
            ? 'puntos-ruta.show.ventana'
            : 'puntos-ruta.show';

        return redirect()
            ->route(
                $ruta,
                $parametrosRuta
            )
            ->with(
                'success',
                $mensaje
            );
    }

    /**
     * Reconstruir filtros desde cadena.
     */
    private function obtenerParametrosRetornoDesdeCadena(
        string $returnQuery
    ): array {
        $returnQuery = trim($returnQuery);

        if ($returnQuery === '') {
            return [];
        }

        parse_str(
            $returnQuery,
            $parametros
        );

        return $this->obtenerParametrosRetornoDesdeConsulta(
            $parametros
        );
    }

    /**
     * Aceptar únicamente filtros conocidos.
     */
    private function obtenerParametrosRetornoDesdeConsulta(
        array $parametros
    ): array {
        $retorno = [];

        if (
            isset($parametros['consultar'])
            && in_array(
                (string) $parametros['consultar'],
                [
                    '1',
                    'true',
                    'on',
                ],
                true
            )
        ) {
            $retorno['consultar'] = 1;
        }

        foreach (
            [
                'empresa_ids',
                'punto_ruta_ids',
            ]
            as $campo
        ) {
            $valores = $parametros[$campo] ?? [];

            if (! is_array($valores)) {
                $valores = [$valores];
            }

            $ids = collect($valores)
                ->filter(
                    fn ($id) =>
                        filter_var(
                            $id,
                            FILTER_VALIDATE_INT
                        ) !== false
                )
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            if ($ids !== []) {
                $retorno[$campo] = $ids;
            }
        }

        /*
         * Compatibilidad con enlaces simples anteriores.
         */
        foreach (
            [
                'empresa_id',
                'punto_ruta_id',
                'page',
            ]
            as $campo
        ) {
            $valor = $parametros[$campo] ?? null;

            if (
                is_array($valor)
                || filter_var(
                    $valor,
                    FILTER_VALIDATE_INT
                ) === false
            ) {
                continue;
            }

            $valor = (int) $valor;

            if ($valor > 0) {
                $retorno[$campo] = $valor;
            }
        }

        if (
            isset($parametros['estado'])
            && ! is_array($parametros['estado'])
            && in_array(
                $parametros['estado'],
                [
                    'activo',
                    'inactivo',
                ],
                true
            )
        ) {
            $retorno['estado'] = $parametros['estado'];
        }

        return $retorno;
    }

    /**
     * Restringir usuarios empresariales a su empresa.
     */
    private function autorizarAccesoPuntoRuta(
        PuntoRuta $puntoRuta
    ): void {
        $user = Auth::user();

        if (
            ! is_null($user->empresa_id)
            && (int) $user->empresa_id
                !== (int) $puntoRuta->empresa_id
        ) {
            abort(
                403,
                'No tiene autorización para acceder a este punto de ruta.'
            );
        }
    }

    /**
     * Exigir empresa activa para operar administrativamente.
     */
    private function validarEmpresaActivaPuntoRuta(
        PuntoRuta $puntoRuta
    ): void {
        $puntoRuta->loadMissing('empresa');

        if (
            ! $puntoRuta->empresa
            || $puntoRuta->empresa->estado !== 'activa'
        ) {
            abort(
                403,
                'No se puede operar sobre este punto de ruta porque la empresa está inactiva.'
            );
        }
    }

    /**
     * Exigir punto activo para editar.
     */
    private function validarPuntoRutaActivoParaEdicion(
        PuntoRuta $puntoRuta
    ): void {
        if ($puntoRuta->estado !== 'activo') {
            abort(
                403,
                'No se puede editar un punto de ruta inactivo. Debe reactivarlo desde su ficha.'
            );
        }
    }

    /**
     * Verificar empresa activa antes de crear.
     */
    private function validarEmpresaActivaPorId(
        int $empresaId
    ): void {
        $empresaActiva = Empresa::query()
            ->whereKey($empresaId)
            ->where('estado', 'activa')
            ->exists();

        if (! $empresaActiva) {
            abort(
                403,
                'No se puede operar sobre puntos de ruta porque la empresa está inactiva.'
            );
        }
    }
}