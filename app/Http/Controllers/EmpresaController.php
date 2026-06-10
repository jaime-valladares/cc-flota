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
        $validated = $request->validate([
            'nombre_comercial' => ['nullable', 'string', 'max:150'],
            'nit' => [
                'nullable',
                'string',
                'max:17',
                'regex:/^\d{4}-\d{6}-\d{3}-\d{1}$/',
            ],
            'estado' => ['nullable', 'in:activa,inactiva'],
        ], [
            'nit.regex' => 'El NIT debe tener el formato 0000-000000-000-0.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ]);

        $nombreComercial = trim((string) ($validated['nombre_comercial'] ?? ''));
        $nit = trim((string) ($validated['nit'] ?? ''));
        $estado = $validated['estado'] ?? null;

        $hayFiltros = $nombreComercial !== ''
            || $nit !== ''
            || in_array($estado, ['activa', 'inactiva'], true);

        $query = Empresa::query();

        if ($hayFiltros) {
            if ($nombreComercial !== '') {
                $query->where('nombre_comercial', 'like', '%' . $nombreComercial . '%');
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

        $totalEmpresas = Empresa::count();
        $empresasActivas = Empresa::where('estado', 'activa')->count();
        $empresasInactivas = Empresa::where('estado', 'inactiva')->count();

        return view('empresas.index', compact(
            'empresas',
            'nombreComercial',
            'nit',
            'estado',
            'hayFiltros',
            'totalEmpresas',
            'empresasActivas',
            'empresasInactivas'
        ));
    }

    /**
     * Display the standalone informational company consultation panel.
     */
    public function consultaVentana(Request $request)
    {
        $validated = $request->validate([
            'nombre_comercial' => ['nullable', 'string', 'max:150'],
            'nit' => [
                'nullable',
                'string',
                'max:17',
                'regex:/^\d{4}-\d{6}-\d{3}-\d{1}$/',
            ],
            'estado' => ['nullable', 'in:activa,inactiva'],
        ], [
            'nit.regex' => 'El NIT debe tener el formato 0000-000000-000-0.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ]);

        $nombreComercial = trim((string) ($validated['nombre_comercial'] ?? ''));
        $nit = trim((string) ($validated['nit'] ?? ''));
        $estado = $validated['estado'] ?? null;

        $hayFiltros = $nombreComercial !== ''
            || $nit !== ''
            || in_array($estado, ['activa', 'inactiva'], true);

        $query = Empresa::query();

        if ($hayFiltros) {
            if ($nombreComercial !== '') {
                $query->where('nombre_comercial', 'like', '%' . $nombreComercial . '%');
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

        $totalEmpresas = Empresa::count();
        $empresasActivas = Empresa::where('estado', 'activa')->count();
        $empresasInactivas = Empresa::where('estado', 'inactiva')->count();

        return view('empresas.index-ventana', compact(
            'empresas',
            'nombreComercial',
            'nit',
            'estado',
            'hayFiltros',
            'totalEmpresas',
            'empresasActivas',
            'empresasInactivas'
        ));
    }

    /**
     * Display the administrative company search panel.
     */
    public function administrar(Request $request)
    {
        $validated = $request->validate([
            'nombre_comercial' => ['nullable', 'string', 'max:150'],
            'nit' => [
                'nullable',
                'string',
                'max:17',
                'regex:/^\d{4}-\d{6}-\d{3}-\d{1}$/',
            ],
            'estado' => ['nullable', 'in:activa,inactiva'],
        ], [
            'nit.regex' => 'El NIT debe tener el formato 0000-000000-000-0.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ]);

        $nombreComercial = trim((string) ($validated['nombre_comercial'] ?? ''));
        $nit = trim((string) ($validated['nit'] ?? ''));
        $estado = $validated['estado'] ?? null;

        $hayFiltros = $nombreComercial !== ''
            || $nit !== ''
            || in_array($estado, ['activa', 'inactiva'], true);

        $query = Empresa::query();

        if ($hayFiltros) {
            if ($nombreComercial !== '') {
                $query->where('nombre_comercial', 'like', '%' . $nombreComercial . '%');
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

        $totalEmpresas = Empresa::count();
        $empresasActivas = Empresa::where('estado', 'activa')->count();
        $empresasInactivas = Empresa::where('estado', 'inactiva')->count();

        return view('empresas.administrar', compact(
            'empresas',
            'nombreComercial',
            'nit',
            'estado',
            'hayFiltros',
            'totalEmpresas',
            'empresasActivas',
            'empresasInactivas'
        ));
    }

    /**
     * Display the standalone administrative company search panel.
     */
    public function administrarVentana(Request $request)
    {
        $validated = $request->validate([
            'nombre_comercial' => ['nullable', 'string', 'max:150'],
            'nit' => [
                'nullable',
                'string',
                'max:17',
                'regex:/^\d{4}-\d{6}-\d{3}-\d{1}$/',
            ],
            'estado' => ['nullable', 'in:activa,inactiva'],
        ], [
            'nit.regex' => 'El NIT debe tener el formato 0000-000000-000-0.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ]);

        $nombreComercial = trim((string) ($validated['nombre_comercial'] ?? ''));
        $nit = trim((string) ($validated['nit'] ?? ''));
        $estado = $validated['estado'] ?? null;

        $hayFiltros = $nombreComercial !== ''
            || $nit !== ''
            || in_array($estado, ['activa', 'inactiva'], true);

        $query = Empresa::query();

        if ($hayFiltros) {
            if ($nombreComercial !== '') {
                $query->where('nombre_comercial', 'like', '%' . $nombreComercial . '%');
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

        return view('empresas.administrar-ventana', compact(
            'empresas',
            'nombreComercial',
            'nit',
            'estado',
            'hayFiltros'
        ));
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
        return view('empresas.show', compact('empresa'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Empresa $empresa)
    {
        return view('empresas.edit', compact('empresa'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Empresa $empresa)
    {
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

        return redirect()
            ->route('empresas.show', $empresa)
            ->with('success', 'Empresa actualizada correctamente.');
    }

    public function inactivar(Request $request, Empresa $empresa)
    {
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

        return redirect()
            ->route('empresas.show', $empresa)
            ->with('success', 'Empresa inactivada correctamente.');
    }

    public function reactivar(Empresa $empresa)
    {
        $empresa->update([
            'estado' => 'activa',
            'fecha_inactivacion' => null,
            'inactivado_por' => null,
            'motivo_inactivacion' => null,
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        return redirect()
            ->route('empresas.show', $empresa)
            ->with('success', 'Empresa reactivada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}