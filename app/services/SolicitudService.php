<?php
namespace App\Services;

use App\Repositories\SolicitudRepository;

class SolicitudService {
    private $repo;

    public function __construct() {
        $this->repo = new SolicitudRepository();
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
    public function generarCartaAceptacion($solicitudID, $numeroExpediente, $formato) {
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

            // Validar formato del expediente
            if (!preg_match('/^\d{4,6}-\d{4}-\d{1,2}$/', $numeroExpediente)) {
                error_log("Error: formato de expediente inválido: $numeroExpediente");
                return [
                    'success' => false,
                    'message' => 'Formato de expediente inválido. Use: XXXXX-YYYY-X'
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
                    $archivo = $this->repo->generarCartaWord($datosCarta, $numeroExpediente);
                } else if ($formato === 'pdf') {
                    $archivo = $this->repo->generarCartaPDF($datosCarta, $numeroExpediente);
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
                $this->registrarGeneracionCarta($solicitudID, $numeroExpediente, $formato);

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
    private function registrarGeneracionCarta($solicitudID, $numeroExpediente, $formato) {
        try {
            error_log("Carta generada - Solicitud: $solicitudID, Expediente: $numeroExpediente, Formato: $formato");
            
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
}
