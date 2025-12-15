<?php
namespace App\Services;

use App\Repositories\MensajeRepository;

class MensajeService {
    private $repository;
    
    public function __construct() {
        $this->repository = new MensajeRepository();
    }
    
    public function enviarSolicitudArea($solicitudID, $remitenteAreaID, $destinatarioAreaID, $contenido) {
        return $this->repository->enviarSolicitudArea($solicitudID, $remitenteAreaID, $destinatarioAreaID, $contenido);
    }
    
    public function responderSolicitud($mensajeID, $respuesta, $contenido) {
        return $this->repository->responderSolicitud($mensajeID, $respuesta, $contenido);
    }
    
    public function listarMensajesPorArea($areaID) {
        return $this->repository->listarMensajesPorArea($areaID);
    }
    public function eliminarMensaje($mensajeID) {
        return $this->repository->eliminarMensaje($mensajeID);
    }

}