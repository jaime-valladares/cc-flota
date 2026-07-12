<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Gasolinera;
use App\Models\MovimientoInventarioCombustible;
use App\Models\RecargaCombustible;
use App\Models\Tanque;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecargaTanqueController extends Controller
{
    /**
     * Display the tank recharge search panel.
     */
    public function index(Request $request)
    {
        $data = $this->prepararConsultaRecargas($request);

        return view('gasolineras.tanques.recargas.index', $data);
    }

    /**
     * Display the standalone tank recharge search panel.
     */
    public function indexVentana(Request $request)
    {
        $data = $this->prepararConsultaRecargas($request);

        return view('gasolineras.tanques.recargas.index-ventana', $data);
    }

    /**
     * Prepare searchable tanks for recharge.
     */
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
            'empresa_id' => ['nullable', 'integer', 'exists:empresas,id'],
            'gasolinera_id' => ['nullable', 'integer', 'exists:gasolineras,id'],
        ], [
            'empresa_id.exists' => 'La empresa seleccionada no es válida.',
            'gasolinera_id.exists' => 'La gasolinera seleccionada no es válida.',
        ]);

        $busquedaEmpresa = trim((string) ($validated['busqueda_empresa'] ?? ''));
        $busquedaGasolinera = trim((string) ($validated['busqueda_gasolinera'] ?? ''));

        $empresaId = $validated['empresa_id'] ?? null;
        $gasolineraId = $validated['gasolinera_id'] ?? null;

        if (! $esUsuarioDieselCop) {
            $empresaId = $user->empresa_id;
        }

        if ($gasolineraId) {
            $gasolineraSeleccionada = Gasolinera::query()
                ->with('empresa')
                ->find($gasolineraId);

            if (! $gasolineraSeleccionada) {
                abort(404);
            }

            $this->autorizarAccesoGasolinera($gasolineraSeleccionada);

            if ($empresaId && (int) $gasolineraSeleccionada->empresa_id !== (int) $empresaId) {
                abort(403, 'La gasolinera seleccionada no pertenece a la empresa indicada.');
            }

            $this->validarEmpresaActivaGasolinera($gasolineraSeleccionada);
        }

        $consultaEjecutada = $request->boolean('consultar');

        $hayFiltros = $consultaEjecutada
            || filled($busquedaEmpresa)
            || filled($busquedaGasolinera)
            || filled($empresaId)
            || filled($gasolineraId);

        $empresasSelector = $esUsuarioDieselCop
            ? Empresa::where('estado', 'activa')
                ->orderBy('nombre_comercial')
                ->orderBy('nombre_legal')
                ->get()
            : collect([$empresaUsuario])
                ->filter(fn ($empresa) => $empresa && $empresa->estado === 'activa')
                ->values();

        $gasolinerasSelector = Gasolinera::query()
            ->where('estado', 'activa')
            ->whereHas('empresa', function ($query) {
                $query->where('estado', 'activa');
            })
            ->when(! $esUsuarioDieselCop, function ($query) use ($user) {
                $query->where('empresa_id', $user->empresa_id);
            })
            ->when($esUsuarioDieselCop && filled($busquedaEmpresa), function ($query) use ($busquedaEmpresa) {
                $query->whereHas('empresa', function ($empresaQuery) use ($busquedaEmpresa) {
                    $empresaQuery
                        ->where('nombre_legal', 'like', '%' . $busquedaEmpresa . '%')
                        ->orWhere('nombre_comercial', 'like', '%' . $busquedaEmpresa . '%');
                });
            })
            ->when($esUsuarioDieselCop && filled($empresaId), function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->orderBy('nombre')
            ->get();

        $query = Tanque::query()
            ->with([
                'gasolinera.empresa',
            ])
            ->where('tanques.estado', 'activo')
            ->whereHas('gasolinera', function ($query) {
                $query->where('gasolineras.estado', 'activa');
            })
            ->whereHas('gasolinera.empresa', function ($query) {
                $query->where('estado', 'activa');
            })
            ->when(! $esUsuarioDieselCop, function ($query) use ($user) {
                $query->whereHas('gasolinera', function ($gasolineraQuery) use ($user) {
                    $gasolineraQuery->where('gasolineras.empresa_id', $user->empresa_id);
                });
            });

        if ($hayFiltros) {
            $query
                ->when(filled($busquedaEmpresa), function ($query) use ($busquedaEmpresa) {
                    $query->whereHas('gasolinera.empresa', function ($empresaQuery) use ($busquedaEmpresa) {
                        $empresaQuery
                            ->where('nombre_legal', 'like', '%' . $busquedaEmpresa . '%')
                            ->orWhere('nombre_comercial', 'like', '%' . $busquedaEmpresa . '%');
                    });
                })
                ->when(filled($empresaId), function ($query) use ($empresaId) {
                    $query->whereHas('gasolinera', function ($gasolineraQuery) use ($empresaId) {
                        $gasolineraQuery->where('gasolineras.empresa_id', $empresaId);
                    });
                })
                ->when(filled($busquedaGasolinera), function ($query) use ($busquedaGasolinera) {
                    $query->whereHas('gasolinera', function ($gasolineraQuery) use ($busquedaGasolinera) {
                        $gasolineraQuery->where('gasolineras.nombre', 'like', '%' . $busquedaGasolinera . '%');
                    });
                })
                ->when(filled($gasolineraId), function ($query) use ($gasolineraId) {
                    $query->where('gasolinera_id', $gasolineraId);
                });
        } else {
            $query->whereRaw('1 = 0');
        }

        $tanques = $query
            ->join('gasolineras', 'tanques.gasolinera_id', '=', 'gasolineras.id')
            ->select('tanques.*')
            ->orderBy('gasolineras.nombre')
            ->orderBy('tanques.nombre')
            ->paginate(10)
            ->withQueryString();

        $baseResumen = Tanque::query()
            ->where('tanques.estado', 'activo')
            ->whereHas('gasolinera', function ($query) {
                $query->where('gasolineras.estado', 'activa');
            })
            ->whereHas('gasolinera.empresa', function ($query) {
                $query->where('estado', 'activa');
            });

        if (! $esUsuarioDieselCop) {
            $baseResumen->whereHas('gasolinera', function ($query) use ($user) {
                $query->where('gasolineras.empresa_id', $user->empresa_id);
            });
        }

        $tanquesRecargables = (clone $baseResumen)->count();

        $tanquesBajoAlerta = (clone $baseResumen)
            ->whereColumn('volumen_actual', '<=', 'volumen_minimo_alerta')
            ->count();

        $capacidadDisponible = (clone $baseResumen)
            ->get()
            ->sum(function ($tanque) {
                return max(0, (float) $tanque->capacidad_total - (float) $tanque->volumen_actual);
            });

        return [
            'tanques' => $tanques,
            'empresasSelector' => $empresasSelector,
            'gasolinerasSelector' => $gasolinerasSelector,

            'busquedaEmpresa' => $busquedaEmpresa,
            'busquedaGasolinera' => $busquedaGasolinera,

            'empresaId' => $empresaId,
            'gasolineraId' => $gasolineraId,

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
     * Display the multi-tank recharge form.
     */
    public function create(Request $request, Gasolinera $gasolinera)
    {
        $this->autorizarAccesoGasolinera($gasolinera);
        $this->validarEmpresaActivaGasolinera($gasolinera);
        $this->validarGasolineraRecargable($gasolinera);

        return view('gasolineras.tanques.recargas.show', $this->prepararFormularioRecargaMultiple($request, $gasolinera));
    }

    /**
     * Display the standalone multi-tank recharge form.
     */
    public function createVentana(Request $request, Gasolinera $gasolinera)
    {
        $this->autorizarAccesoGasolinera($gasolinera);
        $this->validarEmpresaActivaGasolinera($gasolinera);
        $this->validarGasolineraRecargable($gasolinera);

        return view('gasolineras.tanques.recargas.show-ventana', $this->prepararFormularioRecargaMultiple($request, $gasolinera));
    }

    /**
     * Legacy individual screen redirect.
     */
    public function show(Gasolinera $gasolinera, Tanque $tanque)
    {
        $this->autorizarAccesoGasolinera($gasolinera);
        $this->validarEmpresaActivaGasolinera($gasolinera);
        $this->validarTanquePerteneceGasolinera($gasolinera, $tanque);
        $this->validarTanqueRecargable($gasolinera, $tanque);

        return redirect()->route('gasolineras.tanques.recargas.create', [
            'gasolinera' => $gasolinera,
            'tanque_id' => $tanque->id,
        ]);
    }

    /**
     * Legacy individual standalone screen redirect.
     */
    public function showVentana(Gasolinera $gasolinera, Tanque $tanque)
    {
        $this->autorizarAccesoGasolinera($gasolinera);
        $this->validarEmpresaActivaGasolinera($gasolinera);
        $this->validarTanquePerteneceGasolinera($gasolinera, $tanque);
        $this->validarTanqueRecargable($gasolinera, $tanque);

        return redirect()->route('gasolineras.tanques.recargas.create.ventana', [
            'gasolinera' => $gasolinera,
            'tanque_id' => $tanque->id,
        ]);
    }

    /**
     * Prepare multi-tank recharge form data.
     */
    private function prepararFormularioRecargaMultiple(Request $request, Gasolinera $gasolinera): array
    {
        $gasolinera->load('empresa');

        $tanques = Tanque::query()
            ->where('gasolinera_id', $gasolinera->id)
            ->where('estado', 'activo')
            ->orderBy('nombre')
            ->get();

        $tanquePreseleccionadoId = $request->integer('tanque_id');

        $resumenTanques = $tanques->map(function ($tanque) {
            $capacidadTotal = (float) $tanque->capacidad_total;
            $volumenActual = (float) $tanque->volumen_actual;
            $capacidadDisponible = max(0, $capacidadTotal - $volumenActual);

            $porcentajeDisponible = $capacidadTotal > 0
                ? round(($volumenActual / $capacidadTotal) * 100, 2)
                : 0;

            return [
                'tanque' => $tanque,
                'capacidad_total' => $capacidadTotal,
                'volumen_actual' => $volumenActual,
                'capacidad_disponible' => $capacidadDisponible,
                'porcentaje_disponible' => $porcentajeDisponible,
                'bajo_alerta' => (float) $tanque->volumen_actual <= (float) $tanque->volumen_minimo_alerta,
            ];
        });

        $recargasRecientes = RecargaCombustible::query()
            ->with([
                'usuarioRegistra',
                'movimientosInventario.tanque',
            ])
            ->where('gasolinera_id', $gasolinera->id)
            ->where('estado', 'registrado')
            ->latest('fecha_hora_recarga')
            ->limit(8)
            ->get();

        return [
            'gasolinera' => $gasolinera,
            'tanques' => $tanques,
            'resumenTanques' => $resumenTanques,
            'tanquePreseleccionadoId' => $tanquePreseleccionadoId,
            'recargasRecientes' => $recargasRecientes,
        ];
    }

    /**
     * Store a multi-tank fuel recharge.
     */
    public function store(Request $request, Gasolinera $gasolinera)
    {
        $this->autorizarAccesoGasolinera($gasolinera);
        $this->validarEmpresaActivaGasolinera($gasolinera);
        $this->validarGasolineraRecargable($gasolinera);

        $validated = $request->validate([
            'precio_galon' => ['required', 'numeric', 'gt:0'],
            'volumenes' => ['required', 'array'],
            'volumenes.*' => ['nullable', 'numeric', 'gte:0'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'return_to' => ['nullable', 'in:ventana'],
        ], [
            'precio_galon.required' => 'Debe ingresar el precio por galón.',
            'precio_galon.numeric' => 'El precio por galón debe ser numérico.',
            'precio_galon.gt' => 'El precio por galón debe ser mayor que cero.',
            'volumenes.required' => 'Debe ingresar al menos una cantidad de recarga.',
            'volumenes.array' => 'El formato de recarga por tanque no es válido.',
            'volumenes.*.numeric' => 'Las cantidades de recarga deben ser numéricas.',
            'volumenes.*.gte' => 'Las cantidades de recarga no pueden ser negativas.',
            'observaciones.max' => 'Las observaciones no deben exceder 1000 caracteres.',
            'return_to.in' => 'El destino de retorno no es válido.',
        ]);

        $precioGalon = round((float) $validated['precio_galon'], 4);
        $volumenesIngresados = collect($validated['volumenes'])
            ->mapWithKeys(function ($volumen, $tanqueId) {
                return [(int) $tanqueId => round((float) ($volumen ?: 0), 2)];
            })
            ->filter(fn ($volumen) => $volumen > 0);

        if ($volumenesIngresados->isEmpty()) {
            return back()
                ->withErrors([
                    'volumenes' => 'Debe ingresar al menos una cantidad mayor que cero para recargar.',
                ])
                ->withInput();
        }

        $tanqueIds = $volumenesIngresados->keys()->values();

        $tanques = Tanque::query()
            ->whereIn('id', $tanqueIds)
            ->where('gasolinera_id', $gasolinera->id)
            ->where('estado', 'activo')
            ->get()
            ->keyBy('id');

        if ($tanques->count() !== $tanqueIds->count()) {
            return back()
                ->withErrors([
                    'volumenes' => 'Uno o más tanques seleccionados no son válidos para esta gasolinera.',
                ])
                ->withInput();
        }

        foreach ($volumenesIngresados as $tanqueId => $volumenMovimiento) {
            $tanque = $tanques->get($tanqueId);

            $volumenAnterior = (float) $tanque->volumen_actual;
            $volumenResultante = $volumenAnterior + $volumenMovimiento;
            $capacidadTotal = (float) $tanque->capacidad_total;

            if ($volumenResultante > $capacidadTotal) {
                return back()
                    ->withErrors([
                        "volumenes.{$tanqueId}" => "La recarga del tanque {$tanque->nombre} excede su capacidad total.",
                    ])
                    ->withInput();
            }
        }

        $totalGalones = round($volumenesIngresados->sum(), 2);
        $totalCompra = round($totalGalones * $precioGalon, 2);

        DB::transaction(function () use (
            $gasolinera,
            $validated,
            $precioGalon,
            $volumenesIngresados,
            $totalGalones,
            $totalCompra
        ) {
            $tanquesBloqueados = Tanque::query()
                ->whereIn('id', $volumenesIngresados->keys())
                ->where('gasolinera_id', $gasolinera->id)
                ->where('estado', 'activo')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($volumenesIngresados as $tanqueId => $volumenMovimiento) {
                $tanque = $tanquesBloqueados->get($tanqueId);

                if (! $tanque) {
                    throw ValidationException::withMessages([
                        'volumenes' => 'Uno o más tanques seleccionados dejaron de estar disponibles.',
                    ]);
                }

                $volumenAnterior = (float) $tanque->volumen_actual;
                $volumenResultante = $volumenAnterior + $volumenMovimiento;
                $capacidadTotal = (float) $tanque->capacidad_total;

                if ($volumenResultante > $capacidadTotal) {
                    throw ValidationException::withMessages([
                        "volumenes.{$tanqueId}" => "La recarga del tanque {$tanque->nombre} excede su capacidad total.",
                    ]);
                }
            }

            $recarga = RecargaCombustible::create([
                'empresa_id' => $gasolinera->empresa_id,
                'gasolinera_id' => $gasolinera->id,
                'precio_galon' => $precioGalon,
                'total_galones' => $totalGalones,
                'total_compra' => $totalCompra,
                'fecha_hora_recarga' => now(),
                'observaciones' => $validated['observaciones'] ?? null,
                'usuario_registra_id' => Auth::id(),
                'estado' => 'registrado',
                'fecha_creacion' => now(),
            ]);

            foreach ($volumenesIngresados as $tanqueId => $volumenMovimiento) {
                $tanque = $tanquesBloqueados->get($tanqueId);

                $volumenAnterior = (float) $tanque->volumen_actual;
                $volumenResultante = round($volumenAnterior + $volumenMovimiento, 2);
                $subtotalCompra = round($volumenMovimiento * $precioGalon, 2);

                $tanque->update([
                    'volumen_actual' => $volumenResultante,
                    'fecha_actualizacion' => now(),
                    'actualizado_por' => Auth::id(),
                ]);

                MovimientoInventarioCombustible::create([
                    'empresa_id' => $gasolinera->empresa_id,
                    'tanque_id' => $tanque->id,
                    'abastecimiento_id' => null,
                    'recarga_combustible_id' => $recarga->id,
                    'tipo_movimiento' => 'entrada_recarga',
                    'volumen_anterior' => $volumenAnterior,
                    'sentido_movimiento' => 'entrada',
                    'volumen_movimiento' => $volumenMovimiento,
                    'volumen_resultante' => $volumenResultante,
                    'subtotal_compra' => $subtotalCompra,
                    'fecha_hora_movimiento' => now(),
                    'observaciones' => $validated['observaciones'] ?? null,
                    'usuario_registra_id' => Auth::id(),
                    'estado' => 'registrado',
                    'fecha_creacion' => now(),
                ]);
            }
        });

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('gasolineras.tanques.recargas.create.ventana', $gasolinera)
                ->with('success', 'Recarga de combustible registrada correctamente.');
        }

        return redirect()
            ->route('gasolineras.tanques.recargas.create', $gasolinera)
            ->with('success', 'Recarga de combustible registrada correctamente.');
    }

    /**
     * Prevent company users from accessing other companies' gas stations.
     */
    private function autorizarAccesoGasolinera(Gasolinera $gasolinera): void
    {
        $user = Auth::user();

        if (! is_null($user->empresa_id) && (int) $user->empresa_id !== (int) $gasolinera->empresa_id) {
            abort(403, 'No tiene autorización para acceder a esta gasolinera.');
        }
    }

    /**
     * Ensure the gas station belongs to an active company before recharge operations.
     */
    private function validarEmpresaActivaGasolinera(Gasolinera $gasolinera): void
    {
        $gasolinera->loadMissing('empresa');

        if (! $gasolinera->empresa || $gasolinera->empresa->estado !== 'activa') {
            abort(403, 'No se puede operar sobre esta gasolinera porque la empresa está inactiva.');
        }
    }

    /**
     * Ensure the tank belongs to the selected gas station.
     */
    private function validarTanquePerteneceGasolinera(Gasolinera $gasolinera, Tanque $tanque): void
    {
        if ((int) $tanque->gasolinera_id !== (int) $gasolinera->id) {
            abort(404);
        }
    }

    /**
     * Ensure the gas station can receive fuel.
     */
    private function validarGasolineraRecargable(Gasolinera $gasolinera): void
    {
        if ($gasolinera->estado !== 'activa') {
            abort(403, 'No se puede registrar una recarga en una gasolinera inactiva.');
        }
    }

    /**
     * Ensure the gas station and tank can receive fuel.
     */
    private function validarTanqueRecargable(Gasolinera $gasolinera, Tanque $tanque): void
    {
        $this->validarGasolineraRecargable($gasolinera);

        if ($tanque->estado !== 'activo') {
            abort(403, 'No se puede recargar un tanque inactivo.');
        }
    }
}