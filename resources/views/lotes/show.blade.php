@extends('layouts.panel')
@section('title', $lote->codigo_barcode)

@section('content')
  <div class="flex items-center justify-between mb-5">
    <div class="flex items-center gap-4">
      <span class="block w-12 h-12 rounded border border-slate-300 shadow-sm flex-shrink-0"
            style="background-color: {{ $lote->producto->hex }}"
            title="{{ $lote->producto->hex }}"></span>
      <div>
        <h2 class="text-2xl font-bold text-slate-900">{{ $lote->codigo_barcode }}</h2>
        <p class="text-sm text-slate-500">
          {{ $lote->producto->descripcion_corta }}
          @if ($lote->producto->nombre_interno) — {{ $lote->producto->nombre_interno }} @endif
          @if ($lote->producto->nombre_ral_oficial)
            <span class="text-slate-400">· {{ $lote->producto->nombre_ral_oficial }}</span>
          @endif
        </p>
      </div>
    </div>
    <div class="flex items-center gap-3">
      <a href="{{ route('lotes.edit', $lote) }}"
         class="text-sm bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 px-3 py-1.5 rounded">
        Editar lote
      </a>
      <a href="{{ route('lotes.index') }}" class="text-sm text-slate-500 hover:text-slate-800">← volver</a>
    </div>
  </div>

  <div class="grid grid-cols-3 gap-6">
    <!-- Datos del lote -->
    <div class="col-span-2 bg-white rounded-xl shadow-sm border border-slate-200 p-6">
      <h3 class="font-semibold text-slate-800 mb-4">Datos del lote</h3>
      <dl class="grid grid-cols-2 gap-y-3 text-sm">
        <dt class="text-slate-500">Producto</dt>
        <dd class="font-semibold">{{ $lote->producto->descripcion_corta }}</dd>

        <dt class="text-slate-500">Fecha de recepción</dt>
        <dd>{{ $lote->fecha_recepcion->format('Y-m-d') }}</dd>

        <dt class="text-slate-500">Fecha de vencimiento</dt>
        <dd>{{ $lote->fecha_vencimiento?->format('Y-m-d') ?? '—' }}</dd>

        <dt class="text-slate-500">Cantidad de cajas</dt>
        <dd class="tabular-nums">{{ $lote->cantidad_cajas }}</dd>

        <dt class="text-slate-500">Peso total recepcionado</dt>
        <dd class="tabular-nums font-semibold">{{ number_format($lote->peso_total_recepcionado_kg, 3) }} kg</dd>

        <dt class="text-slate-500">Tara por caja</dt>
        <dd class="tabular-nums">{{ number_format($lote->peso_tara_unitario_kg, 3) }} kg</dd>

        <dt class="text-slate-500">Stock actual</dt>
        <dd class="tabular-nums font-bold text-emerald-700">{{ number_format($lote->stock_kg, 3) }} kg</dd>

        <dt class="text-slate-500">Proveedor</dt>
        <dd>{{ $lote->proveedor ?: '—' }}</dd>

        <dt class="text-slate-500">Factura</dt>
        <dd>{{ $lote->factura ?: '—' }}</dd>

        <dt class="text-slate-500">Recepcionado por</dt>
        <dd>{{ $lote->recepcionadoPor->nombre }}</dd>
      </dl>
    </div>

    <!-- Etiqueta para imprimir / recortar -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
      <h3 class="font-semibold text-slate-800 mb-2">Etiqueta a imprimir</h3>
      <p class="text-xs text-slate-500 mb-4">Necesitas <span class="font-bold">{{ $lote->cantidad_cajas * 2 }}</span> etiquetas (2 por caja)</p>

      <div class="border-2 border-dashed border-slate-300 rounded-lg p-4 mb-4">
        <div class="border-2 border-slate-800 rounded p-3 bg-slate-50">
          <div class="flex justify-between items-start">
            <div class="text-[10px] font-bold text-slate-500 tracking-widest">SHADOW</div>
            <div class="text-[10px] text-slate-400">50×30mm</div>
          </div>
          <p class="font-bold text-slate-900 mt-1 leading-tight">{{ $lote->producto->ral }}</p>
          <p class="text-xs text-slate-700">{{ $lote->producto->textura?->nombre ?? '?' }} · {{ $lote->producto->brillo_pct }}%</p>
          <p class="text-xs text-slate-600 mt-2">Recep. {{ $lote->fecha_recepcion->format('Y-m-d') }}</p>
          @if ($lote->fecha_vencimiento)
            <p class="text-xs text-slate-600">Vence {{ $lote->fecha_vencimiento->format('Y-m-d') }}</p>
          @endif
          <div class="mt-2 h-7 bg-black"
               style="background-image: repeating-linear-gradient(90deg, black 0, black 1px, white 1px, white 3px);"></div>
          <p class="text-center text-[10px] mt-0.5 font-mono font-bold">{{ $lote->codigo_barcode }}</p>
        </div>
      </div>

      <button onclick="window.print()" class="w-full bg-slate-900 hover:bg-slate-800 text-white py-2.5 rounded-lg font-semibold">
        Imprimir etiquetas
      </button>
      <p class="text-[11px] text-slate-400 text-center mt-2">
        (En esta v0 imprime sin formato; soporte de impresora térmica viene después)
      </p>
    </div>
  </div>

  {{-- Historial de movimientos del lote --}}
  <div class="mt-6 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
      <div>
        <h3 class="font-semibold text-slate-800">Historial de movimientos</h3>
        <p class="text-xs text-slate-500 mt-0.5">Trazabilidad completa desde la recepción</p>
      </div>
      <span class="text-xs text-slate-500 tabular-nums">
        recepción + {{ $movimientos->count() }}
        {{ $movimientos->count() === 1 ? 'movimiento' : 'movimientos' }}
      </span>
    </div>

    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
        <tr>
          <th class="text-left px-5 py-3">Cuándo</th>
          <th class="text-left px-5 py-3">Evento</th>
          <th class="text-left px-5 py-3">Quién</th>
          <th class="text-right px-5 py-3">Δ Peso</th>
          <th class="text-right px-5 py-3">Stock resultante</th>
          <th class="text-left px-5 py-3">Detalle</th>
          <th class="text-right px-5 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        {{-- Fila 0: la recepción (origen del lote) --}}
        <tr class="bg-blue-50/50">
          <td class="px-5 py-3 text-slate-700 tabular-nums whitespace-nowrap">
            {{ $lote->fecha_recepcion->format('Y-m-d') }}
          </td>
          <td class="px-5 py-3 whitespace-nowrap">
            <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800">
              📦 Recepción
            </span>
          </td>
          <td class="px-5 py-3">
            <div class="font-medium">{{ $lote->recepcionadoPor->nombre }}</div>
            <div class="text-xs text-slate-500">{{ ucfirst($lote->recepcionadoPor->rol) }}</div>
          </td>
          <td class="px-5 py-3 text-right whitespace-nowrap">
            <span class="font-semibold tabular-nums text-blue-700">
              +{{ number_format($lote->peso_total_recepcionado_kg, 3) }} kg
            </span>
          </td>
          <td class="px-5 py-3 text-right tabular-nums font-semibold text-slate-700">
            {{ number_format($lote->peso_total_recepcionado_kg, 3) }} kg
          </td>
          <td class="px-5 py-3 text-slate-600">
            {{ $lote->cantidad_cajas }} {{ $lote->cantidad_cajas === 1 ? 'caja' : 'cajas' }}
            @if ($lote->proveedor) · {{ $lote->proveedor }} @endif
            @if ($lote->factura) · Fact. {{ $lote->factura }} @endif
          </td>
          <td></td>
        </tr>

        {{-- Filas siguientes: movimientos en orden cronológico ASC --}}
        @php
          $stockAcumulado = (float) $lote->peso_total_recepcionado_kg;
        @endphp
        @foreach ($movimientos as $m)
          @php
            $delta = match ($m->tipo) {
              'salida'  => -1 * (float) $m->peso_kg,
              'retorno' => +1 * (float) $m->peso_kg,
              'ajuste'  => ((int) ($m->motivo_signo ?? -1)) * (float) $m->peso_kg,
              default   => 0.0,
            };
            $stockAcumulado += $delta;

            $tipoBadge = match ($m->tipo) {
              'salida'  => ['bg-red-100 text-red-800',       '↗ Salida'],
              'retorno' => ['bg-emerald-100 text-emerald-800','↘ Retorno'],
              'ajuste'  => ['bg-amber-100 text-amber-800',   '⚙ Ajuste'],
              default   => ['bg-slate-100 text-slate-600',   $m->tipo],
            };
            $signo = $delta < 0 ? '−' : '+';
            $pesoClase = $delta < 0 ? 'text-red-700' : 'text-emerald-700';
          @endphp
          <tr id="mov-{{ $m->id }}" class="{{ $m->anomalia ? 'bg-amber-50' : '' }} {{ $m->corregido_por_id ? 'opacity-60' : '' }}">
            <td class="px-5 py-3 text-slate-600 tabular-nums whitespace-nowrap">
              {{ \Illuminate\Support\Carbon::parse($m->created_at)->format('Y-m-d H:i') }}
            </td>
            <td class="px-5 py-3 whitespace-nowrap">
              <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold {{ $tipoBadge[0] }}">
                {{ $tipoBadge[1] }}
              </span>
              @if ($m->corrige_movimiento_id)
                <a href="#mov-{{ $m->corrige_movimiento_id }}"
                   class="ml-1 inline-block px-2 py-0.5 rounded text-xs font-semibold bg-violet-100 text-violet-800 hover:bg-violet-200"
                   title="Esta corrección anula al movimiento #{{ $m->corrige_movimiento_id }}">↺ corrige #{{ $m->corrige_movimiento_id }}</a>
              @endif
              @if ($m->corregido_por_id)
                <a href="#mov-{{ $m->corregido_por_id }}"
                   class="ml-1 inline-block px-2 py-0.5 rounded text-xs font-semibold bg-slate-200 text-slate-700 hover:bg-slate-300"
                   title="Anulado por el ajuste #{{ $m->corregido_por_id }}">corregido</a>
              @endif
              @if ($m->anomalia)
                <span class="ml-1 inline-block px-2 py-0.5 rounded text-xs font-semibold bg-amber-200 text-amber-900"
                      title="Anomalía: {{ $m->tipo_anomalia ?? 'sin detalle' }}">⚠</span>
              @endif
            </td>
            <td class="px-5 py-3">
              @if ($m->usuario_nombre)
                <div class="font-medium">{{ $m->usuario_nombre }}</div>
                <div class="text-xs text-slate-500">{{ ucfirst($m->usuario_rol) }}</div>
              @else
                <span class="text-slate-400 italic">—</span>
              @endif
            </td>
            <td class="px-5 py-3 text-right whitespace-nowrap">
              <span class="font-semibold tabular-nums {{ $pesoClase }}">
                {{ $signo }}{{ number_format(abs($delta), 3) }} kg
              </span>
              @if ($m->peso_manual)
                <div class="text-xs text-slate-400 italic">manual</div>
              @endif
            </td>
            <td class="px-5 py-3 text-right tabular-nums {{ $stockAcumulado < -0.001 ? 'text-red-700 font-bold' : 'text-slate-700' }}">
              {{ number_format($stockAcumulado, 3) }} kg
            </td>
            <td class="px-5 py-3 text-slate-600">
              @if ($m->motivo_descripcion)
                <div class="font-medium">{{ $m->motivo_descripcion }}</div>
              @endif
              @if ($m->nota_texto)
                <div class="text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($m->nota_texto, 100) }}</div>
              @endif
              @if (!$m->motivo_descripcion && !$m->nota_texto)
                <span class="text-slate-300">—</span>
              @endif
            </td>
            <td class="px-5 py-3 text-right whitespace-nowrap">
              @if (!$m->corregido_por_id && !$m->corrige_movimiento_id)
                <a href="{{ route('movimientos.corregir', $m->id) }}"
                   class="text-amber-600 hover:underline text-xs">corregir</a>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>

      {{-- Pie con el stock actual --}}
      <tfoot class="bg-slate-50 border-t-2 border-slate-200">
        <tr>
          <td colspan="4" class="px-5 py-3 text-right text-xs uppercase tracking-wider text-slate-500 font-semibold">
            Stock actual (según vista v_stock_lote)
          </td>
          <td class="px-5 py-3 text-right tabular-nums font-bold text-emerald-700">
            {{ number_format($lote->stock_kg, 3) }} kg
          </td>
          <td colspan="2" class="px-5 py-3 text-xs text-slate-500">
            @php
              $diff = abs($lote->stock_kg - $stockAcumulado);
            @endphp
            @if ($diff > 0.001)
              <span class="text-amber-700" title="El stock calculado y el de la vista difieren — puede ser por movimientos pendientes de sync">
                ⚠ diff {{ number_format($diff, 3) }} kg
              </span>
            @else
              <span class="text-emerald-600">✓ cuadra</span>
            @endif
          </td>
        </tr>
      </tfoot>
    </table>
  </div>
@endsection
