<?php
namespace App\Repositories;

use App\Config\Database;
use PDO;

class MensajeRepository {
    private $conn;
    
    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }
    
    public function enviarSolicitudArea($solicitudID, $remitenteAreaID, $destinatarioAreaID, $contenido) {
        $stmt = $this->conn->prepare("EXEC sp_EnviarSolicitudArea ?, ?, ?, ?");
        $stmt->execute([$solicitudID, $remitenteAreaID, $destinatarioAreaID, $contenido]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function responderSolicitud($mensajeID, $respuesta, $contenido) {
        $stmt = $this->conn->prepare("EXEC sp_ResponderSolicitud ?, ?, ?");
        return $stmt->execute([$mensajeID, $respuesta, $contenido]);
    }
    
    public function listarMensajesPorArea($areaID) {
        $stmt = $this->conn->prepare("EXEC sp_ListarMensajesPorArea ?");
        $stmt->execute([$areaID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function eliminarMensaje($mensajeID)
    {
        try {
            // 🔹 1. Eliminar el mensaje
            $sql = "DELETE FROM Mensajes WHERE MensajeID = :mensajeID";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':mensajeID', $mensajeID, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {

                // 🔹 2. Obtener el MAX actual de MensajeID
                $sqlMax = "SELECT ISNULL(MAX(MensajeID), 0) AS MaxID FROM Mensajes";
                $stmtMax = $this->conn->prepare($sqlMax);
                $stmtMax->execute();
                $row = $stmtMax->fetch(PDO::FETCH_ASSOC);
                $maxID = $row['MaxID'];

                // 🔹 3. Reiniciar el IDENTITY
                // NOTA: DBCC necesita ejecutarse con prepare() normal sin parámetros dinámicos
                $sqlReseed = "DBCC CHECKIDENT ('Mensajes', RESEED, $maxID)";
                $this->conn->exec($sqlReseed);

                return [
                    'success' => true,
                    'message' => 'Mensaje eliminado'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'No se encontró el mensaje o ya fue eliminado.'
                ];
            }

        } catch (\PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ];
        }
    }








}