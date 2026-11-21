@extends('consulta._movil_base')
@section('title', 'Buscar lotes')

@push('head')
  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
@endpush

@section('header-derecha')
  <form method="POST" action="{{ route('consulta.salir', $token) }}" class="flex items-center gap-2 text-xs text-slate-300">
    @csrf
    <span>{{ $usuario->nombre }}</span>
    <button class="underline text-slate-400">salir</button>
  </form>
@endsection

@section('content')
  {{-- Dos acciones grandes: escanear una caja o buscar --}}
  <div class="grid grid-cols-2 gap-3 mb-3">
    <button type="button" id="btn-escanear" class="bg-amber-500 text-white rounded-2xl py-5 font-bold text-lg shadow">📷 Escanear caja</button>
    <button type="button" id="btn-buscar" class="bg-white border border-slate-300 rounded-2xl py-5 font-bold text-lg text-slate-800 shadow-sm">🔎 Buscar</button>
  </div>

  <div id="panel-escaner" hidden class="bg-white rounded-2xl border border-slate-200 shadow-sm p-3 mb-3">
    <p class="text-sm text-slate-600 mb-2">Apunta al código QR de la etiqueta de la caja.</p>
    <div id="lector" class="rounded-xl overflow-hidden"></div>
    <button type="button" id="btn-parar" class="mt-2 w-full border border-slate-300 rounded-lg py-2 text-slate-600">Cancelar</button>
  </div>

  @include('consulta._app', ['modo' => 'movil', 'urlDatos' => $urlDatos, 'urlMovs' => $urlMovs, 'urlFicha' => null])

  <script>
    (function () {
      let lector = null;
      const panel = document.getElementById('panel-escaner');
      async function parar() { try { if (lector) await lector.stop(); } catch (e) {} lector = null; panel.hidden = true; }

      document.getElementById('btn-escanear').addEventListener('click', async () => {
        panel.hidden = false;
        lector = new Html5Qrcode('lector', { formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE] });
        try {
          await lector.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 240, height: 240 } }, async (texto) => {
            const codigo = String(texto).trim().toUpperCase().replace(/'/g, '-');
            await parar();
            if (!codigo.startsWith('LOT-')) { alert('Ese código no es la etiqueta de una caja (' + codigo + ').'); return; }
            window.ConsultaLotes.abrirFicha(codigo);
          });
        } catch (e) {
          panel.hidden = true;
          alert('No se pudo abrir la cámara. Revisa el permiso de cámara del navegador.');
        }
      });
      document.getElementById('btn-parar').addEventListener('click', parar);
      document.getElementById('btn-buscar').addEventListener('click', () => {
        parar();
        const q = document.getElementById('cq'); q.focus(); q.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    })();
  </script>
@endsection
