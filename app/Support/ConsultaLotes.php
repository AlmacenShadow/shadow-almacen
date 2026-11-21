<?php

namespace App\Support;

use App\Models\Lote;
use App\Models\Producto;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Datos del buscador/mapa de lotes (panel y celular). Todo solo-lectura.
 * La búsqueda se hace en el navegador sobre `buscar` (texto sin acentos en minúsculas).
 */
class ConsultaLotes
{
    /** Todos los lotes con lo necesario para listar, buscar y ubicar en el mapa. */
    public static function lotes(): array
    {
        $cajas = Cajas::estadoPorLote();
        $ordenUbic = Catalogo::todas('ubicacion')->map(fn ($o) => $o->orden)->all();

        return DB::table('lotes')
            ->join('productos', 'productos.id', '=', 'lotes.producto_id')
            ->leftJoin('ral_catalogo', 'ral_catalogo.codigo', '=', 'productos.ral')
            ->leftJoin('v_stock_lote', 'v_stock_lote.lote_id', '=', 'lotes.id')
            ->select(
                'lotes.id', 'lotes.codigo_barcode', 'lotes.fecha_recepcion', 'lotes.fecha_vencimiento',
                'lotes.peso_total_recepcionado_kg', 'lotes.cantidad_cajas', 'lotes.proveedor', 'lotes.factura',
                'lotes.propietario_externo', 'lotes.propietario_nombre', 'lotes.notas as lote_notas', 'lotes.ubicacion',
                'productos.ral', 'productos.tipo_pintura', 'productos.descripcion', 'productos.acabado',
                'productos.superficie', 'productos.textura_tipo', 'productos.metalizado', 'productos.origen',
                'productos.marca', 'productos.referencia', 'productos.curado_temp_c', 'productos.curado_tiempo_min',
                'productos.notas as producto_notas', 'productos.hex_override',
                'ral_catalogo.nombre_oficial as ral_nombre', 'ral_catalogo.hex as ral_hex',
                DB::raw('COALESCE(v_stock_lote.stock_kg, 0) as stock_kg'),
            )
            ->orderBy('lotes.codigo_barcode')
            ->get()
            ->map(function ($l) use ($cajas, $ordenUbic) {
                $e = $cajas[$l->id] ?? Cajas::estadoVacio();
                $sem = Vencimiento::semaforo($l->fecha_vencimiento);
                $tipo = Catalogo::nombre('tipo_pintura', $l->tipo_pintura);
                $origen = Catalogo::nombre('origen', $l->origen);
                $textura = Producto::texturaLabelDe($l);
                $ubic = $l->ubicacion ? Catalogo::nombre('ubicacion', $l->ubicacion) : null;
                $fila = [
                    'id'          => $l->id,
                    'codigo'      => $l->codigo_barcode,
                    'ral'         => $l->ral,
                    'ral_nombre'  => $l->ral_nombre,
                    'hex'         => $l->hex_override ?: ($l->ral_hex ?: Producto::HEX_FALLBACK),
                    'sigla'       => Producto::sigla($l->tipo_pintura),
                    'tipo'        => $tipo,
                    'textura'     => $textura,
                    'acabado_corto' => implode(' · ', Producto::acabadoCorto($l)),
                    'origen'      => $l->origen,
                    'origen_nombre' => $origen,
                    'descripcion' => $l->descripcion,
                    'marca'       => $l->marca,
                    'referencia'  => $l->referencia,
                    'curado'      => trim(($l->curado_temp_c ? "{$l->curado_temp_c} °C" : '') . ($l->curado_temp_c && $l->curado_tiempo_min ? ' · ' : '') . ($l->curado_tiempo_min ? "{$l->curado_tiempo_min} min" : '')),
                    'cliente'     => $l->propietario_externo ? ($l->propietario_nombre ?: 'cliente') : null,
                    'proveedor'   => $l->proveedor,
                    'factura'     => $l->factura,
                    'notas_lote'  => $l->lote_notas,
                    'notas_producto' => $l->producto_notas,
                    'ubicacion'   => $l->ubicacion,
                    'ubicacion_nombre' => $ubic,
                    'ubicacion_orden'  => $l->ubicacion ? ($ordenUbic[$l->ubicacion] ?? 9999) : 99999,
                    'stock_kg'    => round((float) $l->stock_kg, 2),
                    'recibido_kg' => round((float) $l->peso_total_recepcionado_kg, 2),
                    'cajas'       => (int) $l->cantidad_cajas,
                    'recepcion'   => Formato::fecha($l->fecha_recepcion),
                    'vence'       => $l->fecha_vencimiento ? Formato::fecha($l->fecha_vencimiento) : null,
                    'vence_estado'=> $sem['estado'],
                    'vence_texto' => $sem['texto'],
                    'caja_abierta_kg' => (!$e['desactivado'] && count($e['fuera']) === 0) ? $e['caja_abierta'] : null,
                    'cajas_fuera' => array_map(fn ($f) => [
                        'quien' => $f['usuario_nombre'], 'kg' => $f['peso_kg'],
                        'desde' => $f['at'] ? Formato::fechaHora($f['at']) : null,
                    ], $e['fuera']),
                ];
                $fila['buscar'] = (string) Str::of(implode(' ', array_filter([
                    $fila['codigo'], $fila['ral'], $fila['ral_nombre'], $fila['sigla'], $fila['tipo'], $fila['textura'],
                    $fila['descripcion'], $fila['marca'], $fila['referencia'], $fila['origen_nombre'], $fila['cliente'],
                    $fila['cliente'] ? 'cliente externo' : null, $fila['proveedor'], $fila['notas_lote'],
                    $fila['notas_producto'], $ubic,
                ])))->ascii()->lower();
                return $fila;
            })
            ->values()
            ->all();
    }

    /** Últimos movimientos de un lote (para la ficha). */
    public static function movimientos(int $loteId, int $limite = 12): array
    {
        return DB::table('movimientos as m')
            ->leftJoin('usuarios as u', 'u.id', '=', 'm.usuario_id')
            ->where('m.lote_id', $loteId)
            ->orderByDesc('m.id')->limit($limite)
            ->get(['m.tipo', 'm.peso_kg', 'm.created_at', 'm.tipo_anomalia', 'u.nombre'])
            ->map(fn ($m) => [
                'tipo'  => $m->tipo,
                'kg'    => round((float) $m->peso_kg, 2),
                'cuando'=> Formato::fechaHora($m->created_at),
                'quien' => $m->nombre,
                'anomalias' => Anomalias::etiquetas($m->tipo_anomalia),
            ])->all();
    }

    public static function banderas(): array
    {
        return Catalogo::banderas();
    }
}
