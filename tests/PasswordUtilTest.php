<?php
/**
 * Tests para PasswordUtil
 * Pruebas de hashing y verificación de contraseñas sin BD
 * 
 * Ejecutar con: vendor\bin\phpunit tests/PasswordUtilTest.php
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Security\PasswordUtil;

class PasswordUtilTest extends TestCase {
    
    /**
     * Test: Hash de contraseña
     */
    public function testHashPassword() {
        $password = 'miContraseñaSegura123!';
        $hash = PasswordUtil::hash($password);
        
        // El hash no debe ser la contraseña en texto plano
        $this->assertNotEquals($password, $hash);
        
        // El hash debe tener longitud
        $this->assertGreaterThan(0, strlen($hash));
    }
    
    /**
     * Test: Verificar hash correcto
     */
    public function testVerifyPasswordCorrect() {
        $password = 'miContraseñaSegura123!';
        $hash = PasswordUtil::hash($password);
        
        // Debe verificar exitosamente
        $this->assertTrue(PasswordUtil::verify($password, $hash));
    }
    
    /**
     * Test: Rechazar hash incorrecto
     */
    public function testVerifyPasswordIncorrect() {
        $password = 'miContraseñaSegura123!';
        $wrongPassword = 'contraseñaIncorrecta';
        
        $hash = PasswordUtil::hash($password);
        
        // Debe rechazar contraseña diferente
        $this->assertFalse(PasswordUtil::verify($wrongPassword, $hash));
    }
    
    /**
     * Test: Hash de diferentes contraseñas son diferentes
     */
    public function testDifferentHashesForDifferentPasswords() {
        $password1 = 'contraseña1';
        $password2 = 'contraseña2';
        
        $hash1 = PasswordUtil::hash($password1);
        $hash2 = PasswordUtil::hash($password2);
        
        // Hashes diferentes
        $this->assertNotEquals($hash1, $hash2);
    }
    
    /**
     * Test: Rehashing necesario para hashes viejos
     */
    public function testNeedsRehash() {
        $newPassword = 'password123';
        $newHash = PasswordUtil::hash($newPassword);
        
        // Hash nuevo no debe necesitar rehash
        $this->assertFalse(PasswordUtil::needsRehash($newHash));
    }
    
    /**
     * Test: Hash válido se ve diferente cada vez (salt)
     */
    public function testHashConsistency() {
        $password = 'MiContraseña123!@#';
        
        $hash1 = PasswordUtil::hash($password);
        $hash2 = PasswordUtil::hash($password);
        
        // Hashes diferentes (por el salt aleatorio)
        $this->assertNotEquals($hash1, $hash2);
        
        // Pero ambos verifican contra la misma contraseña
        $this->assertTrue(PasswordUtil::verify($password, $hash1));
        $this->assertTrue(PasswordUtil::verify($password, $hash2));
    }
}
