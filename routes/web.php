<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\ConsultaController;
use App\Http\Controllers\LoteController;
use App\Http\Controllers\MovimientoController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\RalCatalogoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\TexturaController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Login (público)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Buscador para celulares: enlace privado (token) + gafete. Solo consulta.
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/c/{token}',                    [ConsultaController::class, 'movil'])->name('consulta.movil');
    Route::post('/c/{token}/identificar',       [ConsultaController::class, 'movilIdentificar'])->middleware('throttle:10,1')->name('consulta.identificar');
    Route::post('/c/{token}/salir',             [ConsultaController::class, 'movilSalir'])->name('consulta.salir');
    Route::get('/c/{token}/datos',              [ConsultaController::class, 'movilDatos'])->name('consulta.datos');
    Route::get('/c/{token}/lote/{codigo}/movimientos', [ConsultaController::class, 'movilMovimientos'])->name('consulta.movimientos');
});

// Raíz: si está autenticado va al panel; si no, a login
Route::get('/', function () {
    return redirect()->route(Auth::check() ? 'lotes.index' : 'login');
});

// Zona autenticada (panel del encargado)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/lotes',                  [LoteController::class, 'index'])->name('lotes.index');
    Route::get('/lotes/nuevo',            [LoteController::class, 'create'])->name('lotes.create');
    Route::post('/lotes',                 [LoteController::class, 'store'])->name('lotes.store');
    Route::get('/lotes/{lote}',           [LoteController::class, 'show'])->name('lotes.show');
    Route::get('/lotes/{lote}/etiquetas.pdf', [LoteController::class, 'etiquetasPdf'])->name('lotes.etiquetas');
    Route::get('/lotes/{lote}/editar',    [LoteController::class, 'edit'])->name('lotes.edit');
    Route::patch('/lotes/{lote}',         [LoteController::class, 'update'])->name('lotes.update');
    Route::delete('/lotes/{lote}',        [LoteController::class, 'destroy'])->name('lotes.destroy');
    Route::post('/lotes/{lote}/cerrar-caja', [LoteController::class, 'cerrarCaja'])->name('lotes.cerrar-caja');

    Route::get('/movimientos',                            [MovimientoController::class, 'index'])->name('movimientos.index');
    Route::get('/movimientos/{movimiento}/corregir',      [MovimientoController::class, 'corregirForm'])->name('movimientos.corregir');
    Route::post('/movimientos/{movimiento}/corregir',     [MovimientoController::class, 'corregirStore'])->name('movimientos.corregir.store');
    Route::delete('/movimientos/{movimiento}',            [MovimientoController::class, 'destroy'])->name('movimientos.destroy');

    Route::get('/productos',                 [ProductoController::class, 'index'])->name('productos.index');
    Route::get('/productos/nuevo',           [ProductoController::class, 'create'])->name('productos.create');
    Route::post('/productos',                [ProductoController::class, 'store'])->name('productos.store');
    Route::get('/productos/{producto}/editar', [ProductoController::class, 'edit'])->name('productos.edit');
    Route::patch('/productos/{producto}',    [ProductoController::class, 'update'])->name('productos.update');
    Route::delete('/productos/{producto}',   [ProductoController::class, 'destroy'])->name('productos.destroy');

    // La página vieja de texturas se reemplaza por Configuración
    Route::get('/texturas', fn () => redirect()->route('configuracion.index', ['s' => 'textura_tipo']))->name('texturas.index');
    Route::post('/texturas',                 [TexturaController::class, 'store'])->name('texturas.store');
    Route::patch('/texturas/{textura}',      [TexturaController::class, 'update'])->name('texturas.update');
    Route::delete('/texturas/{textura}',     [TexturaController::class, 'destroy'])->name('texturas.destroy');

    // Configuración (solo admin; el controlador lo verifica)
    Route::get('/configuracion',                            [ConfiguracionController::class, 'index'])->name('configuracion.index');
    Route::post('/configuracion/opciones/{grupo}',          [ConfiguracionController::class, 'opcionStore'])->name('configuracion.opciones.store');
    Route::patch('/configuracion/opciones/{opcion}',        [ConfiguracionController::class, 'opcionUpdate'])->name('configuracion.opciones.update');
    Route::delete('/configuracion/opciones/{opcion}',       [ConfiguracionController::class, 'opcionDestroy'])->name('configuracion.opciones.destroy');
    Route::post('/configuracion/colores',                   [ConfiguracionController::class, 'colorStore'])->name('configuracion.colores.store');
    Route::patch('/configuracion/colores/{codigo}',         [ConfiguracionController::class, 'colorUpdate'])->name('configuracion.colores.update');
    Route::delete('/configuracion/colores/{codigo}',        [ConfiguracionController::class, 'colorDestroy'])->name('configuracion.colores.destroy');
    Route::get('/configuracion/ubicaciones/carteles.pdf',   [ConfiguracionController::class, 'cartelesUbicacion'])->name('configuracion.ubicaciones.carteles');
    Route::post('/configuracion/acceso/regenerar',          [ConfiguracionController::class, 'accesoRegenerar'])->name('configuracion.acceso.regenerar');
    Route::get('/configuracion/acceso/cartel.pdf',          [ConfiguracionController::class, 'accesoCartel'])->name('configuracion.acceso.cartel');
    Route::patch('/configuracion/parametros',               [ConfiguracionController::class, 'parametrosUpdate'])->name('configuracion.parametros.update');

    Route::get('/catalogo-ral',              [RalCatalogoController::class, 'index'])->name('catalogo-ral.index');

    Route::get('/buscar',                    [ConsultaController::class, 'panel'])->name('buscar');
    Route::get('/buscar/datos',              [ConsultaController::class, 'panelDatos'])->name('buscar.datos');
    Route::get('/buscar/lote/{codigo}/movimientos', [ConsultaController::class, 'panelMovimientos'])->name('buscar.movimientos');
    Route::get('/buscar/lote/{codigo}',      [ConsultaController::class, 'panelFicha'])->name('buscar.ficha');

    Route::get('/reportes',                  [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('/reportes/export',           [ReporteController::class, 'exportCsv'])->name('reportes.export');

    Route::get('/usuarios',                [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/tablero',        [UsuarioController::class, 'tablero'])->name('usuarios.tablero');
    Route::get('/usuarios/nuevo',          [UsuarioController::class, 'create'])->name('usuarios.create');
    Route::post('/usuarios',               [UsuarioController::class, 'store'])->name('usuarios.store');
    Route::get('/usuarios/{usuario}/editar', [UsuarioController::class, 'edit'])->name('usuarios.edit');
    Route::patch('/usuarios/{usuario}',    [UsuarioController::class, 'update'])->name('usuarios.update');
});
