<?php

use App\Models\Empresa;
use App\Models\Gasolinera;
use App\Models\Permiso;
use App\Models\Role;
use App\Models\RolPermiso;
use App\Models\User;
use App\Models\Unidad;
use Database\Seeders\PermisosSeeder;
use Database\Seeders\RolesSeeder;
use Database\Seeders\RolPermisosSeeder;

beforeEach(function () {
    $this->seed([RolesSeeder::class, PermisosSeeder::class, RolPermisosSeeder::class]);
});

function securityRoles(): array
{
    return [
        User::ROL_DIESEL_SUPER_ADMIN,
        User::ROL_DIESEL_ADMIN,
        User::ROL_DIESEL_TECNICO,
        User::ROL_DIESEL_AUDITOR,
        User::ROL_EMPRESA_ADMIN,
        User::ROL_EMPRESA_SUPERVISOR,
        User::ROL_EMPRESA_OPERADOR,
        User::ROL_EMPRESA_AUDITOR,
    ];
}

function securityEmpresa(string $suffix): Empresa
{
    return Empresa::query()->create([
        'nombre_legal' => "Seguridad {$suffix}",
        'nombre_comercial' => "SEC {$suffix}",
        'nit' => "SEC-{$suffix}",
        'estado' => 'activa',
    ]);
}

function securityUser(string $role, ?Empresa $empresa = null, string $estado = 'activo'): User
{
    $isEmpresa = str_starts_with($role, 'EMPRESA_');

    return User::factory()->create([
        'rol_id' => Role::query()->where('codigo', $role)->value('id'),
        'tipo_usuario' => $isEmpresa ? User::TIPO_EMPRESA : User::TIPO_DIESEL_COP,
        'empresa_id' => $isEmpresa ? $empresa?->id : null,
        'estado' => $estado,
    ]);
}

