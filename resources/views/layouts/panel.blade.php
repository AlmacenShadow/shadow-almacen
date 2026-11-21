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
          <a href="{{ route('buscar') }}"
             class="{{ request()->routeIs('buscar*') ? 'text-white font-semibold border-b-2 border-amber-400 pb-1' : 'text-slate-400 hover:text-white' }}">
            🔎 Buscar
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
            <a href="{{ route('configuracion.index') }}"
               class="{{ request()->routeIs('configuracion.*') ? 'text-white font-semibold border-b-2 border-amber-400 pb-1' : 'text-slate-400 hover:text-white' }}">
              Configuración
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

<script>
/*
 * Tablas ordenables: <table data-ordenable="nombre"> y <th data-orden="texto|num">.
 * Clic en el encabezado: ascendente; otro clic: descendente. El valor sale de
 * data-v de la celda (si existe) o de su texto. Vacíos siempre al final.
 * La última elección se recuerda por tabla (localStorage, si está disponible).
 */
document.querySelectorAll('table[data-ordenable]').forEach((tabla) => {
  const tbody = tabla.tBodies[0];
  const ths = [...tabla.tHead.rows[0].cells];
  const clave = 'orden-' + tabla.dataset.ordenable;
  const comparar = new Intl.Collator('es', { numeric: true, sensitivity: 'base' }).compare;

  const valor = (fila, i) => {
    const td = fila.cells[i];
    return (td?.dataset.v ?? td?.innerText ?? '').trim();
  };

  const ordenar = (i, dir) => {
    const tipo = ths[i].dataset.orden;
    const filas = [...tbody.rows];
    filas.sort((a, b) => {
      const va = valor(a, i), vb = valor(b, i);
      if (va === '' && vb === '') return 0;
      if (va === '') return 1;          // vacíos al final en ambos sentidos
      if (vb === '') return -1;
      const r = tipo === 'num' ? parseFloat(va) - parseFloat(vb) : comparar(va, vb);
      return dir === 'asc' ? r : -r;
    });
    filas.forEach(f => tbody.appendChild(f));
    ths.forEach((th, j) => {
      const flecha = th.querySelector('.flecha-orden');
      if (!flecha) return;              // columnas sin orden (color, acciones)
      flecha.textContent = j === i ? (dir === 'asc' ? '▲' : '▼') : '↕';
      flecha.classList.toggle('opacity-30', j !== i);
      th.setAttribute('aria-sort', j === i ? (dir === 'asc' ? 'ascending' : 'descending') : 'none');
    });
    try { localStorage.setItem(clave, JSON.stringify({ i, dir })); } catch (e) {}
  };

  ths.forEach((th, i) => {
    if (!th.dataset.orden) return;
    th.classList.add('cursor-pointer', 'select-none', 'hover:text-slate-800', 'whitespace-nowrap');
    th.title = 'Ordenar';
    const flecha = document.createElement('span');
    flecha.className = 'flecha-orden ml-1 opacity-30';
    flecha.textContent = '↕';
    th.appendChild(flecha);
    th.addEventListener('click', () => {
      const actual = th.getAttribute('aria-sort');
      ordenar(i, actual === 'ascending' ? 'desc' : 'asc');
    });
  });

  try {
    const guardado = JSON.parse(localStorage.getItem(clave) || 'null');
    if (guardado && ths[guardado.i]?.dataset.orden) ordenar(guardado.i, guardado.dir);
  } catch (e) {}
});
</script>
</body>
</html>
