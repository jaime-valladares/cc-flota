<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EmpresaController;
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

    Route::resource('empresas', EmpresaController::class)->except(['destroy']);
});

require __DIR__.'/auth.php';