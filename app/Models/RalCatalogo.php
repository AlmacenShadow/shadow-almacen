<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RalCatalogo extends Model
{
    protected $table = 'ral_catalogo';
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['codigo', 'nombre_oficial', 'hex', 'grupo', 'orden'];

    protected $casts = [
        'orden' => 'integer',
    ];
}
