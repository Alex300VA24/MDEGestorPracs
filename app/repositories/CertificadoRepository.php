<?php
namespace App\Repositories;

use PDO;

class CertificadoRepository extends BaseRepository {
    
    protected $table = 'Practicante';
    protected $primaryKey = 'PracticanteID';

    /**
     * Obtener estadísticas de certificados
     */
    public function obtenerEstadisticas() {
        $sql = "
            SELECT 
                SUM(CASE WHEN e.Abreviatura = 'VIG' THEN 1 ELSE 0 END) as totalVigentes,
                SUM(CASE WHEN e.Abreviatura = 'FIN' THEN 1 ELSE 0 END) as totalFinalizados
            FROM Practicante p
            INNER JOIN Estado e ON p.EstadoID = e.EstadoID
            WHERE e.Abreviatura IN ('VIG', 'FIN')
        ";
        
        return $this->executeQuery($sql, [], 'one');
    }

    /**
     * Listar practicantes elegibles para certificado
     */
    public function listarPracticantesParaCertificado() {
        $sql = "
            SELECT 
                p.PracticanteID,
                CONCAT(p.Nombres, ' ', p.ApellidoPaterno, ' ', p.ApellidoMaterno) as NombreCompleto,
                e.Descripcion as Estado,
                e.Abreviatura as EstadoAbrev
            FROM Practicante p
            INNER JOIN Estado e ON p.EstadoID = e.EstadoID
            WHERE e.Abreviatura IN ('VIG', 'FIN')
            ORDER BY 
                CASE WHEN e.Abreviatura = 'VIG' THEN 1 ELSE 2 END,
                p.ApellidoPaterno, 
                p.ApellidoMaterno, 
                p.Nombres
        ";
        
        return $this->executeQuery($sql, [], 'all');
    }

    /**
     * Obtener información completa del practicante para certificado
     */
    public function obtenerInformacionCompleta($practicanteID) {
        $sql = "
            WITH UltimaSolicitud AS (
                SELECT 
                    sp.PracticanteID,
                    sp.AreaID,
                    ROW_NUMBER() OVER (
                        PARTITION BY sp.PracticanteID 
                        ORDER BY sp.SolicitudID DESC
                    ) AS rn
                FROM SolicitudPracticas sp
                WHERE sp.EstadoID = (SELECT EstadoID FROM Estado WHERE Abreviatura = 'APR')
            )
            SELECT 
                p.PracticanteID,
                p.DNI,
                CONCAT(p.Nombres, ' ', p.ApellidoPaterno, ' ', p.ApellidoMaterno) as NombreCompleto,
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
            LEFT JOIN UltimaSolicitud us ON p.PracticanteID = us.PracticanteID AND us.rn = 1
            LEFT JOIN Area a ON us.AreaID = a.AreaID
            WHERE p.PracticanteID = :practicanteID
        ";
        
        $result = $this->executeQuery($sql, [':practicanteID' => $practicanteID], 'one');
        
        if ($result && $result['TotalHoras']) {
            $result['TotalHoras'] = round($result['TotalHoras'], 0);
        }
        
        return $result;
    }

    /**
     * Cambiar estado del practicante a Finalizado
     */
    public function cambiarEstadoAFinalizado($practicanteID) {
        return $this->executeTransaction(function() use ($practicanteID) {
            // Obtener el ID del estado Finalizado
            $estadoFIN = $this->executeQuery(
                "SELECT EstadoID FROM Estado WHERE Abreviatura = 'FIN'",
                [],
                'one'
            );
            
            if (!$estadoFIN) {
                throw new \Exception('Estado Finalizado no encontrado en la base de datos');
            }

            // Obtener la última fecha de asistencia
            $ultimaAsistencia = $this->executeQuery(
                "SELECT MAX(Fecha) as UltimaFecha 
                 FROM Asistencia 
                 WHERE PracticanteID = :practicanteID",
                [':practicanteID' => $practicanteID],
                'one'
            );
            
            // Si tiene asistencias, usar esa fecha, sino usar la fecha actual
            $fechaSalida = $ultimaAsistencia['UltimaFecha'] ?? date('Y-m-d');

            // Actualizar el estado del practicante
            return $this->update($practicanteID, [
                'EstadoID' => $estadoFIN['EstadoID'],
                'FechaSalida' => $fechaSalida
            ]);
        });
    }

    /**
     * Registrar certificado generado
     */
    public function registrarCertificadoGenerado($practicanteID, $nombreArchivo, $numeroExpediente) {
        // Crear tabla si no existe
        $this->crearTablaCertificados();

        // Insertar registro
        $sql = "
            INSERT INTO CertificadosGenerados 
            (PracticanteID, NombreArchivo, NumeroExpediente, UsuarioID) 
            OUTPUT INSERTED.CertificadoID
            VALUES (:practicanteID, :nombreArchivo, :numeroExpediente, :usuarioID)
        ";
        
        $usuarioID = $_SESSION['usuarioID'] ?? null;
        
        return $this->executeQuery($sql, [
            ':practicanteID' => $practicanteID,
            ':nombreArchivo' => $nombreArchivo,
            ':numeroExpediente' => $numeroExpediente,
            ':usuarioID' => $usuarioID
        ], 'one');
    }

    /**
     * Obtener historial de certificados generados
     */
    public function obtenerHistorialCertificados($practicanteID = null) {
        $sql = "
            SELECT 
                c.CertificadoID,
                c.NombreArchivo,
                c.NumeroExpediente,
                c.FechaGeneracion,
                CONCAT(p.Nombres, ' ', p.ApellidoPaterno, ' ', p.ApellidoMaterno) as Practicante,
                CONCAT(u.Nombres, ' ', u.ApellidoPaterno) as GeneradoPor
            FROM CertificadosGenerados c
            INNER JOIN Practicante p ON c.PracticanteID = p.PracticanteID
            LEFT JOIN Usuario u ON c.UsuarioID = u.UsuarioID
        ";
        
        $params = [];
        
        if ($practicanteID) {
            $sql .= " WHERE c.PracticanteID = :practicanteID";
            $params[':practicanteID'] = $practicanteID;
        }
        
        $sql .= " ORDER BY c.FechaGeneracion DESC";
        
        return $this->executeQuery($sql, $params, 'all');
    }

    /**
     * Crear tabla de certificados generados si no existe*/
    private function crearTablaCertificados() {
        $sql = "
            IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES 
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
            END
        ";
        
        try {
            $this->db->exec($sql);
        } catch (\PDOException $e) {
            error_log("Error al crear tabla CertificadosGenerados: " . $e->getMessage());
            // No lanzar excepción, la tabla puede ya existir
        }
    }
}