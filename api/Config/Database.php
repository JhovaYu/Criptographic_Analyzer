<?php
namespace Config;

use PDO;
use PDOException;
use Helpers\EnvHelper;

class Database {
    public $conn;

    public function getConnection() {
        $this->conn = null;

        // Cargar .env asegurando la ruta correcta al root del proyecto
        EnvHelper::load(dirname(__DIR__, 2) . '/.env');

        $host = getenv('DB_HOST') ?: "localhost";
        $port = getenv('DB_PORT') ?: "5432";
        $db_name = getenv('DB_NAME') ?: "caso_practico_2";
        $username = getenv('DB_USER') ?: "postgres";
        $password = getenv('DB_PASSWORD') ?: "";

        try {
            $this->conn = new PDO("pgsql:host=" . $host . ";port=" . $port . ";dbname=" . $db_name, $username, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            echo json_encode(["message" => "Error de conexión a la base de datos."]);
            exit;
        }

        return $this->conn;
    }
}
