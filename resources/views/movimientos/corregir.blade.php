@extends('layouts.panel')
@section('title', 'Corregir movimiento')

@section('content')
  @php
    $tipoBadge = match ($movimiento->tipo) {
      'salida'  => ['bg-red-100 text-red-800',       '↗ Salida'],
      'retorno' => ['bg-emerald-100 text-emerald-800','↘ Retorno'],
      'ajuste'  => ['bg-amber-100 text-amber-800',   '⚙ Ajuste'],
      default   => ['bg-slate-100 text-slate-600',   $movimiento->tipo],
    };
  @endphp

  <div class="flex items-center justify-between mb-5">
    <div>
      <h2 class="text-2xl font-bold text-slate-900">Corregir movimiento</h2>
      <p class="text-sm text-slate-500">
        Se va a generar un ajuste compensatorio con efecto opuesto. El movimiento original no se modifica.
      </p>
    </div>
    <a href="{{ route('lotes.show', $movimiento->lote_id) }}" class="text-sm text-slate-500 hover:text-slate-800">← cancelar</a>
  </div>

  @if ($yaCorregido)
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4 text-sm">
      ⚠ Este movimiento ya fue corregido con el ajuste
      <a href="{{ route('lotes.show', $movimiento->lote_id) }}#mov-{{ $yaCorregido->id }}"
         class="underline font-semibold">#{{ $yaCorregido->id }}</a>
      el {{ $yaCorregido->created_at->format('Y-m-d H:i') }}.
      No se permite corregir dos veces (mantenemos la trazabilidad).
    </div>
  @endif

  @if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4 text-sm">
      <ul class="list-disc list-inside">
        @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
      </ul>
    </div>
  @endif

  <div class="grid grid-cols-3 gap-6">
    {{-- IZQ: detalle del movimiento original --}}
    <div class="col-span-2">
      <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-4">
        <p class="text-xs uppercase tracking-wider text-slate-500 font-semibold mb-3">Movimiento original</p>
        <dl class="grid grid-cols-2 gap-y-2 text-sm">
          <dt class="text-slate-500">ID</dt>
          <dd class="font-mono">#{{ $movimiento->id }}</dd>

          <dt class="text-slate-500">Cuándo</dt>
          <dd class="tabular-nums">{{ $movimiento->created_at->format('Y-m-d H:i') }}</dd>

          <dt class="text-slate-500">Tipo</dt>
          <dd>
            <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold {{ $tipoBadge[0] }}">
              {{ $tipoBadge[1] }}
            </span>
          </dd>

          <dt class="text-slate-500">Quién lo hizo</dt>
          <dd class="font-medium">
            {{ $movimiento->usuario?->nombre ?? '—' }}
            @if($movimiento->usuario) <span class="text-xs text-slate-500">· {{ ucfirst($movimiento->usuario->rol) }}</span> @endif
          </dd>

          <dt class="text-slate-500">Lote</dt>
          <dd class="font-mono">
            <a href="{{ route('lotes.show', $movimiento->lote_id) }}" class="text-amber-600 hover:underline">
              {{ $movimiento->lote->codigo_barcode }}
            </a>
          </dd>

          <dt class="text-slate-500">Producto</dt>
          <dd>{{ $movimiento->lote->producto->descripcion_corta }}</dd>

          <dt class="text-slate-500">Peso</dt>
          <dd class="tabular-nums font-bold">{{ number_format($movimiento->peso_kg, 3) }} kg</dd>

          @if ($movimiento->nota_texto)
            <dt class="text-slate-500">Nota original</dt>
            <dd class="text-slate-700">{{ $movimiento->nota_texto }}</dd>
          @endif
        </dl>
      </div>

      {{-- Formulario de corrección --}}
      @unless ($yaCorregido)
        <form method="POST" action="{{ route('movimientos.corregir.store', $movimiento) }}"
              class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
          @csrf
          <div>
            <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-2">
              Razón de la corrección <span class="text-red-600">*</span>
            </label>
            <textarea name="nota_texto" rows="4" required minlength="10" maxlength="500"
                      class="w-full px-3 py-2 border border-slate-300 rounded-lg"
                      placeholder="Ej: El pintor anotó 8.5 kg pero el peso real fue 0.85 — se duplicó. Se compensa para restaurar el stock."
                      >{{ old('nota_texto') }}</textarea>
            <p class="text-xs text-slate-400 mt-1">Mínimo 10 caracteres. Esta nota queda en el historial permanente.</p>
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
            <a href="{{ route('lotes.show', $movimiento->lote_id) }}" class="px-4 py-2 text-slate-600 hover:text-slate-800">Cancelar</a>
            <button type="submit"
                    class="bg-amber-500 hover:bg-amber-600 text-white px-5 py-2 rounded-lg font-semibold">
              Registrar corrección
            </button>
          </div>
        </form>
      @endunless
    </div>

    {{-- DER: explicación del efecto --}}
    <div>
      <div class="bg-slate-50 rounded-xl border border-slate-200 p-5 text-sm sticky top-4">
        <p class="font-semibold text-slate-800 mb-2">Qué va a pasar</p>
        <ol class="list-decimal list-inside space-y-1.5 text-slate-700">
          <li>El movimiento <strong>#{{ $movimiento->id }}</strong> queda intacto en el historial.</li>
          <li>Se crea un nuevo movimiento tipo <strong>ajuste</strong> con efecto opuesto:
            @php
              $efecto = match ($movimiento->tipo) {
                'salida'  => ['suma', '+', $movimiento->peso_kg],
                'retorno' => ['resta', '−', $movimiento->peso_kg],
                'ajuste'  => [($movimiento->motivo_ajuste_id && ($movimiento->motivoAjuste->signo ?? 0) > 0) ? 'resta' : 'suma',
                              (($movimiento->motivoAjuste->signo ?? 0) > 0) ? '−' : '+',
                              $movimiento->peso_kg],
                default   => ['suma', '+', $movimiento->peso_kg],
              };
            @endphp
            <span class="font-mono font-semibold {{ $efecto[1] === '+' ? 'text-emerald-700' : 'text-red-700' }}">
              {{ $efecto[1] }}{{ number_format($efecto[2], 3) }} kg
            </span>
            al stock del lote.
          </li>
          <li>Tu nota explicativa queda anclada permanentemente al nuevo movimiento.</li>
          <li>En el historial, ambos movimientos aparecen marcados como "corregido" y "corrección".</li>
        </ol>
        <p class="text-xs text-slate-500 mt-4">
          Si el error fue muy grave (ej. el lote nunca existió, los datos son inventados),
          mejor pídele al admin un borrado duro en lugar de corregir.
        </p>
      </div>
    </div>
  </div>
@endsection
