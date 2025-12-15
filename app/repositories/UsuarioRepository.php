<?php
namespace App\Repositories;

use App\Config\Database;
use App\Models\Usuario;
use App\Models\Cargo;
use App\Models\Area;
use PDO;
use PDOException;

class UsuarioRepository {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function login($nombreUsuario, $password) {
        try {
            $sql = "EXEC sp_LoginUsuario @NombreUsuario = :nombreUsuario, @Password = :password";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':nombreUsuario', $nombreUsuario, PDO::PARAM_STR);
            $stmt->bindParam(':password', $password, PDO::PARAM_STR);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return $this->mapToUsuario($result);
            }
            
            return null;
            
        } catch (PDOException $e) {
            // Obtener mensaje exacto del RAISERROR
            $msg = $e->errorInfo[2] ?? $e->getMessage();

            // Limpiar mensajes del driver ODBC
            if (strpos($msg, ':') !== false) {
                $parts = explode(':', $msg);
                $msg = trim(end($parts)); // Última parte (que contiene tu mensaje)
            }

            throw new \Exception($msg);
        }

    }
    
    public function validarCUI($usuarioID, $cui) {
        try {
            $sql = "EXEC sp_ValidarCUI @UsuarioID = :usuarioID, @CUI = :cui";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':usuarioID', $usuarioID, PDO::PARAM_INT);
            $stmt->bindParam(':cui', $cui, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return $this->mapToUsuario($result);
            }
            
            return null;
            
        } catch (PDOException $e) {
            // Obtener mensaje exacto del RAISERROR
            $msg = $e->errorInfo[2] ?? $e->getMessage();

            // Limpiar mensajes del driver ODBC
            if (strpos($msg, ':') !== false) {
                $parts = explode(':', $msg);
                $msg = trim(end($parts)); // Última parte (que contiene tu mensaje)
            }

            throw new \Exception($msg);
        }

    }
    
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
            throw new \Exception($e->getMessage());
        }
    }
    
    public function findById($usuarioID) {
        try {
            $sql = "SELECT * FROM Usuario WHERE UsuarioID = :usuarioID";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':usuarioID', $usuarioID, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return $this->mapToUsuario($result);
            }
            
            return null;
            
        } catch (PDOException $e) {
            error_log("Error en findById: " . $e->getMessage());
            throw new \Exception("Error al buscar usuario: " . $e->getMessage());
        }
    }
    
    public function findAll() {
        try {
            $sql = "SELECT * FROM Usuario WHERE Estado = 'Activo' ORDER BY Nombres";
            $stmt = $this->db->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $usuarios = [];
            foreach ($results as $row) {
                $usuarios[] = $this->mapToUsuario($row);
            }
            
            return $usuarios;
            
        } catch (PDOException $e) {
            error_log("Error en findAll: " . $e->getMessage());
            throw new \Exception("Error al obtener usuarios: " . $e->getMessage());
        }
    }

    /**
     * Listar todos los usuarios con joins
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
     * Obtener usuario por ID
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
     * Crear usuario
     */
    public function crear($data) {

        error_log("Creando usuario con datos: " . print_r($data, true));
        try {
            $query = "EXEC sp_RegistrarUsuario 
                        :nombreUsuario,
                        :nombres,
                        :apellidoPaterno,
                        :apellidoMaterno,
                        :password,
                        :dni,
                        :cui,
                        :cargoID,
                        :areaID,
                        :estadoID,
                        :activo,
                        :fechaRegistro";

            $stmt = $this->db->prepare($query);

            $stmt->bindParam(':nombreUsuario', $data['nombreUsuario']);
            $stmt->bindParam(':nombres', $data['nombres']);
            $stmt->bindParam(':apellidoPaterno', $data['apellidoPaterno']);
            $stmt->bindParam(':apellidoMaterno', $data['apellidoMaterno']);
            $stmt->bindParam(':password', $data['password']);
            $stmt->bindParam(':dni', $data['dni']);
            $stmt->bindParam(':cui', $data['cui']);
            $stmt->bindParam(':cargoID', $data['cargoID']);
            $stmt->bindParam(':areaID', $data['areaID']);
            $stmt->bindParam(':estadoID', $data['estadoID']);
            $stmt->bindParam(':activo', $data['activo']);
            $stmt->bindParam(':fechaRegistro', $data['fechaRegistro']); // formato Y-m-d H:i:s

            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            // Obtener mensaje exacto del RAISERROR
            $msg = $e->errorInfo[2] ?? $e->getMessage();

            // Limpiar mensajes del driver ODBC
            if (strpos($msg, ':') !== false) {
                $parts = explode(':', $msg);
                $msg = trim(end($parts)); // Última parte (que contiene tu mensaje)
            }

            throw new \Exception($msg);
        }
    }


    /**
     * Actualizar usuario
     */
    public function actualizar($usuarioID, $data) {
        try {
            $query = "EXEC sp_ActualizarUsuario
                :usuarioID,
                :nombreUsuario,
                :nombres,
                :apellidoPaterno,
                :apellidoMaterno,
                :password,
                :dni,
                :cui,
                :cargoID,
                :areaID,
                :activo";

            $stmt = $this->db->prepare($query);

            // parámetros (aceptan null sin problema)
            $stmt->bindValue(':usuarioID', $usuarioID, PDO::PARAM_INT);
            $stmt->bindValue(':nombreUsuario', $data['nombreUsuario'] ?? null);
            $stmt->bindValue(':nombres', $data['nombres'] ?? null);
            $stmt->bindValue(':apellidoPaterno', $data['apellidoPaterno'] ?? null);
            $stmt->bindValue(':apellidoMaterno', $data['apellidoMaterno'] ?? null);
            $stmt->bindValue(':password', $data['password'] ?? null);
            $stmt->bindValue(':dni', $data['dni'] ?? null);
            $stmt->bindValue(':cui', $data['cui'] ?? null);
            $stmt->bindValue(':cargoID', $data['cargoID'] ?? null);
            $stmt->bindValue(':areaID', $data['areaID'] ?? null);
            $stmt->bindValue(':activo', $data['activo'] ?? null);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error en actualizar usuario: " . $e->getMessage());
            throw new \Exception("Error al actualizar usuario");
        }
    }


    /**
     * Eliminar usuario
     */
    public function eliminar($usuarioID) {
        try {
            $query = "DELETE FROM Usuario WHERE UsuarioID = :usuarioID";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':usuarioID', $usuarioID, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error en eliminar usuario: " . $e->getMessage());
            throw new \Exception("Error al eliminar usuario");
        }
    }

    /**
     * Cambiar contraseña
     */
    public function cambiarPassword($usuarioID, $passwordPlano) {
        try {
            $query = "UPDATE Usuario SET Password = :password WHERE UsuarioID = :usuarioID";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':password', $passwordPlano, PDO::PARAM_STR);
            $stmt->bindParam(':usuarioID', $usuarioID, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error en cambiarPassword: " . $e->getMessage());
            throw new \Exception("Error al cambiar contraseña");
        }
    }



    /**
     * Filtrar usuarios
     */
    public function filtrar($filtros) {
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
                        a.NombreArea,
                        a.AreaID
                      FROM Usuario u
                      LEFT JOIN Cargo c ON u.CargoID = c.CargoID
                      LEFT JOIN Area a ON u.AreaID = a.AreaID
                      WHERE 1=1";
            
            $params = [];
            
            if (!empty($filtros['texto'])) {
                $query .= " AND (u.NombreUsuario LIKE :texto 
                            OR u.Nombres LIKE :texto 
                            OR u.ApellidoPaterno LIKE :texto 
                            OR u.ApellidoMaterno LIKE :texto)";
                $params[':texto'] = '%' . $filtros['texto'] . '%';
            }
            
            if (!empty($filtros['cargoID'])) {
                $query .= " AND u.CargoID = :cargoID";
                $params[':cargoID'] = $filtros['cargoID'];
            }
            
            if (!empty($filtros['areaID'])) {
                $query .= " AND u.AreaID = :areaID";
                $params[':areaID'] = $filtros['areaID'];
            }
            
            if (isset($filtros['activo'])) {
                $query .= " AND u.Activo = :activo";
                $params[':activo'] = $filtros['activo'];
            }
            
            $query .= " ORDER BY u.UsuarioID DESC";
            
            $stmt = $this->db->prepare($query);
            
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
     * Verificar si existe nombre de usuario
     */
    public function existeNombreUsuario($nombreUsuario) {
        try {
            $query = "SELECT COUNT(*) as total FROM Usuario WHERE NombreUsuario = :nombreUsuario";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':nombreUsuario', $nombreUsuario);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] > 0;
        } catch (PDOException $e) {
            error_log("Error en existeNombreUsuario: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si existe nombre de usuario excepto el actual
     */
    public function existeNombreUsuarioExcepto($nombreUsuario, $usuarioID) {
        try {
            $query = "SELECT COUNT(*) as total FROM Usuario 
                      WHERE NombreUsuario = :nombreUsuario AND UsuarioID != :usuarioID";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':nombreUsuario', $nombreUsuario);
            $stmt->bindParam(':usuarioID', $usuarioID, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] > 0;
        } catch (PDOException $e) {
            error_log("Error en existeNombreUsuarioExcepto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si existe DNI
     */
    public function existeDNI($dni) {
        try {
            $query = "SELECT COUNT(*) as total FROM Usuario WHERE DNI = :dni";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':dni', $dni);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] > 0;
        } catch (PDOException $e) {
            error_log("Error en existeDNI: " . $e->getMessage());
            return false;
        }
    }
    
    private function mapToUsuario($data) {
        $usuario = new Usuario();
        $usuario->setUsuarioID($data['UsuarioID']);
        $usuario->setNombreUsuario($data['NombreUsuario']);
        $usuario->setNombres($data['Nombres']);
        $usuario->setApellidoPaterno($data['ApellidoPaterno']);
        $usuario->setApellidoMaterno($data['ApellidoMaterno']);
        $usuario->setEstadoID($data['EstadoID']);

        // 🔸 Crear objetos relacionados
        $cargo = new Cargo();
        $cargo->setCargoID($data['CargoID']);
        $cargo->setNombreCargo($data['NombreCargo']);
        $usuario->setCargo($cargo);

        $area = new Area();
        $area->setAreaID($data['AreaID']);
        $area->setNombreArea($data['NombreArea']);
        $usuario->setArea($area);

        return $usuario;
    }
    
    public function mapRowToUsuario($data) {
        $usuario = new Usuario();
        $usuario->setUsuarioID($data['UsuarioID'] ?? null);
        $usuario->setNombreUsuario($data['NombreUsuario'] ?? null);
        $usuario->setNombres($data['Nombres'] ?? null);
        $usuario->setApellidoPaterno($data['ApellidoPaterno'] ?? null);
        $usuario->setApellidoMaterno($data['ApellidoMaterno'] ?? null);
        $usuario->setEstadoID($data['EstadoID'] ?? null);
        
        $cargo = new Cargo();
        $cargo->setCargoID($data['CargoID'] ?? null);
        $cargo->setNombreCargo($data['NombreCargo'] ?? null);
        $usuario->setCargo($cargo);
        
        $area = new Area();
        $area->setAreaID($data['AreaID'] ?? null);
        $area->setNombreArea($data['NombreArea'] ?? null);
        $usuario->setArea($area);
        
        return $usuario;
    }

}
