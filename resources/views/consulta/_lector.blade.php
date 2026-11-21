{{--
  Lector de códigos para el celular: en vivo (html5-qrcode) y por FOTO.
  La foto la toma la cámara nativa del teléfono (mejor enfoque y resolución) y se decodifica aquí:
    1) BarcodeDetector nativo (Chrome/Android: muy bueno con códigos de barras),
    2) si no existe (iPhone), html5-qrcode sobre la foto reducida, también girada 90°.
  Uso: LectorCodigos.desdeFoto(file, ['code_128','qr_code']) → Promise<string|null>
       LectorCodigos.enVivo(elementId, ['code_128','qr_code'], onTexto) → Promise<{parar()}>
--}}
@once
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<div id="lector-oculto" style="display:none"></div>
<script>
window.LectorCodigos = (function () {
  const MAPA_FORMATOS = { code_128: 'CODE_128', qr_code: 'QR_CODE' };
  const fmtsLib = (f) => f.map(x => Html5QrcodeSupportedFormats[MAPA_FORMATOS[x]]);

  async function detectorNativo(formatos) {
    if (!('BarcodeDetector' in window)) return null;
    try {
      const soportados = await BarcodeDetector.getSupportedFormats();
      const usar = formatos.filter(f => soportados.includes(f));
      return usar.length ? new BarcodeDetector({ formats: usar }) : null;
    } catch (e) { return null; }
  }

  // Imagen → canvas reducido (máx. 1600 px de lado), opcionalmente girado 90°
  function aCanvas(img, girar) {
    const escala = Math.min(1, 1600 / Math.max(img.width, img.height));
    const w = Math.round(img.width * escala), h = Math.round(img.height * escala);
    const c = document.createElement('canvas');
    c.width = girar ? h : w; c.height = girar ? w : h;
    const ctx = c.getContext('2d');
    if (girar) { ctx.translate(h, 0); ctx.rotate(Math.PI / 2); }
    ctx.drawImage(img, 0, 0, w, h);
    return c;
  }

  const aBlob = (canvas) => new Promise(r => canvas.toBlob(r, 'image/jpeg', 0.92));

  async function cargarImagen(file) {
    if ('createImageBitmap' in window) {
      try { return await createImageBitmap(file, { imageOrientation: 'from-image' }); } catch (e) {}
    }
    return await new Promise((ok, mal) => {
      const img = new Image(); img.onload = () => ok(img); img.onerror = mal; img.src = URL.createObjectURL(file);
    });
  }

  async function desdeFoto(file, formatos) {
    const img = await cargarImagen(file);
    // 1) nativo
    const det = await detectorNativo(formatos);
    if (det) {
      for (const girar of [false, true]) {
        try {
          const r = await det.detect(aCanvas(img, girar));
          if (r && r.length) return r[0].rawValue;
        } catch (e) {}
      }
    }
    // 2) librería (reducida; normal y girada)
    const lector = new Html5Qrcode('lector-oculto', { formatsToSupport: fmtsLib(formatos), verbose: false });
    for (const girar of [false, true]) {
      try {
        const blob = await aBlob(aCanvas(img, girar));
        const txt = await lector.scanFile(new File([blob], 'foto.jpg', { type: 'image/jpeg' }), false);
        if (txt) return txt;
      } catch (e) {}
    }
    return null;
  }

  async function enVivo(elementId, formatos, onTexto) {
    const lector = new Html5Qrcode(elementId, {
      formatsToSupport: fmtsLib(formatos), verbose: false,
      experimentalFeatures: { useBarCodeDetectorIfSupported: true },
    });
    const solo1D = !formatos.includes('qr_code');
    await lector.start(
      { facingMode: 'environment' },
      {
        fps: 12,
        // caja de lectura ancha para códigos de barras; cuadrada para QR
        qrbox: (w, h) => formatos.includes('code_128')
          ? { width: Math.floor(w * 0.9), height: Math.floor(Math.min(h, w) * (solo1D ? 0.35 : 0.6)) }
          : { width: Math.floor(Math.min(w, h) * 0.7), height: Math.floor(Math.min(w, h) * 0.7) },
        videoConstraints: { facingMode: 'environment', width: { ideal: 1920 }, height: { ideal: 1080 }, focusMode: 'continuous' },
      },
      async (texto) => { try { await lector.stop(); } catch (e) {} onTexto(texto); }
    );
    return { parar: async () => { try { await lector.stop(); } catch (e) {} } };
  }

  return { desdeFoto, enVivo };
})();
</script>
@endonce
