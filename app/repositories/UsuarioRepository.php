<?php
namespace App\Repositories;

use App\Models\Usuario;
use App\Models\Cargo;
use App\Models\Area;
use PDO;
use PDOException;

class UsuarioRepository extends BaseRepository {
    
    protected $table = 'Usuario';
    protected $primaryKey = 'UsuarioID';
    
    /**
     * Buscar usuario por nombre de usuario con joins
     */
    public function buscarPorNombreUsuario($nombreUsuario) {
        try {
            $query = "SELECT 
                        u.UsuarioID,
                        u.NombreUsuario,
                        u.Nombres,
                        u.ApellidoPaterno,
                        u.ApellidoMaterno,
                        u.Password,
                        u.EstadoID,
                        c.CargoID,
                        c.NombreCargo,
                        a.AreaID,
                        a.NombreArea
                      FROM Usuario u
                      LEFT JOIN Cargo c ON u.CargoID = c.CargoID
                      LEFT JOIN Area a ON u.AreaID = a.AreaID
                      WHERE u.NombreUsuario = :nombreUsuario";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':nombreUsuario', $nombreUsuario, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \Exception($this->cleanErrorMessage($e));
        }
    }
    
    /**
     * Validar CUI usando stored procedure
     */
    public function validarCUI($usuarioID, $cui) {
        try {
            $resultado = $this->executeSP('sp_ValidarCUI', [
                'UsuarioID' => $usuarioID,
                'CUI' => $cui
            ]);
            
            if (!$resultado) {
                throw new \Exception("CUI inválido");
            }
            
            return $this->mapToUsuario($resultado);
            
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    /**
     * Listar todos los usuarios con información completa
     */
    public function listarTodos() {
        try {
            $query = "SELECT 
                        u.UsuarioID,
                        u.NombreUsuario,
                        u.Nombres,
                        u.ApellidoPaterno,
                        u.ApellidoMaterno,
                        u.DNI,
                        u.CUI,
                        u.Activo,
                        u.FechaRegistro,
                        c.NombreCargo,
                        c.CargoID,
                        a.NombreArea,
                        a.AreaID
                      FROM Usuario u
                      LEFT JOIN Cargo c ON u.CargoID = c.CargoID
                      LEFT JOIN Area a ON u.AreaID = a.AreaID
                      ORDER BY u.UsuarioID ASC";
            
            $stmt = $this->db->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en listarTodos: " . $e->getMessage());
            throw new \Exception("Error al listar usuarios");
        }
    }
    
    /**
     * Obtener usuario por ID con joins
     */
    public function obtenerPorID($usuarioID) {
        try {
            $query = "SELECT 
                        u.UsuarioID,
                        u.NombreUsuario,
                        u.Nombres,
                        u.ApellidoPaterno,
                        u.ApellidoMaterno,
                        u.DNI,
                        u.CUI,
                        u.Activo,
                        u.FechaRegistro,
                        c.NombreCargo,
                        c.CargoID,
                        a.NombreArea,
                        a.AreaID
                      FROM Usuario u
                      LEFT JOIN Cargo c ON u.CargoID = c.CargoID
                      LEFT JOIN Area a ON u.AreaID = a.AreaID
                      WHERE u.UsuarioID = :usuarioID";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':usuarioID', $usuarioID, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerPorID: " . $e->getMessage());
            throw new \Exception("Error al obtener usuario");
        }
    }
    
    /**
     * Crear usuario usando stored procedure
     */
    public function crear($data) {
        error_log("Creando usuario con datos: " . print_r($data, true));
        
        return $this->executeSP('sp_RegistrarUsuario', [
            'nombreUsuario' => $data['nombreUsuario'],
            'nombres' => $data['nombres'],
            'apellidoPaterno' => $data['apellidoPaterno'],
            'apellidoMaterno' => $data['apellidoMaterno'],
            'password' => $data['password'],
            'dni' => $data['dni'],
            'cui' => $data['cui'],
            'cargoID' => $data['cargoID'],
            'areaID' => $data['areaID'],
            'estadoID' => '1',
            'activo' => '1',
            'fechaRegistro' => $data['fechaRegistro']
        ]);
    }
    
    /**
     * Actualizar usuario usando stored procedure
     */
    public function actualizar($usuarioID, $data) {
        return $this->executeSP('sp_ActualizarUsuario', [
            'usuarioID' => $usuarioID,
            'nombreUsuario' => $data['nombreUsuario'] ?? null,
            'nombres' => $data['nombres'] ?? null,
            'apellidoPaterno' => $data['apellidoPaterno'] ?? null,
            'apellidoMaterno' => $data['apellidoMaterno'] ?? null,
            'password' => $data['password'] ?? null,
            'dni' => $data['dni'] ?? null,
            'cui' => $data['cui'] ?? null,
            'cargoID' => $data['cargoID'] ?? null,
            'areaID' => $data['areaID'] ?? null,
            'activo' => $data['activo'] ?? null
        ], 'none');
    }
    
    /**
     * Cambiar contraseña
     */
    public function cambiarPassword($usuarioID, $passwordHash) {
        try {
            $query = "UPDATE Usuario SET Password = :password WHERE UsuarioID = :usuarioID";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':password', $passwordHash, PDO::PARAM_STR);
            $stmt->bindParam(':usuarioID', $usuarioID, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error en cambiarPassword: " . $e->getMessage());
            throw new \Exception("Error al cambiar contraseña");
        }
    }
    
    /**
     * Filtrar usuarios con query builder dinámico
     * MEJORADO: Usa el método buildFilteredQuery de BaseRepository
     */
    public function filtrar($filtros) {
        try {
            $baseQuery = "SELECT 
                        u.UsuarioID,
                        u.NombreUsuario,
                        u.Nombres,
                        u.ApellidoPaterno,
                        u.ApellidoMaterno,
                        u.DNI,
                        u.CUI,
                        u.Activo,
                        u.FechaRegistro,
                        c.NombreCargo,
                        a.NombreArea,
                        a.AreaID
                      FROM Usuario u
                      LEFT JOIN Cargo c ON u.CargoID = c.CargoID
                      LEFT JOIN Area a ON u.AreaID = a.AreaID
                      WHERE 1=1";
            
            $params = [];
            
            // Filtro de texto en múltiples campos
            if (!empty($filtros['texto'])) {
                $baseQuery .= " AND (u.NombreUsuario LIKE :texto 
                            OR u.Nombres LIKE :texto 
                            OR u.ApellidoPaterno LIKE :texto 
                            OR u.ApellidoMaterno LIKE :texto)";
                $params[':texto'] = '%' . $filtros['texto'] . '%';
            }
            
            // Filtro por cargo
            if (!empty($filtros['cargoID'])) {
                $baseQuery .= " AND u.CargoID = :cargoID";
                $params[':cargoID'] = $filtros['cargoID'];
            }
            
            // Filtro por área
            if (!empty($filtros['areaID'])) {
                $baseQuery .= " AND u.AreaID = :areaID";
                $params[':areaID'] = $filtros['areaID'];
            }
            
            // Filtro por estado activo
            if (isset($filtros['activo'])) {
                $baseQuery .= " AND u.Activo = :activo";
                $params[':activo'] = $filtros['activo'];
            }
            
            $baseQuery .= " ORDER BY u.UsuarioID DESC";
            
            $stmt = $this->db->prepare($baseQuery);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en filtrar: " . $e->getMessage());
            throw new \Exception("Error al filtrar usuarios");
        }
    }
    
