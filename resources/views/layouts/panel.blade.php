<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Panel') · Shadow Almacén</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>body { font-family: -apple-system, system-ui, sans-serif; }</style>
</head>
<body class="bg-slate-100 min-h-screen">

  <nav class="bg-slate-900 text-white px-6 py-3 shadow">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
      <div class="flex items-center gap-8">
        <a href="{{ route('lotes.index') }}" class="font-bold tracking-wider text-amber-400">SHADOW · ALMACÉN</a>
        <div class="flex gap-5 text-sm">
          <a href="{{ route('lotes.index') }}"
             class="{{ request()->routeIs('lotes.*') ? 'text-white font-semibold border-b-2 border-amber-400 pb-1' : 'text-slate-400 hover:text-white' }}">
            Lotes
          </a>
          <a href="{{ route('lotes.create') }}"
             class="{{ request()->routeIs('lotes.create') ? 'text-white font-semibold border-b-2 border-amber-400 pb-1' : 'text-slate-400 hover:text-white' }}">
            Nueva recepción
          </a>
          <a href="{{ route('movimientos.index') }}"
             class="{{ request()->routeIs('movimientos.*') ? 'text-white font-semibold border-b-2 border-amber-400 pb-1' : 'text-slate-400 hover:text-white' }}">
            Movimientos
          </a>
          <a href="{{ route('productos.index') }}"
             class="{{ request()->routeIs('productos.*') ? 'text-white font-semibold border-b-2 border-amber-400 pb-1' : 'text-slate-400 hover:text-white' }}">
            Productos
          </a>
          <a href="{{ route('reportes.index') }}"
             class="{{ request()->routeIs('reportes.*') ? 'text-white font-semibold border-b-2 border-amber-400 pb-1' : 'text-slate-400 hover:text-white' }}">
            Reportes
          </a>
          @if (auth()->user()->esAdmin())
            <a href="{{ route('usuarios.index') }}"
               class="{{ request()->routeIs('usuarios.*') ? 'text-white font-semibold border-b-2 border-amber-400 pb-1' : 'text-slate-400 hover:text-white' }}">
              Usuarios
            </a>
          @endif
        </div>
      </div>
      <div class="flex items-center gap-4 text-sm">
        <span class="text-slate-300">{{ auth()->user()->nombre }} · {{ ucfirst(auth()->user()->rol) }}</span>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button class="bg-slate-700 hover:bg-slate-600 px-3 py-1 rounded text-xs">Salir</button>
        </form>
      </div>
    </div>
  </nav>

  @if (session('flash'))
    <div class="max-w-7xl mx-auto mt-4 px-6">
      <div class="bg-emerald-100 border border-emerald-300 text-emerald-800 px-4 py-3 rounded">
        {{ session('flash') }}
      </div>
    </div>
  @endif

  <main class="max-w-7xl mx-auto p-6">
    @yield('content')
  </main>

</body>
</html>
