<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../rate_limit.php';

function auth_register() {
    $config = require __DIR__ . '/../config.php';
    // Hanya gagal login (401) yang mengurangi kuota, error validasi tidak.
    rate_limit_check('auth', $config['rate_limit_auth'], 600, 'Terlalu banyak percobaan login. Tunggu 10 menit.');

    $body = get_json_body();
    $nim = $body['nim'] ?? null;
    $nama = $body['nama'] ?? null;
    $email = $body['email'] ?? null;
    $password = $body['password'] ?? null;

    if (!$nama || !$email || !$password) json_error('Nama, email, dan password wajib diisi');
    if (!is_string($email) || !preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $email)) json_error('Format email tidak valid');
    if (!is_string($password) || strlen($password) < 8) json_error('Password minimal 8 karakter');
    if (!is_string($nama) || strlen($nama) > 100) json_error('Nama tidak valid (maksimal 100 karakter)');
    if ($nim !== null && $nim !== '' && (!is_string($nim) || strlen($nim) > 20)) json_error('NIM tidak valid (maksimal 20 karakter)');

    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare('INSERT INTO users (nim, nama, email, password, role) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$nim ?: null, $nama, $email, password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]), 'mahasiswa']);
        json_response(['message' => 'Registrasi berhasil'], 201);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            if (stripos($e->getMessage(), 'email') !== false) json_error('Email sudah terdaftar', 409);
            if (stripos($e->getMessage(), 'nim') !== false) json_error('NIM sudah terdaftar', 409);
        }
        json_error('Gagal registrasi', 500);
    }
}

function auth_login() {
    $config = require __DIR__ . '/../config.php';
    rate_limit_check('auth', $config['rate_limit_auth'], 600, 'Terlalu banyak percobaan login. Tunggu 10 menit.');

    $body = get_json_body();
    $email = $body['email'] ?? null;
    $password = $body['password'] ?? null;

    if (!$email || !$password) json_error('Email dan password wajib diisi');

    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            // Perhitungannya di sini, bukan di awal: validasi 400 tidak mengunci akun.
            rate_limit_hit('auth', $config['rate_limit_auth'], 600);
            json_error('Email atau password salah', 401);
        }

        $token = jwt_encode([
            'id' => (int)$user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
        ]);

        json_response([
            'token' => $token,
            'user' => [
                'id' => (int)$user['id'],
                'nim' => $user['nim'],
                'nama' => $user['nama'],
                'email' => $user['email'],
                'role' => $user['role'],
            ],
        ]);
    } catch (PDOException $e) {
        json_error('Gagal login', 500);
    }
}

function auth_me() {
    $me = authenticate();
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare('SELECT id, nim, nama, email, role FROM users WHERE id = ?');
        $stmt->execute([$me['id']]);
        $user = $stmt->fetch();
        if (!$user) json_error('User tidak ditemukan', 404);
        json_response($user);
    } catch (PDOException $e) {
        json_error('Gagal mengambil data user', 500);
    }
}
