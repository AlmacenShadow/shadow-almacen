@extends('layouts.panel')
@section('title', 'Reportes')

@section('content')
  <div class="flex items-center justify-between mb-5">
    <div>
      <h2 class="text-2xl font-bold text-slate-900">Reportes</h2>
      <p class="text-sm text-slate-500">
        Consumo y alertas. Las correcciones administrativas se excluyen automáticamente
        para no inflar las cifras.
      </p>
    </div>
  </div>

  {{-- Filtro de fechas (afecta consumos, no stock bajo) --}}
  <form method="GET" action="{{ route('reportes.index') }}" class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6 flex flex-wrap items-end gap-4">
    <div>
      <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Desde</label>
      <input type="date" name="desde" value="{{ $desde->format('Y-m-d') }}"
             class="px-3 py-2 border border-slate-300 rounded-lg tabular-nums">
    </div>
    <div>
      <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Hasta</label>
      <input type="date" name="hasta" value="{{ $hasta->format('Y-m-d') }}"
             class="px-3 py-2 border border-slate-300 rounded-lg tabular-nums">
    </div>
    <button class="bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 rounded-lg font-semibold">
      Aplicar
    </button>
    @php
      $rangos = [
        'Últimos 7 días'  => [now()->subDays(7),  now()],
        'Últimos 30 días' => [now()->subDays(30), now()],
        'Mes actual'      => [now()->startOfMonth(), now()->endOfMonth()],
        'Mes anterior'    => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
        'Este año'        => [now()->startOfYear(), now()->endOfYear()],
      ];
    @endphp
    <div class="flex gap-2 text-xs ml-auto">
      @foreach ($rangos as $label => [$d, $h])
        <a href="{{ route('reportes.index', ['desde' => $d->format('Y-m-d'), 'hasta' => $h->format('Y-m-d')]) }}"
           class="px-3 py-2 rounded border border-slate-200 hover:bg-slate-50 text-slate-600">
          {{ $label }}
        </a>
      @endforeach
    </div>
  </form>

  {{-- ============= STOCK BAJO ============= --}}
  <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
      <div>
        <h3 class="font-semibold text-slate-800">Stock bajo y crítico</h3>
        <p class="text-xs text-slate-500 mt-0.5">Estado actual — independiente del rango de fechas.</p>
      </div>
      <div class="text-xs text-slate-500 flex gap-4">
        <span><span class="inline-block w-3 h-3 rounded-full bg-red-500 align-middle"></span> {{ $stockBajo->where('nivel','critico')->count() }} crítico</span>
        <span><span class="inline-block w-3 h-3 rounded-full bg-amber-500 align-middle"></span> {{ $stockBajo->where('nivel','bajo')->count() }} bajo</span>
      </div>
    </div>

    @if ($stockBajo->isEmpty())
      <div class="p-8 text-center text-slate-500">
        ✓ Todos los productos están por encima de su umbral de alerta.
      </div>
    @else
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
          <tr>
            <th class="px-3 py-3 w-12"></th>
            <th class="text-left px-4 py-3">RAL · textura · brillo</th>
            <th class="text-left px-4 py-3">Nivel</th>
            <th class="text-right px-4 py-3">Stock actual</th>
            <th class="text-right px-4 py-3">Mínimo</th>
            <th class="text-right px-4 py-3">Crítico</th>
            <th class="text-right px-4 py-3">Déficit</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @foreach ($stockBajo as $p)
            @php
              $deficit = (float) $p->stock_minimo_kg - (float) $p->stock_kg;
            @endphp
            <tr class="{{ $p->nivel === 'critico' ? 'bg-red-50' : 'bg-amber-50' }}">
              <td class="px-3 py-2">
                <span class="block w-8 h-8 rounded border border-slate-300 shadow-sm"
                      style="background-color: {{ $p->hex_resuelto }}"></span>
              </td>
              <td class="px-4 py-3">
                <div class="font-mono font-semibold">{{ $p->ral }}</div>
                <div class="text-xs text-slate-500">{{ $p->textura ?? '—' }} · {{ $p->brillo_pct }}%
                  @if ($p->nombre_interno) · {{ $p->nombre_interno }} @endif
                </div>
              </td>
              <td class="px-4 py-3">
                @if ($p->nivel === 'critico')
                  <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold bg-red-200 text-red-800">CRÍTICO</span>
                @else
                  <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold bg-amber-200 text-amber-800">BAJO</span>
                @endif
              </td>
              <td class="px-4 py-3 text-right tabular-nums font-bold {{ $p->nivel === 'critico' ? 'text-red-700' : 'text-amber-700' }}">
                {{ number_format($p->stock_kg, 3) }} kg
              </td>
              <td class="px-4 py-3 text-right tabular-nums text-slate-500">{{ number_format($p->stock_minimo_kg, 0) }} kg</td>
              <td class="px-4 py-3 text-right tabular-nums text-slate-500">{{ number_format($p->stock_critico_kg, 0) }} kg</td>
              <td class="px-4 py-3 text-right tabular-nums font-semibold text-slate-700">
                +{{ number_format(max(0, $deficit), 3) }} kg
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div>

  {{-- ============= CONSUMO POR PRODUCTO ============= --}}
  <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-slate-200">
      <h3 class="font-semibold text-slate-800">Consumo por producto</h3>
      <p class="text-xs text-slate-500 mt-0.5">
        Salidas y retornos del {{ $desde->format('Y-m-d') }} al {{ $hasta->format('Y-m-d') }}. Ordenado por consumo neto descendente.
      </p>
    </div>

    @if ($porProducto->isEmpty())
      <div class="p-8 text-center text-slate-500">Sin movimientos en este rango.</div>
    @else
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
          <tr>
            <th class="px-3 py-3 w-12"></th>
            <th class="text-left px-4 py-3">RAL · textura · brillo</th>
            <th class="text-right px-4 py-3">Salidas (kg)</th>
            <th class="text-right px-4 py-3">Retornos (kg)</th>
            <th class="text-right px-4 py-3">Consumo neto (kg)</th>
            <th class="text-right px-4 py-3">Movs</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @foreach ($porProducto as $r)
            <tr>
              <td class="px-3 py-2">
                <span class="block w-8 h-8 rounded border border-slate-300 shadow-sm"
                      style="background-color: {{ $r->hex_resuelto }}"></span>
              </td>
              <td class="px-4 py-3">
                <div class="font-mono font-semibold">{{ $r->ral }}</div>
                <div class="text-xs text-slate-500">{{ $r->textura ?? '—' }} · {{ $r->brillo_pct }}%
                  @if ($r->nombre_interno) · {{ $r->nombre_interno }} @endif
                </div>
              </td>
              <td class="px-4 py-3 text-right tabular-nums text-red-700">{{ number_format($r->kg_salidas, 3) }}</td>
              <td class="px-4 py-3 text-right tabular-nums text-emerald-700">{{ number_format($r->kg_retornos, 3) }}</td>
              <td class="px-4 py-3 text-right tabular-nums font-bold">{{ number_format($r->kg_netos, 3) }}</td>
              <td class="px-4 py-3 text-right tabular-nums text-slate-500">{{ $r->movimientos_count }}</td>
            </tr>
          @endforeach
        </tbody>
        <tfoot class="bg-slate-50 border-t-2 border-slate-200">
          <tr>
            <td colspan="2" class="px-4 py-3 text-xs uppercase tracking-wider text-slate-500 font-semibold">Total</td>
            <td class="px-4 py-3 text-right tabular-nums font-bold text-red-700">{{ number_format($porProducto->sum('kg_salidas'), 3) }}</td>
            <td class="px-4 py-3 text-right tabular-nums font-bold text-emerald-700">{{ number_format($porProducto->sum('kg_retornos'), 3) }}</td>
            <td class="px-4 py-3 text-right tabular-nums font-bold">{{ number_format($porProducto->sum('kg_netos'), 3) }}</td>
            <td class="px-4 py-3 text-right tabular-nums text-slate-500">{{ $porProducto->sum('movimientos_count') }}</td>
          </tr>
        </tfoot>
      </table>
    @endif
  </div>

  {{-- ============= CONSUMO POR PINTOR ============= --}}
  <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-200">
      <h3 class="font-semibold text-slate-800">Consumo por pintor</h3>
      <p class="text-xs text-slate-500 mt-0.5">
        Salidas y retornos hechos por pintores en el período. Excluye ajustes y correcciones administrativas.
      </p>
    </div>

    @if ($porPintor->isEmpty())
      <div class="p-8 text-center text-slate-500">Sin movimientos de pintores en este rango.</div>
    @else
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
          <tr>
            <th class="text-left px-4 py-3">Pintor</th>
            <th class="text-left px-4 py-3">Código</th>
            <th class="text-right px-4 py-3">Salidas (kg)</th>
            <th class="text-right px-4 py-3">Retornos (kg)</th>
            <th class="text-right px-4 py-3">Consumo neto (kg)</th>
            <th class="text-right px-4 py-3">Movs</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @foreach ($porPintor as $p)
            <tr>
              <td class="px-4 py-3 font-medium">{{ $p->nombre }}</td>
              <td class="px-4 py-3 font-mono text-slate-500">{{ $p->codigo_barcode }}</td>
              <td class="px-4 py-3 text-right tabular-nums text-red-700">{{ number_format($p->kg_salidas, 3) }}</td>
              <td class="px-4 py-3 text-right tabular-nums text-emerald-700">{{ number_format($p->kg_retornos, 3) }}</td>
              <td class="px-4 py-3 text-right tabular-nums font-bold">{{ number_format($p->kg_netos, 3) }}</td>
              <td class="px-4 py-3 text-right tabular-nums text-slate-500">{{ $p->movimientos_count }}</td>
            </tr>
          @endforeach
        </tbody>
        <tfoot class="bg-slate-50 border-t-2 border-slate-200">
          <tr>
            <td colspan="2" class="px-4 py-3 text-xs uppercase tracking-wider text-slate-500 font-semibold">Total</td>
            <td class="px-4 py-3 text-right tabular-nums font-bold text-red-700">{{ number_format($porPintor->sum('kg_salidas'), 3) }}</td>
            <td class="px-4 py-3 text-right tabular-nums font-bold text-emerald-700">{{ number_format($porPintor->sum('kg_retornos'), 3) }}</td>
            <td class="px-4 py-3 text-right tabular-nums font-bold">{{ number_format($porPintor->sum('kg_netos'), 3) }}</td>
            <td class="px-4 py-3 text-right tabular-nums text-slate-500">{{ $porPintor->sum('movimientos_count') }}</td>
          </tr>
        </tfoot>
      </table>
    @endif
  </div>
@endsection
