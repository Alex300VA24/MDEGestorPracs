<?php
namespace App\Services;

use App\Repositories\SolicitudRepository;
use App\Repositories\EstadoRepository;

class SolicitudService extends BaseService {
    private $estadoRepo;

    public function __construct() {
        $this->repository = new SolicitudRepository();
        $this->estadoRepo = new EstadoRepository();
    }

    /**
     * Obtener documentos por practicante
     */
    public function obtenerDocumentosPorPracticante($practicanteID) {
        return $this->executeOperation(
            fn() => $this->repository->obtenerDocumentosPorPracticante($practicanteID),
            'Error al obtener documentos por practicante'
        );
    }

    /**
     * Subir documento
     */
    public function subirDocumento($solicitudID, $tipo, $archivo, $observaciones = null) {
        $this->validateId($solicitudID, 'SolicitudID');
        $this->validateRequiredFields(['tipo' => $tipo, 'archivo' => $archivo], ['tipo', 'archivo']);

        return $this->executeOperation(
            fn() => $this->repository->subirDocumento($solicitudID, $tipo, $archivo, $observaciones),
            'Error al subir documento'
        );
    }

    /**
     * Actualizar documento
     */
    public function actualizarDocumento($solicitudID, $tipo = null, $archivo = null, $observaciones = null) {
        $this->validateId($solicitudID, 'SolicitudID');

        return $this->executeOperation(
            fn() => $this->repository->actualizarDocumento($solicitudID, $tipo, $archivo, $observaciones),
            'Error al actualizar documento'
        );
    }

    /**
     * Obtener documento por tipo y practicante
     */
    public function obtenerDocumentoPorTipoYPracticante($practicanteID, $tipoDocumento) {
        $this->validateId($practicanteID, 'PracticanteID');
        $this->validateRequiredFields(['tipoDocumento' => $tipoDocumento], ['tipoDocumento']);

        return $this->executeOperation(
            fn() => $this->repository->obtenerDocumentoPorTipoYPracticante($practicanteID, $tipoDocumento),
            'Error al obtener documento por tipo y practicante'
        );
    }

    /**
     * Obtener documento por solicitud y tipo
     */
    public function obtenerDocumentoPorTipoYSolicitud($solicitudID, $tipoDocumento) {
        $this->validateId($solicitudID, 'SolicitudID');
        $this->validateRequiredFields(['tipoDocumento' => $tipoDocumento], ['tipoDocumento']);

        return $this->executeOperation(
            fn() => $this->repository->obtenerDocumentoPorTipoYSolicitud($solicitudID, $tipoDocumento),
            'Error al obtener documento por tipo y solicitud'
        );
    }

    /**
     * Obtener solicitud por practicante
     */
    public function obtenerSolicitudPorPracticante($practicanteID) {
        $this->validateId($practicanteID, 'PracticanteID');

        return $this->executeOperation(
            fn() => $this->repository->obtenerSolicitudPorPracticante($practicanteID),
            'Error al obtener solicitud por practicante'
        );
    }

    /**
     * Crear solicitud simple
     */
    public function crearSolicitud($practicanteID) {
        $this->validateId($practicanteID, 'PracticanteID');

        return $this->executeOperation(
            fn() => $this->repository->crearSolicitud($practicanteID),
            'Error al crear solicitud'
        );
    }

    /**
     * Obtener solicitud por ID
     */
    public function obtenerSolicitudPorID($solicitudID) {
        $this->validateId($solicitudID, 'SolicitudID');

        return $this->executeOperation(
            fn() => $this->repository->obtenerSolicitudPorID($solicitudID),
            'Error al obtener solicitud por ID'
        );
    }

    /**
     * Eliminar documento
     */
    public function eliminarDocumento($documentoID) {
        $this->validateId($documentoID, 'DocumentoID');

        return $this->executeOperation(
            fn() => $this->repository->eliminarDocumento($documentoID),
            'Error al eliminar documento'
        );
    }

    /**
     * Verificar estado de solicitud
     */
    public function verificarEstado($solicitudID) {
        $this->validateId($solicitudID, 'SolicitudID');

        $solicitud = $this->repository->obtenerSolicitudPorID($solicitudID);
        $estado = $this->repository->obtenerEstado($solicitudID);

        return [
            'enviada' => $solicitud ? true : false,
            'estado' => $estado ?? [
                'abreviatura' => 'REV',
                'descripcion' => 'En Revisión'
            ],
            'aprobada' => isset($estado['abreviatura']) && strtoupper(trim($estado['abreviatura'])) === 'APR'
        ];
    }

