<?php
// Autoloader básico
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use Helpers\JwtHelper;
use Middleware\AuthMiddleware;
use Config\Database;

// Manejo seguro de CORS (Restringir dominios autorizados en producción)
$allowed_origins = [
    "http://localhost:3000",
    "http://127.0.0.1:3000",
    "http://127.0.0.1:8000",
    "http://localhost:8000",
    "http://192.168.188.10",
    "https://192.168.188.10"
]; // Frontend
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $origin);
}
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Para soportar subcarpetas en MAMP (ej. /Caso-Practico-2-Confidencialidad/api/login)
// Extraemos la parte final de la ruta
$route = '';
if (preg_match('/\/api(\/.*)$/', $uri, $matches)) {
    $route = $matches[1];
} else {
    $route = $uri; // Fallback
}

$authController = new \Controllers\AuthController();

if ($route === '/login' && $method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    $authController->login($data);

} elseif ($route === '/register' && $method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    $authController->register($data);

} elseif ($route === '/protected' && $method === 'GET') {
    $userPayload = AuthMiddleware::authenticate();
    
    http_response_code(200);
    echo json_encode([
        "message" => "Acceso concedido a ruta protegida.",
        "user" => $userPayload
    ]);

} elseif ($route === '/password/change' && $method === 'POST') {
    $userPayload = AuthMiddleware::authenticate();
    $data = json_decode(file_get_contents("php://input"));
    $authController->changePassword($userPayload['id'], $data);

} elseif ($route === '/password/forgot' && $method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    $authController->forgotPassword($data);

} elseif ($route === '/password/reset' && $method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    $authController->resetPassword($data);

// --- RUTAS DE DOCUMENTOS CONFIDENCIALES ---
} elseif ($route === '/content' && $method === 'GET') {
    $userPayload = AuthMiddleware::authenticate();
    AuthMiddleware::authorize($userPayload, 'read_content');
    
    require_once __DIR__ . '/Controllers/ContentController.php';
    $contentController = new \Controllers\ContentController();
    $contentController->getDocuments($userPayload['id']);

} elseif ($route === '/content' && $method === 'POST') {
    $userPayload = AuthMiddleware::authenticate();
    AuthMiddleware::authorize($userPayload, 'write_content');
    
    $data = json_decode(file_get_contents("php://input"));
    require_once __DIR__ . '/Controllers/ContentController.php';
    $contentController = new \Controllers\ContentController();
    $contentController->createDocument($userPayload['id'], $data);

} elseif (preg_match('/^\/content\/(\d+)$/', $route, $matches) && $method === 'PUT') {
    $userPayload = AuthMiddleware::authenticate();
    AuthMiddleware::authorize($userPayload, 'write_content');
    
    $data = json_decode(file_get_contents("php://input"));
    require_once __DIR__ . '/Controllers/ContentController.php';
    $contentController = new \Controllers\ContentController();
    $contentController->updateDocument($userPayload['id'], $matches[1], $data);

} elseif (preg_match('/^\/content\/(\d+)$/', $route, $matches) && $method === 'DELETE') {
    $userPayload = AuthMiddleware::authenticate();
    AuthMiddleware::authorize($userPayload, 'delete_content');
    
    require_once __DIR__ . '/Controllers/ContentController.php';
    $contentController = new \Controllers\ContentController();
    $contentController->deleteDocument($userPayload['id'], $matches[1]);

// --- RUTAS DE ADMINISTRACIÓN ---
} elseif ($route === '/admin/users' && $method === 'GET') {
    $userPayload = AuthMiddleware::authenticate();
    AuthMiddleware::authorize($userPayload, 'manage_users');
    
    $adminController = new \Controllers\AdminController();
    $adminController->getUsers($userPayload['id']);

} elseif ($route === '/admin/roles/assign' && $method === 'POST') {
    $userPayload = AuthMiddleware::authenticate();
    AuthMiddleware::authorize($userPayload, 'manage_roles');
    
    $data = json_decode(file_get_contents("php://input"));
    $adminController = new \Controllers\AdminController();
    $adminController->assignRole($userPayload['id'], $data);

} elseif ($route === '/admin/roles/revoke' && $method === 'POST') {
    $userPayload = AuthMiddleware::authenticate();
    AuthMiddleware::authorize($userPayload, 'manage_roles');
    
    $data = json_decode(file_get_contents("php://input"));
    $adminController = new \Controllers\AdminController();
    $adminController->revokeRole($userPayload['id'], $data);

} elseif ($route === '/admin/roles' && $method === 'GET') {
    $userPayload = AuthMiddleware::authenticate();
    AuthMiddleware::authorize($userPayload, 'manage_users');
    
    $adminController = new \Controllers\AdminController();
    $adminController->getRoles();

} elseif ($route === '/admin/roles' && $method === 'POST') {
    $userPayload = AuthMiddleware::authenticate();
    AuthMiddleware::authorize($userPayload, 'manage_roles');
    
    $data = json_decode(file_get_contents("php://input"));
    $adminController = new \Controllers\AdminController();
    $adminController->createRole($userPayload['id'], $data);

} elseif (preg_match('/^\/admin\/roles\/(\d+)\/permissions$/', $route, $matches) && $method === 'PUT') {
    $userPayload = AuthMiddleware::authenticate();
    AuthMiddleware::authorize($userPayload, 'manage_roles');
    
    $data = json_decode(file_get_contents("php://input"));
    $adminController = new \Controllers\AdminController();
    $adminController->updateRolePermissions($userPayload['id'], $matches[1], $data);

} elseif ($route === '/admin/audit-logs' && $method === 'GET') {
    $userPayload = AuthMiddleware::authenticate();
    // Supongamos que manage_roles o manage_users también les da acceso a la auditoría
    AuthMiddleware::authorize($userPayload, 'manage_users');
    
    $adminController = new \Controllers\AdminController();
    $adminController->getAuditLogs($userPayload['id']);

} else {
    http_response_code(404);
    echo json_encode(["message" => "Ruta no encontrada."]);
}
