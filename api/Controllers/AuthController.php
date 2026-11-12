<?php
namespace Controllers;

use Config\Database;
use Helpers\JwtHelper;
use PDO;

class AuthController {
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

    public function register($data) {
        $name = htmlspecialchars(strip_tags($data->name ?? ''));
        $email = filter_var($data->email ?? '', FILTER_SANITIZE_EMAIL);
        $password = $data->password ?? '';

        if (empty($name) || empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(["message" => "Todos los campos son requeridos."]);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(["message" => "El formato del correo es inválido."]);
            return;
        }

        // Comprobar si el email ya existe
        $checkQuery = "SELECT id FROM users WHERE email = :email LIMIT 1";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(':email', $email);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            http_response_code(409); // Conflict
            echo json_encode(["message" => "El correo ya está registrado."]);
            return;
        }

        // Hashear contraseña con bcrypt (Requisito de seguridad computacional)
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // Insertar usuario
        $query = "INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password_hash) RETURNING id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $passwordHash);

        if ($stmt->execute()) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $newUserId = $row['id'];
            
            // Asignar rol por defecto (Ejemplo: Usuario Regular = id 3)
            // Buscar el id del rol 'Usuario Regular'
            $roleQuery = "SELECT id FROM roles WHERE name = 'Usuario Regular' LIMIT 1";
            $roleStmt = $this->conn->prepare($roleQuery);
            $roleStmt->execute();
            if ($roleStmt->rowCount() > 0) {
                $roleRow = $roleStmt->fetch(PDO::FETCH_ASSOC);
                $roleId = $roleRow['id'];
                
                $assignRoleQuery = "INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)";
                $assignRoleStmt = $this->conn->prepare($assignRoleQuery);
                $assignRoleStmt->bindParam(':user_id', $newUserId);
                $assignRoleStmt->bindParam(':role_id', $roleId);
                $assignRoleStmt->execute();
            }

            // Registrar en auditoría
            $this->logAudit($newUserId, "Registro de usuario");

            http_response_code(201);
            echo json_encode(["message" => "Usuario registrado exitosamente."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error al registrar el usuario."]);
        }
    }

