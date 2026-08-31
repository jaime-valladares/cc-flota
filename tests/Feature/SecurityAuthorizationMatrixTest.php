<?php

use App\Models\Empresa;
use App\Models\Gasolinera;
use App\Models\Permiso;
use App\Models\Role;
use App\Models\RolPermiso;
use App\Models\Unidad;
use App\Models\User;
use Database\Seeders\PermisosSeeder;
use Database\Seeders\RolesSeeder;
use Database\Seeders\RolPermisosSeeder;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed([RolesSeeder::class, PermisosSeeder::class, RolPermisosSeeder::class]);
});

/** Contrato oficial; no se deriva del seeder ni de tienePermiso(). */
function securityExpectedRolePermissions(): array
{
    $read = ['abastecimientos.consultar', 'motoristas.consultar', 'rutas.consultar', 'puntos_ruta.consultar', 'gasolineras_externas.consultar', 'gasolineras.consultar', 'marchamos.consultar', 'unidades.consultar'];
    $audit = ['reportes.gestion-combustible-motorista.consultar', 'reportes.rendimiento-km-galon.consultar', 'reportes.unidades.ficha', 'auditoria.consultar', 'analisis.consultar'];
    $catalogAdmin = ['motoristas.administrar', 'motoristas.editar', 'motoristas.inactivar', 'motoristas.reactivar', 'rutas.administrar', 'rutas.editar', 'rutas.inactivar', 'rutas.reactivar', 'puntos_ruta.administrar', 'puntos_ruta.editar', 'puntos_ruta.inactivar', 'puntos_ruta.reactivar', 'gasolineras_externas.administrar', 'gasolineras_externas.editar', 'gasolineras_externas.inactivar', 'gasolineras_externas.reactivar', 'gasolineras.administrar', 'gasolineras.editar', 'gasolineras.inactivar', 'gasolineras.reactivar', 'tanques.administrar', 'tanques.crear', 'tanques.editar', 'tanques.inactivar', 'tanques.reactivar'];
    $marchamos = ['marchamos.administrar', 'marchamos.reemplazar'];
    $recargas = ['recargas_tanques.registrar', 'recargas_tanques.anular'];
    $unidades = ['unidades.administrar', 'unidades.editar', 'unidades.inactivar', 'unidades.reactivar'];
    $usuarios = ['usuarios.consultar', 'usuarios.administrar', 'usuarios.editar', 'usuarios.inactivar', 'usuarios.reactivar'];
    $creates = ['motoristas.crear', 'rutas.crear', 'puntos_ruta.crear', 'gasolineras_externas.crear'];

    return [
        User::ROL_DIESEL_SUPER_ADMIN => 'ALL',
        User::ROL_DIESEL_ADMIN => array_values(array_unique([...$audit, ...$read, ...$catalogAdmin, ...$marchamos, ...$recargas, ...$unidades, ...$usuarios, 'licencias.consultar', 'licencias.administrar', 'licencias.editar', 'licencias.inactivar', 'licencias.reactivar', 'empresas.consultar', 'empresas.administrar', 'empresas.editar', 'empresas.inactivar', 'empresas.reactivar', 'puntos_seguridad.ver', 'puntos_seguridad.crear', 'puntos_seguridad.actualizar', 'puntos_seguridad.inactivar', 'puntos_seguridad.reactivar', 'reemplazos.ver', 'reemplazos.crear', 'reemplazos.corregir', 'reemplazos.anular', 'inventario.ver', 'inventario.ajustar'])),
        User::ROL_DIESEL_TECNICO => [...$read, 'abastecimientos.registrar', ...$marchamos, ...$recargas, 'empresas.consultar', 'puntos_seguridad.ver', 'puntos_seguridad.crear', 'puntos_seguridad.actualizar', 'reemplazos.ver', 'reemplazos.crear'],
        User::ROL_DIESEL_AUDITOR => [...$audit, ...$read, 'licencias.consultar', 'usuarios.consultar', 'empresas.consultar'],
        User::ROL_EMPRESA_ADMIN => array_values(array_unique([...$audit, ...$read, 'abastecimientos.registrar', ...$catalogAdmin, ...$creates, ...$marchamos, ...$recargas, ...$unidades, ...$usuarios, 'usuarios.crear', 'gasolineras.crear', 'licencias.consultar', 'reemplazos.ver', 'reemplazos.crear', 'reemplazos.corregir', 'reemplazos.anular', 'inventario.ver', 'inventario.ajustar'])),
        User::ROL_EMPRESA_SUPERVISOR => array_values(array_unique([...$audit, ...$read, ...$catalogAdmin, ...$marchamos, ...$recargas, ...$unidades, ...$usuarios, 'licencias.consultar', 'reemplazos.ver', 'reemplazos.crear', 'inventario.ver'])),
        User::ROL_EMPRESA_OPERADOR => [...$read, 'abastecimientos.registrar', ...$marchamos, ...$recargas, 'reemplazos.ver', 'reemplazos.crear', 'inventario.ver'],
        User::ROL_EMPRESA_AUDITOR => [...$audit, ...$read, 'licencias.consultar', 'usuarios.consultar'],
    ];
}

