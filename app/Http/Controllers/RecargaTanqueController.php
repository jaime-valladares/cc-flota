<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Gasolinera;
use App\Models\MovimientoInventarioCombustible;
use App\Models\RecargaCombustible;
use App\Models\Tanque;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecargaTanqueController extends Controller
{
    public function index(Request $request)
    {
        return view(
            'gasolineras.tanques.recargas.index',
            $this->prepararConsultaRecargas($request)
        );
    }

    public function indexVentana(Request $request)
    {
        return view(
            'gasolineras.tanques.recargas.index-ventana',
            $this->prepararConsultaRecargas($request)
        );
    }

    private function prepararConsultaRecargas(Request $request): array
    {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);

        $empresaUsuario = $esUsuarioDieselCop
            ? null
            : Empresa::find($user->empresa_id);

        $validated = $request->validate([
            'busqueda_empresa' => ['nullable', 'string', 'max:150'],
            'busqueda_gasolinera' => ['nullable', 'string', 'max:150'],

            'empresa_ids' => ['nullable', 'array'],
            'empresa_ids.*' => [
                'integer',
                'distinct',
                'exists:empresas,id',
            ],

            'gasolinera_ids' => ['nullable', 'array'],
            'gasolinera_ids.*' => [
                'integer',
                'distinct',
                'exists:gasolineras,id',
            ],

            // Compatibilidad temporal con enlaces y filtros anteriores.
            'empresa_id' => [
                'nullable',
                'integer',
                'exists:empresas,id',
            ],
            'gasolinera_id' => [
                'nullable',
                'integer',
                'exists:gasolineras,id',
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

            'gasolinera_ids.array' =>
                'La selección de gasolineras no es válida.',
            'gasolinera_ids.*.integer' =>
                'Una de las gasolineras seleccionadas no es válida.',
            'gasolinera_ids.*.distinct' =>
                'No debe seleccionar una misma gasolinera más de una vez.',
            'gasolinera_ids.*.exists' =>
                'Una de las gasolineras seleccionadas no existe.',

            'empresa_id.exists' =>
                'La empresa seleccionada no es válida.',
            'gasolinera_id.exists' =>
                'La gasolinera seleccionada no es válida.',
        ]);

        $busquedaEmpresa = trim(
            (string) ($validated['busqueda_empresa'] ?? '')
        );

        $busquedaGasolinera = trim(
            (string) ($validated['busqueda_gasolinera'] ?? '')
        );

        $empresaIds = $this->normalizarIdsSeleccionados(
            $validated['empresa_ids'] ?? [],
            $validated['empresa_id'] ?? null
        );

        $gasolineraIds = $this->normalizarIdsSeleccionados(
            $validated['gasolinera_ids'] ?? [],
            $validated['gasolinera_id'] ?? null
        );

        if (! $esUsuarioDieselCop) {
            $empresaIds = collect([
                (int) $user->empresa_id,
            ]);
        }

        $consultaEjecutada = $request->boolean('consultar');

        $hayFiltros = $consultaEjecutada
            || filled($busquedaEmpresa)
            || filled($busquedaGasolinera)
            || $empresaIds->isNotEmpty()
            || $gasolineraIds->isNotEmpty();

        $empresasSelector = $esUsuarioDieselCop
            ? Empresa::query()
                ->where('estado', 'activa')
                ->orderByRaw(
                    'COALESCE(nombre_comercial, nombre_legal)'
                )
                ->orderBy('nombre_legal')
                ->get()
            : collect([$empresaUsuario])
                ->filter(
                    fn ($empresa) =>
                        $empresa
                        && $empresa->estado === 'activa'
                )
                ->values();

        $gasolinerasSelector = Gasolinera::query()
            ->with('empresa')
            ->where('estado', 'activa')
            ->whereHas('empresa', function ($query) {
                $query->where('estado', 'activa');
            })
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
                $esUsuarioDieselCop
                && filled($busquedaEmpresa),
                function ($query) use ($busquedaEmpresa) {
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
                    );
                }
            )
            ->when(
                $esUsuarioDieselCop
                && $empresaIds->isNotEmpty(),
                function ($query) use ($empresaIds) {
                    $query->whereIn(
                        'empresa_id',
                        $empresaIds->all()
                    );
                }
            )
            ->orderBy('nombre')
            ->get();

        $gasolinerasSeleccionadasValidas =
            $this->obtenerGasolinerasSeleccionadasValidas(
                $gasolineraIds,
                $empresaIds,
                $esUsuarioDieselCop,
                $user->empresa_id
            );

        $combinacionEmpresaGasolineraInvalida =
            $gasolineraIds->isNotEmpty()
            && $gasolinerasSeleccionadasValidas->count()
                !== $gasolineraIds->count();

        $query = $this->consultaBaseTanquesRecargables(
            $esUsuarioDieselCop,
            $user->empresa_id
        )->with('gasolinera.empresa');

        if ($hayFiltros) {
            if ($combinacionEmpresaGasolineraInvalida) {
                $query->whereRaw('1 = 0');
            } else {
                $this->aplicarFiltrosConsultaTanques(
                    $query,
                    $busquedaEmpresa,
                    $busquedaGasolinera,
                    $empresaIds,
                    $gasolineraIds
                );
            }
        } else {
            $query->whereRaw('1 = 0');
        }

        $tanques = $query
            ->join(
                'gasolineras',
                'tanques.gasolinera_id',
                '=',
                'gasolineras.id'
            )
            ->select('tanques.*')
            ->orderBy('gasolineras.nombre')
            ->orderBy('tanques.nombre')
            ->paginate(10)
            ->withQueryString();

        $baseResumen = $this->consultaBaseTanquesRecargables(
            $esUsuarioDieselCop,
            $user->empresa_id
        );

        if ($hayFiltros) {
            if ($combinacionEmpresaGasolineraInvalida) {
                $baseResumen->whereRaw('1 = 0');
            } else {
                $this->aplicarFiltrosConsultaTanques(
                    $baseResumen,
                    $busquedaEmpresa,
                    $busquedaGasolinera,
                    $empresaIds,
                    $gasolineraIds
                );
            }
        } else {
            $baseResumen->whereRaw('1 = 0');
        }

        $tanquesRecargables = (clone $baseResumen)->count();

        $tanquesBajoAlerta = (clone $baseResumen)
            ->whereColumn(
                'volumen_actual',
                '<=',
                'volumen_minimo_alerta'
            )
            ->count();

        $capacidadDisponible = (clone $baseResumen)
            ->get()
            ->sum(function ($tanque) {
                return max(
                    0,
                    (float) $tanque->capacidad_total
                    - (float) $tanque->volumen_actual
                );
            });

        return [
            'tanques' => $tanques,
            'empresasSelector' => $empresasSelector,
            'gasolinerasSelector' => $gasolinerasSelector,

            'busquedaEmpresa' => $busquedaEmpresa,
            'busquedaGasolinera' => $busquedaGasolinera,

            'empresaIds' => $empresaIds->all(),
            'gasolineraIds' => $gasolineraIds->all(),

            // Compatibilidad temporal con vistas o enlaces anteriores.
            'empresaId' => $empresaIds->first(),
            'gasolineraId' => $gasolineraIds->first(),

            'hayFiltros' => $hayFiltros,
            'consultaEjecutada' => $consultaEjecutada,

            'tanquesRecargables' => $tanquesRecargables,
            'tanquesBajoAlerta' => $tanquesBajoAlerta,
            'capacidadDisponible' => $capacidadDisponible,

            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'empresaUsuario' => $empresaUsuario,
        ];
    }

    /**
     * Base query for tanks that can participate in a new refill.
     */
    private function consultaBaseTanquesRecargables(
        bool $esUsuarioDieselCop,
        int|string|null $empresaUsuarioId
    ) {
        return Tanque::query()
            ->where('tanques.estado', 'activo')
            ->whereHas('gasolinera', function ($query) {
                $query->where(
                    'gasolineras.estado',
                    'activa'
                );
            })
            ->whereHas(
                'gasolinera.empresa',
                function ($query) {
                    $query->where('estado', 'activa');
                }
            )
            ->when(
                ! $esUsuarioDieselCop,
                function ($query) use ($empresaUsuarioId) {
                    $query->whereHas(
                        'gasolinera',
                        function ($gasolineraQuery) use (
                            $empresaUsuarioId
                        ) {
                            $gasolineraQuery->where(
                                'gasolineras.empresa_id',
                                $empresaUsuarioId
                            );
                        }
                    );
                }
            );
    }

    /**
     * Apply the same filters to the result list and its summary.
     */
    private function aplicarFiltrosConsultaTanques(
        $query,
        string $busquedaEmpresa,
        string $busquedaGasolinera,
        Collection $empresaIds,
        Collection $gasolineraIds
    ): void {
        $query
            ->when(
                filled($busquedaEmpresa),
                function ($query) use ($busquedaEmpresa) {
                    $query->whereHas(
                        'gasolinera.empresa',
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
                    );
                }
            )
            ->when(
                $empresaIds->isNotEmpty(),
                function ($query) use ($empresaIds) {
                    $query->whereHas(
                        'gasolinera',
                        function ($gasolineraQuery) use (
                            $empresaIds
                        ) {
                            $gasolineraQuery->whereIn(
                                'gasolineras.empresa_id',
                                $empresaIds->all()
                            );
                        }
                    );
                }
            )
            ->when(
                filled($busquedaGasolinera),
                function ($query) use ($busquedaGasolinera) {
                    $query->whereHas(
                        'gasolinera',
                        function ($gasolineraQuery) use (
                            $busquedaGasolinera
                        ) {
                            $gasolineraQuery->where(
                                'gasolineras.nombre',
                                'like',
                                '%' . $busquedaGasolinera . '%'
                            );
                        }
                    );
                }
            )
            ->when(
                $gasolineraIds->isNotEmpty(),
                function ($query) use ($gasolineraIds) {
                    $query->whereIn(
                        'tanques.gasolinera_id',
                        $gasolineraIds->all()
                    );
                }
            );
    }

    /**
     * Normalize multi-select parameters and legacy single parameters.
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
     * Validate that selected gas stations are active, accessible and belong
     * to the selected companies when a company filter is present.
     */
    private function obtenerGasolinerasSeleccionadasValidas(
        Collection $gasolineraIds,
        Collection $empresaIds,
        bool $esUsuarioDieselCop,
        int|string|null $empresaUsuarioId
    ): Collection {
        if ($gasolineraIds->isEmpty()) {
            return collect();
        }

        return Gasolinera::query()
            ->with('empresa')
            ->whereIn(
                'id',
                $gasolineraIds->all()
            )
            ->where('estado', 'activa')
            ->whereHas('empresa', function ($query) {
                $query->where('estado', 'activa');
            })
            ->when(
                ! $esUsuarioDieselCop,
                function ($query) use ($empresaUsuarioId) {
                    $query->where(
                        'empresa_id',
                        $empresaUsuarioId
                    );
                }
            )
            ->when(
                $esUsuarioDieselCop
                && $empresaIds->isNotEmpty(),
                function ($query) use ($empresaIds) {
                    $query->whereIn(
                        'empresa_id',
                        $empresaIds->all()
                    );
                }
            )
            ->get();
    }

    public function create(
        Request $request,
        Gasolinera $gasolinera
    ) {
        $this->autorizarAccesoGasolinera($gasolinera);
        $this->validarEmpresaActivaGasolinera($gasolinera);
        $this->validarGasolineraRecargable($gasolinera);

        return view(
            'gasolineras.tanques.recargas.show',
            $this->prepararFormularioRecargaMultiple(
                $request,
                $gasolinera
            )
        );
    }

    public function createVentana(
        Request $request,
        Gasolinera $gasolinera
    ) {
        $this->autorizarAccesoGasolinera($gasolinera);
        $this->validarEmpresaActivaGasolinera($gasolinera);
        $this->validarGasolineraRecargable($gasolinera);

        return view(
            'gasolineras.tanques.recargas.show-ventana',
            $this->prepararFormularioRecargaMultiple(
                $request,
                $gasolinera
            )
        );
    }

    public function show(
        Gasolinera $gasolinera,
        Tanque $tanque
    ) {
        $this->autorizarAccesoGasolinera($gasolinera);
        $this->validarEmpresaActivaGasolinera($gasolinera);
        $this->validarTanquePerteneceGasolinera(
            $gasolinera,
            $tanque
        );
        $this->validarTanqueRecargable(
            $gasolinera,
            $tanque
        );

        return redirect()->route(
            'gasolineras.tanques.recargas.create',
            [
                'gasolinera' => $gasolinera,
                'tanque_id' => $tanque->id,
            ]
        );
    }

    public function showVentana(
        Gasolinera $gasolinera,
        Tanque $tanque
    ) {
        $this->autorizarAccesoGasolinera($gasolinera);
        $this->validarEmpresaActivaGasolinera($gasolinera);
        $this->validarTanquePerteneceGasolinera(
            $gasolinera,
            $tanque
        );
        $this->validarTanqueRecargable(
            $gasolinera,
            $tanque
        );

        return redirect()->route(
            'gasolineras.tanques.recargas.create.ventana',
            [
                'gasolinera' => $gasolinera,
                'tanque_id' => $tanque->id,
            ]
        );
    }

    private function prepararFormularioRecargaMultiple(
        Request $request,
        Gasolinera $gasolinera
    ): array {
        $gasolinera->load('empresa');

        $tanques = Tanque::query()
            ->where(
                'gasolinera_id',
                $gasolinera->id
            )
            ->where('estado', 'activo')
            ->orderBy('nombre')
            ->get();

        $tanquePreseleccionadoId = $request->integer(
            'tanque_id'
        );

        $resumenTanques = $tanques->map(
            function ($tanque) {
                $capacidadTotal =
                    (float) $tanque->capacidad_total;

                $volumenActual =
                    (float) $tanque->volumen_actual;

                $capacidadDisponible = max(
                    0,
                    $capacidadTotal - $volumenActual
                );

                $porcentajeDisponible =
                    $capacidadTotal > 0
                    ? round(
                        (
                            $volumenActual
                            / $capacidadTotal
                        ) * 100,
                        2
                    )
                    : 0;

                return [
                    'tanque' => $tanque,
                    'capacidad_total' =>
                        $capacidadTotal,
                    'volumen_actual' =>
                        $volumenActual,
                    'capacidad_disponible' =>
                        $capacidadDisponible,
                    'porcentaje_disponible' =>
                        $porcentajeDisponible,
                    'bajo_alerta' =>
                        (float) $tanque->volumen_actual
                        <= (float) $tanque->volumen_minimo_alerta,
                ];
            }
        );

        $recargasRecientes = RecargaCombustible::query()
            ->with([
                'usuarioRegistra',
                'anuladoPor',
                'movimientosInventario.tanque',
            ])
            ->where(
                'gasolinera_id',
                $gasolinera->id
            )
            ->latest('fecha_hora_recarga')
            ->limit(8)
            ->get();

        return [
            'gasolinera' => $gasolinera,
            'tanques' => $tanques,
            'resumenTanques' => $resumenTanques,
            'tanquePreseleccionadoId' =>
                $tanquePreseleccionadoId,
            'recargasRecientes' => $recargasRecientes,
        ];
    }

    public function store(
        Request $request,
        Gasolinera $gasolinera
    ) {
        $this->autorizarAccesoGasolinera($gasolinera);
        $this->validarEmpresaActivaGasolinera($gasolinera);
        $this->validarGasolineraRecargable($gasolinera);

        $validated = $request->validate([
            'precio_galon' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'volumenes' => [
                'required',
                'array',
            ],
            'volumenes.*' => [
                'nullable',
                'numeric',
                'gte:0',
            ],
            'return_to' => [
                'nullable',
                'in:ventana',
            ],
        ], [
            'precio_galon.required' =>
                'Debe ingresar el precio por galón.',
            'precio_galon.numeric' =>
                'El precio por galón debe ser numérico.',
            'precio_galon.gt' =>
                'El precio por galón debe ser mayor que cero.',

            'volumenes.required' =>
                'Debe ingresar al menos una cantidad de recarga.',
            'volumenes.array' =>
                'El formato de recarga por tanque no es válido.',
            'volumenes.*.numeric' =>
                'Las cantidades de recarga deben ser numéricas.',
            'volumenes.*.gte' =>
                'Las cantidades de recarga no pueden ser negativas.',

            'return_to.in' =>
                'El destino de retorno no es válido.',
        ]);

        $precioGalon = round(
            (float) $validated['precio_galon'],
            4
        );

        $volumenesIngresados = collect(
            $validated['volumenes']
        )
            ->mapWithKeys(
                function ($volumen, $tanqueId) {
                    return [
                        (int) $tanqueId => round(
                            (float) ($volumen ?: 0),
                            2
                        ),
                    ];
                }
            )
            ->filter(
                fn ($volumen) => $volumen > 0
            );

        if ($volumenesIngresados->isEmpty()) {
            return back()
                ->withErrors([
                    'volumenes' =>
                        'Debe ingresar al menos una cantidad mayor que cero para recargar.',
                ])
                ->withInput();
        }

        $tanqueIds = $volumenesIngresados
            ->keys()
            ->values();

        $tanques = Tanque::query()
            ->whereIn('id', $tanqueIds)
            ->where(
                'gasolinera_id',
                $gasolinera->id
            )
            ->where('estado', 'activo')
            ->get()
            ->keyBy('id');

        if ($tanques->count() !== $tanqueIds->count()) {
            return back()
                ->withErrors([
                    'volumenes' =>
                        'Uno o más tanques seleccionados no son válidos para esta gasolinera.',
                ])
                ->withInput();
        }

        foreach (
            $volumenesIngresados
            as $tanqueId => $volumenMovimiento
        ) {
            $tanque = $tanques->get($tanqueId);

            $volumenResultante =
                (float) $tanque->volumen_actual
                + $volumenMovimiento;

            if (
                $volumenResultante
                > (float) $tanque->capacidad_total
            ) {
                return back()
                    ->withErrors([
                        "volumenes.{$tanqueId}" =>
                            "La recarga del tanque {$tanque->nombre} excede su capacidad total.",
                    ])
                    ->withInput();
            }
        }

        $totalGalones = round(
            $volumenesIngresados->sum(),
            2
        );

        $totalCompra = round(
            $totalGalones * $precioGalon,
            2
        );

        DB::transaction(function () use (
            $gasolinera,
            $precioGalon,
            $volumenesIngresados,
            $totalGalones,
            $totalCompra
        ) {
            $tanquesBloqueados = Tanque::query()
                ->whereIn(
                    'id',
                    $volumenesIngresados->keys()
                )
                ->where(
                    'gasolinera_id',
                    $gasolinera->id
                )
                ->where('estado', 'activo')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach (
                $volumenesIngresados
                as $tanqueId => $volumenMovimiento
            ) {
                $tanque = $tanquesBloqueados->get(
                    $tanqueId
                );

                if (! $tanque) {
                    throw ValidationException::withMessages([
                        'volumenes' =>
                            'Uno o más tanques seleccionados dejaron de estar disponibles.',
                    ]);
                }

                $volumenResultante =
                    (float) $tanque->volumen_actual
                    + $volumenMovimiento;

                if (
                    $volumenResultante
                    > (float) $tanque->capacidad_total
                ) {
                    throw ValidationException::withMessages([
                        "volumenes.{$tanqueId}" =>
                            "La recarga del tanque {$tanque->nombre} excede su capacidad total.",
                    ]);
                }
            }

            $fechaRecarga = now();
            $usuarioId = Auth::id();

            $recarga = RecargaCombustible::create([
                'empresa_id' =>
                    $gasolinera->empresa_id,
                'gasolinera_id' =>
                    $gasolinera->id,
                'precio_galon' =>
                    $precioGalon,
                'total_galones' =>
                    $totalGalones,
                'total_compra' =>
                    $totalCompra,
                'fecha_hora_recarga' =>
                    $fechaRecarga,
                'observaciones' =>
                    null,
                'usuario_registra_id' =>
                    $usuarioId,
                'estado' =>
                    'registrado',
                'fecha_creacion' =>
                    $fechaRecarga,
            ]);

            foreach (
                $volumenesIngresados
                as $tanqueId => $volumenMovimiento
            ) {
                $tanque = $tanquesBloqueados->get(
                    $tanqueId
                );

                $volumenAnterior =
                    (float) $tanque->volumen_actual;

                $volumenResultante = round(
                    $volumenAnterior
                    + $volumenMovimiento,
                    2
                );

                $subtotalCompra = round(
                    $volumenMovimiento
                    * $precioGalon,
                    2
                );

                $tanque->update([
                    'volumen_actual' =>
                        $volumenResultante,
                    'fecha_actualizacion' =>
                        $fechaRecarga,
                    'actualizado_por' =>
                        $usuarioId,
                ]);

                MovimientoInventarioCombustible::create([
                    'empresa_id' =>
                        $gasolinera->empresa_id,
                    'tanque_id' =>
                        $tanque->id,
                    'abastecimiento_id' =>
                        null,
                    'recarga_combustible_id' =>
                        $recarga->id,
                    'tipo_movimiento' =>
                        'entrada_recarga',
                    'volumen_anterior' =>
                        $volumenAnterior,
                    'sentido_movimiento' =>
                        'entrada',
                    'volumen_movimiento' =>
                        $volumenMovimiento,
                    'volumen_resultante' =>
                        $volumenResultante,
                    'subtotal_compra' =>
                        $subtotalCompra,
                    'fecha_hora_movimiento' =>
                        $fechaRecarga,
                    'observaciones' =>
                        null,
                    'usuario_registra_id' =>
                        $usuarioId,
                    'estado' =>
                        'registrado',
                    'fecha_creacion' =>
                        $fechaRecarga,
                ]);
            }
        });

        return $this->redirigirFormularioRecarga(
            $request,
            $gasolinera,
            'Recarga de combustible registrada correctamente.'
        );
    }

    public function anular(
        Request $request,
        Gasolinera $gasolinera,
        RecargaCombustible $recarga
    ) {
        $this->autorizarAccesoGasolinera($gasolinera);
        $this->validarEmpresaActivaGasolinera($gasolinera);
        $this->validarGasolineraRecargable($gasolinera);

        if (
            (int) $recarga->gasolinera_id
            !== (int) $gasolinera->id
        ) {
            abort(404);
        }

        $validated = $request->validate([
            'motivo_anulacion' => [
                'required',
                'string',
                'max:255',
            ],
            'return_to' => [
                'nullable',
                'in:ventana',
            ],
        ], [
            'motivo_anulacion.required' =>
                'Debe ingresar el motivo de anulación.',
            'motivo_anulacion.max' =>
                'El motivo de anulación no debe exceder 255 caracteres.',
            'return_to.in' =>
                'El destino de retorno no es válido.',
        ]);

        DB::transaction(function () use (
            $recarga,
            $validated
        ) {
            $recargaBloqueada =
                RecargaCombustible::query()
                    ->whereKey($recarga->id)
                    ->lockForUpdate()
                    ->firstOrFail();

            if (
                $recargaBloqueada->estado
                !== 'registrado'
            ) {
                throw ValidationException::withMessages([
                    'motivo_anulacion' =>
                        'La recarga ya fue anulada y no puede modificarse.',
                ]);
            }

            $movimientosOriginales =
                MovimientoInventarioCombustible::query()
                    ->where(
                        'recarga_combustible_id',
                        $recargaBloqueada->id
                    )
                    ->where(
                        'tipo_movimiento',
                        'entrada_recarga'
                    )
                    ->where(
                        'estado',
                        'registrado'
                    )
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

            if ($movimientosOriginales->isEmpty()) {
                throw ValidationException::withMessages([
                    'motivo_anulacion' =>
                        'La recarga no tiene movimientos registrados para revertir.',
                ]);
            }

            $tanquesBloqueados = Tanque::query()
                ->whereIn(
                    'id',
                    $movimientosOriginales
                        ->pluck('tanque_id')
                        ->unique()
                )
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach (
                $movimientosOriginales
                as $movimiento
            ) {
                $tanque = $tanquesBloqueados->get(
                    $movimiento->tanque_id
                );

                if (! $tanque) {
                    throw ValidationException::withMessages([
                        'motivo_anulacion' =>
                            'Uno de los tanques de la recarga ya no está disponible.',
                    ]);
                }

                if (
                    (float) $tanque->volumen_actual
                    < (float) $movimiento->volumen_movimiento
                ) {
                    throw ValidationException::withMessages([
                        'motivo_anulacion' =>
                            "No se puede anular la recarga porque el tanque {$tanque->nombre} no conserva inventario suficiente para revertirla.",
                    ]);
                }
            }

            $fechaAnulacion = now();
            $usuarioId = Auth::id();

            $motivoAnulacion = trim(
                $validated['motivo_anulacion']
            );

            foreach (
                $movimientosOriginales
                as $movimiento
            ) {
                $tanque = $tanquesBloqueados->get(
                    $movimiento->tanque_id
                );

                $volumenAnterior =
                    (float) $tanque->volumen_actual;

                $volumenMovimiento =
                    (float) $movimiento->volumen_movimiento;

                $volumenResultante = round(
                    $volumenAnterior
                    - $volumenMovimiento,
                    2
                );

                $tanque->update([
                    'volumen_actual' =>
                        $volumenResultante,
                    'fecha_actualizacion' =>
                        $fechaAnulacion,
                    'actualizado_por' =>
                        $usuarioId,
                ]);

                $movimiento->update([
                    'estado' =>
                        'anulado',
                    'fecha_actualizacion' =>
                        $fechaAnulacion,
                    'actualizado_por' =>
                        $usuarioId,
                    'fecha_anulacion' =>
                        $fechaAnulacion,
                    'anulado_por' =>
                        $usuarioId,
                    'motivo_anulacion' =>
                        $motivoAnulacion,
                ]);

                MovimientoInventarioCombustible::create([
                    'empresa_id' =>
                        $recargaBloqueada->empresa_id,
                    'tanque_id' =>
                        $tanque->id,
                    'abastecimiento_id' =>
                        null,
                    'recarga_combustible_id' =>
                        $recargaBloqueada->id,
                    'tipo_movimiento' =>
                        'anulacion_recarga',
                    'volumen_anterior' =>
                        $volumenAnterior,
                    'sentido_movimiento' =>
                        'salida',
                    'volumen_movimiento' =>
                        $volumenMovimiento,
                    'volumen_resultante' =>
                        $volumenResultante,
                    'subtotal_compra' =>
                        null,
                    'fecha_hora_movimiento' =>
                        $fechaAnulacion,
                    'observaciones' =>
                        'Reversión automática por anulación completa de recarga.',
                    'usuario_registra_id' =>
                        $usuarioId,
                    'estado' =>
                        'registrado',
                    'fecha_creacion' =>
                        $fechaAnulacion,
                ]);
            }

            $recargaBloqueada->update([
                'estado' =>
                    'anulado',
                'fecha_actualizacion' =>
                    $fechaAnulacion,
                'actualizado_por' =>
                    $usuarioId,
                'fecha_anulacion' =>
                    $fechaAnulacion,
                'anulado_por' =>
                    $usuarioId,
                'motivo_anulacion' =>
                    $motivoAnulacion,
            ]);
        });

        return $this->redirigirFormularioRecarga(
            $request,
            $gasolinera,
            'La recarga fue anulada completamente y su inventario fue revertido.'
        );
    }

    private function redirigirFormularioRecarga(
        Request $request,
        Gasolinera $gasolinera,
        string $mensaje
    ) {
        if (
            $request->input('return_to')
            === 'ventana'
        ) {
            return redirect()
                ->route(
                    'gasolineras.tanques.recargas.create.ventana',
                    $gasolinera
                )
                ->with('success', $mensaje);
        }

        return redirect()
            ->route(
                'gasolineras.tanques.recargas.create',
                $gasolinera
            )
            ->with('success', $mensaje);
    }

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

    private function validarTanquePerteneceGasolinera(
        Gasolinera $gasolinera,
        Tanque $tanque
    ): void {
        if (
            (int) $tanque->gasolinera_id
            !== (int) $gasolinera->id
        ) {
            abort(404);
        }
    }

    private function validarGasolineraRecargable(
        Gasolinera $gasolinera
    ): void {
        if ($gasolinera->estado !== 'activa') {
            abort(
                403,
                'No se puede registrar una recarga en una gasolinera inactiva.'
            );
        }
    }

    private function validarTanqueRecargable(
        Gasolinera $gasolinera,
        Tanque $tanque
    ): void {
        $this->validarGasolineraRecargable(
            $gasolinera
        );

        if ($tanque->estado !== 'activo') {
            abort(
                403,
                'No se puede recargar un tanque inactivo.'
            );
        }
    }
}