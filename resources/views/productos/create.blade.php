@extends('layouts.panel')
@section('title', 'Nuevo producto')

@section('content')
  <div class="mb-5">
    <h2 class="text-2xl font-bold text-slate-900">Nuevo producto</h2>
    <p class="text-sm text-slate-500">La combinación RAL + textura + brillo debe ser única.</p>
  </div>

  @if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4 text-sm">
      <ul class="list-disc list-inside">
        @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('productos.store') }}" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-5 max-w-2xl">
    @csrf

    <div class="grid grid-cols-3 gap-4">
      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">RAL</label>
        <input type="text" name="ral" value="{{ old('ral') }}" required maxlength="16"
               class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono" placeholder="RAL9005">
      </div>
      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Textura</label>
        <select name="textura" required class="w-full px-3 py-2 border border-slate-300 rounded-lg">
          <option value="">— elegir —</option>
          @foreach (['Mate','Brillante','Texturizado','Martillado','Granulado','Cuero','Metálico'] as $t)
            <option value="{{ $t }}" @selected(old('textura')===$t)>{{ $t }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Brillo %</label>
        <input type="number" name="brillo_pct" value="{{ old('brillo_pct', 30) }}" required min="0" max="100"
               class="w-full px-3 py-2 border border-slate-300 rounded-lg tabular-nums">
      </div>
    </div>

    <div>
      <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Nombre interno (opcional)</label>
      <input type="text" name="nombre_interno" value="{{ old('nombre_interno') }}" maxlength="120"
             class="w-full px-3 py-2 border border-slate-300 rounded-lg" placeholder="Negro mate texturizado">
      <p class="text-xs text-slate-400 mt-1">Alias humano para identificarlo más fácil que por el RAL.</p>
    </div>

    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Stock mínimo (kg) — alerta amarilla</label>
        <input type="number" name="stock_minimo_kg" value="{{ old('stock_minimo_kg', 50) }}" required step="0.001" min="0"
               class="w-full px-3 py-2 border border-slate-300 rounded-lg tabular-nums">
      </div>
      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Stock crítico (kg) — alerta roja</label>
        <input type="number" name="stock_critico_kg" value="{{ old('stock_critico_kg', 20) }}" required step="0.001" min="0"
               class="w-full px-3 py-2 border border-slate-300 rounded-lg tabular-nums">
      </div>
    </div>

    <label class="flex items-center gap-2 text-sm">
      <input type="checkbox" name="activo" value="1" @checked(old('activo', true))> Activo
    </label>

    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
      <a href="{{ route('productos.index') }}" class="px-4 py-2 text-slate-600 hover:text-slate-800">Cancelar</a>
      <button class="bg-amber-500 hover:bg-amber-600 text-white px-5 py-2 rounded-lg font-semibold">
        Guardar
      </button>
    </div>
  </form>
@endsection
