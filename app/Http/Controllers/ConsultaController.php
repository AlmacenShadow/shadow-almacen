<?php

namespace App\Http\Controllers;

use App\Models\Lote;
use App\Models\Usuario;
use App\Support\ConsultaLotes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Buscador y mapa de lotes (solo consulta).
 *  - Panel: /buscar (encargado/admin con sesión).
 *  - Celular: /c/{token} — enlace privado (QR en el almacén). La primera vez pide
 *    escanear el gafete; se recuerda 30 días en una cookie cifrada. Cualquier
 *    usuario activo (pintor, encargado, admin) puede consultar.
 */
class ConsultaController extends Controller
{
    private const COOKIE = 'consulta_uid';
    private const COOKIE_MIN = 60 * 24 * 30;

    // ---------- Token del enlace privado ----------

    public static function token(): string
    {
        $t = DB::table('parametros')->where('clave', 'consulta.token')->value('valor');
        if (! $t) {
            $t = Str::random(40);
            DB::table('parametros')->insert(['clave' => 'consulta.token', 'valor' => $t,
                'descripcion' => 'Clave del enlace privado del buscador para celulares']);
        }
        return $t;
    }

    public static function regenerarToken(): string
    {
        self::token();
        $t = Str::random(40);
        DB::table('parametros')->where('clave', 'consulta.token')->update(['valor' => $t, 'updated_at' => now()]);
        return $t;
    }

    private function validarToken(string $token): void
    {
        abort_unless(hash_equals(self::token(), $token), 404);
    }

    private function usuarioMovil(Request $request): ?Usuario
    {
        $id = $request->cookie(self::COOKIE);
        return $id ? Usuario::where('id', (int) $id)->where('activo', true)->first() : null;
    }

    // ---------- Panel ----------

    private function autorizarPanel(): void
    {
        abort_unless(Auth::user()?->puedeUsarPanel(), 403, 'No tienes acceso.');
    }

    public function panel()
    {
        $this->autorizarPanel();
        return view('consulta.panel', [
            'urlDatos' => route('buscar.datos'),
            'urlMovs'  => route('buscar.movimientos', ['codigo' => '__CODIGO__']),
            'urlFicha' => route('buscar.ficha', ['codigo' => '__CODIGO__']),
        ]);
    }

    public function panelDatos(): JsonResponse
    {
        $this->autorizarPanel();
        return $this->datos();
    }

    public function panelMovimientos(string $codigo): JsonResponse
    {
        $this->autorizarPanel();
        return $this->movimientos($codigo);
    }

    /** Desde el buscador del panel, "ver ficha" lleva a la ficha completa del lote. */
    public function panelFicha(string $codigo)
    {
        $this->autorizarPanel();
        $lote = Lote::where('codigo_barcode', $codigo)->firstOrFail();
        return redirect()->route('lotes.show', $lote);
    }

    // ---------- Celular ----------

    public function movil(Request $request, string $token)
    {
        $this->validarToken($token);
        $u = $this->usuarioMovil($request);
        $resp = $u
            ? response()->view('consulta.movil', [
                'usuario'  => $u,
                'token'    => $token,
                'urlDatos' => route('consulta.datos', $token),
                'urlMovs'  => route('consulta.movimientos', [$token, '__CODIGO__']),
            ])
            : response()->view('consulta.identificar', ['token' => $token]);
        return $resp->header('X-Robots-Tag', 'noindex, nofollow')->header('Cache-Control', 'no-store');
    }

    public function movilIdentificar(Request $request, string $token)
    {
        $this->validarToken($token);
        $codigo = strtoupper(trim(str_replace("'", '-', (string) $request->input('codigo'))));
        $u = Usuario::where('codigo_barcode', $codigo)->where('activo', true)->first();
        if (! $u) {
            return back()->withErrors(['codigo' => "No reconozco el gafete «{$codigo}» o el usuario está inactivo."]);
        }
        return redirect()->route('consulta.movil', $token)
            ->withCookie(cookie(self::COOKIE, (string) $u->id, self::COOKIE_MIN, null, null, $request->isSecure(), true, false, 'lax'));
    }

    public function movilSalir(Request $request, string $token)
    {
        $this->validarToken($token);
        return redirect()->route('consulta.movil', $token)->withCookie(cookie()->forget(self::COOKIE));
    }

    public function movilDatos(Request $request, string $token): JsonResponse
    {
        $this->validarToken($token);
        abort_unless($this->usuarioMovil($request), 401);
        return $this->datos();
    }

    public function movilMovimientos(Request $request, string $token, string $codigo): JsonResponse
    {
        $this->validarToken($token);
        abort_unless($this->usuarioMovil($request), 401);
        return $this->movimientos($codigo);
    }

    // ---------- Comunes ----------

    private function datos(): JsonResponse
    {
        return response()->json([
            'lotes'    => ConsultaLotes::lotes(),
            'banderas' => ConsultaLotes::banderas(),
        ])->header('Cache-Control', 'no-store');
    }

    private function movimientos(string $codigo): JsonResponse
    {
        $lote = Lote::where('codigo_barcode', $codigo)->first();
        abort_unless($lote, 404);
        return response()->json(['movimientos' => ConsultaLotes::movimientos($lote->id)]);
    }
}
