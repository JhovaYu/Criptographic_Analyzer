<?php
require_once 'config/Database.php';
require_once 'models/Event.php';
require_once 'models/User.php';

class AdminController {
    private $db;
    private $eventModel;
    private $userModel;

    public function __construct() {
        $database = Database::getInstance();
        $this->db = $database->getConnection();
        $this->eventModel = new Event($this->db);
        $this->userModel = new User($this->db);
    }

    public function handleDashboard() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
            header("Location: index.php?action=login");
            exit;
        }

        // Obtener todos los eventos con información de su creador
        $query = "SELECT e.*, u.name as creator_name, u.username as creator_username 
                  FROM events e 
                  LEFT JOIN users u ON e.creator_id = u.id 
                  ORDER BY e.created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Obtener usuarios
        $queryUsers = "SELECT u.id, u.username, u.name, r.name as role_name, u.created_at 
                       FROM users u 
                       JOIN roles r ON u.role_id = r.id 
                       ORDER BY u.created_at DESC";
        $stmtUsers = $this->db->prepare($queryUsers);
        $stmtUsers->execute();
        $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

        // Calculate dynamic statuses
        foreach ($events as $key => $evt) {
            // Re-using logic from Event model if possible, or just calculate on the fly for the view
            if ($evt['status'] !== 'concluido') {
                $now = new DateTime();
                $start = new DateTime($evt['start_time']);
                $end = new DateTime($evt['end_time']);
                if ($now < $start) {
                    $events[$key]['status_calc'] = 'proximamente';
                } else if ($now >= $start && $now <= $end) {
                    $events[$key]['status_calc'] = 'en_curso';
                } else {
                    $events[$key]['status_calc'] = 'concluido';
                    // Optional: update DB here as well
                    $this->eventModel->updateStatus($evt['id'], 'concluido');
                }
            } else {
                $events[$key]['status_calc'] = 'concluido';
            }
        }

        require_once 'views/panel_admin.php';
    }
    
    // Aquí podrían ir más métodos para delete_user, delete_event force, etc.
    public function handleDeleteEventForce() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) exit;
        $id = $_GET['id'] ?? null;
        if ($id) {
            // Delete participants and turnos first, then event
            $this->db->prepare("DELETE FROM turnos WHERE event_id = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM event_participants WHERE event_id = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM events WHERE id = ?")->execute([$id]);
        }
        header("Location: index.php?action=dashboard");
        exit;
    }
    
    public function handleRestoreEvent() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) exit;
        $id = $_GET['id'] ?? null;
        if ($id) {
            $this->db->prepare("UPDATE events SET is_active = TRUE WHERE id = ?")->execute([$id]);
        }
        header("Location: index.php?action=dashboard");
        exit;
    }
}
?>
