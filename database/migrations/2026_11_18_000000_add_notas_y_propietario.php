<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notas libres en producto y lote, y propietario externo en lote
 * (clientes que traen su propia pintura).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->text('notas')->nullable();
        });

        Schema::table('lotes', function (Blueprint $table) {
            $table->boolean('propietario_externo')->default(false);
            $table->string('propietario_nombre', 120)->nullable();
            $table->text('notas')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('notas');
        });
        Schema::table('lotes', function (Blueprint $table) {
            $table->dropColumn(['propietario_externo', 'propietario_nombre', 'notas']);
        });
    }
};
