@extends('layouts.panel')
@section('title', 'Editar producto')

@section('content')
  <div class="mb-5">
    <h2 class="text-2xl font-bold text-slate-900">Editar producto</h2>
    <p class="text-sm text-slate-500">{{ $producto->ral }} · {{ $producto->textura }} · {{ $producto->brillo_pct }}%</p>
  </div>

  @if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4 text-sm">
      <ul class="list-disc list-inside">
        @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('productos.update', $producto->id) }}" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-5 max-w-2xl">
    @csrf
    @method('PATCH')

    <div class="grid grid-cols-3 gap-4">
      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">RAL</label>
        <input type="text" name="ral" value="{{ old('ral', $producto->ral) }}" required maxlength="16"
               class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono">
      </div>
      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Textura</label>
        <select name="textura" required class="w-full px-3 py-2 border border-slate-300 rounded-lg">
          @foreach (['Mate','Brillante','Texturizado','Martillado','Granulado','Cuero','Metálico'] as $t)
            <option value="{{ $t }}" @selected(old('textura', $producto->textura)===$t)>{{ $t }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Brillo %</label>
        <input type="number" name="brillo_pct" value="{{ old('brillo_pct', $producto->brillo_pct) }}" required min="0" max="100"
               class="w-full px-3 py-2 border border-slate-300 rounded-lg tabular-nums">
      </div>
    </div>

    <div>
      <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Nombre interno (opcional)</label>
      <input type="text" name="nombre_interno" value="{{ old('nombre_interno', $producto->nombre_interno) }}" maxlength="120"
             class="w-full px-3 py-2 border border-slate-300 rounded-lg">
    </div>

    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Stock mínimo (kg)</label>
        <input type="number" name="stock_minimo_kg" value="{{ old('stock_minimo_kg', $producto->stock_minimo_kg) }}" required step="0.001" min="0"
               class="w-full px-3 py-2 border border-slate-300 rounded-lg tabular-nums">
      </div>
      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Stock crítico (kg)</label>
        <input type="number" name="stock_critico_kg" value="{{ old('stock_critico_kg', $producto->stock_critico_kg) }}" required step="0.001" min="0"
               class="w-full px-3 py-2 border border-slate-300 rounded-lg tabular-nums">
      </div>
    </div>

    <label class="flex items-center gap-2 text-sm">
      <input type="checkbox" name="activo" value="1" @checked(old('activo', $producto->activo))> Activo
    </label>

    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
      <a href="{{ route('productos.index') }}" class="px-4 py-2 text-slate-600 hover:text-slate-800">Cancelar</a>
      <button class="bg-amber-500 hover:bg-amber-600 text-white px-5 py-2 rounded-lg font-semibold">
        Guardar cambios
      </button>
    </div>
  </form>
@endsection
