<?php
namespace App\Services;

use App\Repositories\UsuarioRepository;
use App\Security\PasswordUtil;
use App\Security\InputValidator;

class UsuarioService {
    private $repository;
    
    public function __construct() {
        $this->repository = new UsuarioRepository();
    }
    
    public function login($nombreUsuario, $password) {
        // Validar inputs
        InputValidator::validateString($nombreUsuario, 1, 50);
        if (empty($password)) {
            throw new \Exception("Credenciales incorrectas");
        }
        
        $row = $this->repository->buscarPorNombreUsuario($nombreUsuario);
        if (!$row || empty($row['Password'])) {
            throw new \Exception("Credenciales incorrectas");
        }
        
        $hash = $row['Password'];
        if (!PasswordUtil::verify($password, $hash)) {
            throw new \Exception("Credenciales incorrectas");
        }
        
        // Rehash si es necesario
        if (PasswordUtil::needsRehash($hash)) {
            $nuevoHash = PasswordUtil::hash($password);
            $this->repository->cambiarPassword($row['UsuarioID'], $nuevoHash);
        }
        
        $usuario = $this->repository->mapToUsuario($row);
        
        // Registrar login exitoso
        
        return $usuario;
    }
    
    public function validarCUI($usuarioID, $cui) {
        if (empty($usuarioID) || empty($cui)) {
            throw new \Exception("Usuario y CUI son requeridos");
        }
        
        InputValidator::validateInt($usuarioID, 1);
        
        if (strlen($cui) !== 1) {
            throw new \Exception("El CUI debe ser de 1 dígito");
        }
        
        $usuario = $this->repository->validarCUI($usuarioID, $cui);
        
        if ($usuario === null) {
            throw new \Exception("CUI incorrecto");
        }
        
        return $usuario;
    }

    /**
     * Listar todos los usuarios con información completa
     */
    public function listarTodos() {
        return $this->repository->listarTodos();
    }

    /**
     * Obtener usuario por ID
     */
    public function obtenerPorID($usuarioID) {
        InputValidator::validateInt($usuarioID, 1);
        return $this->repository->obtenerPorID($usuarioID);
    }

    /**
     * Crear nuevo usuario
     */
    public function crear($data) {
        // Validar datos de entrada
        InputValidator::validateString($data['nombreUsuario'] ?? '', 1, 50);
        InputValidator::validateDNI($data['dni'] ?? '');
        
        if (!empty($data['password'])) {
            $validation = PasswordUtil::validateStrength($data['password']);
            if (!$validation['valid']) {
                throw new \Exception("Contraseña débil: " . implode(", ", $validation['errors']));
            }
        }
        
        $data['cargoID'] = $this->obtenerCargoID($data['cargo']);
        
        // Calcular CUI (último dígito del DNI)
        $data['cui'] = substr($data['dni'], -1);
        $data['dni'] = substr($data['dni'], 0, -1);
        
        if (!empty($data['password'])) {
            $data['password'] = PasswordUtil::hash($data['password']);
        }
        
        $usuarioID = $this->repository->crear($data);
        
        return $usuarioID;
    }

    /**
     * Actualizar usuario
     */
    public function actualizar($usuarioID, $data) {
        InputValidator::validateInt($usuarioID, 1);
        
        if (isset($data['nombreUsuario'])) {
            InputValidator::validateString($data['nombreUsuario'], 1, 50);
        }
        
        if (isset($data['dni'])) {
            InputValidator::validateDNI($data['dni']);
        }
        
        // Obtener CargoID según el nombre del cargo
        if (isset($data['cargo'])) {
            $data['cargoID'] = $this->obtenerCargoID($data['cargo']);
        }
        
        // Calcular CUI (último dígito del DNI)
        if (isset($data['dni'])) {
            $data['cui'] = substr($data['dni'], -1);
            $data['dni'] = substr($data['dni'], 0, -1);
        }
        
        if (!empty($data['password'])) {
            $validation = PasswordUtil::validateStrength($data['password']);
            if (!$validation['valid']) {
                throw new \Exception("Contraseña débil: " . implode(", ", $validation['errors']));
            }
            $data['password'] = PasswordUtil::hash($data['password']);
        }
        
        $result = $this->repository->actualizar($usuarioID, $data);
        
        return $result;
    }

    /**
     * Eliminar usuario
     */
    public function eliminar($usuarioID) {
        InputValidator::validateInt($usuarioID, 1);
        
        $result = $this->repository->eliminar($usuarioID);
        
        return $result;
    }

    /**
     * Cambiar contraseña
     */
    public function cambiarPassword($usuarioID, $nuevaPassword) {
        InputValidator::validateInt($usuarioID, 1);
        
        $validation = PasswordUtil::validateStrength($nuevaPassword);
        if (!$validation['valid']) {
            throw new \Exception("Contraseña débil: " . implode(", ", $validation['errors']));
        }
        
        $hash = PasswordUtil::hash($nuevaPassword);
        $result = $this->repository->cambiarPassword($usuarioID, $hash);
        
        return $result;
    }

    /**
     * Filtrar usuarios
     */
    public function filtrar($filtros) {
        return $this->repository->filtrar($filtros);
    }

    /**
     * Verificar si existe un nombre de usuario
     */
    public function existeNombreUsuario($nombreUsuario) {
        InputValidator::validateString($nombreUsuario, 1, 50);
        return $this->repository->existeNombreUsuario($nombreUsuario);
    }

    /**
     * Verificar si existe un nombre de usuario excepto el actual
     */
    public function existeNombreUsuarioExcepto($nombreUsuario, $usuarioID) {
        InputValidator::validateString($nombreUsuario, 1, 50);
        InputValidator::validateInt($usuarioID, 1);
        return $this->repository->existeNombreUsuarioExcepto($nombreUsuario, $usuarioID);
    }

    /**
     * Verificar si existe un DNI
     */
    public function existeDNI($dni) {
        InputValidator::validateDNI($dni);
        return $this->repository->existeDNI($dni);
    }

    /**
     * Obtener CargoID según el nombre
     */
    private function obtenerCargoID($cargo) {
        $cargos = [
            'gerente_rrhh' => 1,
            'gerente_area' => 2,
            'usuario_area' => 3,
            'gerente_sistemas' => 4
        ];
        
        return $cargos[$cargo] ?? null;
    }
    
}

