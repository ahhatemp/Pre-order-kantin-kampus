<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/order.php';

// Batalkan pesanan pending yang lewat expires_at + kembalikan stoknya.
function sweep_expired_orders() {
    $pdo = null;

    try {
        $pdo = get_db_connection();
        $pdo->beginTransaction();

        $expired = $pdo->query("SELECT id FROM orders WHERE status = 'pending' AND expires_at < NOW() FOR UPDATE")->fetchAll();
        if (!$expired) {
            $pdo->commit();
            return 0;
        }

        $ids = array_map(fn($o) => (int)$o['id'], $expired);
        refund_order_stock($pdo, $ids);

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $update = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id IN ($placeholders)");
        $update->execute($ids);

        $pdo->commit();
        return count($ids);
    } catch (Throwable $e) {
        if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
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
    sweep_expired_orders();
}
