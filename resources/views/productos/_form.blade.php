{{--
  Partial compartido por create y edit.
  Variables esperadas:
    $producto    — instancia (null en create)
    $texturas    — Collection de Textura activas
    $ralCatalogo — Collection del catálogo RAL Classic K7

  La unicidad la maneja el controller, no el form.
--}}

@php
  $isEdit = isset($producto) && $producto && $producto->exists;
  $action = $isEdit ? route('productos.update', $producto->id) : route('productos.store');

  // Resolver valores iniciales para el preview
  $ralActual          = old('ral', $isEdit ? $producto->ral : '');
  $texturaActual      = old('textura_id', $isEdit ? $producto->textura_id : '');
  $brilloActual       = old('brillo_pct', $isEdit ? $producto->brillo_pct : 30);
  $nombreInternoAct   = old('nombre_interno', $isEdit ? $producto->nombre_interno : '');
  $hexOverrideAct     = old('hex_override', $isEdit ? $producto->hex_override : '');
  $stockMinAct        = old('stock_minimo_kg', $isEdit ? $producto->stock_minimo_kg : 50);
  $stockCritAct       = old('stock_critico_kg', $isEdit ? $producto->stock_critico_kg : 20);
  $activoAct          = old('activo', $isEdit ? $producto->activo : true);

  // Mapa de RAL → {nombre, hex} para usar desde JS
  $ralMap = $ralCatalogo->mapWithKeys(fn ($r) => [
    $r->codigo => ['nombre' => $r->nombre_oficial, 'hex' => $r->hex]
  ])->toArray();
@endphp

@if ($errors->any())
  <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4 text-sm">
    <ul class="list-disc list-inside">
      @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
  </div>
@endif

