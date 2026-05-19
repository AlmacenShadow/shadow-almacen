<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla `texturas` y carga las 7 texturas que hasta ahora vivían
 * hardcodeadas en los formularios.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('texturas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 40)->unique();
            $table->unsignedSmallInteger('orden')->default(100);
            $table->boolean('activo')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });

        // Inserción idempotente de las texturas que ya estaban en uso.
        $defaults = [
            ['nombre' => 'Mate',         'orden' => 10],
            ['nombre' => 'Brillante',    'orden' => 20],
            ['nombre' => 'Texturizado',  'orden' => 30],
            ['nombre' => 'Martillado',   'orden' => 40],
            ['nombre' => 'Granulado',    'orden' => 50],
            ['nombre' => 'Cuero',        'orden' => 60],
            ['nombre' => 'Metálico',     'orden' => 70],
        ];
        foreach ($defaults as $t) {
            DB::table('texturas')->insertOrIgnore([
                'nombre' => $t['nombre'],
                'orden'  => $t['orden'],
                'activo' => true,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('texturas');
    }
};
