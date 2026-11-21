<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Buscador de lotes</title>
<style>
  @page { margin: 18mm; }
  body { font-family: DejaVu Sans, sans-serif; color: #0f172a; text-align: center; }
  .marca { font-size: 13pt; letter-spacing: 3pt; color: #b45309; font-weight: bold; margin-top: 10mm; }
  h1 { font-size: 40pt; margin: 6mm 0 2mm; }
  .sub { font-size: 16pt; color: #475569; margin: 0; }
  .qr { margin-top: 12mm; }
  .qr img { width: 120mm; height: 120mm; }
  ol { text-align: left; font-size: 15pt; margin: 12mm auto 0; width: 150mm; line-height: 1.6; }
</style>
</head>
<body>
  <div class="marca">SHADOW · ALMACÉN</div>
  <h1>¿Dónde está esa pintura?</h1>
  <p class="sub">Escanea con la cámara de tu celular</p>
  <div class="qr"><img src="{{ $qr }}" alt="QR"></div>
  <ol>
    <li>La primera vez, escanea tu gafete.</li>
    <li>Busca por color, RAL, cliente o marca y mira en el mapa dónde está.</li>
    <li>O escanea la etiqueta de una caja para ver toda su información.</li>
  </ol>
</body>
</html>
