<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Parámetros generales (tabla parametros: clave → valor), editables en Configuración.
 * Solo se muestran allí los que el sistema usa de verdad (EDITABLES).
 */
class Parametros
{
    public const EDITABLES = [
        'producto.stock_minimo_default_kg'  => ['titulo' => 'Stock mínimo por defecto (kg)', 'min' => 0, 'max' => 100000, 'step' => '0.01'],
        'producto.stock_critico_default_kg' => ['titulo' => 'Stock crítico por defecto (kg)', 'min' => 0, 'max' => 100000, 'step' => '0.01'],
        'etiquetas.por_caja'                => ['titulo' => 'Etiquetas por caja', 'min' => 1, 'max' => 10, 'step' => '1'],
    ];

    private static ?array $cache = null;

    public static function get(string $clave, $default = null)
    {
        self::$cache ??= DB::table('parametros')->pluck('valor', 'clave')->all();
        return self::$cache[$clave] ?? $default;
    }

    public static function set(string $clave, string $valor): void
    {
        DB::table('parametros')->where('clave', $clave)->update(['valor' => $valor, 'updated_at' => now()]);
        self::$cache = null;
    }

    public static function etiquetasPorCaja(): int
    {
        return max(1, (int) self::get('etiquetas.por_caja', 3));
    }
}
