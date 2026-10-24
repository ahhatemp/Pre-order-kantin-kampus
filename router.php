<?php
// Router untuk server lokal satu port:
//   php -S 0.0.0.0:8000 router.php
// Frontend di /frontend/ dan API di /backend/api/ (origin sama, aman diakses dari HP).

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

if ($path === '/') {
    header('Location: /frontend/index.html');
    exit;
}

if (preg_match('#^/(?:backend/)?api(?:/|$)#', $path)) {
    require __DIR__ . '/backend/api/index.php';
    return true;
}

if (preg_match('#^/frontend(?:/|$)#', $path)) {
    if (str_ends_with($path, '.php')) {
        http_response_code(404);
        return true;
    }
    if (is_dir(__DIR__ . $path)) {
        header('Location: ' . rtrim($path, '/') . '/index.html');
        exit;
    }
    return false;
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo "Not found";
return true;
