<?php
// Web Push berpayload aes128gcm (RFC 8291); service worker menampilkan isi payload.
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../db.php';

const PUSH_ALLOWED_HOSTS = ['fcm.googleapis.com', 'android.googleapis.com', 'updates.push.services.mozilla.com', 'web.push.apple.com'];
const PUSH_ALLOWED_SUFFIXES = ['.notify.windows.com'];
// SPKI DER untuk kunci publik P-256 tanpa kompresi, dipakai openssl_pkey_get_public.
const PUSH_P256_SPKI_PREFIX = "\x30\x59\x30\x13\x06\x07\x2A\x86\x48\xCE\x3D\x02\x01\x06\x08\x2A\x86\x48\xCE\x3D\x03\x01\x07\x03\x42\x00";

function push_vapid_file() {
    return config()['vapid_key_file'];
}

function push_base64url($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function push_base64url_decode($data) {
    $data = strtr($data, '-_', '+/');
    $padding = strlen($data) % 4;
    if ($padding > 0) $data .= str_repeat('=', 4 - $padding);
    $raw = base64_decode($data, true);
    return $raw === false ? '' : $raw;
}

/* Kunci dari PushSubscription.toJSON() berformat base64url.
   p256dh = titik P-256 tanpa kompresi (65 byte, awalan 0x04); auth = 16 byte. */
function push_subscription_key_valid($value, $expectedBytes) {
    if (!is_string($value) || $value === '' || strlen($value) > 255) return false;
    $raw = push_base64url_decode($value);
    if (strlen($raw) !== $expectedBytes) return false;
    if ($expectedBytes === 65 && $raw[0] !== "\x04") return false;
    return true;
}

/* HKDF-Expand (RFC 5869) dengan SHA-256. */
function push_hkdf_expand($prk, $info, $length) {
    $output = '';
    $block = '';
    for ($counter = 1; strlen($output) < $length; $counter++) {
        $block = hash_hmac('sha256', $block . $info . chr($counter), $prk, true);
        $output .= $block;
    }
    return substr($output, 0, $length);
}

function push_new_ec_key($errorMessage = 'Gagal membuat kunci enkripsi push'): array {
    $options = [
        'private_key_type' => OPENSSL_KEYTYPE_EC,
        'curve_name' => 'prime256v1',
    ];
    $resource = @openssl_pkey_new($options);
    if (!$resource) {
        // Sebagian PHP Windows tidak menemukan openssl.cnf bawaan sistem.
        while (openssl_error_string() !== false) {}
        $options['config'] = __DIR__ . '/../openssl.cnf';
        $resource = @openssl_pkey_new($options);
    }
    if (!$resource) throw new RuntimeException($errorMessage);
    return [$resource, $options];
}

/* Enkripsi payload untuk satu subscription (Content-Encoding: aes128gcm). */
function push_encrypt_payload($payload, $p256dh, $authKey) {
    $uaPublic = push_base64url_decode($p256dh);
    $authSecret = push_base64url_decode($authKey);
    if (strlen($uaPublic) !== 65 || $uaPublic[0] !== "\x04") {
        throw new RuntimeException('Kunci p256dh subscription tidak valid');
    }
    if (strlen($authSecret) !== 16) {
        throw new RuntimeException('Kunci auth subscription tidak valid');
    }

    [$localKey] = push_new_ec_key();
    $details = openssl_pkey_get_details($localKey);
    if (!$details || empty($details['ec']['x']) || empty($details['ec']['y'])) {
        throw new RuntimeException('Kunci ephemeral push tidak valid');
    }
    $asPublic = "\x04"
        . str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT)
        . str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT);

    $uaKey = openssl_pkey_get_public('-----BEGIN PUBLIC KEY-----' . "\n"
        . chunk_split(base64_encode(PUSH_P256_SPKI_PREFIX . $uaPublic), 64, "\n")
        . '-----END PUBLIC KEY-----');
    if (!$uaKey) throw new RuntimeException('Kunci publik subscription tidak dapat dibaca');

    $ecdhSecret = openssl_pkey_derive($uaKey, $localKey, 32);
    if ($ecdhSecret === false || strlen($ecdhSecret) !== 32) {
        throw new RuntimeException('Gagal menurunkan shared secret push');
    }

    $prkKey = hash_hmac('sha256', $ecdhSecret, $authSecret, true);
    $ikm = push_hkdf_expand($prkKey, "WebPush: info\x00" . $uaPublic . $asPublic, 32);

    $salt = random_bytes(16);
    $prk = hash_hmac('sha256', $ikm, $salt, true);
    $cek = push_hkdf_expand($prk, "Content-Encoding: aes128gcm\x00", 16);
    $nonce = push_hkdf_expand($prk, "Content-Encoding: nonce\x00", 12);

    $tag = '';
    $ciphertext = openssl_encrypt($payload . "\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
    if ($ciphertext === false || strlen($tag) !== 16) {
        throw new RuntimeException('Gagal mengenkripsi payload push');
    }

    // Header record: salt(16) | rs(4) | idlen(1) | keyid(65)
    return $salt . pack('N', 4096) . chr(strlen($asPublic)) . $asPublic . $ciphertext . $tag;
}

