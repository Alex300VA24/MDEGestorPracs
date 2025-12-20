<?php

namespace App\Controllers;

use App\Services\DocumentoService;

/**
 * Controlador para gestionar documentos de solicitudes
 * Maneja las peticiones HTTP y delega la lógica al servicio
 */
class DocumentoController {
    private $documentoService;

    public function __construct() {
        $this->documentoService = new DocumentoService();
    }

    /**
     * Obtener documentos de la solicitud ACTIVA del practicante
     * GET: /api/documentos?practicanteID={id}
     */
    public function obtenerDocumentosPorPracticante() {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $practicanteID = $_GET['practicanteID'] ?? null;

            if (!$practicanteID || !is_numeric($practicanteID)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'PracticanteID es requerido y debe ser numérico'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $resultado = $this->documentoService->obtenerDocumentosSolicitudActiva($practicanteID);

            http_response_code(200);
            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            error_log('Error en obtenerDocumentosPorPracticante: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener documentos: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}