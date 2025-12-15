<?php
namespace App\Controllers;

use App\Services\CertificadoService;

class CertificadoController {
    private $service;
    
    public function __construct() {
        $this->service = new CertificadoService();
    }

    public function obtenerEstadisticas() {
        try {
            $data = $this->service->obtenerEstadisticas();
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'totalVigentes' => $data['totalVigentes'],
                'totalFinalizados' => $data['totalFinalizados']
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ]);
        }
    }

    public function listarPracticantesParaCertificado() {
        try {
            $practicantes = $this->service->listarPracticantesParaCertificado();
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'practicantes' => $practicantes
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al listar practicantes: ' . $e->getMessage()
            ]);
        }
    }

    public function obtenerInformacionCertificado($practicanteID) {
        try {
            $info = $this->service->obtenerInformacionCompleta($practicanteID);
            
            if (!$info) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Practicante no encontrado'
                ]);
                return;
            }

            http_response_code(200);
            echo json_encode(array_merge(['success' => true], $info));
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener información: ' . $e->getMessage()
            ]);
        }
    }

    public function generarCertificado() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['practicanteID']) || !isset($input['numeroExpediente']) || !isset($input['formato'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos incompletos'
                ]);
                return;
            }

            $resultado = $this->service->generarCertificado(
                $input['practicanteID'],
                $input['numeroExpediente'],
                $input['formato']
            );

            http_response_code(200);
            echo json_encode($resultado);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al generar certificado: ' . $e->getMessage()
            ]);
        }
    }
}