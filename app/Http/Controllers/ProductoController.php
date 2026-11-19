<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Textura;
use App\Support\Catalogo;
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

        $data = $this->normalizar($request);

        if ($this->duplicado($data)) {
            return back()->withErrors([
                'ral' => 'Ya existe un producto con estos mismos atributos (RAL, tipo, acabado, superficie, textura, metalizado y brillo).'
            ])->withInput();
        }

        Producto::create([...$data, 'activo' => $request->boolean('activo')]);

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

        $data = $this->normalizar($request);

        if ($this->duplicado($data, $producto->id)) {
            return back()->withErrors([
                'ral' => 'Ya existe otro producto con estos mismos atributos.'
            ])->withInput();
        }

        $producto->update([...$data, 'activo' => $request->boolean('activo')]);

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

    /** Datos compartidos entre create y edit (catálogo RAL K7). */
    private function datosDeFormulario(): array
    {
        $ralCatalogo = DB::table('ral_catalogo')->orderBy('orden')->get();
        return compact('ralCatalogo');
    }

    /** Valida, limpia y deriva la textura_id legacy a partir de los campos estructurados. */
    private function normalizar(Request $request): array
    {
        $data = $request->validate([
            'ral'               => ['required', 'string', 'max:16'],
            'tipo_pintura'      => ['nullable', Rule::in(array_keys(Catalogo::opciones('tipo_pintura')))],
            'descripcion'       => ['nullable', 'string', 'max:255'],
            'brillo_pct'        => ['required', 'integer', 'min:0', 'max:100'],
            'acabado'           => ['required', Rule::in(array_keys(Catalogo::opciones('acabado')))],
            'superficie'        => ['required', Rule::in(array_keys(Producto::SUPERFICIES))],
            'textura_tipo'      => ['nullable', Rule::in(array_keys(Catalogo::opciones('textura_tipo'))), 'required_if:superficie,texturizado'],
            'origen'            => ['nullable', Rule::in(array_keys(Catalogo::opciones('origen')))],
            'marca'             => ['nullable', 'string', 'max:80'],
            'referencia'        => ['nullable', 'string', 'max:80'],
            'curado_temp_c'     => ['nullable', 'integer', 'min:0', 'max:400'],
            'curado_tiempo_min' => ['nullable', 'integer', 'min:0', 'max:600'],
            'hex_override'      => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'stock_minimo_kg'   => ['required', 'numeric', 'min:0'],
            'stock_critico_kg'  => ['required', 'numeric', 'min:0'],
            'activo'            => ['nullable'],
            'notas'             => ['nullable', 'string', 'max:2000'],
        ], [
            'hex_override.regex'     => 'El color debe estar en formato #RRGGBB (ej: #AB12FF).',
            'textura_tipo.required_if' => 'Si la superficie es texturizada, elige el tipo de textura.',
            'acabado.required'       => 'Elige el acabado.',
            'superficie.required'    => 'Elige la superficie (lisa o texturizada).',
        ]);

        $data['metalizado'] = $request->boolean('metalizado');
        if ($data['superficie'] !== 'texturizado') {
            $data['textura_tipo'] = null;
        }
        $data['textura_id'] = $this->derivarTexturaId($data);

        return $data;
    }

    /** ¿Existe ya un producto con la misma identidad (null-aware)? */
    private function duplicado(array $data, ?int $ignoreId = null): bool
    {
        $q = Producto::query();
        foreach (['ral', 'tipo_pintura', 'acabado', 'superficie', 'textura_tipo', 'brillo_pct'] as $c) {
            $v = $data[$c] ?? null;
            is_null($v) ? $q->whereNull($c) : $q->where($c, $v);
        }
        $q->where('metalizado', $data['metalizado']);
        if ($ignoreId) {
            $q->where('id', '!=', $ignoreId);
        }
        return $q->exists();
    }

    /**
     * Deriva la textura_id legacy (una sola palabra, para vistas/PDF que aún la usan)
     * a partir de los atributos estructurados, priorizando lo más distintivo.
     */
    private function derivarTexturaId(array $data): int
    {
        if (($data['superficie'] ?? '') === 'texturizado' && !empty($data['textura_tipo'])) {
            $nombre = Catalogo::nombre('textura_tipo', $data['textura_tipo']) ?? 'Texturizado';
        } elseif (!empty($data['metalizado'])) {
            $nombre = 'Metálico';
        } elseif (($data['superficie'] ?? '') === 'texturizado') {
            $nombre = 'Texturizado';
        } else {
            $nombre = Catalogo::nombre('acabado', $data['acabado'] ?? 'mate') ?? 'Mate';
        }
        return Textura::firstOrCreate(['nombre' => $nombre], ['orden' => 100, 'activo' => true])->id;
    }
}
