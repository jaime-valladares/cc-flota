<?php

use App\Models\Empresa;
use App\Models\Licencia;
use App\Models\LicenciaTanque;
use App\Models\Marchamo;
use App\Models\Permiso;
use App\Models\PuntoSeguridadUnidad;
use App\Models\Role;
use App\Models\Unidad;
use App\Models\UnidadTanque;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Database\Seeders\PermisosSeeder;
use Database\Seeders\RolesSeeder;
use Database\Seeders\RolPermisosSeeder;

function prepararPermisosReporteUnidades($caso): void
{
    $caso->seed([
        RolesSeeder::class,
        PermisosSeeder::class,
        RolPermisosSeeder::class,
    ]);
}

function empresaReporteUnidades(string $sufijo, string $estado = 'activa'): Empresa
{
    return Empresa::query()->create([
        'nombre_legal' => "Empresa Reporte {$sufijo}",
        'nombre_comercial' => "Reporte {$sufijo}",
        'nit' => "NIT-RU-{$sufijo}",
        'estado' => $estado,
    ]);
}

function unidadReporteUnidades(
    Empresa $empresa,
    string $placa,
    string $estado = 'activa',
    string $modelo = 'kilometros_galon'
): Unidad {
    return Unidad::query()->create([
        'empresa_id' => $empresa->id,
        'placa' => $placa,
        'marca' => 'Marca '.$placa,
        'total_tanques' => 1,
        'cantidad_tanques_con_licencia' => 0,
        'capacidad_total' => 100,
        'capacidad_cubierta' => 0,
        'modelo_medicion' => $modelo,
        'rendimiento_teorico_km_galon' => $modelo === 'kilometros_galon' ? 10 : null,
        'rendimiento_teorico_gal_hora' => $modelo === 'galones_hora' ? 2 : null,
        'estado' => $estado,
    ]);
}

function usuarioReporteUnidades(string $rolCodigo, ?Empresa $empresa = null): User
{
    return User::factory()->create([
        'rol_id' => Role::query()->where('codigo', $rolCodigo)->value('id'),
        'tipo_usuario' => $empresa ? User::TIPO_EMPRESA : User::TIPO_DIESEL_COP,
        'empresa_id' => $empresa?->id,
        'estado' => 'activo',
    ]);
}

function convertirUnidadEnOperable(Unidad $unidad): void
{
    Licencia::query()->create([
        'empresa_id' => $unidad->empresa_id,
        'unidad_id' => $unidad->id,
        'periodo_vigencia_meses' => 12,
        'fecha_activacion' => now()->subMonth()->toDateString(),
        'fecha_vencimiento' => now()->addYear()->toDateString(),
        'estado' => 'activa',
        'plantilla_puntos_seguridad' => 'plantilla_1_tanque',
    ]);

    $punto = PuntoSeguridadUnidad::query()->create([
        'unidad_id' => $unidad->id,
        'orden' => 1,
        'nombre_punto' => 'Punto operable',
        'requiere_marchamo' => true,
        'plantilla_origen' => 'plantilla_1_tanque',
        'estado_asignacion' => 'asignado',
        'estado' => 'activo',
    ]);

    $marchamo = Marchamo::query()->create([
        'empresa_id' => $unidad->empresa_id,
        'unidad_id' => $unidad->id,
        'punto_seguridad_id' => $punto->id,
        'codigo_marchamo' => str_pad((string) $unidad->id, 7, '0', STR_PAD_LEFT),
        'fecha_activacion' => now(),
        'estado' => 'activo',
        'activo_actual' => 1,
        'origen_creacion' => 'asignacion_inicial',
    ]);

    $punto->update(['marchamo_actual_id' => $marchamo->id]);
}

test('permiso de reporte existe activo y conserva exactamente los seis roles autorizados', function () {
    prepararPermisosReporteUnidades($this);

    $permiso = Permiso::query()
        ->where('codigo', 'reportes.unidades.ficha')
        ->firstOrFail();

    expect($permiso->estado)->toBe('activo')
        ->and($permiso->modulo)->toBe('reportes')
        ->and($permiso->roles()->orderBy('codigo')->pluck('codigo')->all())
        ->toBe([
            User::ROL_DIESEL_ADMIN,
            User::ROL_DIESEL_AUDITOR,
            User::ROL_DIESEL_SUPER_ADMIN,
            User::ROL_EMPRESA_ADMIN,
            User::ROL_EMPRESA_AUDITOR,
            User::ROL_EMPRESA_SUPERVISOR,
        ]);
});

test('roles autorizados acceden y tecnico y operador reciben 403', function () {
    prepararPermisosReporteUnidades($this);
    $empresa = empresaReporteUnidades('ROLES');

    foreach ([User::ROL_DIESEL_SUPER_ADMIN, User::ROL_DIESEL_ADMIN, User::ROL_DIESEL_AUDITOR] as $rol) {
        $this->actingAs(usuarioReporteUnidades($rol))
            ->get(route('reportes.unidades.index'))->assertOk();
    }

    foreach ([User::ROL_EMPRESA_ADMIN, User::ROL_EMPRESA_SUPERVISOR, User::ROL_EMPRESA_AUDITOR] as $rol) {
        $this->actingAs(usuarioReporteUnidades($rol, $empresa))
            ->get(route('reportes.unidades.index'))->assertOk();
    }

    $this->actingAs(usuarioReporteUnidades(User::ROL_DIESEL_TECNICO))
        ->get(route('reportes.unidades.index'))->assertForbidden();
    $this->actingAs(usuarioReporteUnidades(User::ROL_EMPRESA_OPERADOR, $empresa))
        ->get(route('reportes.unidades.index'))->assertForbidden();
});

