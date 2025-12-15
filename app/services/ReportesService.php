<?php

namespace App\Services;

use App\Repositories\ReportesRepository;
use DateTime;
use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class ReportesService {
    private $reportesRepository;
    
    public function __construct() {
        $this->reportesRepository = new ReportesRepository();
    }
    
    // ==================== PRACTICANTES ====================
    
    public function obtenerPracticantesActivos() {
        $practicantes = $this->reportesRepository->getPracticantesActivos();
        
        return [
            'titulo' => 'Practicantes Activos',
            'fecha' => date('Y-m-d H:i:s'),
            'total' => count($practicantes),
            'practicantes' => $practicantes
        ];
    }
    
    public function obtenerPracticantesCompletados() {
        $practicantes = $this->reportesRepository->getPracticantesCompletados();
        
        return [
            'titulo' => 'Prácticas Completadas',
            'fecha' => date('Y-m-d H:i:s'),
            'total' => count($practicantes),
            'practicantes' => $practicantes
        ];
    }
    
    public function obtenerPracticantesPorArea($areaID = null) {
        $datos = $this->reportesRepository->getPracticantesPorArea($areaID);
        
        return [
            'titulo' => 'Practicantes por Área',
            'fecha' => date('Y-m-d H:i:s'),
            'areas' => $datos
        ];
    }
    
    public function obtenerPracticantesPorUniversidad() {
        $datos = $this->reportesRepository->getPracticantesPorUniversidad();
        
        return [
            'titulo' => 'Practicantes por Universidad',
            'fecha' => date('Y-m-d H:i:s'),
            'universidades' => $datos
        ];
    }
    
    // ==================== ASISTENCIA ====================
    
    public function obtenerAsistenciaPorPracticante($practicanteID, $fechaInicio = null, $fechaFin = null) {
        $asistencias = $this->reportesRepository->getAsistenciasPorPracticante($practicanteID, $fechaInicio, $fechaFin);
        $practicante = $this->reportesRepository->getPracticanteInfo($practicanteID);
        
        $totalHoras = 0;
        foreach ($asistencias as $asistencia) {
            $totalHoras += $asistencia['HorasTrabajadas'] ?? 0;
        }
        
        return [
            'titulo' => 'Asistencias por Practicante',
            'fecha' => date('Y-m-d H:i:s'),
            'practicante' => $practicante,
            'totalAsistencias' => count($asistencias),
            'totalHoras' => round($totalHoras, 2),
            'asistencias' => $asistencias
        ];
    }
    
    public function obtenerAsistenciaDelDia($fecha) {
        $asistencias = $this->reportesRepository->getAsistenciasDelDia($fecha);
        
        return [
            'titulo' => 'Asistencias del Día',
            'fecha' => $fecha,
            'totalPracticantes' => count($asistencias),
            'asistencias' => $asistencias
        ];
    }
    
    public function obtenerAsistenciaMensual($mes, $anio) {
        $asistencias = $this->reportesRepository->getAsistenciasMensuales($mes, $anio);
        
        $resumen = [];
        foreach ($asistencias as $asistencia) {
            $practicanteID = $asistencia['PracticanteID'];
            if (!isset($resumen[$practicanteID])) {
                $resumen[$practicanteID] = [
                    'practicante' => $asistencia['NombreCompleto'],
                    'area' => $asistencia['AreaNombre'],
                    'diasAsistidos' => 0,
                    'totalHoras' => 0
                ];
            }
            $resumen[$practicanteID]['diasAsistidos']++;
            $resumen[$practicanteID]['totalHoras'] += $asistencia['HorasTrabajadas'] ?? 0;
        }
        
        return [
            'titulo' => 'Asistencias Mensuales',
            'mes' => $mes,
            'anio' => $anio,
            'fecha' => date('Y-m-d H:i:s'),
            'resumen' => array_values($resumen)
        ];
    }

    public function obtenerAsistenciaAnual($anio)
    {
        try {
            $asistencias = $this->reportesRepository->getAsistenciasAnuales($anio);

            $resumen = [];

            error_log('Error en asistencias: '. var_export($asistencias, true));

            foreach ($asistencias as $fila) {
                $practicanteID = $fila['PracticanteID'];

                $resumen[] = [
                    'practicante'    => $fila['NombreCompleto'],
                    'area'           => $fila['AreaNombre'] ?: 'N/A',
                    'diasAsistidos'  => (int) $fila['DiasAsistidos'],
                    'totalHoras'     => (float) $fila['TotalHoras'],
                    'mesesAsistidos' => $fila['MesesAsistidos'], // viene como string "Enero, Febrero..."
                ];
            }

            return [
                'titulo'  => 'Asistencias Anuales',
                'anio'    => $anio,
                'fecha'   => date('Y-m-d H:i:s'),
                'resumen' => $resumen
            ];
            
        } catch (\Exception $e) {
            error_log("Error en obtenerAsistenciaAnual: " . $e->getMessage());
            throw new \Exception("No se pudo generar el reporte de asistencia anual");
        }
    }

    
    public function obtenerHorasAcumuladas($practicanteID = null) {
        $datos = $this->reportesRepository->getHorasAcumuladas($practicanteID);
        
        return [
            'titulo' => 'Horas Acumuladas',
            'fecha' => date('Y-m-d H:i:s'),
            'practicantes' => $datos
        ];
    }
    
    // ==================== ESTADÍSTICAS ====================
    
    public function obtenerEstadisticasGenerales() {
        return [
            'titulo' => 'Estadísticas Generales',
            'fecha' => date('Y-m-d H:i:s'),
            'totalPracticantesActivos' => $this->reportesRepository->countPracticantesActivos(),
            'totalPracticantesCompletados' => $this->reportesRepository->countPracticantesCompletados(),
            'totalAreas' => $this->reportesRepository->countAreas(),
            'promedioHorasDiarias' => $this->reportesRepository->getPromedioHorasDiarias(),
            'distribucionPorArea' => $this->reportesRepository->getDistribucionPorArea(),
            'asistenciasMesActual' => $this->reportesRepository->getAsistenciasMesActual()
        ];
    }
    
    public function obtenerPromedioHoras() {
        $datos = $this->reportesRepository->getPromedioHorasPorPracticante();
        
        return [
            'titulo' => 'Promedio de Horas por Practicante',
            'fecha' => date('Y-m-d H:i:s'),
            'practicantes' => $datos
        ];
    }
    
    public function obtenerComparativoAreas() {
        $datos = $this->reportesRepository->getComparativoAreas();
        
        return [
            'titulo' => 'Comparativo por Áreas',
            'fecha' => date('Y-m-d H:i:s'),
            'areas' => $datos
        ];
    }
    
    public function obtenerReporteCompleto() {
        return [
            'titulo' => 'Reporte General Completo',
            'fecha' => date('Y-m-d H:i:s'),
            'practicantes' => $this->obtenerPracticantesActivos(),
            'asistencias' => $this->obtenerAsistenciaDelDia(date('Y-m-d')),
            'estadisticas' => $this->obtenerEstadisticasGenerales()
        ];
    }
    
    // ==================== EXPORTACIONES ====================
    
    public function generarPDF($tipoReporte, $datos) {
        $dompdf = new Dompdf();
        
        $html = $this->generarHTMLParaPDF($tipoReporte, $datos);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return $dompdf->output();
    }
    
    private function generarHTMLParaPDF($tipoReporte, $datos) {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; font-size: 10px; }
                h1 { color: #2c3e50; text-align: center; font-size: 18px; }
                h2 { color: #34495e; border-bottom: 2px solid #3498db; padding-bottom: 5px; font-size: 14px; margin-top: 20px; }
                table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                th { background-color: #3498db; color: white; padding: 8px; text-align: left; font-size: 9px; }
                td { padding: 6px; border-bottom: 1px solid #ddd; font-size: 9px; }
                tr:nth-child(even) { background-color: #f2f2f2; }
                .header { text-align: center; margin-bottom: 20px; }
                .fecha { color: #7f8c8d; font-size: 10px; }
                .stat-box { display: inline-block; margin: 5px 10px; padding: 10px; background: #ecf0f1; border-radius: 5px; }
                .area-section, .universidad-section { margin: 20px 0; }
                .badge { padding: 3px 8px; border-radius: 3px; font-size: 8px; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>' . htmlspecialchars($datos['titulo']) . '</h1>
                <p class="fecha">Generado el: ' . date('d/m/Y H:i:s') . '</p>
            </div>';
        
        // Generar contenido según el tipo de reporte
        switch($tipoReporte) {
            case 'practicantes_activos':
            case 'practicantes_completados':
                $html .= $this->generarTablaPracticantes($datos);
                break;
                
            case 'por_area':
                $html .= $this->generarTablaPorArea($datos);
                break;
                
            case 'por_universidad':
                $html .= $this->generarTablaPorUniversidad($datos);
                break;
                
            case 'asistencia_practicante':
                $html .= $this->generarTablaAsistenciaPracticante($datos);
                break;
                
            case 'asistencia_dia':
                $html .= $this->generarTablaAsistenciaDia($datos);
                break;
                
            case 'asistencia_mensual':
                $html .= $this->generarTablaAsistenciaMensual($datos);
                break;
                
            case 'asistencia_anual':
                $html .= $this->generarTablaAsistenciaAnual($datos);
                break;
                
            case 'horas_acumuladas':
                $html .= $this->generarTablaHorasAcumuladas($datos);
                break;
                
            case 'estadisticas_generales':
                $html .= $this->generarTablaEstadisticas($datos);
                break;
                
            case 'promedio_horas':
                $html .= $this->generarTablaPromedioHoras($datos);
                break;
                
            case 'comparativo_areas':
                $html .= $this->generarTablaComparativoAreas($datos);
                break;
                
            case 'completo':
                $html .= $this->generarTablaCompleto($datos);
                break;
        }
        
        $html .= '</body></html>';
        return $html;
    }

    // Métodos auxiliares para cada tipo de reporte
    private function generarTablaPracticantes($datos) {
        $html = '<table><tr>
            <th>Nombre Completo</th>
            <th>DNI</th>
            <th>Email</th>
            <th>Universidad</th>
            <th>Carrera</th>
            <th>Área</th>
            <th>Fecha Entrada</th>
            <th>Fecha Salida</th>
            <th>Estado</th>
        </tr>';
        
        if (isset($datos['practicantes'])) {
            foreach ($datos['practicantes'] as $p) {
                $html .= '<tr>
                    <td>' . htmlspecialchars($p['NombreCompleto'] ?? '') . '</td>
                    <td>' . htmlspecialchars($p['DNI'] ?? '') . '</td>
                    <td>' . htmlspecialchars($p['Email'] ?? '') . '</td>
                    <td>' . htmlspecialchars($p['Universidad'] ?? '') . '</td>
                    <td>' . htmlspecialchars($p['Carrera'] ?? '') . '</td>
                    <td>' . htmlspecialchars($p['AreaNombre'] ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($p['FechaEntrada'] ?? '') . '</td>
                    <td>' . htmlspecialchars($p['FechaSalida'] ?? '') . '</td>
                    <td>' . htmlspecialchars($p['Estado'] ?? '') . '</td>
                </tr>';
            }
        }
        $html .= '</table>';
        return $html;
    }

    private function generarTablaPorArea($datos) {
        $html = '';
        if (isset($datos['areas'])) {
            foreach ($datos['areas'] as $area) {
                $html .= '<h2>' . htmlspecialchars($area['AreaNombre']) . '</h2>';
                $html .= '<p>Total: ' . $area['TotalPracticantes'] . ' | Activos: ' . $area['Activos'] . ' | Completados: ' . $area['Completados'] . '</p>';
                $html .= '<table><tr>
                    <th>Nombre</th>
                    <th>DNI</th>
                    <th>Universidad</th>
                    <th>Estado</th>
                    <th>Fecha Entrada</th>
                    <th>Fecha Salida</th>
                </tr>';
                
                if (isset($area['practicantes'])) {
                    foreach ($area['practicantes'] as $p) {
                        $html .= '<tr>
                            <td>' . htmlspecialchars($p['NombreCompleto']) . '</td>
                            <td>' . htmlspecialchars($p['DNI']) . '</td>
                            <td>' . htmlspecialchars($p['Universidad']) . '</td>
                            <td>' . htmlspecialchars($p['Estado']) . '</td>
                            <td>' . htmlspecialchars($p['FechaEntrada']) . '</td>
                            <td>' . htmlspecialchars($p['FechaSalida'] ?? '') . '</td>
                        </tr>';
                    }
                }
                $html .= '</table>';
            }
        }
        return $html;
    }

    private function generarTablaPorUniversidad($datos) {
        $html = '';
        if (isset($datos['universidades'])) {
            foreach ($datos['universidades'] as $uni) {
                $html .= '<h2>' . htmlspecialchars($uni['Universidad']) . '</h2>';
                $html .= '<p>Total: ' . $uni['TotalPracticantes'] . ' | Vigentes: ' . $uni['Activos'] . ' | Finalizados: ' . $uni['Completados'] . '</p>';
                $html .= '<table><tr>
                    <th>Nombre</th>
                    <th>DNI</th>
                    <th>Carrera</th>
                    <th>Área</th>
                    <th>Estado</th>
                </tr>';
                
                if (isset($uni['practicantes'])) {
                    foreach ($uni['practicantes'] as $p) {
                        $html .= '<tr>
                            <td>' . htmlspecialchars($p['NombreCompleto']) . '</td>
                            <td>' . htmlspecialchars($p['DNI']) . '</td>
                            <td>' . htmlspecialchars($p['Carrera']) . '</td>
                            <td>' . htmlspecialchars($p['AreaNombre'] ?? 'N/A') . '</td>
                            <td>' . htmlspecialchars($p['Estado'] ?? '') . '</td>
                        </tr>';
                    }
                }
                $html .= '</table>';
            }
        }
        return $html;
    }

    private function generarTablaAsistenciaPracticante($datos) {
        $html = '<div style="margin-bottom: 20px;">';
        if (isset($datos['practicante'])) {
            $p = $datos['practicante'];
            $html .= '<p><strong>Practicante:</strong> ' . htmlspecialchars($p['NombreCompleto']) . '</p>';
            $html .= '<p><strong>DNI:</strong> ' . htmlspecialchars($p['DNI']) . '</p>';
            $html .= '<p><strong>Universidad:</strong> ' . htmlspecialchars($p['Universidad']) . '</p>';
            $html .= '<p><strong>Total Asistencias:</strong> ' . ($datos['totalAsistencias'] ?? 0) . ' | <strong>Total Horas:</strong> ' . ($datos['totalHoras'] ?? 0) . '</p>';
        }
        $html .= '</div>';
        
        $html .= '<table><tr>
            <th>Fecha</th>
            <th>Turno</th>
            <th>Hora Entrada</th>
            <th>Hora Salida</th>
            <th>Horas Trabajadas</th>
        </tr>';
        
        if (isset($datos['asistencias'])) {
            foreach ($datos['asistencias'] as $a) {
                $html .= '<tr>
                    <td>' . htmlspecialchars($a['Fecha']) . '</td>
                    <td>' . htmlspecialchars($a['TurnoNombre'] ?? '') . '</td>
                    <td>' . htmlspecialchars($a['HoraEntrada'] ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($a['HoraSalida'] ?? 'En proceso') . '</td>
                    <td>' . htmlspecialchars($a['HorasTrabajadas'] ?? '-') . '</td>
                </tr>';
            }
        }
        $html .= '</table>';
        return $html;
    }

    private function generarTablaAsistenciaDia($datos) {
        $html = '<table><tr>
            <th>Practicante</th>
            <th>DNI</th>
            <th>Área</th>
            <th>Turno</th>
            <th>Hora Entrada</th>
            <th>Hora Salida</th>
            <th>Horas Trabajadas</th>
        </tr>';
        
        if (isset($datos['asistencias'])) {
            foreach ($datos['asistencias'] as $a) {
                $html .= '<tr>
                    <td>' . htmlspecialchars($a['NombreCompleto']) . '</td>
                    <td>' . htmlspecialchars($a['DNI']) . '</td>
                    <td>' . htmlspecialchars($a['AreaNombre'] ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($a['TurnoNombre']) . '</td>
                    <td>' . htmlspecialchars($a['HoraEntrada']) . '</td>
                    <td>' . htmlspecialchars($a['HoraSalida'] ?? 'En proceso') . '</td>
                    <td>' . htmlspecialchars($a['HorasTrabajadas'] ?? '-') . '</td>
                </tr>';
            }
        }
        $html .= '</table>';
        return $html;
    }

    private function generarTablaAsistenciaMensual($datos) {
        $html = '<table><tr>
            <th>Practicante</th>
            <th>Área</th>
            <th>Días Asistidos</th>
            <th>Total Horas</th>
            <th>Promedio Diario</th>
        </tr>';
        
        if (isset($datos['resumen'])) {
            foreach ($datos['resumen'] as $r) {
                $promedio = $r['diasAsistidos'] > 0 ? number_format($r['totalHoras'] / $r['diasAsistidos'], 2) : 0;
                $html .= '<tr>
                    <td>' . htmlspecialchars($r['practicante']) . '</td>
                    <td>' . htmlspecialchars($r['area'] ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($r['diasAsistidos']) . '</td>
                    <td>' . number_format($r['totalHoras'], 2) . ' hrs</td>
                    <td>' . $promedio . ' hrs</td>
                </tr>';
            }
        }
        $html .= '</table>';
        return $html;
    }

    private function generarTablaAsistenciaAnual($datos) {
        $html = '<table><tr>
            <th>Practicante</th>
            <th>Área</th>
            <th>Días Asistidos</th>
            <th>Total Horas</th>
            <th>Promedio Horas/Día</th>
            <th>Meses Asistidos</th>
        </tr>';
        
        if (isset($datos['resumen'])) {
            foreach ($datos['resumen'] as $r) {
                $promedio = $r['diasAsistidos'] > 0 ? number_format($r['totalHoras'] / $r['diasAsistidos'], 2) : 0;
                $html .= '<tr>
                    <td>' . htmlspecialchars($r['practicante']) . '</td>
                    <td>' . htmlspecialchars($r['area'] ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($r['diasAsistidos']) . '</td>
                    <td>' . number_format($r['totalHoras'], 2) . ' hrs</td>
                    <td>' . $promedio . ' hrs</td>
                    <td>' . htmlspecialchars($r['mesesAsistidos'] ?? '—') . '</td>
                </tr>';
            }
        }
        $html .= '</table>';
        return $html;
    }

    private function generarTablaHorasAcumuladas($datos) {
        $html = '<table><tr>
            <th>Practicante</th>
            <th>DNI</th>
            <th>Área</th>
            <th>Total Asistencias</th>
            <th>Total Horas</th>
            <th>Promedio Horas/Día</th>
        </tr>';
        
        if (isset($datos['practicantes'])) {
            foreach ($datos['practicantes'] as $p) {
                $html .= '<tr>
                    <td>' . htmlspecialchars($p['NombreCompleto']) . '</td>
                    <td>' . htmlspecialchars($p['DNI']) . '</td>
                    <td>' . htmlspecialchars($p['AreaNombre'] ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($p['TotalAsistencias']) . '</td>
                    <td>' . number_format($p['TotalHoras'], 2) . ' hrs</td>
                    <td>' . number_format($p['PromedioHoras'], 2) . ' hrs</td>
                </tr>';
            }
        }
        $html .= '</table>';
        return $html;
    }

    private function generarTablaEstadisticas($datos) {
        $html = '<div style="margin-bottom: 20px;">';
        $html .= '<div class="stat-box">Practicantes Vigentes: ' . ($datos['totalPracticantesActivos'] ?? 0) . '</div>';
        $html .= '<div class="stat-box">Prácticas Completadas: ' . ($datos['totalPracticantesCompletados'] ?? 0) . '</div>';
        $html .= '<div class="stat-box">Total Áreas: ' . ($datos['totalAreas'] ?? 0) . '</div>';
        $html .= '<div class="stat-box">Promedio Horas/Día: ' . ($datos['promedioHorasDiarias'] ?? 0) . '</div>';
        $html .= '</div>';
        
        $html .= '<h2>Distribución por Área</h2>';
        $html .= '<table><tr><th>Área</th><th>Cantidad de Practicantes</th></tr>';
        
        if (isset($datos['distribucionPorArea'])) {
            foreach ($datos['distribucionPorArea'] as $area) {
                $html .= '<tr>
                    <td>' . htmlspecialchars($area['area']) . '</td>
                    <td>' . htmlspecialchars($area['cantidad']) . '</td>
                </tr>';
            }
        }
        $html .= '</table>';
        return $html;
    }

    private function generarTablaPromedioHoras($datos) {
        $html = '<table><tr>
            <th>#</th>
            <th>Practicante</th>
            <th>Área</th>
            <th>Total Asistencias</th>
            <th>Total Horas</th>
            <th>Promedio Horas/Día</th>
        </tr>';
        
        if (isset($datos['practicantes'])) {
            $pos = 1;
            foreach ($datos['practicantes'] as $p) {
                $html .= '<tr>
                    <td>' . $pos++ . '</td>
                    <td>' . htmlspecialchars($p['NombreCompleto']) . '</td>
                    <td>' . htmlspecialchars($p['AreaNombre'] ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($p['TotalAsistencias']) . '</td>
                    <td>' . number_format($p['TotalHoras'], 2) . ' hrs</td>
                    <td>' . number_format($p['PromedioHoras'], 2) . ' hrs</td>
                </tr>';
            }
        }
        $html .= '</table>';
        return $html;
    }

    private function generarTablaComparativoAreas($datos) {
        $html = '<table><tr>
            <th>Área</th>
            <th>Total Practicantes</th>
            <th>Vigentes</th>
            <th>Total Asistencias</th>
            <th>Total Horas</th>
            <th>Promedio Horas</th>
        </tr>';
        
        if (isset($datos['areas'])) {
            foreach ($datos['areas'] as $area) {
                $html .= '<tr>
                    <td>' . htmlspecialchars($area['AreaNombre']) . '</td>
                    <td>' . htmlspecialchars($area['TotalPracticantes']) . '</td>
                    <td>' . htmlspecialchars($area['Activos']) . '</td>
                    <td>' . htmlspecialchars($area['TotalAsistencias']) . '</td>
                    <td>' . number_format($area['TotalHoras'], 2) . ' hrs</td>
                    <td>' . number_format($area['PromedioHoras'], 2) . ' hrs</td>
                </tr>';
            }
        }
        $html .= '</table>';
        return $html;
    }

    private function generarTablaCompleto($datos) {
        $html = '<h2>Estadísticas Generales</h2>';
        if (isset($datos['estadisticas'])) {
            $e = $datos['estadisticas'];
            $html .= '<p>Practicantes Vigentes: ' . ($e['totalPracticantesActivos'] ?? 0) . '</p>';
            $html .= '<p>Prácticas Completadas: ' . ($e['totalPracticantesCompletados'] ?? 0) . '</p>';
            $html .= '<p>Promedio Horas/Día: ' . ($e['promedioHorasDiarias'] ?? 0) . '</p>';
        }
        
        if (isset($datos['practicantes'])) {
            $html .= '<h2>Practicantes Vigentes (Total: ' . ($datos['practicantes']['total'] ?? 0) . ')</h2>';
        }
        
        if (isset($datos['asistencias'])) {
            $html .= '<h2>Asistencias de Hoy (Total: ' . ($datos['asistencias']['totalPracticantes'] ?? 0) . ')</h2>';
        }
        
        return $html;
    }
    
    public function generarExcel($tipoReporte, $datos) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Título
        $sheet->setCellValue('A1', $datos['titulo']);
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        $sheet->setCellValue('A2', 'Fecha: ' . date('d/m/Y H:i:s'));
        $sheet->mergeCells('A2:F2');
        
        $row = 4;
        
        // Generar contenido según el tipo de reporte
        switch($tipoReporte) {
            case 'practicantes_activos':
            case 'practicantes_completados':
                $row = $this->generarExcelPracticantes($sheet, $datos, $row);
                break;
                
            case 'por_area':
                $row = $this->generarExcelPorArea($sheet, $datos, $row);
                break;
                
            case 'por_universidad':
                $row = $this->generarExcelPorUniversidad($sheet, $datos, $row);
                break;
                
            case 'asistencia_practicante':
                $row = $this->generarExcelAsistenciaPracticante($sheet, $datos, $row);
                break;
                
            case 'asistencia_dia':
                $row = $this->generarExcelAsistenciaDia($sheet, $datos, $row);
                break;
                
            case 'asistencia_mensual':
                $row = $this->generarExcelAsistenciaMensual($sheet, $datos, $row);
                break;
                
            case 'asistencia_anual':
                $row = $this->generarExcelAsistenciaAnual($sheet, $datos, $row);
                break;
                
            case 'horas_acumuladas':
                $row = $this->generarExcelHorasAcumuladas($sheet, $datos, $row);
                break;
                
            case 'estadisticas_generales':
                $row = $this->generarExcelEstadisticas($sheet, $datos, $row);
                break;
                
            case 'promedio_horas':
                $row = $this->generarExcelPromedioHoras($sheet, $datos, $row);
                break;
                
            case 'comparativo_areas':
                $row = $this->generarExcelComparativoAreas($sheet, $datos, $row);
                break;
                
            case 'completo':
                $row = $this->generarExcelCompleto($sheet, $datos, $row);
                break;
        }
        
        // Ajustar todas las columnas
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        $writer = new Xlsx($spreadsheet);
        
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }

    // Métodos auxiliares para Excel
    private function generarExcelPracticantes($sheet, $datos, $row) {
        $headers = ['Nombre Completo', 'DNI', 'Email', 'Universidad', 'Carrera', 'Área', 'Fecha Entrada', 'Fecha Salida', 'Estado'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $col++;
        }
        $row++;
        
        if (isset($datos['practicantes'])) {
            foreach ($datos['practicantes'] as $p) {
                $sheet->setCellValue('A' . $row, $p['NombreCompleto'] ?? '');
                $sheet->setCellValue('B' . $row, $p['DNI'] ?? '');
                $sheet->setCellValue('C' . $row, $p['Email'] ?? '');
                $sheet->setCellValue('D' . $row, $p['Universidad'] ?? '');
                $sheet->setCellValue('E' . $row, $p['Carrera'] ?? '');
                $sheet->setCellValue('F' . $row, $p['AreaNombre'] ?? 'N/A');
                $sheet->setCellValue('G' . $row, $p['FechaEntrada'] ?? '');
                $sheet->setCellValue('H' . $row, $p['FechaSalida'] ?? '');
                $sheet->setCellValue('I' . $row, $p['Estado'] ?? '');
                $row++;
            }
        }
        return $row;
    }

    private function generarExcelPorArea($sheet, $datos, $row) {
        if (isset($datos['areas'])) {
            foreach ($datos['areas'] as $area) {
                $sheet->setCellValue('A' . $row, $area['AreaNombre']);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
                $row++;
                
                $sheet->setCellValue('A' . $row, 'Total: ' . $area['TotalPracticantes'] . ' | Activos: ' . $area['Activos'] . ' | Completados: ' . $area['Completados']);
                $row++;
                
                $headers = ['Nombre', 'DNI', 'Universidad', 'Estado', 'Fecha Entrada', 'Fecha Salida'];
                $col = 'A';
                foreach ($headers as $header) {
                    $sheet->setCellValue($col . $row, $header);
                    $sheet->getStyle($col . $row)->getFont()->setBold(true);
                    $col++;
                }
                $row++;
                
                if (isset($area['practicantes'])) {
                    foreach ($area['practicantes'] as $p) {
                        $sheet->setCellValue('A' . $row, $p['NombreCompleto']);
                        $sheet->setCellValue('B' . $row, $p['DNI']);
                        $sheet->setCellValue('C' . $row, $p['Universidad']);
                        $sheet->setCellValue('D' . $row, $p['Estado']);
                        $sheet->setCellValue('E' . $row, $p['FechaEntrada']);
                        $sheet->setCellValue('F' . $row, $p['FechaSalida'] ?? '');
                        $row++;
                    }
                }
                $row += 2;
            }
        }
        return $row;
    }
    
    public function generarWord($tipoReporte, $datos) {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        
        // Título
        $section->addText(
            $datos['titulo'],
            ['bold' => true, 'size' => 16],
            ['alignment' => 'center']
        );
        
        $section->addText(
            'Fecha: ' . date('d/m/Y H:i:s'),
            ['size' => 10],
            ['alignment' => 'center']
        );
        
        $section->addTextBreak(2);
        
        // Generar tabla si hay practicantes
        if (isset($datos['practicantes']) && is_array($datos['practicantes'])) {
            $table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999']);
            
            $table->addRow();
            $table->addCell(2000)->addText('Nombre', ['bold' => true]);
            $table->addCell(2000)->addText('DNI', ['bold' => true]);
            $table->addCell(3000)->addText('Universidad', ['bold' => true]);
            $table->addCell(2000)->addText('Área', ['bold' => true]);
            $table->addCell(1500)->addText('Estado', ['bold' => true]);
            
            foreach ($datos['practicantes'] as $p) {
                $table->addRow();
                $table->addCell(2000)->addText($p['NombreCompleto'] ?? '');
                $table->addCell(2000)->addText($p['DNI'] ?? '');
                $table->addCell(3000)->addText($p['Universidad'] ?? '');
                $table->addCell(2000)->addText($p['AreaNombre'] ?? '');
                $table->addCell(1500)->addText($p['Estado'] ?? '');
            }
        }
        
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        
        ob_start();
        $objWriter->save('php://output');
        return ob_get_clean();
    }

    private function generarExcelPorUniversidad($sheet, $datos, $row) {
        if (isset($datos['universidades'])) {
            foreach ($datos['universidades'] as $uni) {
                $sheet->setCellValue('A' . $row, $uni['Universidad']);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
                $row++;
                
                $sheet->setCellValue('A' . $row, 'Total: ' . $uni['TotalPracticantes'] . ' | Vigentes: ' . $uni['Activos'] . ' | Finalizados: ' . $uni['Completados']);
                $row++;
                
                $headers = ['Nombre', 'DNI', 'Carrera', 'Área', 'Estado'];
                $col = 'A';
                foreach ($headers as $header) {
                    $sheet->setCellValue($col . $row, $header);
                    $sheet->getStyle($col . $row)->getFont()->setBold(true);
                    $col++;
                }
                $row++;
                
                if (isset($uni['practicantes'])) {
                    foreach ($uni['practicantes'] as $p) {
                        $sheet->setCellValue('A' . $row, $p['NombreCompleto']);
                        $sheet->setCellValue('B' . $row, $p['DNI']);
                        $sheet->setCellValue('C' . $row, $p['Carrera']);
                        $sheet->setCellValue('D' . $row, $p['AreaNombre'] ?? 'N/A');
                        $sheet->setCellValue('E' . $row, $p['Estado'] ?? '');
                        $row++;
                    }
                }
                $row += 2;
            }
        }
        return $row;
    }

    private function generarExcelAsistenciaPracticante($sheet, $datos, $row) {
        if (isset($datos['practicante'])) {
            $p = $datos['practicante'];
            $sheet->setCellValue('A' . $row, 'Practicante: ' . $p['NombreCompleto']);
            $row++;
            $sheet->setCellValue('A' . $row, 'DNI: ' . $p['DNI']);
            $row++;
            $sheet->setCellValue('A' . $row, 'Universidad: ' . $p['Universidad']);
            $row++;
            $sheet->setCellValue('A' . $row, 'Total Asistencias: ' . ($datos['totalAsistencias'] ?? 0) . ' | Total Horas: ' . ($datos['totalHoras'] ?? 0));
            $row += 2;
        }
        
        $headers = ['Fecha', 'Turno', 'Hora Entrada', 'Hora Salida', 'Horas Trabajadas'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $col++;
        }
        $row++;
        
        if (isset($datos['asistencias'])) {
            foreach ($datos['asistencias'] as $a) {
                $sheet->setCellValue('A' . $row, $a['Fecha']);
                $sheet->setCellValue('B' . $row, $a['TurnoNombre'] ?? '');
                $sheet->setCellValue('C' . $row, $a['HoraEntrada'] ?? 'N/A');
                $sheet->setCellValue('D' . $row, $a['HoraSalida'] ?? 'En proceso');
                $sheet->setCellValue('E' . $row, $a['HorasTrabajadas'] ?? '-');
                $row++;
            }
        }
        return $row;
    }

    private function generarExcelAsistenciaDia($sheet, $datos, $row) {
        $headers = ['Practicante', 'DNI', 'Área', 'Turno', 'Hora Entrada', 'Hora Salida', 'Horas Trabajadas'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $col++;
        }
        $row++;
        
        if (isset($datos['asistencias'])) {
            foreach ($datos['asistencias'] as $a) {
                $sheet->setCellValue('A' . $row, $a['NombreCompleto']);
                $sheet->setCellValue('B' . $row, $a['DNI']);
                $sheet->setCellValue('C' . $row, $a['AreaNombre'] ?? 'N/A');
                $sheet->setCellValue('D' . $row, $a['TurnoNombre']);
                $sheet->setCellValue('E' . $row, $a['HoraEntrada']);
                $sheet->setCellValue('F' . $row, $a['HoraSalida'] ?? 'En proceso');
                $sheet->setCellValue('G' . $row, $a['HorasTrabajadas'] ?? '-');
                $row++;
            }
        }
        return $row;
    }

    private function generarExcelAsistenciaMensual($sheet, $datos, $row) {
        $headers = ['Practicante', 'Área', 'Días Asistidos', 'Total Horas', 'Promedio Diario'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $col++;
        }
        $row++;
        
        if (isset($datos['resumen'])) {
            foreach ($datos['resumen'] as $r) {
                $promedio = $r['diasAsistidos'] > 0 ? round($r['totalHoras'] / $r['diasAsistidos'], 2) : 0;
                $sheet->setCellValue('A' . $row, $r['practicante']);
                $sheet->setCellValue('B' . $row, $r['area'] ?? 'N/A');
                $sheet->setCellValue('C' . $row, $r['diasAsistidos']);
                $sheet->setCellValue('D' . $row, round($r['totalHoras'], 2));
                $sheet->setCellValue('E' . $row, $promedio);
                $row++;
            }
        }
        return $row;
    }

    private function generarExcelAsistenciaAnual($sheet, $datos, $row) {
        $headers = ['Practicante', 'Área', 'Días Asistidos', 'Total Horas', 'Promedio Horas/Día', 'Meses Asistidos'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $col++;
        }
        $row++;
        
        if (isset($datos['resumen'])) {
            foreach ($datos['resumen'] as $r) {
                $promedio = $r['diasAsistidos'] > 0 ? round($r['totalHoras'] / $r['diasAsistidos'], 2) : 0;
                $sheet->setCellValue('A' . $row, $r['practicante']);
                $sheet->setCellValue('B' . $row, $r['area'] ?? 'N/A');
                $sheet->setCellValue('C' . $row, $r['diasAsistidos']);
                $sheet->setCellValue('D' . $row, round($r['totalHoras'], 2));
                $sheet->setCellValue('E' . $row, $promedio);
                $sheet->setCellValue('F' . $row, $r['mesesAsistidos'] ?? '—');
                $row++;
            }
        }
        return $row;
    }

    private function generarExcelHorasAcumuladas($sheet, $datos, $row) {
        $headers = ['Practicante', 'DNI', 'Área', 'Total Asistencias', 'Total Horas', 'Promedio Horas/Día'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $col++;
        }
        $row++;
        
        if (isset($datos['practicantes'])) {
            foreach ($datos['practicantes'] as $p) {
                $sheet->setCellValue('A' . $row, $p['NombreCompleto']);
                $sheet->setCellValue('B' . $row, $p['DNI']);
                $sheet->setCellValue('C' . $row, $p['AreaNombre'] ?? 'N/A');
                $sheet->setCellValue('D' . $row, $p['TotalAsistencias']);
                $sheet->setCellValue('E' . $row, round($p['TotalHoras'], 2));
                $sheet->setCellValue('F' . $row, round($p['PromedioHoras'], 2));
                $row++;
            }
        }
        return $row;
    }

    private function generarExcelEstadisticas($sheet, $datos, $row) {
        $sheet->setCellValue('A' . $row, 'Estadísticas Generales');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $row += 2;
        
        $sheet->setCellValue('A' . $row, 'Practicantes Vigentes:');
        $sheet->setCellValue('B' . $row, $datos['totalPracticantesActivos'] ?? 0);
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Prácticas Completadas:');
        $sheet->setCellValue('B' . $row, $datos['totalPracticantesCompletados'] ?? 0);
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Total Áreas:');
        $sheet->setCellValue('B' . $row, $datos['totalAreas'] ?? 0);
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Promedio Horas/Día:');
        $sheet->setCellValue('B' . $row, $datos['promedioHorasDiarias'] ?? 0);
        $row += 2;
        
        $sheet->setCellValue('A' . $row, 'Distribución por Área');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Área');
        $sheet->setCellValue('B' . $row, 'Cantidad');
        $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
        $row++;
        
        if (isset($datos['distribucionPorArea'])) {
            foreach ($datos['distribucionPorArea'] as $area) {
                $sheet->setCellValue('A' . $row, $area['area']);
                $sheet->setCellValue('B' . $row, $area['cantidad']);
                $row++;
            }
        }
        
        return $row;
    }

    private function generarExcelPromedioHoras($sheet, $datos, $row) {
        $headers = ['#', 'Practicante', 'Área', 'Total Asistencias', 'Total Horas', 'Promedio Horas/Día'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $col++;
        }
        $row++;
        
        if (isset($datos['practicantes'])) {
            $pos = 1;
            foreach ($datos['practicantes'] as $p) {
                $sheet->setCellValue('A' . $row, $pos++);
                $sheet->setCellValue('B' . $row, $p['NombreCompleto']);
                $sheet->setCellValue('C' . $row, $p['AreaNombre'] ?? 'N/A');
                $sheet->setCellValue('D' . $row, $p['TotalAsistencias']);
                $sheet->setCellValue('E' . $row, round($p['TotalHoras'], 2));
                $sheet->setCellValue('F' . $row, round($p['PromedioHoras'], 2));
                $row++;
            }
        }
        return $row;
    }

    private function generarExcelComparativoAreas($sheet, $datos, $row) {
        $headers = ['Área', 'Total Practicantes', 'Vigentes', 'Total Asistencias', 'Total Horas', 'Promedio Horas'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $col++;
        }
        $row++;
        
        if (isset($datos['areas'])) {
            foreach ($datos['areas'] as $area) {
                $sheet->setCellValue('A' . $row, $area['AreaNombre']);
                $sheet->setCellValue('B' . $row, $area['TotalPracticantes']);
                $sheet->setCellValue('C' . $row, $area['Activos']);
                $sheet->setCellValue('D' . $row, $area['TotalAsistencias']);
                $sheet->setCellValue('E' . $row, round($area['TotalHoras'], 2));
                $sheet->setCellValue('F' . $row, round($area['PromedioHoras'], 2));
                $row++;
            }
        }
        return $row;
    }

    private function generarExcelCompleto($sheet, $datos, $row) {
        $sheet->setCellValue('A' . $row, 'Estadísticas Generales');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $row += 2;
        
        if (isset($datos['estadisticas'])) {
            $e = $datos['estadisticas'];
            $sheet->setCellValue('A' . $row, 'Practicantes Vigentes:');
            $sheet->setCellValue('B' . $row, $e['totalPracticantesActivos'] ?? 0);
            $row++;
            
            $sheet->setCellValue('A' . $row, 'Prácticas Completadas:');
            $sheet->setCellValue('B' . $row, $e['totalPracticantesCompletados'] ?? 0);
            $row++;
            
            $sheet->setCellValue('A' . $row, 'Promedio Horas/Día:');
            $sheet->setCellValue('B' . $row, $e['promedioHorasDiarias'] ?? 0);
            $row += 2;
        }
        
        if (isset($datos['practicantes'])) {
            $sheet->setCellValue('A' . $row, 'Practicantes Vigentes');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
            $sheet->setCellValue('A' . $row, 'Total: ' . ($datos['practicantes']['total'] ?? 0));
            $row += 2;
        }
        
        if (isset($datos['asistencias'])) {
            $sheet->setCellValue('A' . $row, 'Asistencias de Hoy');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
            $sheet->setCellValue('A' . $row, 'Total: ' . ($datos['asistencias']['totalPracticantes'] ?? 0));
            $row++;
        }
        
        return $row;
    }
}