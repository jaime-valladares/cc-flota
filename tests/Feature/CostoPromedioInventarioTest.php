<?php

use App\Models\AbastecimientoTanque;
use App\Models\Empresa;
use App\Models\Gasolinera;
use App\Models\GasolineraExterna;
use App\Models\Marchamo;
use App\Models\Motorista;
use App\Models\MovimientoInventarioCombustible;
use App\Models\PuntoSeguridadUnidad;
use App\Models\RecargaCombustible;
use App\Models\Role;
use App\Models\Tanque;
use App\Models\Unidad;
use App\Models\User;
use App\Services\AbastecimientoService;
use Illuminate\Validation\ValidationException;

function contextoInventarioPromedio(array $tanques = [['nombre' => 'A', 'capacidad' => 20000]]): array
{
    $rol = Role::create([
        'codigo' => User::ROL_DIESEL_SUPER_ADMIN,
        'nombre' => 'Superadministrador costos',
        'alcance' => 'diesel_cop',
        'estado' => 'activo',
    ]);
    $usuario = User::factory()->create([
        'rol_id' => $rol->id,
        'tipo_usuario' => User::TIPO_DIESEL_COP,
        'empresa_id' => null,
    ]);
    $empresa = Empresa::create([
        'nombre_legal' => 'Empresa costos',
        'nombre_comercial' => 'Costos',
        'nit' => 'TEST-COSTOS',
        'estado' => 'activa',
    ]);
    $gasolinera = Gasolinera::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Gasolinera costos',
        'direccion' => 'Prueba',
        'estado' => 'activa',
        'fecha_creacion' => now(),
        'creado_por' => $usuario->id,
    ]);
    $modelos = collect($tanques)->map(fn (array $datos) => Tanque::create([
        'gasolinera_id' => $gasolinera->id,
        'nombre' => $datos['nombre'],
        'capacidad_total' => $datos['capacidad'],
        'volumen_actual' => $datos['volumen'] ?? 0,
        'valor_inventario_actual' => array_key_exists('valor', $datos)
            ? $datos['valor'] : '0.00000000',
        'costo_promedio_galon_actual' => $datos['costo'] ?? null,
        'volumen_minimo_alerta' => 100,
        'estado' => 'activo',
        'fecha_creacion' => now(),
        'creado_por' => $usuario->id,
    ]));

    return [$usuario, $empresa, $gasolinera, $modelos];
}

function recargarTanques($test, User $usuario, Gasolinera $gasolinera, array $volumenes, string $precio): void
{
    $test->actingAs($usuario)
        ->post(route('gasolineras.tanques.recargas.store', $gasolinera), [
            'precio_galon' => $precio,
            'volumenes' => $volumenes,
        ])
        ->assertSessionHasNoErrors();
}

function prepararUnidadCosto($test, User $usuario, Empresa $empresa): array
{
    $test->actingAs($usuario)->post(route('unidades.store'), [
        'empresa_id' => $empresa->id,
        'placa' => 'UNIDAD-COSTO',
        'marca' => 'Prueba',
        'cantidad_tanques' => 1,
        'tanques' => [['capacidad' => 1000, 'cubierto_por_licencia' => 1]],
        'modelo_medicion' => 'kilometros_galon',
        'rendimiento_teorico_km_galon' => 8,
    ])->assertSessionHasNoErrors();
    $unidad = Unidad::firstOrFail();

    $test->actingAs($usuario)->post(route('licencias.store'), [
        'empresa_id' => $empresa->id,
        'unidad_id' => $unidad->id,
        'periodo_vigencia_meses' => 12,
        'fecha_activacion' => now()->toDateString(),
    ])->assertSessionHasNoErrors();

    $codigos = $unidad->puntosSeguridad()->orderBy('id')->pluck('id')
        ->mapWithKeys(fn (int $id, int $i) => [$id => sprintf('%07d', 2000000 + $i)])->all();
    $test->actingAs($usuario)->post(
        route('marchamos.asignacion-inicial.guardar-avance', $unidad),
        ['marchamos' => $codigos]
    )->assertSessionHasNoErrors();
    $test->actingAs($usuario)->post(
        route('marchamos.asignacion-inicial.finalizar', $unidad)
    )->assertSessionHasNoErrors();

    $motorista = Motorista::create([
        'empresa_id' => $empresa->id,
        'nombres' => 'Ana',
        'apellidos' => 'Prueba',
        'licencia' => 'LIC-COSTO-01',
        'telefono' => '2222-2222',
        'estado' => 'activo',
        'fecha_creacion' => now(),
        'creado_por' => $usuario->id,
    ]);
    $punto = $unidad->puntosSeguridad()
        ->where('tipo_punto', 'tapón')->where('subgrupo', 'Depósito')->firstOrFail();

    return [$unidad->refresh(), $motorista, $punto];
}

