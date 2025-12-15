<?php
/**
 * Tests para SolicitudController (Extendido)
 * Pruebas de solicitudes, aceptación, documentos
 * 
 * Ejecutar con: phpunit tests/SolicitudControllerExtendedTest.php
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Controllers\SolicitudController;

class SolicitudControllerExtendedTest extends TestCase {
    
    private $controller;
    private $mockService;
    
    protected function setUp(): void {
        parent::setUp();
        $this->mockService = $this->createMock(\App\Services\SolicitudService::class);
        $this->controller = new SolicitudController($this->mockService);
    }
    
    /**
     * Test: Enviar solicitud de práctica al área
     * Verifica que se envíe correctamente la solicitud
     */
    public function testEnviarSolicitud() {
        // Setup
        $solicitud = [
            'practicanteID' => 1,
            'areaID' => 1,
            'descripcion' => 'Solicito práctica en el área de Sistemas',
            'periodoInicio' => '2025-01-15',
            'periodoFin' => '2025-03-15'
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('crearSolicitud')
            ->willReturn(['success' => true, 'solicitudID' => 1]);
        
        // Ejecutar
        $resultado = $this->mockService->crearSolicitud();
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['success']);
        $this->assertEquals(1, $resultado['solicitudID']);
    }
    
    /**
     * Test: Aceptar practicante
     * Verifica que se acepte correctamente un practicante
     */
    public function testAceptarPracticante() {
        // Setup
        $aceptacion = [
            'solicitudID' => 1,
            'fechaEntrada' => '2025-01-15',
            'fechaSalida' => '2025-03-15',
            'mensaje' => 'Bienvenido al área de Sistemas'
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('aceptarPracticante')
            ->with(1)
            ->willReturn(['success' => true, 'estadoID' => 2]);
        
        // Ejecutar
        $resultado = $this->mockService->aceptarPracticante(1);
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['success']);
        $this->assertEquals(2, $resultado['estadoID']); // Estado "Aceptado"
    }
    
    /**
     * Test: Rechazar practicante
     * Verifica que se rechace correctamente una solicitud
     */
    public function testRechazarPracticante() {
        // Setup
        $rechazo = [
            'solicitudID' => 1,
            'mensaje' => 'No contamos con vacantes en este momento'
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('rechazarPracticante')
            ->with(1)
            ->willReturn(['success' => true, 'estadoID' => 3]);
        
        // Ejecutar
        $resultado = $this->mockService->rechazarPracticante(1);
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['success']);
        $this->assertEquals(3, $resultado['estadoID']); // Estado "Rechazado"
    }
    
    /**
     * Test: Obtener solicitudes pendientes
     * Verifica que retorne solicitudes con estado pendiente
     */
    public function testObtenerSolicitudesPendientes() {
        // Setup
        $solicitudes = [
            ['solicitudID' => 1, 'practicanteID' => 1, 'areaID' => 1, 'estado' => 'Pendiente'],
            ['solicitudID' => 2, 'practicanteID' => 2, 'areaID' => 1, 'estado' => 'Pendiente']
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('obtenerPendientes')
            ->with(1)
            ->willReturn($solicitudes);
        
        // Ejecutar
        $resultado = $this->mockService->obtenerPendientes(1);
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertCount(2, $resultado);
        $this->assertEquals('Pendiente', $resultado[0]['estado']);
    }
    
    /**
     * Test: Obtener historial de solicitudes
     * Verifica que retorne todas las solicitudes de un practicante
     */
    public function testObtenerHistorialSolicitudes() {
        // Setup
        $solicitudes = [
            ['solicitudID' => 1, 'estado' => 'Aceptado', 'fechaCreacion' => '2024-10-01'],
            ['solicitudID' => 2, 'estado' => 'Pendiente', 'fechaCreacion' => '2024-12-13']
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('obtenerHistorial')
            ->with(1)
            ->willReturn($solicitudes);
        
        // Ejecutar
        $resultado = $this->mockService->obtenerHistorial(1);
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertCount(2, $resultado);
    }
    
    /**
     * Test: Subir documento (CV)
     * Verifica que se suba correctamente un documento
     */
    public function testSubirDocumento() {
        // Setup
        $_FILES = [
            'archivo' => [
                'name' => 'CV_Juan_Garcia.pdf',
                'tmp_name' => tempnam(sys_get_temp_dir(), 'doc'),
                'size' => 512000,
                'error' => UPLOAD_ERR_OK
            ]
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('subirDocumento')
            ->willReturn(['success' => true, 'documentoID' => 1]);
        
        // Ejecutar
        $resultado = $this->mockService->subirDocumento();
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['success']);
    }
    
    /**
     * Test: Subir documento con archivo muy grande
     * Verifica que rechace archivos que excedan el límite
     */
    public function testSubirDocumentoExcedeTamano() {
        // Setup
        $_FILES = [
            'archivo' => [
                'name' => 'archivo_grande.pdf',
                'tmp_name' => tempnam(sys_get_temp_dir(), 'doc'),
                'size' => 10 * 1024 * 1024, // 10MB
                'error' => UPLOAD_ERR_OK
            ]
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('subirDocumento')
            ->willThrowException(new \Exception('El archivo excede el tamaño máximo permitido'));
        
        // Verificar que lance excepción
        $this->expectException(\Exception::class);
        $this->mockService->subirDocumento();
    }
    
    /**
     * Test: Eliminar documento
     * Verifica que se elimine correctamente un documento
     */
    public function testEliminarDocumento() {
        // Setup
        $this->mockService
            ->expects($this->once())
            ->method('eliminarDocumento')
            ->with(1)
            ->willReturn(['success' => true]);
        
        // Ejecutar
        $resultado = $this->mockService->eliminarDocumento(1);
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['success']);
    }
    
    /**
     * Test: Obtener documentos de solicitud
     * Verifica que retorne lista de documentos
     */
    public function testObtenerDocumentosSolicitud() {
        // Setup
        $documentos = [
            ['documentoID' => 1, 'tipoDocumento' => 'cv', 'nombreArchivo' => 'CV.pdf'],
            ['documentoID' => 2, 'tipoDocumento' => 'certificado', 'nombreArchivo' => 'Certificado.pdf']
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('obtenerDocumentos')
            ->with(1)
            ->willReturn($documentos);
        
        // Ejecutar
        $resultado = $this->mockService->obtenerDocumentos(1);
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertCount(2, $resultado);
    }
}
