@extends('layouts.panel')
@section('title', 'Editar ' . $usuario->nombre)

@section('content')
  <div class="mb-5">
    <h2 class="text-2xl font-bold text-slate-900">Editar usuario</h2>
    <p class="text-sm text-slate-500">{{ $usuario->codigo_barcode }} · {{ $usuario->nombre }}</p>
  </div>

  @if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4 text-sm">
      <ul class="list-disc list-inside">
        @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('usuarios.update', $usuario->id) }}" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-5 max-w-2xl">
    @csrf
    @method('PATCH')

    <div>
      <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Nombre completo</label>
      <input type="text" name="nombre" value="{{ old('nombre', $usuario->nombre) }}" required maxlength="120"
             class="w-full px-3 py-2 border border-slate-300 rounded-lg">
    </div>

    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Rol</label>
        <select name="rol" id="rol" required class="w-full px-3 py-2 border border-slate-300 rounded-lg">
          <option value="pintor"    @selected(old('rol', $usuario->rol)==='pintor')>Pintor</option>
          <option value="encargado" @selected(old('rol', $usuario->rol)==='encargado')>Encargado</option>
          <option value="admin"     @selected(old('rol', $usuario->rol)==='admin')>Administrador</option>
        </select>
      </div>
      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Código de barcode</label>
        <input type="text" name="codigo_barcode" value="{{ old('codigo_barcode', $usuario->codigo_barcode) }}" required maxlength="32"
               class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono">
      </div>
    </div>

    <div id="campos-login" class="grid grid-cols-2 gap-4" style="display: {{ $usuario->rol === 'pintor' ? 'none' : 'grid' }};">
      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Email</label>
        <input type="email" name="email" value="{{ old('email', $usuario->email) }}" maxlength="120"
               class="w-full px-3 py-2 border border-slate-300 rounded-lg">
      </div>
      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Contraseña</label>
        <input type="text" name="password" value="" minlength="6"
               class="w-full px-3 py-2 border border-slate-300 rounded-lg" placeholder="(dejar vacío para no cambiar)">
      </div>
    </div>

    <label class="flex items-center gap-2 text-sm">
      <input type="checkbox" name="activo" value="1" @checked(old('activo', $usuario->activo))> Activo
    </label>

    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
      <a href="{{ route('usuarios.index') }}" class="px-4 py-2 text-slate-600 hover:text-slate-800">Cancelar</a>
      <button class="bg-amber-500 hover:bg-amber-600 text-white px-5 py-2 rounded-lg font-semibold">
        Guardar cambios
      </button>
    </div>
  </form>

  <script>
    document.getElementById('rol').addEventListener('change', e => {
      document.getElementById('campos-login').style.display = e.target.value === 'pintor' ? 'none' : 'grid';
    });
  </script>
@endsection