function securityRoles(): array
{
    return array_keys(securityExpectedRolePermissions());
}

function securityAllows(string $role, string $permission): bool
{
    $expected = securityExpectedRolePermissions()[$role];

    return $expected === 'ALL' || in_array($permission, $expected, true);
}

function securityEmpresa(string $suffix): Empresa
{
    return Empresa::query()->create(['nombre_legal' => "Seguridad {$suffix}", 'nombre_comercial' => "SEC {$suffix}", 'nit' => 'SEC-'.substr(md5($suffix), 0, 12), 'estado' => 'activa']);
}

function securityUser(string $role, ?Empresa $empresa = null, string $estado = 'activo'): User
{
    $isEmpresa = str_starts_with($role, 'EMPRESA_');

    return User::factory()->create(['rol_id' => Role::query()->where('codigo', $role)->value('id'), 'tipo_usuario' => $isEmpresa ? User::TIPO_EMPRESA : User::TIPO_DIESEL_COP, 'empresa_id' => $isEmpresa ? $empresa?->id : null, 'estado' => $estado]);
}

function securitySafeGetCases(): array
{
    return [
        'empresas' => ['empresas.index', 'empresas.consultar', 200],
        'usuarios' => ['usuarios.index', 'usuarios.consultar', 200],
        'unidades' => ['unidades.index', 'unidades.consultar', 200],
        'licencias' => ['licencias.index', 'licencias.consultar', 200],
        'marchamos' => ['marchamos.index', 'marchamos.consultar', 200],
        'asignación inicial' => ['marchamos.asignacion-inicial.index', 'marchamos.asignar_inicial', 200],
        'reemplazos' => ['marchamos.reemplazos.index', 'marchamos.administrar', 200],
        'recargas' => ['gasolineras.tanques.recargas.index', 'recargas_tanques.registrar', 200],
        'abastecer' => ['abastecimientos.index', 'abastecimientos.registrar', 200],
        'auditoría' => ['auditoria.abastecimientos.index', 'auditoria.consultar', 200],
        'análisis' => ['analisis.rendimientos.index', 'analisis.consultar', 200],
        'panel operativo' => ['analisis.panel-operativo', 'auditoria.consultar', 200],
        'reporte unidades' => ['reportes.unidades.index', 'reportes.unidades.ficha', 200],
        'reporte rendimiento' => ['reportes.rendimiento-km-galon.index', 'reportes.rendimiento-km-galon.consultar', 200],
        'reporte motorista' => ['reportes.gestion-combustible-motorista.index', 'reportes.gestion-combustible-motorista.consultar', 200],
    ];
}

