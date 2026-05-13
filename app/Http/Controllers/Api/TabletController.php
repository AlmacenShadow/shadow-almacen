<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lote;
use App\Models\Movimiento;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Las puertas que la tablet del almacén usa para hablar con el sistema.
 *
 * No hay sesión ni cookies: la tablet manda el código del pintor en cada llamada.
 * Cuando despleguemos a internet añadiremos un API key en la cabecera.
 */
class TabletController extends Controller
{
    /**
     * GET /api/snapshot
     * Devuelve la "foto" completa de lo que la tablet necesita conocer para operar
     * sin internet: usuarios activos, productos activos y lotes con stock.
     *
     * La tablet la consulta al arrancar y después de cada sincronización
     * exitosa. Con esto puede identificar pintores y lotes incluso offline.
     */
    public function snapshot(): JsonResponse
    {
        $usuarios = Usuario::where('activo', true)
            ->select('id', 'codigo_barcode', 'nombre', 'rol')
            ->orderBy('codigo_barcode')
            ->get();

        $productos = DB::table('productos')
            ->where('activo', true)
            ->select('id', 'ral', 'textura', 'brillo_pct', 'nombre_interno')
            ->orderBy('ral')
            ->get();

        $lotes = DB::table('lotes')
            ->join('productos', 'productos.id', '=', 'lotes.producto_id')
            ->leftJoin('v_stock_lote', 'v_stock_lote.lote_id', '=', 'lotes.id')
            ->select(
                'lotes.id',
                'lotes.codigo_barcode',
                'lotes.producto_id',
                'lotes.fecha_recepcion',
                'lotes.fecha_vencimiento',
                'lotes.peso_tara_unitario_kg',
                'productos.ral',
                'productos.textura',
                'productos.brillo_pct',
                'productos.nombre_interno',
                DB::raw('COALESCE(v_stock_lote.stock_kg, 0) as stock_actual_kg')
            )
            ->where(function ($q) {
                $q->where('v_stock_lote.stock_kg', '>', 0)
                  ->orWhereNull('v_stock_lote.stock_kg');
            })
            ->orderBy('lotes.fecha_recepcion')
            ->get();

        return response()->json([
            'timestamp' => now()->toIso8601String(),
            'usuarios'  => $usuarios,
            'productos' => $productos,
            'lotes'     => $lotes,
        ]);
    }

    /**
     * GET /api/usuarios/{codigo}
     * La tablet escanea el papel del pintor del tablero. Esto le dice quién es.
     */
    public function mostrarUsuario(string $codigo): JsonResponse
    {
        $u = Usuario::where('codigo_barcode', $codigo)->where('activo', true)->first();

        if (! $u) {
            return response()->json(['error' => 'Código no reconocido o usuario inactivo'], 404);
        }

        return response()->json([
            'id'             => $u->id,
            'codigo_barcode' => $u->codigo_barcode,
            'nombre'         => $u->nombre,
            'rol'            => $u->rol,
        ]);
    }

    /**
     * GET /api/lotes/{codigo}
     * La tablet escanea la etiqueta de la caja. Devuelve el detalle del lote +
     * stock actual + aviso si hay un lote más viejo del mismo producto con stock.
     */
    public function mostrarLote(string $codigo): JsonResponse
    {
        $lote = Lote::with('producto')->where('codigo_barcode', $codigo)->first();

        if (! $lote) {
            return response()->json(['error' => 'Lote no encontrado'], 404);
        }

        // Buscamos un lote más antiguo del mismo producto que aún tenga stock.
        // Es la regla FIFO: si hay uno más viejo con material, debería usarse primero.
        $loteAnterior = DB::table('lotes')
            ->join('v_stock_lote', 'v_stock_lote.lote_id', '=', 'lotes.id')
            ->where('lotes.producto_id', $lote->producto_id)
            ->where('lotes.id', '!=', $lote->id)
            ->where('lotes.fecha_recepcion', '<', $lote->fecha_recepcion)
            ->where('v_stock_lote.stock_kg', '>', 0)
            ->orderBy('lotes.fecha_recepcion')
            ->select('lotes.codigo_barcode', 'lotes.fecha_recepcion', 'v_stock_lote.stock_kg')
            ->first();

        return response()->json([
            'lote' => [
                'id'                          => $lote->id,
                'codigo_barcode'              => $lote->codigo_barcode,
                'fecha_recepcion'             => $lote->fecha_recepcion->format('Y-m-d'),
                'fecha_vencimiento'           => $lote->fecha_vencimiento?->format('Y-m-d'),
                'peso_tara_unitario_kg'       => (float) $lote->peso_tara_unitario_kg,
                'peso_total_recepcionado_kg'  => (float) $lote->peso_total_recepcionado_kg,
                'stock_actual_kg'             => (float) $lote->stock_kg,
            ],
            'producto' => [
                'ral'            => $lote->producto->ral,
                'textura'        => $lote->producto->textura,
                'brillo_pct'     => $lote->producto->brillo_pct,
                'nombre_interno' => $lote->producto->nombre_interno,
            ],
            // Si no es null, la tablet debe avisar "hay un lote anterior, usa ése primero"
            'aviso_fifo' => $loteAnterior ? [
                'codigo_barcode'  => $loteAnterior->codigo_barcode,
                'fecha_recepcion' => $loteAnterior->fecha_recepcion,
                'stock_kg'        => (float) $loteAnterior->stock_kg,
            ] : null,
        ]);
    }

