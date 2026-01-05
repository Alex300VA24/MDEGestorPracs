<?php

namespace App\Services;

use App\Repositories\AsistenciaRepository;

class AsistenciaService extends BaseService
{
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

    public function __construct()
    {
        date_default_timezone_set('America/Lima');
        $this->repository = new AsistenciaRepository();
    }

    /**
     * Registrar entrada con validación de horarios
     */
    public function registrarEntrada($practicanteID, $turnoID, $horaEntrada = null)
    {
        // Validaciones
        $this->validateId($practicanteID, 'PracticanteID');
        $this->validateId($turnoID, 'TurnoID');
        $this->validateInList($turnoID, array_keys(self::TURNOS), 'TurnoID');

        $fecha = date('Y-m-d');
        $hora = $horaEntrada ?? date('H:i:s');

        // Validar fecha de entrada del practicante
        $this->validarFechaEntradaPracticante($practicanteID, $fecha);

        // Ajustar hora según límites del turno
        $hora = $this->ajustarHoraEntrada($hora, $turnoID);

        // Verificar si ya existe asistencia para este turno hoy
        if ($this->repository->existeAsistenciaTurno($practicanteID, $fecha, $turnoID)) {
            throw new \Exception("Ya existe registro de entrada para el turno de " . self::TURNOS[$turnoID]['nombre'] . " hoy");
        }

        // Registrar entrada
        $resultado = $this->repository->registrarEntrada($practicanteID, $fecha, $hora, $turnoID);

        return $this->successResult([
            'horaRegistrada' => $hora,
            'turno' => self::TURNOS[$turnoID]['nombre'],
            'asistenciaID' => $resultado['asistenciaID']
        ], $resultado['message']);
    }

    /**
     * Registrar salida con validación de horarios
     */
    public function registrarSalida($practicanteID, $horaSalida = null)
    {
        // Validaciones
        $this->validateId($practicanteID, 'PracticanteID');

        $fecha = date('Y-m-d');
        $hora = $horaSalida ?? date('H:i:s');

        // Validar fecha de entrada del practicante
        $this->validarFechaEntradaPracticante($practicanteID, $fecha);

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

        return $this->successResult([
            'horaRegistrada' => $hora,
            'turno' => self::TURNOS[$turnoID]['nombre']
        ], $resultado['message']);
    }

    /**
     * Iniciar pausa
     */
    public function iniciarPausa($asistenciaID, $motivo = null)
    {
        // Validaciones
        $this->validateId($asistenciaID, 'AsistenciaID');

        if ($motivo !== null) {
            $this->validateStringLength($motivo, 3, 500, 'Motivo');
        }

        $horaInicio = date('H:i:s');

        // Verificar que no haya pausa activa
        if ($this->repository->tienePausaActiva($asistenciaID)) {
            throw new \Exception("Ya existe una pausa activa para esta asistencia");
        }

        $resultado = $this->repository->iniciarPausa($asistenciaID, $horaInicio, $motivo);

        return $this->successResult(
            $resultado['data'] ?? [],
            $resultado['message']
        );
    }

    /**
     * Finalizar pausa
     */
    public function finalizarPausa($pausaID)
    {
        // Validaciones
        $this->validateId($pausaID, 'PausaID');

        $horaFin = date('H:i:s');

        $resultado = $this->repository->finalizarPausa($pausaID, $horaFin);

        return $this->successResult(
            $resultado['data'] ?? [],
            $resultado['message']
        );
    }

    /**
     * Listar asistencias por área
     */
    public function listarAsistencias($areaID, $fecha = null)
    {
        // Validaciones
        $this->validateId($areaID, 'AreaID');

        if ($fecha !== null) {
            $this->validateDate($fecha, 'Y-m-d', 'Fecha');
        }

        $asistencias = $this->repository->obtenerAsistenciasPorArea($areaID, $fecha);

        return $this->successResult(
            $asistencias['data'] ?? [],
            'Asistencias obtenidas correctamente'
        );
    }

    /**
     * Obtener asistencia completa de un practicante para hoy
     */
    public function obtenerAsistenciaCompleta($practicanteID)
    {
        // Validaciones
        $this->validateId($practicanteID, 'PracticanteID');

        $fecha = date('Y-m-d');
        $asistencia = $this->repository->obtenerAsistenciaCompleta($practicanteID, $fecha);

        if (!$asistencia) {
            return $this->successResult(null, 'No se encontró asistencia para hoy');
        }

        return $this->successResult($asistencia, 'Asistencia obtenida correctamente');
    }

    /**
     * Validar fecha de entrada del practicante
     */
    private function validarFechaEntradaPracticante($practicanteID, $fechaActual)
    {
        $fechaEntradaPracticante = $this->repository->obtenerFechaEntradaPracticante($practicanteID);
        
        if ($fechaEntradaPracticante && $fechaEntradaPracticante > $fechaActual) {
            throw new \Exception(
                "No se puede registrar asistencia. La fecha de inicio de prácticas ({$fechaEntradaPracticante}) es posterior a la fecha actual."
            );
        }
    }

    /**
     * Ajustar hora de entrada según límites del turno
     */
    private function ajustarHoraEntrada($hora, $turnoID)
    {
        $turno = self::TURNOS[$turnoID];

        // Si es antes del inicio del turno, ajustar al inicio
        if ($hora < $turno['horaInicio']) {
            $this->log("Ajustando entrada de $hora a {$turno['horaInicio']}", 'INFO');
            return $turno['horaInicio'];
        }

        return $hora;
    }

    /**
     * Ajustar hora de salida según límites del turno
     */
    private function ajustarHoraSalida($hora, $turnoID)
    {
        $turno = self::TURNOS[$turnoID];

        // Si es después del fin del turno, ajustar al fin
        if ($hora > $turno['horaFin']) {
            $this->log("Ajustando salida de $hora a {$turno['horaFin']}", 'INFO');
            return $turno['horaFin'];
        }

        return $hora;
    }
}