<div class="grid grid-cols-3 gap-6">
  {{-- COLUMNA IZQUIERDA: formulario (2 anchos) --}}
  <form method="POST" action="{{ $action }}" class="col-span-2 bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-5">
    @csrf
    @if ($isEdit) @method('PATCH') @endif

    {{-- RAL + Textura + Brillo --}}
    <div class="grid grid-cols-3 gap-4">
      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">RAL</label>
        <input list="ral-catalogo" type="text" id="ral" name="ral" value="{{ $ralActual }}" required maxlength="16"
               class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono uppercase"
               placeholder="RAL9005" autocomplete="off">
        <datalist id="ral-catalogo">
          @foreach ($ralCatalogo as $r)
            <option value="{{ $r->codigo }}">{{ $r->nombre_oficial }}</option>
          @endforeach
        </datalist>
        <p class="text-xs text-slate-400 mt-1" id="ral-nombre-hint">— elige uno del catálogo o escribe libre —</p>
      </div>
      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Textura</label>
        <select name="textura_id" id="textura_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg">
          <option value="">— elegir —</option>
          @foreach ($texturas as $t)
            <option value="{{ $t->id }}" data-nombre="{{ $t->nombre }}" @selected((string) $texturaActual === (string) $t->id)>
              {{ $t->nombre }}
            </option>
          @endforeach
        </select>
        <p class="text-xs text-slate-400 mt-1">
          ¿Falta alguna? <a href="{{ route('texturas.index') }}" class="text-amber-600 hover:underline">Gestionar texturas</a>
        </p>
      </div>
      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Brillo %</label>
        <input type="number" name="brillo_pct" id="brillo_pct" value="{{ $brilloActual }}" required min="0" max="100"
               class="w-full px-3 py-2 border border-slate-300 rounded-lg tabular-nums">
      </div>
    </div>

    {{-- Nombre interno --}}
    <div>
      <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Nombre interno (opcional)</label>
      <input type="text" name="nombre_interno" value="{{ $nombreInternoAct }}" maxlength="120"
             class="w-full px-3 py-2 border border-slate-300 rounded-lg" placeholder="Negro mate texturizado">
      <p class="text-xs text-slate-400 mt-1">Alias humano para identificarlo más fácil que por el RAL.</p>
    </div>

    {{-- Hex override --}}
    <div>
      <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">
        Color personalizado (opcional)
      </label>
      <div class="flex items-center gap-3">
        <input type="color" id="hex_override_picker"
               value="{{ $hexOverrideAct ?: '#cbd5e1' }}"
               class="w-12 h-10 border border-slate-300 rounded cursor-pointer">
        <input type="text" name="hex_override" id="hex_override" value="{{ $hexOverrideAct }}"
               maxlength="7" pattern="^#[0-9a-fA-F]{6}$"
               placeholder="#RRGGBB"
               class="px-3 py-2 border border-slate-300 rounded-lg font-mono w-32 uppercase">
        <button type="button" id="clear-hex"
                class="text-xs text-slate-500 hover:text-slate-700 underline">usar color del catálogo</button>
      </div>
      <p class="text-xs text-slate-400 mt-1">Si Shadow usa una variante distinta al RAL Classic, ponla aquí. Si lo dejas vacío, se usa el del catálogo.</p>
    </div>

    {{-- Stock mínimo / crítico --}}
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Stock mínimo (kg) — alerta amarilla</label>
        <input type="number" name="stock_minimo_kg" value="{{ $stockMinAct }}" required step="0.001" min="0"
               class="w-full px-3 py-2 border border-slate-300 rounded-lg tabular-nums">
      </div>
      <div>
        <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Stock crítico (kg) — alerta roja</label>
        <input type="number" name="stock_critico_kg" value="{{ $stockCritAct }}" required step="0.001" min="0"
               class="w-full px-3 py-2 border border-slate-300 rounded-lg tabular-nums">
      </div>
    </div>

    <label class="flex items-center gap-2 text-sm">
      <input type="checkbox" name="activo" value="1" @checked($activoAct)> Activo
    </label>

    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
      <a href="{{ route('productos.index') }}" class="px-4 py-2 text-slate-600 hover:text-slate-800">Cancelar</a>
      <button class="bg-amber-500 hover:bg-amber-600 text-white px-5 py-2 rounded-lg font-semibold">
        {{ $isEdit ? 'Guardar cambios' : 'Crear producto' }}
      </button>
    </div>
  </form>

  {{-- COLUMNA DERECHA: preview --}}
  <div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 sticky top-4">
      <p class="text-xs uppercase tracking-wider text-slate-500 font-semibold mb-3">Vista previa</p>
      <div class="aspect-square rounded-lg border border-slate-300 shadow-inner mb-4"
           id="preview-swatch"
           style="background-color: {{ $hexOverrideAct ?: ($ralMap[$ralActual]['hex'] ?? '#cbd5e1') }}"></div>
      <div class="space-y-1">
        <p class="font-mono font-semibold text-slate-900" id="preview-ral">{{ $ralActual ?: 'RAL—' }}</p>
        <p class="text-sm text-slate-500" id="preview-nombre">{{ $ralMap[$ralActual]['nombre'] ?? '—' }}</p>
        <p class="text-sm text-slate-700" id="preview-descripcion">
          @if ($texturaActual)
            {{ $texturas->firstWhere('id', (int) $texturaActual)?->nombre }} · {{ $brilloActual }}%
          @else
            — textura · {{ $brilloActual }}%
          @endif
        </p>
        <p class="text-xs text-slate-400 font-mono" id="preview-hex">
          {{ $hexOverrideAct ?: ($ralMap[$ralActual]['hex'] ?? '#cbd5e1') }}
        </p>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const RAL = @json($ralMap);

  const $ral        = document.getElementById('ral');
  const $textura    = document.getElementById('textura_id');
  const $brillo     = document.getElementById('brillo_pct');
  const $hex        = document.getElementById('hex_override');
  const $hexPicker  = document.getElementById('hex_override_picker');
  const $clearHex   = document.getElementById('clear-hex');

  const $swatch     = document.getElementById('preview-swatch');
  const $pRal       = document.getElementById('preview-ral');
  const $pNombre    = document.getElementById('preview-nombre');
  const $pDesc      = document.getElementById('preview-descripcion');
  const $pHex       = document.getElementById('preview-hex');
  const $ralHint    = document.getElementById('ral-nombre-hint');

  const FALLBACK = '#cbd5e1';

  function colorResuelto() {
    const override = ($hex.value || '').trim();
    if (/^#[0-9a-fA-F]{6}$/.test(override)) return override.toUpperCase();
    const code = ($ral.value || '').trim().toUpperCase();
    return RAL[code]?.hex || FALLBACK;
  }

  function refresh() {
    const code = ($ral.value || '').trim().toUpperCase();
    const entry = RAL[code];
    const c = colorResuelto();

    $swatch.style.backgroundColor = c;
    $pHex.textContent = c;
    $pRal.textContent = code || 'RAL—';
    $pNombre.textContent = entry?.nombre || '—';
    $ralHint.textContent = entry
      ? '✓ ' + entry.nombre + ' (catálogo K7)'
      : (code ? '⚠ no está en el catálogo K7 — color personalizado requerido' : '— elige uno del catálogo o escribe libre —');

    const tx = $textura.options[$textura.selectedIndex]?.dataset?.nombre || '— textura';
    const br = $brillo.value || '0';
    $pDesc.textContent = `${tx} · ${br}%`;
  }

  $ral.addEventListener('input', () => {
    // Si cambió al RAL del catálogo y no hay hex_override activo, sincroniza el picker
    const entry = RAL[($ral.value || '').trim().toUpperCase()];
    if (entry && !$hex.value) $hexPicker.value = entry.hex;
    refresh();
  });
  $textura.addEventListener('change', refresh);
  $brillo.addEventListener('input', refresh);
  $hex.addEventListener('input', () => {
    if (/^#[0-9a-fA-F]{6}$/.test($hex.value)) $hexPicker.value = $hex.value;
    refresh();
  });
  $hexPicker.addEventListener('input', () => {
    $hex.value = $hexPicker.value.toUpperCase();
    refresh();
  });
  $clearHex.addEventListener('click', () => {
    $hex.value = '';
    const entry = RAL[($ral.value || '').trim().toUpperCase()];
    if (entry) $hexPicker.value = entry.hex;
    refresh();
  });

  // Init
  refresh();
})();
</script>
