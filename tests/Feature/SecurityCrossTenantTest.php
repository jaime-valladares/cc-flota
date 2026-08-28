<?php

use App\Models\Empresa;
use App\Models\GasolineraExterna;
use App\Models\Motorista;
use App\Models\PuntoRuta;
use App\Models\Role;
use App\Models\Ruta;
use App\Models\Unidad;
use App\Models\User;
use Database\Seeders\PermisosSeeder;
use Database\Seeders\RolesSeeder;
use Database\Seeders\RolPermisosSeeder;

beforeEach(function () {
    $this->seed([RolesSeeder::class, PermisosSeeder::class, RolPermisosSeeder::class]);
});

function tenantFixture(string $suffix): array
{
    $empresa = Empresa::query()->create([
        'nombre_legal' => "Tenant {$suffix}",
        'nombre_comercial' => "TEN {$suffix}",
        'nit' => "TEN-{$suffix}",
        'estado' => 'activa',
    ]);
    $user = User::factory()->create([
        'rol_id' => Role::query()->where('codigo', User::ROL_EMPRESA_ADMIN)->value('id'),
        'tipo_usuario' => User::TIPO_EMPRESA,
        'empresa_id' => $empresa->id,
        'estado' => 'activo',
    ]);
    $unidad = Unidad::query()->create([
        'empresa_id' => $empresa->id,
        'placa' => "TEN-{$suffix}",
        'marca' => 'Prueba',
        'total_tanques' => 1,
        'cantidad_tanques_con_licencia' => 1,
        'capacidad_total' => 100,
        'capacidad_cubierta' => 100,
        'modelo_medicion' => 'kilometros_galon',
        'rendimiento_teorico_km_galon' => 10,
        'estado' => 'activa',
    ]);
    $motorista = Motorista::query()->create([
        'empresa_id' => $empresa->id,
        'nombres' => "Motorista {$suffix}",
        'apellidos' => 'Seguridad',
        'licencia' => "LIC-{$suffix}",
        'telefono' => '70000000',
        'estado' => 'activo',
    ]);
    $gasolinera = GasolineraExterna::query()->create([
        'empresa_id' => $empresa->id,
        'compania' => "Externa {$suffix}",
        'direccion' => 'Dirección de prueba',
        'estado' => 'activa',
        'creado_por' => $user->id,
    ]);
    $origen = PuntoRuta::query()->create([
        'empresa_id' => $empresa->id,
        'nombre' => "Origen {$suffix}",
        'direccion' => 'Origen',
        'estado' => 'activo',
        'creado_por' => $user->id,
    ]);
    $destino = PuntoRuta::query()->create([
        'empresa_id' => $empresa->id,
        'nombre' => "Destino {$suffix}",
        'direccion' => 'Destino',
        'estado' => 'activo',
        'creado_por' => $user->id,
    ]);
    $ruta = Ruta::query()->create([
        'empresa_id' => $empresa->id,
        'punto_origen_id' => $origen->id,
        'punto_destino_id' => $destino->id,
        'ruta' => "Ruta {$suffix}",
        'kilometros_estimados' => 100,
        'galones_estimados' => 10,
        'estado' => 'activo',
        'creado_por' => $user->id,
    ]);

    return compact('empresa', 'user', 'unidad', 'motorista', 'gasolinera', 'origen', 'destino', 'ruta');
}

function assertTenantDenied($test, User $actor, string $routeName, mixed $parameter): void
{
    $response = $test->actingAs($actor)->get(route($routeName, $parameter));
    expect($response->status())->toBeIn([403, 404], "{$routeName} devolvió {$response->status()}");
}

test('acceso directo a fichas edición ventanas y PDFs de otra empresa nunca devuelve 200', function () {
    $a = tenantFixture('A');
    $b = tenantFixture('B');
    $actor = $a['user'];

    $attempts = [
        ['usuarios.show', $b['user']],
        ['usuarios.edit', $b['user']],
        ['usuarios.show.ventana', $b['user']],
        ['unidades.show', $b['unidad']],
        ['unidades.edit', $b['unidad']],
        ['unidades.show.ventana', $b['unidad']],
        ['unidades.edit.ventana', $b['unidad']],
        ['reportes.unidades.show', $b['unidad']],
        ['reportes.unidades.show.ventana', $b['unidad']],
        ['reportes.unidades.show.pdf', $b['unidad']],
        ['motoristas.show', $b['motorista']],
        ['motoristas.edit', $b['motorista']],
        ['motoristas.show.ventana', $b['motorista']],
        ['motoristas.edit.ventana', $b['motorista']],
        ['reportes.gestion-combustible-motorista.show', $b['motorista']],
        ['reportes.gestion-combustible-motorista.show.ventana', $b['motorista']],
        ['reportes.gestion-combustible-motorista.show.pdf', $b['motorista']],
        ['gasolineras-externas.show', $b['gasolinera']],
        ['gasolineras-externas.edit', $b['gasolinera']],
        ['gasolineras-externas.show.ventana', $b['gasolinera']],
        ['gasolineras-externas.edit.ventana', $b['gasolinera']],
        ['puntos-ruta.show', $b['origen']],
        ['puntos-ruta.edit', $b['origen']],
        ['puntos-ruta.show.ventana', $b['origen']],
        ['puntos-ruta.edit.ventana', $b['origen']],
        ['rutas.show', $b['ruta']],
        ['rutas.edit', $b['ruta']],
        ['rutas.show.ventana', $b['ruta']],
        ['rutas.edit.ventana', $b['ruta']],
    ];

    foreach ($attempts as [$routeName, $resource]) {
        assertTenantDenied($this, $actor, $routeName, $resource);
    }
});

