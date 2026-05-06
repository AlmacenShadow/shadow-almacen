<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('ral', 16);
            $table->string('textura', 40);
            $table->unsignedTinyInteger('brillo_pct');
            $table->string('nombre_interno', 120)->nullable();
            $table->decimal('stock_minimo_kg', 9, 3)->default(0);
            $table->decimal('stock_critico_kg', 9, 3)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['ral', 'textura', 'brillo_pct'], 'uk_producto');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
