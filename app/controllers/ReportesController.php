<?php

namespace App\Controllers;

use App\Services\ReportesService;

class ReportesController extends BaseController {
    private $reportesService;
    
    public function __construct($reportesService = null) {
        $this->reportesService = $reportesService ?? new ReportesService();
    }
    
    // ==================== REPORTES DE PRACTICANTES ====================
    
    public function practicantesActivos() {
        try {
            $this->validateMethod('GET');
            
            $this->executeServiceAction(function() {
                return $this->reportesService->obtenerPracticantesActivos();
            });
        } catch (\Exception $e) {
            error_log("Error en practicantesActivos: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }
    
    public function practicantesCompletados() {
        try {
            $this->validateMethod('GET');
            
            $this->executeServiceAction(function() {
                return $this->reportesService->obtenerPracticantesCompletados();
            });
        } catch (\Exception $e) {
            error_log("Error en practicantesCompletados: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }
    
    public function practicantesPorArea() {
        try {
            $this->validateMethod('GET');
            
            $areaID = $_GET['areaID'] ?? null;
            
            $this->executeServiceAction(function() use ($areaID) {
                return $this->reportesService->obtenerPracticantesPorArea($areaID);
            });
        } catch (\Exception $e) {
            error_log("Error en practicantesPorArea: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }
    
    public function practicantesPorUniversidad() {
        try {
            $this->validateMethod('GET');
            
            $this->executeServiceAction(function() {
                return $this->reportesService->obtenerPracticantesPorUniversidad();
            });
        } catch (\Exception $e) {
            error_log("Error en practicantesPorUniversidad: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }
    
    // ==================== REPORTES DE ASISTENCIA ====================
    
    public function asistenciaPorPracticante() {
        try {
            $this->validateMethod('GET');
            
            $practicanteID = $_GET['practicanteID'] ?? null;
            $fechaInicio = $_GET['fechaInicio'] ?? null;
            $fechaFin = $_GET['fechaFin'] ?? null;
            
            if (!$practicanteID) {
                throw new \Exception('Se requiere el ID del practicante');
            }
            
            $this->executeServiceAction(function() use ($practicanteID, $fechaInicio, $fechaFin) {
                return $this->reportesService->obtenerAsistenciaPorPracticante(
                    $practicanteID, 
                    $fechaInicio, 
                    $fechaFin
                );
            });
        } catch (\Exception $e) {
            error_log("Error en asistenciaPorPracticante: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }
    
    public function asistenciaDelDia() {
        try {
            $this->validateMethod('GET');
            
            $fecha = $_GET['fecha'] ?? date('Y-m-d');
            
            $this->executeServiceAction(function() use ($fecha) {
                return $this->reportesService->obtenerAsistenciaDelDia($fecha);
            });
        } catch (\Exception $e) {
            error_log("Error en asistenciaDelDia: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }
    
    public function asistenciaMensual() {
        try {
            $this->validateMethod('GET');
            
            $mes = $_GET['mes'] ?? date('m');
            $anio = $_GET['anio'] ?? date('Y');
            
            $this->executeServiceAction(function() use ($mes, $anio) {
                return $this->reportesService->obtenerAsistenciaMensual($mes, $anio);
            });
        } catch (\Exception $e) {
            error_log("Error en asistenciaMensual: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function asistenciaAnual() {
        try {
            $this->validateMethod('GET');
            
            $anio = $_GET['anio'] ?? date('Y');
            
            $this->executeServiceAction(function() use ($anio) {
                return $this->reportesService->obtenerAsistenciaAnual($anio);
            });
        } catch (\Exception $e) {
            error_log("Error en asistenciaAnual: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }
    
    public function horasAcumuladas() {
        try {
            $this->validateMethod('GET');
            
            $practicanteID = $_GET['practicanteID'] ?? null;
            
            $this->executeServiceAction(function() use ($practicanteID) {
                return $this->reportesService->obtenerHorasAcumuladas($practicanteID);
            });
        } catch (\Exception $e) {
            error_log("Error en horasAcumuladas: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }
    
    // ==================== REPORTES ESTADÍSTICOS ====================
    
    public function estadisticasGenerales() {
        try {
            $this->validateMethod('GET');
            
            $this->executeServiceAction(function() {
                return $this->reportesService->obtenerEstadisticasGenerales();
            });
        } catch (\Exception $e) {
            error_log("Error en estadisticasGenerales: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }
    
    public function promedioHoras() {
        try {
            $this->validateMethod('GET');
            
            $this->executeServiceAction(function() {
                return $this->reportesService->obtenerPromedioHoras();
            });
        } catch (\Exception $e) {
            error_log("Error en promedioHoras: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }
    
    public function comparativoAreas() {
        try {
            $this->validateMethod('GET');
            
            $this->executeServiceAction(function() {
                return $this->reportesService->obtenerComparativoAreas();
            });
        } catch (\Exception $e) {
            error_log("Error en comparativoAreas: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }
    
    public function reporteCompleto() {
        try {
            $this->validateMethod('GET');
            
            $this->executeServiceAction(function() {
                return $this->reportesService->obtenerReporteCompleto();
            });
        } catch (\Exception $e) {
            error_log("Error en reporteCompleto: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }
    
    // ==================== EXPORTACIONES ====================
    
    public function exportarPDF() {
        try {
            $this->validateMethod('POST');
            
            $input = $this->getValidatedInput(['tipoReporte', 'datos']);
            
            $pdf = $this->reportesService->generarPDF(
                $input['tipoReporte'], 
                $input['datos']
            );
            
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="reporte_' . date('Y-m-d') . '.pdf"');
            echo $pdf;
            exit;
        } catch (\Exception $e) {
            error_log("Error en exportarPDF: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }
    
    public function exportarExcel() {
        try {
            $this->validateMethod('POST');
            
            $input = $this->getValidatedInput(['tipoReporte', 'datos']);
            
            $excel = $this->reportesService->generarExcel(
                $input['tipoReporte'], 
                $input['datos']
            );
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="reporte_' . date('Y-m-d') . '.xlsx"');
            echo $excel;
            exit;
        } catch (\Exception $e) {
            error_log("Error en exportarExcel: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }
    
    public function exportarWord() {
        try {
            $this->validateMethod('POST');
            
            $input = $this->getValidatedInput(['tipoReporte', 'datos']);
            
            $word = $this->reportesService->generarWord(
                $input['tipoReporte'], 
                $input['datos']
            );
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment; filename="reporte_' . date('Y-m-d') . '.docx"');
            echo $word;
            exit;
        } catch (\Exception $e) {
            error_log("Error en exportarWord: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }
}