<?php
namespace App\Security;

/**
 * Sistema de auditoría para registrar actividades críticas
 */
class AuditLogger {
    
    const ACTION_LOGIN = 'LOGIN';
    const ACTION_LOGOUT = 'LOGOUT';
    const ACTION_CREATE = 'CREATE';
    const ACTION_UPDATE = 'UPDATE';
    const ACTION_DELETE = 'DELETE';
    const ACTION_VIEW = 'VIEW';
    const ACTION_EXPORT = 'EXPORT';
    const ACTION_FAILED_LOGIN = 'FAILED_LOGIN';
    const ACTION_PERMISSION_DENIED = 'PERMISSION_DENIED';
    
    private static $logFile = __DIR__ . '/../../logs/audit.log';
    
    /**
     * Registrar una acción en el log de auditoría
     */
    public static function log($action, $entity, $details = [], $userId = null) {
        // Crear directorio de logs si no existe
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $userId = $userId ?? ($_SESSION['usuarioID'] ?? 'SYSTEM');
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
        
        $logEntry = [
            'timestamp' => $timestamp,
            'action' => $action,
            'entity' => $entity,
            'userId' => $userId,
            'ip' => $ip,
            'userAgent' => $userAgent,
            'details' => json_encode($details),
        ];
        
        $line = json_encode($logEntry) . PHP_EOL;
        
        try {
            file_put_contents(self::$logFile, $line, FILE_APPEND | LOCK_EX);
        } catch (\Exception $e) {
            error_log("Error escribiendo en audit log: " . $e->getMessage());
        }
    }
    
    /**
     * Registrar login exitoso
     */
    public static function logLogin($userId, $userName) {
        self::log(self::ACTION_LOGIN, 'Usuario', [
            'userName' => $userName,
            'status' => 'exitoso'
        ], $userId);
    }
    
    /**
     * Registrar intento de login fallido
     */
    public static function logFailedLogin($userName) {
        self::log(self::ACTION_FAILED_LOGIN, 'Usuario', [
            'userName' => $userName,
            'status' => 'fallido'
        ]);
    }
    
    /**
     * Registrar logout
     */
    public static function logLogout($userId) {
        self::log(self::ACTION_LOGOUT, 'Usuario', [
            'status' => 'exitoso'
        ], $userId);
    }
    
    /**
     * Registrar creación de entidad
     */
    public static function logCreate($entity, $entityId, $details = []) {
        self::log(self::ACTION_CREATE, $entity, array_merge(['entityId' => $entityId], $details));
    }
    
    /**
     * Registrar actualización de entidad
     */
    public static function logUpdate($entity, $entityId, $changes = []) {
        self::log(self::ACTION_UPDATE, $entity, [
            'entityId' => $entityId,
            'changes' => $changes
        ]);
    }
    
    /**
     * Registrar eliminación de entidad
     */
    public static function logDelete($entity, $entityId) {
        self::log(self::ACTION_DELETE, $entity, ['entityId' => $entityId]);
    }
    
    /**
     * Registrar acceso denegado
     */
    public static function logPermissionDenied($action, $entity) {
        self::log(self::ACTION_PERMISSION_DENIED, $entity, [
            'action' => $action,
            'status' => 'denegado'
        ]);
    }
    
    /**
     * Obtener logs de auditoría (solo para admin)
     */
    public static function getLogs($filters = []) {
        if (!file_exists(self::$logFile)) {
            return [];
        }
        
        $logs = [];
        $handle = fopen(self::$logFile, 'r');
        
        while (($line = fgets($handle)) !== false) {
            $entry = json_decode(trim($line), true);
            
            if ($entry) {
                // Aplicar filtros
                if (!self::applyFilters($entry, $filters)) {
                    continue;
                }
                $logs[] = $entry;
            }
        }
        
        fclose($handle);
        
        // Retornar logs en orden inverso (más recientes primero)
        return array_reverse($logs);
    }
    
    /**
     * Aplicar filtros a los logs
     */
    private static function applyFilters($entry, $filters) {
        foreach ($filters as $key => $value) {
            if (!isset($entry[$key]) || $entry[$key] !== $value) {
                return false;
            }
        }
        return true;
    }
}
