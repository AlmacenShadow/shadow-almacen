@extends('layouts.panel')
@section('title', 'Catálogo RAL Classic K7')

@section('content')
  <div class="flex items-center justify-between mb-5">
    <div>
      <h2 class="text-2xl font-bold text-slate-900">Catálogo RAL Classic K7</h2>
      <p class="text-sm text-slate-500">213 colores estándar — referencia y consulta.</p>
    </div>
    <a href="{{ route('productos.index') }}" class="text-sm text-slate-600 hover:text-slate-900">← volver a productos</a>
  </div>

  {{-- Filtros --}}
  <form method="GET" action="{{ route('catalogo-ral.index') }}" class="flex flex-wrap items-end gap-3 mb-5">
    <div class="flex-1 min-w-64">
      <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Buscar</label>
      <input type="text" name="q" value="{{ $q }}" placeholder="RAL9005, negro, amarillo…"
             class="w-full px-3 py-2 border border-slate-300 rounded-lg">
    </div>
    <div>
      <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Grupo</label>
      <select name="grupo" class="px-3 py-2 border border-slate-300 rounded-lg">
        <option value="">Todos</option>
        @foreach ($grupos as $g)
          <option value="{{ $g }}" @selected($grupo === $g)>{{ $g }}</option>
        @endforeach
      </select>
    </div>
    <button class="bg-slate-900 text-white px-4 py-2 rounded-lg font-semibold">Filtrar</button>
    @if ($q || $grupo)
      <a href="{{ route('catalogo-ral.index') }}" class="text-sm text-slate-600 hover:text-slate-900 px-2 py-2">limpiar</a>
    @endif
    <span class="ml-auto text-sm text-slate-500">{{ $colores->count() }} colores</span>
  </form>

  {{-- Grid de colores --}}
  @if ($colores->isEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center text-slate-500">
      <p>Sin resultados para esos criterios.</p>
    </div>
  @else
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3">
      @foreach ($colores as $c)
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
          <div class="aspect-square" style="background-color: {{ $c->hex }}" title="{{ $c->hex }}"></div>
          <div class="p-2 text-center">
            <div class="font-mono font-semibold text-sm">{{ $c->codigo }}</div>
            <div class="text-xs text-slate-500 truncate" title="{{ $c->nombre_oficial }}">{{ $c->nombre_oficial }}</div>
            <div class="text-[10px] text-slate-400 font-mono">{{ $c->hex }}</div>
          </div>
        </div>
      @endforeach
    </div>
  @endif
@endsection
