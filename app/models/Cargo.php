<?php
namespace App\Models;

class Cargo {
    private $cargoID;
    private $nombreCargo;
    private $descripcion;

    public function getCargoID() { return $this->cargoID; }
    public function getNombreCargo() { return $this->nombreCargo; }
    public function getDescripcion() { return $this->descripcion; }

    public function setCargoID($v) { $this->cargoID = $v; }
    public function setNombreCargo($v) { $this->nombreCargo = $v; }
    public function setDescripcion($v) { $this->descripcion = $v; }

    public function toArray() {
        return [
            'cargoID' => $this->cargoID,
            'nombreCargo' => $this->nombreCargo,
            'descripcion' => $this->descripcion
        ];
    }
}
