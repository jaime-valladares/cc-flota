<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        return view(
            'usuarios.index',
            $this->prepararConsultaUsuarios($request)
        );
    }

    public function consultaVentana(Request $request): View
    {
        return view(
            'usuarios.index-ventana',
            $this->prepararConsultaUsuarios($request)
        );
    }

    public function administrar(Request $request): View
    {
        return view(
            'usuarios.administrar',
            $this->prepararConsultaUsuarios($request)
        );
    }

    public function administrarVentana(Request $request): View
    {
        return view(
            'usuarios.administrar-ventana',
            $this->prepararConsultaUsuarios($request)
        );
    }

    private function prepararConsultaUsuarios(Request $request): array
    {
        /** @var User $usuarioAutenticado */
        $usuarioAutenticado = Auth::user();
        $esUsuarioDieselCop = $usuarioAutenticado->esDieselCop();

        $validated = $request->validate([
            'consultar' => ['nullable', 'boolean'],
            'busqueda_usuario' => ['nullable', 'string', 'max:150'],
            'tipo_usuario' => [
                'nullable',
                Rule::in([
                    User::TIPO_DIESEL_COP,
                    User::TIPO_EMPRESA,
                ]),
            ],
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
            'rol_ids' => ['nullable', 'array'],
            'rol_ids.*' => [
                'nullable',
                'integer',
                'exists:roles,id',
            ],
            'rol_id' => [
                'nullable',
                'integer',
                'exists:roles,id',
            ],
            'estado' => [
                'nullable',
                Rule::in(['activo', 'inactivo']),
            ],
        ], [
            'busqueda_usuario.max' =>
                'La búsqueda no debe exceder 150 caracteres.',
            'tipo_usuario.in' =>
                'El tipo de usuario seleccionado no es válido.',
            'empresa_ids.array' =>
                'La selección de empresas no es válida.',
            'empresa_ids.*.exists' =>
                'Una de las empresas seleccionadas no es válida.',
            'empresa_id.exists' =>
                'La empresa seleccionada no es válida.',
            'rol_ids.array' =>
                'La selección de roles no es válida.',
            'rol_ids.*.exists' =>
                'Uno de los roles seleccionados no es válido.',
            'rol_id.exists' =>
                'El rol seleccionado no es válido.',
            'estado.in' =>
                'El estado seleccionado no es válido.',
        ]);

        $busquedaUsuario = trim(
            (string) ($validated['busqueda_usuario'] ?? '')
        );

        $tipoUsuario = $validated['tipo_usuario'] ?? null;
        $estado = $validated['estado'] ?? null;

        $empresaIds = collect(
            $validated['empresa_ids'] ?? []
        )
            ->filter()
            ->map(fn ($id) => (int) $id);

        if (! empty($validated['empresa_id'])) {
            $empresaIds->push(
                (int) $validated['empresa_id']
            );
        }

        $empresaIds = $empresaIds
            ->unique()
            ->values()
            ->all();

        $rolIds = collect(
            $validated['rol_ids'] ?? []
        )
            ->filter()
            ->map(fn ($id) => (int) $id);

        if (! empty($validated['rol_id'])) {
            $rolIds->push(
                (int) $validated['rol_id']
            );
        }

        $rolIds = $rolIds
            ->unique()
            ->values()
            ->all();

        if (! $esUsuarioDieselCop) {
            $tipoUsuario = User::TIPO_EMPRESA;
            $empresaIds = [
                (int) $usuarioAutenticado->empresaIdOperativa(),
            ];
        }

        $hayFiltros = ! $esUsuarioDieselCop
            || $request->boolean('consultar')
            || $busquedaUsuario !== ''
            || filled($tipoUsuario)
            || count($empresaIds) > 0
            || count($rolIds) > 0
            || filled($estado);

        $empresasSelector = $esUsuarioDieselCop
            ? Empresa::query()
                ->orderBy('nombre_comercial')
                ->orderBy('nombre_legal')
                ->get()
            : Empresa::query()
                ->where(
                    'id',
                    $usuarioAutenticado->empresaIdOperativa()
                )
                ->get();

        $rolesSelector = Role::query()
            ->where('estado', 'activo')
            ->when(
                ! $esUsuarioDieselCop,
                fn (Builder $query) =>
                    $query->where('alcance', 'empresa')
            )
            ->orderBy('alcance')
            ->orderBy('nombre')
            ->get();

        $query = User::query()
            ->with(['empresa', 'role']);

        $this->aplicarAlcanceUsuario(
            $query,
            $usuarioAutenticado
        );

        if ($hayFiltros) {
            $this->aplicarFiltrosUsuario(
                $query,
                $busquedaUsuario,
                $tipoUsuario,
                $empresaIds,
                $rolIds,
                $estado
            );
        } else {
            $query->whereRaw('1 = 0');
        }

        $usuarios = $query
            ->orderBy('tipo_usuario')
            ->orderBy('name')
            ->orderBy('apellido')
            ->paginate(10)
            ->withQueryString();

        $baseResumen = User::query();

        $this->aplicarAlcanceUsuario(
            $baseResumen,
            $usuarioAutenticado
        );

        if ($hayFiltros) {
            $this->aplicarFiltrosUsuario(
                $baseResumen,
                $busquedaUsuario,
                $tipoUsuario,
                $empresaIds,
                $rolIds,
                $estado
            );
        }

        return [
            'usuarios' => $usuarios,
            'empresasSelector' => $empresasSelector,
            'rolesSelector' => $rolesSelector,
            'busquedaUsuario' => $busquedaUsuario,
            'tipoUsuario' => $tipoUsuario,
            'empresaIds' => $empresaIds,
            'rolIds' => $rolIds,
            'estado' => $estado,
            'hayFiltros' => $hayFiltros,
            'totalUsuarios' => (clone $baseResumen)->count(),
            'usuariosActivos' => (clone $baseResumen)
                ->where('estado', 'activo')
                ->count(),
            'usuariosInactivos' => (clone $baseResumen)
                ->where('estado', 'inactivo')
                ->count(),
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
        ];
    }

    private function aplicarAlcanceUsuario(
        Builder $query,
        User $usuarioAutenticado
    ): void {
        if ($usuarioAutenticado->esUsuarioEmpresa()) {
            $query->where(
                'empresa_id',
                $usuarioAutenticado->empresaIdOperativa()
            );
        }
    }

    private function aplicarFiltrosUsuario(
        Builder $query,
        string $busquedaUsuario,
        ?string $tipoUsuario,
        array $empresaIds,
        array $rolIds,
        ?string $estado
    ): void {
        if ($busquedaUsuario !== '') {
            $query->where(
                function (Builder $subquery)
                use ($busquedaUsuario) {
                    $subquery
                        ->where(
                            'name',
                            'like',
                            '%' . $busquedaUsuario . '%'
                        )
                        ->orWhere(
                            'apellido',
                            'like',
                            '%' . $busquedaUsuario . '%'
                        )
                        ->orWhere(
                            'email',
                            'like',
                            '%' . $busquedaUsuario . '%'
                        );
                }
            );
        }

        if (filled($tipoUsuario)) {
            $query->where(
                'tipo_usuario',
                $tipoUsuario
            );
        }

        if (count($empresaIds) > 0) {
            $query->whereIn(
                'empresa_id',
                $empresaIds
            );
        }

        if ($tipoUsuario === User::TIPO_DIESEL_COP) {
            $query->whereNull('empresa_id');
        }

        if (count($rolIds) > 0) {
            $query->whereIn(
                'rol_id',
                $rolIds
            );
        }

        if (filled($estado)) {
            $query->where(
                'estado',
                $estado
            );
        }
    }

    public function create(): View
    {
        return view(
            'usuarios.create',
            $this->prepararFormulario()
        );
    }

    public function createVentana(): View
    {
        return view(
            'usuarios.create-ventana',
            $this->prepararFormulario()
        );
    }

    private function prepararFormulario(
        ?User $usuario = null
    ): array {
        /** @var User $usuarioAutenticado */
        $usuarioAutenticado = Auth::user();
        $esUsuarioDieselCop =
            $usuarioAutenticado->esDieselCop();

        $empresas = $esUsuarioDieselCop
            ? Empresa::query()
                ->where('estado', 'activa')
                ->orderBy('nombre_comercial')
                ->orderBy('nombre_legal')
                ->get()
            : Empresa::query()
                ->where(
                    'id',
                    $usuarioAutenticado->empresaIdOperativa()
                )
                ->get();

        $roles = Role::query()
            ->where('estado', 'activo')
            ->when(
                ! $esUsuarioDieselCop,
                fn (Builder $query) =>
                    $query->where('alcance', 'empresa')
            )
            ->orderBy('alcance')
            ->orderBy('nombre')
            ->get();

        return [
            'usuario' => $usuario,
            'empresas' => $empresas,
            'roles' => $roles,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'puedeCambiarRol' =>
                $usuarioAutenticado->tienePermiso(
                    'usuarios.cambiar_rol'
                ),
        ];
    }

    public function store(
        Request $request
    ): RedirectResponse {
        /** @var User $usuarioAutenticado */
        $usuarioAutenticado = Auth::user();

        $validated = $this->validarUsuario(
            $request
        );

        $this->forzarAlcanceEmpresa(
            $validated,
            $usuarioAutenticado
        );

        $role = $this->obtenerRolValido(
            (int) $validated['rol_id']
        );

        $this->validarCoherenciaRol(
            $validated,
            $role
        );

        $usuario = User::create([
            'empresa_id' =>
                $validated['tipo_usuario']
                    === User::TIPO_EMPRESA
                    ? $validated['empresa_id']
                    : null,
            'rol_id' => $role->id,
            'tipo_usuario' =>
                $validated['tipo_usuario'],
            'name' => $validated['name'],
            'apellido' =>
                $validated['apellido'] ?? null,
            'email' => $validated['email'],
            'telefono' =>
                $validated['telefono'] ?? null,
            'cargo' =>
                $validated['cargo'] ?? null,
            'estado' => 'activo',
            'password' => $validated['password'],
            'creado_por' => Auth::id(),
            'actualizado_por' => Auth::id(),
        ]);

        if ($request->input('return_to') === 'ventana') {
            return redirect()
                ->route('usuarios.create.ventana')
                ->with(
                    'success',
                    'Usuario creado correctamente.'
                );
        }

        return redirect()
            ->route('usuarios.create')
            ->with(
                'success',
                'Usuario creado correctamente.'
            );
    }

    public function show(User $usuario): View
    {
        $this->autorizarAccesoUsuario($usuario);

        $usuario->load([
            'empresa',
            'role',
            'creadoPor',
            'actualizadoPor',
            'inactivadoPor',
        ]);

        return view(
            'usuarios.show',
            compact('usuario')
        );
    }

    public function showVentana(User $usuario): View
    {
        $this->autorizarAccesoUsuario($usuario);

        $usuario->load([
            'empresa',
            'role',
            'creadoPor',
            'actualizadoPor',
            'inactivadoPor',
        ]);

        return view(
            'usuarios.show-ventana',
            compact('usuario')
        );
    }

    public function edit(User $usuario): View
    {
        $this->autorizarAccesoUsuario($usuario);
        $this->validarUsuarioActivo($usuario);

        return view(
            'usuarios.edit',
            $this->prepararFormulario($usuario)
        );
    }

    public function editVentana(User $usuario): View
    {
        $this->autorizarAccesoUsuario($usuario);
        $this->validarUsuarioActivo($usuario);

        return view(
            'usuarios.edit-ventana',
            $this->prepararFormulario($usuario)
        );
    }

    public function update(
        Request $request,
        User $usuario
    ): RedirectResponse {
        $this->autorizarAccesoUsuario($usuario);
        $this->validarUsuarioActivo($usuario);

        /** @var User $usuarioAutenticado */
        $usuarioAutenticado = Auth::user();

        $validated = $this->validarUsuario(
            $request,
            $usuario
        );

        $this->forzarAlcanceEmpresa(
            $validated,
            $usuarioAutenticado
        );

        $role = $this->obtenerRolValido(
            (int) $validated['rol_id']
        );

        $this->validarCoherenciaRol(
            $validated,
            $role
        );

        if (
            (int) $usuario->rol_id !== (int) $role->id
            && ! $usuarioAutenticado->tienePermiso(
                'usuarios.cambiar_rol'
            )
        ) {
            abort(
                403,
                'No tiene autorización para cambiar el rol del usuario.'
            );
        }

        $datosActualizar = [
            'empresa_id' =>
                $validated['tipo_usuario']
                    === User::TIPO_EMPRESA
                    ? $validated['empresa_id']
                    : null,
            'rol_id' => $role->id,
            'tipo_usuario' =>
                $validated['tipo_usuario'],
            'name' => $validated['name'],
            'apellido' =>
                $validated['apellido'] ?? null,
            'email' => $validated['email'],
            'telefono' =>
                $validated['telefono'] ?? null,
            'cargo' =>
                $validated['cargo'] ?? null,
            'actualizado_por' => Auth::id(),
        ];

        if (filled($validated['password'] ?? null)) {
            $datosActualizar['password'] =
                $validated['password'];
        }

        $usuario->update($datosActualizar);

        return $this->redirigirAFicha(
            $request,
            $usuario,
            'Usuario actualizado correctamente.'
        );
    }

    public function inactivar(
        Request $request,
        User $usuario
    ): RedirectResponse {
        $this->autorizarAccesoUsuario($usuario);
        $this->validarUsuarioActivo($usuario);

        if ((int) $usuario->id === (int) Auth::id()) {
            return back()->withErrors([
                'motivo_inactivacion' =>
                    'No puede inactivar su propio usuario mientras está en sesión.',
            ]);
        }

        $validated = $request->validate([
            'motivo_inactivacion' => [
                'required',
                'string',
                'max:255',
                Rule::in([
                    'Falta de uso',
                    'Cambio de puesto',
                    'Salida de la empresa',
                    'Acceso duplicado',
                    'Datos incorrectos en registro',
                    'Solicitud administrativa',
                    'Suspensión temporal',
                    'Otro',
                ]),
            ],
        ], [
            'motivo_inactivacion.required' =>
                'Debe seleccionar el motivo de inactivación.',
            'motivo_inactivacion.in' =>
                'El motivo seleccionado no es válido.',
        ]);

        $usuario->update([
            'estado' => 'inactivo',
            'fecha_inactivacion' => now(),
            'inactivado_por' => Auth::id(),
            'motivo_inactivacion' =>
                $validated['motivo_inactivacion'],
            'actualizado_por' => Auth::id(),
        ]);

        return $this->redirigirAFicha(
            $request,
            $usuario,
            'Usuario inactivado correctamente.'
        );
    }

    public function reactivar(
        Request $request,
        User $usuario
    ): RedirectResponse {
        $this->autorizarAccesoUsuario($usuario);

        if ($usuario->estado !== 'inactivo') {
            abort(
                403,
                'El usuario ya se encuentra activo.'
            );
        }

        $usuario->update([
            'estado' => 'activo',
            'fecha_inactivacion' => null,
            'inactivado_por' => null,
            'motivo_inactivacion' => null,
            'actualizado_por' => Auth::id(),
        ]);

        return $this->redirigirAFicha(
            $request,
            $usuario,
            'Usuario reactivado correctamente.'
        );
    }

    private function validarUsuario(
        Request $request,
        ?User $usuario = null
    ): array {
        $passwordRules = $usuario
            ? [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ]
            : [
                'required',
                'string',
                'min:8',
                'confirmed',
            ];

        return $request->validate([
            'tipo_usuario' => [
                'required',
                Rule::in([
                    User::TIPO_DIESEL_COP,
                    User::TIPO_EMPRESA,
                ]),
            ],
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
                'max:100',
            ],
            'apellido' => [
                'nullable',
                'string',
                'max:100',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($usuario?->id),
            ],
            'telefono' => [
                'nullable',
                'string',
                'max:9',
                'regex:/^\d{4}-\d{4}$/',
            ],
            'cargo' => [
                'nullable',
                'string',
                'max:100',
            ],
            'password' => $passwordRules,
        ], [
            'tipo_usuario.required' =>
                'Debe seleccionar el tipo de usuario.',
            'empresa_id.required_if' =>
                'Debe seleccionar una empresa.',
            'rol_id.required' =>
                'Debe seleccionar un rol.',
            'name.required' =>
                'El nombre es obligatorio.',
            'email.required' =>
                'El correo electrónico es obligatorio.',
            'email.unique' =>
                'Ya existe un usuario con este correo.',
            'telefono.regex' =>
                'El teléfono debe tener el formato 0000-0000.',
            'password.required' =>
                'La contraseña es obligatoria.',
            'password.min' =>
                'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' =>
                'La confirmación no coincide.',
        ]);
    }

    private function forzarAlcanceEmpresa(
        array &$validated,
        User $usuarioAutenticado
    ): void {
        if ($usuarioAutenticado->esUsuarioEmpresa()) {
            $validated['tipo_usuario'] =
                User::TIPO_EMPRESA;
            $validated['empresa_id'] =
                $usuarioAutenticado->empresaIdOperativa();
        }
    }

    private function obtenerRolValido(int $rolId): Role
    {
        return Role::query()
            ->whereKey($rolId)
            ->where('estado', 'activo')
            ->firstOrFail();
    }

    private function validarCoherenciaRol(
        array $validated,
        Role $role
    ): void {
        if (
            $validated['tipo_usuario']
                === User::TIPO_DIESEL_COP
            && $role->alcance !== 'diesel_cop'
        ) {
            abort(
                422,
                'El rol seleccionado no corresponde a un usuario Diesel Cop.'
            );
        }

        if (
            $validated['tipo_usuario']
                === User::TIPO_EMPRESA
            && $role->alcance !== 'empresa'
        ) {
            abort(
                422,
                'El rol seleccionado no corresponde a un usuario de empresa.'
            );
        }
    }

    private function autorizarAccesoUsuario(
        User $usuario
    ): void {
        /** @var User $usuarioAutenticado */
        $usuarioAutenticado = Auth::user();

        if (
            $usuarioAutenticado->esUsuarioEmpresa()
            && (int) $usuarioAutenticado
                ->empresaIdOperativa()
                !== (int) $usuario->empresa_id
        ) {
            abort(
                403,
                'No tiene autorización para acceder a este usuario.'
            );
        }
    }

    private function validarUsuarioActivo(
        User $usuario
    ): void {
        if ($usuario->estado !== 'activo') {
            abort(
                403,
                'El usuario está inactivo y no puede modificarse.'
            );
        }
    }

    private function redirigirAFicha(
        Request $request,
        User $usuario,
        string $mensaje
    ): RedirectResponse {
        $ruta = $request->input('return_to')
            === 'ventana'
            ? 'usuarios.show.ventana'
            : 'usuarios.show';

        return redirect()
            ->route($ruta, $usuario)
            ->with('success', $mensaje);
    }
}