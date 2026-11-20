<?php
require_once __DIR__ . '/Helpers/EnvHelper.php';
require_once __DIR__ . '/Config/Database.php';

use Helpers\EnvHelper;
use Config\Database;

EnvHelper::load(dirname(__DIR__) . '/.env');
$db = new Database();
$conn = $db->getConnection();

echo "<h1>Asignando Permisos a Roles Predeterminados...</h1>";

try {
    $conn->beginTransaction();

    // Obtener IDs de roles
    $stmt = $conn->prepare("SELECT id, name FROM roles");
    $stmt->execute();
    $roles = [];
    while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) { $roles[$r['name']] = $r['id']; }

    // Obtener IDs de permisos
    $stmt = $conn->prepare("SELECT id, name FROM permissions");
    $stmt->execute();
    $perms = [];
    while ($p = $stmt->fetch(\PDO::FETCH_ASSOC)) { $perms[$p['name']] = $p['id']; }

    // Asignar a Editor: read_content, write_content, delete_content
    if (isset($roles['Editor'])) {
        $rId = $roles['Editor'];
        foreach (['read_content', 'write_content', 'delete_content'] as $p) {
            if (isset($perms[$p])) {
                $q = $conn->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?) ON CONFLICT DO NOTHING");
                $q->execute([$rId, $perms[$p]]);
            }
        }
        echo "<p>✔️ Permisos asignados al Editor (lectura, escritura y eliminación).</p>";
    }

    // Asignar a Usuario Regular: read_content
    if (isset($roles['Usuario Regular'])) {
        $rId = $roles['Usuario Regular'];
        if (isset($perms['read_content'])) {
            $q = $conn->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?) ON CONFLICT DO NOTHING");
            $q->execute([$rId, $perms['read_content']]);
        }
        echo "<p>✔️ Permisos asignados al Usuario Regular.</p>";
    }

    $conn->commit();
    echo "<h2 style='color:green'>¡Listo! Ahora cierra sesión y vuelve a entrar con el editor.</h2>";

} catch (Exception $e) {
    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}
