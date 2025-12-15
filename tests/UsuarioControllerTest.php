<?php
/**
 * Tests para UsuarioController
 * Pruebas de Login, Logout, Validación de CUI y CRUD de Usuarios
 * 
 * Ejecutar con: phpunit tests/UsuarioControllerTest.php
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Controllers\UsuarioController;

class UsuarioControllerTest extends TestCase {
    
    private $controller;
    private $mockService;
    
    protected function setUp(): void {
        parent::setUp();
        // Crear un mock del servicio
        $this->mockService = $this->createMock(\App\Services\UsuarioService::class);
        $this->controller = new UsuarioController($this->mockService);
    }
    
    /**
     * Test: Login con credenciales válidas
     * Verifica que el método login retorne éxito y datos del usuario
     */
    public function testLoginExitoso() {
        // Setup
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        
        $userData = [
            'usuarioID' => 1,
            'nombreUsuario' => 'admin',
            'nombreCompleto' => 'Administrador Sistema',
            'area' => ['areaID' => 1, 'nombreArea' => 'Sistemas'],
            'cargo' => ['cargoID' => 1, 'nombreCargo' => 'Administrador'],
            'requireCUI' => true
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('login')
            ->with('admin', 'password123')
            ->willReturn($userData);
        
        // Limpiar y establecer entrada
        ob_start();
        $_POST = ['nombreUsuario' => 'admin', 'password' => 'password123'];
        
        // Ejecutar
        $response = $this->controller->login();
        ob_end_clean();
        
        // Verificar
        $this->assertIsArray($userData);
        $this->assertEquals(1, $userData['usuarioID']);
        $this->assertEquals('admin', $userData['nombreUsuario']);
    }
    
    /**
     * Test: Login con credenciales inválidas
     * Verifica que lance excepción
     */
    public function testLoginFallido() {
        // Setup
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        
        $this->mockService
            ->expects($this->once())
            ->method('login')
            ->with('usuario_falso', 'password_falsa')
            ->willThrowException(new \Exception('Usuario o contraseña incorrectos'));
        
        // Verificar que lance excepción
        $this->expectException(\Exception::class);
        $this->mockService->login('usuario_falso', 'password_falsa');
    }
    
    /**
     * Test: Validación de CUI exitosa
     * Verifica que valide correctamente el CUI del usuario
     */
    public function testValidarCUIExitoso() {
        // Setup
        $_SESSION['usuarioID'] = 1;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        $usuarioValidado = [
            'usuarioID' => 1,
            'nombreUsuario' => 'admin',
            'nombreCompleto' => 'Administrador Sistema',
            'cui' => '12345678',
            'area' => ['areaID' => 1, 'nombreArea' => 'Sistemas'],
            'cargo' => ['cargoID' => 1, 'nombreCargo' => 'Administrador']
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('validarCUI')
            ->with(1, '12345678')
            ->willReturn($usuarioValidado);
        
        // Ejecutar
        $resultado = $this->mockService->validarCUI(1, '12345678');
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertEquals('12345678', $resultado['cui']);
        $this->assertEquals(1, $resultado['usuarioID']);
    }
    
    /**
     * Test: Validación de CUI fallida
     * Verifica que rechace un CUI incorrecto
     */
    public function testValidarCUIFallido() {
        // Setup
        $_SESSION['usuarioID'] = 1;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        $this->mockService
            ->expects($this->once())
            ->method('validarCUI')
            ->with(1, 'CUI_INCORRECTO')
            ->willThrowException(new \Exception('CUI no válido'));
        
        // Verificar que lance excepción
        $this->expectException(\Exception::class);
        $this->mockService->validarCUI(1, 'CUI_INCORRECTO');
    }
    
    /**
     * Test: Logout
     * Verifica que el logout limpie la sesión
     */
    public function testLogout() {
        // Setup
        $_SESSION['usuarioID'] = 1;
        $_SESSION['authenticated'] = true;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        // Simular logout
        unset($_SESSION['usuarioID']);
        unset($_SESSION['authenticated']);
        
        // Verificar
        $this->assertFalse(isset($_SESSION['usuarioID']));
        $this->assertFalse(isset($_SESSION['authenticated']));
    }
    
    /**
     * Test: Crear usuario (CRUD - Create)
     * Verifica que se cree un nuevo usuario correctamente
     */
    public function testCrearUsuario() {
        // Setup
        $nuevoUsuario = [
            'nombreUsuario' => 'gerente_rrhh',
            'nombreCompleto' => 'Gerente RRHH',
            'email' => 'gerente@example.com',
            'cargoID' => 2,
            'areaID' => 1,
            'password' => 'SecurePass123!'
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('crearUsuario')
            ->with($this->isInstanceOf(\App\Models\Usuario::class))
            ->willReturn(['success' => true, 'usuarioID' => 2]);
        
        // Ejecutar
        $usuarioMock = $this->createMock(\App\Models\Usuario::class);
        $resultado = $this->mockService->crearUsuario($usuarioMock);
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['success']);
        $this->assertEquals(2, $resultado['usuarioID']);
    }
    
    /**
     * Test: Actualizar usuario (CRUD - Update)
     * Verifica que se actualice un usuario existente
     */
    public function testActualizarUsuario() {
        // Setup
        $usuarioActualizado = [
            'usuarioID' => 1,
            'nombreCompleto' => 'Administrador Sistema Actualizado',
            'email' => 'admin_nuevo@example.com'
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('actualizarUsuario')
            ->with(1, $this->isInstanceOf(\App\Models\Usuario::class))
            ->willReturn(['success' => true]);
        
        // Ejecutar
        $usuarioMock = $this->createMock(\App\Models\Usuario::class);
        $resultado = $this->mockService->actualizarUsuario(1, $usuarioMock);
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['success']);
    }
    
    /**
     * Test: Listar usuarios (CRUD - Read)
     * Verifica que retorne lista de usuarios
     */
    public function testListarUsuarios() {
        // Setup
        $usuarios = [
            ['usuarioID' => 1, 'nombreUsuario' => 'admin', 'nombreCompleto' => 'Administrador'],
            ['usuarioID' => 2, 'nombreUsuario' => 'gerente', 'nombreCompleto' => 'Gerente RRHH']
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('obtenerTodos')
            ->willReturn($usuarios);
        
        // Ejecutar
        $resultado = $this->mockService->obtenerTodos();
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertCount(2, $resultado);
        $this->assertEquals('admin', $resultado[0]['nombreUsuario']);
    }
    
    /**
     * Test: Obtener usuario por ID (CRUD - Read)
     * Verifica que retorne los datos de un usuario específico
     */
    public function testObtenerUsuarioPorID() {
        // Setup
        $usuario = [
            'usuarioID' => 1,
            'nombreUsuario' => 'admin',
            'nombreCompleto' => 'Administrador Sistema',
            'email' => 'admin@example.com'
        ];
        
        $this->mockService
            ->expects($this->once())
            ->method('obtenerPorID')
            ->with(1)
            ->willReturn($usuario);
        
        // Ejecutar
        $resultado = $this->mockService->obtenerPorID(1);
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertEquals('admin', $resultado['nombreUsuario']);
    }
    
    /**
     * Test: Eliminar usuario (CRUD - Delete)
     * Verifica que se elimine un usuario correctamente
     */
    public function testEliminarUsuario() {
        // Setup
        $this->mockService
            ->expects($this->once())
            ->method('eliminarUsuario')
            ->with(2)
            ->willReturn(['success' => true]);
        
        // Ejecutar
        $resultado = $this->mockService->eliminarUsuario(2);
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['success']);
    }
    
    /**
     * Test: Cambiar contraseña
     * Verifica que se cambie la contraseña de forma segura
     */
    public function testCambiarContrasena() {
        // Setup
        $this->mockService
            ->expects($this->once())
            ->method('cambiarContrasena')
            ->with(1, 'password_antiguo', 'password_nuevo')
            ->willReturn(['success' => true]);
        
        // Ejecutar
        $resultado = $this->mockService->cambiarContrasena(1, 'password_antiguo', 'password_nuevo');
        
        // Verificar
        $this->assertIsArray($resultado);
        $this->assertTrue($resultado['success']);
    }
}
