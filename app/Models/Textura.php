<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Textura extends Model
{
    public $timestamps = false;

    protected $fillable = ['nombre', 'orden', 'activo'];

    protected $casts = [
        'orden'  => 'integer',
        'activo' => 'boolean',
    ];

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }
}
