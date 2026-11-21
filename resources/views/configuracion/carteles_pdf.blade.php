<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Carteles de ubicación</title>
<style>
  @page { margin: 12mm; }
  body { font-family: DejaVu Sans, sans-serif; margin: 0; color: #0f172a; }
  /* 2 carteles por hoja carta: cada uno ocupa media hoja, para recortar por la línea */
  .cartel { height: 122mm; border: 2px dashed #94a3b8; border-radius: 6mm; position: relative; overflow: hidden; }
  .cartel + .cartel { margin-top: 8mm; }
  .salto { page-break-after: always; }
  table { width: 100%; border-collapse: collapse; margin-top: 22mm; }
  td { vertical-align: middle; }
  .nombre { font-size: 150pt; font-weight: bold; line-height: 1; letter-spacing: -4pt; padding-left: 10mm; }
  .sub { font-size: 16pt; color: #475569; padding-left: 12mm; margin-top: 4mm; }
  .marca { font-size: 10pt; color: #94a3b8; padding-left: 12mm; margin-top: 2mm; letter-spacing: 2pt; }
  .qr { text-align: center; width: 85mm; }
  .qr img { width: 72mm; height: 72mm; }
  .codigo { font-family: DejaVu Sans Mono, monospace; font-size: 12pt; margin-top: 2mm; }
</style>
</head>
<body>
@foreach ($carteles as $i => $c)
  <div class="cartel">
    <table><tr>
      <td>
        <div class="nombre">{{ $c['nombre'] }}</div>
        <div class="sub">{{ $c['sub'] }}</div>
        <div class="marca">SHADOW · ALMACÉN</div>
      </td>
      <td class="qr">
        <img src="{{ $c['qr'] }}" alt="{{ $c['codigo'] }}">
        <div class="codigo">{{ $c['codigo'] }}</div>
      </td>
    </tr></table>
  </div>
  @if ($i % 2 === 1 && ! $loop->last)
    <div class="salto"></div>
  @endif
@endforeach
</body>
</html>
