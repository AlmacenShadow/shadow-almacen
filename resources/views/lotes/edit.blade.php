@extends('layouts.panel')
@section('title', 'Editar ' . $lote->codigo_barcode)

@section('content')
  <div class="flex items-center justify-between mb-5">
    <div>
      <h2 class="text-2xl font-bold text-slate-900">Editar lote</h2>
      <p class="text-sm text-slate-500 font-mono">{{ $lote->codigo_barcode }}</p>
    </div>
    <a href="{{ route('lotes.show', $lote) }}" class="text-sm text-slate-500 hover:text-slate-800">← volver al detalle</a>
  </div>

  @if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4 text-sm">
      <ul class="list-disc list-inside">
        @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
      </ul>
    </div>
  @endif

  @unless ($sinMovimientos)
    <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded mb-4 text-sm">
      ⚠ Este lote tiene <strong>{{ $cantMovimientos }}</strong>
      {{ $cantMovimientos === 1 ? 'movimiento asociado' : 'movimientos asociados' }}.
      Por integridad del stock no puedes cambiar producto, fechas de recepción, peso ni cajas
      desde aquí — solo metadatos blandos (proveedor, factura, fecha de vencimiento).
      Si necesitas corregir un peso, usa un <em>ajuste compensatorio</em> en lugar de editar.
    </div>
  @endunless

  <form method="POST" action="{{ route('lotes.update', $lote) }}" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-5">
    @csrf
    @method('PATCH')

    <div class="grid grid-cols-2 gap-5">
      <div class="col-span-2">
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Producto</label>
        @if ($sinMovimientos)
          <select name="producto_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg">
            @foreach ($productos as $p)
              <option value="{{ $p->id }}" @selected(old('producto_id', $lote->producto_id) == $p->id)>
                {{ $p->descripcion_corta }}
                @if($p->nombre_interno) — {{ $p->nombre_interno }} @endif
              </option>
            @endforeach
          </select>
        @else
          <input type="text" value="{{ $lote->producto->descripcion_corta }}@if($lote->producto->nombre_interno) — {{ $lote->producto->nombre_interno }} @endif" disabled
                 class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-slate-50 text-slate-500 cursor-not-allowed">
        @endif
      </div>

      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Fecha de recepción</label>
        <input type="date" name="fecha_recepcion"
               value="{{ old('fecha_recepcion', $lote->fecha_recepcion->format('Y-m-d')) }}"
               {{ $sinMovimientos ? 'required' : 'disabled' }}
               class="w-full px-3 py-2 border border-slate-300 rounded-lg {{ $sinMovimientos ? '' : 'bg-slate-50 text-slate-500 cursor-not-allowed' }}">
      </div>

      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Fecha de vencimiento (opcional)</label>
        <input type="date" name="fecha_vencimiento"
               value="{{ old('fecha_vencimiento', $lote->fecha_vencimiento?->format('Y-m-d')) }}"
               class="w-full px-3 py-2 border border-slate-300 rounded-lg">
      </div>

      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Cantidad de cajas</label>
        <input type="number" name="cantidad_cajas"
               value="{{ old('cantidad_cajas', $lote->cantidad_cajas) }}"
               min="1"
               {{ $sinMovimientos ? 'required' : 'disabled' }}
               class="w-full px-3 py-2 border border-slate-300 rounded-lg tabular-nums {{ $sinMovimientos ? '' : 'bg-slate-50 text-slate-500 cursor-not-allowed' }}">
      </div>

      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Peso total neto (kg)</label>
        <input type="number" name="peso_total_recepcionado_kg"
               value="{{ old('peso_total_recepcionado_kg', $lote->peso_total_recepcionado_kg) }}"
               step="0.001" min="0.001"
               {{ $sinMovimientos ? 'required' : 'disabled' }}
               class="w-full px-3 py-2 border border-slate-300 rounded-lg tabular-nums {{ $sinMovimientos ? '' : 'bg-slate-50 text-slate-500 cursor-not-allowed' }}">
      </div>

      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Tara por caja (kg)</label>
        <input type="number" name="peso_tara_unitario_kg"
               value="{{ old('peso_tara_unitario_kg', $lote->peso_tara_unitario_kg) }}"
               step="0.001" min="0"
               {{ $sinMovimientos ? '' : 'disabled' }}
               class="w-full px-3 py-2 border border-slate-300 rounded-lg tabular-nums {{ $sinMovimientos ? '' : 'bg-slate-50 text-slate-500 cursor-not-allowed' }}">
      </div>

      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Proveedor (opcional)</label>
        <input type="text" name="proveedor" value="{{ old('proveedor', $lote->proveedor) }}" maxlength="120"
               class="w-full px-3 py-2 border border-slate-300 rounded-lg">
      </div>

      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Factura (opcional)</label>
        <input type="text" name="factura" value="{{ old('factura', $lote->factura) }}" maxlength="60"
               class="w-full px-3 py-2 border border-slate-300 rounded-lg">
      </div>
    </div>

    <div class="flex justify-between items-center pt-4 border-t border-slate-100">
      <div>
        @if ($sinMovimientos)
          <form method="POST" action="{{ route('lotes.destroy', $lote) }}" class="inline"
                onsubmit="return confirm('¿Borrar definitivamente el lote {{ $lote->codigo_barcode }}? Esta acción no se puede deshacer.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-red-600 hover:text-red-700 text-sm hover:underline">
              Eliminar lote
            </button>
          </form>
        @else
          <span class="text-xs text-slate-400 cursor-help"
                title="No se puede borrar un lote con movimientos asociados">
            Eliminar deshabilitado ({{ $cantMovimientos }} mov.)
          </span>
        @endif
      </div>
      <div class="flex gap-3">
        <a href="{{ route('lotes.show', $lote) }}" class="px-4 py-2 text-slate-600 hover:text-slate-800">Cancelar</a>
        <button class="bg-amber-500 hover:bg-amber-600 text-white px-5 py-2 rounded-lg font-semibold">
          Guardar cambios
        </button>
      </div>
    </div>
  </form>
@endsection
