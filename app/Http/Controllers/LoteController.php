<?php

namespace App\Http\Controllers;

use App\Models\Lote;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LoteController extends Controller
{
    /** Lista de lotes con su stock actual desde la vista. */
    public function index(): View
    {
        $lotes = DB::table('lotes')
            ->join('productos', 'productos.id', '=', 'lotes.producto_id')
            ->leftJoin('texturas', 'texturas.id', '=', 'productos.textura_id')
            ->leftJoin('ral_catalogo', 'ral_catalogo.codigo', '=', 'productos.ral')
            ->leftJoin('v_stock_lote', 'v_stock_lote.lote_id', '=', 'lotes.id')
            ->select(
                'lotes.id',
                'lotes.codigo_barcode',
                'lotes.fecha_recepcion',
                'lotes.fecha_vencimiento',
                'lotes.peso_total_recepcionado_kg',
                'lotes.cantidad_cajas',
                'lotes.proveedor',
                'productos.ral',
                DB::raw('texturas.nombre as textura'),
                'productos.brillo_pct',
                'productos.nombre_interno',
                'productos.hex_override',
                'ral_catalogo.hex as ral_hex',
                DB::raw('COALESCE(v_stock_lote.stock_kg, 0) as stock_kg'),
            )
            ->orderByDesc('lotes.fecha_recepcion')
            ->orderByDesc('lotes.id')
            ->get()
            ->map(function ($l) {
                $l->hex_resuelto = $l->hex_override ?: ($l->ral_hex ?: \App\Models\Producto::HEX_FALLBACK);
                return $l;
            });

        return view('lotes.index', compact('lotes'));
    }

    /** Formulario de recepción. */
    public function create(): View
    {
        $productos = Producto::where('activo', true)
            ->orderBy('ral')
            ->get();

        return view('lotes.create', compact('productos'));
    }

    /** Persiste el lote nuevo y genera su codigo_barcode definitivo. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'producto_id'                => ['required', 'exists:productos,id'],
            'fecha_recepcion'            => ['required', 'date'],
            'fecha_vencimiento'          => ['nullable', 'date', 'after:fecha_recepcion'],
            'peso_total_recepcionado_kg' => ['required', 'numeric', 'min:0.001'],
            'peso_tara_unitario_kg'      => ['required', 'numeric', 'min:0'],
            'cantidad_cajas'             => ['required', 'integer', 'min:1'],
            'proveedor'                  => ['nullable', 'string', 'max:120'],
            'factura'                    => ['nullable', 'string', 'max:60'],
        ]);

        $lote = DB::transaction(function () use ($data) {
            $lote = Lote::create([
                ...$data,
                'origen'              => 'recepcion',
                'recepcionado_por_id' => Auth::id(),
                // placeholder único; se reemplaza con LOT-{id} apenas tengamos el id
                'codigo_barcode'      => 'TMP-' . uniqid(),
            ]);
            $lote->codigo_barcode = sprintf('LOT-%05d', $lote->id);
            $lote->save();
            return $lote;
        });

        return redirect()
            ->route('lotes.show', $lote)
            ->with('flash', 'Lote registrado. Imprime las etiquetas y pégalas a las cajas.');
    }

    /** Detalle del lote + previsualización de etiqueta + historial de movimientos. */
    public function show(Lote $lote): View
    {
        $lote->load('producto.textura', 'producto.ralCatalogo', 'recepcionadoPor');

        $movimientos = DB::table('movimientos as m')
            ->leftJoin('usuarios', 'usuarios.id', '=', 'm.usuario_id')
            ->leftJoin('motivos_ajuste as ma', 'ma.id', '=', 'm.motivo_ajuste_id')
            ->where('m.lote_id', $lote->id)
            ->orderBy('m.id', 'asc') // cronológico ASC: recepción primero, último al final
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
                'ma.descripcion as motivo_descripcion',
                'ma.signo as motivo_signo',
            )
            ->get();

        return view('lotes.show', compact('lote', 'movimientos'));
    }
}
