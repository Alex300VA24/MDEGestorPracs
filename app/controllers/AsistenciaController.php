<?php
namespace App\Controllers;

use App\Services\AsistenciaService;

class AsistenciaController {
    private $service;

    public function __construct($service = null) {
        $this->service = $service ?? new AsistenciaService();
    }

    /**
     * Registrar entrada con turno
     */
    public function registrarEntrada() {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            $practicanteID = $data['practicanteID'] ?? null;
            $turnoID = $data['turnoID'] ?? null;
            $horaEntrada = $data['horaEntrada'] ?? null;

            if (empty($practicanteID) || empty($turnoID)) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Datos incompletos. Se requiere practicanteID y turnoID',
                    'debug' => $data
                ]);
                return;
            }

            $response = $this->service->registrarEntrada($practicanteID, $turnoID, $horaEntrada);
            $this->jsonResponse($response);

        } catch (\Throwable $e) {
            error_log("Error en registrarEntrada: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Registrar salida
     */
    public function registrarSalida() {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            $practicanteID = $data['practicanteID'] ?? null;
            $horaSalida = $data['horaSalida'] ?? null;

            if (empty($practicanteID)) {
                throw new \Exception("Datos incompletos. Se requiere practicanteID");
            }

            $response = $this->service->registrarSalida($practicanteID, $horaSalida);
            $this->jsonResponse($response);

        } catch (\Throwable $e) {
            error_log("Error en registrarSalida: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Iniciar pausa
     */
    public function iniciarPausa() {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            $asistenciaID = $data['asistenciaID'] ?? null;
            $motivo = $data['motivo'] ?? null;

            if (empty($asistenciaID)) {
                throw new \Exception("Se requiere asistenciaID");
            }

            $response = $this->service->iniciarPausa($asistenciaID, $motivo);
            $this->jsonResponse($response);

        } catch (\Throwable $e) {
            error_log("Error en iniciarPausa: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Finalizar pausa
     */
    public function finalizarPausa() {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            $pausaID = $data['pausaID'] ?? null;

            if (empty($pausaID)) {
                throw new \Exception("Se requiere pausaID");
            }

            $response = $this->service->finalizarPausa($pausaID);
            $this->jsonResponse($response);

        } catch (\Throwable $e) {
            error_log("Error en finalizarPausa: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar asistencias por área
     */

    public function listarAsistencias() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $areaID = $input['areaID'] ?? null;
            $fecha = $input['fecha'] ?? null;

            if (!$areaID) {
                throw new \Exception("El parámetro areaID es requerido.");
            }

            $response = $this->service->listarAsistencias($areaID, $fecha);

            $this->jsonResponse([
                'success' => true,
                'data' => $response
            ]);
        } catch (\Throwable $e) {
            error_log("Error en listarAsistencias: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ===========================================
// CONTROLLER - AsistenciaController.php
// ===========================================

    /**
     * Obtener asistencia completa de un practicante
     */
    public function obtenerAsistenciaCompleta() {
        try {
            $practicanteID = $_GET['practicanteID'] ?? null;

            if (empty($practicanteID)) {
                throw new \Exception("Se requiere practicanteID");
            }

            $response = $this->service->obtenerAsistenciaCompleta($practicanteID);
            $this->jsonResponse($response);

        } catch (\Throwable $e) {
            error_log("Error en obtenerAsistenciaCompleta: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enviar respuesta JSON
     */
    protected function jsonResponse($data, $status = 200) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}