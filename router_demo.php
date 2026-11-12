<?php
// Solo servidor local de demostración: php -S 127.0.0.1:8000 router_demo.php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (strpos($path, '/api/') === 0) {
    require __DIR__ . '/api/index.php';
    return true;
}
// Servir exclusivamente la interfaz pública; nunca .env, SQL o código PHP.
if (strpos($path, '/frontend/') === 0) {
    $file = realpath(__DIR__ . $path);
    $root = realpath(__DIR__ . '/frontend') . DIRECTORY_SEPARATOR;
    if ($file && strpos($file, $root) === 0 && is_file($file) && preg_match('/\.(html|css|js|png|jpg|svg)$/i', $file)) {
        return false;
    }
}
http_response_code(404);
echo 'No encontrado';
