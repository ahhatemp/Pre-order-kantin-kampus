CREATE TABLE IF NOT EXISTS kantin_profile (
  id TINYINT NOT NULL PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  lokasi VARCHAR(150) NOT NULL DEFAULT '',
  kontak VARCHAR(30) NOT NULL DEFAULT '',
  deskripsi VARCHAR(255) NOT NULL DEFAULT '',
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO kantin_profile (id, nama, lokasi, kontak, deskripsi)
VALUES (1, 'Kantin Kampus', 'Gedung A, Lantai 1', '0812-3456-7890', 'Masakan rumahan segar setiap hari.');
