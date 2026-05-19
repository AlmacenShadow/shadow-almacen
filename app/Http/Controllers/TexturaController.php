<?php

namespace App\Http\Controllers;

use App\Models\Textura;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TexturaController extends Controller
{
    private function autorizar(): void
    {
        if (! Auth::user()->puedeUsarPanel()) {
            abort(403, 'No tienes acceso.');
        }
    }

    public function index(): View
    {
        $this->autorizar();

        $texturas = DB::table('texturas')
            ->select(
                'texturas.*',
                DB::raw('(SELECT COUNT(*) FROM productos WHERE productos.textura_id = texturas.id) as productos_count'),
            )
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        return view('texturas.index', compact('texturas'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->autorizar();

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:40', 'unique:texturas,nombre'],
            'orden'  => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        Textura::create([
            'nombre' => $data['nombre'],
            'orden'  => $data['orden'] ?? 100,
            'activo' => true,
        ]);

        return redirect()->route('texturas.index')->with('flash', 'Textura creada.');
    }

    public function update(Request $request, Textura $textura): RedirectResponse
    {
        $this->autorizar();

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:40', Rule::unique('texturas', 'nombre')->ignore($textura->id)],
            'orden'  => ['nullable', 'integer', 'min:0', 'max:9999'],
            'activo' => ['nullable'],
        ]);

        $textura->update([
            'nombre' => $data['nombre'],
            'orden'  => $data['orden'] ?? $textura->orden,
            'activo' => $request->boolean('activo'),
        ]);

        return redirect()->route('texturas.index')->with('flash', 'Textura actualizada.');
    }

    public function destroy(Textura $textura): RedirectResponse
    {
        $this->autorizar();

        $cant = $textura->productos()->count();
        if ($cant > 0) {
            return back()->withErrors([
                'destroy' => "No puedo borrar la textura «{$textura->nombre}»: está en uso por {$cant} producto(s). "
                    . "Desactívala en lugar de borrarla."
            ]);
        }

        $textura->delete();
        return redirect()->route('texturas.index')->with('flash', 'Textura eliminada.');
    }
}
