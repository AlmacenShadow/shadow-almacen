<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos');
            $table->date('fecha_recepcion');
            $table->date('fecha_vencimiento')->nullable();
            $table->decimal('peso_total_recepcionado_kg', 10, 3);
            $table->decimal('peso_tara_unitario_kg', 6, 3)->default(0);
            $table->unsignedInteger('cantidad_cajas');
            $table->string('proveedor', 120)->nullable();
            $table->string('factura', 60)->nullable();
            $table->enum('origen', ['recepcion', 'migracion_inicial'])->default('recepcion');
            $table->foreignId('recepcionado_por_id')->constrained('usuarios');
            $table->string('codigo_barcode', 40)->unique();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['producto_id', 'fecha_recepcion'], 'uk_lote');
            $table->index(['producto_id', 'fecha_recepcion'], 'idx_fifo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lotes');
    }
};
