<?php

namespace App\Models;

use App\Support\Catalogo;
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
        'notas',
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

    /**
     * Superficie es estructural (con "texturizado" se abre el tipo de textura),
     * por eso queda fija. El resto de catálogos son editables desde Configuración
     * (tabla catalogo_opciones, ver App\Support\Catalogo).
     */
    public const SUPERFICIES = ['liso' => 'Liso', 'texturizado' => 'Texturizado'];

    /** Sigla del tipo de pintura (clave guardada en productos.tipo_pintura). */
    public static function sigla(?string $tipo): ?string
    {
        return Catalogo::sigla($tipo);
    }

    /**
     * Partes cortas del acabado para listados: "Mate", "Brillante · Martillado",
     * "Mate · Texturizado · Metalizado". Omite "Liso" (es lo normal) y el brillo %.
     * Acepta un Producto o una fila de DB::table con las mismas columnas.
     */
    public static function acabadoCorto(object $p): array
    {
        $partes = [];
        if (!empty($p->acabado)) $partes[] = Catalogo::nombre('acabado', $p->acabado);
        if (($p->superficie ?? null) === 'texturizado') {
            $partes[] = !empty($p->textura_tipo)
                ? Catalogo::nombre('textura_tipo', $p->textura_tipo)
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

    /** Etiqueta compuesta de la textura estructurada: "Mate · Liso", "Brillante · Martillado · Metalizado". */
    public function getTexturaLabelAttribute(): string
    {
        return self::texturaLabelDe($this) ?? ($this->textura?->nombre ?? '—');
    }

    /**
     * Igual que textura_label pero para cualquier objeto con acabado/superficie/
     * textura_tipo/metalizado (Producto o fila de DB::table). Null si no hay datos.
     */
    public static function texturaLabelDe(object $p): ?string
    {
        $partes = [];
        if (!empty($p->acabado)) $partes[] = Catalogo::nombre('acabado', $p->acabado);
        // Si es texturizado con tipo, basta el tipo ("Martillado"), sin la palabra "Texturizado".
        if (($p->superficie ?? null) === 'texturizado' && !empty($p->textura_tipo)) {
            $partes[] = Catalogo::nombre('textura_tipo', $p->textura_tipo);
        } elseif (!empty($p->superficie)) {
            $partes[] = self::SUPERFICIES[$p->superficie] ?? $p->superficie;
        }
        if (!empty($p->metalizado)) $partes[] = 'Metalizado';

        return $partes ? implode(' · ', $partes) : null;
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
            Catalogo::nombre('origen', $this->origen),
        ]);

        return implode(' · ', $partes);
    }
}
