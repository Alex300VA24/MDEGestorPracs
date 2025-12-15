<?php
namespace App\Security;

/**
 * Clase para validar y sanitizar entradas de usuario
 * Previene XSS, inyección SQL y otros ataques de validación
 */
class InputValidator {
    
    /**
     * Valida un email según RFC 5322
     */
    public static function validateEmail($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Email inválido");
        }
        return $email;
    }
    
    /**
     * Valida que sea un string seguro sin caracteres especiales peligrosos
     */
    public static function validateString($input, $minLength = 1, $maxLength = 255, $allowSpecial = false) {
        if (!is_string($input)) {
            throw new \Exception("El input debe ser un string");
        }
        
        if (strlen($input) < $minLength || strlen($input) > $maxLength) {
            throw new \Exception("La longitud debe estar entre $minLength y $maxLength caracteres");
        }
        
        // Remover espacios en blanco al inicio y final
        $input = trim($input);
        
        // Si no permite caracteres especiales, validar
        if (!$allowSpecial) {
            if (preg_match('/[<>"\'`%;&\\\\]/', $input)) {
                throw new \Exception("El input contiene caracteres no permitidos");
            }
        }
        
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Valida un número entero
     */
    public static function validateInt($input, $min = null, $max = null) {
        if (!is_numeric($input) || intval($input) != $input) {
            throw new \Exception("El input debe ser un número entero");
        }
        
        $value = (int)$input;
        
        if ($min !== null && $value < $min) {
            throw new \Exception("El valor debe ser mayor o igual a $min");
        }
        
        if ($max !== null && $value > $max) {
            throw new \Exception("El valor debe ser menor o igual a $max");
        }
        
        return $value;
    }
    
    /**
     * Valida un DNI peruano (8 dígitos)
     */
    public static function validateDNI($dni) {
        if (!preg_match('/^\d{9}$/', $dni)) {
            throw new \Exception("DNI y CUI deben sumar 9 dígitos");
        }
        return $dni;
    }
    
    /**
     * Valida un teléfono peruano
     */
    public static function validatePhone($phone) {
        if (!preg_match('/^(\+51)?[9]\d{8}$/', $phone)) {
            throw new \Exception("Teléfono inválido. Debe tener formato peruano");
        }
        return $phone;
    }
    
    /**
     * Valida una fecha en formato YYYY-MM-DD
     */
    public static function validateDate($date, $format = 'Y-m-d') {
        $d = \DateTime::createFromFormat($format, $date);
        if (!$d || $d->format($format) !== $date) {
            throw new \Exception("Fecha inválida. Formato: $format");
        }
        return $date;
    }
    
    /**
     * Sanitiza una cadena para usar en nombres de archivo
     */
    public static function sanitizeFilename($filename) {
        // Eliminar caracteres especiales y peligrosos
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        // Limitar la longitud
        $filename = substr($filename, 0, 255);
        if (empty($filename)) {
            throw new \Exception("Nombre de archivo inválido");
        }
        return $filename;
    }
    
    /**
     * Valida una URL
     */
    public static function validateURL($url) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \Exception("URL inválida");
        }
        return $url;
    }
    
    /**
     * Valida un array de datos contra un esquema definido
     */
    public static function validateArray($data, $schema) {
        if (!is_array($data)) {
            throw new \Exception("Los datos deben ser un array");
        }
        
        foreach ($schema as $field => $rules) {
            if ($rules['required'] && !isset($data[$field])) {
                throw new \Exception("El campo '$field' es requerido");
            }
            
            if (isset($data[$field])) {
                $value = $data[$field];
                
                if (isset($rules['type']) && !self::checkType($value, $rules['type'])) {
                    throw new \Exception("El campo '$field' tiene tipo inválido");
                }
                
                if (isset($rules['minLength']) && strlen($value) < $rules['minLength']) {
                    throw new \Exception("El campo '$field' es demasiado corto");
                }
                
                if (isset($rules['maxLength']) && strlen($value) > $rules['maxLength']) {
                    throw new \Exception("El campo '$field' es demasiado largo");
                }
                
                if (isset($rules['pattern']) && !preg_match($rules['pattern'], $value)) {
                    throw new \Exception("El campo '$field' no cumple el formato requerido");
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Verificar tipo de dato
     */
    private static function checkType($value, $type) {
        switch ($type) {
            case 'string':
                return is_string($value);
            case 'int':
                return is_int($value) || (is_numeric($value) && intval($value) == $value);
            case 'bool':
                return is_bool($value);
            case 'array':
                return is_array($value);
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL);
            default:
                return true;
        }
    }
}
