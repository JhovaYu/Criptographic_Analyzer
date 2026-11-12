<?php
class EventConfig {
    private $conn;
    private $table_name = "event_config";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Obtener la configuración actual (siempre id = 1)
    public function getConfig() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = 1 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Valores por defecto si falla
        return [
            'event_name' => 'Evento Principal',
            'field1_active' => true,
            'field1_name' => 'Organización/Empresa',
            'field2_active' => true,
            'field2_name' => 'Cargo/Puesto'
        ];
    }

    // Actualizar la configuración
    public function updateConfig($event_name, $f1_active, $f1_name, $f2_active, $f2_name) {
        $query = "UPDATE " . $this->table_name . " 
                  SET event_name = :event_name,
                      field1_active = :f1_active,
                      field1_name = :f1_name,
                      field2_active = :f2_active,
                      field2_name = :f2_name
                  WHERE id = 1";
                  
        $stmt = $this->conn->prepare($query);
        
        // Convertir booleanos a enteros para la BD
        $f1_active = $f1_active ? 1 : 0;
        $f2_active = $f2_active ? 1 : 0;

        $stmt->bindParam(":event_name", $event_name);
        $stmt->bindParam(":f1_active", $f1_active);
        $stmt->bindParam(":f1_name", $f1_name);
        $stmt->bindParam(":f2_active", $f2_active);
        $stmt->bindParam(":f2_name", $f2_name);

        return $stmt->execute();
    }
}
?>
