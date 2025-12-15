<?php
namespace App\Repositories;

use App\Config\Database;
use PDO;

class AsistenciaRepository {
    private $conn;

    public function __construct() {
        try {
            $this->conn = Database::getInstance()->getConnection();
        } catch (\Throwable $e) {
            error_log("Error en conexión DB: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verificar si existe asistencia para un turno específico
     */
    public function existeAsistenciaTurno($practicanteID, $fecha, $turnoID) {
        try {
            $stmt = $this->conn->prepare("
                SELECT AsistenciaID 
                FROM Asistencia 
                WHERE PracticanteID = ? AND Fecha = ? AND TurnoID = ?
            ");
            $stmt->execute([$practicanteID, $fecha, $turnoID]);
            return $stmt->fetch() !== false;
        } catch (\Throwable $e) {
            error_log("Error en existeAsistenciaTurno: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verificar si existe asistencia (cualquier turno)
     */
    public function existeAsistencia($practicanteID, $fecha) {
        try {
            $stmt = $this->conn->prepare("
                SELECT AsistenciaID FROM Asistencia 
                WHERE PracticanteID = ? AND Fecha = ?
            ");
            $stmt->execute([$practicanteID, $fecha]);
            return $stmt->fetch() !== false;
        } catch (\Throwable $e) {
            error_log("Error en existeAsistencia: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Registrar entrada
     */
    public function registrarEntrada($practicanteID, $fecha, $horaEntrada, $turnoID) {
        try {
            $sqlInsert = "
                INSERT INTO Asistencia (PracticanteID, Fecha, HoraEntrada, TurnoID)
                VALUES (?, ?, ?, ?)
            ";
            $stmt = $this->conn->prepare($sqlInsert);
            $stmt->execute([$practicanteID, $fecha, $horaEntrada, $turnoID]);

            return [
                'success' => true, 
                'message' => 'Entrada registrada correctamente a las ' . $horaEntrada,
                'asistenciaID' => $this->conn->lastInsertId()
            ];

        } catch (\Throwable $e) {
            error_log("Error en registrarEntrada: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener asistencia activa (sin hora de salida)
     */
    public function obtenerAsistenciaActiva($practicanteID, $fecha) {
        try {
            $sql = "
                SELECT AsistenciaID, HoraEntrada, TurnoID 
                FROM Asistencia 
                WHERE PracticanteID = ? AND Fecha = ? AND HoraSalida IS NULL
                ORDER BY HoraEntrada DESC
            ";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$practicanteID, $fecha]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log("Error en obtenerAsistenciaActiva: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Registrar salida
     */
    public function registrarSalida($asistenciaID, $horaSalida) {
        try {
            $sqlUpdate = "
                UPDATE Asistencia 
                SET HoraSalida = ? 
                WHERE AsistenciaID = ?
            ";
            $stmt = $this->conn->prepare($sqlUpdate);
            $stmt->execute([$horaSalida, $asistenciaID]);

            return [
                'success' => true, 
                'message' => 'Salida registrada correctamente a las ' . $horaSalida
            ];

        } catch (\Throwable $e) {
            error_log("Error en registrarSalida: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verificar si hay pausa activa
     */
    public function tienePausaActiva($asistenciaID) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total 
                FROM Pausa 
                WHERE AsistenciaID = ? AND HoraFin IS NULL
            ");
            $stmt->execute([$asistenciaID]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row['total'] > 0;
        } catch (\Throwable $e) {
            error_log("Error en tienePausaActiva: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Iniciar pausa
     */
    public function iniciarPausa($asistenciaID, $horaInicio, $motivo) {
        try {
            $sqlInsert = "
                INSERT INTO Pausa (AsistenciaID, HoraInicio, Motivo)
                VALUES (?, ?, ?)
            ";
            $stmt = $this->conn->prepare($sqlInsert);
            $stmt->execute([$asistenciaID, $horaInicio, $motivo]);

            $pausaID = $this->conn->lastInsertId();

            return [
                'success' => true,
                'message' => 'Pausa iniciada correctamente',
                'data' => [
                    'pausaID' => $pausaID,
                    'horaInicio' => $horaInicio
                ]
            ];

        } catch (\Throwable $e) {
            error_log("Error en iniciarPausa: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Finalizar pausa
     */
    public function finalizarPausa($pausaID, $horaFin) {
        try {
            // Obtener hora de inicio para calcular duración
            $sqlGet = "SELECT HoraInicio FROM Pausa WHERE PausaID = ?";
            $stmtGet = $this->conn->prepare($sqlGet);
            $stmtGet->execute([$pausaID]);
            $pausa = $stmtGet->fetch(\PDO::FETCH_ASSOC);

            if (!$pausa) {
                throw new \Exception("Pausa no encontrada");
            }

            // Actualizar pausa
            $sqlUpdate = "UPDATE Pausa SET HoraFin = ? WHERE PausaID = ?";
            $stmtUpdate = $this->conn->prepare($sqlUpdate);
            $stmtUpdate->execute([$horaFin, $pausaID]);

            // Calcular duración en segundos
            $inicio = new \DateTime($pausa['HoraInicio']);
            $fin = new \DateTime($horaFin);
            $duracion = $fin->getTimestamp() - $inicio->getTimestamp();

            return [
                'success' => true,
                'message' => 'Pausa finalizada correctamente',
                'data' => [
                    'horaFin' => $horaFin,
                    'duracionPausa' => $duracion
                ]
            ];

        } catch (\Throwable $e) {
            error_log("Error en finalizarPausa: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener pausas de una asistencia
     */
    public function obtenerPausas($asistenciaID) {
        try {
            $sql = "
                SELECT PausaID, HoraInicio, HoraFin, Motivo
                FROM Pausa
                WHERE AsistenciaID = ?
                ORDER BY HoraInicio
            ";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$asistenciaID]);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log("Error en obtenerPausas: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener asistencias por área con información completa
     */
    public function obtenerAsistenciasPorArea($areaID, $fecha = null) {
        try {
            error_log("===> Repository obtenerAsistenciasPorArea con areaID: $areaID");

            // Si no hay fecha específica, usar fecha de hoy
            $fechaConsulta = $fecha ?? date('Y-m-d');

            $sql = "
                SELECT 
                    p.PracticanteID,
                    CONCAT(p.Nombres, ' ', p.ApellidoPaterno, ' ', p.ApellidoMaterno) AS NombreCompleto,
                    a.AsistenciaID,
                    a.Fecha,
                    a.HoraEntrada,
                    a.HoraSalida,
                    a.TurnoID,
                    t.Descripcion AS Turno,
                    CASE 
                        WHEN a.AsistenciaID IS NULL THEN 'Sin registro'
                        WHEN a.HoraEntrada IS NULL THEN 'Ausente'
                        WHEN a.HoraSalida IS NULL THEN 'En curso'
                        ELSE 'Presente'
                    END AS Estado
                FROM Practicante p
                INNER JOIN SolicitudPracticas sp
                    ON p.PracticanteID = sp.PracticanteID
                INNER JOIN Area ar
                    ON sp.AreaID = ar.AreaID
                LEFT JOIN Asistencia a
                    ON p.PracticanteID = a.PracticanteID 
                    AND a.Fecha = ?
                LEFT JOIN Turno t 
                    ON a.TurnoID = t.TurnoID
                INNER JOIN Estado eP
                    ON p.EstadoID = eP.EstadoID
                INNER JOIN Estado eS
                    ON sp.EstadoID = eS.EstadoID
                WHERE 
                    ar.AreaID = ?
                    AND eP.Abreviatura = 'VIG'
                    AND eS.Abreviatura = 'APR'
                ORDER BY 
                    p.Nombres, p.ApellidoPaterno, p.ApellidoMaterno;
            ";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$fechaConsulta, $areaID]);
            $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Agregar pausas a cada asistencia
            foreach ($asistencias as &$asistencia) {
                // Agregar la fecha consultada si no existe (para registros sin asistencia)
                if (!$asistencia['Fecha']) {
                    $asistencia['Fecha'] = $fechaConsulta;
                }
                
                if ($asistencia['AsistenciaID']) {
                    $asistencia['Pausas'] = $this->obtenerPausas($asistencia['AsistenciaID']);
                    
                    // Calcular tiempo total de pausas
                    $tiempoPausas = 0;
                    foreach ($asistencia['Pausas'] as $pausa) {
                        if ($pausa['HoraFin']) {
                            $inicio = new \DateTime($pausa['HoraInicio']);
                            $fin = new \DateTime($pausa['HoraFin']);
                            $tiempoPausas += $fin->getTimestamp() - $inicio->getTimestamp();
                        }
                    }
                    $asistencia['TiempoPausas'] = $tiempoPausas;
                } else {
                    $asistencia['Pausas'] = [];
                    $asistencia['TiempoPausas'] = 0;
                }
            }
            unset($asistencia); // Romper la referencia

            error_log("===> Resultado obtenido: " . count($asistencias) . " registros para fecha: $fechaConsulta");

            return [
                'success' => true,
                'data' => $asistencias  // Ahora sí retorna el array modificado
            ];

        } catch (\Throwable $e) {
            error_log("❌ Error en Repository obtenerAsistenciasPorArea: " . $e->getMessage());
            throw $e;
        }
    }
    /**
     * Obtener asistencia completa de un practicante para una fecha específica
     */
    public function obtenerAsistenciaCompleta($practicanteID, $fecha) {
        try {
            $sql = "
                SELECT 
                    a.AsistenciaID,
                    a.PracticanteID,
                    a.Fecha,
                    a.HoraEntrada,
                    a.HoraSalida,
                    a.TurnoID,
                    t.Descripcion AS Turno
                FROM Asistencia a
                LEFT JOIN Turno t ON a.TurnoID = t.TurnoID
                WHERE a.PracticanteID = ? AND a.Fecha = ?
                ORDER BY a.HoraEntrada DESC
            ";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$practicanteID, $fecha]);
            $asistencia = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$asistencia) {
                return null;
            }
            
            // Obtener pausas de esta asistencia
            $asistencia['Pausas'] = $this->obtenerPausas($asistencia['AsistenciaID']);
            
            // Calcular tiempo total de pausas
            $tiempoPausas = 0;
            foreach ($asistencia['Pausas'] as $pausa) {
                if ($pausa['HoraFin']) {
                    $inicio = new \DateTime($pausa['HoraInicio']);
                    $fin = new \DateTime($pausa['HoraFin']);
                    $tiempoPausas += $fin->getTimestamp() - $inicio->getTimestamp();
                }
            }
            $asistencia['TiempoPausas'] = $tiempoPausas;
            
            return $asistencia;
            
        } catch (\Throwable $e) {
            error_log("Error en obtenerAsistenciaCompleta: " . $e->getMessage());
            throw $e;
        }
    }
}