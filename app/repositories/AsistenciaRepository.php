<?php

namespace App\Repositories;

use App\Config\Database;
use PDO;

class AsistenciaRepository extends BaseRepository
{
    protected $table = 'Asistencia';
    protected $primaryKey = 'AsistenciaID';

    /**
     * Verificar si existe asistencia para un turno específico
     */
    public function existeAsistenciaTurno($practicanteID, $fecha, $turnoID)
    {
        return $this->exists('PracticanteID', $practicanteID) && 
               $this->executeQuery(
                   "SELECT COUNT(*) as total FROM {$this->table} 
                    WHERE PracticanteID = :practicanteID AND Fecha = :fecha AND TurnoID = :turnoID",
                   [
                       ':practicanteID' => $practicanteID,
                       ':fecha' => $fecha,
                       ':turnoID' => $turnoID
                   ],
                   'one'
               )['total'] > 0;
    }

    /**
     * Verificar si existe asistencia (cualquier turno)
     */
    public function existeAsistencia($practicanteID, $fecha)
    {
        return $this->count([
            'PracticanteID' => $practicanteID,
            'Fecha' => $fecha
        ]) > 0;
    }

    /**
     * Registrar entrada
     */
    public function registrarEntrada($practicanteID, $fecha, $horaEntrada, $turnoID)
    {
        $asistenciaID = $this->insertAndGetId([
            'PracticanteID' => $practicanteID,
            'Fecha' => $fecha,
            'HoraEntrada' => $horaEntrada,
            'TurnoID' => $turnoID
        ]);
        
        return [
            'success' => true,
            'message' => "Entrada registrada correctamente a las $horaEntrada",
            'asistenciaID' => $asistenciaID
        ];
    }

    /**
     * Obtener asistencia activa (sin hora de salida)
     */
    public function obtenerAsistenciaActiva($practicanteID, $fecha)
    {
        return $this->executeQuery(
            "SELECT TOP 1 AsistenciaID, HoraEntrada, TurnoID 
             FROM {$this->table} 
             WHERE PracticanteID = :practicanteID AND Fecha = :fecha AND HoraSalida IS NULL
             ORDER BY HoraEntrada DESC",
            [
                ':practicanteID' => $practicanteID,
                ':fecha' => $fecha
            ],
            'one'
        );
    }

    /**
     * Registrar salida
     */
    public function registrarSalida($asistenciaID, $horaSalida)
    {
        $updated = $this->update($asistenciaID, [
            'HoraSalida' => $horaSalida
        ]);

        if (!$updated) {
            throw new \Exception("No se pudo registrar la salida");
        }

        return [
            'success' => true,
            'message' => "Salida registrada correctamente a las $horaSalida"
        ];
    }

    /**
     * Verificar si hay pausa activa
     */
    public function tienePausaActiva($asistenciaID)
    {
        return $this->count([
            'AsistenciaID' => $asistenciaID
        ], 'Pausa') > 0 && 
        $this->executeQuery(
            "SELECT COUNT(*) as total FROM Pausa 
             WHERE AsistenciaID = :asistenciaID AND HoraFin IS NULL",
            [':asistenciaID' => $asistenciaID],
            'one'
        )['total'] > 0;
    }

    /**
     * Iniciar pausa
     */
    public function iniciarPausa($asistenciaID, $horaInicio, $motivo)
    {
        $sql = "INSERT INTO Pausa (AsistenciaID, HoraInicio, Motivo)
                OUTPUT INSERTED.PausaID
                VALUES (:asistenciaID, :horaInicio, :motivo)";

        $result = $this->executeQuery(
            $sql,
            [
                ':asistenciaID' => $asistenciaID,
                ':horaInicio' => $horaInicio,
                ':motivo' => $motivo
            ],
            'one'
        );

        return [
            'success' => true,
            'message' => 'Pausa iniciada correctamente',
            'data' => [
                'pausaID' => $result['PausaID'],
                'horaInicio' => $horaInicio
            ]
        ];
    }

    /**
     * Finalizar pausa
     */
    public function finalizarPausa($pausaID, $horaFin)
    {
        // Obtener hora de inicio
        $pausa = $this->executeQuery(
            "SELECT HoraInicio FROM Pausa WHERE PausaID = :pausaID",
            [':pausaID' => $pausaID],
            'one'
        );

        if (!$pausa) {
            throw new \Exception("Pausa no encontrada");
        }

        // Actualizar pausa
        $this->updateWhereTable('Pausa', 
            ['HoraFin' => $horaFin],
            ['PausaID' => $pausaID]
        );

        // Calcular duración
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
    }

