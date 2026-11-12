<?php
require_once 'config/Database.php';
require_once 'models/Turno.php';

class TurnoController {
    private $db;
    private $turnoModel;

    public function __construct() {
        $database = Database::getInstance();
        $this->db = $database->getConnection();
        $this->turnoModel = new Turno($this->db);
    }

    // Retorna el estado en formato JSON
    public function handleApi() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(["error" => "No autorizado"]);
            exit;
        }

        $event_id = $_SESSION['current_event_id'] ?? null;
        if (!$event_id) {
            echo json_encode(["error" => "No hay evento seleccionado"]);
            exit;
        }

        $accion = $_GET['api_action'] ?? '';
        $user_id = $_SESSION['user_id'];
        $role_id = $_SESSION['role_id'];

        switch ($accion) {
            // -- ACCIONES DE ASISTENTE --
            case 'estado_asistente':
                // Devuelve si estoy en la fila y en qué posición
                $posicion = $this->turnoModel->getMiPosicion($event_id, $user_id);
                echo json_encode([
                    "en_fila" => ($posicion !== null),
                    "posicion" => $posicion
                ]);
                break;

            case 'pedir_palabra':
                $result = $this->turnoModel->pedirPalabra($event_id, $user_id);
                echo json_encode($result);
                break;

            // -- ACCIONES DE MODERADOR --
            case 'estado_fila':
                // Devuelve la lista completa para el moderador
                if ($role_id > 2) { echo json_encode(["error" => "No autorizado"]); exit; }
                $fila = $this->turnoModel->getFilaCompleta($event_id);
                echo json_encode(["fila" => $fila]);
                break;

            case 'pasar_siguiente':
                if ($role_id > 2) { echo json_encode(["error" => "No autorizado"]); exit; }
                $exito = $this->turnoModel->pasarSiguiente($event_id);
                echo json_encode(["success" => $exito]);
                break;
                
            case 'eliminar_turno':
                if ($role_id > 2) { echo json_encode(["error" => "No autorizado"]); exit; }
                $turno_id = $_POST['turno_id'] ?? 0;
                $exito = $this->turnoModel->eliminarTurno($event_id, $turno_id);
                echo json_encode(["success" => $exito]);
                break;

            case 'limpiar_fila':
                if ($role_id > 2) { echo json_encode(["error" => "No autorizado"]); exit; }
                $exito = $this->turnoModel->limpiarFila($event_id);
                echo json_encode(["success" => $exito]);
                break;

            default:
                echo json_encode(["error" => "Acción no válida"]);
                break;
        }
        exit; // Siempre termina la ejecución del script en las APIs
    }
}
?>