function securityResourceGetCases(): array
{
    return [
        'empresa show' => ['empresas.show', 'empresas.administrar', 'empresa', 200],
        'usuario show' => ['usuarios.show', 'usuarios.administrar', 'usuario', 200],
        'unidad show' => ['unidades.show', 'unidades.administrar', 'unidad', 200],
        'unidad edit' => ['unidades.edit', 'unidades.editar', 'unidad', 200],
    ];
}

function securityPdfCases(): array
{
    return [
        'unidades' => ['reportes.unidades.pdf', 'reportes.unidades.ficha'],
        'km galón' => ['reportes.rendimiento-km-galon.pdf', 'reportes.rendimiento-km-galon.consultar'],
        'galones hora' => ['reportes.rendimiento-galones-hora.pdf', 'reportes.rendimiento-km-galon.consultar'],
        'galones viaje' => ['reportes.rendimiento-galones-viaje.pdf', 'reportes.rendimiento-km-galon.consultar'],
        'motorista' => ['reportes.gestion-combustible-motorista.pdf', 'reportes.gestion-combustible-motorista.consultar'],
    ];
}

/** Catálogo literal de las 218 rutas protegidas; una ruta nueva exige clasificación. */
function securityProtectedRouteNames(): array
{
    $names = <<<'ROUTES'
abastecimientos.index abastecimientos.administrar abastecimientos.administrar.ventana abastecimientos.ciclos.index abastecimientos.ciclos.ventana abastecimientos.ciclos.show abastecimientos.ciclos.show.ventana abastecimientos.consulta abastecimientos.consulta.ventana abastecimientos.store abastecimientos.create abastecimientos.create.ventana abastecimientos.index.ventana abastecimientos.show abastecimientos.show.ventana
analisis.consumo-unidades.index analisis.consumo-unidades.index.ventana analisis.panel-operativo analisis.panel-operativo.ventana analisis.rendimientos.index analisis.rendimientos.index.ventana analisis.rutas.index analisis.rutas.index.ventana auditoria.abastecimientos.index auditoria.abastecimientos.index.ventana auditoria.marchamos.index auditoria.marchamos.index.ventana
empresas.index empresas.store empresas.administrar empresas.administrar.ventana empresas.consulta.ventana empresas.create empresas.create.ventana empresas.show empresas.update empresas.edit empresas.edit.ventana empresas.inactivar empresas.reactivar empresas.show.ventana
gasolineras.index gasolineras.store gasolineras.administrar gasolineras.administrar.ventana gasolineras.consulta.ventana gasolineras.create gasolineras.create.ventana gasolineras.show gasolineras.update gasolineras.edit gasolineras.edit.ventana gasolineras.show.ventana gasolineras.inactivar gasolineras.reactivar gasolineras.tanques.store gasolineras.tanques.update gasolineras.tanques.show gasolineras.tanques.show.ventana gasolineras.tanques.inactivar gasolineras.tanques.reactivar gasolineras.tanques.index gasolineras.tanques.index.ventana gasolineras.tanques.recargas.index gasolineras.tanques.recargas.index.ventana gasolineras.tanques.recargas.store gasolineras.tanques.recargas.create gasolineras.tanques.recargas.create.ventana gasolineras.tanques.recargas.anular gasolineras.tanques.recargas.show gasolineras.tanques.recargas.show.ventana
gasolineras-externas.index gasolineras-externas.store gasolineras-externas.administrar gasolineras-externas.administrar.ventana gasolineras-externas.consulta.ventana gasolineras-externas.create gasolineras-externas.create.ventana gasolineras-externas.show gasolineras-externas.update gasolineras-externas.edit gasolineras-externas.edit.ventana gasolineras-externas.show.ventana gasolineras-externas.inactivar gasolineras-externas.reactivar
licencias.index licencias.store licencias.administrar licencias.administrar.ventana licencias.consulta.ventana licencias.create licencias.create.ventana licencias.show licencias.update licencias.edit licencias.edit.ventana licencias.show.ventana licencias.inactivar licencias.reactivar licencias.renovar
marchamos.index marchamos.asignacion-inicial.index marchamos.asignacion-inicial.index.ventana marchamos.consulta.ventana marchamos.detalle-unidad.ventana marchamos.reemplazos.index marchamos.reemplazos.show marchamos.reemplazos.store marchamos.reemplazos.show.ventana marchamos.reemplazos.index.ventana marchamos.detalle-unidad marchamos.asignacion-inicial.show marchamos.asignacion-inicial.guardar-avance marchamos.asignacion-inicial.extras.store marchamos.asignacion-inicial.extras.update marchamos.asignacion-inicial.extras.destroy marchamos.asignacion-inicial.show.ventana marchamos.asignacion-inicial.finalizar
motoristas.index motoristas.store motoristas.administrar motoristas.administrar.ventana motoristas.consulta.ventana motoristas.create motoristas.create.ventana motoristas.show motoristas.update motoristas.edit motoristas.edit.ventana motoristas.show.ventana motoristas.inactivar motoristas.reactivar
puntos-ruta.index puntos-ruta.store puntos-ruta.administrar puntos-ruta.administrar.ventana puntos-ruta.consulta.ventana puntos-ruta.create puntos-ruta.create.ventana puntos-ruta.show puntos-ruta.update puntos-ruta.edit puntos-ruta.edit.ventana puntos-ruta.show.ventana puntos-ruta.inactivar puntos-ruta.reactivar
reportes.gestion-combustible-motorista.index reportes.gestion-combustible-motorista.pdf reportes.gestion-combustible-motorista.ventana reportes.gestion-combustible-motorista.show reportes.gestion-combustible-motorista.show.pdf reportes.gestion-combustible-motorista.show.ventana reportes.rendimiento-galones-hora.index reportes.rendimiento-galones-hora.pdf reportes.rendimiento-galones-hora.ventana reportes.rendimiento-galones-hora.show reportes.rendimiento-galones-hora.show.pdf reportes.rendimiento-galones-hora.show.ventana reportes.rendimiento-galones-viaje.index reportes.rendimiento-galones-viaje.pdf reportes.rendimiento-galones-viaje.ventana reportes.rendimiento-galones-viaje.show reportes.rendimiento-galones-viaje.show.pdf reportes.rendimiento-galones-viaje.show.ventana reportes.rendimiento-km-galon.index reportes.rendimiento-km-galon.pdf reportes.rendimiento-km-galon.ventana reportes.rendimiento-km-galon.show reportes.rendimiento-km-galon.show.pdf reportes.rendimiento-km-galon.show.ventana reportes.unidades.index reportes.unidades.pdf reportes.unidades.ventana reportes.unidades.show reportes.unidades.show.pdf reportes.unidades.show.ventana
rutas.index rutas.store rutas.administrar rutas.administrar.ventana rutas.consulta.ventana rutas.create rutas.create.ventana rutas.show rutas.update rutas.edit rutas.edit.ventana rutas.show.ventana rutas.inactivar rutas.reactivar
unidades.index unidades.store unidades.administrar unidades.administrar.ventana unidades.consulta.ventana unidades.create unidades.create.ventana unidades.show unidades.update unidades.edit unidades.edit.ventana unidades.show.ventana unidades.inactivar unidades.reactivar
usuarios.index usuarios.store usuarios.administrar usuarios.administrar.ventana usuarios.consulta.ventana usuarios.create usuarios.create.ventana usuarios.show usuarios.update usuarios.edit usuarios.edit.ventana usuarios.inactivar usuarios.reactivar usuarios.show.ventana
ROUTES;

    return preg_split('/\s+/', trim($names));
}