test('diesel cop ve una fila por unidad de multiples empresas y enlaces de ficha', function () {
    prepararPermisosReporteUnidades($this);
    $empresaA = empresaReporteUnidades('GLOBAL-A');
    $empresaB = empresaReporteUnidades('GLOBAL-B');
    $unidadA = unidadReporteUnidades($empresaA, 'RU-GLOBAL-A');
    $unidadB = unidadReporteUnidades($empresaB, 'RU-GLOBAL-B');

    $respuesta = $this->actingAs(usuarioReporteUnidades(User::ROL_DIESEL_ADMIN))
        ->get(route('reportes.unidades.index', ['consultar' => 1]))
        ->assertOk()
        ->assertSee('RU-GLOBAL-A')
        ->assertSee('RU-GLOBAL-B')
        ->assertSee(route('reportes.unidades.show', $unidadA), false)
        ->assertSee(route('reportes.unidades.show', $unidadB), false);

    expect(substr_count($respuesta->getContent(), 'data-report-unit-row='))->toBe(2);
});

test('usuario empresa solo ve sus unidades y manipulacion de filtros no amplia tenant', function () {
    prepararPermisosReporteUnidades($this);
    $propia = empresaReporteUnidades('TENANT-PROPIO');
    $ajena = empresaReporteUnidades('TENANT-AJENO');
    $unidadPropia = unidadReporteUnidades($propia, 'RU-PROPIA');
    $unidadAjena = unidadReporteUnidades($ajena, 'RU-AJENA');
    $usuario = usuarioReporteUnidades(User::ROL_EMPRESA_ADMIN, $propia);

    $this->actingAs($usuario)
        ->get(route('reportes.unidades.index', [
            'consultar' => 1,
            'empresa_ids' => [$ajena->id],
            'unidad_ids' => [$unidadAjena->id],
        ]))
        ->assertOk()->assertDontSee('RU-AJENA')->assertDontSee('Reporte TENANT-AJENO');

    $this->actingAs($usuario)
        ->get(route('reportes.unidades.show', $unidadPropia))->assertOk();
    $this->actingAs($usuario)
        ->get(route('reportes.unidades.show', $unidadAjena))->assertForbidden();
});

test('reporte incluye estados y muestra resumen correcto', function () {
    prepararPermisosReporteUnidades($this);
    $empresa = empresaReporteUnidades('RESUMEN');
    unidadReporteUnidades($empresa, 'RU-REGISTRADA', 'registrada');
    $operable = unidadReporteUnidades($empresa, 'RU-ACTIVA', 'activa');
    convertirUnidadEnOperable($operable);
    unidadReporteUnidades($empresa, 'RU-INACTIVA', 'inactiva');

    $this->actingAs(usuarioReporteUnidades(User::ROL_EMPRESA_AUDITOR, $empresa))
        ->get(route('reportes.unidades.index', ['consultar' => 1]))
        ->assertOk()
        ->assertSee('RU-REGISTRADA')->assertSee('RU-ACTIVA')->assertSee('RU-INACTIVA')
        ->assertSee('data-summary-label="Total unidades" data-summary-value="3"', false)
        ->assertSee('data-summary-label="Registradas" data-summary-value="1"', false)
        ->assertSee('data-summary-label="Activas" data-summary-value="1"', false)
        ->assertSee('data-summary-label="Inactivas" data-summary-value="1"', false)
        ->assertSee('data-summary-label="Operables" data-summary-value="1"', false);
});

