<?php
namespace App\Services;

use App\Repositories\SolicitudRepository;
use App\Repositories\EstadoRepository;

class SolicitudService {
    private $repo;
    private $estadoRepo;

    public function __construct() {
        $this->repo = new SolicitudRepository();
        $this->estadoRepo = new EstadoRepository();
    }

    public function obtenerDocumentosPorPracticante($id) {
        return $this->repo->obtenerDocumentosPorPracticante($id);
        
    }

    public function subirDocumento($id, $tipo, $archivo, $observaciones = null) {
        return $this->repo->subirDocumento($id, $tipo, $archivo, $observaciones);
    }

    public function actualizarDocumento($id, $tipo = null, $archivo = null, $observaciones = null) {
        return $this->repo->actualizarDocumento($id, $tipo, $archivo, $observaciones);
    }

    public function obtenerDocumentoPorTipoYPracticante($practicanteID, $tipoDocumento) {
        return $this->repo->obtenerDocumentoPorTipoYPracticante($practicanteID, $tipoDocumento);
    }

    /**
     * Obtener documento por solicitud y tipo
     */
    public function obtenerDocumentoPorTipoYSolicitud($solicitudID, $tipoDocumento) {
        return $this->repo->obtenerDocumentoPorTipoYSolicitud($solicitudID, $tipoDocumento);
    }

    // Agregar a SolicitudService

    public function obtenerSolicitudPorPracticante($practicanteID) {
        return $this->repo->obtenerSolicitudPorPracticante($practicanteID);
    }

    public function crearSolicitud($practicanteID) {
        return $this->repo->crearSolicitud($practicanteID);
    }
    public function obtenerSolicitudPorID($solicitudID) {
        return $this->repo->obtenerSolicitudPorID($solicitudID);
    }


    // Agregar a SolicitudService

    public function eliminarDocumento($documentoID) {
        return $this->repo->eliminarDocumento($documentoID);
    }


