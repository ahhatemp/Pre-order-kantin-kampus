<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/midtrans.php';

/* Kelompokkan item pesanan per order_id agar daftar pesanan tidak N+1. */
function order_items_by_order(PDO $pdo, array $orderIds): array {
    if (!$orderIds) return [];

    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $stmt = $pdo->prepare(
        "SELECT oi.order_id, oi.quantity, oi.price_at_order, m.nama
         FROM order_items oi
         JOIN menus m ON m.id = oi.menu_id
         WHERE oi.order_id IN ($placeholders)
         ORDER BY oi.id"
    );
    $stmt->execute($orderIds);

    $grouped = [];
    foreach ($stmt->fetchAll() as $item) {
        $grouped[(int)$item['order_id']][] = [
            'name' => $item['nama'],
            'qty' => (int)$item['quantity'],
            'price' => (int)$item['price_at_order'],
        ];
    }
    return $grouped;
}

/* Tempelkan item ke tiap order + samakan tipe angka (PDO mengembalikan string). */
function attach_order_items(PDO $pdo, array $orders): array {
    if (!$orders) return [];

    $itemsByOrder = order_items_by_order($pdo, array_map(fn($o) => (int)$o['id'], $orders));
    foreach ($orders as &$order) {
        $order['id'] = (int)$order['id'];
        $order['user_id'] = (int)$order['user_id'];
        $order['total_price'] = (int)$order['total_price'];
        $order['items'] = $itemsByOrder[$order['id']] ?? [];
    }
    return $orders;
}

/* Kembalikan stok untuk satu atau beberapa order (batal/kedaluwarsa). */
function refund_order_stock(PDO $pdo, array $orderIds): void {
    if (!$orderIds) return;

    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $stmt = $pdo->prepare("SELECT menu_id, quantity FROM order_items WHERE order_id IN ($placeholders)");
    $stmt->execute($orderIds);

    $refund = $pdo->prepare('UPDATE menus SET stok = stok + ? WHERE id = ?');
    foreach ($stmt->fetchAll() as $item) {
        $refund->execute([(int)$item['quantity'], (int)$item['menu_id']]);
    }
}

// Pembuatan pesanan dalam satu transaksi: lock baris menu (anti oversell),
// potong stok, simpan order + item, lalu buat token Midtrans.
function create_order_tx($userId, array $items, $pickupAt, $catatan = null) {
    // Agregasi item dengan menu_id sama agar tidak double-deduct stok
    $merged = [];
    foreach ($items as $item) {
        $id = (int)$item['menu_id'];
        $merged[$id] = ($merged[$id] ?? 0) + (int)$item['quantity'];
    }

    $pdo = get_db_connection();
    $pdo->beginTransaction();

    try {
        $menuIds = array_keys($merged);
        $placeholders = implode(',', array_fill(0, count($menuIds), '?'));
        $stmt = $pdo->prepare("SELECT id, nama, harga, stok, is_active FROM menus WHERE id IN ($placeholders) AND deleted_at IS NULL FOR UPDATE");
        $stmt->execute($menuIds);

        $menuMap = [];
        foreach ($stmt->fetchAll() as $row) $menuMap[(int)$row['id']] = $row;

        $totalPrice = 0;
        $orderItems = [];
        $deduct = $pdo->prepare('UPDATE menus SET stok = stok - ? WHERE id = ?');

        foreach ($merged as $menuId => $quantity) {
            $menu = $menuMap[$menuId] ?? null;
            if (!$menu) throw new Exception("Menu ID {$menuId} tidak ditemukan");
            if (!$menu['is_active']) throw new Exception("Menu \"{$menu['nama']}\" sedang tidak tersedia");
            if ((int)$menu['stok'] < $quantity) {
                throw new Exception("Stok untuk \"{$menu['nama']}\" tidak mencukupi (tersedia {$menu['stok']})");
            }

            $totalPrice += (int)$menu['harga'] * $quantity;
            $orderItems[] = ['menu_id' => $menuId, 'quantity' => $quantity, 'price' => (int)$menu['harga']];
            $deduct->execute([$quantity, $menuId]);
        }

        // ponytail: 32 bit acak cukup untuk skala kantin; ganti ke retry bila order > jutaan.
        $orderCode = 'ORD-' . strtoupper(bin2hex(random_bytes(4)));

        $insertOrder = $pdo->prepare(
            "INSERT INTO orders (order_code, user_id, total_price, pickup_at, catatan, status, expires_at)
             VALUES (?, ?, ?, ?, ?, 'pending', DATE_ADD(NOW(), INTERVAL 10 MINUTE))"
        );
        $insertOrder->execute([$orderCode, $userId, $totalPrice, $pickupAt, $catatan ?: null]);
        $orderId = (int)$pdo->lastInsertId();

        $insertItem = $pdo->prepare('INSERT INTO order_items (order_id, menu_id, quantity, price_at_order) VALUES (?, ?, ?, ?)');
        foreach ($orderItems as $oi) {
            $insertItem->execute([$orderId, $oi['menu_id'], $oi['quantity'], $oi['price']]);
        }

        $snapToken = create_snap_token($orderCode, $totalPrice);
        // Mode demo tanpa Midtrans: pembayaran dianggap berhasil otomatis,
        // jadi admin tidak perlu mengonfirmasi pending -> paid secara manual.
        $isDemo = is_string($snapToken) && strpos($snapToken, 'dummy-') === 0;
        $updateToken = $pdo->prepare('UPDATE orders SET payment_token = ?, status = ? WHERE id = ?');
        $updateToken->execute([$snapToken, $isDemo ? 'paid' : 'pending', $orderId]);

        $pdo->commit();

        $stmtExp = $pdo->prepare('SELECT expires_at FROM orders WHERE id = ?');
        $stmtExp->execute([$orderId]);
        $expiresAt = $stmtExp->fetchColumn();

        return [
            'id' => $orderId,
            'order_code' => $orderCode,
            'total_price' => $totalPrice,
            'payment_token' => $snapToken,
            'status' => $isDemo ? 'paid' : 'pending',
            'expires_at' => $expiresAt,
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
