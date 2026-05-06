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
            <th class="text-left px-5 py-3">Código</th>
            <th class="text-left px-5 py-3">Producto</th>
            <th class="text-right px-5 py-3">Recepción</th>
            <th class="text-right px-5 py-3">Vence</th>
            <th class="text-right px-5 py-3">Cajas</th>
            <th class="text-right px-5 py-3">Recibido</th>
            <th class="text-right px-5 py-3">Stock actual</th>
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
              <td class="px-5 py-3 font-mono">{{ $l->codigo_barcode }}</td>
              <td class="px-5 py-3">
                <div class="font-medium">{{ $l->ral }} · {{ $l->textura }} · {{ $l->brillo_pct }}%</div>
                @if ($l->nombre_interno)
                  <div class="text-xs text-slate-500">{{ $l->nombre_interno }}</div>
                @endif
              </td>
              <td class="px-5 py-3 text-right tabular-nums">{{ \Illuminate\Support\Carbon::parse($l->fecha_recepcion)->format('Y-m-d') }}</td>
              <td class="px-5 py-3 text-right tabular-nums text-slate-500">
                {{ $l->fecha_vencimiento ? \Illuminate\Support\Carbon::parse($l->fecha_vencimiento)->format('Y-m-d') : '—' }}
              </td>
              <td class="px-5 py-3 text-right tabular-nums">{{ $l->cantidad_cajas }}</td>
              <td class="px-5 py-3 text-right tabular-nums">{{ number_format($l->peso_total_recepcionado_kg, 3) }} kg</td>
              <td class="px-5 py-3 text-right">
                <div class="font-semibold tabular-nums">{{ number_format($l->stock_kg, 3) }} kg</div>
                <div class="w-32 ml-auto bg-slate-200 rounded-full h-1.5 mt-1">
                  <div class="{{ $color }} h-1.5 rounded-full" style="width: {{ $pct }}%"></div>
                </div>
              </td>
              <td class="px-5 py-3 text-right">
                <a href="{{ route('lotes.show', $l->id) }}" class="text-amber-600 hover:underline text-sm">ver</a>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div>
@endsection