test('filtros de empresa nombre placa estado y modelo funcionan', function () {
    prepararPermisosReporteUnidades($this);
    $empresaA = empresaReporteUnidades('FILTRO-A');
    $empresaB = empresaReporteUnidades('FILTRO-B');
    $unidadKm = unidadReporteUnidades($empresaA, 'RU-KM-BUSCADA', 'registrada', 'kilometros_galon');
    $unidadHora = unidadReporteUnidades($empresaA, 'RU-HORA', 'activa', 'galones_hora');
    $unidadViaje = unidadReporteUnidades($empresaB, 'RU-VIAJE', 'inactiva', 'galones_viaje');
    $usuario = usuarioReporteUnidades(User::ROL_DIESEL_AUDITOR);

    $this->actingAs($usuario)->get(route('reportes.unidades.index', ['consultar' => 1, 'empresa_ids' => [$empresaA->id]]))
        ->assertSee('data-report-unit-row="'.$unidadKm->id.'"', false)
        ->assertSee('data-report-unit-row="'.$unidadHora->id.'"', false)
        ->assertDontSee('data-report-unit-row="'.$unidadViaje->id.'"', false);
    $this->actingAs($usuario)->get(route('reportes.unidades.index', ['consultar' => 1, 'unidad_ids' => [$unidadKm->id]]))
        ->assertSee('data-report-unit-row="'.$unidadKm->id.'"', false)
        ->assertDontSee('data-report-unit-row="'.$unidadHora->id.'"', false);
    $this->actingAs($usuario)->get(route('reportes.unidades.index', ['consultar' => 1, 'busqueda' => 'KM-BUSCADA']))
        ->assertSee('data-report-unit-row="'.$unidadKm->id.'"', false)
        ->assertDontSee('data-report-unit-row="'.$unidadHora->id.'"', false);
    $this->actingAs($usuario)->get(route('reportes.unidades.index', ['consultar' => 1, 'estado' => 'inactiva']))
        ->assertSee('data-report-unit-row="'.$unidadViaje->id.'"', false)
        ->assertDontSee('data-report-unit-row="'.$unidadHora->id.'"', false);
    $this->actingAs($usuario)->get(route('reportes.unidades.index', ['consultar' => 1, 'modelo_medicion' => 'galones_hora']))
        ->assertSee('data-report-unit-row="'.$unidadHora->id.'"', false)
        ->assertDontSee('data-report-unit-row="'.$unidadKm->id.'"', false);
});

test('filtros operable y no operable usan disponibilidad del modelo', function () {
    prepararPermisosReporteUnidades($this);
    $empresa = empresaReporteUnidades('DISPONIBILIDAD');
    $operable = unidadReporteUnidades($empresa, 'RU-OPERABLE');
    convertirUnidadEnOperable($operable);
    $noOperable = unidadReporteUnidades($empresa, 'RU-NO-OPERABLE');
    $usuario = usuarioReporteUnidades(User::ROL_EMPRESA_SUPERVISOR, $empresa);

    $this->actingAs($usuario)->get(route('reportes.unidades.index', ['consultar' => 1, 'disponibilidad' => 'operable']))
        ->assertOk()
        ->assertSee('data-report-unit-row="'.$operable->id.'"', false)
        ->assertDontSee('data-report-unit-row="'.$noOperable->id.'"', false);
    $this->actingAs($usuario)->get(route('reportes.unidades.index', ['consultar' => 1, 'disponibilidad' => 'no_operable']))
        ->assertOk()
        ->assertSee('data-report-unit-row="'.$noOperable->id.'"', false)
        ->assertDontSee('data-report-unit-row="'.$operable->id.'"', false);
});

test('ruta ventana comparte permiso aislamiento y preserva filtros', function () {
    prepararPermisosReporteUnidades($this);
    $propia = empresaReporteUnidades('VENTANA-PROPIA');
    $ajena = empresaReporteUnidades('VENTANA-AJENA');
    unidadReporteUnidades($propia, 'RU-VENTANA-PROPIA');
    $unidadAjena = unidadReporteUnidades($ajena, 'RU-VENTANA-AJENA');

    $this->actingAs(usuarioReporteUnidades(User::ROL_EMPRESA_AUDITOR, $propia))
        ->get(route('reportes.unidades.ventana', [
            'consultar' => 1,
            'busqueda' => 'VENTANA',
            'empresa_ids' => [$ajena->id],
            'unidad_ids' => [$unidadAjena->id],
        ]))
        ->assertOk()->assertDontSee('RU-VENTANA-AJENA')
        ->assertSee('value="VENTANA"', false);

    $this->actingAs(usuarioReporteUnidades(User::ROL_DIESEL_TECNICO))
        ->get(route('reportes.unidades.ventana'))->assertForbidden();
});

test('enlace de nueva pestaña conserva query string activa', function () {
    prepararPermisosReporteUnidades($this);
    $empresa = empresaReporteUnidades('QUERY');
    unidadReporteUnidades($empresa, 'RU-QUERY');

    $this->actingAs(usuarioReporteUnidades(User::ROL_DIESEL_ADMIN))
        ->get(route('reportes.unidades.index', [
            'consultar' => 1,
            'busqueda' => 'RU-QUERY',
            'estado' => 'activa',
        ]))
        ->assertOk()
        ->assertSee('Abrir en nueva pestaña')
        ->assertSee('busqueda=RU-QUERY', false)
        ->assertSee('estado=activa', false);
});

test('limpiar en vista normal vuelve al universo completo sin query string', function () {
    prepararPermisosReporteUnidades($this);
    $empresaA = empresaReporteUnidades('LIMPIAR-A');
    $empresaB = empresaReporteUnidades('LIMPIAR-B');
    $unidadA = unidadReporteUnidades($empresaA, 'RU-LIMPIAR-A');
    $unidadB = unidadReporteUnidades($empresaB, 'RU-LIMPIAR-B');
    $usuario = usuarioReporteUnidades(User::ROL_DIESEL_ADMIN);

    $this->actingAs($usuario)
        ->get(route('reportes.unidades.index', [
            'consultar' => 1,
            'unidad_ids' => [$unidadA->id],
            'page' => 1,
        ]))
        ->assertOk()
        ->assertSee('data-report-unit-row="'.$unidadA->id.'"', false)
        ->assertDontSee('data-report-unit-row="'.$unidadB->id.'"', false)
        ->assertSee(
            'href="'.route('reportes.unidades.index').'" class="cc-btn-secondary">Limpiar</a>',
            false
        );

    $this->actingAs($usuario)
        ->get(route('reportes.unidades.index'))
        ->assertOk()
        ->assertDontSee('data-report-unit-row=', false)
        ->assertSee('No hay resultados para mostrar. Utilice los filtros y presione Consultar.')
        ->assertDontSee('data-summary-label=', false);
});

