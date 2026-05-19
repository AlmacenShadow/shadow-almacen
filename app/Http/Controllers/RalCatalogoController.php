<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Vista solo-lectura del catálogo RAL Classic K7.
 * Útil como referencia y para verificar el color asociado a un código.
 */
class RalCatalogoController extends Controller
{
    public function index(Request $request): View
    {
        if (! Auth::user()->puedeUsarPanel()) {
            abort(403, 'No tienes acceso.');
        }

        $grupo = $request->query('grupo');
        $q = trim((string) $request->query('q', ''));

        $query = DB::table('ral_catalogo');

        if ($grupo) {
            $query->where('grupo', $grupo);
        }
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('codigo', 'like', "%{$q}%")
                  ->orWhere('nombre_oficial', 'like', "%{$q}%");
            });
        }

        $colores = $query->orderBy('orden')->get();
        $grupos = DB::table('ral_catalogo')->select('grupo')->distinct()->orderBy('grupo')->pluck('grupo');

        return view('catalogo-ral.index', compact('colores', 'grupos', 'grupo', 'q'));
    }
}
