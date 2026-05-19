<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'ral',
        'textura_id',
        'brillo_pct',
        'nombre_interno',
        'hex_override',
        'stock_minimo_kg',
        'stock_critico_kg',
        'activo',
    ];

    protected $casts = [
        'brillo_pct'        => 'integer',
        'stock_minimo_kg'   => 'decimal:3',
        'stock_critico_kg'  => 'decimal:3',
        'activo'            => 'boolean',
    ];

    /** Color por defecto cuando ni hex_override ni catálogo tienen valor. */
    public const HEX_FALLBACK = '#cbd5e1'; // slate-300

    public function textura(): BelongsTo
    {
        return $this->belongsTo(Textura::class);
    }

    public function ralCatalogo(): BelongsTo
    {
        return $this->belongsTo(RalCatalogo::class, 'ral', 'codigo');
    }

    public function lotes(): HasMany
    {
        return $this->hasMany(Lote::class);
    }

    /**
     * Color resuelto: hex_override del producto → hex del catálogo → fallback.
     * Útil para el swatch en vistas.
     */
    public function getHexAttribute(): string
    {
        if (!empty($this->attributes['hex_override'])) {
            return $this->attributes['hex_override'];
        }
        if ($this->relationLoaded('ralCatalogo') && $this->ralCatalogo) {
            return $this->ralCatalogo->hex;
        }
        // Lookup directo si no hay relación cargada (para no romper otros usos)
        $row = \Illuminate\Support\Facades\DB::table('ral_catalogo')
            ->where('codigo', $this->ral)
            ->value('hex');
        return $row ?: self::HEX_FALLBACK;
    }

    /** Nombre oficial del RAL si está en el catálogo, o null. */
    public function getNombreRalOficialAttribute(): ?string
    {
        if ($this->relationLoaded('ralCatalogo') && $this->ralCatalogo) {
            return $this->ralCatalogo->nombre_oficial;
        }
        return \Illuminate\Support\Facades\DB::table('ral_catalogo')
            ->where('codigo', $this->ral)
            ->value('nombre_oficial');
    }

    public function getDescripcionCortaAttribute(): string
    {
        $textura = $this->textura?->nombre ?? '?';
        return "{$this->ral} · {$textura} · {$this->brillo_pct}%";
    }
}
