<?php
namespace App\Security;

/**
 * Rate Limiter para prevenir ataques de fuerza bruta y abuso de API
 */
class RateLimiter {
    
    const DEFAULT_LIMIT = 100;      // Máximo de intentos
    const DEFAULT_WINDOW = 3600;    // Ventana de tiempo en segundos (1 hora)
    const LOGIN_LIMIT = 5;          // Máximo de intentos de login
    const LOGIN_WINDOW = 600;       // 10 minutos
    const API_LIMIT = 1000;         // Máximo de requests a la API
    const API_WINDOW = 3600;        // 1 hora
    
    /**
     * Verificar si un cliente ha excedido el límite de rate
     */
    public static function checkLimit($identifier, $limit = self::DEFAULT_LIMIT, $window = self::DEFAULT_WINDOW) {
        $key = "ratelimit_" . hash('sha256', $identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'count' => 0,
                'reset_at' => time() + $window
            ];
        }
        
        $bucket = $_SESSION[$key];
        
        // Si la ventana ha pasado, resetear
        if (time() > $bucket['reset_at']) {
            $_SESSION[$key] = [
                'count' => 0,
                'reset_at' => time() + $window
            ];
            $bucket = $_SESSION[$key];
        }
        
        // Verificar si se ha excedido el límite
        if ($bucket['count'] >= $limit) {
            $remaining = $bucket['reset_at'] - time();
            throw new \Exception("Rate limit excedido. Intente de nuevo en $remaining segundos");
        }
        
        return true;
    }
    
    /**
     * Registrar un intento en el rate limiter
     */
    public static function recordAttempt($identifier, $window = self::DEFAULT_WINDOW) {
        $key = "ratelimit_" . hash('sha256', $identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'count' => 0,
                'reset_at' => time() + $window
            ];
        }
        
        // Resetear si es necesario
        if (time() > $_SESSION[$key]['reset_at']) {
            $_SESSION[$key] = [
                'count' => 0,
                'reset_at' => time() + $window
            ];
        }
        
        $_SESSION[$key]['count']++;
    }
    
    /**
     * Reset manual de rate limit
     */
    public static function reset($identifier) {
        $key = "ratelimit_" . hash('sha256', $identifier);
        unset($_SESSION[$key]);
    }
    
    /**
     * Obtener información del rate limit actual
     */
    public static function getInfo($identifier, $limit = self::DEFAULT_LIMIT, $window = self::DEFAULT_WINDOW) {
        $key = "ratelimit_" . hash('sha256', $identifier);
        
        if (!isset($_SESSION[$key])) {
            return [
                'remaining' => $limit,
                'reset_at' => time() + $window,
                'exceeded' => false
            ];
        }
        
        $bucket = $_SESSION[$key];
        
        if (time() > $bucket['reset_at']) {
            return [
                'remaining' => $limit,
                'reset_at' => time() + $window,
                'exceeded' => false
            ];
        }
        
        return [
            'remaining' => max(0, $limit - $bucket['count']),
            'reset_at' => $bucket['reset_at'],
            'exceeded' => $bucket['count'] >= $limit
        ];
    }
}
