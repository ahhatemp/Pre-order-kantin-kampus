<?php
require_once __DIR__ . '/helpers.php';

function get_db_connection() {
    static $pdo = null;
    if ($pdo === null) {
        $config = config();
        $dsn = "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        // Lempar, jangan exit: biarkan pemanggil yang menentukan status code.
        $pdo = new PDO($dsn, $config['db_user'], $config['db_password'], $options);
    }
    return $pdo;
}
