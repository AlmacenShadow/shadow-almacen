@extends('consulta._movil_base')
@section('title', 'Buscar lotes')

@section('header-derecha')
  <form method="POST" action="{{ route('consulta.salir', $token) }}" class="flex items-center gap-2 text-xs text-slate-300">
    @csrf
    <span>{{ $usuario->nombre }}</span>
    <button class="underline text-slate-400">salir</button>
  </form>
@endsection

@section('content')
  @include('consulta._lector')

  {{-- Dos acciones grandes: escanear una caja (foto o en vivo) o buscar --}}
  <div class="grid grid-cols-2 gap-3 mb-3">
    <label class="bg-amber-500 text-white rounded-2xl py-5 font-bold text-lg shadow flex items-center justify-center gap-2 cursor-pointer">
      📸 Foto de caja
      <input type="file" id="foto-caja" accept="image/*" capture="environment" class="hidden">
    </label>
    <button type="button" id="btn-buscar" class="bg-white border border-slate-300 rounded-2xl py-5 font-bold text-lg text-slate-800 shadow-sm">🔎 Buscar</button>
  </div>
  <div class="flex items-center justify-between mb-3 px-1">
    <p id="estado-caja" class="text-sm text-slate-600" hidden></p>
    <button type="button" id="btn-vivo" class="ml-auto text-sm text-slate-600 underline">📷 escanear en vivo</button>
  </div>

  <div id="panel-escaner" hidden class="bg-white rounded-2xl border border-slate-200 shadow-sm p-3 mb-3">
    <p class="text-sm text-slate-600 mb-2">Apunta al código QR de la etiqueta de la caja.</p>
    <div id="lector" class="rounded-xl overflow-hidden"></div>
    <button type="button" id="btn-parar" class="mt-2 w-full border border-slate-300 rounded-lg py-2 text-slate-600">Cancelar</button>
  </div>

  @include('consulta._app', ['modo' => 'movil', 'urlDatos' => $urlDatos, 'urlMovs' => $urlMovs, 'urlFicha' => null])

  <script>
    (function () {
      const FORMATOS = ['qr_code', 'code_128'];
      const estado = document.getElementById('estado-caja');
      const panel = document.getElementById('panel-escaner');
      let vivo = null;
      const aviso = (t) => { estado.hidden = !t; estado.textContent = t || ''; };

      function abrirCodigo(texto) {
        const codigo = String(texto).trim().toUpperCase().replace(/'/g, '-');
        if (!codigo.startsWith('LOT-')) { aviso('Ese código no es la etiqueta de una caja (' + codigo + ').'); return; }
        aviso('');
        window.ConsultaLotes.abrirFicha(codigo);
      }
      async function parar() { if (vivo) await vivo.parar(); vivo = null; panel.hidden = true; }

      document.getElementById('foto-caja').addEventListener('change', async (e) => {
        const f = e.target.files && e.target.files[0];
        if (!f) return;
        aviso('Leyendo la foto…');
        const texto = await LectorCodigos.desdeFoto(f, FORMATOS).catch(() => null);
        e.target.value = '';
        if (texto) abrirCodigo(texto);
        else aviso('No pude leer la etiqueta. Prueba más cerca y con buena luz.');
      });

      document.getElementById('btn-vivo').addEventListener('click', async () => {
        panel.hidden = false;
        try { vivo = await LectorCodigos.enVivo('lector', FORMATOS, (t) => { panel.hidden = true; vivo = null; abrirCodigo(t); }); }
        catch (e) { panel.hidden = true; aviso('No se pudo abrir la cámara en vivo. Usa "Foto de caja".'); }
      });
      document.getElementById('btn-parar').addEventListener('click', parar);
      document.getElementById('btn-buscar').addEventListener('click', () => {
        parar();
        const q = document.getElementById('cq'); q.focus(); q.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    })();
  </script>
@endsection
