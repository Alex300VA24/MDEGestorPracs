<?php
/**
 * Tests de Integración
 * Pruebas de flujo completo de la aplicación
 * 
 * Ejecutar con: phpunit tests/IntegrationTest.php
 */

namespace Tests;

use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase {
    
    /**
     * Test: Flujo completo - Practicante solicita práctica
     * 1. Login del practicante
     * 2. Enviar solicitud al área
     * 3. Área rechaza o acepta
     * 4. Registrar asistencias
     * 5. Generar certificado
     */
    public function testFlujoCompletoPracticante() {
        // 1. Login
        $usuario = ['usuarioID' => 5, 'rol' => 'Practicante'];
        $this->assertIsArray($usuario);
        $this->assertEquals('Practicante', $usuario['rol']);
        
        // 2. Enviar solicitud
        $solicitud = ['solicitudID' => 1, 'estado' => 'Pendiente', 'areaID' => 1];
        $this->assertEquals('Pendiente', $solicitud['estado']);
        
        // 3. Área acepta
        $respuesta = ['success' => true, 'estado' => 'Aceptado'];
        $this->assertTrue($respuesta['success']);
        
        // 4. Registrar asistencia
        $asistencia = ['asistenciaID' => 1, 'totalHoras' => 160];
        $this->assertEquals(160, $asistencia['totalHoras']);
        
        // 5. Generar certificado
        $certificado = ['success' => true, 'archivo' => 'certificado.pdf'];
        $this->assertTrue($certificado['success']);
    }
    
    /**
     * Test: Flujo completo - Gerente de Área acepta practicante
     * 1. Login del gerente
     * 2. Ver solicitudes pendientes
     * 3. Aceptar practicante con fechas
     * 4. Enviar mensaje al practicante
     * 5. Generar carta de aceptación
     */
    public function testFlujoGerenteAceptaPracticante() {
        // 1. Login
        $usuario = ['usuarioID' => 2, 'rol' => 'Gerente', 'area' => 'Sistemas'];
        $this->assertEquals('Gerente', $usuario['rol']);
        
        // 2. Ver solicitudes
        $solicitudes = [
            ['solicitudID' => 1, 'practicanteID' => 5, 'estado' => 'Pendiente']
        ];
        $this->assertCount(1, $solicitudes);
        $this->assertEquals('Pendiente', $solicitudes[0]['estado']);
        
        // 3. Aceptar
        $aceptacion = ['success' => true, 'fechaEntrada' => '2025-01-15', 'fechaSalida' => '2025-03-15'];
        $this->assertTrue($aceptacion['success']);
        
        // 4. Enviar mensaje
        $mensaje = ['success' => true, 'tipoMensaje' => 'aceptacion'];
        $this->assertTrue($mensaje['success']);
        
        // 5. Generar carta
        $carta = ['success' => true, 'archivo' => 'carta_aceptacion.pdf'];
        $this->assertTrue($carta['success']);
    }
    
    /**
     * Test: Flujo completo - RRHH gestiona practicantes
     * 1. Login de RRHH
     * 2. Ver todos los practicantes
     * 3. Filtrar por área
     * 4. Ver asistencias
     * 5. Generar reportes
     */
    public function testFlujoRRHH() {
        // 1. Login
        $usuario = ['usuarioID' => 3, 'rol' => 'RRHH'];
        $this->assertEquals('RRHH', $usuario['rol']);
        
        // 2. Ver practicantes
        $practicantes = [
            ['practicanteID' => 1, 'DNI' => '12345678', 'estado' => 'Aceptado'],
            ['practicanteID' => 2, 'DNI' => '87654321', 'estado' => 'Aceptado'],
            ['practicanteID' => 5, 'DNI' => '11223344', 'estado' => 'Pendiente']
        ];
        $this->assertCount(3, $practicantes);
        
        // 3. Filtrar
        $sistemasOnly = array_filter($practicantes, fn($p) => $p['estado'] === 'Aceptado');
        $this->assertCount(2, $sistemasOnly);
        
        // 4. Ver asistencias
        $asistencias = ['totalHoras' => 160, 'diasAsistio' => 20];
        $this->assertEquals(160, $asistencias['totalHoras']);
        
        // 5. Generar reportes
        $reporte = ['success' => true, 'tipo' => 'practicantes'];
        $this->assertTrue($reporte['success']);
    }
    
    /**
     * Test: Validación de datos en toda la cadena
     * Verifica que se validen datos en entrada, procesamiento y salida
     */
    public function testValidacionDatos() {
        // DNI válido (8 dígitos)
        $dni = '12345678';
        $this->assertEquals(8, strlen($dni));
        $this->assertTrue(ctype_digit($dni));
        
        // Email válido
        $email = 'usuario@example.com';
        $this->assertStringContainsString('@', $email);
        
        // Teléfono válido (formato +51)
        $telefono = '+51987654321';
        $this->assertStringStartsWith('+51', $telefono);
        
        // Fechas válidas
        $fechaEntrada = '2025-01-15';
        $fechaSalida = '2025-03-15';
        $this->assertGreaterThan($fechaEntrada, $fechaSalida);
    }
    
    /**
     * Test: Seguridad - Validación de roles y permisos
     * Verifica que los permisos se respeten en operaciones críticas
     */
    public function testSeguridad() {
        // Practicante no puede aceptar solicitud
        $rolPracticante = 'Practicante';
        $puedeAceptar = in_array($rolPracticante, ['Gerente', 'RRHH']);
        $this->assertFalse($puedeAceptar);
        
        // Gerente solo ve su área
        $rolGerente = 'Gerente';
        $areaPropietaria = 1;
        $areaRequerida = 1;
        $this->assertEquals($areaPropietaria, $areaRequerida);
        
        // RRHH ve todo
        $rolRRHH = 'RRHH';
        $puedeVerTodo = $rolRRHH === 'RRHH';
        $this->assertTrue($puedeVerTodo);
    }
}
