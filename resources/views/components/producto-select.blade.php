{{--
  Selector de producto con cuadrito de color, sigla, RAL, textura y bandera,
  con buscador que filtra mientras escribes.
  Un <select> nativo no puede pintar colores ni SVG, así que es una lista
  propia: un input oculto (name) + botón + panel con buscador y lista.
  Teclado: escribir filtra, ↑ ↓ mueven, Enter elige, Esc cierra.

  Busca en: sigla, tipo, RAL, nombre del color, textura, descripción, marca,
  referencia y país. Varias palabras = deben estar todas ("pol 9003 mate").
  Sin distinguir mayúsculas ni acentos.

  Props: productos (Collection<Producto>), name, selected (id o null), required.
--}}
@props(['productos', 'name' => 'producto_id', 'selected' => null, 'required' => true])
@php
  $uid = 'ps-' . \Illuminate\Support\Str::random(6);
  $sel = $productos->firstWhere('id', (int) $selected);
  $C = \App\Support\Catalogo::class;
  $textoBusqueda = fn ($p) => \Illuminate\Support\Str::of(implode(' ', array_filter([
      \App\Models\Producto::sigla($p->tipo_pintura),
      $C::nombre('tipo_pintura', $p->tipo_pintura),
      $p->ral,
      $p->nombre_ral_oficial,
      $p->textura_label,
      $p->descripcion,
      $p->marca,
      $p->referencia,
      $C::nombre('origen', $p->origen),
  ])))->ascii()->lower();
@endphp

<div class="relative" id="{{ $uid }}" data-producto-select>
  <input type="hidden" name="{{ $name }}" value="{{ $sel?->id }}" @if ($required) data-required @endif>

  <button type="button" data-ps-boton aria-haspopup="listbox" aria-expanded="false"
          class="w-full flex items-center gap-3 px-3 py-2 border border-slate-300 rounded-lg bg-white text-left hover:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400">
    <span data-ps-actual class="flex-1 min-w-0">
      @if ($sel)
        @include('components.partials.producto-opcion', ['p' => $sel])
      @else
        <span class="text-slate-400">— elegir producto —</span>
      @endif
    </span>
    <svg class="w-4 h-4 text-slate-400 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor"><path d="M5.5 7.5 10 12l4.5-4.5"/></svg>
  </button>

  <div data-ps-panel hidden class="absolute z-30 mt-1 w-full bg-white border border-slate-300 rounded-lg shadow-lg">
    <div class="p-2 border-b border-slate-100 flex items-center gap-2">
      <svg class="w-4 h-4 text-slate-400 flex-shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="9" r="6"/><path d="m14 14 4 4"/></svg>
      <input type="text" data-ps-buscar autocomplete="off" spellcheck="false"
             placeholder="Buscar: RAL, color, sigla, textura, marca, país…"
             class="flex-1 px-2 py-1.5 text-sm border border-slate-200 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-400">
      <span data-ps-contador class="text-xs text-slate-400 tabular-nums whitespace-nowrap"></span>
    </div>
    <ul data-ps-lista role="listbox" tabindex="-1" class="max-h-80 overflow-auto py-1">
      @foreach ($productos as $p)
        <li role="option" data-id="{{ $p->id }}" data-buscar="{{ $textoBusqueda($p) }}"
            aria-selected="{{ $sel?->id === $p->id ? 'true' : 'false' }}"
            class="px-3 py-2 cursor-pointer hover:bg-amber-50 aria-selected:bg-amber-100 data-[activo=1]:bg-amber-50">
          @include('components.partials.producto-opcion', ['p' => $p])
        </li>
      @endforeach
    </ul>
    <p data-ps-vacio hidden class="px-3 py-4 text-sm text-slate-400 text-center">Ningún producto coincide.</p>
  </div>
</div>

@once
<script>
document.addEventListener('DOMContentLoaded', () => {
  // minúsculas y sin acentos, igual que data-buscar
  const normalizar = (t) => t.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

  document.querySelectorAll('[data-producto-select]').forEach((raiz) => {
    const input    = raiz.querySelector('input[type=hidden]');
    const boton    = raiz.querySelector('[data-ps-boton]');
    const actual   = raiz.querySelector('[data-ps-actual]');
    const panel    = raiz.querySelector('[data-ps-panel]');
    const buscar   = raiz.querySelector('[data-ps-buscar]');
    const contador = raiz.querySelector('[data-ps-contador]');
    const vacio    = raiz.querySelector('[data-ps-vacio]');
    const opciones = [...raiz.querySelectorAll('[role=option]')];
    let visibles = opciones;
    let activo = -1;

    const marcar = () => {
      opciones.forEach(o => o.dataset.activo = '');
      if (visibles[activo]) {
        visibles[activo].dataset.activo = '1';
        visibles[activo].scrollIntoView({ block: 'nearest' });
      }
    };

    const filtrar = () => {
      const palabras = normalizar(buscar.value).split(/\s+/).filter(Boolean);
      visibles = opciones.filter(o => {
        const ok = palabras.every(w => o.dataset.buscar.includes(w));
        o.hidden = !ok;
        return ok;
      });
      vacio.hidden = visibles.length > 0;
      contador.textContent = palabras.length ? `${visibles.length} de ${opciones.length}` : `${opciones.length}`;
      activo = visibles.length ? 0 : -1;
      marcar();
    };

    const abrir = () => {
      panel.hidden = false; boton.setAttribute('aria-expanded', 'true');
      buscar.value = ''; filtrar();
      const i = visibles.findIndex(o => o.dataset.id === input.value);
      activo = i >= 0 ? i : 0; marcar();
      buscar.focus();
    };
    const cerrar = () => { panel.hidden = true; boton.setAttribute('aria-expanded', 'false'); };
    const elegir = (o) => {
      input.value = o.dataset.id;
      opciones.forEach(x => x.setAttribute('aria-selected', x === o ? 'true' : 'false'));
      actual.innerHTML = o.innerHTML;
      boton.classList.remove('border-red-400');
      cerrar(); boton.focus();
    };

    boton.addEventListener('click', () => panel.hidden ? abrir() : cerrar());
    buscar.addEventListener('input', filtrar);
    opciones.forEach(o => o.addEventListener('click', () => elegir(o)));
    document.addEventListener('click', (e) => { if (!raiz.contains(e.target)) cerrar(); });

    // Con el botón enfocado, empezar a escribir abre el buscador con esa letra
    boton.addEventListener('keydown', (e) => {
      if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(e.key)) { e.preventDefault(); abrir(); }
      else if (e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
        e.preventDefault(); abrir(); buscar.value = e.key; filtrar();
      }
    });
    buscar.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowDown') { e.preventDefault(); activo = Math.min(visibles.length - 1, activo + 1); marcar(); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); activo = Math.max(0, activo - 1); marcar(); }
      else if (e.key === 'Enter') { e.preventDefault(); if (visibles[activo]) elegir(visibles[activo]); }
      else if (e.key === 'Escape') { e.preventDefault(); cerrar(); boton.focus(); }
    });

    // Obligatorio: el input oculto no valida solo
    raiz.closest('form')?.addEventListener('submit', (e) => {
      if (input.hasAttribute('data-required') && !input.value) {
        e.preventDefault(); boton.classList.add('border-red-400'); boton.focus(); abrir();
      }
    });
  });
});
</script>
@endonce
