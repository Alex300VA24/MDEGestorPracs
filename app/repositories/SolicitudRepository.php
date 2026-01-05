<?php
namespace App\Repositories;

use PDO;
use PDOException;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use Dompdf\Dompdf;
use Dompdf\Options;

class SolicitudRepository extends BaseRepository {
    
    protected $table = 'SolicitudPracticas';
    protected $primaryKey = 'SolicitudID';

    // ==================== MÉTODOS DE DOCUMENTOS ====================

    /**
     * Obtener documentos por practicante
     */
    public function obtenerDocumentosPorPracticante($practicanteID) {
        $result = $this->executeSPPositional('sp_ObtenerDocumentosPorPracticante', [$practicanteID], 'all');
        return $this->formatBinaryResults($result);
    }

    /**
     * Obtener documento específico por tipo y practicante
     */
    public function obtenerDocumentoPorTipoYPracticante($practicanteID, $tipoDocumento) {
        $documento = $this->executeSPPositional(
            'sp_ObtenerDocumentoPorTipoYPracticante',
            [$practicanteID, $tipoDocumento],
            'one'
        );

        if ($documento && isset($documento['Archivo'])) {
            $documento['Archivo'] = $this->convertBinaryToBase64($documento['Archivo']);
        }

        return $documento;
    }

    /**
     * Obtener documento por tipo y solicitud
     */
    public function obtenerDocumentoPorTipoYSolicitud($solicitudID, $tipoDocumento) {
        $sql = "
            SELECT 
                d.DocumentoID,
                d.SolicitudID,
                d.TipoDocumento,
                d.Observaciones,
                d.FechaSubida,
                d.Archivo
            FROM DocumentoSolicitud d
            WHERE d.SolicitudID = :solicitudID 
            AND d.TipoDocumento = :tipoDocumento
        ";

        $documento = $this->executeQuery($sql, [
            ':solicitudID' => $solicitudID,
            ':tipoDocumento' => $tipoDocumento
        ], 'one');

        if ($documento && isset($documento['Archivo'])) {
            $documento['Archivo'] = $this->convertBinaryToBase64($documento['Archivo']);
            
            if (isset($documento['FechaSubida'])) {
                $documento['FechaSubida'] = date('d/m/Y', strtotime($documento['FechaSubida']));
            }
        }

        return $documento;
    }

    /**
     * Obtener documentos de la solicitud activa del practicante
     */
    public function obtenerDocumentosSolicitudActiva($practicanteID) {
        $sql = "
            SELECT 
                d.DocumentoID,
                d.SolicitudID,
                d.TipoDocumento,
                d.Observaciones,
                d.FechaSubida,
                CONVERT(VARCHAR(MAX), CAST(d.Archivo AS VARBINARY(MAX)), 1) AS Archivo
            FROM DocumentoSolicitud d
            INNER JOIN SolicitudPracticas s ON d.SolicitudID = s.SolicitudID
            INNER JOIN Estado e ON s.EstadoID = e.EstadoID
            WHERE s.PracticanteID = :practicanteID
                AND e.Abreviatura IN ('PEN', 'REV', 'APR')
            ORDER BY d.FechaSubida DESC
        ";

        return $this->executeQuery($sql, [':practicanteID' => $practicanteID], 'all');
    }

    /**
     * Obtener documento por ID
     */
    public function obtenerPorID($documentoID) {
        $sql = "
            SELECT 
                d.DocumentoID,
                d.SolicitudID,
                d.TipoDocumento,
                d.Observaciones,
                d.FechaSubida,
                CONVERT(VARCHAR(MAX), CAST(d.Archivo AS VARBINARY(MAX)), 1) AS Archivo
            FROM DocumentoSolicitud d
            WHERE d.DocumentoID = :documentoID
        ";

        return $this->executeQuery($sql, [':documentoID' => $documentoID], 'one');
    }

