<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ubicación física de cada lote: estante + cara (A = derecha, B = izquierda,
 * cada estante se ve de una sola forma). Catálogo editable en Configuración
 * (grupo 'ubicacion'), lotes.ubicacion guarda la clave ("3a") y un historial
 * registra cada cambio (de dónde, a dónde, quién, cuándo).
 * Sin FK hacia lotes en SQLite para no reconstruir tablas (vistas v_stock_*).
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $orden = 10;
        foreach (range(1, 7) as $estante) {
            foreach (['a', 'b'] as $cara) {
                DB::table('catalogo_opciones')->insertOrIgnore([
                    'grupo' => 'ubicacion', 'clave' => "{$estante}{$cara}", 'nombre' => $estante . strtoupper($cara),
                    'orden' => $orden, 'activo' => true, 'created_at' => $now, 'updated_at' => $now,
                ]);
                $orden += 10;
            }
        }

        if (! Schema::hasColumn('lotes', 'ubicacion')) {
            Schema::table('lotes', function (Blueprint $table) {
                $table->string('ubicacion', 60)->nullable();
            });
        }

        Schema::create('lote_ubicaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lote_id')->index();
            $table->string('desde', 60)->nullable();
            $table->string('hasta', 60)->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('nota', 200)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lote_ubicaciones');
        Schema::table('lotes', function (Blueprint $table) {
            $table->dropColumn('ubicacion');
        });
        DB::table('catalogo_opciones')->where('grupo', 'ubicacion')->delete();
    }
};