    /**
     * Verificaciones de existencia usando método heredado
     */
    public function existeNombreUsuario($nombreUsuario) {
        return $this->exists('NombreUsuario', $nombreUsuario);
    }
    
    public function existeNombreUsuarioExcepto($nombreUsuario, $usuarioID) {
        return $this->exists('NombreUsuario', $nombreUsuario, $usuarioID);
    }
    
    public function existeDNI($dni) {
        return $this->exists('DNI', $dni);
    }
    
    public function existeDNIExcepto($dni, $usuarioID) {
        return $this->exists('DNI', $dni, $usuarioID);
    }
    
    public function existeCUI($cui) {
        return $this->exists('CUI', $cui);
    }
    
    public function existeCUIExcepto($cui, $usuarioID) {
        return $this->exists('CUI', $cui, $usuarioID);
    }
    
    /**
     * Mapear resultado a objeto Usuario
     */
    public function mapToUsuario($data) {
        $usuario = new Usuario();
        $usuario->setUsuarioID($data['UsuarioID']);
        $usuario->setNombreUsuario($data['NombreUsuario']);
        $usuario->setNombres($data['Nombres']);
        $usuario->setApellidoPaterno($data['ApellidoPaterno']);
        $usuario->setApellidoMaterno($data['ApellidoMaterno']);
        $usuario->setEstadoID($data['EstadoID']);

        if (isset($data['CargoID']) && isset($data['NombreCargo'])) {
            $cargo = new Cargo();
            $cargo->setCargoID($data['CargoID']);
            $cargo->setNombreCargo($data['NombreCargo']);
            $usuario->setCargo($cargo);
        }

        if (isset($data['AreaID']) && isset($data['NombreArea'])) {
            $area = new Area();
            $area->setAreaID($data['AreaID']);
            $area->setNombreArea($data['NombreArea']);
            $usuario->setArea($area);
        }

        return $usuario;
    }
}