<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LoteController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Login (público)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Raíz: si está autenticado va al panel; si no, a login
Route::get('/', function () {
    return redirect()->route(Auth::check() ? 'lotes.index' : 'login');
});

// Zona autenticada (panel del encargado)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/lotes',           [LoteController::class, 'index'])->name('lotes.index');
    Route::get('/lotes/nuevo',     [LoteController::class, 'create'])->name('lotes.create');
    Route::post('/lotes',          [LoteController::class, 'store'])->name('lotes.store');
    Route::get('/lotes/{lote}',    [LoteController::class, 'show'])->name('lotes.show');
});
