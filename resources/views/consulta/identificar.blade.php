@extends('consulta._movil_base')
@section('title', 'Identifícate')

@section('content')
  @include('consulta._lector')

  <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 mt-4">
    <h1 class="text-2xl font-bold text-slate-900">Escanea tu gafete</h1>
    <p class="text-slate-500 mt-1">Una sola vez: el celular te recordará durante 30 días.</p>

    @if ($errors->any())
      <div class="bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded mt-3 text-sm">{{ $errors->first() }}</div>
    @endif

    {{-- Foto con la cámara del teléfono (la forma más fiable para el código de barras) --}}
    <label class="mt-4 w-full bg-amber-500 text-white font-bold text-lg rounded-xl py-4 flex items-center justify-center gap-2 cursor-pointer">
      📸 Tomar foto del gafete
      <input type="file" id="foto" accept="image/*" capture="environment" class="hidden">
    </label>
    <p class="text-xs text-slate-500 mt-1 text-center">Acerca el celular hasta que el código ocupe casi toda la foto y salga nítido.</p>
    <p id="estado" class="text-sm text-center mt-2 text-slate-600" hidden></p>

    <button type="button" id="btn-vivo" class="mt-3 w-full border border-slate-300 rounded-xl py-3 font-semibold text-slate-700">📷 Escanear en vivo</button>
    <div id="lector" class="mt-3 rounded-xl overflow-hidden"></div>

    {{-- Solo por escaneo (foto o en vivo): no se puede escribir el código a mano --}}
    <form method="POST" action="{{ route('consulta.identificar', $token) }}" id="f-codigo" hidden>
      @csrf
      <input type="hidden" id="codigo" name="codigo">
    </form>
  </div>

  <script>
    (function () {
      const FORMATOS = ['code_128', 'qr_code'];
      const estado = document.getElementById('estado');
      const enviar = (texto) => { document.getElementById('codigo').value = texto; document.getElementById('f-codigo').submit(); };
      const aviso = (t) => { estado.hidden = false; estado.textContent = t; };

      document.getElementById('foto').addEventListener('change', async (e) => {
        const f = e.target.files && e.target.files[0];
        if (!f) return;
        aviso('Leyendo la foto…');
        const texto = await LectorCodigos.desdeFoto(f, FORMATOS).catch(() => null);
        e.target.value = '';
        if (texto) { aviso('Gafete leído: ' + texto); enviar(texto); }
        else aviso('No pude leer el código en la foto. Prueba más cerca, con buena luz y sin reflejos.');
      });

      document.getElementById('btn-vivo').addEventListener('click', async () => {
        const b = document.getElementById('btn-vivo'); b.hidden = true;
        try { await LectorCodigos.enVivo('lector', FORMATOS, enviar); }
        catch (e) { b.hidden = false; aviso('No se pudo abrir la cámara en vivo. Usa "Tomar foto".'); }
      });
    })();
  </script>
@endsection
