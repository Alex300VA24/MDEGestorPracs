<?php

namespace App\Repositories;

use App\Config\Database;
use PDO;
use PDOException;

/**
 * Repositorio para acceso a datos de documentos de solicitudes
 * Maneja las operaciones con la tabla DocumentoSolicitud
 */
class DocumentoRepository {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Obtener documentos de la solicitud ACTIVA del practicante
     * Solo retorna documentos de solicitudes con estado PEN, REV o APR
     */
    public function obtenerDocumentosSolicitudActiva($practicanteID) {
        try {
            $query = "
                SELECT 
                    d.DocumentoID,
                    d.SolicitudID,
                    d.TipoDocumento,
                    d.Observaciones,
                    d.FechaSubida,
                    CONVERT(VARCHAR(MAX), CAST(d.Archivo AS VARBINARY(MAX)), 1) AS Archivo
                FROM DocumentoSolicitud d
                INNER JOIN SolicitudPracticas s ON d.SolicitudID = s.SolicitudID
                INNER JOIN Estado e ON s.EstadoID = e.EstadoID
                WHERE s.PracticanteID = :practicanteID
                    AND e.Abreviatura IN ('PEN', 'REV', 'APR')
                ORDER BY d.FechaSubida DESC
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':practicanteID', $practicanteID, PDO::PARAM_INT);
            $stmt->execute();

            $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $documentos;

        } catch (PDOException $e) {
            throw new \Exception('Error al obtener documentos: ' . $e->getMessage());
        }
    }

    /**
     * Obtener documento por ID
     */
    public function obtenerPorID($documentoID) {
        try {
            $query = "
                SELECT 
                    d.DocumentoID,
                    d.SolicitudID,
                    d.TipoDocumento,
                    d.Observaciones,
                    d.FechaSubida,
                    CONVERT(VARCHAR(MAX), CAST(d.Archivo AS VARBINARY(MAX)), 1) AS Archivo
                FROM DocumentoSolicitud d
                WHERE d.DocumentoID = :documentoID
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':documentoID', $documentoID, PDO::PARAM_INT);
            $stmt->execute();

            $documento = $stmt->fetch(PDO::FETCH_ASSOC);

            return $documento ?: null;

        } catch (PDOException $e) {
            throw new \Exception('Error al obtener documento: ' . $e->getMessage());
        }
    }

    /**
     * Obtener todos los documentos de una solicitud específica
     */
    public function obtenerPorSolicitud($solicitudID) {
        try {
            $query = "
                SELECT 
                    d.DocumentoID,
                    d.SolicitudID,
                    d.TipoDocumento,
                    d.Observaciones,
                    d.FechaSubida,
                    CONVERT(VARCHAR(MAX), CAST(d.Archivo AS VARBINARY(MAX)), 1) AS Archivo
                FROM DocumentoSolicitud d
                WHERE d.SolicitudID = :solicitudID
                ORDER BY d.FechaSubida DESC
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':solicitudID', $solicitudID, PDO::PARAM_INT);
            $stmt->execute();

            $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $documentos;

        } catch (PDOException $e) {
            throw new \Exception('Error al obtener documentos por solicitud: ' . $e->getMessage());
        }
    }

    /**
     * Crear nuevo documento
     */
    public function crearDocumento($solicitudID, $tipoDocumento, $archivo, $observaciones = null) {
        try {
            $query = "
                INSERT INTO DocumentoSolicitud (SolicitudID, TipoDocumento, Archivo, Observaciones, FechaSubida)
                OUTPUT INSERTED.DocumentoID
                VALUES (:solicitudID, :tipoDocumento, CONVERT(VARBINARY(MAX), :archivo, 1), :observaciones, GETDATE())
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':solicitudID', $solicitudID, PDO::PARAM_INT);
            $stmt->bindParam(':tipoDocumento', $tipoDocumento, PDO::PARAM_STR);
            $stmt->bindParam(':archivo', $archivo, PDO::PARAM_STR);
            $stmt->bindParam(':observaciones', $observaciones, PDO::PARAM_STR);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['DocumentoID'];

        } catch (PDOException $e) {
            throw new \Exception('Error al crear documento: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar documento
     */
    public function eliminarDocumento($documentoID) {
        try {
            $query = "DELETE FROM DocumentoSolicitud WHERE DocumentoID = :documentoID";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':documentoID', $documentoID, PDO::PARAM_INT);
            
            return $stmt->execute();

        } catch (PDOException $e) {
            throw new \Exception('Error al eliminar documento: ' . $e->getMessage());
        }
    }
}