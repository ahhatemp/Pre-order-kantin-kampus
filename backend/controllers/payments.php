<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../db.php';

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

    $config = require __DIR__ . '/../config.php';
    $serverKey = $config['midtrans_server_key'];

    if ($serverKey && strpos($serverKey, 'YOUR_SERVER_KEY') === false) {
        $hash = hash('sha512', $orderCode . $statusCode . $grossAmount . $serverKey);
        if ($hash !== $signatureKey) {
            json_error('Signature key tidak valid', 403);
        }
    }

    $pdo = get_db_connection();

    if (in_array($transactionStatus, ['capture', 'settlement'], true)) {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'paid' WHERE order_code = ? AND status = 'pending'");
        $stmt->execute([$orderCode]);
    } elseif (in_array($transactionStatus, ['cancel', 'expire', 'deny'], true)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT id FROM orders WHERE order_code = ? AND status = 'pending' FOR UPDATE");
            $stmt->execute([$orderCode]);
            $order = $stmt->fetch();

            if ($order) {
                $orderId = (int)$order['id'];
                $itemsStmt = $pdo->prepare('SELECT menu_id, quantity FROM order_items WHERE order_id = ?');
                $itemsStmt->execute([$orderId]);

                $refund = $pdo->prepare('UPDATE menus SET stok = stok + ? WHERE id = ?');
                foreach ($itemsStmt->fetchAll() as $item) {
                    $refund->execute([(int)$item['quantity'], (int)$item['menu_id']]);
                }

                $cancel = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
                $cancel->execute([$orderId]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Webhook cancel error: ' . $e->getMessage());
            json_error('Gagal memproses webhook', 500);
        }
    }

    json_response(['status' => 'ok']);
}
