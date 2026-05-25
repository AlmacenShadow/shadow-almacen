<?php

namespace App\Http\Controllers;

use App\Models\Movimiento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MovimientoController extends Controller
{
    /** Encargado y admin operan sobre movimientos. */
    private function autorizar(): void
    {
        if (! Auth::user()->puedeUsarPanel()) {
            abort(403, 'No tienes acceso.');
        }
    }

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
            ->leftJoin('texturas', 'texturas.id', '=', 'productos.textura_id')
            ->leftJoin('usuarios', 'usuarios.id', '=', 'm.usuario_id')
            ->leftJoin('motivos_ajuste as ma', 'ma.id', '=', 'm.motivo_ajuste_id')
            ->leftJoin('movimientos as orig', 'orig.id', '=', 'm.corrige_movimiento_id')
            // existe otro movimiento que corrige a este?
            ->leftJoin('movimientos as corr', 'corr.corrige_movimiento_id', '=', 'm.id')
            ->select(
                'm.id',
                'm.created_at',
                'm.tipo',
                'm.peso_kg',
                'm.peso_manual',
                'm.anomalia',
                'm.tipo_anomalia',
                'm.nota_texto',
                'm.corrige_movimiento_id',
                'usuarios.nombre as usuario_nombre',
                'usuarios.rol as usuario_rol',
                'lotes.codigo_barcode as lote_codigo',
                'lotes.id as lote_id',
                'productos.ral',
                DB::raw('texturas.nombre as textura'),
                'productos.brillo_pct',
                'productos.nombre_interno',
                'ma.descripcion as motivo_descripcion',
                'ma.signo as motivo_signo',
                'orig.id as original_id',
                'corr.id as corregido_por_id',
            )
            ->orderByDesc('m.id');

        if ($tipo) {
            $q->where('m.tipo', $tipo);
        }

        $movimientos = $q->paginate(50)->withQueryString();

        return view('movimientos.index', compact('movimientos', 'tipo'));
    }

    /**
     * Formulario para registrar un movimiento compensatorio que corrige
     * un movimiento ya capturado.
     */
    public function corregirForm(Movimiento $movimiento): View
    {
        $this->autorizar();

        // Si ya fue corregido, no permitir corregir de nuevo
        $yaCorregido = Movimiento::where('corrige_movimiento_id', $movimiento->id)->first();

        $movimiento->load(['lote.producto.textura', 'usuario']);

        return view('movimientos.corregir', compact('movimiento', 'yaCorregido'));
    }

    /**
     * Persiste el movimiento compensatorio.
     *
     * Reglas:
     * - El compensatorio es un movimiento NUEVO (no edita el original)
     * - tipo = 'ajuste' (es una operación administrativa, no un flujo de pintor)
     * - peso_kg = mismo del original
     * - motivo_ajuste_id apunta al motivo CORRECCION_REGISTRO o _NEG según el signo necesario
     * - corrige_movimiento_id = id del original
     * - nota_texto OBLIGATORIA explicando
     */
    public function corregirStore(Request $request, Movimiento $movimiento): RedirectResponse
    {
        $this->autorizar();

        // No permitir corregir un movimiento ya corregido
        if (Movimiento::where('corrige_movimiento_id', $movimiento->id)->exists()) {
            return back()->withErrors([
                'nota_texto' => 'Este movimiento ya tiene una corrección registrada.',
            ]);
        }

        // No permitir corregir una corrección (evita cascadas)
        if ($movimiento->corrige_movimiento_id !== null) {
            return back()->withErrors([
                'nota_texto' => 'Este movimiento ya es una corrección. Si necesitas anular la corrección, pídelo al admin.',
            ]);
        }

        $data = $request->validate([
            'nota_texto' => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'nota_texto.required' => 'Tienes que explicar por qué se está corrigiendo.',
            'nota_texto.min'      => 'La explicación es muy corta (mínimo 10 caracteres).',
        ]);

        // Determinar qué motivo usar según el efecto que tenía el original
        // Original suma stock (retorno o ajuste +1) → compensar restando → signo -1
        // Original resta stock (salida o ajuste -1) → compensar sumando → signo +1
        $efectoOriginal = match ($movimiento->tipo) {
            'salida'  => -1,
            'retorno' => +1,
            'ajuste'  => (int) optional(DB::table('motivos_ajuste')->find($movimiento->motivo_ajuste_id))->signo ?: -1,
            default   => -1,
        };
        $codigoMotivo = $efectoOriginal === -1 ? 'CORRECCION_REGISTRO' : 'CORRECCION_REGISTRO_NEG';
        $motivo = DB::table('motivos_ajuste')->where('codigo', $codigoMotivo)->first();

        if (! $motivo) {
            return back()->withErrors([
                'nota_texto' => "Falta el motivo de ajuste {$codigoMotivo} en la base. Avisar al admin.",
            ]);
        }

        $correccion = Movimiento::create([
            'lote_id'               => $movimiento->lote_id,
            'usuario_id'            => Auth::id(),
            'tipo'                  => 'ajuste',
            'peso_kg'               => $movimiento->peso_kg,
            'peso_manual'           => true,
            'motivo_ajuste_id'      => $motivo->id,
            'corrige_movimiento_id' => $movimiento->id,
            'nota_texto'            => $data['nota_texto'],
            'sync_uuid'             => (string) Str::uuid(),
            'device_id'             => 'panel-web',
        ]);

        return redirect()
            ->route('lotes.show', $movimiento->lote_id)
            ->with('flash', "Corrección registrada (movimiento #{$correccion->id}). El stock del lote se actualizó.");
    }

    /**
     * Borrado duro de un movimiento. Solo admin. Requiere razón.
     * Use case: data de prueba, duplicados que sync_uuid no atrapó,
     * o casos donde la corrección compensatoria no aplica.
     *
     * Deja log en storage/logs/laravel.log para auditoría externa al sistema.
     */
    public function destroy(Request $request, Movimiento $movimiento): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->esAdmin()) {
            abort(403, 'Solo el admin puede borrar movimientos. El flujo normal es "Corregir".');
        }

        $data = $request->validate([
            'razon' => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'razon.required' => 'Tienes que dar una razón para borrar (mínimo 10 caracteres).',
            'razon.min'      => 'La razón es muy corta.',
        ]);

        // Si tiene corrección pendiente o ES corrección, refuse para que el admin
        // decida limpio: o borra ambos o ninguno.
        if (Movimiento::where('corrige_movimiento_id', $movimiento->id)->exists()) {
            return back()->withErrors([
                'razon' => 'Este movimiento tiene una corrección asociada. Borra primero la corrección o ambos en orden.',
            ]);
        }

        $loteId = $movimiento->lote_id;

        Log::warning('MOVIMIENTO_HARD_DELETE', [
            'borrado_por_usuario_id' => $user->id,
            'borrado_por_nombre'     => $user->nombre,
            'movimiento_id'          => $movimiento->id,
            'lote_id'                => $movimiento->lote_id,
            'tipo'                   => $movimiento->tipo,
            'peso_kg'                => $movimiento->peso_kg,
            'sync_uuid'              => $movimiento->sync_uuid,
            'created_at'             => $movimiento->created_at?->toIso8601String(),
            'razon'                  => $data['razon'],
        ]);

        $movimientoId = $movimiento->id;
        $movimiento->delete();

        return redirect()
            ->route('lotes.show', $loteId)
            ->with('flash', "Movimiento #{$movimientoId} eliminado. El stock se recalculó automáticamente.");
    }
}
