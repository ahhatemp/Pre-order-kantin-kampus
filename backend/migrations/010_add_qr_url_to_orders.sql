-- QR QRIS dirender di halaman pesanan, jadi URL gambarnya ikut disimpan.
-- migrate.php mengeksekusi ulang semua file, jadi DDL dijaga idempoten.
SET @has_qr_url = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'qr_url'
);
SET @ddl = IF(@has_qr_url = 0,
  'ALTER TABLE orders ADD COLUMN qr_url VARCHAR(255) NULL AFTER paid_at',
  'DO 0');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
