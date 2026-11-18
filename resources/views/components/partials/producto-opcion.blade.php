{{-- Una fila del selector de producto: cuadrito de color, sigla, RAL, nombre K7, textura, bandera. --}}
@php
  $sigla   = \App\Models\Producto::sigla($p->tipo_pintura);
  $bandera = \App\Models\Producto::BANDERAS[$p->origen ?? ''] ?? null;
@endphp
<span class="flex items-center gap-2.5 min-w-0">
  <span class="w-6 h-6 rounded border border-slate-300 flex-shrink-0 shadow-sm" style="background-color: {{ $p->hex }}"></span>
  @if ($sigla)
    <span class="font-mono font-bold text-[0.65rem] tracking-wide bg-amber-100 text-amber-800 px-1.5 py-0.5 rounded flex-shrink-0">{{ $sigla }}</span>
  @endif
  <span class="font-mono font-semibold text-slate-900 flex-shrink-0">{{ $p->ral }}</span>
  <span class="text-slate-500 truncate">
    @if ($p->nombre_ral_oficial){{ $p->nombre_ral_oficial }} · @endif{{ $p->textura_label }}
  </span>
  @if ($bandera)
    <span class="flex-shrink-0 inline-flex [&>svg]:inline-block [&>svg]:w-[22px] [&>svg]:h-[15px] [&>svg]:rounded-sm [&>svg]:shadow-[0_0_0_1px_rgba(0,0,0,.12)]"
          title="{{ \App\Models\Producto::ORIGENES[$p->origen] }}">{!! $bandera !!}</span>
  @endif
</span>
