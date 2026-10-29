<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../db.php';

function get_menus() {
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->query('SELECT id, nama, deskripsi, kategori, harga, stok, image_url FROM menus WHERE is_active = 1 AND deleted_at IS NULL ORDER BY nama ASC');
        json_response(array_map(fn($m) => cast_ints($m, ['id', 'harga', 'stok']), $stmt->fetchAll()));
    } catch (PDOException $e) {
        json_error('Gagal mengambil menu', 500);
    }
}
