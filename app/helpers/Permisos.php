<?php
namespace App\Helpers;

class Permisos {
    public static function obtenerPermisos($cargoID) {
        $permisos = [
            1 => ['inicio', 'practicantes', 'documentos', 'asistencias', 'reportes', 'certificados'], // Gerente RRHH
            2 => ['inicio', 'practicantes', 'asistencias'], // Encargado de Área
            3 => ['inicio', 'asistencias'], // Usuario de Área
            4 => ['inicio', 'practicantes', 'asistencias', 'usuarios'], // Encargado de Sistemas
        ];

        return $permisos[$cargoID] ?? ['inicio'];
    }
}
