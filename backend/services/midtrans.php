<?php
require_once __DIR__ . '/../helpers.php';

function create_snap_token($orderCode, $grossAmount) {
    $config = config();
    $serverKey = $config['midtrans_server_key'];

    if (!$serverKey || strpos($serverKey, 'YOUR_SERVER_KEY') !== false) {
        // ponytail: fallback dummy token saat Midtrans belum disetup
        return 'dummy-snap-token-' . round(microtime(true) * 1000);
    }

    $url = $config['midtrans_is_production']
        ? 'https://app.midtrans.com/snap/v1/transactions'
        : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

    $payload = json_encode([
        'transaction_details' => [
            'order_id' => $orderCode,
            'gross_amount' => (int)$grossAmount,
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

    if ($httpCode !== 200 || !$response) {
        throw new Exception('Gagal memanggil Midtrans Snap API');
    }

    $data = json_decode($response, true);
    return $data['token'] ?? null;
}
