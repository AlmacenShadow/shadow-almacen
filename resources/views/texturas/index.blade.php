@extends('layouts.panel')
@section('title', 'Texturas')

@section('content')
  <div class="flex items-center justify-between mb-5">
    <div>
      <h2 class="text-2xl font-bold text-slate-900">Texturas</h2>
      <p class="text-sm text-slate-500">Acabados disponibles para los productos.</p>
    </div>
    <a href="{{ route('productos.index') }}" class="text-sm text-slate-600 hover:text-slate-900">← volver a productos</a>
  </div>

  @if (session('flash'))
    {{-- el layout ya muestra session('flash') --}}
  @endif

  @if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4 text-sm">
      <ul class="list-disc list-inside">
        @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
      </ul>
    </div>
  @endif

  <div class="grid grid-cols-3 gap-6">
    {{-- Listado existente --}}
    <div class="col-span-2 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
          <tr>
            <th class="text-left px-5 py-3">Nombre</th>
            <th class="text-right px-5 py-3">Orden</th>
            <th class="text-center px-5 py-3">Activa</th>
            <th class="text-center px-5 py-3">Productos</th>
            <th class="text-right px-5 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @foreach ($texturas as $t)
            <tr class="{{ $t->activo ? '' : 'opacity-60' }}">
              <form method="POST" action="{{ route('texturas.update', $t->id) }}">
                @csrf @method('PATCH')
                <td class="px-5 py-2">
                  <input type="text" name="nombre" value="{{ $t->nombre }}" required maxlength="40"
                         class="w-full px-2 py-1 border border-slate-200 rounded">
                </td>
                <td class="px-5 py-2 text-right">
                  <input type="number" name="orden" value="{{ $t->orden }}" min="0" max="9999"
                         class="w-20 px-2 py-1 border border-slate-200 rounded tabular-nums text-right">
                </td>
                <td class="px-5 py-2 text-center">
                  <input type="checkbox" name="activo" value="1" @checked($t->activo)>
                </td>
                <td class="px-5 py-2 text-center text-slate-500 tabular-nums">{{ $t->productos_count }}</td>
                <td class="px-5 py-2 text-right whitespace-nowrap">
                  <button type="submit" class="text-amber-600 hover:underline text-xs">guardar</button>
              </form>
                  @if ($t->productos_count == 0)
                    <form method="POST" action="{{ route('texturas.destroy', $t->id) }}" class="inline ml-2"
                          onsubmit="return confirm('¿Borrar la textura «{{ $t->nombre }}»?');">
                      @csrf @method('DELETE')
                      <button type="submit" class="text-red-600 hover:underline text-xs">borrar</button>
                    </form>
                  @else
                    <span class="text-slate-300 text-xs ml-2 cursor-help"
                          title="En uso por {{ $t->productos_count }} producto(s)">borrar</span>
                  @endif
                </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    {{-- Formulario de nueva textura --}}
    <div>
      <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 sticky top-4">
        <p class="text-xs uppercase tracking-wider text-slate-500 font-semibold mb-3">Nueva textura</p>
        <form method="POST" action="{{ route('texturas.store') }}" class="space-y-3">
          @csrf
          <div>
            <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Nombre</label>
            <input type="text" name="nombre" required maxlength="40" value="{{ old('nombre') }}"
                   class="w-full px-3 py-2 border border-slate-300 rounded-lg"
                   placeholder="Ej: Satinado">
          </div>
          <div>
            <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Orden</label>
            <input type="number" name="orden" value="{{ old('orden', 100) }}" min="0" max="9999"
                   class="w-full px-3 py-2 border border-slate-300 rounded-lg tabular-nums">
            <p class="text-xs text-slate-400 mt-1">Menor número aparece primero en los desplegables.</p>
          </div>
          <button class="w-full bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg font-semibold">
            Agregar
          </button>
        </form>
      </div>
    </div>
  </div>
@endsection
