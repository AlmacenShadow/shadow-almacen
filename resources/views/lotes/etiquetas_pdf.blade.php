<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Etiquetas {{ $lote->codigo_barcode }}</title>
  <style>
    /*
      Avery 5160 en hoja Letter (8.5" x 11" = 215.9mm x 279.4mm).
      Grid 3 cols x 10 rows = 30 etiquetas.

      Medidas oficiales Avery 5160:
        - Margen superior:    12.7 mm  (0.5")
        - Margen izquierdo:    4.8 mm  (0.1875")
        - Etiqueta:           66.7 mm × 25.4 mm  (2.625" × 1")
        - Gap horizontal:      3.2 mm  (0.125")
        - Gap vertical:        0 mm    (filas contiguas)

      Posición de etiqueta (1-indexed) en mm:
        top  = 12.7 + (row - 1) * 25.4
        left = 4.8  + (col - 1) * (66.7 + 3.2) = 4.8 + (col - 1) * 69.9
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
      width: 66.7mm;
      height: 25.4mm;
      box-sizing: border-box;
      padding: 2mm 2.5mm;
      overflow: hidden;
      /* sin borde en producción; se descomenta para debug del alineado */
      /* border: 0.1mm dashed #ccc; */
    }
    .et-header {
      display: block;
      font-size: 6pt;
      letter-spacing: 0.5pt;
      font-weight: bold;
      color: #666;
      margin-bottom: 0.5mm;
    }
    .et-ral {
      font-size: 11pt;
      font-weight: bold;
      line-height: 1;
      margin: 0;
    }
    .et-nombre-ral {
      font-size: 6.5pt;
      color: #444;
      font-style: italic;
      line-height: 1;
      margin: 0.3mm 0 0 0;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .et-textura {
      font-size: 7pt;
      color: #333;
      margin: 0.3mm 0 0 0;
      line-height: 1;
    }
    .et-fechas {
      font-size: 6.5pt;
      color: #555;
      margin: 0.5mm 0 0 0;
      line-height: 1.1;
    }
    .et-barcode {
      margin-top: 0.5mm;
    }
    .et-barcode img {
      width: 100%;
      height: 7mm;
      display: block;
    }
    .et-codigo {
      font-family: "Courier New", monospace;
      font-size: 7pt;
      font-weight: bold;
      text-align: center;
      margin: 0;
      line-height: 1;
    }
  </style>
</head>
<body>
  @foreach ($hojas as $hojaIdx => $celdas)
    <div class="hoja">
      @foreach ($celdas as $celda)
        @php
          $top  = 12.7 + ($celda['row'] - 1) * 25.4;
          $left = 4.8  + ($celda['col'] - 1) * 69.9;
        @endphp
        <div class="etiqueta" style="top: {{ $top }}mm; left: {{ $left }}mm;">
          <span class="et-header">SHADOW</span>
          <p class="et-ral">{{ $lote->producto->ral }}</p>
          @if ($lote->producto->nombre_ral_oficial)
            <p class="et-nombre-ral">{{ $lote->producto->nombre_ral_oficial }}</p>
          @endif
          <p class="et-textura">
            {{ $lote->producto->textura?->nombre ?? '?' }} · {{ $lote->producto->brillo_pct }}%
          </p>
          <p class="et-fechas">
            Rec. {{ $lote->fecha_recepcion->format('Y-m-d') }}@if ($lote->fecha_vencimiento) · Vence {{ $lote->fecha_vencimiento->format('Y-m-d') }}@endif
          </p>
          <div class="et-barcode">
            <img src="{{ $barcodeDataUri }}" alt="{{ $lote->codigo_barcode }}">
          </div>
          <p class="et-codigo">{{ $lote->codigo_barcode }}</p>
        </div>
      @endforeach
    </div>
  @endforeach
</body>
</html>
