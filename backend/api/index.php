<?php
// Front controller: semua request /api/* masuk ke sini (lihat .htaccess).

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../rate_limit.php';
require_once __DIR__ . '/../services/order_expiry.php';
require_once __DIR__ . '/../controllers/auth.php';
require_once __DIR__ . '/../controllers/menus.php';
require_once __DIR__ . '/../controllers/orders.php';
require_once __DIR__ . '/../controllers/payments.php';
require_once __DIR__ . '/../controllers/admin_menus.php';
require_once __DIR__ . '/../controllers/admin_orders.php';
require_once __DIR__ . '/../controllers/uploads.php';
require_once __DIR__ . '/../controllers/push.php';
require_once __DIR__ . '/../controllers/kantin.php';

set_cors_headers();

$method = get_request_method();
$path = get_request_path();

// Buang prefix subdirektori kalau ada, ambil mulai dari "/api"
$apiPos = strpos($path, '/api');
if ($apiPos !== false) $path = substr($path, $apiPos);

$id = '([0-9]+)';

$routes = [
    ['GET',    '#^/api/health$#',                      fn() => json_response(['status' => 'ok', 'timestamp' => date('c')])],
    ['POST',   '#^/api/auth/register$#',               'auth_register'],
    ['POST',   '#^/api/auth/login$#',                  'auth_login'],
    ['GET',    '#^/api/auth/me$#',                     'auth_me'],
    ['GET',    '#^/api/menus$#',                       'get_menus'],
    ['GET',    '#^/api/kantin/profile$#',               'kantin_profile_get'],
    ['GET',    '#^/api/kantin/hours$#',                 'kantin_hours_get'],
    ['GET',    '#^/api/orders/me$#',                   'orders_my'],
    ['POST',   '#^/api/orders$#',                      'orders_create'],
    ['GET',    "#^/api/orders/$id$#",                  'orders_detail'],
    ['POST',   '#^/api/payments/webhook$#',            'payments_webhook'],
    ['GET',    '#^/api/payments/config$#',             'payments_config'],
    ['GET',    '#^/api/push/public-key$#',             'push_public_key'],
    ['POST',   '#^/api/push/subscribe$#',              'push_subscribe'],
    ['POST',   '#^/api/push/unsubscribe$#',            'push_unsubscribe'],
    ['POST',   '#^/api/admin/menus$#',                 'admin_menu_create'],
    ['GET',    '#^/api/admin/menus$#',                  'admin_menus_all'],
    ['POST',   '#^/api/admin/menus/upload$#',          'admin_menu_upload'],
    ['PATCH',  '#^/api/admin/kantin/profile$#',        'admin_kantin_profile_update'],
    ['PATCH',  '#^/api/admin/kantin/hours$#',          'admin_kantin_hours_update'],
    ['GET',    "#^/api/admin/menus/$id$#",             'admin_menu_detail'],
    ['PATCH',  "#^/api/admin/menus/$id$#",             'admin_menu_update'],
    ['PATCH',  "#^/api/admin/menus/$id/stock$#",       'admin_menu_stock'],
    ['DELETE', "#^/api/admin/menus/$id$#",             'admin_menu_delete'],
    ['GET',    '#^/api/uploads/([A-Za-z0-9._-]+)$#',   'upload_serve'],
    ['GET',    '#^/api/admin/orders$#',                'admin_orders_all'],
    ['PATCH',  "#^/api/admin/orders/$id/status$#",     'admin_order_status'],
];

foreach ($routes as [$routeMethod, $pattern, $handler]) {
    if ($method === $routeMethod && preg_match($pattern, $path, $matches)) {
        // Sweep pesanan kedaluwarsa di sela request (tanpa cron)
        maybe_sweep_expired_orders();
        $handler(...array_slice($matches, 1));
        exit;
    }
}

json_error("Endpoint {$method} {$path} tidak ditemukan", 404);
