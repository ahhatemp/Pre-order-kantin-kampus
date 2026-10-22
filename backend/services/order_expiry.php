<?php
require_once __DIR__ . '/../db.php';

// Batalkan pesanan pending yang lewat expires_at + kembalikan stoknya.
function sweep_expired_orders() {
    $pdo = get_db_connection();

    try {
        $pdo->beginTransaction();

        $expired = $pdo->query("SELECT id FROM orders WHERE status = 'pending' AND expires_at < NOW() FOR UPDATE")->fetchAll();
        if (!$expired) {
            $pdo->commit();
            return 0;
        }

        $ids = array_map(fn($o) => (int)$o['id'], $expired);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $items = $pdo->prepare("SELECT menu_id, quantity FROM order_items WHERE order_id IN ($placeholders)");
        $items->execute($ids);

        $refund = $pdo->prepare('UPDATE menus SET stok = stok + ? WHERE id = ?');
        foreach ($items->fetchAll() as $item) {
            $refund->execute([(int)$item['quantity'], (int)$item['menu_id']]);
        }

        $update = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id IN ($placeholders)");
        $update->execute($ids);

        $pdo->commit();
        return count($ids);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Sweep expired orders error: ' . $e->getMessage());
        return 0;
    }
}

// PHP tidak punya proses lama seperti setInterval, jadi sweep jalan di sela
// request, paling sering sekali per menit (dikunci lewat mtime file).
function maybe_sweep_expired_orders() {
    $lock = __DIR__ . '/../storage/.sweep.lock';
    if (!is_dir(dirname($lock))) @mkdir(dirname($lock), 0775, true);
    if (file_exists($lock) && time() - filemtime($lock) < 60) return;
    @touch($lock);
    try {
        sweep_expired_orders();
    } catch (Throwable $e) {
        error_log('Sweep expired orders error: ' . $e->getMessage());
    }
}
