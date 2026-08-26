<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EmpresaController extends Controller
{
    /**
     * Muestra la consulta informativa de empresas.
     */
    public function index(Request $request)
    {
        $data = $this->prepararConsultaEmpresas($request);

        return view('empresas.index', $data);
    }

    /**
     * Muestra la consulta informativa de empresas en ventana independiente.
     */
    public function consultaVentana(Request $request)
    {
        $data = $this->prepararConsultaEmpresas($request);

        return view('empresas.index-ventana', $data);
    }

    /**
     * Muestra la administración de empresas.
     */
    public function administrar(Request $request)
    {
        $data = $this->prepararConsultaEmpresas(
            $request,
            true
        );

        return view('empresas.administrar', $data);
    }

    /**
     * Muestra la administración de empresas en ventana independiente.
     */
    public function administrarVentana(Request $request)
    {
        $data = $this->prepararConsultaEmpresas(
            $request,
            true
        );

        return view('empresas.administrar-ventana', $data);
    }

    /**
     * Prepara los datos de consulta y administración de empresas.
     */
    private function prepararConsultaEmpresas(
        Request $request,
        bool $modoAdministracion = false
    ): array {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);

        $empresaUsuario = $esUsuarioDieselCop
            ? null
            : Empresa::find($user->empresa_id);

        $validated = $request->validate([
            'consultar' => ['nullable', 'boolean'],

            /*
             * busqueda_empresa será el nombre estándar.
             * busqueda se conserva temporalmente para no romper enlaces anteriores.
             */
            'busqueda_empresa' => ['nullable', 'string', 'max:150'],
            'busqueda' => ['nullable', 'string', 'max:150'],

            /*
             * empresa_ids es el filtro estándar de selección múltiple.
             * empresa_id se conserva temporalmente por compatibilidad.
             */
            'empresa_ids' => ['nullable', 'array'],
            'empresa_ids.*' => [
                'nullable',
                'integer',
                'exists:empresas,id',
            ],

            'empresa_id' => [
                'nullable',
                'integer',
                'exists:empresas,id',
            ],

            /*
             * Se conserva en backend por compatibilidad con enlaces o pantallas
             * anteriores, aunque no necesariamente permanezca visible.
             */
            'nit' => [
                'nullable',
                'string',
                'max:17',
                'regex:/^\d{4}-\d{6}-\d{3}-\d{1}$/',
            ],

            'estado' => ['nullable', 'in:activa,inactiva'],
        ], [
            'busqueda_empresa.max' => 'La búsqueda de empresa no debe exceder 150 caracteres.',
            'busqueda.max' => 'La búsqueda de empresa no debe exceder 150 caracteres.',

            'empresa_ids.array' => 'La selección de empresas no es válida.',
            'empresa_ids.*.exists' => 'Una de las empresas seleccionadas no es válida.',
            'empresa_id.exists' => 'La empresa seleccionada no es válida.',

            'nit.regex' => 'El NIT debe tener el formato 0000-000000-000-0.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ]);

        $busquedaEmpresa = trim((string) (
            $validated['busqueda_empresa']
            ?? $validated['busqueda']
            ?? ''
        ));

        $empresaIds = collect($validated['empresa_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id);

        /*
         * Compatibilidad temporal con el filtro individual anterior.
         */
        if (! empty($validated['empresa_id'])) {
            $empresaIds->push((int) $validated['empresa_id']);
        }

        $empresaIds = $empresaIds
            ->unique()
            ->values()
            ->all();

        $nit = trim((string) ($validated['nit'] ?? ''));
        $estado = $validated['estado'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | Alcance multiempresa
        |--------------------------------------------------------------------------
        |
        | Un usuario de empresa queda limitado a su propia empresa,
        | aunque intente enviar otros identificadores por URL.
        |
        */

        if (! $esUsuarioDieselCop) {
            $empresaIds = [(int) $user->empresa_id];
        }

        $empresaId = $empresaIds[0] ?? null;

        $hayFiltros = ! $esUsuarioDieselCop
            || $request->boolean('consultar')
            || $busquedaEmpresa !== ''
            || count($empresaIds) > 0
            || $nit !== ''
            || in_array($estado, ['activa', 'inactiva'], true);

        /*
        |--------------------------------------------------------------------------
        | Selector de empresas
        |--------------------------------------------------------------------------
        */

        $empresasSelector = $esUsuarioDieselCop
            ? Empresa::query()
                ->when(
                    $modoAdministracion,
                    fn (Builder $query) =>
                        $query->where('estado', 'activa')
                )
                ->orderBy('nombre_comercial')
                ->orderBy('nombre_legal')
                ->get()
            : collect([$empresaUsuario])
                ->filter()
                ->when(
                    $modoAdministracion,
                    fn ($empresas) => $empresas->where(
                        'estado',
                        'activa'
                    )
                )
                ->values();

        /*
        |--------------------------------------------------------------------------
        | Resultados
        |--------------------------------------------------------------------------
        */

        $query = Empresa::query();

        if ($modoAdministracion) {
            $query->where('estado', 'activa');
        }

        if ($hayFiltros) {
            $this->aplicarFiltrosEmpresa(
                $query,
                $busquedaEmpresa,
                $empresaIds,
                $nit,
                $estado
            );
        } else {
            $query->whereRaw('1 = 0');
        }

        $empresas = $query
            ->orderBy('nombre_comercial')
            ->orderBy('nombre_legal')
            ->paginate(10)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Resumen
        |--------------------------------------------------------------------------
        |
        | Cuando existe una consulta, el resumen representa el mismo conjunto
        | filtrado. Sin filtros, representa el alcance total permitido al usuario.
        |
        */

        $baseResumen = Empresa::query();

        if ($modoAdministracion) {
            $baseResumen->where('estado', 'activa');
        }

        if (! $esUsuarioDieselCop) {
            $baseResumen->where('id', $user->empresa_id);
        }

        if ($hayFiltros) {
            $this->aplicarFiltrosEmpresa(
                $baseResumen,
                $busquedaEmpresa,
                $empresaIds,
                $nit,
                $estado
            );
        }

        $totalEmpresas = (clone $baseResumen)->count();

        $empresasActivas = (clone $baseResumen)
            ->where('estado', 'activa')
            ->count();

        $empresasInactivas = (clone $baseResumen)
            ->where('estado', 'inactiva')
            ->count();

        return [
            'empresas' => $empresas,
            'empresasSelector' => $empresasSelector,

            /*
             * Variables nuevas y de compatibilidad.
             */
            'busquedaEmpresa' => $busquedaEmpresa,
            'busqueda' => $busquedaEmpresa,

            'empresaIds' => $empresaIds,
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
             * Variable conservada temporalmente para evitar errores
             * en vistas anteriores durante la transición.
             */
            'nombreComercial' => '',
        ];
    }

    /**
     * Aplica los filtros comunes de empresas.
     */
    private function aplicarFiltrosEmpresa(
        Builder $query,
        string $busquedaEmpresa,
        array $empresaIds,
        string $nit,
        ?string $estado
    ): void {
        if (count($empresaIds) > 0) {
            $query->whereIn('id', $empresaIds);
        }

        if ($busquedaEmpresa !== '') {
            $query->where(function (Builder $subquery) use ($busquedaEmpresa) {
                $subquery
                    ->where('nombre_legal', 'like', '%' . $busquedaEmpresa . '%')
                    ->orWhere('nombre_comercial', 'like', '%' . $busquedaEmpresa . '%');
            });
        }

        if ($nit !== '') {
            $query->where('nit', $nit);
        }

        if (in_array($estado, ['activa', 'inactiva'], true)) {
            $query->where('estado', $estado);
        }
    }

    /**
     * Muestra el formulario para registrar una empresa.
     */
    public function create()
    {
        return view('empresas.create');
    }

    /**
     * Muestra el formulario de registro en ventana independiente.
     */
    public function createVentana()
    {
        return view('empresas.create-ventana');
    }

    /**
     * Guarda una nueva empresa.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre_legal' => [
                'required',
                'string',
                'max:150',
            ],

            'nombre_comercial' => [
                'nullable',
                'string',
                'max:150',
            ],

            'nit' => [
                'required',
                'string',
                'max:17',
                'regex:/^\d{4}-\d{6}-\d{3}-\d{1}$/',
                'unique:empresas,nit',
            ],

            'direccion' => [
                'nullable',
                'string',
                'max:255',
            ],

            'telefono_empresa' => [
                'nullable',
                'string',
                'max:9',
                'regex:/^\d{4}-\d{4}$/',
            ],

            'correo_empresa' => [
                'required',
                'email',
                'max:150',
            ],

            'poc_nombre' => [
                'required',
                'string',
                'max:150',
            ],

            'poc_email' => [
                'required',
                'email',
                'max:150',
            ],

            'poc_telefono' => [
                'nullable',
                'string',
                'max:9',
                'regex:/^\d{4}-\d{4}$/',
            ],
        ], [
            'nombre_legal.required' => 'Debe ingresar el nombre legal de la empresa.',
            'nombre_legal.max' => 'El nombre legal no debe exceder 150 caracteres.',

            'nombre_comercial.max' => 'El nombre comercial no debe exceder 150 caracteres.',

            'nit.required' => 'Debe ingresar el NIT de la empresa.',
            'nit.regex' => 'El NIT debe tener el formato 0000-000000-000-0.',
            'nit.unique' => 'Ya existe una empresa registrada con este NIT.',

            'telefono_empresa.regex' => 'El teléfono de la empresa debe tener el formato 0000-0000.',

            'correo_empresa.required' => 'Debe ingresar el correo de la empresa.',
            'correo_empresa.email' => 'El correo de la empresa no tiene un formato válido.',

            'poc_nombre.required' => 'Debe ingresar el nombre del punto de contacto.',

            'poc_email.required' => 'Debe ingresar el correo del punto de contacto.',
            'poc_email.email' => 'El correo del punto de contacto no tiene un formato válido.',

            'poc_telefono.regex' => 'El teléfono del POC debe tener el formato 0000-0000.',
        ]);

        $empresa = Empresa::create([
            ...$validated,
            'estado' => 'activa',
            'fecha_creacion' => now(),
            'creado_por' => Auth::id(),
        ]);

        $queryParams = $request->query();

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route(
                    'empresas.show.ventana',
                    array_merge($queryParams, ['empresa' => $empresa])
                )
                ->with('success', 'Empresa creada correctamente.');
        }

        return redirect()
            ->route(
                'empresas.show',
                array_merge($queryParams, ['empresa' => $empresa])
            )
            ->with('success', 'Empresa creada correctamente.');
    }

    /**
     * Muestra la ficha administrativa de una empresa.
     *
     * La ficha puede consultarse tanto para empresas activas como inactivas.
     */
    public function show(Empresa $empresa)
    {
        $this->autorizarAccesoEmpresa($empresa);

        return view('empresas.show', compact('empresa'));
    }

    /**
     * Muestra la ficha administrativa en ventana independiente.
     */
    public function showVentana(Empresa $empresa)
    {
        $this->autorizarAccesoEmpresa($empresa);

        return view('empresas.show-ventana', compact('empresa'));
    }

    /**
     * Muestra el formulario de edición.
     *
     * Una empresa inactiva debe reactivarse desde su ficha
     * antes de poder modificarse.
     */
    public function edit(Empresa $empresa)
    {
        $this->autorizarAccesoEmpresa($empresa);
        $this->validarEmpresaActivaParaOperacion($empresa);

        return view('empresas.edit', compact('empresa'));
    }

    /**
     * Muestra el formulario de edición en ventana independiente.
     */
    public function editVentana(Empresa $empresa)
    {
        $this->autorizarAccesoEmpresa($empresa);
        $this->validarEmpresaActivaParaOperacion($empresa);

        return view('empresas.edit-ventana', compact('empresa'));
    }

    /**
     * Actualiza una empresa activa.
     */
    public function update(Request $request, Empresa $empresa)
    {
        $this->autorizarAccesoEmpresa($empresa);
        $this->validarEmpresaActivaParaOperacion($empresa);

        $validated = $request->validate([
            'nombre_legal' => [
                'required',
                'string',
                'max:150',
            ],

            'nombre_comercial' => [
                'nullable',
                'string',
                'max:150',
            ],

            'nit' => [
                'required',
                'string',
                'max:17',
                'regex:/^\d{4}-\d{6}-\d{3}-\d{1}$/',
                Rule::unique('empresas', 'nit')->ignore($empresa->id),
            ],

            'direccion' => [
                'nullable',
                'string',
                'max:255',
            ],

            'telefono_empresa' => [
                'nullable',
                'string',
                'max:9',
                'regex:/^\d{4}-\d{4}$/',
            ],

            'correo_empresa' => [
                'required',
                'email',
                'max:150',
            ],

            'poc_nombre' => [
                'required',
                'string',
                'max:150',
            ],

            'poc_email' => [
                'required',
                'email',
                'max:150',
            ],

            'poc_telefono' => [
                'nullable',
                'string',
                'max:9',
                'regex:/^\d{4}-\d{4}$/',
            ],
        ], [
            'nombre_legal.required' => 'Debe ingresar el nombre legal de la empresa.',
            'nombre_legal.max' => 'El nombre legal no debe exceder 150 caracteres.',

            'nombre_comercial.max' => 'El nombre comercial no debe exceder 150 caracteres.',

            'nit.required' => 'Debe ingresar el NIT de la empresa.',
            'nit.regex' => 'El NIT debe tener el formato 0000-000000-000-0.',
            'nit.unique' => 'Ya existe otra empresa registrada con este NIT.',

            'telefono_empresa.regex' => 'El teléfono de la empresa debe tener el formato 0000-0000.',

            'correo_empresa.required' => 'Debe ingresar el correo de la empresa.',
            'correo_empresa.email' => 'El correo de la empresa no tiene un formato válido.',

            'poc_nombre.required' => 'Debe ingresar el nombre del punto de contacto.',

            'poc_email.required' => 'Debe ingresar el correo del punto de contacto.',
            'poc_email.email' => 'El correo del punto de contacto no tiene un formato válido.',

            'poc_telefono.regex' => 'El teléfono del POC debe tener el formato 0000-0000.',
        ]);

        $empresa->update([
            ...$validated,
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        $queryParams = $request->query();

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route(
                    'empresas.show.ventana',
                    array_merge($queryParams, ['empresa' => $empresa])
                )
                ->with('success', 'Empresa actualizada correctamente.');
        }

        return redirect()
            ->route(
                'empresas.show',
                array_merge($queryParams, ['empresa' => $empresa])
            )
            ->with('success', 'Empresa actualizada correctamente.');
    }

    /**
     * Inactiva una empresa actualmente activa.
     */
    public function inactivar(Request $request, Empresa $empresa)
    {
        $this->autorizarAccesoEmpresa($empresa);
        $this->validarEmpresaActivaParaOperacion($empresa);

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

        $queryParams = $request->query();

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route(
                    'empresas.show.ventana',
                    array_merge($queryParams, ['empresa' => $empresa])
                )
                ->with('success', 'Empresa inactivada correctamente.');
        }

        return redirect()
            ->route(
                'empresas.show',
                array_merge($queryParams, ['empresa' => $empresa])
            )
            ->with('success', 'Empresa inactivada correctamente.');
    }

    /**
     * Reactiva una empresa actualmente inactiva.
     */
    public function reactivar(Request $request, Empresa $empresa)
    {
        $this->autorizarAccesoEmpresa($empresa);
        $this->validarEmpresaInactivaParaReactivacion($empresa);

        $empresa->update([
            'estado' => 'activa',
            'fecha_inactivacion' => null,
            'inactivado_por' => null,
            'motivo_inactivacion' => null,
            'fecha_actualizacion' => now(),
            'actualizado_por' => Auth::id(),
        ]);

        $queryParams = $request->query();

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route(
                    'empresas.show.ventana',
                    array_merge($queryParams, ['empresa' => $empresa])
                )
                ->with('success', 'Empresa reactivada correctamente.');
        }

        return redirect()
            ->route(
                'empresas.show',
                array_merge($queryParams, ['empresa' => $empresa])
            )
            ->with('success', 'Empresa reactivada correctamente.');
    }

    /**
     * Impide que un usuario de empresa acceda a otra empresa.
     */
    private function autorizarAccesoEmpresa(Empresa $empresa): void
    {
        $user = Auth::user();

        if (
            ! is_null($user->empresa_id)
            && (int) $user->empresa_id !== (int) $empresa->id
        ) {
            abort(403, 'No tiene autorización para acceder a esta empresa.');
        }
    }

    /**
     * Protege las operaciones que requieren una empresa activa.
     */
    private function validarEmpresaActivaParaOperacion(Empresa $empresa): void
    {
        if ($empresa->estado !== 'activa') {
            abort(
                403,
                'No se puede modificar esta empresa porque está inactiva. Debe reactivarla desde la ficha antes de realizar cambios.'
            );
        }
    }

    /**
     * Impide reactivar una empresa que ya se encuentra activa.
     */
    private function validarEmpresaInactivaParaReactivacion(Empresa $empresa): void
    {
        if ($empresa->estado !== 'inactiva') {
            abort(
                403,
                'No se puede reactivar esta empresa porque ya se encuentra activa.'
            );
        }
    }
}
