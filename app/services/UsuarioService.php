<?php
namespace App\Services;

use App\Repositories\UsuarioRepository;
use App\Security\PasswordUtil;
use App\Security\InputValidator;

class UsuarioService extends BaseService {
    
    // Constantes para los cargos
    private const CARGOS = [
        'gerente_rrhh' => 1,
        'gerente_area' => 2,
        'usuario_area' => 3,
        'gerente_sistemas' => 4
    ];
    
    // Campos permitidos para actualización
    private const UPDATE_ALLOWED_FIELDS = [
        'nombreUsuario', 'nombres', 'apellidoPaterno', 'apellidoMaterno',
        'dni', 'cargo', 'areaID', 'activo', 'password'
    ];
    
    public function __construct() {
        $this->repository = new UsuarioRepository();
    }
    
    /**
     * Login de usuario
     */
    public function login($nombreUsuario, $password) {
        // Validar inputs
        InputValidator::validateString($nombreUsuario, 1, 50);
        
        if (empty($password)) {
            throw new \Exception("Credenciales incorrectas");
        }
        
        // Buscar usuario
        $row = $this->repository->buscarPorNombreUsuario($nombreUsuario);
        
        if (!$row || empty($row['Password'])) {
            throw new \Exception("Credenciales incorrectas");
        }
        
        // Verificar contraseña
        if (!PasswordUtil::verify($password, $row['Password'])) {
            throw new \Exception("Credenciales incorrectas");
        }
        
        // Rehash si es necesario
        if (PasswordUtil::needsRehash($row['Password'])) {
            $nuevoHash = PasswordUtil::hash($password);
            $this->repository->cambiarPassword($row['UsuarioID'], $nuevoHash);
        }
        
        return $this->repository->mapToUsuario($row);
    }
    
