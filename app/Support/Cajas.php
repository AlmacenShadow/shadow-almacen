<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Vigilante de caja abierta (acordado 2026-11-18, PENDIENTES.md sección 2).
 *
 * Estado de un lote, calculado recorriendo sus salidas y retornos en orden:
 *   fuera        — cajas que salieron y no han vuelto: [{usuario_codigo, usuario_nombre, peso_kg, at}]
 *   caja_abierta — kg del último retorno si fue >= 0.3 kg (caja empezada en el estante); null si no hay
 *   desactivado  — true si hubo dos cajas fuera a la vez; vuelve a false cuando no queda ninguna fuera
 *
 * Paso por movimiento (el mismo que aplica la tablet, app.js → pasoCaja):
 *   salida : si ya había cajas fuera → desactivado; se agrega a fuera; la caja abierta sale con ella (null)
 *   retorno: cierra una caja fuera (la del mismo pintor, la más reciente; si no, la más antigua);
 *            peso >= 0.3 → caja_abierta = peso, si no → null; sin cajas fuera → desactivado = false
 */
class Cajas
{
    public const MINIMO_ABIERTA_KG = 0.3;

    public static function estadoVacio(): array
    {
        return ['fuera' => [], 'caja_abierta' => null, 'desactivado' => false];
    }

    public static function paso(array $e, string $tipo, float $peso, string $usuarioCodigo, ?string $usuarioNombre, ?string $at): array
    {
        if ($tipo === 'salida') {
            if (count($e['fuera']) > 0) $e['desactivado'] = true;
            $e['fuera'][] = ['usuario_codigo' => $usuarioCodigo, 'usuario_nombre' => $usuarioNombre,
                             'peso_kg' => round($peso, 3), 'at' => $at];
            $e['caja_abierta'] = null;
        } elseif ($tipo === 'retorno') {
            $i = null;
            foreach ($e['fuera'] as $k => $f) {
                if ($f['usuario_codigo'] === $usuarioCodigo) $i = $k; // la más reciente del mismo pintor
            }
            if ($i === null && count($e['fuera']) > 0) $i = 0;        // si no, la más antigua
            if ($i !== null) array_splice($e['fuera'], $i, 1);
            $e['caja_abierta'] = $peso >= self::MINIMO_ABIERTA_KG ? round($peso, 3) : null;
            if (count($e['fuera']) === 0) $e['desactivado'] = false;
        }
        return $e;
    }

    /**
     * Estado de todos los lotes: [lote_id => estado].
     * Ignora ajustes y los movimientos anulados por una corrección.
     */
    public static function estadoPorLote(?int $soloLoteId = null): array
    {
        $anulados = DB::table('movimientos as c')
            ->join('motivos_ajuste as ma', 'ma.id', '=', 'c.motivo_ajuste_id')
            ->whereNotNull('c.corrige_movimiento_id')
            ->where('ma.codigo', 'ANULACION')
            ->pluck('c.corrige_movimiento_id')->all();

        $movs = DB::table('movimientos as m')
            ->join('usuarios as u', 'u.id', '=', 'm.usuario_id')
            ->whereIn('m.tipo', ['salida', 'retorno'])
            ->when($soloLoteId, fn ($q) => $q->where('m.lote_id', $soloLoteId))
            ->when($anulados, fn ($q) => $q->whereNotIn('m.id', $anulados))
            ->orderBy('m.id')
            ->select('m.lote_id', 'm.tipo', 'm.peso_kg', 'm.created_at', 'u.codigo_barcode', 'u.nombre')
            ->get();

        $estados = [];
        foreach ($movs as $m) {
            $estados[$m->lote_id] = self::paso(
                $estados[$m->lote_id] ?? self::estadoVacio(),
                $m->tipo, (float) $m->peso_kg, $m->codigo_barcode, $m->nombre,
                $m->created_at ? \Illuminate\Support\Carbon::parse($m->created_at)->toIso8601String() : null
            );
        }
        return $estados;
    }

    public static function estadoDeLote(int $loteId): array
    {
        return self::estadoPorLote($loteId)[$loteId] ?? self::estadoVacio();
    }

    /** ¿Una salida de $peso kg es una caja nueva teniendo una abierta de $abierta kg? */
    public static function esCajaNueva(float $peso, float $abierta): bool
    {
        return $peso > $abierta + max(0.5, 0.05 * $abierta);
    }
}
