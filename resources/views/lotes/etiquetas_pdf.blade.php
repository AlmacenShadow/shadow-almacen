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

    /* Columna izquierda: QR con la pildora de color debajo. Absolute para que
       dompdf no la meta en el flujo vertical y nos deje el alto completo
       disponible para el bloque de texto. */
    .et-marca {
      position: absolute;
      top: 2mm;
      left: 2mm;
      width: 15mm;
    }
    .et-marca img {
      width: 15mm;
      height: 15mm;
      display: block;
    }
    /* Muestra del color RAL. El fondo va inline porque sale de la BD. */
    .et-pildora {
      height: 3.5mm;
      margin-top: 1.2mm;
      border: 0.2mm solid #999;
      border-radius: 1.75mm;
    }

    /* Columna de texto, pegada al borde derecho del QR. El padding-left
       reserva los 15mm del QR mas 2mm de aire, sobre los 63mm de caja de
       contenido => 46mm utiles. */
    .et-info {
      padding-left: 17mm;
    }
    /* Bloque 1 — producto: RAL + nombre oficial en una linea, textura debajo.
       El nombre comparte linea base con el RAL; si es largo se recorta contra
       el ancho de la columna en vez de empujar nada. */
    .et-titulo {
      line-height: 1;
      white-space: nowrap;
      overflow: hidden;
    }
    .et-ral {
      font-size: 13pt;
      font-weight: bold;
    }
    .et-nombre {
      font-size: 6.5pt;
      font-weight: bold;
      text-transform: uppercase;
      color: #222;
      margin-left: 1.5mm;
    }
    .et-textura {
      font-size: 11pt;
      color: #333;
      line-height: 1;
      margin: 0.4mm 0 0 0;
      white-space: nowrap;
      overflow: hidden;
    }
    /* Bloque 2 — almacen: lote y fechas. El margen de arriba es el corte
       visual entre que ES la pintura y de que entrega vino. */
    .et-codigo {
      font-family: "Courier New", monospace;
      font-size: 11pt;
      font-weight: bold;
      line-height: 1;
      margin: 2mm 0 0 0;
    }
    /* Fechas compactas: R2605 = recibido mayo 2026, V2609 = vence sep 2026 */
    .et-fechas {
      font-size: 6.5pt;
      color: #555;
      line-height: 1;
      margin: 0.5mm 0 0 0;
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

          $fechas = 'R' . $lote->fecha_recepcion->format('ym');
          if ($lote->fecha_vencimiento) {
              $fechas .= ' / V' . $lote->fecha_vencimiento->format('ym');
          }
        @endphp
        <div class="etiqueta" style="top: {{ $top }}mm; left: {{ $left }}mm;">
          <div class="et-marca">
            <img src="{{ $qrDataUri }}" alt="{{ $lote->codigo_barcode }}">
            <div class="et-pildora" style="background-color: {{ $lote->producto->hex }};"></div>
          </div>
          <div class="et-info">
            <div class="et-titulo">
              <span class="et-ral">{{ $lote->producto->ral }}</span>
              @if ($lote->producto->nombre_ral_oficial)
                <span class="et-nombre">{{ $lote->producto->nombre_ral_oficial }}</span>
              @endif
            </div>
            <div class="et-textura">[{{ $lote->producto->textura?->nombre ?? '?' }} · {{ $lote->producto->brillo_pct }}%]</div>
            <p class="et-codigo">{{ $lote->codigo_barcode }}</p>
            <div class="et-fechas">{{ $fechas }}</div>
          </div>
        </div>
      @endforeach
    </div>
  @endforeach
</body>
</html>
