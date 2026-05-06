<?php

use App\Http\Controllers\Api\TabletController;
use Illuminate\Support\Facades\Route;

// Tres puertas que la tablet usa para hablar con el sistema.
// Sin sesión ni cookies: la tablet se identifica por el código del pintor en cada llamada.

Route::get('usuarios/{codigo}',  [TabletController::class, 'mostrarUsuario']);
Route::get('lotes/{codigo}',     [TabletController::class, 'mostrarLote']);
Route::post('movimientos',       [TabletController::class, 'registrarMovimiento']);
