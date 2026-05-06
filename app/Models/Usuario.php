<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

/**
 * Usuario unificado: pintor | encargado | admin.
 * Pintores solo tienen codigo_barcode (login en kiosko).
 * Encargado/admin además tienen email + password_hash (login en panel web).
 */
class Usuario extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'usuarios';

    public $timestamps = true;

    protected $fillable = [
        'nombre',
        'rol',
        'codigo_barcode',
        'email',
        'password_hash',
        'activo',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /** Laravel busca por defecto la columna `password`; aquí redirigimos a `password_hash`. */
    public function getAuthPassword(): string
    {
        return $this->password_hash ?? '';
    }

    public function esPintor(): bool    { return $this->rol === 'pintor'; }
    public function esEncargado(): bool { return $this->rol === 'encargado'; }
    public function esAdmin(): bool     { return $this->rol === 'admin'; }
    public function puedeUsarPanel(): bool { return in_array($this->rol, ['encargado', 'admin']); }
}
