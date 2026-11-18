{{--
  Partial compartido por create y edit. Sigue el mockup aprobado
  (artifact "Nuevo producto — mockup"): tarjetas Identidad / Textura en
  cascada / Procedencia y ficha técnica, píldoras en vez de selects y
  vista previa con resumen.

  Variables esperadas:
    $producto    — instancia (null en create)
    $ralCatalogo — Collection del catálogo RAL Classic K7

  Las píldoras son radios estilizados: el form se envía sin JS. El JS solo
  maneja la cascada de textura, deseleccionar origen y la vista previa.
  La unicidad la maneja el controller.
--}}

@php
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

  $ralMap = $ralCatalogo->mapWithKeys(fn ($r) => [
    $r->codigo => ['nombre' => $r->nombre_oficial, 'hex' => $r->hex]
  ])->toArray();

  $banderas = [
    'china'    => '<svg class="flag" viewBox="0 0 30 20"><rect width="30" height="20" fill="#de2910"/><polygon points="7,4 7.9,6.5 10.5,6.5 8.4,8.1 9.2,10.6 7,9 4.8,10.6 5.6,8.1 3.5,6.5 6.1,6.5" fill="#ffde00"/></svg>',
    'colombia' => '<svg class="flag" viewBox="0 0 30 20"><rect width="30" height="20" fill="#fcd116"/><rect y="10" width="30" height="5" fill="#003893"/><rect y="15" width="30" height="5" fill="#ce1126"/></svg>',
    'usa'      => '<svg class="flag" viewBox="0 0 30 20"><rect width="30" height="20" fill="#ffffff"/><g fill="#b22234"><rect width="30" height="2"/><rect y="4" width="30" height="2"/><rect y="8" width="30" height="2"/><rect y="12" width="30" height="2"/><rect y="16" width="30" height="2"/></g><rect width="12" height="11" fill="#3c3b6e"/></svg>',
    'panama'   => '<svg class="flag" viewBox="0 0 30 20"><rect width="30" height="20" fill="#ffffff"/><rect x="15" width="15" height="10" fill="#d21034"/><rect y="10" width="15" height="10" fill="#005293"/><polygon points="7.5,2.5 8.3,4.8 10.8,4.8 8.8,6.3 9.5,8.7 7.5,7.2 5.5,8.7 6.2,6.3 4.2,4.8 6.7,4.8" fill="#005293"/><polygon points="22.5,12.5 23.3,14.8 25.8,14.8 23.8,16.3 24.5,18.7 22.5,17.2 20.5,18.7 21.2,16.3 19.2,14.8 21.7,14.8" fill="#d21034"/></svg>',
  ];
@endphp

