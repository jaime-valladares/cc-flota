<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Gasolinera;
use App\Models\MovimientoInventarioCombustible;
use App\Models\Tanque;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TanqueController extends Controller
{
    /**
     * Display the tank management search panel.
     */
    public function index(Request $request)
    {
        $data = $this->prepararConsultaTanques($request);

        return view('gasolineras.tanques.index', $data);
    }

    /**
     * Display the standalone tank management search panel.
     */
    public function indexVentana(Request $request)
    {
        $data = $this->prepararConsultaTanques($request);

        return view('gasolineras.tanques.index-ventana', $data);
    }

    /**
     * Prepare tank query data for normal and standalone screens.
     */
    private function prepararConsultaTanques(Request $request): array
    {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);
        $empresaUsuario = $esUsuarioDieselCop
            ? null
            : Empresa::find($user->empresa_id);

        $validated = $request->validate([
            'empresa_id' => ['nullable', 'integer', 'exists:empresas,id'],
            'gasolinera_id' => ['nullable', 'integer', 'exists:gasolineras,id'],
            'nombre' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'in:activo,inactivo'],
        ], [
            'empresa_id.exists' => 'La empresa seleccionada no es válida.',
            'gasolinera_id.exists' => 'La gasolinera seleccionada no es válida.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ]);

        $empresaId = $validated['empresa_id'] ?? null;
        $gasolineraId = $validated['gasolinera_id'] ?? null;
        $nombre = trim((string) ($validated['nombre'] ?? ''));
        $estado = $validated['estado'] ?? null;

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

        $hayFiltros = ! $esUsuarioDieselCop
            || $request->hasAny(['empresa_id', 'gasolinera_id', 'nombre', 'estado', 'consultar']);

        $query = Tanque::query()
            ->with([
                'gasolinera.empresa',
            ])
            ->whereHas('gasolinera.empresa', function ($query) {
                $query->where('estado', 'activa');
            });

        if ($hayFiltros) {
            if ($empresaId) {
                $query->whereHas('gasolinera', function ($query) use ($empresaId) {
                    $query->where('empresa_id', $empresaId);
                });
            }

            if ($gasolineraId) {
                $query->where('gasolinera_id', $gasolineraId);
            }

            if ($nombre !== '') {
                $query->where('nombre', 'like', '%' . $nombre . '%');
            }

            if (in_array($estado, ['activo', 'inactivo'], true)) {
                $query->where('estado', $estado);
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
            : collect([$empresaUsuario])
                ->filter(fn ($empresa) => $empresa && $empresa->estado === 'activa')
                ->values();

        $gasolinerasSelectorQuery = Gasolinera::query()
            ->with('empresa')
            ->whereHas('empresa', function ($query) {
                $query->where('estado', 'activa');
            })
            ->orderBy('nombre');

        if (! $esUsuarioDieselCop) {
            $gasolinerasSelectorQuery->where('empresa_id', $user->empresa_id);
        } elseif ($empresaId) {
            $gasolinerasSelectorQuery->where('empresa_id', $empresaId);
        }

        $gasolinerasSelector = $gasolinerasSelectorQuery->get();

        $baseResumen = Tanque::query()
            ->whereHas('gasolinera.empresa', function ($query) {
                $query->where('estado', 'activa');
            });

        if (! $esUsuarioDieselCop) {
            $baseResumen->whereHas('gasolinera', function ($query) use ($user) {
                $query->where('empresa_id', $user->empresa_id);
            });
        }

        $totalTanques = (clone $baseResumen)->count();
        $tanquesActivos = (clone $baseResumen)->where('estado', 'activo')->count();
        $tanquesInactivos = (clone $baseResumen)->where('estado', 'inactivo')->count();

        return [
            'tanques' => $tanques,
            'empresasSelector' => $empresasSelector,
            'gasolinerasSelector' => $gasolinerasSelector,
            'empresaId' => $empresaId,
            'gasolineraId' => $gasolineraId,
            'nombre' => $nombre,
            'estado' => $estado,
            'hayFiltros' => $hayFiltros,
            'totalTanques' => $totalTanques,
            'tanquesActivos' => $tanquesActivos,
            'tanquesInactivos' => $tanquesInactivos,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'empresaUsuario' => $empresaUsuario,
        ];
    }

    /**
     * Display the tank management detail screen.
     */
    public function show(Gasolinera $gasolinera, Tanque $tanque)
    {
        $this->autorizarAccesoGasolinera($gasolinera);
        $this->validarEmpresaActivaGasolinera($gasolinera);
        $this->validarTanquePerteneceGasolinera($gasolinera, $tanque);

        $tanque->load([
            'gasolinera.empresa',
            'movimientosInventario' => function ($query) {
                $query->where('estado', 'registrado')
                    ->latest('fecha_hora_movimiento')
                    ->limit(10);
            },
        ]);

        return view('gasolineras.tanques.show', $this->prepararFichaTanque($gasolinera, $tanque));
    }

    /**
     * Display the standalone tank management detail screen.
     */
    public function showVentana(Gasolinera $gasolinera, Tanque $tanque)
    {
        $this->autorizarAccesoGasolinera($gasolinera);
        $this->validarEmpresaActivaGasolinera($gasolinera);
        $this->validarTanquePerteneceGasolinera($gasolinera, $tanque);

        $tanque->load([
            'gasolinera.empresa',
            'movimientosInventario' => function ($query) {
                $query->where('estado', 'registrado')
                    ->latest('fecha_hora_movimiento')
                    ->limit(10);
            },
        ]);

        return view('gasolineras.tanques.show-ventana', $this->prepararFichaTanque($gasolinera, $tanque));
    }

    /**
     * Prepare summary data for tank detail screens.
     */
    private function prepararFichaTanque(Gasolinera $gasolinera, Tanque $tanque): array
    {
        $capacidadTotal = (float) $tanque->capacidad_total;
        $volumenActual = (float) $tanque->volumen_actual;
        $volumenMinimoAlerta = (float) $tanque->volumen_minimo_alerta;

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
            'porcentajeDisponible' => $porcentajeDisponible,
            'bajoAlerta' => $bajoAlerta,
            'movimientosRecientes' => $movimientosRecientes,
        ];
    }

    /**
     * Update controlled tank data.
     */
    public function update(Request $request, Gasolinera $gasolinera, Tanque $tanque)
    {
        $this->autorizarAccesoGasolinera($gasolinera);
        $this->validarEmpresaActivaGasolinera($gasolinera);
        $this->validarTanquePerteneceGasolinera($gasolinera, $tanque);

        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tanques', 'nombre')
                    ->where('gasolinera_id', $gasolinera->id)
                    ->ignore($tanque->id),
            ],
            'capacidad_total' => ['required', 'numeric', 'gt:0'],
            'volumen_minimo_alerta' => ['required', 'numeric', 'gte:0'],
        ], [
            'nombre.required' => 'Debe ingresar el nombre del tanque.',
            'nombre.unique' => 'Ya existe un tanque con ese nombre para esta gasolinera.',
            'capacidad_total.required' => 'Debe ingresar la capacidad total del tanque.',
            'capacidad_total.gt' => 'La capacidad total del tanque debe ser mayor que cero.',
            'volumen_minimo_alerta.required' => 'Debe ingresar el volumen mínimo de alerta.',
            'volumen_minimo_alerta.gte' => 'El volumen mínimo de alerta no puede ser negativo.',
        ]);

        $capacidadTotal = (float) $validated['capacidad_total'];
        $volumenActual = (float) $tanque->volumen_actual;
        $volumenMinimoAlerta = (float) $validated['volumen_minimo_alerta'];

        if ($capacidadTotal < $volumenActual) {
            return back()
                ->withErrors([
                    'capacidad_total' => 'La capacidad total no puede ser menor que el volumen actual del tanque.',
                ])
                ->withInput();
        }

        if ($volumenMinimoAlerta >= $capacidadTotal) {
            return back()
                ->withErrors([
                    'volumen_minimo_alerta' => 'El volumen mínimo de alerta debe ser menor que la capacidad total del tanque.',
                ])
                ->withInput();
        }

        $tanque->update([
            'nombre' => $validated['nombre'],
            'capacidad_total' => $validated['capacidad_total'],
            'volumen_minimo_alerta' => $validated['volumen_minimo_alerta'],
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('gasolineras.tanques.show.ventana', [$gasolinera, $tanque])
                ->with('success', 'Tanque actualizado correctamente.');
        }

        return redirect()
            ->route('gasolineras.tanques.show', [$gasolinera, $tanque])
            ->with('success', 'Tanque actualizado correctamente.');
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
     * Ensure the gas station belongs to an active company before tank administration.
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
}