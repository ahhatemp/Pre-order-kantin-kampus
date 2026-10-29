<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../services/order.php';
require_once __DIR__ . '/../services/push.php';

function admin_orders_all() {
    $me = authenticate();
    require_role($me, 'admin');

    $pickupDate = $_GET['pickup_date'] ?? null;

    try {
        $pdo = get_db_connection();
        $sql = 'SELECT o.*, u.nama as user_nama, u.nim as user_nim FROM orders o JOIN users u ON o.user_id = u.id';
        $params = [];

        if ($pickupDate) {
            $sql .= ' WHERE DATE(pickup_at) = ?';
            $params[] = $pickupDate;
        }

        $sql .= ' ORDER BY pickup_at ASC, created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        json_response(attach_order_items($pdo, $stmt->fetchAll()));
    } catch (PDOException $e) {
        json_error('Gagal mengambil daftar pesanan', 500);
    }
}

function admin_order_status($id) {
    $me = authenticate();
    require_role($me, 'admin');

    $body = get_json_body();
    $status = $body['status'] ?? null;

    if (!is_string($status)) json_error('Status wajib diisi');

    $validTransitions = [
        'pending' => ['cancelled'],
        'paid' => ['ready', 'cancelled'],
        'ready' => ['completed'],
    ];

    // 'paid' khusus konfirmasi tunai; validasinya butuh data order.
    if ($status !== 'paid' && !in_array($status, array_merge(...array_values($validTransitions)), true)) {
        json_error("Status \"{$status}\" tidak valid");
    }

    $pdo = get_db_connection();

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? FOR UPDATE');
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if (!$order) {
            $pdo->rollBack();
            json_error('Pesanan tidak ditemukan', 404);
        }

        if ($status === 'paid') {
            if ($order['status'] !== 'pending' || ($order['payment_type'] ?? '') !== 'cash') {
                $pdo->rollBack();
                json_error('Status pembayaran QRIS diperbarui otomatis oleh sistem');
            }

            $pdo->prepare("UPDATE orders SET status = 'paid', payment_status = 'cash', paid_at = COALESCE(paid_at, NOW()) WHERE id = ?")
                ->execute([$id]);
            $pdo->commit();
            json_response(['message' => 'Pembayaran tunai berhasil dikonfirmasi']);
        }

        if (!in_array($status, $validTransitions[$order['status']] ?? [], true)) {
            $pdo->rollBack();
            json_error("Transisi status dari {$order['status']} ke {$status} tidak valid");
        }

        // Jika cancel, kembalikan stok
        if ($status === 'cancelled' && $order['status'] !== 'cancelled') {
            refund_order_stock($pdo, [(int)$id]);
        }

        $update = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $update->execute([$status, $id]);

        $pdo->commit();

        // Kirim setelah respons supaya admin tidak menunggu layanan push.
        if ($status === 'ready') {
            $userId = (int)$order['user_id'];
            register_shutdown_function(function () use ($userId) {
                push_send_to_user($userId, [
                    'title' => 'Kantin Kampus',
                    'body' => 'Pesananmu telah siap',
                    'url' => 'pesanan.html',
                    'tag' => 'pesanan-siap',
                ]);
            });
        }

        json_response(['message' => 'Status pesanan berhasil diperbarui']);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Update order status error: ' . $e->getMessage());
        json_error('Gagal memperbarui status pesanan', 500);
    }
}
