<?php
namespace App\Security;

/**
 * Sistema de autorización basado en roles y permisos
 */
class Authorization {
    
    // Roles disponibles
    const ROLE_ADMIN = 'admin';
    const ROLE_COORDINATOR = 'coordinador';
    const ROLE_SUPERVISOR = 'supervisor';
    const ROLE_GUEST = 'guest';
    
    // Mapa de permisos por rol
    private static $rolePermissions = [
        self::ROLE_ADMIN => [
            'usuarios.crear',
            'usuarios.leer',
            'usuarios.editar',
            'usuarios.eliminar',
            'practicantes.crear',
            'practicantes.leer',
            'practicantes.editar',
            'practicantes.eliminar',
            'solicitudes.crear',
            'solicitudes.leer',
            'solicitudes.editar',
            'solicitudes.eliminar',
            'reportes.leer',
            'reportes.generar',
            'asistencia.crear',
            'asistencia.editar',
        ],
        self::ROLE_COORDINATOR => [
            'practicantes.leer',
            'practicantes.editar',
            'solicitudes.leer',
            'solicitudes.editar',
            'reportes.leer',
            'asistencia.leer',
        ],
        self::ROLE_SUPERVISOR => [
            'practicantes.leer',
            'solicitudes.leer',
            'reportes.leer',
            'asistencia.leer',
            'asistencia.crear',
        ],
        self::ROLE_GUEST => [
            'practicantes.leer',
        ]
    ];
    
    /**
     * Verificar si el usuario está autenticado
     */
    public static function isAuthenticated() {
        return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true && !empty($_SESSION['usuarioID']);
    }
    
    /**
     * Obtener el rol del usuario actual
     */
    public static function getCurrentRole() {
        if (!self::isAuthenticated()) {
            return self::ROLE_GUEST;
        }
        
        return $_SESSION['userRole'] ?? self::ROLE_GUEST;
    }
    
    /**
     * Verificar si el usuario tiene un rol específico
     */
    public static function hasRole($role) {
        return self::getCurrentRole() === $role;
    }
    
    /**
     * Verificar si el usuario tiene un permiso específico
     */
    public static function hasPermission($permission) {
        $role = self::getCurrentRole();
        
        if (!isset(self::$rolePermissions[$role])) {
            return false;
        }
        
        return in_array($permission, self::$rolePermissions[$role]);
    }
    
    /**
     * Verificar si el usuario tiene alguno de los permisos solicitados
     */
    public static function hasAnyPermission($permissions) {
        foreach ($permissions as $permission) {
            if (self::hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Verificar si el usuario tiene todos los permisos solicitados
     */
    public static function hasAllPermissions($permissions) {
        foreach ($permissions as $permission) {
            if (!self::hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Requerir un permiso específico
     */
    public static function require($permission) {
        if (!self::hasPermission($permission)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'No tiene permisos para realizar esta acción'
            ]);
            exit;
        }
    }
    
    /**
     * Requerir autenticación
     */
    public static function requireAuth() {
        if (!self::isAuthenticated()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Debe estar autenticado para acceder a este recurso'
            ]);
            exit;
        }
    }
    
    /**
     * Requerir un rol específico
     */
    public static function requireRole($role) {
        if (!self::hasRole($role)) {
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
     * Obtener todos los permisos del rol actual
     */
    public static function getPermissions() {
        $role = self::getCurrentRole();
        return self::$rolePermissions[$role] ?? [];
    }
    
    /**
     * Obtener todos los permisos de un rol específico
     */
    public static function getRolePermissions($role) {
        return self::$rolePermissions[$role] ?? [];
    }
}
