<?php
// Script para insertar datos semilla (Roles, Permisos y un Usuario Administrador)
require_once __DIR__ . '/Helpers/EnvHelper.php';
require_once __DIR__ . '/Config/Database.php';

use Helpers\EnvHelper;
use Config\Database;

// Cargar el entorno
EnvHelper::load(dirname(__DIR__) . '/.env');

$db = new Database();
$conn = $db->getConnection();

echo "<h1>Semilla de Base de Datos - Seguridad App</h1>";

try {
    $conn->beginTransaction();

    // 1. Insertar roles básicos si no existen (idempotente gracias a ON CONFLICT DO NOTHING o manejándolo con verificaciones)
    $roles = [
        ['Administrador', 'Tiene acceso completo a todas las funcionalidades.'],
        ['Editor', 'Puede crear, editar y eliminar contenidos.'],
        ['Usuario Regular', 'Solo puede ver y consumir contenido.']
    ];

    foreach ($roles as $role) {
        $stmt = $conn->prepare("INSERT INTO roles (name, description) VALUES (:name, :desc) ON CONFLICT (name) DO NOTHING");
        $stmt->execute([':name' => $role[0], ':desc' => $role[1]]);
    }
    echo "<p>✔️ Roles asegurados en la base de datos.</p>";

    // 2. Insertar permisos
    $permissions = [
        ['read_content', 'Acceso a visualizar recursos.'],
        ['write_content', 'Acceso para crear o modificar recursos.'],
        ['delete_content', 'Permiso para eliminar recursos.'],
        ['manage_users', 'Permiso para gestionar usuarios.'],
        ['manage_roles', 'Permiso para gestionar roles y permisos.']
    ];

    foreach ($permissions as $perm) {
        $stmt = $conn->prepare("INSERT INTO permissions (name, description) VALUES (:name, :desc) ON CONFLICT (name) DO NOTHING");
        $stmt->execute([':name' => $perm[0], ':desc' => $perm[1]]);
    }
    echo "<p>✔️ Permisos asegurados en la base de datos.</p>";

    // 3. Asignar todos los permisos al Administrador
    $stmt = $conn->prepare("SELECT id FROM roles WHERE name = 'Administrador'");
    $stmt->execute();
    $adminRoleId = $stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT id FROM permissions");
    $stmt->execute();
    $allPerms = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if ($adminRoleId) {
        foreach ($allPerms as $permId) {
            $stmt = $conn->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (:role, :perm) ON CONFLICT DO NOTHING");
            $stmt->execute([':role' => $adminRoleId, ':perm' => $permId]);
        }
    }

    // 4. Crear el usuario administrador
    $adminEmail = 'admin@admin.com';
    $adminPassword = 'admin'; // Contraseña por defecto (se hasheará)

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute([':email' => $adminEmail]);
    $adminUserId = $stmt->fetchColumn();

    if (!$adminUserId) {
        $hash = password_hash($adminPassword, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash) VALUES ('Administrador Principal', :email, :hash) RETURNING id");
        $stmt->execute([':email' => $adminEmail, ':hash' => $hash]);
        $adminUserId = $stmt->fetchColumn();
        echo "<p>✔️ Usuario Administrador creado exitosamente.</p>";
    } else {
        echo "<p>⚠️ El usuario Administrador ya existe.</p>";
    }

    // 5. Asignar rol de Administrador al usuario
    if ($adminUserId && $adminRoleId) {
        $stmt = $conn->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (:uid, :rid) ON CONFLICT DO NOTHING");
        $stmt->execute([':uid' => $adminUserId, ':rid' => $adminRoleId]);
    }

    $conn->commit();
    echo "<h2 style='color: green;'>¡Proceso completado exitosamente!</h2>";
    echo "<h3>Tus credenciales de prueba:</h3>";
    echo "<ul><li><b>Correo:</b> admin@admin.com</li><li><b>Contraseña:</b> admin</li></ul>";
    echo "<p><a href='../frontend/index.html'>Ir al Inicio de Sesión</a></p>";

} catch (Exception $e) {
    $conn->rollBack();
    echo "<h2 style='color: red;'>Error en el proceso</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