test('primera y segunda recarga calculan costo promedio ponderado', function () {
    [$usuario, , $gasolinera, $tanques] = contextoInventarioPromedio();
    $tanque = $tanques->first();

    recargarTanques($this, $usuario, $gasolinera, [$tanque->id => '5000.00'], '4.0000');
    expect($tanque->refresh()->volumen_actual)->toBe('5000.00')
        ->and($tanque->valor_inventario_actual)->toBe('20000.00000000')
        ->and($tanque->costo_promedio_galon_actual)->toBe('4.00000000');
    $movimientoInicial = MovimientoInventarioCombustible::latest('id')->firstOrFail();
    expect($movimientoInicial->valor_inventario_anterior)->toBe('0.00000000')
        ->and($movimientoInicial->valor_movimiento)->toBe('20000.00000000')
        ->and($movimientoInicial->valor_inventario_resultante)->toBe('20000.00000000')
        ->and($movimientoInicial->costo_unitario_aplicado)->toBe('4.00000000');

    recargarTanques($this, $usuario, $gasolinera, [$tanque->id => '5000.00'], '5.0000');
    expect($tanque->refresh()->volumen_actual)->toBe('10000.00')
        ->and($tanque->valor_inventario_actual)->toBe('45000.00000000')
        ->and($tanque->costo_promedio_galon_actual)->toBe('4.50000000');
});

test('segunda recarga al mismo precio conserva el promedio', function () {
    [$usuario, , $gasolinera, $tanques] = contextoInventarioPromedio();
    $tanque = $tanques->first();
    recargarTanques($this, $usuario, $gasolinera, [$tanque->id => 100], '4.2500');
    recargarTanques($this, $usuario, $gasolinera, [$tanque->id => 200], '4.2500');
    expect($tanque->refresh()->costo_promedio_galon_actual)->toBe('4.25000000');
});

test('ejemplo de 3000 a 4.50 mas 7000 a 3.90 produce 4.08', function () {
    [$usuario, , $gasolinera, $tanques] = contextoInventarioPromedio();
    $tanque = $tanques->first();
    recargarTanques($this, $usuario, $gasolinera, [$tanque->id => 3000], '4.5000');
    recargarTanques($this, $usuario, $gasolinera, [$tanque->id => 7000], '3.9000');
    expect($tanque->refresh()->valor_inventario_actual)->toBe('40800.00000000')
        ->and($tanque->costo_promedio_galon_actual)->toBe('4.08000000');
});

test('recarga multitank calcula promedios independientes', function () {
    [$usuario, , $gasolinera, $tanques] = contextoInventarioPromedio([
        ['nombre' => 'A', 'capacidad' => 1000, 'volumen' => 100, 'valor' => '400.00000000', 'costo' => '4.00000000'],
        ['nombre' => 'B', 'capacidad' => 1000, 'volumen' => 300, 'valor' => '900.00000000', 'costo' => '3.00000000'],
    ]);
    recargarTanques($this, $usuario, $gasolinera, $tanques->mapWithKeys(fn ($t) => [$t->id => 100])->all(), '5.0000');
    expect($tanques[0]->refresh()->costo_promedio_galon_actual)->toBe('4.50000000')
        ->and($tanques[1]->refresh()->costo_promedio_galon_actual)->toBe('3.50000000');
});

