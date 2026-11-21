{{--
  Buscador + mapa de lotes (solo consulta). Compartido por el panel (/buscar) y el celular (/c/{token}).
  Variables: $urlDatos, $urlMovs (con __CODIGO__), $urlFicha (opcional, panel), $modo ('panel'|'movil').
  El plano reproduce el dibujo del almacén: islas 2–7 con cara B a la izquierda y A a la derecha;
  estante 1 horizontal con B arriba y A abajo; puerta abajo y la tablet junto a la entrada.
--}}
<div id="consulta" class="space-y-4">
  {{-- Buscador --}}
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-3 sm:p-4">
    <div class="flex items-center gap-2">
      <svg class="w-5 h-5 text-slate-400 flex-shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="9" r="6"/><path d="m14 14 4 4"/></svg>
      <input id="cq" type="search" autocomplete="off" spellcheck="false"
             placeholder="Color, RAL, cliente, marca, lote, estante…"
             class="flex-1 min-w-0 px-3 py-2.5 text-base border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-400">
    </div>
    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mt-2 text-sm">
      <label class="inline-flex items-center gap-1.5 text-slate-600"><input type="checkbox" id="cvacios"> incluir lotes sin stock</label>
      <span id="cfiltro-ubic" hidden class="inline-flex items-center gap-1 bg-sky-100 text-sky-900 px-2 py-0.5 rounded-full">
        solo <b id="cfiltro-ubic-txt"></b> <button type="button" id="cfiltro-ubic-x" class="ml-1 font-bold" aria-label="Quitar filtro de estante">✕</button>
      </span>
      <span id="cresumen" class="text-slate-500 ml-auto"></span>
    </div>
  </div>

  {{-- Resultados --}}
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
    <ul id="clista" class="divide-y divide-slate-100 max-h-[55vh] overflow-auto"></ul>
    <p id="cvacio" hidden class="p-6 text-center text-slate-400">Ningún lote coincide.</p>
    <p id="ccargando" class="p-6 text-center text-slate-400">Cargando lotes…</p>
  </div>

  {{-- Mapa --}}
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-3 sm:p-4">
    <div class="flex items-center justify-between mb-2">
      <h3 class="font-semibold text-slate-800">Mapa del almacén</h3>
      <span class="text-xs text-slate-400">Toca una cara del estante para ver solo sus lotes</span>
    </div>
    <div id="cmapa"></div>
  </div>
</div>

{{-- Ficha de un lote (hoja deslizable) --}}
<div id="cficha" hidden class="fixed inset-0 z-50 bg-slate-900/50 flex items-end sm:items-center justify-center p-0 sm:p-6">
  <div class="bg-white w-full sm:max-w-2xl max-h-[92vh] overflow-auto rounded-t-2xl sm:rounded-2xl shadow-xl">
    <div class="sticky top-0 bg-white border-b border-slate-100 px-4 py-3 flex items-center justify-between">
      <span class="font-mono font-bold text-lg" id="cf-codigo"></span>
      <button type="button" id="cf-cerrar" class="text-slate-500 text-2xl leading-none px-2" aria-label="Cerrar">✕</button>
    </div>
    <div class="p-4 space-y-4" id="cf-cuerpo"></div>
  </div>
</div>

