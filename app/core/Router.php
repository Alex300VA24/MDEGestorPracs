<?php
namespace App\Core;

class Router {
    private $routes = [];
    private $middleware = [];
    private $groupPrefix = '';
    private $groupMiddleware = [];

    public function addMiddleware($middleware) {
        $this->middleware[] = $middleware;
    }

    public function group(array $attributes, callable $callback) {
        $previousPrefix = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        if (isset($attributes['prefix'])) {
            $this->groupPrefix = $previousPrefix . $attributes['prefix'];
        }

        if (isset($attributes['middleware'])) {
            $this->groupMiddleware = array_merge(
                $this->groupMiddleware,
                (array) $attributes['middleware']
            );
        }

        $callback($this);

        $this->groupPrefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    public function get($path, $handler, $attributes = []) {
        $this->addRoute('GET', $path, $handler, $attributes);
    }

    public function post($path, $handler, $attributes = []) {
        $this->addRoute('POST', $path, $handler, $attributes);
    }

    public function put($path, $handler, $attributes = []) {
        $this->addRoute('PUT', $path, $handler, $attributes);
    }

    public function delete($path, $handler, $attributes = []) {
        $this->addRoute('DELETE', $path, $handler, $attributes);
    }

    private function addRoute($method, $path, $handler, $attributes = []) {
        $fullPath = $this->groupPrefix . $path;
        
        $this->routes[] = [
            'method' => $method,
            'path' => $fullPath,
            'handler' => $handler,
            'middleware' => $this->groupMiddleware,
            'skip_csrf' => $attributes['skip_csrf'] ?? false  // Nuevo
        ];
    }

    public function dispatch(Request $request) {
        $method = $request->getMethod();
        $path = $request->getPath();

        // PASO 1: Buscar la ruta primero
        $matchedRoute = null;
        $params = [];
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $routeParams = $this->matchRoute($route['path'], $path);
            
            if ($routeParams !== false) {
                $matchedRoute = $route;
                $params = $routeParams;
                break;
            }
        }

        // Si no hay ruta, retornar 404 inmediatamente
        if (!$matchedRoute) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Ruta no encontrada',
                'path' => $path,
                'method' => $method
            ]);
            return;
        }

        // 🔑 PASO 2: Guardar info de ruta en request para que middleware pueda acceder
        $request->setRoute($matchedRoute);

        // 🔑 PASO 3: Ejecutar middleware global (ahora sabe qué ruta es)
        foreach ($this->middleware as $middleware) {
            if (!$middleware->handle($request)) {
                return;
            }
        }

        // 🔑 PASO 4: Ejecutar middleware de ruta
        foreach ($matchedRoute['middleware'] as $middlewareClass) {
            $middleware = new $middlewareClass();
            if (!$middleware->handle($request)) {
                return;
            }
        }

        // 🔑 PASO 5: Ejecutar handler
        $this->executeHandler($matchedRoute['handler'], $params);
    }

    private function matchRoute($routePath, $requestPath) {
        // Convertir parámetros {id} a regex
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $requestPath, $matches)) {
            array_shift($matches); // Remover match completo
            return $matches;
        }

        return false;
    }

    private function executeHandler($handler, $params = []) {
        if (is_callable($handler)) {
            call_user_func_array($handler, $params);
            return;
        }

        if (is_string($handler) && strpos($handler, '@') !== false) {
            [$controller, $method] = explode('@', $handler);
            $controllerClass = "App\\Controllers\\$controller";
            
            if (class_exists($controllerClass)) {
                $instance = new $controllerClass();
                if (method_exists($instance, $method)) {
                    call_user_func_array([$instance, $method], $params);
                    return;
                }
            }
        }

        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Handler no válido']);
    }
}