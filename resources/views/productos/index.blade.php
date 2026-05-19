@extends('layouts.panel')
@section('title', 'Productos')

@section('content')
  <div class="flex items-center justify-between mb-5">
    <div>
      <h2 class="text-2xl font-bold text-slate-900">Productos</h2>
      <p class="text-sm text-slate-500">Cada combinación de RAL + textura + brillo es un producto distinto.</p>
    </div>
    <div class="flex items-center gap-2">
      <a href="{{ route('catalogo-ral.index') }}"
         class="text-sm text-slate-600 hover:text-slate-900 px-3 py-2 rounded border border-slate-300 bg-white">
        Catálogo K7
      </a>
      <a href="{{ route('texturas.index') }}"
         class="text-sm text-slate-600 hover:text-slate-900 px-3 py-2 rounded border border-slate-300 bg-white">
        Texturas
      </a>
      <a href="{{ route('productos.create') }}"
         class="bg-amber-500 hover:bg-amber-600 text-white font-semibold px-4 py-2 rounded-lg shadow">
        + Nuevo producto
      </a>
    </div>
  </div>

  @if ($errors->has('destroy'))
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4 text-sm">
      {{ $errors->first('destroy') }}
    </div>
  @endif

  <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    @if ($productos->isEmpty())
      <div class="p-12 text-center text-slate-500">
        <p class="text-lg">Aún no hay productos.</p>
        <p class="text-sm mt-2">Empieza con un <a href="{{ route('productos.create') }}" class="text-amber-600 underline">nuevo producto</a>.</p>
      </div>
    @else
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
          <tr>
            <th class="px-3 py-3 w-12"></th>
            <th class="text-left px-4 py-3">RAL</th>
            <th class="text-left px-4 py-3">Textura</th>
            <th class="text-right px-4 py-3">Brillo</th>
            <th class="text-left px-4 py-3">Nombre interno</th>
            <th class="text-right px-4 py-3">Stock</th>
            <th class="text-right px-4 py-3">Mín / Crítico</th>
            <th class="text-center px-4 py-3">Lotes</th>
            <th class="text-center px-4 py-3">Activo</th>
            <th></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @foreach ($productos as $p)
            @php
              $stock = (float) $p->stock_kg;
              $clase = '';
              if ($stock <= $p->stock_critico_kg) $clase = 'text-red-700 font-bold';
              elseif ($stock <= $p->stock_minimo_kg) $clase = 'text-amber-700 font-semibold';
              $tienelotes = $p->lotes_count > 0;
            @endphp
            <tr class="{{ $p->activo ? '' : 'opacity-60' }}">
              <td class="px-3 py-2">
                <span class="block w-8 h-8 rounded border border-slate-300 shadow-sm"
                      style="background-color: {{ $p->hex_resuelto }}"
                      title="{{ $p->hex_resuelto }}"></span>
              </td>
              <td class="px-4 py-3">
                <div class="font-mono font-semibold">{{ $p->ral }}</div>
                @if ($p->ral_nombre_oficial)
                  <div class="text-xs text-slate-500">{{ $p->ral_nombre_oficial }}</div>
                @endif
              </td>
              <td class="px-4 py-3">{{ $p->textura_nombre ?? '—' }}</td>
              <td class="px-4 py-3 text-right tabular-nums">{{ $p->brillo_pct }}%</td>
              <td class="px-4 py-3 text-slate-600">{{ $p->nombre_interno ?: '—' }}</td>
              <td class="px-4 py-3 text-right tabular-nums {{ $clase }}">{{ number_format($stock, 3) }} kg</td>
              <td class="px-4 py-3 text-right tabular-nums text-slate-500 text-xs">
                {{ number_format($p->stock_minimo_kg, 0) }} / {{ number_format($p->stock_critico_kg, 0) }}
              </td>
              <td class="px-4 py-3 text-center text-slate-500 tabular-nums">{{ $p->lotes_count }}</td>
              <td class="px-4 py-3 text-center">
                @if ($p->activo)
                  <span class="text-emerald-600" title="Activo">●</span>
                @else
                  <span class="text-slate-400" title="Inactivo">○</span>
                @endif
              </td>
              <td class="px-4 py-3 text-right whitespace-nowrap">
                <a href="{{ route('productos.edit', $p->id) }}" class="text-amber-600 hover:underline text-sm">editar</a>
                @if ($tienelotes)
                  <span class="text-slate-300 text-sm ml-3 cursor-help"
                        title="No se puede borrar: tiene {{ $p->lotes_count }} lote(s). Desactívalo en su lugar.">borrar</span>
                @else
                  <form method="POST" action="{{ route('productos.destroy', $p->id) }}" class="inline ml-3"
                        onsubmit="return confirm('¿Borrar definitivamente este producto?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-600 hover:underline text-sm">borrar</button>
                  </form>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div>
@endsection
