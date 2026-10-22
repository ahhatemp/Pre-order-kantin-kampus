<?php
function get_db_connection() {
    static $pdo = null;
    if ($pdo === null) {
        $config = require __DIR__ . '/config.php';
        $dsn = "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            $pdo = new PDO($dsn, $config['db_user'], $config['db_password'], $options);
        } catch (PDOException $e) {
            // Lempar, jangan exit: biarkan pemanggil yang menentukan status code.
            throw new PDOException('Database connection failed: ' . $e->getMessage(), 0, $e);
        }
    }
    return $pdo;
}