    /**
     * Validar CUI
     */
    public function validarCUI($usuarioID, $cui) {
        $this->validateId($usuarioID, 'Usuario ID');
        
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
     * Listar todos los usuarios
     */
    public function listarTodos() {
        return $this->executeOperation(
            fn() => $this->repository->listarTodos(),
            'Error al listar usuarios'
        );
    }

    /**
     * Obtener usuario por ID
     */
    public function obtenerPorID($usuarioID) {
        $this->validateId($usuarioID, 'Usuario ID');
        
        return $this->executeOperation(
            fn() => $this->repository->obtenerPorID($usuarioID),
            'Error al obtener usuario'
        );
    }

    /**
     * Crear nuevo usuario
     */
    public function crear($data) {
        // Validar campos requeridos
        $this->validateRequiredFields($data, [
            'nombreUsuario', 'nombres', 'apellidoPaterno', 'apellidoMaterno',
            'dni', 'cargo', 'areaID'
        ]);

        // Validaciones movidas al servicio, pero verificaciones de duplicados aquí
        if ($this->repository->existeNombreUsuario($data['nombreUsuario'])) {
            throw new \Exception('El nombre de usuario ya está en uso');
        }
        
        if ($this->repository->existeDNI($data['dni'])) {
            throw new \Exception('El DNI ya está registrado');
        }
        
        // Validar formato de datos
        $this->validateUserData($data, false);
        
        // Procesar datos
        $data = $this->prepareUserData($data);
        
        return $this->executeOperation(
            fn() => $this->repository->crear($data),
            'Error al crear usuario'
        );
    }

    /**
     * Actualizar usuario
     */
    public function actualizar($usuarioID, $data) {
        $this->validateId($usuarioID, 'Usuario ID');

        // Verificar existencia
        if (!$this->repository->obtenerPorID($usuarioID)) {
            throw new \Exception('Usuario no encontrado');
        }
        
        // Verificar nombre de usuario único (excepto el actual)
        if (isset($data['nombreUsuario']) && 
            $this->repository->existeNombreUsuarioExcepto($data['nombreUsuario'], $usuarioID)) {
            throw new \Exception('El nombre de usuario ya está en uso');
        }
        
        // Validar formato de datos si se están actualizando
        $this->validateUserData($data, true);
        
        // Preparar datos
        $updateData = $this->prepareUpdateData($data, self::UPDATE_ALLOWED_FIELDS);
        $updateData = $this->prepareUserData($updateData, false);
        
        return $this->executeOperation(
            fn() => $this->repository->actualizar($usuarioID, $updateData),
            'Error al actualizar usuario'
        );
    }

    /**
     * Eliminar usuario
     */
    public function eliminar($usuarioID) {
        $this->validateId($usuarioID, 'Usuario ID');
        
        $this->validateExists($usuarioID, 'Usuario no encontrado');
        
        return $this->executeOperation(
            fn() => $this->repository->delete($usuarioID),
            'Error al eliminar usuario'
        );
    }

    /**
     * Cambiar contraseña
     */
    public function cambiarPassword($usuarioID, $nuevaPassword) {
        $this->validateId($usuarioID, 'Usuario ID');
        
        // Validar fortaleza de la contraseña
        $validation = PasswordUtil::validateStrength($nuevaPassword);
        if (!$validation['valid']) {
            throw new \Exception("Contraseña débil: " . implode(", ", $validation['errors']));
        }
        
        $hash = PasswordUtil::hash($nuevaPassword);
        
        return $this->executeOperation(
            fn() => $this->repository->cambiarPassword($usuarioID, $hash),
            'Error al cambiar contraseña'
        );
    }

    /**
     * Filtrar usuarios
     */
    public function filtrar($filtros) {
        return $this->executeOperation(
            fn() => $this->repository->filtrar($filtros),
            'Error al filtrar usuarios'
        );
    }

    /**
     * Preparar datos del usuario (lógica común para crear/actualizar)
     */
    private function prepareUserData(array $data, $isCreation = true) {
        // Procesar cargo
        if (isset($data['cargo'])) {
            $data['cargoID'] = $this->obtenerCargoID($data['cargo']);
            unset($data['cargo']);
        }
        
        // Procesar DNI y CUI
        if (isset($data['dni'])) {
            $data['cui'] = substr($data['dni'], -1);
            $data['dni'] = substr($data['dni'], 0, -1);
        }
        
        // Procesar contraseña
        if (!empty($data['password'])) {
            $validation = PasswordUtil::validateStrength($data['password']);
            if (!$validation['valid']) {
                throw new \Exception("Contraseña débil: " . implode(", ", $validation['errors']));
            }
            $data['password'] = PasswordUtil::hash($data['password']);
        } elseif ($isCreation) {
            // En creación, si no hay contraseña, usar una por defecto
            $data['password'] = PasswordUtil::hash('12345678');
        }
        
        // Agregar fecha de registro si es creación
        if ($isCreation && !isset($data['fechaRegistro'])) {
            $data['fechaRegistro'] = date('Y-m-d H:i:s');
        }
        
        // Establecer activo por defecto
        if ($isCreation && !isset($data['activo'])) {
            $data['activo'] = 1;
        }
        
        return $data;
    }

    /**
     * Obtener CargoID según el nombre
     */
    private function obtenerCargoID($cargo) {
        if (!isset(self::CARGOS[$cargo])) {
            throw new \Exception("Cargo inválido: $cargo");
        }
        
        return self::CARGOS[$cargo];
    }
    
    /**
     * Validar datos del usuario según reglas de negocio
     */
    private function validateUserData(array $data, $isUpdate = false) {
        // Validar nombre de usuario
        if (isset($data['nombreUsuario'])) {
            InputValidator::validateString($data['nombreUsuario'], 1, 50);
            
            if (!preg_match('/^[a-z0-9]+$/', $data['nombreUsuario'])) {
                throw new \Exception('El nombre de usuario solo puede contener letras minúsculas y números');
            }
        }
        
        // Validar contraseña (solo requerida en creación)
        if (!$isUpdate && isset($data['password'])) {
            if (strlen($data['password']) < 8) {
                throw new \Exception('La contraseña debe tener al menos 8 caracteres');
            }
        }
        
        // Validar nombres y apellidos
        $camposTexto = ['nombres', 'apellidoPaterno', 'apellidoMaterno'];
        foreach ($camposTexto as $campo) {
            if (isset($data[$campo]) && empty(trim($data[$campo]))) {
                $label = ucfirst(str_replace('_', ' ', $campo));
                throw new \Exception("$label es requerido");
            }
        }
        
        // Validar DNI
        if (isset($data['dni'])) {
            InputValidator::validateDNI($data['dni']);
            
            if (!preg_match('/^\d{9}$/', $data['dni'])) {
                throw new \Exception('El DNI debe tener 9 dígitos');
            }
        }
        
        // Validar cargo
        if (isset($data['cargo']) && empty($data['cargo'])) {
            throw new \Exception('El cargo es requerido');
        }
        
        // Validar área
        if (isset($data['areaID']) && empty($data['areaID'])) {
            throw new \Exception('El área es requerida');
        }
    }
}