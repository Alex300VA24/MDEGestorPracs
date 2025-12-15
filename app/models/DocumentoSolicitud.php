<?php
namespace App\Models;

class DocumentoSolicitud {
    private $documentoID;
    private $tipoDocumento;
    private $archivo;
    private $observaciones;
    private $fechaSubida;
    private $solicitudID;

    // Getters
    public function getDocumentoID() { return $this->documentoID; }
    public function getTipoDocumento() { return $this->tipoDocumento; }
    public function getArchivo() { return $this->archivo; }
    public function getObservaciones() { return $this->observaciones; }
    public function getFechaSubida() { return $this->fechaSubida; }
    public function getSolicitudID() { return $this->solicitudID; }

    // Setters
    public function setDocumentoID($documentoID) { $this->documentoID = $documentoID; }
    public function setTipoDocumento($tipoDocumento) { $this->tipoDocumento = $tipoDocumento; }
    public function setArchivo($archivo) { $this->archivo = $archivo; }
    public function setObservaciones($observaciones) { $this->observaciones = $observaciones; }
    public function setFechaSubida($fechaSubida) { $this->fechaSubida = $fechaSubida; }
    public function setSolicitudID($solicitudID) { $this->solicitudID = $solicitudID; }

    // Convertir a arreglo (para JSON)
    public function toArray() {
        return [
            'documentoID' => $this->documentoID,
            'tipoDocumento' => $this->tipoDocumento,
            'archivo' => $this->archivo ? base64_encode($this->archivo) : null,
            'observaciones' => $this->observaciones,
            'fechaSubida' => $this->fechaSubida,
            'solicitudID' => $this->solicitudID
        ];
    }
}
