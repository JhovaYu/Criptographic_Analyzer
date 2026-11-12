<?php
class Turno {
    private $conn;
    private $table_name = "turnos";

    public function __construct($db) {
        $this->conn = $db;
    }

    // El asistente pide la palabra
    public function pedirPalabra($event_id, $user_id) {
        // Verificar si ya está en la fila
        if ($this->getMiTurno($event_id, $user_id)) {
            return ["success" => false, "message" => "Ya estás en la fila."];
        }

        $query = "INSERT INTO " . $this->table_name . " (event_id, user_id, status) VALUES (:event_id, :user_id, 'espera')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":event_id", $event_id);
        $stmt->bindParam(":user_id", $user_id);

        if ($stmt->execute()) {
            return ["success" => true, "message" => "Te has formado en la fila."];
        }
        return ["success" => false, "message" => "Error al pedir turno."];
    }

    // Obtener si el usuario actual tiene un turno activo en espera
    public function getMiTurno($event_id, $user_id) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE event_id = :event_id AND user_id = :user_id AND status = 'espera' LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":event_id", $event_id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obtener la posición en la fila (1, 2, 3...)
    public function getMiPosicion($event_id, $user_id) {
        // Seleccionamos todos los turnos en espera ordenados por fecha
        $query = "SELECT user_id FROM " . $this->table_name . " WHERE event_id = :event_id AND status = 'espera' ORDER BY created_at ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":event_id", $event_id);
        $stmt->execute();
        
        $posicion = 1;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['user_id'] == $user_id) {
                return $posicion;
            }
            $posicion++;
        }
        return null; // No está en la fila
    }

    // Obtener toda la lista de turnos (Para el moderador)
    public function getFilaCompleta($event_id) {
        $query = "SELECT t.id as turno_id, t.created_at, u.id as user_id, u.name, u.custom_field_1, u.custom_field_2, u.es_suplente
                  FROM " . $this->table_name . " t
                  JOIN users u ON t.user_id = u.id
                  WHERE t.event_id = :event_id AND t.status = 'espera'
                  ORDER BY t.created_at ASC";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":event_id", $event_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // El moderador atiende al siguiente (marca como finalizado)
    public function pasarSiguiente($event_id) {
        // Buscar al primero en la fila de ese evento
        $query = "SELECT id FROM " . $this->table_name . " WHERE event_id = :event_id AND status = 'espera' ORDER BY created_at ASC LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":event_id", $event_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $turno_id = $row['id'];
            
            // Actualizar su estado
            $update = "UPDATE " . $this->table_name . " SET status = 'finalizado' WHERE id = :id";
            $stmtUpdate = $this->conn->prepare($update);
            $stmtUpdate->bindParam(":id", $turno_id);
            return $stmtUpdate->execute();
        }
        return false;
    }
    
    // Eliminar a una persona específica de la fila (Moderador)
    public function eliminarTurno($event_id, $turno_id) {
        // Ensure it belongs to the event just in case
        $query = "UPDATE " . $this->table_name . " SET status = 'finalizado' WHERE id = :id AND event_id = :event_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $turno_id);
        $stmt->bindParam(":event_id", $event_id);
        return $stmt->execute();
    }

    // Limpiar toda la fila (Moderador)
    public function limpiarFila($event_id) {
        $query = "UPDATE " . $this->table_name . " SET status = 'finalizado' WHERE event_id = :event_id AND status = 'espera'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":event_id", $event_id);
        return $stmt->execute();
    }
}
?>
