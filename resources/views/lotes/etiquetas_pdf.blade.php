<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Etiquetas {{ $lote->codigo_barcode }}</title>
  <style>
    /*
      Hoja Letter (8.5" x 11" = 215.9mm x 279.4mm), grid 3 cols x 10 rows.
      NO son Avery 5160: son etiquetas genericas, medidas por el usuario.

        Horizontal: 4mm + 67mm + 4mm + 67mm + 4mm + 67mm + 4mm
        Vertical:   14mm + (25mm x 10) + 14mm   (filas contiguas, sin gap)

      Las sumas del usuario dan 217mm y 278mm contra los 215.9 y 279.4 reales,
      asi que vienen redondeadas. Anclamos en el margen izquierdo (4mm) y el
      superior (14mm), que son los que definen donde arranca la grilla; la
      diferencia se absorbe en los margenes derecho e inferior.

      Posicion de etiqueta (1-indexed) en mm:
        top  = 14 + (row - 1) * 25
        left = 4  + (col - 1) * 71
    */
    @page {
      size: letter portrait;
      margin: 0;
    }
    body {
      margin: 0;
      padding: 0;
      font-family: "Helvetica", "Arial", sans-serif;
      color: #000;
    }
    .hoja {
      position: relative;
      width: 215.9mm;
      height: 279.4mm;
      page-break-after: always;
    }
    .hoja:last-child {
      page-break-after: auto;
    }
    .etiqueta {
      position: absolute;
      width: 67mm;
      height: 25mm;
      box-sizing: border-box;
      padding: 1.5mm 2mm;
      overflow: hidden;
      /* sin borde en produccion; descomentar para debug de alineado */
      /* border: 0.2mm dashed #e11; */
    }

    /* QR cuadrado a la derecha. Absolute para que dompdf no lo meta en el flujo
       vertical y nos deje el alto completo para el bloque de texto. */
    .et-qr {
      position: absolute;
      top: 2.5mm;
      right: 2mm;
    }
    .et-qr img {
      width: 20mm;
      height: 20mm;
      display: block;
    }

    /* Columna de texto a la izquierda. El padding-right reserva el ancho del QR
       (20mm) mas 2mm de aire, sobre los 63mm de caja de contenido => 41mm utiles. */
    .et-info {
      padding-right: 22mm;
    }
    .et-shadow {
      display: block;
      font-size: 6pt;
      line-height: 1;
      letter-spacing: 0.5pt;
      font-weight: bold;
      color: #666;
    }
    .et-ral {
      font-size: 13pt;
      font-weight: bold;
      line-height: 1;
      margin: 0.3mm 0 0 0;
    }
    /* Nombre oficial K7 + textura/brillo entre corchetes, en una sola linea */
    .et-detalle {
      font-size: 6.5pt;
      color: #333;
      margin: 0.5mm 0 0 0;
      line-height: 1;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .et-nombre {
      font-weight: bold;
      text-transform: uppercase;
      color: #222;
    }
    .et-textura-inline {
      color: #555;
    }
    .et-fechas {
      font-size: 6pt;
      color: #555;
      line-height: 1.3;
      margin: 0.8mm 0 0 0;
    }
    .et-codigo {
      font-family: "Courier New", monospace;
      font-size: 8pt;
      font-weight: bold;
      margin: 0.8mm 0 0 0;
      line-height: 1;
    }
  </style>
</head>
<body>
  @foreach ($hojas as $celdas)
    <div class="hoja">
      @foreach ($celdas as $celda)
        @php
          $top  = 14 + ($celda['row'] - 1) * 25;
          $left = 4  + ($celda['col'] - 1) * 71;
        @endphp
        <div class="etiqueta" style="top: {{ $top }}mm; left: {{ $left }}mm;">
          <div class="et-qr">
            <img src="{{ $qrDataUri }}" alt="{{ $lote->codigo_barcode }}">
          </div>
          <div class="et-info">
            <span class="et-shadow">SHADOW</span>
            <div class="et-ral">{{ $lote->producto->ral }}</div>
            <div class="et-detalle">
              @if ($lote->producto->nombre_ral_oficial)
                <span class="et-nombre">{{ $lote->producto->nombre_ral_oficial }}</span>
              @endif
              <span class="et-textura-inline">[{{ $lote->producto->textura?->nombre ?? '?' }} · {{ $lote->producto->brillo_pct }}%]</span>
            </div>
            <div class="et-fechas">
              <div>Rec. {{ $lote->fecha_recepcion->format('Y-m-d') }}</div>
              @if ($lote->fecha_vencimiento)
                <div>Vence {{ $lote->fecha_vencimiento->format('Y-m-d') }}</div>
              @endif
            </div>
            <p class="et-codigo">{{ $lote->codigo_barcode }}</p>
          </div>
        </div>
      @endforeach
    </div>
  @endforeach
</body>
</html>