test('inventario con costo desconocido no se valoriza artificialmente', function () {
    [$usuario, , $gasolinera, $tanques] = contextoInventarioPromedio([
        ['nombre' => 'A', 'capacidad' => 1000, 'volumen' => 100, 'valor' => null, 'costo' => null],
    ]);
    $this->actingAs($usuario)->post(route('gasolineras.tanques.recargas.store', $gasolinera), [
        'precio_galon' => '5.0000', 'volumenes' => [$tanques[0]->id => 100],
    ])->assertSessionHasErrors("volumenes.{$tanques[0]->id}");
    expect($tanques[0]->refresh()->volumen_actual)->toBe('100.00')
        ->and($tanques[0]->valor_inventario_actual)->toBeNull();
});

test('salida interna guarda snapshots y vaciar tanque deja costo nulo', function () {
    [$usuario, $empresa, $gasolinera, $tanques] = contextoInventarioPromedio([
        ['nombre' => 'A', 'capacidad' => 1000, 'volumen' => 300, 'valor' => '1350.00000000', 'costo' => '4.50000000'],
    ]);
    [$unidad, $motorista, $punto] = prepararUnidadCosto($this, $usuario, $empresa);
    $service = app(AbastecimientoService::class);

    $abastecimiento = $service->registrar([
        'empresa_id' => $empresa->id, 'unidad_id' => $unidad->id,
        'motorista_id' => $motorista->id, 'ultimo_abastecimiento_id' => null,
        'kilometraje_actual' => 100, 'volumen_inicial' => 0,
        'tipo_origen' => 'interno', 'gasolinera_interna_id' => $gasolinera->id,
        'tanques' => [['tanque_id' => $tanques[0]->id, 'galones' => 300]],
        'rutas' => [], 'marchamos' => [[
            'punto_seguridad_id' => $punto->id, 'nuevo_codigo_marchamo' => '7654321',
        ]],
    ], $usuario->id);

    $detalle = AbastecimientoTanque::where('abastecimiento_id', $abastecimiento->id)->firstOrFail();
    expect($detalle->costo_promedio_galon_snapshot)->toBe('4.50000000')
        ->and($detalle->costo_total_snapshot)->toBe('1350.00000000')
        ->and($tanques[0]->refresh()->volumen_actual)->toBe('0.00')
        ->and($tanques[0]->valor_inventario_actual)->toBe('0.00000000')
        ->and($tanques[0]->costo_promedio_galon_actual)->toBeNull();
});

test('salida multitank pondera costos y recarga posterior no cambia snapshots', function () {
    [$usuario, $empresa, $gasolinera, $tanques] = contextoInventarioPromedio([
        ['nombre' => 'A', 'capacidad' => 1000, 'volumen' => 500, 'valor' => '2000.00000000', 'costo' => '4.00000000'],
        ['nombre' => 'B', 'capacidad' => 1000, 'volumen' => 500, 'valor' => '2250.00000000', 'costo' => '4.50000000'],
    ]);
    [$unidad, $motorista, $punto] = prepararUnidadCosto($this, $usuario, $empresa);
    $abastecimiento = app(AbastecimientoService::class)->registrar([
        'empresa_id' => $empresa->id, 'unidad_id' => $unidad->id,
        'motorista_id' => $motorista->id, 'ultimo_abastecimiento_id' => null,
        'kilometraje_actual' => 100, 'volumen_inicial' => 0,
        'tipo_origen' => 'interno', 'gasolinera_interna_id' => $gasolinera->id,
        'tanques' => [
            ['tanque_id' => $tanques[0]->id, 'galones' => 100],
            ['tanque_id' => $tanques[1]->id, 'galones' => 200],
        ], 'rutas' => [], 'marchamos' => [[
            'punto_seguridad_id' => $punto->id, 'nuevo_codigo_marchamo' => '7654321',
        ]],
    ], $usuario->id);
    $detalles = AbastecimientoTanque::where('abastecimiento_id', $abastecimiento->id)->orderBy('orden')->get();
    expect($detalles->sum(fn ($d) => (float) $d->costo_total_snapshot))->toBe(1300.0);
    expect($tanques[0]->refresh()->volumen_actual)->toBe('400.00')
        ->and($tanques[0]->valor_inventario_actual)->toBe('1600.00000000')
        ->and($tanques[0]->costo_promedio_galon_actual)->toBe('4.00000000')
        ->and($tanques[1]->refresh()->volumen_actual)->toBe('300.00')
        ->and($tanques[1]->valor_inventario_actual)->toBe('1350.00000000')
        ->and($tanques[1]->costo_promedio_galon_actual)->toBe('4.50000000');

    recargarTanques($this, $usuario, $gasolinera, [$tanques[0]->id => 100], '5.0000');
    expect($detalles[0]->refresh()->costo_promedio_galon_snapshot)->toBe('4.00000000')
        ->and($detalles[0]->costo_total_snapshot)->toBe('400.00000000');
});