test('limpiar en ventana conserva tenant y usa ruta base sin query string', function () {
    prepararPermisosReporteUnidades($this);
    $propia = empresaReporteUnidades('LIMPIAR-TENANT');
    $ajena = empresaReporteUnidades('LIMPIAR-AJENA');
    $propiaA = unidadReporteUnidades($propia, 'RU-TENANT-A');
    $propiaB = unidadReporteUnidades($propia, 'RU-TENANT-B');
    $unidadAjena = unidadReporteUnidades($ajena, 'RU-TENANT-AJENA');
    $usuario = usuarioReporteUnidades(User::ROL_EMPRESA_AUDITOR, $propia);

    $this->actingAs($usuario)
        ->get(route('reportes.unidades.ventana', ['consultar' => 1, 'unidad_ids' => [$propiaA->id]]))
        ->assertOk()
        ->assertDontSee('data-report-unit-row="'.$propiaB->id.'"', false)
        ->assertSee(
            'href="'.route('reportes.unidades.ventana').'" class="cc-btn-secondary">Limpiar</a>',
            false
        );

    $this->actingAs($usuario)
        ->get(route('reportes.unidades.ventana'))
        ->assertOk()
        ->assertDontSee('data-report-unit-row=', false)
        ->assertDontSee('data-report-unit-row="'.$unidadAjena->id.'"', false)
        ->assertSee('No hay resultados para mostrar. Utilice los filtros y presione Consultar.');
});

test('ruta base autorizada permanece en estado inicial hasta consultar', function () {
    prepararPermisosReporteUnidades($this);
    $empresa = empresaReporteUnidades('INICIAL');
    $unidad = unidadReporteUnidades($empresa, 'RU-INICIAL');
    $usuario = usuarioReporteUnidades(User::ROL_DIESEL_ADMIN);

    $this->actingAs($usuario)
        ->get(route('reportes.unidades.index'))
        ->assertOk()
        ->assertDontSee('data-report-unit-row="'.$unidad->id.'"', false)
        ->assertDontSee('data-summary-label=', false)
        ->assertDontSee('Descargar PDF')
        ->assertSee('name="consultar" value="1"', false);

    $this->actingAs($usuario)
        ->get(route('reportes.unidades.index', ['consultar' => 1]))
        ->assertOk()
        ->assertSee('data-report-unit-row="'.$unidad->id.'"', false)
        ->assertSee(route('reportes.unidades.pdf', ['consultar' => 1]))
        ->assertSee('data-summary-label="Total unidades" data-summary-value="1"', false);
});

test('usuario empresa al consultar sin filtros obtiene todo su tenant y nada ajeno', function () {
    prepararPermisosReporteUnidades($this);
    $propia = empresaReporteUnidades('CONSULTA-TENANT');
    $ajena = empresaReporteUnidades('CONSULTA-AJENA');
    $propiaA = unidadReporteUnidades($propia, 'RU-CONSULTA-A');
    $propiaB = unidadReporteUnidades($propia, 'RU-CONSULTA-B');
    $unidadAjena = unidadReporteUnidades($ajena, 'RU-CONSULTA-AJENA');

    $this->actingAs(usuarioReporteUnidades(User::ROL_EMPRESA_ADMIN, $propia))
        ->get(route('reportes.unidades.index', ['consultar' => 1]))
        ->assertOk()
        ->assertSee('data-report-unit-row="'.$propiaA->id.'"', false)
        ->assertSee('data-report-unit-row="'.$propiaB->id.'"', false)
        ->assertDontSee('data-report-unit-row="'.$unidadAjena->id.'"', false)
        ->assertSee('data-summary-label="Total unidades" data-summary-value="2"', false);
});

test('paginacion limita a diez y conserva consultar filtros y pagina', function () {
    prepararPermisosReporteUnidades($this);
    $empresa = empresaReporteUnidades('PAGINACION');
    $unidades = collect(range(1, 12))->map(
        fn (int $numero) => unidadReporteUnidades(
            $empresa,
            'RU-PAG-'.str_pad((string) $numero, 2, '0', STR_PAD_LEFT),
            'activa'
        )
    );
    $usuario = usuarioReporteUnidades(User::ROL_DIESEL_AUDITOR);
    $filtros = ['consultar' => 1, 'estado' => 'activa'];

    $paginaUno = $this->actingAs($usuario)
        ->get(route('reportes.unidades.index', $filtros))
        ->assertOk()
        ->assertSee('data-report-unit-row="'.$unidades[0]->id.'"', false)
        ->assertDontSee('data-report-unit-row="'.$unidades[10]->id.'"', false)
        ->assertSee('consultar=1', false)
        ->assertSee('estado=activa', false);

    expect(substr_count($paginaUno->getContent(), 'data-report-unit-row='))->toBe(10);

    $paginaDos = $this->actingAs($usuario)
        ->get(route('reportes.unidades.index', $filtros + ['page' => 2]))
        ->assertOk()
        ->assertSee('data-report-unit-row="'.$unidades[10]->id.'"', false)
        ->assertSee('data-report-unit-row="'.$unidades[11]->id.'"', false)
        ->assertDontSee('data-report-unit-row="'.$unidades[0]->id.'"', false)
        ->assertSee('consultar=1', false)
        ->assertSee('estado=activa', false);

    expect(substr_count($paginaDos->getContent(), 'data-report-unit-row='))->toBe(2);
});

