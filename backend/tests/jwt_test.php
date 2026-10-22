<?php
// Self-check untuk logika custom yang tidak tercakup cek framework:
// JWT HS256 dan rate limiter. Jalankan: php tests/jwt_test.php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rate_limit.php';

$config = require __DIR__ . '/../config.php';

$fails = 0;
function check($label, $cond) {
    global $fails;
    if ($cond) {
        echo "PASS  {$label}\n";
    } else {
        $fails++;
        echo "FAIL  {$label}\n";
    }
}

// --- JWT ---
$token = jwt_encode(['id' => 42, 'email' => 'test@kantin.com', 'role' => 'mahasiswa']);
check('encode menghasilkan token', is_string($token) && substr_count($token, '.') === 2);

$decoded = jwt_decode($token);
check('decode sukses', is_array($decoded));
check('payload id', ($decoded['id'] ?? null) === 42);
check('payload email', ($decoded['email'] ?? null) === 'test@kantin.com');
check('payload role', ($decoded['role'] ?? null) === 'mahasiswa');
check('payload exp di masa depan', ($decoded['exp'] ?? 0) > time());

check('token rusak ditolak', jwt_decode($token . 'x') === null);
check('token ngawur ditolak', jwt_decode('abc.def.ghi') === null);

// token yang sudah kadaluarsa
$expired = jwt_encode(['id' => 1, 'role' => 'admin']);
$parts = explode('.', $expired);
$payload = json_decode(base64url_decode($parts[1]), true);
$payload['exp'] = time() - 10;
$parts[1] = base64url_encode(json_encode($payload));
$parts[2] = base64url_encode(hash_hmac('sha256', $parts[0] . '.' . $parts[1], $config['jwt_secret'], true));
check('token kadaluarsa ditolak', jwt_decode(implode('.', $parts)) === null);

// --- Rate limiter ---
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$bucket = 'selfcheck_' . uniqid();
check('permintaan 1 diizinkan', rate_limit_run($bucket, 2, 60, true) === true);
check('permintaan 2 diizinkan', rate_limit_run($bucket, 2, 60, true) === true);
check('permintaan 3 diblokir', rate_limit_run($bucket, 2, 60, true) === false);
check('cek tanpa increment tidak memakai kuota', rate_limit_run($bucket, 2, 60, false) === false);

// window kedaluwarsa -> counter reset
$file = rate_limit_dir() . '/' . sha1($bucket . '|127.0.0.1') . '.json';
$data = json_decode(file_get_contents($file), true);
$data['reset'] = time() - 1;
file_put_contents($file, json_encode($data));
check('window baru setelah reset', rate_limit_run($bucket, 2, 60, true) === true);

echo $fails === 0 ? "\nSemua cek lulus.\n" : "\n{$fails} cek gagal.\n";
exit($fails === 0 ? 0 : 1);
