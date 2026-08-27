<?php

use App\Http\Controllers\AbastecimientoController;
use App\Http\Controllers\AnalisisConsumoUnidadController;
use App\Http\Controllers\AnalisisOperativoController;
use App\Http\Controllers\AnalisisRendimientoController;
use App\Http\Controllers\AnalisisRutaController;
use App\Http\Controllers\AuditoriaAbastecimientoController;
use App\Http\Controllers\AuditoriaMarchamoController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\GasolineraController;
use App\Http\Controllers\GasolineraExternaController;
use App\Http\Controllers\LicenciaController;
use App\Http\Controllers\MarchamoAsignacionInicialController;
use App\Http\Controllers\MarchamoController;
use App\Http\Controllers\MarchamoReemplazoController;
use App\Http\Controllers\MotoristaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PuntoRutaController;
use App\Http\Controllers\RecargaTanqueController;
use App\Http\Controllers\RutaController;
use App\Http\Controllers\TanqueController;
use App\Http\Controllers\UnidadController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'usuario.activo', 'verified'])->name('dashboard');

Route::middleware(['auth', 'usuario.activo'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Perfil
    |--------------------------------------------------------------------------
    */

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    /*
    |--------------------------------------------------------------------------
    | Usuarios
    |--------------------------------------------------------------------------
    |
    | Las rutas fijas deben declararse antes de /usuarios/{usuario}
    | para evitar que palabras reservadas sean interpretadas como identificadores.
    |
    */

    Route::middleware('permiso:usuarios.consultar')->group(function () {
        Route::get(
            '/usuarios',
            [UsuarioController::class, 'index']
        )->name('usuarios.index');

        Route::get(
            '/usuarios/consulta/ventana',
            [UsuarioController::class, 'consultaVentana']
        )->name('usuarios.consulta.ventana');
    });

    Route::middleware('permiso:usuarios.crear')->group(function () {
        Route::get(
            '/usuarios/nuevo',
            [UsuarioController::class, 'create']
        )->name('usuarios.create');

        Route::get(
            '/usuarios/nuevo/ventana',
            [UsuarioController::class, 'createVentana']
        )->name('usuarios.create.ventana');

        Route::post(
            '/usuarios',
            [UsuarioController::class, 'store']
        )->name('usuarios.store');
    });

    Route::middleware('permiso:usuarios.administrar')->group(function () {
        Route::get(
            '/usuarios/administrar',
            [UsuarioController::class, 'administrar']
        )->name('usuarios.administrar');

        Route::get(
            '/usuarios/administrar/ventana',
            [UsuarioController::class, 'administrarVentana']
        )->name('usuarios.administrar.ventana');
    });

    /*
    |--------------------------------------------------------------------------
    | Usuarios - Rutas dinámicas
    |--------------------------------------------------------------------------
    */

    Route::middleware('permiso:usuarios.administrar')->group(function () {
        Route::get(
            '/usuarios/{usuario}/ventana',
            [UsuarioController::class, 'showVentana']
        )->name('usuarios.show.ventana');

        Route::get(
            '/usuarios/{usuario}',
            [UsuarioController::class, 'show']
        )->name('usuarios.show');
    });

    Route::middleware('permiso:usuarios.editar')->group(function () {
        Route::get(
            '/usuarios/{usuario}/editar/ventana',
            [UsuarioController::class, 'editVentana']
        )->name('usuarios.edit.ventana');

        Route::get(
            '/usuarios/{usuario}/editar',
            [UsuarioController::class, 'edit']
        )->name('usuarios.edit');

        Route::put(
            '/usuarios/{usuario}',
            [UsuarioController::class, 'update']
        )->name('usuarios.update');
    });

    Route::patch(
        '/usuarios/{usuario}/inactivar',
        [UsuarioController::class, 'inactivar']
    )
        ->middleware('permiso:usuarios.inactivar')
        ->name('usuarios.inactivar');

    Route::patch(
        '/usuarios/{usuario}/reactivar',
        [UsuarioController::class, 'reactivar']
    )
        ->middleware('permiso:usuarios.reactivar')
        ->name('usuarios.reactivar');

    /*
    |--------------------------------------------------------------------------
    | Unidades
    |--------------------------------------------------------------------------
    |
    | Las rutas fijas deben declararse antes de /unidades/{unidad}.
    |
    */

    Route::middleware('permiso:unidades.consultar')->group(function () {
        Route::get(
            '/unidades',
            [UnidadController::class, 'index']
        )->name('unidades.index');

        Route::get(
            '/unidades/consulta/ventana',
            [UnidadController::class, 'consultaVentana']
        )->name('unidades.consulta.ventana');
    });

    Route::middleware('permiso:unidades.crear')->group(function () {
        Route::get(
            '/unidades/nueva',
            [UnidadController::class, 'create']
        )->name('unidades.create');

        Route::get(
            '/unidades/nueva/ventana',
            [UnidadController::class, 'createVentana']
        )->name('unidades.create.ventana');

        Route::post(
            '/unidades',
            [UnidadController::class, 'store']
        )->name('unidades.store');
    });

    Route::middleware('permiso:unidades.administrar')->group(function () {
        Route::get(
            '/unidades/administrar',
            [UnidadController::class, 'administrar']
        )->name('unidades.administrar');

        Route::get(
            '/unidades/administrar/ventana',
            [UnidadController::class, 'administrarVentana']
        )->name('unidades.administrar.ventana');
    });

    /*
    |--------------------------------------------------------------------------
    | Unidades - Rutas dinámicas
    |--------------------------------------------------------------------------
    */

    Route::middleware('permiso:unidades.administrar')->group(function () {
        Route::get(
            '/unidades/{unidad}/ficha/ventana',
            [UnidadController::class, 'showVentana']
        )->name('unidades.show.ventana');

        Route::get(
            '/unidades/{unidad}',
            [UnidadController::class, 'show']
        )->name('unidades.show');
    });

    Route::middleware('permiso:unidades.editar')->group(function () {
        Route::get(
            '/unidades/{unidad}/editar/ventana',
            [UnidadController::class, 'editVentana']
        )->name('unidades.edit.ventana');

        Route::get(
            '/unidades/{unidad}/editar',
            [UnidadController::class, 'edit']
        )->name('unidades.edit');

        Route::put(
            '/unidades/{unidad}',
            [UnidadController::class, 'update']
        )->name('unidades.update');
    });

    Route::patch(
        '/unidades/{unidad}/inactivar',
        [UnidadController::class, 'inactivar']
    )
        ->middleware('permiso:unidades.inactivar')
        ->name('unidades.inactivar');

    Route::patch(
        '/unidades/{unidad}/reactivar',
        [UnidadController::class, 'reactivar']
    )
        ->middleware('permiso:unidades.reactivar')
        ->name('unidades.reactivar');

    /*
    |--------------------------------------------------------------------------
    | Gasolineras internas
    |--------------------------------------------------------------------------
    |
    | Todas las rutas fijas se declaran antes de /gasolineras/{gasolinera}.
    |
    */

    Route::middleware('permiso:gasolineras.consultar')->group(function () {
        Route::get(
            '/gasolineras',
            [GasolineraController::class, 'index']
        )->name('gasolineras.index');

        Route::get(
            '/gasolineras/consulta/ventana',
            [GasolineraController::class, 'consultaVentana']
        )->name('gasolineras.consulta.ventana');
    });

    Route::middleware('permiso:gasolineras.administrar')->group(function () {
        Route::get(
            '/gasolineras/administrar',
            [GasolineraController::class, 'administrar']
        )->name('gasolineras.administrar');

        Route::get(
            '/gasolineras/administrar/ventana',
            [GasolineraController::class, 'administrarVentana']
        )->name('gasolineras.administrar.ventana');
    });

    Route::middleware('permiso:gasolineras.crear')->group(function () {
        Route::get(
            '/gasolineras/nueva',
            [GasolineraController::class, 'create']
        )->name('gasolineras.create');

        Route::get(
            '/gasolineras/nueva/ventana',
            [GasolineraController::class, 'createVentana']
        )->name('gasolineras.create.ventana');

        Route::post(
            '/gasolineras',
            [GasolineraController::class, 'store']
        )->name('gasolineras.store');
    });

    /*
    |--------------------------------------------------------------------------
    | Gestión de tanques
    |--------------------------------------------------------------------------
    */

    Route::middleware('permiso:tanques.administrar')->group(function () {
        Route::get(
            '/gasolineras/tanques',
            [TanqueController::class, 'index']
        )->name('gasolineras.tanques.index');

        Route::get(
            '/gasolineras/tanques/ventana',
            [TanqueController::class, 'indexVentana']
        )->name('gasolineras.tanques.index.ventana');

        Route::get(
            '/gasolineras/{gasolinera}/tanques/{tanque}/administrar',
            [TanqueController::class, 'show']
        )->name('gasolineras.tanques.show');

        Route::get(
            '/gasolineras/{gasolinera}/tanques/{tanque}/administrar/ventana',
            [TanqueController::class, 'showVentana']
        )->name('gasolineras.tanques.show.ventana');
    });

    Route::post(
        '/gasolineras/{gasolinera}/tanques',
        [GasolineraController::class, 'storeTanque']
    )
        ->middleware('permiso:tanques.crear')
        ->name('gasolineras.tanques.store');

    Route::put(
        '/gasolineras/{gasolinera}/tanques/{tanque}',
        [TanqueController::class, 'update']
    )
        ->middleware('permiso:tanques.editar')
        ->name('gasolineras.tanques.update');

    Route::patch(
        '/gasolineras/{gasolinera}/tanques/{tanque}/inactivar',
        [TanqueController::class, 'inactivar']
    )
        ->middleware('permiso:tanques.inactivar')
        ->name('gasolineras.tanques.inactivar');

    Route::patch(
        '/gasolineras/{gasolinera}/tanques/{tanque}/reactivar',
        [TanqueController::class, 'reactivar']
    )
        ->middleware('permiso:tanques.reactivar')
        ->name('gasolineras.tanques.reactivar');

    /*
    |--------------------------------------------------------------------------
    | Recarga de tanques
    |--------------------------------------------------------------------------
    */

    Route::middleware('permiso:recargas_tanques.registrar')->group(function () {
        Route::get(
            '/gasolineras/tanques/recargas',
            [RecargaTanqueController::class, 'index']
        )->name('gasolineras.tanques.recargas.index');

        Route::get(
            '/gasolineras/tanques/recargas/ventana',
            [RecargaTanqueController::class, 'indexVentana']
        )->name('gasolineras.tanques.recargas.index.ventana');

        Route::get(
            '/gasolineras/{gasolinera}/tanques/recargas/crear',
            [RecargaTanqueController::class, 'create']
        )->name('gasolineras.tanques.recargas.create');

        Route::get(
            '/gasolineras/{gasolinera}/tanques/recargas/crear/ventana',
            [RecargaTanqueController::class, 'createVentana']
        )->name('gasolineras.tanques.recargas.create.ventana');

        Route::post(
            '/gasolineras/{gasolinera}/tanques/recargas',
            [RecargaTanqueController::class, 'store']
        )->name('gasolineras.tanques.recargas.store');

        /*
         * Rutas heredadas para enlaces individuales.
         */
        Route::get(
            '/gasolineras/{gasolinera}/tanques/{tanque}/recargar',
            [RecargaTanqueController::class, 'show']
        )->name('gasolineras.tanques.recargas.show');

        Route::get(
            '/gasolineras/{gasolinera}/tanques/{tanque}/recargar/ventana',
            [RecargaTanqueController::class, 'showVentana']
        )->name('gasolineras.tanques.recargas.show.ventana');
    });

    Route::patch(
        '/gasolineras/{gasolinera}/tanques/recargas/{recarga}/anular',
        [RecargaTanqueController::class, 'anular']
    )
        ->middleware('permiso:recargas_tanques.anular')
        ->name('gasolineras.tanques.recargas.anular');

    /*
    |--------------------------------------------------------------------------
    | Gasolineras internas - Rutas dinámicas
    |--------------------------------------------------------------------------
    */

    Route::middleware('permiso:gasolineras.administrar')->group(function () {
        Route::get(
            '/gasolineras/{gasolinera}/ficha/ventana',
            [GasolineraController::class, 'showVentana']
        )->name('gasolineras.show.ventana');

        Route::get(
            '/gasolineras/{gasolinera}',
            [GasolineraController::class, 'show']
        )->name('gasolineras.show');
    });

    Route::middleware('permiso:gasolineras.editar')->group(function () {
        Route::get(
            '/gasolineras/{gasolinera}/editar/ventana',
            [GasolineraController::class, 'editVentana']
        )->name('gasolineras.edit.ventana');

        Route::get(
            '/gasolineras/{gasolinera}/editar',
            [GasolineraController::class, 'edit']
        )->name('gasolineras.edit');

        Route::put(
            '/gasolineras/{gasolinera}',
            [GasolineraController::class, 'update']
        )->name('gasolineras.update');
    });

    Route::patch(
        '/gasolineras/{gasolinera}/inactivar',
        [GasolineraController::class, 'inactivar']
    )
        ->middleware('permiso:gasolineras.inactivar')
        ->name('gasolineras.inactivar');

    Route::patch(
        '/gasolineras/{gasolinera}/reactivar',
        [GasolineraController::class, 'reactivar']
    )
        ->middleware('permiso:gasolineras.reactivar')
        ->name('gasolineras.reactivar');

    /*
    |--------------------------------------------------------------------------
    | Gasolineras externas
    |--------------------------------------------------------------------------
    |
    | Las rutas estáticas deben declararse antes de las rutas dinámicas
    | /gasolineras-externas/{gasolineraExterna}.
    |
    */

    Route::middleware('permiso:gasolineras_externas.consultar')->group(function () {
        Route::get(
            '/gasolineras-externas',
            [GasolineraExternaController::class, 'index']
        )->name('gasolineras-externas.index');

        Route::get(
            '/gasolineras-externas/consulta/ventana',
            [GasolineraExternaController::class, 'consultaVentana']
        )->name('gasolineras-externas.consulta.ventana');
    });

    Route::middleware('permiso:gasolineras_externas.administrar')->group(function () {
        Route::get(
            '/gasolineras-externas/administrar',
            [GasolineraExternaController::class, 'administrar']
        )->name('gasolineras-externas.administrar');

        Route::get(
            '/gasolineras-externas/administrar/ventana',
            [GasolineraExternaController::class, 'administrarVentana']
        )->name('gasolineras-externas.administrar.ventana');
    });

    Route::middleware('permiso:gasolineras_externas.crear')->group(function () {
        Route::get(
            '/gasolineras-externas/nueva',
            [GasolineraExternaController::class, 'create']
        )->name('gasolineras-externas.create');

        Route::get(
            '/gasolineras-externas/nueva/ventana',
            [GasolineraExternaController::class, 'createVentana']
        )->name('gasolineras-externas.create.ventana');

        Route::post(
            '/gasolineras-externas',
            [GasolineraExternaController::class, 'store']
        )->name('gasolineras-externas.store');
    });

    /*
    |--------------------------------------------------------------------------
    | Gasolineras externas - Rutas dinámicas
    |--------------------------------------------------------------------------
    */

    Route::middleware('permiso:gasolineras_externas.administrar')->group(function () {
        Route::get(
            '/gasolineras-externas/{gasolineraExterna}/ficha/ventana',
            [GasolineraExternaController::class, 'showVentana']
        )->name('gasolineras-externas.show.ventana');

        Route::get(
            '/gasolineras-externas/{gasolineraExterna}',
            [GasolineraExternaController::class, 'show']
        )->name('gasolineras-externas.show');
    });

    Route::middleware('permiso:gasolineras_externas.editar')->group(function () {
        Route::get(
            '/gasolineras-externas/{gasolineraExterna}/editar/ventana',
            [GasolineraExternaController::class, 'editVentana']
        )->name('gasolineras-externas.edit.ventana');

        Route::get(
            '/gasolineras-externas/{gasolineraExterna}/editar',
            [GasolineraExternaController::class, 'edit']
        )->name('gasolineras-externas.edit');

        Route::put(
            '/gasolineras-externas/{gasolineraExterna}',
            [GasolineraExternaController::class, 'update']
        )->name('gasolineras-externas.update');
    });

    Route::patch(
        '/gasolineras-externas/{gasolineraExterna}/inactivar',
        [GasolineraExternaController::class, 'inactivar']
    )
        ->middleware('permiso:gasolineras_externas.inactivar')
        ->name('gasolineras-externas.inactivar');

    Route::patch(
        '/gasolineras-externas/{gasolineraExterna}/reactivar',
        [GasolineraExternaController::class, 'reactivar']
    )
        ->middleware('permiso:gasolineras_externas.reactivar')
        ->name('gasolineras-externas.reactivar');

    /*
    |--------------------------------------------------------------------------
    | Puntos de ruta
    |--------------------------------------------------------------------------
    |
    | Las rutas estáticas se declaran antes de las rutas dinámicas
    | /puntos-ruta/{puntoRuta}.
    |
    */

    Route::middleware('permiso:puntos_ruta.consultar')->group(function () {
        Route::get(
            '/puntos-ruta',
            [PuntoRutaController::class, 'index']
        )->name('puntos-ruta.index');

        Route::get(
            '/puntos-ruta/consulta/ventana',
            [PuntoRutaController::class, 'consultaVentana']
        )->name('puntos-ruta.consulta.ventana');
    });

    Route::middleware('permiso:puntos_ruta.administrar')->group(function () {
        Route::get(
            '/puntos-ruta/administrar',
            [PuntoRutaController::class, 'administrar']
        )->name('puntos-ruta.administrar');

        Route::get(
            '/puntos-ruta/administrar/ventana',
            [PuntoRutaController::class, 'administrarVentana']
        )->name('puntos-ruta.administrar.ventana');
    });

    Route::middleware('permiso:puntos_ruta.crear')->group(function () {
        Route::get(
            '/puntos-ruta/nuevo',
            [PuntoRutaController::class, 'create']
        )->name('puntos-ruta.create');

        Route::get(
            '/puntos-ruta/nuevo/ventana',
            [PuntoRutaController::class, 'createVentana']
        )->name('puntos-ruta.create.ventana');

        Route::post(
            '/puntos-ruta',
            [PuntoRutaController::class, 'store']
        )->name('puntos-ruta.store');
    });

    /*
    |--------------------------------------------------------------------------
    | Puntos de ruta - Rutas dinámicas
    |--------------------------------------------------------------------------
    */

    Route::middleware('permiso:puntos_ruta.administrar')->group(function () {
        Route::get(
            '/puntos-ruta/{puntoRuta}/ficha/ventana',
            [PuntoRutaController::class, 'showVentana']
        )->name('puntos-ruta.show.ventana');

        Route::get(
            '/puntos-ruta/{puntoRuta}',
            [PuntoRutaController::class, 'show']
        )->name('puntos-ruta.show');
    });

    Route::middleware('permiso:puntos_ruta.editar')->group(function () {
        Route::get(
            '/puntos-ruta/{puntoRuta}/editar/ventana',
            [PuntoRutaController::class, 'editVentana']
        )->name('puntos-ruta.edit.ventana');

        Route::get(
            '/puntos-ruta/{puntoRuta}/editar',
            [PuntoRutaController::class, 'edit']
        )->name('puntos-ruta.edit');

        Route::put(
            '/puntos-ruta/{puntoRuta}',
            [PuntoRutaController::class, 'update']
        )->name('puntos-ruta.update');
    });

    Route::patch(
        '/puntos-ruta/{puntoRuta}/inactivar',
        [PuntoRutaController::class, 'inactivar']
    )
        ->middleware('permiso:puntos_ruta.inactivar')
        ->name('puntos-ruta.inactivar');

    Route::patch(
        '/puntos-ruta/{puntoRuta}/reactivar',
        [PuntoRutaController::class, 'reactivar']
    )
        ->middleware('permiso:puntos_ruta.reactivar')
        ->name('puntos-ruta.reactivar');

    /*
    |--------------------------------------------------------------------------
    | Rutas
    |--------------------------------------------------------------------------
    |
    | Todas las rutas estáticas se declaran antes de /rutas/{ruta}.
    |
    */

    Route::middleware('permiso:rutas.consultar')->group(function () {
        Route::get(
            '/rutas',
            [RutaController::class, 'index']
        )->name('rutas.index');

        Route::get(
            '/rutas/consulta/ventana',
            [RutaController::class, 'consultaVentana']
        )->name('rutas.consulta.ventana');
    });

    Route::middleware('permiso:rutas.administrar')->group(function () {
        Route::get(
            '/rutas/administrar',
            [RutaController::class, 'administrar']
        )->name('rutas.administrar');

        Route::get(
            '/rutas/administrar/ventana',
            [RutaController::class, 'administrarVentana']
        )->name('rutas.administrar.ventana');
    });

    Route::middleware('permiso:rutas.crear')->group(function () {
        Route::get(
            '/rutas/nueva',
            [RutaController::class, 'create']
        )->name('rutas.create');

        Route::get(
            '/rutas/nueva/ventana',
            [RutaController::class, 'createVentana']
        )->name('rutas.create.ventana');

        Route::post(
            '/rutas',
            [RutaController::class, 'store']
        )->name('rutas.store');
    });

    /*
    |--------------------------------------------------------------------------
    | Rutas - Rutas dinámicas
    |--------------------------------------------------------------------------
    */

    Route::middleware('permiso:rutas.administrar')->group(function () {
        Route::get(
            '/rutas/{ruta}/ficha/ventana',
            [RutaController::class, 'showVentana']
        )->name('rutas.show.ventana');

        Route::get(
            '/rutas/{ruta}',
            [RutaController::class, 'show']
        )->name('rutas.show');
    });

    Route::middleware('permiso:rutas.editar')->group(function () {
        Route::get(
            '/rutas/{ruta}/editar/ventana',
            [RutaController::class, 'editVentana']
        )->name('rutas.edit.ventana');

        Route::get(
            '/rutas/{ruta}/editar',
            [RutaController::class, 'edit']
        )->name('rutas.edit');

        Route::put(
            '/rutas/{ruta}',
            [RutaController::class, 'update']
        )->name('rutas.update');
    });

    Route::patch(
        '/rutas/{ruta}/inactivar',
        [RutaController::class, 'inactivar']
    )
        ->middleware('permiso:rutas.inactivar')
        ->name('rutas.inactivar');

    Route::patch(
        '/rutas/{ruta}/reactivar',
        [RutaController::class, 'reactivar']
    )
        ->middleware('permiso:rutas.reactivar')
        ->name('rutas.reactivar');

    /*
    |--------------------------------------------------------------------------
    | Motoristas
    |--------------------------------------------------------------------------
    |
    | Todas las rutas estáticas se declaran antes de /motoristas/{motorista}.
    |
    */

    Route::middleware('permiso:motoristas.consultar')->group(function () {
        Route::get(
            '/motoristas',
            [MotoristaController::class, 'index']
        )->name('motoristas.index');

        Route::get(
            '/motoristas/consulta/ventana',
            [MotoristaController::class, 'consultaVentana']
        )->name('motoristas.consulta.ventana');
    });

    Route::middleware('permiso:motoristas.administrar')->group(function () {
        Route::get(
            '/motoristas/administrar',
            [MotoristaController::class, 'administrar']
        )->name('motoristas.administrar');

        Route::get(
            '/motoristas/administrar/ventana',
            [MotoristaController::class, 'administrarVentana']
        )->name('motoristas.administrar.ventana');
    });

    Route::middleware('permiso:motoristas.crear')->group(function () {
        Route::get(
            '/motoristas/nuevo',
            [MotoristaController::class, 'create']
        )->name('motoristas.create');

        Route::get(
            '/motoristas/nuevo/ventana',
            [MotoristaController::class, 'createVentana']
        )->name('motoristas.create.ventana');

        Route::post(
            '/motoristas',
            [MotoristaController::class, 'store']
        )->name('motoristas.store');
    });

    /*
    |--------------------------------------------------------------------------
    | Motoristas - Rutas dinámicas
    |--------------------------------------------------------------------------
    */

    Route::middleware('permiso:motoristas.administrar')->group(function () {
        Route::get(
            '/motoristas/{motorista}/ficha/ventana',
            [MotoristaController::class, 'showVentana']
        )->name('motoristas.show.ventana');

        Route::get(
            '/motoristas/{motorista}',
            [MotoristaController::class, 'show']
        )->name('motoristas.show');
    });

    Route::middleware('permiso:motoristas.editar')->group(function () {
        Route::get(
            '/motoristas/{motorista}/editar/ventana',
            [MotoristaController::class, 'editVentana']
        )->name('motoristas.edit.ventana');

        Route::get(
            '/motoristas/{motorista}/editar',
            [MotoristaController::class, 'edit']
        )->name('motoristas.edit');

        Route::put(
            '/motoristas/{motorista}',
            [MotoristaController::class, 'update']
        )->name('motoristas.update');
    });

    Route::patch(
        '/motoristas/{motorista}/inactivar',
        [MotoristaController::class, 'inactivar']
    )
        ->middleware('permiso:motoristas.inactivar')
        ->name('motoristas.inactivar');

    Route::patch(
        '/motoristas/{motorista}/reactivar',
        [MotoristaController::class, 'reactivar']
    )
        ->middleware('permiso:motoristas.reactivar')
        ->name('motoristas.reactivar');

    /*
    |--------------------------------------------------------------------------
    | Licencias
    |--------------------------------------------------------------------------
    |
    | Las rutas fijas se declaran antes de las rutas dinámicas.
    |
    */

    Route::middleware('permiso:licencias.consultar')->group(function () {
        Route::get('/licencias', [LicenciaController::class, 'index'])
            ->name('licencias.index');

        Route::get('/licencias/consulta/ventana', [LicenciaController::class, 'consultaVentana'])
            ->name('licencias.consulta.ventana');
    });

    Route::middleware('permiso:licencias.administrar')->group(function () {
        Route::get('/licencias/administrar', [LicenciaController::class, 'administrar'])
            ->name('licencias.administrar');

        Route::get('/licencias/administrar/ventana', [LicenciaController::class, 'administrarVentana'])
            ->name('licencias.administrar.ventana');
    });

    Route::middleware('permiso:licencias.crear')->group(function () {
        Route::get('/licencias/nueva', [LicenciaController::class, 'create'])
            ->name('licencias.create');

        Route::get('/licencias/nueva/ventana', [LicenciaController::class, 'createVentana'])
            ->name('licencias.create.ventana');

        Route::post('/licencias', [LicenciaController::class, 'store'])
            ->name('licencias.store');
    });

    Route::middleware('permiso:licencias.administrar')->group(function () {
        Route::get('/licencias/{licencia}/ficha/ventana', [LicenciaController::class, 'showVentana'])
            ->name('licencias.show.ventana');

        Route::get('/licencias/{licencia}', [LicenciaController::class, 'show'])
            ->name('licencias.show');
    });

    Route::middleware('permiso:licencias.editar')->group(function () {
        Route::get('/licencias/{licencia}/editar/ventana', [LicenciaController::class, 'editVentana'])
            ->name('licencias.edit.ventana');

        Route::get('/licencias/{licencia}/editar', [LicenciaController::class, 'edit'])
            ->name('licencias.edit');

        Route::put('/licencias/{licencia}', [LicenciaController::class, 'update'])
            ->name('licencias.update');
    });

    Route::patch('/licencias/{licencia}/inactivar', [LicenciaController::class, 'inactivar'])
        ->middleware('permiso:licencias.inactivar')
        ->name('licencias.inactivar');

    Route::patch('/licencias/{licencia}/reactivar', [LicenciaController::class, 'reactivar'])
        ->middleware('permiso:licencias.reactivar')
        ->name('licencias.reactivar');

    Route::patch('/licencias/{licencia}/renovar', [LicenciaController::class, 'renovar'])
        ->middleware('permiso:licencias.reactivar')
        ->name('licencias.renovar');

    /*
    |--------------------------------------------------------------------------
    | Marchamos
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Consulta histórica
    |--------------------------------------------------------------------------
    */

    Route::middleware('permiso:marchamos.consultar')->group(function () {
        Route::get(
            '/marchamos',
            [MarchamoController::class, 'index']
        )->name('marchamos.index');

        Route::get(
            '/marchamos/consulta/ventana',
            [MarchamoController::class, 'consultaVentana']
        )->name('marchamos.consulta.ventana');

        Route::get(
            '/marchamos/consulta/ventana/unidades/{unidad}',
            [MarchamoController::class, 'detalleUnidadVentana']
        )->name('marchamos.detalle-unidad.ventana');

        Route::get(
            '/marchamos/unidades/{unidad}',
            [MarchamoController::class, 'detalleUnidad']
        )->name('marchamos.detalle-unidad');
    });

    /*
    |--------------------------------------------------------------------------
    | Administración y reemplazos
    |--------------------------------------------------------------------------
    */

    Route::middleware('permiso:marchamos.administrar')->group(function () {
        Route::get(
            '/marchamos/reemplazos',
            [MarchamoReemplazoController::class, 'index']
        )->name('marchamos.reemplazos.index');

        Route::get(
            '/marchamos/reemplazos/ventana',
            [MarchamoReemplazoController::class, 'indexVentana']
        )->name('marchamos.reemplazos.index.ventana');

        Route::get(
            '/marchamos/reemplazos/unidades/{unidad}',
            [MarchamoReemplazoController::class, 'show']
        )->name('marchamos.reemplazos.show');

        Route::get(
            '/marchamos/reemplazos/unidades/{unidad}/ventana',
            [MarchamoReemplazoController::class, 'showVentana']
        )->name('marchamos.reemplazos.show.ventana');
    });

    Route::post(
        '/marchamos/reemplazos/unidades/{unidad}',
        [MarchamoReemplazoController::class, 'store']
    )
        ->middleware('permiso:marchamos.reemplazar')
        ->name('marchamos.reemplazos.store');

    /*
    |--------------------------------------------------------------------------
    | Asignación inicial
    |--------------------------------------------------------------------------
    */

    Route::middleware('permiso:marchamos.asignar_inicial')->group(function () {
        Route::get(
            '/marchamos/asignacion-inicial',
            [MarchamoAsignacionInicialController::class, 'index']
        )->name('marchamos.asignacion-inicial.index');

        Route::get(
            '/marchamos/asignacion-inicial/ventana',
            [MarchamoAsignacionInicialController::class, 'indexVentana']
        )->name('marchamos.asignacion-inicial.index.ventana');

        Route::get(
            '/unidades/{unidad}/marchamos/asignacion-inicial',
            [MarchamoAsignacionInicialController::class, 'show']
        )->name('marchamos.asignacion-inicial.show');

        Route::get(
            '/unidades/{unidad}/marchamos/asignacion-inicial/ventana',
            [MarchamoAsignacionInicialController::class, 'showVentana']
        )->name('marchamos.asignacion-inicial.show.ventana');

        Route::post(
            '/unidades/{unidad}/marchamos/asignacion-inicial',
            [MarchamoAsignacionInicialController::class, 'guardarAvance']
        )->name('marchamos.asignacion-inicial.guardar-avance');

        Route::post(
            '/unidades/{unidad}/marchamos/asignacion-inicial/extras',
            [MarchamoAsignacionInicialController::class, 'agregarExtra']
        )->name('marchamos.asignacion-inicial.extras.store');

        Route::patch(
            '/unidades/{unidad}/marchamos/asignacion-inicial/extras/{punto}',
            [MarchamoAsignacionInicialController::class, 'renombrarExtra']
        )->name('marchamos.asignacion-inicial.extras.update');

        Route::delete(
            '/unidades/{unidad}/marchamos/asignacion-inicial/extras/{punto}',
            [MarchamoAsignacionInicialController::class, 'eliminarExtra']
        )->name('marchamos.asignacion-inicial.extras.destroy');

        Route::post(
            '/unidades/{unidad}/marchamos/finalizar-asignacion-inicial',
            [MarchamoAsignacionInicialController::class, 'finalizar']
        )->name('marchamos.asignacion-inicial.finalizar');
    });

    /*
    |--------------------------------------------------------------------------
    | Abastecimientos de unidades
    |--------------------------------------------------------------------------
    |
    | Todas las rutas estáticas permanecen antes de las rutas dinámicas.
    |
    */

    Route::middleware('permiso:abastecimientos.registrar')->group(function () {
        Route::get('/abastecimientos', [AbastecimientoController::class, 'index'])
            ->name('abastecimientos.index');
        Route::get('/abastecimientos/ventana', [AbastecimientoController::class, 'indexVentana'])
            ->name('abastecimientos.index.ventana');
        Route::get('/abastecimientos/unidades/{unidad}/crear', [AbastecimientoController::class, 'create'])
            ->name('abastecimientos.create');
        Route::get('/abastecimientos/unidades/{unidad}/crear/ventana', [AbastecimientoController::class, 'createVentana'])
            ->name('abastecimientos.create.ventana');
        Route::post('/abastecimientos/unidades/{unidad}', [AbastecimientoController::class, 'store'])
            ->name('abastecimientos.store');
    });

    Route::middleware('permiso:abastecimientos.consultar')->group(function () {
        Route::get('/abastecimientos/consulta', [AbastecimientoController::class, 'consulta'])
            ->name('abastecimientos.consulta');
        Route::get('/abastecimientos/consulta/ventana', [AbastecimientoController::class, 'consultaVentana'])
            ->name('abastecimientos.consulta.ventana');
        Route::get('/abastecimientos/ciclos', [AbastecimientoController::class, 'ciclos'])
            ->name('abastecimientos.ciclos.index');
        Route::get('/abastecimientos/ciclos/ventana', [AbastecimientoController::class, 'ciclosVentana'])
            ->name('abastecimientos.ciclos.ventana');
        Route::get('/abastecimientos/ciclos/{abastecimiento}/ficha', [AbastecimientoController::class, 'showCiclo'])
            ->name('abastecimientos.ciclos.show');
        Route::get('/abastecimientos/ciclos/{abastecimiento}/ficha/ventana', [AbastecimientoController::class, 'showCicloVentana'])
            ->name('abastecimientos.ciclos.show.ventana');
        Route::get(
            '/abastecimientos/administrar',
            fn () => redirect()->route('abastecimientos.ciclos.index')
        )
            ->name('abastecimientos.administrar');
        Route::get(
            '/abastecimientos/administrar/ventana',
            fn () => redirect()->route('abastecimientos.ciclos.ventana')
        )
            ->name('abastecimientos.administrar.ventana');
    });

    /* Rutas dinámicas: deben permanecer después de todas las rutas fijas. */
    Route::middleware('permiso:abastecimientos.consultar')->group(function () {
        Route::get('/abastecimientos/{abastecimiento}/ficha/ventana', [AbastecimientoController::class, 'showVentana'])
            ->name('abastecimientos.show.ventana');
        Route::get('/abastecimientos/{abastecimiento}/ficha', [AbastecimientoController::class, 'show'])
            ->name('abastecimientos.show');
    });

    /*
    |--------------------------------------------------------------------------
    | Empresas
    |--------------------------------------------------------------------------
    |
    | Las rutas fijas deben declararse antes de /empresas/{empresa}
    | para evitar que palabras reservadas sean interpretadas como identificadores.
    |
    */

    Route::middleware('permiso:empresas.consultar')->group(function () {
        Route::get(
            '/empresas',
            [EmpresaController::class, 'index']
        )->name('empresas.index');

        Route::get(
            '/empresas/consulta/ventana',
            [EmpresaController::class, 'consultaVentana']
        )->name('empresas.consulta.ventana');
    });

    Route::middleware('permiso:empresas.crear')->group(function () {
        Route::get(
            '/empresas/create',
            [EmpresaController::class, 'create']
        )->name('empresas.create');

        Route::get(
            '/empresas/create/ventana',
            [EmpresaController::class, 'createVentana']
        )->name('empresas.create.ventana');

        Route::post(
            '/empresas',
            [EmpresaController::class, 'store']
        )->name('empresas.store');
    });

    Route::middleware('permiso:empresas.administrar')->group(function () {
        Route::get(
            '/empresas/administrar',
            [EmpresaController::class, 'administrar']
        )->name('empresas.administrar');

        Route::get(
            '/empresas/administrar/ventana',
            [EmpresaController::class, 'administrarVentana']
        )->name('empresas.administrar.ventana');
    });

    /*
    |--------------------------------------------------------------------------
    | Empresas - Rutas dinámicas
    |--------------------------------------------------------------------------
    */

    Route::middleware('permiso:empresas.administrar')->group(function () {
        Route::get(
            '/empresas/{empresa}/ventana',
            [EmpresaController::class, 'showVentana']
        )->name('empresas.show.ventana');

        Route::get(
            '/empresas/{empresa}',
            [EmpresaController::class, 'show']
        )->name('empresas.show');
    });

    Route::middleware('permiso:empresas.editar')->group(function () {
        Route::get(
            '/empresas/{empresa}/editar/ventana',
            [EmpresaController::class, 'editVentana']
        )->name('empresas.edit.ventana');

        Route::get(
            '/empresas/{empresa}/editar',
            [EmpresaController::class, 'edit']
        )->name('empresas.edit');

        Route::put(
            '/empresas/{empresa}',
            [EmpresaController::class, 'update']
        )->name('empresas.update');
    });

    Route::patch(
        '/empresas/{empresa}/inactivar',
        [EmpresaController::class, 'inactivar']
    )
        ->middleware('permiso:empresas.inactivar')
        ->name('empresas.inactivar');

    Route::patch(
        '/empresas/{empresa}/reactivar',
        [EmpresaController::class, 'reactivar']
    )
        ->middleware('permiso:empresas.reactivar')
        ->name('empresas.reactivar');

    /*
    |--------------------------------------------------------------------------
    | Capa analítica
    |--------------------------------------------------------------------------
    */

    Route::middleware('permiso:auditoria.consultar')->group(function () {
        /*
        |--------------------------------------------------------------------------
        | Panel operativo
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/analisis/panel-operativo',
            [AnalisisOperativoController::class, 'panelOperativo']
        )->name('analisis.panel-operativo');

        Route::get(
            '/analisis/panel-operativo/ventana',
            [AnalisisOperativoController::class, 'panelOperativo']
        )->name('analisis.panel-operativo.ventana');

        /*
        |--------------------------------------------------------------------------
        | Auditoría de abastecimientos
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/auditoria/abastecimientos',
            [AuditoriaAbastecimientoController::class, 'index']
        )->name('auditoria.abastecimientos.index');

        Route::get(
            '/auditoria/abastecimientos/ventana',
            [AuditoriaAbastecimientoController::class, 'indexVentana']
        )->name('auditoria.abastecimientos.index.ventana');

        /*
        |--------------------------------------------------------------------------
        | Auditoría de reemplazo de marchamos
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/auditoria/marchamos',
            [AuditoriaMarchamoController::class, 'index']
        )->name('auditoria.marchamos.index');

        Route::get(
            '/auditoria/marchamos/ventana',
            [AuditoriaMarchamoController::class, 'indexVentana']
        )->name('auditoria.marchamos.index.ventana');
    });

    Route::middleware('permiso:analisis.consultar')->group(function () {
        /*
        |--------------------------------------------------------------------------
        | Análisis de rendimientos
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/analisis/rendimientos',
            [AnalisisRendimientoController::class, 'index']
        )->name('analisis.rendimientos.index');

        Route::get(
            '/analisis/rendimientos/ventana',
            [AnalisisRendimientoController::class, 'indexVentana']
        )->name('analisis.rendimientos.index.ventana');

        /*
        |--------------------------------------------------------------------------
        | Análisis de consumo por unidad
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/analisis/consumo-unidades',
            [AnalisisConsumoUnidadController::class, 'index']
        )->name('analisis.consumo-unidades.index');

        Route::get(
            '/analisis/consumo-unidades/ventana',
            [AnalisisConsumoUnidadController::class, 'indexVentana']
        )->name('analisis.consumo-unidades.index.ventana');

        /*
        |--------------------------------------------------------------------------
        | Análisis de rutas
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/analisis/rutas',
            [AnalisisRutaController::class, 'index']
        )->name('analisis.rutas.index');

        Route::get(
            '/analisis/rutas/ventana',
            [AnalisisRutaController::class, 'indexVentana']
        )->name('analisis.rutas.index.ventana');
    });

});

require __DIR__.'/auth.php';