test('reporte y ver ficha preservan contexto y ofrecen nueva pestaña', function () {
    prepararPermisosReporteUnidades($this);
    $empresa = empresaReporteUnidades('NAVEGACION');
    $unidad = unidadReporteUnidades($empresa, 'RU-NAVEGACION');
    $usuario = usuarioReporteUnidades(User::ROL_DIESEL_ADMIN);
    $query = [
        'consultar' => 1,
        'estado' => 'activa',
        'busqueda' => 'RU-NAVEGACION',
        'page' => 1,
    ];

    $this->actingAs($usuario)
        ->get(route('reportes.unidades.index', $query))
        ->assertOk()
        ->assertSee('Abrir en nueva pestaña')
        ->assertSee('target="_blank"', false)
        ->assertSee('rel="noopener noreferrer"', false)
        ->assertSee(route('reportes.unidades.ventana', $query))
        ->assertSee('consultar=1', false)
        ->assertSee('estado=activa', false)
        ->assertSee(route('reportes.unidades.show', ['unidad' => $unidad->id] + $query))
        ->assertSee(route('reportes.unidades.pdf', collect($query)->except('page')->all()))
        ->assertSee('busqueda=RU-NAVEGACION', false);

    $this->actingAs($usuario)
        ->get(route('reportes.unidades.show', ['unidad' => $unidad->id] + $query))
        ->assertOk()
        ->assertSee('Volver al reporte')
        ->assertSee('Abrir en nueva pestaña')
        ->assertSee(route('reportes.unidades.index', $query))
        ->assertSee('estado=activa', false)
        ->assertSee(route('reportes.unidades.show.ventana', ['unidad' => $unidad->id] + $query))
        ->assertSee(route('reportes.unidades.show.pdf', $unidad))
        ->assertSee('busqueda=RU-NAVEGACION', false)
        ->assertDontSee('Infraestructura preparada');
});

test('ficha ventana comparte permiso tenant contenido y regreso contextual', function () {
    prepararPermisosReporteUnidades($this);
    $propia = empresaReporteUnidades('FICHA-VENTANA');
    $ajena = empresaReporteUnidades('FICHA-VENTANA-AJENA');
    $unidadPropia = unidadReporteUnidades($propia, 'RU-FICHA-VENTANA');
    $unidadAjena = unidadReporteUnidades($ajena, 'RU-FICHA-AJENA');
    $query = ['consultar' => 1, 'estado' => 'activa', 'page' => 2];
    $usuario = usuarioReporteUnidades(User::ROL_EMPRESA_AUDITOR, $propia);

    $this->actingAs($usuario)
        ->get(route('reportes.unidades.show.ventana', ['unidad' => $unidadPropia->id] + $query))
        ->assertOk()
        ->assertSee('Ficha completa de la unidad')
        ->assertSee('RU-FICHA-VENTANA')
        ->assertSee('Volver al reporte')
        ->assertSee(route('reportes.unidades.ventana', $query))
        ->assertSee('consultar=1', false)
        ->assertSee('estado=activa', false)
        ->assertSee('page=2', false)
        ->assertDontSee('Abrir en nueva pestaña');

    $this->actingAs($usuario)
        ->get(route('reportes.unidades.show.ventana', $unidadAjena))
        ->assertForbidden();

    $this->actingAs(usuarioReporteUnidades(User::ROL_EMPRESA_OPERADOR, $propia))
        ->get(route('reportes.unidades.show.ventana', $unidadPropia))
        ->assertForbidden();
});

test('reporte general muestra rendimiento teorico correcto para los tres modelos disponibles', function () {
    prepararPermisosReporteUnidades($this);
    $empresa = empresaReporteUnidades('RENDIMIENTO');
    unidadReporteUnidades($empresa, 'RU-REND-KM', 'activa', 'kilometros_galon');
    unidadReporteUnidades($empresa, 'RU-REND-HORA', 'activa', 'galones_hora');
    $unidadViaje = unidadReporteUnidades($empresa, 'RU-REND-VIAJE', 'activa', 'galones_viaje');
    $usuario = usuarioReporteUnidades(User::ROL_DIESEL_ADMIN);

    $this->actingAs($usuario)
        ->get(route('reportes.unidades.index', ['consultar' => 1]))
        ->assertOk()
        ->assertSee('Rendimiento Teórico')
        ->assertDontSee('Rendimiento configurado')
        ->assertSee('10.00 km/gal')
        ->assertSee('2.00 gal/hora')
        ->assertSee('Según ruta (gal/viaje)')
        ->assertDontSee('No definido');

    $this->actingAs($usuario)
        ->get(route('reportes.unidades.show', $unidadViaje))
        ->assertOk()
        ->assertSee('Rendimiento Teórico')
        ->assertSee('Según ruta (gal/viaje)');
});

