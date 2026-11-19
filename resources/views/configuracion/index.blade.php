@extends('layouts.panel')
@section('title', 'Configuración')

@php
  $menu = collect($grupos)->map(fn ($g) => $g['titulo'])->all()
        + ['colores' => 'Colores personalizados', 'parametros' => 'Parámetros'];
  $inp = 'px-2.5 py-1.5 border border-slate-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-amber-400';
  $g = $grupos[$seccion] ?? null;
@endphp

@section('content')
  <div class="mb-5">
    <h2 class="text-2xl font-bold text-slate-900">Configuración</h2>
    <p class="text-sm text-slate-500">Catálogos que se usan en productos y lotes. Los cambios se ven en el panel al instante y en la tablet en su próxima sincronización.</p>
  </div>

  @if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4 text-sm">
      <ul class="list-disc list-inside">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
  @endif

  <div class="grid grid-cols-[220px_1fr] gap-6 items-start">
    {{-- Menú de secciones --}}
    <nav class="bg-white rounded-xl border border-slate-200 shadow-sm p-2 sticky top-4">
      @foreach ($menu as $clave => $titulo)
        <a href="{{ route('configuracion.index', ['s' => $clave]) }}"
           class="block px-3 py-2 rounded-lg text-sm {{ $seccion === $clave ? 'bg-amber-100 text-amber-900 font-semibold' : 'text-slate-600 hover:bg-slate-50' }}">
          {{ $titulo }}
        </a>
      @endforeach
      <div class="border-t border-slate-100 my-2"></div>
      <a href="{{ route('usuarios.index') }}" class="block px-3 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-50">Usuarios →</a>
      <a href="{{ route('catalogo-ral.index') }}" target="_blank" class="block px-3 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-50">Catálogo RAL K7 ↗</a>
    </nav>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">

      {{-- ================= Catálogos ================= --}}
      @if ($g)
        <h3 class="text-lg font-semibold text-slate-900">{{ $g['titulo'] }}</h3>
        <p class="text-sm text-slate-500 mb-4">{{ $g['ayuda'] }}
          @if ($seccion !== 'proveedor') Si una opción ya la usan productos, no se puede borrar: desactívala y dejará de aparecer en los formularios. @endif
        </p>

        <table class="w-full text-sm">
          <thead class="text-xs uppercase tracking-wider text-slate-500 border-b border-slate-200">
            <tr>
              <th class="text-left py-2 pr-2">Nombre</th>
              @if ($seccion === 'tipo_pintura')<th class="text-left py-2 pr-2">Sigla</th>@endif
              @if ($seccion === 'origen')<th class="text-left py-2 pr-2">Bandera</th><th class="text-left py-2 pr-2">ISO</th><th class="text-left py-2 pr-2">Subir imagen</th>@endif
              <th class="text-left py-2 pr-2">Orden</th>
              <th class="text-center py-2 pr-2">Activo</th>
              <th class="text-center py-2 pr-2">{{ $seccion === 'proveedor' ? 'Lotes' : 'Productos' }}</th>
              <th></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @forelse ($opciones as $o)
              <tr class="{{ $o->activo ? '' : 'opacity-60' }}">
                <td class="py-2 pr-2">
                  <input form="f-upd-{{ $o->id }}" name="nombre" value="{{ $o->nombre }}" required maxlength="120" class="{{ $inp }} w-full">
                  <div class="text-[0.65rem] text-slate-400 font-mono mt-0.5" title="Clave interna: no cambia aunque cambies el nombre">{{ $o->clave }}</div>
                </td>
                @if ($seccion === 'tipo_pintura')
                  <td class="py-2 pr-2"><input form="f-upd-{{ $o->id }}" name="sigla" value="{{ $o->sigla }}" required maxlength="5" class="{{ $inp }} w-20 font-mono uppercase"></td>
                @endif
                @if ($seccion === 'origen')
                  <td class="py-2 pr-2">{!! \App\Support\Catalogo::banderaHtml($o->clave, 33) ?: '<span class="text-slate-300">—</span>' !!}</td>
                  <td class="py-2 pr-2"><input form="f-upd-{{ $o->id }}" name="iso" value="{{ $o->iso }}" maxlength="2" class="{{ $inp }} w-14 font-mono uppercase" placeholder="PA"></td>
                  <td class="py-2 pr-2">
                    <input form="f-upd-{{ $o->id }}" type="file" name="archivo" accept=".png,.jpg,.jpeg,.webp,.svg" class="text-xs w-44">
                    <label class="flex items-center gap-1 text-[0.7rem] text-slate-500 mt-1">
                      <input form="f-upd-{{ $o->id }}" type="checkbox" name="refrescar_bandera" value="1"> volver a descargar por ISO
                    </label>
                  </td>
                @endif
                <td class="py-2 pr-2"><input form="f-upd-{{ $o->id }}" type="number" name="orden" value="{{ $o->orden }}" min="0" max="9999" class="{{ $inp }} w-20 tabular-nums"></td>
                <td class="py-2 pr-2 text-center"><input form="f-upd-{{ $o->id }}" type="checkbox" name="activo" value="1" @checked($o->activo)></td>
                <td class="py-2 pr-2 text-center tabular-nums text-slate-500">{{ $o->num_usos }}</td>
                <td class="py-2 text-right whitespace-nowrap">
                  <button form="f-upd-{{ $o->id }}" class="text-amber-600 hover:underline text-sm font-medium">guardar</button>
                  @if ($o->num_usos === 0)
                    <button form="f-del-{{ $o->id }}" class="text-red-600 hover:underline text-sm ml-3">borrar</button>
                  @else
                    <span class="text-slate-300 text-sm ml-3 cursor-help" title="En uso: desactívalo en lugar de borrar">borrar</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="9" class="py-6 text-center text-slate-400">Todavía no hay {{ mb_strtolower($g['titulo']) }}.</td></tr>
            @endforelse
          </tbody>
        </table>

        {{-- Forms de cada fila (fuera de la tabla: no se pueden anidar forms) --}}
        @foreach ($opciones as $o)
          <form id="f-upd-{{ $o->id }}" method="POST" action="{{ route('configuracion.opciones.update', $o) }}" enctype="multipart/form-data" class="hidden">
            @csrf @method('PATCH')
          </form>
          <form id="f-del-{{ $o->id }}" method="POST" action="{{ route('configuracion.opciones.destroy', $o) }}" class="hidden"
                onsubmit="return confirm('¿Borrar «{{ $o->nombre }}»?');">
            @csrf @method('DELETE')
          </form>
        @endforeach

        {{-- Agregar --}}
        <form method="POST" action="{{ route('configuracion.opciones.store', $seccion) }}" enctype="multipart/form-data"
              class="mt-6 pt-5 border-t border-slate-200 flex flex-wrap items-end gap-3">
          @csrf
          <div>
            <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Nuevo {{ $g['singular'] }}</label>
            <input name="nombre" required maxlength="120" class="{{ $inp }} w-64" value="{{ old('nombre') }}" placeholder="Nombre">
          </div>
          @if ($seccion === 'tipo_pintura')
            <div>
              <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Sigla</label>
              <input name="sigla" required maxlength="5" class="{{ $inp }} w-24 font-mono uppercase" value="{{ old('sigla') }}" placeholder="POL">
            </div>
          @endif
          @if ($seccion === 'origen')
            <div>
              <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Código ISO</label>
              <input name="iso" maxlength="2" class="{{ $inp }} w-20 font-mono uppercase" value="{{ old('iso') }}" placeholder="MX">
            </div>
            <div>
              <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">o sube la bandera</label>
              <input type="file" name="archivo" accept=".png,.jpg,.jpeg,.webp,.svg" class="text-xs w-52">
            </div>
          @endif
          <button class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg font-semibold text-sm">+ Agregar</button>
          @if ($seccion === 'origen')
            <p class="w-full text-xs text-slate-400">Con el código ISO de 2 letras (MX = México, ES = España, DE = Alemania…) descargo la bandera automáticamente.</p>
          @endif
        </form>

      {{-- ================= Colores personalizados ================= --}}
      @elseif ($seccion === 'colores')
        <h3 class="text-lg font-semibold text-slate-900">Colores personalizados</h3>
        <p class="text-sm text-slate-500 mb-4">
          Colores que no están en el catálogo RAL K7 (ej: códigos de fabricante como UPHG5000). Aparecen como sugerencia
          al escribir el RAL de un producto y le dan su color al cuadrito. El código no se puede cambiar después.
        </p>

        <table class="w-full text-sm">
          <thead class="text-xs uppercase tracking-wider text-slate-500 border-b border-slate-200">
            <tr>
              <th class="text-left py-2 pr-2">Código</th>
              <th class="text-left py-2 pr-2">Nombre</th>
              <th class="text-left py-2 pr-2">Color</th>
              <th class="text-center py-2 pr-2">Productos</th>
              <th></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @forelse ($colores as $c)
              @php $fid = 'f-col-' . md5($c->codigo); @endphp
              <tr>
                <td class="py-2 pr-2 font-mono font-semibold">{{ $c->codigo }}</td>
                <td class="py-2 pr-2"><input form="{{ $fid }}" name="nombre_oficial" value="{{ $c->nombre_oficial }}" required maxlength="120" class="{{ $inp }} w-full"></td>
                <td class="py-2 pr-2">
                  <span class="inline-flex items-center gap-2">
                    <input type="color" value="{{ $c->hex }}" class="w-9 h-8 p-0.5 border border-slate-300 rounded cursor-pointer"
                           oninput="this.nextElementSibling.value=this.value.toUpperCase()">
                    <input form="{{ $fid }}" name="hex" value="{{ $c->hex }}" required maxlength="7" class="{{ $inp }} w-24 font-mono uppercase"
                           oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value)) this.previousElementSibling.value=this.value">
                  </span>
                </td>
                <td class="py-2 pr-2 text-center tabular-nums text-slate-500">{{ $c->num_usos }}</td>
                <td class="py-2 text-right whitespace-nowrap">
                  <button form="{{ $fid }}" class="text-amber-600 hover:underline text-sm font-medium">guardar</button>
                  @if ($c->num_usos === 0)
                    <button form="{{ $fid }}-del" class="text-red-600 hover:underline text-sm ml-3">borrar</button>
                  @else
                    <span class="text-slate-300 text-sm ml-3 cursor-help" title="Lo usan productos">borrar</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="5" class="py-6 text-center text-slate-400">Todavía no hay colores personalizados.</td></tr>
            @endforelse
          </tbody>
        </table>

        @foreach ($colores as $c)
          @php $fid = 'f-col-' . md5($c->codigo); @endphp
          <form id="{{ $fid }}" method="POST" action="{{ route('configuracion.colores.update', $c->codigo) }}" class="hidden">@csrf @method('PATCH')</form>
          <form id="{{ $fid }}-del" method="POST" action="{{ route('configuracion.colores.destroy', $c->codigo) }}" class="hidden"
                onsubmit="return confirm('¿Borrar el color {{ $c->codigo }}?');">@csrf @method('DELETE')</form>
        @endforeach

        <form method="POST" action="{{ route('configuracion.colores.store') }}" class="mt-6 pt-5 border-t border-slate-200 flex flex-wrap items-end gap-3">
          @csrf
          <div>
            <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Código</label>
            <input name="codigo" required maxlength="16" class="{{ $inp }} w-36 font-mono uppercase" value="{{ old('codigo') }}" placeholder="UPHG5000">
          </div>
          <div>
            <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Nombre</label>
            <input name="nombre_oficial" required maxlength="120" class="{{ $inp }} w-56" value="{{ old('nombre_oficial') }}" placeholder="Oro chino">
          </div>
          <div>
            <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Color</label>
            <span class="inline-flex items-center gap-2">
              <input type="color" value="{{ old('hex', '#CBD5E1') }}" class="w-9 h-8 p-0.5 border border-slate-300 rounded cursor-pointer"
                     oninput="this.nextElementSibling.value=this.value.toUpperCase()">
              <input name="hex" required maxlength="7" class="{{ $inp }} w-24 font-mono uppercase" value="{{ old('hex', '#CBD5E1') }}">
            </span>
          </div>
          <button class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg font-semibold text-sm">+ Agregar color</button>
        </form>

      {{-- ================= Parámetros ================= --}}
      @else
        <h3 class="text-lg font-semibold text-slate-900">Parámetros</h3>
        <p class="text-sm text-slate-500 mb-4">Valores por defecto que usa el sistema.</p>

        <form method="POST" action="{{ route('configuracion.parametros.update') }}" class="space-y-4 max-w-xl">
          @csrf @method('PATCH')
          @foreach ($parametros as $p)
            <div class="flex items-center justify-between gap-4">
              <label class="text-sm text-slate-700" for="par-{{ $loop->index }}">{{ $p['titulo'] }}</label>
              <input id="par-{{ $loop->index }}" type="number" name="{{ str_replace('.', '__', $p['clave']) }}" value="{{ $p['valor'] }}"
                     min="{{ $p['min'] }}" max="{{ $p['max'] }}" step="{{ $p['step'] }}" required class="{{ $inp }} w-32 tabular-nums text-right">
            </div>
          @endforeach
          <div class="pt-2">
            <button class="bg-amber-500 hover:bg-amber-600 text-white px-5 py-2 rounded-lg font-semibold text-sm">Guardar parámetros</button>
          </div>
        </form>
        <p class="text-xs text-slate-400 mt-6">
          Los umbrales de stock se proponen al crear un producto (cada producto puede tener los suyos).
          Las etiquetas por caja definen cuántas se imprimen por defecto al recibir un lote.
        </p>
      @endif
    </div>
  </div>
@endsection
