<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Textura;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductoController extends Controller
{
    /** Encargado y admin pueden gestionar productos. */
    private function autorizar(): void
    {
        if (! Auth::user()->puedeUsarPanel()) {
            abort(403, 'No tienes acceso.');
        }
    }

    public function index(): View
    {
        $this->autorizar();

        $productos = DB::table('productos')
            ->leftJoin('texturas', 'texturas.id', '=', 'productos.textura_id')
            ->leftJoin('ral_catalogo', 'ral_catalogo.codigo', '=', 'productos.ral')
            ->leftJoin('v_stock_producto', 'v_stock_producto.producto_id', '=', 'productos.id')
            ->select(
                'productos.*',
                'texturas.nombre as textura_nombre',
                'ral_catalogo.nombre_oficial as ral_nombre_oficial',
                'ral_catalogo.hex as ral_hex',
                DB::raw('COALESCE(v_stock_producto.stock_kg, 0) as stock_kg'),
                DB::raw('(SELECT COUNT(*) FROM lotes WHERE lotes.producto_id = productos.id) as lotes_count'),
            )
            ->orderBy('productos.ral')
            ->orderBy('texturas.orden')
            ->orderBy('productos.brillo_pct')
            ->get()
            ->map(function ($p) {
                $p->hex_resuelto = $p->hex_override ?: ($p->ral_hex ?: Producto::HEX_FALLBACK);
                return $p;
            });

        return view('productos.index', compact('productos'));
    }

    public function create(): View
    {
        $this->autorizar();
        return view('productos.create', $this->datosDeFormulario());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->autorizar();

        $data = $this->validar($request);

        $existe = Producto::where('ral', $data['ral'])
            ->where('textura_id', $data['textura_id'])
            ->where('brillo_pct', $data['brillo_pct'])
            ->exists();
        if ($existe) {
            return back()->withErrors([
                'ral' => 'Ya existe un producto con esta combinación de RAL + textura + brillo.'
            ])->withInput();
        }

        Producto::create([
            ...$data,
            'activo' => $request->boolean('activo'),
        ]);

        return redirect()->route('productos.index')->with('flash', 'Producto creado.');
    }

    public function edit(Producto $producto): View
    {
        $this->autorizar();
        return view('productos.edit', array_merge(
            $this->datosDeFormulario(),
            ['producto' => $producto]
        ));
    }

    public function update(Request $request, Producto $producto): RedirectResponse
    {
        $this->autorizar();

        $data = $this->validar($request);

        $existe = Producto::where('ral', $data['ral'])
            ->where('textura_id', $data['textura_id'])
            ->where('brillo_pct', $data['brillo_pct'])
            ->where('id', '!=', $producto->id)
            ->exists();
        if ($existe) {
            return back()->withErrors([
                'ral' => 'Ya existe otro producto con esta combinación.'
            ])->withInput();
        }

        $producto->update([
            ...$data,
            'activo' => $request->boolean('activo'),
        ]);

        return redirect()->route('productos.index')->with('flash', 'Producto actualizado.');
    }

    /**
     * Solo permite borrar si no tiene lotes asociados.
     * Si los tiene, sugiere desactivar.
     */
    public function destroy(Producto $producto): RedirectResponse
    {
        $this->autorizar();

        $cantLotes = $producto->lotes()->count();
        if ($cantLotes > 0) {
            return back()->withErrors([
                'destroy' => "No puedo borrar este producto: tiene {$cantLotes} lote(s) asociado(s). "
                    . "Desactívalo en lugar de borrar para conservar el historial."
            ]);
        }

        $producto->delete();
        return redirect()->route('productos.index')->with('flash', 'Producto eliminado.');
    }

    // ---- helpers ----

    /** Datos compartidos entre create y edit (texturas activas + catálogo K7). */
    private function datosDeFormulario(): array
    {
        $texturas = Textura::where('activo', true)->orderBy('orden')->orderBy('nombre')->get();
        $ralCatalogo = DB::table('ral_catalogo')->orderBy('orden')->get();
        return compact('texturas', 'ralCatalogo');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'ral'             => ['required', 'string', 'max:16'],
            'textura_id'      => ['required', 'integer', Rule::exists('texturas', 'id')->where('activo', true)],
            'brillo_pct'      => ['required', 'integer', 'min:0', 'max:100'],
            'nombre_interno'  => ['nullable', 'string', 'max:120'],
            'hex_override'    => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'stock_minimo_kg' => ['required', 'numeric', 'min:0'],
            'stock_critico_kg'=> ['required', 'numeric', 'min:0'],
            'activo'          => ['nullable'],
        ], [
            'hex_override.regex' => 'El color debe estar en formato #RRGGBB (ej: #AB12FF).',
            'textura_id.exists'  => 'La textura seleccionada no existe o está inactiva.',
        ]);
    }
}
