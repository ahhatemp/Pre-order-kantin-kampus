<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../services/order.php';
require_once __DIR__ . '/../services/push.php';

function payments_config() {
    $config = config();
    $serverKey = $config['midtrans_server_key'];
    $clientKey = $config['midtrans_client_key'];
    json_response([
        // Client key memang publik; server key tidak pernah dikirim.
        'client_key' => $clientKey,
        'is_production' => (bool)$config['midtrans_is_production'],
        'enabled' => $serverKey && $clientKey
            && strpos($serverKey, 'YOUR_SERVER_KEY') === false
            && strpos($clientKey, 'YOUR_CLIENT_KEY') === false,
    ]);
}

function payments_webhook() {
    $body = get_json_body();
    $orderCode = $body['order_id'] ?? null;
    $statusCode = $body['status_code'] ?? null;
    $grossAmount = $body['gross_amount'] ?? null;
    $signatureKey = $body['signature_key'] ?? null;
    $transactionStatus = $body['transaction_status'] ?? null;

    if (!$orderCode || !$transactionStatus) {
        json_error('order_id dan transaction_status wajib diisi');
    }

    $config = config();
    $serverKey = $config['midtrans_server_key'];

    // Fail closed: tanpa key asli, webhook tidak boleh dipercaya sama sekali.
    if (!$serverKey || strpos($serverKey, 'YOUR_SERVER_KEY') !== false) {
        json_error('Webhook belum dikonfigurasi', 503);
    }

    $expected = hash('sha512', $orderCode . $statusCode . $grossAmount . $serverKey);
    if (!is_string($signatureKey) || $signatureKey === '' || !hash_equals($expected, $signatureKey)) {
        json_error('Signature key tidak valid', 403);
    }

    // Kartu kredit: capture hanya sah kalau fraud_status accept.
    if ($transactionStatus === 'capture' && ($body['fraud_status'] ?? '') !== 'accept') {
        json_response(['status' => 'ignored']);
    }

    $pdo = get_db_connection();
    $paidOrder = null;

    if (in_array($transactionStatus, ['capture', 'settlement'], true)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_code = ? FOR UPDATE');
            $stmt->execute([$orderCode]);
            $order = $stmt->fetch();

            if (!$order) {
                $pdo->commit();
                json_response(['status' => 'ignored']);
            }

            if ($grossAmount !== null && (int)round((float)$grossAmount) !== (int)$order['total_price']) {
                $pdo->rollBack();
                json_error('gross_amount tidak cocok dengan total pesanan', 400);
            }

            if ($order['status'] === 'pending') {
                $pdo->prepare("UPDATE orders SET status = 'paid' WHERE id = ?")->execute([$order['id']]);
                $paidOrder = $order;
            } elseif ($order['status'] === 'cancelled') {
                /* Sweep sudah membatalkan + mengembalikan stok, tapi pembayaran
                   tetap masuk. Pesan ulang stok kalau masih ada; kalau tidak,
                   tolak supaya Midtrans retry dan kasusnya masuk log untuk refund. */
                $itemsStmt = $pdo->prepare('SELECT menu_id, quantity FROM order_items WHERE order_id = ?');
                $itemsStmt->execute([$order['id']]);
                $items = $itemsStmt->fetchAll();

                foreach ($items as $item) {
                    $lock = $pdo->prepare('SELECT stok FROM menus WHERE id = ? FOR UPDATE');
                    $lock->execute([(int)$item['menu_id']]);
                    $stok = $lock->fetchColumn();
                    if ($stok === false || (int)$stok < (int)$item['quantity']) {
                        $pdo->rollBack();
                        error_log("Late payment {$orderCode}: stok tidak cukup, refund manual diperlukan");
                        json_error('Stok tidak cukup untuk pembayaran terlambat; refund manual diperlukan', 409);
                    }
                }

                $deduct = $pdo->prepare('UPDATE menus SET stok = stok - ? WHERE id = ?');
                foreach ($items as $item) {
                    $deduct->execute([(int)$item['quantity'], (int)$item['menu_id']]);
                }
                $pdo->prepare("UPDATE orders SET status = 'paid' WHERE id = ?")->execute([$order['id']]);
                $paidOrder = $order;
                error_log("Late payment {$orderCode}: pesanan diaktifkan kembali");
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Webhook settlement error: ' . $e->getMessage());
            json_error('Gagal memproses webhook', 500);
        }
    } elseif (in_array($transactionStatus, ['cancel', 'expire', 'deny'], true)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT id FROM orders WHERE order_code = ? AND status = 'pending' FOR UPDATE");
            $stmt->execute([$orderCode]);
            $order = $stmt->fetch();

            if ($order) {
                $orderId = (int)$order['id'];
                refund_order_stock($pdo, [$orderId]);
                $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")->execute([$orderId]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Webhook cancel error: ' . $e->getMessage());
            json_error('Gagal memproses webhook', 500);
        }
    }

    // Kirim setelah respons supaya webhook Midtrans tidak menunggu layanan push.
    if ($paidOrder !== null) {
        register_shutdown_function(fn() => push_notify_new_order($paidOrder));
    }

    json_response(['status' => 'ok']);
}
