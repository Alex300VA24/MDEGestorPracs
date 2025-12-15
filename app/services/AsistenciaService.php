<?php
namespace App\Services;

use App\Repositories\AsistenciaRepository;

class AsistenciaService {
    private $repository;

    // Configuración de turnos
    const TURNOS = [
        1 => [
            'nombre' => 'Mañana',
            'horaInicio' => '08:00:00',
            'horaFin' => '13:15:00'
        ],
        2 => [
            'nombre' => 'Tarde',
            'horaInicio' => '14:00:00',
            'horaFin' => '16:30:00'
        ]
    ];

    public function __construct() {
        date_default_timezone_set('America/Lima');
        $this->repository = new AsistenciaRepository();
    }

    /**
     * Registrar entrada con validación de horarios
     */
    public function registrarEntrada($practicanteID, $turnoID, $horaEntrada = null) {
        try {
            if (empty($practicanteID) || empty($turnoID)) {
                throw new \Exception("Datos incompletos en service");
            }

            $fecha = date('Y-m-d');
            $hora = $horaEntrada ?? date('H:i:s');

            // Validar que el turno existe
            if (!isset(self::TURNOS[$turnoID])) {
                throw new \Exception("Turno inválido");
            }

            // Ajustar hora según límites del turno
            $hora = $this->ajustarHoraEntrada($hora, $turnoID);

            // Verificar si ya existe asistencia para este turno hoy
            if ($this->repository->existeAsistenciaTurno($practicanteID, $fecha, $turnoID)) {
                throw new \Exception("Ya existe registro de entrada para el turno de " . self::TURNOS[$turnoID]['nombre'] . " hoy");
            }

            // Registrar entrada
            $resultado = $this->repository->registrarEntrada($practicanteID, $fecha, $hora, $turnoID);

            return [
                "success" => $resultado['success'],
                "message" => $resultado['message'],
                "data" => [
                    "horaRegistrada" => $hora,
                    "turno" => self::TURNOS[$turnoID]['nombre']
                ]
            ];

        } catch (\Throwable $e) {
            error_log("Service registrarEntrada: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Registrar salida con validación de horarios
     */
    public function registrarSalida($practicanteID, $horaSalida = null) {
        try {
            if (empty($practicanteID)) {
                throw new \Exception("Datos incompletos salida service");
            }

            $fecha = date('Y-m-d');
            $hora = $horaSalida ?? date('H:i:s');

            // Obtener asistencia activa (sin salida)
            $asistenciaActiva = $this->repository->obtenerAsistenciaActiva($practicanteID, $fecha);

            if (!$asistenciaActiva) {
                throw new \Exception("No se encontró registro de entrada activo para hoy");
            }

            $turnoID = $asistenciaActiva['TurnoID'];

            // Ajustar hora según límites del turno
            $hora = $this->ajustarHoraSalida($hora, $turnoID);

            // Registrar salida
            $resultado = $this->repository->registrarSalida($asistenciaActiva['AsistenciaID'], $hora);

            return [
                "success" => $resultado['success'],
                "message" => $resultado['message'],
                "data" => [
                    "horaRegistrada" => $hora,
                    "turno" => self::TURNOS[$turnoID]['nombre']
                ]
            ];

        } catch (\Throwable $e) {
            error_log("Service registrarSalida: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Iniciar pausa
     */
    public function iniciarPausa($asistenciaID, $motivo = null) {
        try {
            if (empty($asistenciaID)) {
                throw new \Exception("Se requiere asistenciaID");
            }

            $horaInicio = date('H:i:s');

            // Verificar que no haya pausa activa
            if ($this->repository->tienePausaActiva($asistenciaID)) {
                throw new \Exception("Ya existe una pausa activa para esta asistencia");
            }

            $resultado = $this->repository->iniciarPausa($asistenciaID, $horaInicio, $motivo);

            return [
                "success" => $resultado['success'],
                "message" => $resultado['message'],
                "data" => $resultado['data'] ?? []
            ];

        } catch (\Throwable $e) {
            error_log("Service iniciarPausa: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Finalizar pausa
     */
    public function finalizarPausa($pausaID) {
        try {
            if (empty($pausaID)) {
                throw new \Exception("Se requiere pausaID");
            }

            $horaFin = date('H:i:s');

            $resultado = $this->repository->finalizarPausa($pausaID, $horaFin);

            return [
                "success" => $resultado['success'],
                "message" => $resultado['message'],
                "data" => $resultado['data'] ?? []
            ];

        } catch (\Throwable $e) {
            error_log("Service finalizarPausa: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Listar asistencias por área
     */
    public function listarAsistencias($areaID, $fecha = null) {
        try {
            if (!$areaID) {
                throw new \Exception("El área del usuario no está definida.");
            }

            $asistencias = $this->repository->obtenerAsistenciasPorArea($areaID, $fecha);
            return $asistencias;

        } catch (\Throwable $e) {
            error_log("❌ Error en Service listarAsistencias: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Ajustar hora de entrada según límites del turno
     */
    private function ajustarHoraEntrada($hora, $turnoID) {
        $turno = self::TURNOS[$turnoID];
        
        // Si es antes del inicio del turno, ajustar al inicio
        if ($hora < $turno['horaInicio']) {
            error_log("Ajustando entrada de $hora a {$turno['horaInicio']}");
            return $turno['horaInicio'];
        }
        
        return $hora;
    }

    /**
     * Ajustar hora de salida según límites del turno
     */
    private function ajustarHoraSalida($hora, $turnoID) {
        $turno = self::TURNOS[$turnoID];
        
        // Si es después del fin del turno, ajustar al fin
        if ($hora > $turno['horaFin']) {
            error_log("Ajustando salida de $hora a {$turno['horaFin']}");
            return $turno['horaFin'];
        }
        
        return $hora;
    }

    /**
     * Obtener asistencia completa de un practicante para hoy
     */
    public function obtenerAsistenciaCompleta($practicanteID) {
        try {
            if (empty($practicanteID)) {
                throw new \Exception("Se requiere practicanteID");
            }

            $fecha = date('Y-m-d');
            $asistencia = $this->repository->obtenerAsistenciaCompleta($practicanteID, $fecha);
            
            return [
                'success' => true,
                'data' => $asistencia
            ];

        } catch (\Throwable $e) {
            error_log("Service obtenerAsistenciaCompleta: " . $e->getMessage());
            throw $e;
        }
    }
}