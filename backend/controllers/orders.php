<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../rate_limit.php';
require_once __DIR__ . '/../services/order.php';
require_once __DIR__ . '/../services/push.php';
require_once __DIR__ . '/../services/kantin_hours.php';

function orders_create() {
    $me = authenticate();

    $config = config();
    rate_limit('order', $config['rate_limit_order'], 60, 'Terlalu banyak permintaan order. Tunggu 1 menit.');

    $body = get_json_body();
    $items = $body['items'] ?? null;
    $pickupAt = $body['pickup_at'] ?? null;
    $catatan = $body['catatan'] ?? null;
    $paymentMethod = $body['payment_method'] ?? 'qris';

    if (!is_string($paymentMethod) || !in_array($paymentMethod, ['qris', 'cash'], true)) {
        json_error('Metode pembayaran tidak valid');
    }

    if (!is_array($items) || count($items) === 0) json_error('Pesanan harus berisi minimal 1 menu');
    if (count($items) > 10) json_error('Maksimal 10 item dalam satu pesanan');

    foreach ($items as $item) {
        $menuId = is_array($item) ? ($item['menu_id'] ?? null) : null;
        $quantity = is_array($item) ? ($item['quantity'] ?? null) : null;

        if (!is_int($menuId) && !is_float($menuId)) json_error('menu_id harus berupa angka');
        if (!is_int($quantity) && !is_float($quantity)) json_error('quantity harus berupa angka');
        if ($quantity < 1 || $quantity > 50) json_error('quantity harus antara 1 sampai 50');
    }

    if (!$pickupAt) json_error('Waktu pengambilan wajib diisi');
    $pickupTs = is_string($pickupAt) ? strtotime($pickupAt) : false;
    if ($pickupTs === false) json_error('Format waktu pengambilan tidak valid');

    // Waktu ambil harus masuk akal: tidak di masa lalu, maksimal 7 hari, dan dalam jam buka.
    if ($pickupTs < time() + 5 * 60) json_error('Waktu pengambilan sudah lewat');
    if ($pickupTs > time() + 7 * 86400) json_error('Waktu pengambilan maksimal 7 hari ke depan');
    $pickupTime = date('H:i', $pickupTs);
    $dayOfWeek = (int)date('N', $pickupTs);
    try {
        $hours = kantin_hours_for_day(get_db_connection(), $dayOfWeek);
    } catch (PDOException $e) {
        error_log('Opening hours check error: ' . $e->getMessage());
        json_error('Gagal memeriksa jam operasional', 500);
    }
    if ($hours === null) json_error('Jam operasional kantin belum diatur', 409);
    if (!$hours['is_open']) {
        $dayNames = kantin_day_names();
        json_error('Kantin tutup pada hari ' . ($dayNames[$dayOfWeek] ?? 'itu'));
    }
    if ($pickupTime < $hours['open_time'] || $pickupTime > $hours['close_time']) {
        json_error('Jam pengambilan di luar jam operasional (' . $hours['open_time'] . '-' . $hours['close_time'] . ')');
    }

    if ($catatan !== null && !is_string($catatan)) json_error('Catatan harus berupa teks');
    if (is_string($catatan) && strlen($catatan) > 255) json_error('Catatan maksimal 255 karakter');

    try {
        $pdo = get_db_connection();
        $pending = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'pending' AND expires_at > NOW() AND COALESCE(payment_type, '') <> 'cash'");
        $pending->execute([$me['id']]);

        if ((int)$pending->fetchColumn() >= $config['order_pending_limit']) {
            json_error('Masih ada pesanan menunggu pembayaran. Selesaikan atau tunggu 10 menit.', 409);
        }
    } catch (PDOException $e) {
        error_log('Pending order check error: ' . $e->getMessage());
        json_error('Gagal membuat pesanan', 500);
    }

    try {
        $order = create_order_tx($me['id'], $items, date('Y-m-d H:i:s', $pickupTs), $catatan, $paymentMethod);

        // Beri tahu admin setelah respons terkirim: order demo langsung paid,
        // order tunai tetap pending sampai dikonfirmasi di kasir.
        if (($order['status'] ?? '') === 'paid' || ($order['payment_type'] ?? '') === 'cash') {
            register_shutdown_function(fn() => push_notify_new_order($order));
        }

        json_response($order, 201);
    } catch (PDOException $e) {
        error_log('Create order database error: ' . $e->getMessage());
        json_error('Gagal membuat pesanan', 500);
    } catch (Exception $e) {
        error_log('Create order error: ' . $e->getMessage());
        json_error($e->getMessage() ?: 'Gagal membuat pesanan', 400);
    }
}

function orders_my() {
    $me = authenticate();
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$me['id']]);
        json_response(attach_order_items($pdo, $stmt->fetchAll()));
    } catch (PDOException $e) {
        json_error('Gagal mengambil riwayat pesanan', 500);
    }
}

function orders_detail($id) {
    $me = authenticate();
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $me['id']]);
        $order = $stmt->fetch();
        if (!$order) json_error('Pesanan tidak ditemukan', 404);

        json_response(attach_order_items($pdo, [$order])[0]);
    } catch (PDOException $e) {
        json_error('Gagal mengambil detail pesanan', 500);
    }
}
