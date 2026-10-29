<?php
require_once __DIR__ . '/../helpers.php';

/* Padukan prefix key dengan flag produksi supaya key sandbox tidak pernah
   dipakai untuk menagih uang nyata (atau sebaliknya). */
function midtrans_key_mode(string $key): ?string {
    if (strpos($key, 'SB-') === 0) return 'sandbox';
    if (strpos($key, 'Mid-') === 0) return 'production';
    return null;
}

function midtrans_mode(): array {
    $config = config();
    $serverKey = (string)($config['midtrans_server_key'] ?? '');
    $clientKey = (string)($config['midtrans_client_key'] ?? '');
    $isProduction = (bool)$config['midtrans_is_production'];

    if ($serverKey === '' || strpos($serverKey, 'YOUR_SERVER_KEY') !== false) {
        return ['mode' => 'demo', 'is_production' => false, 'reason' => null];
    }

    $serverMode = midtrans_key_mode($serverKey);
    $mismatch = fn($reason) => ['mode' => 'mismatch', 'is_production' => $isProduction, 'reason' => $reason];

    if ($serverMode === null) return $mismatch('Prefix server key tidak dikenal');
    if ($serverMode === 'production' && !$isProduction) return $mismatch('Server key produksi tetapi MIDTRANS_IS_PRODUCTION=false');
    if ($serverMode === 'sandbox' && $isProduction) return $mismatch('Server key sandbox tetapi MIDTRANS_IS_PRODUCTION=true');

    $clientMode = midtrans_key_mode($clientKey);
    if ($clientMode === null) return $mismatch('Prefix client key tidak dikenal');
    if ($clientMode !== $serverMode) return $mismatch('Client key dan server key berbeda mode');

    return ['mode' => $serverMode, 'is_production' => $serverMode === 'production', 'reason' => null];
}

/* Buat transaksi QRIS lewat Core API supaya URL gambar QR bisa dirender
   langsung di halaman pesanan (bukan popup Snap yang mudah tertutup). */
function create_qris_charge($orderCode, $grossAmount, $expiryMinutes = 10): array {
    $mode = midtrans_mode();
    if ($mode['mode'] === 'mismatch') {
        throw new Exception('Konfigurasi Midtrans tidak konsisten: ' . $mode['reason']);
    }
    if ($mode['mode'] === 'demo') {
        // ponytail: fallback demo tanpa Midtrans, order langsung dianggap lunas.
        return ['demo' => true, 'transaction_id' => null, 'qr_url' => null];
    }

    $serverKey = config()['midtrans_server_key'];
    $url = $mode['mode'] === 'production'
        ? 'https://api.midtrans.com/v2/charge'
        : 'https://api.sandbox.midtrans.com/v2/charge';

    $payload = json_encode([
        'payment_type' => 'qris',
        'transaction_details' => [
            'order_id' => $orderCode,
            'gross_amount' => (int)$grossAmount,
        ],
        'qris' => ['acquirer' => 'gopay'],
        // Samakan masa berlaku QR dengan auto-cancel 10 menit milik aplikasi.
        'custom_expiry' => [
            'expiry_duration' => (int)$expiryMinutes,
            'unit' => 'minute',
        ],
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($serverKey . ':'),
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = $response ? json_decode($response, true) : null;
    $statusCode = (string)($data['status_code'] ?? '');
    if (($httpCode !== 200 && $httpCode !== 201) || !is_array($data) || ($statusCode !== '200' && $statusCode !== '201')) {
        error_log('Midtrans QRIS charge error: HTTP ' . $httpCode . ' ' . (string)$response);
        throw new Exception('Gagal membuat kode QRIS');
    }

    $qrUrl = null;
    foreach ($data['actions'] ?? [] as $action) {
        if (($action['name'] ?? '') === 'generate-qr-code' && !empty($action['url'])) {
            $qrUrl = (string)$action['url'];
            break;
        }
    }

    if (!$qrUrl) {
        error_log('Midtrans QRIS charge tanpa action qr-code: ' . (string)$response);
        throw new Exception('Kode QRIS tidak tersedia');
    }

    return [
        'demo' => false,
        'transaction_id' => isset($data['transaction_id']) ? (string)$data['transaction_id'] : null,
        'qr_url' => mb_substr($qrUrl, 0, 255),
    ];
}
