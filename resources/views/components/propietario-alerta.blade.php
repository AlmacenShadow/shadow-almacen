{{--
  Triángulo amarillo de advertencia para lotes de propietario externo.
  Con $nombre muestra el tooltip "Propietario externo: <nombre>".
--}}
@props(['nombre' => null])
<span {{ $attributes->merge(['class' => 'inline-flex items-center align-middle cursor-help']) }}
      @if ($nombre) title="Propietario externo: {{ $nombre }}" @endif>
  <svg viewBox="0 0 24 24" class="w-full h-full min-w-4 min-h-4" aria-label="Propietario externo">
    <path d="M12 2.5 1.5 21h21L12 2.5z" fill="#facc15" stroke="#a16207" stroke-width="1.4" stroke-linejoin="round"/>
    <rect x="11" y="9" width="2" height="6.5" rx="1" fill="#422006"/>
    <circle cx="12" cy="18" r="1.2" fill="#422006"/>
  </svg>
</span>
