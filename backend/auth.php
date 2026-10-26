<?php
require_once __DIR__ . '/helpers.php';

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

function jwt_encode(array $payload) {
    $config = config();
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $payload['iat'] = time();
    $payload['exp'] = time() + 12 * 3600; // 12 jam, sama dengan backend lama

    $segments = [
        base64url_encode(json_encode($header)),
        base64url_encode(json_encode($payload)),
    ];
    $signingInput = implode('.', $segments);
    $signature = hash_hmac('sha256', $signingInput, $config['jwt_secret'], true);
    $segments[] = base64url_encode($signature);

    return implode('.', $segments);
}

function jwt_decode($token) {
    $config = config();
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;

    list($headerB64, $payloadB64, $signatureB64) = $parts;
    $signingInput = $headerB64 . '.' . $payloadB64;
    $expected = base64url_encode(hash_hmac('sha256', $signingInput, $config['jwt_secret'], true));

    if (!hash_equals($expected, $signatureB64)) return null;

    $payload = json_decode(base64url_decode($payloadB64), true);
    if (!is_array($payload) || !isset($payload['exp']) || $payload['exp'] < time()) return null;

    return $payload;
}

function bearer_token() {
    // Apache/CGI sering membuang header Authorization; cek semua jalur yang ada.
    $header = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? '';

    if (!$header && function_exists('apache_request_headers')) {
        foreach (apache_request_headers() as $key => $value) {
            if (strcasecmp($key, 'Authorization') === 0) { $header = $value; break; }
        }
    }

    if (!preg_match('/^Bearer\s+(.+)$/i', $header, $m)) return null;
    return trim($m[1]);
}

// Returns the decoded user or sends 401 and exits.
function authenticate() {
    $token = bearer_token();
    if (!$token) json_error('Token diperlukan', 401);

    $payload = jwt_decode($token);
    if (!$payload) json_error('Token tidak valid', 401);

    return $payload;
}

function require_role($user, $role) {
    if (($user['role'] ?? null) !== $role) {
        json_error('Akses ditolak', 403);
    }
}
