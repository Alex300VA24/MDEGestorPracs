<?php
namespace App\Models;

class Turno {
    private $turnoID;
    private $descripcion;
    private $horaInicio;
    private $horaFin;

    // Getters
    public function getTurnoID() { return $this->turnoID; }
    public function getDescripcion() { return $this->descripcion; }
    public function getHoraInicio() { return $this->horaInicio; }
    public function getHoraFin() { return $this->horaFin; }

    // Setters
    public function setTurnoID($turnoID) { $this->turnoID = $turnoID; }
    public function setDescripcion($descripcion) { $this->descripcion = $descripcion; }
    public function setHoraInicio($horaInicio) { $this->horaInicio = $horaInicio; }
    public function setHoraFin($horaFin) { $this->horaFin = $horaFin; }

    public function toArray() {
        return [
            'turnoID' => $this->turnoID,
            'descripcion' => $this->descripcion,
            'horaInicio' => $this->horaInicio,
            'horaFin' => $this->horaFin
        ];
    }
}