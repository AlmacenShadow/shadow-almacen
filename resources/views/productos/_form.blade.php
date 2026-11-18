{{--
  Partial compartido por create y edit.
  Variables esperadas:
    $producto    — instancia (null en create)
    $ralCatalogo — Collection del catálogo RAL Classic K7

  La textura ya no se elige de una lista: se describe con atributos
  estructurados (acabado, superficie, tipo de textura, metalizado) y el
  controller deriva la textura_id legacy que usan vistas y etiquetas.
  La unicidad la maneja el controller, no el form.
--}}

@php
  use App\Models\Producto;

  $isEdit = isset($producto) && $producto && $producto->exists;
  $action = $isEdit ? route('productos.update', $producto->id) : route('productos.store');
  $v = fn (string $campo, $default = null) => old($campo, $isEdit ? $producto->{$campo} : $default);

  $ralActual       = $v('ral', '');
  $tipoActual      = $v('tipo_pintura', '');
  $descActual      = $v('descripcion', '');
  $brilloActual    = $v('brillo_pct', 30);
  $acabadoActual   = $v('acabado', 'mate');
  $superfActual    = $v('superficie', 'liso');
  $texturaTipoAct  = $v('textura_tipo', '');
  $metalizadoAct   = (bool) old('metalizado', $isEdit ? $producto->metalizado : false);
  $origenActual    = $v('origen', '');
  $marcaActual     = $v('marca', '');
  $refActual       = $v('referencia', '');
  $curadoTempAct   = $v('curado_temp_c', '');
  $curadoTiempoAct = $v('curado_tiempo_min', '');
  $hexOverrideAct  = $v('hex_override', '');
  $stockMinAct     = $v('stock_minimo_kg', 50);
  $stockCritAct    = $v('stock_critico_kg', 20);
  $activoAct       = old('activo', $isEdit ? $producto->activo : true);

  // Mapa de RAL → {nombre, hex} para usar desde JS
  $ralMap = $ralCatalogo->mapWithKeys(fn ($r) => [
    $r->codigo => ['nombre' => $r->nombre_oficial, 'hex' => $r->hex]
  ])->toArray();

  $lbl = 'block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1';
  $inp = 'w-full px-3 py-2 border border-slate-300 rounded-lg';
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
  <form method="POST" action="{{ $action }}" class="col-span-2 bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-6">
    @csrf
    @if ($isEdit) @method('PATCH') @endif

    {{-- 1. Identidad: RAL + tipo de pintura --}}
    <fieldset class="space-y-4">
      <legend class="text-sm font-bold text-slate-800 mb-1">Color y tipo</legend>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="{{ $lbl }}">RAL</label>
          <input list="ral-catalogo" type="text" id="ral" name="ral" value="{{ $ralActual }}" required maxlength="16"
                 class="{{ $inp }} font-mono uppercase" placeholder="RAL9005" autocomplete="off">
          <datalist id="ral-catalogo">
            @foreach ($ralCatalogo as $r)
              <option value="{{ $r->codigo }}">{{ $r->nombre_oficial }}</option>
            @endforeach
          </datalist>
          <p class="text-xs text-slate-400 mt-1" id="ral-nombre-hint">— elige uno del catálogo o escribe libre —</p>
        </div>
        <div>
          <label class="{{ $lbl }}">Tipo de pintura (opcional)</label>
          <select name="tipo_pintura" class="{{ $inp }}">
            <option value="">— sin especificar —</option>
            @foreach (\App\Models\Producto::TIPOS_PINTURA as $t)
              <option value="{{ $t }}" @selected($tipoActual === $t)>{{ $t }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div>
        <label class="{{ $lbl }}">Descripción (opcional)</label>
        <input type="text" name="descripcion" value="{{ $descActual }}" maxlength="255" class="{{ $inp }}"
               placeholder="Ej: blanco para estructuras exteriores">
      </div>
    </fieldset>

    {{-- 2. Textura estructurada --}}
    <fieldset class="space-y-4 pt-4 border-t border-slate-100">
      <legend class="text-sm font-bold text-slate-800 mb-1">Acabado y textura</legend>
      <div class="grid grid-cols-3 gap-4">
        <div>
          <label class="{{ $lbl }}">Acabado</label>
          <select name="acabado" id="acabado" required class="{{ $inp }}">
            @foreach (\App\Models\Producto::ACABADOS as $k => $txt)
              <option value="{{ $k }}" @selected($acabadoActual === $k)>{{ $txt }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="{{ $lbl }}">Superficie</label>
          <select name="superficie" id="superficie" required class="{{ $inp }}">
            @foreach (\App\Models\Producto::SUPERFICIES as $k => $txt)
              <option value="{{ $k }}" @selected($superfActual === $k)>{{ $txt }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="{{ $lbl }}">Brillo %</label>
          <input type="number" name="brillo_pct" id="brillo_pct" value="{{ $brilloActual }}" required min="0" max="100"
                 class="{{ $inp }} tabular-nums">
        </div>
      </div>
      <div class="grid grid-cols-3 gap-4 items-end">
        <div id="grupo-textura-tipo" class="col-span-2">
          <label class="{{ $lbl }}">Tipo de textura</label>
          <select name="textura_tipo" id="textura_tipo" class="{{ $inp }}">
            <option value="">— elegir —</option>
            @foreach (\App\Models\Producto::TEXTURA_TIPOS as $k => $txt)
              <option value="{{ $k }}" @selected($texturaTipoAct === $k)>{{ $txt }}</option>
            @endforeach
          </select>
        </div>
        <label class="flex items-center gap-2 text-sm pb-2">
          <input type="checkbox" name="metalizado" id="metalizado" value="1" @checked($metalizadoAct)> Metalizado
        </label>
      </div>
    </fieldset>

    {{-- 3. Proveedor y ficha técnica --}}
    <fieldset class="space-y-4 pt-4 border-t border-slate-100">
      <legend class="text-sm font-bold text-slate-800 mb-1">Proveedor y ficha técnica <span class="font-normal text-slate-400">(opcional)</span></legend>
      <div class="grid grid-cols-3 gap-4">
        <div>
          <label class="{{ $lbl }}">Origen</label>
          <select name="origen" class="{{ $inp }}">
            <option value="">— sin especificar —</option>
            @foreach (\App\Models\Producto::ORIGENES as $k => $txt)
              <option value="{{ $k }}" @selected($origenActual === $k)>{{ $txt }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="{{ $lbl }}">Marca</label>
          <input type="text" name="marca" value="{{ $marcaActual }}" maxlength="80" class="{{ $inp }}">
        </div>
        <div>
          <label class="{{ $lbl }}">Referencia</label>
          <input type="text" name="referencia" value="{{ $refActual }}" maxlength="80" class="{{ $inp }} font-mono"
                 placeholder="código del fabricante">
        </div>
      </div>
      <div class="grid grid-cols-3 gap-4">
        <div>
          <label class="{{ $lbl }}">Curado: temperatura (°C)</label>
          <input type="number" name="curado_temp_c" value="{{ $curadoTempAct }}" min="0" max="400" class="{{ $inp }} tabular-nums"
                 placeholder="ej. 200">
        </div>
        <div>
          <label class="{{ $lbl }}">Curado: tiempo (min)</label>
          <input type="number" name="curado_tiempo_min" value="{{ $curadoTiempoAct }}" min="0" max="600" class="{{ $inp }} tabular-nums"
                 placeholder="ej. 10">
        </div>
      </div>
    </fieldset>

    {{-- 4. Color personalizado --}}
    <fieldset class="pt-4 border-t border-slate-100">
      <label class="{{ $lbl }}">Color personalizado (opcional)</label>
      <div class="flex items-center gap-3">
        <input type="color" id="hex_override_picker"
               value="{{ $hexOverrideAct ?: '#cbd5e1' }}"
               class="w-12 h-10 border border-slate-300 rounded cursor-pointer">
        <input type="text" name="hex_override" id="hex_override" value="{{ $hexOverrideAct }}"
               maxlength="7" pattern="^#[0-9a-fA-F]{6}$" placeholder="#RRGGBB"
               class="px-3 py-2 border border-slate-300 rounded-lg font-mono w-32 uppercase">
        <button type="button" id="clear-hex"
                class="text-xs text-slate-500 hover:text-slate-700 underline">usar color del catálogo</button>
      </div>
      <p class="text-xs text-slate-400 mt-1">Si Shadow usa una variante distinta al RAL Classic, ponla aquí. Si lo dejas vacío, se usa el del catálogo.</p>
    </fieldset>

    {{-- 5. Stock mínimo / crítico --}}
    <fieldset class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-100">
      <div>
        <label class="{{ $lbl }}">Stock mínimo (kg) — alerta amarilla</label>
        <input type="number" name="stock_minimo_kg" value="{{ $stockMinAct }}" required step="0.001" min="0"
               class="{{ $inp }} tabular-nums">
      </div>
      <div>
        <label class="{{ $lbl }}">Stock crítico (kg) — alerta roja</label>
        <input type="number" name="stock_critico_kg" value="{{ $stockCritAct }}" required step="0.001" min="0"
               class="{{ $inp }} tabular-nums">
      </div>
    </fieldset>

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
        <p class="text-sm text-slate-700" id="preview-descripcion">—</p>
        <p class="text-xs text-slate-400 font-mono" id="preview-hex">
          {{ $hexOverrideAct ?: ($ralMap[$ralActual]['hex'] ?? '#cbd5e1') }}
        </p>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const RAL         = @json($ralMap);
  const ACABADOS    = @json(\App\Models\Producto::ACABADOS);
  const SUPERFICIES = @json(\App\Models\Producto::SUPERFICIES);
  const TEXTURAS    = @json(\App\Models\Producto::TEXTURA_TIPOS);

  const $ral        = document.getElementById('ral');
  const $acabado    = document.getElementById('acabado');
  const $superficie = document.getElementById('superficie');
  const $texTipo    = document.getElementById('textura_tipo');
  const $grupoTex   = document.getElementById('grupo-textura-tipo');
  const $metal      = document.getElementById('metalizado');
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

  // Mismo criterio que \App\Models\Producto::getTexturaLabelAttribute()
  function texturaLabel() {
    const partes = [ACABADOS[$acabado.value], SUPERFICIES[$superficie.value]];
    if ($superficie.value === 'texturizado' && $texTipo.value) partes.push(TEXTURAS[$texTipo.value]);
    if ($metal.checked) partes.push('Metalizado');
    return partes.filter(Boolean).join(' · ');
  }

  function refresh() {
    const code = ($ral.value || '').trim().toUpperCase();
    const entry = RAL[code];
    const c = colorResuelto();

    // El tipo de textura solo aplica a superficie texturizada
    const texturizado = $superficie.value === 'texturizado';
    $grupoTex.style.visibility = texturizado ? 'visible' : 'hidden';
    $texTipo.required = texturizado;

    $swatch.style.backgroundColor = c;
    $pHex.textContent = c;
    $pRal.textContent = code || 'RAL—';
    $pNombre.textContent = entry?.nombre || '—';
    $ralHint.textContent = entry
      ? '✓ ' + entry.nombre + ' (catálogo K7)'
      : (code ? '⚠ no está en el catálogo K7 — color personalizado requerido' : '— elige uno del catálogo o escribe libre —');
    $pDesc.textContent = `${texturaLabel()} · ${$brillo.value || '0'}%`;
  }

  $ral.addEventListener('input', () => {
    const entry = RAL[($ral.value || '').trim().toUpperCase()];
    if (entry && !$hex.value) $hexPicker.value = entry.hex;
    refresh();
  });
  [$acabado, $superficie, $texTipo, $metal].forEach(el => el.addEventListener('change', refresh));
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

  refresh();
})();
</script>
