-- Menu dihapus secara lunak agar riwayat order lama tetap menunjuk menu yang valid.
SET @has_deleted_at = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'menus' AND COLUMN_NAME = 'deleted_at'
);
SET @ddl = IF(@has_deleted_at = 0,
  'ALTER TABLE menus ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL AFTER is_active',
  'DO 0');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
