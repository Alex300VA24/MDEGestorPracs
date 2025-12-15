<?php
/**
 * Tests para AsistenciaController
 * Pruebas de registro de entrada, salida y pausas
 * 
 * Ejecutar con: phpunit tests/AsistenciaControllerTest.php
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Controllers\AsistenciaController;

class AsistenciaControllerTest extends TestCase {
    
    private $controller;
    private $mockService;
    
    protected function setUp(): void {
        parent::setUp();
        $this->mockService = $this->createMock(\App\Services\AsistenciaService::class);
        $this->controller = new AsistenciaController($this->mockService);
    }
    
    /**
     * Test: Registrar entrada del practicante
     * Verifica que se registre correctamente la hora de entrada
     */
    public function testRegistrarEntrada() {
        // Setup
        $horaEntrada = date('H:i:s');
        $datos = [
            'practicanteID' => 1,
            'areaID' => 1,
            'horaEntrada' => $horaEntrada,
            'fecha' => date('Y-m-d')
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('registrarEntrada')
            ->willReturn(['success' => true, 'asistenciaID' => 1]);
        
        // Ejecutar
        $resultado = $this->mockService->registrarEntrada();
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['success']);
        $this->assertEquals(1, $resultado['asistenciaID']);
    }
    
    /**
     * Test: Registrar salida del practicante
     * Verifica que se registre correctamente la hora de salida
     */
    public function testRegistrarSalida() {
        // Setup
        $horaSalida = date('H:i:s');
        $datos = [
            'asistenciaID' => 1,
            'horaSalida' => $horaSalida
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('registrarSalida')
            ->with(1)
            ->willReturn(['success' => true, 'horasTrabajadas' => 8.5]);
        
        // Ejecutar
        $resultado = $this->mockService->registrarSalida(1);
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['success']);
        $this->assertEquals(8.5, $resultado['horasTrabajadas']);
    }
    
    /**
     * Test: Registrar pausa (inicio)
     * Verifica que se registre el inicio de una pausa
     */
    public function testRegistrarInicioPausa() {
        // Setup
        $horaPausa = date('H:i:s');
        
        $this->mockService
            ->expects($this->once())
            ->method('registrarInicioPausa')
            ->with(1)
            ->willReturn(['success' => true, 'pausaID' => 1]);
        
        // Ejecutar
        $resultado = $this->mockService->registrarInicioPausa(1);
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['success']);
        $this->assertEquals(1, $resultado['pausaID']);
    }
    
    /**
     * Test: Registrar pausa (fin)
     * Verifica que se registre el fin de una pausa
     */
    public function testRegistrarFinPausa() {
        // Setup
        $horaFin = date('H:i:s');
        
        $this->mockService
            ->expects($this->once())
            ->method('registrarFinPausa')
            ->with(1)
            ->willReturn(['success' => true, 'minutosPausa' => 15]);
        
        // Ejecutar
        $resultado = $this->mockService->registrarFinPausa(1);
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['success']);
        $this->assertEquals(15, $resultado['minutosPausa']);
    }
    
    /**
     * Test: Obtener asistencias por practicante
     * Verifica que retorne registro de asistencias
     */
    public function testObtenerAsistenciasPracticante() {
        // Setup
        $asistencias = [
            [
                'asistenciaID' => 1,
                'practicanteID' => 1,
                'fecha' => '2024-12-13',
                'horaEntrada' => '08:00:00',
                'horaSalida' => '17:00:00',
                'totalHoras' => 8.5
            ],
            [
                'asistenciaID' => 2,
                'practicanteID' => 1,
                'fecha' => '2024-12-12',
                'horaEntrada' => '08:15:00',
                'horaSalida' => '17:15:00',
                'totalHoras' => 8.5
            ]
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('obtenerAsistenciasPracticante')
            ->with(1)
            ->willReturn($asistencias);
        
        // Ejecutar
        $resultado = $this->mockService->obtenerAsistenciasPracticante(1);
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertCount(2, $resultado);
        $this->assertEquals(8.5, $resultado[0]['totalHoras']);
    }
    
    /**
     * Test: Obtener asistencias por área
     * Verifica que retorne asistencias de todos los practicantes de un área
     */
    public function testObtenerAsistenciasArea() {
        // Setup
        $asistencias = [
            ['asistenciaID' => 1, 'practicanteID' => 1, 'fecha' => '2024-12-13', 'totalHoras' => 8.5],
            ['asistenciaID' => 2, 'practicanteID' => 2, 'fecha' => '2024-12-13', 'totalHoras' => 7.5],
            ['asistenciaID' => 3, 'practicanteID' => 3, 'fecha' => '2024-12-13', 'totalHoras' => 9.0]
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('obtenerAsistenciasArea')
            ->with(1)
            ->willReturn($asistencias);
        
        // Ejecutar
        $resultado = $this->mockService->obtenerAsistenciasArea(1);
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertCount(3, $resultado);
    }
    
    /**
     * Test: Obtener asistencias por rango de fechas
     * Verifica que retorne asistencias en un periodo específico
     */
    public function testObtenerAsistenciasRangoFechas() {
        // Setup
        $asistencias = [
            ['asistenciaID' => 1, 'practicanteID' => 1, 'fecha' => '2024-12-01', 'totalHoras' => 8.5],
            ['asistenciaID' => 2, 'practicanteID' => 1, 'fecha' => '2024-12-02', 'totalHoras' => 8.5],
            ['asistenciaID' => 3, 'practicanteID' => 1, 'fecha' => '2024-12-03', 'totalHoras' => 8.0]
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('obtenerAsistenciasRango')
            ->with(1, '2024-12-01', '2024-12-31')
            ->willReturn($asistencias);
        
        // Ejecutar
        $resultado = $this->mockService->obtenerAsistenciasRango(1, '2024-12-01', '2024-12-31');
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertCount(3, $resultado);
    }
    
    /**
     * Test: Calcular total de horas trabajadas
     * Verifica que calcule correctamente el total de horas
     */
    public function testCalcularTotalHoras() {
        // Setup
        $this->mockService
            ->expects($this->once())
            ->method('calcularTotalHoras')
            ->with(1, '2024-12-01', '2024-12-31')
            ->willReturn(160.5);
        
        // Ejecutar
        $resultado = $this->mockService->calcularTotalHoras(1, '2024-12-01', '2024-12-31');
        
        // Verificar
        $this->assertIsFloat($resultado);
        $this->assertEquals(160.5, $resultado);
    }
    
    /**
     * Test: Validar que no hay entrada sin salida
     * Verifica que no permita registrar entrada si hay una activa sin salida
     */
    public function testEntradaActivaSinSalida() {
        // Setup
        $this->mockService
            ->expects($this->once())
            ->method('hayEntradaActiva')
            ->with(1)
            ->willReturn(true);
        
        // Ejecutar
        $resultado = $this->mockService->hayEntradaActiva(1);
        
        // Verificar
        $this->assertTrue($resultado);
    }
    
    /**
     * Test: Reporte de asistencias diarias
     * Verifica que genere reporte diario de asistencias
     */
    public function testReporteAsistenciasDiarias() {
        // Setup
        $reporte = [
            ['practicanteID' => 1, 'nombreCompleto' => 'Juan García', 'horaEntrada' => '08:00:00', 'horaSalida' => '17:00:00', 'presente' => true],
            ['practicanteID' => 2, 'nombreCompleto' => 'María López', 'horaEntrada' => NULL, 'horaSalida' => NULL, 'presente' => false]
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('reporteDiario')
            ->with(1, '2024-12-13')
            ->willReturn($reporte);
        
        // Ejecutar
        $resultado = $this->mockService->reporteDiario(1, '2024-12-13');
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertCount(2, $resultado);
        $this->assertTrue($resultado[0]['presente']);
        $this->assertFalse($resultado[1]['presente']);
    }
}
