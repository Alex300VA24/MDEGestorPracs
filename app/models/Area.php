<?php
namespace App\Models;

class Area {
    private $areaID;
    private $nombreArea;
    private $descripcion;

    public function getAreaID() { return $this->areaID; }
    public function getNombreArea() { return $this->nombreArea; }
    public function getDescripcion() { return $this->descripcion; }

    public function setAreaID($v) { $this->areaID = $v; }
    public function setNombreArea($v) { $this->nombreArea = $v; }
    public function setDescripcion($v) { $this->descripcion = $v; }

    public function toArray() {
        return [
            'areaID' => $this->areaID,
            'nombreArea' => $this->nombreArea,
            'descripcion' => $this->descripcion
        ];
    }

}