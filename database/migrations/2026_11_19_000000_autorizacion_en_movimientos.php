<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Autorización de un superior en salidas que se saltan una regla
 * (orden por vencimiento / caja de propietario externo), y varias
 * anomalías por movimiento (tipo_anomalia pasa a lista separada por comas).
 *
 * Ojo SQLite (entorno local): cambiar una columna o agregar una FK obliga a
 * reconstruir la tabla, y eso choca con las vistas v_stock_* que dependen de
 * movimientos. Por eso la FK y el ensanche de tipo_anomalia solo se hacen en
 * MySQL (producción); SQLite no limita el largo de VARCHAR.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            if (! Schema::hasColumn('movimientos', 'autorizado_por_id')) {
                $table->unsignedBigInteger('autorizado_por_id')->nullable();
            }
            if (! Schema::hasColumn('movimientos', 'autorizacion_motivo')) {
                $table->text('autorizacion_motivo')->nullable();
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE movimientos MODIFY tipo_anomalia VARCHAR(150) NULL');
            Schema::table('movimientos', function (Blueprint $table) {
                $table->foreign('autorizado_por_id')->references('id')->on('usuarios')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('movimientos', function (Blueprint $table) {
                $table->dropForeign(['autorizado_por_id']);
            });
            DB::statement('ALTER TABLE movimientos MODIFY tipo_anomalia VARCHAR(40) NULL');
        }
        Schema::table('movimientos', function (Blueprint $table) {
            $table->dropColumn(['autorizado_por_id', 'autorizacion_motivo']);
        });
    }
};
