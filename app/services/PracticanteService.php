<?php
namespace App\Services;

use App\Repositories\PracticanteRepository;
use App\Models\Practicante;
use App\Security\InputValidator;

class PracticanteService extends BaseService {
    
    public function __construct() {
        $this->repository = new PracticanteRepository();
    }

    /**
     * Lista todos los practicantes
     */
    public function listarPracticantes() {
        return $this->executeOperation(
            fn() => $this->repository->listarPracticantes(),
            'Error al listar practicantes'
        );
    }

    /**
     * Obtiene un practicante por ID
     */
    public function obtenerPorId($practicanteID) {
        $this->validateId($practicanteID, 'ID del practicante');
        
        return $this->executeOperation(function() use ($practicanteID) {
            $practicante = $this->repository->obtenerPorID($practicanteID);
            
            if ($practicante === null) {
                throw new \Exception("Practicante no encontrado");
            }
            
            return $practicante;
        }, 'Error al obtener practicante');
    }

    /**
     * Registra un nuevo practicante
     */
    public function registrarPracticante($datos) {
        return $this->executeOperation(function() use ($datos) {
            // Validar DNI
            $this->validarDNI($datos['DNI'] ?? null);
            
            // Procesar nombres
            $nombresData = $this->procesarNombres($datos);
            
            // Validar nombres procesados
            if (empty($nombresData['nombres']) || empty($nombresData['apellidoPaterno'])) {
                throw new \Exception("Nombres y apellido paterno son requeridos");
            }
            
            // Validar campos adicionales si es necesario
            if (empty($datos['Carrera'])) {
                throw new \Exception("Carrera es requerida");
            }
            
            // Validar email si está presente
            if (!empty($datos['Email'])) {
                InputValidator::validateEmail($datos['Email']);
            }
            
            // Validar teléfono si está presente
            if (!empty($datos['Telefono'])) {
                $this->validarTelefono($datos['Telefono']);
            }
            
            // Crear modelo Practicante
            $practicante = $this->crearModeloPracticante($datos, $nombresData);
            
            // Procesar AreaID
            $areaID = $this->procesarAreaID($datos['AreaID'] ?? null);
            
            // Registrar en BD
            return $this->repository->registrarPracticante($practicante, $areaID);
            
        }, 'Error al registrar practicante');
    }

    /**
     * Actualiza un practicante existente
     */
    public function actualizar($id, $data) {
        $this->validateId($id, 'ID del practicante');
        
        return $this->executeOperation(function() use ($id, $data) {
            // Verificar que el practicante existe
            $existente = $this->repository->obtenerPorID($id);
            if (!$existente) {
                throw new \Exception("Practicante no encontrado");
            }
            
            // Validar datos si están presentes
            if (isset($data['DNI'])) {
                $this->validarDNI($data['DNI']);
            }
            
            if (isset($data['Email']) && !empty($data['Email'])) {
                InputValidator::validateEmail($data['Email']);
            }
            
            if (isset($data['Telefono']) && !empty($data['Telefono'])) {
                $this->validarTelefono($data['Telefono']);
            }
            
            // Actualizar
            return $this->repository->actualizar($id, $data);
            
        }, 'Error al actualizar practicante');
    }

    /**
     * Elimina un practicante
     */
    public function eliminar($id) {
        $this->validateId($id, 'ID del practicante');
        
        return $this->executeOperation(function() use ($id) {
            // Verificar existencia
            $existente = $this->repository->obtenerPorID($id);
            if (!$existente) {
                throw new \Exception("Practicante no encontrado");
            }
            
            $resultado = $this->repository->eliminar($id);
            
            if (!$resultado) {
                throw new \Exception("No se pudo eliminar el practicante");
            }
            
            return $resultado;
            
        }, 'Error al eliminar practicante');
    }

    /**
     * Filtra practicantes por nombre y/o área
     */
    public function filtrarPracticantes($nombre = null, $areaID = null) {
        return $this->executeOperation(function() use ($nombre, $areaID) {
            // Validar areaID si está presente
            if ($areaID !== null && !empty($areaID)) {
                InputValidator::validateInt($areaID, 1);
            }
            
            return $this->repository->filtrarPracticantes($nombre, $areaID);
            
        }, 'Error al filtrar practicantes');
    }

    /**
     * Acepta un practicante
     */
    public function aceptarPracticante($practicanteID, $solicitudID, $areaID, $fechaEntrada, $fechaSalida, $mensajeRespuesta) {
        return $this->executeOperation(function() use ($practicanteID, $solicitudID, $areaID, $fechaEntrada, $fechaSalida, $mensajeRespuesta) {
            // Validar IDs requeridos
            $this->validateId($practicanteID, 'ID del practicante');
            $this->validateId($solicitudID, 'ID de la solicitud');
            $this->validateId($areaID, 'ID del área');
            
            // Validar fechas
            $this->validarFechas($fechaEntrada, $fechaSalida);
            
            return $this->repository->aceptarPracticante(
                $practicanteID,
                $solicitudID,
                $areaID,
                $fechaEntrada,
                $fechaSalida,
                $mensajeRespuesta
            );
            
        }, 'Error al aceptar practicante');
    }

