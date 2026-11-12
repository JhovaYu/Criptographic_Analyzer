<?php
session_start();

require_once 'config/Database.php';

// Verificación silenciosa
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
} catch (Exception $e) {
    die("Error de base de datos. Asegúrate de ejecutar MAMP y PostgreSQL.");
}

$action = $_GET['action'] ?? 'login';

// Si es una petición a la API de Turnos
if ($action === 'api') {
    require_once 'controllers/TurnoController.php';
    $apiController = new TurnoController();
    $apiController->handleApi();
    exit;
}

if (in_array($action, ['event', 'create_event', 'join_event', 'delete_event'])) {
    require_once 'controllers/EventController.php';
    $eventController = new EventController();
    switch ($action) {
        case 'event': $eventController->handleEventView(); break;
        case 'create_event': $eventController->handleCreateEvent(); break;
        case 'join_event': $eventController->handleJoinEvent(); break;
        case 'delete_event': $eventController->handleDeleteEvent(); break;
    }
    exit;
}

if (in_array($action, ['admin_dashboard', 'admin_delete_event', 'admin_restore_event'])) {
    require_once 'controllers/AdminController.php';
    $adminController = new AdminController();
    switch ($action) {
        case 'admin_dashboard': $adminController->handleDashboard(); break;
        case 'admin_delete_event': $adminController->handleDeleteEventForce(); break;
        case 'admin_restore_event': $adminController->handleRestoreEvent(); break;
    }
    exit;
}

// Requerir el controlador principal de Vistas
require_once 'controllers/UserController.php';
$controller = new UserController();

switch ($action) {
    case 'register':
        $controller->handleRegister();
        break;
    case 'logout':
        $controller->handleLogout();
        break;
    case 'dashboard':
        $controller->handleDashboard();
        break;
    case 'login':
    default:
        $controller->handleLogin();
        break;
}
?>
