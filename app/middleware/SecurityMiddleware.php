<?php
namespace App\Middleware;

use App\Security\Authorization;
use App\Security\AuditLogger;
use App\Config\SecurityConfig;

/**
 * Middleware de seguridad para validar requests
 */
class SecurityMiddleware {
    
    /**
     * Validar autenticación y sesión
     */
    public static function checkAuthentication() {
        // Validar que exista sesión
        if (!isset($_SESSION)) {
            throw new \Exception("Sesión no iniciada");
        }
        
        // Validar que el usuario esté autenticado
        if (!Authorization::isAuthenticated()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Debe estar autenticado para acceder a este recurso'
            ]);
            exit;
        }
        
        // Validar sesión (timeout y cambio de IP)
        try {
            SecurityConfig::validateSession();
        } catch (\Exception $e) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }
    
    /**
     * Requerir un permiso específico
     */
    public static function requirePermission($permission) {
        self::checkAuthentication();
        
        if (!Authorization::hasPermission($permission)) {
            AuditLogger::logPermissionDenied($permission, 'API');
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'No tiene permiso para realizar esta acción'
            ]);
            exit;
        }
    }
    
    /**
     * Requerir un rol específico
     */
    public static function requireRole($role) {
        self::checkAuthentication();
        
        if (!Authorization::hasRole($role)) {
            AuditLogger::logPermissionDenied('access:' . $role, 'API');
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Su rol no tiene acceso a este recurso'
            ]);
            exit;
        }
    }
    
    /**
     * Validar CSRF token
     */
    public static function validateCSRF() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Solo verificar para métodos no-seguros
        if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            return true;
        }
        
        $tokenHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $tokenHeader)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'CSRF token inválido o ausente'
            ]);
            exit;
        }
        
        return true;
    }
    
    /**
     * Validar JSON input
     */
    public static function validateJSON() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Solo validar para métodos que esperan JSON
        if (!in_array($method, ['POST', 'PUT', 'PATCH'])) {
            return true;
        }
        
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (empty($contentType) || strpos($contentType, 'application/json') === false) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Content-Type debe ser application/json'
            ]);
            exit;
        }
        
        return true;
    }
    
    /**
     * Prevenir ataques comunes
     */
    public static function preventCommonAttacks() {
        // Validar IP si está habilitado
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        try {
            SecurityConfig::validateIP($ip);
        } catch (\Exception $e) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
        }
        
        // Prevenir path traversal
        $path = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('/\.\./', $path)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Solicitud inválida'
            ]);
            exit;
        }
    }
    
    /**
     * Aplicar todos los middleware de seguridad
     */
    public static function applyAll($requireAuth = true, $permissions = [], $roles = []) {
        // Prevenir ataques comunes
        self::preventCommonAttacks();
        
        // Validar JSON
        self::validateJSON();
        
        // Validar CSRF
        self::validateCSRF();
        
        // Validar autenticación si es requerida
        if ($requireAuth) {
            self::checkAuthentication();
        }
        
        // Validar permisos
        foreach ($permissions as $permission) {
            self::requirePermission($permission);
        }
        
        // Validar roles
        foreach ($roles as $role) {
            self::requireRole($role);
        }
        
        return true;
    }
}
