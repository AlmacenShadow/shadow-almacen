<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Buscador de lotes</title>
<style>
  @page { margin: 14mm; }
  body { font-family: DejaVu Sans, sans-serif; color: #0f172a; text-align: center; }
  .marca { font-size: 12pt; letter-spacing: 3pt; color: #b45309; font-weight: bold; margin-top: 2mm; }
  h1 { font-size: 30pt; margin: 4mm 0 2mm; }
  .sub { font-size: 16pt; color: #475569; margin: 0; }
  .qr { margin-top: 6mm; }
  .qr img { width: 105mm; height: 105mm; }
  ol { text-align: left; font-size: 14pt; margin: 8mm auto 0; width: 160mm; line-height: 1.5; }
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
