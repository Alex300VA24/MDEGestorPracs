<?php
namespace App\Services;

use App\Repositories\TurnoRepository;

class TurnoService {
    private $repository;
    
    public function __construct() {
        $this->repository = new TurnoRepository();
    }
    
    public function listarTurnos() {
        return $this->repository->listarTurnos();
    }
    
    public function obtenerTurnosPracticante($practicanteID) {
        return $this->repository->obtenerTurnosPracticante($practicanteID);
    }
}