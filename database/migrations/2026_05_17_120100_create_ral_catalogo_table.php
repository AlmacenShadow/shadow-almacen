<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo RAL Classic K7 — 213 colores estándar con su hex aproximado.
 * El seed se carga en RalCatalogoSeeder para mantener esta migración limpia.
 *
 * `codigo` es la clave primaria natural (string tipo "RAL9005").
 * `hex_override` en productos permite que Shadow ajuste el color sin tocar el catálogo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ral_catalogo', function (Blueprint $table) {
            $table->string('codigo', 16)->primary(); // RAL9005
            $table->string('nombre_oficial', 120);   // Negro intenso
            $table->char('hex', 7);                  // #0A0A0A
            $table->string('grupo', 40);             // Amarillo, Naranja, etc.
            $table->unsignedSmallInteger('orden')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ral_catalogo');
    }
};
