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
    
    Route::resource('empresas', EmpresaController::class);

    Route::patch('/empresas/{empresa}/inactivar', [EmpresaController::class, 'inactivar'])
        ->name('empresas.inactivar');

    Route::patch('/empresas/{empresa}/reactivar', [EmpresaController::class, 'reactivar'])
        ->name('empresas.reactivar');
});

require __DIR__.'/auth.php';
