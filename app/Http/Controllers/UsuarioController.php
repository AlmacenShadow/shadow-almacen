<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    /** Solo admin puede tocar usuarios. */
    private function autorizar(): void
    {
        if (! Auth::user()->esAdmin()) {
            abort(403, 'Solo el administrador puede gestionar usuarios.');
        }
    }

    /** Sugiere el próximo código secuencial para cada rol (PNT-0001, ENC-0001, ADM-0001). */
    private function siguienteCodigo(string $rol): string
    {
        $prefijos = ['pintor' => 'PNT-', 'encargado' => 'ENC-', 'admin' => 'ADM-'];
        $prefijo = $prefijos[$rol];

        $max = DB::table('usuarios')
            ->where('codigo_barcode', 'LIKE', $prefijo . '%')
            ->selectRaw('MAX(CAST(SUBSTRING(codigo_barcode, ' . (strlen($prefijo) + 1) . ') AS UNSIGNED)) as ultimo')
            ->value('ultimo');

        $siguiente = ((int) $max) + 1;
        return $prefijo . str_pad($siguiente, 4, '0', STR_PAD_LEFT);
    }

    public function index(): View
    {
        $this->autorizar();

        $usuarios = Usuario::orderBy('rol')->orderBy('nombre')->get();

        return view('usuarios.index', compact('usuarios'));
    }

    public function create(): View
    {
        $this->autorizar();

        $sugerencias = [
            'pintor'    => $this->siguienteCodigo('pintor'),
            'encargado' => $this->siguienteCodigo('encargado'),
            'admin'     => $this->siguienteCodigo('admin'),
        ];

        return view('usuarios.create', compact('sugerencias'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->autorizar();

        $data = $request->validate([
            'nombre'         => ['required', 'string', 'max:120'],
            'rol'            => ['required', 'in:pintor,encargado,admin'],
            'codigo_barcode' => ['required', 'string', 'max:32', 'unique:usuarios,codigo_barcode'],
            'email'          => ['nullable', 'email', 'max:120', 'unique:usuarios,email'],
            'password'       => ['nullable', 'string', 'min:6'],
            'activo'         => ['nullable'],
        ]);

        // Pintor no necesita email/password; encargado y admin sí
        if ($data['rol'] !== 'pintor') {
            $request->validate([
                'email'    => ['required', 'email'],
                'password' => ['required', 'string', 'min:6'],
            ]);
        }

        Usuario::create([
            'nombre'         => $data['nombre'],
            'rol'            => $data['rol'],
            'codigo_barcode' => $data['codigo_barcode'],
            'email'          => $data['email'] ?: null,
            'password_hash'  => !empty($data['password']) ? Hash::make($data['password']) : null,
            'activo'         => $request->has('activo'),
        ]);

        return redirect()->route('usuarios.index')->with('flash', "Usuario {$data['nombre']} creado.");
    }

    public function edit(Usuario $usuario): View
    {
        $this->autorizar();
        return view('usuarios.edit', compact('usuario'));
    }

    /** Hoja imprimible con los códigos de barcode de todos los usuarios activos. */
    public function tablero(): View
    {
        $this->autorizar();

        $pintores  = Usuario::where('rol', 'pintor')->where('activo', true)->orderBy('codigo_barcode')->get();
        $personal  = Usuario::whereIn('rol', ['encargado', 'admin'])->where('activo', true)->orderBy('codigo_barcode')->get();

        return view('usuarios.tablero', compact('pintores', 'personal'));
    }

    public function update(Request $request, Usuario $usuario): RedirectResponse
    {
        $this->autorizar();

        $data = $request->validate([
            'nombre'         => ['required', 'string', 'max:120'],
            'rol'            => ['required', 'in:pintor,encargado,admin'],
            'codigo_barcode' => ['required', 'string', 'max:32', 'unique:usuarios,codigo_barcode,' . $usuario->id],
            'email'          => ['nullable', 'email', 'max:120', 'unique:usuarios,email,' . $usuario->id],
            'password'       => ['nullable', 'string', 'min:6'],
            'activo'         => ['nullable'],
        ]);

        if ($data['rol'] !== 'pintor' && empty($usuario->email) && empty($data['email'])) {
            return back()->withErrors(['email' => 'Encargado y admin requieren email.'])->withInput();
        }

        $update = [
            'nombre'         => $data['nombre'],
            'rol'            => $data['rol'],
            'codigo_barcode' => $data['codigo_barcode'],
            'email'          => $data['email'] ?: null,
            'activo'         => $request->has('activo'),
        ];

        if (! empty($data['password'])) {
            $update['password_hash'] = Hash::make($data['password']);
        }

        $usuario->update($update);

        return redirect()->route('usuarios.index')->with('flash', "Usuario {$usuario->nombre} actualizado.");
    }
}
