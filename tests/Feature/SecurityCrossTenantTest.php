<?php

use App\Models\Abastecimiento;
use App\Models\Empresa;
use App\Models\Gasolinera;
use App\Models\GasolineraExterna;
use App\Models\Licencia;
use App\Models\Marchamo;
use App\Models\Motorista;
use App\Models\MovimientoInventarioCombustible;
use App\Models\PuntoRuta;
use App\Models\PuntoSeguridadUnidad;
use App\Models\RecargaCombustible;
use App\Models\ReemplazoMarchamoEvento;
use App\Models\Role;
use App\Models\Ruta;
use App\Models\Tanque;
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

function tenantOperationalFixture(string $suffix): array
{
    $fixture = tenantFixture($suffix);
    $gasolineraInterna = Gasolinera::query()->create([
        'empresa_id' => $fixture['empresa']->id,
        'nombre' => "Interna {$suffix}",
        'direccion' => 'Dirección interna',
        'estado' => 'activa',
        'creado_por' => $fixture['user']->id,
    ]);
    $tanque = Tanque::query()->create([
        'gasolinera_id' => $gasolineraInterna->id,
        'nombre' => "Tanque {$suffix}",
        'capacidad_total' => 1000,
        'volumen_actual' => 500,
        'volumen_minimo_alerta' => 100,
        'estado' => 'activo',
        'creado_por' => $fixture['user']->id,
    ]);
    $licencia = Licencia::query()->create([
        'empresa_id' => $fixture['empresa']->id,
        'unidad_id' => $fixture['unidad']->id,
        'periodo_vigencia_meses' => 12,
        'fecha_activacion' => now()->subMonth()->toDateString(),
        'fecha_vencimiento' => now()->addMonths(11)->toDateString(),
        'estado' => 'activa',
        'plantilla_puntos_seguridad' => 'plantilla_1_tanque',
        'creado_por' => $fixture['user']->id,
    ]);
    $puntoSeguridad = PuntoSeguridadUnidad::query()->create([
        'unidad_id' => $fixture['unidad']->id,
        'orden' => 1,
        'nombre_punto' => "Punto {$suffix}",
        'requiere_marchamo' => true,
        'plantilla_origen' => 'plantilla_1_tanque',
        'estado_asignacion' => 'asignado',
        'estado' => 'activo',
        'creado_por' => $fixture['user']->id,
    ]);
    $marchamo = Marchamo::query()->create([
        'empresa_id' => $fixture['empresa']->id,
        'unidad_id' => $fixture['unidad']->id,
        'punto_seguridad_id' => $puntoSeguridad->id,
        'codigo_marchamo' => substr(str_pad((string) crc32($suffix), 7, '0'), 0, 7),
        'fecha_activacion' => now(),
        'estado' => 'activo',
        'activo_actual' => 1,
        'origen_creacion' => 'asignacion_inicial',
        'creado_por' => $fixture['user']->id,
    ]);
    $puntoSeguridad->update(['marchamo_actual_id' => $marchamo->id]);
    $recarga = RecargaCombustible::query()->create([
        'empresa_id' => $fixture['empresa']->id,
        'gasolinera_id' => $gasolineraInterna->id,
        'precio_galon' => 4,
        'total_galones' => 10,
        'total_compra' => 40,
        'fecha_hora_recarga' => now(),
        'usuario_registra_id' => $fixture['user']->id,
        'estado' => 'registrado',
    ]);
    $abastecimiento = Abastecimiento::query()->create([
        'empresa_id' => $fixture['empresa']->id, 'unidad_id' => $fixture['unidad']->id,
        'motorista_id' => $fixture['motorista']->id, 'registrado_por' => $fixture['user']->id,
        'empresa_nombre_snapshot' => $fixture['empresa']->nombre_legal,
        'unidad_placa_snapshot' => $fixture['unidad']->placa,
        'motorista_nombre_snapshot' => $fixture['motorista']->nombres,
        'fecha_hora_abastecimiento' => now(), 'estado' => 'registrado',
        'modelo_medicion' => 'kilometros_galon', 'lectura_actual' => 100,
        'kilometraje_actual' => 100, 'volumen_inicial' => 10, 'volumen_cargado' => 20,
        'volumen_final' => 30, 'capacidad_cubierta_snapshot' => 100,
        'tipo_origen' => 'externo', 'gasolinera_externa_id' => $fixture['gasolinera']->id,
        'origen_nombre_snapshot' => $fixture['gasolinera']->compania,
    ]);

    return $fixture + compact('gasolineraInterna', 'tanque', 'licencia', 'puntoSeguridad', 'marchamo', 'recarga', 'abastecimiento');
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

test('licencias ajenas son rechazadas y ninguna mutación altera sus datos', function () {
    $a = tenantOperationalFixture('LA');
    $b = tenantOperationalFixture('LB');
    $before = $b['licencia']->only(['empresa_id', 'unidad_id', 'estado', 'fecha_vencimiento']);
    $before['fecha_vencimiento'] = $b['licencia']->fecha_vencimiento->toDateString();

    foreach (['licencias.show', 'licencias.show.ventana', 'licencias.edit', 'licencias.edit.ventana'] as $routeName) {
        $this->actingAs($a['user'])->get(route($routeName, $b['licencia']))->assertStatus(403);
    }
    foreach (['licencias.inactivar', 'licencias.reactivar', 'licencias.renovar'] as $routeName) {
        $this->actingAs($a['user'])->patch(route($routeName, $b['licencia']), [])->assertStatus(403);
    }
    $this->actingAs($a['user'])->put(route('licencias.update', $b['licencia']), [])->assertStatus(403);

    $after = $b['licencia']->refresh()->only(array_keys($before));
    $after['fecha_vencimiento'] = $b['licencia']->fecha_vencimiento->toDateString();
    expect($after)->toBe($before)
        ->and($b['unidad']->refresh()->estado)->toBe('activa');
});

test('gasolineras internas ajenas no pueden consultarse ni mutarse', function () {
    $a = tenantOperationalFixture('GA');
    $b = tenantOperationalFixture('GB');
    $before = $b['gasolineraInterna']->only(['empresa_id', 'nombre', 'estado']);

    foreach (['gasolineras.show', 'gasolineras.show.ventana', 'gasolineras.edit', 'gasolineras.edit.ventana'] as $routeName) {
        $this->actingAs($a['user'])->get(route($routeName, $b['gasolineraInterna']))->assertStatus(403);
    }
    $this->actingAs($a['user'])->put(route('gasolineras.update', $b['gasolineraInterna']), [])->assertStatus(403);
    $this->actingAs($a['user'])->patch(route('gasolineras.inactivar', $b['gasolineraInterna']), [])->assertStatus(403);
    $this->actingAs($a['user'])->patch(route('gasolineras.reactivar', $b['gasolineraInterna']), [])->assertStatus(403);

    expect($b['gasolineraInterna']->refresh()->only(array_keys($before)))->toBe($before);
});

test('tanques exigen tenant y parentesco con la gasolinera de la ruta', function () {
    $a = tenantOperationalFixture('TA');
    $b = tenantOperationalFixture('TB');
    $before = $b['tanque']->only(['gasolinera_id', 'volumen_actual', 'estado']);

    foreach (['gasolineras.tanques.show', 'gasolineras.tanques.show.ventana'] as $routeName) {
        $this->actingAs($a['user'])->get(route($routeName, [$b['gasolineraInterna'], $b['tanque']]))->assertStatus(403);
        $this->actingAs($a['user'])->get(route($routeName, [$a['gasolineraInterna'], $b['tanque']]))->assertStatus(404);
    }
    $this->actingAs($a['user'])->post(route('gasolineras.tanques.store', $b['gasolineraInterna']), [])->assertStatus(403);
    foreach (['gasolineras.tanques.update', 'gasolineras.tanques.inactivar', 'gasolineras.tanques.reactivar'] as $routeName) {
        $method = $routeName === 'gasolineras.tanques.update' ? 'put' : 'patch';
        $this->actingAs($a['user'])->{$method}(route($routeName, [$a['gasolineraInterna'], $b['tanque']]), [])->assertStatus(404);
    }

    expect($b['tanque']->refresh()->only(array_keys($before)))->toBe($before)
        ->and(Tanque::query()->count())->toBe(2);
});

test('recargas manipuladas son rechazadas sin alterar inventario ni movimientos', function () {
    $a = tenantOperationalFixture('RA');
    $b = tenantOperationalFixture('RB');
    $tankBefore = $b['tanque']->only(['gasolinera_id', 'volumen_actual', 'estado']);
    $reloadBefore = $b['recarga']->only(['empresa_id', 'gasolinera_id', 'total_galones', 'estado']);
    $counts = [RecargaCombustible::count(), MovimientoInventarioCombustible::count()];

    foreach (['gasolineras.tanques.recargas.show', 'gasolineras.tanques.recargas.show.ventana'] as $routeName) {
        $this->actingAs($a['user'])->get(route($routeName, [$b['gasolineraInterna'], $b['tanque']]))->assertStatus(403);
        $this->actingAs($a['user'])->get(route($routeName, [$a['gasolineraInterna'], $b['tanque']]))->assertStatus(404);
    }
    $this->actingAs($a['user'])->post(route('gasolineras.tanques.recargas.store', $b['gasolineraInterna']), [])->assertStatus(403);
    $this->actingAs($a['user'])->patch(route('gasolineras.tanques.recargas.anular', [$a['gasolineraInterna'], $b['recarga']]), [])->assertStatus(404);

    expect([RecargaCombustible::count(), MovimientoInventarioCombustible::count()])->toBe($counts)
        ->and($b['tanque']->refresh()->only(array_keys($tankBefore)))->toBe($tankBefore)
        ->and($b['recarga']->refresh()->only(array_keys($reloadBefore)))->toBe($reloadBefore);
});

test('marchamos de unidad ajena son rechazados sin cambios relacionados', function () {
    $a = tenantOperationalFixture('MA2');
    $b = tenantOperationalFixture('MB2');
    $pointBefore = $b['puntoSeguridad']->only(['unidad_id', 'marchamo_actual_id', 'estado_asignacion', 'estado']);
    $sealBefore = $b['marchamo']->only(['empresa_id', 'unidad_id', 'punto_seguridad_id', 'estado', 'activo_actual']);
    $counts = [Marchamo::count(), ReemplazoMarchamoEvento::count(), PuntoSeguridadUnidad::count()];

    foreach (['marchamos.detalle-unidad', 'marchamos.detalle-unidad.ventana', 'marchamos.reemplazos.show', 'marchamos.reemplazos.show.ventana'] as $routeName) {
        $this->actingAs($a['user'])->get(route($routeName, $b['unidad']))->assertStatus(403);
    }
    foreach (['marchamos.asignacion-inicial.show', 'marchamos.asignacion-inicial.show.ventana'] as $routeName) {
        $this->actingAs($a['user'])->get(route($routeName, $b['unidad']))->assertStatus(403);
    }
    $this->actingAs($a['user'])->post(route('marchamos.asignacion-inicial.guardar-avance', $b['unidad']), [])->assertStatus(403);
    $this->actingAs($a['user'])->post(route('marchamos.asignacion-inicial.finalizar', $b['unidad']), [])->assertStatus(403);
    $this->actingAs($a['user'])->post(route('marchamos.reemplazos.store', $b['unidad']), ['reemplazos' => [['seleccionado' => 1, 'punto_seguridad_id' => $b['puntoSeguridad']->id]]])->assertStatus(403);
    $this->actingAs($a['user'])->post(route('marchamos.reemplazos.store', $a['unidad']), [
        'reemplazos' => [[
            'seleccionado' => 1,
            'punto_seguridad_id' => $b['puntoSeguridad']->id,
            'nuevo_codigo_marchamo' => '7123456',
            'motivo_reemplazo' => 'dano',
        ]],
    ])->assertRedirect()->assertSessionHasErrors('reemplazos');

    expect([Marchamo::count(), ReemplazoMarchamoEvento::count(), PuntoSeguridadUnidad::count()])->toBe($counts)
        ->and($b['puntoSeguridad']->refresh()->only(array_keys($pointBefore)))->toBe($pointBefore)
        ->and($b['marchamo']->refresh()->only(array_keys($sealBefore)))->toBe($sealBefore);
});

test('abastecimientos ajenos no pueden abrirse ni registrarse y no afectan inventario', function () {
    $a = tenantOperationalFixture('AA');
    $b = tenantOperationalFixture('AB');
    $counts = [Abastecimiento::count(), MovimientoInventarioCombustible::count()];

    foreach (['abastecimientos.create', 'abastecimientos.create.ventana'] as $routeName) {
        $response = $this->actingAs($a['user'])->get(route($routeName, $b['unidad']));
        expect($response->status())->toBe(403, $routeName);
    }
    foreach (['abastecimientos.show', 'abastecimientos.show.ventana', 'abastecimientos.ciclos.show', 'abastecimientos.ciclos.show.ventana'] as $routeName) {
        $response = $this->actingAs($a['user'])->get(route($routeName, $b['abastecimiento']));
        expect($response->status())->toBe(403, $routeName);
    }
    $response = $this->actingAs($a['user'])->post(route('abastecimientos.store', $b['unidad']), [
        'empresa_id' => $b['empresa']->id,
        'unidad_id' => $b['unidad']->id,
        'motorista_id' => $b['motorista']->id,
        'kilometraje_actual' => '200.00',
        'tipo_origen' => 'externo',
        'gasolinera_externa_id' => $b['gasolinera']->id,
        'galones_externos' => '10.00',
        'precio_galon' => '4.00',
        'marchamos' => [[
            'punto_seguridad_id' => $b['puntoSeguridad']->id,
            'nuevo_codigo_marchamo' => '7654321',
        ]],
    ]);
    expect($response->status())->toBe(403, 'abastecimientos.store');

    expect([Abastecimiento::count(), MovimientoInventarioCombustible::count()])->toBe($counts);
});

test('detalles y PDFs de rendimiento rechazan ciclos de otra empresa', function (string $routeName) {
    $a = tenantOperationalFixture('RDA');
    $b = tenantOperationalFixture('RDB');
    $this->actingAs($a['user'])->get(route($routeName, $b['abastecimiento']))->assertStatus(403);
})->with([
    'km galón detalle' => ['reportes.rendimiento-km-galon.show'],
    'km galón PDF' => ['reportes.rendimiento-km-galon.show.pdf'],
    'galones hora detalle' => ['reportes.rendimiento-galones-hora.show'],
    'galones hora PDF' => ['reportes.rendimiento-galones-hora.show.pdf'],
    'galones viaje detalle' => ['reportes.rendimiento-galones-viaje.show'],
    'galones viaje PDF' => ['reportes.rendimiento-galones-viaje.show.pdf'],
]);

test('análisis y auditorías no amplían el tenant mediante filtros ajenos', function (string $routeName, string $filter, string $fixtureKey) {
    $a = tenantOperationalFixture('FIA');
    $b = tenantOperationalFixture('FIB');
    $response = $this->actingAs($a['user'])->get(route($routeName, [
        'consultar' => 1,
        $filter => [$b[$fixtureKey]->id],
    ]));

    $response->assertStatus(200)->assertDontSee($b['unidad']->placa);
})->with([
    'consumo empresa' => ['analisis.consumo-unidades.index', 'empresa_ids', 'empresa'],
    'consumo unidad' => ['analisis.consumo-unidades.index', 'unidad_ids', 'unidad'],
    'rendimientos empresa' => ['analisis.rendimientos.index', 'empresa_ids', 'empresa'],
    'rutas motorista' => ['analisis.rutas.index', 'motorista_ids', 'motorista'],
    'rutas ruta' => ['analisis.rutas.index', 'ruta_ids', 'ruta'],
    'panel unidad' => ['analisis.panel-operativo', 'unidad_ids', 'unidad'],
    'auditoría abastecimiento' => ['auditoria.abastecimientos.index', 'unidad_ids', 'unidad'],
    'auditoría marchamos' => ['auditoria.marchamos.index', 'unidad_ids', 'unidad'],
]);
