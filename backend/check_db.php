<?php
require_once __DIR__ . '/db.php';

$config = config();
try {
    $pdo = get_db_connection();
    echo "OK koneksi ke {$config['db_name']}\n";
    foreach (['users', 'menus', 'orders', 'order_items'] as $t) {
        $n = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo "$t: $n baris\n";
    }
} catch (Exception $e) {
    echo 'GAGAL: ' . $e->getMessage() . "\n";
    exit(1);
}
