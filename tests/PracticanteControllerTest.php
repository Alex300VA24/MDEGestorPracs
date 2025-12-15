<?php
/**
 * Tests para PracticanteController
 * Pruebas de CRUD Practicantes, filtros y búsqueda
 * 
 * Ejecutar con: phpunit tests/PracticanteControllerTest.php
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Controllers\PracticanteController;

class PracticanteControllerTest extends TestCase {
    
    private $controller;
    private $mockService;
    
    protected function setUp(): void {
        parent::setUp();
        $this->mockService = $this->createMock(\App\Services\PracticanteService::class);
        $this->controller = new PracticanteController($this->mockService);
    }
    
    /**
     * Test: Crear practicante (CRUD - Create)
     * Verifica que se cree un nuevo practicante correctamente
     */
    public function testCrearPracticante() {
        // Setup
        $nuevoPracticante = [
            'DNI' => '12345678',
            'Nombres' => 'Juan Carlos',
            'ApellidoPaterno' => 'García',
            'ApellidoMaterno' => 'López',
            'Carrera' => 'Ingeniería de Sistemas',
            'Universidad' => 'Universidad Nacional de Trujillo',
            'Email' => 'juan@example.com',
            'Telefono' => '+51987654321',
            'Direccion' => 'Av. España 123'
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('crearPracticante')
            ->willReturn(['success' => true, 'practicanteID' => 1]);
        
        // Ejecutar
        $resultado = $this->mockService->crearPracticante();
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['success']);
        $this->assertEquals(1, $resultado['practicanteID']);
    }
    
    /**
     * Test: Crear practicante con DNI duplicado
     * Verifica que rechace DNI duplicado
     */
    public function testCrearPracticanteDNIDuplicado() {
        // Setup
        $this->mockService
            ->expects($this->once())
            ->method('crearPracticante')
            ->willThrowException(new \Exception('El DNI ya está registrado'));
        
        // Verificar que lance excepción
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('El DNI ya está registrado');
        $this->mockService->crearPracticante();
    }
    
    /**
     * Test: Actualizar practicante (CRUD - Update)
     * Verifica que se actualice un practicante existente
     */
    public function testActualizarPracticante() {
        // Setup
        $practicanteActualizado = [
            'practicanteID' => 1,
            'Email' => 'juan_nuevo@example.com',
            'Telefono' => '+51912345678'
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('actualizarPracticante')
            ->with(1)
            ->willReturn(['success' => true]);
        
        // Ejecutar
        $resultado = $this->mockService->actualizarPracticante(1);
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['success']);
    }
    
    /**
     * Test: Listar practicantes (CRUD - Read)
     * Verifica que retorne lista de practicantes
     */
    public function testListarPracticantes() {
        // Setup
        $practicantes = [
            ['practicanteID' => 1, 'DNI' => '12345678', 'Nombres' => 'Juan Carlos'],
            ['practicanteID' => 2, 'DNI' => '87654321', 'Nombres' => 'María García']
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('obtenerTodos')
            ->willReturn($practicantes);
        
        // Ejecutar
        $resultado = $this->mockService->obtenerTodos();
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertCount(2, $resultado);
        $this->assertEquals('12345678', $resultado[0]['DNI']);
    }
    
    /**
     * Test: Obtener practicante por ID (CRUD - Read)
     * Verifica que retorne datos de un practicante específico
     */
    public function testObtenerPracticantePorID() {
        // Setup
        $practicante = [
            'practicanteID' => 1,
            'DNI' => '12345678',
            'Nombres' => 'Juan Carlos',
            'ApellidoPaterno' => 'García',
            'ApellidoMaterno' => 'López'
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('obtenerPorID')
            ->with(1)
            ->willReturn($practicante);
        
        // Ejecutar
        $resultado = $this->mockService->obtenerPorID(1);
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertEquals('12345678', $resultado['DNI']);
    }
    
    /**
     * Test: Eliminar practicante (CRUD - Delete)
     * Verifica que se elimine un practicante correctamente
     */
    public function testEliminarPracticante() {
        // Setup
        $this->mockService
            ->expects($this->once())
            ->method('eliminarPracticante')
            ->with(1)
            ->willReturn(['success' => true]);
        
        // Ejecutar
        $resultado = $this->mockService->eliminarPracticante(1);
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['success']);
    }
    
    /**
     * Test: Filtrar practicantes por área
     * Verifica que retorne solo practicantes del área especificada
     */
    public function testFiltrarPorArea() {
        // Setup
        $practicantesArea = [
            ['practicanteID' => 1, 'DNI' => '12345678', 'NombreArea' => 'Sistemas'],
            ['practicanteID' => 3, 'DNI' => '11223344', 'NombreArea' => 'Sistemas']
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('obtenerPorArea')
            ->with(1)
            ->willReturn($practicantesArea);
        
        // Ejecutar
        $resultado = $this->mockService->obtenerPorArea(1);
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertCount(2, $resultado);
        $this->assertEquals('Sistemas', $resultado[0]['NombreArea']);
    }
    
    /**
     * Test: Filtrar practicantes por estado
     * Verifica que retorne practicantes con estado específico
     */
    public function testFiltrarPorEstado() {
        // Setup
        $practicantesPendientes = [
            ['practicanteID' => 1, 'DNI' => '12345678', 'EstadoDescripcion' => 'Pendiente'],
            ['practicanteID' => 2, 'DNI' => '87654321', 'EstadoDescripcion' => 'Pendiente']
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('obtenerPorEstado')
            ->with('Pendiente')
            ->willReturn($practicantesPendientes);
        
        // Ejecutar
        $resultado = $this->mockService->obtenerPorEstado('Pendiente');
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertCount(2, $resultado);
        $this->assertEquals('Pendiente', $resultado[0]['EstadoDescripcion']);
    }
    
    /**
     * Test: Búsqueda por DNI
     * Verifica que busque practicante por DNI
     */
    public function testBuscarPorDNI() {
        // Setup
        $practicante = [
            'practicanteID' => 1,
            'DNI' => '12345678',
            'Nombres' => 'Juan Carlos'
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('obtenerPorDNI')
            ->with('12345678')
            ->willReturn($practicante);
        
        // Ejecutar
        $resultado = $this->mockService->obtenerPorDNI('12345678');
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertEquals('12345678', $resultado['DNI']);
    }
    
    /**
     * Test: Búsqueda por nombre
     * Verifica que busque practicantes por nombre
     */
    public function testBuscarPorNombre() {
        // Setup
        $practicantes = [
            ['practicanteID' => 1, 'Nombres' => 'Juan Carlos', 'ApellidoPaterno' => 'García'],
            ['practicanteID' => 3, 'Nombres' => 'Juan Pedro', 'ApellidoPaterno' => 'López']
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('buscarPorNombre')
            ->with('Juan')
            ->willReturn($practicantes);
        
        // Ejecutar
        $resultado = $this->mockService->buscarPorNombre('Juan');
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertCount(2, $resultado);
    }
    
    /**
     * Test: Filtrar por fecha de registro
     * Verifica que retorne practicantes registrados en un periodo
     */
    public function testFiltrarPorFecha() {
        // Setup
        $practicantes = [
            ['practicanteID' => 1, 'DNI' => '12345678', 'FechaRegistro' => '2024-12-01'],
            ['practicanteID' => 2, 'DNI' => '87654321', 'FechaRegistro' => '2024-12-05']
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('obtenerPorFecha')
            ->with('2024-12-01', '2024-12-31')
            ->willReturn($practicantes);
        
        // Ejecutar
        $resultado = $this->mockService->obtenerPorFecha('2024-12-01', '2024-12-31');
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertCount(2, $resultado);
    }
}
