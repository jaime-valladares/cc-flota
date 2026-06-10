<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmpresaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $empresas = Empresa::orderBy('nombre_legal')->get();

        return view('empresas.index', compact('empresas'));

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('empresas.create');
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
    public function show(string $id)
    {
        //
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
            ->route('empresas.index')
            ->with('success', 'Empresa actualizada correctamente.');
    }


    public function inactivar(Request $request, Empresa $empresa)
    {
        $validated = $request->validate([
            'motivo_inactivacion' => ['required', 'string', 'max:255'],
        ], [
            'motivo_inactivacion.required' => 'Debe ingresar el motivo de inactivación.',
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
            ->route('empresas.index')
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
            ->route('empresas.index')
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
