<?php
class Event {
    private $conn;
    private $table_name = "events";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function createEvent($name, $description, $creator_id, $event_code, $event_password, $start_time, $end_time, $f1_active, $f1_name, $f2_active, $f2_name) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (name, description, creator_id, event_code, event_password, start_time, end_time, field1_active, field1_name, field2_active, field2_name) 
                  VALUES (:name, :description, :creator_id, :event_code, :event_password, :start_time, :end_time, :f1_active, :f1_name, :f2_active, :f2_name)";
        
        $stmt = $this->conn->prepare($query);

        $f1_active = $f1_active ? 1 : 0;
        $f2_active = $f2_active ? 1 : 0;
        if (!empty($event_password)) {
            $event_password = password_hash($event_password, PASSWORD_DEFAULT);
        } else {
            $event_password = null;
        }

        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":description", $description);
        $stmt->bindParam(":creator_id", $creator_id);
        $stmt->bindParam(":event_code", $event_code);
        $stmt->bindParam(":event_password", $event_password);
        $stmt->bindParam(":start_time", $start_time);
        $stmt->bindParam(":end_time", $end_time);
        $stmt->bindParam(":f1_active", $f1_active);
        $stmt->bindParam(":f1_name", $f1_name);
        $stmt->bindParam(":f2_active", $f2_active);
        $stmt->bindParam(":f2_name", $f2_name);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function getEventById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($event) {
            $event = $this->updateDynamicStatus($event);
        }
        return $event;
    }

    public function getEventByCode($code) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE event_code = :code AND is_active = TRUE LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":code", $code);
        $stmt->execute();
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($event) {
            $event = $this->updateDynamicStatus($event);
        }
        return $event;
    }

    private function updateDynamicStatus($event) {
        // Only update if it's not manually concluded
        if ($event['status'] === 'concluido') return $event;

        $now = new DateTime();
        $start = new DateTime($event['start_time']);
        $end = new DateTime($event['end_time']);
        $newStatus = $event['status'];

        if ($now < $start) {
            $newStatus = 'proximamente';
        } else if ($now >= $start && $now <= $end) {
            $newStatus = 'en_curso';
        } else if ($now > $end) {
            $newStatus = 'concluido';
        }

        if ($newStatus !== $event['status']) {
            $this->updateStatus($event['id'], $newStatus);
            $event['status'] = $newStatus;
        }

        return $event;
    }

    public function updateStatus($event_id, $status) {
        $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $event_id);
        return $stmt->execute();
    }

    public function deleteEventSoft($event_id) {
        $query = "UPDATE " . $this->table_name . " SET is_active = FALSE WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $event_id);
        return $stmt->execute();
    }

    public function getActiveEventsForUser($user_id, $role_id) {
        // Admin gets all active (maybe even inactive, but let's say all)
        if ($role_id == 1) {
            $query = "SELECT * FROM " . $this->table_name . " ORDER BY start_time DESC";
            $stmt = $this->conn->prepare($query);
        } else if ($role_id == 2) {
            // Moderator gets the ones they created or joined
            $query = "SELECT e.* FROM " . $this->table_name . " e 
                      LEFT JOIN event_participants ep ON e.id = ep.event_id 
                      WHERE e.is_active = TRUE AND (e.creator_id = :user_id OR ep.user_id = :user_id) 
                      GROUP BY e.id ORDER BY e.start_time DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
        } else {
            // Attendee gets the ones they joined
            $query = "SELECT e.* FROM " . $this->table_name . " e 
                      JOIN event_participants ep ON e.id = ep.event_id 
                      WHERE e.is_active = TRUE AND ep.user_id = :user_id 
                      ORDER BY e.start_time DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
        }
        
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Update dynamic status for all
        foreach ($events as &$event) {
            $event = $this->updateDynamicStatus($event);
        }
        return $events;
    }

    public function joinEvent($event_id, $user_id, $role_in_event) {
        $query = "INSERT INTO event_participants (event_id, user_id, role_in_event) VALUES (:event_id, :user_id, :role_in_event) ON CONFLICT DO NOTHING";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":event_id", $event_id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":role_in_event", $role_in_event);
        return $stmt->execute();
    }

    public function verifyPassword($event, $password) {
        if (empty($event['event_password'])) return true;
        return password_verify($password, $event['event_password']);
    }
}
?>
