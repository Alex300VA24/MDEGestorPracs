<?php

namespace App\Repositories;

use App\Config\Database;
use PDO;
use PDOException;
use Exception;

class ReportesRepository extends BaseRepository {
    
    protected $table = 'Practicante';
    protected $primaryKey = 'PracticanteID';
    
    // ==================== PRACTICANTES ====================
    
    public function getPracticantesActivos() {
        $sql = "
            WITH UltimaSolicitud AS (
                SELECT 
                    PracticanteID,
                    AreaID,
                    ROW_NUMBER() OVER (
                        PARTITION BY PracticanteID 
                        ORDER BY SolicitudID DESC
                    ) AS rn
                FROM SolicitudPracticas
            )
            SELECT 
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
            INNER JOIN Estado e ON p.EstadoID = e.EstadoID
            LEFT JOIN UltimaSolicitud us ON p.PracticanteID = us.PracticanteID AND us.rn = 1
            LEFT JOIN Area a ON us.AreaID = a.AreaID
            WHERE e.Abreviatura = 'VIG'
            ORDER BY p.Nombres, p.ApellidoPaterno
        ";
        
        return $this->executeQuery($sql, [], 'all');
    }
    
    public function getPracticantesCompletados() {
        $sql = "
            WITH UltimaSolicitud AS (
                SELECT 
                    PracticanteID,
                    AreaID,
                    ROW_NUMBER() OVER (
                        PARTITION BY PracticanteID 
                        ORDER BY SolicitudID DESC
                    ) AS rn
                FROM SolicitudPracticas
            )
            SELECT 
                p.PracticanteID,
                CONCAT(p.Nombres, ' ', p.ApellidoPaterno, ' ', p.ApellidoMaterno) AS NombreCompleto,
                p.DNI,
                p.Email,
                p.Universidad,
                p.Carrera,
                p.FechaEntrada,
                p.FechaSalida,
                a.NombreArea AS AreaNombre,
                e.Descripcion AS Estado
            FROM Practicante p
            INNER JOIN Estado e ON p.EstadoID = e.EstadoID
            LEFT JOIN UltimaSolicitud us ON p.PracticanteID = us.PracticanteID AND us.rn = 1
            LEFT JOIN Area a ON us.AreaID = a.AreaID
            WHERE e.Abreviatura = 'FIN'
            ORDER BY p.FechaSalida DESC
        ";
        
        return $this->executeQuery($sql, [], 'all');
    }
    
    public function getPracticantesPorArea($areaID = null) {
        // Resumen por área
        $sqlResumen = "
            WITH UltimaSolicitud AS (
                SELECT 
                    sp.PracticanteID,
                    sp.AreaID,
                    ROW_NUMBER() OVER (
                        PARTITION BY sp.PracticanteID 
                        ORDER BY sp.SolicitudID DESC
                    ) AS rn
                FROM SolicitudPracticas sp
            )
            SELECT 
                a.AreaID,
                a.NombreArea AS AreaNombre,
                COUNT(DISTINCT us.PracticanteID) AS TotalPracticantes,
                COUNT(DISTINCT CASE WHEN e.Abreviatura = 'VIG' THEN us.PracticanteID END) AS Activos,
                COUNT(DISTINCT CASE WHEN e.Abreviatura = 'FIN' THEN us.PracticanteID END) AS Completados
            FROM Area a
            LEFT JOIN UltimaSolicitud us ON a.AreaID = us.AreaID AND us.rn = 1
            LEFT JOIN Practicante p ON us.PracticanteID = p.PracticanteID
            LEFT JOIN Estado e ON p.EstadoID = e.EstadoID
            " . ($areaID ? "WHERE a.AreaID = :areaID" : "") . "
            GROUP BY a.AreaID, a.NombreArea
            ORDER BY a.NombreArea
        ";
        
        $params = $areaID ? [':areaID' => $areaID] : [];
        $areas = $this->executeQuery($sqlResumen, $params, 'all');
        
        // Detalle de practicantes por área
        foreach ($areas as &$area) {
            $sqlPracticantes = "
                WITH UltimaSolicitud AS (
                    SELECT 
                        sp.PracticanteID,
                        sp.AreaID,
                        ROW_NUMBER() OVER (
                            PARTITION BY sp.PracticanteID 
                            ORDER BY sp.SolicitudID DESC
                        ) AS rn
                    FROM SolicitudPracticas sp
                )
                SELECT 
                    p.PracticanteID,
                    CONCAT(p.Nombres, ' ', p.ApellidoPaterno, ' ', p.ApellidoMaterno) AS NombreCompleto,
                    p.DNI,
                    p.Universidad,
                    e.Descripcion AS Estado,
                    p.FechaEntrada,
                    p.FechaSalida
                FROM UltimaSolicitud us
                INNER JOIN Practicante p ON us.PracticanteID = p.PracticanteID
                INNER JOIN Estado e ON p.EstadoID = e.EstadoID
                WHERE us.AreaID = :areaID AND us.rn = 1
                ORDER BY e.Abreviatura, p.Nombres
            ";
            
            $area['practicantes'] = $this->executeQuery(
                $sqlPracticantes, 
                [':areaID' => $area['AreaID']], 
                'all'
            );
        }
        
        return $areas;
    }
    
