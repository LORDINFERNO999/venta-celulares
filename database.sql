-- ============================================================
--  Base de datos de la tienda de celulares
--  Ejecuta este archivo en phpMyAdmin (pestaña "Importar") o en la
--  consola de MySQL para crear las tablas que usa la aplicación.
--
--  Nota: los USUARIOS del panel (admin / vendedor) NO se guardan aquí,
--  están predefinidos en auth.php (no requieren base de datos).
-- ============================================================

CREATE DATABASE IF NOT EXISTS venta_celulares
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE venta_celulares;

-- ------------------------------------------------------------
-- Catálogo de productos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS productos (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  nombre         VARCHAR(120)  NOT NULL,
  marca          VARCHAR(60)   NOT NULL,
  descripcion    VARCHAR(255)  DEFAULT '',
  ram            VARCHAR(20)   DEFAULT '',
  almacenamiento VARCHAR(20)   DEFAULT '',
  bateria        VARCHAR(20)   DEFAULT '',
  pantalla       VARCHAR(40)   DEFAULT '',
  precio         DECIMAL(12,2) NOT NULL DEFAULT 0,
  stock          INT           NOT NULL DEFAULT 0,
  -- Opcional: ruta o URL de la foto. Si queda NULL/vacío se dibuja
  -- una ilustración del celular con el color de la marca.
  imagen         VARCHAR(255)  DEFAULT NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Pedidos / órdenes de pago
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ordenes (
  id                  INT AUTO_INCREMENT PRIMARY KEY,
  reference_code      VARCHAR(80)   NOT NULL UNIQUE,
  producto_id         INT           NOT NULL,
  cantidad            INT           NOT NULL DEFAULT 1,
  valor               DECIMAL(12,2) NOT NULL DEFAULT 0,
  estado              VARCHAR(20)   NOT NULL DEFAULT 'PENDIENTE',
  comprador_email     VARCHAR(150)  DEFAULT '',
  payu_transaction_id VARCHAR(80)   DEFAULT NULL,
  created_at          TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  INDEX (estado),
  INDEX (producto_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Datos de ejemplo para el catálogo (opcional)
-- ------------------------------------------------------------
INSERT INTO productos (nombre, marca, descripcion, ram, almacenamiento, bateria, pantalla, precio, stock) VALUES
('Galaxy Signal X12', 'Samsung',  'Pantalla AMOLED 120Hz, cámara triple 108MP', '8GB', '256GB', '5000mAh', '6.7" AMOLED',       2190000, 14),
('Pulse P40 Lite',    'Xiaomi',   'Carga rápida 67W, cuerpo ultraliviano',       '6GB', '128GB', '4500mAh', '6.5" IPS',          1090000, 22),
('Orbit One 5G',      'Motorola', 'Conectividad 5G, resistente a salpicaduras',  '8GB', '256GB', '5000mAh', '6.6" LCD',          1650000,  9),
('Aria S Pro',        'Apple',    'Chip A-series, sistema de cámaras Pro',        '6GB', '256GB', '4325mAh', '6.1" Super Retina', 4890000,  5);
