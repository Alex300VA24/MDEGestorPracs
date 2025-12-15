<?php
namespace App\Config;

use PDO;
use PDOException;
class Database {
    private static $instance = null;
    private $connection;
    
    private $host;
    private $database;
    private $username;
    private $password;
    private $port;
    
    private function __construct() {
        $this->loadEnv();
        $this->connect();
    }
    
    private function loadEnv() {
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                list($name, $value) = explode('=', $line, 2);
                $_ENV[trim($name)] = trim($value);
            }
        }
        $this->host = $_ENV['DB_HOST'] ?? null;
        $this->database = $_ENV['DB_NAME'] ?? null;
        $this->username = $_ENV['DB_USER'] ?? null;
        $this->password = $_ENV['DB_PASS'] ?? null;
        $this->port = $_ENV['DB_PORT'] ?? '1433';
        
    }
    
    private function connect() {
        try {
            $dsn = "sqlsrv:Server={$this->host},{$this->port};Database={$this->database}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                //PDO::ATTR_EMULATE_PREPARES => false, // Habilitar consultas preparadas nativas
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch (PDOException $e) {
            throw new \RuntimeException('Error de conexión a la base de datos');
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
}
