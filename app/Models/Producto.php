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
        'tipo_pintura',
        'descripcion',
        'textura_id',
        'brillo_pct',
        'acabado',
        'superficie',
        'textura_tipo',
        'metalizado',
        'origen',
        'marca',
        'referencia',
        'curado_temp_c',
        'curado_tiempo_min',
        'hex_override',
        'stock_minimo_kg',
        'stock_critico_kg',
        'activo',
    ];

    protected $casts = [
        'brillo_pct'        => 'integer',
        'metalizado'        => 'boolean',
        'curado_temp_c'     => 'integer',
        'curado_tiempo_min' => 'integer',
        'stock_minimo_kg'   => 'decimal:3',
        'stock_critico_kg'  => 'decimal:3',
        'activo'            => 'boolean',
    ];

    /** Color por defecto cuando ni hex_override ni catálogo tienen valor. */
    public const HEX_FALLBACK = '#cbd5e1'; // slate-300

    /** Catálogos de opciones (clave guardada => etiqueta mostrada). */
    public const TIPOS_PINTURA = ['Epoxi', 'Poliéster', 'Híbrida (epoxi-poliéster)', 'Poliuretano', 'Poliéster superdurable'];
    public const ACABADOS      = ['mate' => 'Mate', 'semibrillo' => 'Semibrillo', 'brillante' => 'Brillante'];
    public const SUPERFICIES   = ['liso' => 'Liso', 'texturizado' => 'Texturizado'];
    public const TEXTURA_TIPOS = ['martillado' => 'Martillado', 'granulado' => 'Granulado', 'cuero' => 'Cuero', 'arrugado' => 'Arrugado', 'piel_naranja' => 'Piel de naranja'];
    public const ORIGENES      = ['china' => 'China', 'colombia' => 'Colombia', 'usa' => 'USA', 'panama' => 'Panamá'];

    /**
     * Sigla corta por tipo de pintura (para resúmenes y listados).
     * EP/PE/PU son las abreviaturas usuales del sector; aquí van a 3 letras.
     */
    public const TIPO_SIGLAS = [
        'Epoxi'                     => 'EPO',
        'Poliéster'                 => 'POL',
        'Híbrida (epoxi-poliéster)' => 'EPP',
        'Poliuretano'               => 'PUR',
        'Poliéster superdurable'    => 'PSD',
    ];

    /** Banderas SVG inline por origen (22x15 vía la clase .flag). */
    public const BANDERAS = [
        'china'    => '<svg class="flag" viewBox="0 0 30 20"><rect width="30" height="20" fill="#de2910"/><polygon points="7,4 7.9,6.5 10.5,6.5 8.4,8.1 9.2,10.6 7,9 4.8,10.6 5.6,8.1 3.5,6.5 6.1,6.5" fill="#ffde00"/></svg>',
        'colombia' => '<svg class="flag" viewBox="0 0 30 20"><rect width="30" height="20" fill="#fcd116"/><rect y="10" width="30" height="5" fill="#003893"/><rect y="15" width="30" height="5" fill="#ce1126"/></svg>',
        'usa'      => '<svg class="flag" viewBox="0 0 30 20"><rect width="30" height="20" fill="#ffffff"/><g fill="#b22234"><rect width="30" height="2"/><rect y="4" width="30" height="2"/><rect y="8" width="30" height="2"/><rect y="12" width="30" height="2"/><rect y="16" width="30" height="2"/></g><rect width="12" height="11" fill="#3c3b6e"/></svg>',
        'panama'   => '<svg class="flag" viewBox="0 0 30 20"><rect width="30" height="20" fill="#ffffff"/><rect x="15" width="15" height="10" fill="#d21034"/><rect y="10" width="15" height="10" fill="#005293"/><polygon points="7.5,2.5 8.3,4.8 10.8,4.8 8.8,6.3 9.5,8.7 7.5,7.2 5.5,8.7 6.2,6.3 4.2,4.8 6.7,4.8" fill="#005293"/><polygon points="22.5,12.5 23.3,14.8 25.8,14.8 23.8,16.3 24.5,18.7 22.5,17.2 20.5,18.7 21.2,16.3 19.2,14.8 21.7,14.8" fill="#d21034"/></svg>',
    ];

    public static function sigla(?string $tipo): ?string
    {
        return $tipo ? (self::TIPO_SIGLAS[$tipo] ?? mb_strtoupper(mb_substr($tipo, 0, 3))) : null;
    }

    /**
     * Partes cortas del acabado para listados: "Mate", "Brillante · Martillado",
     * "Mate · Texturizado · Metalizado". Omite "Liso" (es lo normal) y el brillo %.
     * Acepta un Producto o una fila de DB::table con las mismas columnas.
     */
    public static function acabadoCorto(object $p): array
    {
        $partes = [];
        if (!empty($p->acabado)) $partes[] = self::ACABADOS[$p->acabado] ?? $p->acabado;
        if (($p->superficie ?? null) === 'texturizado') {
            $partes[] = !empty($p->textura_tipo)
                ? (self::TEXTURA_TIPOS[$p->textura_tipo] ?? $p->textura_tipo)
                : 'Texturizado';
        }
        if (!empty($p->metalizado)) $partes[] = 'Metalizado';
        return $partes;
    }

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

    /** Etiqueta compuesta de la textura estructurada: "Mate · Texturizado · Martillado · Metalizado". */
    public function getTexturaLabelAttribute(): string
    {
        $partes = [];
        if (!empty($this->acabado))    $partes[] = self::ACABADOS[$this->acabado] ?? $this->acabado;
        if (!empty($this->superficie)) $partes[] = self::SUPERFICIES[$this->superficie] ?? $this->superficie;
        if ($this->superficie === 'texturizado' && !empty($this->textura_tipo)) {
            $partes[] = self::TEXTURA_TIPOS[$this->textura_tipo] ?? $this->textura_tipo;
        }
        if ($this->metalizado) $partes[] = 'Metalizado';

        return $partes ? implode(' · ', $partes) : ($this->textura?->nombre ?? '—');
    }

    public function getDescripcionCortaAttribute(): string
    {
        return implode(' · ', array_filter([self::sigla($this->tipo_pintura), $this->ral, $this->textura_label]));
    }

    /** Como descripcion_corta pero con el nombre oficial del K7 intercalado. */
    public function getDescripcionCompletaAttribute(): string
    {
        $partes = array_filter([
            self::sigla($this->tipo_pintura),
            $this->ral,
            $this->nombre_ral_oficial,
            $this->textura_label,
            isset(self::ORIGENES[$this->origen]) ? self::ORIGENES[$this->origen] : null,
        ]);

        return implode(' · ', $partes);
    }
}
