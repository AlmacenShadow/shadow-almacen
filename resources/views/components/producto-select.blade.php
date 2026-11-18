{{--
  Selector de producto con cuadrito de color, sigla, RAL, textura y bandera.
  Un <select> nativo no puede pintar colores ni SVG, así que es una lista
  propia: un input oculto (name) + botón + lista. Teclado: ↑ ↓ Enter Esc.

  Props: productos (Collection<Producto>), name, selected (id o null), required.
--}}
@props(['productos', 'name' => 'producto_id', 'selected' => null, 'required' => true])
@php
  $uid = 'ps-' . \Illuminate\Support\Str::random(6);
  $sel = $productos->firstWhere('id', (int) $selected);
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

  <ul data-ps-lista role="listbox" hidden tabindex="-1"
      class="absolute z-30 mt-1 w-full max-h-80 overflow-auto bg-white border border-slate-300 rounded-lg shadow-lg py-1">
    @foreach ($productos as $p)
      <li role="option" data-id="{{ $p->id }}" aria-selected="{{ $sel?->id === $p->id ? 'true' : 'false' }}"
          class="px-3 py-2 cursor-pointer hover:bg-amber-50 aria-selected:bg-amber-100 data-[activo=1]:bg-amber-50">
        @include('components.partials.producto-opcion', ['p' => $p])
      </li>
    @endforeach
  </ul>
</div>

@once
<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-producto-select]').forEach((raiz) => {
    const input = raiz.querySelector('input[type=hidden]');
    const boton = raiz.querySelector('[data-ps-boton]');
    const actual = raiz.querySelector('[data-ps-actual]');
    const lista = raiz.querySelector('[data-ps-lista]');
    const opciones = [...lista.querySelectorAll('[role=option]')];
    let activo = -1;

    const abrir = () => {
      lista.hidden = false; boton.setAttribute('aria-expanded', 'true');
      activo = Math.max(0, opciones.findIndex(o => o.dataset.id === input.value));
      marcar(); opciones[activo]?.scrollIntoView({ block: 'nearest' });
    };
    const cerrar = () => { lista.hidden = true; boton.setAttribute('aria-expanded', 'false'); };
    const marcar = () => opciones.forEach((o, i) => o.dataset.activo = i === activo ? '1' : '');
    const elegir = (o) => {
      input.value = o.dataset.id;
      opciones.forEach(x => x.setAttribute('aria-selected', x === o ? 'true' : 'false'));
      actual.innerHTML = o.innerHTML;
      boton.classList.remove('border-red-400');
      cerrar(); boton.focus();
    };

    boton.addEventListener('click', () => lista.hidden ? abrir() : cerrar());
    opciones.forEach(o => o.addEventListener('click', () => elegir(o)));
    document.addEventListener('click', (e) => { if (!raiz.contains(e.target)) cerrar(); });
    raiz.addEventListener('keydown', (e) => {
      if (lista.hidden) {
        if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(e.key)) { e.preventDefault(); abrir(); }
        return;
      }
      if (e.key === 'ArrowDown') { e.preventDefault(); activo = Math.min(opciones.length - 1, activo + 1); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); activo = Math.max(0, activo - 1); }
      else if (e.key === 'Enter') { e.preventDefault(); if (opciones[activo]) elegir(opciones[activo]); return; }
      else if (e.key === 'Escape') { cerrar(); return; }
      else return;
      marcar(); opciones[activo]?.scrollIntoView({ block: 'nearest' });
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
