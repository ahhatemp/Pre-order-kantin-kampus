-- Detail transaksi Midtrans disimpan terpisah dari alur status kantin.
-- migrate.php mengeksekusi ulang semua file, jadi DDL dijaga idempoten.
SET @has_payment_type = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'payment_type'
);
SET @ddl = IF(@has_payment_type = 0,
  'ALTER TABLE orders
     ADD COLUMN payment_type VARCHAR(30) NULL AFTER payment_token,
     ADD COLUMN payment_channel VARCHAR(40) NULL AFTER payment_type,
     ADD COLUMN transaction_id VARCHAR(64) NULL AFTER payment_channel,
     ADD COLUMN payment_status VARCHAR(20) NULL AFTER transaction_id,
     ADD COLUMN fraud_status VARCHAR(20) NULL AFTER payment_status,
     ADD COLUMN paid_at DATETIME NULL AFTER fraud_status',
  'DO 0');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