<style>
  .pf-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;box-shadow:0 1px 2px rgba(15,23,42,.04);padding:22px}
  .pf-card + .pf-card{margin-top:18px}
  .pf-st{font-weight:600;font-size:1.02rem;color:#0f172a;margin:0 0 2px}
  .pf-sd{color:#94a3b8;font-size:.78rem;margin:0 0 14px}
  .pf-lbl{display:block;font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;font-weight:600;color:#64748b;margin-bottom:.4rem}
  .pf-lbl .opt{color:#d97706;font-weight:700;text-transform:none;letter-spacing:0;margin-left:.3rem}
  .pf-field{margin-top:16px}
  .pf-field:first-of-type{margin-top:0}
  .pf-in{width:100%;padding:.55rem .7rem;border:1px solid #cbd5e1;border-radius:10px;background:#f8fafc;color:#0f172a;font:inherit}
  .pf-in:focus{outline:2px solid #f59e0b;outline-offset:1px;border-color:#f59e0b}
  .pills{display:flex;flex-wrap:wrap;gap:8px}
  .pill{position:relative;cursor:pointer;border:1px solid #cbd5e1;background:#fff;color:#334155;border-radius:999px;padding:.42rem .9rem;
        font-size:.86rem;font-weight:500;display:inline-flex;align-items:center;gap:.4rem;user-select:none;
        transition:transform .08s ease,background .12s,border-color .12s,color .12s}
  .pill:hover{border-color:#f59e0b}
  .pill:active{transform:scale(.96)}
  .pill input{position:absolute;opacity:0;pointer-events:none;width:0;height:0}
  .pill:has(input:checked){background:#f59e0b;border-color:#f59e0b;color:#fff;font-weight:600;box-shadow:0 1px 6px rgba(245,158,11,.35)}
  .pill:has(input:focus-visible){outline:2px solid #d97706;outline-offset:2px}
  .pill .dot{width:8px;height:8px;border-radius:50%;background:currentColor;opacity:.55}
  .flag{display:inline-block;width:22px;height:15px;border-radius:2px;box-shadow:0 0 0 1px rgba(0,0,0,.12);flex-shrink:0}
  .cascade{margin-top:14px;padding-left:14px;border-left:2px solid #f59e0b;animation:pfslide .18s ease}
  @keyframes pfslide{from{opacity:0;transform:translateY(-4px)}to{opacity:1;transform:none}}
  .pf-hint{color:#94a3b8;font-size:.75rem;margin-top:.4rem}
  .pv-tipo{display:inline-block;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;
           background:#fef3c7;color:#92400e;padding:.2rem .55rem;border-radius:6px;margin-bottom:10px}
  .pv-line{display:flex;gap:.5rem;align-items:flex-start;font-size:.84rem;margin-top:10px;color:#0f172a}
  .pv-line .k{color:#94a3b8;min-width:74px;flex-shrink:0}
  .pv-line .flag{margin-right:.3rem;vertical-align:-2px}
  .pv-summary{margin-top:14px;padding-top:14px;border-top:1px dashed #cbd5e1;font-size:.9rem;font-weight:600;line-height:1.6}
</style>

@if ($errors->any())
  <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4 text-sm">
    <ul class="list-disc list-inside">
      @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
  </div>
@endif

<div class="grid grid-cols-3 gap-6 items-start">
  <form method="POST" action="{{ $action }}" class="col-span-2" id="form-producto">
    @csrf
    @if ($isEdit) @method('PATCH') @endif

    {{-- ===== Identidad ===== --}}
    <div class="pf-card">
      <p class="pf-st">Identidad</p>
      <p class="pf-sd">Qué pintura es y cómo la reconoces.</p>

      <div class="pf-field">
        <label class="pf-lbl" for="descripcion">Descripción <span class="opt">opcional</span></label>
        <input id="descripcion" name="descripcion" type="text" value="{{ $descActual }}" maxlength="255"
               class="pf-in" placeholder="Texto libre, aparte del RAL">
      </div>

      <div class="grid grid-cols-2 gap-4 pf-field">
        <div>
          <label class="pf-lbl" for="ral">RAL</label>
          <input list="ral-catalogo" type="text" id="ral" name="ral" value="{{ $ralActual }}" required maxlength="16"
                 class="pf-in font-mono uppercase" placeholder="RAL9005" autocomplete="off">
          <datalist id="ral-catalogo">
            @foreach ($ralCatalogo as $r)
              <option value="{{ $r->codigo }}">{{ $r->nombre_oficial }}</option>
            @endforeach
          </datalist>
          <p class="pf-hint" id="ral-nombre-hint">— elige uno del catálogo o escribe libre —</p>
        </div>
        <div>
          <label class="pf-lbl" for="tipo_pintura">Tipo de pintura</label>
          <select id="tipo_pintura" name="tipo_pintura" class="pf-in">
            <option value="">— elegir —</option>
            @foreach (\App\Models\Producto::TIPOS_PINTURA as $t)
              <option value="{{ $t }}" @selected($tipoActual === $t)>{{ $t }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="pf-field">
        <label class="pf-lbl">Color personalizado <span class="opt">opcional</span></label>
        <div class="flex items-center gap-3 flex-wrap">
          <input type="color" id="hex_override_picker" value="{{ $hexOverrideAct ?: '#cbd5e1' }}"
                 class="w-12 h-10 p-0.5 border border-slate-300 rounded-lg cursor-pointer bg-slate-50">
          <input type="text" name="hex_override" id="hex_override" value="{{ $hexOverrideAct }}"
                 maxlength="7" pattern="^#[0-9a-fA-F]{6}$" placeholder="#RRGGBB"
                 class="pf-in font-mono uppercase" style="width:120px">
          <button type="button" id="clear-hex" class="text-xs text-slate-500 hover:text-amber-600 underline">usar color del RAL</button>
        </div>
        <p class="pf-hint">Si el color difiere del RAL (variante de Shadow), elígelo aquí. Si lo dejas vacío, se usa el del RAL.</p>
      </div>
    </div>

    {{-- ===== Textura en cascada ===== --}}
    <div class="pf-card">
      <p class="pf-st">Textura</p>
      <p class="pf-sd">Toca las píldoras; las opciones de textura solo aparecen si aplican.</p>

      <div class="pf-field">
        <span class="pf-lbl">Acabado</span>
        <div class="pills">
          @foreach (\App\Models\Producto::ACABADOS as $k => $txt)
            <label class="pill"><input type="radio" name="acabado" value="{{ $k }}" required @checked($acabadoActual === $k)><span class="dot"></span>{{ $txt }}</label>
          @endforeach
        </div>
      </div>

      <div class="pf-field">
        <span class="pf-lbl">Superficie</span>
        <div class="pills">
          @foreach (\App\Models\Producto::SUPERFICIES as $k => $txt)
            <label class="pill"><input type="radio" name="superficie" value="{{ $k }}" required @checked($superfActual === $k)>{{ $txt }}</label>
          @endforeach
        </div>
        <div class="cascade" id="c-textura" @if ($superfActual !== 'texturizado') hidden @endif>
          <span class="pf-lbl">Tipo de textura</span>
          <div class="pills">
            @foreach (\App\Models\Producto::TEXTURA_TIPOS as $k => $txt)
              <label class="pill"><input type="radio" name="textura_tipo" value="{{ $k }}" @checked($texturaTipoAct === $k)>{{ $txt }}</label>
            @endforeach
          </div>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4 pf-field">
        <div>
          <span class="pf-lbl">Metalizado</span>
          <div class="pills">
            <label class="pill"><input type="radio" name="metalizado" value="0" @checked(!$metalizadoAct)>No</label>
            <label class="pill"><input type="radio" name="metalizado" value="1" @checked($metalizadoAct)>✨ Sí</label>
          </div>
        </div>
        <div>
          <label class="pf-lbl" for="brillo_pct">Brillo %</label>
          <input type="number" name="brillo_pct" id="brillo_pct" value="{{ $brilloActual }}" required min="0" max="100"
                 class="pf-in tabular-nums" style="max-width:120px">
        </div>
      </div>
    </div>

    {{-- ===== Procedencia y ficha técnica ===== --}}
    <div class="pf-card">
      <p class="pf-st">Procedencia y ficha técnica</p>
      <p class="pf-sd">De dónde viene y cómo se hornea.</p>

      <div class="pf-field">
        <span class="pf-lbl">Origen <span class="opt">opcional · toca de nuevo para quitar</span></span>
        <div class="pills" id="g-origen">
          @foreach (\App\Models\Producto::ORIGENES as $k => $txt)
            <label class="pill"><input type="radio" name="origen" value="{{ $k }}" @checked($origenActual === $k)>{!! $banderas[$k] ?? '' !!} {{ $txt }}</label>
          @endforeach
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4 pf-field">
        <div>
          <label class="pf-lbl" for="marca">Marca / fabricante</label>
          <input id="marca" name="marca" type="text" value="{{ $marcaActual }}" maxlength="80" class="pf-in"
                 placeholder="Ej: Interpon, Sherwin, Tiger…">
        </div>
        <div>
          <label class="pf-lbl" for="referencia">Referencia del fabricante</label>
          <input id="referencia" name="referencia" type="text" value="{{ $refActual }}" maxlength="80" class="pf-in"
                 placeholder="Código del proveedor">
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4 pf-field">
        <div>
          <label class="pf-lbl" for="curado_temp_c">Temperatura de curado (°C)</label>
          <input id="curado_temp_c" name="curado_temp_c" type="number" value="{{ $curadoTempAct }}" min="0" max="400" step="5"
                 class="pf-in tabular-nums" placeholder="ej. 200">
        </div>
        <div>
          <label class="pf-lbl" for="curado_tiempo_min">Tiempo de curado (min)</label>
          <input id="curado_tiempo_min" name="curado_tiempo_min" type="number" value="{{ $curadoTiempoAct }}" min="0" max="600" step="1"
                 class="pf-in tabular-nums" placeholder="ej. 10">
        </div>
      </div>
    </div>

    {{-- ===== Inventario (no estaba en el mockup, el sistema lo necesita) ===== --}}
    <div class="pf-card">
      <p class="pf-st">Inventario</p>
      <p class="pf-sd">Umbrales para las alertas de reposición.</p>

      <div class="grid grid-cols-2 gap-4 pf-field">
        <div>
          <label class="pf-lbl" for="stock_minimo_kg">Stock mínimo (kg) — alerta amarilla</label>
          <input type="number" id="stock_minimo_kg" name="stock_minimo_kg" value="{{ $stockMinAct }}" required step="0.001" min="0" class="pf-in tabular-nums">
        </div>
        <div>
          <label class="pf-lbl" for="stock_critico_kg">Stock crítico (kg) — alerta roja</label>
          <input type="number" id="stock_critico_kg" name="stock_critico_kg" value="{{ $stockCritAct }}" required step="0.001" min="0" class="pf-in tabular-nums">
        </div>
      </div>

      <label class="flex items-center gap-2 text-sm pf-field">
        <input type="checkbox" name="activo" value="1" @checked($activoAct)> Activo
      </label>

      <div class="flex justify-end gap-3 mt-5 pt-4 border-t border-slate-200">
        <a href="{{ route('productos.index') }}" class="px-4 py-2 text-slate-500 hover:text-slate-800">Cancelar</a>
        <button class="bg-amber-500 hover:bg-amber-600 text-white px-5 py-2 rounded-lg font-semibold">
          {{ $isEdit ? 'Guardar cambios' : 'Crear producto' }}
        </button>
      </div>
    </div>
  </form>

  {{-- ===== Vista previa ===== --}}
  <div class="pf-card sticky top-4">
    <span class="pf-lbl">Vista previa</span>
    <div class="aspect-square rounded-xl border border-slate-300 mb-4" id="pv-swatch"
         style="box-shadow:inset 0 2px 8px rgba(15,23,42,.12);background-color: {{ $hexOverrideAct ?: ($ralMap[$ralActual]['hex'] ?? '#cbd5e1') }}"></div>
    <span class="pv-tipo" id="pv-tipo">{{ $tipoActual ?: '—' }}</span>
    <div class="font-mono font-bold text-lg text-slate-900" id="pv-ral">{{ $ralActual ?: 'RAL—' }}</div>
    <div class="text-xs text-slate-400" id="pv-nombre">{{ $ralMap[$ralActual]['nombre'] ?? '' }}</div>
    <div class="text-sm text-slate-500 mt-0.5" id="pv-desc">{{ $descActual ?: '—' }}</div>

    <div class="pv-line"><span class="k">Textura</span><span id="pv-textura">—</span></div>
    <div class="pv-line"><span class="k">Origen</span><span id="pv-origen">—</span></div>
    <div class="pv-line"><span class="k">Marca</span><span id="pv-marca">—</span></div>
    <div class="pv-line"><span class="k">Curado</span><span id="pv-curado">—</span></div>

    <div class="pv-summary" id="pv-summary"></div>
  </div>
</div>

<script>
(function () {
  const RAL         = @json($ralMap);
  const ACABADOS    = @json(\App\Models\Producto::ACABADOS);
  const SUPERFICIES = @json(\App\Models\Producto::SUPERFICIES);
  const TEXTURAS    = @json(\App\Models\Producto::TEXTURA_TIPOS);
  const ORIGENES    = @json(\App\Models\Producto::ORIGENES);
  const BANDERAS    = @json($banderas);
  const FALLBACK    = '#cbd5e1';

  const form  = document.getElementById('form-producto');
  const $     = (id) => document.getElementById(id);
  const radio = (name) => form.querySelector(`input[name="${name}"]:checked`)?.value || '';

  const $ral = $('ral'), $hex = $('hex_override'), $pick = $('hex_override_picker');

  function colorResuelto() {
    const ov = ($hex.value || '').trim();
    if (/^#[0-9a-fA-F]{6}$/.test(ov)) return ov.toUpperCase();
    return RAL[($ral.value || '').trim().toUpperCase()]?.hex || FALLBACK;
  }

  // Mismo criterio que Producto::getTexturaLabelAttribute()
  function texturaLabel() {
    const sup = radio('superficie');
    const partes = [ACABADOS[radio('acabado')], SUPERFICIES[sup]];
    if (sup === 'texturizado' && radio('textura_tipo')) partes.push(TEXTURAS[radio('textura_tipo')]);
    if (radio('metalizado') === '1') partes.push('Metalizado');
    return partes.filter(Boolean);
  }

  function render() {
    // Cascada: tipo de textura solo con superficie texturizada
    const texturizado = radio('superficie') === 'texturizado';
    $('c-textura').hidden = !texturizado;
    form.querySelectorAll('input[name="textura_tipo"]').forEach(r => {
      r.required = texturizado;
      if (!texturizado) r.checked = false;
    });

    const code  = ($ral.value || '').trim().toUpperCase();
    const entry = RAL[code];
    $('pv-swatch').style.backgroundColor = colorResuelto();
    $('pv-ral').textContent    = code || 'RAL—';
    $('pv-nombre').textContent = entry?.nombre || '';
    $('ral-nombre-hint').textContent = entry
      ? '✓ ' + entry.nombre + ' (catálogo K7)'
      : (code ? '⚠ no está en el catálogo K7 — usa color personalizado' : '— elige uno del catálogo o escribe libre —');

    const tipo = $('tipo_pintura').value;
    $('pv-tipo').textContent = tipo || '—';
    $('pv-desc').textContent = $('descripcion').value || '—';

    const tx = texturaLabel();
    const brillo = $('brillo_pct').value;
    $('pv-textura').textContent = tx.length ? tx.join(' · ') + (brillo ? ` · ${brillo}%` : '') : '—';

    const org = radio('origen');
    $('pv-origen').innerHTML = org ? (BANDERAS[org] || '') + ORIGENES[org] : '—';

    $('pv-marca').textContent  = [$('marca').value, $('referencia').value].filter(Boolean).join(' · ') || '—';
    const t = $('curado_temp_c').value, m = $('curado_tiempo_min').value;
    $('pv-curado').textContent = [t ? `${t} °C` : '', m ? `${m} min` : ''].filter(Boolean).join(' · ') || '—';

    const resumen = [tipo, code].concat(tx).filter(Boolean).join(' · ');
    $('pv-summary').innerHTML = '<span class="text-slate-500 font-normal">Resumen:</span> '
      + (resumen || '—') + (org ? ' · ' + (BANDERAS[org] || '') + ORIGENES[org] : '');
  }

  // Origen es opcional: tocar la píldora ya marcada la desmarca
  document.querySelectorAll('#g-origen input').forEach(r => {
    const lbl = r.closest('label');
    lbl.addEventListener('pointerdown', () => { r.dataset.estaba = r.checked ? '1' : ''; });
    lbl.addEventListener('click', (e) => {
      if (r.dataset.estaba === '1') { e.preventDefault(); r.checked = false; r.dataset.estaba = ''; render(); }
    });
  });

  $ral.addEventListener('input', () => {
    const entry = RAL[($ral.value || '').trim().toUpperCase()];
    if (entry && !$hex.value) $pick.value = entry.hex;
  });
  $hex.addEventListener('input', () => { if (/^#[0-9a-fA-F]{6}$/.test($hex.value)) $pick.value = $hex.value; });
  $pick.addEventListener('input', () => { $hex.value = $pick.value.toUpperCase(); render(); });
  $('clear-hex').addEventListener('click', () => {
    $hex.value = '';
    const entry = RAL[($ral.value || '').trim().toUpperCase()];
    if (entry) $pick.value = entry.hex;
    render();
  });

  form.addEventListener('input', render);
  form.addEventListener('change', render);
  render();
})();
</script>
