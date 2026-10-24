<?php
$config = require __DIR__ . '/config.php';
try {
    $pdo = new PDO(
        "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'],
        $config['db_password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
    );
    echo "OK koneksi ke {$config['db_name']}\n";
    foreach (['users', 'menus', 'orders', 'order_items'] as $t) {
        $n = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo "$t: $n baris\n";
    }
} catch (Exception $e) {
    echo 'GAGAL: ' . $e->getMessage() . "\n";
    exit(1);
}
