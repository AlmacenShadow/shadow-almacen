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
        'corrige_movimiento_id',
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

    public function lote(): BelongsTo         { return $this->belongsTo(Lote::class); }
    public function usuario(): BelongsTo      { return $this->belongsTo(Usuario::class); }
    public function motivoAjuste(): BelongsTo { return $this->belongsTo(\App\Models\MotivoAjuste::class, 'motivo_ajuste_id'); }

    /** El movimiento ORIGINAL que este movimiento está corrigiendo (si es una corrección). */
    public function corrige(): BelongsTo
    {
        return $this->belongsTo(Movimiento::class, 'corrige_movimiento_id');
    }

    /** El movimiento CORRECTIVO que anula a este (si fue corregido). */
    public function correccion()
    {
        return $this->hasOne(Movimiento::class, 'corrige_movimiento_id');
    }
}