test('filtros forzados de otra empresa son rechazados en reportes sensibles', function (string $routeName, string $filter) {
    $a = tenantFixture('FA');
    $b = tenantFixture('FB');
    $value = match ($filter) {
        'empresa_ids' => $b['empresa']->id,
        'unidad_ids' => $b['unidad']->id,
        'motorista_ids' => $b['motorista']->id,
    };

    $response = $this->actingAs($a['user'])->get(route($routeName, [
        'consultar' => 1,
        $filter => [$value],
    ]));

    expect($response->status())->toBeIn([403, 404], "{$routeName} aceptó {$filter} cross-tenant con HTTP {$response->status()}");
})->with([
    'unidades por empresa' => ['reportes.unidades.index', 'empresa_ids'],
    'unidades por unidad' => ['reportes.unidades.index', 'unidad_ids'],
    'PDF unidades por empresa' => ['reportes.unidades.pdf', 'empresa_ids'],
    'PDF unidades por unidad' => ['reportes.unidades.pdf', 'unidad_ids'],
    'km gal por empresa' => ['reportes.rendimiento-km-galon.index', 'empresa_ids'],
    'PDF km gal por empresa' => ['reportes.rendimiento-km-galon.pdf', 'empresa_ids'],
    'gal hora por unidad' => ['reportes.rendimiento-galones-hora.index', 'unidad_ids'],
    'PDF gal hora por unidad' => ['reportes.rendimiento-galones-hora.pdf', 'unidad_ids'],
    'gal viaje por motorista' => ['reportes.rendimiento-galones-viaje.index', 'motorista_ids'],
    'PDF gal viaje por motorista' => ['reportes.rendimiento-galones-viaje.pdf', 'motorista_ids'],
    'gestión motorista por empresa' => ['reportes.gestion-combustible-motorista.index', 'empresa_ids'],
    'gestión motorista por unidad' => ['reportes.gestion-combustible-motorista.index', 'unidad_ids'],
    'gestión motorista por motorista' => ['reportes.gestion-combustible-motorista.index', 'motorista_ids'],
    'PDF gestión motorista por empresa' => ['reportes.gestion-combustible-motorista.pdf', 'empresa_ids'],
]);

test('filtros mixtos propia y ajena no amplían el tenant', function (string $routeName, string $filter) {
    $a = tenantFixture('MA');
    $b = tenantFixture('MB');
    [$own, $foreign] = match ($filter) {
        'empresa_ids' => [$a['empresa']->id, $b['empresa']->id],
        'unidad_ids' => [$a['unidad']->id, $b['unidad']->id],
        'motorista_ids' => [$a['motorista']->id, $b['motorista']->id],
    };
    $response = $this->actingAs($a['user'])->get(route($routeName, [
        'consultar' => 1,
        $filter => [$own, $foreign],
    ]));

    expect($response->status())->toBeIn([403, 404], "{$routeName} aceptó mezcla cross-tenant con HTTP {$response->status()}");
})->with([
    'km gal empresas mixtas' => ['reportes.rendimiento-km-galon.index', 'empresa_ids'],
    'gal hora unidades mixtas' => ['reportes.rendimiento-galones-hora.index', 'unidad_ids'],
    'gal viaje motoristas mixtos' => ['reportes.rendimiento-galones-viaje.index', 'motorista_ids'],
    'gestión motorista empresas mixtas' => ['reportes.gestion-combustible-motorista.index', 'empresa_ids'],
]);

test('usuario empresarial no puede autoescalar ni editar un usuario de otro tenant', function () {
    $a = tenantFixture('EA');
    $b = tenantFixture('EB');
    $dieselRole = Role::query()->where('codigo', User::ROL_DIESEL_ADMIN)->firstOrFail();
    $originalRole = $a['user']->rol_id;

    $self = $this->actingAs($a['user'])->put(route('usuarios.update', $a['user']), [
        'rol_id' => $dieselRole->id,
    ]);
    expect($self->status())->toBeIn([302, 403, 422]);
    expect($a['user']->refresh()->rol_id)->toBe($originalRole);

    $foreign = $this->actingAs($a['user'])->put(route('usuarios.update', $b['user']), [
        'rol_id' => $dieselRole->id,
    ]);
    expect($foreign->status())->toBeIn([403, 404]);
    expect($b['user']->refresh()->rol_id)->not->toBe($dieselRole->id);
});
