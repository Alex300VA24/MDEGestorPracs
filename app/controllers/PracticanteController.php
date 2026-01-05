<?php
namespace App\Controllers;

use App\Services\PracticanteService;

class PracticanteController extends BaseController {
    private $practicanteService;
    
    public function __construct($practicanteService = null) {
        $this->practicanteService = $practicanteService ?? new PracticanteService();
    }
    
    /**
     * GET /api/practicantes
     */
    public function listarPracticantes() {
        $this->executeServiceAction(function() {
            $this->requireAuth();
            return $this->practicanteService->listarPracticantes();
        });
    }
    
    /**
     * GET /api/practicantes/{id}
     */
    public function obtener($practicanteID) {
        $this->executeServiceAction(function() use ($practicanteID) {
            $this->requireAuth();
            return $this->practicanteService->obtenerPorId($practicanteID);
        });
    }
    
    /**
     * POST /api/practicantes
     */
    public function registrarPracticante() {
        $this->executeServiceAction(function() {
            $this->requireAuth();
            $this->validateMethod('POST');
            
            $data = $this->getJsonInput();
            $practicanteID = $this->practicanteService->registrarPracticante($data);
            
            return [
                'data' => ['practicanteID' => $practicanteID],
                'message' => 'Practicante registrado exitosamente',
                'statusCode' => 201
            ];
        });
    }
    
    /**
     * PUT /api/practicantes/{id}
     */
    public function actualizar($id) {
        $this->executeServiceAction(function() use ($id) {
            $this->requireAuth();
            $this->validateMethod('PUT');
            
            $data = $this->getJsonInput();
            $mensaje = $this->practicanteService->actualizar($id, $data);
            
            return [
                'message' => $mensaje
            ];
        });
    }
    
    /**
     * DELETE /api/practicantes/{id}
     */
    public function eliminar($id) {
        $this->executeServiceAction(function() use ($id) {
            $this->requireAuth();
            $this->validateMethod('DELETE');
            
            $this->practicanteService->eliminar($id);
            
            return [
                'message' => 'Practicante eliminado correctamente'
            ];
        });
    }
    
    /**
     * POST /api/practicantes/filtrar
     */
    public function filtrarPracticantes() {
        $this->executeServiceAction(function() {
            $this->requireAuth();
            $this->validateMethod('POST');
            
            $input = $this->getJsonInput();
            $nombre = $input['nombre'] ?? null;
            $areaID = $input['areaID'] ?? null;
            
            return $this->practicanteService->filtrarPracticantes($nombre, $areaID);
        });
    }
    
    /**
     * POST /api/practicantes/aceptar
     */
    public function aceptarPracticante() {
        $this->executeServiceAction(function() {
            $this->requireAuth();
            $this->validateMethod('POST');
            
            $data = $this->getJsonInput();
            
            $this->practicanteService->aceptarPracticante(
                $data['practicanteID'] ?? null,
                $data['solicitudID'] ?? null,
                $data['areaID'] ?? null,
                $data['fechaEntradaVal'] ?? null,
                $data['fechaSalidaVal'] ?? null,
                $data['mensajeRespuesta'] ?? null
            );
            
            return [
                'message' => 'Practicante aceptado correctamente'
            ];
        });
    }
    
    /**
     * POST /api/practicantes/rechazar
     */
    public function rechazarPracticante() {
        $this->executeServiceAction(function() {
            $this->requireAuth();
            $this->validateMethod('POST');
            
            $data = $this->getJsonInput();
            
            $this->practicanteService->rechazarPracticante(
                $data['practicanteID'] ?? null,
                $data['solicitudID'] ?? null,
                $data['mensajeRespuesta'] ?? null
            );
            
            return [
                'message' => 'Practicante rechazado'
            ];
        });
    }
    
    /**
     * GET /api/practicantes/nombres
     */
    public function listarNombresPracticantes() {
        $this->executeServiceAction(function() {
            $this->requireAuth();
            return $this->practicanteService->listarNombresPracticantes();
        });
    }
}