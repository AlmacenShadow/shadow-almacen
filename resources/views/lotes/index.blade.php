@extends('layouts.panel')
@section('title', 'Lotes')

@section('content')
  <div class="flex items-center justify-between mb-5">
    <div>
      <h2 class="text-2xl font-bold text-slate-900">Lotes en almacén</h2>
      <p class="text-sm text-slate-500">Stock actual derivado de movimientos</p>
    </div>
    <a href="{{ route('lotes.create') }}"
       class="bg-amber-500 hover:bg-amber-600 text-white font-semibold px-4 py-2 rounded-lg shadow">
      + Nueva recepción
    </a>
  </div>

  <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    @if ($lotes->isEmpty())
      <div class="p-12 text-center text-slate-500">
        <p class="text-lg">Aún no hay lotes registrados.</p>
        <p class="text-sm mt-2">Empieza con una <a href="{{ route('lotes.create') }}" class="text-amber-600 underline">nueva recepción</a>.</p>
      </div>
    @else
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
          <tr>
            <th class="px-3 py-3 w-10"></th>
            <th class="text-left px-4 py-3">Código</th>
            <th class="text-left px-4 py-3">Producto</th>
            <th class="text-right px-4 py-3">Recepción</th>
            <th class="text-right px-4 py-3">Vence</th>
            <th class="text-right px-4 py-3">Cajas</th>
            <th class="text-right px-4 py-3">Recibido</th>
            <th class="text-right px-4 py-3">Stock actual</th>
            <th></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @foreach ($lotes as $l)
            @php
              $consumido = (float) $l->peso_total_recepcionado_kg - (float) $l->stock_kg;
              $pct = $l->peso_total_recepcionado_kg > 0
                  ? max(0, min(100, 100 * $l->stock_kg / $l->peso_total_recepcionado_kg))
                  : 0;
              $color = $pct < 20 ? 'bg-red-500' : ($pct < 50 ? 'bg-amber-500' : 'bg-emerald-500');
            @endphp
            <tr>
              <td class="px-3 py-2">
                <span class="block w-8 h-8 rounded border border-slate-300 shadow-sm"
                      style="background-color: {{ $l->hex_resuelto ?? '#cbd5e1' }}"
                      title="{{ $l->hex_resuelto ?? '' }}"></span>
              </td>
              <td class="px-4 py-3 font-mono whitespace-nowrap">
                {{ $l->codigo_barcode }}
                @if ($l->propietario_externo)
                  <x-propietario-alerta :nombre="$l->propietario_nombre" class="w-4 h-4 ml-1" />
                @endif
                @if ($l->notas)
                  <span class="cursor-help text-slate-400 ml-1" title="{{ $l->notas }}">📝</span>
                @endif
              </td>
              <td class="px-4 py-3">
                <x-producto-resumen :p="$l" />
                @if ($l->descripcion)
                  <div class="text-xs text-slate-500 mt-0.5">{{ $l->descripcion }}</div>
                @endif
              </td>
              <td class="px-4 py-3 text-right tabular-nums">@fecha($l->fecha_recepcion)</td>
              <td class="px-4 py-3 text-right tabular-nums text-slate-500">
                @fecha($l->fecha_vencimiento)
              </td>
              <td class="px-4 py-3 text-right tabular-nums">{{ $l->cantidad_cajas }}</td>
              <td class="px-4 py-3 text-right tabular-nums">@kg($l->peso_total_recepcionado_kg) kg</td>
              <td class="px-4 py-3 text-right">
                <div class="font-semibold tabular-nums">@kg($l->stock_kg) kg</div>
                <div class="w-32 ml-auto bg-slate-200 rounded-full h-1.5 mt-1">
                  <div class="{{ $color }} h-1.5 rounded-full" style="width: {{ $pct }}%"></div>
                </div>
              </td>
              <td class="px-4 py-3 text-right">
                <a href="{{ route('lotes.show', $l->id) }}" class="text-amber-600 hover:underline text-sm">ver</a>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div>
@endsection
