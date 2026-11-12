<?php
class User {
    private $conn;
    private $table_name = "users";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Método para registrar un usuario con campos dinámicos
    public function register($username, $password, $role_id, $name, $custom_field_1 = '', $custom_field_2 = '') {
        if ($this->userExists($username)) {
            return ["success" => false, "message" => "El nombre de usuario ya está en uso."];
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO " . $this->table_name . " (username, password_hash, role_id, name, custom_field_1, custom_field_2) VALUES (:username, :password_hash, :role_id, :name, :cf1, :cf2)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":password_hash", $hashed_password);
        $stmt->bindParam(":role_id", $role_id);
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":cf1", $custom_field_1);
        $stmt->bindParam(":cf2", $custom_field_2);

        try {
            if ($stmt->execute()) {
                return ["success" => true, "message" => "Usuario registrado exitosamente."];
            } else {
                return ["success" => false, "message" => "No se pudo registrar el usuario."];
            }
        } catch (PDOException $e) {
             return ["success" => false, "message" => "Error de base de datos: " . $e->getMessage()];
        }
    }

    // Método para iniciar sesión con restricción de única sesión
    public function login($username, $password, $session_id) {
        $query = "SELECT id, username, password_hash, role_id, session_token FROM " . $this->table_name . " WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $row['password_hash'])) {
                if (!empty($row['session_token']) && $row['session_token'] !== $session_id) {
                    return ["success" => false, "message" => "Este usuario ya tiene una sesión abierta en otro dispositivo."];
                }

                $this->updateSessionToken($row['id'], $session_id);

                unset($row['password_hash']);
                return ["success" => true, "message" => "Inicio de sesión exitoso.", "user" => $row];
            } else {
                return ["success" => false, "message" => "Contraseña incorrecta."];
            }
        } else {
            return ["success" => false, "message" => "El usuario no existe."];
        }
    }

    // Cerrar sesión
    public function logout($user_id) {
        $this->updateSessionToken($user_id, null);
        return ["success" => true, "message" => "Sesión cerrada correctamente."];
    }

    // Obtener toda la información de un usuario
    public function getUserById($user_id) {
        $query = "SELECT id, username, role_id, name, custom_field_1, custom_field_2, es_suplente FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $user_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Actualizar información del asistente
    public function updateInfo($user_id, $name, $custom_field_1, $custom_field_2, $es_suplente) {
        $query = "UPDATE " . $this->table_name . " 
                  SET name = :name, custom_field_1 = :cf1, custom_field_2 = :cf2, es_suplente = :es_suplente 
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        $es_suplente_val = $es_suplente ? 1 : 0;
        
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":cf1", $custom_field_1);
        $stmt->bindParam(":cf2", $custom_field_2);
        $stmt->bindParam(":es_suplente", $es_suplente_val);
        $stmt->bindParam(":id", $user_id);
        
        return $stmt->execute();
    }

    private function updateSessionToken($user_id, $token) {
        $query = "UPDATE " . $this->table_name . " SET session_token = :token WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->bindParam(":id", $user_id);
        $stmt->execute();
    }

    private function userExists($username) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
}
?>
