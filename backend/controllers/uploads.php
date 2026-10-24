<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../auth.php';

const MENU_UPLOAD_MAX_BYTES = 5 * 1024 * 1024;
const MENU_UPLOAD_DIR = __DIR__ . '/../storage/uploads';

function admin_menu_upload() {
    $me = authenticate();
    require_role($me, 'admin');

    if (empty($_FILES) && empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        json_error('Ukuran gambar melebihi batas upload server', 413);
    }

    $file = $_FILES['image'] ?? null;
    if (!$file) json_error('File gambar wajib diunggah');

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            json_error('Pilih file gambar terlebih dahulu');
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            json_error('Ukuran gambar terlalu besar (maksimal 5 MB)', 413);
        default:
            json_error('Gambar gagal diunggah', 500);
    }

    if ($file['size'] <= 0 || $file['size'] > MENU_UPLOAD_MAX_BYTES) {
        json_error('Ukuran gambar maksimal 5 MB', 413);
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        json_error('Format gambar harus JPG, PNG, atau WebP');
    }
    if (@getimagesize($file['tmp_name']) === false) {
        json_error('File bukan gambar yang valid');
    }

    if (!is_dir(MENU_UPLOAD_DIR) && !mkdir(MENU_UPLOAD_DIR, 0755, true) && !is_dir(MENU_UPLOAD_DIR)) {
        json_error('Gagal menyiapkan folder upload', 500);
    }

    $name = 'menu_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], MENU_UPLOAD_DIR . '/' . $name)) {
        json_error('Gagal menyimpan gambar', 500);
    }

    json_response(['image_url' => 'uploads/' . $name], 201);
}

function upload_serve($filename) {
    if (!preg_match('/^[A-Za-z0-9_-]+\.(jpe?g|png|webp)$/i', $filename)) {
        json_error('Gambar tidak ditemukan', 404);
    }

    $path = MENU_UPLOAD_DIR . '/' . $filename;
    if (!is_file($path)) json_error('Gambar tidak ditemukan', 404);

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: public, max-age=31536000, immutable');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}