function push_vapid_keys() {
    static $keys = null;
    if ($keys !== null) return $keys;

    $file = push_vapid_file();
    if (is_file($file)) {
        $saved = json_decode((string)file_get_contents($file), true);
        if (is_array($saved) && !empty($saved['private_pem']) && !empty($saved['public_key'])) {
            return $keys = $saved;
        }
    }

    [$resource, $options] = push_new_ec_key('Gagal membuat kunci VAPID');

    if (!openssl_pkey_export($resource, $privatePem, null, $options)) {
        throw new RuntimeException('Gagal mengekspor kunci VAPID');
    }
    $details = openssl_pkey_get_details($resource);
    if (!$details || empty($details['ec']['x']) || empty($details['ec']['y'])) {
        throw new RuntimeException('Kunci VAPID tidak valid');
    }

    $publicKey = "\x04"
        . str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT)
        . str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT);

    $keys = [
        'private_pem' => $privatePem,
        'public_key' => push_base64url($publicKey),
    ];

    $dir = dirname($file);
    if (!is_dir($dir)) mkdir($dir, 0700, true);
    if (file_put_contents($file, json_encode($keys), LOCK_EX) === false) {
        throw new RuntimeException('Gagal menyimpan kunci VAPID');
    }
    @chmod($file, 0600);

    return $keys;
}

function push_vapid_subject() {
    return config()['push_subject'];
}

/* Ubah signature DER ECDSA menjadi format mentah R||S untuk JWT ES256. */
function push_der_signature_to_raw($der) {
    $offset = 0;
    $length = strlen($der);
    if ($length < 2 || ord($der[$offset++]) !== 0x30) return null;

    $seqLength = ord($der[$offset++]);
    if ($seqLength & 0x80) {
        $bytes = $seqLength & 0x7f;
        if ($bytes < 1 || $bytes > 2 || $offset + $bytes > $length) return null;
        $seqLength = 0;
        for ($i = 0; $i < $bytes; $i++) $seqLength = ($seqLength << 8) | ord($der[$offset++]);
    }
    $sequenceEnd = $offset + $seqLength;

    $raw = '';
    for ($i = 0; $i < 2; $i++) {
        if ($offset >= $sequenceEnd || ord($der[$offset++]) !== 0x02) return null;
        $intLength = ord($der[$offset++]);
        if ($intLength & 0x80) {
            $bytes = $intLength & 0x7f;
            if ($bytes < 1 || $bytes > 2 || $offset + $bytes > $sequenceEnd) return null;
            $intLength = 0;
            for ($j = 0; $j < $bytes; $j++) $intLength = ($intLength << 8) | ord($der[$offset++]);
        }
        if ($offset + $intLength > $sequenceEnd) return null;

        $integer = ltrim(substr($der, $offset, $intLength), "\0");
        $offset += $intLength;
        if (strlen($integer) > 32) return null;
        $raw .= str_pad($integer, 32, "\0", STR_PAD_LEFT);
    }

    return strlen($raw) === 64 ? $raw : null;
}

