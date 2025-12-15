<?php 
namespace App\Models;

class Asistencia {
    private $asistenciaID;
    private $fecha;
    private $horaEntrada;
    private $horaSalida;
    private $practicanteID;
    private $turnoID;

    // Getters
    public function getAsistenciaID() { return $this->asistenciaID; } 
    public function getFecha() { return $this->fecha; } 
    public function getHoraEntrada() { return $this->horaEntrada; } 
    public function getHoraSalida() { return $this->horaSalida; } 
    public function getPracticanteID() { return $this->practicanteID; } 
    public function getTurnoID() { return $this->turnoID; }
    
    // Setters
    public function setAsistenciaID($asistenciaID) { $this->asistenciaID = $asistenciaID; }
    public function setFecha($fecha) { $this->fecha = $fecha; }
    public function setHoraEntrada($horaEntrada) { $this->horaEntrada = $horaEntrada; }
    public function setHoraSalida($horaSalida) { $this->horaSalida = $horaSalida; }
    public function setPracticanteID($practicanteID) { $this->practicanteID = $practicanteID; }
    public function setTurnoID($turnoID) { $this->turnoID = $turnoID; }

    public function toArray() {
        return [
            'asistencia' => $this->asistenciaID,
            'fecha' => $this->fecha,
            'horaEntrada' => $this->horaEntrada,
            'horaSalida' => $this->horaSalida,
            'practicanteID' => $this->practicanteID,
            'turnoID' => $this->turnoID 
        ];
    }
}