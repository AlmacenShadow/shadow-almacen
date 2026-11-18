<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Formatos de presentación del panel.
 *  - Fechas: dd-mmm-yy en español ("17-nov-26"), sin depender del locale del servidor.
 *  - Kilos: 2 decimales. En la base se guardan 3 (lo que da la báscula); solo cambia cómo se muestran.
 * En Blade: @fecha($x), @fechaHora($x), @kg($x).
 */
class Formato
{
    private const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    public static function fecha($valor, string $vacio = '—'): string
    {
        if ($valor === null || $valor === '') return $vacio;
        $d = $valor instanceof \DateTimeInterface ? Carbon::instance($valor) : Carbon::parse($valor);
        return $d->format('d') . '-' . self::MESES[$d->month - 1] . '-' . $d->format('y');
    }

    public static function fechaHora($valor, string $vacio = '—'): string
    {
        if ($valor === null || $valor === '') return $vacio;
        $d = $valor instanceof \DateTimeInterface ? Carbon::instance($valor) : Carbon::parse($valor);
        return self::fecha($d) . ' ' . $d->format('H:i');
    }

    public static function kg($valor): string
    {
        return number_format((float) $valor, 2);
    }
}
