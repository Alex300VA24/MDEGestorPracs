<?php
namespace App\Repositories;

use App\Config\Database;
use PDO;

class CertificadoRepository {
    private $conn;
    
    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function obtenerEstadisticas() {
        $sql = "SELECT 
                    SUM(CASE WHEN e.Abreviatura = 'VIG' THEN 1 ELSE 0 END) as totalVigentes,
                    SUM(CASE WHEN e.Abreviatura = 'FIN' THEN 1 ELSE 0 END) as totalFinalizados
                FROM Practicante p
                INNER JOIN Estado e ON p.EstadoID = e.EstadoID
                WHERE e.Abreviatura IN ('VIG', 'FIN')";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function listarPracticantesParaCertificado() {
        $sql = "SELECT 
                    p.PracticanteID,
                    p.Nombres + ' ' + p.ApellidoPaterno + ' ' + p.ApellidoMaterno as NombreCompleto,
                    e.Descripcion as Estado,
                    e.Abreviatura as EstadoAbrev
                FROM Practicante p
                INNER JOIN Estado e ON p.EstadoID = e.EstadoID
                WHERE e.Abreviatura IN ('VIG', 'FIN')
                ORDER BY 
                    CASE WHEN e.Abreviatura = 'VIG' THEN 1 ELSE 2 END,
                    p.ApellidoPaterno, 
                    p.ApellidoMaterno, 
                    p.Nombres";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerInformacionCompleta($practicanteID) {
        $sql = "SELECT 
                    p.PracticanteID,
                    p.DNI,
                    p.Nombres + ' ' + p.ApellidoPaterno + ' ' + p.ApellidoMaterno as NombreCompleto,
                    p.Nombres,
                    p.ApellidoPaterno,
                    p.ApellidoMaterno,
                    p.Carrera,
                    p.Universidad,
                    p.FechaEntrada,
                    p.FechaSalida,
                    p.Genero,
                    e.Descripcion as Estado,
                    e.Abreviatura as EstadoAbrev,
                    a.NombreArea as Area,
                    ISNULL(
                        (SELECT SUM(DATEDIFF(MINUTE, HoraEntrada, HoraSalida) / 60.0)
                         FROM Asistencia
                         WHERE PracticanteID = p.PracticanteID
                           AND HoraEntrada IS NOT NULL
                           AND HoraSalida IS NOT NULL), 
                        0
                    ) as TotalHoras,
                    (SELECT MAX(Fecha) 
                     FROM Asistencia 
                     WHERE PracticanteID = p.PracticanteID) as UltimaAsistencia
                FROM Practicante p
                INNER JOIN Estado e ON p.EstadoID = e.EstadoID
                LEFT JOIN SolicitudPracticas sp ON sp.PracticanteID = p.PracticanteID 
                    AND sp.EstadoID = (SELECT EstadoID FROM Estado WHERE Abreviatura = 'APR')
                LEFT JOIN Area a ON sp.AreaID = a.AreaID
                WHERE p.PracticanteID = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$practicanteID]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['TotalHoras']) {
            $result['TotalHoras'] = round($result['TotalHoras'], 0);
        }
        
        return $result;
    }

    public function cambiarEstadoAFinalizado($practicanteID) {
        // Obtener el ID del estado Finalizado
        $sqlEstado = "SELECT EstadoID FROM Estado WHERE Abreviatura = 'FIN'";
        $stmtEstado = $this->conn->prepare($sqlEstado);
        $stmtEstado->execute();
        $estadoFIN = $stmtEstado->fetch(PDO::FETCH_ASSOC);
        
        if (!$estadoFIN) {
            throw new \Exception('Estado Finalizado no encontrado en la base de datos');
        }

        // Obtener la última fecha de asistencia
        $sqlUltimaAsistencia = "SELECT MAX(Fecha) as UltimaFecha 
                                FROM Asistencia 
                                WHERE PracticanteID = ?";
        $stmtUltima = $this->conn->prepare($sqlUltimaAsistencia);
        $stmtUltima->execute([$practicanteID]);
        $ultimaAsistencia = $stmtUltima->fetch(PDO::FETCH_ASSOC);
        
        // Si tiene asistencias, usar esa fecha, sino usar la fecha actual
        $fechaSalida = $ultimaAsistencia['UltimaFecha'] ?? date('Y-m-d');

        // Actualizar el estado del practicante y la fecha de salida
        $sql = "UPDATE Practicante 
                SET EstadoID = ?, 
                    FechaSalida = ? 
                WHERE PracticanteID = ?";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$estadoFIN['EstadoID'], $fechaSalida, $practicanteID]);
    }

    public function registrarCertificadoGenerado($practicanteID, $nombreArchivo, $numeroExpediente) {
        // Crear tabla de registro si no existe
        $sqlCreateTable = "IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES 
                           WHERE TABLE_NAME = 'CertificadosGenerados')
                           BEGIN
                               CREATE TABLE CertificadosGenerados (
                                   CertificadoID INT PRIMARY KEY IDENTITY(1,1),
                                   PracticanteID INT NOT NULL,
                                   NombreArchivo NVARCHAR(255) NOT NULL,
                                   NumeroExpediente VARCHAR(50) NOT NULL,
                                   FechaGeneracion DATETIME DEFAULT GETDATE(),
                                   UsuarioID INT,
                                   FOREIGN KEY (PracticanteID) REFERENCES Practicante(PracticanteID)
                               )
                           END";
        
        $this->conn->exec($sqlCreateTable);

        // Insertar el registro
        $sql = "INSERT INTO CertificadosGenerados 
                (PracticanteID, NombreArchivo, NumeroExpediente, UsuarioID) 
                VALUES (?, ?, ?, ?)";
        
        $usuarioID = $_SESSION['usuarioID'] ?? null;
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $practicanteID, 
            $nombreArchivo, 
            $numeroExpediente,
            $usuarioID
        ]);
    }

    public function obtenerHistorialCertificados($practicanteID = null) {
        $sql = "SELECT 
                    c.CertificadoID,
                    c.NombreArchivo,
                    c.NumeroExpediente,
                    c.FechaGeneracion,
                    p.Nombres + ' ' + p.ApellidoPaterno + ' ' + p.ApellidoMaterno as Practicante,
                    u.Nombres + ' ' + u.ApellidoPaterno as GeneradoPor
                FROM CertificadosGenerados c
                INNER JOIN Practicante p ON c.PracticanteID = p.PracticanteID
                LEFT JOIN Usuario u ON c.UsuarioID = u.UsuarioID";
        
        if ($practicanteID) {
            $sql .= " WHERE c.PracticanteID = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$practicanteID]);
        } else {
            $sql .= " ORDER BY c.FechaGeneracion DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}