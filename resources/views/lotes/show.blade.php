@extends('layouts.panel')
@section('title', $lote->codigo_barcode)

@section('content')
  <div class="flex items-center justify-between mb-5">
    <div>
      <h2 class="text-2xl font-bold text-slate-900">{{ $lote->codigo_barcode }}</h2>
      <p class="text-sm text-slate-500">
        {{ $lote->producto->descripcion_corta }}
        @if ($lote->producto->nombre_interno) — {{ $lote->producto->nombre_interno }} @endif
      </p>
    </div>
    <a href="{{ route('lotes.index') }}" class="text-sm text-slate-500 hover:text-slate-800">← volver</a>
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
          <p class="text-xs text-slate-700">{{ $lote->producto->textura }} · {{ $lote->producto->brillo_pct }}%</p>
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
@endsection
