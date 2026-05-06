<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'ral',
        'textura',
        'brillo_pct',
        'nombre_interno',
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

    public function lotes(): HasMany
    {
        return $this->hasMany(Lote::class);
    }

    public function getDescripcionCortaAttribute(): string
    {
        return "{$this->ral} · {$this->textura} · {$this->brillo_pct}%";
    }
}
