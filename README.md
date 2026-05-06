# Shadow Almacén

Sistema de gestión del almacén de pintura electrostática de **Shadow Panamá S.A.**.
El pintor se identifica con barcode en un kiosko (tablet Android), escanea la caja
de pintura, pesa en una balanza digital conectada por USB y registra el movimiento.
Todo sin almacenista presente.

## Arquitectura en una línea

- **Backend**: Laravel 13 + MySQL (en hosting cPanel) — panel web del encargado + API.
- **Kiosko**: tablet Android con app Capacitor que lee la balanza por puerto serie y la pistola scanner como teclado HID.

## Levantar local

Requiere PHP 8.3 (vía Laravel Herd) y Composer.

```powershell
cd shadow-almacen
composer install
php artisan migrate:fresh --seed
php -S 127.0.0.1:9000 -t public public/index.php
```

Abrir `http://127.0.0.1:9000` y entrar con `admin@shadowpanama.com` / `admin123`.

## Despliegue (cPanel sin SSH)

1. cPanel → Git Version Control → Create → URL del repo (HTTPS si es público, o configurar deploy key si es privado).
2. **Repository Path**: `public_html/almacen.shadowpanama.com`.
3. cPanel → Subdomains → editar `almacen` → cambiar Document Root a `public_html/almacen.shadowpanama.com/public`.
4. cPanel → MySQL Databases → crear DB y usuario.
5. Subir el `.env` por File Manager con las credenciales de la DB.
6. cPanel → phpMyAdmin → importar dump de migraciones (o ejecutar SQL manualmente).
7. Cambios futuros: `git push` → cPanel "Update from Remote".

Cuando el hosting habilite SSH, descomentar las tareas del `.cpanel.yml` para
automatizar `composer install`, `artisan migrate` y los caches en cada update.
