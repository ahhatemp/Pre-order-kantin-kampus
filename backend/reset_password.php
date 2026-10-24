<?php
// Reset password akun (development). Jalankan: php reset_password.php <email> <password>
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Hanya bisa dijalankan lewat CLI.\n");
}

require_once __DIR__ . '/db.php';

$email = $argv[1] ?? null;
$password = $argv[2] ?? null;

if (!$email || !$password) {
    fwrite(STDERR, "Pakai: php reset_password.php <email> <password>\n");
    exit(1);
}
if (strlen($password) < 8) {
    fwrite(STDERR, "Password minimal 8 karakter.\n");
    exit(1);
}

try {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE email = ?');
    $stmt->execute([password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]), $email]);
    if ($stmt->rowCount() === 0) {
        fwrite(STDERR, "User dengan email {$email} tidak ditemukan.\n");
        exit(1);
    }
    echo "Password {$email} berhasil direset.\n";
} catch (PDOException $e) {
    fwrite(STDERR, 'Gagal reset password: ' . $e->getMessage() . "\n");
    exit(1);
}
