<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Stock por lote = peso recepcionado − salidas + retornos + ajustes_con_signo
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

        // Stock por producto = suma de todos sus lotes
        DB::statement("
            CREATE VIEW v_stock_producto AS
            SELECT producto_id, SUM(stock_kg) AS stock_kg
            FROM v_stock_lote
            GROUP BY producto_id
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS v_stock_producto");
        DB::statement("DROP VIEW IF EXISTS v_stock_lote");
    }
};