test('la matriz oficial coincide exactamente con los pivots activos', function () {
    expect(Role::query()->whereIn('codigo', securityRoles())->count())->toBe(8);
    $all = Permiso::query()->where('estado', 'activo')->pluck('codigo')->sort()->values()->all();
    foreach (securityExpectedRolePermissions() as $roleCode => $expected) {
        $role = Role::query()->where('codigo', $roleCode)->firstOrFail();
        $actual = RolPermiso::query()->where('rol_id', $role->id)->join('permisos', 'permisos.id', '=', 'rol_permisos.permiso_id')->where('permisos.estado', 'activo')->pluck('permisos.codigo')->sort()->values()->all();
        expect($actual)->toBe($expected === 'ALL' ? $all : collect($expected)->sort()->values()->all(), "Matriz divergente para {$roleCode}");
    }
});

test('toda ruta con permiso está registrada en el catálogo de seguridad', function () {
    $catalog = collect(securityProtectedRouteNames())->sort()->values();
    $actual = collect(Route::getRoutes()->getRoutes())->filter(fn ($route) => collect($route->gatherMiddleware())->contains(fn (string $middleware) => str_starts_with($middleware, 'permiso:')))->pluck('action.as')->sort()->values();
    expect($catalog)->toHaveCount(218)->and($catalog->unique())->toHaveCount(218)->and($actual->all())->toBe($catalog->all());
});

