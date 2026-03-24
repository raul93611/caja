-- ============================================================
-- Caja — Sistema de Gestión Financiera para Tienda de Abarrotes
-- Importar en phpMyAdmin o con: mysql -u root -p < caja.sql
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- usuarios
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`        VARCHAR(100) NOT NULL,
  `email`         VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `creado_en`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- categorias
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categorias` (
  `id`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`  VARCHAR(100) NOT NULL,
  `tipo`    ENUM('ingreso','egreso')                                    NOT NULL,
  `subtipo` ENUM('venta_producto','compra_producto','servicio','otro')  NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- transacciones
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `transacciones` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `usuario_id`   INT UNSIGNED  NOT NULL,
  `tipo`         ENUM('ingreso','egreso') NOT NULL,
  `categoria_id` INT UNSIGNED  NOT NULL,
  `monto`        DECIMAL(10,2) NOT NULL,
  `cantidad`     DECIMAL(10,3) NOT NULL DEFAULT '1.000',
  `detalles`     TEXT          NULL,
  `fecha`        DATE          NOT NULL,
  `creado_en`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_fecha`     (`fecha`),
  KEY `idx_tipo`      (`tipo`),
  KEY `idx_usuario`   (`usuario_id`),
  KEY `idx_categoria` (`categoria_id`),
  CONSTRAINT `fk_trans_usuario`
    FOREIGN KEY (`usuario_id`)   REFERENCES `usuarios`   (`id`),
  CONSTRAINT `fk_trans_categoria`
    FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Datos: categorías predefinidas
-- ------------------------------------------------------------
INSERT INTO `categorias` (`nombre`, `tipo`, `subtipo`) VALUES
  ('Venta de productos',  'ingreso', 'venta_producto'),
  ('Compra de mercancía', 'egreso',  'compra_producto'),
  ('Electricidad',        'egreso',  'servicio'),
  ('Renta',               'egreso',  'servicio'),
  ('Agua',                'egreso',  'servicio'),
  ('Otros servicios',     'egreso',  'otro');

-- ------------------------------------------------------------
-- Datos: usuario administrador de prueba
--   email:      admin@caja.com
--   contraseña: admin123
--
-- El hash fue generado con PHP 8:
--   password_hash('admin123', PASSWORD_DEFAULT)
-- ------------------------------------------------------------
INSERT INTO `usuarios` (`nombre`, `email`, `password_hash`) VALUES
  ('Administrador', 'admin@caja.com',
   '$2y$12$uTDSmkEPRGBbg5L5PkBrLeE1fYZmQ2YXbpf.KNkbm4KiG0BbKK3Gy');

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- Si la base de datos ya existe, ejecuta solo esta línea:
-- ALTER TABLE `transacciones` ADD COLUMN `detalles` TEXT NULL AFTER `cantidad`;
-- ------------------------------------------------------------
