<?php

namespace App\Http\Controllers;

use App\Models\CatalogoOpcion;
use App\Models\Producto;
use App\Support\Parametros;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Configuración (solo admin): catálogos editables (tipos de pintura, acabados,
 * tipos de textura, países, proveedores), colores personalizados y parámetros.
 */
class ConfiguracionController extends Controller
{
    private const SECCIONES = ['tipo_pintura', 'acabado', 'textura_tipo', 'origen', 'proveedor', 'ubicacion', 'colores', 'parametros'];

    private function autorizar(): void
    {
        if (! Auth::user()->esAdmin()) {
            abort(403, 'Solo el administrador puede cambiar la configuración.');
        }
    }

    public function index(Request $request): View
    {
        $this->autorizar();
        $seccion = in_array($request->query('s'), self::SECCIONES, true) ? $request->query('s') : 'tipo_pintura';

        $datos = ['seccion' => $seccion, 'grupos' => CatalogoOpcion::GRUPOS];

        if (isset(CatalogoOpcion::GRUPOS[$seccion])) {
            $datos['opciones'] = CatalogoOpcion::where('grupo', $seccion)->orderBy('orden')->orderBy('nombre')->get()
                ->each(fn ($o) => $o->num_usos = $o->usos());
        } elseif ($seccion === 'colores') {
            $datos['colores'] = DB::table('ral_catalogo')->where('personalizado', true)->orderBy('codigo')->get()
                ->each(fn ($c) => $c->num_usos = Producto::where('ral', $c->codigo)->count());
        } else {
            $datos['parametros'] = collect(Parametros::EDITABLES)
                ->map(fn ($def, $clave) => $def + ['clave' => $clave, 'valor' => Parametros::get($clave)]);
        }

        return view('configuracion.index', $datos);
    }

    // ---------- Catálogos ----------

    public function opcionStore(Request $request, string $grupo): RedirectResponse
    {
        $this->autorizar();
        abort_unless(isset(CatalogoOpcion::GRUPOS[$grupo]), 404);

        $data = $this->validarOpcion($request, $grupo);
        $opcion = new CatalogoOpcion($data + ['grupo' => $grupo]);
        $opcion->clave = $this->claveUnica($grupo, $data['nombre']);
        $opcion->orden = $data['orden'] ?? ((int) CatalogoOpcion::where('grupo', $grupo)->max('orden') + 10);
        $aviso = $this->aplicarBandera($request, $opcion);
        $opcion->save();

        return $this->volver($grupo, "«{$opcion->nombre}» agregado." . $aviso);
    }

    public function opcionUpdate(Request $request, CatalogoOpcion $opcion): RedirectResponse
    {
        $this->autorizar();

        $data = $this->validarOpcion($request, $opcion->grupo, $opcion->id);
        $isoAntes = $opcion->iso;
        $opcion->fill($data);
        $opcion->activo = $request->boolean('activo');
        $aviso = $this->aplicarBandera($request, $opcion, $isoAntes !== $opcion->iso || $request->boolean('refrescar_bandera'));
        $opcion->save();

        return $this->volver($opcion->grupo, "«{$opcion->nombre}» actualizado." . $aviso);
    }

    public function opcionDestroy(CatalogoOpcion $opcion): RedirectResponse
    {
        $this->autorizar();

        if (($usos = $opcion->usos()) > 0) {
            $que = in_array($opcion->grupo, ['proveedor', 'ubicacion'], true) ? 'lote(s)' : 'producto(s)';
            return $this->volver($opcion->grupo, null,
                "No puedo borrar «{$opcion->nombre}»: lo usan {$usos} {$que}. Desactívalo para que no aparezca en los formularios.");
        }
        $opcion->delete();

        return $this->volver($opcion->grupo, "«{$opcion->nombre}» eliminado.");
    }

