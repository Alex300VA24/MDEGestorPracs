<?php
namespace App\Controllers;

use App\Services\UsuarioService;
use App\Security\RateLimiter;
use App\Security\Authorization;
use Exception;

class UsuarioController extends BaseController {
    
    private $service;
    
    public function __construct($service = null) {
        $this->service = $service ?? new UsuarioService();
    }
    
    /**
     * Login de usuario - Solo valida credenciales y retorna usuarioID
     * NO autentica completamente hasta validar CUI
     */
    public function login() {
        $this->executeServiceAction(function() {
            $this->validateMethod('POST');
            
            $ip = $this->getClientIP();
            
            // Rate limiting para login
            try {
                RateLimiter::checkLimit($ip . '_login', RateLimiter::LOGIN_LIMIT, RateLimiter::LOGIN_WINDOW);
            } catch (Exception $e) {
                throw new Exception('Demasiados intentos de login. Por favor, intente nuevamente más tarde.');
            }
            
            $request = $this->getJsonInput();
            $this->validateRequired($request, ['nombreUsuario', 'password']);
            
            try {
                $response = $this->service->login($request['nombreUsuario'], $request['password']);
                
                // Reset rate limiting en login exitoso
                RateLimiter::reset($ip . '_login');
                
                // Solo retornar el usuarioID para el siguiente paso (validar CUI)
                return [
                    'message' => 'Credenciales válidas. Por favor, ingrese su CUI.',
                    'data' => [
                        'usuarioID' => $response->getUsuarioID(),
                        'requireCUI' => true
                    ]
                ];
            } catch (Exception $e) {
                // Registrar intento fallido
                RateLimiter::recordAttempt($ip . '_login', RateLimiter::LOGIN_WINDOW);
                throw $e;
            }
        });
    }
    
    /**
     * Validar CUI del usuario - AQUÍ se autentica completamente
     */
    public function validarCUI() {
        $this->executeServiceAction(function() {
            $this->validateMethod('POST');
            
            $request = $this->getJsonInput();
            error_log("El request recibido en validarCUI: " . print_r($request, true));
            $this->validateRequired($request, ['id', 'cui']);
            
            $ip = $this->getClientIP();
            
            // Validar CUI con el servicio
            $response = $this->service->validarCUI($request['id'], $request['cui']);
            // AHORA SÍ establecer la sesión completa
            $this->setUserSession($response, $ip);
            
            return [
                'message' => 'Autenticación exitosa',
                'data' => [
                    'usuarioID' => $response->getUsuarioID(),
                    'nombreUsuario' => $response->getNombreUsuario(),
                    'nombreCompleto' => $response->getNombreCompleto(),
                    'cargo' => $response->getCargo()->toArray(),
                    'area' => $response->getArea()->toArray()
                ]
            ];
        });
    }
    
    /**
     * Cerrar sesión
     */
    public function logout() {
        $this->executeServiceAction(function() {
            $this->destroySession();
            
            return [
                'message' => 'Logout exitoso'
            ];
        });
    }
    
    /**
     * Listar todos los usuarios
     */
    public function listar() {
        $this->executeServiceAction(function() {
            $this->validateMethod('GET');
            $this->requireAuth();
            
            $usuarios = $this->service->listarTodos();
            
            return [
                'message' => 'Usuarios obtenidos exitosamente',
                'data' => $usuarios
            ];
        });
    }
    
    /**
     * Obtener usuario por ID
     */
    public function obtener($usuarioID) {
        $this->executeServiceAction(function() use ($usuarioID) {
            $this->validateMethod('GET');
            $this->requireAuth();
            
            $usuario = $this->service->obtenerPorID($usuarioID);
            
            if (!$usuario) {
                throw new Exception('Usuario no encontrado');
            }
            
            return [
                'message' => 'Usuario obtenido exitosamente',
                'data' => $usuario
            ];
        });
    }
    
