<?php

namespace App\Support;

use App\Models\CatalogoOpcion;
use Illuminate\Support\Collection;

/**
 * Lectura de los catálogos editables (tabla catalogo_opciones), con caché
 * en memoria por petición. Reemplaza las constantes que había en Producto.
 */
class Catalogo
{
    /** @var array<string, Collection> */
    private static array $cache = [];

    public static function flush(): void
    {
        self::$cache = [];
    }

    /** Todas las opciones del grupo (activas e inactivas), ordenadas. */
    public static function todas(string $grupo): Collection
    {
        return self::$cache[$grupo] ??= CatalogoOpcion::where('grupo', $grupo)
            ->orderBy('orden')->orderBy('nombre')->get()->keyBy('clave');
    }

    /**
     * [clave => nombre]. Con $soloActivas, solo las activas más $incluir
     * (la que ya tiene el producto, aunque esté desactivada).
     */
    public static function opciones(string $grupo, bool $soloActivas = false, ?string $incluir = null): array
    {
        return self::todas($grupo)
            ->filter(fn ($o) => !$soloActivas || $o->activo || $o->clave === $incluir)
            ->map(fn ($o) => $o->nombre)
            ->all();
    }

    public static function nombre(string $grupo, ?string $clave): ?string
    {
        if ($clave === null || $clave === '') return null;
        return self::todas($grupo)->get($clave)?->nombre ?? $clave;
    }

    /** [clave => sigla] de los tipos de pintura. */
    public static function siglas(): array
    {
        return self::todas('tipo_pintura')->map(fn ($o) => $o->sigla ?: self::siglaAuto($o->nombre))->all();
    }

    public static function sigla(?string $clave): ?string
    {
        if (!$clave) return null;
        $o = self::todas('tipo_pintura')->get($clave);
        return $o ? ($o->sigla ?: self::siglaAuto($o->nombre)) : self::siglaAuto($clave);
    }

    private static function siglaAuto(string $texto): string
    {
        return mb_strtoupper(mb_substr($texto, 0, 3));
    }

    /** Data URI de la bandera de un origen, o null. */
    public static function banderaUri(?string $clave): ?string
    {
        if (!$clave) return null;
        return self::todas('origen')->get($clave)?->bandera ?: null;
    }

    /** [clave => data URI] de todas las banderas. */
    public static function banderas(): array
    {
        return self::todas('origen')->map(fn ($o) => $o->bandera)->filter()->all();
    }

    /** <img> de la bandera (seguro: data URI en img no ejecuta scripts). */
    public static function banderaHtml(?string $clave, int $ancho = 22): string
    {
        $uri = self::banderaUri($clave);
        if (!$uri) return '';
        $alto = (int) round($ancho * 2 / 3);
        $nombre = e(self::nombre('origen', $clave));
        return '<img src="' . e($uri) . '" alt="' . $nombre . '" title="' . $nombre . '" '
            . 'style="display:inline-block;width:' . $ancho . 'px;height:' . $alto . 'px;object-fit:cover;'
            . 'border-radius:2px;box-shadow:0 0 0 1px rgba(0,0,0,.12);vertical-align:middle;flex-shrink:0">';
    }
}
