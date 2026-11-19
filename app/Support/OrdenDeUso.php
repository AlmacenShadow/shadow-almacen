<?php

namespace App\Support;

use App\Models\Lote;
use Illuminate\Support\Facades\DB;

/**
 * Regla de qué lote usar primero en una SALIDA (acordada 2026-11-18).
 * Dentro del mismo producto, solo lotes de Shadow (no propietario externo) con stock > 0:
 *   1. por fecha de vencimiento ascendente (así los vencidos van primero, el que venció antes primero),
 *   2. sin fecha de vencimiento al final,
 *   3. empate: el recibido primero.
 * La tablet aplica la misma regla offline (app.js → Storage.loteQueDebeUsarseAntes).
 */
class OrdenDeUso
{
    /** Clave de orden comparable: [sinFecha, vencimiento, recepción]. */
    public static function clave(object $l): array
    {
        $v = $l->fecha_vencimiento ? substr((string) $l->fecha_vencimiento, 0, 10) : null;
        return [$v === null ? 1 : 0, $v ?? '', substr((string) $l->fecha_recepcion, 0, 10)];
    }

    /**
     * Lote de Shadow que debería usarse antes que $lote, o null.
     * Si $lote es de propietario externo no aplica (la regla es solo para lotes de Shadow).
     */
    public static function loteAnterior(Lote $lote): ?object
    {
        if ($lote->propietario_externo) return null;

        $candidatos = DB::table('lotes')
            ->join('v_stock_lote', 'v_stock_lote.lote_id', '=', 'lotes.id')
            ->where('lotes.producto_id', $lote->producto_id)
            ->where('lotes.id', '!=', $lote->id)
            ->where('lotes.propietario_externo', false)
            ->where('v_stock_lote.stock_kg', '>', 0)
            ->select('lotes.codigo_barcode', 'lotes.fecha_recepcion', 'lotes.fecha_vencimiento', 'v_stock_lote.stock_kg')
            ->get()
            ->sort(fn ($a, $b) => self::clave($a) <=> self::clave($b))
            ->values();

        $primero = $candidatos->first();
        return ($primero && self::clave($primero) < self::clave($lote)) ? $primero : null;
    }
}
