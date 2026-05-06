<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Lote extends Model
{
    use HasFactory;

    /** Solo created_at, no updated_at (los lotes son inmutables salvo el barcode al crear). */
    const UPDATED_AT = null;

    protected $fillable = [
        'producto_id',
        'fecha_recepcion',
        'fecha_vencimiento',
        'peso_total_recepcionado_kg',
        'peso_tara_unitario_kg',
        'cantidad_cajas',
        'proveedor',
        'factura',
        'origen',
        'recepcionado_por_id',
        'codigo_barcode',
    ];

    protected $casts = [
        'fecha_recepcion'             => 'date',
        'fecha_vencimiento'           => 'date',
        'peso_total_recepcionado_kg'  => 'decimal:3',
        'peso_tara_unitario_kg'       => 'decimal:3',
        'cantidad_cajas'              => 'integer',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function recepcionadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'recepcionado_por_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class);
    }

    /** Stock actual derivado de la vista v_stock_lote. */
    public function getStockKgAttribute(): float
    {
        $row = DB::table('v_stock_lote')->where('lote_id', $this->id)->first();
        return $row ? (float) $row->stock_kg : 0.0;
    }
}
