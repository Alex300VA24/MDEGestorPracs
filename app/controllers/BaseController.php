<?php
namespace App\Controllers;

abstract class BaseController {
    
    /**
     * Enviar respuesta JSON
     */
    protected function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Respuesta de éxito
     */
    protected function successResponse($data = null, $message = 'Operación exitosa', $statusCode = 200) {
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        $this->jsonResponse($response, $statusCode);
    }
    
    /**
     * Respuesta de error
     */
    protected function errorResponse($message, $statusCode = 400, $errors = null) {
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        $this->jsonResponse($response, $statusCode);
    }
    
    /**
     * Validar método HTTP
     */
    protected function validateMethod($expectedMethod) {
        if ($_SERVER['REQUEST_METHOD'] !== $expectedMethod) {
            throw new \Exception("Método no permitido. Se esperaba $expectedMethod");
        }
    }
    
    /**
     * Validar múltiples métodos HTTP
     */
    protected function validateMethods(array $expectedMethods) {
        if (!in_array($_SERVER['REQUEST_METHOD'], $expectedMethods)) {
            $methods = implode(', ', $expectedMethods);
            throw new \Exception("Método no permitido. Se esperaba: $methods");
        }
    }
    
    /**
     * Obtener datos JSON del request
     */
    protected function getJsonInput() {
        $json = file_get_contents('php://input');
        return json_decode($json, true) ?? [];
    }
    
    /**
     * Validar sesión activa
     */
    protected function requireAuth() {
        if (!isset($_SESSION['usuarioID']) || !isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
            throw new \Exception("Sesión no iniciada o no autenticada");
        }
    }
    
    /**
     * Obtener usuario actual de la sesión
     */
    protected function getCurrentUser() {
        $this->requireAuth();
        return [
            'usuarioID' => $_SESSION['usuarioID'],
            'nombreUsuario' => $_SESSION['nombreUsuario'] ?? null,
            'nombreCargo' => $_SESSION['nombreCargo'] ?? null,
            'nombreArea' => $_SESSION['nombreArea'] ?? null,
            'cargoID' => $_SESSION['cargoID'] ?? null,
            'userRole' => $_SESSION['userRole'] ?? null
        ];
    }
    
    /**
     * Obtener IP del cliente
     */
    protected function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
    }
    
    /**
     * Validar campos requeridos
     */
    protected function validateRequired(array $data, array $requiredFields) {
        $missing = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            throw new \Exception('Campos requeridos faltantes: ' . implode(', ', $missing));
        }
        
        return true;
    }
    
    /**
     * Sanitizar string
     */
    protected function sanitizeString($string) {
        return htmlspecialchars(strip_tags(trim($string)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validar y sanitizar array de datos
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
     * Manejo genérico de excepciones
     */
    protected function handleException(\Exception $e, $customMessage = null) {
        $message = $customMessage ?? $e->getMessage();
        $this->errorResponse($message, 500);
    }
}