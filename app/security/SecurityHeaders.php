<?php
namespace App\Security;

/**
 * Gestor de headers de seguridad HTTP
 */
class SecurityHeaders {
    
    /**
     * Aplicar todos los headers de seguridad recomendados
     */
    public static function applyAllHeaders($isHttps = true) {
        // Prevenir MIME sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Protección contra clickjacking
        header('X-Frame-Options: SAMEORIGIN');
        
        // Prevenir XSS
        header("X-XSS-Protection: 1; mode=block");
        
        // Política de referrer
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy
        header("Content-Security-Policy: " . self::getCSPHeader());
        
        // Permitir solo conexiones HTTPS
        if ($isHttps) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
        
        // Permisos de características
        header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');
        
        // Información de servidor
        header_remove('Server');
        header_remove('X-Powered-By');
        
        // Cache control para recursos sensibles
        header('Cache-Control: no-cache, no-store, must-revalidate, private');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
    
    /**
     * Obtener CSP header completo
     */
    private static function getCSPHeader() {
        $directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",  // Más restrictivo en producción
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "upgrade-insecure-requests"
        ];
        
        return implode('; ', $directives);
    }
    
    /**
     * Header para evitar MIME sniffing
     */
    public static function preventMimeSniffer() {
        header('X-Content-Type-Options: nosniff');
    }
    
    /**
     * Header para prevenir clickjacking
     */
    public static function preventClickjacking() {
        header('X-Frame-Options: SAMEORIGIN');
    }
    
    /**
     * Header para XSS protection
     */
    public static function xssProtection() {
        header('X-XSS-Protection: 1; mode=block');
    }
    
    /**
     * Header para HTTPS
     */
    public static function enforceHTTPS() {
        if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
            header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            exit;
        }
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
    
    /**
     * Eliminar headers que expongan información del servidor
     */
    public static function hideSensitiveHeaders() {
        header_remove('Server');
        header_remove('X-Powered-By');
        header_remove('X-AspNet-Version');
        header_remove('X-Runtime');
    }
}
