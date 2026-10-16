<!DOCTYPE html>
@php
  /*
    CALIBRACION — medida el 2026-10-15 sobre la hoja fisica y sobre una
    impresion al 100%. Hoja Letter (215.9 x 279.4mm), grid 3 cols x 10 rows,
    etiquetas genericas (NO Avery 5160).

    Se midio, entre el borde de la primera y el de la ultima:

                        en la hoja fisica   en el PDF impreso   diseñado
      vertical   (f1->f10)   228.5mm             225mm            225mm
      horizontal (c1->c3)    141.5mm             143.5mm          142mm

    Dos correcciones distintas salen de ahi:

    1) El paso real de las etiquetas no es el que creiamos:
         vertical   228.5 / 9 intervalos = 25.389mm  (no 25)
         horizontal 141.5 / 2 intervalos = 70.75mm   (no 71)

    2) La impresora estira ~1.06% SOLO en horizontal: lo diseñado a 142mm
       salio a 143.5mm, mientras que en vertical 225mm salio exacto. Por eso
       todas las medidas horizontales se predividen por esa escala (asi salen
       impresas en su valor real) y las verticales se dejan tal cual.

    Si cambias de impresora hay que volver a medir: imprime la version con
    bordes al 100% y comprueba que f1->f10 de 228.5mm y c1->c3 de 141.5mm.
  */
  $escalaH = 143.5 / 142;          // estiramiento horizontal de la impresora

  $pasoV   = 228.5 / 9;            // 25.389mm
  $pasoH   = (141.5 / 2) / $escalaH;

  $margenTop  = 14;
  $margenLeft = 4 / $escalaH;

  $altoEt  = $pasoV;               // filas contiguas: alto = paso
  $anchoEt = 67 / $escalaH;

  // El QR se predivide tambien, si no la impresora lo saca 1% mas ancho que
  // alto y deja de ser cuadrado.
  $qrLado = 15 / $escalaH;
@endphp
<html>
<head>
  <meta charset="UTF-8">
  <title>Etiquetas {{ $lote->codigo_barcode }}</title>
  <style>
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
    /* El padding-top centra el bloque de texto verticalmente: el peor caso
       (nombre que baja de linea) mide ~19mm en una etiqueta de ~25.4mm, asi que
       {{ round(($altoEt - 19) / 2, 1) }}mm arriba lo deja con margen parejo
       arriba y abajo (~3mm de aire a cada lado). Eso absorbe un ±2% de escala
       vertical de la impresora sin que el texto toque el troquel. El QR es
       absolute, asi que este padding solo mueve el texto. */
    .etiqueta {
      position: absolute;
      width: {{ round($anchoEt, 3) }}mm;
      height: {{ round($altoEt, 3) }}mm;
      box-sizing: border-box;
      padding-top: {{ round(($altoEt - 19) / 2, 2) }}mm;
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
      left: {{ round(2 / $escalaH, 3) }}mm;
      width: {{ round($qrLado, 3) }}mm;
    }
    .et-marca img {
      width: {{ round($qrLado, 3) }}mm;
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

    /* Columna de texto. El QR es absolute en left:2mm y mide 15mm, asi que
       termina en 17mm; el texto arranca 2mm despues, en 19mm, para dejar aire
       real entre el QR y el texto. */
    .et-info {
      padding-left: {{ round(19 / $escalaH, 3) }}mm;
      padding-right: {{ round(2 / $escalaH, 3) }}mm;
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
          $top  = $margenTop  + ($celda['row'] - 1) * $pasoV;
          $left = $margenLeft + ($celda['col'] - 1) * $pasoH;

          $fechas = 'R' . $lote->fecha_recepcion->format('ym');
          if ($lote->fecha_vencimiento) {
              $fechas .= ' / V' . $lote->fecha_vencimiento->format('ym');
          }
        @endphp
        <div class="etiqueta" style="top: {{ round($top, 3) }}mm; left: {{ round($left, 3) }}mm;">
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
