<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MovimientoController extends Controller
{
    /**
     * Lista paginada de los movimientos más recientes para auditoría.
     * Filtro opcional por tipo (salida / retorno / ajuste).
     */
    public function index(Request $request): View
    {
        $tipo = $request->query('tipo');
        if (!in_array($tipo, ['salida', 'retorno', 'ajuste'], true)) {
            $tipo = null;
        }

        $q = DB::table('movimientos as m')
            ->join('lotes', 'lotes.id', '=', 'm.lote_id')
            ->join('productos', 'productos.id', '=', 'lotes.producto_id')
            ->leftJoin('usuarios', 'usuarios.id', '=', 'm.usuario_id')
            ->leftJoin('motivos_ajuste as ma', 'ma.id', '=', 'm.motivo_ajuste_id')
            ->select(
                'm.id',
                'm.created_at',
                'm.tipo',
                'm.peso_kg',
                'm.peso_manual',
                'm.anomalia',
                'm.tipo_anomalia',
                'm.nota_texto',
                'usuarios.nombre as usuario_nombre',
                'usuarios.rol as usuario_rol',
                'lotes.codigo_barcode as lote_codigo',
                'lotes.id as lote_id',
                'productos.ral',
                'productos.textura',
                'productos.brillo_pct',
                'productos.nombre_interno',
                'ma.descripcion as motivo_descripcion',
                'ma.signo as motivo_signo',
            )
            ->orderByDesc('m.id');

        if ($tipo) {
            $q->where('m.tipo', $tipo);
        }

        $movimientos = $q->paginate(50)->withQueryString();

        return view('movimientos.index', compact('movimientos', 'tipo'));
    }
}