test('anulacion exige que la recarga sea ultimo movimiento en todos los tanques', function () {
    [$usuario, , $gasolinera, $tanques] = contextoInventarioPromedio();
    recargarTanques($this, $usuario, $gasolinera, [$tanques[0]->id => 100], '4.0000');
    $primera = RecargaCombustible::firstOrFail();
    recargarTanques($this, $usuario, $gasolinera, [$tanques[0]->id => 100], '6.0000');

    $this->actingAs($usuario)->patch(
        route('gasolineras.tanques.recargas.anular', [$gasolinera, $primera]),
        ['motivo_anulacion' => 'Prueba de integridad']
    )->assertSessionHasErrors('motivo_anulacion');
    expect($tanques[0]->refresh()->volumen_actual)->toBe('200.00')
        ->and($tanques[0]->valor_inventario_actual)->toBe('1000.00000000');
});

test('anulacion valida revierte exactamente volumen y valor', function () {
    [$usuario, , $gasolinera, $tanques] = contextoInventarioPromedio([
        ['nombre' => 'A', 'capacidad' => 1000, 'volumen' => 100, 'valor' => '400.00000000', 'costo' => '4.00000000'],
    ]);
    recargarTanques($this, $usuario, $gasolinera, [$tanques[0]->id => 100], '6.0000');
    $recarga = RecargaCombustible::firstOrFail();
    $this->actingAs($usuario)->patch(
        route('gasolineras.tanques.recargas.anular', [$gasolinera, $recarga]),
        ['motivo_anulacion' => 'Corrección válida']
    )->assertSessionHasNoErrors();
    expect($tanques[0]->refresh()->volumen_actual)->toBe('100.00')
        ->and($tanques[0]->valor_inventario_actual)->toBe('400.00000000')
        ->and($tanques[0]->costo_promedio_galon_actual)->toBe('4.00000000');
});

test('anulacion multitank es indivisible si un tanque tiene movimiento posterior', function () {
    [$usuario, , $gasolinera, $tanques] = contextoInventarioPromedio([
        ['nombre' => 'A', 'capacidad' => 1000], ['nombre' => 'B', 'capacidad' => 1000],
    ]);
    recargarTanques($this, $usuario, $gasolinera, [
        $tanques[0]->id => 100, $tanques[1]->id => 100,
    ], '4.0000');
    $recargaMultiple = RecargaCombustible::firstOrFail();
    recargarTanques($this, $usuario, $gasolinera, [$tanques[1]->id => 50], '5.0000');

    $this->actingAs($usuario)->patch(
        route('gasolineras.tanques.recargas.anular', [$gasolinera, $recargaMultiple]),
        ['motivo_anulacion' => 'Debe rechazarse completa']
    )->assertSessionHasErrors('motivo_anulacion');
    expect($tanques[0]->refresh()->volumen_actual)->toBe('100.00')
        ->and($tanques[0]->valor_inventario_actual)->toBe('400.00000000')
        ->and($tanques[1]->refresh()->volumen_actual)->toBe('150.00')
        ->and($tanques[1]->valor_inventario_actual)->toBe('650.00000000')
        ->and($recargaMultiple->refresh()->estado)->toBe('registrado');
});