    public function getPracticantesPorUniversidad() {
        // Resumen por universidad
        $sqlResumen = "
            SELECT 
                p.Universidad,
                COUNT(DISTINCT p.PracticanteID) AS TotalPracticantes,
                COUNT(DISTINCT CASE WHEN e.Abreviatura = 'VIG' THEN p.PracticanteID END) AS Activos,
                COUNT(DISTINCT CASE WHEN e.Abreviatura = 'FIN' THEN p.PracticanteID END) AS Completados
            FROM Practicante p
            INNER JOIN Estado e ON p.EstadoID = e.EstadoID
            GROUP BY p.Universidad
            ORDER BY TotalPracticantes DESC, p.Universidad
        ";
        
        $universidades = $this->executeQuery($sqlResumen, [], 'all');
        
        // Detalle de practicantes por universidad
        foreach ($universidades as &$uni) {
            $sqlPracticantes = "
                WITH UltimaSolicitud AS (
                    SELECT 
                        sp.PracticanteID,
                        sp.AreaID,
                        ROW_NUMBER() OVER (
                            PARTITION BY sp.PracticanteID 
                            ORDER BY sp.SolicitudID DESC
                        ) AS rn
                    FROM SolicitudPracticas sp
                )
                SELECT 
                    p.PracticanteID,
                    CONCAT(p.Nombres, ' ', p.ApellidoPaterno, ' ', p.ApellidoMaterno) AS NombreCompleto,
                    p.DNI,
                    p.Carrera,
                    e.Descripcion AS Estado,
                    a.NombreArea AS AreaNombre
                FROM Practicante p
                INNER JOIN Estado e ON p.EstadoID = e.EstadoID
                LEFT JOIN UltimaSolicitud us ON p.PracticanteID = us.PracticanteID AND us.rn = 1
                LEFT JOIN Area a ON us.AreaID = a.AreaID
                WHERE p.Universidad = :universidad
                ORDER BY p.Nombres
            ";
            
            $uni['practicantes'] = $this->executeQuery(
                $sqlPracticantes, 
                [':universidad' => $uni['Universidad']], 
                'all'
            );
        }
        
        return $universidades;
    }
    
    // ==================== ASISTENCIAS ====================
    
    public function getAsistenciasPorPracticante($practicanteID, $fechaInicio = null, $fechaFin = null) {
        $sql = "
            SELECT 
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
            WHERE a.PracticanteID = :practicanteID
        ";
        
        $params = [':practicanteID' => $practicanteID];
        
        if ($fechaInicio) {
            $sql .= " AND a.Fecha >= :fechaInicio";
            $params[':fechaInicio'] = $fechaInicio;
        }
        
        if ($fechaFin) {
            $sql .= " AND a.Fecha <= :fechaFin";
            $params[':fechaFin'] = $fechaFin;
        }
        
        $sql .= " ORDER BY a.Fecha DESC, a.HoraEntrada DESC";
        
        return $this->executeQuery($sql, $params, 'all');
    }
    
