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
        $data = $this->prepararConsultaGasolineras($request);

        return view('gasolineras.index', $data);
    }

    /**
     * Display the standalone informational gas station consultation panel.
     */
    public function consultaVentana(Request $request)
    {
        $data = $this->prepararConsultaGasolineras($request);

        return view('gasolineras.index-ventana', $data);
    }

    /**
     * Display the administrative gas station search panel.
     */
    public function administrar(Request $request)
    {
        $data = $this->prepararConsultaGasolineras($request);

        return view('gasolineras.administrar', $data);
    }

    /**
     * Display the standalone administrative gas station search panel.
     */
    public function administrarVentana(Request $request)
    {
        $data = $this->prepararConsultaGasolineras($request);

        return view('gasolineras.administrar-ventana', $data);
    }

    /**
     * Prepare gas station query data for normal and standalone screens.
     */
    private function prepararConsultaGasolineras(Request $request): array
    {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);
        $empresaUsuario = $esUsuarioDieselCop
            ? null
            : Empresa::find($user->empresa_id);

        $validated = $request->validate([
            'empresa_id' => ['nullable', 'integer', 'exists:empresas,id'],
            'nombre' => ['nullable', 'string', 'max:150'],
            'estado' => ['nullable', 'in:activa,inactiva'],
        ], [
            'empresa_id.exists' => 'La empresa seleccionada no es válida.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ]);

        $empresaId = $validated['empresa_id'] ?? null;
        $nombre = trim((string) ($validated['nombre'] ?? ''));
        $estado = $validated['estado'] ?? null;

        if (! $esUsuarioDieselCop) {
            $empresaId = $user->empresa_id;
        }

        $hayFiltros = ! $esUsuarioDieselCop
            || $request->hasAny(['empresa_id', 'nombre', 'estado']);

        $query = Gasolinera::query()
            ->with([
                'empresa',
                'tanques' => function ($query) {
                    $query->orderBy('nombre');
                },
            ])
            ->withCount([
                'tanques',
                'tanques as tanques_activos_count' => function ($query) {
                    $query->where('estado', 'activo');
                },
            ]);

        if ($hayFiltros) {
            if ($empresaId) {
                $query->where('empresa_id', $empresaId);
            }

            if ($nombre !== '') {
                $query->where('nombre', 'like', '%' . $nombre . '%');
            }

            if (in_array($estado, ['activa', 'inactiva'], true)) {
                $query->where('estado', $estado);
            }
        } else {
            $query->whereRaw('1 = 0');
        }

        $gasolineras = $query
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        $baseResumen = Gasolinera::query();

        if (! $esUsuarioDieselCop) {
            $baseResumen->where('empresa_id', $user->empresa_id);
        }

        $totalGasolineras = (clone $baseResumen)->count();
        $gasolinerasActivas = (clone $baseResumen)->where('estado', 'activa')->count();
        $gasolinerasInactivas = (clone $baseResumen)->where('estado', 'inactiva')->count();

        $empresasSelector = $esUsuarioDieselCop
            ? Empresa::where('estado', 'activa')
                ->orderBy('nombre_comercial')
                ->orderBy('nombre_legal')
                ->get()
            : collect([$empresaUsuario])->filter();

        return [
            'gasolineras' => $gasolineras,
            'empresasSelector' => $empresasSelector,
            'empresaId' => $empresaId,
            'nombre' => $nombre,
            'estado' => $estado,
            'hayFiltros' => $hayFiltros,
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

        return view('gasolineras.create', $data);
    }

    /**
     * Show the standalone form for creating a new gas station.
     */
    public function createVentana()
    {
        $data = $this->prepararFormularioGasolinera();

        return view('gasolineras.create-ventana', $data);
    }

    /**
     * Prepare common data for create/edit gas station forms.
     */
    private function prepararFormularioGasolinera(): array
    {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);
        $empresaUsuario = $esUsuarioDieselCop
            ? null
            : Empresa::find($user->empresa_id);

        $empresasSelector = $esUsuarioDieselCop
            ? Empresa::where('estado', 'activa')
                ->orderBy('nombre_comercial')
                ->orderBy('nombre_legal')
                ->get()
            : collect([$empresaUsuario])->filter();

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
        $esUsuarioDieselCop = is_null($user->empresa_id);

        $baseRules = [
            'nombre' => ['required', 'string', 'max:150'],
            'direccion' => ['required', 'string', 'max:255'],
            'encargado' => ['nullable', 'string', 'max:150'],
            'telefono' => [
                'nullable',
                'string',
                'max:9',
                'regex:/^\d{4}-\d{4}$/',
            ],
            'correo' => ['nullable', 'email', 'max:150'],

            'tanques' => ['required', 'array', 'min:1'],
            'tanques.*.nombre' => ['required', 'string', 'max:100', 'distinct'],
            'tanques.*.capacidad_total' => ['required', 'numeric', 'gt:0'],
            'tanques.*.volumen_actual' => ['required', 'numeric', 'gte:0'],
            'tanques.*.volumen_minimo_alerta' => ['required', 'numeric', 'gte:0'],
        ];

        if ($esUsuarioDieselCop) {
            $baseRules['empresa_id'] = [
                'required',
                'integer',
                Rule::exists('empresas', 'id')->where('estado', 'activa'),
            ];
        } else {
            $baseRules['empresa_id'] = ['nullable'];
        }

        $validated = $request->validate($baseRules, [
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
        ]);

        $empresaId = $esUsuarioDieselCop
            ? (int) $validated['empresa_id']
            : (int) $user->empresa_id;

        $request->validate([
            'nombre' => [
                Rule::unique('gasolineras', 'nombre')
                    ->where('empresa_id', $empresaId),
            ],
        ], [
            'nombre.unique' => 'Ya existe una gasolinera con ese nombre para la empresa seleccionada.',
        ]);

        foreach ($validated['tanques'] as $index => $tanqueData) {
            $capacidadTotal = (float) $tanqueData['capacidad_total'];
            $volumenActual = (float) $tanqueData['volumen_actual'];
            $volumenMinimoAlerta = (float) $tanqueData['volumen_minimo_alerta'];

            if ($volumenActual > $capacidadTotal) {
                return back()
                    ->withErrors([
                        "tanques.$index.volumen_actual" => 'El volumen actual no puede superar la capacidad total del tanque.',
                    ])
                    ->withInput();
            }

            if ($volumenMinimoAlerta >= $capacidadTotal) {
                return back()
                    ->withErrors([
                        "tanques.$index.volumen_minimo_alerta" => 'El volumen mínimo de alerta debe ser menor que la capacidad total del tanque.',
                    ])
                    ->withInput();
            }
        }

        DB::transaction(function () use ($validated, $empresaId) {
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

            foreach ($validated['tanques'] as $tanqueData) {
                $volumenActual = (float) $tanqueData['volumen_actual'];

                $tanque = Tanque::create([
                    'gasolinera_id' => $gasolinera->id,
                    'nombre' => $tanqueData['nombre'],
                    'capacidad_total' => $tanqueData['capacidad_total'],
                    'volumen_actual' => $tanqueData['volumen_actual'],
                    'volumen_minimo_alerta' => $tanqueData['volumen_minimo_alerta'],
                    'estado' => 'activo',
                    'inactivado_por_gasolinera' => false,
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
        });

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('gasolineras.create.ventana')
                ->with('success', 'Gasolinera guardada correctamente.');
        }

        return redirect()
            ->route('gasolineras.create')
            ->with('success', 'Gasolinera guardada correctamente.');
    }

    /**
     * Display the specified gas station.
     */
    public function show(Gasolinera $gasolinera)
    {
        $this->autorizarAccesoGasolinera($gasolinera);

        $gasolinera->load([
            'empresa',
            'tanques' => function ($query) {
                $query->orderBy('nombre');
            },
            'tanques.movimientosInventario' => function ($query) {
                $query->where('estado', 'registrado')
                    ->latest('fecha_hora_movimiento')
                    ->limit(5);
            },
        ]);

        return view('gasolineras.show', $this->prepararFichaGasolinera($gasolinera));
    }

    /**
     * Display the specified gas station in standalone window.
     */
    public function showVentana(Gasolinera $gasolinera)
    {
        $this->autorizarAccesoGasolinera($gasolinera);

        $gasolinera->load([
            'empresa',
            'tanques' => function ($query) {
                $query->orderBy('nombre');
            },
            'tanques.movimientosInventario' => function ($query) {
                $query->where('estado', 'registrado')
                    ->latest('fecha_hora_movimiento')
                    ->limit(5);
            },
        ]);

        return view('gasolineras.show-ventana', $this->prepararFichaGasolinera($gasolinera));
    }

    /**
     * Prepare summary data for gas station detail screens.
     */
    private function prepararFichaGasolinera(Gasolinera $gasolinera): array
    {
        $tanques = $gasolinera->tanques;

        $capacidadTotal = $tanques->sum(fn ($tanque) => (float) $tanque->capacidad_total);
        $volumenActual = $tanques->sum(fn ($tanque) => (float) $tanque->volumen_actual);
        $volumenMinimoAlerta = $tanques->sum(fn ($tanque) => (float) $tanque->volumen_minimo_alerta);

        $porcentajeDisponible = $capacidadTotal > 0
            ? round(($volumenActual / $capacidadTotal) * 100, 2)
            : 0;

        $tanquesBajoAlerta = $tanques->filter(fn ($tanque) => $tanque->estaBajoAlerta())->count();

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
        $this->autorizarAccesoGasolinera($gasolinera);

        $data = $this->prepararFormularioGasolinera();
        $data['gasolinera'] = $gasolinera;

        return view('gasolineras.edit', $data);
    }

    /**
     * Show the standalone form for editing the specified gas station.
     */
    public function editVentana(Gasolinera $gasolinera)
    {
        $this->autorizarAccesoGasolinera($gasolinera);

        $data = $this->prepararFormularioGasolinera();
        $data['gasolinera'] = $gasolinera;

        return view('gasolineras.edit-ventana', $data);
    }

    /**
     * Update the specified gas station general data.
     */
    public function update(Request $request, Gasolinera $gasolinera)
    {
        $this->autorizarAccesoGasolinera($gasolinera);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'direccion' => ['required', 'string', 'max:255'],
            'encargado' => ['nullable', 'string', 'max:150'],
            'telefono' => [
                'nullable',
                'string',
                'max:9',
                'regex:/^\d{4}-\d{4}$/',
            ],
            'correo' => ['nullable', 'email', 'max:150'],
        ], [
            'nombre.required' => 'Debe ingresar el nombre de la gasolinera.',
            'direccion.required' => 'Debe ingresar la dirección de la gasolinera.',
            'telefono.regex' => 'El teléfono debe tener el formato 0000-0000.',
            'correo.email' => 'Debe ingresar un correo válido.',
        ]);

        $empresaId = (int) $gasolinera->empresa_id;

        $request->validate([
            'nombre' => [
                Rule::unique('gasolineras', 'nombre')
                    ->where('empresa_id', $empresaId)
                    ->ignore($gasolinera->id),
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

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('gasolineras.show.ventana', $gasolinera)
                ->with('success', 'Gasolinera actualizada correctamente.');
        }

        return redirect()
            ->route('gasolineras.show', $gasolinera)
            ->with('success', 'Gasolinera actualizada correctamente.');
    }

    /**
     * Deactivate a gas station and cascade deactivation to active tanks.
     */
    public function inactivar(Request $request, Gasolinera $gasolinera)
    {
        $this->autorizarAccesoGasolinera($gasolinera);

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

        DB::transaction(function () use ($gasolinera, $validated) {
            $gasolinera->update([
                'estado' => 'inactiva',
                'fecha_inactivacion' => now(),
                'inactivado_por' => Auth::id(),
                'motivo_inactivacion' => $validated['motivo_inactivacion'],
                'fecha_actualizacion' => now(),
                'actualizado_por' => Auth::id(),
            ]);

            $gasolinera->tanques()
                ->where('estado', 'activo')
                ->update([
                    'estado' => 'inactivo',
                    'inactivado_por_gasolinera' => true,
                    'fecha_inactivacion' => now(),
                    'inactivado_por' => Auth::id(),
                    'motivo_inactivacion' => 'Gasolinera inactivada: ' . $validated['motivo_inactivacion'],
                    'fecha_actualizacion' => now(),
                    'actualizado_por' => Auth::id(),
                ]);
        });

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('gasolineras.show.ventana', $gasolinera)
                ->with('success', 'Gasolinera inactivada correctamente. Los tanques activos fueron inactivados por cascada.');
        }

        return redirect()
            ->route('gasolineras.show', $gasolinera)
            ->with('success', 'Gasolinera inactivada correctamente. Los tanques activos fueron inactivados por cascada.');
    }

    /**
     * Reactivate a gas station and tanks previously deactivated by cascade.
     */
    public function reactivar(Request $request, Gasolinera $gasolinera)
    {
        $this->autorizarAccesoGasolinera($gasolinera);

        DB::transaction(function () use ($gasolinera) {
            $gasolinera->update([
                'estado' => 'activa',
                'fecha_inactivacion' => null,
                'inactivado_por' => null,
                'motivo_inactivacion' => null,
                'fecha_actualizacion' => now(),
                'actualizado_por' => Auth::id(),
            ]);

            $gasolinera->tanques()
                ->where('estado', 'inactivo')
                ->where('inactivado_por_gasolinera', true)
                ->update([
                    'estado' => 'activo',
                    'inactivado_por_gasolinera' => false,
                    'fecha_inactivacion' => null,
                    'inactivado_por' => null,
                    'motivo_inactivacion' => null,
                    'fecha_actualizacion' => now(),
                    'actualizado_por' => Auth::id(),
                ]);
        });

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('gasolineras.show.ventana', $gasolinera)
                ->with('success', 'Gasolinera reactivada correctamente. Los tanques inactivados por cascada fueron reactivados.');
        }

        return redirect()
            ->route('gasolineras.show', $gasolinera)
            ->with('success', 'Gasolinera reactivada correctamente. Los tanques inactivados por cascada fueron reactivados.');
    }

    /**
     * Show the tank recharge form.
     */
    public function recargarTanque(Gasolinera $gasolinera, Tanque $tanque)
    {
        $this->autorizarAccesoGasolinera($gasolinera);
        $this->validarTanquePerteneceGasolinera($gasolinera, $tanque);

        return view('gasolineras.recarga', compact('gasolinera', 'tanque'));
    }

    /**
     * Show the standalone tank recharge form.
     */
    public function recargarTanqueVentana(Gasolinera $gasolinera, Tanque $tanque)
    {
        $this->autorizarAccesoGasolinera($gasolinera);
        $this->validarTanquePerteneceGasolinera($gasolinera, $tanque);

        return view('gasolineras.recarga-ventana', compact('gasolinera', 'tanque'));
    }

    /**
     * Store a tank recharge movement.
     */
    public function guardarRecargaTanque(Request $request, Gasolinera $gasolinera, Tanque $tanque)
    {
        $this->autorizarAccesoGasolinera($gasolinera);
        $this->validarTanquePerteneceGasolinera($gasolinera, $tanque);

        if ($gasolinera->estado !== 'activa') {
            return back()
                ->withErrors(['gasolinera' => 'No se puede recargar un tanque de una gasolinera inactiva.'])
                ->withInput();
        }

        if ($tanque->estado !== 'activo') {
            return back()
                ->withErrors(['tanque' => 'No se puede recargar un tanque inactivo.'])
                ->withInput();
        }

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
                ->route('gasolineras.show.ventana', $gasolinera)
                ->with('success', 'Recarga de tanque registrada correctamente.');
        }

        return redirect()
            ->route('gasolineras.show', $gasolinera)
            ->with('success', 'Recarga de tanque registrada correctamente.');
    }

    /**
     * Store an additional tank for an existing gas station.
     */
    public function storeTanque(Request $request, Gasolinera $gasolinera)
    {
        $this->autorizarAccesoGasolinera($gasolinera);

        if ($gasolinera->estado !== 'activa') {
            return back()
                ->withErrors(['gasolinera' => 'No se puede agregar un tanque a una gasolinera inactiva.'])
                ->withInput();
        }

        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tanques', 'nombre')->where('gasolinera_id', $gasolinera->id),
            ],
            'capacidad_total' => ['required', 'numeric', 'gt:0'],
            'volumen_actual' => ['required', 'numeric', 'gte:0'],
            'volumen_minimo_alerta' => ['required', 'numeric', 'gte:0'],
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

        if ($volumenActual > $capacidadTotal) {
            return back()
                ->withErrors(['volumen_actual' => 'El volumen actual no puede superar la capacidad total del tanque.'])
                ->withInput();
        }

        if ($volumenMinimoAlerta >= $capacidadTotal) {
            return back()
                ->withErrors(['volumen_minimo_alerta' => 'El volumen mínimo de alerta debe ser menor que la capacidad total del tanque.'])
                ->withInput();
        }

        DB::transaction(function () use ($gasolinera, $validated, $volumenActual) {
            $tanque = Tanque::create([
                'gasolinera_id' => $gasolinera->id,
                'nombre' => $validated['nombre'],
                'capacidad_total' => $validated['capacidad_total'],
                'volumen_actual' => $validated['volumen_actual'],
                'volumen_minimo_alerta' => $validated['volumen_minimo_alerta'],
                'estado' => 'activo',
                'inactivado_por_gasolinera' => false,
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
        });

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('gasolineras.show.ventana', $gasolinera)
                ->with('success', 'Tanque agregado correctamente.');
        }

        return redirect()
            ->route('gasolineras.show', $gasolinera)
            ->with('success', 'Tanque agregado correctamente.');
    }

    /**
     * Deactivate an individual tank.
     */
    public function inactivarTanque(Request $request, Gasolinera $gasolinera, Tanque $tanque)
    {
        $this->autorizarAccesoGasolinera($gasolinera);
        $this->validarTanquePerteneceGasolinera($gasolinera, $tanque);

        $validated = $request->validate([
            'motivo_inactivacion' => [
                'required',
                'string',
                'max:255',
                'in:Mantenimiento,Daño operativo,Fuera de servicio,Datos incorrectos en registro,Solicitud del cliente,Otro',
            ],
        ], [
            'motivo_inactivacion.required' => 'Debe seleccionar el motivo de inactivación.',
            'motivo_inactivacion.in' => 'El motivo de inactivación seleccionado no es válido.',
            'motivo_inactivacion.max' => 'El motivo de inactivación no debe exceder 255 caracteres.',
        ]);

        $tanque->update([
            'estado' => 'inactivo',
            'inactivado_por_gasolinera' => false,
            'fecha_inactivacion' => now(),
            'inactivado_por' => Auth::id(),
            'motivo_inactivacion' => $validated['motivo_inactivacion'],
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('gasolineras.tanques.show.ventana', [$gasolinera, $tanque])
                ->with('success', 'Tanque inactivado correctamente.');
        }

        return redirect()
            ->route('gasolineras.tanques.show', [$gasolinera, $tanque])
            ->with('success', 'Tanque inactivado correctamente.');
    }

    /**
     * Reactivate an individual tank.
     */
    public function reactivarTanque(Request $request, Gasolinera $gasolinera, Tanque $tanque)
    {
        $this->autorizarAccesoGasolinera($gasolinera);
        $this->validarTanquePerteneceGasolinera($gasolinera, $tanque);

        if ($gasolinera->estado !== 'activa') {
            return back()
                ->withErrors(['gasolinera' => 'No se puede reactivar un tanque si la gasolinera está inactiva.'])
                ->withInput();
        }

        $tanque->update([
            'estado' => 'activo',
            'inactivado_por_gasolinera' => false,
            'fecha_inactivacion' => null,
            'inactivado_por' => null,
            'motivo_inactivacion' => null,
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('gasolineras.tanques.show.ventana', [$gasolinera, $tanque])
                ->with('success', 'Tanque reactivado correctamente.');
        }

        return redirect()
            ->route('gasolineras.tanques.show', [$gasolinera, $tanque])
            ->with('success', 'Tanque reactivado correctamente.');
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
}