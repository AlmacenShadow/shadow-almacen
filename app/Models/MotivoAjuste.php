<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MotivoAjuste extends Model
{
    protected $table = 'motivos_ajuste';
    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'descripcion',
        'signo',
        'requiere_nota',
        'activo',
    ];

    protected $casts = [
        'signo'         => 'integer',
        'requiere_nota' => 'boolean',
        'activo'        => 'boolean',
    ];
}