    /**
     * Obtener documentos por solicitud
     */
    public function obtenerPorSolicitud($solicitudID) {
        $sql = "
            SELECT 
                d.DocumentoID,
                d.SolicitudID,
                d.TipoDocumento,
                d.Observaciones,
                d.FechaSubida,
                CONVERT(VARCHAR(MAX), CAST(d.Archivo AS VARBINARY(MAX)), 1) AS Archivo
            FROM DocumentoSolicitud d
            WHERE d.SolicitudID = :solicitudID
            ORDER BY d.FechaSubida DESC
        ";

        return $this->executeQuery($sql, [':solicitudID' => $solicitudID], 'all');
    }

    /**
     * Subir documento
     */
    public function subirDocumento($solicitudID, $tipo, $archivo, $observaciones = null) {
        error_log("Subiendo documento para SolicitudID: $solicitudID, Tipo: $tipo");
        error_log(print_r($archivo, true));
        return $this->executeSPWithLOB('sp_SubirDocumento', [
            'solicitudID' => (int)$solicitudID,
            'tipo' => $tipo,
            'archivo' => $archivo,
            'observaciones' => $observaciones
        ]);
    }

    /**
     * Actualizar documento
     */
    public function actualizarDocumento($solicitudID, $tipoDocumento, $archivo = null, $observaciones = null) {
        if ($archivo !== null) {
            return $this->executeSPWithLOB('sp_ActualizarDocumento', [
                'solicitudID' => (int)$solicitudID,
                'tipoDocumento' => $tipoDocumento,
                'archivo' => $archivo,
                'observaciones' => $observaciones
            ]);
        } else {
            // Sin archivo, solo actualizar observaciones
            $sql = "EXEC sp_ActualizarDocumento @SolicitudID = ?, @TipoDocumento = ?, @Observaciones = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, (int)$solicitudID, PDO::PARAM_INT);
            $stmt->bindValue(2, $tipoDocumento, PDO::PARAM_STR);
            $stmt->bindValue(3, $observaciones, PDO::PARAM_STR);
            
            return $stmt->execute();
        }
    }

    /**
     * Crear documento
     */
    public function crearDocumento($solicitudID, $tipoDocumento, $archivo, $observaciones = null) {
        $sql = "
            INSERT INTO DocumentoSolicitud (SolicitudID, TipoDocumento, Archivo, Observaciones, FechaSubida)
            OUTPUT INSERTED.DocumentoID
            VALUES (:solicitudID, :tipoDocumento, CONVERT(VARBINARY(MAX), :archivo, 1), :observaciones, GETDATE())
        ";

        return $this->executeQuery($sql, [
            ':solicitudID' => $solicitudID,
            ':tipoDocumento' => $tipoDocumento,
            ':archivo' => $archivo,
            ':observaciones' => $observaciones
        ], 'one')['DocumentoID'] ?? null;
    }

