{{--
  Resumen compacto de un producto para listados: [SIGLA] RAL · Acabado 🏳
  $p puede ser un Producto o una fila de DB::table con: ral, tipo_pintura,
  acabado, superficie, textura_tipo, metalizado, origen.
  Sin brillo %: en los listados importa RAL, acabado y origen.
--}}
@props(['p', 'small' => false])
@php
  $sigla   = \App\Models\Producto::sigla($p->tipo_pintura ?? null);
  $acabado = \App\Models\Producto::acabadoCorto($p);
  $bandera = \App\Models\Producto::BANDERAS[$p->origen ?? ''] ?? null;
  $origen  = \App\Models\Producto::ORIGENES[$p->origen ?? ''] ?? null;
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 flex-wrap ' . ($small ? 'text-xs text-slate-500' : 'font-medium')]) }}>
  @if ($sigla)
    <span class="font-mono font-bold text-[0.65rem] tracking-wide bg-amber-100 text-amber-800 px-1.5 py-0.5 rounded"
          title="{{ $p->tipo_pintura }}">{{ $sigla }}</span>
  @endif
  <span class="font-mono">{{ $p->ral }}</span>
  @if ($acabado)<span>· {{ implode(' · ', $acabado) }}</span>@endif
  @if ($bandera)
    <span title="{{ $origen }}" class="inline-flex [&>svg]:inline-block [&>svg]:w-[22px] [&>svg]:h-[15px] [&>svg]:rounded-sm [&>svg]:shadow-[0_0_0_1px_rgba(0,0,0,.12)]">{!! $bandera !!}</span>
  @endif
</span>
