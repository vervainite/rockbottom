-- RockBottom — Cute Things From The Depths
-- Clean import for MySQL 8.0+ / MariaDB 10.6+
-- Import this file with phpMyAdmin or: mysql -u root < rockbottom.sql

SET NAMES utf8mb4;
SET time_zone = '+08:00';

CREATE DATABASE IF NOT EXISTS rockbottom
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE rockbottom;

SET FOREIGN_KEY_CHECKS = 0;
DROP TRIGGER IF EXISTS rb_products_active_limit_ins;
DROP TRIGGER IF EXISTS rb_products_active_limit_upd;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS cart;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  phone VARCHAR(30) NULL,
  address VARCHAR(500) NULL,
  avatar VARCHAR(255) NULL,
  cover VARCHAR(255) NULL,
  bio VARCHAR(200) NULL,
  card_style VARCHAR(20) NOT NULL DEFAULT 'reef',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(90) NOT NULL,
  description VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_categories_name (name),
  UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB;

CREATE TABLE products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NULL,
  name VARCHAR(140) NOT NULL,
  slug VARCHAR(160) NOT NULL,
  description TEXT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  stock INT UNSIGNED NOT NULL DEFAULT 0,
  image VARCHAR(190) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_products_slug (slug),
  KEY idx_products_active_created (active, created_at),
  KEY idx_products_category (category_id),
  CONSTRAINT fk_products_category FOREIGN KEY (category_id)
    REFERENCES categories(id) ON DELETE SET NULL,
  CONSTRAINT chk_products_price CHECK (price >= 0),
  CONSTRAINT chk_products_stock CHECK (stock >= 0)
) ENGINE=InnoDB;

CREATE TABLE cart (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cart_user (user_id),
  CONSTRAINT fk_cart_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cart_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cart_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cart_items (cart_id, product_id),
  KEY idx_cart_items_product (product_id),
  CONSTRAINT fk_cart_items_cart FOREIGN KEY (cart_id)
    REFERENCES cart(id) ON DELETE CASCADE,
  CONSTRAINT fk_cart_items_product FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT chk_cart_items_qty CHECK (quantity > 0)
) ENGINE=InnoDB;

CREATE TABLE orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  shipping_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(10,2) NOT NULL,
  status ENUM('Pending','Processing','Shipped','Delivered','Cancelled')
    NOT NULL DEFAULT 'Pending',
  payment_method VARCHAR(40) NOT NULL DEFAULT 'Cash on Delivery',
  shipping_address VARCHAR(500) NOT NULL,
  contact_phone VARCHAR(30) NOT NULL,
  notes VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_orders_user_created (user_id, created_at),
  KEY idx_orders_status_created (status, created_at),
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NULL,
  product_name VARCHAR(140) NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  quantity INT UNSIGNED NOT NULL,
  line_total DECIMAL(10,2) NOT NULL,
  KEY idx_order_items_order (order_id),
  KEY idx_order_items_product (product_id),
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id)
    REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_order_items_product FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE SET NULL,
  CONSTRAINT chk_order_items_qty CHECK (quantity > 0)
) ENGINE=InnoDB;

CREATE TABLE payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  method VARCHAR(40) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  status ENUM('Pending','Paid','Cancelled','Refunded') NOT NULL DEFAULT 'Pending',
  reference VARCHAR(80) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_payments_order (order_id),
  CONSTRAINT fk_payments_order FOREIGN KEY (order_id)
    REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE contact_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('new','read') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_contact_status_created (status, created_at)
) ENGINE=InnoDB;

INSERT INTO categories (id, name, slug, description) VALUES
  (1, 'Figures', 'figures', 'Character collectibles from the world below.'),
  (2, 'Blind Boxes', 'blind-boxes', 'A surprise in every sealed box.'),
  (3, 'Keychains', 'keychains', 'Pocket-sized companions for the road.');

INSERT INTO products
  (category_id, name, slug, description, price, stock, image, active, created_at)
VALUES
  (1, 'The Lost Prince', 'the-lost-prince',
   'A boy with a forgotten crown and a bigger dream.',
   1299.00, 8, 'moonlight-bunny.png', 1, '2026-10-10 09:00:00'),
  (1, 'Street Dreamer', 'street-dreamer',
   'Skates through life, chasing his own dreams.',
   1199.00, 7, 'mystery-box.png', 1, '2026-10-09 09:00:00'),
  (1, 'Night Walker', 'night-walker',
   'Moves in silence, finds his own light.',
   1349.00, 6, 'shadow-cat.png', 1, '2026-10-08 09:00:00'),
  (1, 'Mechanic Boy', 'mechanic-boy',
   'Fixes what''s broken, even if it''s just his own heart.',
   1249.00, 8, 'little-ghost.png', 1, '2026-10-07 09:00:00'),
  (1, 'Lifeline', 'lifeline',
   'Still standing, that''s enough.',
   1099.00, 9, 'forest-sprite.png', 1, '2026-10-06 09:00:00'),
  (1, 'Gentleman', 'gentleman',
   'Polite, much more dangerous than he looks.',
   1299.00, 7, 'candy-monster.png', 1, '2026-10-05 09:00:00'),
  (1, 'Find Your Way', 'find-your-way',
   'Not lost, just exploring new paths.',
   1199.00, 6, 'dreamer-girl.png', 1, '2026-10-04 09:00:00'),
  (1, 'On a Mission', 'on-a-mission',
   'Focused. Silent. Always on a mission.',
   1349.00, 5, 'starry-bear.png', 1, '2026-10-03 09:00:00'),
  (1, 'Cowboy', 'cowboy',
   'Rides the sunset, not the rules.',
   1249.00, 7, 'lucky-rabbit.png', 1, '2026-10-02 09:00:00'),
  (1, 'Vagabond', 'vagabond',
   'No home, no schedule, just the next horizon.',
   1149.00, 8, 'midnight-box.png', 1, '2026-10-01 09:00:00');

-- Database-level guard: the storefront can never expose more than 10 active products,
-- even if an application check is bypassed.
DELIMITER $$
CREATE TRIGGER rb_products_active_limit_ins
BEFORE INSERT ON products
FOR EACH ROW
BEGIN
  IF NEW.active = 1 AND (SELECT COUNT(*) FROM products WHERE active = 1) >= 10 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Active product limit of 10 reached';
  END IF;
END$$

CREATE TRIGGER rb_products_active_limit_upd
BEFORE UPDATE ON products
FOR EACH ROW
BEGIN
  IF NEW.active = 1 AND OLD.active = 0
     AND (SELECT COUNT(*) FROM products WHERE active = 1) >= 10 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Active product limit of 10 reached';
  END IF;
END$$
DELIMITER ;
