-- ============================================================
-- Shadow Almacén — Script de inicialización de producción
-- ============================================================
-- Crea todas las tablas, vistas, motivos de ajuste, parámetros
-- y los usuarios/productos de arranque.
--
-- Para usar: importar desde phpMyAdmin contra la base de datos
-- recién creada en cPanel.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- TABLA: usuarios (pintores, encargados, admin — todos juntos)
-- ============================================================
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `nombre` VARCHAR(120) NOT NULL,
  `rol` ENUM('pintor','encargado','admin') NOT NULL,
  `codigo_barcode` VARCHAR(32) NOT NULL UNIQUE,
  `email` VARCHAR(120) NULL UNIQUE,
  `password_hash` VARCHAR(255) NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `remember_token` VARCHAR(100) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: productos
-- ============================================================
DROP TABLE IF EXISTS `productos`;
CREATE TABLE `productos` (
  `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `ral` VARCHAR(16) NOT NULL,
  `textura` VARCHAR(40) NOT NULL,
  `brillo_pct` TINYINT UNSIGNED NOT NULL,
  `nombre_interno` VARCHAR(120) NULL,
  `stock_minimo_kg` DECIMAL(9,3) NOT NULL DEFAULT 0,
  `stock_critico_kg` DECIMAL(9,3) NOT NULL DEFAULT 0,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_producto` (`ral`, `textura`, `brillo_pct`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: motivos_ajuste (catálogo)
-- ============================================================
DROP TABLE IF EXISTS `motivos_ajuste`;
CREATE TABLE `motivos_ajuste` (
  `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `codigo` VARCHAR(40) NOT NULL UNIQUE,
  `descripcion` VARCHAR(120) NOT NULL,
  `signo` TINYINT NOT NULL,
  `requiere_nota` TINYINT(1) NOT NULL DEFAULT 0,
  `activo` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: lotes
-- ============================================================
DROP TABLE IF EXISTS `lotes`;
CREATE TABLE `lotes` (
  `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `producto_id` BIGINT UNSIGNED NOT NULL,
  `fecha_recepcion` DATE NOT NULL,
  `fecha_vencimiento` DATE NULL,
  `peso_total_recepcionado_kg` DECIMAL(10,3) NOT NULL,
  `peso_tara_unitario_kg` DECIMAL(6,3) NOT NULL DEFAULT 0,
  `cantidad_cajas` INT UNSIGNED NOT NULL,
  `proveedor` VARCHAR(120) NULL,
  `factura` VARCHAR(60) NULL,
  `origen` ENUM('recepcion','migracion_inicial') NOT NULL DEFAULT 'recepcion',
  `recepcionado_por_id` BIGINT UNSIGNED NOT NULL,
  `codigo_barcode` VARCHAR(40) NOT NULL UNIQUE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_lote` (`producto_id`, `fecha_recepcion`),
  INDEX `idx_fifo` (`producto_id`, `fecha_recepcion`),
  CONSTRAINT `fk_lotes_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`),
  CONSTRAINT `fk_lotes_recepcionado_por` FOREIGN KEY (`recepcionado_por_id`) REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: movimientos (append-only)
-- ============================================================
DROP TABLE IF EXISTS `movimientos`;
CREATE TABLE `movimientos` (
  `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `lote_id` BIGINT UNSIGNED NOT NULL,
  `usuario_id` BIGINT UNSIGNED NOT NULL,
  `tipo` ENUM('salida','retorno','ajuste') NOT NULL,
  `peso_kg` DECIMAL(9,3) NOT NULL,
  `peso_manual` TINYINT(1) NOT NULL DEFAULT 0,
  `motivo_ajuste_id` BIGINT UNSIGNED NULL,
  `nota_texto` TEXT NULL,
  `anomalia` TINYINT(1) NOT NULL DEFAULT 0,
  `tipo_anomalia` VARCHAR(40) NULL,
  `sync_uuid` CHAR(36) NOT NULL UNIQUE,
  `device_id` VARCHAR(40) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `device_at` TIMESTAMP NULL,
  INDEX `idx_lote` (`lote_id`),
  INDEX `idx_usuario` (`usuario_id`),
  INDEX `idx_fecha` (`created_at`),
  CONSTRAINT `fk_mov_lote` FOREIGN KEY (`lote_id`) REFERENCES `lotes`(`id`),
  CONSTRAINT `fk_mov_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`),
  CONSTRAINT `fk_mov_motivo` FOREIGN KEY (`motivo_ajuste_id`) REFERENCES `motivos_ajuste`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: parametros
-- ============================================================
DROP TABLE IF EXISTS `parametros`;
CREATE TABLE `parametros` (
  `clave` VARCHAR(60) PRIMARY KEY,
  `valor` TEXT NOT NULL,
  `descripcion` VARCHAR(200) NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLAS de Laravel framework (sessions, cache, jobs, migrations)
-- ============================================================
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `migration` VARCHAR(255) NOT NULL,
  `batch` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla `users` (legacy de Laravel, vacía — usamos `usuarios`)
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `email_verified_at` TIMESTAMP NULL,
  `password` VARCHAR(255) NOT NULL,
  `remember_token` VARCHAR(100) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` VARCHAR(255) PRIMARY KEY,
  `token` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` VARCHAR(255) PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `payload` LONGTEXT NOT NULL,
  `last_activity` INT NOT NULL,
  INDEX `sessions_user_id_index` (`user_id`),
  INDEX `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` VARCHAR(255) PRIMARY KEY,
  `value` MEDIUMTEXT NOT NULL,
  `expiration` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` VARCHAR(255) PRIMARY KEY,
  `owner` VARCHAR(255) NOT NULL,
  `expiration` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `queue` VARCHAR(255) NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `attempts` TINYINT UNSIGNED NOT NULL,
  `reserved_at` INT UNSIGNED NULL,
  `available_at` INT UNSIGNED NOT NULL,
  `created_at` INT UNSIGNED NOT NULL,
  INDEX `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` VARCHAR(255) PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `total_jobs` INT NOT NULL,
  `pending_jobs` INT NOT NULL,
  `failed_jobs` INT NOT NULL,
  `failed_job_ids` LONGTEXT NOT NULL,
  `options` MEDIUMTEXT NULL,
  `cancelled_at` INT NULL,
  `created_at` INT NOT NULL,
  `finished_at` INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `uuid` VARCHAR(255) NOT NULL UNIQUE,
  `connection` TEXT NOT NULL,
  `queue` TEXT NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `exception` LONGTEXT NOT NULL,
  `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- VISTAS: stock derivado de los movimientos
-- ============================================================
DROP VIEW IF EXISTS `v_stock_lote`;
CREATE VIEW `v_stock_lote` AS
SELECT
  l.id AS lote_id,
  l.producto_id,
  l.fecha_recepcion,
  l.fecha_vencimiento,
  l.peso_total_recepcionado_kg
    - COALESCE(SUM(CASE WHEN m.tipo='salida'  THEN m.peso_kg END), 0)
    + COALESCE(SUM(CASE WHEN m.tipo='retorno' THEN m.peso_kg END), 0)
    + COALESCE(SUM(CASE WHEN m.tipo='ajuste'  THEN m.peso_kg * ma.signo END), 0)
    AS stock_kg
FROM lotes l
LEFT JOIN movimientos    m  ON m.lote_id = l.id
LEFT JOIN motivos_ajuste ma ON ma.id = m.motivo_ajuste_id
GROUP BY l.id, l.producto_id, l.fecha_recepcion, l.fecha_vencimiento, l.peso_total_recepcionado_kg;

DROP VIEW IF EXISTS `v_stock_producto`;
CREATE VIEW `v_stock_producto` AS
SELECT producto_id, SUM(stock_kg) AS stock_kg
FROM v_stock_lote
GROUP BY producto_id;

-- ============================================================
-- SEED: motivos de ajuste
-- ============================================================
INSERT INTO `motivos_ajuste` (`codigo`, `descripcion`, `signo`, `requiere_nota`, `activo`) VALUES
('MERMA_DERRAME',         'Derrame o caída de caja',                -1, 1, 1),
('MERMA_HUMEDAD',         'Daño por humedad',                       -1, 1, 1),
('MERMA_CONTAMINACION',   'Contaminación / mezcla',                 -1, 1, 1),
('MERMA_VENCIMIENTO',     'Descarte por vencimiento',               -1, 1, 1),
('DEVOLUCION_PROVEEDOR',  'Devolución al proveedor',                -1, 1, 1),
('INGRESO_AJUSTE_FISICO', 'Ajuste físico: sobrante encontrado',      1, 1, 1),
('MERMA_AJUSTE_FISICO',   'Ajuste físico: faltante',                -1, 1, 1),
('CORRECCION_REGISTRO',   'Corrección de movimiento mal capturado',  1, 1, 1),
('MUESTRA_QC',            'Muestra para prueba de color',           -1, 1, 1),
('OTRO',                  'Otro motivo',                            -1, 1, 1);

-- ============================================================
-- SEED: parametros globales
-- ============================================================
INSERT INTO `parametros` (`clave`, `valor`, `descripcion`) VALUES
('balanza.peso_estable_ms',       '1500',     'ms de lectura estable antes de permitir confirmar'),
('balanza.peso_minimo_kg',        '0.5',      'peso mínimo en báscula para habilitar confirmación'),
('balanza.modo_sin_balanza',      'false',    'modo pruebas sin hardware; captura manual siempre'),
('stock.dias_lead_time',          '30',       'días de lead-time del proveedor para alerta de reposición'),
('stock.dias_cobertura_amarillo', '45',       'umbral amarillo para cobertura vs consumo promedio'),
('stock.dias_cobertura_rojo',     '30',       'umbral rojo para cobertura vs consumo promedio'),
('vencimiento.dias_alerta',       '60,30,15', 'alertas escalonadas de próximos a vencer'),
('ventana_consumo.dias',          '60',       'ventana para calcular consumo promedio');

-- ============================================================
-- SEED: usuarios de arranque
-- ============================================================
-- Contraseñas: admin123 y luis123 (cambiar después del primer login)
-- Los hashes bcrypt son válidos para estas contraseñas:
INSERT INTO `usuarios` (`nombre`, `rol`, `codigo_barcode`, `email`, `password_hash`, `activo`) VALUES
('Admin Demo',   'admin',     'ADM-0001', 'admin@shadowpanama.com', '$2y$12$eJtXdpJ0HRAoLIrfo5GeR.H5AHc162edmMpFHWXFGhFyAoB0k81bi', 1),
('Luis Ramírez', 'encargado', 'ENC-0001', 'luis@shadowpanama.com',  '$2y$12$YoSr2CyAEYggeYftRCUw8eN8vRjA9ZxvtC4genkPBFO9XIWCRA0NW', 1),
('Juan Pérez',   'pintor',    'PNT-0001', NULL, NULL, 1),
('Carlos Díaz',  'pintor',    'PNT-0002', NULL, NULL, 1);

-- ============================================================
-- SEED: productos demo
-- ============================================================
INSERT INTO `productos` (`ral`, `textura`, `brillo_pct`, `nombre_interno`, `stock_minimo_kg`, `stock_critico_kg`, `activo`) VALUES
('RAL9005', 'Mate',        30, 'Negro mate',          50, 20, 1),
('RAL9016', 'Texturizado', 20, 'Blanco texturizado',  40, 15, 1);

-- ============================================================
-- Migraciones marcadas como aplicadas (para que Laravel no
-- intente correr `php artisan migrate` y duplique tablas)
-- ============================================================
INSERT INTO `migrations` (`migration`, `batch`) VALUES
('0001_01_01_000000_create_users_table',          1),
('0001_01_01_000001_create_cache_table',          1),
('0001_01_01_000002_create_jobs_table',           1),
('2026_05_05_030022_create_usuarios_table',       1),
('2026_05_05_030023_create_productos_table',      1),
('2026_05_05_030024_create_lotes_table',          1),
('2026_05_05_030024_create_motivos_ajuste_table', 1),
('2026_05_05_030025_create_movimientos_table',    1),
('2026_05_05_030026_create_parametros_table',     1),
('2026_05_05_030026_create_stock_views',          1);

SET FOREIGN_KEY_CHECKS = 1;