    /**
     * Crear nuevo usuario
     */
    public function crear() {
        $this->executeServiceAction(function() {
            $this->validateMethod('POST');
            $this->requireAuth();
            
            $request = $this->getJsonInput();
            $request = $this->sanitizeData($request);
            
            $usuarioID = $this->service->crear($request);
            
            return [
                'message' => 'Usuario creado exitosamente',
                'data' => $usuarioID,
                'statusCode' => 201
            ];
        });
    }
    
    /**
     * Actualizar usuario
     */
    public function actualizar($usuarioID) {
        $this->executeServiceAction(function() use ($usuarioID) {
            $this->validateMethod('PUT');
            $this->requireAuth();
            
            $request = $this->getJsonInput();
            $request = $this->sanitizeData($request);
            
            $this->service->actualizar($usuarioID, $request);
            
            return [
                'message' => 'Usuario actualizado exitosamente'
            ];
        });
    }
    
    /**
     * Eliminar usuario
     */
    public function eliminar($usuarioID) {
        $this->executeServiceAction(function() use ($usuarioID) {
            $this->validateMethod('DELETE');
            $this->requireAuth();
            
            // Verificar existencia
            if (!$this->service->obtenerPorID($usuarioID)) {
                throw new Exception('Usuario no encontrado');
            }
            
            // No permitir eliminar el usuario actual
            $currentUser = $this->getCurrentUser();
            if ($currentUser['usuarioID'] == $usuarioID) {
                throw new Exception('No puede eliminar su propio usuario');
            }
            
            $this->service->eliminar($usuarioID);
            
            return [
                'message' => 'Usuario eliminado exitosamente'
            ];
        });
    }
    
    /**
     * Cambiar contraseña
     */
    public function cambiarPassword($usuarioID) {
        $this->executeServiceAction(function() use ($usuarioID) {
            $this->validateMethod('PUT');
            $this->requireAuth();
            
            $request = $this->getJsonInput();
            $this->validateRequired($request, ['password']);
            
            // Verificar existencia
            if (!$this->service->obtenerPorID($usuarioID)) {
                throw new Exception('Usuario no encontrado');
            }
            
            $this->service->cambiarPassword($usuarioID, $request['password']);
            
            return [
                'message' => 'Contraseña actualizada exitosamente'
            ];
        });
    }
    
    /**
     * Filtrar usuarios
     */
    public function filtrar() {
        $this->executeServiceAction(function() {
            $this->validateMethods(['GET', 'POST']);
            $this->requireAuth();
            
            $request = $_SERVER['REQUEST_METHOD'] === 'GET' 
                ? $_GET 
                : $this->getJsonInput();
            
            $usuarios = $this->service->filtrar($request);
            
            return [
                'message' => 'Usuarios filtrados exitosamente',
                'data' => ['usuarios' => $usuarios]
            ];
        });
    }
    
    /**
     * Establecer datos de sesión del usuario (llamado SOLO después de validar CUI)
     */
    private function setUserSession($usuario, $ip) {
        $_SESSION['authenticated'] = true;
        $_SESSION['usuarioID'] = $usuario->getUsuarioID();
        $_SESSION['nombreUsuario'] = $usuario->getNombreUsuario();
        $_SESSION['nombreCargo'] = $usuario->getCargo()->getNombreCargo();
        $_SESSION['nombreArea'] = $usuario->getArea()->getNombreArea();
        $_SESSION['cargoID'] = $usuario->getCargo()->getCargoID();
        $_SESSION['userRole'] = $this->mapCargoToRole($usuario->getCargo()->getCargoID());
        $_SESSION['requireCUI'] = false; // Ya validado
        $_SESSION['login_time'] = time();
        $_SESSION['ip'] = $ip;
        $_SESSION['usuario'] = $usuario->toArray();
    }
    
    /**
     * Mapear CargoID a role
     */
    private function mapCargoToRole($cargoID) {
        $roleMap = [
            1 => Authorization::ROLE_ADMIN,         // Gerente RRHH
            2 => Authorization::ROLE_COORDINATOR,   // Gerente Área
            3 => Authorization::ROLE_SUPERVISOR,    // Usuario Área
            4 => Authorization::ROLE_ADMIN          // Gerente Sistemas
        ];
        
        return $roleMap[$cargoID] ?? Authorization::ROLE_GUEST;
    }
}