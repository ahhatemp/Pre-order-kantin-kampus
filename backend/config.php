<?php
// Konfigurasi tunggal. File ini bebas definisi fungsi supaya aman dipanggil
// berulang lewat require_once di mana pun.

// --- loader .env sederhana (tanpa dependensi) ---
$__envFile = __DIR__ . '/.env';
if (file_exists($__envFile)) {
    foreach ((array)@file($__envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $__line) {
        $__line = trim($__line);
        if ($__line === '' || $__line[0] === '#') continue;
        if (strpos($__line, '=') === false) continue;
        list($__name, $__value) = explode('=', $__line, 2);
        $__name = trim($__name);
        $__value = trim($__value, " \t\n\r\0\x0B\"'");
        if (!array_key_exists($__name, $_ENV) && !getenv($__name)) {
            putenv($__name . '=' . $__value);
            $_ENV[$__name] = $__value;
            $_SERVER[$__name] = $__value;
        }
    }
}
unset($__envFile, $__line, $__name, $__value);

// Samakan zona waktu PHP dengan MySQL lokal supaya jam di /api/health cocok
// dengan expires_at/pickup_at yang dihitung MySQL.
date_default_timezone_set('Asia/Jakarta');

return [
    'db_host' => getenv('DB_HOST') ?: '127.0.0.1',
    'db_port' => getenv('DB_PORT') ?: '3306',
    'db_user' => getenv('DB_USER') ?: 'root',
    'db_password' => getenv('DB_PASSWORD') ?: '',
    'db_name' => getenv('DB_NAME') ?: 'kantin',
    'jwt_secret' => getenv('JWT_SECRET') ?: 'default_jwt_secret',
    'midtrans_server_key' => getenv('MIDTRANS_SERVER_KEY') ?: '',
    'midtrans_client_key' => getenv('MIDTRANS_CLIENT_KEY') ?: '',
    'midtrans_is_production' => getenv('MIDTRANS_IS_PRODUCTION') === 'true',
    'rate_limit_order' => (int)(getenv('RATE_LIMIT_ORDER_MAX') ?: 20),
    'order_pending_limit' => (int)(getenv('ORDER_PENDING_MAX') ?: 3),
];
