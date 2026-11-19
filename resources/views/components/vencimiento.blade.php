{{--
  Fecha de vencimiento con semáforo (solo color, el texto va en el tooltip): vencido morado, <30 días rojo, <60 naranja, <90 amarillo.
  Props: fecha (string|Carbon|null), detalle (bool: mostrar "vence en N días" debajo).
--}}
@props(['fecha', 'detalle' => false])
@php $s = \App\Support\Vencimiento::semaforo($fecha); @endphp
@if (!$fecha)
  <span class="text-slate-400">—</span>
@elseif ($s['estado'] === 'normal')
  <span>@fecha($fecha)</span>
@else
  <span class="inline-flex flex-col items-end">
    <span class="inline-block px-2 py-0.5 rounded-md font-semibold {{ $s['clase'] }}" title="{{ $s['texto'] }}">
      @fecha($fecha)
    </span>
    @if ($detalle && $s['estado'] !== 'vencido')
      <span class="text-[0.7rem] text-slate-500 mt-0.5">{{ $s['texto'] }}</span>
    @endif
  </span>
@endif
