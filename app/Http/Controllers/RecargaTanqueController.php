<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Gasolinera;
use App\Models\MovimientoInventarioCombustible;
use App\Models\Tanque;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            'empresa_id' => ['nullable', 'integer', 'exists:empresas,id'],
            'gasolinera_id' => ['nullable', 'integer', 'exists:gasolineras,id'],
        ], [
            'empresa_id.exists' => 'La empresa seleccionada no es válida.',
            'gasolinera_id.exists' => 'La gasolinera seleccionada no es válida.',
        ]);

        $empresaId = $validated['empresa_id'] ?? null;
        $gasolineraId = $validated['gasolinera_id'] ?? null;

        if (! $esUsuarioDieselCop) {
            $empresaId = $user->empresa_id;
        }

        if ($gasolineraId) {
            $gasolineraSeleccionada = Gasolinera::find($gasolineraId);

            if (! $gasolineraSeleccionada) {
                abort(404);
            }

            $this->autorizarAccesoGasolinera($gasolineraSeleccionada);

            if ($empresaId && (int) $gasolineraSeleccionada->empresa_id !== (int) $empresaId) {
                abort(403, 'La gasolinera seleccionada no pertenece a la empresa indicada.');
            }
        }

        $hayFiltros = ! $esUsuarioDieselCop
            || $request->hasAny(['empresa_id', 'gasolinera_id', 'consultar']);

        $query = Tanque::query()
            ->with([
                'gasolinera.empresa',
            ])
            ->where('tanques.estado', 'activo')
            ->whereHas('gasolinera', function ($query) {
                $query->where('gasolineras.estado', 'activa');
            });

        if ($hayFiltros) {
            if ($empresaId) {
                $query->whereHas('gasolinera', function ($query) use ($empresaId) {
                    $query->where('gasolineras.empresa_id', $empresaId);
                });
            }

            if ($gasolineraId) {
                $query->where('gasolinera_id', $gasolineraId);
            }
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

        $empresasSelector = $esUsuarioDieselCop
            ? Empresa::where('estado', 'activa')
                ->orderBy('nombre_comercial')
                ->orderBy('nombre_legal')
                ->get()
            : collect([$empresaUsuario])->filter();

        $gasolinerasSelectorQuery = Gasolinera::query()
            ->where('estado', 'activa')
            ->orderBy('nombre');

        if (! $esUsuarioDieselCop) {
            $gasolinerasSelectorQuery->where('empresa_id', $user->empresa_id);
        } elseif ($empresaId) {
            $gasolinerasSelectorQuery->where('empresa_id', $empresaId);
        }

        $gasolinerasSelector = $gasolinerasSelectorQuery->get();

        $baseResumen = Tanque::query()
            ->where('tanques.estado', 'activo')
            ->whereHas('gasolinera', function ($query) {
                $query->where('gasolineras.estado', 'activa');
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
            'empresaId' => $empresaId,
            'gasolineraId' => $gasolineraId,
            'hayFiltros' => $hayFiltros,
            'tanquesRecargables' => $tanquesRecargables,
            'tanquesBajoAlerta' => $tanquesBajoAlerta,
            'capacidadDisponible' => $capacidadDisponible,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'empresaUsuario' => $empresaUsuario,
        ];
    }

    /**
     * Display the tank recharge form.
     */
    public function show(Gasolinera $gasolinera, Tanque $tanque)
    {
        $this->autorizarAccesoGasolinera($gasolinera);
        $this->validarTanquePerteneceGasolinera($gasolinera, $tanque);
        $this->validarTanqueRecargable($gasolinera, $tanque);

        return view('gasolineras.tanques.recargas.show', $this->prepararFormularioRecarga($gasolinera, $tanque));
    }

    /**
     * Display the standalone tank recharge form.
     */
    public function showVentana(Gasolinera $gasolinera, Tanque $tanque)
    {
        $this->autorizarAccesoGasolinera($gasolinera);
        $this->validarTanquePerteneceGasolinera($gasolinera, $tanque);
        $this->validarTanqueRecargable($gasolinera, $tanque);

        return view('gasolineras.tanques.recargas.show-ventana', $this->prepararFormularioRecarga($gasolinera, $tanque));
    }

    /**
     * Prepare recharge form data.
     */
    private function prepararFormularioRecarga(Gasolinera $gasolinera, Tanque $tanque): array
    {
        $tanque->load([
            'gasolinera.empresa',
        ]);

        $capacidadTotal = (float) $tanque->capacidad_total;
        $volumenActual = (float) $tanque->volumen_actual;
        $volumenMinimoAlerta = (float) $tanque->volumen_minimo_alerta;

        $capacidadDisponible = max(0, $capacidadTotal - $volumenActual);

        $porcentajeDisponible = $capacidadTotal > 0
            ? round(($volumenActual / $capacidadTotal) * 100, 2)
            : 0;

        $bajoAlerta = $tanque->estaBajoAlerta();

        $movimientosRecientes = MovimientoInventarioCombustible::query()
            ->where('tanque_id', $tanque->id)
            ->where('estado', 'registrado')
            ->latest('fecha_hora_movimiento')
            ->limit(10)
            ->get();

        return [
            'gasolinera' => $gasolinera,
            'tanque' => $tanque,
            'capacidadTotal' => $capacidadTotal,
            'volumenActual' => $volumenActual,
            'volumenMinimoAlerta' => $volumenMinimoAlerta,
            'capacidadDisponible' => $capacidadDisponible,
            'porcentajeDisponible' => $porcentajeDisponible,
            'bajoAlerta' => $bajoAlerta,
            'movimientosRecientes' => $movimientosRecientes,
        ];
    }

    /**
     * Store a tank recharge movement.
     */
    public function store(Request $request, Gasolinera $gasolinera, Tanque $tanque)
    {
        $this->autorizarAccesoGasolinera($gasolinera);
        $this->validarTanquePerteneceGasolinera($gasolinera, $tanque);
        $this->validarTanqueRecargable($gasolinera, $tanque);

        $validated = $request->validate([
            'volumen_movimiento' => ['required', 'numeric', 'gt:0'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ], [
            'volumen_movimiento.required' => 'Debe ingresar el volumen a recargar.',
            'volumen_movimiento.numeric' => 'El volumen a recargar debe ser numérico.',
            'volumen_movimiento.gt' => 'El volumen a recargar debe ser mayor que cero.',
            'observaciones.max' => 'Las observaciones no deben exceder 1000 caracteres.',
        ]);

        $volumenAnterior = (float) $tanque->volumen_actual;
        $volumenMovimiento = (float) $validated['volumen_movimiento'];
        $volumenResultante = $volumenAnterior + $volumenMovimiento;
        $capacidadTotal = (float) $tanque->capacidad_total;

        if ($volumenResultante > $capacidadTotal) {
            return back()
                ->withErrors([
                    'volumen_movimiento' => 'La recarga excede la capacidad total del tanque.',
                ])
                ->withInput();
        }

        DB::transaction(function () use (
            $gasolinera,
            $tanque,
            $validated,
            $volumenAnterior,
            $volumenMovimiento,
            $volumenResultante
        ) {
            $tanque->update([
                'volumen_actual' => $volumenResultante,
                'fecha_actualizacion' => now(),
                'actualizado_por' => Auth::id(),
            ]);

            MovimientoInventarioCombustible::create([
                'empresa_id' => $gasolinera->empresa_id,
                'tanque_id' => $tanque->id,
                'abastecimiento_id' => null,
                'tipo_movimiento' => 'entrada_recarga',
                'volumen_anterior' => $volumenAnterior,
                'sentido_movimiento' => 'entrada',
                'volumen_movimiento' => $volumenMovimiento,
                'volumen_resultante' => $volumenResultante,
                'fecha_hora_movimiento' => now(),
                'observaciones' => $validated['observaciones'] ?? null,
                'usuario_registra_id' => Auth::id(),
                'estado' => 'registrado',
                'fecha_creacion' => now(),
            ]);
        });

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('gasolineras.tanques.recargas.show.ventana', [$gasolinera, $tanque])
                ->with('success', 'Recarga de tanque registrada correctamente.');
        }

        return redirect()
            ->route('gasolineras.tanques.recargas.show', [$gasolinera, $tanque])
            ->with('success', 'Recarga de tanque registrada correctamente.');
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
     * Ensure the tank belongs to the selected gas station.
     */
    private function validarTanquePerteneceGasolinera(Gasolinera $gasolinera, Tanque $tanque): void
    {
        if ((int) $tanque->gasolinera_id !== (int) $gasolinera->id) {
            abort(404);
        }
    }

    /**
     * Ensure the gas station and tank can receive fuel.
     */
    private function validarTanqueRecargable(Gasolinera $gasolinera, Tanque $tanque): void
    {
        if ($gasolinera->estado !== 'activa') {
            abort(403, 'No se puede recargar un tanque de una gasolinera inactiva.');
        }

        if ($tanque->estado !== 'activo') {
            abort(403, 'No se puede recargar un tanque inactivo.');
        }
    }
}