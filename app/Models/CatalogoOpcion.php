<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Una opción de un catálogo editable desde Configuración.
 * grupo: tipo_pintura | acabado | textura_tipo | origen | proveedor
 * La `clave` es lo que guardan los productos y no cambia; el `nombre` sí.
 */
class CatalogoOpcion extends Model
{
    protected $table = 'catalogo_opciones';

    protected $fillable = ['grupo', 'clave', 'nombre', 'sigla', 'iso', 'bandera', 'orden', 'activo'];

    protected $casts = [
        'orden'  => 'integer',
        'activo' => 'boolean',
    ];

    /** Grupos con su título y qué campos extra usan. */
    public const GRUPOS = [
        'tipo_pintura' => ['titulo' => 'Tipos de pintura', 'singular' => 'tipo de pintura', 'campo' => 'tipo_pintura',
                           'ayuda' => 'La sigla (3 letras) aparece en los resúmenes: POL, EPO…'],
        'acabado'      => ['titulo' => 'Acabados', 'singular' => 'acabado', 'campo' => 'acabado',
                           'ayuda' => 'Nivel de brillo visual: mate, semibrillo, brillante…'],
        'textura_tipo' => ['titulo' => 'Tipos de textura', 'singular' => 'tipo de textura', 'campo' => 'textura_tipo',
                           'ayuda' => 'Aparecen cuando la superficie es texturizada.'],
        'origen'       => ['titulo' => 'Países de origen', 'singular' => 'país', 'campo' => 'origen',
                           'ayuda' => 'País de fabricación de la pintura. Con el código ISO (2 letras) se descarga la bandera.'],
        'proveedor'    => ['titulo' => 'Proveedores', 'singular' => 'proveedor', 'campo' => null,
                           'ayuda' => 'Empresas a las que se compra. Se sugieren al escribir el proveedor de un lote.'],
        'ubicacion'    => ['titulo' => 'Ubicaciones', 'singular' => 'ubicación', 'campo' => null,
                           'ayuda' => 'Estante + cara donde se guardan los lotes (A = derecha, B = izquierda). El orden es el del recorrido.'],
    ];

    protected static function booted(): void
    {
        // Cualquier cambio invalida la caché en memoria de App\Support\Catalogo
        static::saved(fn () => \App\Support\Catalogo::flush());
        static::deleted(fn () => \App\Support\Catalogo::flush());
    }

    /** Cuántos productos (o lotes, para proveedor) usan esta opción. */
    public function usos(): int
    {
        if ($this->grupo === 'proveedor') {
            return \Illuminate\Support\Facades\DB::table('lotes')->where('proveedor', $this->nombre)->count();
        }
        if ($this->grupo === 'ubicacion') {
            return \Illuminate\Support\Facades\DB::table('lotes')->where('ubicacion', $this->clave)->count();
        }
        $campo = self::GRUPOS[$this->grupo]['campo'] ?? null;
        return $campo ? Producto::where($campo, $this->clave)->count() : 0;
    }
}