    /**
     * Rechaza un practicante
     */
    public function rechazarPracticante($practicanteID, $solicitudID, $mensajeRespuesta) {
        return $this->executeOperation(function() use ($practicanteID, $solicitudID, $mensajeRespuesta) {
            // Validar IDs requeridos
            $this->validateId($practicanteID, 'ID del practicante');
            $this->validateId($solicitudID, 'ID de la solicitud');
            
            return $this->repository->rechazarPracticante(
                $practicanteID,
                $solicitudID,
                $mensajeRespuesta
            );
            
        }, 'Error al rechazar practicante');
    }

    /**
     * Lista nombres de practicantes (para dropdowns)
     */
    public function listarNombresPracticantes() {
        return $this->executeOperation(
            fn() => $this->repository->listarNombresPracticantes(),
            'Error al listar nombres de practicantes'
        );
    }

    // ==================== MÉTODOS PRIVADOS DE VALIDACIÓN ====================

    /**
     * Valida DNI peruano (8 dígitos)
     */
    private function validarDNI($dni) {
        if (empty($dni)) {
            throw new \Exception("DNI es requerido");
        }
        
        if (!is_numeric($dni) || strlen($dni) != 8) {
            throw new \Exception("DNI inválido (debe tener 8 dígitos)");
        }
    }

    /**
     * Valida formato de teléfono
     */
    private function validarTelefono($telefono) {
        // Permitir números con o sin espacios/guiones
        $telefonoLimpio = preg_replace('/[\s\-]/', '', $telefono);
        
        if (!is_numeric($telefonoLimpio) || strlen($telefonoLimpio) < 7 || strlen($telefonoLimpio) > 15) {
            throw new \Exception("Teléfono inválido");
        }
    }

    /**
     * Valida que fechaSalida sea posterior a fechaEntrada
     */
    private function validarFechas($fechaEntrada, $fechaSalida) {
        if (empty($fechaEntrada) || empty($fechaSalida)) {
            throw new \Exception("Fechas de entrada y salida son requeridas");
        }
        
        $entrada = strtotime($fechaEntrada);
        $salida = strtotime($fechaSalida);
        
        if ($entrada === false || $salida === false) {
            throw new \Exception("Formato de fecha inválido");
        }
        
        if ($salida <= $entrada) {
            throw new \Exception("La fecha de salida debe ser posterior a la fecha de entrada");
        }
    }

    /**
     * Procesa nombres completos o separados
     */
    private function procesarNombres($datos) {
        $nombres = $datos['Nombres'] ?? null;
        $apellidoP = $datos['ApellidoPaterno'] ?? null;
        $apellidoM = $datos['ApellidoMaterno'] ?? null;

        // Si no hay nombres pero sí nombre completo, separar
        if (empty($nombres) && !empty($datos['NombreCompleto'])) {
            $parts = preg_split('/\s+/', trim($datos['NombreCompleto']));
            
            if (count($parts) === 1) {
                $nombres = $parts[0];
                $apellidoP = $apellidoP ?? '';
                $apellidoM = $apellidoM ?? '';
            } elseif (count($parts) === 2) {
                $nombres = $parts[0];
                $apellidoP = $parts[1];
                $apellidoM = '';
            } else {
                // Últimos 2 como apellidos, el resto como nombres
                $apellidoM = array_pop($parts);
                $apellidoP = array_pop($parts);
                $nombres = implode(' ', $parts);
            }
        }

        return [
            'nombres' => $nombres,
            'apellidoPaterno' => $apellidoP ?? '',
            'apellidoMaterno' => $apellidoM ?? ''
        ];
    }

    /**
     * Crea el modelo Practicante con los datos validados
     */
    private function crearModeloPracticante($datos, $nombresData) {
        $practicante = new Practicante();
        $practicante->setDNI($datos['DNI']);
        $practicante->setNombres($nombresData['nombres']);
        $practicante->setApellidoPaterno($nombresData['apellidoPaterno']);
        $practicante->setApellidoMaterno($nombresData['apellidoMaterno']);
        $practicante->setGenero($datos['genero'] ?? '');
        $practicante->setCarrera($datos['Carrera']);
        $practicante->setEmail($datos['Email'] ?? null);
        $practicante->setTelefono($datos['Telefono'] ?? null);
        $practicante->setDireccion($datos['Direccion'] ?? null);
        $practicante->setUniversidad($datos['Universidad'] ?? null);
        $practicante->setFechaEntrada($datos['FechaEntrada'] ?? date('Y-m-d'));
        $practicante->setFechaSalida($datos['FechaSalida'] ?? date('Y-m-d'));
        $practicante->setEstadoID($datos['EstadoID'] ?? 1);
        
        return $practicante;
    }

    /**
     * Procesa y valida el AreaID
     */
    private function procesarAreaID($areaID) {
        if ($areaID === null) {
            return null;
        }
        
        if (is_numeric($areaID)) {
            $id = (int)$areaID;
            if ($id > 0) {
                return $id;
            }
        }
        
        return null;
    }
}