    public function verificarEstado($solicitudID) {
        $solicitud = $this->repo->obtenerSolicitudPorID($solicitudID);
        $estado = $this->repo->obtenerEstado($solicitudID);

        return [
            'enviada' => $solicitud ? true : false, // ahora es booleano
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
            if (empty($solicitudID)) {
                error_log("Error: solicitudID vacío");
                return [
                    'success' => false,
                    'message' => 'ID de solicitud no válido'
                ];
            }

            if (empty($numeroExpediente)) {
                error_log("Error: numeroExpediente vacío");
                return [
                    'success' => false,
                    'message' => 'Número de expediente no puede estar vacío'
                ];
            }

            // Validar formato del expediente (5 o 6 dígitos al inicio)
            if (!preg_match('/^\d{5,6}-\d{4}-\d{1,2}$/', $numeroExpediente)) {
                error_log("Error: formato de expediente inválido: $numeroExpediente");
                return [
                    'success' => false,
                    'message' => 'Formato de expediente inválido. Use: XXXXX-YYYY-X o XXXXXX-YYYY-X'
                ];
            }

            error_log("Obteniendo datos de la carta...");
            // Obtener datos de la solicitud
            $datosCarta = $this->repo->obtenerDatosParaCarta($solicitudID);
            error_log("Datos obtenidos: " . print_r($datosCarta, true));
            
            if (!$datosCarta) {
                error_log("Error: No se encontraron datos para la solicitud $solicitudID");
                return [
                    'success' => false,
                    'message' => 'No se encontró una solicitud aprobada con ese ID o faltan datos requeridos (fechas de entrada/salida)'
                ];
            }

            // Validar que todos los campos necesarios estén presentes
            $camposRequeridos = ['Nombres', 'ApellidoPaterno', 'ApellidoMaterno', 'DNI', 'Carrera', 'Universidad', 'NombreArea', 'FechaEntrada', 'FechaSalida'];
            $camposFaltantes = [];
            
            foreach ($camposRequeridos as $campo) {
                if (empty($datosCarta[$campo])) {
                    $camposFaltantes[] = $campo;
                }
            }

            if (!empty($camposFaltantes)) {
                error_log("Error: Campos faltantes: " . implode(', ', $camposFaltantes));
                return [
                    'success' => false,
                    'message' => 'Datos incompletos del practicante. Faltan: ' . implode(', ', $camposFaltantes)
                ];
            }

            // Validar formato del DNI
            if (!preg_match('/^\d{8}$/', $datosCarta['DNI'])) {
                error_log("Error: DNI inválido: " . $datosCarta['DNI']);
                return [
                    'success' => false,
                    'message' => 'DNI del practicante no válido'
                ];
            }

            // Validar que la fecha de entrada sea anterior a la fecha de salida
            $fechaEntrada = strtotime($datosCarta['FechaEntrada']);
            $fechaSalida = strtotime($datosCarta['FechaSalida']);
            
            if ($fechaEntrada >= $fechaSalida) {
                error_log("Error: Fechas inválidas - Entrada: {$datosCarta['FechaEntrada']}, Salida: {$datosCarta['FechaSalida']}");
                return [
                    'success' => false,
                    'message' => 'La fecha de entrada debe ser anterior a la fecha de salida'
                ];
            }

            // Generar el archivo según el formato
            try {
                error_log("Generando archivo en formato: $formato");
                
                if ($formato === 'word') {
                    $archivo = $this->repo->generarCartaWord($datosCarta, $numeroExpediente, $nombreDirector, $cargoDirector);
                } else if ($formato === 'pdf') {
                    $archivo = $this->repo->generarCartaPDF($datosCarta, $numeroExpediente, $nombreDirector, $cargoDirector);
                } else {
                    error_log("Error: Formato no reconocido: $formato");
                    return [
                        'success' => false,
                        'message' => 'Formato no válido. Use "word" o "pdf"'
                    ];
                }

                error_log("Archivo generado: " . print_r($archivo, true));

                // Verificar que el archivo se haya creado correctamente
                if (!file_exists($archivo['ruta'])) {
                    error_log("Error: No se creó el archivo físico en: " . $archivo['ruta']);
                    return [
                        'success' => false,
                        'message' => 'Error al crear el archivo físico'
                    ];
                }

                error_log("Archivo creado exitosamente en: " . $archivo['ruta']);

                // Registrar la generación de la carta (opcional - para auditoría)
                $this->registrarGeneracionCarta($solicitudID, $numeroExpediente, $formato, $nombreDirector, $cargoDirector);

                return [
                    'success' => true,
                    'message' => 'Carta generada exitosamente',
                    'archivo' => $archivo,
                    'datosPracticante' => [
                        'nombreCompleto' => trim($datosCarta['Nombres'] . ' ' . $datosCarta['ApellidoPaterno'] . ' ' . $datosCarta['ApellidoMaterno']),
                        'dni' => $datosCarta['DNI'],
                        'carrera' => $datosCarta['Carrera'],
                        'area' => $datosCarta['NombreArea']
                    ]
                ];

            } catch (\Exception $e) {
                error_log("EXCEPCIÓN al generar archivo: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                return [
                    'success' => false,
                    'message' => 'Error al generar el archivo: ' . $e->getMessage()
                ];
            }

        } catch (\Exception $e) {
            error_log("EXCEPCIÓN general en generarCartaAceptacion: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [
                'success' => false,
                'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Registrar en log o tabla de auditoría la generación de la carta
     * (Opcional - puedes crear una tabla de auditoría si lo necesitas)
     */
    private function registrarGeneracionCarta($solicitudID, $numeroExpediente, $formato, $nombreDirector, $cargoDirector) {
        try {
            error_log("Carta generada - Solicitud: $solicitudID, Expediente: $numeroExpediente, Formato: $formato, Nombre Director: $nombreDirector, Cargo: $cargoDirector");
            
            // Si deseas guardar en una tabla de auditoría, puedes hacerlo aquí:
            // $this->repo->insertarLogGeneracionCarta($solicitudID, $numeroExpediente, $formato);
            
        } catch (\Exception $e) {
            // No fallar si el registro de auditoría falla
            error_log("Error al registrar generación de carta: " . $e->getMessage());
        }
    }

    /**
     * Verificar si una solicitud puede generar carta
     */
    public function verificarSolicitud($solicitudID) {
        try {
            return $this->repo->verificarSolicitudAprobada($solicitudID);
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
        try {
            return $this->repo->listarSolicitudesAprobadas();
        } catch (\Exception $e) {
            error_log("Error en listarSolicitudesAprobadas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener solicitud activa del practicante
     * Estados activos: REV (En Revisión), APR (Aprobado), PEN (Pendiente)
     */
    public function obtenerSolicitudActiva($practicanteID) {
        $solicitud = $this->repo->obtenerSolicitudActivaPorPracticante($practicanteID);

        if ($solicitud) {
            // Formatear fecha si es DateTime
            if (isset($solicitud['FechaSolicitud']) && $solicitud['FechaSolicitud'] instanceof \DateTime) {
                $solicitud['FechaSolicitud'] = $solicitud['FechaSolicitud']->format('Y-m-d H:i:s');
            }

            return [
                'success' => true,
                'data' => $solicitud,
                'mensaje' => 'Solicitud activa encontrada'
            ];
        } else {
            return [
                'success' => false,
                'data' => null,
                'mensaje' => 'No hay solicitud activa. El practicante puede crear una nueva.'
            ];
        }
    }

    /**
     * Crear nueva solicitud con validación de solicitudes activas
     */
    public function crearNuevaSolicitud($practicanteID, $migrarDocumentos = false) {
    // 1️⃣ Validar que NO tenga solicitudes activas
    $tieneSolicitudActiva = $this->repo->tieneSolicitudesActivas($practicanteID);

    if ($tieneSolicitudActiva) {
        return [
            'success' => false,
            'mensaje' => 'El practicante ya tiene una solicitud activa. No puede crear otra hasta que finalice o sea rechazada.'
        ];
    }

    // 2️⃣ Obtener última solicitud si hay que migrar
    $ultimaSolicitudID = null;
    $documentosMigrados = 0;
    
    if ($migrarDocumentos) {
        $ultimaSolicitud = $this->repo->obtenerUltimaSolicitudPorPracticante($practicanteID);
        if ($ultimaSolicitud) {
            $ultimaSolicitudID = $ultimaSolicitud['SolicitudID'];
            error_log("📋 Se migraran documentos de solicitud #{$ultimaSolicitudID}");
        }
    }

    // 3️⃣ Obtener EstadoID para "Pendiente" (PEN)
    $estadoID = $this->estadoRepo->obtenerEstadoIDPorAbreviatura('PEN');

    if (!$estadoID) {
        throw new \Exception('Estado "Pendiente" no encontrado en la base de datos');
    }

    // 4️⃣ Crear nueva solicitud
    $nuevaSolicitudID = $this->repo->crearSolicitudMultiple($practicanteID, $estadoID);

    // 5️⃣ Migrar documentos si corresponde
    if ($migrarDocumentos && $ultimaSolicitudID) {
        try {
            $documentosMigrados = $this->repo->migrarDocumentos($ultimaSolicitudID, $nuevaSolicitudID);
            error_log("✅ Documentos migrados: {$documentosMigrados}");
        } catch (\Exception $e) {
            error_log("⚠️ Error al migrar documentos: " . $e->getMessage());
            // No fallar la creación si falla la migración
        }
    }

    return [
        'success' => true,
        'solicitudID' => $nuevaSolicitudID,
        'mensaje' => 'Nueva solicitud creada exitosamente',
        'documentosMigrados' => $documentosMigrados,
        'solicitudAnterior' => $ultimaSolicitudID
    ];
}

    /**
     * Obtener historial completo de solicitudes del practicante
     */
    public function obtenerHistorialSolicitudes($practicanteID) {
        $solicitudes = $this->repo->obtenerHistorialPorPracticante($practicanteID);

        // Formatear fechas
        foreach ($solicitudes as &$solicitud) {
            if (isset($solicitud['FechaSolicitud']) && $solicitud['FechaSolicitud'] instanceof \DateTime) {
                $solicitud['FechaSolicitud'] = $solicitud['FechaSolicitud']->format('Y-m-d H:i:s');
            }
        }

        return [
            'success' => true,
            'data' => $solicitudes,
            'total' => count($solicitudes)
        ];
    }

    /**
     * Obtener documentos de la solicitud ACTIVA del practicante
     * Solo retorna documentos de solicitudes con estado PEN, REV o APR
     */
    public function obtenerDocumentosSolicitudActiva($practicanteID) {
        $documentos = $this->repo->obtenerDocumentosSolicitudActiva($practicanteID);

        // Formatear y procesar documentos
        $documentosFormateados = [];
        foreach ($documentos as $doc) {
            // Formatear fecha
            if (isset($doc['FechaSubida'])) {
                if ($doc['FechaSubida'] instanceof \DateTime) {
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