test('creacion normal rechaza inventario inicial mayor que cero', function () {
    [$usuario, $empresa] = contextoInventarioPromedio();
    $this->actingAs($usuario)->post(route('gasolineras.store'), [
        'empresa_id' => $empresa->id, 'nombre' => 'Nueva', 'direccion' => 'Prueba',
        'tanques' => [[
            'nombre' => 'Inicial', 'capacidad_total' => 1000,
            'volumen_actual' => 10, 'volumen_minimo_alerta' => 100,
        ]],
    ])->assertSessionHasErrors('tanques.0.volumen_actual');
});

test('gasolinera externa conserva precio y total sin alterar inventario interno', function () {
    [$usuario, $empresa, , $tanques] = contextoInventarioPromedio();
    [$unidad, $motorista, $punto] = prepararUnidadCosto($this, $usuario, $empresa);
    $externa = GasolineraExterna::create([
        'empresa_id' => $empresa->id,
        'compania' => 'Proveedor externo',
        'direccion' => 'Prueba',
        'estado' => 'activa',
        'fecha_creacion' => now(),
        'creado_por' => $usuario->id,
    ]);

    $abastecimiento = app(AbastecimientoService::class)->registrar([
        'empresa_id' => $empresa->id, 'unidad_id' => $unidad->id,
        'motorista_id' => $motorista->id, 'ultimo_abastecimiento_id' => null,
        'kilometraje_actual' => 100, 'volumen_inicial' => 0,
        'tipo_origen' => 'externo', 'gasolinera_externa_id' => $externa->id,
        'galones_externos' => 10, 'precio_galon' => '4.2500',
        'rutas' => [], 'marchamos' => [[
            'punto_seguridad_id' => $punto->id, 'nuevo_codigo_marchamo' => '7654321',
        ]],
    ], $usuario->id);

    expect($abastecimiento->precio_galon)->toBe('4.2500')
        ->and($abastecimiento->total_pagado)->toBe('42.50')
        ->and(MovimientoInventarioCombustible::where('abastecimiento_id', $abastecimiento->id)->exists())->toBeFalse()
        ->and($tanques[0]->refresh()->volumen_actual)->toBe('0.00');
});

test('edicion de salida interna ajusta conjuntamente volumen y valor', function () {
    [$usuario, $empresa, $gasolinera, $tanques] = contextoInventarioPromedio([
        ['nombre' => 'A', 'capacidad' => 1000, 'volumen' => 500, 'valor' => '2250.00000000', 'costo' => '4.50000000'],
    ]);
    [$unidad, $motorista, $punto] = prepararUnidadCosto($this, $usuario, $empresa);
    $service = app(AbastecimientoService::class);
    $abastecimiento = $service->registrar([
        'empresa_id' => $empresa->id, 'unidad_id' => $unidad->id,
        'motorista_id' => $motorista->id, 'ultimo_abastecimiento_id' => null,
        'kilometraje_actual' => 100, 'volumen_inicial' => 0,
        'tipo_origen' => 'interno', 'gasolinera_interna_id' => $gasolinera->id,
        'tanques' => [['tanque_id' => $tanques[0]->id, 'galones' => 300]],
        'rutas' => [], 'marchamos' => [[
            'punto_seguridad_id' => $punto->id, 'nuevo_codigo_marchamo' => '7654321',
        ]],
    ], $usuario->id);

    $punto->refresh();
    $service->modificar($abastecimiento, [
        'abastecimiento_version' => $service->versionAbastecimiento($abastecimiento->fresh()),
        'empresa_id' => $empresa->id, 'unidad_id' => $unidad->id,
        'motorista_id' => $motorista->id,
        'kilometraje_actual' => 100, 'volumen_inicial' => 0,
        'tipo_origen' => 'interno', 'gasolinera_interna_id' => $gasolinera->id,
        'tanques' => [['tanque_id' => $tanques[0]->id, 'galones' => 200]],
        'rutas' => [], 'marchamos' => [[
            'punto_seguridad_id' => $punto->id,
            'marchamo_actual_id' => $punto->marchamo_actual_id,
            'nuevo_codigo_marchamo' => '7654322',
        ]],
    ], $usuario->id);

    expect($tanques[0]->refresh()->volumen_actual)->toBe('300.00')
        ->and($tanques[0]->valor_inventario_actual)->toBe('1350.00000000')
        ->and($tanques[0]->costo_promedio_galon_actual)->toBe('4.50000000')
        ->and(AbastecimientoTanque::where('abastecimiento_id', $abastecimiento->id)->value('costo_total_snapshot'))
        ->toBe('900.00000000');
});

