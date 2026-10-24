<?php
// File-based sliding-window rate limiter (pengganti express-rate-limit).
// ponytail: window disimpan per file, batas ~ribuan request/detik; kalau perlu
// skala lebih besar pindahkan ke tabel MySQL.
require_once __DIR__ . '/helpers.php';

function rate_limit_dir() {
    $dir = __DIR__ . '/storage/ratelimit';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    return $dir;
}

function client_ip() {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// Mutasi counter pada file lock-nya. return: true kalau masih boleh request.
function rate_limit_run($bucket, $max, $windowSeconds, $increment) {
    $file = rate_limit_dir() . '/' . sha1($bucket . '|' . client_ip()) . '.json';
    $fp = @fopen($file, 'c+');
    if (!$fp) return true; // storage bermasalah: jangan blokir user

    flock($fp, LOCK_EX);
    $now = time();
    $raw = stream_get_contents($fp);
    $data = $raw ? json_decode($raw, true) : null;
    if (!is_array($data) || ($data['reset'] ?? 0) <= $now) {
        $data = ['reset' => $now + $windowSeconds, 'count' => 0];
    }

    $allowed = $data['count'] < $max;
    if ($increment) $data['count']++;

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data));
    flock($fp, LOCK_UN);
    fclose($fp);

    return $allowed;
}

// Hitung sekarang + tandai terpakai.
function rate_limit($bucket, $max, $windowSeconds, $message) {
    if (!rate_limit_run($bucket, $max, $windowSeconds, true)) {
        json_error($message, 429);
    }
}

