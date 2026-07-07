<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\GasolineraExterna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class GasolineraExternaController extends Controller
{
    /**
     * Display the informational external gas station consultation panel.
     */
    public function index(Request $request)
    {
        $data = $this->prepararConsultaGasolinerasExternas($request);

        return view('gasolineras-externas.index', $data);
    }

    /**
     * Display the standalone informational external gas station consultation panel.
     */
    public function consultaVentana(Request $request)
    {
        $data = $this->prepararConsultaGasolinerasExternas($request);

        return view('gasolineras-externas.index-ventana', $data);
    }

    /**
     * Display the administrative external gas station search panel.
     */
    public function administrar(Request $request)
    {
        $data = $this->prepararConsultaGasolinerasExternas($request);

        return view('gasolineras-externas.administrar', $data);
    }

    /**
     * Display the standalone administrative external gas station search panel.
     */
    public function administrarVentana(Request $request)
    {
        $data = $this->prepararConsultaGasolinerasExternas($request);

        return view('gasolineras-externas.administrar-ventana', $data);
    }

    /**
     * Prepare external gas station query data for normal and standalone screens.
     */
    private function prepararConsultaGasolinerasExternas(Request $request): array
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
            || $request->hasAny(['empresa_id', 'nombre', 'estado', 'consultar']);

        $query = GasolineraExterna::query()
            ->with('empresa');

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

        $gasolinerasExternas = $query
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        $baseResumen = GasolineraExterna::query();

        if (! $esUsuarioDieselCop) {
            $baseResumen->where('empresa_id', $user->empresa_id);
        }

        $totalGasolinerasExternas = (clone $baseResumen)->count();
        $gasolinerasExternasActivas = (clone $baseResumen)->where('estado', 'activa')->count();
        $gasolinerasExternasInactivas = (clone $baseResumen)->where('estado', 'inactiva')->count();

        $empresasSelector = $esUsuarioDieselCop
            ? Empresa::where('estado', 'activa')
                ->orderBy('nombre_comercial')
                ->orderBy('nombre_legal')
                ->get()
            : collect([$empresaUsuario])->filter();

        return [
            'gasolinerasExternas' => $gasolinerasExternas,
            'empresasSelector' => $empresasSelector,
            'empresaId' => $empresaId,
            'nombre' => $nombre,
            'estado' => $estado,
            'hayFiltros' => $hayFiltros,
            'totalGasolinerasExternas' => $totalGasolinerasExternas,
            'gasolinerasExternasActivas' => $gasolinerasExternasActivas,
            'gasolinerasExternasInactivas' => $gasolinerasExternasInactivas,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'empresaUsuario' => $empresaUsuario,
        ];
    }

    /**
     * Show the form for creating a new external gas station.
     */
    public function create()
    {
        $data = $this->prepararFormularioGasolineraExterna();

        return view('gasolineras-externas.create', $data);
    }

    /**
     * Show the standalone form for creating a new external gas station.
     */
    public function createVentana()
    {
        $data = $this->prepararFormularioGasolineraExterna();

        return view('gasolineras-externas.create-ventana', $data);
    }

    /**
     * Prepare common data for create/edit external gas station forms.
     */
    private function prepararFormularioGasolineraExterna(): array
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
     * Store a newly created external gas station.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $esUsuarioDieselCop = is_null($user->empresa_id);

        $baseRules = [
            'nombre' => ['required', 'string', 'max:150'],
            'direccion' => ['required', 'string', 'max:255'],

            /*
             * Campos creados para uso futuro.
             * No serán protagonistas en V1, pero se aceptan si llegan desde el formulario.
             */
            'compania' => ['nullable', 'string', 'max:150'],
            'ciudad' => ['nullable', 'string', 'max:100'],
            'departamento' => ['nullable', 'string', 'max:100'],
            'telefono' => [
                'nullable',
                'string',
                'max:9',
                'regex:/^\d{4}-\d{4}$/',
            ],
            'correo' => ['nullable', 'email', 'max:150'],
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
            'nombre.required' => 'Debe ingresar el nombre de la gasolinera externa.',
            'direccion.required' => 'Debe ingresar la dirección de la gasolinera externa.',
            'telefono.regex' => 'El teléfono debe tener el formato 0000-0000.',
            'correo.email' => 'Debe ingresar un correo válido.',
        ]);

        $empresaId = $esUsuarioDieselCop
            ? (int) $validated['empresa_id']
            : (int) $user->empresa_id;

        $request->validate([
            'nombre' => [
                Rule::unique('gasolineras_externas', 'nombre')
                    ->where('empresa_id', $empresaId),
            ],
        ], [
            'nombre.unique' => 'Ya existe una gasolinera externa con ese nombre para la empresa seleccionada.',
        ]);

        GasolineraExterna::create([
            'empresa_id' => $empresaId,
            'nombre' => $validated['nombre'],
            'direccion' => $validated['direccion'],
            'compania' => $validated['compania'] ?? null,
            'ciudad' => $validated['ciudad'] ?? null,
            'departamento' => $validated['departamento'] ?? null,
            'telefono' => $validated['telefono'] ?? null,
            'correo' => $validated['correo'] ?? null,
            'estado' => 'activa',
            'fecha_creacion' => now(),
            'creado_por' => Auth::id(),
        ]);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('gasolineras-externas.create.ventana')
                ->with('success', 'Gasolinera externa guardada correctamente.');
        }

        return redirect()
            ->route('gasolineras-externas.create')
            ->with('success', 'Gasolinera externa guardada correctamente.');
    }

    /**
     * Display the specified external gas station.
     */
    public function show(GasolineraExterna $gasolineraExterna)
    {
        $this->autorizarAccesoGasolineraExterna($gasolineraExterna);

        $gasolineraExterna->load([
            'empresa',
            'creadoPor',
            'actualizadoPor',
            'inactivadoPor',
        ]);

        return view('gasolineras-externas.show', compact('gasolineraExterna'));
    }

    /**
     * Display the specified external gas station in standalone window.
     */
    public function showVentana(GasolineraExterna $gasolineraExterna)
    {
        $this->autorizarAccesoGasolineraExterna($gasolineraExterna);

        $gasolineraExterna->load([
            'empresa',
            'creadoPor',
            'actualizadoPor',
            'inactivadoPor',
        ]);

        return view('gasolineras-externas.show-ventana', compact('gasolineraExterna'));
    }

    /**
     * Show the form for editing the specified external gas station.
     */
    public function edit(GasolineraExterna $gasolineraExterna)
    {
        $this->autorizarAccesoGasolineraExterna($gasolineraExterna);

        $data = $this->prepararFormularioGasolineraExterna();
        $data['gasolineraExterna'] = $gasolineraExterna;

        return view('gasolineras-externas.edit', $data);
    }

    /**
     * Show the standalone form for editing the specified external gas station.
     */
    public function editVentana(GasolineraExterna $gasolineraExterna)
    {
        $this->autorizarAccesoGasolineraExterna($gasolineraExterna);

        $data = $this->prepararFormularioGasolineraExterna();
        $data['gasolineraExterna'] = $gasolineraExterna;

        return view('gasolineras-externas.edit-ventana', $data);
    }

    /**
     * Update the specified external gas station.
     */
    public function update(Request $request, GasolineraExterna $gasolineraExterna)
    {
        $this->autorizarAccesoGasolineraExterna($gasolineraExterna);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'direccion' => ['required', 'string', 'max:255'],

            /*
             * Campos creados para uso futuro.
             */
            'compania' => ['nullable', 'string', 'max:150'],
            'ciudad' => ['nullable', 'string', 'max:100'],
            'departamento' => ['nullable', 'string', 'max:100'],
            'telefono' => [
                'nullable',
                'string',
                'max:9',
                'regex:/^\d{4}-\d{4}$/',
            ],
            'correo' => ['nullable', 'email', 'max:150'],
        ], [
            'nombre.required' => 'Debe ingresar el nombre de la gasolinera externa.',
            'direccion.required' => 'Debe ingresar la dirección de la gasolinera externa.',
            'telefono.regex' => 'El teléfono debe tener el formato 0000-0000.',
            'correo.email' => 'Debe ingresar un correo válido.',
        ]);

        $empresaId = (int) $gasolineraExterna->empresa_id;

        $request->validate([
            'nombre' => [
                Rule::unique('gasolineras_externas', 'nombre')
                    ->where('empresa_id', $empresaId)
                    ->ignore($gasolineraExterna->id),
            ],
        ], [
            'nombre.unique' => 'Ya existe una gasolinera externa con ese nombre para la empresa actual.',
        ]);

        $gasolineraExterna->update([
            'nombre' => $validated['nombre'],
            'direccion' => $validated['direccion'],
            'compania' => $validated['compania'] ?? null,
            'ciudad' => $validated['ciudad'] ?? null,
            'departamento' => $validated['departamento'] ?? null,
            'telefono' => $validated['telefono'] ?? null,
            'correo' => $validated['correo'] ?? null,
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('gasolineras-externas.show.ventana', $gasolineraExterna)
                ->with('success', 'Gasolinera externa actualizada correctamente.');
        }

        return redirect()
            ->route('gasolineras-externas.show', $gasolineraExterna)
            ->with('success', 'Gasolinera externa actualizada correctamente.');
    }

    /**
     * Deactivate an external gas station.
     */
    public function inactivar(Request $request, GasolineraExterna $gasolineraExterna)
    {
        $this->autorizarAccesoGasolineraExterna($gasolineraExterna);

        $validated = $request->validate([
            'motivo_inactivacion' => [
                'required',
                'string',
                'max:255',
                'in:No continúa en uso,Cambio de proveedor,Cierre de estación,Datos incorrectos en registro,Solicitud del cliente,Suspensión administrativa,Otro',
            ],
        ], [
            'motivo_inactivacion.required' => 'Debe seleccionar el motivo de inactivación.',
            'motivo_inactivacion.in' => 'El motivo de inactivación seleccionado no es válido.',
            'motivo_inactivacion.max' => 'El motivo de inactivación no debe exceder 255 caracteres.',
        ]);

        $gasolineraExterna->update([
            'estado' => 'inactiva',
            'fecha_inactivacion' => now(),
            'inactivado_por' => Auth::id(),
            'motivo_inactivacion' => $validated['motivo_inactivacion'],
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('gasolineras-externas.show.ventana', $gasolineraExterna)
                ->with('success', 'Gasolinera externa inactivada correctamente.');
        }

        return redirect()
            ->route('gasolineras-externas.show', $gasolineraExterna)
            ->with('success', 'Gasolinera externa inactivada correctamente.');
    }

    /**
     * Reactivate an external gas station.
     */
    public function reactivar(Request $request, GasolineraExterna $gasolineraExterna)
    {
        $this->autorizarAccesoGasolineraExterna($gasolineraExterna);

        $gasolineraExterna->update([
            'estado' => 'activa',
            'fecha_inactivacion' => null,
            'inactivado_por' => null,
            'motivo_inactivacion' => null,
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('gasolineras-externas.show.ventana', $gasolineraExterna)
                ->with('success', 'Gasolinera externa reactivada correctamente.');
        }

        return redirect()
            ->route('gasolineras-externas.show', $gasolineraExterna)
            ->with('success', 'Gasolinera externa reactivada correctamente.');
    }

    /**
     * Prevent company users from accessing other companies' external gas stations.
     */
    private function autorizarAccesoGasolineraExterna(GasolineraExterna $gasolineraExterna): void
    {
        $user = Auth::user();

        if (! is_null($user->empresa_id) && (int) $user->empresa_id !== (int) $gasolineraExterna->empresa_id) {
            abort(403, 'No tiene autorización para acceder a esta gasolinera externa.');
        }
    }
}