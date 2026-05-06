<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movimiento extends Model
{
    /** Append-only: solo created_at, sin updated_at. */
    const UPDATED_AT = null;

    protected $fillable = [
        'lote_id',
        'usuario_id',
        'tipo',
        'peso_kg',
        'peso_manual',
        'motivo_ajuste_id',
        'nota_texto',
        'anomalia',
        'tipo_anomalia',
        'sync_uuid',
        'device_id',
        'device_at',
    ];

    protected $casts = [
        'peso_kg'     => 'decimal:3',
        'peso_manual' => 'boolean',
        'anomalia'    => 'boolean',
        'device_at'   => 'datetime',
    ];

    public function lote(): BelongsTo    { return $this->belongsTo(Lote::class); }
    public function usuario(): BelongsTo { return $this->belongsTo(Usuario::class); }
}
