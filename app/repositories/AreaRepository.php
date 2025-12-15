<?php
namespace App\Repositories;

use App\Config\Database;
use PDO;

class AreaRepository {
    private $conn;
    
    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }
    
    public function listarAreas() {
        $stmt = $this->conn->prepare("SELECT AreaID, NombreArea, Descripcion FROM Area ORDER BY NombreArea");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}