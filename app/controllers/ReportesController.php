<?php

namespace App\Controllers;

use App\Services\ReportesService;

class ReportesController {
    private $reportesService;
    
    public function __construct($reportesService = null) {
        $this->reportesService = $reportesService ?? new ReportesService();
    }
    
    private function sendJsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // ==================== REPORTES DE PRACTICANTES ====================
    
    public function practicantesActivos() {
        try {
            $result = $this->reportesService->obtenerPracticantesActivos();
            $this->sendJsonResponse([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function practicantesCompletados() {
        try {
            $result = $this->reportesService->obtenerPracticantesCompletados();
            $this->sendJsonResponse([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function practicantesPorArea() {
        try {
            $areaID = $_GET['areaID'] ?? null;
            $result = $this->reportesService->obtenerPracticantesPorArea($areaID);
            $this->sendJsonResponse([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function practicantesPorUniversidad() {
        try {
            $result = $this->reportesService->obtenerPracticantesPorUniversidad();
            $this->sendJsonResponse([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    // ==================== REPORTES DE ASISTENCIA ====================
    
    public function asistenciaPorPracticante() {
        try {
            $practicanteID = $_GET['practicanteID'] ?? null;
            $fechaInicio = $_GET['fechaInicio'] ?? null;
            $fechaFin = $_GET['fechaFin'] ?? null;
            
            if (!$practicanteID) {
                throw new \Exception('Se requiere el ID del practicante');
            }
            
            $result = $this->reportesService->obtenerAsistenciaPorPracticante($practicanteID, $fechaInicio, $fechaFin);
            $this->sendJsonResponse([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function asistenciaDelDia() {
        try {
            $fecha = $_GET['fecha'] ?? date('Y-m-d');
            $result = $this->reportesService->obtenerAsistenciaDelDia($fecha);
            $this->sendJsonResponse([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function asistenciaMensual() {
        try {
            $mes = $_GET['mes'] ?? date('m');
            $anio = $_GET['anio'] ?? date('Y');
            
            $result = $this->reportesService->obtenerAsistenciaMensual($mes, $anio);
            $this->sendJsonResponse([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function asistenciaAnual() {
        try {
            $anio = $_GET['anio'] ?? date('Y');
            
            $result = $this->reportesService->obtenerAsistenciaAnual($anio);
            $this->sendJsonResponse([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function horasAcumuladas() {
        try {
            $practicanteID = $_GET['practicanteID'] ?? null;
            $result = $this->reportesService->obtenerHorasAcumuladas($practicanteID);
            $this->sendJsonResponse([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    // ==================== REPORTES ESTADÍSTICOS ====================
    
    public function estadisticasGenerales() {
        try {
            $result = $this->reportesService->obtenerEstadisticasGenerales();
            $this->sendJsonResponse([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function promedioHoras() {
        try {
            $result = $this->reportesService->obtenerPromedioHoras();
            $this->sendJsonResponse([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function comparativoAreas() {
        try {
            $result = $this->reportesService->obtenerComparativoAreas();
            $this->sendJsonResponse([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function reporteCompleto() {
        try {
            $result = $this->reportesService->obtenerReporteCompleto();
            $this->sendJsonResponse([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    // ==================== EXPORTACIONES ====================
    
    public function exportarPDF() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $tipoReporte = $input['tipoReporte'] ?? null;
            $datos = $input['datos'] ?? null;
            
            if (!$tipoReporte || !$datos) {
                throw new \Exception('Faltan datos para la exportación');
            }
            
            $pdf = $this->reportesService->generarPDF($tipoReporte, $datos);
            
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="reporte_' . date('Y-m-d') . '.pdf"');
            echo $pdf;
            exit;
        } catch (\Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function exportarExcel() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $tipoReporte = $input['tipoReporte'] ?? null;
            $datos = $input['datos'] ?? null;
            
            if (!$tipoReporte || !$datos) {
                throw new \Exception('Faltan datos para la exportación');
            }
            
            $excel = $this->reportesService->generarExcel($tipoReporte, $datos);
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="reporte_' . date('Y-m-d') . '.xlsx"');
            echo $excel;
            exit;
        } catch (\Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function exportarWord() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $tipoReporte = $input['tipoReporte'] ?? null;
            $datos = $input['datos'] ?? null;
            
            if (!$tipoReporte || !$datos) {
                throw new \Exception('Faltan datos para la exportación');
            }
            
            $word = $this->reportesService->generarWord($tipoReporte, $datos);
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment; filename="reporte_' . date('Y-m-d') . '.docx"');
            echo $word;
            exit;
        } catch (\Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}