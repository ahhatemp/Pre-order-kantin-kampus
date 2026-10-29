<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../services/order.php';
require_once __DIR__ . '/../services/midtrans.php';
require_once __DIR__ . '/../services/push.php';

function payments_config() {
    $config = config();
    $mode = midtrans_mode();
    $enabled = in_array($mode['mode'], ['sandbox', 'production'], true);

    json_response([
        // Client key memang publik; server key tidak pernah dikirim.
        'client_key' => $config['midtrans_client_key'],
        'is_production' => $mode['is_production'],
        'enabled' => $enabled,
        'mode' => $mode['mode'],
        'message' => $mode['reason'] ?? ($mode['mode'] === 'demo' ? 'Midtrans belum dikonfigurasi; QRIS memakai mode demo.' : null),
    ]);
}

function payment_channel_from_notification(array $body): ?string {
    $channel = null;

    if (!empty($body['bank']) && is_string($body['bank'])) {
        $channel = $body['bank'];
    } elseif (is_array($body['va_numbers'] ?? null) && isset($body['va_numbers'][0]['bank']) && is_string($body['va_numbers'][0]['bank'])) {
        $channel = $body['va_numbers'][0]['bank'];
    } elseif (!empty($body['store']) && is_string($body['store'])) {
        $channel = $body['store'];
    } elseif (!empty($body['acquirer']) && is_string($body['acquirer'])) {
        $channel = $body['acquirer'];
    }

    if (($body['payment_type'] ?? '') === 'echannel') $channel = 'mandiri';
    return $channel ? mb_substr($channel, 0, 40) : null;
}

function payment_paid_at(array $body): string {
    $raw = $body['settlement_time'] ?? $body['transaction_time'] ?? null;

    if (is_string($raw) && strlen($raw) >= 19) {
        $dt = DateTime::createFromFormat('Y-m-d H:i:s', substr($raw, 0, 19), new DateTimeZone('Asia/Jakarta'));
        if ($dt instanceof DateTime) return $dt->format('Y-m-d H:i:s');
    }

    return date('Y-m-d H:i:s');
}

function payment_detail_columns(): string {
    return 'payment_type = COALESCE(payment_type, ?), payment_channel = COALESCE(payment_channel, ?), '
        . 'transaction_id = COALESCE(transaction_id, ?), payment_status = ?, '
        . 'fraud_status = COALESCE(fraud_status, ?), paid_at = COALESCE(paid_at, ?)';
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
    $mode = midtrans_mode();

    // Fail closed: konfigurasi setengah jadi tidak boleh dipercaya.
    if ($mode['mode'] === 'mismatch') {
        json_error('Konfigurasi Midtrans tidak konsisten', 503);
    }
    if ($mode['mode'] === 'demo') {
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

    $isSettled = in_array($transactionStatus, ['capture', 'settlement'], true);
    $isCancelled = in_array($transactionStatus, ['cancel', 'expire', 'deny'], true);
    if (!$isSettled && !$isCancelled && $transactionStatus !== 'pending') {
        json_response(['status' => 'ok']);
    }

    $paymentType = isset($body['payment_type']) && is_string($body['payment_type'])
        ? mb_substr($body['payment_type'], 0, 30) : null;
    $paymentChannel = payment_channel_from_notification($body);
    $transactionId = isset($body['transaction_id']) && is_string($body['transaction_id'])
        ? mb_substr($body['transaction_id'], 0, 64) : null;
    $fraudStatus = isset($body['fraud_status']) && is_string($body['fraud_status'])
        ? mb_substr($body['fraud_status'], 0, 20) : null;
    $paidAt = payment_paid_at($body);
    $detailColumns = payment_detail_columns();
    $detailParams = [$paymentType, $paymentChannel, $transactionId, $transactionStatus, $fraudStatus, $paidAt];

    $pdo = get_db_connection();
    $paidOrder = null;

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_code = ? FOR UPDATE');
        $stmt->execute([$orderCode]);
        $order = $stmt->fetch();

        if (!$order || ($order['payment_type'] ?? '') === 'cash') {
            $pdo->commit();
            json_response(['status' => 'ignored']);
        }

        if ($isSettled) {
            if ($grossAmount !== null && (int)round((float)$grossAmount) !== (int)$order['total_price']) {
                $pdo->rollBack();
                json_error('gross_amount tidak cocok dengan total pesanan', 400);
            }

            if ($order['status'] === 'pending') {
                $pdo->prepare("UPDATE orders SET status = 'paid', $detailColumns WHERE id = ?")
                    ->execute([...$detailParams, $order['id']]);
                $paidOrder = $order;
            } elseif ($order['status'] === 'cancelled' && $order['paid_at'] === null) {
                /* Sweep sudah membatalkan + mengembalikan stok, tapi pembayaran
                   tetap masuk. Pesan ulang stok kalau masih ada; kalau tidak,
                   tolak supaya Midtrans retry dan kasusnya masuk log untuk refund.
                   Hanya berlaku untuk order yang belum pernah lunas: order yang
                   dibatalkan setelah lunas tidak boleh dihidupkan kembali oleh
                   notifikasi settlement duplikat. */
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
                $pdo->prepare("UPDATE orders SET status = 'paid', $detailColumns WHERE id = ?")
                    ->execute([...$detailParams, $order['id']]);
                $paidOrder = $order;
                error_log("Late payment {$orderCode}: pesanan diaktifkan kembali");
            } else {
                // Notifikasi ulang untuk order yang sudah lunas, termasuk order
                // yang dibatalkan setelah lunas: lengkapi detail tanpa mengubah
                // status atau memotong stok lagi.
                $pdo->prepare("UPDATE orders SET $detailColumns WHERE id = ?")
                    ->execute([...$detailParams, $order['id']]);
            }
        } elseif ($transactionStatus === 'pending') {
            if ($order['status'] === 'pending') {
                $pdo->prepare(
                    "UPDATE orders SET payment_type = COALESCE(payment_type, ?), payment_channel = COALESCE(payment_channel, ?),
                     transaction_id = COALESCE(transaction_id, ?), payment_status = 'pending',
                     fraud_status = COALESCE(fraud_status, ?) WHERE id = ?"
                )->execute([$paymentType, $paymentChannel, $transactionId, $fraudStatus, $order['id']]);
            }
        } else {
            if ($order['status'] === 'pending') {
                $orderId = (int)$order['id'];
                refund_order_stock($pdo, [$orderId]);
                $pdo->prepare(
                    "UPDATE orders SET status = 'cancelled', payment_type = COALESCE(payment_type, ?),
                     payment_channel = COALESCE(payment_channel, ?), transaction_id = COALESCE(transaction_id, ?),
                     payment_status = ?, fraud_status = COALESCE(fraud_status, ?) WHERE id = ?"
                )->execute([$paymentType, $paymentChannel, $transactionId, $transactionStatus, $fraudStatus, $orderId]);
            } elseif ($order['status'] === 'cancelled' && $order['paid_at'] === null) {
                $pdo->prepare('UPDATE orders SET payment_status = ?, fraud_status = COALESCE(fraud_status, ?) WHERE id = ?')
                    ->execute([$transactionStatus, $fraudStatus, $order['id']]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Webhook {$transactionStatus} error: " . $e->getMessage());
        json_error('Gagal memproses webhook', 500);
    }

    // Kirim setelah respons supaya webhook Midtrans tidak menunggu layanan push.
    if ($paidOrder !== null) {
        register_shutdown_function(fn() => push_notify_new_order($paidOrder));
    }

    json_response(['status' => 'ok']);
}
