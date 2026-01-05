<?php
namespace App\Core;

class Request {
    private $method;
    private $path;
    private $query;
    private $body;
    private $headers;
    private $route;  // ✅ Nuevo

    public function __construct() {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->path = $this->parsePath();
        $this->query = $_GET;
        $this->body = $this->parseBody();
        $this->headers = $this->parseHeaders();
    }

    private function parsePath() {
        $request_uri = $_SERVER['REQUEST_URI'];
        $script_name = dirname($_SERVER['SCRIPT_NAME']);
        $path = str_replace($script_name, '', $request_uri);
        $path = parse_url($path, PHP_URL_PATH);

        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        return $path;
    }

    private function parseBody() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            return json_decode(file_get_contents('php://input'), true) ?? [];
        }

        if ($this->method === 'POST') {
            return $_POST;
        }

        if (in_array($this->method, ['PUT', 'PATCH', 'DELETE'])) {
            parse_str(file_get_contents('php://input'), $data);
            return $data;
        }

        return [];
    }

    private function parseHeaders() {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace('_', '-', substr($key, 5));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }

    // ============ GETTERS BÁSICOS ============
    
    public function getMethod() {
        return $this->method;
    }

    public function getPath() {
        return $this->path;
    }
    
    public function getUri() {
        return $_SERVER['REQUEST_URI'];
    }

    // ============ QUERY Y BODY ============
    
    public function query($key = null, $default = null) {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    public function input($key = null, $default = null) {
        if ($key === null) {
            return $this->body;
        }
        return $this->body[$key] ?? $default;
    }

    public function all() {
        return array_merge($this->query, $this->body);
    }

    public function has($key) {
        return isset($this->body[$key]) || isset($this->query[$key]);
    }

    // ============ HEADERS ============
    
    public function header($key, $default = null) {
        // Normalizar: 'X-CSRF-TOKEN' → 'X-CSRF-TOKEN'
        $normalizedKey = strtoupper(str_replace('-', '-', $key));
        
        if (isset($this->headers[$normalizedKey])) {
            return $this->headers[$normalizedKey];
        }
        
        // Fallback directo a $_SERVER
        $serverKey = 'HTTP_' . str_replace('-', '_', strtoupper($key));
        return $_SERVER[$serverKey] ?? $default;
    }

    // ============ FILES ============
    
    public function file($key) {
        return $_FILES[$key] ?? null;
    }

    public function hasFile($key) {
        return isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK;
    }

    // ============ ROUTE INFO (para middleware) ============
    
    public function setRoute($route) {
        $this->route = $route;
    }

    public function getRoute() {
        return $this->route;
    }

    public function shouldSkipCsrf() {
        return $this->route['skip_csrf'] ?? false;
    }
}