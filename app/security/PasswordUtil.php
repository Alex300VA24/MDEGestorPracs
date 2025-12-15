<?php
namespace App\Security;

/**
 * Utilidades de seguridad para contraseñas
 * Implementa estándares de hashing y validación segura
 */
class PasswordUtil {
    
    const MIN_LENGTH = 8;
    const HASH_ALGO = PASSWORD_ARGON2ID;
    
    /**
     * Hash seguro de contraseña usando Argon2id
     */
    public static function hash($password) {
        if (strlen($password) < self::MIN_LENGTH) {
            throw new \Exception("La contraseña debe tener al menos " . self::MIN_LENGTH . " caracteres");
        }
        
        // Usar Argon2id si está disponible, sino usar bcrypt
        if (defined('PASSWORD_ARGON2ID')) {
            return password_hash($password, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536,  // 64 MB
                'time_cost' => 4,        // 4 iteraciones
                'threads' => 1
            ]);
        } else {
            return password_hash($password, PASSWORD_BCRYPT, [
                'cost' => 12  // Aumentar el costo para mejor seguridad
            ]);
        }
    }
    
    /**
     * Verificar contraseña de forma segura contra hash
     */
    public static function verify($password, $hash) {
        // Usar timing-safe comparison
        return password_verify($password, $hash);
    }
    
    /**
     * Verificar si el hash necesita ser rehashed
     */
    public static function needsRehash($hash) {
        if (defined('PASSWORD_ARGON2ID')) {
            return password_needs_rehash($hash, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 1
            ]);
        } else {
            return password_needs_rehash($hash, PASSWORD_BCRYPT, [
                'cost' => 12
            ]);
        }
    }
    
    /**
     * Validar fortaleza de contraseña
     */
    public static function validateStrength($password) {
        $errors = [];
        
        if (strlen($password) < self::MIN_LENGTH) {
            $errors[] = "Mínimo 8 caracteres";
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Debe contener letras minúsculas";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Debe contener letras mayúsculas";
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Debe contener números";
        }
        
        if (!preg_match('/[!@#$%^&*()_\-+=\[\]{};:\'",.<>?\/\\|`~]/', $password)) {
            $errors[] = "Debe contener caracteres especiales";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Generar contraseña temporal segura
     */
    public static function generateTemporary($length = 16) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }
    
    /**
     * Verificar si una contraseña ha sido comprometida en bases de datos públicas
     * Usando servicio Have I Been Pwned (HIBP) API
     */
    public static function checkCompromised($password) {
        try {
            // Crear SHA1 del hash SHA1 de la contraseña
            $hash = strtoupper(sha1($password));
            $prefix = substr($hash, 0, 5);
            $suffix = substr($hash, 5);
            
            // Usar API de Have I Been Pwned
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'user_agent' => 'GestorPracticantes/1.0'
                ]
            ]);
            
            $response = @file_get_contents(
                "https://api.pwnedpasswords.com/range/" . $prefix,
                false,
                $context
            );
            
            if ($response === false) {
                // Si no se puede validar, permitir (mejor experiencia de usuario)
                return false;
            }
            
            $hashes = explode("\r\n", $response);
            foreach ($hashes as $hash_line) {
                [$hash_suffix, $count] = explode(':', $hash_line);
                if (strtoupper($hash_suffix) === $suffix) {
                    return true; // Contraseña comprometida
                }
            }
            
            return false; // Contraseña no comprometida
        } catch (\Exception $e) {
            // Registrar error pero no bloquear
            error_log("Error al verificar contraseña comprometida: " . $e->getMessage());
            return false;
        }
    }
}
