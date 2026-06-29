<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmpresaController extends Controller
{
    /**
     * Display the informational company consultation panel.
     */
    public function index(Request $request)
    {
        $data = $this->prepararConsultaEmpresas($request);

        return view('empresas.index', $data);
    }

    /**
     * Display the standalone informational company consultation panel.
     */
    public function consultaVentana(Request $request)
    {
        $data = $this->prepararConsultaEmpresas($request);

        return view('empresas.index-ventana', $data);
    }

    /**
     * Display the administrative company search panel.
     */
    public function administrar(Request $request)
    {
        $data = $this->prepararConsultaEmpresas($request);

        return view('empresas.administrar', $data);
    }

    /**
     * Display the standalone administrative company search panel.
     */
    public function administrarVentana(Request $request)
    {
        $data = $this->prepararConsultaEmpresas($request);

        return view('empresas.administrar-ventana', $data);
    }

    /**
     * Prepare company query data for normal and standalone company screens.
     */
    private function prepararConsultaEmpresas(Request $request): array
    {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);
        $empresaUsuario = $esUsuarioDieselCop
            ? null
            : Empresa::find($user->empresa_id);

        $validated = $request->validate([
            'empresa_id' => ['nullable', 'integer', 'exists:empresas,id'],
            'nit' => [
                'nullable',
                'string',
                'max:17',
                'regex:/^\d{4}-\d{6}-\d{3}-\d{1}$/',
            ],
            'estado' => ['nullable', 'in:activa,inactiva'],
        ], [
            'empresa_id.exists' => 'La empresa seleccionada no es válida.',
            'nit.regex' => 'El NIT debe tener el formato 0000-000000-000-0.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ]);

        $empresaId = $validated['empresa_id'] ?? null;
        $nit = trim((string) ($validated['nit'] ?? ''));
        $estado = $validated['estado'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | Alcance multiempresa
        |--------------------------------------------------------------------------
        |
        | Diesel Cop puede consultar todas o una empresa específica.
        | Un usuario de empresa siempre queda limitado a su propia empresa,
        | sin importar lo que llegue en la URL.
        |
        */

        if (! $esUsuarioDieselCop) {
            $empresaId = $user->empresa_id;
        }

        $hayFiltros = ! $esUsuarioDieselCop
            || $request->hasAny(['empresa_id', 'nit', 'estado']);

        $query = Empresa::query();

        if ($hayFiltros) {
            if ($empresaId) {
                $query->where('id', $empresaId);
            }

            if ($nit !== '') {
                $query->where('nit', $nit);
            }

            if (in_array($estado, ['activa', 'inactiva'], true)) {
                $query->where('estado', $estado);
            }
        } else {
            $query->whereRaw('1 = 0');
        }

        $empresas = $query
            ->orderBy('nombre_legal')
            ->paginate(10)
            ->withQueryString();

        $totalEmpresas = $esUsuarioDieselCop
            ? Empresa::count()
            : Empresa::where('id', $user->empresa_id)->count();

        $empresasActivas = $esUsuarioDieselCop
            ? Empresa::where('estado', 'activa')->count()
            : Empresa::where('id', $user->empresa_id)->where('estado', 'activa')->count();

        $empresasInactivas = $esUsuarioDieselCop
            ? Empresa::where('estado', 'inactiva')->count()
            : Empresa::where('id', $user->empresa_id)->where('estado', 'inactiva')->count();

        $empresasSelector = $esUsuarioDieselCop
            ? Empresa::orderBy('nombre_comercial')
                ->orderBy('nombre_legal')
                ->get()
            : collect([$empresaUsuario])->filter();

        return [
            'empresas' => $empresas,
            'empresasSelector' => $empresasSelector,
            'empresaId' => $empresaId,
            'nit' => $nit,
            'estado' => $estado,
            'hayFiltros' => $hayFiltros,
            'totalEmpresas' => $totalEmpresas,
            'empresasActivas' => $empresasActivas,
            'empresasInactivas' => $empresasInactivas,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'empresaUsuario' => $empresaUsuario,

            /*
             * Variable conservada temporalmente para evitar errores si alguna vista
             * antigua todavía la referencia durante la transición.
             */
            'nombreComercial' => '',
        ];
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('empresas.create');
    }

    /**
     * Show the standalone form for creating a new company.
     */
    public function createVentana()
    {
        return view('empresas.create-ventana');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre_legal' => ['required', 'string', 'max:150'],
            'nombre_comercial' => ['nullable', 'string', 'max:150'],

            'nit' => [
                'required',
                'string',
                'max:17',
                'regex:/^\d{4}-\d{6}-\d{3}-\d{1}$/',
                'unique:empresas,nit',
            ],

            'direccion' => ['nullable', 'string', 'max:255'],

            'telefono_empresa' => [
                'nullable',
                'string',
                'max:9',
                'regex:/^\d{4}-\d{4}$/',
            ],

            'correo_empresa' => ['required', 'email', 'max:150'],
            'poc_nombre' => ['required', 'string', 'max:150'],
            'poc_email' => ['required', 'email', 'max:150'],

            'poc_telefono' => [
                'nullable',
                'string',
                'max:9',
                'regex:/^\d{4}-\d{4}$/',
            ],
        ], [
            'nit.regex' => 'El NIT debe tener el formato 0000-000000-000-0.',
            'telefono_empresa.regex' => 'El teléfono de la empresa debe tener el formato 0000-0000.',
            'poc_telefono.regex' => 'El teléfono del POC debe tener el formato 0000-0000.',
        ]);

        $validated['estado'] = 'activa';
        $validated['fecha_creacion'] = now();
        $validated['creado_por'] = Auth::id();

        Empresa::create($validated);

        return redirect()
            ->route('empresas.index')
            ->with('success', 'Empresa creada correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Empresa $empresa)
    {
        $this->autorizarAccesoEmpresa($empresa);

        return view('empresas.show', compact('empresa'));
    }

    /**
     * Display the specified resource in standalone window.
     */
    public function showVentana(Empresa $empresa)
    {
        $this->autorizarAccesoEmpresa($empresa);

        return view('empresas.show-ventana', compact('empresa'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Empresa $empresa)
    {
        $this->autorizarAccesoEmpresa($empresa);

        return view('empresas.edit', compact('empresa'));
    }

    /**
     * Show the standalone form for editing the specified resource.
     */
    public function editVentana(Empresa $empresa)
    {
        $this->autorizarAccesoEmpresa($empresa);

        return view('empresas.edit-ventana', compact('empresa'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Empresa $empresa)
    {
        $this->autorizarAccesoEmpresa($empresa);

        $validated = $request->validate([
            'nombre_legal' => ['required', 'string', 'max:150'],
            'nombre_comercial' => ['nullable', 'string', 'max:150'],

            'nit' => [
                'required',
                'string',
                'max:17',
                'regex:/^\d{4}-\d{6}-\d{3}-\d{1}$/',
                'unique:empresas,nit,' . $empresa->id,
            ],

            'direccion' => ['nullable', 'string', 'max:255'],

            'telefono_empresa' => [
                'nullable',
                'string',
                'max:9',
                'regex:/^\d{4}-\d{4}$/',
            ],

            'correo_empresa' => ['required', 'email', 'max:150'],
            'poc_nombre' => ['required', 'string', 'max:150'],
            'poc_email' => ['required', 'email', 'max:150'],

            'poc_telefono' => [
                'nullable',
                'string',
                'max:9',
                'regex:/^\d{4}-\d{4}$/',
            ],
        ], [
            'nit.regex' => 'El NIT debe tener el formato 0000-000000-000-0.',
            'telefono_empresa.regex' => 'El teléfono de la empresa debe tener el formato 0000-0000.',
            'poc_telefono.regex' => 'El teléfono del POC debe tener el formato 0000-0000.',
        ]);

        $validated['fecha_actualizacion'] = now();
        $validated['actualizado_por'] = Auth::id();

        $empresa->update($validated);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('empresas.show.ventana', $empresa)
                ->with('success', 'Empresa actualizada correctamente.');
        }

        return redirect()
            ->route('empresas.show', $empresa)
            ->with('success', 'Empresa actualizada correctamente.');
    }

    public function inactivar(Request $request, Empresa $empresa)
    {
        $this->autorizarAccesoEmpresa($empresa);

        $validated = $request->validate([
            'motivo_inactivacion' => [
                'required',
                'string',
                'max:255',
                'in:Falta de pago,No continúa como cliente,Contrato finalizado,Empresa duplicada,Datos incorrectos en registro,Solicitud del cliente,Suspensión administrativa,Otro',
            ],
        ], [
            'motivo_inactivacion.required' => 'Debe seleccionar el motivo de inactivación.',
            'motivo_inactivacion.in' => 'El motivo de inactivación seleccionado no es válido.',
            'motivo_inactivacion.max' => 'El motivo de inactivación no debe exceder 255 caracteres.',
        ]);

        $empresa->update([
            'estado' => 'inactiva',
            'fecha_inactivacion' => now(),
            'inactivado_por' => Auth::id(),
            'motivo_inactivacion' => $validated['motivo_inactivacion'],
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('empresas.show.ventana', $empresa)
                ->with('success', 'Empresa inactivada correctamente.');
        }

        return redirect()
            ->route('empresas.show', $empresa)
            ->with('success', 'Empresa inactivada correctamente.');
    }

    public function reactivar(Request $request, Empresa $empresa)
    {
        $this->autorizarAccesoEmpresa($empresa);

        $empresa->update([
            'estado' => 'activa',
            'fecha_inactivacion' => null,
            'inactivado_por' => null,
            'motivo_inactivacion' => null,
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('empresas.show.ventana', $empresa)
                ->with('success', 'Empresa reactivada correctamente.');
        }

        return redirect()
            ->route('empresas.show', $empresa)
            ->with('success', 'Empresa reactivada correctamente.');
    }

    /**
     * Prevent company users from accessing other companies.
     */
    private function autorizarAccesoEmpresa(Empresa $empresa): void
    {
        $user = Auth::user();

        if (! is_null($user->empresa_id) && (int) $user->empresa_id !== (int) $empresa->id) {
            abort(403, 'No tiene autorización para acceder a esta empresa.');
        }
    }
}