<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migra productos.textura (string) a productos.textura_id (FK a texturas).
 * Agrega productos.hex_override para colores no estándar.
 *
 * Pasos:
 *  1. Agrega columnas textura_id (nullable) y hex_override.
 *  2. Pobla textura_id desde la string actual (matching por nombre).
 *  3. Inserta cualquier textura que estuviera en productos pero no en la tabla.
 *  4. Vuelve a poblar para asegurar 100% de cobertura.
 *  5. Elimina constraint único viejo, elimina columna textura, recrea único.
 *
 * SQLite no soporta ALTER TABLE con FK directamente; Laravel recrea la tabla
 * internamente. En MySQL es directo.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Agregar columnas
        Schema::table('productos', function (Blueprint $table) {
            $table->foreignId('textura_id')
                ->nullable()
                ->after('ral')
                ->constrained('texturas');
            $table->char('hex_override', 7)
                ->nullable()
                ->after('nombre_interno');
        });

        // 2. Inserta texturas que estuvieran en productos pero no en la tabla
        $texturasEnUso = DB::table('productos')
            ->select('textura')
            ->distinct()
            ->whereNotNull('textura')
            ->pluck('textura');
        foreach ($texturasEnUso as $nombre) {
            DB::table('texturas')->insertOrIgnore([
                'nombre' => $nombre,
                'orden'  => 100,
                'activo' => true,
            ]);
        }

        // 3. Poblar textura_id por nombre
        DB::statement('
            UPDATE productos
            SET textura_id = (
                SELECT id FROM texturas WHERE texturas.nombre = productos.textura
            )
            WHERE textura_id IS NULL
        ');

        // 4. Eliminar índice único viejo y columna textura
        Schema::table('productos', function (Blueprint $table) {
            $table->dropUnique('uk_producto');
            $table->dropColumn('textura');
        });

        // 5. Recrear índice único con textura_id
        Schema::table('productos', function (Blueprint $table) {
            $table->unique(['ral', 'textura_id', 'brillo_pct'], 'uk_producto');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropUnique('uk_producto');
            $table->string('textura', 40)->nullable()->after('ral');
        });

        // Repoblar textura string desde texturas.nombre
        DB::statement('
            UPDATE productos
            SET textura = (
                SELECT nombre FROM texturas WHERE texturas.id = productos.textura_id
            )
        ');

        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['textura_id']);
            $table->dropColumn('textura_id');
            $table->dropColumn('hex_override');
            $table->unique(['ral', 'textura', 'brillo_pct'], 'uk_producto');
        });
    }
};