test('ficha completa presenta identificacion tanques licencia puntos actuales y resumen operacional', function () {
    prepararPermisosReporteUnidades($this);
    $empresa = empresaReporteUnidades('FICHA-COMPLETA');
    $unidad = unidadReporteUnidades($empresa, 'RU-FICHA-COMPLETA');
    convertirUnidadEnOperable($unidad);

    $tanqueCubierto = UnidadTanque::query()->create([
        'unidad_id' => $unidad->id,
        'numero' => 1,
        'capacidad' => 60,
        'cubierto_por_licencia' => true,
    ]);
    UnidadTanque::query()->create([
        'unidad_id' => $unidad->id,
        'numero' => 2,
        'capacidad' => 40,
        'cubierto_por_licencia' => false,
    ]);
    $licencia = Licencia::query()->where('unidad_id', $unidad->id)->firstOrFail();
    LicenciaTanque::query()->create([
        'licencia_id' => $licencia->id,
        'unidad_tanque_id' => $tanqueCubierto->id,
        'numero_tanque_snapshot' => 1,
        'capacidad_snapshot' => 60,
    ]);
    $puntoActual = PuntoSeguridadUnidad::query()->where('unidad_id', $unidad->id)->firstOrFail();
    $puntoActual->update([
        'codigo_punto' => 'PS-001',
        'grupo' => 'Gabinete',
        'subgrupo' => 'Principal',
        'posicion_tanque' => 'Tanque 1',
    ]);
    PuntoSeguridadUnidad::query()->create([
        'unidad_id' => $unidad->id,
        'orden' => 2,
        'codigo_punto' => 'PS-002',
        'grupo' => 'Tapadera',
        'subgrupo' => 'Secundaria',
        'nombre_punto' => 'Punto pendiente',
        'posicion_tanque' => 'Tanque 2',
        'requiere_marchamo' => true,
        'plantilla_origen' => 'plantilla_1_tanque',
        'estado_asignacion' => 'pendiente',
        'estado' => 'activo',
    ]);
    $codigoMarchamo = $puntoActual->fresh()->marchamoActual->codigo_marchamo;

    $respuesta = $this->actingAs(usuarioReporteUnidades(User::ROL_DIESEL_AUDITOR))
        ->get(route('reportes.unidades.show', $unidad))
        ->assertOk();

    foreach (['Identificación', 'Rendimiento Teórico', '10.00 km/gal', 'Tanques físicos',
        'Tanque 1', '60.00 gal', 'Tanque 2', '40.00 gal', 'Licencia', 'Plantilla 1 tanque',
        'Puntos esperados', '29', 'Tanques cubiertos', 'Capacidad cubierta', 'PS-001',
        'PS-002', $codigoMarchamo, 'Pendiente', 'Puntos de seguridad', 'Marchamos asignados',
        'Marchamos pendientes', 'Cobertura', 'Estado operacional', 'Resultado final'] as $texto) {
        $respuesta->assertSee($texto);
    }

    expect(strpos($respuesta->getContent(), 'PS-001'))
        ->toBeLessThan(strpos($respuesta->getContent(), 'PS-002'));
    $respuesta->assertDontSee('Historial de marchamos');
    $respuesta->assertSee('Estado marchamo')
        ->assertDontSee('Estado punto');
});

test('ficha sin licencia muestra condicion explicita y la variante ventana comparte contenido', function () {
    prepararPermisosReporteUnidades($this);
    $empresa = empresaReporteUnidades('SIN-LICENCIA');
    $unidad = unidadReporteUnidades($empresa, 'RU-SIN-LICENCIA', 'registrada', 'galones_hora');
    $usuario = usuarioReporteUnidades(User::ROL_EMPRESA_SUPERVISOR, $empresa);

    foreach (['reportes.unidades.show', 'reportes.unidades.show.ventana'] as $ruta) {
        $this->actingAs($usuario)
            ->get(route($ruta, $unidad))
            ->assertOk()
            ->assertSee('RU-SIN-LICENCIA')
            ->assertSee('2.00 gal/hora')
            ->assertSee('Sin licencia')
            ->assertSee('Sin tanques físicos registrados.')
            ->assertSee('Sin puntos de seguridad registrados.')
            ->assertSee('No operable');
    }
});