    /**
     * Eliminar documento
     */
    public function eliminarDocumento($documentoID) {
        return $this->executeTransaction(function() use ($documentoID) {
            // Eliminar documento
            $deleted = $this->deleteWhere(['DocumentoID' => $documentoID]);
            
            if ($deleted > 0) {
                // Reiniciar IDENTITY
                $this->reseedIdentity('DocumentoSolicitud');
                
                return [
                    'success' => true,
                    'message' => 'Documento eliminado'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'No se encontró el documento o ya fue eliminado'
            ];
        });
    }

    // ==================== MÉTODOS DE SOLICITUDES ====================

    /**
     * Crear solicitud simple
     */
    public function crearSolicitud($practicanteID) {
        $result = $this->executeSPPositional('sp_CrearSolicitud', [(int)$practicanteID], 'one');
        return $result ? $result['SolicitudID'] : null;
    }

    /**
     * Crear solicitud con estado específico
     */
    public function crearSolicitudMultiple($practicanteID, $estadoID) {
        $sql = "
            INSERT INTO SolicitudPracticas (FechaSolicitud, EstadoID, PracticanteID, AreaID)
            OUTPUT INSERTED.SolicitudID
            VALUES (GETDATE(), :estadoID, :practicanteID, NULL)
        ";

        $result = $this->executeQuery($sql, [
            ':estadoID' => $estadoID,
            ':practicanteID' => $practicanteID
        ], 'one');

        return $result['SolicitudID'] ?? null;
    }

    /**
     * Obtener solicitud por ID
     */
    public function obtenerSolicitudPorID($solicitudID) {
        $sql = "
            SELECT 
                s.SolicitudID, 
                s.FechaSolicitud, 
                s.EstadoID, 
                a.NombreArea AS areaNombre, 
                p.Nombres AS practicanteNombre
            FROM SolicitudPracticas s
            INNER JOIN Area a ON s.AreaID = a.AreaID
            INNER JOIN Practicante p ON s.PracticanteID = p.PracticanteID
            WHERE s.SolicitudID = :id
        ";

        return $this->executeQuery($sql, [':id' => $solicitudID], 'one');
    }

    /**
     * Obtener solicitud por practicante
     */
    public function obtenerSolicitudPorPracticante($practicanteID) {
        return $this->findFirst(
            ['PracticanteID' => $practicanteID],
            'FechaSolicitud DESC'
        );
    }

    /**
     * Obtener solicitud activa del practicante
     */
    public function obtenerSolicitudActivaPorPracticante($practicanteID) {
        $sql = "
            SELECT TOP 1 
                s.SolicitudID,
                s.FechaSolicitud,
                s.EstadoID,
                s.PracticanteID,
                s.AreaID,
                e.Abreviatura AS EstadoAbrev,
                e.Descripcion AS EstadoDesc,
                a.NombreArea
            FROM SolicitudPracticas s
            INNER JOIN Estado e ON s.EstadoID = e.EstadoID
            LEFT JOIN Area a ON s.AreaID = a.AreaID
            WHERE s.PracticanteID = :practicanteID
                AND e.Abreviatura IN ('PEN', 'REV', 'APR')
            ORDER BY s.FechaSolicitud DESC
        ";

        return $this->executeQuery($sql, [':practicanteID' => $practicanteID], 'one');
    }

    /**
     * Obtener última solicitud del practicante
     */
    public function obtenerUltimaSolicitudPorPracticante($practicanteID) {
        $sql = "
            SELECT TOP 1 
                s.SolicitudID,
                s.FechaSolicitud,
                s.EstadoID,
                e.Abreviatura AS EstadoAbrev,
                e.Descripcion AS EstadoDesc
            FROM SolicitudPracticas s
            INNER JOIN Estado e ON s.EstadoID = e.EstadoID
            WHERE s.PracticanteID = :practicanteID
            ORDER BY s.FechaSolicitud DESC
        ";

        return $this->executeQuery($sql, [':practicanteID' => $practicanteID], 'one');
    }

    /**
     * Verificar si tiene solicitudes activas
     */
    public function tieneSolicitudesActivas($practicanteID) {
        $sql = "
            SELECT COUNT(*) AS Total
            FROM SolicitudPracticas s
            INNER JOIN Estado e ON s.EstadoID = e.EstadoID
            WHERE s.PracticanteID = :practicanteID
                AND e.Abreviatura IN ('PEN', 'REV', 'APR')
        ";

        $result = $this->executeQuery($sql, [':practicanteID' => $practicanteID], 'one');
        return $result['Total'] > 0;
    }

    /**
     * Obtener historial de solicitudes
     */
    public function obtenerHistorialPorPracticante($practicanteID) {
        $sql = "
            SELECT 
                s.SolicitudID,
                s.FechaSolicitud,
                s.EstadoID,
                s.AreaID,
                e.Abreviatura AS EstadoAbrev,
                e.Descripcion AS EstadoDesc,
                a.NombreArea
            FROM SolicitudPracticas s
            INNER JOIN Estado e ON s.EstadoID = e.EstadoID
            LEFT JOIN Area a ON s.AreaID = a.AreaID
            WHERE s.PracticanteID = :practicanteID
            ORDER BY s.FechaSolicitud DESC
        ";

        return $this->executeQuery($sql, [':practicanteID' => $practicanteID], 'all');
    }

    /**
     * Obtener estado de la solicitud
     */
    public function obtenerEstado($solicitudID) {
        $sql = "
            SELECT e.Abreviatura, e.Descripcion
            FROM SolicitudPracticas sp
            INNER JOIN Estado e ON sp.EstadoID = e.EstadoID
            WHERE sp.SolicitudID = :solicitudID
        ";

        $row = $this->executeQuery($sql, [':solicitudID' => $solicitudID], 'one');

        return $row ? [
            'abreviatura' => $row['Abreviatura'],
            'descripcion' => $row['Descripcion']
        ] : null;
    }

    // ==================== MIGRACIÓN DE DOCUMENTOS ====================

    /**
     * Migrar documentos de una solicitud a otra
     */
    public function migrarDocumentos($solicitudOrigenID, $solicitudDestinoID) {
        return $this->executeTransaction(function() use ($solicitudOrigenID, $solicitudDestinoID) {
            // Contar en tabla específica
            $total = $this->count(
                ['SolicitudID' => $solicitudOrigenID],
                'DocumentoSolicitud' // ← Especificar tabla
            );
            
            if ($total == 0) {
                return 0;
            }
            
            // Actualizar en tabla específica
            return $this->updateWhereTable(
                'DocumentoSolicitud', // ← Especificar tabla
                [
                    'SolicitudID' => $solicitudDestinoID,
                    'FechaSubida' => date('Y-m-d H:i:s')
                ],
                ['SolicitudID' => $solicitudOrigenID]
            );
        });
    }

    // ==================== CARTAS DE ACEPTACIÓN ====================

    /**
     * Obtener datos para carta de aceptación
     */
    public function obtenerDatosParaCarta($solicitudID) {
        $sql = "
            SELECT 
                p.Nombres,
                p.ApellidoPaterno,
                p.ApellidoMaterno,
                p.Genero,
                p.DNI,
                p.Carrera,
                p.Universidad,
                p.FechaEntrada,
                p.FechaSalida,
                a.NombreArea,
                s.SolicitudID,
                s.FechaSolicitud,
                e.Abreviatura AS EstadoAbreviatura,
                e.Descripcion AS EstadoDescripcion
            FROM SolicitudPracticas s
            INNER JOIN Practicante p ON s.PracticanteID = p.PracticanteID
            INNER JOIN Area a ON s.AreaID = a.AreaID
            INNER JOIN Estado e ON s.EstadoID = e.EstadoID
            WHERE s.SolicitudID = :solicitudID
            AND e.Abreviatura = 'APR'
        ";

        $resultado = $this->executeQuery($sql, [':solicitudID' => $solicitudID], 'one');

        // Validar fechas requeridas
        if ($resultado && (empty($resultado['FechaEntrada']) || empty($resultado['FechaSalida']))) {
            error_log("Advertencia: Practicante sin fechas asignadas para solicitud ID: $solicitudID");
            return null;
        }

        return $resultado;
    }

    /**
     * Verificar si solicitud está aprobada
     */
    public function verificarSolicitudAprobada($solicitudID) {
        $sql = "
            SELECT 
                s.SolicitudID,
                s.EstadoID,
                e.Abreviatura,
                e.Descripcion,
                p.Nombres,
                p.ApellidoPaterno,
                p.ApellidoMaterno
            FROM SolicitudPracticas s
            INNER JOIN Estado e ON s.EstadoID = e.EstadoID
            INNER JOIN Practicante p ON s.PracticanteID = p.PracticanteID
            WHERE s.SolicitudID = :solicitudID
        ";

        $resultado = $this->executeQuery($sql, [':solicitudID' => $solicitudID], 'one');

        if (!$resultado) {
            return [
                'valido' => false,
                'mensaje' => 'Solicitud no encontrada'
            ];
        }

        if ($resultado['Abreviatura'] !== 'APR') {
            return [
                'valido' => false,
                'mensaje' => 'La solicitud debe estar en estado Aprobado',
                'estadoActual' => $resultado['Descripcion']
            ];
        }

        return [
            'valido' => true,
            'mensaje' => 'Solicitud válida para generar carta',
            'practicante' => trim($resultado['Nombres'] . ' ' . $resultado['ApellidoPaterno'] . ' ' . $resultado['ApellidoMaterno'])
        ];
    }

    /**
     * Listar solicitudes aprobadas
     */
    public function listarSolicitudesAprobadas() {
        $sql = "
            SELECT 
                s.SolicitudID,
                s.FechaSolicitud,
                p.PracticanteID,
                p.Nombres,
                p.ApellidoPaterno,
                p.ApellidoMaterno,
                p.DNI,
                p.Carrera,
                p.Universidad,
                p.FechaEntrada,
                p.FechaSalida,
                a.NombreArea,
                e.Descripcion AS Estado
            FROM SolicitudPracticas s
            INNER JOIN Practicante p ON s.PracticanteID = p.PracticanteID
            INNER JOIN Area a ON s.AreaID = a.AreaID
            INNER JOIN Estado e ON s.EstadoID = e.EstadoID
            WHERE e.Abreviatura = 'APR'
            AND p.FechaEntrada IS NOT NULL
            AND p.FechaSalida IS NOT NULL
            ORDER BY s.FechaSolicitud DESC
        ";

        return $this->executeQuery($sql, [], 'all');
    }

    /**
     * Generar carta en formato Word
     */
    public function generarCartaWord($datos, $numeroExpediente, $nombreDirector, $cargoDirector) {
        $phpWord = new PhpWord();
        
        // Configuración de la sección
        $section = $phpWord->addSection([
            'marginLeft' => 1440,
            'marginRight' => 1440,
            'marginTop' => 1440,
            'marginBottom' => 1440,
        ]);

        // Estilos
        $titleStyle = ['bold' => true, 'size' => 12, 'name' => 'Arial'];
        $normalStyle = ['size' => 11, 'name' => 'Arial'];
        $boldStyle = ['bold' => true, 'size' => 11, 'name' => 'Arial'];
        $pieStyle = ['size' => 8, 'name' => 'Arial'];

        // Encabezado
        $section->addText(
            'EL GERENTE DE RECURSOS HUMANOS DE LA MUNICIPALIDAD DISTRITAL DE LA ESPERANZA, EXTIENDE:',
            $titleStyle,
            ['alignment' => Jc::CENTER, 'spaceAfter' => 1000]
        );

        // Título
        $section->addText(
            'CARTA DE ACEPTACIÓN',
            ['bold' => true, 'size' => 20, 'name' => 'Arial', 'underline' => 'single'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 500]
        );

        // Fecha
        $fechaActual = $this->obtenerFechaEnTexto();
        $section->addText($fechaActual, $normalStyle, ['alignment' => 'right', 'spaceAfter' => 240]);

        // Destinatario
        $section->addText(strtoupper($nombreDirector), $boldStyle, ['spaceAfter' => 0]);
        $section->addText(strtoupper($cargoDirector), $boldStyle, ['spaceAfter' => 240]);

        // Cuerpo
        $nombreCompleto = $datos['Nombres'] . ' ' . $datos['ApellidoPaterno'] . ' ' . $datos['ApellidoMaterno'];
        $genero = $datos['Genero'] === 'M' ? "el Sr." : "la Srta.";
        $admitido = $datos['Genero'] === 'M' ? "admitido" : "admitida";
        $identificado = $datos['Genero'] === 'M' ? "identificado" : "identificada";
        
        $textRun = $section->addTextRun(['alignment' => Jc::BOTH, 'spaceAfter' => 120]);
        $textRun->addText('Tengo el agrado de dirigirme a usted con la finalidad de hacer de su conocimiento que ', $normalStyle);
        $textRun->addText($genero . ' ' . strtoupper($nombreCompleto), $boldStyle);
        $textRun->addText(', ' . $identificado . ' con ', $normalStyle);
        $textRun->addText('DNI N° ' . $datos['DNI'], $boldStyle);
        $textRun->addText(' estudiante de la carrera profesional de ', $normalStyle);
        $textRun->addText($datos['Carrera'], $boldStyle);
        $textRun->addText(' de la ' . $datos['Universidad'], $normalStyle);
        $textRun->addText(', ha sido ' . $admitido . ' para que realice ', $normalStyle);
        $textRun->addText('Programa de Voluntariado Municipal', $boldStyle);
        $textRun->addText(', en el Área de ', $normalStyle);
        $textRun->addText($datos['NombreArea'], $boldStyle);
        $textRun->addText('.', $normalStyle);

        // Fechas
        $fechaEntrada = date('d.m.Y', strtotime($datos['FechaEntrada']));
        $fechaSalida = date('d.m.Y', strtotime($datos['FechaSalida']));
        
        $textRun2 = $section->addTextRun(['alignment' => Jc::BOTH, 'spaceAfter' => 240]);
        $textRun2->addText('A partir del día ', $normalStyle);
        $textRun2->addText($fechaEntrada . ' al ' . $fechaSalida, $boldStyle);
        $textRun2->addText(' los días lunes a viernes de 08.00 a.m. a 1.30 p.m.', $boldStyle);

        // Despedida
        $section->addText(
            'Aprovecho la oportunidad para expresarle mi consideración y estima personal.',
            $normalStyle,
            ['alignment' => Jc::BOTH, 'spaceAfter' => 480]
        );

        $section->addText('Atentamente,', $normalStyle, ['spaceAfter' => 1000]);

        // Pie
        $section->addText('VAMG/svv', $pieStyle, ['spaceAfter' => 0]);
        $section->addText('C.c. Archivo', $pieStyle, ['spaceAfter' => 0]);
        $section->addText('Exp. Nº ' . $numeroExpediente, $pieStyle);

        // Guardar archivo
        return $this->guardarArchivo($phpWord, $nombreCompleto, 'docx');
    }

    /**
     * Generar carta en formato PDF
     */
    public function generarCartaPDF($datos, $numeroExpediente, $nombreDirector, $cargoDirector) {
        $nombreCompleto = $datos['Nombres'] . ' ' . $datos['ApellidoPaterno'] . ' ' . $datos['ApellidoMaterno'];
        $fechaEntrada = date('d.m.Y', strtotime($datos['FechaEntrada']));
        $fechaSalida = date('d.m.Y', strtotime($datos['FechaSalida']));
        $fechaActual = $this->obtenerFechaEnTexto();

        $genero = $datos['Genero'] === 'M' ? "el Sr." : "la Srta.";
        $identificado = $datos['Genero'] === 'M' ? "identificado" : "identificada";
        $admitido = $datos['Genero'] === 'M' ? "admitido" : "admitida";

        $html = $this->generarHTMLCarta([
            'nombreCompleto' => $nombreCompleto,
            'genero' => $genero,
            'identificado' => $identificado,
            'admitido' => $admitido,
            'dni' => $datos['DNI'],
            'carrera' => $datos['Carrera'],
            'universidad' => $datos['Universidad'],
            'area' => $datos['NombreArea'],
            'fechaEntrada' => $fechaEntrada,
            'fechaSalida' => $fechaSalida,
            'fechaActual' => $fechaActual,
            'nombreDirector' => $nombreDirector,
            'cargoDirector' => $cargoDirector,
            'numeroExpediente' => $numeroExpediente
        ]);

        // Generar PDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Guardar archivo
        $directorioCartas = __DIR__ . '/../../public/cartas/';
        if (!file_exists($directorioCartas)) {
            mkdir($directorioCartas, 0777, true);
        }

        $anio = date('Y');
        $nombreArchivo = "CARTA_ACEPTACION_{$anio}_{$nombreCompleto}.pdf";
        $nombreArchivo = $this->limpiarNombreArchivo($nombreArchivo);
        $rutaArchivo = $directorioCartas . $nombreArchivo;

        file_put_contents($rutaArchivo, $dompdf->output());

        return [
            'ruta' => $rutaArchivo,
            'nombre' => $nombreArchivo,
            'url' => '/MDEGestorPracs/public/cartas/' . $nombreArchivo
        ];
    }

    // ==================== MÉTODOS PRIVADOS DE UTILIDAD ====================

    /**
     * Guardar archivo Word o PDF
     */
    private function guardarArchivo($phpWord, $nombreCompleto, $extension) {
        $directorioCartas = __DIR__ . '/../../public/cartas/';
        if (!file_exists($directorioCartas)) {
            mkdir($directorioCartas, 0777, true);
        }

        $anio = date('Y');
        $nombreArchivo = "CARTA_ACEPTACION_{$anio}_{$nombreCompleto}.{$extension}";
        $nombreArchivo = $this->limpiarNombreArchivo($nombreArchivo);
        $rutaArchivo = $directorioCartas . $nombreArchivo;

        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($rutaArchivo);

        return [
            'ruta' => $rutaArchivo,
            'nombre' => $nombreArchivo,
            'url' => '/MDEGestorPracs/public/cartas/' . $nombreArchivo
        ];
    }

    /**
     * Generar HTML para PDF
     */
    private function generarHTMLCarta($datos) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; font-size: 11pt; line-height: 1.5; margin: 2cm; }
                .encabezado { text-align: center; font-weight: bold; font-size: 12pt; margin-bottom: 20px; }
                .titulo { text-align: center; font-weight: bold; font-size: 16pt; text-decoration: underline; margin: 20px; }
                .fecha { text-align: right; margin: 20px 0; }
                .destinatario { font-weight: bold; margin: 20px 0; }
                .contenido { text-align: justify; margin: 20px 0; }
                .despedida { margin-top: 40px; }
                .firma { margin-top: 60px; font-size: 8pt; }
                .bold { font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="encabezado">
                EL GERENTE DE RECURSOS HUMANOS DE LA MUNICIPALIDAD DISTRITAL DE LA ESPERANZA, EXTIENDE:
            </div>
            <div class="titulo">CARTA DE ACEPTACIÓN</div>
            <div class="fecha">' . $datos['fechaActual'] . '</div>
            <div class="destinatario">
                ' . strtoupper($datos['nombreDirector']) . '<br>
                ' . strtoupper($datos['cargoDirector']) . '
            </div>
            <div class="contenido">
                Tengo el agrado de dirigirme a usted con la finalidad de hacer de su conocimiento que 
                <span class="bold">' . $datos['genero'] . ' ' . strtoupper($datos['nombreCompleto']) . '</span>, 
                ' . $datos['identificado'] . ' con <span class="bold">DNI N° ' . $datos['dni'] . '</span> 
                estudiante de la carrera profesional de <span class="bold">' . $datos['carrera'] . '</span> 
                de la ' . $datos['universidad'] . ', ha sido ' . $datos['admitido'] . ' para que realice 
                <span class="bold">Programa de Voluntariado Municipal</span>, en el Área de 
                <span class="bold">' . $datos['area'] . '</span>.
            </div>
            <div class="contenido">
                A partir del día <span class="bold">' . $datos['fechaEntrada'] . ' al ' . $datos['fechaSalida'] . '</span> 
                los días <span class="bold">lunes a viernes de 08.00 a.m. a 1.30 p.m.</span>
            </div>
            <div class="despedida">
                Aprovecho la oportunidad para expresarle mi consideración y estima personal.<br><br>
                Atentamente,<br><br><br>
            </div>
            <div class="firma">
                VAMG/svv<br>
                C.c. Archivo<br>
                Exp. Nº ' . $datos['numeroExpediente'] . '
            </div>
        </body>
        </html>';
    }

    /**
     * Obtener fecha en formato texto
     */
    private function obtenerFechaEnTexto() {
        $meses = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
        ];
        
        $dia = date('d');
        $mes = $meses[(int)date('m')];
        $anio = date('Y');
        
        return "La Esperanza, $dia de $mes del $anio";
    }

    /**
     * Limpiar nombre de archivo
     */
    private function limpiarNombreArchivo($nombre) {
        $nombre = str_replace(' ', '_', $nombre);
        $nombre = preg_replace('/[^A-Za-z0-9_\-.]/', '', $nombre);
        return $nombre;
    }
}