<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta name="robots" content="noindex, nofollow">
  <meta name="referrer" content="no-referrer">
  <meta name="theme-color" content="#0f172a">
  <title>@yield('title', 'Almacén') · Shadow</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>body { font-family: -apple-system, system-ui, sans-serif; -webkit-tap-highlight-color: transparent; }</style>
  @stack('head')
</head>
<body class="bg-slate-100 min-h-screen">
  <header class="bg-slate-900 text-white px-4 py-3 flex items-center justify-between sticky top-0 z-40">
    <span class="font-bold tracking-wider text-amber-400 text-sm">SHADOW · ALMACÉN</span>
    @yield('header-derecha')
  </header>
  <main class="p-3 max-w-3xl mx-auto">
    @yield('content')
  </main>
</body>
</html>
