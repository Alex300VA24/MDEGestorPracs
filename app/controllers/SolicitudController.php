<?php
namespace App\Controllers;

use App\Services\SolicitudService;

class SolicitudController {
    private $service;

    public function __construct($service = null) {
        $this->service = $service ?? new SolicitudService();
    }


    public function obtenerDocumentosPorPracticante() {
        if (!isset($_GET['practicanteID'])) {
            echo json_encode([]);
            return;
        }

        $id = (int) $_GET['practicanteID'];
        $documentos = $this->service->obtenerDocumentosPorPracticante($id);

        if (!$documentos || count($documentos) === 0) {
            echo json_encode([]);
            return;
        }

        $resultado = [];

        foreach ($documentos as $doc) {
            $resultado[] = [
                'documentoID'   => $doc['DocumentoID'],
                'solicitudID'   => $doc['SolicitudID'],
                'tipo'          => $doc['TipoDocumento'],
                'fecha'         => $doc['FechaSubida'],
                'archivo'       => $doc['Archivo'],
                'observaciones' => $doc['Observaciones'] ?? '',
                'area'          => $doc['Area'] ?? '-'
            ];
        }

        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }

    public function obtenerDocumentoPorTipoYPracticante()
    {
        // Siempre devolver JSON
        header('Content-Type: application/json; charset=utf-8');

        try {
            // 🔹 Obtener parámetros desde la URL
            $practicanteID = $_GET['practicanteID'] ?? null;
            $tipoDocumento = $_GET['tipoDocumento'] ?? null;

            if (!$practicanteID || !$tipoDocumento) {
                echo json_encode([
                    "success" => false,
                    "message" => "Faltan parámetros: practianteID o tipoDocumento."
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            // 🔹 Llamar al servicio (modelo)
            $documento = $this->service->obtenerDocumentoPorTipoYPracticante($practicanteID, $tipoDocumento);

            // 🔹 Asegurar respuesta coherente
            if ($documento && is_array($documento)) {
                echo json_encode([
                    "success" => true,
                    "data" => $documento
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    "success" => true,
                    "data" => null
                ], JSON_UNESCAPED_UNICODE);
            }
        } catch (\Throwable $e) {
            // 🔹 Capturar cualquier error del servidor
            echo json_encode([
                "success" => false,
                "message" => "Error en el servidor: " . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function crearSolicitud() {
        try {
            $practicanteID = $_GET['practicanteID'] ?? null;

            if (!$practicanteID) {
                echo json_encode(['success' => false, 'message' => 'PracticanteID no proporcionado']);
                return;
            }

            // Crear solicitud desde el servicio
            $solicitudID = $this->service->crearSolicitud($practicanteID);

            if ($solicitudID) {
                echo json_encode(['success' => true, 'solicitudID' => $solicitudID]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al crear solicitud']);
            }
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Excepción: ' . $e->getMessage()]);
        }
    }


    public function subirDocumento() {
        try {

            $solicitudID   = $_POST['solicitudID'] ?? null;
            $tipoDocumento = $_POST['tipoDocumento'] ?? null;
            $observaciones = $_POST['observacionesDoc'] ?? null;
            $archivo       = $_FILES['archivoDocumento']['tmp_name'] ?? null;
            $archivoSize   = $_FILES['archivoDocumento']['size'] ?? 0;
            $archivoErr    = $_FILES['archivoDocumento']['error'] ?? UPLOAD_ERR_NO_FILE;

            if (!$solicitudID || !$tipoDocumento || !$archivo) {
                echo json_encode(['error' => 'Datos incompletos']);
                return;
            }

            $mapaTipos = [
                'cv' => 'cv',
                'carta_presentacion' => 'carta_presentacion',
                'carnet_vacunacion' => 'carnet_vacunacion',
                'dni' => 'dni'
            ];

            if (!isset($mapaTipos[$tipoDocumento])) {
                echo json_encode(['error' => 'Tipo de documento no válido']);
                return;
            }

            $tipoSP = $mapaTipos[$tipoDocumento];

            if (!file_exists($archivo)) {
                echo json_encode(['error' => 'No se pudo acceder al archivo']);
                return;
            }
            if ($archivoErr !== UPLOAD_ERR_OK) {
                echo json_encode(['error' => 'Error al subir el archivo']);
                return;
            }
            if ($archivoSize <= 0 || $archivoSize > 5 * 1024 * 1024) {
                echo json_encode(['error' => 'El archivo excede el tamaño permitido (5MB)']);
                return;
            }
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = $finfo ? finfo_file($finfo, $archivo) : null;
            if ($finfo) finfo_close($finfo);
            $tiposPermitidos = [
                'application/pdf',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'image/png',
                'image/jpeg'
            ];
            if (!$mime || !in_array($mime, $tiposPermitidos, true)) {
                echo json_encode(['error' => 'Tipo de archivo no permitido']);
                return;
            }

            $contenido = file_get_contents($archivo);
            if ($contenido === false) {
                echo json_encode(['error' => 'No se pudo leer el archivo']);
                return;
            }

            $ok = $this->service->subirDocumento($solicitudID, $tipoSP, $contenido, $observaciones);

            if ($ok) {
                // Si la subida fue exitosa ($ok es true)
                echo json_encode(['success' => true, 'message' => 'Documento subido correctamente']); 
            } else {
                // Si no fue exitosa ($ok es false)
                echo json_encode(['success' => false, 'message' => 'Error al subir el documento en el servicio']); 
            }
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Excepción: ' . $e->getMessage()]);
        }
    }

    public function actualizarDocumento(){
        try {
            
            $solicitudID   = $_POST['solicitudID'] ?? null;
            $tipoDocumento = $_POST['tipoDocumento'] ?? null;
            $observaciones = $_POST['observacionesDoc'] ?? null;
            $archivoTmp    = $_FILES['archivoDocumento']['tmp_name'] ?? null;
            $archivoErr    = $_FILES['archivoDocumento']['error'] ?? 4; // 4 = UPLOAD_ERR_NO_FILE
            $archivoSize   = $_FILES['archivoDocumento']['size'] ?? 0;

            if (!$solicitudID || !$tipoDocumento) {
                echo json_encode(['success' => false, 'message' => 'Faltan datos: solicitudID o tipoDocumento']);
                return;
            }

            // Normalizar tipoDocumento por seguridad
            $tipoDocumento = strtolower(trim($tipoDocumento));
            $tipoDocumento = str_replace([' ', '-'], '_', $tipoDocumento);
            $tipoDocumento = iconv('UTF-8', 'ASCII//TRANSLIT', $tipoDocumento);

            // Leer archivo si existe y si no hubo error
            $contenido = null;
            if ($archivoTmp && file_exists($archivoTmp) && $archivoErr === UPLOAD_ERR_OK && $archivoSize > 0) {
                if ($archivoSize > 5 * 1024 * 1024) {
                    echo json_encode(['success' => false, 'message' => 'El archivo excede el tamaño permitido (5MB)']);
                    return;
                }
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime  = $finfo ? finfo_file($finfo, $archivoTmp) : null;
                if ($finfo) finfo_close($finfo);
                $tiposPermitidos = [
                    'application/pdf',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'image/png',
                    'image/jpeg'
                ];
                if (!$mime || !in_array($mime, $tiposPermitidos, true)) {
                    echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido']);
                    return;
                }
                $contenido = file_get_contents($archivoTmp);
                if ($contenido === false) {
                    echo json_encode(['success' => false, 'message' => 'No se pudo leer el archivo en el servidor']);
                    return;
                }
                // fijar fecha de subida sólo si hay archivo
                $fechaSubida = date('Y-m-d H:i:s');
                file_put_contents("debug_actualizar.txt", "Archivo leido, bytes: " . strlen($contenido) . " FechaSubida: $fechaSubida\n", FILE_APPEND);
            } else {
                $contenido = null;
                $fechaSubida = null;
                file_put_contents("debug_actualizar.txt", "No hay archivo para actualizar (archivoErr=$archivoErr size=$archivoSize)\n", FILE_APPEND);
            }

            // Llamada a la capa de servicio/repositorio
            $ok = false;

            try {
                $ok = $this->service->actualizarDocumento($solicitudID, $tipoDocumento, $contenido, $observaciones);

                file_put_contents("debug_actualizar.txt", "Resultado service.actualizarDocumento: " . json_encode($ok) . "\n", FILE_APPEND);

                echo json_encode([
                    'success' => true,
                    'message' => 'Documento procesado correctamente'
                ]);
            } catch (\Exception $e) {
                file_put_contents("debug_actualizar.txt", "❌ Error en actualizarDocumento: " . $e->getMessage() . "\n", FILE_APPEND);

                echo json_encode([
                    'success' => false,
                    'message' => 'Error al procesar el documento: ' . $e->getMessage()
                ]);
            }

        } catch (\Throwable $e) {
            file_put_contents("error_actualizar.txt", $e->getMessage() . "\n" . $e->getTraceAsString(), FILE_APPEND);
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function obtenerSolicitudPorID() {
        $id = $_GET['solicitudID'] ?? null;

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Falta solicitudID']);
            return;
        }

        $data = $this->service->obtenerSolicitudPorID($id);
        echo json_encode(['success' => true, 'data' => $data]);
    }


    // Agregar este método a SolicitudController

    public function obtenerSolicitudPorPracticante() {
        try {
            if (!isset($_GET['practicanteID'])) {
                echo json_encode(['success' => false, 'message' => 'Falta practicanteID']);
                return;
            }
            
            $practicanteID = (int)$_GET['practicanteID'];
            $solicitud = $this->service->obtenerSolicitudPorPracticante($practicanteID);
            
            echo json_encode([
                'success' => true,
                'data' => $solicitud
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function eliminarDocumento() {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $documentoID = $data['documentoID'] ?? null;

            if (!$documentoID) {
                echo json_encode([
                    'success' => false,
                    'message' => 'DocumentoID no proporcionado'
                ]);
                return;
            }

            $resultado = $this->service->eliminarDocumento($documentoID);

            if ($resultado) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Documento eliminado correctamente'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No se pudo eliminar el documento'
                ]);
            }
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function verificarEstado($solicitudID) {
        header('Content-Type: application/json');

        try {
            if (!$solicitudID) {
                echo json_encode(['success' => false, 'message' => 'Falta el parámetro solicitudID']);
                return;
            }

            $data = $this->service->verificarEstado($solicitudID);

            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al verificar estado de solicitud: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Generar carta de aceptación
     */
    public function generarCartaAceptacion() {
        header('Content-Type: application/json');
        error_log("=== INICIO generarCartaAceptacion ===");
        
        try {
            // Obtener datos del request
            $rawInput = file_get_contents('php://input');
            error_log("Raw input recibido: " . $rawInput);
            
            $data = json_decode($rawInput, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Error al decodificar JSON: " . json_last_error_msg());
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'JSON inválido: ' . json_last_error_msg()
                ]);
                return;
            }
            
            error_log("Datos decodificados: " . print_r($data, true));
            
            // Validar parámetros
            if (!isset($data['solicitudID'])) {
                error_log("Falta solicitudID");
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Falta parámetro: solicitudID'
                ]);
                return;
            }
            
            if (!isset($data['numeroExpediente'])) {
                error_log("Falta numeroExpediente");
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Falta parámetro: numeroExpediente'
                ]);
                return;
            }
            
            if (!isset($data['formato'])) {
                error_log("Falta formato");
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Falta parámetro: formato'
                ]);
                return;
            }

            $solicitudID = $data['solicitudID'];
            $numeroExpediente = $data['numeroExpediente'];
            $formato = strtolower($data['formato']);
            
            error_log("Parámetros validados - SolicitudID: $solicitudID, Expediente: $numeroExpediente, Formato: $formato");

            if (!in_array($formato, ['word', 'pdf'])) {
                error_log("Formato inválido: $formato");
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Formato inválido. Use "word" o "pdf"'
                ]);
                return;
            }

            error_log("Llamando al service...");
            $resultado = $this->service->generarCartaAceptacion($solicitudID, $numeroExpediente, $formato);
            error_log("Resultado del service: " . print_r($resultado, true));
            
            if ($resultado['success']) {
                http_response_code(200);
                echo json_encode($resultado);
            } else {
                http_response_code(400);
                echo json_encode($resultado);
            }
            
            error_log("=== FIN generarCartaAceptacion ===");
            
        } catch (\Exception $e) {
            error_log("EXCEPCIÓN en generarCartaAceptacion: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al generar carta: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Verificar si una solicitud puede generar carta de aceptación
     */
    public function verificarSolicitudParaCarta() {
        header('Content-Type: application/json');
        
        try {
            $solicitudID = $_GET['solicitudID'] ?? null;
            
            if (!$solicitudID) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID de solicitud no proporcionado'
                ]);
                return;
            }

            $resultado = $this->service->verificarSolicitud($solicitudID);
            echo json_encode($resultado);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al verificar solicitud: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Listar solicitudes aprobadas que pueden generar carta
     */
    public function listarSolicitudesAprobadas() {
        header('Content-Type: application/json');
        
        try {
            $resultado = $this->service->listarSolicitudesAprobadas();
            echo json_encode([
                'success' => true,
                'data' => $resultado
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al listar solicitudes: ' . $e->getMessage()
            ]);
        }
    }




}
