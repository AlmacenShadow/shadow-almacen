<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Credenciales no válidas.',
            ])->onlyInput('email');
        }

        // Solo encargado/admin pueden entrar al panel
        if (! Auth::user()->puedeUsarPanel()) {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Este usuario no tiene acceso al panel web.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('lotes.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
