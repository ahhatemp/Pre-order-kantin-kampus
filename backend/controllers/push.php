<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../services/push.php';

function push_public_key() {
    try {
        json_response(['publicKey' => push_vapid_keys()['public_key']]);
    } catch (Throwable $e) {
        error_log('Push public key error: ' . $e->getMessage());
        json_error('Notifikasi push belum siap', 500);
    }
}

function push_subscribe() {
    $me = authenticate();

    $body = get_json_body();
    $endpoint = $body['endpoint'] ?? null;
    if (!is_string($endpoint) || $endpoint === '' || strlen($endpoint) > 4096) {
        json_error('Endpoint subscription tidak valid');
    }
    if (!push_endpoint_allowed($endpoint)) {
        json_error('Endpoint push tidak didukung');
    }

    $keys = is_array($body['keys'] ?? null) ? $body['keys'] : [];
    $p256dh = is_string($keys['p256dh'] ?? null) ? $keys['p256dh'] : '';
    $authKey = is_string($keys['auth'] ?? null) ? $keys['auth'] : '';
    if (strlen($p256dh) > 255 || strlen($authKey) > 255) {
        json_error('Kunci subscription tidak valid');
    }

    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare(
            'INSERT INTO push_subscriptions (user_id, endpoint, endpoint_hash, p256dh, auth)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), p256dh = VALUES(p256dh), auth = VALUES(auth)'
        );
        $stmt->execute([(int)$me['id'], $endpoint, hash('sha256', $endpoint), $p256dh, $authKey]);
        json_response(['message' => 'Subscription push disimpan']);
    } catch (PDOException $e) {
        error_log('Push subscribe error: ' . $e->getMessage());
        json_error('Gagal menyimpan subscription push', 500);
    }
}

function push_unsubscribe() {
    $me = authenticate();

    $body = get_json_body();
    $endpoint = $body['endpoint'] ?? null;
    if (!is_string($endpoint) || $endpoint === '') {
        json_error('Endpoint subscription tidak valid');
    }

    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare('DELETE FROM push_subscriptions WHERE user_id = ? AND endpoint_hash = ?');
        $stmt->execute([(int)$me['id'], hash('sha256', $endpoint)]);
        json_response(['message' => 'Subscription push dihapus']);
    } catch (PDOException $e) {
        error_log('Push unsubscribe error: ' . $e->getMessage());
        json_error('Gagal menghapus subscription push', 500);
    }
}
