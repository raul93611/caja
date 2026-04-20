-- ============================================================
-- Migración: reemplazar 'Venta de productos' por Alimento / Ropa / Productos
-- Fecha: 2026-04-20
--
-- Ejecutar UNA sola vez en la base de datos remota (phpMyAdmin o MySQL CLI).
-- Es idempotente: si ya corrió, los INSERT IGNORE / UPDATE / DELETE no hacen
-- nada. No ejecutar `caja.sql` completo sobre una base existente — duplicaría
-- todas las categorías.
-- ============================================================

START TRANSACTION;

INSERT IGNORE INTO `categorias` (`nombre`, `tipo`, `subtipo`) VALUES
  ('Alimento',  'ingreso', 'venta_producto'),
  ('Ropa',      'ingreso', 'venta_producto'),
  ('Productos', 'ingreso', 'venta_producto');

UPDATE `transacciones`
  SET `categoria_id` = (
    SELECT `id` FROM `categorias`
    WHERE `nombre` = 'Productos' AND `tipo` = 'ingreso' LIMIT 1
  )
  WHERE `categoria_id` = (
    SELECT `id` FROM (
      SELECT `id` FROM `categorias`
      WHERE `nombre` = 'Venta de productos' AND `tipo` = 'ingreso' LIMIT 1
    ) AS x
  );

DELETE FROM `categorias`
  WHERE `nombre` = 'Venta de productos' AND `tipo` = 'ingreso';

COMMIT;

-- Verificación:
-- SELECT id, nombre, tipo FROM categorias ORDER BY tipo, id;
