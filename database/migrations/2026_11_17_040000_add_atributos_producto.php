<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Enriquece productos con atributos de pintura en polvo:
 * tipo de pintura, textura estructurada (acabado/superficie/textura_tipo/metalizado),
 * descripción, origen, marca, referencia y curado (temp/tiempo).
 *
 * - Mantiene textura_id (se sigue derivando para las vistas/PDF que ya lo usan).
 * - Migra los productos existentes: deduce los nuevos campos desde su textura actual.
 * - Quita uk_producto (ya no refleja la identidad del producto; el aviso de
 *   duplicado lo maneja el controlador con los campos nuevos).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('tipo_pintura', 40)->nullable()->after('ral');
            $table->string('descripcion', 255)->nullable()->after('tipo_pintura');
            $table->string('acabado', 20)->nullable()->after('brillo_pct');       // mate|semibrillo|brillante
            $table->string('superficie', 20)->nullable()->after('acabado');        // liso|texturizado
            $table->string('textura_tipo', 30)->nullable()->after('superficie');   // martillado|granulado|cuero|arrugado|piel_naranja
            $table->boolean('metalizado')->default(false)->after('textura_tipo');
            $table->string('origen', 20)->nullable()->after('metalizado');         // china|colombia|usa|panama
            $table->string('marca', 80)->nullable()->after('origen');
            $table->string('referencia', 80)->nullable()->after('marca');
            $table->unsignedSmallInteger('curado_temp_c')->nullable()->after('referencia');
            $table->unsignedSmallInteger('curado_tiempo_min')->nullable()->after('curado_temp_c');
        });

        // Asegura que existan las texturas que la derivación necesita.
        foreach ([
            ['nombre' => 'Mate', 'orden' => 10], ['nombre' => 'Semibrillo', 'orden' => 15],
            ['nombre' => 'Brillante', 'orden' => 20], ['nombre' => 'Texturizado', 'orden' => 30],
            ['nombre' => 'Martillado', 'orden' => 40], ['nombre' => 'Granulado', 'orden' => 50],
            ['nombre' => 'Cuero', 'orden' => 60], ['nombre' => 'Arrugado', 'orden' => 65],
            ['nombre' => 'Piel de naranja', 'orden' => 68], ['nombre' => 'Metálico', 'orden' => 70],
        ] as $t) {
            DB::table('texturas')->insertOrIgnore(['nombre' => $t['nombre'], 'orden' => $t['orden'], 'activo' => true]);
        }

        // Deduce los campos nuevos desde la textura actual de cada producto.
        // En PHP (no UPDATE ... JOIN) para que funcione igual en MySQL y en SQLite local.
        $mapa = [
            'Mate'            => ['acabado' => 'mate',       'superficie' => 'liso'],
            'Semibrillo'      => ['acabado' => 'semibrillo', 'superficie' => 'liso'],
            'Brillante'       => ['acabado' => 'brillante',  'superficie' => 'liso'],
            'Metálico'        => ['superficie' => 'liso', 'metalizado' => true],
            'Texturizado'     => ['superficie' => 'texturizado'],
            'Martillado'      => ['superficie' => 'texturizado', 'textura_tipo' => 'martillado'],
            'Granulado'       => ['superficie' => 'texturizado', 'textura_tipo' => 'granulado'],
            'Cuero'           => ['superficie' => 'texturizado', 'textura_tipo' => 'cuero'],
            'Arrugado'        => ['superficie' => 'texturizado', 'textura_tipo' => 'arrugado'],
            'Piel de naranja' => ['superficie' => 'texturizado', 'textura_tipo' => 'piel_naranja'],
        ];
        $productos = DB::table('productos')
            ->leftJoin('texturas', 'texturas.id', '=', 'productos.textura_id')
            ->select('productos.id', 'texturas.nombre as textura')
            ->get();
        foreach ($productos as $p) {
            $campos = $mapa[$p->textura] ?? [];
            $campos += ['acabado' => 'mate', 'superficie' => 'liso']; // defaults si la textura no está mapeada
            DB::table('productos')->where('id', $p->id)->update($campos);
        }

        // Quita el único viejo; la unicidad ahora es responsabilidad del controlador.
        Schema::table('productos', function (Blueprint $table) {
            $table->dropUnique('uk_producto');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->unique(['ral', 'textura_id', 'brillo_pct'], 'uk_producto');
            $table->dropColumn([
                'tipo_pintura', 'descripcion', 'acabado', 'superficie', 'textura_tipo',
                'metalizado', 'origen', 'marca', 'referencia', 'curado_temp_c', 'curado_tiempo_min',
            ]);
        });
    }
};
