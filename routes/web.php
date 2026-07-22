<?php

use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\GasolineraController;
use App\Http\Controllers\GasolineraExternaController;
use App\Http\Controllers\PuntoRutaController;
use App\Http\Controllers\RutaController;
use App\Http\Controllers\LicenciaController;
use App\Http\Controllers\RecargaTanqueController;
use App\Http\Controllers\AbastecimientoController;
use App\Http\Controllers\AnalisisOperativoController;
use App\Http\Controllers\AnalisisRendimientoController;
use App\Http\Controllers\MarchamoAsignacionInicialController;
use App\Http\Controllers\MarchamoController;
use App\Http\Controllers\MarchamoReemplazoController;
use App\Http\Controllers\MotoristaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TanqueController;
use App\Http\Controllers\UnidadController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Perfil
    |--------------------------------------------------------------------------
    */

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | Usuarios
    |--------------------------------------------------------------------------
    */

    Route::get('/usuarios', [UsuarioController::class, 'index'])
        ->name('usuarios.index');

    Route::get('/usuarios/consulta/ventana', [UsuarioController::class, 'consultaVentana'])
        ->name('usuarios.consulta.ventana');

    Route::get('/usuarios/administrar', [UsuarioController::class, 'administrar'])
        ->name('usuarios.administrar');

    Route::get('/usuarios/administrar/ventana', [UsuarioController::class, 'administrarVentana'])
        ->name('usuarios.administrar.ventana');

    Route::get('/usuarios/nuevo', [UsuarioController::class, 'create'])
        ->name('usuarios.create');

    Route::get('/usuarios/nuevo/ventana', [UsuarioController::class, 'createVentana'])
        ->name('usuarios.create.ventana');

    Route::post('/usuarios', [UsuarioController::class, 'store'])
        ->name('usuarios.store');

    Route::get('/usuarios/{usuario}/ventana', [UsuarioController::class, 'showVentana'])
        ->name('usuarios.show.ventana');

    Route::get('/usuarios/{usuario}/editar/ventana', [UsuarioController::class, 'editVentana'])
        ->name('usuarios.edit.ventana');

    Route::get('/usuarios/{usuario}', [UsuarioController::class, 'show'])
        ->name('usuarios.show');

    Route::get('/usuarios/{usuario}/editar', [UsuarioController::class, 'edit'])
        ->name('usuarios.edit');

    Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])
        ->name('usuarios.update');

    Route::patch('/usuarios/{usuario}/inactivar', [UsuarioController::class, 'inactivar'])
        ->name('usuarios.inactivar');

    Route::patch('/usuarios/{usuario}/reactivar', [UsuarioController::class, 'reactivar'])
        ->name('usuarios.reactivar');

    /*
    |--------------------------------------------------------------------------
    | Unidades
    |--------------------------------------------------------------------------
    */

    Route::get('/unidades', [UnidadController::class, 'index'])
        ->name('unidades.index');

    Route::get('/unidades/consulta/ventana', [UnidadController::class, 'consultaVentana'])
        ->name('unidades.consulta.ventana');

    Route::get('/unidades/administrar', [UnidadController::class, 'administrar'])
        ->name('unidades.administrar');

    Route::get('/unidades/administrar/ventana', [UnidadController::class, 'administrarVentana'])
        ->name('unidades.administrar.ventana');

    Route::get('/unidades/nueva', [UnidadController::class, 'create'])
        ->name('unidades.create');

    Route::get('/unidades/nueva/ventana', [UnidadController::class, 'createVentana'])
        ->name('unidades.create.ventana');

    Route::post('/unidades', [UnidadController::class, 'store'])
        ->name('unidades.store');

    Route::get('/unidades/{unidad}/ficha/ventana', [UnidadController::class, 'showVentana'])
        ->name('unidades.show.ventana');

    Route::get('/unidades/{unidad}/editar/ventana', [UnidadController::class, 'editVentana'])
        ->name('unidades.edit.ventana');

    Route::get('/unidades/{unidad}', [UnidadController::class, 'show'])
        ->name('unidades.show');

    Route::get('/unidades/{unidad}/editar', [UnidadController::class, 'edit'])
        ->name('unidades.edit');

    Route::put('/unidades/{unidad}', [UnidadController::class, 'update'])
        ->name('unidades.update');

    Route::patch('/unidades/{unidad}/inactivar', [UnidadController::class, 'inactivar'])
        ->name('unidades.inactivar');

    Route::patch('/unidades/{unidad}/reactivar', [UnidadController::class, 'reactivar'])
        ->name('unidades.reactivar');

    /*
    |--------------------------------------------------------------------------
    | Gasolineras
    |--------------------------------------------------------------------------
    */

    Route::get('/gasolineras', [GasolineraController::class, 'index'])
        ->name('gasolineras.index');

    Route::get('/gasolineras/consulta/ventana', [GasolineraController::class, 'consultaVentana'])
        ->name('gasolineras.consulta.ventana');

    Route::get('/gasolineras/administrar', [GasolineraController::class, 'administrar'])
        ->name('gasolineras.administrar');

    Route::get('/gasolineras/administrar/ventana', [GasolineraController::class, 'administrarVentana'])
        ->name('gasolineras.administrar.ventana');

    Route::get('/gasolineras/nueva', [GasolineraController::class, 'create'])
        ->name('gasolineras.create');

    Route::get('/gasolineras/nueva/ventana', [GasolineraController::class, 'createVentana'])
        ->name('gasolineras.create.ventana');

    Route::post('/gasolineras', [GasolineraController::class, 'store'])
        ->name('gasolineras.store');

    /*
    |--------------------------------------------------------------------------
    | Gestión de tanques
    |--------------------------------------------------------------------------
    |
    | Estas rutas deben estar antes de /gasolineras/{gasolinera},
    | porque /gasolineras/tanques es una ruta fija.
    |
    */

    Route::get('/gasolineras/tanques', [TanqueController::class, 'index'])
        ->name('gasolineras.tanques.index');

    Route::get('/gasolineras/tanques/ventana', [TanqueController::class, 'indexVentana'])
        ->name('gasolineras.tanques.index.ventana');

    Route::post('/gasolineras/{gasolinera}/tanques', [GasolineraController::class, 'storeTanque'])
        ->name('gasolineras.tanques.store');

    Route::get('/gasolineras/{gasolinera}/tanques/{tanque}/administrar', [TanqueController::class, 'show'])
        ->name('gasolineras.tanques.show');

    Route::get('/gasolineras/{gasolinera}/tanques/{tanque}/administrar/ventana', [TanqueController::class, 'showVentana'])
        ->name('gasolineras.tanques.show.ventana');

    Route::put('/gasolineras/{gasolinera}/tanques/{tanque}', [TanqueController::class, 'update'])
        ->name('gasolineras.tanques.update');

    Route::patch('/gasolineras/{gasolinera}/tanques/{tanque}/inactivar', [TanqueController::class, 'inactivar'])
        ->name('gasolineras.tanques.inactivar');

    Route::patch('/gasolineras/{gasolinera}/tanques/{tanque}/reactivar', [TanqueController::class, 'reactivar'])
        ->name('gasolineras.tanques.reactivar');

    /*
    |--------------------------------------------------------------------------
    | Recarga de tanques
    |--------------------------------------------------------------------------
    */

    Route::get('/gasolineras/tanques/recargas', [RecargaTanqueController::class, 'index'])
        ->name('gasolineras.tanques.recargas.index');

    Route::get('/gasolineras/tanques/recargas/ventana', [RecargaTanqueController::class, 'indexVentana'])
        ->name('gasolineras.tanques.recargas.index.ventana');

    Route::get('/gasolineras/{gasolinera}/tanques/recargas/crear', [RecargaTanqueController::class, 'create'])
        ->name('gasolineras.tanques.recargas.create');

    Route::get('/gasolineras/{gasolinera}/tanques/recargas/crear/ventana', [RecargaTanqueController::class, 'createVentana'])
        ->name('gasolineras.tanques.recargas.create.ventana');

    Route::post('/gasolineras/{gasolinera}/tanques/recargas', [RecargaTanqueController::class, 'store'])
        ->name('gasolineras.tanques.recargas.store');

    Route::patch('/gasolineras/{gasolinera}/tanques/recargas/{recarga}/anular', [RecargaTanqueController::class, 'anular'])
        ->name('gasolineras.tanques.recargas.anular');

    /*
    |--------------------------------------------------------------------------
    | Recarga individual heredada
    |--------------------------------------------------------------------------
    |
    | Estas rutas se conservan temporalmente para evitar romper enlaces existentes.
    | Más adelante pueden eliminarse cuando el flujo multi-tanque esté validado.
    |
    */

    Route::get('/gasolineras/{gasolinera}/tanques/{tanque}/recargar', [RecargaTanqueController::class, 'show'])
        ->name('gasolineras.tanques.recargas.show');

    Route::get('/gasolineras/{gasolinera}/tanques/{tanque}/recargar/ventana', [RecargaTanqueController::class, 'showVentana'])
        ->name('gasolineras.tanques.recargas.show.ventana');

    /*
    |--------------------------------------------------------------------------
    | Gasolineras - Rutas dinámicas
    |--------------------------------------------------------------------------
    |
    | Estas rutas deben quedar después de las rutas fijas de gasolineras
    | y después de gestión de tanques.
    |
    */

    Route::get('/gasolineras/{gasolinera}/ficha/ventana', [GasolineraController::class, 'showVentana'])
        ->name('gasolineras.show.ventana');

    Route::get('/gasolineras/{gasolinera}/editar/ventana', [GasolineraController::class, 'editVentana'])
        ->name('gasolineras.edit.ventana');

    Route::get('/gasolineras/{gasolinera}', [GasolineraController::class, 'show'])
        ->name('gasolineras.show');

    Route::get('/gasolineras/{gasolinera}/editar', [GasolineraController::class, 'edit'])
        ->name('gasolineras.edit');

    Route::put('/gasolineras/{gasolinera}', [GasolineraController::class, 'update'])
        ->name('gasolineras.update');

    Route::patch('/gasolineras/{gasolinera}/inactivar', [GasolineraController::class, 'inactivar'])
        ->name('gasolineras.inactivar');

    Route::patch('/gasolineras/{gasolinera}/reactivar', [GasolineraController::class, 'reactivar'])
        ->name('gasolineras.reactivar');

    /*
    |--------------------------------------------------------------------------
    | Gasolineras externas
    |--------------------------------------------------------------------------
    */

    Route::get('/gasolineras-externas', [GasolineraExternaController::class, 'index'])
        ->name('gasolineras-externas.index');

    Route::get('/gasolineras-externas/consulta/ventana', [GasolineraExternaController::class, 'consultaVentana'])
        ->name('gasolineras-externas.consulta.ventana');

    Route::get('/gasolineras-externas/administrar', [GasolineraExternaController::class, 'administrar'])
        ->name('gasolineras-externas.administrar');

    Route::get('/gasolineras-externas/administrar/ventana', [GasolineraExternaController::class, 'administrarVentana'])
        ->name('gasolineras-externas.administrar.ventana');

    Route::get('/gasolineras-externas/nueva', [GasolineraExternaController::class, 'create'])
        ->name('gasolineras-externas.create');

    Route::get('/gasolineras-externas/nueva/ventana', [GasolineraExternaController::class, 'createVentana'])
        ->name('gasolineras-externas.create.ventana');

    Route::post('/gasolineras-externas', [GasolineraExternaController::class, 'store'])
        ->name('gasolineras-externas.store');

    Route::get('/gasolineras-externas/{gasolineraExterna}/ficha/ventana', [GasolineraExternaController::class, 'showVentana'])
        ->name('gasolineras-externas.show.ventana');

    Route::get('/gasolineras-externas/{gasolineraExterna}/editar/ventana', [GasolineraExternaController::class, 'editVentana'])
        ->name('gasolineras-externas.edit.ventana');

    Route::get('/gasolineras-externas/{gasolineraExterna}', [GasolineraExternaController::class, 'show'])
        ->name('gasolineras-externas.show');

    Route::get('/gasolineras-externas/{gasolineraExterna}/editar', [GasolineraExternaController::class, 'edit'])
        ->name('gasolineras-externas.edit');

    Route::put('/gasolineras-externas/{gasolineraExterna}', [GasolineraExternaController::class, 'update'])
        ->name('gasolineras-externas.update');

    Route::patch('/gasolineras-externas/{gasolineraExterna}/inactivar', [GasolineraExternaController::class, 'inactivar'])
        ->name('gasolineras-externas.inactivar');

    Route::patch('/gasolineras-externas/{gasolineraExterna}/reactivar', [GasolineraExternaController::class, 'reactivar'])
        ->name('gasolineras-externas.reactivar');

        /*
    |--------------------------------------------------------------------------
    | Puntos de ruta
    |--------------------------------------------------------------------------
    */

    Route::get('/puntos-ruta', [PuntoRutaController::class, 'index'])
        ->name('puntos-ruta.index');

    Route::get('/puntos-ruta/consulta/ventana', [PuntoRutaController::class, 'consultaVentana'])
        ->name('puntos-ruta.consulta.ventana');

    Route::get('/puntos-ruta/administrar', [PuntoRutaController::class, 'administrar'])
        ->name('puntos-ruta.administrar');

    Route::get('/puntos-ruta/administrar/ventana', [PuntoRutaController::class, 'administrarVentana'])
        ->name('puntos-ruta.administrar.ventana');

    Route::get('/puntos-ruta/nuevo', [PuntoRutaController::class, 'create'])
        ->name('puntos-ruta.create');

    Route::get('/puntos-ruta/nuevo/ventana', [PuntoRutaController::class, 'createVentana'])
        ->name('puntos-ruta.create.ventana');

    Route::post('/puntos-ruta', [PuntoRutaController::class, 'store'])
        ->name('puntos-ruta.store');

    Route::get('/puntos-ruta/{puntoRuta}/ficha/ventana', [PuntoRutaController::class, 'showVentana'])
        ->name('puntos-ruta.show.ventana');

    Route::get('/puntos-ruta/{puntoRuta}/editar/ventana', [PuntoRutaController::class, 'editVentana'])
        ->name('puntos-ruta.edit.ventana');

    Route::get('/puntos-ruta/{puntoRuta}', [PuntoRutaController::class, 'show'])
        ->name('puntos-ruta.show');

    Route::get('/puntos-ruta/{puntoRuta}/editar', [PuntoRutaController::class, 'edit'])
        ->name('puntos-ruta.edit');

    Route::put('/puntos-ruta/{puntoRuta}', [PuntoRutaController::class, 'update'])
        ->name('puntos-ruta.update');

    Route::patch('/puntos-ruta/{puntoRuta}/inactivar', [PuntoRutaController::class, 'inactivar'])
        ->name('puntos-ruta.inactivar');

    Route::patch('/puntos-ruta/{puntoRuta}/reactivar', [PuntoRutaController::class, 'reactivar'])
        ->name('puntos-ruta.reactivar');

    Route::get('/rutas', [RutaController::class, 'index'])
        ->name('rutas.index');

    Route::get('/rutas/consulta-ventana', [RutaController::class, 'consultaVentana'])
        ->name('rutas.consulta.ventana');

    Route::get('/rutas/administrar', [RutaController::class, 'administrar'])
        ->name('rutas.administrar');

    Route::get('/rutas/administrar-ventana', [RutaController::class, 'administrarVentana'])
        ->name('rutas.administrar.ventana');

    Route::get('/rutas/create', [RutaController::class, 'create'])
        ->name('rutas.create');

    Route::get('/rutas/create-ventana', [RutaController::class, 'createVentana'])
        ->name('rutas.create.ventana');

    Route::post('/rutas', [RutaController::class, 'store'])
        ->name('rutas.store');

    Route::get('/rutas/{ruta}', [RutaController::class, 'show'])
        ->name('rutas.show');

    Route::get('/rutas/{ruta}/ventana', [RutaController::class, 'showVentana'])
        ->name('rutas.show.ventana');

    Route::get('/rutas/{ruta}/edit', [RutaController::class, 'edit'])
        ->name('rutas.edit');

    Route::get('/rutas/{ruta}/edit-ventana', [RutaController::class, 'editVentana'])
        ->name('rutas.edit.ventana');

    Route::put('/rutas/{ruta}', [RutaController::class, 'update'])
        ->name('rutas.update');

    Route::patch('/rutas/{ruta}/inactivar', [RutaController::class, 'inactivar'])
        ->name('rutas.inactivar');

    Route::patch('/rutas/{ruta}/reactivar', [RutaController::class, 'reactivar'])
        ->name('rutas.reactivar');

        /*
    |--------------------------------------------------------------------------
    | Motoristas
    |--------------------------------------------------------------------------
    */

    Route::get('/motoristas', [MotoristaController::class, 'index'])
        ->name('motoristas.index');

    Route::get('/motoristas/consulta/ventana', [MotoristaController::class, 'consultaVentana'])
        ->name('motoristas.consulta.ventana');

    Route::get('/motoristas/administrar', [MotoristaController::class, 'administrar'])
        ->name('motoristas.administrar');

    Route::get('/motoristas/administrar/ventana', [MotoristaController::class, 'administrarVentana'])
        ->name('motoristas.administrar.ventana');

    Route::get('/motoristas/nuevo', [MotoristaController::class, 'create'])
        ->name('motoristas.create');

    Route::get('/motoristas/nuevo/ventana', [MotoristaController::class, 'createVentana'])
        ->name('motoristas.create.ventana');

    Route::post('/motoristas', [MotoristaController::class, 'store'])
        ->name('motoristas.store');

    Route::get('/motoristas/{motorista}/ficha/ventana', [MotoristaController::class, 'showVentana'])
        ->name('motoristas.show.ventana');

    Route::get('/motoristas/{motorista}/editar/ventana', [MotoristaController::class, 'editVentana'])
        ->name('motoristas.edit.ventana');

    Route::get('/motoristas/{motorista}', [MotoristaController::class, 'show'])
        ->name('motoristas.show');

    Route::get('/motoristas/{motorista}/editar', [MotoristaController::class, 'edit'])
        ->name('motoristas.edit');

    Route::put('/motoristas/{motorista}', [MotoristaController::class, 'update'])
        ->name('motoristas.update');

    Route::patch('/motoristas/{motorista}/inactivar', [MotoristaController::class, 'inactivar'])
        ->name('motoristas.inactivar');

    Route::patch('/motoristas/{motorista}/reactivar', [MotoristaController::class, 'reactivar'])
        ->name('motoristas.reactivar');    

    /*
    |--------------------------------------------------------------------------
    | Licencias
    |--------------------------------------------------------------------------
    */

    Route::get('/licencias', [LicenciaController::class, 'index'])
        ->name('licencias.index');

    Route::get('/licencias/consulta/ventana', [LicenciaController::class, 'consultaVentana'])
        ->name('licencias.consulta.ventana');

    Route::get('/licencias/administrar', [LicenciaController::class, 'administrar'])
        ->name('licencias.administrar');

    Route::get('/licencias/administrar/ventana', [LicenciaController::class, 'administrarVentana'])
        ->name('licencias.administrar.ventana');

    Route::get('/licencias/nueva', [LicenciaController::class, 'create'])
        ->name('licencias.create');

    Route::get('/licencias/nueva/ventana', [LicenciaController::class, 'createVentana'])
        ->name('licencias.create.ventana');

    Route::post('/licencias', [LicenciaController::class, 'store'])
        ->name('licencias.store');

    Route::get('/licencias/{licencia}/ficha/ventana', [LicenciaController::class, 'showVentana'])
        ->name('licencias.show.ventana');

    Route::get('/licencias/{licencia}/editar/ventana', [LicenciaController::class, 'editVentana'])
        ->name('licencias.edit.ventana');

    Route::get('/licencias/{licencia}', [LicenciaController::class, 'show'])
        ->name('licencias.show');

    Route::get('/licencias/{licencia}/editar', [LicenciaController::class, 'edit'])
        ->name('licencias.edit');

    Route::put('/licencias/{licencia}', [LicenciaController::class, 'update'])
        ->name('licencias.update');

    Route::patch('/licencias/{licencia}/inactivar', [LicenciaController::class, 'inactivar'])
        ->name('licencias.inactivar');

    Route::patch('/licencias/{licencia}/reactivar', [LicenciaController::class, 'reactivar'])
        ->name('licencias.reactivar');

    /*
    |--------------------------------------------------------------------------
    | Marchamos
    |--------------------------------------------------------------------------
    */

    Route::get('/marchamos', [MarchamoController::class, 'index'])
        ->name('marchamos.index');

    Route::get('/marchamos/consulta/ventana', [MarchamoController::class, 'consultaVentana'])
        ->name('marchamos.consulta.ventana');

    Route::get('/marchamos/consulta/ventana/unidades/{unidad}', [MarchamoController::class, 'detalleUnidadVentana'])
        ->name('marchamos.detalle-unidad.ventana');

    Route::get('/marchamos/unidades/{unidad}', [MarchamoController::class, 'detalleUnidad'])
        ->name('marchamos.detalle-unidad');

    Route::get('/marchamos/asignacion-inicial', [MarchamoAsignacionInicialController::class, 'index'])
        ->name('marchamos.asignacion-inicial.index');

    Route::get('/marchamos/asignacion-inicial/ventana', [MarchamoAsignacionInicialController::class, 'indexVentana'])
        ->name('marchamos.asignacion-inicial.index.ventana');

    Route::get('/unidades/{unidad}/marchamos/asignacion-inicial', [MarchamoAsignacionInicialController::class, 'show'])
        ->name('marchamos.asignacion-inicial.show');

    Route::get('/unidades/{unidad}/marchamos/asignacion-inicial/ventana', [MarchamoAsignacionInicialController::class, 'showVentana'])
        ->name('marchamos.asignacion-inicial.show.ventana');

    Route::post('/unidades/{unidad}/marchamos/asignacion-inicial', [MarchamoAsignacionInicialController::class, 'guardarAvance'])
        ->name('marchamos.asignacion-inicial.guardar-avance');

    Route::post('/unidades/{unidad}/marchamos/finalizar-asignacion-inicial', [MarchamoAsignacionInicialController::class, 'finalizar'])
        ->name('marchamos.asignacion-inicial.finalizar');

    Route::get('/marchamos/reemplazos', [MarchamoReemplazoController::class, 'index'])
        ->name('marchamos.reemplazos.index');

    Route::get('/marchamos/reemplazos/ventana', [MarchamoReemplazoController::class, 'indexVentana'])
        ->name('marchamos.reemplazos.index.ventana');

    Route::get('/marchamos/reemplazos/unidades/{unidad}', [MarchamoReemplazoController::class, 'show'])
        ->name('marchamos.reemplazos.show');

    Route::get('/marchamos/reemplazos/unidades/{unidad}/ventana', [MarchamoReemplazoController::class, 'showVentana'])
        ->name('marchamos.reemplazos.show.ventana');

    Route::post('/marchamos/reemplazos/unidades/{unidad}', [MarchamoReemplazoController::class, 'store'])
        ->name('marchamos.reemplazos.store');

    /*
    |--------------------------------------------------------------------------
    | Abastecimientos de unidades
    |--------------------------------------------------------------------------
    |
    | Las rutas fijas deben permanecer antes de las rutas dinámicas para
    | evitar que Laravel interprete palabras como "consulta" o "administrar"
    | como identificadores de abastecimientos.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Registro de abastecimientos
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/abastecimientos',
        [
            AbastecimientoController::class,
            'index',
        ]
    )->name('abastecimientos.index');

    Route::get(
        '/abastecimientos/ventana',
        [
            AbastecimientoController::class,
            'indexVentana',
        ]
    )->name('abastecimientos.index.ventana');

    /*
    |--------------------------------------------------------------------------
    | Consulta histórica
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/abastecimientos/consulta',
        [
            AbastecimientoController::class,
            'consulta',
        ]
    )->name('abastecimientos.consulta');

    Route::get(
        '/abastecimientos/consulta/ventana',
        [
            AbastecimientoController::class,
            'consultaVentana',
        ]
    )->name('abastecimientos.consulta.ventana');

    /*
    |--------------------------------------------------------------------------
    | Administración
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/abastecimientos/administrar',
        [
            AbastecimientoController::class,
            'administrar',
        ]
    )->name('abastecimientos.administrar');

    Route::get(
        '/abastecimientos/administrar/ventana',
        [
            AbastecimientoController::class,
            'administrarVentana',
        ]
    )->name('abastecimientos.administrar.ventana');

    /*
    |--------------------------------------------------------------------------
    | Registro por unidad
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/abastecimientos/unidades/{unidad}/crear',
        [
            AbastecimientoController::class,
            'create',
        ]
    )->name('abastecimientos.create');

    Route::get(
        '/abastecimientos/unidades/{unidad}/crear/ventana',
        [
            AbastecimientoController::class,
            'createVentana',
        ]
    )->name('abastecimientos.create.ventana');

    Route::post(
        '/abastecimientos/unidades/{unidad}',
        [
            AbastecimientoController::class,
            'store',
        ]
    )->name('abastecimientos.store');

    /*
    |--------------------------------------------------------------------------
    | Modificación de abastecimientos
    |--------------------------------------------------------------------------
    |
    | Solo el último abastecimiento registrado de cada unidad puede abrir
    | estas rutas. La validación definitiva se ejecuta nuevamente en el
    | controlador y dentro de la transacción del servicio.
    |
    */

    Route::get(
        '/abastecimientos/{abastecimiento}/editar',
        [
            AbastecimientoController::class,
            'edit',
        ]
    )->name('abastecimientos.edit');

    Route::get(
        '/abastecimientos/{abastecimiento}/editar/ventana',
        [
            AbastecimientoController::class,
            'editVentana',
        ]
    )->name('abastecimientos.edit.ventana');

    Route::put(
        '/abastecimientos/{abastecimiento}',
        [
            AbastecimientoController::class,
            'update',
        ]
    )->name('abastecimientos.update');

    /*
    |--------------------------------------------------------------------------
    | Ficha de abastecimiento
    |--------------------------------------------------------------------------
    |
    | Estas rutas dinámicas deben permanecer al final del bloque.
    |
    */

    Route::get(
        '/abastecimientos/{abastecimiento}/ficha/ventana',
        [
            AbastecimientoController::class,
            'showVentana',
        ]
    )->name('abastecimientos.show.ventana');

    Route::get(
        '/abastecimientos/{abastecimiento}/ficha',
        [
            AbastecimientoController::class,
            'show',
        ]
    )->name('abastecimientos.show');

    /*
    |--------------------------------------------------------------------------
    | Empresas
    |--------------------------------------------------------------------------
    */

    Route::get('/empresas/consulta/ventana', [EmpresaController::class, 'consultaVentana'])
        ->name('empresas.consulta.ventana');

    Route::get('/empresas/create/ventana', [EmpresaController::class, 'createVentana'])
        ->name('empresas.create.ventana');

    Route::get('/empresas/administrar', [EmpresaController::class, 'administrar'])
        ->name('empresas.administrar');

    Route::get('/empresas/administrar/ventana', [EmpresaController::class, 'administrarVentana'])
        ->name('empresas.administrar.ventana');

    Route::patch('/empresas/{empresa}/inactivar', [EmpresaController::class, 'inactivar'])
        ->name('empresas.inactivar');

    Route::patch('/empresas/{empresa}/reactivar', [EmpresaController::class, 'reactivar'])
        ->name('empresas.reactivar');

    Route::get('/empresas/{empresa}/ventana', [EmpresaController::class, 'showVentana'])
        ->name('empresas.show.ventana');

    Route::get('/empresas/{empresa}/editar/ventana', [EmpresaController::class, 'editVentana'])
        ->name('empresas.edit.ventana');

    Route::resource('empresas', EmpresaController::class)->except(['destroy']);

    /*
    |--------------------------------------------------------------------------
    | Capa analítica
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Panel operativo
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/analisis/panel-operativo',
        [
            AnalisisOperativoController::class,
            'panelOperativo',
        ]
    )->name('analisis.panel-operativo');

    /*
    |--------------------------------------------------------------------------
    | Análisis de rendimientos
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/analisis/rendimientos',
        [
            AnalisisRendimientoController::class,
            'index',
        ]
    )->name('analisis.rendimientos.index');

    Route::get(
        '/analisis/rendimientos/ventana',
        [
            AnalisisRendimientoController::class,
            'indexVentana',
        ]
)->name('analisis.rendimientos.index.ventana');
});

require __DIR__.'/auth.php';