    /**
     * Generar carta de aceptación con validaciones completas
     */
    public function generarCartaAceptacion($solicitudID, $numeroExpediente, $formato, $nombreDirector, $cargoDirector) {
        error_log("=== SERVICE generarCartaAceptacion ===");
        error_log("SolicitudID: $solicitudID, Expediente: $numeroExpediente, Formato: $formato");

        try {
            // Validar parámetros de entrada
            $this->validateId($solicitudID, 'SolicitudID');
            $this->validateRequiredFields(
                [
                    'numeroExpediente' => $numeroExpediente,
                    'formato' => $formato,
                    'nombreDirector' => $nombreDirector,
                    'cargoDirector' => $cargoDirector
                ],
                ['numeroExpediente', 'formato', 'nombreDirector', 'cargoDirector']
            );

            // Validar formato del expediente (5 o 6 dígitos al inicio)
            if (!$this->validarFormatoExpediente($numeroExpediente)) {
                return $this->errorResult('Formato de expediente inválido. Use: XXXXX-YYYY-X o XXXXXX-YYYY-X');
            }

            // Validar formato de salida
            if (!in_array(strtolower($formato), ['word', 'pdf'])) {
                return $this->errorResult('Formato no válido. Use "word" o "pdf"');
            }

            error_log("Obteniendo datos de la carta...");
            // Obtener datos de la solicitud
            $datosCarta = $this->repository->obtenerDatosParaCarta($solicitudID);
            error_log("Datos obtenidos: " . print_r($datosCarta, true));

            if (!$datosCarta) {
                return $this->errorResult('No se encontró una solicitud aprobada con ese ID o faltan datos requeridos (fechas de entrada/salida)');
            }

            // Validar campos requeridos del practicante
            $validacionDatos = $this->validarDatosPracticante($datosCarta);
            if (!$validacionDatos['valido']) {
                return $this->errorResult($validacionDatos['mensaje']);
            }

            // Validar fechas
            $validacionFechas = $this->validarFechas($datosCarta['FechaEntrada'], $datosCarta['FechaSalida']);
            if (!$validacionFechas['valido']) {
                return $this->errorResult($validacionFechas['mensaje']);
            }

            // Generar el archivo según el formato
            $archivo = $this->generarArchivoCarta($datosCarta, $numeroExpediente, $formato, $nombreDirector, $cargoDirector);

            if (!$archivo) {
                return $this->errorResult('Error al generar el archivo');
            }

            // Verificar que el archivo se haya creado físicamente
            if (!file_exists($archivo['ruta'])) {
                return $this->errorResult('Error al crear el archivo físico');
            }

            // Registrar la generación de la carta (auditoría)
            $this->registrarGeneracionCarta($solicitudID, $numeroExpediente, $formato, $nombreDirector, $cargoDirector);

            return $this->successResult([
                'archivo' => $archivo,
                'datosPracticante' => [
                    'nombreCompleto' => trim($datosCarta['Nombres'] . ' ' . $datosCarta['ApellidoPaterno'] . ' ' . $datosCarta['ApellidoMaterno']),
                    'dni' => $datosCarta['DNI'],
                    'carrera' => $datosCarta['Carrera'],
                    'area' => $datosCarta['NombreArea']
                ]
            ], 'Carta generada exitosamente');

        } catch (\Exception $e) {
            error_log("EXCEPCIÓN en generarCartaAceptacion: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return $this->errorResult('Error al procesar la solicitud: ' . $e->getMessage());
        }
    }

    /**
     * Verificar si una solicitud puede generar carta
     */
    public function verificarSolicitud($solicitudID) {
        try {
            $this->validateId($solicitudID, 'SolicitudID');
            return $this->repository->verificarSolicitudAprobada($solicitudID);
        } catch (\Exception $e) {
            return [
                'valido' => false,
                'mensaje' => 'Error al verificar la solicitud: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Listar solicitudes aprobadas
     */
    public function listarSolicitudesAprobadas() {
        return $this->executeOperation(
            fn() => $this->repository->listarSolicitudesAprobadas(),
            'Error al listar solicitudes aprobadas'
        );
    }

    /**
     * Obtener solicitud activa del practicante
     */
    public function obtenerSolicitudActiva($practicanteID) {
        $this->validateId($practicanteID, 'PracticanteID');

        $solicitud = $this->repository->obtenerSolicitudActivaPorPracticante($practicanteID);

        if ($solicitud) {
            // Formatear fecha
            $solicitud = $this->formatearFechasSolicitud($solicitud);

            return $this->successResult($solicitud, 'Solicitud activa encontrada');
        }

        return [
            'success' => false,
            'data' => null,
            'mensaje' => 'No hay solicitud activa. El practicante puede crear una nueva.'
        ];
    }

    /**
     * Crear nueva solicitud con validación y opción de migración
     */
    public function crearNuevaSolicitud($practicanteID, $migrarDocumentos = false) {
        $this->validateId($practicanteID, 'PracticanteID');

        // Validar que NO tenga solicitudes activas
        $tieneSolicitudActiva = $this->repository->tieneSolicitudesActivas($practicanteID);

        if ($tieneSolicitudActiva) {
            return [
                'success' => false,
                'mensaje' => 'El practicante ya tiene una solicitud activa. No puede crear otra hasta que finalice o sea rechazada.'
            ];
        }

        // Obtener última solicitud si hay que migrar
        $ultimaSolicitudID = null;
        $documentosMigrados = 0;

        if ($migrarDocumentos) {
            $ultimaSolicitud = $this->repository->obtenerUltimaSolicitudPorPracticante($practicanteID);
            if ($ultimaSolicitud) {
                $ultimaSolicitudID = $ultimaSolicitud['SolicitudID'];
                error_log("Se migrarán documentos de solicitud #{$ultimaSolicitudID}");
            }
        }

        // Obtener EstadoID para "En Revision"
        $estadoID = $this->estadoRepo->obtenerEstadoIDPorAbreviatura('REV');

        if (!$estadoID) {
            throw new \Exception('Estado "En Revision" no encontrado en la base de datos');
        }

        // Crear nueva solicitud
        $nuevaSolicitudID = $this->repository->crearSolicitudMultiple($practicanteID, $estadoID);

        // Migrar documentos si corresponde
        if ($migrarDocumentos && $ultimaSolicitudID) {
            try {
                $documentosMigrados = $this->repository->migrarDocumentos($ultimaSolicitudID, $nuevaSolicitudID);
                error_log("Documentos migrados: {$documentosMigrados}");
            } catch (\Exception $e) {
                error_log("Error al migrar documentos: " . $e->getMessage());
            }
        }

        return $this->successResult([
            'solicitudID' => $nuevaSolicitudID,
            'documentosMigrados' => $documentosMigrados,
            'solicitudAnterior' => $ultimaSolicitudID
        ], 'Nueva solicitud creada exitosamente');
    }

    /**
     * Obtener historial completo de solicitudes
     */
    public function obtenerHistorialSolicitudes($practicanteID) {
        $this->validateId($practicanteID, 'PracticanteID');

        $solicitudes = $this->repository->obtenerHistorialPorPracticante($practicanteID);

        // Formatear fechas
        foreach ($solicitudes as &$solicitud) {
            $solicitud = $this->formatearFechasSolicitud($solicitud);
        }
        
        return [
            'success' => true,
            'data' => $solicitudes,
            'total' => count($solicitudes)
        ];
    }

    /**
     * Obtener documentos de la solicitud activa
     */
    public function obtenerDocumentosSolicitudActiva($practicanteID) {
        $this->validateId($practicanteID, 'PracticanteID');

        $documentos = $this->repository->obtenerDocumentosSolicitudActiva($practicanteID);

        // Formatear documentos
        $documentosFormateados = array_map(
            fn($doc) => $this->formatearDocumento($doc),
            $documentos
        );

        return [
            'success' => true,
            'data' => $documentosFormateados,
            'total' => count($documentosFormateados)
        ];
    }

    // ==================== MÉTODOS PRIVADOS DE UTILIDAD ====================

    /**
     * Validar formato de expediente
     */
    private function validarFormatoExpediente($numeroExpediente) {
        return preg_match('/^\d{5,6}-\d{4}-\d{1,2}$/', $numeroExpediente);
    }

    /**
     * Validar datos del practicante
     */
    private function validarDatosPracticante($datos) {
        $camposRequeridos = [
            'Nombres',
            'ApellidoPaterno',
            'ApellidoMaterno',
            'DNI',
            'Carrera',
            'Universidad',
            'NombreArea',
            'FechaEntrada',
            'FechaSalida'
        ];

        $camposFaltantes = [];

        foreach ($camposRequeridos as $campo) {
            if (empty($datos[$campo])) {
                $camposFaltantes[] = $campo;
            }
        }

        if (!empty($camposFaltantes)) {
            return [
                'valido' => false,
                'mensaje' => 'Datos incompletos del practicante. Faltan: ' . implode(', ', $camposFaltantes)
            ];
        }

        // Validar formato del DNI
        if (!preg_match('/^\d{8}$/', $datos['DNI'])) {
            return [
                'valido' => false,
                'mensaje' => 'DNI del practicante no válido (debe tener 8 dígitos)'
            ];
        }

        return ['valido' => true];
    }

    /**
     * Validar fechas de entrada y salida
     */
    private function validarFechas($fechaEntrada, $fechaSalida) {
        $entrada = strtotime($fechaEntrada);
        $salida = strtotime($fechaSalida);

        if ($entrada >= $salida) {
            return [
                'valido' => false,
                'mensaje' => 'La fecha de entrada debe ser anterior a la fecha de salida'
            ];
        }

        return ['valido' => true];
    }

    /**
     * Generar archivo de carta según formato
     */
    private function generarArchivoCarta($datosCarta, $numeroExpediente, $formato, $nombreDirector, $cargoDirector) {
        try {
            error_log("Generando archivo en formato: $formato");

            if ($formato === 'word') {
                return $this->repository->generarCartaWord($datosCarta, $numeroExpediente, $nombreDirector, $cargoDirector);
            } elseif ($formato === 'pdf') {
                return $this->repository->generarCartaPDF($datosCarta, $numeroExpediente, $nombreDirector, $cargoDirector);
            }

            throw new \Exception("Formato no reconocido: $formato");

        } catch (\Exception $e) {
            error_log("Error al generar archivo: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Registrar generación de carta (auditoría)
     */
    private function registrarGeneracionCarta($solicitudID, $numeroExpediente, $formato, $nombreDirector, $cargoDirector) {
        try {
            error_log("📄 Carta generada - Solicitud: $solicitudID, Expediente: $numeroExpediente, Formato: $formato, Director: $nombreDirector, Cargo: $cargoDirector");
            
            // Aquí puedes agregar lógica para guardar en tabla de auditoría
            // $this->repository->insertarLogGeneracionCarta($solicitudID, $numeroExpediente, $formato);
            
        } catch (\Exception $e) {
            error_log("⚠️ Error al registrar generación de carta: " . $e->getMessage());
        }
    }

    /**
     * Formatear fechas de solicitud
     */
    private function formatearFechasSolicitud($solicitud) {
        if (isset($solicitud['FechaSolicitud']) && $solicitud['FechaSolicitud'] instanceof \DateTime) {
            $solicitud['FechaSolicitud'] = $solicitud['FechaSolicitud']->format('Y-m-d H:i:s');
        }
        return $solicitud;
    }

    /**
     * Formatear documento con limpieza de datos
     */
    private function formatearDocumento($doc) {
        // Formatear fecha
        if (isset($doc['FechaSubida']) && $doc['FechaSubida'] instanceof \DateTime) {
            $doc['FechaSubida'] = $doc['FechaSubida']->format('Y-m-d H:i:s');
        }

        // Limpiar el prefijo '0x' del archivo hexadecimal
        $archivo = $doc['Archivo'] ?? '';
        if (strpos($archivo, '0x') === 0) {
            $archivo = substr($archivo, 2);
        }

        return [
            'documentoID' => $doc['DocumentoID'],
            'solicitudID' => $doc['SolicitudID'],
            'tipo' => $doc['TipoDocumento'],
            'archivo' => $archivo,
            'observaciones' => $doc['Observaciones'],
            'fechaSubida' => $doc['FechaSubida']
        ];
    }
}