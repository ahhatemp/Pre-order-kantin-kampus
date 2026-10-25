-- ============================================================
--  Kantin Kampus - Pre-Order
--  Skema MySQL + data awal
--  Import: mysql -u root < schema.sql
--          atau paste ke phpMyAdmin > SQL
-- ============================================================

CREATE DATABASE IF NOT EXISTS kantin
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE kantin;

-- ---------- users ----------
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nim VARCHAR(20) UNIQUE NULL,
  nama VARCHAR(100) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('mahasiswa', 'admin') NOT NULL DEFAULT 'mahasiswa',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- menus ----------
CREATE TABLE IF NOT EXISTS menus (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  deskripsi TEXT NULL,
  kategori VARCHAR(50) NULL,
  harga INT NOT NULL,
  stok INT NOT NULL DEFAULT 0,
  image_url VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  deleted_at DATETIME NULL DEFAULT NULL,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- orders ----------
CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_code VARCHAR(20) UNIQUE NOT NULL,
  user_id INT NOT NULL,
  total_price INT NOT NULL,
  pickup_at DATETIME NOT NULL,
  catatan VARCHAR(255) NULL,
  status ENUM('pending', 'paid', 'ready', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  payment_token VARCHAR(255) NULL,
  expires_at DATETIME NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  INDEX idx_status (status),
  INDEX idx_pickup_at (pickup_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- order_items ----------
CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  menu_id INT NOT NULL,
  quantity INT NOT NULL,
  price_at_order INT NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (menu_id) REFERENCES menus(id),
  INDEX idx_order_id (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- push_subscriptions ----------
CREATE TABLE IF NOT EXISTS push_subscriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  endpoint TEXT NOT NULL,
  endpoint_hash CHAR(64) NOT NULL,
  p256dh VARCHAR(255) NOT NULL DEFAULT '',
  auth VARCHAR(255) NOT NULL DEFAULT '',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_endpoint_hash (endpoint_hash),
  INDEX idx_push_user (user_id),
  CONSTRAINT fk_push_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- kantin_profile ----------
CREATE TABLE IF NOT EXISTS kantin_profile (
  id TINYINT NOT NULL PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  lokasi VARCHAR(150) NOT NULL DEFAULT '',
  kontak VARCHAR(30) NOT NULL DEFAULT '',
  deskripsi VARCHAR(255) NOT NULL DEFAULT '',
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- kantin_hours ----------
CREATE TABLE IF NOT EXISTS kantin_hours (
  day_of_week TINYINT NOT NULL PRIMARY KEY,
  is_open TINYINT(1) NOT NULL DEFAULT 1,
  open_time TIME NOT NULL DEFAULT '07:00:00',
  close_time TIME NOT NULL DEFAULT '16:00:00',
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- data awal ----------
-- Password semua akun awal: password123
INSERT INTO users (nim, nama, email, password, role)
VALUES
('11111111', 'Admin Kantin', 'admin@kantin.com', '$2y$10$6H5YKVI8OGctcwyH8Uv5BeC/BtYVSTBkH6vVSWNlaOSU/sbZQIwfq', 'admin'),
('22222222', 'Mahasiswa Test', 'user@kantin.com', '$2y$10$6H5YKVI8OGctcwyH8Uv5BeC/BtYVSTBkH6vVSWNlaOSU/sbZQIwfq', 'mahasiswa');

INSERT IGNORE INTO kantin_profile (id, nama, lokasi, kontak, deskripsi)
VALUES (1, 'Kantin Kampus', 'Gedung A, Lantai 1', '0812-3456-7890', 'Masakan rumahan segar setiap hari.');

INSERT IGNORE INTO kantin_hours (day_of_week, is_open, open_time, close_time) VALUES
(1, 1, '07:00:00', '16:00:00'),
(2, 1, '07:00:00', '16:00:00'),
(3, 1, '07:00:00', '16:00:00'),
(4, 1, '07:00:00', '16:00:00'),
(5, 1, '07:00:00', '15:00:00'),
(6, 0, '08:00:00', '13:00:00'),
(7, 0, '07:00:00', '16:00:00');

INSERT INTO menus (nama, kategori, harga, stok, deskripsi, image_url, is_active)
VALUES
('Nasi Goreng Spesial', 'Makanan Utama', 25000, 50, 'Nasi goreng dengan telur dan ayam suwir', 'nasi_goreng.jpg', 1),
('Mie Ayam Original', 'Makanan Utama', 20000, 40, 'Mie ayam dengan kuah gurih', 'mie_ayam.jpg', 1),
('Es Teh Manis', 'Minuman', 5000, 100, 'Es teh manis segar', 'es_teh.jpg', 1),
('Ayam Bakar', 'Makanan Utama', 30000, 25, 'Ayam bakar bumbu kecap', 'ayam_bakar.jpg', 1),
('Es Jeruk', 'Minuman', 8000, 60, 'Es jeruk manis segar', 'es_jeruk.jpg', 1);
