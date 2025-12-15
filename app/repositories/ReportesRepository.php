<?php

namespace App\Repositories;

use App\Config\Database;
use PDO;
use PDOException;
use Exception;

class ReportesRepository {
    private $db;
    
    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (\Throwable $e) {
            error_log("Error en conexión DB: " . $e->getMessage());
            throw $e;
        }
    }
    
    // ==================== PRACTICANTES ====================
    
    public function getPracticantesActivos() {
        try {
            $sql = "SELECT 
                        p.PracticanteID,
                        CONCAT(p.Nombres, ' ', p.ApellidoPaterno, ' ', p.ApellidoMaterno) AS NombreCompleto,
                        p.DNI,
                        p.Email,
                        p.Universidad,
                        p.Carrera,
                        a.NombreArea AS AreaNombre,
                        p.FechaEntrada,
                        p.FechaSalida,
                        e.Descripcion AS Estado
                    FROM Practicante p
                    LEFT JOIN Estado e ON p.EstadoID = e.EstadoID
                    LEFT JOIN SolicitudPracticas sp ON p.PracticanteID = sp.PracticanteID
                    LEFT JOIN Area a ON sp.AreaID = a.AreaID
                    WHERE e.Abreviatura = 'VIG'
                    ORDER BY p.Nombres";

            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en getPracticantesVigentes: " . $e->getMessage());
            throw new Exception("Error al obtener practicantes vigentes");
        }
    }

    
    public function getPracticantesCompletados() {
        try {
            $sql = "SELECT 
                        p.PracticanteID,
                        CONCAT(p.Nombres, ' ', p.ApellidoPaterno, ' ', p.ApellidoMaterno) AS NombreCompleto,
                        p.DNI,
                        p.Email,
                        p.Universidad,
                        p.Carrera,
                        p.FechaEntrada,
                        p.FechaSalida,
                        ar.NombreArea AS AreaNombre,
                        e.Descripcion AS Estado
                    FROM Practicante p
                    INNER JOIN Estado e ON p.EstadoID = e.EstadoID
                    LEFT JOIN SolicitudPracticas sp ON p.PracticanteID = sp.PracticanteID
                    LEFT JOIN Area ar ON sp.AreaID = ar.AreaID
                    WHERE e.Abreviatura = 'FIN'
                    ORDER BY p.FechaSalida DESC";

            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en getPracticantesFinalizados: " . $e->getMessage());
            throw new Exception("Error al obtener practicantes finalizados");
        }
    }


    
    public function getPracticantesPorArea($areaID = null) {
        try {
            // ==========================
            // 1. Resumen por Área
            // ==========================
            $sql = "SELECT 
                        a.AreaID,
                        a.NombreArea AS AreaNombre,
                        COUNT(DISTINCT sp.PracticanteID) AS TotalPracticantes,
                        COUNT(DISTINCT CASE WHEN e.Abreviatura = 'VIG' THEN sp.PracticanteID END) AS Activos,
                        COUNT(DISTINCT CASE WHEN e.Abreviatura = 'FIN' THEN sp.PracticanteID END) AS Completados
                    FROM Area a
                    LEFT JOIN SolicitudPracticas sp ON a.AreaID = sp.AreaID
                    LEFT JOIN Practicante p ON sp.PracticanteID = p.PracticanteID
                    LEFT JOIN Estado e ON p.EstadoID = e.EstadoID
                    " . ($areaID ? "WHERE a.AreaID = :areaID" : "") . "
                    GROUP BY a.AreaID, a.NombreArea
                    ORDER BY a.NombreArea";

            $stmt = $this->db->prepare($sql);

            if ($areaID) {
                $stmt->bindParam(':areaID', $areaID, PDO::PARAM_INT);
            }

            $stmt->execute();
            $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // ==========================
            // 2. Detalles por practicante
            // ==========================
            foreach ($areas as &$area) {
                $sqlPracticantes = "SELECT 
                                        p.PracticanteID,
                                        CONCAT(p.Nombres, ' ', p.ApellidoPaterno, ' ', p.ApellidoMaterno) AS NombreCompleto,
                                        p.DNI,
                                        p.Universidad,
                                        e.Descripcion AS Estado,
                                        p.FechaEntrada,
                                        p.FechaSalida
                                    FROM SolicitudPracticas sp
                                    INNER JOIN Practicante p ON sp.PracticanteID = p.PracticanteID
                                    INNER JOIN Estado e ON p.EstadoID = e.EstadoID
                                    WHERE sp.AreaID = :areaID
                                    ORDER BY e.Abreviatura, p.Nombres";

                $stmtPracticantes = $this->db->prepare($sqlPracticantes);
                $stmtPracticantes->bindParam(':areaID', $area['AreaID'], PDO::PARAM_INT);
                $stmtPracticantes->execute();

                $area['practicantes'] = $stmtPracticantes->fetchAll(PDO::FETCH_ASSOC);
            }

            return $areas;

        } catch (PDOException $e) {
            error_log("Error en getPracticantesPorArea: " . $e->getMessage());
            throw new Exception("Error al obtener practicantes por área");
        }
    }

    
    public function getPracticantesPorUniversidad() {
        try {
            // ==========================
            // 1. Resumen por Universidad
            // ==========================
            $sql = "SELECT 
                        p.Universidad,
                        COUNT(DISTINCT p.PracticanteID) AS TotalPracticantes,
                        COUNT(DISTINCT CASE WHEN e.Abreviatura = 'VIG' THEN p.PracticanteID END) AS Activos,
                        COUNT(DISTINCT CASE WHEN e.Abreviatura = 'FIN' THEN p.PracticanteID END) AS Completados
                    FROM Practicante p
                    INNER JOIN Estado e ON p.EstadoID = e.EstadoID
                    GROUP BY p.Universidad
                    ORDER BY TotalPracticantes DESC, p.Universidad";

            $stmt = $this->db->query($sql);
            $universidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // ==========================
            // 2. Detalle de practicantes por universidad
            // ==========================
            foreach ($universidades as &$universidad) {

                $sqlPracticantes = "SELECT 
                                        p.PracticanteID,
                                        CONCAT(p.Nombres, ' ', p.ApellidoPaterno, ' ', p.ApellidoMaterno) AS NombreCompleto,
                                        p.DNI,
                                        p.Carrera,
                                        e.Descripcion AS Estado,
                                        a.NombreArea AS AreaNombre
                                    FROM Practicante p
                                    INNER JOIN Estado e ON p.EstadoID = e.EstadoID
                                    LEFT JOIN SolicitudPracticas sp ON p.PracticanteID = sp.PracticanteID
                                    LEFT JOIN Area a ON sp.AreaID = a.AreaID
                                    WHERE p.Universidad = :universidad
                                    ORDER BY p.Nombres";

                $stmtPracticantes = $this->db->prepare($sqlPracticantes);
                $stmtPracticantes->bindParam(':universidad', $universidad['Universidad']);
                $stmtPracticantes->execute();

                $universidad['practicantes'] = $stmtPracticantes->fetchAll(PDO::FETCH_ASSOC);
            }

            return $universidades;

        } catch (PDOException $e) {
            error_log("Error en getPracticantesPorUniversidad: " . $e->getMessage());
            throw new Exception("Error al obtener practicantes por universidad");
        }
    }

    
    // ==================== ASISTENCIAS ====================
    
    public function getAsistenciasPorPracticante($practicanteID, $fechaInicio = null, $fechaFin = null)
    {
        try {
            $sql = "SELECT 
                        a.AsistenciaID,
                        a.Fecha,
                        a.HoraEntrada,
                        a.HoraSalida,
                        CASE 
                            WHEN a.HoraEntrada IS NOT NULL AND a.HoraSalida IS NOT NULL 
                            THEN CAST(DATEDIFF(MINUTE, a.HoraEntrada, a.HoraSalida) AS FLOAT) / 60
                            ELSE NULL
                        END AS HorasTrabajadas,
                        t.Descripcion AS TurnoNombre,
                        t.HoraInicio AS TurnoInicio,
                        t.HoraFin AS TurnoFin
                    FROM Asistencia a
                    INNER JOIN Turno t ON a.TurnoID = t.TurnoID
                    WHERE a.PracticanteID = :practicanteID";

            // Filtros dinámicos
            $params = [
                ':practicanteID' => $practicanteID
            ];

            if (!empty($fechaInicio)) {
                $sql .= " AND a.Fecha >= :fechaInicio";
                $params[':fechaInicio'] = $fechaInicio;
            }

            if (!empty($fechaFin)) {
                $sql .= " AND a.Fecha <= :fechaFin";
                $params[':fechaFin'] = $fechaFin;
            }

            $sql .= " ORDER BY a.Fecha DESC, a.HoraEntrada DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en getAsistenciasPorPracticante: " . $e->getMessage());
            throw new Exception("Error al obtener asistencias del practicante");
        }
    }




    
    public function getPracticanteInfo($practicanteID)
    {
        try {
            $sql = "SELECT 
                        p.PracticanteID,
                        CONCAT(p.Nombres, ' ', p.ApellidoPaterno, ' ', p.ApellidoMaterno) AS NombreCompleto,
                        p.DNI,
                        p.Email,
                        p.Universidad,
                        p.Carrera,
                        a.NombreArea AS AreaNombre
                    FROM Practicante p
                    LEFT JOIN SolicitudPracticas sp 
                        ON p.PracticanteID = sp.PracticanteID 
                        AND sp.EstadoID IN (3,4)  -- REV=3, APR=4 (según tus inserts)
                    LEFT JOIN Area a ON sp.AreaID = a.AreaID
                    WHERE p.PracticanteID = :practicanteID";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':practicanteID', $practicanteID, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en getPracticanteInfo: " . $e->getMessage());
            throw new Exception("Error al obtener información del practicante");
        }
    }

    
    public function getAsistenciasDelDia($fecha)
    {
        try {

            $sql = "SELECT 
                        a.AsistenciaID,
                        CONCAT(p.Nombres, ' ', p.ApellidoPaterno, ' ', p.ApellidoMaterno) AS NombreCompleto,
                        p.DNI,
                        ar.NombreArea AS AreaNombre,
                        a.HoraEntrada,
                        a.HoraSalida,
                        -- Turno
                        a.TurnoID,
                        t.Descripcion AS TurnoNombre,
                        t.HoraInicio AS TurnoInicio,
                        t.HoraFin AS TurnoFin,
                        -- Horas trabajadas
                        CASE 
                            WHEN a.HoraEntrada IS NOT NULL AND a.HoraSalida IS NOT NULL 
                            THEN CAST(DATEDIFF(MINUTE, a.HoraEntrada, a.HoraSalida) AS FLOAT) / 60
                            ELSE NULL
                        END AS HorasTrabajadas
                    FROM Asistencia a
                    INNER JOIN Practicante p ON a.PracticanteID = p.PracticanteID
                    LEFT JOIN SolicitudPracticas sp ON p.PracticanteID = sp.PracticanteID
                    LEFT JOIN Area ar ON sp.AreaID = ar.AreaID
                    LEFT JOIN Turno t ON a.TurnoID = t.TurnoID
                    WHERE a.Fecha = :fecha
                    ORDER BY a.HoraEntrada";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':fecha', $fecha);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en getAsistenciasDelDia: " . $e->getMessage());
            throw new Exception("Error al obtener asistencias del día");
        }
    }



    
    public function getAsistenciasMensuales($mes, $anio) {
        try {
            $sql = "SELECT 
                        a.AsistenciaID,
                        a.PracticanteID,
                        a.Fecha,
                        CONCAT(p.Nombres, ' ', p.ApellidoPaterno, ' ', p.ApellidoMaterno) AS NombreCompleto,
                        ar.NombreArea AS AreaNombre,
                        a.HoraEntrada,
                        a.HoraSalida,
                        CASE 
                            WHEN a.HoraEntrada IS NOT NULL AND a.HoraSalida IS NOT NULL
                            THEN DATEDIFF(MINUTE, a.HoraEntrada, a.HoraSalida) / 60.0
                            ELSE NULL
                        END AS HorasTrabajadas
                    FROM Asistencia a
                    INNER JOIN Practicante p ON a.PracticanteID = p.PracticanteID
                    LEFT JOIN SolicitudPracticas sp ON p.PracticanteID = sp.PracticanteID
                    LEFT JOIN Area ar ON sp.AreaID = ar.AreaID
                    WHERE MONTH(a.Fecha) = :mes 
                    AND YEAR(a.Fecha) = :anio
                    ORDER BY a.Fecha, p.Nombres";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':mes', $mes, PDO::PARAM_INT);
            $stmt->bindParam(':anio', $anio, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en getAsistenciasMensuales: " . $e->getMessage());
            throw new Exception("Error al obtener asistencias mensuales");
        }
    }

    public function getAsistenciasAnuales($anio)
    {
        try {
            $sql = "SELECT 
                        p.PracticanteID,
                        CONCAT(p.Nombres, ' ', p.ApellidoPaterno, ' ', p.ApellidoMaterno) AS NombreCompleto,
                        ISNULL(ar.NombreArea, '') AS AreaNombre,

                        COUNT(DISTINCT a.Fecha) AS DiasAsistidos,

                        SUM(
                            CASE 
                                WHEN a.HoraEntrada IS NOT NULL AND a.HoraSalida IS NOT NULL
                                THEN CAST(DATEDIFF(MINUTE, a.HoraEntrada, a.HoraSalida) AS FLOAT) / 60
                                ELSE 0
                            END
                        ) AS TotalHoras,

                        STUFF(
                            (
                                SELECT ', ' + M.MesNombre
                                FROM (
                                    SELECT DISTINCT 
                                        DATENAME(MONTH, a2.Fecha) AS MesNombre,
                                        MONTH(a2.Fecha) AS MesNumero
                                    FROM Asistencia a2
                                    WHERE YEAR(a2.Fecha) = :anio
                                    AND a2.PracticanteID = p.PracticanteID
                                ) AS M
                                ORDER BY M.MesNumero
                                FOR XML PATH(''), TYPE
                            ).value('.', 'NVARCHAR(MAX)')
                        , 1, 2, '') AS MesesAsistidos

                    FROM Asistencia a
                    INNER JOIN Practicante p ON a.PracticanteID = p.PracticanteID
                    LEFT JOIN SolicitudPracticas sp ON p.PracticanteID = sp.PracticanteID
                    LEFT JOIN Area ar ON sp.AreaID = ar.AreaID

                    WHERE YEAR(a.Fecha) = :anio2

                    GROUP BY 
                        p.PracticanteID,
                        p.Nombres, p.ApellidoPaterno, p.ApellidoMaterno,
                        ar.NombreArea

                    ORDER BY NombreCompleto;";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':anio', $anio, PDO::PARAM_INT);
            $stmt->bindParam(':anio2', $anio, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en getAsistenciasAnuales: " . $e->getMessage());
            throw new Exception("Error al obtener resumen anual de asistencias");
        }
    }



    
    public function getHorasAcumuladas($practicanteID = null) {
        try {
            $sql = "SELECT 
                        p.PracticanteID,
                        CONCAT(p.Nombres, ' ', p.ApellidoPaterno, ' ', p.ApellidoMaterno) AS NombreCompleto,
                        p.DNI,
                        ar.NombreArea AS AreaNombre,
                        COUNT(a.AsistenciaID) AS TotalAsistencias,

                        COALESCE(
                            SUM(
                                CASE 
                                    WHEN a.HoraEntrada IS NOT NULL AND a.HoraSalida IS NOT NULL 
                                    THEN DATEDIFF(MINUTE, a.HoraEntrada, a.HoraSalida) / 60.0
                                    ELSE 0
                                END
                            ),
                        0) AS TotalHoras,

                        COALESCE(
                            AVG(
                                CASE 
                                    WHEN a.HoraEntrada IS NOT NULL AND a.HoraSalida IS NOT NULL 
                                    THEN DATEDIFF(MINUTE, a.HoraEntrada, a.HoraSalida) / 60.0
                                    ELSE NULL
                                END
                            ),
                        0) AS PromedioHoras

                    FROM Practicante p
                    LEFT JOIN Asistencia a 
                        ON p.PracticanteID = a.PracticanteID
                    LEFT JOIN SolicitudPracticas sp 
                        ON p.PracticanteID = sp.PracticanteID
                    LEFT JOIN Area ar 
                        ON sp.AreaID = ar.AreaID
                    " . ($practicanteID ? "WHERE p.PracticanteID = :practicanteID" : "") . "
                    GROUP BY 
                        p.PracticanteID, 
                        p.Nombres, 
                        p.ApellidoPaterno, 
                        p.ApellidoMaterno, 
                        p.DNI, 
                        ar.NombreArea
                    ORDER BY TotalHoras DESC";

            $stmt = $this->db->prepare($sql);

            if ($practicanteID) {
                $stmt->bindParam(':practicanteID', $practicanteID, PDO::PARAM_INT);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en getHorasAcumuladas: " . $e->getMessage());
            throw new Exception("Error al obtener horas acumuladas");
        }
    }

    
    // ==================== ESTADÍSTICAS ====================
    
    public function countPracticantesActivos() {
        try {
            $sql = "SELECT COUNT(DISTINCT p.PracticanteID) AS total
                    FROM Practicante p
                    INNER JOIN Estado ep ON p.EstadoID = ep.EstadoID          -- estado del practicante
                    INNER JOIN SolicitudPracticas sp ON p.PracticanteID = sp.PracticanteID
                    INNER JOIN Estado es ON sp.EstadoID = es.EstadoID         -- estado de la solicitud
                    WHERE ep.Abreviatura = 'VIG' 
                    AND es.Abreviatura = 'APR'";

            $stmt = $this->db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) ($result['total'] ?? 0);

        } catch (PDOException $e) {
            error_log("Error en countPracticantesVigentes: " . $e->getMessage());
            return 0;
        }
    }

    public function countPracticantesCompletados() {
        try {
            $sql = "SELECT COUNT(DISTINCT p.PracticanteID) AS total
                    FROM Practicante p
                    INNER JOIN Estado ep ON p.EstadoID = ep.EstadoID          -- estado del practicante
                    INNER JOIN SolicitudPracticas sp ON p.PracticanteID = sp.PracticanteID
                    INNER JOIN Estado es ON sp.EstadoID = es.EstadoID         -- estado de la solicitud
                    WHERE ep.Abreviatura = 'FIN' 
                    AND es.Abreviatura = 'APR'";

            $stmt = $this->db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) ($result['total'] ?? 0);

        } catch (PDOException $e) {
            error_log("Error en countPracticantesFinalizados: " . $e->getMessage());
            return 0;
        }
    }
    
    public function countAreas() {
        try {
            $sql = "SELECT COUNT(*) as total FROM Area";
            $stmt = $this->db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error en countAreas: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getPromedioHorasDiarias() {
        try {
            $sql = "SELECT 
                        AVG(
                            CASE 
                                WHEN HoraEntrada IS NOT NULL AND HoraSalida IS NOT NULL
                                THEN DATEDIFF(MINUTE, HoraEntrada, HoraSalida) / 60.0
                            END
                        ) AS promedio
                    FROM Asistencia";

            $result = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
            return round($result['promedio'] ?? 0, 2);

        } catch (PDOException $e) {
            error_log("Error en getPromedioHorasDiarias: " . $e->getMessage());
            return 0;
        }
    }

    public function getDistribucionPorArea() {
        try {
            $sql = "SELECT 
                        a.NombreArea AS area,
                        COUNT(DISTINCT sp.PracticanteID) AS cantidad
                    FROM Area a
                    LEFT JOIN SolicitudPracticas sp 
                        ON a.AreaID = sp.AreaID
                    LEFT JOIN Estado e 
                        ON sp.EstadoID = e.EstadoID 
                        AND e.Abreviatura = 'APR'  -- Estado 'Aprobado'
                    GROUP BY a.AreaID, a.NombreArea
                    ORDER BY cantidad DESC";

            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en getDistribucionPorArea: " . $e->getMessage());
            return [];
        }
    }


    
    public function getAsistenciasMesActual() {
        try {
            $sql = "SELECT COUNT(*) 
                    FROM Asistencia 
                    WHERE MONTH(Fecha) = MONTH(GETDATE())
                    AND YEAR(Fecha) = YEAR(GETDATE())";

            return $this->db->query($sql)->fetchColumn() ?? 0;

        } catch (PDOException $e) {
            error_log("Error en getAsistenciasMesActual: " . $e->getMessage());
            return 0;
        }
    }

    
    public function getPromedioHorasPorPracticante() {
        try {

            $sql = "
                WITH UltimaSolicitud AS (
                    SELECT 
                        sp.PracticanteID,
                        ar.NombreArea,
                        ROW_NUMBER() OVER (
                            PARTITION BY sp.PracticanteID 
                            ORDER BY sp.SolicitudID DESC
                        ) AS rn
                    FROM SolicitudPracticas sp
                    LEFT JOIN Area ar 
                        ON ar.AreaID = sp.AreaID
                )
                SELECT 
                    p.PracticanteID,
                    CONCAT(p.Nombres, ' ', p.ApellidoPaterno, ' ', p.ApellidoMaterno) AS NombreCompleto,
                    us.NombreArea AS AreaNombre,
                    COUNT(a.AsistenciaID) AS TotalAsistencias,

                    COALESCE(
                        SUM(
                            CASE 
                            WHEN a.HoraEntrada IS NOT NULL AND a.HoraSalida IS NOT NULL
                            THEN DATEDIFF(MINUTE, a.HoraEntrada, a.HoraSalida) / 60.0
                            END
                        ), 0
                    ) AS TotalHoras,

                    COALESCE(
                        AVG(
                            CASE 
                            WHEN a.HoraEntrada IS NOT NULL AND a.HoraSalida IS NOT NULL
                            THEN DATEDIFF(MINUTE, a.HoraEntrada, a.HoraSalida) / 60.0
                            END
                        ), 0
                    ) AS PromedioHoras

                FROM Practicante p
                LEFT JOIN Asistencia a 
                    ON p.PracticanteID = a.PracticanteID
                LEFT JOIN UltimaSolicitud us 
                    ON p.PracticanteID = us.PracticanteID AND us.rn = 1
                GROUP BY 
                    p.PracticanteID, 
                    CONCAT(p.Nombres, ' ', p.ApellidoPaterno, ' ', p.ApellidoMaterno),
                    us.NombreArea
                ORDER BY 
                    PromedioHoras DESC;
            ";

            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en getPromedioHorasPorPracticante: " . $e->getMessage());
            throw new Exception("Error al obtener promedio de horas");
        }
    }


    
    public function getComparativoAreas() {
        try {

            $sql = "
            -- 1. Obtener el área por practicante (última solicitud)
            WITH UltimaSolicitud AS (
                SELECT 
                    sp.PracticanteID,
                    sp.AreaID,
                    ROW_NUMBER() OVER (
                        PARTITION BY sp.PracticanteID 
                        ORDER BY sp.SolicitudID DESC
                    ) AS rn
                FROM SolicitudPracticas sp
            ),

            -- 2. Calcular horas por practicante sin duplicarse
            HorasPracticante AS (
                SELECT 
                    a.PracticanteID,
                    COUNT(a.AsistenciaID) AS TotalAsistencias,
                    SUM(
                        CASE 
                            WHEN a.HoraEntrada IS NOT NULL AND a.HoraSalida IS NOT NULL
                            THEN DATEDIFF(MINUTE, a.HoraEntrada, a.HoraSalida) / 60.0
                        END
                    ) AS TotalHoras
                FROM Asistencia a
                GROUP BY a.PracticanteID
            )

            -- 3. Agregar por área
            SELECT 
                ar.NombreArea AS AreaNombre,

                COUNT(DISTINCT us.PracticanteID) AS TotalPracticantes,

                COUNT(DISTINCT CASE 
                    WHEN est.Abreviatura = 'REV' THEN us.PracticanteID 
                END) AS Activos,

                COALESCE(SUM(hp.TotalAsistencias), 0) AS TotalAsistencias,

                COALESCE(SUM(hp.TotalHoras), 0) AS TotalHoras,

                COALESCE(
                    AVG(CASE WHEN hp.TotalAsistencias > 0 
                        THEN hp.TotalHoras 
                    END), 0
                ) AS PromedioHoras

            FROM Area ar
            LEFT JOIN UltimaSolicitud us 
                ON ar.AreaID = us.AreaID AND us.rn = 1
            LEFT JOIN HorasPracticante hp 
                ON us.PracticanteID = hp.PracticanteID
            LEFT JOIN SolicitudPracticas sp
                ON sp.PracticanteID = us.PracticanteID
            LEFT JOIN Estado est 
                ON sp.EstadoID = est.EstadoID

            GROUP BY 
                ar.AreaID, 
                ar.NombreArea
            ORDER BY 
                TotalPracticantes DESC;
            ";

            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en getComparativoAreas: " . $e->getMessage());
            throw new Exception("Error al obtener comparativo de áreas");
        }
    }


}