<?php
namespace App\Controllers;

use App\Services\AsistenciaService;

class AsistenciaController extends BaseController {
    private $service;

    public function __construct($service = null) {
        $this->service = $service ?? new AsistenciaService();
    }

    /**
     * Registrar entrada con turno
     */
    public function registrarEntrada() {
        try {
            $this->validateMethod('POST');
            
            $input = $this->getValidatedInput(['practicanteID', 'turnoID']);
            
            $this->executeServiceAction(function() use ($input) {
                return $this->service->registrarEntrada(
                    $input['practicanteID'],
                    $input['turnoID'],
                    $input['horaEntrada'] ?? null
                );
            });
            
        } catch (\Exception $e) {
            error_log("Error en registrarEntrada: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Registrar salida
     */
    public function registrarSalida() {
        try {
            $this->validateMethod('POST');
            
            $input = $this->getValidatedInput(['practicanteID']);
            
            $this->executeServiceAction(function() use ($input) {
                return $this->service->registrarSalida(
                    $input['practicanteID'],
                    $input['horaSalida'] ?? null
                );
            });
            
        } catch (\Exception $e) {
            error_log("Error en registrarSalida: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Iniciar pausa
     */
    public function iniciarPausa() {
        try {
            $this->validateMethod('POST');
            
            $input = $this->getValidatedInput(['asistenciaID']);
            
            $this->executeServiceAction(function() use ($input) {
                return $this->service->iniciarPausa(
                    $input['asistenciaID'],
                    $input['motivo'] ?? null
                );
            });
            
        } catch (\Exception $e) {
            error_log("Error en iniciarPausa: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Finalizar pausa
     */
    public function finalizarPausa() {
        try {
            $this->validateMethod('POST');
            
            $input = $this->getValidatedInput(['pausaID']);
            
            $this->executeServiceAction(function() use ($input) {
                return $this->service->finalizarPausa($input['pausaID']);
            });
            
        } catch (\Exception $e) {
            error_log("Error en finalizarPausa: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Listar asistencias por área
     */
    public function listarAsistencias() {
        try {
            $this->validateMethod('POST');
            
            $input = $this->getValidatedInput(['areaID']);
            
            $this->executeServiceAction(function() use ($input) {
                return $this->service->listarAsistencias(
                    $input['areaID'],
                    $input['fecha'] ?? null
                );
            });
            
        } catch (\Exception $e) {
            error_log("Error en listarAsistencias: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Obtener asistencia completa de un practicante
     */
    public function obtenerAsistenciaCompleta() {
        try {
            $this->validateMethod('GET');
            
            $practicanteID = $_GET['practicanteID'] ?? null;
            
            if (empty($practicanteID)) {
                throw new \Exception("Se requiere practicanteID");
            }
            
            $this->executeServiceAction(function() use ($practicanteID) {
                return $this->service->obtenerAsistenciaCompleta($practicanteID);
            });
            
        } catch (\Exception $e) {
            error_log("Error en obtenerAsistenciaCompleta: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }
}