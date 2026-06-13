<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    /**
     * Muestra la consulta informativa de usuarios.
     */
    public function index(Request $request): View
    {
        $data = $this->prepararConsultaUsuarios($request);

        return view('usuarios.index', $data);
    }

    /**
     * Muestra la consulta informativa de usuarios en ventana independiente.
     */
    public function consultaVentana(Request $request): View
    {
        $data = $this->prepararConsultaUsuarios($request);

        return view('usuarios.index-ventana', $data);
    }

    /**
     * Muestra la búsqueda administrativa de usuarios.
     */
    public function administrar(Request $request): View
    {
        $data = $this->prepararConsultaUsuarios($request);

        return view('usuarios.administrar', $data);
    }

    /**
     * Muestra la búsqueda administrativa de usuarios en ventana independiente.
     */
    public function administrarVentana(Request $request): View
    {
        $data = $this->prepararConsultaUsuarios($request);

        return view('usuarios.administrar-ventana', $data);
    }

    /**
     * Prepara la consulta reutilizable para pantallas normales y ventanas.
     */
    private function prepararConsultaUsuarios(Request $request): array
    {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);

        $tipoUsuario = $request->input('tipo_usuario');
        $empresaId = $request->input('empresa_id');
        $rolId = $request->input('rol_id');

        $consultaEjecutada = $request->boolean('consultar');

        /*
        |--------------------------------------------------------------------------
        | Alcance multiempresa
        |--------------------------------------------------------------------------
        |
        | Diesel Cop puede consultar usuarios globalmente.
        | Un usuario de empresa siempre queda limitado a su propia empresa.
        |
        */

        if (! $esUsuarioDieselCop) {
            $tipoUsuario = 'empresa';
            $empresaId = $user->empresa_id;
        }

        $hayFiltros = ! $esUsuarioDieselCop
            || $consultaEjecutada
            || filled($tipoUsuario)
            || filled($empresaId)
            || filled($rolId);

        $totalUsuarios = $esUsuarioDieselCop
            ? User::count()
            : User::where('empresa_id', $user->empresa_id)->count();

        $usuariosActivos = $esUsuarioDieselCop
            ? User::where('estado', 'activo')->count()
            : User::where('empresa_id', $user->empresa_id)->where('estado', 'activo')->count();

        $usuariosInactivos = $esUsuarioDieselCop
            ? User::where('estado', 'inactivo')->count()
            : User::where('empresa_id', $user->empresa_id)->where('estado', 'inactivo')->count();

        $empresas = $esUsuarioDieselCop
            ? Empresa::query()
                ->where('estado', 'activa')
                ->orderBy('nombre_legal')
                ->get()
            : Empresa::query()
                ->where('id', $user->empresa_id)
                ->get();

        $roles = Role::query()
            ->where('estado', 'activo')
            ->orderBy('alcance')
            ->orderBy('nombre')
            ->get();

        $query = User::query()
            ->with(['empresa', 'role'])
            ->orderBy('tipo_usuario')
            ->orderBy('name')
            ->orderBy('apellido');

        if (! $esUsuarioDieselCop) {
            $query->where('empresa_id', $user->empresa_id);
        }

        if (filled($tipoUsuario)) {
            $query->where('tipo_usuario', $tipoUsuario);
        }

        if ($tipoUsuario === 'empresa' && filled($empresaId)) {
            $query->where('empresa_id', $empresaId);
        }

        if ($tipoUsuario === 'diesel_cop') {
            $query->whereNull('empresa_id');
        }

        if (filled($rolId)) {
            $query->where('rol_id', $rolId);
        }

        $usuarios = $hayFiltros
            ? $query->paginate(10)->withQueryString()
            : User::query()->whereRaw('1 = 0')->paginate(10)->withQueryString();

        return [
            'usuarios' => $usuarios,
            'tipoUsuario' => $tipoUsuario,
            'empresaId' => $empresaId,
            'rolId' => $rolId,
            'hayFiltros' => $hayFiltros,
            'totalUsuarios' => $totalUsuarios,
            'usuariosActivos' => $usuariosActivos,
            'usuariosInactivos' => $usuariosInactivos,
            'empresas' => $empresas,
            'roles' => $roles,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
        ];
    }

    /**
     * Muestra el formulario de creación de usuario.
     */
    public function create(): View
    {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);

        $empresas = $esUsuarioDieselCop
            ? Empresa::query()
                ->where('estado', 'activa')
                ->orderBy('nombre_legal')
                ->get()
            : Empresa::query()
                ->where('id', $user->empresa_id)
                ->get();

        $roles = Role::query()
            ->where('estado', 'activo')
            ->orderBy('alcance')
            ->orderBy('nombre')
            ->get();

        return view('usuarios.create', compact('empresas', 'roles', 'esUsuarioDieselCop'));
    }

    /**
    * Formulario de creación de usuario en ventana independiente.
    */
    public function createVentana(): View
    {
        $user = Auth::user();
        $esUsuarioDieselCop = is_null($user->empresa_id);

        $empresas = Empresa::query()
            ->when(! $esUsuarioDieselCop, function ($query) use ($user) {
                $query->where('id', $user->empresa_id);
            })
            ->orderBy('nombre_comercial')
            ->orderBy('nombre_legal')
            ->get();

        $roles = Role::query()
            ->when(! $esUsuarioDieselCop, function ($query) {
                $query->where('alcance', 'empresa');
            })
            ->where('estado', 'activo')
            ->orderBy('nombre')
            ->get();

        return view('usuarios.create-ventana', [
            'empresas' => $empresas,
            'roles' => $roles,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
        ]);
    }

    /**
     * Guarda un nuevo usuario.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'tipo_usuario' => ['required', Rule::in(['diesel_cop', 'empresa'])],

            'empresa_id' => [
                'nullable',
                'required_if:tipo_usuario,empresa',
                'exists:empresas,id',
            ],

            'rol_id' => [
                'required',
                'exists:roles,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'apellido' => [
                'nullable',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'telefono' => [
                'nullable',
                'string',
                'max:30',
            ],

            'cargo' => [
                'nullable',
                'string',
                'max:120',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ], [
            'tipo_usuario.required' => 'Debe seleccionar el tipo de usuario.',
            'tipo_usuario.in' => 'El tipo de usuario seleccionado no es válido.',

            'empresa_id.required_if' => 'Debe seleccionar una empresa para usuarios de tipo Empresa.',
            'empresa_id.exists' => 'La empresa seleccionada no existe.',

            'rol_id.required' => 'Debe seleccionar un rol.',
            'rol_id.exists' => 'El rol seleccionado no existe.',

            'name.required' => 'El nombre del usuario es obligatorio.',

            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ingresar un correo electrónico válido.',
            'email.unique' => 'Ya existe un usuario registrado con este correo electrónico.',

            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ]);

        if (! is_null($user->empresa_id)) {
            $validated['tipo_usuario'] = 'empresa';
            $validated['empresa_id'] = $user->empresa_id;
        }

        $role = Role::where('id', $validated['rol_id'])
            ->where('estado', 'activo')
            ->firstOrFail();

        if ($validated['tipo_usuario'] === 'diesel_cop' && $role->alcance !== 'diesel_cop') {
            return back()
                ->withErrors([
                    'rol_id' => 'Para usuarios Diesel Cop debe seleccionar un rol de alcance Diesel Cop.',
                ])
                ->withInput();
        }

        if ($validated['tipo_usuario'] === 'empresa' && $role->alcance !== 'empresa') {
            return back()
                ->withErrors([
                    'rol_id' => 'Para usuarios de empresa debe seleccionar un rol de alcance Empresa.',
                ])
                ->withInput();
        }

        $empresaId = $validated['tipo_usuario'] === 'empresa'
            ? $validated['empresa_id']
            : null;

        User::create([
            'empresa_id' => $empresaId,
            'rol_id' => $validated['rol_id'],
            'tipo_usuario' => $validated['tipo_usuario'],
            'name' => $validated['name'],
            'apellido' => $validated['apellido'] ?? null,
            'email' => $validated['email'],
            'telefono' => $validated['telefono'] ?? null,
            'cargo' => $validated['cargo'] ?? null,
            'estado' => 'activo',
            'password' => $validated['password'],
            'creado_por' => Auth::id(),
            'actualizado_por' => Auth::id(),
        ]);

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    /**
     * Muestra la ficha administrativa de usuario.
     */
    public function show(User $usuario): View
    {
        $this->autorizarAccesoUsuario($usuario);

        $usuario->load(['empresa', 'role', 'creadoPor', 'actualizadoPor', 'inactivadoPor']);

        return view('usuarios.show', compact('usuario'));
    }

    /**
     * Muestra el formulario de edición de usuario.
     */
    public function edit(User $usuario): View
    {
        $this->autorizarAccesoUsuario($usuario);

        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);

        $empresas = $esUsuarioDieselCop
            ? Empresa::query()
                ->where('estado', 'activa')
                ->orderBy('nombre_legal')
                ->get()
            : Empresa::query()
                ->where('id', $user->empresa_id)
                ->get();

        $roles = Role::query()
            ->where('estado', 'activo')
            ->orderBy('alcance')
            ->orderBy('nombre')
            ->get();

        return view('usuarios.edit', compact('usuario', 'empresas', 'roles', 'esUsuarioDieselCop'));
    }

    /**
     * Actualiza un usuario existente.
     */
    public function update(Request $request, User $usuario): RedirectResponse
    {
        $this->autorizarAccesoUsuario($usuario);

        $user = Auth::user();

        $validated = $request->validate([
            'tipo_usuario' => ['required', Rule::in(['diesel_cop', 'empresa'])],

            'empresa_id' => [
                'nullable',
                'required_if:tipo_usuario,empresa',
                'exists:empresas,id',
            ],

            'rol_id' => [
                'required',
                'exists:roles,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'apellido' => [
                'nullable',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($usuario->id),
            ],

            'telefono' => [
                'nullable',
                'string',
                'max:30',
            ],

            'cargo' => [
                'nullable',
                'string',
                'max:120',
            ],

            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
        ], [
            'tipo_usuario.required' => 'Debe seleccionar el tipo de usuario.',
            'tipo_usuario.in' => 'El tipo de usuario seleccionado no es válido.',

            'empresa_id.required_if' => 'Debe seleccionar una empresa para usuarios de tipo Empresa.',
            'empresa_id.exists' => 'La empresa seleccionada no existe.',

            'rol_id.required' => 'Debe seleccionar un rol.',
            'rol_id.exists' => 'El rol seleccionado no existe.',

            'name.required' => 'El nombre del usuario es obligatorio.',

            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ingresar un correo electrónico válido.',
            'email.unique' => 'Ya existe otro usuario registrado con este correo electrónico.',

            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ]);

        if (! is_null($user->empresa_id)) {
            $validated['tipo_usuario'] = 'empresa';
            $validated['empresa_id'] = $user->empresa_id;
        }

        $role = Role::where('id', $validated['rol_id'])
            ->where('estado', 'activo')
            ->firstOrFail();

        if ($validated['tipo_usuario'] === 'diesel_cop' && $role->alcance !== 'diesel_cop') {
            return back()
                ->withErrors([
                    'rol_id' => 'Para usuarios Diesel Cop debe seleccionar un rol de alcance Diesel Cop.',
                ])
                ->withInput();
        }

        if ($validated['tipo_usuario'] === 'empresa' && $role->alcance !== 'empresa') {
            return back()
                ->withErrors([
                    'rol_id' => 'Para usuarios de empresa debe seleccionar un rol de alcance Empresa.',
                ])
                ->withInput();
        }

        $empresaId = $validated['tipo_usuario'] === 'empresa'
            ? $validated['empresa_id']
            : null;

        $datosActualizar = [
            'empresa_id' => $empresaId,
            'rol_id' => $validated['rol_id'],
            'tipo_usuario' => $validated['tipo_usuario'],
            'name' => $validated['name'],
            'apellido' => $validated['apellido'] ?? null,
            'email' => $validated['email'],
            'telefono' => $validated['telefono'] ?? null,
            'cargo' => $validated['cargo'] ?? null,
            'actualizado_por' => Auth::id(),
        ];

        if (filled($validated['password'] ?? null)) {
            $datosActualizar['password'] = $validated['password'];
        }

        $usuario->update($datosActualizar);

        return redirect()
            ->route('usuarios.show', $usuario)
            ->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * Inactiva un usuario sin eliminarlo físicamente.
     */
    public function inactivar(Request $request, User $usuario): RedirectResponse
    {
        $this->autorizarAccesoUsuario($usuario);

        if ((int) $usuario->id === (int) Auth::id()) {
            return back()
                ->withErrors([
                    'motivo_inactivacion' => 'No puede inactivar su propio usuario mientras está en sesión.',
                ]);
        }

        $validated = $request->validate([
            'motivo_inactivacion' => [
                'required',
                'string',
                'max:255',
                'in:Falta de uso,Cambio de puesto,Salida de la empresa,Acceso duplicado,Datos incorrectos en registro,Solicitud administrativa,Suspensión temporal,Otro',
            ],
        ], [
            'motivo_inactivacion.required' => 'Debe seleccionar el motivo de inactivación.',
            'motivo_inactivacion.in' => 'El motivo de inactivación seleccionado no es válido.',
            'motivo_inactivacion.max' => 'El motivo de inactivación no debe exceder 255 caracteres.',
        ]);

        $usuario->update([
            'estado' => 'inactivo',
            'fecha_inactivacion' => now(),
            'inactivado_por' => Auth::id(),
            'motivo_inactivacion' => $validated['motivo_inactivacion'],
            'actualizado_por' => Auth::id(),
        ]);

        return redirect()
            ->route('usuarios.show', $usuario)
            ->with('success', 'Usuario inactivado correctamente.');
    }

    /**
     * Reactiva un usuario previamente inactivo.
     */
    public function reactivar(User $usuario): RedirectResponse
    {
        $this->autorizarAccesoUsuario($usuario);

        $usuario->update([
            'estado' => 'activo',
            'fecha_inactivacion' => null,
            'inactivado_por' => null,
            'motivo_inactivacion' => null,
            'actualizado_por' => Auth::id(),
        ]);

        return redirect()
            ->route('usuarios.show', $usuario)
            ->with('success', 'Usuario reactivado correctamente.');
    }

    /**
     * Evita que usuarios de empresa accedan a usuarios de otras empresas.
     */
    private function autorizarAccesoUsuario(User $usuario): void
    {
        $user = Auth::user();

        if (! is_null($user->empresa_id) && (int) $user->empresa_id !== (int) $usuario->empresa_id) {
            abort(403, 'No tiene autorización para acceder a este usuario.');
        }
    }
}