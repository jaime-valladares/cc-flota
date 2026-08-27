<?php
use App\Http\Controllers\AnalisisConsumoUnidadController;
use App\Models\Empresa;
use App\Models\Gasolinera;
use App\Models\GasolineraExterna;
use App\Models\Motorista;
use App\Models\Permiso;
use App\Models\PuntoRuta;
use App\Models\Role;
use App\Models\RolPermiso;
use App\Models\Ruta;
use App\Models\Tanque;
use App\Models\Unidad;
use App\Models\User;
use App\Services\AbastecimientoService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
function contextoFullToFull($test, string $modelo = 'kilometros_galon'): array
{
    $rol = Role::create([
        'codigo' => User::ROL_DIESEL_SUPER_ADMIN,
        'nombre' => 'Superadministrador M4',
        'alcance' => 'diesel_cop',
        'estado' => 'activa',
    ]);
    $usuario = User::factory()->create([
        'rol_id' => $rol->id,
        'tipo_usuario' => User::TIPO_DIESEL_COP,
        'empresa_id' => null,
    ]);
    $empresa = Empresa::create([
        'nombre_legal' => 'Empresa M4',
        'nombre_comercial' => 'M4',
        'nit' => 'TEST-M4',
        'estado' => 'activa',
    ]);
    $gasolinera = Gasolinera::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Gasolinera M4',
        'direccion' => 'Prueba',
        'estado' => 'activa',
        'fecha_creacion' => now(),
        'creado_por' => $usuario->id,
    ]);
    $tanque = Tanque::create([
        'gasolinera_id' => $gasolinera->id,
        'nombre' => 'Tanque M4',
        'capacidad_total' => 5000,
        'volumen_actual' => 2000,
        'valor_inventario_actual' => '10000.00000000',
        'costo_promedio_galon_actual' => '5.00000000',
        'volumen_minimo_alerta' => 100,
        'estado' => 'activo',
        'fecha_creacion' => now(),
        'creado_por' => $usuario->id,
    ]);
    $test->actingAs($usuario)->post(route('unidades.store'), [
        'empresa_id' => $empresa->id,
        'placa' => 'M4-001',
        'marca' => 'Prueba',
        'cantidad_tanques' => 1,
        'tanques' => [['capacidad' => 500, 'cubierto_por_licencia' => 1]],
        'modelo_medicion' => $modelo,
        'rendimiento_teorico_km_galon' => 10,
        'rendimiento_teorico_gal_hora' => $modelo === 'galones_hora' ? 5 : null,
    ])->assertSessionHasNoErrors();
    $unidad = Unidad::firstOrFail();
    $test->actingAs($usuario)->post(route('licencias.store'), [
        'empresa_id' => $empresa->id,
        'unidad_id' => $unidad->id,
        'tanques_cubiertos' => $unidad->tanquesUnidad()->pluck('id')->all(),
        'periodo_vigencia_meses' => 12,
        'fecha_activacion' => now()->toDateString(),
    ])->assertSessionHasNoErrors();
    $codigos = $unidad->puntosSeguridad()->orderBy('id')->pluck('id')
        ->mapWithKeys(fn (int $id, int $i) => [$id => sprintf('%07d', 3000000 + $i)])->all();
    $test->actingAs($usuario)->post(
        route('marchamos.asignacion-inicial.guardar-avance', $unidad),
        ['marchamos' => $codigos]
    )->assertSessionHasNoErrors();
    $test->actingAs($usuario)->post(
        route('marchamos.asignacion-inicial.finalizar', $unidad)
    )->assertSessionHasNoErrors();
    $motorista = Motorista::create([
        'empresa_id' => $empresa->id,
        'nombres' => 'Juan',
        'apellidos' => 'M4',
        'licencia' => 'LIC-M4',
        'telefono' => '2222-2222',
        'estado' => 'activo',
        'fecha_creacion' => now(),
        'creado_por' => $usuario->id,
    ]);
    return [$usuario, $empresa, $gasolinera, $tanque, $unidad->refresh(), $motorista];
}
function registrarInternoM4(
    array $contexto,
    float $galones,
    int $km,
    ?int $anterior,
    string $codigo,
    ?float $horometro = null,
    array $rutas = []
) {
    [$usuario, $empresa, $gasolinera, $tanque, $unidad, $motorista] = $contexto;
    $punto = $unidad->puntosSeguridad()
        ->where('tipo_punto', 'tapón')->where('subgrupo', 'Depósito')->firstOrFail();
    return app(AbastecimientoService::class)->registrar([
        'empresa_id' => $empresa->id,
        'unidad_id' => $unidad->id,
        'motorista_id' => $motorista->id,
        'ultimo_abastecimiento_id' => $anterior,
        'kilometraje_actual' => $km,
        'horometro_actual' => $horometro,
        'tipo_origen' => 'interno',
        'gasolinera_interna_id' => $gasolinera->id,
        'tanques' => [['tanque_id' => $tanque->id, 'galones' => $galones]],
        'rutas' => $rutas,
        'marchamos' => [[
            'punto_seguridad_id' => $punto->id,
            'nuevo_codigo_marchamo' => $codigo,
        ]],
    ], $usuario->id);
}
test('validacion fallida conserva el estado visual y los datos del origen interno', function () {
    [$usuario, $empresa, $gasolinera, $tanque, $unidad, $motorista] = contextoFullToFull($this);
    $punto = $unidad->puntosSeguridad()
        ->where('tipo_punto', 'tapón')
        ->where('subgrupo', 'Depósito')
        ->firstOrFail();
    $rutaFormulario = route('abastecimientos.create', $unidad);
    $respuesta = $this->actingAs($usuario)
        ->from($rutaFormulario)
        ->post(route('abastecimientos.store', $unidad), [
            'empresa_id' => $empresa->id,
            'unidad_id' => $unidad->id,
            'motorista_id' => $motorista->id,
            'kilometraje_actual' => '25000.50',
            'tipo_origen' => 'interno',
            'gasolinera_interna_id' => $gasolinera->id,
            'tanques' => [[
                'tanque_id' => $tanque->id,
                'galones' => '125.50',
            ]],
            'marchamos' => [[
                'punto_seguridad_id' => $punto->id,
                'nuevo_codigo_marchamo' => '123',
            ]],
        ]);
    $respuesta->assertRedirect($rutaFormulario)
        ->assertSessionHasErrors('marchamos.0.nuevo_codigo_marchamo')
        ->assertSessionHasInput('tipo_origen', 'interno')
        ->assertSessionHasInput('motorista_id', $motorista->id)
        ->assertSessionHasInput('kilometraje_actual', '25000.50')
        ->assertSessionHasInput('gasolinera_interna_id', $gasolinera->id)
        ->assertSessionHasInput('tanques.0.galones', '125.50')
        ->assertSessionHasInput('marchamos.0.nuevo_codigo_marchamo', '123');
    $this->get($rutaFormulario)
        ->assertOk()
        ->assertSee('value="interno"', false)
        ->assertSee('value="125.50"', false)
        ->assertSee('value="25000.50"', false)
        ->assertSee('const volumenInicial', false)
        ->assertSee('actualizarOrigen();', false);
});
test('validacion fallida conserva el estado visual y los datos del origen externo', function () {
    [$usuario, $empresa, , , $unidad, $motorista] = contextoFullToFull($this, 'galones_hora');
    $externa = GasolineraExterna::create([
        'empresa_id' => $empresa->id,
        'compania' => 'Externa persistente',
        'direccion' => 'Prueba',
        'estado' => 'activa',
        'fecha_creacion' => now(),
        'creado_por' => $usuario->id,
    ]);
    $punto = $unidad->puntosSeguridad()
        ->where('tipo_punto', 'tapón')
        ->where('subgrupo', 'Depósito')
        ->firstOrFail();
    $rutaFormulario = route('abastecimientos.create', $unidad);
    $respuesta = $this->actingAs($usuario)
        ->from($rutaFormulario)
        ->post(route('abastecimientos.store', $unidad), [
            'empresa_id' => $empresa->id,
            'unidad_id' => $unidad->id,
            'motorista_id' => $motorista->id,
            'kilometraje_actual' => '301.25',
            'horometro_actual' => '40.75',
            'tipo_origen' => 'externo',
            'gasolinera_externa_id' => $externa->id,
            'galones_externos' => '150.25',
            'precio_galon' => '3.70',
            'marchamos' => [[
                'punto_seguridad_id' => $punto->id,
                'nuevo_codigo_marchamo' => 'invalido',
            ]],
        ]);
    $respuesta->assertRedirect($rutaFormulario)
        ->assertSessionHasErrors('marchamos.0.nuevo_codigo_marchamo')
        ->assertSessionHasInput('tipo_origen', 'externo')
        ->assertSessionHasInput('motorista_id', $motorista->id)
        ->assertSessionHasInput('kilometraje_actual', '301.25')
        ->assertSessionHasInput('horometro_actual', '40.75')
        ->assertSessionHasInput('gasolinera_externa_id', $externa->id)
        ->assertSessionHasInput('galones_externos', '150.25')
        ->assertSessionHasInput('precio_galon', '3.70')
        ->assertSessionHasInput('marchamos.0.nuevo_codigo_marchamo', 'invalido');
    $this->get($rutaFormulario)
        ->assertOk()
        ->assertSee('value="externo"', false)
        ->assertSee('value="150.25"', false)
        ->assertSee('value="3.70"', false)
        ->assertSee('value="40.75"', false)
        ->assertSee('step="0.01"', false)
        ->assertSee('actualizarOrigen();', false);
});
test('consulta de ciclos deriva uno en proceso desde un unico abastecimiento', function () {
    $contexto = contextoFullToFull($this);
    $apertura = registrarInternoM4($contexto, 500, 100, null, '7654301');
    $this->actingAs($contexto[0])
        ->get(route('abastecimientos.ciclos.index'))
        ->assertOk()
        ->assertSee('Consultar ciclos')
        ->assertSee('Consulta pendiente')
        ->assertSee('Los resultados permanecerán vacíos hasta que realice una búsqueda.')
        ->assertDontSee(route('abastecimientos.ciclos.show', $apertura), false);
    $this->actingAs($contexto[0])
        ->get(route('abastecimientos.ciclos.index', ['consultar' => 1]))
        ->assertOk()
        ->assertSee('En proceso')
        ->assertSee('Pendiente')
        ->assertSee('500.00 gal iniciales')
        ->assertSee(route('abastecimientos.ciclos.show', $apertura), false);
    $this->actingAs($contexto[0])
        ->get(route('abastecimientos.ciclos.show', $apertura))
        ->assertOk()
        ->assertSee('Ficha del ciclo')
        ->assertSee('Empresa:')
        ->assertSee('Estado:')
        ->assertSee('Cierre:')
        ->assertSee('Combustible consumido:')
        ->assertSee('Costo por kilómetro:')
        ->assertSee('Pendiente')
        ->assertSee('Cierre pendiente')
        ->assertSee('Ver ficha de abastecimiento');
});
test('dos abastecimientos producen un ciclo completo y otro en proceso con extremos correctos', function () {
    $contexto = contextoFullToFull($this);
    $apertura = registrarInternoM4($contexto, 500, 100, null, '7654301');
    $cierre = registrarInternoM4($contexto, 100, 1100, $apertura->id, '7654302');
    $this->actingAs($contexto[0])
        ->get(route('abastecimientos.ciclos.index', ['consultar' => 1, 'estado_ciclo' => 'completo']))
        ->assertOk()
        ->assertSee('Completo')
        ->assertSee('1,000.00 km')
        ->assertSee('100.00 gal')
        ->assertDontSee('Cierre pendiente');
    $this->actingAs($contexto[0])
        ->get(route('abastecimientos.ciclos.index', ['consultar' => 1, 'estado_ciclo' => 'en_proceso']))
        ->assertOk()
        ->assertSee('En proceso')
        ->assertSee(route('abastecimientos.ciclos.show', $cierre), false)
        ->assertDontSee(route('abastecimientos.ciclos.show', $apertura), false);
    $this->actingAs($contexto[0])
        ->get(route('abastecimientos.ciclos.show', $apertura))
        ->assertOk()
        ->assertSee('Completo')
        ->assertSee('1,000.00 km')
        ->assertSee('Costo consumido:')
        ->assertSee('Costo por kilómetro:')
        ->assertSee('$0.50/km')
        ->assertSee(route('abastecimientos.show', $apertura), false)
        ->assertSee(route('abastecimientos.show', $cierre), false);
});
test('tres abastecimientos producen dos ciclos completos y uno en proceso y respetan filtros', function () {
    $contexto = contextoFullToFull($this);
    $primero = registrarInternoM4($contexto, 500, 100, null, '7654301');
    $segundo = registrarInternoM4($contexto, 100, 1100, $primero->id, '7654302');
    $tercero = registrarInternoM4($contexto, 100, 2100, $segundo->id, '7654303');
    $respuesta = $this->actingAs($contexto[0])->get(route('abastecimientos.ciclos.index', [
        'consultar' => 1,
        'empresa_id' => $contexto[1]->id,
        'unidad_id' => $contexto[4]->id,
    ]));
    $respuesta->assertOk()
        ->assertSee(route('abastecimientos.ciclos.show', $primero), false)
        ->assertSee(route('abastecimientos.ciclos.show', $segundo), false)
        ->assertSee(route('abastecimientos.ciclos.show', $tercero), false);
    expect($primero->refresh()->cierreCiclo?->is($segundo))->toBeTrue()
        ->and($segundo->refresh()->cierreCiclo?->is($tercero))->toBeTrue()
        ->and($tercero->refresh()->cierreCiclo)->toBeNull();
});
test('buscador maestro y motorista consideran participantes de apertura y cierre', function () {
    $contexto = contextoFullToFull($this);
    [$usuario, $empresa, , , $unidad, $motoristaA] = $contexto;
    $motoristaA->update([
        'nombres' => 'Antonio',
        'apellidos' => 'Hernández',
    ]);
    $motoristaB = Motorista::create([
        'empresa_id' => $empresa->id,
        'nombres' => 'Beatriz',
        'apellidos' => 'Cierre',
        'licencia' => 'LIC-CIERRE',
        'telefono' => '2222-3333',
        'estado' => 'activo',
        'fecha_creacion' => now(),
        'creado_por' => $usuario->id,
    ]);
    $motoristaAjeno = Motorista::create([
        'empresa_id' => $empresa->id,
        'nombres' => 'Carlos',
        'apellidos' => 'Sin ciclo',
        'licencia' => 'LIC-AJENO',
        'telefono' => '2222-4444',
        'estado' => 'activo',
        'fecha_creacion' => now(),
        'creado_por' => $usuario->id,
    ]);
    $apertura = registrarInternoM4($contexto, 500, 100, null, '7654301');
    $contextoCierre = $contexto;
    $contextoCierre[5] = $motoristaB;
    $cierre = registrarInternoM4($contextoCierre, 100, 1100, $apertura->id, '7654302');
    $rutaApertura = route('abastecimientos.ciclos.show', $apertura);
    $rutaCierre = route('abastecimientos.ciclos.show', $cierre);
    foreach (['Empresa M4', 'M4-001', 'anton', 'triz'] as $buscar) {
        $this->actingAs($usuario)
            ->get(route('abastecimientos.ciclos.index', ['consultar' => 1, 'buscar' => $buscar]))
            ->assertOk()
            ->assertSee($rutaApertura, false);
    }
    $this->actingAs($usuario)
        ->get(route('abastecimientos.ciclos.index', [
            'consultar' => 1,
            'motorista_id' => $motoristaA->id,
            'estado_ciclo' => 'completo',
            'empresa_id' => $empresa->id,
        ]))
        ->assertOk()
        ->assertSee($rutaApertura, false)
        ->assertDontSee($rutaCierre, false);
    $this->actingAs($usuario)
        ->get(route('abastecimientos.ciclos.index', [
            'consultar' => 1,
            'motorista_id' => $motoristaB->id,
            'estado_ciclo' => 'completo',
        ]))
        ->assertOk()
        ->assertSee($rutaApertura, false)
        ->assertDontSee($rutaCierre, false);
    $this->actingAs($usuario)
        ->get(route('abastecimientos.ciclos.index', [
            'consultar' => 1,
            'motorista_id' => $motoristaB->id,
            'estado_ciclo' => 'en_proceso',
        ]))
        ->assertOk()
        ->assertSee($rutaCierre, false)
        ->assertDontSee($rutaApertura, false);
    $this->actingAs($usuario)
        ->get(route('abastecimientos.ciclos.index', [
            'consultar' => 1,
            'buscar' => 'Antonio',
            'estado_ciclo' => 'completo',
        ]))
        ->assertOk()
        ->assertSee($rutaApertura, false)
        ->assertDontSee($rutaCierre, false);
    $this->actingAs($usuario)
        ->get(route('abastecimientos.ciclos.index', [
            'consultar' => 1,
            'motorista_id' => $motoristaAjeno->id,
        ]))
        ->assertOk()
        ->assertSee('No se encontraron ciclos con los filtros seleccionados.');
    $this->actingAs($usuario)
        ->get(route('abastecimientos.ciclos.ventana'))
        ->assertOk()
        ->assertSee('Buscar empresa, unidad o motorista')
        ->assertSee('name="motorista_id"', false)
        ->assertSee('Consultar')
        ->assertSee('Limpiar');
    $empresaFueraAlcance = Empresa::create([
        'nombre_legal' => 'Empresa fuera de alcance',
        'nombre_comercial' => 'CICLO-FUERA',
        'nit' => 'CICLO-FUERA',
        'estado' => 'activa',
    ]);
    $motoristaFueraAlcance = Motorista::create([
        'empresa_id' => $empresaFueraAlcance->id,
        'nombres' => 'Motorista',
        'apellidos' => 'Fuera de alcance',
        'licencia' => 'LIC-FUERA',
        'telefono' => '2222-5555',
        'estado' => 'activo',
        'fecha_creacion' => now(),
        'creado_por' => $usuario->id,
    ]);
    $usuario->update(['empresa_id' => $empresa->id]);
    $this->actingAs($usuario)
        ->get(route('abastecimientos.ciclos.index'))
        ->assertOk()
        ->assertDontSee('Fuera de alcance');
});
test('limpiar ciclos vuelve a estado inicial sin filtros ni resultados', function () {
    $contexto = contextoFullToFull($this);
    [$usuario, $empresa, , , $unidad, $motorista] = $contexto;
    $cicloFueraDelRango = registrarInternoM4($contexto, 500, 100, null, '7654391');
    $cicloDentroDelRango = registrarInternoM4(
        $contexto,
        100,
        1100,
        $cicloFueraDelRango->id,
        '7654392'
    );
    $cicloFueraDelRango->update([
        'fecha_hora_abastecimiento' => '2026-07-15 08:00:00',
    ]);
    $cicloDentroDelRango->update([
        'fecha_hora_abastecimiento' => '2026-08-15 08:00:00',
    ]);
    $filtros = [
        'consultar' => 1,
        'buscar' => 'M4',
        'empresa_id' => $empresa->id,
        'unidad_id' => $unidad->id,
        'motorista_id' => $motorista->id,
        'modelo_medicion' => 'kilometros_galon',
        'fecha_desde' => '2026-08-01',
        'fecha_hasta' => '2026-08-27',
    ];
    foreach ([
        'abastecimientos.ciclos.index',
        'abastecimientos.ciclos.ventana',
    ] as $nombreRuta) {
        $nombreRutaFicha = $nombreRuta === 'abastecimientos.ciclos.ventana'
            ? 'abastecimientos.ciclos.show.ventana'
            : 'abastecimientos.ciclos.show';
        $rutaCicloFueraDelRango = route($nombreRutaFicha, $cicloFueraDelRango);
        $rutaCicloDentroDelRango = route($nombreRutaFicha, $cicloDentroDelRango);
        $respuestaFiltrada = $this->actingAs($usuario)->get(route($nombreRuta, $filtros));
        $respuestaFiltrada
            ->assertOk()
            ->assertSee($rutaCicloDentroDelRango, false)
            ->assertDontSee($rutaCicloFueraDelRango, false);
        preg_match(
            '/data-ciclos-limpiar href="([^"]+)"/',
            $respuestaFiltrada->getContent(),
            $coincidencia
        );
        $documento = new DOMDocument;
        @$documento->loadHTML($respuestaFiltrada->getContent());
        $xpath = new DOMXPath($documento);
        /** @var DOMElement|null $enlaceLimpiar */
        $enlaceLimpiar = $xpath->query('//a[@data-ciclos-limpiar]')?->item(0);
        $formulariosAncestros = $enlaceLimpiar
            ? $xpath->query('ancestor::form', $enlaceLimpiar)->length
            : null;
        expect($coincidencia[1] ?? null)->toBe(route($nombreRuta))
            ->and($coincidencia[1] ?? '')->not->toContain('?')
            ->and($enlaceLimpiar)->toBeInstanceOf(DOMElement::class)
            ->and($enlaceLimpiar?->getAttribute('href'))->toBe(route($nombreRuta))
            ->and($enlaceLimpiar?->hasAttribute('onclick'))->toBeFalse()
            ->and($enlaceLimpiar?->hasAttribute('type'))->toBeFalse()
            ->and($formulariosAncestros)->toBe(0);
        $respuestaLimpia = $this->actingAs($usuario)->get($coincidencia[1]);
        $respuestaLimpia
            ->assertOk()
            ->assertDontSee($rutaCicloFueraDelRango, false)
            ->assertDontSee($rutaCicloDentroDelRango, false)
            ->assertSee('Consulta pendiente')
            ->assertSee('Los resultados permanecerán vacíos hasta que realice una búsqueda.');
        $contenido = $respuestaLimpia->getContent();
        expect($contenido)
            ->toMatch('/id="buscar"[\s\S]*?value=""/')
            ->toMatch('/id="empresa_id"[\s\S]*?<option value="" selected>Todas<\/option>/')
            ->toMatch('/id="unidad_id"[\s\S]*?<option value="" selected>Todas<\/option>/')
            ->toMatch('/id="motorista_id"[\s\S]*?<option value="" selected>Todos<\/option>/')
            ->toMatch('/id="modelo_medicion"[\s\S]*?<option value="" selected>Todos<\/option>/')
            ->toMatch('/id="estado_ciclo"[\s\S]*?<option value="" selected>Todos<\/option>/')
            ->toMatch('/id="fecha_desde"[^>]*value=""/')
            ->toMatch('/id="fecha_hasta"[^>]*value=""/');
    }
});
test('consultar ciclos reutiliza el permiso de consulta y conserva redirects legacy', function () {
    [$usuario, $empresa] = contextoFullToFull($this);
    $rolConsulta = Role::create([
        'codigo' => 'EMPRESA_CONSULTA_CICLOS_TEST',
        'nombre' => 'Consulta de ciclos de prueba',
        'alcance' => 'empresa',
        'estado' => 'activo',
        'fecha_creacion' => now(),
    ]);
    $permisoConsulta = Permiso::create([
        'codigo' => 'abastecimientos.consultar',
        'modulo' => 'abastecimientos',
        'accion' => 'consultar',
        'nombre' => 'Consultar abastecimientos',
        'alcance' => 'ambos',
        'estado' => 'activo',
        'fecha_creacion' => now(),
    ]);
    RolPermiso::create([
        'rol_id' => $rolConsulta->id,
        'permiso_id' => $permisoConsulta->id,
        'fecha_creacion' => now(),
    ]);
    $usuario->update([
        'rol_id' => $rolConsulta->id,
        'tipo_usuario' => User::TIPO_EMPRESA,
        'empresa_id' => $empresa->id,
    ]);
    $this->actingAs($usuario->refresh())
        ->get(route('abastecimientos.ciclos.index'))
        ->assertOk()
        ->assertSee('Consultar ciclos')
        ->assertSee(route('abastecimientos.ciclos.index'), false)
        ->assertDontSee(route('abastecimientos.administrar'), false);
    $this->actingAs($usuario)
        ->get(route('abastecimientos.ciclos.ventana'))
        ->assertOk()
        ->assertSee('Consultar ciclos');
    $this->actingAs($usuario)
        ->get(route('abastecimientos.administrar'))
        ->assertRedirect(route('abastecimientos.ciclos.index'));
    $this->actingAs($usuario)
        ->get(route('abastecimientos.administrar.ventana'))
        ->assertRedirect(route('abastecimientos.ciclos.ventana'));
    RolPermiso::query()->where('rol_id', $rolConsulta->id)->delete();
    $this->actingAs($usuario->refresh())
        ->get(route('abastecimientos.ciclos.index'))
        ->assertForbidden();
    $this->actingAs($usuario)
        ->get(route('abastecimientos.ciclos.ventana'))
        ->assertForbidden();
});
test('costo por kilometro conserva precision interna y presenta dos decimales', function () {
    $contexto = contextoFullToFull($this);
    $contexto[3]->update([
        'valor_inventario_actual' => '8500.00000000',
        'costo_promedio_galon_actual' => '4.25000000',
    ]);
    $apertura = registrarInternoM4($contexto, 500, 100, null, '7654301');
    $cierre = registrarInternoM4($contexto, 270, 2500, $apertura->id, '7654302');
    expect($cierre->costo_combustible_consumido_ciclo)->toBe('1147.50000000')
        ->and($cierre->diferencia_kilometraje)->toBe('2400.00')
        ->and($cierre->costo_unitario_ciclo)->toBe('0.47812500');
    $this->actingAs($contexto[0])
        ->get(route('abastecimientos.ciclos.show', $apertura))
        ->assertOk()
        ->assertSee('Lectura final:')
        ->assertSee('2,500.00 km')
        ->assertSee('Combustible consumido:')
        ->assertSee('270.00 gal')
        ->assertSee('Costo consumido:')
        ->assertSee('$1,147.50')
        ->assertSee('Costo por kilómetro:')
        ->assertSee('$0.48/km');
});
test('linea base exige carga exactamente igual a capacidad cubierta', function () {
    $contexto = contextoFullToFull($this);
    expect(fn () => registrarInternoM4($contexto, 499, 100, null, '7654301'))
        ->toThrow(ValidationException::class);
    expect(fn () => registrarInternoM4($contexto, 501, 100, null, '7654301'))
        ->toThrow(ValidationException::class);
    $primero = registrarInternoM4($contexto, 500, 100, null, '7654301');
    expect($primero->abastecimiento_anterior_id)->toBeNull()
        ->and($primero->volumen_final)->toBe('500.00')
        ->and($primero->consumo_real_ciclo)->toBeNull()
        ->and($primero->costo_unitario_ciclo)->toBeNull()
        ->and($primero->valor_carga_snapshot)->toBe('2500.00000000')
        ->and($primero->costo_promedio_abordo_resultante)->toBe('5.00000000')
        ->and($contexto[4]->refresh()->valor_combustible_abordo_actual)->toBe('2500.00000000');
});
test('linea base externa exacta calcula valor sin tocar inventario interno', function () {
    $contexto = contextoFullToFull($this);
    [$usuario, $empresa, , $tanque, $unidad, $motorista] = $contexto;
    $externa = GasolineraExterna::create([
        'empresa_id' => $empresa->id,
        'compania' => 'Externa M4',
        'direccion' => 'Prueba',
        'estado' => 'activa',
        'fecha_creacion' => now(),
        'creado_por' => $usuario->id,
    ]);
    $punto = $unidad->puntosSeguridad()
        ->where('tipo_punto', 'tapón')->where('subgrupo', 'Depósito')->firstOrFail();
    $abastecimiento = app(AbastecimientoService::class)->registrar([
        'empresa_id' => $empresa->id,
        'unidad_id' => $unidad->id,
        'motorista_id' => $motorista->id,
        'ultimo_abastecimiento_id' => null,
        'kilometraje_actual' => 100,
        'tipo_origen' => 'externo',
        'gasolinera_externa_id' => $externa->id,
        'galones_externos' => 500,
        'precio_galon' => '4.7500',
        'rutas' => [],
        'marchamos' => [[
            'punto_seguridad_id' => $punto->id,
            'nuevo_codigo_marchamo' => '7654301',
        ]],
    ], $usuario->id);
    expect($abastecimiento->valor_carga_snapshot)->toBe('2375.00000000')
        ->and($abastecimiento->costo_efectivo_carga_snapshot)->toBe('4.75000000')
        ->and($tanque->refresh()->volumen_actual)->toBe('2000.00');
});
test('cierre km gal usa carga como consumo y mezcla remanente sin revalorizar', function () {
    $contexto = contextoFullToFull($this);
    $primero = registrarInternoM4($contexto, 500, 100, null, '7654301');
    $contexto[3]->update([
        'volumen_actual' => 1000,
        'valor_inventario_actual' => '4750.00000000',
        'costo_promedio_galon_actual' => '4.75000000',
    ]);
    $segundo = registrarInternoM4($contexto, 400, 1100, $primero->id, '7654302');
    expect($segundo->abastecimiento_anterior_id)->toBe($primero->id)
        ->and($segundo->consumo_real_ciclo)->toBe('400.00')
        ->and($segundo->costo_unitario_ciclo)->toBe('2.00000000')
        ->and($segundo->kilometros_por_galon)->toBe('2.500000')
        ->and($segundo->consumo_teorico_ciclo)->toBe('100.00000000')
        ->and($segundo->diferencia_galones_ciclo)->toBe('-300.00000000')
        ->and($segundo->costo_combustible_consumido_ciclo)->toBe('2000.00000000')
        ->and($segundo->valor_remanente_antes_carga_snapshot)->toBe('500.00000000')
        ->and($segundo->valor_abordo_resultante)->toBe('2400.00000000')
        ->and($segundo->costo_promedio_abordo_resultante)->toBe('4.80000000');
});
test('distancia cero en km gal persiste rendimiento cero y sobreconsumo', function () {
    $contexto = contextoFullToFull($this);
    $primero = registrarInternoM4($contexto, 500, 100, null, '7654301');
    $segundo = registrarInternoM4($contexto, 100, 100, $primero->id, '7654302');
    expect($segundo->kilometros_por_galon)->toBe('0.000000')
        ->and($segundo->costo_unitario_ciclo)->toBeNull()
        ->and($segundo->consumo_teorico_ciclo)->toBe('0.00000000')
        ->and($segundo->diferencia_galones_ciclo)->toBe('-100.00000000');
});
test('galones hora exige avance y usa el teorico de apertura', function () {
    $contexto = contextoFullToFull($this, 'galones_hora');
    $primero = registrarInternoM4($contexto, 500, 100, null, '7654301', 100);
    expect(fn () => registrarInternoM4(
        $contexto, 100, 100, $primero->id, '7654302', 100
    ))->toThrow(ValidationException::class);
    $segundo = registrarInternoM4(
        $contexto, 100, 100, $primero->id, '7654302', 120
    );
    expect($segundo->galones_por_hora)->toBe('5.000000')
        ->and($segundo->costo_unitario_ciclo)->toBe('25.00000000')
        ->and($segundo->consumo_teorico_ciclo)->toBe('100.00000000')
        ->and($segundo->diferencia_galones_ciclo)->toBe('0.00000000');
});
test('galones viaje usa factores y bloquea viajes sin avance', function () {
    $contexto = contextoFullToFull($this, 'galones_viaje');
    [$usuario, $empresa] = $contexto;
    $origen = PuntoRuta::create([
        'empresa_id' => $empresa->id, 'nombre' => 'Origen M4',
        'direccion' => 'A', 'estado' => 'activo', 'creado_por' => $usuario->id,
    ]);
    $destino = PuntoRuta::create([
        'empresa_id' => $empresa->id, 'nombre' => 'Destino M4',
        'direccion' => 'B', 'estado' => 'activo', 'creado_por' => $usuario->id,
    ]);
    $ruta = Ruta::create([
        'empresa_id' => $empresa->id,
        'punto_origen_id' => $origen->id,
        'punto_destino_id' => $destino->id,
        'ruta' => 'Ruta M4',
        'kilometros_estimados' => 50,
        'galones_estimados' => 20,
        'estado' => 'activo',
        'creado_por' => $usuario->id,
    ]);
    $primero = registrarInternoM4($contexto, 500, 100, null, '7654301');
    $viajes = [
        ['ruta_id' => $ruta->id, 'tipo_recorrido' => 'ida'],
        ['ruta_id' => $ruta->id, 'tipo_recorrido' => 'ida_vuelta'],
    ];
    expect(fn () => registrarInternoM4(
        $contexto, 100, 100, $primero->id, '7654302', null, $viajes
    ))->toThrow(ValidationException::class);
    $segundo = registrarInternoM4(
        $contexto, 100, 250, $primero->id, '7654302', null, $viajes
    );
    expect($segundo->total_rutas)->toBe(2)
        ->and($segundo->total_viajes)->toBe(3)
        ->and($segundo->costo_unitario_ciclo)->toBe('166.66666667')
        ->and($segundo->consumo_teorico_ciclo)->toBe('60.00000000')
        ->and($segundo->diferencia_galones_ciclo)->toBe('-40.00000000');
});
test('analisis legacy galones viaje deriva factores de detalles', function () {
    $contexto = contextoFullToFull($this, 'galones_viaje');
    [$usuario, $empresa] = $contexto;
    $origen = PuntoRuta::create([
        'empresa_id' => $empresa->id, 'nombre' => 'Origen legacy',
        'direccion' => 'A', 'estado' => 'activo', 'creado_por' => $usuario->id,
    ]);
    $destino = PuntoRuta::create([
        'empresa_id' => $empresa->id, 'nombre' => 'Destino legacy',
        'direccion' => 'B', 'estado' => 'activo', 'creado_por' => $usuario->id,
    ]);
    $ruta = Ruta::create([
        'empresa_id' => $empresa->id,
        'punto_origen_id' => $origen->id,
        'punto_destino_id' => $destino->id,
        'ruta' => 'Ruta legacy',
        'kilometros_estimados' => 50,
        'galones_estimados' => 20,
        'estado' => 'activo',
        'creado_por' => $usuario->id,
    ]);
    $primero = registrarInternoM4($contexto, 500, 100, null, '7654301');
    $cierre = registrarInternoM4(
        $contexto,
        100,
        250,
        $primero->id,
        '7654302',
        null,
        [
            ['ruta_id' => $ruta->id, 'tipo_recorrido' => 'ida'],
            ['ruta_id' => $ruta->id, 'tipo_recorrido' => 'ida_vuelta'],
        ]
    );
    $cierre->update([
        'consumo_real_ciclo' => null,
        'total_viajes' => 0,
    ]);
    $legacy = $cierre->fresh('rutas');
    $controlador = app(AnalisisConsumoUnidadController::class);
    $viajes = new ReflectionMethod($controlador, 'viajesEfectivos');
    $resultado = new ReflectionMethod($controlador, 'calcularResultadoCiclo');
    expect($legacy->total_viajes)->toBe(0)
        ->and($legacy->total_rutas)->toBe(2)
        ->and($legacy->rutas->pluck('factor_recorrido')->all())->toBe([1, 2])
        ->and($viajes->invoke($controlador, $legacy))->toBe(3)
        ->and($resultado->invoke(
            $controlador,
            $legacy,
            'galones_viaje'
        ))->toBe(33.333333);
});
test('carga posterior valida capacidad concurrencia e indice unico de cadena', function () {
    $contexto = contextoFullToFull($this);
    $primero = registrarInternoM4($contexto, 500, 100, null, '7654301');
    expect(fn () => registrarInternoM4($contexto, 501, 200, $primero->id, '7654302'))
        ->toThrow(ValidationException::class);
    $segundo = registrarInternoM4($contexto, 100, 200, $primero->id, '7654302');
    expect(fn () => registrarInternoM4($contexto, 100, 300, $primero->id, '7654303'))
        ->toThrow(ValidationException::class);
    $duplicado = $segundo->replicate();
    $duplicado->abastecimiento_anterior_id = $primero->id;
    expect(fn () => $duplicado->save())->toThrow(QueryException::class);
});
test('abastecimientos son inmutables y no tienen rutas ordinarias de edicion', function () {
    $contexto = contextoFullToFull($this);
    $primero = registrarInternoM4($contexto, 500, 100, null, '7654301');
    $inventario = $contexto[3]->refresh()->only([
        'volumen_actual',
        'valor_inventario_actual',
        'costo_promedio_galon_actual',
    ]);
    $movimientos = $primero->movimientosInventario()->count();
    $rutas = $primero->rutas()->count();
    $punto = $contexto[4]->puntosSeguridad()
        ->where('tipo_punto', 'tapón')->where('subgrupo', 'Depósito')->firstOrFail();
    $marchamoActual = $punto->marchamo_actual_id;
    expect(Route::has('abastecimientos.edit'))->toBeFalse()
        ->and(Route::has('abastecimientos.update'))->toBeFalse();
    expect(fn () => app(AbastecimientoService::class)->modificar($primero, [], $contexto[0]->id))
        ->toThrow(ValidationException::class);
    expect($primero->fresh()->volumen_cargado)->toBe('500.00')
        ->and($contexto[3]->refresh()->only(array_keys($inventario)))->toBe($inventario)
        ->and($primero->movimientosInventario()->count())->toBe($movimientos)
        ->and($primero->rutas()->count())->toBe($rutas)
        ->and($punto->refresh()->marchamo_actual_id)->toBe($marchamoActual);
});
test('ciclo abierto bloquea edicion estructural pero no inactivacion', function () {
    $contexto = contextoFullToFull($this);
    registrarInternoM4($contexto, 500, 100, null, '7654301');
    $this->actingAs($contexto[0])
        ->get(route('unidades.edit', $contexto[4]))
        ->assertForbidden();
    $this->actingAs($contexto[0])->patch(
        route('unidades.inactivar', $contexto[4]),
        ['motivo_inactivacion' => 'Suspensión temporal']
    )->assertSessionHasNoErrors();
});
