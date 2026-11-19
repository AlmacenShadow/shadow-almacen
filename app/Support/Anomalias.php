<?php

namespace App\Support;

/**
 * Anomalías de un movimiento. Se guardan en movimientos.tipo_anomalia como
 * lista separada por comas ("fifo_override,lote_vencido").
 */
class Anomalias
{
    public const ETIQUETAS = [
        'fifo_override'          => 'No usó el lote que tocaba (orden por vencimiento)',
        'lote_ajeno'             => 'Usó una caja de propietario externo',
        'lote_vencido'           => 'Usó un lote vencido',
        'autorizacion_invalida'  => 'La autorización no es válida (quien autorizó no es encargado/admin activo)',
        'retorno_sin_salida'     => 'Retorno sin salida previa del mismo pintor',
        'retorno_excede_salida'  => 'Devolvió más peso del que sacó',
    ];

    /** Las que la tablet puede informar. */
    public const DE_TABLET = ['fifo_override', 'lote_ajeno', 'lote_vencido'];

    /** Las que exigen autorización de un superior. */
    public const REQUIEREN_AUTORIZACION = ['fifo_override', 'lote_ajeno'];

    public static function lista(?string $csv): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $csv))));
    }

    public static function aCsv(array $tipos): ?string
    {
        $tipos = array_values(array_unique(array_filter($tipos)));
        return $tipos ? implode(',', $tipos) : null;
    }

    /** Etiquetas legibles de un CSV. */
    public static function etiquetas(?string $csv): array
    {
        return array_map(fn ($t) => self::ETIQUETAS[$t] ?? $t, self::lista($csv));
    }
}