test('rutas GET seguras cumplen la matriz literal', function (string $routeName, string $permission, int $allowedStatus) {
    $empresa = securityEmpresa('GET-'.$routeName);
    foreach (securityRoles() as $role) {
        $response = $this->actingAs(securityUser($role, $empresa))->get(route($routeName));
        securityAllows($role, $permission) ? $response->assertStatus($allowedStatus) : $response->assertStatus(403);
    }
})->with(securitySafeGetCases());

test('rutas GET con recurso cumplen la matriz literal', function (string $routeName, string $permission, string $fixture, int $allowedStatus) {
    $empresa = securityEmpresa('RESOURCE-'.$routeName);
    $target = securityUser(User::ROL_EMPRESA_OPERADOR, $empresa);
    $unit = Unidad::query()->create(['empresa_id' => $empresa->id, 'placa' => 'SEC-'.substr(md5($routeName), 0, 7), 'marca' => 'Prueba', 'total_tanques' => 1, 'cantidad_tanques_con_licencia' => 0, 'capacidad_total' => 100, 'capacidad_cubierta' => 0, 'modelo_medicion' => 'kilometros_galon', 'rendimiento_teorico_km_galon' => 10, 'estado' => 'activa']);
    $resource = ['empresa' => $empresa, 'usuario' => $target, 'unidad' => $unit][$fixture];
    foreach (securityRoles() as $role) {
        $response = $this->actingAs(securityUser($role, $empresa))->get(route($routeName, $resource));
        securityAllows($role, $permission) ? $response->assertStatus($allowedStatus) : $response->assertStatus(403);
    }
})->with(securityResourceGetCases());

test('PDFs de listado cumplen la matriz literal', function (string $routeName, string $permission) {
    $empresa = securityEmpresa('PDF-'.$routeName);

    foreach (securityRoles() as $role) {
        if (securityAllows($role, $permission)) {
            continue;
        }

        $this->actingAs(securityUser($role, $empresa))
            ->get(route($routeName, ['consultar' => 1]))
            ->assertStatus(403);
    }

    /*
     * La matriz literal y sus pivots validan todos los roles autorizados. Aquí
     * basta un representante por familia: repetir Dompdf para cada rol agrega
     * trabajo y memoria, mientras la generación funcional vive en sus tests.
     */
    $this->actingAs(securityUser(User::ROL_DIESEL_SUPER_ADMIN))
        ->get(route($routeName, ['consultar' => 1]))
        ->assertStatus(200);
})->with(securityPdfCases());