    private function validarOpcion(Request $request, string $grupo, ?int $ignorarId = null): array
    {
        $reglas = [
            'nombre' => ['required', 'string', 'max:120',
                Rule::unique('catalogo_opciones', 'nombre')->where('grupo', $grupo)->ignore($ignorarId)],
            'orden'  => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
        if ($grupo === 'tipo_pintura') {
            $reglas['sigla'] = ['required', 'string', 'max:5', 'regex:/^[A-Za-z0-9]+$/'];
        }
        if ($grupo === 'origen') {
            $reglas['iso']     = ['nullable', 'string', 'size:2', 'alpha'];
            $reglas['archivo'] = ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:200'];
        }

        $data = $request->validate($reglas, [
            'nombre.unique' => 'Ya existe una opción con ese nombre.',
            'sigla.regex'   => 'La sigla solo puede tener letras y números.',
            'iso.size'      => 'El código ISO tiene 2 letras (ej: MX, ES, DE).',
            'archivo.max'   => 'La imagen de la bandera no puede pasar de 200 KB.',
        ]);
        if (isset($data['sigla'])) $data['sigla'] = mb_strtoupper($data['sigla']);
        if (isset($data['iso']))   $data['iso'] = mb_strtolower($data['iso']);
        unset($data['archivo']);
        if (!array_key_exists('orden', $data) || $data['orden'] === null) unset($data['orden']);

        return $data;
    }

    /**
     * Bandera de un país: archivo subido > descarga por código ISO (flagcdn.com).
     * Devuelve un aviso si no se pudo conseguir.
     */
    private function aplicarBandera(Request $request, CatalogoOpcion $opcion, bool $refrescar = false): string
    {
        if ($opcion->grupo !== 'origen') return '';

        if ($request->hasFile('archivo')) {
            $f = $request->file('archivo');
            $mime = $f->getClientOriginalExtension() === 'svg' ? 'image/svg+xml' : $f->getMimeType();
            $opcion->bandera = "data:{$mime};base64," . base64_encode(file_get_contents($f->getRealPath()));
            return '';
        }

        if ($opcion->iso && ($refrescar || !$opcion->bandera)) {
            try {
                $r = Http::timeout(8)->get("https://flagcdn.com/w80/{$opcion->iso}.png");
                if ($r->successful() && str_starts_with((string) $r->header('Content-Type'), 'image/')) {
                    $opcion->bandera = 'data:image/png;base64,' . base64_encode($r->body());
                    return '';
                }
            } catch (\Throwable $e) {
                // sin conexión o código inválido: se avisa abajo
            }
            return " ⚠ No pude descargar la bandera para «{$opcion->iso}»; revisa el código o sube la imagen.";
        }

        return '';
    }

    private function claveUnica(string $grupo, string $nombre): string
    {
        $base = Str::slug($nombre, '_') ?: 'opcion';
        $base = Str::limit($base, 50, '');
        $clave = $base;
        for ($i = 2; CatalogoOpcion::where('grupo', $grupo)->where('clave', $clave)->exists(); $i++) {
            $clave = "{$base}_{$i}";
        }
        return $clave;
    }

    private function volver(string $seccion, ?string $ok, ?string $error = null): RedirectResponse
    {
        $r = redirect()->route('configuracion.index', ['s' => $seccion]);
        if ($ok)    $r->with('flash', $ok);
        if ($error) $r->withErrors(['config' => $error]);
        return $r;
    }

    // ---------- Colores personalizados ----------

    public function colorStore(Request $request): RedirectResponse
    {
        $this->autorizar();
        $data = $this->validarColor($request);
        DB::table('ral_catalogo')->insert($data + ['grupo' => 'Personalizado', 'orden' => 9000, 'personalizado' => true]);
        return $this->volver('colores', "Color {$data['codigo']} agregado.");
    }

    public function colorUpdate(Request $request, string $codigo): RedirectResponse
    {
        $this->autorizar();
        $this->colorPersonalizado($codigo);
        $data = $this->validarColor($request, $codigo);
        unset($data['codigo']); // el código no cambia: lo usan los productos
        DB::table('ral_catalogo')->where('codigo', $codigo)->update($data);
        return $this->volver('colores', "Color {$codigo} actualizado.");
    }

    public function colorDestroy(string $codigo): RedirectResponse
    {
        $this->autorizar();
        $this->colorPersonalizado($codigo);
        if (($usos = Producto::where('ral', $codigo)->count()) > 0) {
            return $this->volver('colores', null, "No puedo borrar {$codigo}: lo usan {$usos} producto(s).");
        }
        DB::table('ral_catalogo')->where('codigo', $codigo)->delete();
        return $this->volver('colores', "Color {$codigo} eliminado.");
    }

    private function colorPersonalizado(string $codigo): void
    {
        abort_unless(DB::table('ral_catalogo')->where('codigo', $codigo)->where('personalizado', true)->exists(), 404);
    }

    private function validarColor(Request $request, ?string $ignorar = null): array
    {
        $request->merge(['codigo' => mb_strtoupper(trim((string) $request->input('codigo', $ignorar)))]);
        $data = $request->validate([
            'codigo'         => ['required', 'string', 'max:16', 'regex:/^[A-Z0-9\-]+$/',
                                 Rule::unique('ral_catalogo', 'codigo')->ignore($ignorar, 'codigo')],
            'nombre_oficial' => ['required', 'string', 'max:120'],
            'hex'            => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ], [
            'codigo.unique' => 'Ese código ya existe (en el catálogo K7 o en tus colores).',
            'codigo.regex'  => 'El código solo puede tener letras, números y guiones (ej: UPHG5000).',
            'hex.regex'     => 'El color debe estar en formato #RRGGBB.',
        ]);
        $data['hex'] = mb_strtoupper($data['hex']);
        return $data;
    }

    // ---------- Parámetros ----------

    public function parametrosUpdate(Request $request): RedirectResponse
    {
        $this->autorizar();
        $reglas = [];
        foreach (Parametros::EDITABLES as $clave => $def) {
            $reglas[str_replace('.', '__', $clave)] = ['required', 'numeric', "min:{$def['min']}", "max:{$def['max']}"];
        }
        $data = $request->validate($reglas);
        foreach ($data as $campo => $valor) {
            Parametros::set(str_replace('__', '.', $campo), (string) $valor);
        }
        return $this->volver('parametros', 'Parámetros guardados.');
    }
}