    public function login($data) {
        $email = filter_var($data->email ?? '', FILTER_SANITIZE_EMAIL);
        $password = $data->password ?? '';

        if (empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(["message" => "Faltan credenciales."]);
            return;
        }

        // Obtener usuario
        $query = "SELECT id, name, email, password_hash, failed_login_attempts, account_locked_until FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verificar si la cuenta está bloqueada temporalmente
            if ($user['account_locked_until'] && strtotime($user['account_locked_until']) > time()) {
                $this->logAudit($user['id'], "Intento de inicio de sesión en cuenta bloqueada");
                http_response_code(429); // Too Many Requests
                echo json_encode(["message" => "Cuenta temporalmente bloqueada por múltiples intentos fallidos. Intente más tarde."]);
                return;
            }

            // Verificar contraseña
            if (password_verify($password, $user['password_hash'])) {
                
                // Resetear intentos fallidos
                $resetStmt = $this->conn->prepare("UPDATE users SET failed_login_attempts = 0, account_locked_until = NULL WHERE id = :id");
                $resetStmt->execute([':id' => $user['id']]);

                // Obtener roles del usuario
                $rolesQuery = "SELECT r.name FROM roles r INNER JOIN user_roles ur ON r.id = ur.role_id WHERE ur.user_id = :user_id";
                $rolesStmt = $this->conn->prepare($rolesQuery);
                $rolesStmt->bindParam(':user_id', $user['id']);
                $rolesStmt->execute();
                
                $roles = [];
                while ($row = $rolesStmt->fetch(PDO::FETCH_ASSOC)) {
                    $roles[] = $row['name'];
                }

                // Obtener permisos
                $permsQuery = "SELECT DISTINCT p.name FROM permissions p 
                               INNER JOIN role_permissions rp ON p.id = rp.permission_id 
                               INNER JOIN user_roles ur ON rp.role_id = ur.role_id 
                               WHERE ur.user_id = :user_id";
                $permsStmt = $this->conn->prepare($permsQuery);
                $permsStmt->bindParam(':user_id', $user['id']);
                $permsStmt->execute();

                $permissions = [];
                while ($row = $permsStmt->fetch(PDO::FETCH_ASSOC)) {
                    $permissions[] = $row['name'];
                }

                // Generar token
                $payload = [
                    "id" => $user['id'],
                    "name" => $user['name'],
                    "email" => $user['email'],
                    "roles" => $roles,
                    "permissions" => $permissions
                ];

                // 30 minutos de vida según requerimientos
                $token = JwtHelper::generateToken($payload, 30);

                // Registrar en auditoría
                $this->logAudit($user['id'], "Inicio de sesión exitoso");

                http_response_code(200);
                echo json_encode([
                    "message" => "Inicio de sesión exitoso.",
                    "token" => $token
                ]);
            } else {
                // Incrementar intentos fallidos
                $attempts = $user['failed_login_attempts'] + 1;
                $lockQuery = "UPDATE users SET failed_login_attempts = :attempts";
                
                // Si llega a 5 intentos, bloquear por 15 minutos
                if ($attempts >= 5) {
                    $lockQuery .= ", account_locked_until = NOW() + INTERVAL '15 minutes'";
                }
                $lockQuery .= " WHERE id = :id";
                
                $lockStmt = $this->conn->prepare($lockQuery);
                $lockStmt->execute([':attempts' => $attempts, ':id' => $user['id']]);

                // Registrar intento fallido
                $this->logAudit($user['id'], "Intento fallido de inicio de sesión (contraseña incorrecta)");
                http_response_code(401);
                echo json_encode(["message" => "Credenciales incorrectas."]);
            }
        } else {
            // No existe el usuario
            $this->logAudit(null, "Intento fallido de inicio de sesión (usuario inexistente)", ["email" => $email]);
            http_response_code(401);
            echo json_encode(["message" => "Credenciales incorrectas."]); // Mismo mensaje para evitar fuga de información
        }
    }

    public function changePassword($userId, $data) {
        $oldPassword = $data->old_password ?? '';
        $newPassword = $data->new_password ?? '';

        if (empty($oldPassword) || empty($newPassword)) {
            http_response_code(400);
            echo json_encode(["message" => "Ambas contraseñas son requeridas."]);
            return;
        }

        $query = "SELECT password_hash FROM users WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($oldPassword, $user['password_hash'])) {
            $this->logAudit($userId, "Intento fallido de cambio de contraseña");
            http_response_code(401);
            echo json_encode(["message" => "La contraseña actual es incorrecta."]);
            return;
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $updateStmt = $this->conn->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
        $updateStmt->execute([':hash' => $newHash, ':id' => $userId]);

        $this->logAudit($userId, "Cambio de contraseña exitoso");
        http_response_code(200);
        echo json_encode(["message" => "Contraseña actualizada exitosamente."]);
    }

    public function forgotPassword($data) {
        $email = filter_var($data->email ?? '', FILTER_SANITIZE_EMAIL);
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(["message" => "Correo inválido."]);
            return;
        }

        $query = "SELECT id, name FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':email' => $email]);
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            // Generar token seguro
            $token = bin2hex(random_bytes(32));
            
            // Expiración en 1 hora
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $resetQuery = "INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires_at)";
            $resetStmt = $this->conn->prepare($resetQuery);
            $resetStmt->execute([
                ':email' => $email,
                ':token' => password_hash($token, PASSWORD_DEFAULT), // Hasheamos el token en BD por seguridad
                ':expires_at' => $expiresAt
            ]);

            // Crear el enlace
            // Detectamos si hay subcarpeta base de MAMP a partir del Origin (de forma rudimentaria)
            $origin = $_SERVER['HTTP_ORIGIN'] ?? 'http://localhost';
            $resetLink = $origin . "/Caso-Practico-2-Confidencialidad/frontend/reset.html?token=" . $token . "&email=" . urlencode($email);

            $subject = "Recuperación de Contraseña - Seguridad App";
            $message = "<h3>Hola {$user['name']},</h3>
                        <p>Hemos recibido una solicitud para restablecer tu contraseña.</p>
                        <p>Haz clic en el siguiente enlace para cambiar tu contraseña. Este enlace expira en 1 hora:</p>
                        <p><a href='{$resetLink}'>Restablecer mi contraseña</a></p>
                        <p>Si no solicitaste este cambio, puedes ignorar este correo.</p>";

            \Helpers\SmtpHelper::sendEmail($email, $subject, $message);
            
            $this->logAudit($user['id'], "Solicitud de restablecimiento de contraseña");
        }

        // Siempre devolvemos 200 aunque no exista, para evitar enumeración de usuarios
        http_response_code(200);
        echo json_encode(["message" => "Si el correo existe, se han enviado las instrucciones."]);
    }

    public function resetPassword($data) {
        $email = filter_var($data->email ?? '', FILTER_SANITIZE_EMAIL);
        $token = $data->token ?? '';
        $newPassword = $data->new_password ?? '';

        if (empty($email) || empty($token) || empty($newPassword)) {
            http_response_code(400);
            echo json_encode(["message" => "Faltan datos."]);
            return;
        }

        // Buscar tokens para el email que no hayan expirado
        $query = "SELECT id, token FROM password_resets WHERE email = :email AND expires_at > NOW() ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':email' => $email]);
        
        $validReset = null;
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if (password_verify($token, $row['token'])) {
                $validReset = $row;
                break;
            }
        }

        if (!$validReset) {
            http_response_code(400);
            echo json_encode(["message" => "El token es inválido o ha expirado."]);
            return;
        }

        // Si es válido, actualizamos la contraseña
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $updateStmt = $this->conn->prepare("UPDATE users SET password_hash = :hash WHERE email = :email");
        $updateStmt->execute([':hash' => $newHash, ':email' => $email]);

        // Borrar todos los tokens de ese usuario
        $deleteStmt = $this->conn->prepare("DELETE FROM password_resets WHERE email = :email");
        $deleteStmt->execute([':email' => $email]);

        // Loguear
        $userStmt = $this->conn->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $userStmt->execute([':email' => $email]);
        $user = $userStmt->fetch(\PDO::FETCH_ASSOC);
        $this->logAudit($user['id'], "Restableció contraseña vía token");

        http_response_code(200);
        echo json_encode(["message" => "Contraseña restablecida exitosamente."]);
    }
}