function securityGetRoutes(): array
{
    return [
        ['dashboard', null],
        ['empresas.index', 'empresas.consultar'],
        ['empresas.consulta.ventana', 'empresas.consultar'],
        ['empresas.administrar', 'empresas.administrar'],
        ['empresas.administrar.ventana', 'empresas.administrar'],
        ['empresas.create', 'empresas.crear'],
        ['empresas.create.ventana', 'empresas.crear'],
        ['usuarios.index', 'usuarios.consultar'],
        ['usuarios.consulta.ventana', 'usuarios.consultar'],
        ['usuarios.administrar', 'usuarios.administrar'],
        ['usuarios.administrar.ventana', 'usuarios.administrar'],
        ['usuarios.create', 'usuarios.crear'],
        ['usuarios.create.ventana', 'usuarios.crear'],
        ['unidades.index', 'unidades.consultar'],
        ['unidades.consulta.ventana', 'unidades.consultar'],
        ['unidades.administrar', 'unidades.administrar'],
        ['unidades.administrar.ventana', 'unidades.administrar'],
        ['unidades.create', 'unidades.crear'],
        ['unidades.create.ventana', 'unidades.crear'],
        ['licencias.index', 'licencias.consultar'],
        ['licencias.consulta.ventana', 'licencias.consultar'],
        ['licencias.administrar', 'licencias.administrar'],
        ['licencias.administrar.ventana', 'licencias.administrar'],
        ['licencias.create', 'licencias.crear'],
        ['licencias.create.ventana', 'licencias.crear'],
        ['marchamos.index', 'marchamos.consultar'],
        ['marchamos.consulta.ventana', 'marchamos.consultar'],
        ['marchamos.asignacion-inicial.index', 'marchamos.asignar_inicial'],
        ['marchamos.asignacion-inicial.index.ventana', 'marchamos.asignar_inicial'],
        ['marchamos.reemplazos.index', 'marchamos.administrar'],
        ['marchamos.reemplazos.index.ventana', 'marchamos.administrar'],
        ['gasolineras.index', 'gasolineras.consultar'],
        ['gasolineras.consulta.ventana', 'gasolineras.consultar'],
        ['gasolineras.administrar', 'gasolineras.administrar'],
        ['gasolineras.administrar.ventana', 'gasolineras.administrar'],
        ['gasolineras.create', 'gasolineras.crear'],
        ['gasolineras.create.ventana', 'gasolineras.crear'],
        ['gasolineras.tanques.index', 'tanques.administrar'],
        ['gasolineras.tanques.index.ventana', 'tanques.administrar'],
        ['gasolineras.tanques.recargas.index', 'recargas_tanques.registrar'],
        ['gasolineras.tanques.recargas.index.ventana', 'recargas_tanques.registrar'],
        ['gasolineras-externas.index', 'gasolineras_externas.consultar'],
        ['gasolineras-externas.consulta.ventana', 'gasolineras_externas.consultar'],
        ['gasolineras-externas.administrar', 'gasolineras_externas.administrar'],
        ['gasolineras-externas.administrar.ventana', 'gasolineras_externas.administrar'],
        ['gasolineras-externas.create', 'gasolineras_externas.crear'],
        ['gasolineras-externas.create.ventana', 'gasolineras_externas.crear'],
        ['motoristas.index', 'motoristas.consultar'],
        ['motoristas.consulta.ventana', 'motoristas.consultar'],
        ['motoristas.administrar', 'motoristas.administrar'],
        ['motoristas.administrar.ventana', 'motoristas.administrar'],
        ['motoristas.create', 'motoristas.crear'],
        ['motoristas.create.ventana', 'motoristas.crear'],
        ['puntos-ruta.index', 'puntos_ruta.consultar'],
        ['puntos-ruta.consulta.ventana', 'puntos_ruta.consultar'],
        ['puntos-ruta.administrar', 'puntos_ruta.administrar'],
        ['puntos-ruta.administrar.ventana', 'puntos_ruta.administrar'],
        ['puntos-ruta.create', 'puntos_ruta.crear'],
        ['puntos-ruta.create.ventana', 'puntos_ruta.crear'],
        ['rutas.index', 'rutas.consultar'],
        ['rutas.consulta.ventana', 'rutas.consultar'],
        ['rutas.administrar', 'rutas.administrar'],
        ['rutas.administrar.ventana', 'rutas.administrar'],
        ['rutas.create', 'rutas.crear'],
        ['rutas.create.ventana', 'rutas.crear'],
        ['abastecimientos.consulta', 'abastecimientos.consultar'],
        ['abastecimientos.consulta.ventana', 'abastecimientos.consultar'],
        ['abastecimientos.ciclos.index', 'abastecimientos.consultar'],
        ['abastecimientos.ciclos.ventana', 'abastecimientos.consultar'],
        ['analisis.panel-operativo', 'auditoria.consultar'],
        ['analisis.panel-operativo.ventana', 'auditoria.consultar'],
        ['analisis.consumo-unidades.index', 'analisis.consultar'],
        ['analisis.consumo-unidades.index.ventana', 'analisis.consultar'],
        ['analisis.rendimientos.index', 'analisis.consultar'],
        ['analisis.rendimientos.index.ventana', 'analisis.consultar'],
        ['analisis.rutas.index', 'analisis.consultar'],
        ['analisis.rutas.index.ventana', 'analisis.consultar'],
        ['auditoria.abastecimientos.index', 'auditoria.consultar'],
        ['auditoria.abastecimientos.index.ventana', 'auditoria.consultar'],
        ['auditoria.marchamos.index', 'auditoria.consultar'],
        ['auditoria.marchamos.index.ventana', 'auditoria.consultar'],
        ['reportes.unidades.index', 'reportes.unidades.ficha'],
        ['reportes.unidades.ventana', 'reportes.unidades.ficha'],
        ['reportes.rendimiento-km-galon.index', 'reportes.rendimiento-km-galon.consultar'],
        ['reportes.rendimiento-km-galon.ventana', 'reportes.rendimiento-km-galon.consultar'],
        ['reportes.rendimiento-galones-hora.index', 'reportes.rendimiento-km-galon.consultar'],
        ['reportes.rendimiento-galones-hora.ventana', 'reportes.rendimiento-km-galon.consultar'],
        ['reportes.rendimiento-galones-viaje.index', 'reportes.rendimiento-km-galon.consultar'],
        ['reportes.rendimiento-galones-viaje.ventana', 'reportes.rendimiento-km-galon.consultar'],
        ['reportes.gestion-combustible-motorista.index', 'reportes.gestion-combustible-motorista.consultar'],
        ['reportes.gestion-combustible-motorista.ventana', 'reportes.gestion-combustible-motorista.consultar'],
    ];
}

test('los ocho roles y la matriz activa sembrada son la fuente efectiva de autorización', function () {
    expect(Role::query()->whereIn('codigo', securityRoles())->count())->toBe(8);

    $empresa = securityEmpresa('MATRIX');
    foreach (securityRoles() as $roleCode) {
        $user = securityUser($roleCode, $empresa);
        foreach (Permiso::query()->get() as $permission) {
            $assigned = $roleCode === User::ROL_DIESEL_SUPER_ADMIN
                || ($permission->estado === 'activo' && RolPermiso::query()
                    ->where('rol_id', $user->rol_id)->where('permiso_id', $permission->id)->exists());
            expect($user->tienePermiso($permission->codigo))
                ->toBe($assigned, "{$roleCode} / {$permission->codigo}");
        }
    }
});

test('la matriz HTTP GET aplica a rutas principales ventanas análisis auditorías y reportes', function () {
    $empresa = securityEmpresa('HTTP');
    foreach (securityRoles() as $roleCode) {
        $user = securityUser($roleCode, $empresa);
        foreach (securityGetRoutes() as [$routeName, $permission]) {
            $response = $this->actingAs($user)->get(route($routeName));
            $allowed = $permission === null || $user->tienePermiso($permission);
            $allowed
                ? $response->assertOk()
                : $response->assertForbidden();
        }
    }
});

