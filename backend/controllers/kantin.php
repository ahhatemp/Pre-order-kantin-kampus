<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../services/kantin_hours.php';

function kantin_profile_get() {
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->query('SELECT nama, lokasi, kontak, deskripsi, updated_at FROM kantin_profile WHERE id = 1');
        $profile = $stmt->fetch();
        if (!$profile) json_error('Profil kantin belum diatur', 404);
        json_response($profile);
    } catch (PDOException $e) {
        json_error('Gagal mengambil profil kantin', 500);
    }
}

function admin_kantin_profile_update() {
    $me = authenticate();
    require_role($me, 'admin');

    $body = get_json_body();
    $rules = [
        'nama' => ['label' => 'Nama kantin', 'max' => 100, 'required' => true],
        'lokasi' => ['label' => 'Lokasi', 'max' => 150, 'required' => false],
        'kontak' => ['label' => 'Kontak', 'max' => 30, 'required' => false],
        'deskripsi' => ['label' => 'Deskripsi', 'max' => 255, 'required' => false],
    ];

    $fields = [];
    $params = [];
    foreach ($rules as $key => $rule) {
        if (!array_key_exists($key, $body)) continue;

        $value = $body[$key];
        if (!is_string($value)) json_error("{$rule['label']} harus berupa teks");
        $value = trim($value);
        if ($rule['required'] && $value === '') json_error("{$rule['label']} wajib diisi");
        if (strlen($value) > $rule['max']) json_error("{$rule['label']} maksimal {$rule['max']} karakter");

        $fields[] = "{$key} = ?";
        $params[] = $value;
    }

    if (empty($fields)) json_error('Tidak ada field yang diperbarui');
    $params[] = 1;

    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare('UPDATE kantin_profile SET ' . implode(', ', $fields) . ' WHERE id = ?');
        $stmt->execute($params);

        $stmt = $pdo->query('SELECT nama, lokasi, kontak, deskripsi, updated_at FROM kantin_profile WHERE id = 1');
        $profile = $stmt->fetch();
        if (!$profile) json_error('Profil kantin belum diatur', 404);
        json_response($profile);
    } catch (PDOException $e) {
        json_error('Gagal menyimpan profil kantin', 500);
    }
}

function kantin_hours_get() {
    try {
        $pdo = get_db_connection();
        json_response(['jam' => kantin_hours_fetch($pdo)]);
    } catch (PDOException $e) {
        json_error('Gagal mengambil jam operasional', 500);
    }
}

function admin_kantin_hours_update() {
    $me = authenticate();
    require_role($me, 'admin');

    $body = get_json_body();
    $input = $body['jam'] ?? null;
    if (!is_array($input) || count($input) !== 7) json_error('Jam operasional harus berisi 7 hari');

    $days = [];
    foreach ($input as $row) {
        if (!is_array($row)) json_error('Format jam operasional tidak valid');

        $day = $row['day_of_week'] ?? null;
        if (!is_int($day) || $day < 1 || $day > 7) json_error('Hari tidak valid');
        if (isset($days[$day])) json_error('Hari tidak boleh duplikat');

        $isOpen = $row['is_open'] ?? null;
        if (!is_bool($isOpen)) json_error('Status buka harus berupa boolean');

        $openTime = $row['open_time'] ?? null;
        $closeTime = $row['close_time'] ?? null;
        foreach (['Jam buka' => $openTime, 'Jam tutup' => $closeTime] as $label => $value) {
            if (!is_string($value) || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value)) {
                json_error("{$label} harus berformat HH:MM");
            }
        }
        if ($isOpen && $openTime >= $closeTime) json_error('Jam tutup harus setelah jam buka');

        $days[$day] = [
            'is_open' => $isOpen ? 1 : 0,
            'open_time' => $openTime . ':00',
            'close_time' => $closeTime . ':00',
        ];
    }
    if (count($days) !== 7) json_error('Jam operasional harus berisi 7 hari');

    $pdo = null;
    try {
        $pdo = get_db_connection();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('UPDATE kantin_hours SET is_open = ?, open_time = ?, close_time = ? WHERE day_of_week = ?');
        foreach ($days as $day => $row) {
            $stmt->execute([$row['is_open'], $row['open_time'], $row['close_time'], $day]);
        }
        $pdo->commit();
        json_response(['jam' => kantin_hours_fetch($pdo)]);
    } catch (PDOException $e) {
        if ($pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
        json_error('Gagal menyimpan jam operasional', 500);
    }
}
