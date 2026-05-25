@extends('layouts.panel')
@section('title', 'Movimientos')

@section('content')
  <div class="flex items-center justify-between mb-5">
    <div>
      <h2 class="text-2xl font-bold text-slate-900">Movimientos recientes</h2>
      <p class="text-sm text-slate-500">Auditoría de salidas, retornos y ajustes</p>
    </div>
  </div>

  {{-- Filtro por tipo --}}
  @php
    $filtros = [
      null      => 'Todos',
      'salida'  => 'Salidas',
      'retorno' => 'Retornos',
      'ajuste'  => 'Ajustes',
    ];
  @endphp
  <div class="flex gap-2 mb-4 text-sm">
    @foreach ($filtros as $val => $label)
      <a href="{{ $val ? route('movimientos.index', ['tipo' => $val]) : route('movimientos.index') }}"
         class="px-3 py-1 rounded {{ $tipo === $val ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
        {{ $label }}
      </a>
    @endforeach
  </div>

  <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    @if ($movimientos->isEmpty())
      <div class="p-12 text-center text-slate-500">
        <p class="text-lg">
          @if ($tipo)
            Sin movimientos del tipo seleccionado.
          @else
            Aún no hay movimientos registrados.
          @endif
        </p>
      </div>
    @else
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
          <tr>
            <th class="text-left px-5 py-3">Cuándo</th>
            <th class="text-left px-5 py-3">Tipo</th>
            <th class="text-left px-5 py-3">Quién</th>
            <th class="text-left px-5 py-3">Lote / producto</th>
            <th class="text-right px-5 py-3">Peso</th>
            <th class="text-left px-5 py-3">Motivo / nota</th>
            <th class="text-right px-5 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @foreach ($movimientos as $m)
            @php
              $tipoBadge = match ($m->tipo) {
                'salida'  => ['bg-red-100 text-red-800', '↗ Salida'],
                'retorno' => ['bg-emerald-100 text-emerald-800', '↘ Retorno'],
                'ajuste'  => ['bg-amber-100 text-amber-800', '⚙ Ajuste'],
                default   => ['bg-slate-100 text-slate-600', $m->tipo],
              };
              $signo = match ($m->tipo) {
                'salida'  => '−',
                'retorno' => '+',
                'ajuste'  => $m->motivo_signo == 1 ? '+' : ($m->motivo_signo == -1 ? '−' : ''),
                default   => '',
              };
              $pesoClase = match ($m->tipo) {
                'salida'  => 'text-red-700',
                'retorno' => 'text-emerald-700',
                'ajuste'  => $m->motivo_signo == 1 ? 'text-emerald-700' : 'text-red-700',
                default   => 'text-slate-700',
              };
            @endphp
            <tr class="{{ $m->anomalia ? 'bg-amber-50' : '' }} {{ $m->corregido_por_id ? 'opacity-60' : '' }}">
              <td class="px-5 py-3 text-slate-600 tabular-nums whitespace-nowrap">
                {{ \Illuminate\Support\Carbon::parse($m->created_at)->format('Y-m-d H:i') }}
              </td>
              <td class="px-5 py-3 whitespace-nowrap">
                <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold {{ $tipoBadge[0] }}">
                  {{ $tipoBadge[1] }}
                </span>
                @if ($m->corrige_movimiento_id)
                  <span class="ml-1 inline-block px-2 py-0.5 rounded text-xs font-semibold bg-violet-100 text-violet-800"
                        title="Corrige al movimiento #{{ $m->corrige_movimiento_id }}">↺ corrección</span>
                @endif
                @if ($m->corregido_por_id)
                  <span class="ml-1 inline-block px-2 py-0.5 rounded text-xs font-semibold bg-slate-200 text-slate-700"
                        title="Anulado por el ajuste #{{ $m->corregido_por_id }}">corregido</span>
                @endif
                @if ($m->anomalia)
                  <span class="ml-1 inline-block px-2 py-0.5 rounded text-xs font-semibold bg-amber-200 text-amber-900"
                        title="Anomalía: {{ $m->tipo_anomalia ?? 'sin detalle' }}">⚠</span>
                @endif
              </td>
              <td class="px-5 py-3">
                @if ($m->usuario_nombre)
                  <div class="font-medium">{{ $m->usuario_nombre }}</div>
                  <div class="text-xs text-slate-500">{{ ucfirst($m->usuario_rol) }}</div>
                @else
                  <span class="text-slate-400 italic">—</span>
                @endif
              </td>
              <td class="px-5 py-3">
                <a href="{{ route('lotes.show', $m->lote_id) }}"
                   class="font-mono text-amber-600 hover:underline">{{ $m->lote_codigo }}</a>
                <div class="text-xs text-slate-500">
                  {{ $m->ral }} · {{ $m->textura }} · {{ $m->brillo_pct }}%
                  @if ($m->nombre_interno)
                    · <span class="text-slate-400">{{ $m->nombre_interno }}</span>
                  @endif
                </div>
              </td>
              <td class="px-5 py-3 text-right whitespace-nowrap">
                <span class="font-semibold tabular-nums {{ $pesoClase }}">
                  {{ $signo }}{{ number_format($m->peso_kg, 3) }} kg
                </span>
                @if ($m->peso_manual)
                  <div class="text-xs text-slate-400 italic">manual</div>
                @endif
              </td>
              <td class="px-5 py-3 text-slate-600">
                @if ($m->motivo_descripcion)
                  <div class="font-medium">{{ $m->motivo_descripcion }}</div>
                @endif
                @if ($m->nota_texto)
                  <div class="text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($m->nota_texto, 90) }}</div>
                @endif
                @if (!$m->motivo_descripcion && !$m->nota_texto)
                  <span class="text-slate-300">—</span>
                @endif
              </td>
              <td class="px-5 py-3 text-right whitespace-nowrap">
                @if (!$m->corregido_por_id && !$m->corrige_movimiento_id)
                  <a href="{{ route('movimientos.corregir', $m->id) }}"
                     class="text-amber-600 hover:underline text-sm">corregir</a>
                @endif
                @if (auth()->user()->esAdmin() && !$m->corregido_por_id)
                  <form method="POST" action="{{ route('movimientos.destroy', $m->id) }}" class="inline ml-3"
                        onsubmit="var r=prompt('Razón del borrado de mov #{{ $m->id }} (min 10 caracteres):'); if(!r||r.length<10){alert('Razón muy corta.');return false;} this.querySelector('input[name=razon]').value=r; return confirm('¿Borrar definitivamente el movimiento #{{ $m->id }}?');">
                    @csrf @method('DELETE')
                    <input type="hidden" name="razon" value="">
                    <button type="submit" class="text-red-600 hover:underline text-sm">borrar</button>
                  </form>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>

      {{-- Paginación --}}
      <div class="px-5 py-3 border-t border-slate-100 bg-slate-50">
        {{ $movimientos->links() }}
      </div>
    @endif
  </div>
@endsection
