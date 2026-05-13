@extends('layouts.panel')
@section('title', 'Nuevo usuario')

@section('content')
  <div class="mb-5">
    <h2 class="text-2xl font-bold text-slate-900">Nuevo usuario</h2>
    <p class="text-sm text-slate-500">Si es pintor, solo necesita código de barcode. Si es encargado o admin, además email y contraseña.</p>
  </div>

  @if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4 text-sm">
      <ul class="list-disc list-inside">
        @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('usuarios.store') }}" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-5 max-w-2xl">
    @csrf

    <div>
      <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Nombre completo</label>
      <input type="text" name="nombre" value="{{ old('nombre') }}" required maxlength="120"
             class="w-full px-3 py-2 border border-slate-300 rounded-lg" placeholder="Juan Pérez">
    </div>

    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Rol</label>
        <select name="rol" id="rol" required class="w-full px-3 py-2 border border-slate-300 rounded-lg">
          <option value="pintor"    @selected(old('rol','pintor')==='pintor')>Pintor</option>
          <option value="encargado" @selected(old('rol')==='encargado')>Encargado</option>
          <option value="admin"     @selected(old('rol')==='admin')>Administrador</option>
        </select>
      </div>
      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Código de barcode</label>
        <input type="text" name="codigo_barcode" id="codigo_barcode" value="{{ old('codigo_barcode', $sugerencias['pintor']) }}" required maxlength="32"
               class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono">
        <p class="text-xs text-slate-400 mt-1">Se sugiere automáticamente según el rol; puedes cambiarlo.</p>
      </div>
    </div>

    <div id="campos-login" class="grid grid-cols-2 gap-4" style="display:none;">
      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Email</label>
        <input type="email" name="email" value="{{ old('email') }}" maxlength="120"
               class="w-full px-3 py-2 border border-slate-300 rounded-lg" placeholder="ejemplo@shadowpanama.com">
      </div>
      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Contraseña</label>
        <input type="text" name="password" value="" minlength="6"
               class="w-full px-3 py-2 border border-slate-300 rounded-lg" placeholder="mínimo 6 caracteres">
        <p class="text-xs text-slate-400 mt-1">El usuario puede cambiarla después de entrar.</p>
      </div>
    </div>

    <label class="flex items-center gap-2 text-sm">
      <input type="checkbox" name="activo" value="1" @checked(old('activo', true))> Activo
    </label>

    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
      <a href="{{ route('usuarios.index') }}" class="px-4 py-2 text-slate-600 hover:text-slate-800">Cancelar</a>
      <button class="bg-amber-500 hover:bg-amber-600 text-white px-5 py-2 rounded-lg font-semibold">
        Guardar
      </button>
    </div>
  </form>

  <script>
    const rol = document.getElementById('rol');
    const codigo = document.getElementById('codigo_barcode');
    const camposLogin = document.getElementById('campos-login');
    const sugerencias = @json($sugerencias);

    function actualizar() {
      const r = rol.value;
      camposLogin.style.display = r === 'pintor' ? 'none' : 'grid';
      // Solo auto-sugerimos si el código actual coincide con alguna sugerencia anterior
      const todasSugerencias = Object.values(sugerencias);
      if (todasSugerencias.includes(codigo.value) || codigo.value === '') {
        codigo.value = sugerencias[r];
      }
    }
    rol.addEventListener('change', actualizar);
    actualizar();
  </script>
@endsection
