<?php
namespace App\Config;

/**
 * Configuración centralizada de seguridad
 */
class SecurityConfig {
    
    // Session configuration
    const SESSION_TIMEOUT = 3600;           // 1 hora
    const SESSION_ABSOLUTE_TIMEOUT = 28800; // 8 horas máximo
    
    // Password configuration
    const MIN_PASSWORD_LENGTH = 8;
    const PASSWORD_REQUIRE_UPPERCASE = true;
    const PASSWORD_REQUIRE_LOWERCASE = true;
    const PASSWORD_REQUIRE_NUMBERS = true;
    const PASSWORD_REQUIRE_SPECIAL = true;
    
    // Rate limiting configuration
    const RATE_LIMIT_LOGIN_ATTEMPTS = 5;
    const RATE_LIMIT_LOGIN_WINDOW = 600;    // 10 minutos
    const RATE_LIMIT_API_REQUESTS = 1000;
    const RATE_LIMIT_API_WINDOW = 3600;     // 1 hora
    
    // CORS configuration
    const CORS_ALLOW_CREDENTIALS = true;
    const CORS_MAX_AGE = 86400;            // 24 horas
    
    // File upload configuration
    const MAX_UPLOAD_SIZE = 10 * 1024 * 1024;  // 10 MB
    const ALLOWED_UPLOAD_TYPES = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
    
    // Security headers
    const ENABLE_HSTS = true;
    const ENABLE_CSP = true;
    const ENABLE_CORS = true;
    
    // Encryption
    const ENCRYPTION_ALGO = 'AES-256-CBC';
    
    // Audit logging
    const ENABLE_AUDIT_LOGGING = true;
    const AUDIT_LOG_FILE = __DIR__ . '/../../logs/audit.log';
    
    // IP whitelist/blacklist (para producción)
    const IP_WHITELIST_ENABLED = false;
    const IP_WHITELIST = [];
    
    const IP_BLACKLIST_ENABLED = false;
    const IP_BLACKLIST = [];
    
    /**
     * Validar sesión actual
     */
    public static function validateSession() {
        // Verificar si la sesión ha expirado
        if (isset($_SESSION['login_time'])) {
            $elapsed = time() - $_SESSION['login_time'];
            
            if ($elapsed > self::SESSION_TIMEOUT) {
                session_destroy();
                throw new \Exception("Sesión expirada. Por favor, inicie sesión nuevamente.");
            }
        }
        
        // Verificar cambio de IP (detección de session hijacking)
        if (isset($_SESSION['ip'])) {
            $currentIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            if ($_SESSION['ip'] !== $currentIP) {
                session_destroy();
                throw new \Exception("Cambio de IP detectado. Sesión invalidada por seguridad.");
            }
        }
    }
    
    /**
     * Validar tamaño de archivo
     */
    public static function validateFileSize($size) {
        if ($size > self::MAX_UPLOAD_SIZE) {
            throw new \Exception("Archivo demasiado grande. Máximo: " . (self::MAX_UPLOAD_SIZE / 1024 / 1024) . " MB");
        }
        return true;
    }
    
    /**
     * Validar tipo de archivo
     */
    public static function validateFileType($extension) {
        $extension = strtolower($extension);
        if (!in_array($extension, self::ALLOWED_UPLOAD_TYPES)) {
            throw new \Exception("Tipo de archivo no permitido. Tipos válidos: " . implode(", ", self::ALLOWED_UPLOAD_TYPES));
        }
        return true;
    }
    
    /**
     * Validar IP (si está habilitado)
     */
    public static function validateIP($ip) {
        if (self::IP_WHITELIST_ENABLED) {
            if (!in_array($ip, self::IP_WHITELIST)) {
                throw new \Exception("Acceso denegado. Tu IP no está en la lista blanca.");
            }
        }
        
        if (self::IP_BLACKLIST_ENABLED) {
            if (in_array($ip, self::IP_BLACKLIST)) {
                throw new \Exception("Acceso denegado. Tu IP está bloqueada.");
            }
        }
        
        return true;
    }
}
