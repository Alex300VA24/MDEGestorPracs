<?php
namespace App\Repositories;

use App\Config\Database;
use App\Models\Practicante;
use PDO;
use PDOException;

class PracticanteRepository {
    private $db;

    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (\Throwable $e) {
            error_log("Error en conexión DB: " . $e->getMessage());
            throw $e;
        }
    }


    // Metodo que devuelve un array de filas asociativas con los campos de sp_ListarPracticantes

    public function listarPracticantes() {
        try {
            $sql = "EXEC sp_ListarPracticantes";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $results;
        } catch (PDOException $e) {
            error_log("PracticanteRepository::listarPracticantes - " . $e->getMessage());
            throw new \Exception("Error al listar practicantes: " . $e->getMessage());
        }
    }

    /**
     * Obtiene un practicante por ID (incluye estado y área más reciente)
     */
    public function obtenerPorID($practicanteID) {
        try {
            $sql = "EXEC sp_ObtenerPracticantePorID :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $practicanteID, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            error_log("PracticanteRepository::obtenerPorID - " . $e->getMessage());
            throw new \Exception("Error al obtener practicante: " . $e->getMessage());
        }
    }


    /**
     * Crea un practicante (usa sp_CrearPracticante). Devuelve el ID insertado.
     * Recibe un objeto Practicante y opcionalmente $areaID (int|null)
     */
    public function registrarPracticante($p, $areaID = null) {
        try {
            $stmt = $this->db->prepare("EXEC sp_RegistrarPracticante ?, ?, ?, ?, ?, ?, ?, ?, ?, ?");

            $stmt->execute([
                $p->getDNI(),
                $p->getNombres(),
                $p->getApellidoPaterno(),
                $p->getApellidoMaterno(),
                $p->getGenero(),
                $p->getCarrera(),
                $p->getEmail(),
                $p->getTelefono(),
                $p->getDireccion(),
                $p->getUniversidad(),
            ]);

            // El SP devuelve SELECT @NewID AS PracticanteID;
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $nuevoID = $row['PracticanteID'] ?? null;

            return $nuevoID;
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


    public function actualizar($id, $data) {
        error_log("Este es el id: " . $id);
        try{
            $sql = "EXEC sp_ActualizarPracticante 
            @PracticanteID = :id,
            @DNI = :DNI,
            @Nombres = :Nombres,
            @ApellidoPaterno = :ApellidoPaterno,
            @ApellidoMaterno = :ApellidoMaterno,
            @Genero = :Genero,
            @Carrera = :Carrera,
            @Email = :Email,
            @Telefono = :Telefono,
            @Direccion = :Direccion,
            @Universidad = :Universidad";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':DNI' => $data['DNI'],
            ':Nombres' => $data['Nombres'],
            ':ApellidoPaterno' => $data['ApellidoPaterno'],
            ':ApellidoMaterno' => $data['ApellidoMaterno'],
            ':Genero' => $data['genero'],
            ':Carrera' => $data['Carrera'],
            ':Email' => $data['Email'],
            ':Telefono' => $data['Telefono'],
            ':Direccion' => $data['Direccion'],
            ':Universidad' => $data['Universidad']
        ]);
        return "Practicante actualizado correctamente";
        } catch (PDOException $e) {
            error_log("Error al actualizar practicante". $e->getMessage());
        }
    }

    public function eliminar($id) {
        try{
            $stmt = $this->db->prepare("EXEC sp_EliminarPracticante @PracticanteID = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return "Practicante eliminado correctamente";
        }catch (PDOException $e) {
            $msg = $e->errorInfo[2] ?? null;

            // Si el SP devolvió mensaje válido
            if ($msg) {
                // Limpieza del prefijo de SQL Server
                $msg = preg_replace('/^(.*SQL Server\])/', '', $msg);
                $msg = trim($msg);

                throw new \Exception($msg);
            }

            // Si NO hay mensaje del SP → error del motor SQL
            throw new \Exception("No se puede eliminar el practicante. Existen datos relacionados.");
        }

    }

    public function filtrarPracticantes($nombre = null, $areaID = null) {
        try {
            $sql = "EXEC sp_FiltrarPracticantes @Nombre = :nombre, @AreaID = :areaID";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':areaID', $areaID);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error al filtrar practicantes: " . $e->getMessage());
            throw new \Exception("Error al filtrar practicantes");
        }
    }

    public function listarNombresPracticantes() {
        $stmt = $this->db->prepare("EXEC sp_ListarNombresPracticantes");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    

    public function aceptarPracticante($practicanteID, $solicitudID, $areaID, $fechaEntrada, $fechaSalida, $mensajeRespuesta) {
        try {
            error_log("Fechas: " . $fechaEntrada . " " . $fechaSalida);
            $stmt = $this->db->prepare("EXEC sp_AceptarPracticante ?, ?, ?, ?, ?, ?");
            $stmt->execute([
                $practicanteID,
                $solicitudID,
                $areaID,
                $fechaEntrada,
                $fechaSalida,
                $mensajeRespuesta
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result && $result['Resultado'] == 1;
        } catch (PDOException $e) {
            error_log("Error al aceptar practicante: " . $e->getMessage());
            throw new \Exception("Error al aceptar practicante: " . $e->getMessage());
        }
    }


    public function rechazarPracticante($practicanteID, $solicitudID, $mensajeRespuesta) {
        try {
            $stmt = $this->db->prepare("EXEC sp_RechazarPracticante ?, ?, ?");
            return $stmt->execute([$practicanteID, $solicitudID, $mensajeRespuesta]);
        } catch (PDOException $e) {
            error_log("Error al rechazar practicante: " . $e->getMessage());
            throw new \Exception("Error al rechazar practicante");
        }
    }

}
