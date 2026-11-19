{{--
  Detalle de anomalías + autorización de un movimiento (columna "Detalle").
  $m: fila con tipo_anomalia, autorizador_nombre, autorizacion_motivo.
--}}
@props(['m'])
@php $etiquetas = \App\Support\Anomalias::etiquetas($m->tipo_anomalia ?? null); @endphp
@foreach ($etiquetas as $e)
  <div class="text-xs text-amber-800">⚠ {{ $e }}</div>
@endforeach
@if (!empty($m->autorizador_nombre))
  <div class="text-xs mt-0.5 text-slate-700">
    <span class="font-semibold">✔ Autorizó {{ $m->autorizador_nombre }}:</span> {{ $m->autorizacion_motivo ?: '—' }}
  </div>
@elseif (!empty($m->autorizacion_motivo))
  <div class="text-xs mt-0.5 text-red-700">
    <span class="font-semibold">✖ Autorización no válida.</span> Motivo indicado: {{ $m->autorizacion_motivo }}
  </div>
@endif
