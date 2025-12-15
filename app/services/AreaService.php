<?php
namespace App\Services;

use App\Repositories\AreaRepository;

class AreaService {
    private $repository;
    
    public function __construct() {
        $this->repository = new AreaRepository();
    }
    
    public function listarAreas() {
        return $this->repository->listarAreas();
    }
}