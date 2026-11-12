<?php
require_once 'EnvLoader.php';

class Database {
    private static $instance = null;
    private $conn;

    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;

    // Constructor privado para el patrón Singleton
    private function __construct() {
        // Cargar variables de entorno
        EnvLoader::load(dirname(__DIR__) . '/.env');

        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->port = $_ENV['DB_PORT'] ?? '5432';
        $this->db_name = $_ENV['DB_NAME'] ?? 'mvc_app';
        $this->username = $_ENV['DB_USER'] ?? 'postgres';
        $this->password = $_ENV['DB_PASSWORD'] ?? '';

        try {
            $dsn = "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            // Configurar PDO para que lance excepciones en caso de error
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw $e;
        }
    }

    // Método estático para obtener la única instancia (Singleton)
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    // Obtener la conexión PDO
    public function getConnection() {
        return $this->conn;
    }

    public function getDbInfo() {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'db_name' => $this->db_name
        ];
    }

    // Evitar la clonación
    private function __clone() {}
}
?>
