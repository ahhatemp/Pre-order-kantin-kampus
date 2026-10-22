<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';

function admin_menu_create() {
    $me = authenticate();
    require_role($me, 'admin');

    $body = get_json_body();
    $nama = $body['nama'] ?? null;
    $deskripsi = $body['deskripsi'] ?? null;
    $kategori = $body['kategori'] ?? null;
    $harga = $body['harga'] ?? null;
    // stok opsional, default 0; tapi kalau dikirim harus angka
    $stok = $body['stok'] ?? null;
    $imageUrl = $body['image_url'] ?? null;

    if (!$nama || $harga === null) json_error('Nama dan harga wajib diisi');
    if (!is_int($harga) && !is_float($harga)) json_error('Harga harus berupa angka positif');
    if ($harga <= 0) json_error('Harga harus berupa angka positif');
    if ($stok !== null && (!is_int($stok) && !is_float($stok))) json_error('Stok harus berupa angka dan tidak boleh negatif');
    if ($stok !== null && $stok < 0) json_error('Stok harus berupa angka dan tidak boleh negatif');

    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare(
            'INSERT INTO menus (nama, deskripsi, kategori, harga, stok, image_url) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$nama, $deskripsi ?: null, $kategori ?: null, (int)$harga, (int)($stok ?? 0), $imageUrl ?: null]);
        json_response(['id' => (int)$pdo->lastInsertId()], 201);
    } catch (PDOException $e) {
        json_error('Gagal membuat menu', 500);
    }
}

function admin_menu_detail($id) {
    $me = authenticate();
    require_role($me, 'admin');

    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare('SELECT * FROM menus WHERE id = ?');
        $stmt->execute([$id]);
        $menu = $stmt->fetch();
        if (!$menu) json_error('Menu tidak ditemukan', 404);

        $menu['id'] = (int)$menu['id'];
        $menu['harga'] = (int)$menu['harga'];
        $menu['stok'] = (int)$menu['stok'];
        json_response($menu);
    } catch (PDOException $e) {
        json_error('Gagal mengambil detail menu', 500);
    }
}

function admin_menu_update($id) {
    $me = authenticate();
    require_role($me, 'admin');

    $body = get_json_body();
    $fields = [];
    $params = [];

    foreach (['nama', 'deskripsi', 'kategori', 'harga'] as $key) {
        if (array_key_exists($key, $body)) {
            $fields[] = "{$key} = ?";
            $params[] = $body[$key];
        }
    }

    if (empty($fields)) json_error('Tidak ada field yang diperbarui');
    $params[] = $id;

    try {
        $pdo = get_db_connection();
        $sql = 'UPDATE menus SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_response(['message' => 'Menu berhasil diperbarui']);
    } catch (PDOException $e) {
        json_error('Gagal memperbarui menu', 500);
    }
}

function admin_menu_stock($id) {
    $me = authenticate();
    require_role($me, 'admin');

    $body = get_json_body();
    $stok = $body['stok'] ?? null;

    if (!is_int($stok) && !is_float($stok)) json_error('Stok harus berupa angka');
    if ($stok < 0) json_error('Stok tidak boleh negatif');

    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare('UPDATE menus SET stok = ? WHERE id = ?');
        $stmt->execute([(int)$stok, $id]);
        json_response(['message' => 'Stok berhasil diperbarui']);
    } catch (PDOException $e) {
        json_error('Gagal memperbarui stok', 500);
    }
}