test('edicion historica devuelve galones al costo snapshot sobre el estado actual', function () {
    [$usuario, $empresa, $gasolinera, $tanques] = contextoInventarioPromedio([
        ['nombre' => 'A', 'capacidad' => 2000, 'volumen' => 1000, 'valor' => '4000.00000000', 'costo' => '4.00000000'],
    ]);
    [$unidad, $motorista, $punto] = prepararUnidadCosto($this, $usuario, $empresa);
    $service = app(AbastecimientoService::class);
    $abastecimiento = $service->registrar([
        'empresa_id' => $empresa->id, 'unidad_id' => $unidad->id,
        'motorista_id' => $motorista->id, 'ultimo_abastecimiento_id' => null,
        'kilometraje_actual' => 100, 'volumen_inicial' => 0,
        'tipo_origen' => 'interno', 'gasolinera_interna_id' => $gasolinera->id,
        'tanques' => [['tanque_id' => $tanques[0]->id, 'galones' => 100]],
        'rutas' => [], 'marchamos' => [[
            'punto_seguridad_id' => $punto->id, 'nuevo_codigo_marchamo' => '7654321',
        ]],
    ], $usuario->id);
    recargarTanques($this, $usuario, $gasolinera, [$tanques[0]->id => 100], '6.0000');
    $movimientoRecarga = MovimientoInventarioCombustible::where('tipo_movimiento', 'entrada_recarga')->firstOrFail();
    $snapshotRecarga = $movimientoRecarga->only([
        'volumen_anterior', 'volumen_movimiento', 'volumen_resultante',
        'valor_inventario_anterior', 'valor_movimiento', 'valor_inventario_resultante',
        'costo_unitario_aplicado',
    ]);
    expect($tanques[0]->refresh()->volumen_actual)->toBe('1000.00')
        ->and($tanques[0]->valor_inventario_actual)->toBe('4200.00000000')
        ->and($tanques[0]->costo_promedio_galon_actual)->toBe('4.20000000');

    $punto->refresh();
    $service->modificar($abastecimiento, [
        'abastecimiento_version' => $service->versionAbastecimiento($abastecimiento->fresh()),
        'empresa_id' => $empresa->id, 'unidad_id' => $unidad->id,
        'motorista_id' => $motorista->id, 'kilometraje_actual' => 100,
        'volumen_inicial' => 0, 'tipo_origen' => 'interno',
        'gasolinera_interna_id' => $gasolinera->id,
        'tanques' => [['tanque_id' => $tanques[0]->id, 'galones' => 90]],
        'rutas' => [], 'marchamos' => [[
            'punto_seguridad_id' => $punto->id,
            'marchamo_actual_id' => $punto->marchamo_actual_id,
            'nuevo_codigo_marchamo' => '7654322',
        ]],
    ], $usuario->id);

    $ajuste = MovimientoInventarioCombustible::where('tipo_movimiento', 'ajuste_modificacion_abastecimiento')->firstOrFail();
    $detalle = AbastecimientoTanque::where('abastecimiento_id', $abastecimiento->id)->firstOrFail();
    expect($tanques[0]->refresh()->volumen_actual)->toBe('1010.00')
        ->and($tanques[0]->valor_inventario_actual)->toBe('4240.00000000')
        ->and($tanques[0]->costo_promedio_galon_actual)->toBe('4.19801980')
        ->and($ajuste->sentido_movimiento)->toBe('entrada')
        ->and($ajuste->volumen_movimiento)->toBe('10.00')
        ->and($ajuste->valor_movimiento)->toBe('40.00000000')
        ->and($ajuste->costo_unitario_aplicado)->toBe('4.00000000')
        ->and($detalle->galones_retirados)->toBe('90.00')
        ->and($detalle->costo_promedio_galon_snapshot)->toBe('4.00000000')
        ->and($detalle->costo_total_snapshot)->toBe('360.00000000')
        ->and($movimientoRecarga->refresh()->only(array_keys($snapshotRecarga)))->toBe($snapshotRecarga);
});

