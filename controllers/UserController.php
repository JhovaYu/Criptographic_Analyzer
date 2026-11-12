<?php
require_once 'config/Database.php';
require_once 'models/User.php';
require_once 'models/Event.php';

class UserController {
    private $db;
    private $userModel;
    private $eventModel;

    public function __construct() {
        $database = Database::getInstance();
        $this->db = $database->getConnection();
        $this->userModel = new User($this->db);
        $this->eventModel = new Event($this->db);
    }

    public function handleLogin() {
        if (isset($_SESSION['user_id'])) {
            if ($_SESSION['role_id'] == 1) {
                header("Location: index.php?action=admin_dashboard");
            } else {
                header("Location: index.php?action=dashboard");
            }
            exit;
        }

        $message = '';
        $messageType = '';

        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
            $username = trim($_POST['username']);
            $password = $_POST['password'];

            if (empty($username) || empty($password)) {
                $message = "Por favor, llena todos los campos.";
                $messageType = "error";
            } else {
                $session_id = session_id(); 
                $result = $this->userModel->login($username, $password, $session_id);
                
                if ($result['success']) {
                    $_SESSION['user_id'] = $result['user']['id'];
                    $_SESSION['username'] = $result['user']['username'];
                    $_SESSION['role_id'] = $result['user']['role_id'];
                    $_SESSION['name'] = $result['user']['name'];
                    
                    if ($result['user']['role_id'] == 1) {
                        header("Location: index.php?action=admin_dashboard");
                    } else {
                        header("Location: index.php?action=dashboard");
                    }
                    exit;
                } else {
                    $message = $result['message'];
                    $messageType = "error";
                }
            }
        }

        require_once 'views/login.php';
    }

    public function handleRegister() {
        if (isset($_SESSION['user_id'])) {
            if ($_SESSION['role_id'] == 1) {
                header("Location: index.php?action=admin_dashboard");
            } else {
                header("Location: index.php?action=dashboard");
            }
            exit;
        }

        $message = '';
        $messageType = '';

        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
            $username = trim($_POST['username']);
            $password = $_POST['password'];
            $name = trim($_POST['name'] ?? '');
            $custom_field_1 = trim($_POST['custom_field_1'] ?? '');
            $custom_field_2 = trim($_POST['custom_field_2'] ?? '');
            
            // Por seguridad, todo registro público es Asistente (Rol 3) por defecto
            $role_id = 3;

            if (empty($username) || empty($password) || empty($name)) {
                $message = "Por favor, llena todos los campos requeridos.";
                $messageType = "error";
            } else {
                $result = $this->userModel->register($username, $password, $role_id, $name, $custom_field_1, $custom_field_2);
                $message = $result['message'];
                $messageType = $result['success'] ? "success" : "error";
            }
        }

        require_once 'views/register.php';
    }

    public function handleLogout() {
        if (isset($_SESSION['user_id'])) {
            $this->userModel->logout($_SESSION['user_id']);
        }
        
        session_unset();
        session_destroy();
        
        header("Location: index.php?action=login");
        exit;
    }

    public function handleDashboard() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?action=login");
            exit;
        }

        $role_id = $_SESSION['role_id'];
        $user_id = $_SESSION['user_id'];
        
        // Unset event context so we are cleanly in the lobby
        unset($_SESSION['current_event_id']);

        if ($role_id == 1) {
            // Redirigir al dashboard del administrador
            header("Location: index.php?action=admin_dashboard");
            exit;
        } else {
            // Moderadores y Asistentes
            $events = $this->eventModel->getActiveEventsForUser($user_id, $role_id);
            $lobby_error = $_SESSION['lobby_error'] ?? '';
            unset($_SESSION['lobby_error']);
            
            require_once 'views/lobby.php';
        }
    }
}
?>
