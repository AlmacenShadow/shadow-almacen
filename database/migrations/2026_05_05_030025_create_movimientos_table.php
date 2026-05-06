<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lote_id')->constrained('lotes');
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->enum('tipo', ['salida', 'retorno', 'ajuste']);
            $table->decimal('peso_kg', 9, 3); // siempre positivo
            $table->boolean('peso_manual')->default(false);
            $table->foreignId('motivo_ajuste_id')->nullable()->constrained('motivos_ajuste');
            $table->text('nota_texto')->nullable();
            $table->boolean('anomalia')->default(false);
            $table->string('tipo_anomalia', 40)->nullable();
            $table->uuid('sync_uuid')->unique();
            $table->string('device_id', 40)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('device_at')->nullable();

            $table->index('lote_id', 'idx_lote');
            $table->index('usuario_id', 'idx_usuario');
            $table->index('created_at', 'idx_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos');
    }
};
