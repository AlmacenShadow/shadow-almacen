<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            ->leftJoin('v_stock_producto', 'v_stock_producto.producto_id', '=', 'productos.id')
            ->select(
                'productos.*',
                DB::raw('COALESCE(v_stock_producto.stock_kg, 0) as stock_kg')
            )
            ->orderBy('productos.ral')
            ->orderBy('productos.textura')
            ->orderBy('productos.brillo_pct')
            ->get();

        return view('productos.index', compact('productos'));
    }

    public function create(): View
    {
        $this->autorizar();
        return view('productos.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->autorizar();

        $data = $request->validate([
            'ral'             => ['required', 'string', 'max:16'],
            'textura'         => ['required', 'string', 'max:40'],
            'brillo_pct'      => ['required', 'integer', 'min:0', 'max:100'],
            'nombre_interno'  => ['nullable', 'string', 'max:120'],
            'stock_minimo_kg' => ['required', 'numeric', 'min:0'],
            'stock_critico_kg'=> ['required', 'numeric', 'min:0'],
            'activo'          => ['nullable'],
        ]);

        // Unicidad por combinación
        $existe = Producto::where('ral', $data['ral'])
            ->where('textura', $data['textura'])
            ->where('brillo_pct', $data['brillo_pct'])
            ->exists();
        if ($existe) {
            return back()->withErrors([
                'ral' => "Ya existe un producto con esta combinación de RAL + textura + brillo."
            ])->withInput();
        }

        Producto::create([
            ...$data,
            'activo' => $request->has('activo'),
        ]);

        return redirect()->route('productos.index')->with('flash', 'Producto creado.');
    }

    public function edit(Producto $producto): View
    {
        $this->autorizar();
        return view('productos.edit', compact('producto'));
    }

    public function update(Request $request, Producto $producto): RedirectResponse
    {
        $this->autorizar();

        $data = $request->validate([
            'ral'             => ['required', 'string', 'max:16'],
            'textura'         => ['required', 'string', 'max:40'],
            'brillo_pct'      => ['required', 'integer', 'min:0', 'max:100'],
            'nombre_interno'  => ['nullable', 'string', 'max:120'],
            'stock_minimo_kg' => ['required', 'numeric', 'min:0'],
            'stock_critico_kg'=> ['required', 'numeric', 'min:0'],
            'activo'          => ['nullable'],
        ]);

        // Unicidad — excluir el actual
        $existe = Producto::where('ral', $data['ral'])
            ->where('textura', $data['textura'])
            ->where('brillo_pct', $data['brillo_pct'])
            ->where('id', '!=', $producto->id)
            ->exists();
        if ($existe) {
            return back()->withErrors([
                'ral' => "Ya existe otro producto con esta combinación."
            ])->withInput();
        }

        $producto->update([
            ...$data,
            'activo' => $request->has('activo'),
        ]);

        return redirect()->route('productos.index')->with('flash', 'Producto actualizado.');
    }
}
