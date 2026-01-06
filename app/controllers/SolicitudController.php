<?php
namespace App\Controllers;

use App\Services\SolicitudService;

class SolicitudController extends BaseController {
    private $service;

    public function __construct($service = null) {
        $this->service = $service ?? new SolicitudService();
    }

    /**
     * Obtener documentos por practicante
     * GET: /api/solicitudes/documentos?practicanteID={id}
     */
    public function obtenerDocumentosPorPracticante() {
        try {
            $practicanteID = $_GET['practicanteID'] ?? null;

            if (!$practicanteID || !is_numeric($practicanteID)) {
                $this->errorResponse('PracticanteID es requerido y debe ser numérico', 400);
            }

            $resultado = $this->service->obtenerDocumentosSolicitudActiva($practicanteID);
            $this->successResponse($resultado);

        } catch (\Exception $e) {
            error_log('Error en obtenerDocumentosPorPracticante: ' . $e->getMessage());
            $this->errorResponse('Error al obtener documentos: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener documento específico por tipo y practicante
     * GET: /api/solicitudes/documento?practicanteID={id}&tipoDocumento={tipo}
     */
    public function obtenerDocumentoPorTipoYPracticante() {
        try {
            $practicanteID = $_GET['practicanteID'] ?? null;
            $tipoDocumento = $_GET['tipoDocumento'] ?? null;

            $this->validateRequired(
                ['practicanteID' => $practicanteID, 'tipoDocumento' => $tipoDocumento],
                ['practicanteID', 'tipoDocumento']
            );

            $documento = $this->service->obtenerDocumentoPorTipoYPracticante($practicanteID, $tipoDocumento);
            $this->successResponse($documento);

        } catch (\Exception $e) {
            $this->errorResponse('Error en el servidor: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener documento específico por tipo y solicitud
     * GET: /api/solicitudes/documento-solicitud?solicitudID={id}&tipoDocumento={tipo}
     */
    public function obtenerDocumentoPorTipoYSolicitud() {
        try {
            $solicitudID = $_GET['solicitudID'] ?? null;
            $tipoDocumento = $_GET['tipoDocumento'] ?? null;

            $this->validateRequired(
                ['solicitudID' => $solicitudID, 'tipoDocumento' => $tipoDocumento],
                ['solicitudID', 'tipoDocumento']
            );

            $documento = $this->service->obtenerDocumentoPorTipoYSolicitud($solicitudID, $tipoDocumento);
            $this->successResponse($documento);

        } catch (\Exception $e) {
            $this->errorResponse('Error en el servidor: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Crear nueva solicitud
     * GET: /api/solicitudes/crear?practicanteID={id}
     */
    public function crearSolicitud() {
        try {
            $practicanteID = $_GET['practicanteID'] ?? null;

            if (!$practicanteID) {
                $this->errorResponse('PracticanteID no proporcionado', 400);
            }

            $solicitudID = $this->service->crearSolicitud($practicanteID);

            if ($solicitudID) {
                $this->successResponse(['solicitudID' => $solicitudID], 'Solicitud creada exitosamente');
            } else {
                $this->errorResponse('Error al crear solicitud', 500);
            }

        } catch (\Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Subir documento
     * POST: /api/solicitudes/documento
     */
    public function subirDocumento() {
        try {
            $this->validateMethod('POST');

            $solicitudID = $_POST['solicitudID'] ?? null;
            $tipoDocumento = $_POST['tipoDocumento'] ?? null;
            $observaciones = $_POST['observacionesDoc'] ?? null;

            $this->validateRequired(
                ['solicitudID' => $solicitudID, 'tipoDocumento' => $tipoDocumento],
                ['solicitudID', 'tipoDocumento']
            );

            // Validar archivo
            $archivo = $this->validateUploadedFile('archivoDocumento');

            // Mapeo de tipos de documentos
            $mapaTipos = [
                'cv' => 'cv',
                'carta_presentacion' => 'carta_presentacion',
                'carnet_vacunacion' => 'carnet_vacunacion',
                'dni' => 'dni'
            ];

            if (!isset($mapaTipos[$tipoDocumento])) {
                $this->errorResponse('Tipo de documento no válido', 400);
            }

            $tipoSP = $mapaTipos[$tipoDocumento];
            $contenido = file_get_contents($archivo['tmp_name']);

            if ($contenido === false) {
                $this->errorResponse('No se pudo leer el archivo', 500);
            }

            $ok = $this->service->subirDocumento($solicitudID, $tipoSP, $contenido, $observaciones);

            if ($ok) {
                $this->logAction('subir_documento', [
                    'solicitudID' => $solicitudID,
                    'tipoDocumento' => $tipoDocumento
                ]);
                $this->successResponse(null, 'Documento subido correctamente');
            } else {
                $this->errorResponse('Error al subir el documento en el servicio', 500);
            }

        } catch (\Exception $e) {
            error_log('Error en subirDocumento: ' . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Actualizar documento existente
     * POST: /api/solicitudes/documento/actualizar
     */
    public function actualizarDocumento() {
        try {
            $this->validateMethod('POST');

            $solicitudID = $_POST['solicitudID'] ?? null;
            $tipoDocumento = $_POST['tipoDocumento'] ?? null;
            $observaciones = $_POST['observacionesDoc'] ?? null;

            $this->validateRequired(
                ['solicitudID' => $solicitudID, 'tipoDocumento' => $tipoDocumento],
                ['solicitudID', 'tipoDocumento']
            );

            // Normalizar tipo de documento
            $tipoDocumento = $this->normalizeDocumentType($tipoDocumento);

            // Procesar archivo si existe
            $contenido = null;
            if (isset($_FILES['archivoDocumento']) && $_FILES['archivoDocumento']['error'] === UPLOAD_ERR_OK) {
                $archivo = $this->validateUploadedFile('archivoDocumento');
                $contenido = file_get_contents($archivo['tmp_name']);

                if ($contenido === false) {
                    $this->errorResponse('No se pudo leer el archivo en el servidor', 500);
                }
            }

            $ok = $this->service->actualizarDocumento($solicitudID, $tipoDocumento, $contenido, $observaciones);

            if ($ok) {
                $this->logAction('actualizar_documento', [
                    'solicitudID' => $solicitudID,
                    'tipoDocumento' => $tipoDocumento,
                    'conArchivo' => $contenido !== null
                ]);
                $this->successResponse(null, 'Documento procesado correctamente');
            } else {
                $this->errorResponse('Error al procesar el documento', 500);
            }

        } catch (\Exception $e) {
            error_log('Error en actualizarDocumento: ' . $e->getMessage());
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Obtener solicitud por ID
     * GET: /api/solicitudes/{id}
     */
    public function obtenerSolicitudPorID() {
        try {
            $solicitudID = $_GET['solicitudID'] ?? null;

            if (!$solicitudID) {
                $this->errorResponse('Falta solicitudID', 400);
            }

            $this->validateId($solicitudID, 'SolicitudID');

            $data = $this->service->obtenerSolicitudPorID($solicitudID);
            $this->successResponse($data);

        } catch (\Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Obtener solicitud por practicante
     * GET: /api/solicitudes/practicante?practicanteID={id}
     */
    public function obtenerSolicitudPorPracticante() {
        try {
            $practicanteID = $_GET['practicanteID'] ?? null;

            if (!$practicanteID) {
                $this->errorResponse('Falta practicanteID', 400);
            }

            $this->validateId($practicanteID, 'PracticanteID');

            $solicitud = $this->service->obtenerSolicitudPorPracticante($practicanteID);
            $this->successResponse($solicitud);

        } catch (\Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Eliminar documento
     * DELETE: /api/solicitudes/documento
     */
    public function eliminarDocumento($documentoID) {
        try {
            $this->validateMethod('DELETE');

            if (!$documentoID) {
                $this->errorResponse('DocumentoID no proporcionado', 400);
            }

            $this->validateId($documentoID, 'DocumentoID');

            $resultado = $this->service->eliminarDocumento($documentoID);

            if ($resultado) {
                $this->logAction('eliminar_documento', ['documentoID' => $documentoID]);
                $this->successResponse(null, 'Documento eliminado correctamente');
            } else {
                $this->errorResponse('No se pudo eliminar el documento', 500);
            }

        } catch (\Exception $e) {
            $this->errorResponse('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Verificar estado de solicitud
     * GET: /api/solicitudes/estado/{id}
     */
    public function verificarEstado($solicitudID) {
        try {
            if (!$solicitudID) {
                $this->errorResponse('Falta el parámetro solicitudID', 400);
            }

            $this->validateId($solicitudID, 'SolicitudID');

            $data = $this->service->verificarEstado($solicitudID);
            $this->successResponse($data);

        } catch (\Exception $e) {
            $this->errorResponse('Error al verificar estado de solicitud: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Generar carta de aceptación
     * POST: /api/solicitudes/carta-aceptacion
     */
    public function generarCartaAceptacion() {
        error_log("=== INICIO generarCartaAceptacion ===");
        
        try {
            $this->validateMethod('POST');

            $data = $this->getJsonInput();
            error_log("Datos recibidos: " . json_encode($data));

            // Validar parámetros requeridos
            $this->validateRequired($data, [
                'solicitudID',
                'numeroExpediente',
                'formato',
                'nombreDirector',
                'cargoDirector'
            ]);

            $solicitudID = $data['solicitudID'];
            $numeroExpediente = $data['numeroExpediente'];
            $formato = strtolower($data['formato']);
            $nombreDirector = $data['nombreDirector'];
            $cargoDirector = $data['cargoDirector'];

            // Validar formato
            if (!in_array($formato, ['word', 'pdf'])) {
                $this->errorResponse('Formato inválido. Use "word" o "pdf"', 400);
            }

            $this->validateId($solicitudID, 'SolicitudID');

            error_log("Llamando al service con: SolicitudID=$solicitudID, Expediente=$numeroExpediente, Formato=$formato");

            $resultado = $this->service->generarCartaAceptacion(
                $solicitudID,
                $numeroExpediente,
                $formato,
                $nombreDirector,
                $cargoDirector
            );

            error_log("Resultado del service: " . json_encode($resultado));

            if ($resultado['success']) {
                $this->logAction('generar_carta_aceptacion', [
                    'solicitudID' => $solicitudID,
                    'formato' => $formato
                ]);
                $this->jsonResponse($resultado, 200);
            } else {
                $this->jsonResponse($resultado, 400);
            }

            error_log("=== FIN generarCartaAceptacion ===");

        } catch (\Exception $e) {
            error_log("EXCEPCIÓN en generarCartaAceptacion: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->errorResponse('Error al generar carta: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Verificar si una solicitud puede generar carta de aceptación
     * GET: /api/solicitudes/verificar-carta?solicitudID={id}
     */
    public function verificarSolicitudParaCarta() {
        try {
            $solicitudID = $_GET['solicitudID'] ?? null;

            if (!$solicitudID) {
                $this->errorResponse('ID de solicitud no proporcionado', 400);
            }

            $this->validateId($solicitudID, 'SolicitudID');

            $resultado = $this->service->verificarSolicitud($solicitudID);
            $this->jsonResponse($resultado);

        } catch (\Exception $e) {
            $this->errorResponse('Error al verificar solicitud: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Listar solicitudes aprobadas que pueden generar carta
     * GET: /api/solicitudes/aprobadas
     */
    public function listarSolicitudesAprobadas() {
        try {
            $resultado = $this->service->listarSolicitudesAprobadas();
            $this->successResponse($resultado);

        } catch (\Exception $e) {
            $this->errorResponse('Error al listar solicitudes: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener solicitud activa del practicante
     * GET: /api/solicitudes/activa/{practicanteID}
     */
    public function obtenerSolicitudActiva($practicanteID) {
        try {
            if (!isset($practicanteID) || !is_numeric($practicanteID)) {
                $this->errorResponse('PracticanteID inválido', 400);
            }

            $resultado = $this->service->obtenerSolicitudActiva($practicanteID);
            $this->jsonResponse($resultado);

        } catch (\Exception $e) {
            error_log('Error en obtenerSolicitudActiva: ' . $e->getMessage());
            $this->errorResponse('Error al obtener solicitud activa: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Crear nueva solicitud con opción de migrar documentos
     * POST: /api/solicitudes
     */
    public function crearNuevaSolicitud() {
        try {
            $this->validateMethod('POST');

            $input = $this->getJsonInput();
            $practicanteID = $input['practicanteID'] ?? null;
            $migrarDocumentos = $input['migrarDocumentos'] ?? false;

            if (!$practicanteID || !is_numeric($practicanteID)) {
                $this->errorResponse('PracticanteID es requerido y debe ser numérico', 400);
            }

            $resultado = $this->service->crearNuevaSolicitud($practicanteID, $migrarDocumentos);

            if ($resultado['success']) {
                $this->logAction('crear_nueva_solicitud', [
                    'practicanteID' => $practicanteID,
                    'migrarDocumentos' => $migrarDocumentos,
                    'solicitudID' => $resultado['solicitudID'] ?? null
                ]);
                $this->jsonResponse($resultado, 201);
            } else {
                $this->jsonResponse($resultado, 400);
            }

        } catch (\Exception $e) {
            error_log('Error en crearNuevaSolicitud: ' . $e->getMessage());
            $this->errorResponse('Error al crear solicitud: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener historial completo de solicitudes
     * GET: /api/solicitudes/historial/{practicanteID}
     */
    public function obtenerHistorialSolicitudes($practicanteID) {
        try {
            if (!isset($practicanteID) || !is_numeric($practicanteID)) {
                $this->errorResponse('PracticanteID inválido', 400);
            }

            $resultado = $this->service->obtenerHistorialSolicitudes($practicanteID);
            $this->jsonResponse($resultado);

        } catch (\Exception $e) {
            error_log('Error en obtenerHistorialSolicitudes: ' . $e->getMessage());
            $this->errorResponse('Error al obtener historial: ' . $e->getMessage(), 500);
        }
    }

    // ==================== MÉTODOS PRIVADOS DE UTILIDAD ====================

    /**
     * Normalizar tipo de documento
     */
    private function normalizeDocumentType($tipo) {
        $tipo = strtolower(trim($tipo));
        $tipo = str_replace([' ', '-'], '_', $tipo);
        $tipo = iconv('UTF-8', 'ASCII//TRANSLIT', $tipo);
        return $tipo;
    }

    /**
     * Validar archivo subido
     */
    private function validateUploadedFile($fieldName, $maxSize = 5242880) {
        if (!isset($_FILES[$fieldName])) {
            throw new \Exception("No se encontró el archivo: $fieldName");
        }

        $file = $_FILES[$fieldName];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('Error al subir el archivo: ' . $this->getUploadErrorMessage($file['error']));
        }

        if (!file_exists($file['tmp_name'])) {
            throw new \Exception('No se pudo acceder al archivo');
        }

        if ($file['size'] <= 0 || $file['size'] > $maxSize) {
            throw new \Exception('El archivo excede el tamaño permitido (5MB)');
        }

        // Validar tipo MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : null;
        if ($finfo) finfo_close($finfo);

        $tiposPermitidos = [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'image/png',
            'image/jpeg'
        ];

        if (!$mime || !in_array($mime, $tiposPermitidos, true)) {
            throw new \Exception('Tipo de archivo no permitido. Use PDF, DOCX, PNG o JPEG');
        }

        return $file;
    }

    /**
     * Obtener mensaje de error de upload
     */
    private function getUploadErrorMessage($errorCode) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido por el formulario',
            UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
            UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal',
            UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en disco',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo'
        ];

        return $errors[$errorCode] ?? 'Error desconocido al subir archivo';
    }
}