    /**
     * Obtener pausas de una asistencia
     */
    public function obtenerPausas($asistenciaID)
    {
        return $this->executeQuery(
            "SELECT PausaID, HoraInicio, HoraFin, Motivo
             FROM Pausa
             WHERE AsistenciaID = :asistenciaID
             ORDER BY HoraInicio",
            [':asistenciaID' => $asistenciaID],
            'all'
        );
    }

    /**
     * Obtener asistencias por área con información completa
     */
    public function obtenerAsistenciasPorArea($areaID, $fecha = null)
    {
        $fechaConsulta = $fecha ?? date('Y-m-d');

        $sql = "
            SELECT 
                p.PracticanteID,
                CONCAT(
                    p.Nombres, ' ',
                    p.ApellidoPaterno, ' ',
                    p.ApellidoMaterno
                ) AS NombreCompleto,
                p.FechaEntrada AS FechaInicioPracticas,

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

            INNER JOIN Estado eP
                ON p.EstadoID = eP.EstadoID
            AND eP.Abreviatura = 'VIG'

            /* ÚLTIMA SOLICITUD APROBADA */
            OUTER APPLY (
                SELECT TOP 1
                    sp.SolicitudID,
                    sp.AreaID
                FROM SolicitudPracticas sp
                INNER JOIN Estado eS
                    ON sp.EstadoID = eS.EstadoID
                WHERE sp.PracticanteID = p.PracticanteID
                AND eS.Abreviatura = 'APR'
                ORDER BY sp.FechaSolicitud DESC,
                        sp.SolicitudID DESC
            ) ultAPR

            INNER JOIN Area ar
                ON ar.AreaID = ultAPR.AreaID
            AND ar.AreaID = :areaID

            LEFT JOIN Asistencia a
                ON p.PracticanteID = a.PracticanteID
            AND a.Fecha = :fecha

            LEFT JOIN Turno t
                ON a.TurnoID = t.TurnoID

            ORDER BY 
                p.Nombres,
                p.ApellidoPaterno,
                p.ApellidoMaterno

        ";

        $asistencias = $this->executeQuery(
            $sql,
            [
                ':fecha' => $fechaConsulta,
                ':areaID' => $areaID
            ],
            'all'
        );

        // Agregar pausas y calcular tiempos
        foreach ($asistencias as &$asistencia) {
            if (!$asistencia['Fecha']) {
                $asistencia['Fecha'] = $fechaConsulta;
            }

            if ($asistencia['AsistenciaID']) {
                $asistencia['Pausas'] = $this->obtenerPausas($asistencia['AsistenciaID']);
                $asistencia['TiempoPausas'] = $this->calcularTiempoPausas($asistencia['Pausas']);
            } else {
                $asistencia['Pausas'] = [];
                $asistencia['TiempoPausas'] = 0;
            }
        }
        unset($asistencia);

        return [
            'success' => true,
            'data' => $asistencias
        ];
    }

    /**
     * Obtener asistencia completa de un practicante
     */
    public function obtenerAsistenciaCompleta($practicanteID, $fecha)
    {
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
            WHERE a.PracticanteID = :practicanteID AND a.Fecha = :fecha
            ORDER BY a.HoraEntrada DESC
        ";

        $asistencia = $this->executeQuery(
            $sql,
            [
                ':practicanteID' => $practicanteID,
                ':fecha' => $fecha
            ],
            'one'
        );

        if (!$asistencia) {
            return null;
        }

        // Agregar pausas
        $asistencia['Pausas'] = $this->obtenerPausas($asistencia['AsistenciaID']);
        $asistencia['TiempoPausas'] = $this->calcularTiempoPausas($asistencia['Pausas']);

        return $asistencia;
    }

    /**
     * Obtener fecha de entrada del practicante
     */
    public function obtenerFechaEntradaPracticante($practicanteID)
    {
        $result = $this->executeQuery(
            "SELECT FechaEntrada FROM Practicante WHERE PracticanteID = :practicanteID",
            [':practicanteID' => $practicanteID],
            'one'
        );

        return $result['FechaEntrada'] ?? null;
    }

    /**
     * Calcular tiempo total de pausas en segundos
     */
    private function calcularTiempoPausas(array $pausas)
    {
        $tiempoTotal = 0;
        
        foreach ($pausas as $pausa) {
            if ($pausa['HoraFin']) {
                $inicio = new \DateTime($pausa['HoraInicio']);
                $fin = new \DateTime($pausa['HoraFin']);
                $tiempoTotal += $fin->getTimestamp() - $inicio->getTimestamp();
            }
        }
        
        return $tiempoTotal;
    }
}