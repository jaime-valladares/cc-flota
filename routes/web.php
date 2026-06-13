<?php

use App\Http\Controllers\EmpresaController;
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
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | Usuarios
    |--------------------------------------------------------------------------
    |
    | Se mantiene la misma separación funcional del módulo Empresas:
    | consulta informativa, administración operativa, creación, ficha,
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
    | Se mantiene la misma separación funcional aplicada en Empresas y Usuarios:
    | consulta informativa, administración operativa, creación, ficha,
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

    Route::get('/empresas/administrar', [EmpresaController::class, 'administrar'])->name('empresas.administrar');

    Route::get('/empresas/administrar/ventana', [EmpresaController::class, 'administrarVentana'])
        ->name('empresas.administrar.ventana');

    Route::patch('/empresas/{empresa}/inactivar', [EmpresaController::class, 'inactivar'])
        ->name('empresas.inactivar');

    Route::patch('/empresas/{empresa}/reactivar', [EmpresaController::class, 'reactivar'])
        ->name('empresas.reactivar');

    Route::resource('empresas', EmpresaController::class)->except(['destroy']);
});

require __DIR__.'/auth.php';