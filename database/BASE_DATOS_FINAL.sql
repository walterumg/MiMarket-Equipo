DROP DATABASE IF EXISTS mimarket;
CREATE DATABASE mimarket CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mimarket;

CREATE TABLE users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 email VARCHAR(180) NOT NULL UNIQUE,
 password VARCHAR(255) NOT NULL,
 role ENUM('cliente','operador','admin') NOT NULL DEFAULT 'cliente',
 status ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL UNIQUE,
 status ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE products (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 sku VARCHAR(50) NOT NULL UNIQUE,
 brand VARCHAR(100) NOT NULL DEFAULT '',
 name VARCHAR(160) NOT NULL,
 description TEXT NULL,
 price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
 sale_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  unit_type VARCHAR(20) NOT NULL DEFAULT 'unidad',
 stock INT NOT NULL DEFAULT 0,
 min_stock INT NOT NULL DEFAULT 3,
 category_id INT UNSIGNED NOT NULL,
 image VARCHAR(255) NULL,
 status ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_products_category FOREIGN KEY(category_id) REFERENCES categories(id) ON UPDATE CASCADE ON DELETE RESTRICT,
 CONSTRAINT chk_product_price CHECK(price >= 0),
 CONSTRAINT chk_product_sale CHECK(sale_price >= 0),
 CONSTRAINT chk_product_stock CHECK(stock >= 0),
 CONSTRAINT chk_product_min_stock CHECK(min_stock >= 0)
) ENGINE=InnoDB;

CREATE TABLE orders (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 fullname VARCHAR(160) NOT NULL,
 email VARCHAR(180) NOT NULL,
 address VARCHAR(255) NOT NULL,
 subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
 shipping_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
 total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
 status ENUM('PEDIDO','PREPARACION','LISTO','EN_CAMINO','ENTREGADO','CANCELADO') NOT NULL DEFAULT 'PEDIDO',
 payment_method VARCHAR(40) NOT NULL DEFAULT 'TARJETA',
 payment_status ENUM('PENDIENTE','PAGADO','RECHAZADO','REEMBOLSADO') NOT NULL DEFAULT 'PENDIENTE',
 payment_reference VARCHAR(80) NULL,
 card_brand VARCHAR(30) NULL,
 card_last4 CHAR(4) NULL,
 shipping_method ENUM('DOMICILIO','RETIRO') NOT NULL DEFAULT 'DOMICILIO',
 notes VARCHAR(255) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_orders_user FOREIGN KEY(user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE order_items (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id INT UNSIGNED NOT NULL,
 product_id INT UNSIGNED NOT NULL,
 quantity INT NOT NULL,
 unit_price DECIMAL(10,2) NOT NULL,
 subtotal DECIMAL(10,2) NOT NULL,
 CONSTRAINT fk_items_order FOREIGN KEY(order_id) REFERENCES orders(id) ON UPDATE CASCADE ON DELETE CASCADE,
 CONSTRAINT fk_items_product FOREIGN KEY(product_id) REFERENCES products(id) ON UPDATE CASCADE ON DELETE RESTRICT,
 CONSTRAINT chk_item_qty CHECK(quantity > 0)
) ENGINE=InnoDB;

CREATE TABLE inventory_movements (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 product_id INT UNSIGNED NOT NULL,
 quantity INT NOT NULL,
 type ENUM('ENTRADA','SALIDA','VENTA','AJUSTE') NOT NULL,
 reason VARCHAR(255) NULL,
 user_id INT UNSIGNED NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_inventory_product FOREIGN KEY(product_id) REFERENCES products(id) ON UPDATE CASCADE ON DELETE CASCADE,
 CONSTRAINT fk_inventory_user FOREIGN KEY(user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE order_status_history (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id INT UNSIGNED NOT NULL,
 status ENUM('PEDIDO','PREPARACION','LISTO','EN_CAMINO','ENTREGADO','CANCELADO') NOT NULL,
 changed_by INT UNSIGNED NULL,
 notes VARCHAR(255) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_history_order FOREIGN KEY(order_id) REFERENCES orders(id) ON UPDATE CASCADE ON DELETE CASCADE,
 CONSTRAINT fk_history_user FOREIGN KEY(changed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;


CREATE TABLE password_resets (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL, token_hash CHAR(64) NOT NULL, expires_at DATETIME NOT NULL, used_at DATETIME NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_reset_lookup(user_id,token_hash,expires_at), CONSTRAINT fk_reset_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO categories(name) VALUES
('Tecnología'),('Computación'),('Celulares'),('Audio y video'),('Accesorios'),('Hogar'),('Gaming'),('Ofertas');

INSERT INTO products(sku,brand,name,description,price,sale_price,stock,min_stock,category_id,image,status) VALUES
('MM-ROUT-001','TP-Link','Router Wi-Fi','Router inalámbrico para hogar y oficina con cobertura estable.',349.00,299.00,12,3,1,'uploads/router.jpg','activo'),
('MM-MOU-001','Logitech','Mouse inalámbrico','Mouse cómodo para computadora y trabajo diario.',129.00,0.00,20,4,5,'uploads/Mou.jpg','activo'),
('MM-TAB-001','Samsung','Tablet Galaxy','Tablet para productividad, estudio y entretenimiento.',3299.00,2999.00,7,2,1,'uploads/SMX920NZA_4_14102024123238.webp','activo'),
('MM-AUD-001','JBL','Audífonos Bluetooth','Audífonos inalámbricos con sonido estéreo.',599.00,549.00,10,3,4,'uploads/descarga.jpg','activo'),
('MM-TEC-001','Redragon','Teclado mecánico','Teclado mecánico para trabajo y gaming.',499.00,449.00,9,3,7,'uploads/descarga (1).jpg','activo'),
('MM-USB-001','Kingston','Memoria USB 64 GB','Unidad USB de 64 GB para archivos y respaldos.',99.00,79.00,30,5,5,'uploads/images.jpg','activo');

INSERT INTO inventory_movements(product_id,quantity,type,reason,user_id)
SELECT id,stock,'ENTRADA','Inventario inicial',NULL FROM products;


-- Índices de seguridad/rendimiento consolidados de V5/V6/V7
CREATE INDEX idx_products_status_category ON products(status,category_id);
CREATE INDEX idx_products_unit_type ON products(unit_type);
CREATE INDEX idx_orders_user_status_created ON orders(user_id,status,created_at);
CREATE INDEX idx_orders_status_created ON orders(status,created_at);
CREATE INDEX idx_inventory_product_created ON inventory_movements(product_id,created_at);

SELECT 'MiMarket Definitivo creada correctamente' AS resultado;
SHOW TABLES;
