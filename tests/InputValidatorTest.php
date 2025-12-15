<?php
/**
 * Tests para InputValidator
 * Pruebas de validaciones de seguridad sin necesidad de BD
 * 
 * Ejecutar con: vendor\bin\phpunit tests/InputValidatorTest.php
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Security\InputValidator;

class InputValidatorTest extends TestCase {
    
    /**
     * Test: Validar DNI válido (9 dígitos)
     */
    public function testValidateDNIValido() {
        // Debería pasar sin excepciones
        $resultado = InputValidator::validateDNI('123456789');
        $this->assertEquals('123456789', $resultado);
    }
    
    /**
     * Test: Validar DNI inválido (menos de 9 dígitos)
     */
    public function testValidateDNIInvalido() {
        // Debe lanzar excepción
        $this->expectException(\Exception::class);
        InputValidator::validateDNI('123');
    }
    
    /**
     * Test: Validar string válido
     */
    public function testValidateStringValido() {
        // Debería pasar sin excepciones
        $resultado = InputValidator::validateString('usuario_válido', 1, 50);
        $this->assertGreaterThan(0, strlen($resultado));
    }
    
    /**
     * Test: Validar string muy corto
     */
    public function testValidateStringCorto() {
        // Debe lanzar excepción
        $this->expectException(\Exception::class);
        InputValidator::validateString('', 1, 50);
    }
    
    /**
     * Test: Validar email válido
     */
    public function testValidateEmailValido() {
        // Debería pasar sin excepciones
        $resultado = InputValidator::validateEmail('test@example.com');
        $this->assertEquals('test@example.com', $resultado);
    }
    
    /**
     * Test: Validar email inválido
     */
    public function testValidateEmailInvalido() {
        // Debe lanzar excepción
        $this->expectException(\Exception::class);
        InputValidator::validateEmail('email_invalido');
    }
    
    /**
     * Test: Validar número entero válido
     */
    public function testValidateIntValido() {
        $resultado = InputValidator::validateInt(42);
        $this->assertEquals(42, $resultado);
    }
    
    /**
     * Test: Validar número entero inválido
     */
    public function testValidateIntInvalido() {
        $this->expectException(\Exception::class);
        InputValidator::validateInt('abc');
    }
    
    /**
     * Test: Validar nombre de archivo válido
     */
    public function testSanitizeFilenameValido() {
        $resultado = InputValidator::sanitizeFilename('documento_prueba.pdf');
        $this->assertStringNotContainsString('/', $resultado);
        $this->assertStringNotContainsString('\\', $resultado);
    }
    
    /**
     * Test: Validar URL válida
     */
    public function testValidateURLValida() {
        $resultado = InputValidator::validateURL('https://example.com');
        $this->assertEquals('https://example.com', $resultado);
    }
    
    /**
     * Test: Validar URL inválida
     */
    public function testValidateURLInvalida() {
        $this->expectException(\Exception::class);
        InputValidator::validateURL('no_es_url');
    }
}
