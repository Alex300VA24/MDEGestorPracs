<?php
namespace App\Models;

class Estado {
    private $estadoID;
    private $abreviatura;
    private $descripcion;
    private $entidadAplicable;

    public function getEstadoID() { return $this->estadoID; }
    public function getAbreviatura() { return $this->abreviatura; }
    public function getDescripcion() { return $this->descripcion; }
    public function getEntidadAplicable() { return $this->entidadAplicable; }

    public function setEstadoID($v) { $this->estadoID = $v; }
    public function setAbreviatura($v) { $this->abreviatura = $v; }
    public function setDescripcion($v) { $this->descripcion = $v; }
    public function setEntidadAplicable($v) { $this->entidadAplicable = $v; }

    public function toArray() {
        return [
            'estadoID' => $this->estadoID,
            'abreviatura' => $this->abreviatura,
            'descripcion' => $this->descripcion,
            'entidadAplicable' => $this->entidadAplicable
        ];
    }
}
