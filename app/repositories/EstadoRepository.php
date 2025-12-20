<?php

namespace App\Repositories;

use App\Config\Database;
use PDO;
use PDOException;

/**
 * Repositorio para acceso a datos de estados
 * Maneja las operaciones relacionadas con la tabla Estado
 */
class EstadoRepository {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Obtener EstadoID por abreviatura
     */
    public function obtenerEstadoIDPorAbreviatura($abreviatura) {
        try {
            $query = "SELECT EstadoID FROM Estado WHERE Abreviatura = :abreviatura";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':abreviatura', $abreviatura, PDO::PARAM_STR);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['EstadoID'] : null;
            
        } catch (PDOException $e) {
            throw new \Exception('Error al obtener estado por abreviatura: ' . $e->getMessage());
        }
    }

    /**
     * Obtener todos los estados
     */
    public function obtenerTodos() {
        try {
            $query = "SELECT EstadoID, Abreviatura, Descripcion FROM Estado ORDER BY Descripcion";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $estados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $estados;
            
        } catch (PDOException $e) {
            throw new \Exception('Error al obtener estados: ' . $e->getMessage());
        }
    }

    /**
     * Obtener estado por ID
     */
    public function obtenerPorID($estadoID) {
        try {
            $query = "SELECT EstadoID, Abreviatura, Descripcion FROM Estado WHERE EstadoID = :estadoID";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':estadoID', $estadoID, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: null;
            
        } catch (PDOException $e) {
            throw new \Exception('Error al obtener estado: ' . $e->getMessage());
        }
    }
}