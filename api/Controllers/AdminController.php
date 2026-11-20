<?php
namespace Controllers;

use Config\Database;
use PDO;

class AdminController {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    private function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    private function logAudit($userId, $action, $details = null) {
        $ip = $this->getClientIp();
        $query = "INSERT INTO audit_logs (user_id, action, ip_address, details) VALUES (:user_id, :action, :ip_address, :details)";
        $stmt = $this->conn->prepare($query);
        $detailsJson = $details ? json_encode($details) : null;
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':ip_address', $ip);
        $stmt->bindParam(':details', $detailsJson);
        $stmt->execute();
    }

    // 1. Ver todos los usuarios registrados
    public function getUsers($adminUserId) {
        $query = "
            SELECT u.id, u.name, u.email, u.created_at, 
                   json_agg(r.name) as roles 
            FROM users u
            LEFT JOIN user_roles ur ON u.id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.id
            GROUP BY u.id
            ORDER BY u.id ASC
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $users = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // json_agg returns a JSON string, or "[null]" if no roles
            $rolesArr = json_decode($row['roles'], true);
            // Filtrar nulos si un usuario no tiene roles
            $row['roles'] = array_values(array_filter($rolesArr, function($val) { return $val !== null; }));
            $users[] = $row;
        }

        $this->logAudit($adminUserId, "Consultó la lista de usuarios");

        http_response_code(200);
        echo json_encode(["data" => $users]);
    }

    // 2. Asignar rol a usuario
    public function assignRole($adminUserId, $data) {
        $userId = $data->user_id ?? null;
        $roleId = $data->role_id ?? null;

        if (!$userId || !$roleId) {
            http_response_code(400);
            echo json_encode(["message" => "user_id y role_id son requeridos."]);
            return;
        }

        // Verificar si ya tiene el rol
        $checkQuery = "SELECT * FROM user_roles WHERE user_id = :user_id AND role_id = :role_id";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(':user_id', $userId);
        $checkStmt->bindParam(':role_id', $roleId);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            http_response_code(409);
            echo json_encode(["message" => "El usuario ya tiene asignado este rol."]);
            return;
        }

        $query = "INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':role_id', $roleId);

        if ($stmt->execute()) {
            $this->logAudit($adminUserId, "Asignó rol", ["target_user_id" => $userId, "role_id" => $roleId]);
            http_response_code(200);
            echo json_encode(["message" => "Rol asignado correctamente."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al asignar el rol."]);
        }
    }

    // 3. Revocar rol a usuario
    public function revokeRole($adminUserId, $data) {
        $userId = $data->user_id ?? null;
        $roleId = $data->role_id ?? null;

        if (!$userId || !$roleId) {
            http_response_code(400);
            echo json_encode(["message" => "user_id y role_id son requeridos."]);
            return;
        }

        $query = "DELETE FROM user_roles WHERE user_id = :user_id AND role_id = :role_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':role_id', $roleId);

        if ($stmt->execute()) {
            $this->logAudit($adminUserId, "Revocó rol", ["target_user_id" => $userId, "role_id" => $roleId]);
            http_response_code(200);
            echo json_encode(["message" => "Rol revocado correctamente."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al revocar el rol."]);
        }
    }

    // 4. Listar todos los roles con sus permisos
    public function getRoles() {
        $rolesQuery = "
            SELECT r.id, r.name, r.description,
                   COALESCE(json_agg(json_build_object('id', p.id, 'name', p.name, 'description', p.description)) FILTER (WHERE p.id IS NOT NULL), '[]') as permissions
            FROM roles r
            LEFT JOIN role_permissions rp ON r.id = rp.role_id
            LEFT JOIN permissions p ON rp.permission_id = p.id
            GROUP BY r.id
            ORDER BY r.id ASC
        ";
        $stmt = $this->conn->prepare($rolesQuery);
        $stmt->execute();
        $roles = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['permissions'] = json_decode($row['permissions'], true);
            $roles[] = $row;
        }

        $permStmt = $this->conn->query("SELECT id, name, description FROM permissions ORDER BY id ASC");
        $allPermissions = $permStmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode(["data" => $roles, "permissions" => $allPermissions]);
    }

    // 4b. Actualizar permisos de un rol existente
    public function updateRolePermissions($adminUserId, $roleId, $data) {
        $permissions = $data->permissions ?? [];
        if (!is_array($permissions)) {
            http_response_code(400);
            echo json_encode(["message" => "Formato de permisos inválido."]);
            return;
        }

        try {
            $this->conn->beginTransaction();
            $delStmt = $this->conn->prepare("DELETE FROM role_permissions WHERE role_id = :role_id");
            $delStmt->execute([':role_id' => $roleId]);

            if (!empty($permissions)) {
                $insStmt = $this->conn->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)");
                foreach ($permissions as $permId) {
                    $insStmt->execute([':role_id' => $roleId, ':permission_id' => (int)$permId]);
                }
            }
            $this->conn->commit();
            $this->logAudit($adminUserId, "Actualizó permisos de rol", ["role_id" => (int)$roleId, "permissions" => $permissions]);
            http_response_code(200);
            echo json_encode(["message" => "Permisos del rol actualizados correctamente."]);
        } catch (\Exception $e) {
            $this->conn->rollBack();
            http_response_code(500);
            echo json_encode(["message" => "Error al actualizar los permisos del rol."]);
        }
    }

    // 4c. Crear nuevo rol y asignarle permisos
    public function createRole($adminUserId, $data) {
        $name = htmlspecialchars(strip_tags($data->name ?? ''));
        $description = htmlspecialchars(strip_tags($data->description ?? ''));
        $permissions = $data->permissions ?? []; // Array de IDs de permisos

        if (empty($name)) {
            http_response_code(400);
            echo json_encode(["message" => "El nombre del rol es requerido."]);
            return;
        }

        try {
            $this->conn->beginTransaction();

            $query = "INSERT INTO roles (name, description) VALUES (:name, :description) RETURNING id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $newRoleId = $row['id'];

            if (!empty($permissions) && is_array($permissions)) {
                $permQuery = "INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)";
                $permStmt = $this->conn->prepare($permQuery);
                foreach ($permissions as $permId) {
                    $permStmt->bindValue(':role_id', $newRoleId);
                    $permStmt->bindValue(':permission_id', $permId);
                    $permStmt->execute();
                }
            }

            $this->conn->commit();
            $this->logAudit($adminUserId, "Creó nuevo rol", ["role_name" => $name, "permissions" => $permissions]);

            http_response_code(201);
            echo json_encode(["message" => "Rol creado exitosamente.", "role_id" => $newRoleId]);

        } catch (\Exception $e) {
            $this->conn->rollBack();
            http_response_code(500);
            echo json_encode(["message" => "Error al crear el rol."]);
        }
    }

    // 5. Consultar registro de auditoría
    public function getAuditLogs($adminUserId) {
        $query = "
            SELECT a.id, a.action, a.ip_address, a.details, a.created_at, u.name as user_name
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.id
            ORDER BY a.created_at DESC
            LIMIT 100
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        $logs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['details']) {
                $row['details'] = json_decode($row['details']);
            }
            $logs[] = $row;
        }

        $this->logAudit($adminUserId, "Consultó registros de auditoría");

        http_response_code(200);
        echo json_encode(["data" => $logs]);
    }
}
