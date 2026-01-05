<?php
namespace App\Repositories;

use App\Models\Practicante;
use PDO;
use PDOException;

class PracticanteRepository extends BaseRepository {
    
    protected $table = 'Practicantes';
    protected $primaryKey = 'PracticanteID';

    /**
     * Lista todos los practicantes con su información completa
     */
    public function listarPracticantes() {
        try {
            return $this->executeSP('sp_ListarPracticantes', [], 'all');
        } catch (\Exception $e) {
            error_log("Error al listar practicantes: " . $e->getMessage());
            throw new \Exception("Error al listar practicantes");
        }
    }

    /**
     * Obtiene un practicante por ID (incluye estado y área más reciente)
     */
    public function obtenerPorID($practicanteID) {
        try {
            error_log("Obteniendo practicante por ID: $practicanteID");
            return $this->executeSP('sp_ObtenerPracticantePorID', [
                'PracticanteID' => $practicanteID
            ]);
        } catch (\Exception $e) {
            error_log("Error al obtener practicante: " . $e->getMessage());
            throw new \Exception("Error al obtener practicante");
        }
    }

    /**
     * Registra un nuevo practicante
     * @param Practicante $p
     * @param int|null $areaID
     * @return int ID del practicante creado
     */
    public function registrarPracticante(Practicante $p, $areaID = null) {
        try {
            $params = [
                $p->getDNI(),
                $p->getNombres(),
                $p->getApellidoPaterno(),
                $p->getApellidoMaterno(),
                $p->getGenero(),
                $p->getCarrera(),
                $p->getEmail(),
                $p->getTelefono(),
                $p->getDireccion(),
                $p->getUniversidad()
            ];

            $result = $this->executeSPPositional('sp_RegistrarPracticante', $params);
            
            return $result['PracticanteID'] ?? null;
            
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Actualiza un practicante existente
     */
    public function actualizar($id, $data) {
        try {
            $params = [
                'PracticanteID' => $id,
                'DNI' => $data['DNI'],
                'Nombres' => $data['Nombres'],
                'ApellidoPaterno' => $data['ApellidoPaterno'],
                'ApellidoMaterno' => $data['ApellidoMaterno'],
                'Genero' => $data['genero'],
                'Carrera' => $data['Carrera'],
                'Email' => $data['Email'],
                'Telefono' => $data['Telefono'],
                'Direccion' => $data['Direccion'],
                'Universidad' => $data['Universidad']
            ];

            $this->executeSP('sp_ActualizarPracticante', $params, 'none');
            
            return "Practicante actualizado correctamente";
            
        } catch (\Exception $e) {
            error_log("Error al actualizar practicante: " . $e->getMessage());
            throw new \Exception("Error al actualizar practicante");
        }
    }

    /**
     * Elimina un practicante
     */
    public function eliminar($id) {
        try {
            $this->executeSP('sp_EliminarPracticante', [
                'PracticanteID' => $id
            ], 'none');

            return true;

        } catch (\Exception $e) {

            $msg = $this->cleanErrorMessage($e);

            // 👉 Si el SP envió un mensaje, respétalo
            if (!empty($msg)) {
                throw new \Exception($msg);
            }

            // Fallback (raro)
            throw new \Exception("No se pudo eliminar el practicante.");
        }
    }


    /**
     * Filtra practicantes por nombre y/o área
     */
    public function filtrarPracticantes($nombre = null, $areaID = null) {
        try {
            return $this->executeSP('sp_FiltrarPracticantes', [
                'Nombre' => $nombre,
                'AreaID' => $areaID
            ], 'all');
            
        } catch (\Exception $e) {
            error_log("Error al filtrar practicantes: " . $e->getMessage());
            throw new \Exception("Error al filtrar practicantes");
        }
    }

    /**
     * Lista solo nombres de practicantes (para dropdowns)
     */
    public function listarNombresPracticantes() {
        try {
            return $this->executeSP('sp_ListarNombresPracticantes', [], 'all');
        } catch (\Exception $e) {
            error_log("Error al listar nombres: " . $e->getMessage());
            throw new \Exception("Error al listar nombres de practicantes");
        }
    }

    /**
     * Acepta un practicante en el sistema
     */
    public function aceptarPracticante($practicanteID, $solicitudID, $areaID, $fechaEntrada, $fechaSalida, $mensajeRespuesta) {
        try {
            $params = [
                $practicanteID,
                $solicitudID,
                $areaID,
                $fechaEntrada,
                $fechaSalida,
                $mensajeRespuesta
            ];

            $result = $this->executeSPPositional('sp_AceptarPracticante', $params);
            
            return $result && isset($result['Resultado']) && $result['Resultado'] == 1;
            
        } catch (\Exception $e) {
            error_log("Error al aceptar practicante: " . $e->getMessage());
            throw new \Exception("Error al aceptar practicante");
        }
    }

    /**
     * Rechaza un practicante
     */
    public function rechazarPracticante($practicanteID, $solicitudID, $mensajeRespuesta) {
        try {
            $params = [
                $practicanteID,
                $solicitudID,
                $mensajeRespuesta
            ];

            $this->executeSPPositional('sp_RechazarPracticante', $params, 'none');
            
            return true;
            
        } catch (\Exception $e) {
            error_log("Error al rechazar practicante: " . $e->getMessage());
            throw new \Exception("Error al rechazar practicante");
        }
    }
}