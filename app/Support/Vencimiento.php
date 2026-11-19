<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Semáforo de vencimiento (panel web; la tablet usa la misma regla en JS):
 *   vencido → morado · < 30 días → rojo · < 60 → naranja · < 90 → amarillo · resto o sin fecha → normal
 */
class Vencimiento
{
    /**
     * @return array{estado:string, dias:?int, clase:string, texto:?string}
     *   clase: clases Tailwind para una pastilla; texto: leyenda corta (o null si es normal).
     */
    public static function semaforo($fecha): array
    {
        if (!$fecha) {
            return ['estado' => 'normal', 'dias' => null, 'clase' => '', 'texto' => null];
        }
        $vence = Carbon::parse($fecha)->startOfDay();
        $dias = (int) Carbon::today()->diffInDays($vence, false); // negativo = ya venció

        if ($dias < 0)  return ['estado' => 'vencido',  'dias' => $dias, 'clase' => 'bg-purple-100 text-purple-800 ring-1 ring-purple-300', 'texto' => 'Vencido'];
        if ($dias < 30) return ['estado' => 'rojo',     'dias' => $dias, 'clase' => 'bg-red-100 text-red-800',       'texto' => self::faltan($dias)];
        if ($dias < 60) return ['estado' => 'naranja',  'dias' => $dias, 'clase' => 'bg-orange-100 text-orange-800', 'texto' => self::faltan($dias)];
        if ($dias < 90) return ['estado' => 'amarillo', 'dias' => $dias, 'clase' => 'bg-yellow-100 text-yellow-800', 'texto' => self::faltan($dias)];
        return ['estado' => 'normal', 'dias' => $dias, 'clase' => '', 'texto' => null];
    }

    public static function vencido($fecha): bool
    {
        return $fecha && Carbon::parse($fecha)->startOfDay()->lt(Carbon::today());
    }

    private static function faltan(int $dias): string
    {
        return $dias === 0 ? 'vence hoy' : ($dias === 1 ? 'vence mañana' : "vence en {$dias} días");
    }
}
