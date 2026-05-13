<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Tablero de barcodes · Shadow Almacén</title>
  <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
  <style>
    body {
      font-family: -apple-system, system-ui, sans-serif;
      background: #f5f5f5;
      margin: 0; padding: 24px;
      color: #1e293b;
    }
    .hoja {
      background: #fff;
      max-width: 210mm;
      margin: 0 auto 24px;
      padding: 16mm;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      page-break-after: always;
    }
    .hoja:last-child { page-break-after: auto; }
    h1 {
      font-size: 22px; margin: 0 0 4px;
      border-bottom: 2px solid #f59e0b; padding-bottom: 8px;
    }
    .subtitulo { font-size: 12px; color: #64748b; margin: 0 0 18px; }

    /* Tarjetas grandes para tablero del almacén (pintores) */
    .grid-tablero {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 12mm;
    }
    .tarjeta-pintor {
      border: 2px solid #1e293b;
      border-radius: 8px;
      padding: 10mm;
      text-align: center;
    }
    .tarjeta-pintor .nombre {
      font-size: 24px; font-weight: 700; margin-bottom: 6px;
    }
    .tarjeta-pintor svg { width: 100%; height: 60px; }
    .tarjeta-pintor .codigo {
      font-family: monospace; font-size: 16px; font-weight: 700;
      margin-top: 4px;
    }

    /* Tarjetas pequeñas tipo llavero para encargado/admin */
    .grid-llavero {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 8mm;
    }
    .tarjeta-llavero {
      border: 1px solid #cbd5e1;
      border-radius: 6px;
      padding: 6mm;
      display: flex;
      align-items: center;
      gap: 8mm;
    }
    .tarjeta-llavero .info { flex: 1; }
    .tarjeta-llavero .nombre { font-weight: 700; font-size: 14px; margin: 0; }
    .tarjeta-llavero .rol {
      font-size: 10px; text-transform: uppercase; letter-spacing: 0.1em;
      color: #64748b;
    }
    .tarjeta-llavero svg { width: 38mm; height: 38px; }
    .tarjeta-llavero .codigo { font-family: monospace; font-size: 12px; }

    .badge {
      display: inline-block;
      padding: 2px 8px; border-radius: 999px;
      font-size: 10px; font-weight: 700; letter-spacing: 0.1em;
    }
    .badge.admin     { background: #ddd6fe; color: #5b21b6; }
    .badge.encargado { background: #dbeafe; color: #1e40af; }

    .barra-acciones {
      max-width: 210mm; margin: 0 auto 16px;
      display: flex; justify-content: space-between; align-items: center;
    }
    .btn {
      background: #1e293b; color: #fff;
      padding: 8px 16px; border: none; border-radius: 6px;
      cursor: pointer; font-weight: 600;
    }
    .btn:hover { background: #334155; }
    .vacio { color: #94a3b8; font-style: italic; padding: 12mm 0; text-align: center; }

    @media print {
      body { background: #fff; padding: 0; }
      .hoja { box-shadow: none; padding: 12mm; }
      .barra-acciones { display: none; }
    }
  </style>
</head>
<body>

  <div class="barra-acciones">
    <a href="{{ route('usuarios.index') }}" style="color: #64748b; text-decoration: none;">← volver</a>
    <button class="btn" onclick="window.print()">Imprimir</button>
  </div>

  {{-- HOJA 1: pintores (papel grande para colgar en pared del almacén) --}}
  <div class="hoja">
    <h1>Tablero del almacén — pintores</h1>
    <p class="subtitulo">Recorta cada tarjeta, plastifica y pega en el tablero al lado del kiosko</p>

    @if ($pintores->isEmpty())
      <p class="vacio">Aún no hay pintores activos.</p>
    @else
      <div class="grid-tablero">
        @foreach ($pintores as $p)
          <div class="tarjeta-pintor">
            <div class="nombre">{{ $p->nombre }}</div>
            <svg class="bc" jsbarcode-value="{{ $p->codigo_barcode }}"
                 jsbarcode-format="CODE128"
                 jsbarcode-displayvalue="false"
                 jsbarcode-height="60"
                 jsbarcode-margin="0"></svg>
            <div class="codigo">{{ $p->codigo_barcode }}</div>
          </div>
        @endforeach
      </div>
    @endif
  </div>

  {{-- HOJA 2: encargado / admin (tarjeta para llavero) --}}
  <div class="hoja">
    <h1>Identificación de encargados y admin</h1>
    <p class="subtitulo">Recorta cada tarjeta y entrega a la persona correspondiente (ej. llavero, tarjetero, etc.)</p>

    @if ($personal->isEmpty())
      <p class="vacio">No hay encargados ni admin activos.</p>
    @else
      <div class="grid-llavero">
        @foreach ($personal as $u)
          <div class="tarjeta-llavero">
            <div class="info">
              <p class="rol"><span class="badge {{ $u->rol }}">{{ $u->rol }}</span></p>
              <p class="nombre">{{ $u->nombre }}</p>
              <p class="codigo">{{ $u->codigo_barcode }}</p>
            </div>
            <svg class="bc" jsbarcode-value="{{ $u->codigo_barcode }}"
                 jsbarcode-format="CODE128"
                 jsbarcode-displayvalue="false"
                 jsbarcode-height="38"
                 jsbarcode-margin="0"></svg>
          </div>
        @endforeach
      </div>
    @endif
  </div>

  <script>
    JsBarcode(".bc").init();
  </script>
</body>
</html>
