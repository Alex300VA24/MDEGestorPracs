<?php
namespace App\Controllers;

abstract class BaseController {
    
    protected function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
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
    
    protected function validateMethod($expectedMethod) {
        error_log("Validating method: expected $expectedMethod, actual " . $_SERVER['REQUEST_METHOD']);
        if ($_SERVER['REQUEST_METHOD'] !== $expectedMethod) {
            throw new \Exception("Método no permitido. Se esperaba $expectedMethod");
        }
    }
    
    protected function validateMethods(array $expectedMethods) {
        if (!in_array($_SERVER['REQUEST_METHOD'], $expectedMethods)) {
            $methods = implode(', ', $expectedMethods);
            throw new \Exception("Método no permitido. Se esperaba: $methods");
        }
    }
    
    protected function getJsonInput() {
        $json = file_get_contents('php://input');
        $decoded = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE && !empty($json)) {
            throw new \Exception("JSON inválido: " . json_last_error_msg());
        }
        
        return $decoded ?? [];
    }
    
    protected function requireAuth() {
        error_log("Verificando autenticación...");
        error_log("Sesión actual: " . print_r($_SESSION['usuarioID'] ?? 'NO SET', true));
        error_log("Autenticado: " . print_r($_SESSION['authenticated'] ?? 'NO SET', true));
        
        if (!isset($_SESSION['usuarioID']) || !isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
            throw new \Exception("Sesión no iniciada o no autenticada");
        }
    }
    
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
    
    protected function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
    }
    
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
     * Valida que un ID sea válido
     */
    protected function validateId($id, $fieldName = 'ID') {
        if (empty($id) || !is_numeric($id) || $id <= 0) {
            throw new \Exception("$fieldName inválido");
        }
        return true;
    }
    
    protected function sanitizeString($string) {
        return htmlspecialchars(strip_tags(trim($string)), ENT_QUOTES, 'UTF-8');
    }
    
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
     * Wrapper genérico para ejecutar acciones del servicio
     * Reduce el código repetitivo en los controllers
     */
    protected function executeServiceAction(callable $action) {
        try {
            $result = $action();
            
            if (is_array($result) && isset($result['message'])) {
                $this->successResponse(
                    $result['data'] ?? null, 
                    $result['message'], 
                    $result['statusCode'] ?? 200
                );
            } else {
                $this->successResponse($result);
            }
        } catch (\Exception $e) {
            error_log("Error en executeServiceAction: " . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }
    
    /**
     * Valida y obtiene parámetros de paginación
     */
    protected function getPaginationParams(array $input = null) {
        $input = $input ?? $this->getJsonInput();
        
        $page = isset($input['page']) ? max(1, (int)$input['page']) : 1;
        $limit = isset($input['limit']) ? min(100, max(1, (int)$input['limit'])) : 10;
        $offset = ($page - 1) * $limit;
        
        return [
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset
        ];
    }
    
    /**
     * Destruir sesión de forma segura (puede ser usado por cualquier controller)
     */
    protected function destroySession() {
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), 
                '', 
                time() - 42000,
                $params["path"], 
                $params["domain"],
                $params["secure"], 
                $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    /**
     * Log de auditoría genérico
     */
    protected function logAction($action, $details = []) {
        try {
            $user = $this->getCurrentUser();
            $logData = [
                'timestamp' => date('Y-m-d H:i:s'),
                'usuario_id' => $user['usuarioID'],
                'action' => $action,
                'ip' => $this->getClientIP(),
                'details' => $details
            ];
            error_log("AUDIT: " . json_encode($logData));
        } catch (\Exception $e) {
            error_log("Error logging action: " . $e->getMessage());
        }
    }

    /**
     * Validar y obtener input JSON de forma segura
     */
    protected function getValidatedInput(array $requiredFields = []) {
        $input = $this->getJsonInput();
        
        if (!empty($requiredFields)) {
            $this->validateRequired($input, $requiredFields);
        }
        
        return $input;
    }
}