function push_vapid_authorization($endpoint) {
    $parts = parse_url($endpoint);
    $audience = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '');

    $header = push_base64url(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
    $payload = push_base64url(json_encode([
        'aud' => $audience,
        'exp' => time() + 3600,
        'sub' => push_vapid_subject(),
    ]));
    $unsigned = $header . '.' . $payload;
    $keys = push_vapid_keys();

    if (!openssl_sign($unsigned, $der, $keys['private_pem'], OPENSSL_ALGO_SHA256)) {
        throw new RuntimeException('Gagal menandatangani token VAPID');
    }
    $raw = push_der_signature_to_raw($der);
    if ($raw === null) throw new RuntimeException('Signature VAPID tidak valid');

    return 'vapid t=' . $unsigned . '.' . push_base64url($raw) . ', k=' . $keys['public_key'];
}

/* Batasi endpoint ke layanan push browser yang dikenal agar tidak menjadi SSRF. */
function push_endpoint_allowed($endpoint) {
    $parts = parse_url($endpoint);
    if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) return false;

    $host = strtolower($parts['host']);
    if (in_array($host, PUSH_ALLOWED_HOSTS, true)) return true;
    foreach (PUSH_ALLOWED_SUFFIXES as $suffix) {
        if (str_ends_with($host, $suffix)) return true;
    }
    return false;
}

function push_send_one($endpoint, array $payload, $p256dh, $authKey) {
    if (!push_endpoint_allowed($endpoint)) {
        error_log('Push endpoint tidak diizinkan: ' . (parse_url($endpoint, PHP_URL_HOST) ?: '-'));
        return 0;
    }

    try {
        $authorization = push_vapid_authorization($endpoint);
        $body = push_encrypt_payload(
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $p256dh,
            $authKey
        );
    } catch (Throwable $e) {
        error_log('Push payload error: ' . $e->getMessage());
        return 0;
    }

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . $authorization,
            'Content-Encoding: aes128gcm',
            'Content-Type: application/octet-stream',
            'TTL: 86400',
            'Urgency: high',
        ],
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 5,
    ]);
    curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error !== '') error_log('Push cURL error: ' . $error);
    return $status;
}

/* Kirim satu payload ke semua perangkat milik user. */
function push_send_to_user($userId, array $payload) {
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare('SELECT id, endpoint, p256dh, auth FROM push_subscriptions WHERE user_id = ?');
        $stmt->execute([(int)$userId]);
        $subscriptions = $stmt->fetchAll();
        if (!$subscriptions) return 0;

        $delete = $pdo->prepare('DELETE FROM push_subscriptions WHERE id = ?');
        $sent = 0;
        foreach ($subscriptions as $subscription) {
            if (!push_endpoint_allowed($subscription['endpoint'])) {
                $delete->execute([(int)$subscription['id']]);
                continue;
            }

            $status = push_send_one(
                $subscription['endpoint'],
                $payload,
                $subscription['p256dh'],
                $subscription['auth']
            );
            if ($status === 200 || $status === 201 || $status === 202) {
                $sent++;
            } elseif ($status === 404 || $status === 410) {
                $delete->execute([(int)$subscription['id']]);
            } else {
                error_log('Push gagal dengan status ' . $status . ' untuk subscription ' . $subscription['id']);
            }
        }
        return $sent;
    } catch (Throwable $e) {
        error_log('Push send error: ' . $e->getMessage());
        return 0;
    }
}

/* Kirim payload ke semua user dengan role tertentu, mis. semua admin. */
function push_send_to_role($role, array $payload) {
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE role = ?');
        $stmt->execute([$role]);

        $sent = 0;
        foreach ($stmt->fetchAll() as $user) {
            $sent += push_send_to_user((int)$user['id'], $payload);
        }
        return $sent;
    } catch (Throwable $e) {
        error_log('Push role send error: ' . $e->getMessage());
        return 0;
    }
}

/* Beri tahu semua admin bahwa ada pesanan yang sudah dibayar. */
function push_notify_new_order(array $order) {
    $total = (int)($order['total_price'] ?? 0);
    push_send_to_role('admin', [
        'title' => 'Pesanan baru masuk',
        'body' => 'Pesanan ' . ($order['order_code'] ?? '') . ' sebesar Rp ' . number_format($total, 0, ',', '.') . ' sudah dibayar.',
        'url' => 'admin-pesanan.html',
        'tag' => 'pesanan-baru-' . (int)($order['id'] ?? 0),
    ]);
}
