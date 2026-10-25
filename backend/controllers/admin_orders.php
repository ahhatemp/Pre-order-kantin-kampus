<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';
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

        $orders = $stmt->fetchAll();
        if (!$orders) json_response([]);

        // Ambil semua item sekaligus, lalu kelompokkan per order (hindari N+1).
        $ids = array_map(fn($o) => (int)$o['id'], $orders);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $itemsStmt = $pdo->prepare(
            "SELECT oi.order_id, oi.quantity, oi.price_at_order, m.nama
             FROM order_items oi
             JOIN menus m ON m.id = oi.menu_id
             WHERE oi.order_id IN ($placeholders)
             ORDER BY oi.id"
        );
        $itemsStmt->execute($ids);

        $itemsByOrder = [];
        foreach ($itemsStmt->fetchAll() as $item) {
            $itemsByOrder[(int)$item['order_id']][] = [
                'name' => $item['nama'],
                'qty' => (int)$item['quantity'],
                'price' => (int)$item['price_at_order'],
            ];
        }

        foreach ($orders as &$o) {
            $o['id'] = (int)$o['id'];
            $o['user_id'] = (int)$o['user_id'];
            $o['total_price'] = (int)$o['total_price'];
            $o['items'] = $itemsByOrder[$o['id']] ?? [];
        }
        json_response($orders);
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
    if ($status === 'paid') json_error('Status pembayaran diperbarui otomatis oleh sistem');

    $validTransitions = [
        'pending' => ['cancelled'],
        'paid' => ['ready', 'cancelled'],
        'ready' => ['completed'],
    ];

    $isValidStatus = false;
    foreach ($validTransitions as $allowed) {
        if (in_array($status, $allowed, true)) $isValidStatus = true;
    }
    if (!$isValidStatus) json_error("Status \"{$status}\" tidak valid");

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

        if (!in_array($status, $validTransitions[$order['status']] ?? [], true)) {
            $pdo->rollBack();
            json_error("Transisi status dari {$order['status']} ke {$status} tidak valid");
        }

        // Jika cancel, kembalikan stok
        if ($status === 'cancelled' && $order['status'] !== 'cancelled') {
            $itemsStmt = $pdo->prepare('SELECT menu_id, quantity FROM order_items WHERE order_id = ?');
            $itemsStmt->execute([$id]);

            $refund = $pdo->prepare('UPDATE menus SET stok = stok + ? WHERE id = ?');
            foreach ($itemsStmt->fetchAll() as $item) {
                $refund->execute([(int)$item['quantity'], (int)$item['menu_id']]);
            }
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
