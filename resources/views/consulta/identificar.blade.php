@extends('consulta._movil_base')
@section('title', 'Identifícate')

@push('head')
  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
@endpush

@section('content')
  <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 mt-4">
    <h1 class="text-2xl font-bold text-slate-900">Escanea tu gafete</h1>
    <p class="text-slate-500 mt-1">Una sola vez: el celular te recordará durante 30 días.</p>

    @if ($errors->any())
      <div class="bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded mt-3 text-sm">{{ $errors->first() }}</div>
    @endif

    <button type="button" id="btn-camara"
            class="mt-4 w-full bg-amber-500 text-white font-bold text-lg rounded-xl py-4">📷 Abrir cámara</button>
    <div id="lector" class="mt-3 rounded-xl overflow-hidden"></div>

    <form method="POST" action="{{ route('consulta.identificar', $token) }}" id="f-codigo" class="mt-5">
      @csrf
      <label class="block text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1" for="codigo">o escribe el código de tu gafete</label>
      <div class="flex gap-2">
        <input id="codigo" name="codigo" autocomplete="off" autocapitalize="characters" placeholder="PNT-0001"
               class="flex-1 min-w-0 px-3 py-2.5 border border-slate-300 rounded-lg font-mono uppercase text-lg">
        <button class="bg-slate-900 text-white px-4 rounded-lg font-semibold">Entrar</button>
      </div>
    </form>
  </div>

  <script>
    document.getElementById('btn-camara').addEventListener('click', async () => {
      const btn = document.getElementById('btn-camara');
      btn.hidden = true;
      const lector = new Html5Qrcode('lector', { formatsToSupport: [
        Html5QrcodeSupportedFormats.CODE_128, Html5QrcodeSupportedFormats.QR_CODE ] });
      try {
        await lector.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 280, height: 140 } }, async (texto) => {
          await lector.stop();
          document.getElementById('codigo').value = texto;
          document.getElementById('f-codigo').submit();
        });
      } catch (e) {
        btn.hidden = false;
        alert('No se pudo abrir la cámara. Escribe el código de tu gafete.');
      }
    });
  </script>
@endsection
