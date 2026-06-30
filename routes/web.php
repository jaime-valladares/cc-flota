<?php

use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\LicenciaController;
use App\Http\Controllers\MarchamoAsignacionInicialController;
use App\Http\Controllers\MarchamoReemplazoController;
use App\Http\Controllers\MarchamoController;
use App\Http\Controllers\ProfileController;
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
    |
    | Consulta informativa, administración operativa, creación, ficha,
    | edición e inactivación/reactivación.
    |
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
    |
    | Consulta informativa, administración operativa, creación, ficha,
    | edición e inactivación/reactivación.
    |
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
    | Licencias
    |--------------------------------------------------------------------------
    |
    | La licencia nace desde una unidad existente. La placa de la unidad será
    | la referencia visible principal; la tabla licencias controla vigencia,
    | estado de cobertura Diesel Cop y plantilla de puntos de seguridad.
    |
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
    |
    | Módulo operativo avanzado. Incluye consulta global de marchamos,
    | asignación inicial por unidad y, posteriormente, administración,
    | correcciones, reemplazos e historial.
    |
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

    Route::get('/unidades/{unidad}/marchamos/asignacion-inicial', [MarchamoAsignacionInicialController::class, 'show'])
        ->name('marchamos.asignacion-inicial.show');

    Route::post('/unidades/{unidad}/marchamos/asignacion-inicial', [MarchamoAsignacionInicialController::class, 'guardarAvance'])
        ->name('marchamos.asignacion-inicial.guardar-avance');

    Route::post('/unidades/{unidad}/marchamos/finalizar-asignacion-inicial', [MarchamoAsignacionInicialController::class, 'finalizar'])
        ->name('marchamos.asignacion-inicial.finalizar');

    Route::get('/marchamos/reemplazos', [MarchamoReemplazoController::class, 'index'])
        ->name('marchamos.reemplazos.index');

    Route::get('/marchamos/reemplazos/unidades/{unidad}', [MarchamoReemplazoController::class, 'show'])
        ->name('marchamos.reemplazos.show');

    Route::post('/marchamos/reemplazos/unidades/{unidad}', [MarchamoReemplazoController::class, 'store'])
        ->name('marchamos.reemplazos.store');

    /*
    |--------------------------------------------------------------------------
    | Empresas
    |--------------------------------------------------------------------------
    |
    | Las rutas específicas deben declararse antes del resource, porque de lo
    | contrario Laravel podría interpretar segmentos como "consulta",
    | "administrar" o "ventana" como si fueran {empresa}.
    |
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
});

require __DIR__.'/auth.php';