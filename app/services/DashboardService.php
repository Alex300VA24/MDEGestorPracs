<?php
namespace App\Services;

use App\Repositories\DashboardRepository;

class DashboardService {
    private $repo;
    private const CACHE_TTL = 30;

    public function __construct() {
        $this->repo = new DashboardRepository();
    }

    public function obtenerDatosInicio($usuarioID = null, $areaID = null, $cargoID = null) {
        $key = 'dashboard_' . ($areaID ?? 'all') . '_' . ($usuarioID ?? '0') . '_' . ($cargoID ?? '0');
        $cache = $_SESSION['dashboard_cache'][$key] ?? null;
        $now = time();
        if ($cache && isset($cache['expires']) && $cache['expires'] > $now) {
            return $cache['data'];
        }
        $data = [
            'totalPracticantes' => $this->repo->obtenerTotalPracticantes($areaID),
            'pendientesAprobacion' => $this->repo->obtenerPendientesAprobacion($areaID),
            'practicantesActivos' => $this->repo->obtenerPracticantesActivos($areaID),
            'asistenciaHoy' => $this->repo->obtenerAsistenciasHoy($areaID),
            'actividadReciente' => $this->repo->obtenerActividadReciente(10, $usuarioID, $areaID, $cargoID)
        ];
        $_SESSION['dashboard_cache'][$key] = ['data' => $data, 'expires' => $now + self::CACHE_TTL];
        return $data;
    }

}
