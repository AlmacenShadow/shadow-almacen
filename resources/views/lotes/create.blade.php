@extends('layouts.panel')
@section('title', 'Nueva recepción')

@section('content')
  <div class="mb-5">
    <h2 class="text-2xl font-bold text-slate-900">Nueva recepción</h2>
    <p class="text-sm text-slate-500">Un lote es un producto + fecha de recepción. Se generan {{ '$n' }} etiquetas (2 por caja).</p>
  </div>

  @if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4 text-sm">
      <ul class="list-disc list-inside">
        @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('lotes.store') }}" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-5">
    @csrf

    <div class="grid grid-cols-2 gap-5">
      <div class="col-span-2">
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Producto</label>
        <select name="producto_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg">
          <option value="">— elegir —</option>
          @foreach ($productos as $p)
            <option value="{{ $p->id }}" @selected(old('producto_id') == $p->id)>
              {{ $p->descripcion_corta }}
              @if($p->nombre_interno) — {{ $p->nombre_interno }} @endif
            </option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Fecha de recepción</label>
        <input type="date" name="fecha_recepcion" value="{{ old('fecha_recepcion', now()->toDateString()) }}" required
               class="w-full px-3 py-2 border border-slate-300 rounded-lg">
      </div>

      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Fecha de vencimiento (opcional)</label>
        <input type="date" name="fecha_vencimiento" value="{{ old('fecha_vencimiento') }}"
               class="w-full px-3 py-2 border border-slate-300 rounded-lg">
      </div>

      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Cantidad de cajas</label>
        <input type="number" name="cantidad_cajas" value="{{ old('cantidad_cajas', 1) }}" min="1" required
               class="w-full px-3 py-2 border border-slate-300 rounded-lg tabular-nums">
      </div>

      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Peso total neto (kg)</label>
        <input type="number" name="peso_total_recepcionado_kg" value="{{ old('peso_total_recepcionado_kg') }}" step="0.001" min="0.001" required
               class="w-full px-3 py-2 border border-slate-300 rounded-lg tabular-nums" placeholder="ej. 200.000">
      </div>

      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Tara por caja (kg, opcional)</label>
        <input type="number" name="peso_tara_unitario_kg" value="{{ old('peso_tara_unitario_kg', '0.000') }}" step="0.001" min="0"
               class="w-full px-3 py-2 border border-slate-300 rounded-lg tabular-nums">
        <p class="text-xs text-slate-400 mt-1">Si dejas 0 trabajamos en peso bruto. Configurable después.</p>
      </div>

      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Proveedor (opcional)</label>
        <input type="text" name="proveedor" value="{{ old('proveedor') }}" maxlength="120"
               class="w-full px-3 py-2 border border-slate-300 rounded-lg">
      </div>

      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Factura (opcional)</label>
        <input type="text" name="factura" value="{{ old('factura') }}" maxlength="60"
               class="w-full px-3 py-2 border border-slate-300 rounded-lg">
      </div>
    </div>

    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
      <a href="{{ route('lotes.index') }}" class="px-4 py-2 text-slate-600 hover:text-slate-800">Cancelar</a>
      <button class="bg-amber-500 hover:bg-amber-600 text-white px-5 py-2 rounded-lg font-semibold">
        Guardar y ver etiqueta
      </button>
    </div>
  </form>
@endsection
