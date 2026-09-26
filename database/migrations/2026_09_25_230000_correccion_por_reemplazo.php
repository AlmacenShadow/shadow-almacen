<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cambia la semántica de la corrección: de anulación a reemplazo.
 *
 * Antes, corregir generaba un ajuste con el mismo peso y signo opuesto, así
 * que el neto quedaba en cero y el movimiento desaparecía — eso es anular, no
 * corregir. Ahora la corrección es un movimiento nuevo del mismo tipo que
 * carga el peso verdadero, y el original deja de contar.
 *
 * La regla queda en una sola frase, y aplica igual en stock y en reportes:
 * un movimiento que tiene un hijo de corrección no cuenta; cuenta el hijo.
 *
 * Anular sigue disponible como caso aparte: el hijo es un ajuste de 0 kg con
 * motivo ANULACION, así que no aporta nada y el original queda neutralizado
 * por la misma regla.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_stock_producto');
        DB::statement('DROP VIEW IF EXISTS v_stock_lote');

        DB::statement("
            CREATE VIEW v_stock_lote AS
            SELECT
                l.id AS lote_id,
                l.producto_id,
                l.fecha_recepcion,
                l.fecha_vencimiento,
                l.peso_total_recepcionado_kg
                    - COALESCE(SUM(CASE WHEN m.tipo='salida'  THEN m.peso_kg END), 0)
                    + COALESCE(SUM(CASE WHEN m.tipo='retorno' THEN m.peso_kg END), 0)
                    + COALESCE(SUM(CASE WHEN m.tipo='ajuste'  THEN m.peso_kg * ma.signo END), 0)
                    AS stock_kg
            FROM lotes l
            LEFT JOIN movimientos m
                   ON m.lote_id = l.id
                  AND NOT EXISTS (
                        SELECT 1 FROM movimientos c
                        WHERE c.corrige_movimiento_id = m.id
                      )
            LEFT JOIN motivos_ajuste ma ON ma.id = m.motivo_ajuste_id
            GROUP BY l.id, l.producto_id, l.fecha_recepcion, l.fecha_vencimiento, l.peso_total_recepcionado_kg
        ");

        DB::statement("
            CREATE VIEW v_stock_producto AS
            SELECT producto_id, SUM(stock_kg) AS stock_kg
            FROM v_stock_lote
            GROUP BY producto_id
        ");

        DB::table('motivos_ajuste')->updateOrInsert(
            ['codigo' => 'ANULACION'],
            [
                'descripcion'   => 'Anulación: el movimiento nunca debió existir',
                'signo'         => 1,
                'requiere_nota' => true,
                'activo'        => true,
            ]
        );
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_stock_producto');
        DB::statement('DROP VIEW IF EXISTS v_stock_lote');

        DB::statement("
            CREATE VIEW v_stock_lote AS
            SELECT
                l.id AS lote_id,
                l.producto_id,
                l.fecha_recepcion,
                l.fecha_vencimiento,
                l.peso_total_recepcionado_kg
                    - COALESCE(SUM(CASE WHEN m.tipo='salida'  THEN m.peso_kg END), 0)
                    + COALESCE(SUM(CASE WHEN m.tipo='retorno' THEN m.peso_kg END), 0)
                    + COALESCE(SUM(CASE WHEN m.tipo='ajuste'  THEN m.peso_kg * ma.signo END), 0)
                    AS stock_kg
            FROM lotes l
            LEFT JOIN movimientos    m  ON m.lote_id = l.id
            LEFT JOIN motivos_ajuste ma ON ma.id = m.motivo_ajuste_id
            GROUP BY l.id, l.producto_id, l.fecha_recepcion, l.fecha_vencimiento, l.peso_total_recepcionado_kg
        ");

        DB::statement("
            CREATE VIEW v_stock_producto AS
            SELECT producto_id, SUM(stock_kg) AS stock_kg
            FROM v_stock_lote
            GROUP BY producto_id
        ");

        DB::table('motivos_ajuste')->where('codigo', 'ANULACION')->delete();
    }
};
