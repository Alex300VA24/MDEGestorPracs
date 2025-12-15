<?php
namespace App\Controllers;

use App\Services\DashboardService;
use App\Config\Database;

class DashboardController {
    private $service;
    private $db;

    public function __construct() {
        $this->service = new DashboardService();
        $this->db = Database::getInstance()->getConnection();
    }

    public function obtenerDatosInicio() {
        header('Content-Type: application/json');

        try {
            // Obtener parámetros
            $areaID = $_GET['areaID'] ?? null;
            $usuarioID = $_SESSION['usuarioID'] ?? null;
            $cargoID = $_SESSION['cargoID'] ?? null;

            // Establecer contexto de usuario para los triggers
            if ($usuarioID) {
                $this->establecerContextoUsuario($usuarioID);
            }

            // Llamar al servicio
            $data = $this->service->obtenerDatosInicio($usuarioID, $areaID, $cargoID);

            echo json_encode([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            error_log("Error en obtenerDatosInicio: " . $e->getMessage());
            
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener los datos del inicio',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Establece el contexto del usuario en SQL Server para los triggers
     */
    private function establecerContextoUsuario($usuarioID) {
        try {
            $stmt = $this->db->prepare("EXEC sp_SetCurrentUser @UsuarioID = ?");
            $stmt->execute([$usuarioID]);
        } catch (\Exception $e) {
            error_log("Error al establecer contexto de usuario: " . $e->getMessage());
            // No lanzar excepción, solo loguear
        }
    }
}