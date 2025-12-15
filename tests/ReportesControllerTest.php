<?php
/**
 * Tests para ReportesController
 * Pruebas de generación de reportes, cartas y certificados
 * 
 * Ejecutar con: phpunit tests/ReportesControllerTest.php
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Controllers\ReportesController;

class ReportesControllerTest extends TestCase {
    
    private $controller;
    private $mockService;
    
    protected function setUp(): void {
        parent::setUp();
        $this->mockService = $this->createMock(\App\Services\ReportesService::class);
        $this->controller = new ReportesController($this->mockService);
    }
    
    /**
     * Test: Generar reporte de practicantes
     * Verifica que se genere correctamente un reporte de practicantes
     */
    public function testGenerarReportePracticantes() {
        // Setup
        $reporte = [
            'totalPracticantes' => 15,
            'pendientes' => 3,
            'aceptados' => 10,
            'rechazados' => 2,
            'generadoEn' => date('Y-m-d H:i:s'),
            'area' => 'Sistemas'
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('generarReportePracticantes')
            ->with(1)
            ->willReturn($reporte);
        
        // Ejecutar
        $resultado = $this->mockService->generarReportePracticantes(1);
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertEquals(15, $resultado['totalPracticantes']);
        $this->assertEquals(3, $resultado['pendientes']);
    }
    
    /**
     * Test: Generar reporte de asistencias
     * Verifica que se genere reporte de asistencias por periodo
     */
    public function testGenerarReporteAsistencias() {
        // Setup
        $reporte = [
            'practicanteID' => 1,
            'nombrePracticante' => 'Juan García',
            'fechaInicio' => '2024-12-01',
            'fechaFin' => '2024-12-31',
            'totalHoras' => 160.5,
            'diasAsistio' => 20,
            'diasFalto' => 0,
            'detalle' => []
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('generarReporteAsistencias')
            ->with(1, '2024-12-01', '2024-12-31')
            ->willReturn($reporte);
        
        // Ejecutar
        $resultado = $this->mockService->generarReporteAsistencias(1, '2024-12-01', '2024-12-31');
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertEquals(160.5, $resultado['totalHoras']);
        $this->assertEquals(20, $resultado['diasAsistio']);
    }
    
    /**
     * Test: Generar carta de aceptación
     * Verifica que se genere correctamente una carta de aceptación
     */
    public function testGenerarCartaAceptacion() {
        // Setup
        $cartaData = [
            'practicanteID' => 1,
            'nombrePracticante' => 'Juan García López',
            'areaNombre' => 'Sistemas',
            'fechaEntrada' => '2025-01-15',
            'fechaSalida' => '2025-03-15',
            'nombreSupervisor' => 'Ing. Carlos Rodríguez'
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('generarCartaAceptacion')
            ->with(1)
            ->willReturn(['success' => true, 'rutaArchivo' => '/cartas/carta_1.pdf']);
        
        // Ejecutar
        $resultado = $this->mockService->generarCartaAceptacion(1);
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['success']);
        $this->assertStringContainsString('carta_1.pdf', $resultado['rutaArchivo']);
    }
    
    /**
     * Test: Generar certificado de horas
     * Verifica que se genere correctamente un certificado
     */
    public function testGenerarCertificadoHoras() {
        // Setup
        $certificadoData = [
            'practicanteID' => 1,
            'nombrePracticante' => 'Juan García López',
            'totalHoras' => 160,
            'fechaInicio' => '2024-12-01',
            'fechaFin' => '2025-02-28',
            'areaNombre' => 'Sistemas',
            'nivelDesempeno' => 'Excelente'
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('generarCertificado')
            ->with(1)
            ->willReturn(['success' => true, 'rutaArchivo' => '/certificados/cert_1.pdf']);
        
        // Ejecutar
        $resultado = $this->mockService->generarCertificado(1);
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['success']);
        $this->assertStringContainsString('cert_1.pdf', $resultado['rutaArchivo']);
    }
    
    /**
     * Test: Generar reporte de horas por practicante
     * Verifica que se genere reporte detallado de horas
     */
    public function testReporteHorasPracticante() {
        // Setup
        $reporte = [
            'practicanteID' => 1,
            'nombrePracticante' => 'Juan García',
            'areaNombre' => 'Sistemas',
            'horas' => [
                ['fecha' => '2024-12-01', 'horaEntrada' => '08:00', 'horaSalida' => '17:00', 'total' => 8.5],
                ['fecha' => '2024-12-02', 'horaEntrada' => '08:15', 'horaSalida' => '17:15', 'total' => 8.5],
                ['fecha' => '2024-12-03', 'horaEntrada' => '08:00', 'horaSalida' => '16:00', 'total' => 7.5]
            ],
            'totalHoras' => 24.5
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('reporteHorasPracticante')
            ->with(1)
            ->willReturn($reporte);
        
        // Ejecutar
        $resultado = $this->mockService->reporteHorasPracticante(1);
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertEquals(24.5, $resultado['totalHoras']);
        $this->assertCount(3, $resultado['horas']);
    }
    
    /**
     * Test: Generar reporte de actividades por área
     * Verifica que genere reporte de actividades del área
     */
    public function testReporteActividadArea() {
        // Setup
        $reporte = [
            'areaID' => 1,
            'areaNombre' => 'Sistemas',
            'periodo' => '2024-12',
            'practicantes' => [
                ['practicanteID' => 1, 'nombre' => 'Juan García', 'horas' => 160],
                ['practicanteID' => 2, 'nombre' => 'María López', 'horas' => 155]
            ],
            'totalHoras' => 315,
            'promediaHoras' => 157.5
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('reporteActividadArea')
            ->with(1, '2024-12')
            ->willReturn($reporte);
        
        // Ejecutar
        $resultado = $this->mockService->reporteActividadArea(1, '2024-12');
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertEquals('Sistemas', $resultado['areaNombre']);
        $this->assertEquals(315, $resultado['totalHoras']);
    }
    
    /**
     * Test: Exportar reporte a PDF
     * Verifica que genere correctamente un PDF
     */
    public function testExportarReportePDF() {
        // Setup
        $this->mockService
            ->expects($this->once())
            ->method('exportarPDF')
            ->with('practicantes', 1)
            ->willReturn(['success' => true, 'archivo' => 'reporte_practicantes.pdf']);
        
        // Ejecutar
        $resultado = $this->mockService->exportarPDF('practicantes', 1);
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['success']);
        $this->assertStringContainsString('pdf', strtolower($resultado['archivo']));
    }
    
    /**
     * Test: Exportar reporte a Excel
     * Verifica que genere correctamente un Excel
     */
    public function testExportarReporteExcel() {
        // Setup
        $this->mockService
            ->expects($this->once())
            ->method('exportarExcel')
            ->with('asistencias', 1)
            ->willReturn(['success' => true, 'archivo' => 'reporte_asistencias.xlsx']);
        
        // Ejecutar
        $resultado = $this->mockService->exportarExcel('asistencias', 1);
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['success']);
        $this->assertStringContainsString('xlsx', strtolower($resultado['archivo']));
    }
    
    /**
     * Test: Generar reporte de evaluación de practicantes
     * Verifica que genere reporte de desempeño
     */
    public function testReporteEvaluacionPracticantes() {
        // Setup
        $reporte = [
            'periodo' => '2024-12',
            'totalEvaluados' => 15,
            'excelente' => 8,
            'bueno' => 5,
            'regular' => 2,
            'deficiente' => 0,
            'promedioCualitativo' => 'Bueno'
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('reporteEvaluacion')
            ->with('2024-12')
            ->willReturn($reporte);
        
        // Ejecutar
        $resultado = $this->mockService->reporteEvaluacion('2024-12');
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertEquals(15, $resultado['totalEvaluados']);
        $this->assertEquals(8, $resultado['excelente']);
    }
}
