-- Password semua akun awal: password123
INSERT INTO users (nim, nama, email, password, role)
VALUES 
('11111111', 'Admin Kantin', 'admin@kantin.com', '$2y$10$6H5YKVI8OGctcwyH8Uv5BeC/BtYVSTBkH6vVSWNlaOSU/sbZQIwfq', 'admin'),
('22222222', 'Mahasiswa Test', 'user@kantin.com', '$2y$10$6H5YKVI8OGctcwyH8Uv5BeC/BtYVSTBkH6vVSWNlaOSU/sbZQIwfq', 'mahasiswa');

INSERT INTO menus (nama, kategori, harga, stok, deskripsi, image_url, is_active)
VALUES 
('Nasi Goreng Spesial', 'Makanan Utama', 25000, 50, 'Nasi goreng dengan telur dan ayam suwir', 'nasi_goreng.jpg', 1),
('Mie Ayam Original', 'Makanan Utama', 20000, 40, 'Mie ayam dengan kuah gurih', 'mie_ayam.jpg', 1),
('Es Teh Manis', 'Minuman', 5000, 100, 'Es teh manis segar', 'es_teh.jpg', 1),
('Ayam Bakar', 'Makanan Utama', 30000, 25, 'Ayam bakar bumbu kecap', 'ayam_bakar.jpg', 1),
('Es Jeruk', 'Minuman', 8000, 60, 'Es jeruk manis segar', 'es_jeruk.jpg', 1);
