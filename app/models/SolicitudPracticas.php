<?php
namespace App\Models;

class SolicitudPracticas {
    private $solicitudID;
    private $fechaSolicitud;
    private $estadoID;
    private $practicanteID;
    private $areaID;

    // Getters
    public function getSolicitudID() { return $this->solicitudID; }
    public function getFechaSolicitud() { return $this->fechaSolicitud; }
    public function getEstadoID() { return $this->estadoID; }
    public function getPracticanteID() { return $this->practicanteID; }
    public function getAreaID() { return $this->areaID; }


    // Setters
    public function setSolicitudID($solicitudID) { $this->solicitudID = $solicitudID; }
    public function setFechaSolicitud($fechaSolicitud) { $this->fechaSolicitud = $fechaSolicitud; }
    public function setEstadoID($estadoID) { $this->estadoID = $estadoID; }
    public function setPracticanteID($practicanteID) { $this->practicanteID = $practicanteID; }
    public function setAreaID($areaID) { $this->areaID = $areaID; }


    // Convertir a arreglo (para JSON)
    public function toArray() {
        return [
            'solicitudID' => $this->solicitudID,
            'fechaSolicitud' => $this->fechaSolicitud,
            'estadoID' => $this->estadoID,
            'practicanteID' => $this->practicanteID,
            'areaID' => $this->areaID
        ];
    }
}
