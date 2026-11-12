<?php
require_once 'config/Database.php';
require_once 'models/Event.php';
require_once 'models/User.php';

class EventController {
    private $db;
    private $eventModel;

    public function __construct() {
        $database = Database::getInstance();
        $this->db = $database->getConnection();
        $this->eventModel = new Event($this->db);
    }

    public function handleEventView() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?action=login");
            exit;
        }
        $event_id = $_GET['id'] ?? null;
        if (!$event_id) {
            header("Location: index.php?action=dashboard");
            exit;
        }

        $event = $this->eventModel->getEventById($event_id);
        if (!$event || (!$event['is_active'] && $_SESSION['role_id'] != 1)) {
            header("Location: index.php?action=dashboard");
            exit;
        }

        // Pass event down to the specific panel
        $role_id = $_SESSION['role_id'];
        $user_id = $_SESSION['user_id'];
        
        $config = [
            'event_name' => $event['name'],
            'field1_active' => $event['field1_active'],
            'field1_name' => $event['field1_name'],
            'field2_active' => $event['field2_active'],
            'field2_name' => $event['field2_name']
        ];

        $_SESSION['current_event_id'] = $event['id'];

        if ($role_id == 1 || $role_id == 2) {
            // El administrador y el moderador ven el panel de moderador para gestionar la fila
            require_once 'views/panel_moderador.php';
        } else {
            $userModel = new User($this->db);
            $message = '';
            $messageType = '';

            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
                $name = trim($_POST['name']);
                $cf1 = trim($_POST['custom_field_1'] ?? '');
                $cf2 = trim($_POST['custom_field_2'] ?? '');
                $es_suplente = isset($_POST['es_suplente']) ? true : false;

                if (!empty($name)) {
                    if ($userModel->updateInfo($user_id, $name, $cf1, $cf2, $es_suplente)) {
                        $message = "Información actualizada correctamente.";
                        $messageType = "success";
                        $_SESSION['name'] = $name;
                    } else {
                        $message = "Error al actualizar la información.";
                        $messageType = "error";
                    }
                }
            }

            $user_info = $userModel->getUserById($user_id);
            require_once 'views/panel_asistente.php';
        }
    }

    public function handleCreateEvent() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) { // Solo moderadores
            header("Location: index.php?action=dashboard");
            exit;
        }

        $message = "";
        $messageType = "";

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $name = trim($_POST['name']);
            $desc = trim($_POST['description']);
            
            // Generar código aleatorio (ej. EVT-ABCD12)
            $code = 'EVT-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
            
            $pass = $_POST['event_password'];
            $start = $_POST['start_time'];
            $end = $_POST['end_time'];
            
            $f1_a = isset($_POST['field1_active']);
            $f1_n = trim($_POST['field1_name']);
            $f2_a = isset($_POST['field2_active']);
            $f2_n = trim($_POST['field2_name']);

            if (empty($name) || empty($start) || empty($end)) {
                $message = "Campos obligatorios incompletos.";
                $messageType = "error";
            } else {
                $event_id = $this->eventModel->createEvent($name, $desc, $_SESSION['user_id'], $code, $pass, $start, $end, $f1_a, $f1_n, $f2_a, $f2_n);
                if ($event_id) {
                    $this->eventModel->joinEvent($event_id, $_SESSION['user_id'], 'Moderador');
                    header("Location: index.php?action=dashboard");
                    exit;
                } else {
                    $message = "Error al crear el evento en la base de datos.";
                    $messageType = "error";
                }
            }
        }

        require_once 'views/crear_evento.php';
    }

    public function handleJoinEvent() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?action=login");
            exit;
        }

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $code = trim($_POST['event_code']);
            $pass = $_POST['event_password'] ?? '';

            $event = $this->eventModel->getEventByCode($code);
            if ($event) {
                if ($event['status'] === 'concluido') {
                    $_SESSION['lobby_error'] = "El evento ha concluido.";
                } else if ($this->eventModel->verifyPassword($event, $pass)) {
                    $role_name = ($_SESSION['role_id'] == 2) ? 'Moderador' : 'Asistente';
                    $this->eventModel->joinEvent($event['id'], $_SESSION['user_id'], $role_name);
                    header("Location: index.php?action=event&id=" . $event['id']);
                    exit;
                } else {
                    $_SESSION['lobby_error'] = "Contraseña incorrecta.";
                }
            } else {
                $_SESSION['lobby_error'] = "Código de evento no encontrado.";
            }
        }
        header("Location: index.php?action=dashboard");
        exit;
    }

    public function handleDeleteEvent() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
            header("Location: index.php?action=dashboard");
            exit;
        }
        $event_id = $_GET['id'] ?? null;
        if ($event_id) {
            $this->eventModel->deleteEventSoft($event_id);
        }
        header("Location: index.php?action=dashboard");
        exit;
    }
}
?>
