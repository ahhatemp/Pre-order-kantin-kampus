<?php
// Jalankan: php migrate.php
// Membuat database (kalau belum ada) lalu mengeksekusi semua file di migrations/.
// File seed dilewati bila tabel users sudah berisi data, supaya aman diulang.

$config = require __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['db_host']};port={$config['db_port']};charset=utf8mb4",
        $config['db_user'],
        $config['db_password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
    );

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$config['db_name']}`");

    $files = glob(__DIR__ . '/migrations/*.sql');
    sort($files);

    foreach ($files as $file) {
        $name = basename($file);

        if (strpos($name, 'seed') !== false) {
            $hasUser = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
            if ((int)$hasUser > 0) {
                echo "Skip {$name} (users sudah berisi data)\n";
                continue;
            }
        }

        echo "Running {$name}\n";
        // ponytail: split sederhana di titik koma, tanpa parser string.
        // Cukup selama file SQL tidak punya ';' di dalam nilai string.
        foreach (explode(';', file_get_contents($file)) as $statement) {
            if (trim($statement) !== '') $pdo->exec($statement);
        }
    }

    echo "Migrasi selesai.\n";
} catch (PDOException $e) {
    fwrite(STDERR, 'Migrasi gagal: ' . $e->getMessage() . "\n");
    exit(1);
}
