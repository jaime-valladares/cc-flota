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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AbastecimientoController extends Controller
{
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
     * Consulta dentro de la ventana interna del sistema.
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
     * Formulario de abastecimiento dentro de ventana.
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

        return redirect()
            ->route(
                $esVentana
                    ? 'abastecimientos.create.ventana'
                    : 'abastecimientos.create',
                [
                    'unidad' =>
                        $abastecimiento->unidad_id,
                ]
            )
            ->with(
                'success',
                'Abastecimiento registrado correctamente.'
            );
    }

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

    /**
     * Aislamiento multiempresa.
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
}