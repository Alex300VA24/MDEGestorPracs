<?php
namespace App\Services;

use App\Security\InputValidator;

abstract class BaseService {
    
    protected $repository;
    
    /**
     * Validar ID genérico
     */
    protected function validateId($id, $fieldName = 'ID') {
        if (empty($id)) {
            throw new \Exception("$fieldName es requerido");
        }
        
        if (!is_numeric($id) || $id <= 0) {
            throw new \Exception("$fieldName debe ser un número positivo");
        }
        
        InputValidator::validateInt($id, 1);
    }
    
    /**
     * Validar múltiples IDs
     */
    protected function validateIds(array $ids, $fieldName = 'IDs') {
        if (empty($ids)) {
            throw new \Exception("$fieldName son requeridos");
        }
        
        foreach ($ids as $id) {
            $this->validateId($id, $fieldName);
        }
    }
    
    /**
     * Validar campos requeridos
     */
    protected function validateRequiredFields(array $data, array $requiredFields) {
        $missing = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            throw new \Exception('Campos requeridos faltantes: ' . implode(', ', $missing));
        }
    }
    
    /**
     * Validar email
     */
    protected function validateEmail($email, $fieldName = 'Email') {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("$fieldName no tiene un formato válido");
        }
    }
    
    /**
     * Validar DNI (8 dígitos)
     */
    protected function validateDNI($dni) {
        if (!preg_match('/^\d{8}$/', $dni)) {
            throw new \Exception('DNI debe tener 8 dígitos numéricos');
        }
    }
    
    /**
     * Validar fecha
     */
    protected function validateDate($date, $format = 'Y-m-d', $fieldName = 'Fecha') {
        $d = \DateTime::createFromFormat($format, $date);
        if (!$d || $d->format($format) !== $date) {
            throw new \Exception("$fieldName no tiene un formato válido");
        }
        return $d;
    }
    
    /**
     * Validar rango de fechas
     */
    protected function validateDateRange($startDate, $endDate, $startFieldName = 'Fecha inicio', $endFieldName = 'Fecha fin') {
        $start = strtotime($startDate);
        $end = strtotime($endDate);
        
        if ($start === false || $end === false) {
            throw new \Exception("Formato de fecha inválido");
        }
        
        if ($start >= $end) {
            throw new \Exception("$startFieldName debe ser anterior a $endFieldName");
        }
    }
    
    /**
     * Validar longitud de string
     */
    protected function validateStringLength($string, $min, $max = null, $fieldName = 'Campo') {
        $length = strlen($string);
        
        if ($length < $min) {
            throw new \Exception("$fieldName debe tener al menos $min caracteres");
        }
        
        if ($max !== null && $length > $max) {
            throw new \Exception("$fieldName no debe exceder $max caracteres");
        }
    }
    
    /**
     * Validar que un valor esté en una lista permitida
     */
    protected function validateInList($value, array $allowedValues, $fieldName = 'Valor') {
        if (!in_array($value, $allowedValues, true)) {
            throw new \Exception("$fieldName debe ser uno de: " . implode(', ', $allowedValues));
        }
    }
    
    /**
     * Validar número en rango
     */
    protected function validateNumberRange($number, $min, $max, $fieldName = 'Número') {
        if ($number < $min || $number > $max) {
            throw new \Exception("$fieldName debe estar entre $min y $max");
        }
    }
    
    /**
     * Validar archivo subido (tamaño y tipo MIME)
     */
    protected function validateFile($file, $maxSize = 5242880, array $allowedMimeTypes = []) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('Error al subir el archivo');
        }
        
        if ($file['size'] > $maxSize) {
            $maxSizeMB = $maxSize / 1048576;
            throw new \Exception("El archivo excede el tamaño máximo permitido ({$maxSizeMB}MB)");
        }
        
        if (!empty($allowedMimeTypes)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime, $allowedMimeTypes, true)) {
                throw new \Exception('Tipo de archivo no permitido');
            }
        }
    }
    
    /**
     * Ejecutar operación con manejo de errores
     */
    protected function executeOperation(callable $operation, $errorMessage = 'Error en la operación') {
        try {
            return $operation();
        } catch (\Exception $e) {
            error_log("$errorMessage: " . $e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }
    
    /**
     * Validar existencia de registro
     */
    protected function validateExists($id, $errorMessage = 'Registro no encontrado') {
        $record = $this->repository->findById($id);
        
        if (!$record) {
            throw new \Exception($errorMessage);
        }
        
        return $record;
    }
    
    /**
     * Preparar datos para actualización (solo campos presentes)
     */
    protected function prepareUpdateData(array $inputData, array $allowedFields) {
        $data = [];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $inputData)) {
                $data[$field] = $inputData[$field];
            }
        }
        
        return $data;
    }
    
    /**
     * Respuesta exitosa estructurada
     */
    protected function successResult($data = null, $message = 'Operación exitosa') {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];
    }
    
    /**
     * Respuesta de error estructurada
     */
    protected function errorResult($message, $errors = null) {
        return [
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ];
    }
    
    /**
     * Formatear fecha de DateTime a string
     */
    protected function formatDateTime($datetime, $format = 'Y-m-d H:i:s') {
        if ($datetime instanceof \DateTime) {
            return $datetime->format($format);
        }
        return $datetime;
    }
    
    /**
     * Formatear array de objetos con fechas
     */
    protected function formatDateTimeArray(array $items, array $dateFields = ['FechaSolicitud', 'FechaCreacion', 'FechaSubida']) {
        return array_map(function($item) use ($dateFields) {
            foreach ($dateFields as $field) {
                if (isset($item[$field])) {
                    $item[$field] = $this->formatDateTime($item[$field]);
                }
            }
            return $item;
        }, $items);
    }
    
    /**
     * Sanitizar string (para prevenir XSS)
     */
    protected function sanitizeString($string) {
        return htmlspecialchars(strip_tags(trim($string)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitizar array de datos
     */
    protected function sanitizeData(array $data) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = $this->sanitizeString($value);
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeData($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Normalizar string (lowercase, sin espacios extras, sin acentos)
     */
    protected function normalizeString($string) {
        $string = strtolower(trim($string));
        $string = str_replace([' ', '-'], '_', $string);
        $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
        return $string;
    }
    
    /**
     * Generar slug a partir de un string
     */
    protected function generateSlug($string) {
        $string = $this->normalizeString($string);
        $string = preg_replace('/[^a-z0-9_]+/', '_', $string);
        $string = trim($string, '_');
        return $string;
    }
    
    /**
     * Crear respuesta paginada
     */
    protected function paginatedResult(array $items, $total, $page, $limit) {
        return [
            'success' => true,
            'data' => $items,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => ceil($total / $limit),
                'hasMore' => ($page * $limit) < $total
            ]
        ];
    }
    
    /**
     * Log de operación (wrapper para error_log con formato)
     */
    protected function log($message, $level = 'INFO', $context = []) {
        $contextStr = !empty($context) ? json_encode($context) : '';
        error_log("[$level] $message $contextStr");
    }
    
    /**
     * Verificar permisos (puede ser sobrescrito en servicios específicos)
     */
    protected function checkPermission($userId, $action, $resourceId = null) {
        // Implementación básica - puede ser sobrescrita
        // Por defecto, permite todas las acciones
        return true;
    }
    
    /**
     * Convertir array asociativo a objeto
     */
    protected function arrayToObject(array $data) {
        return json_decode(json_encode($data));
    }
    
    /**
     * Convertir objeto a array asociativo
     */
    protected function objectToArray($object) {
        return json_decode(json_encode($object), true);
    }
    
    /**
     * Generar token aleatorio seguro
     */
    protected function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Validar token de seguridad
     */
    protected function validateToken($token, $expectedToken) {
        return hash_equals($expectedToken, $token);
    }
}