<?php
namespace App\Services;

use App\Repositories\PracticanteRepository;
use App\Models\Practicante;

class PracticanteService {
    private $repo;

    public function __construct() {
        $this->repo = new PracticanteRepository();
    }

    public function listarPracticantes() {
        return $this->repo->listarPracticantes();
    }

    public function obtenerPorId($practicanteID) {
        if (empty($practicanteID)) {
            throw new \Exception("ID de practicante requerido");
        }
        $row = $this->repo->obtenerPorID($practicanteID);
        if ($row === null) {
            throw new \Exception("Practicante no encontrado");
        }
        return $row;
    }

    // Metodo para llamar a registrarPracticante del repository
    public function registrarPracticante($datos) {
        // Validaciones básicas
        if (empty($datos['DNI']) || strlen($datos['DNI']) != 8) {

            throw new \Exception("DNI invalido (8 digitos)");
        }

        // Si envían nombre completo, lo separamos en nombres/apellidos
        $nombres = $datos['Nombres'] ?? null;
        $apellidoP = $datos['ApellidoPaterno'] ?? null;
        $apellidoM = $datos['ApellidoMaterno'] ?? null;

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
                // última 2 como apellidos, el resto como nombres
                $apellidoM = array_pop($parts);
                $apellidoP = array_pop($parts);
                $nombres = implode(' ', $parts);
            }
        }

        if (empty($nombres) || empty($apellidoP)) {
            throw new \Exception("Nombres y apellido paterno son requeridos");
        }

        $p = new Practicante();
        $p->setDNI($datos['DNI']);
        $p->setNombres($nombres);
        $p->setApellidoPaterno($apellidoP ?? '');
        $p->setApellidoMaterno($apellidoM ?? '');
        $p->setGenero($datos['genero'] ?? '');
        $p->setCarrera($datos['Carrera']);
        $p->setEmail($datos['Email'] ?? null);
        $p->setTelefono($datos['Telefono'] ?? null);
        $p->setDireccion($datos['Direccion'] ?? null);
        $p->setUniversidad($datos['Universidad'] ?? null);
        $p->setFechaEntrada($datos['FechaEntrada'] ?? date('Y-m-d'));
        $p->setFechaSalida($datos['FechaSalida'] ?? date('Y-m-d'));
        $p->setEstadoID($datos['EstadoID'] ?? 1);

        $areaID = isset($datos['AreaID']) ? (is_numeric($datos['AreaID']) ? (int)$datos['AreaID'] : null) : null;

        return $this->repo->registrarPracticante($p, $areaID);
    }

    public function actualizar($id, $data) {
        error_log("Este es el valor de id: " . $id);
        return $this->repo->actualizar($id, $data);
    }

    public function eliminar($id) {
        return $this->repo->eliminar($id);
    }

    public function filtrarPracticantes($nombre = null, $areaID = null) {
        return $this->repo->filtrarPracticantes($nombre, $areaID);
    }

    public function aceptarPracticante($practicanteID, $solicitudID, $areaID, $fechaEntrada, $fechaSalida, $mensajeRespuesta) {
        return $this->repo->aceptarPracticante($practicanteID, $solicitudID, $areaID, $fechaEntrada, $fechaSalida, $mensajeRespuesta);
    }


    public function rechazarPracticante($practicanteID, $solicitudID, $mensajeRespuesta) {
        return $this->repo->rechazarPracticante($practicanteID, $solicitudID, $mensajeRespuesta);
    }

    public function listarNombresPracticantes() {
        return $this->repo->listarNombresPracticantes();
    }


}