test('mutaciones denegadas terminan en 403 antes de validar o escribir', function () {
    $empresa = securityEmpresa('WRITE');
    $auditor = securityUser(User::ROL_DIESEL_AUDITOR);
    $target = securityUser(User::ROL_EMPRESA_OPERADOR, $empresa);
    $unit = Unidad::query()->create(['empresa_id' => $empresa->id, 'placa' => 'SEC-WRITE', 'marca' => 'Prueba', 'total_tanques' => 1, 'cantidad_tanques_con_licencia' => 0, 'capacidad_total' => 100, 'capacidad_cubierta' => 0, 'modelo_medicion' => 'kilometros_galon', 'rendimiento_teorico_km_galon' => 10, 'estado' => 'activa']);
    $station = Gasolinera::query()->create(['empresa_id' => $empresa->id, 'nombre' => 'SEC estación', 'direccion' => 'Prueba', 'estado' => 'activa', 'creado_por' => $target->id]);
    $counts = [Empresa::count(), User::count(), Unidad::count()];
    $role = $target->rol_id;
    $attempts = [fn () => $this->actingAs($auditor)->post(route('empresas.store'), []), fn () => $this->actingAs($auditor)->post(route('usuarios.store'), []), fn () => $this->actingAs($auditor)->put(route('usuarios.update', $target), ['rol_id' => $auditor->rol_id]), fn () => $this->actingAs($auditor)->post(route('unidades.store'), []), fn () => $this->actingAs($auditor)->put(route('unidades.update', $unit), []), fn () => $this->actingAs($auditor)->patch(route('unidades.inactivar', $unit)), fn () => $this->actingAs($auditor)->post(route('licencias.store'), []), fn () => $this->actingAs($auditor)->post(route('marchamos.reemplazos.store', $unit), []), fn () => $this->actingAs($auditor)->post(route('gasolineras.tanques.recargas.store', $station), []), fn () => $this->actingAs($auditor)->post(route('abastecimientos.store', $unit), [])];
    foreach ($attempts as $attempt) {
        $attempt()->assertStatus(403);
    }
    expect([Empresa::count(), User::count(), Unidad::count()])->toBe($counts)->and($target->refresh()->rol_id)->toBe($role)->and($unit->refresh()->estado)->toBe('activa');
});

test('estados y contextos inválidos son rechazados', function () {
    $empresa = securityEmpresa('CONTEXT');
    $inactive = securityUser(User::ROL_DIESEL_ADMIN, null, 'inactivo');
    $this->actingAs($inactive)->get(route('empresas.index'))->assertRedirect(route('login'));
    $this->assertGuest();
    $inactiveRole = securityUser(User::ROL_DIESEL_ADMIN);
    $inactiveRole->role->update(['estado' => 'inactivo']);
    $this->actingAs($inactiveRole)->get(route('empresas.index'))->assertStatus(403);
    $inactiveRole->role->update(['estado' => 'activo']);
    $inactivePermission = securityUser(User::ROL_DIESEL_AUDITOR);
    Permiso::query()->where('codigo', 'empresas.consultar')->update(['estado' => 'inactivo']);
    $this->actingAs($inactivePermission)->get(route('empresas.index'))->assertStatus(403);
});

test('rol sin pivot y contextos incoherentes devuelven 403', function () {
    $empresa = securityEmpresa('INVALID');
    $withoutPivot = securityUser(User::ROL_DIESEL_ADMIN);
    RolPermiso::query()->where('rol_id', $withoutPivot->rol_id)->delete();
    $this->actingAs($withoutPivot)->get(route('empresas.index'))->assertStatus(403);
    $empresaWithoutTenant = securityUser(User::ROL_EMPRESA_ADMIN, $empresa);
    $empresaWithoutTenant->update(['empresa_id' => null]);
    $this->actingAs($empresaWithoutTenant)->get(route('unidades.index'))->assertStatus(403);
    $dieselWithTenant = securityUser(User::ROL_DIESEL_ADMIN);
    $dieselWithTenant->update(['empresa_id' => $empresa->id]);
    $this->actingAs($dieselWithTenant)->get(route('empresas.index'))->assertStatus(403);
});

test('usuario no autenticado es redirigido antes de evaluar permisos', function (string $routeName) {
    $this->get(route($routeName))->assertRedirect(route('login'));
})->with(['usuarios' => ['usuarios.index'], 'unidades' => ['unidades.index'], 'abastecimientos' => ['abastecimientos.index'], 'auditoria' => ['auditoria.abastecimientos.index'], 'reporte' => ['reportes.rendimiento-km-galon.index']]);
