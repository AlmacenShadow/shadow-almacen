@extends('layouts.panel')
@section('title', 'Productos')

@section('content')
  <div class="flex items-center justify-between mb-5">
    <div>
      <h2 class="text-2xl font-bold text-slate-900">Productos</h2>
      <p class="text-sm text-slate-500">Cada combinación de RAL + textura + brillo es un producto distinto.</p>
    </div>
    <a href="{{ route('productos.create') }}"
       class="bg-amber-500 hover:bg-amber-600 text-white font-semibold px-4 py-2 rounded-lg shadow">
      + Nuevo producto
    </a>
  </div>

  <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
        <tr>
          <th class="text-left px-5 py-3">RAL</th>
          <th class="text-left px-5 py-3">Textura</th>
          <th class="text-right px-5 py-3">Brillo</th>
          <th class="text-left px-5 py-3">Nombre interno</th>
          <th class="text-right px-5 py-3">Stock actual</th>
          <th class="text-right px-5 py-3">Mínimo</th>
          <th class="text-right px-5 py-3">Crítico</th>
          <th class="text-center px-5 py-3">Activo</th>
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
          @endphp
          <tr>
            <td class="px-5 py-3 font-mono font-semibold">{{ $p->ral }}</td>
            <td class="px-5 py-3">{{ $p->textura }}</td>
            <td class="px-5 py-3 text-right tabular-nums">{{ $p->brillo_pct }}%</td>
            <td class="px-5 py-3 text-slate-600">{{ $p->nombre_interno ?: '—' }}</td>
            <td class="px-5 py-3 text-right tabular-nums {{ $clase }}">{{ number_format($stock, 3) }} kg</td>
            <td class="px-5 py-3 text-right tabular-nums text-slate-500">{{ number_format($p->stock_minimo_kg, 0) }}</td>
            <td class="px-5 py-3 text-right tabular-nums text-slate-500">{{ number_format($p->stock_critico_kg, 0) }}</td>
            <td class="px-5 py-3 text-center">
              @if ($p->activo)
                <span class="text-emerald-600">●</span>
              @else
                <span class="text-slate-400">○</span>
              @endif
            </td>
            <td class="px-5 py-3 text-right">
              <a href="{{ route('productos.edit', $p->id) }}" class="text-amber-600 hover:underline text-sm">editar</a>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endsection
