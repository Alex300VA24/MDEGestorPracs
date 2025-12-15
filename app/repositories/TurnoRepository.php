<?php
namespace App\Repositories;

use App\Config\Database;
use PDO;

class TurnoRepository {
    private $conn;
    
    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }
    
    public function listarTurnos() {
        $stmt = $this->conn->prepare("EXEC sp_ListarTurnos");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerTurnosPracticante($practicanteID) {
        $stmt = $this->conn->prepare("EXEC sp_ObtenerTurnosPracticante ?");
        $stmt->execute([$practicanteID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}