test('edicion historica retira galones adicionales al costo vigente', function () {
    [$usuario, $empresa, $gasolinera, $tanques] = contextoInventarioPromedio([
        ['nombre' => 'A', 'capacidad' => 2000, 'volumen' => 1000, 'valor' => '4000.00000000', 'costo' => '4.00000000'],
    ]);
    [$unidad, $motorista, $punto] = prepararUnidadCosto($this, $usuario, $empresa);
    $service = app(AbastecimientoService::class);
    $abastecimiento = $service->registrar([
        'empresa_id' => $empresa->id, 'unidad_id' => $unidad->id,
        'motorista_id' => $motorista->id, 'ultimo_abastecimiento_id' => null,
        'kilometraje_actual' => 100, 'volumen_inicial' => 0,
        'tipo_origen' => 'interno', 'gasolinera_interna_id' => $gasolinera->id,
        'tanques' => [['tanque_id' => $tanques[0]->id, 'galones' => 100]],
        'rutas' => [], 'marchamos' => [[
            'punto_seguridad_id' => $punto->id, 'nuevo_codigo_marchamo' => '7654321',
        ]],
    ], $usuario->id);
    recargarTanques($this, $usuario, $gasolinera, [$tanques[0]->id => 100], '6.0000');
    $movimientoRecarga = MovimientoInventarioCombustible::where('tipo_movimiento', 'entrada_recarga')->firstOrFail();
    $snapshotRecarga = $movimientoRecarga->only([
        'volumen_anterior', 'volumen_movimiento', 'volumen_resultante',
        'valor_inventario_anterior', 'valor_movimiento', 'valor_inventario_resultante',
        'costo_unitario_aplicado',
    ]);

    $punto->refresh();
    $service->modificar($abastecimiento, [
        'abastecimiento_version' => $service->versionAbastecimiento($abastecimiento->fresh()),
        'empresa_id' => $empresa->id, 'unidad_id' => $unidad->id,
        'motorista_id' => $motorista->id, 'kilometraje_actual' => 100,
        'volumen_inicial' => 0, 'tipo_origen' => 'interno',
        'gasolinera_interna_id' => $gasolinera->id,
        'tanques' => [['tanque_id' => $tanques[0]->id, 'galones' => 110]],
        'rutas' => [], 'marchamos' => [[
            'punto_seguridad_id' => $punto->id,
            'marchamo_actual_id' => $punto->marchamo_actual_id,
            'nuevo_codigo_marchamo' => '7654322',
        ]],
    ], $usuario->id);

    $ajuste = MovimientoInventarioCombustible::where('tipo_movimiento', 'ajuste_modificacion_abastecimiento')->firstOrFail();
    $detalle = AbastecimientoTanque::where('abastecimiento_id', $abastecimiento->id)->firstOrFail();
    expect($tanques[0]->refresh()->volumen_actual)->toBe('990.00')
        ->and($tanques[0]->valor_inventario_actual)->toBe('4158.00000000')
        ->and($tanques[0]->costo_promedio_galon_actual)->toBe('4.20000000')
        ->and($ajuste->sentido_movimiento)->toBe('salida')
        ->and($ajuste->volumen_movimiento)->toBe('10.00')
        ->and($ajuste->valor_movimiento)->toBe('42.00000000')
        ->and($ajuste->costo_unitario_aplicado)->toBe('4.20000000')
        ->and($detalle->galones_retirados)->toBe('110.00')
        ->and($detalle->costo_total_snapshot)->toBe('442.00000000')
        ->and($detalle->costo_promedio_galon_snapshot)->toBe('4.01818182')
        ->and($movimientoRecarga->refresh()->only(array_keys($snapshotRecarga)))->toBe($snapshotRecarga);
});
