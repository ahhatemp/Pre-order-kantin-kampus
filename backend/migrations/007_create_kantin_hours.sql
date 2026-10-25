-- Jam operasional kantin, satu baris per hari (1 = Senin ... 7 = Minggu).
CREATE TABLE IF NOT EXISTS kantin_hours (
  day_of_week TINYINT NOT NULL PRIMARY KEY,
  is_open TINYINT(1) NOT NULL DEFAULT 1,
  open_time TIME NOT NULL DEFAULT '07:00:00',
  close_time TIME NOT NULL DEFAULT '16:00:00',
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO kantin_hours (day_of_week, is_open, open_time, close_time) VALUES
(1, 1, '07:00:00', '16:00:00'),
(2, 1, '07:00:00', '16:00:00'),
(3, 1, '07:00:00', '16:00:00'),
(4, 1, '07:00:00', '16:00:00'),
(5, 1, '07:00:00', '15:00:00'),
(6, 0, '08:00:00', '13:00:00'),
(7, 0, '07:00:00', '16:00:00');
