<?php
namespace App\Controllers;

use App\Services\UsuarioService;
use App\Security\RateLimiter;
use App\Security\AuditLogger;
use App\Security\Authorization;

class UsuarioController {
    private $usuarioService;
    
    public function __construct($usuarioService = null) {
        $this->usuarioService = $usuarioService ?? new UsuarioService();
    }

    // Metodo UsuarioController:login
    public function login() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception("Método no permitido");
            }
            
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            
            // Rate limiting para login (5 intentos por 10 minutos)
            try {
                RateLimiter::checkLimit($ip . '_login', RateLimiter::LOGIN_LIMIT, RateLimiter::LOGIN_WINDOW);
            } catch (\Exception $e) {
                AuditLogger::log(AuditLogger::ACTION_FAILED_LOGIN, 'Usuario', [
                    'reason' => 'Rate limit excedido'
                ]);
                throw $e;
            }
            
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            
            $nombreUsuario = $data['nombreUsuario'] ?? '';
            $password = $data['password'] ?? '';
            
            $usuario = $this->usuarioService->login($nombreUsuario, $password);
            
            $_SESSION['authenticated'] = true;
            $_SESSION['usuarioID'] = $usuario->getUsuarioID();
            $_SESSION['nombreUsuario'] = $usuario->getNombreUsuario();
            $_SESSION['nombreCargo'] = $usuario->getCargo()->getNombreCargo();
            $_SESSION['nombreArea'] = $usuario->getArea()->getNombreArea();
            $_SESSION['cargoID'] = $usuario->getCargo()->getCargoID();
            $_SESSION['userRole'] = $this->mapCargoToRole($usuario->getCargo()->getCargoID());
            $_SESSION['requireCUI'] = true;
            $_SESSION['login_time'] = time();
            $_SESSION['ip'] = $ip;
            
            // Reset rate limiting para esta IP
            RateLimiter::reset($ip . '_login');
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Login exitoso',
                'data' => [
                    'usuarioID' => $usuario->getUsuarioID(),
                    'nombreUsuario' => $usuario->getNombreUsuario(),
                    'nombreCompleto' => $usuario->getNombreCompleto(),
                    'cargo' => $usuario->getCargo()->toArray(),
                    'area' => $usuario->getArea()->toArray(),
                    'requireCUI' => true
                ]
            ]);
            
        } catch (\Exception $e) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            // Registrar intento fallido
            RateLimiter::recordAttempt($ip . '_login', RateLimiter::LOGIN_WINDOW);
            
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    // Metodo UsuarioController:validarCUI
    public function validarCUI() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception("Método no permitido");
            }
            
            if (!isset($_SESSION['usuarioID'])) {
                throw new \Exception("Sesión no iniciada");
            }
            
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            
            $usuarioID = $_SESSION['usuarioID'];
            $cui = $data['cui'] ?? '';
            $usuario = $this->usuarioService->validarCUI($usuarioID, $cui);
        
            // Actualizar sesión
            $_SESSION['authenticated'] = true;
            $_SESSION['requireCUI'] = false;
            $_SESSION['usuario'] = $usuario->toArray();
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'CUI validado correctamente',
                'data' => $usuario->toArray()
            ]);
            
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    // Metodo UsuarioController:logout()
    public function logout() {
        try {
            $usuarioID = $_SESSION['usuarioID'] ?? null;
            
            // Registrar logout
            if ($usuarioID) {
                AuditLogger::logLogout($usuarioID);
            }
            
            // Destruir sesión de forma segura
            $_SESSION = [];
            
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            
            session_destroy();
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Sesión cerrada correctamente'
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar todos los usuarios
     */
    public function listar() {
        try {
            $usuarios = $this->usuarioService->listarTodos();
            $this->jsonResponse(['success' => true, 'data' => $usuarios]);
        } catch (\Exception $e) {
            error_log("Error en listar usuarios: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al obtener usuarios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un usuario específico
     */
    public function obtener($usuarioID) {
        try {
            $usuario = $this->usuarioService->obtenerPorID($usuarioID);
            
            if (!$usuario) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
                return;
            }
            
            $this->jsonResponse(['success' => true, 'data' => $usuario]);
        } catch (\Exception $e) {
            error_log("Error en obtener usuario: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al obtener usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nuevo usuario
     */
    public function crear() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validaciones
            $errores = $this->validarDatosUsuario($data, false);
            if (!empty($errores)) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => implode(', ', $errores)
                ], 400);
                return;
            }
            
            // Verificar si el usuario ya existe
            if ($this->usuarioService->existeNombreUsuario($data['nombreUsuario'])) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'El nombre de usuario ya está en uso'
                ], 400);
                return;
            }
            
            // Verificar si el DNI ya existe
            if ($this->usuarioService->existeDNI($data['dni'])) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'El DNI ya está registrado'
                ], 400);
                return;
            }
            
            $usuarioID = $this->usuarioService->crear($data);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Usuario creado exitosamente',
                'data' => ['usuarioID' => $usuarioID]
            ]);
        } catch (\Exception $e) {
            error_log("Error en crear usuario: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al crear usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar usuario
     */
    public function actualizar($usuarioID) {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $data['usuarioID'] = $usuarioID;
            
            // Validaciones (sin requerir password)
            $errores = $this->validarDatosUsuario($data, true);
            if (!empty($errores)) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => implode(', ', $errores)
                ], 400);
                return;
            }
            
            // Verificar si el usuario existe
            if (!$this->usuarioService->obtenerPorID($usuarioID)) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
                return;
            }
            
            // Verificar nombre de usuario único (excepto el actual)
            if ($this->usuarioService->existeNombreUsuarioExcepto($data['nombreUsuario'], $usuarioID)) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'El nombre de usuario ya está en uso'
                ], 400);
                return;
            }
            
            $this->usuarioService->actualizar($usuarioID, $data);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente'
            ]);
        } catch (\Exception $e) {
            error_log("Error en actualizar usuario: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al actualizar usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar usuario
     */
    public function eliminar($usuarioID) {
        try {
            // Verificar si el usuario existe
            if (!$this->usuarioService->obtenerPorID($usuarioID)) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
                return;
            }
            
            // No permitir eliminar el usuario actual
            if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] == $usuarioID) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'No puede eliminar su propio usuario'
                ], 400);
                return;
            }
            
            $this->usuarioService->eliminar($usuarioID);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            error_log("Error en eliminar usuario: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al eliminar usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar contraseña
     */
    public function cambiarPassword($usuarioID) {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['password']) || empty($data['password'])) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'La contraseña es requerida'
                ], 400);
                return;
            }
            
            if (strlen($data['password']) < 8) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'La contraseña debe tener al menos 8 caracteres'
                ], 400);
                return;
            }
            
            // Verificar si el usuario existe
            if (!$this->usuarioService->obtenerPorID($usuarioID)) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
                return;
            }
            
            $this->usuarioService->cambiarPassword($usuarioID, $data['password']);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Contraseña actualizada exitosamente'
            ]);
        } catch (\Exception $e) {
            error_log("Error en cambiar password: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al cambiar contraseña: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Filtrar usuarios
     */
    public function filtrar() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $usuarios = $this->usuarioService->filtrar($data);
            $this->jsonResponse(['success' => true, 'data' => $usuarios]);
        } catch (\Exception $e) {
            error_log("Error en filtrar usuarios: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al filtrar usuarios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validar datos de usuario
     */
    private function validarDatosUsuario($data, $esActualizacion = false) {
        $errores = [];
        
        // Validar nombre de usuario
        if (empty($data['nombreUsuario'])) {
            $errores[] = 'El nombre de usuario es requerido';
        } elseif (!preg_match('/^[a-z0-9]+$/', $data['nombreUsuario'])) {
            $errores[] = 'El nombre de usuario solo puede contener letras minúsculas y números';
        }
        
        // Validar contraseña (solo requerida en creación)
        if (!$esActualizacion) {
            if (empty($data['password'])) {
                $errores[] = 'La contraseña es requerida';
            } elseif (strlen($data['password']) < 8) {
                $errores[] = 'La contraseña debe tener al menos 8 caracteres';
            }
        }
        
        // Validar nombres
        if (empty($data['nombres'])) {
            $errores[] = 'Los nombres son requeridos';
        }
        if (empty($data['apellidoPaterno'])) {
            $errores[] = 'El apellido paterno es requerido';
        }
        if (empty($data['apellidoMaterno'])) {
            $errores[] = 'El apellido materno es requerido';
        }
        
        // Validar DNI
        if (empty($data['dni'])) {
            $errores[] = 'El DNI es requerido';
        } elseif (!preg_match('/^\d{9}$/', $data['dni'])) {
            $errores[] = 'El DNI debe tener 9 dígitos';
        }
        
        // Validar cargo
        if (empty($data['cargo'])) {
            $errores[] = 'El cargo es requerido';
        }
        
        // Validar área
        if (empty($data['areaID'])) {
            $errores[] = 'El área es requerida';
        }
        
        return $errores;
    }



    // Metodo para formatear la respuesta en json
    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Mapear CargoID a role
     */
    private function mapCargoToRole($cargoID) {
        $roleMap = [
            1 => Authorization::ROLE_ADMIN,              // Gerente RRHH
            2 => Authorization::ROLE_COORDINATOR,       // Gerente Área
            3 => Authorization::ROLE_SUPERVISOR,        // Usuario Área
            4 => Authorization::ROLE_ADMIN              // Gerente Sistemas
        ];
        
        return $roleMap[$cargoID] ?? Authorization::ROLE_GUEST;
    }

}

