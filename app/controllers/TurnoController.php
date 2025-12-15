<?php
namespace App\Controllers;

use App\Services\TurnoService;

class TurnoController {
    private $service;
    
    public function __construct() {
        $this->service = new TurnoService();
        $this->checkAuth();
    }
    
    private function checkAuth() {
        if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
            $this->jsonResponse(['success' => false, 'message' => 'No autorizado'], 401);
        }
    }
    
    public function listar() {
        try {
            $turnos = $this->service->listarTurnos();
            $this->jsonResponse([
                'success' => true,
                'data' => $turnos
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function obtenerPorPracticante($practicanteID) {
        try {
            $turnos = $this->service->obtenerTurnosPracticante($practicanteID);
            $this->jsonResponse([
                'success' => true,
                'data' => $turnos
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    protected function jsonResponse($data, $status = 200) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}