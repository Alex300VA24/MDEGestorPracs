<?php

namespace App\Services;

use App\Repositories\DocumentoRepository;
use DateTime;

/**
 * Servicio para la lógica de negocio de documentos
 * Procesa y formatea documentos de solicitudes
 */
class DocumentoService {
    private $documentoRepository;

    public function __construct() {
        $this->documentoRepository = new DocumentoRepository();
    }

    /**
     * Obtener documentos de la solicitud ACTIVA del practicante
     * Solo retorna documentos de solicitudes con estado PEN, REV o APR
     */
    public function obtenerDocumentosSolicitudActiva($practicanteID) {
        $documentos = $this->documentoRepository->obtenerDocumentosSolicitudActiva($practicanteID);

        // Formatear y procesar documentos
        $documentosFormateados = [];
        foreach ($documentos as $doc) {
            // Formatear fecha
            if (isset($doc['FechaSubida'])) {
                if ($doc['FechaSubida'] instanceof DateTime) {
                    $doc['FechaSubida'] = $doc['FechaSubida']->format('Y-m-d H:i:s');
                }
            }

            // Limpiar el prefijo '0x' del archivo hexadecimal
            $archivo = $doc['Archivo'] ?? '';
            if (strpos($archivo, '0x') === 0) {
                $archivo = substr($archivo, 2);
            }

            $documentosFormateados[] = [
                'documentoID' => $doc['DocumentoID'],
                'solicitudID' => $doc['SolicitudID'],
                'tipo' => $doc['TipoDocumento'],
                'archivo' => $archivo,
                'observaciones' => $doc['Observaciones'],
                'fechaSubida' => $doc['FechaSubida']
            ];
        }

        return [
            'success' => true,
            'data' => $documentosFormateados,
            'total' => count($documentosFormateados)
        ];
    }
}