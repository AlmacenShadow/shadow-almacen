<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('motivos_ajuste', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 40)->unique();
            $table->string('descripcion', 120);
            $table->tinyInteger('signo'); // -1 o +1
            $table->boolean('requiere_nota')->default(false);
            $table->boolean('activo')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('motivos_ajuste');
    }
};
