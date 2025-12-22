<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\UsuarioService;
use App\Security\RateLimiter;
use App\Security\Authorization;
use Exception;

class UsuarioController extends BaseController {
    private $service;

    public function __construct($service = null) {
        $this->service = $service ?? new UsuarioService();
    }

    // Metodo UsuarioController:login
    public function login() {
        try {
            $this->validateMethod('POST');
            
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            
            // Rate limiting para login (5 intentos por 10 minutos)
            try {
                RateLimiter::checkLimit($ip . '_login', RateLimiter::LOGIN_LIMIT, RateLimiter::LOGIN_WINDOW);
            } catch (Exception $e) {
                $this->handleException($e, 'Demasiados intentos de login. Por favor, intente nuevamente más tarde.');
            }
            
            $request = $this->getJsonInput();
            
            $nombreUsuario = $request['nombreUsuario'] ?? '';
            $password = $request['password'] ?? '';
            
            $response = $this->service->login($nombreUsuario, $password);
            error_log("Este es response" . print_r($response, true));
            
            // Iniciar sesión de forma segura
            $_SESSION['authenticated'] = true;
            $_SESSION['usuarioID'] = $response->getUsuarioID();
            $_SESSION['nombreUsuario'] = $response->getNombreUsuario();
            $_SESSION['nombreCargo'] = $response->getCargo()->getNombreCargo();
            $_SESSION['nombreArea'] = $response->getArea()->getNombreArea();
            $_SESSION['cargoID'] = $response->getCargo()->getCargoID();
            $_SESSION['userRole'] = $this->mapCargoToRole($response->getCargo()->getCargoID());
            $_SESSION['requireCUI'] = true;
            $_SESSION['login_time'] = time();
            $_SESSION['ip'] = $ip;
            
            // Reset rate limiting para esta IP
            RateLimiter::reset($ip . '_login');
            
            
            $this->successResponse('Login exitoso', [
                'usuarioID' => $response->getUsuarioID(),
                'nombreUsuario' => $response->getNombreUsuario(),
                'nombreCompleto' => $response->getNombreCompleto(),
                'cargo' => $response->getCargo()->toArray(),
                'area' => $response->getArea()->toArray(),
                'requireCUI' => true
            ]);
            
        } catch (Exception $e) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            // Registrar intento fallido
            RateLimiter::recordAttempt($ip . '_login', RateLimiter::LOGIN_WINDOW);
            
            $this->handleException($e, 'Error en el proceso de login');
        }
    }

    // Metodo UsuarioController:validarCUI
    public function validarCUI() {
        try {
            $this->validateMethod('POST');
            
            if (!isset($_SESSION['usuarioID'])) {
                throw new Exception("Sesión no iniciada");
            }

            $request = $this->getJsonInput();
            
            $usuarioID = $_SESSION['usuarioID'];
            $cui = $request['cui'] ?? '';
            $response = $this->service->validarCUI($usuarioID, $cui);
        
            // Actualizar sesión
            $_SESSION['authenticated'] = true;
            $_SESSION['requireCUI'] = false;
            $_SESSION['usuario'] = $response->toArray();
            

            $this->successResponse('CUI validado correctamente', [
                'usuario' => $response->toArray()
            ]);
            
        } catch (Exception $e) {
            $this->errorResponse('Error al validar CUI: ' . $e->getMessage(), 400);
        }
    }

    // Metodo UsuarioController:logout()
    public function logout() {
        try {

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
            
            $this->successResponse('Logout exitoso');
        } catch (Exception $e) {
            $this->handleException($e, 'Error en el proceso de logout');
        }
    }

    //Listar todos los usuarios
    public function listar() {
        try {
            $response = $this->service->listarTodos();
            $this->successResponse('Usuarios obtenidos exitosamente', ['usuarios' => $response]);
        } catch (Exception $e) {

            $this->handleException($e, 'Error al listar usuarios');
        }
    }

    // Obtener un usuario específico
    public function obtener($usuarioID) {
        try {
            $usuario = $this->service->obtenerPorID($usuarioID);
            
            if (!$usuario) {
                $this->errorResponse('Usuario no encontrado', 404);
                return;
            }

            $this->successResponse('Usuario obtenido exitosamente', ['usuario' => $usuario]);
        } catch (Exception $e) {

            $this->handleException($e, 'Error al obtener usuario');
        }
    }

    // Crear nuevo usuario
    public function crear() {
        try {
            $request = $this->getJsonInput();
            
            // Validaciones
            $errores = $this->validarDatosUsuario($request, false);
            if (!empty($errores)) {
                $this->errorResponse(implode(', ', $errores), 400);
                return;
            }
            
            // Verificar si el usuario ya existe
            if ($this->service->existeNombreUsuario($request['nombreUsuario'])) {
                $this->errorResponse('El nombre de usuario ya está en uso', 400);
                return;
            }
            
            // Verificar si el DNI ya existe
            if ($this->service->existeDNI($request['dni'])) {
                $this->errorResponse('El DNI ya está registrado', 400);
                return;
            }

            $usuarioID = $this->service->crear($request);
            
            $this->successResponse('Usuario creado exitosamente', ['usuarioID' => $usuarioID]);
        } catch (Exception $e) {

            $this->handleException($e, 'Error al crear usuario');
        }
    }

    /**
     * Actualizar usuario
     */
    public function actualizar($usuarioID) {
        try {
            $request = $this->getJsonInput();
            $request['usuarioID'] = $usuarioID;
            
            // Validaciones (sin requerir password)
            $errores = $this->validarDatosUsuario($request, true);
            if (!empty($errores)) {
                $this->errorResponse(implode(', ', $errores), 400);
                return;
            }
            
            // Verificar si el usuario existe
            if (!$this->service->obtenerPorID($usuarioID)) {
                $this->errorResponse('Usuario no encontrado', 404);
                return;
            }
            
            // Verificar nombre de usuario único (excepto el actual)
            if ($this->service->existeNombreUsuarioExcepto($request['nombreUsuario'], $usuarioID)) {
                $this->errorResponse('El nombre de usuario ya está en uso', 400);
                return;
            }
            
            $this->service->actualizar($usuarioID, $request);
            
            $this->successResponse('Usuario actualizado exitosamente');
        } catch (Exception $e) {

            $this->handleException($e, 'Error al actualizar usuario');
        }
    }


    // Eliminar usuario
    public function eliminar($usuarioID) {
        try {
            // Verificar si el usuario existe
            if (!$this->service->obtenerPorID($usuarioID)) {
                $this->errorResponse('Usuario no encontrado', 404);
                return;
            }
            
            // No permitir eliminar el usuario actual
            if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] == $usuarioID) {
                $this->errorResponse('No puede eliminar su propio usuario', 400);
                return;
            }

            $this->service->eliminar($usuarioID);
            
            $this->successResponse('Usuario eliminado exitosamente');
        } catch (Exception $e) {

            $this->handleException($e, 'Error al eliminar usuario');
        }
    }

    // Cambiar contraseña
    public function cambiarPassword($usuarioID) {
        try {
            $request = $this->getJsonInput();
            
            if (!isset($request['password']) || empty($request['password'])) {
                $this->errorResponse('La nueva contraseña es requerida', 400);
                return;
            }

            if (strlen($request['password']) < 8) {
                $this->errorResponse('La contraseña debe tener al menos 8 caracteres', 400);
                return;
            }
            
            // Verificar si el usuario existe
            if (!$this->service->obtenerPorID($usuarioID)) {
                $this->errorResponse('Usuario no encontrado', 404);
                return;
            }
            
            $this->service->cambiarPassword($usuarioID, $request['password']);

            $this->successResponse('Contraseña actualizada exitosamente');
        } catch (Exception $e) {

            $this->handleException($e, 'Error al cambiar contraseña');
        }
    }

    /**
     * Filtrar usuarios
     */
    public function filtrar() {
        try {
            $request = $this->getJsonInput();
            $usuarios = $this->service->filtrar($request);
            $this->successResponse('Usuarios filtrados exitosamente', ['usuarios' => $usuarios]);
        } catch (Exception $e) {

            $this->handleException($e, 'Error al filtrar usuarios');
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

