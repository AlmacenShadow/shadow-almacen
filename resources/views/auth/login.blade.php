<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login · Shadow Almacén</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>body { font-family: -apple-system, system-ui, sans-serif; }</style>
</head>
<body class="bg-gradient-to-br from-slate-900 to-slate-800 min-h-screen flex items-center justify-center p-6">

  <div class="w-full max-w-md">
    <div class="text-center mb-8">
      <p class="text-amber-400 font-bold tracking-[0.3em] text-sm">SHADOW · ALMACÉN</p>
      <h1 class="text-white text-2xl font-light mt-2">Panel del encargado</h1>
    </div>

    <div class="bg-white rounded-2xl shadow-2xl p-8">
      @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4 text-sm">
          {{ $errors->first() }}
        </div>
      @endif

      <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
          <label class="block text-xs uppercase tracking-wider text-slate-600 font-semibold mb-1">Email</label>
          <input
            type="email" name="email" value="{{ old('email') }}" autofocus required
            class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-400 focus:border-amber-400 outline-none">
        </div>

        <div>
          <label class="block text-xs uppercase tracking-wider text-slate-600 font-semibold mb-1">Contraseña</label>
          <input
            type="password" name="password" required
            class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-400 focus:border-amber-400 outline-none">
        </div>

        <label class="flex items-center text-sm text-slate-600">
          <input type="checkbox" name="remember" class="mr-2"> Recordarme
        </label>

        <button class="w-full bg-slate-900 hover:bg-slate-800 text-white py-3 rounded-lg font-semibold mt-2">
          Entrar
        </button>
      </form>

      <p class="text-center text-xs text-slate-400 mt-6">
        Para pintores: usen su gafete en el kiosko del almacén.
      </p>
    </div>
  </div>

</body>
</html>
