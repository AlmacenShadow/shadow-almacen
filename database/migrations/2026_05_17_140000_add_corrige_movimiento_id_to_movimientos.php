<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Soporte para correcciones por movimiento compensatorio.
 *
 * Cuando un movimiento se registra mal (ej. un peso equivocado, una salida
 * que en realidad no salió, un retorno mal asignado), el modelo dicta que
 * NO editamos ni borramos el original — registramos un movimiento nuevo
 * con efecto opuesto y nota explicativa. La columna `corrige_movimiento_id`
 * apunta al original para mantener trazabilidad.
 */
return new class extends Migration
{
    public function up(): void
    {
        // SQLite tiene que recrear la tabla en ALTER. Las vistas que dependen
        // de `movimientos` rompen ese recreate. Las dropeamos y recreamos.
        DB::statement('DROP VIEW IF EXISTS v_stock_producto');
        DB::statement('DROP VIEW IF EXISTS v_stock_lote');

        // 1) Columna FK self-referenciada en movimientos
        Schema::table('movimientos', function (Blueprint $table) {
            $table->foreignId('corrige_movimiento_id')
                ->nullable()
                ->after('motivo_ajuste_id')
                ->constrained('movimientos')
                ->nullOnDelete();
        });

        // Recrear las vistas tal cual estaban
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

        // 2) Motivo nuevo con signo -1 para anular un retorno erróneo.
        //    El CORRECCION_REGISTRO existente (signo +1) se usa para anular
        //    salidas erróneas. Necesitamos los dos sentidos.
        DB::table('motivos_ajuste')->updateOrInsert(
            ['codigo' => 'CORRECCION_REGISTRO_NEG'],
            [
                'descripcion'   => 'Corrección: anular retorno o ajuste positivo erróneo',
                'signo'         => -1,
                'requiere_nota' => true,
                'activo'        => true,
            ]
        );

        // Renombramos el existente para que sea claro que es el sentido positivo
        DB::table('motivos_ajuste')
            ->where('codigo', 'CORRECCION_REGISTRO')
            ->update([
                'descripcion' => 'Corrección: anular salida o ajuste negativo erróneo',
            ]);
    }

    public function down(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->dropForeign(['corrige_movimiento_id']);
            $table->dropColumn('corrige_movimiento_id');
        });

        DB::table('motivos_ajuste')->where('codigo', 'CORRECCION_REGISTRO_NEG')->delete();
        DB::table('motivos_ajuste')
            ->where('codigo', 'CORRECCION_REGISTRO')
            ->update(['descripcion' => 'Corrección de movimiento mal capturado']);
    }
};
