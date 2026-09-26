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

    // Cómo afecta al stock un kg de este tipo de movimiento. Sirve para
    // mostrar en vivo el efecto de cambiar el peso.
    $signoStock = match ($movimiento->tipo) {
      'salida'  => -1,
      'retorno' => 1,
      'ajuste'  => (int) ($movimiento->motivoAjuste->signo ?? -1),
      default   => -1,
    };
  @endphp

  <div class="flex items-center justify-between mb-5">
    <div>
      <h2 class="text-2xl font-bold text-slate-900">Corregir movimiento</h2>
      <p class="text-sm text-slate-500">
        El original no se modifica nunca: se le cuelga un registro nuevo y deja de contar.
      </p>
    </div>
    <a href="{{ route('lotes.show', $movimiento->lote_id) }}" class="text-sm text-slate-500 hover:text-slate-800">← cancelar</a>
  </div>

  @if ($yaCorregido)
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4 text-sm">
      ⚠ Este movimiento ya fue corregido por el registro
      <a href="{{ route('lotes.show', $movimiento->lote_id) }}#mov-{{ $yaCorregido->id }}"
         class="underline font-semibold">#{{ $yaCorregido->id }}</a>
      el {{ $yaCorregido->created_at->format('Y-m-d H:i') }}.
      Si ese también quedó mal, corrige <strong>ese</strong>, no este.
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

          <dt class="text-slate-500">Peso registrado</dt>
          <dd class="tabular-nums font-bold">{{ number_format($movimiento->peso_kg, 3) }} kg</dd>

          @if ($movimiento->nota_texto)
            <dt class="text-slate-500">Nota original</dt>
            <dd class="text-slate-700">{{ $movimiento->nota_texto }}</dd>
          @endif
        </dl>
      </div>

      @unless ($yaCorregido)
        <form method="POST" action="{{ route('movimientos.corregir.store', $movimiento) }}"
              class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-5">
          @csrf

          <div>
            <p class="text-xs uppercase tracking-wider text-slate-500 font-semibold mb-2">¿Qué pasó?</p>

            <label class="flex items-start gap-3 p-3 rounded-lg border border-slate-200 cursor-pointer has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50">
              <input type="radio" name="accion" value="corregir" class="mt-1 accion-radio"
                     @checked(old('accion', 'corregir') === 'corregir')>
              <span>
                <span class="font-semibold text-slate-800">El peso estaba mal</span>
                <span class="block text-xs text-slate-500">
                  El movimiento sí ocurrió, pero con otro peso. Se registra el peso verdadero.
                </span>
              </span>
            </label>

            <label class="flex items-start gap-3 p-3 mt-2 rounded-lg border border-slate-200 cursor-pointer has-[:checked]:border-red-400 has-[:checked]:bg-red-50">
              <input type="radio" name="accion" value="anular" class="mt-1 accion-radio"
                     @checked(old('accion') === 'anular')>
              <span>
                <span class="font-semibold text-slate-800">El movimiento nunca debió existir</span>
                <span class="block text-xs text-slate-500">
                  Se anula completo: deja de contar para el stock, pero queda en el historial.
                </span>
              </span>
            </label>
          </div>

          <div id="bloque-peso">
            <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">
              Peso verdadero (kg) <span class="text-red-600">*</span>
            </label>
            <input type="number" name="peso_correcto" id="peso_correcto"
                   step="0.001" min="0.001" max="99999"
                   value="{{ old('peso_correcto') }}"
                   class="w-48 px-3 py-2 border border-slate-300 rounded-lg tabular-nums text-lg font-semibold">
            <p class="text-xs text-slate-400 mt-1">
              El original decía {{ number_format($movimiento->peso_kg, 3) }} kg.
            </p>
          </div>

          <div>
            <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-2">
              Razón <span class="text-red-600">*</span>
            </label>
            <textarea name="nota_texto" rows="3" required minlength="10" maxlength="400"
                      class="w-full px-3 py-2 border border-slate-300 rounded-lg"
                      placeholder="Ej: la báscula estaba sin tarar, el peso real de la caja fue 6.5 kg."
                      >{{ old('nota_texto') }}</textarea>
            <p class="text-xs text-slate-400 mt-1">Mínimo 10 caracteres. Queda en el historial permanente.</p>
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
            <a href="{{ route('lotes.show', $movimiento->lote_id) }}" class="px-4 py-2 text-slate-600 hover:text-slate-800">Cancelar</a>
            <button type="submit"
                    class="bg-amber-500 hover:bg-amber-600 text-white px-5 py-2 rounded-lg font-semibold">
              Registrar
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
          <li>El movimiento <strong>#{{ $movimiento->id }}</strong> queda en el historial, pero marcado y sin contar.</li>
          <li id="paso-nuevo">Se crea un registro nuevo con el peso verdadero.</li>
          <li>El consumo se le sigue atribuyendo a
            <strong>{{ $movimiento->usuario?->nombre ?? 'quien lo hizo' }}</strong>, no a ti.</li>
          <li>Tu nota queda anclada al registro nuevo.</li>
        </ol>

        <div class="mt-4 pt-4 border-t border-slate-200">
          <p class="text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">Efecto en el stock del lote</p>
          <p id="efecto-stock" class="tabular-nums text-lg font-bold text-slate-400">—</p>
        </div>

        <p class="text-xs text-slate-500 mt-4">
          Si los datos son inventados o de prueba, pídele al admin un borrado duro en lugar de corregir.
        </p>
      </div>
    </div>
  </div>

  <script>
    (function () {
      const pesoOriginal = {{ (float) $movimiento->peso_kg }};
      const signoStock   = {{ $signoStock }};
      const bloquePeso   = document.getElementById('bloque-peso');
      const inputPeso    = document.getElementById('peso_correcto');
      const efecto       = document.getElementById('efecto-stock');
      const pasoNuevo    = document.getElementById('paso-nuevo');
      const radios       = document.querySelectorAll('.accion-radio');

      function fmt(n) {
        return (n > 0 ? '+' : n < 0 ? '−' : '') + Math.abs(n).toFixed(3) + ' kg';
      }

      function pintar() {
        const modo = document.querySelector('.accion-radio:checked')?.value ?? 'corregir';
        const anular = modo === 'anular';

        bloquePeso.style.display = anular ? 'none' : '';
        inputPeso.required = !anular;

        if (anular) {
          const delta = -signoStock * pesoOriginal;
          efecto.textContent = fmt(delta);
          efecto.className = 'tabular-nums text-lg font-bold ' +
            (delta > 0 ? 'text-emerald-700' : delta < 0 ? 'text-red-700' : 'text-slate-400');
          pasoNuevo.textContent = 'Se crea un ajuste de 0 kg que deja constancia de la anulación.';
          return;
        }

        pasoNuevo.textContent = 'Se crea un registro nuevo con el peso verdadero.';
        const nuevo = parseFloat(inputPeso.value);
        if (isNaN(nuevo) || nuevo <= 0) {
          efecto.textContent = '—';
          efecto.className = 'tabular-nums text-lg font-bold text-slate-400';
          return;
        }
        const delta = signoStock * (nuevo - pesoOriginal);
        efecto.textContent = fmt(delta);
        efecto.className = 'tabular-nums text-lg font-bold ' +
          (delta > 0 ? 'text-emerald-700' : delta < 0 ? 'text-red-700' : 'text-slate-400');
      }

      radios.forEach(r => r.addEventListener('change', pintar));
      inputPeso.addEventListener('input', pintar);
      pintar();
    })();
  </script>
@endsection
