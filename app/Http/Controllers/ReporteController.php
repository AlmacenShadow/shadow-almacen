<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Reportes operativos básicos del almacén:
 *  - Consumo por producto (top RAL movidos en el período)
 *  - Consumo por pintor (quién gastó cuánto en el período)
 *  - Stock bajo / crítico (alertas sobre stock_minimo y stock_critico)
 *
 * Filtra automáticamente:
 *  - movimientos corregidos (su efecto se anula con el compensatorio)
 *  - movimientos que SON correcciones (van por el admin, no por el pintor)
 * Esto deja solo los movimientos "limpios" que reflejan operación real.
 */
class ReporteController extends Controller
{
    public function index(Request $request): View
    {
        if (! Auth::user()->puedeUsarPanel()) {
            abort(403, 'No tienes acceso.');
        }

        // Rango por defecto: últimos 30 días
        $desde = $request->query('desde')
            ? Carbon::parse($request->query('desde'))->startOfDay()
            : Carbon::today()->subDays(30);
        $hasta = $request->query('hasta')
            ? Carbon::parse($request->query('hasta'))->endOfDay()
            : Carbon::today()->endOfDay();

        // Subquery: movimientos "limpios" (sin correcciones)
        // Usamos un select base que las 3 secciones reutilizan.
        $movsLimpios = DB::table('movimientos as m')
            ->whereBetween('m.created_at', [$desde, $hasta])
            ->whereNull('m.corrige_movimiento_id') // no es una corrección
            // y no es un movimiento que haya sido corregido:
            ->whereNotIn('m.id', function ($q) {
                $q->select('corrige_movimiento_id')
                  ->from('movimientos')
                  ->whereNotNull('corrige_movimiento_id');
            });

        // === Consumo por producto ===
        $porProducto = (clone $movsLimpios)
            ->join('lotes', 'lotes.id', '=', 'm.lote_id')
            ->join('productos', 'productos.id', '=', 'lotes.producto_id')
            ->leftJoin('texturas', 'texturas.id', '=', 'productos.textura_id')
            ->leftJoin('ral_catalogo', 'ral_catalogo.codigo', '=', 'productos.ral')
            ->whereIn('m.tipo', ['salida', 'retorno'])
            ->select(
                'productos.id as producto_id',
                'productos.ral',
                DB::raw('texturas.nombre as textura'),
                'productos.brillo_pct',
                'productos.nombre_interno',
                'productos.hex_override',
                'ral_catalogo.hex as ral_hex',
                DB::raw("SUM(CASE WHEN m.tipo='salida'  THEN m.peso_kg ELSE 0 END) as kg_salidas"),
                DB::raw("SUM(CASE WHEN m.tipo='retorno' THEN m.peso_kg ELSE 0 END) as kg_retornos"),
                DB::raw("COUNT(*) as movimientos_count"),
            )
            ->groupBy('productos.id', 'productos.ral', 'texturas.nombre', 'productos.brillo_pct', 'productos.nombre_interno', 'productos.hex_override', 'ral_catalogo.hex')
            ->orderByDesc(DB::raw("SUM(CASE WHEN m.tipo='salida' THEN m.peso_kg ELSE 0 END) - SUM(CASE WHEN m.tipo='retorno' THEN m.peso_kg ELSE 0 END)"))
            ->get()
            ->map(function ($r) {
                $r->kg_netos = (float) $r->kg_salidas - (float) $r->kg_retornos;
                $r->hex_resuelto = $r->hex_override ?: ($r->ral_hex ?: '#cbd5e1');
                return $r;
            });

        // === Consumo por pintor ===
        // Solo consideramos salidas y retornos hechos por pintores
        // (ajustes los hacen encargado/admin y no son "consumo")
        $porPintor = (clone $movsLimpios)
            ->join('usuarios', 'usuarios.id', '=', 'm.usuario_id')
            ->where('usuarios.rol', 'pintor')
            ->whereIn('m.tipo', ['salida', 'retorno'])
            ->select(
                'usuarios.id as usuario_id',
                'usuarios.nombre',
                'usuarios.codigo_barcode',
                DB::raw("SUM(CASE WHEN m.tipo='salida'  THEN m.peso_kg ELSE 0 END) as kg_salidas"),
                DB::raw("SUM(CASE WHEN m.tipo='retorno' THEN m.peso_kg ELSE 0 END) as kg_retornos"),
                DB::raw("COUNT(*) as movimientos_count"),
            )
            ->groupBy('usuarios.id', 'usuarios.nombre', 'usuarios.codigo_barcode')
            ->orderByDesc(DB::raw("SUM(CASE WHEN m.tipo='salida' THEN m.peso_kg ELSE 0 END) - SUM(CASE WHEN m.tipo='retorno' THEN m.peso_kg ELSE 0 END)"))
            ->get()
            ->map(function ($r) {
                $r->kg_netos = (float) $r->kg_salidas - (float) $r->kg_retornos;
                return $r;
            });

        // === Stock bajo / crítico ===
        // No depende del rango de fechas — es el estado actual
        $stockBajo = DB::table('productos')
            ->leftJoin('texturas', 'texturas.id', '=', 'productos.textura_id')
            ->leftJoin('ral_catalogo', 'ral_catalogo.codigo', '=', 'productos.ral')
            ->leftJoin('v_stock_producto as vsp', 'vsp.producto_id', '=', 'productos.id')
            ->where('productos.activo', true)
            ->select(
                'productos.id',
                'productos.ral',
                DB::raw('texturas.nombre as textura'),
                'productos.brillo_pct',
                'productos.nombre_interno',
                'productos.stock_minimo_kg',
                'productos.stock_critico_kg',
                'productos.hex_override',
                'ral_catalogo.hex as ral_hex',
                DB::raw('COALESCE(vsp.stock_kg, 0) as stock_kg'),
            )
            ->get()
            ->map(function ($r) {
                $r->hex_resuelto = $r->hex_override ?: ($r->ral_hex ?: '#cbd5e1');
                $r->nivel = (float) $r->stock_kg <= (float) $r->stock_critico_kg
                    ? 'critico'
                    : ((float) $r->stock_kg <= (float) $r->stock_minimo_kg ? 'bajo' : 'ok');
                return $r;
            })
            ->filter(fn ($r) => $r->nivel !== 'ok')
            ->sortBy(fn ($r) => [$r->nivel === 'critico' ? 0 : 1, $r->stock_kg])
            ->values();

        return view('reportes.index', compact(
            'desde', 'hasta', 'porProducto', 'porPintor', 'stockBajo'
        ));
    }
}