<style>
  /* las clases flex/inline-flex de Tailwind pisan el atributo hidden */
  #consulta [hidden], #cficha[hidden] { display: none !important; }
  #cmapa svg { width: 100%; height: auto; display: block; }
  .cara { cursor: pointer; transition: fill .15s; }
  .cara:hover { filter: brightness(1.15); }
  .sem-vencido { background:#ede9fe; color:#5b21b6; box-shadow: inset 0 0 0 1px #c4b5fd; }
  .sem-rojo { background:#fee2e2; color:#991b1b; }
  .sem-naranja { background:#ffedd5; color:#9a3412; }
  .sem-amarillo { background:#fef9c3; color:#854d0e; }
  .flagimg { display:inline-block; width:20px; height:14px; object-fit:cover; border-radius:2px; box-shadow:0 0 0 1px rgba(0,0,0,.12); vertical-align:middle; }
</style>

<script>
window.ConsultaLotes = (function () {
  const URL_DATOS = @json($urlDatos);
  const URL_MOVS  = @json($urlMovs);
  const URL_FICHA = @json($urlFicha ?? null);

  // Plano (coordenadas del dibujo del almacén)
  const PLANO = {
    sala: { x: 98, y: 95, w: 1847, h: 928 },
    puerta: { x1: 572, x2: 830, y: 1023 },
    tablet: { x: 374, y: 890, w: 179, h: 130 },
    estantes: [
      { n: '7', x: 213,  y: 95,  w: 173, h: 693, o: 'v' },
      { n: '6', x: 498,  y: 95,  w: 172, h: 525, o: 'v' },
      { n: '5', x: 783,  y: 95,  w: 172, h: 525, o: 'v' },
      { n: '4', x: 1068, y: 95,  w: 172, h: 525, o: 'v' },
      { n: '3', x: 1352, y: 95,  w: 173, h: 525, o: 'v' },
      { n: '2', x: 1637, y: 95,  w: 173, h: 525, o: 'v' },
      { n: '1', x: 1068, y: 750, w: 877, h: 172, o: 'h' },
    ],
  };

  let LOTES = [], BANDERAS = {}, ubicFiltro = null;
  const $ = (id) => document.getElementById(id);
  const esc = (t) => String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const norm = (t) => String(t || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
  const kg = (n) => Number(n).toFixed(2);
  const bandera = (o) => BANDERAS[o] ? `<img class="flagimg" src="${esc(BANDERAS[o])}" alt="">` : '';

  function filtrados() {
    const palabras = norm($('cq').value).split(/\s+/).filter(Boolean);
    const vacios = $('cvacios').checked;
    return LOTES.filter(l =>
      (vacios || l.stock_kg > 0.004) &&
      palabras.every(w => l.buscar.includes(w))
    );
  }

  function pintar() {
    const base = filtrados();
    const lista = ubicFiltro ? base.filter(l => l.ubicacion === ubicFiltro) : base;
    lista.sort((a, b) => a.ubicacion_orden - b.ubicacion_orden || a.codigo.localeCompare(b.codigo));

    const total = lista.reduce((s, l) => s + l.stock_kg, 0);
    $('cresumen').textContent = `${lista.length} ${lista.length === 1 ? 'lote' : 'lotes'} · ${kg(total)} kg`;
    $('cfiltro-ubic').hidden = !ubicFiltro;
    if (ubicFiltro) $('cfiltro-ubic-txt').textContent = (LOTES.find(l => l.ubicacion === ubicFiltro)?.ubicacion_nombre) || ubicFiltro.toUpperCase();
    $('cvacio').hidden = lista.length > 0;
    $('clista').innerHTML = lista.map(filaHtml).join('');

    // El mapa cuenta lo que coincide con la búsqueda (sin el filtro de estante, para poder cambiar de cara)
    const cuenta = {};
    base.forEach(l => { if (l.ubicacion) cuenta[l.ubicacion] = (cuenta[l.ubicacion] || 0) + 1; });
    $('cmapa').innerHTML = mapaSvg(cuenta, ubicFiltro, true);
  }

  function filaHtml(l) {
    const sem = l.vence_estado !== 'normal' && l.vence ? `<span class="px-1.5 py-0.5 rounded text-xs font-semibold sem-${l.vence_estado}" title="${esc(l.vence_texto || '')}">${esc(l.vence)}</span>` : '';
    return `<li class="px-3 sm:px-4 py-2.5 flex items-center gap-3 hover:bg-amber-50 cursor-pointer" data-codigo="${esc(l.codigo)}">
      <span class="w-9 h-9 rounded-md border border-slate-300 flex-shrink-0" style="background:${esc(l.hex)}"></span>
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-1.5 flex-wrap">
          <span class="font-mono text-xs text-slate-500">${esc(l.codigo)}</span>
          ${l.sigla ? `<span class="font-mono font-bold text-[0.65rem] bg-amber-100 text-amber-800 px-1.5 py-0.5 rounded">${esc(l.sigla)}</span>` : ''}
          <span class="font-mono font-semibold">${esc(l.ral)}</span>
          <span class="text-sm text-slate-600">· ${esc(l.acabado_corto)}</span> ${bandera(l.origen)}
          ${l.cliente ? `<span title="Propietario externo: ${esc(l.cliente)}" class="text-xs bg-yellow-100 text-yellow-900 px-1.5 py-0.5 rounded">⚠ ${esc(l.cliente)}</span>` : ''}
        </div>
        <div class="text-xs text-slate-500 truncate">${esc([l.ral_nombre, l.descripcion].filter(Boolean).join(' · '))}</div>
      </div>
      <div class="text-right flex-shrink-0">
        ${l.ubicacion_nombre ? `<span class="inline-block font-mono font-bold text-sm bg-sky-100 text-sky-900 px-2 py-0.5 rounded">${esc(l.ubicacion_nombre)}</span>` : '<span class="text-xs text-slate-400">sin ubicación</span>'}
        <div class="text-xs tabular-nums mt-0.5">${kg(l.stock_kg)} kg ${sem}</div>
      </div>
    </li>`;
  }

  // Mapa SVG: cada estante partido en sus dos caras; las que tienen coincidencias se iluminan con su número
  function mapaSvg(cuenta, seleccion, interactivo) {
    const p = PLANO, s = p.sala;
    let svg = `<svg viewBox="70 60 1905 1050" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Mapa del almacén">`;
    svg += `<rect x="${s.x}" y="${s.y}" width="${s.w}" height="${s.h}" fill="#f8fafc" stroke="#0f172a" stroke-width="6"/>`;
    // puerta: hueco + hoja abierta
    svg += `<line x1="${p.puerta.x1}" y1="${p.puerta.y}" x2="${p.puerta.x2}" y2="${p.puerta.y}" stroke="#f8fafc" stroke-width="10"/>`;
    svg += `<line x1="595" y1="1090" x2="${p.puerta.x2}" y2="${p.puerta.y}" stroke="#1e6b8a" stroke-width="6"/>`;
    svg += `<text x="700" y="1080" font-size="34" fill="#64748b" text-anchor="middle">entrada</text>`;
    const t = p.tablet;
    svg += `<rect x="${t.x}" y="${t.y}" width="${t.w}" height="${t.h}" fill="#e2e8f0"/><text x="${t.x + t.w / 2}" y="${t.y + 75}" font-size="32" fill="#334155" text-anchor="middle">Tablet</text>`;

    p.estantes.forEach(e => {
      const caras = e.o === 'v'
        ? [{ c: 'b', x: e.x, y: e.y, w: e.w / 2, h: e.h }, { c: 'a', x: e.x + e.w / 2, y: e.y, w: e.w / 2, h: e.h }]
        : [{ c: 'b', x: e.x, y: e.y, w: e.w, h: e.h / 2 }, { c: 'a', x: e.x, y: e.y + e.h / 2, w: e.w, h: e.h / 2 }];
      svg += `<rect x="${e.x}" y="${e.y}" width="${e.w}" height="${e.h}" rx="22" fill="#1e5f7e" stroke="#0f2f3f" stroke-width="5"/>`;
      caras.forEach(f => {
        const clave = e.n + f.c, n = cuenta[clave] || 0, sel = seleccion === clave;
        const fill = sel ? '#f59e0b' : (n ? '#fbbf24' : 'transparent');
        svg += `<rect class="cara" data-ubic="${clave}" x="${f.x + 6}" y="${f.y + 6}" width="${f.w - 12}" height="${f.h - 12}" rx="16"
                  fill="${fill}" fill-opacity="${n || sel ? 0.9 : 0}" stroke="${sel ? '#7c2d12' : 'none'}" stroke-width="6">
                  <title>${(e.n + f.c).toUpperCase()}${n ? ` · ${n} ${n === 1 ? 'lote' : 'lotes'}` : ''}</title></rect>`;
        // letra de la cara
        const lx = e.o === 'v' ? f.x + f.w / 2 : e.x + 40, ly = e.o === 'v' ? e.y + e.h - 30 : f.y + f.h / 2 + 18;
        svg += `<text x="${lx}" y="${ly}" font-size="46" font-weight="700" fill="${n || sel ? '#422006' : '#fbbf24'}" text-anchor="middle" pointer-events="none">${f.c.toUpperCase()}</text>`;
        if (n) {
          const cx = e.o === 'v' ? f.x + f.w / 2 : f.x + f.w / 2 + 120, cy = e.o === 'v' ? e.y + e.h / 2 + 70 : f.y + f.h / 2;
          svg += `<circle cx="${cx}" cy="${cy}" r="40" fill="#b91c1c" stroke="#fff" stroke-width="5" pointer-events="none"/>
                  <text x="${cx}" y="${cy + 14}" font-size="40" font-weight="700" fill="#fff" text-anchor="middle" pointer-events="none">${n}</text>`;
        }
      });
      const nx = e.o === 'v' ? e.x + e.w / 2 : e.x + e.w / 2 - 120, ny = e.o === 'v' ? e.y + 110 : e.y + e.h / 2 + 30;
      svg += `<text x="${nx}" y="${ny}" font-size="80" fill="#fff" text-anchor="middle" pointer-events="none">${e.n}</text>`;
    });
    return svg + '</svg>';
  }

  async function abrirFicha(codigo) {
    const l = LOTES.find(x => x.codigo === codigo);
    if (!l) { alert(`No encuentro el lote ${codigo}.`); return; }
    $('cf-codigo').textContent = l.codigo;
    const fila = (k, v) => v ? `<div class="flex justify-between gap-4 py-1.5 border-b border-slate-100"><span class="text-slate-500">${k}</span><span class="text-right font-medium">${v}</span></div>` : '';
    const sem = l.vence ? (l.vence_estado !== 'normal' ? `<span class="px-1.5 py-0.5 rounded font-semibold sem-${l.vence_estado}" title="${esc(l.vence_texto || '')}">${esc(l.vence)}</span>${l.vence_estado !== 'vencido' ? ` <span class="text-xs text-slate-500">${esc(l.vence_texto)}</span>` : ''}` : esc(l.vence)) : '';
    const fuera = l.cajas_fuera.length ? l.cajas_fuera.map(f => `${esc(f.quien)} · ${kg(f.kg)} kg · ${esc(f.desde || '')}`).join('<br>') : '';
    $('cf-cuerpo').innerHTML = `
      <div class="flex items-center gap-3">
        <span class="w-16 h-16 rounded-lg border border-slate-300 flex-shrink-0" style="background:${esc(l.hex)}"></span>
        <div>
          <div class="flex items-center gap-2 flex-wrap">
            ${l.sigla ? `<span class="font-mono font-bold text-xs bg-amber-100 text-amber-800 px-1.5 py-0.5 rounded" title="${esc(l.tipo || '')}">${esc(l.sigla)}</span>` : ''}
            <span class="font-mono font-bold text-xl">${esc(l.ral)}</span> ${bandera(l.origen)}
          </div>
          <div class="text-sm text-slate-500">${esc([l.ral_nombre, l.descripcion].filter(Boolean).join(' · '))}</div>
        </div>
        ${l.ubicacion_nombre ? `<span class="ml-auto font-mono font-bold text-2xl bg-sky-100 text-sky-900 px-3 py-1 rounded-lg">${esc(l.ubicacion_nombre)}</span>` : ''}
      </div>
      ${l.cliente ? `<div class="bg-yellow-50 border-2 border-yellow-400 text-yellow-900 rounded-lg px-3 py-2 font-semibold">⚠ Propietario externo: ${esc(l.cliente)}</div>` : ''}
      ${l.caja_abierta_kg != null ? `<div class="bg-sky-50 border border-sky-300 text-sky-900 rounded-lg px-3 py-2">📦 Tiene una caja abierta de ~${kg(l.caja_abierta_kg)} kg: úsala primero.</div>` : ''}
      <div class="text-sm">
        ${fila('Stock', `${kg(l.stock_kg)} kg de ${kg(l.recibido_kg)} kg recibidos`)}
        ${fila('Cajas recibidas', String(l.cajas))}
        ${fila('Textura', esc(l.textura))}
        ${fila('Tipo', esc(l.tipo))}
        ${fila('Origen', l.origen_nombre ? bandera(l.origen) + ' ' + esc(l.origen_nombre) : '')}
        ${fila('Marca', esc([l.marca, l.referencia].filter(Boolean).join(' · ')))}
        ${fila('Curado', esc(l.curado))}
        ${fila('Recepción', esc(l.recepcion))}
        ${fila('Vence', sem)}
        ${fila('Proveedor', esc(l.proveedor))}
        ${fila('Factura', esc(l.factura))}
        ${fila('Cajas fuera ahora', fuera)}
        ${fila('Notas del lote', esc(l.notas_lote))}
        ${fila('Notas del producto', esc(l.notas_producto))}
      </div>
      <div>
        <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Dónde está</div>
        ${l.ubicacion ? mapaSvg({ [l.ubicacion]: 1 }, l.ubicacion, false) : '<p class="text-slate-400 text-sm">Sin ubicación asignada.</p>'}
      </div>
      <div>
        <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Últimos movimientos</div>
        <div id="cf-movs" class="text-sm text-slate-400">Cargando…</div>
      </div>
      ${URL_FICHA ? `<a href="${esc(URL_FICHA.replace('__CODIGO__', encodeURIComponent(l.codigo)))}" class="block text-center bg-slate-900 text-white rounded-lg py-2 font-semibold">Abrir ficha completa</a>` : ''}`;
    $('cficha').hidden = false;
    try {
      const r = await fetch(URL_MOVS.replace('__CODIGO__', encodeURIComponent(l.codigo)), { headers: { Accept: 'application/json' } });
      const d = await r.json();
      const nombres = { salida: '↗ Salida', retorno: '↘ Retorno', ajuste: '⚙ Ajuste' };
      $('cf-movs').innerHTML = d.movimientos.length
        ? `<ul class="divide-y divide-slate-100 text-slate-700">${d.movimientos.map(m =>
            `<li class="py-1.5 flex justify-between gap-3"><span>${esc(m.cuando)} · ${nombres[m.tipo] || esc(m.tipo)} · ${esc(m.quien || '')}${m.anomalias.length ? ` <span title="${esc(m.anomalias.join(' · '))}" class="text-amber-700">⚠</span>` : ''}</span><span class="tabular-nums">${kg(m.kg)} kg</span></li>`).join('')}</ul>`
        : 'Sin movimientos todavía.';
    } catch (e) { $('cf-movs').textContent = 'No se pudieron cargar los movimientos.'; }
  }

  function cerrarFicha() { $('cficha').hidden = true; }

  async function cargar() {
    const r = await fetch(URL_DATOS, { headers: { Accept: 'application/json' } });
    if (!r.ok) throw new Error('HTTP ' + r.status);
    const d = await r.json();
    LOTES = d.lotes; BANDERAS = d.banderas || {};
    $('ccargando').hidden = true;
    pintar();
  }

  function iniciar() {
    $('cq').addEventListener('input', pintar);
    $('cvacios').addEventListener('change', pintar);
    $('cfiltro-ubic-x').addEventListener('click', () => { ubicFiltro = null; pintar(); });
    $('clista').addEventListener('click', (e) => { const li = e.target.closest('[data-codigo]'); if (li) abrirFicha(li.dataset.codigo); });
    $('cmapa').addEventListener('click', (e) => {
      const c = e.target.closest('[data-ubic]'); if (!c) return;
      ubicFiltro = ubicFiltro === c.dataset.ubic ? null : c.dataset.ubic; pintar();
      $('clista').scrollTop = 0;
    });
    $('cf-cerrar').addEventListener('click', cerrarFicha);
    $('cficha').addEventListener('click', (e) => { if (e.target === $('cficha')) cerrarFicha(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') cerrarFicha(); });
    cargar().catch(() => { $('ccargando').textContent = 'No se pudieron cargar los lotes. Revisa la conexión y recarga.'; });
  }

  document.addEventListener('DOMContentLoaded', iniciar);
  return { abrirFicha, lotes: () => LOTES };
})();
</script>