test('pdf general exige consulta respeta permiso y genera descarga real', function () {
    prepararPermisosReporteUnidades($this);
    $empresa = empresaReporteUnidades('PDF-MOTOR');
    unidadReporteUnidades($empresa, 'RU-PDF-MOTOR');

    $this->actingAs(usuarioReporteUnidades(User::ROL_DIESEL_ADMIN))
        ->get(route('reportes.unidades.pdf'))
        ->assertNotFound();

    $respuesta = $this->actingAs(usuarioReporteUnidades(User::ROL_DIESEL_ADMIN))
        ->get(route('reportes.unidades.pdf', ['consultar' => 1]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('reporte-unidades-'.now()->format('Y-m-d').'.pdf');

    expect($respuesta->getContent())->toStartWith('%PDF');

    $this->actingAs(usuarioReporteUnidades(User::ROL_DIESEL_TECNICO))
        ->get(route('reportes.unidades.pdf', ['consultar' => 1]))
        ->assertForbidden();
    $this->actingAs(usuarioReporteUnidades(User::ROL_EMPRESA_OPERADOR, $empresa))
        ->get(route('reportes.unidades.pdf', ['consultar' => 1]))
        ->assertForbidden();
});

test('pdf general usa todos los filtros desde la misma consulta', function () {
    prepararPermisosReporteUnidades($this);
    $empresaA = empresaReporteUnidades('PDF-FILTRO-A');
    $empresaB = empresaReporteUnidades('PDF-FILTRO-B');
    $objetivo = unidadReporteUnidades($empresaA, 'RU-PDF-OBJETIVO', 'activa', 'galones_hora');
    convertirUnidadEnOperable($objetivo);
    unidadReporteUnidades($empresaA, 'RU-PDF-OTRA', 'inactiva', 'kilometros_galon');
    unidadReporteUnidades($empresaB, 'RU-PDF-AJENA', 'activa', 'galones_hora');
    $datosPdf = null;
    $documento = Mockery::mock(\Barryvdh\DomPDF\PDF::class);

    Pdf::shouldReceive('loadView')->once()->withArgs(function (string $vista, array $datos) use (&$datosPdf): bool {
        $datosPdf = $datos;
        return $vista === 'reportes.unidades.pdf-general';
    })->andReturn($documento);
    $documento->shouldReceive('setPaper')->once()->with('a4', 'landscape')->andReturnSelf();
    $documento->shouldReceive('download')->once()->andReturn(response('PDF', 200, ['Content-Type' => 'application/pdf']));

    $this->actingAs(usuarioReporteUnidades(User::ROL_DIESEL_AUDITOR))
        ->get(route('reportes.unidades.pdf', [
            'consultar' => 1,
            'busqueda' => 'OBJETIVO',
            'empresa_ids' => [$empresaA->id],
            'unidad_ids' => [$objetivo->id],
            'estado' => 'activa',
            'disponibilidad' => 'operable',
            'modelo_medicion' => 'galones_hora',
        ]))->assertOk();

    expect($datosPdf['unidades'])->toHaveCount(1)
        ->and($datosPdf['unidades']->first()->is($objetivo))->toBeTrue()
        ->and($datosPdf['resumen'])->toBe([
            'resultados' => 1,
            'registradas' => 0,
            'activas' => 1,
            'operables' => 1,
        ])
        ->and($datosPdf['filtrosAplicados'])->toMatchArray([
            'Búsqueda' => 'OBJETIVO',
            'Estado' => 'Activa',
            'Disponibilidad' => 'Operable',
            'Modelo' => 'Galones por hora',
        ]);
});

test('pdf general ignora page incluye mas de diez unidades y conserva terminologia', function () {
    prepararPermisosReporteUnidades($this);
    $empresa = empresaReporteUnidades('PDF-TODAS');
    collect(range(1, 12))->each(fn (int $numero) => unidadReporteUnidades(
        $empresa,
        'RU-PDF-TODAS-'.str_pad((string) $numero, 2, '0', STR_PAD_LEFT),
        'activa',
        $numero === 12 ? 'galones_viaje' : 'kilometros_galon'
    ));
    $datosPdf = null;
    $documento = Mockery::mock(\Barryvdh\DomPDF\PDF::class);

    Pdf::shouldReceive('loadView')->once()->withArgs(function (string $vista, array $datos) use (&$datosPdf): bool {
        $datosPdf = $datos;
        return $vista === 'reportes.unidades.pdf-general';
    })->andReturn($documento);
    $documento->shouldReceive('setPaper')->with('a4', 'landscape')->andReturnSelf();
    $documento->shouldReceive('download')->andReturn(response('PDF'));

    $this->actingAs(usuarioReporteUnidades(User::ROL_DIESEL_ADMIN))
        ->get(route('reportes.unidades.pdf', ['consultar' => 1, 'page' => 2]))
        ->assertOk();

    $html = view('reportes.unidades.pdf-general', $datosPdf)->render();
    expect($datosPdf['unidades'])->toHaveCount(12)
        ->and(substr_count($html, 'data-pdf-unit='))->toBe(12)
        ->and($html)->toContain('Resultados')
        ->toContain('Registradas')
        ->toContain('Activas')
        ->toContain('Operables')
        ->and($html)->toContain('Rendimiento Teórico')
        ->toContain('Según ruta (gal/viaje)')
        ->not->toContain('Rendimiento configurado')
        ->not->toContain('<th>Acción</th>');
});

test('pdf general de usuario empresa mantiene tenant ante filtros manipulados', function () {
    prepararPermisosReporteUnidades($this);
    $propia = empresaReporteUnidades('PDF-TENANT');
    $ajena = empresaReporteUnidades('PDF-TENANT-AJENA');
    $unidadPropia = unidadReporteUnidades($propia, 'RU-PDF-PROPIA');
    $unidadAjena = unidadReporteUnidades($ajena, 'RU-PDF-AJENA');
    $datosPdf = null;
    $documento = Mockery::mock(\Barryvdh\DomPDF\PDF::class);

    Pdf::shouldReceive('loadView')->once()->withArgs(function (string $vista, array $datos) use (&$datosPdf): bool {
        $datosPdf = $datos;
        return true;
    })->andReturn($documento);
    $documento->shouldReceive('setPaper')->andReturnSelf();
    $documento->shouldReceive('download')->andReturn(response('PDF'));

    $this->actingAs(usuarioReporteUnidades(User::ROL_EMPRESA_ADMIN, $propia))
        ->get(route('reportes.unidades.pdf', [
            'consultar' => 1,
            'empresa_ids' => [$ajena->id],
            'unidad_ids' => [$unidadPropia->id, $unidadAjena->id],
        ]))->assertOk();

    expect($datosPdf['unidades']->pluck('id')->all())->toBe([$unidadPropia->id])
        ->and($datosPdf['filtrosAplicados']['Nombre / Placa'])->toBe('RU-PDF-PROPIA')
        ->and($datosPdf['filtrosAplicados']['Empresa'])->toBe('Reporte PDF-TENANT');
});

test('pdf ficha contiene configuracion actual completa y protege tenant', function () {
    prepararPermisosReporteUnidades($this);
    $propia = empresaReporteUnidades('PDF-FICHA');
    $ajena = empresaReporteUnidades('PDF-FICHA-AJENA');
    $unidad = unidadReporteUnidades($propia, 'RU-PDF-FICHA', 'activa', 'galones_viaje');
    $unidadAjena = unidadReporteUnidades($ajena, 'RU-PDF-FICHA-AJENA');
    convertirUnidadEnOperable($unidad);
    UnidadTanque::query()->create(['unidad_id' => $unidad->id, 'numero' => 1, 'capacidad' => 100, 'cubierto_por_licencia' => true]);
    $punto = PuntoSeguridadUnidad::query()->where('unidad_id', $unidad->id)->firstOrFail();
    $codigoMarchamo = $punto->marchamoActual->codigo_marchamo;
    $datosPdf = null;
    $documento = Mockery::mock(\Barryvdh\DomPDF\PDF::class);

    Pdf::shouldReceive('loadView')->once()->withArgs(function (string $vista, array $datos) use (&$datosPdf): bool {
        $datosPdf = $datos + ['generadoEn' => now(), 'logoPath' => public_path('images/cc-flota/logo.png')];
        return $vista === 'reportes.unidades.pdf-ficha';
    })->andReturn($documento);
    $documento->shouldReceive('setPaper')->once()->with('a4', 'portrait')->andReturnSelf();
    $documento->shouldReceive('download')->once()
        ->with('ficha-unidad-RU-PDF-FICHA-'.now()->format('Y-m-d').'.pdf')
        ->andReturn(response('PDF'));
    $usuario = usuarioReporteUnidades(User::ROL_EMPRESA_AUDITOR, $propia);

    $this->actingAs($usuario)->get(route('reportes.unidades.show.pdf', $unidad))->assertOk();
    $html = view('reportes.unidades.pdf-ficha', $datosPdf)->render();

    expect($html)->toContain('RU-PDF-FICHA')
        ->toContain('Reporte PDF-FICHA')
        ->toContain('Rendimiento Teórico')
        ->toContain('Según ruta (gal/viaje)')
        ->toContain('Tanque 1')
        ->toContain('Licencia')
        ->toContain('Identificación')
        ->toContain('Estado operacional')
        ->toContain('Resumen de seguridad')
        ->toContain('Puntos de seguridad y marchamos actuales')
        ->toContain($codigoMarchamo)
        ->toContain('Estado marchamo')
        ->not->toContain('Estado punto')
        ->not->toContain('Historial')
        ->not->toContain('Reemplazo');

    $this->actingAs($usuario)
        ->get(route('reportes.unidades.show.pdf', $unidadAjena))
        ->assertForbidden();
});

test('resumen de unidades aparece entre filtros y tabla solo con resultados consultados', function () {
    prepararPermisosReporteUnidades($this);
    $empresa = empresaReporteUnidades('ORDEN-RESUMEN');
    unidadReporteUnidades($empresa, 'RU-ORDEN-RESUMEN');
    $usuario = usuarioReporteUnidades(User::ROL_DIESEL_AUDITOR);

    $this->actingAs($usuario)->get(route('reportes.unidades.index'))
        ->assertOk()->assertDontSee('data-report-summary', false);
    $respuesta = $this->actingAs($usuario)->get(route('reportes.unidades.index', ['consultar' => 1]))
        ->assertOk()->assertSee('data-report-summary', false);
    $html = $respuesta->getContent();
    expect(strpos($html, '<form'))->toBeLessThan(strpos($html, 'data-report-summary'))
        ->and(strpos($html, 'data-report-summary'))->toBeLessThan(strpos($html, 'cc-result-count'));
});
