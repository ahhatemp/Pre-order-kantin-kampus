<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../db.php';

function get_menus() {
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->query('SELECT id, nama, deskripsi, kategori, harga, stok, image_url FROM menus WHERE is_active = 1 AND deleted_at IS NULL ORDER BY nama ASC');
        $menus = $stmt->fetchAll();
        // harga & stok harus integer untuk konsistensi API
        foreach ($menus as &$m) {
            $m['id'] = (int)$m['id'];
            $m['harga'] = (int)$m['harga'];
            $m['stok'] = (int)$m['stok'];
        }
        json_response($menus);
    } catch (PDOException $e) {
        json_error('Gagal mengambil menu', 500);
    }
}
