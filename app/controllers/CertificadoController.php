<?php
namespace App\Controllers;

use App\Services\CertificadoService;

class CertificadoController extends BaseController {
    private $service;
    
    public function __construct($service = null) {
        $this->service = $service ?? new CertificadoService();
    }

    /**
     * Obtener estadísticas de certificados
     */
    public function obtenerEstadisticas() {
        try {
            $this->validateMethod('GET');
            
            $this->executeServiceAction(function() {
                return $this->service->obtenerEstadisticas();
            });
        } catch (\Exception $e) {
            error_log("Error en obtenerEstadisticas: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Listar practicantes elegibles para certificado
     */
    public function listarPracticantesParaCertificado() {
        try {
            $this->validateMethod('GET');
            
            $this->executeServiceAction(function() {
                return $this->service->listarPracticantesParaCertificado();
            });
        } catch (\Exception $e) {
            error_log("Error en listarPracticantesParaCertificado: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Obtener información completa para certificado
     */
    public function obtenerInformacionCertificado($practicanteID) {
        try {
            $this->validateMethod('GET');
            
            if (!$practicanteID) {
                throw new \Exception('Se requiere el ID del practicante');
            }
            
            $this->executeServiceAction(function() use ($practicanteID) {
                return $this->service->obtenerInformacionCompleta($practicanteID);
            });
        } catch (\Exception $e) {
            error_log("Error en obtenerInformacionCertificado: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Generar certificado en PDF o Word
     */
    public function generarCertificado() {
        try {
            $this->validateMethod('POST');
            
            $input = $this->getValidatedInput(['practicanteID', 'numeroExpediente', 'formato']);
            
            // Validar formato
            $this->validateInList(
                $input['formato'], 
                ['pdf', 'word'], 
                'Formato'
            );
            
            $resultado = $this->service->generarCertificado(
                $input['practicanteID'],
                $input['numeroExpediente'],
                $input['formato']
            );
            
            $this->successResponse($resultado, $resultado['message'] ?? 'Certificado generado exitosamente');
        } catch (\Exception $e) {
            error_log("Error en generarCertificado: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Obtener historial de certificados generados
     */
    public function obtenerHistorialCertificados() {
        try {
            $this->validateMethod('GET');
            
            $practicanteID = $_GET['practicanteID'] ?? null;
            
            $this->executeServiceAction(function() use ($practicanteID) {
                return $this->service->obtenerHistorialCertificados($practicanteID);
            });
        } catch (\Exception $e) {
            error_log("Error en obtenerHistorialCertificados: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Validar lista de valores permitidos
     */
    private function validateInList($value, array $allowedValues, $fieldName = 'Valor') {
        if (!in_array($value, $allowedValues, true)) {
            throw new \Exception("$fieldName debe ser uno de: " . implode(', ', $allowedValues));
        }
    }
}