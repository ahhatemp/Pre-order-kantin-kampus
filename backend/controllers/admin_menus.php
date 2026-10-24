<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';

function admin_menus_all() {
    $me = authenticate();
    require_role($me, 'admin');

    try {
        $pdo = get_db_connection();
        $menus = $pdo->query('SELECT * FROM menus ORDER BY nama ASC')->fetchAll();
        foreach ($menus as &$m) {
            $m['id'] = (int)$m['id'];
            $m['harga'] = (int)$m['harga'];
            $m['stok'] = (int)$m['stok'];
            $m['is_active'] = (int)$m['is_active'];
        }
        json_response($menus);
    } catch (PDOException $e) {
        json_error('Gagal mengambil daftar menu', 500);
    }
}

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

    // Validasi per field supaya harga/is_active tidak bisa jadi nilai rusak.
    foreach (['nama', 'deskripsi', 'kategori', 'harga', 'image_url', 'is_active'] as $key) {
        if (!array_key_exists($key, $body)) continue;
        $value = $body[$key];

        if ($key === 'nama') {
            if (!is_string($value) || trim($value) === '') json_error('Nama menu wajib diisi');
            if (strlen($value) > 100) json_error('Nama menu maksimal 100 karakter');
        } elseif ($key === 'deskripsi') {
            if ($value !== null && !is_string($value)) json_error('Deskripsi harus berupa teks');
        } elseif ($key === 'kategori') {
            if ($value !== null && (!is_string($value) || strlen($value) > 50)) json_error('Kategori maksimal 50 karakter');
        } elseif ($key === 'harga') {
            if (!is_int($value) && !is_float($value)) json_error('Harga harus berupa angka');
            if ($value <= 0) json_error('Harga harus lebih dari 0');
            if ($value > 1000000000) json_error('Harga terlalu besar');
        } elseif ($key === 'image_url') {
            if ($value !== null && (!is_string($value) || strlen($value) > 255)) json_error('URL gambar maksimal 255 karakter');
        } elseif ($key === 'is_active') {
            if (!is_bool($value) && $value !== 0 && $value !== 1) json_error('is_active harus boolean');
        }
    }

    $fields = [];
    $params = [];
    foreach (['nama', 'deskripsi', 'kategori', 'harga', 'image_url', 'is_active'] as $key) {
        if (array_key_exists($key, $body)) {
            $fields[] = "{$key} = ?";
            $value = $body[$key];
            if ($key === 'is_active') $value = (int)(bool)$value;
            if ($key === 'harga') $value = (int)$value;
            $params[] = $value;
        }
    }

    if (empty($fields)) json_error('Tidak ada field yang diperbarui');
    $params[] = $id;

    try {
        $pdo = get_db_connection();
        $exists = $pdo->prepare('SELECT id FROM menus WHERE id = ?');
        $exists->execute([$id]);
        if (!$exists->fetch()) json_error('Menu tidak ditemukan', 404);

        $sql = 'UPDATE menus SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_response(['message' => 'Menu berhasil diperbarui']);
    } catch (PDOException $e) {
        error_log('Update menu error: ' . $e->getMessage());
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
        $exists = $pdo->prepare('SELECT id FROM menus WHERE id = ?');
        $exists->execute([$id]);
        if (!$exists->fetch()) json_error('Menu tidak ditemukan', 404);

        $stmt = $pdo->prepare('UPDATE menus SET stok = ? WHERE id = ?');
        $stmt->execute([(int)$stok, $id]);
        json_response(['message' => 'Stok berhasil diperbarui']);
    } catch (PDOException $e) {
        json_error('Gagal memperbarui stok', 500);
    }
}