    public function getPracticanteInfo($practicanteID) {
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
                WHERE sp.EstadoID IN (3, 4) -- REV y APR
            )
            SELECT 
                p.PracticanteID,
                CONCAT(p.Nombres, ' ', p.ApellidoPaterno, ' ', p.ApellidoMaterno) AS NombreCompleto,
                p.DNI,
                p.Email,
                p.Universidad,
                p.Carrera,
                a.NombreArea AS AreaNombre
            FROM Practicante p
            LEFT JOIN UltimaSolicitud us ON p.PracticanteID = us.PracticanteID AND us.rn = 1
            LEFT JOIN Area a ON us.AreaID = a.AreaID
            WHERE p.PracticanteID = :practicanteID
        ";
        
        return $this->executeQuery($sql, [':practicanteID' => $practicanteID], 'one');
    }
    
    public function getAsistenciasDelDia($fecha) {
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
            )
            SELECT 
                a.AsistenciaID,
                CONCAT(p.Nombres, ' ', p.ApellidoPaterno, ' ', p.ApellidoMaterno) AS NombreCompleto,
                p.DNI,
                ar.NombreArea AS AreaNombre,
                a.HoraEntrada,
                a.HoraSalida,
                a.TurnoID,
                t.Descripcion AS TurnoNombre,
                t.HoraInicio AS TurnoInicio,
                t.HoraFin AS TurnoFin,
                CASE 
                    WHEN a.HoraEntrada IS NOT NULL AND a.HoraSalida IS NOT NULL 
                    THEN CAST(DATEDIFF(MINUTE, a.HoraEntrada, a.HoraSalida) AS FLOAT) / 60
                    ELSE NULL
                END AS HorasTrabajadas
            FROM Asistencia a
            INNER JOIN Practicante p ON a.PracticanteID = p.PracticanteID
            LEFT JOIN UltimaSolicitud us ON p.PracticanteID = us.PracticanteID AND us.rn = 1
            LEFT JOIN Area ar ON us.AreaID = ar.AreaID
            LEFT JOIN Turno t ON a.TurnoID = t.TurnoID
            WHERE a.Fecha = :fecha
            ORDER BY a.HoraEntrada
        ";
        
        return $this->executeQuery($sql, [':fecha' => $fecha], 'all');
    }
    
    public function getAsistenciasMensuales($mes, $anio) {
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
            )
            SELECT 
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
            LEFT JOIN UltimaSolicitud us ON p.PracticanteID = us.PracticanteID AND us.rn = 1
            LEFT JOIN Area ar ON us.AreaID = ar.AreaID
            WHERE MONTH(a.Fecha) = :mes AND YEAR(a.Fecha) = :anio
            ORDER BY a.Fecha, p.Nombres
        ";
        
        return $this->executeQuery($sql, [':mes' => $mes, ':anio' => $anio], 'all');
    }
    
    public function getAsistenciasAnuales($anio) {
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
            )
            SELECT 
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
            LEFT JOIN UltimaSolicitud us ON p.PracticanteID = us.PracticanteID AND us.rn = 1
            LEFT JOIN Area ar ON us.AreaID = ar.AreaID
            WHERE YEAR(a.Fecha) = :anio2
            GROUP BY 
                p.PracticanteID,
                p.Nombres, p.ApellidoPaterno, p.ApellidoMaterno,
                ar.NombreArea
            ORDER BY NombreCompleto
        ";
        
        return $this->executeQuery($sql, [':anio' => $anio, ':anio2' => $anio], 'all');
    }
    
    public function getHorasAcumuladas($practicanteID = null) {
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
            )
            SELECT 
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
            LEFT JOIN Asistencia a ON p.PracticanteID = a.PracticanteID
            LEFT JOIN UltimaSolicitud us ON p.PracticanteID = us.PracticanteID AND us.rn = 1
            LEFT JOIN Area ar ON us.AreaID = ar.AreaID
            " . ($practicanteID ? "WHERE p.PracticanteID = :practicanteID" : "") . "
            GROUP BY
            p.PracticanteID,
            p.Nombres,
            p.ApellidoPaterno,
            p.ApellidoMaterno,
            p.DNI,
            ar.NombreArea
            ORDER BY TotalHoras DESC
            ";
            $params = $practicanteID ? [':practicanteID' => $practicanteID] : [];
            return $this->executeQuery($sql, $params, 'all');
        }

    // ==================== ESTADÍSTICAS ====================

    public function countPracticantesActivos() {
        $sql = "
            SELECT COUNT(DISTINCT p.PracticanteID) AS total
            FROM Practicante p
            INNER JOIN Estado ep ON p.EstadoID = ep.EstadoID
            WHERE ep.Abreviatura = 'VIG'
        ";
        
        $result = $this->executeQuery($sql, [], 'one');
        return (int) ($result['total'] ?? 0);
    }

    public function countPracticantesCompletados() {
        $sql = "
            SELECT COUNT(DISTINCT p.PracticanteID) AS total
            FROM Practicante p
            INNER JOIN Estado ep ON p.EstadoID = ep.EstadoID
            WHERE ep.Abreviatura = 'FIN'
        ";
        
        $result = $this->executeQuery($sql, [], 'one');
        return (int) ($result['total'] ?? 0);
    }

    public function countAreas() {
        return $this->count([], 'Area');
    }

    public function getPromedioHorasDiarias() {
        $sql = "
            SELECT 
                AVG(
                    CASE 
                        WHEN HoraEntrada IS NOT NULL AND HoraSalida IS NOT NULL
                        THEN DATEDIFF(MINUTE, HoraEntrada, HoraSalida) / 60.0
                    END
                ) AS promedio
            FROM Asistencia
        ";
        
        $result = $this->executeQuery($sql, [], 'one');
        return round($result['promedio'] ?? 0, 2);
    }

    public function getDistribucionPorArea() {
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
                WHERE sp.EstadoID = 4 -- APR
            )
            SELECT 
                a.NombreArea AS area,
                COUNT(DISTINCT us.PracticanteID) AS cantidad
            FROM Area a
            LEFT JOIN UltimaSolicitud us ON a.AreaID = us.AreaID AND us.rn = 1
            GROUP BY a.AreaID, a.NombreArea
            ORDER BY cantidad DESC
        ";
        
        return $this->executeQuery($sql, [], 'all');
    }

    public function getAsistenciasMesActual() {
        $sql = "
            SELECT COUNT(*) AS total
            FROM Asistencia 
            WHERE MONTH(Fecha) = MONTH(GETDATE())
            AND YEAR(Fecha) = YEAR(GETDATE())
        ";
        
        $result = $this->executeQuery($sql, [], 'one');
        return (int) ($result['total'] ?? 0);
    }

    public function getPromedioHorasPorPracticante() {
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
                LEFT JOIN Area ar ON ar.AreaID = sp.AreaID
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
            LEFT JOIN Asistencia a ON p.PracticanteID = a.PracticanteID
            LEFT JOIN UltimaSolicitud us ON p.PracticanteID = us.PracticanteID AND us.rn = 1
            GROUP BY 
                p.PracticanteID, 
                CONCAT(p.Nombres, ' ', p.ApellidoPaterno, ' ', p.ApellidoMaterno),
                us.NombreArea
            ORDER BY PromedioHoras DESC
        ";
        
        return $this->executeQuery($sql, [], 'all');
    }

    public function getComparativoAreas() {
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
            ),
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
            SELECT 
                ar.NombreArea AS AreaNombre,
                COUNT(DISTINCT us.PracticanteID) AS TotalPracticantes,
                COUNT(DISTINCT CASE 
                    WHEN est.Abreviatura = 'VIG' THEN us.PracticanteID 
                END) AS Activos,
                COALESCE(SUM(hp.TotalAsistencias), 0) AS TotalAsistencias,
                COALESCE(SUM(hp.TotalHoras), 0) AS TotalHoras,
                COALESCE(
                    AVG(CASE WHEN hp.TotalAsistencias > 0 
                        THEN hp.TotalHoras 
                    END), 0
                ) AS PromedioHoras
            FROM Area ar
            LEFT JOIN UltimaSolicitud us ON ar.AreaID = us.AreaID AND us.rn = 1
            LEFT JOIN HorasPracticante hp ON us.PracticanteID = hp.PracticanteID
            LEFT JOIN Practicante p ON us.PracticanteID = p.PracticanteID
            LEFT JOIN Estado est ON p.EstadoID = est.EstadoID
            GROUP BY ar.AreaID, ar.NombreArea
            ORDER BY TotalPracticantes DESC
        ";
        
        return $this->executeQuery($sql, [], 'all');
    }
}