<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Gasolinera;
use App\Models\MovimientoInventarioCombustible;
use App\Models\Tanque;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GasolineraController extends Controller
{
    /**
     * Display the informational gas station consultation panel.
     */
    public function index(Request $request)
    {
        $data = $this->prepararConsultaGasolineras($request, false);

        return view('gasolineras.index', $data);
    }

    /**
     * Display the standalone informational gas station consultation panel.
     */
    public function consultaVentana(Request $request)
    {
        $data = $this->prepararConsultaGasolineras($request, false);

        return view('gasolineras.index-ventana', $data);
    }

    /**
     * Display the administrative gas station search panel.
     */
    public function administrar(Request $request)
    {
        $data = $this->prepararConsultaGasolineras($request, true);

        return view('gasolineras.administrar', $data);
    }

    /**
     * Display the standalone administrative gas station search panel.
     */
    public function administrarVentana(Request $request)
    {
        $data = $this->prepararConsultaGasolineras($request, true);

        return view('gasolineras.administrar-ventana', $data);
    }

    /**
     * Prepare gas station query data for normal and standalone screens.
     */
    private function prepararConsultaGasolineras(
        Request $request,
        bool $soloEmpresasActivas
    ): array {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);

        $empresaUsuario = $esUsuarioDieselCop
            ? null
            : Empresa::find($user->empresa_id);

        $validated = $request->validate([
            'busqueda_empresa' => [
                'nullable',
                'string',
                'max:150',
            ],

            'busqueda_gasolinera' => [
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
                'exists:empresas,id',
            ],

            'gasolinera_ids' => [
                'nullable',
                'array',
            ],

            'gasolinera_ids.*' => [
                'nullable',
                'integer',
                'exists:gasolineras,id',
            ],

            /*
             * Compatibilidad temporal con filtros anteriores.
             */
            'empresa_id' => [
                'nullable',
                'integer',
                'exists:empresas,id',
            ],

            'nombre' => [
                'nullable',
                'string',
                'max:150',
            ],

            'estado' => [
                'nullable',
                'in:activa,inactiva',
            ],
        ], [
            'empresa_ids.*.exists' => 'Una de las empresas seleccionadas no es válida.',
            'gasolinera_ids.*.exists' => 'Una de las gasolineras seleccionadas no es válida.',
            'empresa_id.exists' => 'La empresa seleccionada no es válida.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ]);

        $busquedaEmpresa = trim(
            (string) ($validated['busqueda_empresa'] ?? '')
        );

        $busquedaGasolinera = trim(
            (string) ($validated['busqueda_gasolinera'] ?? '')
        );

        $empresaIds = collect($validated['empresa_ids'] ?? [])
            ->when(
                filled($validated['empresa_id'] ?? null),
                function ($collection) use ($validated) {
                    return $collection->push(
                        $validated['empresa_id']
                    );
                }
            )
            ->filter(
                fn ($id) => filled($id)
            )
            ->map(
                fn ($id) => (int) $id
            )
            ->unique()
            ->values()
            ->all();

        $gasolineraIds = collect(
            $validated['gasolinera_ids'] ?? []
        )
            ->filter(
                fn ($id) => filled($id)
            )
            ->map(
                fn ($id) => (int) $id
            )
            ->unique()
            ->values()
            ->all();

        $nombre = trim(
            (string) ($validated['nombre'] ?? '')
        );

        $estado = $validated['estado'] ?? null;

        if (
            $busquedaGasolinera === ''
            && $nombre !== ''
        ) {
            $busquedaGasolinera = $nombre;
        }

        if (! $esUsuarioDieselCop) {
            $empresaIds = [
                (int) $user->empresa_id,
            ];
        }

        $empresaId = $empresaIds[0] ?? null;
        $gasolineraId = $gasolineraIds[0] ?? null;

        $consultaEjecutada = $request->boolean('consultar');

        /*
         * La empresa obligatoria del usuario empresarial limita el alcance,
         * pero no ejecuta automáticamente Consulta ni Administrar.
         */
        $hayFiltros =
            $consultaEjecutada
            || filled($busquedaEmpresa)
            || filled($busquedaGasolinera)
            || count($gasolineraIds) > 0
            || filled($estado)
            || (
                $esUsuarioDieselCop
                && count($empresaIds) > 0
            );

        $empresasSelector = Empresa::query()
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

        $baseGasolinerasQuery = Gasolinera::query()
            ->with([
                'empresa',

                'tanques' => function ($query) {
                    $query->orderBy('nombre');
                },
            ])
            ->withCount([
                'tanques',

                'tanques as tanques_activos_count' => function ($query) {
                    $query->where(
                        'estado',
                        'activo'
                    );
                },
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

        $gasolinerasSelector = (
            clone $baseGasolinerasQuery
        )
            ->when(
                filled($busquedaEmpresa),
                function ($query) use ($busquedaEmpresa) {
                    $query->whereHas(
                        'empresa',
                        function ($empresaQuery) use ($busquedaEmpresa) {
                            $empresaQuery
                                ->where(
                                    'nombre_legal',
                                    'like',
                                    '%' . $busquedaEmpresa . '%'
                                )
                                ->orWhere(
                                    'nombre_comercial',
                                    'like',
                                    '%' . $busquedaEmpresa . '%'
                                );
                        }
                    );
                }
            )
            ->when(
                count($empresaIds) > 0,
                function ($query) use ($empresaIds) {
                    $query->whereIn(
                        'empresa_id',
                        $empresaIds
                    );
                }
            )
            ->orderBy('nombre')
            ->get();

        $gasolineras = (
            clone $baseGasolinerasQuery
        )
            ->when(
                $hayFiltros
                && filled($busquedaEmpresa),
                function ($query) use ($busquedaEmpresa) {
                    $query->whereHas(
                        'empresa',
                        function ($empresaQuery) use ($busquedaEmpresa) {
                            $empresaQuery
                                ->where(
                                    'nombre_legal',
                                    'like',
                                    '%' . $busquedaEmpresa . '%'
                                )
                                ->orWhere(
                                    'nombre_comercial',
                                    'like',
                                    '%' . $busquedaEmpresa . '%'
                                );
                        }
                    );
                }
            )
            ->when(
                $hayFiltros
                && count($empresaIds) > 0,
                function ($query) use ($empresaIds) {
                    $query->whereIn(
                        'empresa_id',
                        $empresaIds
                    );
                }
            )
            ->when(
                $hayFiltros
                && filled($busquedaGasolinera),
                function ($query) use ($busquedaGasolinera) {
                    $query->where(
                        'nombre',
                        'like',
                        '%' . $busquedaGasolinera . '%'
                    );
                }
            )
            ->when(
                $hayFiltros
                && count($gasolineraIds) > 0,
                function ($query) use ($gasolineraIds) {
                    $query->whereIn(
                        'id',
                        $gasolineraIds
                    );
                }
            )
            ->when(
                $hayFiltros
                && in_array(
                    $estado,
                    ['activa', 'inactiva'],
                    true
                ),
                function ($query) use ($estado) {
                    $query->where(
                        'estado',
                        $estado
                    );
                }
            )
            ->when(
                ! $hayFiltros,
                function ($query) {
                    $query->whereRaw('1 = 0');
                }
            )
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        $baseResumen = Gasolinera::query()
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

        $totalGasolineras = (
            clone $baseResumen
        )->count();

        $gasolinerasActivas = (
            clone $baseResumen
        )
            ->where(
                'estado',
                'activa'
            )
            ->count();

        $gasolinerasInactivas = (
            clone $baseResumen
        )
            ->where(
                'estado',
                'inactiva'
            )
            ->count();

        return [
            'gasolineras' => $gasolineras,
            'gasolinerasSelector' => $gasolinerasSelector,
            'empresasSelector' => $empresasSelector,

            'busquedaEmpresa' => $busquedaEmpresa,
            'busquedaGasolinera' => $busquedaGasolinera,

            'empresaIds' => $empresaIds,
            'gasolineraIds' => $gasolineraIds,

            'empresaId' => $empresaId,
            'gasolineraId' => $gasolineraId,
            'nombre' => $busquedaGasolinera,
            'estado' => $estado,

            'hayFiltros' => $hayFiltros,
            'consultaEjecutada' => $consultaEjecutada,

            'totalGasolineras' => $totalGasolineras,
            'gasolinerasActivas' => $gasolinerasActivas,
            'gasolinerasInactivas' => $gasolinerasInactivas,

            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'empresaUsuario' => $empresaUsuario,
        ];
    }

    /**
     * Show the form for creating a new gas station.
     */
    public function create()
    {
        $data = $this->prepararFormularioGasolinera();

        return view(
            'gasolineras.create',
            $data
        );
    }

    /**
     * Show the standalone form for creating a new gas station.
     */
    public function createVentana()
    {
        $data = $this->prepararFormularioGasolinera();

        return view(
            'gasolineras.create-ventana',
            $data
        );
    }

    /**
     * Prepare common data for create/edit gas station forms.
     */
    private function prepararFormularioGasolinera(): array
    {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null(
            $user->empresa_id
        );

        $empresaUsuario = $esUsuarioDieselCop
            ? null
            : Empresa::find(
                $user->empresa_id
            );

        if (
            ! $esUsuarioDieselCop
            && (
                ! $empresaUsuario
                || $empresaUsuario->estado !== 'activa'
            )
        ) {
            abort(
                403,
                'No se puede operar sobre gasolineras porque la empresa está inactiva.'
            );
        }

        $empresasSelector = $esUsuarioDieselCop
            ? Empresa::query()
                ->where(
                    'estado',
                    'activa'
                )
                ->orderBy('nombre_comercial')
                ->orderBy('nombre_legal')
                ->get()
            : collect([
                $empresaUsuario,
            ])->filter();

        return [
            'empresasSelector' => $empresasSelector,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'empresaUsuario' => $empresaUsuario,
        ];
    }

    /**
     * Store a newly created gas station with at least one initial tank.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null(
            $user->empresa_id
        );

        $baseRules = [
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

            'encargado' => [
                'nullable',
                'string',
                'max:150',
            ],

            'telefono' => [
                'nullable',
                'string',
                'max:9',
                'regex:/^\d{4}-\d{4}$/',
            ],

            'correo' => [
                'nullable',
                'email',
                'max:150',
            ],

            'tanques' => [
                'required',
                'array',
                'min:1',
            ],

            'tanques.*.nombre' => [
                'required',
                'string',
                'max:100',
                'distinct',
            ],

            'tanques.*.capacidad_total' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'tanques.*.volumen_actual' => [
                'required',
                'numeric',
                'gte:0',
            ],

            'tanques.*.volumen_minimo_alerta' => [
                'required',
                'numeric',
                'gte:0',
            ],
        ];

        if ($esUsuarioDieselCop) {
            $baseRules['empresa_id'] = [
                'required',
                'integer',

                Rule::exists(
                    'empresas',
                    'id'
                )->where(
                    'estado',
                    'activa'
                ),
            ];
        } else {
            $baseRules['empresa_id'] = [
                'nullable',
            ];
        }

        $validated = $request->validate(
            $baseRules,
            [
                'empresa_id.required' => 'Debe seleccionar una empresa.',
                'empresa_id.exists' => 'La empresa seleccionada no es válida o no está activa.',

                'nombre.required' => 'Debe ingresar el nombre de la gasolinera.',
                'direccion.required' => 'Debe ingresar la dirección de la gasolinera.',

                'telefono.regex' => 'El teléfono debe tener el formato 0000-0000.',
                'correo.email' => 'Debe ingresar un correo válido.',

                'tanques.required' => 'Debe registrar al menos un tanque para crear la gasolinera.',
                'tanques.array' => 'Debe registrar al menos un tanque válido.',
                'tanques.min' => 'Debe registrar al menos un tanque para crear la gasolinera.',

                'tanques.*.nombre.required' => 'Debe ingresar el nombre de cada tanque.',
                'tanques.*.nombre.distinct' => 'No puede repetir el nombre de un tanque dentro de la misma gasolinera.',

                'tanques.*.capacidad_total.required' => 'Debe ingresar la capacidad total de cada tanque.',
                'tanques.*.capacidad_total.gt' => 'La capacidad total del tanque debe ser mayor que cero.',

                'tanques.*.volumen_actual.required' => 'Debe ingresar el volumen actual de cada tanque.',
                'tanques.*.volumen_actual.gte' => 'El volumen actual no puede ser negativo.',

                'tanques.*.volumen_minimo_alerta.required' => 'Debe ingresar el volumen mínimo de alerta de cada tanque.',
                'tanques.*.volumen_minimo_alerta.gte' => 'El volumen mínimo de alerta no puede ser negativo.',
            ]
        );

        $empresaId = $esUsuarioDieselCop
            ? (int) $validated['empresa_id']
            : (int) $user->empresa_id;

        $this->validarEmpresaActivaPorId(
            $empresaId
        );

        $request->validate([
            'nombre' => [
                Rule::unique(
                    'gasolineras',
                    'nombre'
                )->where(
                    'empresa_id',
                    $empresaId
                ),
            ],
        ], [
            'nombre.unique' => 'Ya existe una gasolinera con ese nombre para la empresa seleccionada.',
        ]);

        foreach (
            $validated['tanques'] as $index => $tanqueData
        ) {
            $capacidadTotal = (float) $tanqueData['capacidad_total'];
            $volumenActual = (float) $tanqueData['volumen_actual'];
            $volumenMinimoAlerta = (float) $tanqueData['volumen_minimo_alerta'];

            if (
                $volumenActual > $capacidadTotal
            ) {
                return back()
                    ->withErrors([
                        "tanques.$index.volumen_actual" => 'El volumen actual no puede superar la capacidad total del tanque.',
                    ])
                    ->withInput();
            }

            if (
                $volumenMinimoAlerta >= $capacidadTotal
            ) {
                return back()
                    ->withErrors([
                        "tanques.$index.volumen_minimo_alerta" => 'El volumen mínimo de alerta debe ser menor que la capacidad total del tanque.',
                    ])
                    ->withInput();
            }
        }

        $gasolinera = DB::transaction(
            function () use (
                $validated,
                $empresaId
            ) {
                $gasolinera = Gasolinera::create([
                    'empresa_id' => $empresaId,
                    'nombre' => $validated['nombre'],
                    'direccion' => $validated['direccion'],
                    'encargado' => $validated['encargado'] ?? null,
                    'telefono' => $validated['telefono'] ?? null,
                    'correo' => $validated['correo'] ?? null,
                    'estado' => 'activa',
                    'fecha_creacion' => now(),
                    'creado_por' => Auth::id(),
                ]);

                foreach (
                    $validated['tanques'] as $tanqueData
                ) {
                    $volumenActual = (float) $tanqueData['volumen_actual'];

                    $tanque = Tanque::create([
                        'gasolinera_id' => $gasolinera->id,
                        'nombre' => $tanqueData['nombre'],
                        'capacidad_total' => $tanqueData['capacidad_total'],
                        'volumen_actual' => $tanqueData['volumen_actual'],
                        'volumen_minimo_alerta' => $tanqueData['volumen_minimo_alerta'],
                        'estado' => 'activo',
                        'fecha_creacion' => now(),
                        'creado_por' => Auth::id(),
                    ]);

                    MovimientoInventarioCombustible::create([
                        'empresa_id' => $empresaId,
                        'tanque_id' => $tanque->id,
                        'abastecimiento_id' => null,
                        'tipo_movimiento' => 'carga_inicial',
                        'volumen_anterior' => 0,
                        'sentido_movimiento' => 'entrada',
                        'volumen_movimiento' => $volumenActual,
                        'volumen_resultante' => $volumenActual,
                        'fecha_hora_movimiento' => now(),
                        'observaciones' => 'Carga inicial registrada al crear el tanque.',
                        'usuario_registra_id' => Auth::id(),
                        'estado' => 'registrado',
                        'fecha_creacion' => now(),
                    ]);
                }

                return $gasolinera;
            }
        );

        $filtrosRetorno = $this->obtenerFiltrosRetorno(
            $request
        );

        if (
            $request->input('return_to') === 'ventana'
        ) {
            return redirect()
                ->route(
                    'gasolineras.show.ventana',
                    array_merge(
                        [
                            'gasolinera' => $gasolinera->id,
                        ],
                        $filtrosRetorno
                    )
                )
                ->with(
                    'success',
                    'Gasolinera guardada correctamente.'
                );
        }

        return redirect()
            ->route(
                'gasolineras.show',
                array_merge(
                    [
                        'gasolinera' => $gasolinera->id,
                    ],
                    $filtrosRetorno
                )
            )
            ->with(
                'success',
                'Gasolinera guardada correctamente.'
            );
    }

    /**
     * Display the specified gas station.
     */
    public function show(Gasolinera $gasolinera)
    {
        $this->autorizarAccesoGasolinera(
            $gasolinera
        );

        $this->validarEmpresaActivaGasolinera(
            $gasolinera
        );

        $gasolinera->load([
            'empresa',

            'tanques' => function ($query) {
                $query->orderBy('nombre');
            },

            'tanques.movimientosInventario' => function ($query) {
                $query
                    ->where(
                        'estado',
                        'registrado'
                    )
                    ->latest(
                        'fecha_hora_movimiento'
                    )
                    ->limit(5);
            },
        ]);

        return view(
            'gasolineras.show',
            $this->prepararFichaGasolinera(
                $gasolinera
            )
        );
    }

    /**
     * Display the specified gas station in standalone window.
     */
    public function showVentana(Gasolinera $gasolinera)
    {
        $this->autorizarAccesoGasolinera(
            $gasolinera
        );

        $this->validarEmpresaActivaGasolinera(
            $gasolinera
        );

        $gasolinera->load([
            'empresa',

            'tanques' => function ($query) {
                $query->orderBy('nombre');
            },

            'tanques.movimientosInventario' => function ($query) {
                $query
                    ->where(
                        'estado',
                        'registrado'
                    )
                    ->latest(
                        'fecha_hora_movimiento'
                    )
                    ->limit(5);
            },
        ]);

        return view(
            'gasolineras.show-ventana',
            $this->prepararFichaGasolinera(
                $gasolinera
            )
        );
    }

    /**
     * Prepare summary data for gas station detail screens.
     */
    private function prepararFichaGasolinera(
        Gasolinera $gasolinera
    ): array {
        $tanques = $gasolinera->tanques;

        $capacidadTotal = $tanques->sum(
            fn ($tanque) => (float) $tanque->capacidad_total
        );

        $volumenActual = $tanques->sum(
            fn ($tanque) => (float) $tanque->volumen_actual
        );

        $volumenMinimoAlerta = $tanques->sum(
            fn ($tanque) => (float) $tanque->volumen_minimo_alerta
        );

        $porcentajeDisponible = $capacidadTotal > 0
            ? round(
                (
                    $volumenActual
                    / $capacidadTotal
                ) * 100,
                2
            )
            : 0;

        $tanquesBajoAlerta = $tanques
            ->filter(
                fn ($tanque) => $tanque->estaBajoAlerta()
            )
            ->count();

        return [
            'gasolinera' => $gasolinera,
            'tanques' => $tanques,
            'capacidadTotal' => $capacidadTotal,
            'volumenActual' => $volumenActual,
            'volumenMinimoAlerta' => $volumenMinimoAlerta,
            'porcentajeDisponible' => $porcentajeDisponible,
            'tanquesBajoAlerta' => $tanquesBajoAlerta,
        ];
    }

    /**
     * Show the form for editing the specified gas station.
     */
    public function edit(Gasolinera $gasolinera)
    {
        $this->autorizarAccesoGasolinera(
            $gasolinera
        );

        $this->validarEmpresaActivaGasolinera(
            $gasolinera
        );

        $this->validarGasolineraActivaParaEdicion(
            $gasolinera
        );

        $data = $this->prepararFormularioGasolinera();
        $data['gasolinera'] = $gasolinera;

        return view(
            'gasolineras.edit',
            $data
        );
    }

    /**
     * Show the standalone form for editing the specified gas station.
     */
    public function editVentana(Gasolinera $gasolinera)
    {
        $this->autorizarAccesoGasolinera(
            $gasolinera
        );

        $this->validarEmpresaActivaGasolinera(
            $gasolinera
        );

        $this->validarGasolineraActivaParaEdicion(
            $gasolinera
        );

        $data = $this->prepararFormularioGasolinera();
        $data['gasolinera'] = $gasolinera;

        return view(
            'gasolineras.edit-ventana',
            $data
        );
    }

    /**
     * Update the specified gas station general data.
     */
    public function update(
        Request $request,
        Gasolinera $gasolinera
    ) {
        $this->autorizarAccesoGasolinera(
            $gasolinera
        );

        $this->validarEmpresaActivaGasolinera(
            $gasolinera
        );

        $this->validarGasolineraActivaParaEdicion(
            $gasolinera
        );

        $validated = $request->validate([
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

            'encargado' => [
                'nullable',
                'string',
                'max:150',
            ],

            'telefono' => [
                'nullable',
                'string',
                'max:9',
                'regex:/^\d{4}-\d{4}$/',
            ],

            'correo' => [
                'nullable',
                'email',
                'max:150',
            ],
        ], [
            'nombre.required' => 'Debe ingresar el nombre de la gasolinera.',
            'direccion.required' => 'Debe ingresar la dirección de la gasolinera.',
            'telefono.regex' => 'El teléfono debe tener el formato 0000-0000.',
            'correo.email' => 'Debe ingresar un correo válido.',
        ]);

        $empresaId = (int) $gasolinera->empresa_id;

        $request->validate([
            'nombre' => [
                Rule::unique(
                    'gasolineras',
                    'nombre'
                )
                    ->where(
                        'empresa_id',
                        $empresaId
                    )
                    ->ignore(
                        $gasolinera->id
                    ),
            ],
        ], [
            'nombre.unique' => 'Ya existe una gasolinera con ese nombre para la empresa actual.',
        ]);

        $gasolinera->update([
            'nombre' => $validated['nombre'],
            'direccion' => $validated['direccion'],
            'encargado' => $validated['encargado'] ?? null,
            'telefono' => $validated['telefono'] ?? null,
            'correo' => $validated['correo'] ?? null,
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        return $this->redirigirAFichaGasolinera(
            $request,
            $gasolinera,
            'Gasolinera actualizada correctamente.'
        );
    }

    /**
     * Deactivate a gas station without changing the individual state of its tanks.
     */
    public function inactivar(
        Request $request,
        Gasolinera $gasolinera
    ) {
        $this->autorizarAccesoGasolinera(
            $gasolinera
        );

        $this->validarEmpresaActivaGasolinera(
            $gasolinera
        );

        if (
            $gasolinera->estado !== 'activa'
        ) {
            return back()->withErrors([
                'gasolinera' => 'La gasolinera ya se encuentra inactiva.',
            ]);
        }

        $validated = $request->validate([
            'motivo_inactivacion' => [
                'required',
                'string',
                'max:255',
                'in:Mantenimiento operativo,Cierre de gasolinera,No continúa en operación,Datos incorrectos en registro,Suspensión administrativa,Solicitud del cliente,Otro',
            ],
        ], [
            'motivo_inactivacion.required' => 'Debe seleccionar el motivo de inactivación.',
            'motivo_inactivacion.in' => 'El motivo de inactivación seleccionado no es válido.',
            'motivo_inactivacion.max' => 'El motivo de inactivación no debe exceder 255 caracteres.',
        ]);

        $gasolinera->update([
            'estado' => 'inactiva',
            'fecha_inactivacion' => now(),
            'inactivado_por' => Auth::id(),
            'motivo_inactivacion' => $validated['motivo_inactivacion'],
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        return $this->redirigirAFichaGasolinera(
            $request,
            $gasolinera,
            'Gasolinera inactivada correctamente. Sus tanques conservaron su estado individual.'
        );
    }

    /**
     * Reactivate a gas station without changing the individual state of its tanks.
     */
    public function reactivar(
        Request $request,
        Gasolinera $gasolinera
    ) {
        $this->autorizarAccesoGasolinera(
            $gasolinera
        );

        $this->validarEmpresaActivaGasolinera(
            $gasolinera
        );

        if (
            $gasolinera->estado === 'activa'
        ) {
            return back()->withErrors([
                'gasolinera' => 'La gasolinera ya se encuentra activa.',
            ]);
        }

        $gasolinera->update([
            'estado' => 'activa',
            'fecha_inactivacion' => null,
            'inactivado_por' => null,
            'motivo_inactivacion' => null,
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        return $this->redirigirAFichaGasolinera(
            $request,
            $gasolinera,
            'Gasolinera reactivada correctamente. Sus tanques conservaron su estado individual.'
        );
    }

    /**
     * Store an additional tank for an existing gas station.
     */
    public function storeTanque(
        Request $request,
        Gasolinera $gasolinera
    ) {
        $this->autorizarAccesoGasolinera(
            $gasolinera
        );

        $this->validarEmpresaActivaGasolinera(
            $gasolinera
        );

        if (
            $gasolinera->estado !== 'activa'
        ) {
            return back()
                ->withErrors([
                    'gasolinera' => 'No se puede agregar un tanque a una gasolinera inactiva.',
                ])
                ->withInput();
        }

        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',

                Rule::unique(
                    'tanques',
                    'nombre'
                )->where(
                    'gasolinera_id',
                    $gasolinera->id
                ),
            ],

            'capacidad_total' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'volumen_actual' => [
                'required',
                'numeric',
                'gte:0',
            ],

            'volumen_minimo_alerta' => [
                'required',
                'numeric',
                'gte:0',
            ],
        ], [
            'nombre.required' => 'Debe ingresar el nombre del tanque.',
            'nombre.unique' => 'Ya existe un tanque con ese nombre para esta gasolinera.',

            'capacidad_total.required' => 'Debe ingresar la capacidad total del tanque.',
            'capacidad_total.gt' => 'La capacidad total del tanque debe ser mayor que cero.',

            'volumen_actual.required' => 'Debe ingresar el volumen actual del tanque.',
            'volumen_actual.gte' => 'El volumen actual no puede ser negativo.',

            'volumen_minimo_alerta.required' => 'Debe ingresar el volumen mínimo de alerta.',
            'volumen_minimo_alerta.gte' => 'El volumen mínimo de alerta no puede ser negativo.',
        ]);

        $capacidadTotal = (float) $validated['capacidad_total'];
        $volumenActual = (float) $validated['volumen_actual'];
        $volumenMinimoAlerta = (float) $validated['volumen_minimo_alerta'];

        if (
            $volumenActual > $capacidadTotal
        ) {
            return back()
                ->withErrors([
                    'volumen_actual' => 'El volumen actual no puede superar la capacidad total del tanque.',
                ])
                ->withInput();
        }

        if (
            $volumenMinimoAlerta >= $capacidadTotal
        ) {
            return back()
                ->withErrors([
                    'volumen_minimo_alerta' => 'El volumen mínimo de alerta debe ser menor que la capacidad total del tanque.',
                ])
                ->withInput();
        }

        DB::transaction(
            function () use (
                $gasolinera,
                $validated,
                $volumenActual
            ) {
                $tanque = Tanque::create([
                    'gasolinera_id' => $gasolinera->id,
                    'nombre' => $validated['nombre'],
                    'capacidad_total' => $validated['capacidad_total'],
                    'volumen_actual' => $validated['volumen_actual'],
                    'volumen_minimo_alerta' => $validated['volumen_minimo_alerta'],
                    'estado' => 'activo',
                    'fecha_creacion' => now(),
                    'creado_por' => Auth::id(),
                ]);

                MovimientoInventarioCombustible::create([
                    'empresa_id' => $gasolinera->empresa_id,
                    'tanque_id' => $tanque->id,
                    'abastecimiento_id' => null,
                    'tipo_movimiento' => 'carga_inicial',
                    'volumen_anterior' => 0,
                    'sentido_movimiento' => 'entrada',
                    'volumen_movimiento' => $volumenActual,
                    'volumen_resultante' => $volumenActual,
                    'fecha_hora_movimiento' => now(),
                    'observaciones' => 'Carga inicial registrada al agregar el tanque.',
                    'usuario_registra_id' => Auth::id(),
                    'estado' => 'registrado',
                    'fecha_creacion' => now(),
                ]);
            }
        );

        return $this->redirigirAFichaGasolinera(
            $request,
            $gasolinera,
            'Tanque agregado correctamente.'
        );
    }

    /**
     * Redirect to the internal or standalone gas station detail,
     * preserving the administration filters.
     */
    private function redirigirAFichaGasolinera(
        Request $request,
        Gasolinera $gasolinera,
        string $mensaje
    ) {
        $filtrosRetorno = $this->obtenerFiltrosRetorno(
            $request
        );

        $routeName = $request->input('return_to') === 'ventana'
            ? 'gasolineras.show.ventana'
            : 'gasolineras.show';

        return redirect()
            ->route(
                $routeName,
                array_merge(
                    [
                        'gasolinera' => $gasolinera->id,
                    ],
                    $filtrosRetorno
                )
            )
            ->with(
                'success',
                $mensaje
            );
    }

    /**
     * Extract only recognized navigation filters.
     */
    private function obtenerFiltrosRetorno(
        Request $request
    ): array {
        $filtros = $request->input(
            'filtros_retorno',
            []
        );

        if (! is_array($filtros)) {
            return [];
        }

        $permitidos = [
            'consultar',
            'busqueda_empresa',
            'busqueda_gasolinera',
            'empresa_ids',
            'gasolinera_ids',
            'empresa_id',
            'nombre',
            'estado',
            'page',
        ];

        return collect($filtros)
            ->only($permitidos)
            ->map(function ($valor) {
                if (is_array($valor)) {
                    return collect($valor)
                        ->filter(
                            fn ($item) => filled($item)
                        )
                        ->values()
                        ->all();
                }

                return $valor;
            })
            ->filter(function ($valor) {
                if (is_array($valor)) {
                    return count($valor) > 0;
                }

                return filled($valor);
            })
            ->all();
    }

    /**
     * Prevent company users from accessing other companies' gas stations.
     */
    private function autorizarAccesoGasolinera(
        Gasolinera $gasolinera
    ): void {
        $user = Auth::user();

        if (
            ! is_null($user->empresa_id)
            && (int) $user->empresa_id
                !== (int) $gasolinera->empresa_id
        ) {
            abort(
                403,
                'No tiene autorización para acceder a esta gasolinera.'
            );
        }
    }

    /**
     * Ensure the gas station belongs to an active company before
     * administrative or operational actions.
     */
    private function validarEmpresaActivaGasolinera(
        Gasolinera $gasolinera
    ): void {
        $gasolinera->loadMissing('empresa');

        if (
            ! $gasolinera->empresa
            || $gasolinera->empresa->estado !== 'activa'
        ) {
            abort(
                403,
                'No se puede operar sobre esta gasolinera porque la empresa está inactiva.'
            );
        }
    }

    /**
     * Ensure the selected company is active before creating
     * operational records.
     */
    private function validarEmpresaActivaPorId(
        int $empresaId
    ): void {
        $empresaActiva = Empresa::query()
            ->where(
                'id',
                $empresaId
            )
            ->where(
                'estado',
                'activa'
            )
            ->exists();

        if (! $empresaActiva) {
            abort(
                403,
                'No se puede operar sobre gasolineras porque la empresa está inactiva.'
            );
        }
    }

    /**
     * Prevent direct editing of an inactive gas station.
     */
    private function validarGasolineraActivaParaEdicion(
        Gasolinera $gasolinera
    ): void {
        if (
            $gasolinera->estado !== 'activa'
        ) {
            abort(
                403,
                'No se puede editar una gasolinera inactiva. Debe reactivarla desde su ficha.'
            );
        }
    }
}