    /**
     * POST /api/movimientos
     * La tablet manda un movimiento (salida o retorno) para guardarlo.
     * Usa sync_uuid: si la tablet reintenta por mala conexión, no duplicamos.
     */
    public function registrarMovimiento(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'lote_codigo'    => ['required', 'string', 'exists:lotes,codigo_barcode'],
            'usuario_codigo' => ['required', 'string', 'exists:usuarios,codigo_barcode'],
            'tipo'           => ['required', 'in:salida,retorno'],
            'peso_kg'        => ['required', 'numeric', 'min:0.001'],
            'peso_manual'    => ['boolean'],
            'sync_uuid'      => ['required', 'uuid'],
            'device_id'      => ['nullable', 'string', 'max:40'],
            'device_at'      => ['nullable', 'date'],
            // Si la tablet decidió ignorar el aviso FIFO, nos lo dice
            'override_fifo'  => ['boolean'],
        ]);

        // ¿Ya nos llegó este movimiento antes? (reintento). Devolvemos OK sin duplicar.
        $existente = Movimiento::where('sync_uuid', $datos['sync_uuid'])->first();
        if ($existente) {
            return response()->json([
                'ok'        => true,
                'duplicado' => true,
                'movimiento_id' => $existente->id,
            ]);
        }

        $lote    = Lote::where('codigo_barcode', $datos['lote_codigo'])->firstOrFail();
        $usuario = Usuario::where('codigo_barcode', $datos['usuario_codigo'])->firstOrFail();

        $anomalia    = false;
        $tipoAnomal  = null;

        // Anomalía 1: el pintor sacó polvo ignorando el aviso FIFO
        if (! empty($datos['override_fifo'])) {
            $anomalia    = true;
            $tipoAnomal  = 'fifo_override';
        }

        // Anomalía 2: en un retorno devolvió más peso del que había en stock antes
        // (no bloqueamos, solo dejamos la marca de "algo raro")
        if ($datos['tipo'] === 'retorno' && $datos['peso_kg'] > $lote->stock_kg + 0.01) {
            // peso devuelto > stock teórico antes del retorno: caso raro pero posible
            // (ej. cambio de envase). Aceptamos según la regla v1 pero marcamos.
            $anomalia    = true;
            $tipoAnomal  = $tipoAnomal ?? 'retorno_excede_stock';
        }

        $mov = Movimiento::create([
            'lote_id'        => $lote->id,
            'usuario_id'     => $usuario->id,
            'tipo'           => $datos['tipo'],
            'peso_kg'        => $datos['peso_kg'],
            'peso_manual'    => $datos['peso_manual'] ?? false,
            'anomalia'       => $anomalia,
            'tipo_anomalia'  => $tipoAnomal,
            'sync_uuid'      => $datos['sync_uuid'],
            'device_id'      => $datos['device_id'] ?? null,
            'device_at'      => $datos['device_at'] ?? null,
        ]);

        $lote->refresh();

        return response()->json([
            'ok'              => true,
            'movimiento_id'   => $mov->id,
            'stock_lote_kg'   => (float) $lote->stock_kg,
            'anomalia'        => $anomalia,
            'tipo_anomalia'   => $tipoAnomal,
        ]);
    }
}
