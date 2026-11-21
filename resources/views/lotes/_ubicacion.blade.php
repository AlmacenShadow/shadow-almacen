{{--
  Selector de ubicación (estante + cara). Compartido por create y edit.
  Variables: $actual (clave o null), $obligatorio (bool).
--}}
@php
  $opcUbic = \App\Support\Catalogo::opciones('ubicacion', true, $actual ?? null);
@endphp
<div>
  <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1" for="ubicacion">
    Ubicación (estante y cara){{ ($obligatorio ?? false) ? '' : ' — opcional' }}
  </label>
  <select name="ubicacion" id="ubicacion" @if ($obligatorio ?? false) required @endif
          class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white">
    <option value="">— elegir —</option>
    @foreach ($opcUbic as $clave => $nombre)
      <option value="{{ $clave }}" @selected(old('ubicacion', $actual ?? null) === $clave)>{{ $nombre }}</option>
    @endforeach
  </select>
  <p class="text-xs text-slate-400 mt-1">A = cara derecha, B = cara izquierda del estante.</p>
</div>
