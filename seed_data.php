<?php
require_once 'config/Database.php';

$db = Database::getInstance()->getConnection();

echo "Iniciando seeding de datos...\n";

// Insertar 30 usuarios asistententes
for ($i = 1; $i <= 30; $i++) {
    $username = "asistente" . rand(1000, 9999) . $i;
    $password_hash = password_hash("password123", PASSWORD_DEFAULT);
    $name = "Usuario Mock " . $i;
    $cf1 = "Organización " . rand(1, 5);
    $cf2 = "Cargo " . rand(1, 3);
    
    try {
        $stmt = $db->prepare("INSERT INTO users (username, password_hash, role_id, name, custom_field_1, custom_field_2) VALUES (?, ?, 3, ?, ?, ?)");
        $stmt->execute([$username, $password_hash, $name, $cf1, $cf2]);
        $userId = $db->lastInsertId();
        
        // Crear algunos turnos para estos usuarios
        $randTurno = rand(1, 10);
        if ($randTurno <= 4) {
            $status = 'finalizado';
        } elseif ($randTurno <= 7) {
            $status = 'espera';
        } elseif ($randTurno == 8) {
            $status = 'hablando';
        } else {
            $status = null; // No turno
        }
        
        if ($status) {
            $stmtTurno = $db->prepare("INSERT INTO turnos (user_id, status) VALUES (?, ?)");
            $stmtTurno->execute([$userId, $status]);
        }
        
    } catch (Exception $e) {
        echo "Error insertando usuario: " . $e->getMessage() . "\n";
    }
}

echo "Seeding completado.\n";
?>