test('reportes y recargas conservan exactamente la matriz aprobada', function () {
    $reportsAllowed = [
        User::ROL_DIESEL_SUPER_ADMIN, User::ROL_DIESEL_ADMIN, User::ROL_DIESEL_AUDITOR,
        User::ROL_EMPRESA_ADMIN, User::ROL_EMPRESA_SUPERVISOR, User::ROL_EMPRESA_AUDITOR,
    ];
    $reloadAllowed = [
        User::ROL_DIESEL_SUPER_ADMIN, User::ROL_DIESEL_ADMIN, User::ROL_DIESEL_TECNICO,
        User::ROL_EMPRESA_ADMIN, User::ROL_EMPRESA_SUPERVISOR, User::ROL_EMPRESA_OPERADOR,
    ];

    foreach (['reportes.rendimiento-km-galon.consultar', 'reportes.gestion-combustible-motorista.consultar'] as $code) {
        expect(Permiso::query()->where('codigo', $code)->firstOrFail()->roles()->orderBy('codigo')->pluck('codigo')->all())
            ->toBe(collect($reportsAllowed)->sort()->values()->all());
    }
    foreach (['recargas_tanques.registrar', 'recargas_tanques.anular'] as $code) {
        expect(Permiso::query()->where('codigo', $code)->firstOrFail()->roles()->orderBy('codigo')->pluck('codigo')->all())
            ->toBe(collect($reloadAllowed)->sort()->values()->all());
    }
});

test('usuario rol y permiso inactivos no conservan acceso', function () {
    $empresa = securityEmpresa('STATES');
    $inactiveUser = securityUser(User::ROL_DIESEL_ADMIN, $empresa, 'inactivo');
    $this->actingAs($inactiveUser)->get(route('empresas.index'))->assertRedirect(route('login'));
    $this->assertGuest();

    $inactiveRoleUser = securityUser(User::ROL_DIESEL_ADMIN);
    $inactiveRoleUser->role->update(['estado' => 'inactivo']);
    $this->actingAs($inactiveRoleUser)->get(route('empresas.index'))->assertForbidden();

    $inactivePermissionUser = securityUser(User::ROL_DIESEL_AUDITOR);
    Permiso::query()->where('codigo', 'empresas.consultar')->update(['estado' => 'inactivo']);
    $this->actingAs($inactivePermissionUser)->get(route('empresas.index'))->assertForbidden();
});

test('rutas protegidas representativas redirigen a login sin autenticación', function (string $routeName) {
    $this->get(route($routeName))->assertRedirect(route('login'));
})->with([
    'dashboard' => ['dashboard'],
    'usuarios' => ['usuarios.index'],
    'unidades' => ['unidades.index'],
    'abastecimientos' => ['abastecimientos.consulta'],
    'reportes' => ['reportes.rendimiento-km-galon.index'],
    'auditoria' => ['auditoria.abastecimientos.index'],
]);

test('acciones críticas sin permiso devuelven 403 y no modifican datos', function () {
    $empresa = securityEmpresa('WRITE');
    $auditor = securityUser(User::ROL_DIESEL_AUDITOR);
    $target = securityUser(User::ROL_EMPRESA_OPERADOR, $empresa);
    $unit = Unidad::query()->create([
        'empresa_id' => $empresa->id, 'placa' => 'SEC-WRITE', 'marca' => 'Prueba',
        'total_tanques' => 1, 'cantidad_tanques_con_licencia' => 0,
        'capacidad_total' => 100, 'capacidad_cubierta' => 0,
        'modelo_medicion' => 'kilometros_galon', 'rendimiento_teorico_km_galon' => 10,
        'estado' => 'activa',
    ]);
    $station = Gasolinera::query()->create([
        'empresa_id' => $empresa->id, 'nombre' => 'SEC estación', 'direccion' => 'Prueba',
        'estado' => 'activa', 'creado_por' => $target->id,
    ]);
    $before = [
        'empresas' => Empresa::query()->count(),
        'users' => User::query()->count(),
    ];

    $this->actingAs($auditor)->post(route('empresas.store'), [])->assertForbidden();
    $this->actingAs($auditor)->post(route('usuarios.store'), [])->assertForbidden();
    $this->actingAs($auditor)->put(route('usuarios.update', $target), ['rol_id' => $auditor->rol_id])->assertForbidden();
    $this->actingAs($auditor)->post(route('unidades.store'), [])->assertForbidden();
    $this->actingAs($auditor)->put(route('unidades.update', $unit), [])->assertForbidden();
    $this->actingAs($auditor)->patch(route('unidades.inactivar', $unit))->assertForbidden();
    $this->actingAs($auditor)->post(route('licencias.store'), [])->assertForbidden();
    $this->actingAs($auditor)->post(route('marchamos.reemplazos.store', $unit), [])->assertForbidden();
    $this->actingAs($auditor)->post(route('gasolineras.tanques.recargas.store', $station), [])->assertForbidden();
    $this->actingAs($auditor)->post(route('abastecimientos.store', $unit), [])->assertForbidden();

    expect(Empresa::query()->count())->toBe($before['empresas'])
        ->and(User::query()->count())->toBe($before['users'])
        ->and($target->refresh()->rol_id)->not->toBe($auditor->rol_id);
});
