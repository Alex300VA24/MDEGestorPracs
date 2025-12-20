<?php
namespace App\Repositories;

use App\Config\Database;
use App\Models\SolicitudPracticas;
use PDO;
use PDOException;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\SimpleType\Jc;
use Dompdf\Dompdf;
use Dompdf\Options;

class SolicitudRepository {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function obtenerDocumentosPorPracticante($practicanteID) {
        $stmt = $this->conn->prepare("EXEC sp_ObtenerDocumentosPorPracticante :id");
        $stmt->bindValue(':id', $practicanteID, PDO::PARAM_INT);
        $stmt->execute();

        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Convertir binario a Base64
            if (isset($row['Archivo'])) {
                $row['Archivo'] = base64_encode($row['Archivo']);
            }
            $result[] = $row;
        }

        return $result;
    }

    public function obtenerDocumentoPorTipoYPracticante($practicanteID, $tipoDocumento)
    {
        $sql = "EXEC sp_ObtenerDocumentoPorTipoYPracticante ?, ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$practicanteID, $tipoDocumento]);

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fila && isset($fila['Archivo'])) {
            // Convertir binario a base64
            $fila['Archivo'] = base64_encode($fila['Archivo']);
        }

        return $fila;
    }

    public function obtenerDocumentoPorTipoYSolicitud($solicitudID, $tipoDocumento)
    {
        try {
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
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':solicitudID', $solicitudID, PDO::PARAM_INT);
            $stmt->bindParam(':tipoDocumento', $tipoDocumento, PDO::PARAM_STR);
            $stmt->execute();

            $fila = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($fila && isset($fila['Archivo'])) {
                // Convertir binario a base64
                $fila['Archivo'] = base64_encode($fila['Archivo']);
                
                // Formatear fecha
                if (isset($fila['FechaSubida'])) {
                    $fila['FechaSubida'] = date('d/m/Y', strtotime($fila['FechaSubida']));
                }
            }

            return $fila;

        } catch (PDOException $e) {
            error_log("Error en obtenerDocumentoPorTipoYSolicitud: " . $e->getMessage());
            return null;
        }
    }

    public function crearSolicitud($practicanteID) {
        $sql = "EXEC sp_CrearSolicitud ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(1, (int)$practicanteID, PDO::PARAM_INT);

        if ($stmt->execute()) {
            // Obtener el ID recién creado (asumiendo que el SP hace un SELECT SCOPE_IDENTITY())
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row['SolicitudID'] : null;
        }

        return null;
    }

    // =====================================
// 5️⃣ AGREGAR EN SolicitudRepository.php
// =====================================

/**
 * Obtener última solicitud del practicante (cualquier estado)
 */
public function obtenerUltimaSolicitudPorPracticante($practicanteID) {
    try {
        $query = "
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

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':practicanteID', $practicanteID, PDO::PARAM_INT);
        $stmt->execute();

        $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);

        return $solicitud ?: null;
        
    } catch (PDOException $e) {
        throw new \Exception('Error al obtener última solicitud: ' . $e->getMessage());
    }
}

/**
 * Migrar documentos de una solicitud a otra
 * 🔑 FUNCIÓN CLAVE: Transfiere documentos sin duplicar
 */
public function migrarDocumentos($solicitudOrigenID, $solicitudDestinoID) {
    try {
        error_log("🔄 Iniciando migración de documentos: {$solicitudOrigenID} → {$solicitudDestinoID}");
        
        // 1️⃣ Verificar que existan documentos en la solicitud origen
        $queryCheck = "
            SELECT COUNT(*) as Total
            FROM DocumentoSolicitud
            WHERE SolicitudID = :solicitudOrigenID
        ";
        
        $stmtCheck = $this->conn->prepare($queryCheck);
        $stmtCheck->bindParam(':solicitudOrigenID', $solicitudOrigenID, PDO::PARAM_INT);
        $stmtCheck->execute();
        $result = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        
        if ($result['Total'] == 0) {
            error_log("ℹ️ No hay documentos para migrar de solicitud #{$solicitudOrigenID}");
            return 0;
        }
        
        error_log("📄 Documentos encontrados para migrar: {$result['Total']}");
        
        // 2️⃣ Actualizar los documentos cambiando el SolicitudID
        // Esto MUEVE los documentos, no los copia
        $queryUpdate = "
            UPDATE DocumentoSolicitud
            SET SolicitudID = :solicitudDestinoID,
                FechaSubida = GETDATE()
            WHERE SolicitudID = :solicitudOrigenID
        ";
        
        $stmtUpdate = $this->conn->prepare($queryUpdate);
        $stmtUpdate->bindParam(':solicitudDestinoID', $solicitudDestinoID, PDO::PARAM_INT);
        $stmtUpdate->bindParam(':solicitudOrigenID', $solicitudOrigenID, PDO::PARAM_INT);
        $stmtUpdate->execute();
        
        $documentosMigrados = $stmtUpdate->rowCount();
        
        error_log("✅ Documentos migrados exitosamente: {$documentosMigrados}");
        
        return $documentosMigrados;
        
    } catch (PDOException $e) {
        error_log("❌ Error al migrar documentos: " . $e->getMessage());
        throw new \Exception('Error al migrar documentos: ' . $e->getMessage());
    }
}


    public function subirDocumento($id, $tipo, $archivo, $observaciones = null) {
        $sql = "EXEC sp_SubirDocumento ?, ?, ?, ?";
        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(1, (int)$id, PDO::PARAM_INT);                     // @SolicitudID
        $stmt->bindValue(2, $tipo, PDO::PARAM_STR);                        // @TipoDocumento
        $stmt->bindParam(3, $archivo, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY); // @Archivo
        $stmt->bindValue(4, $observaciones, PDO::PARAM_STR);               // @Observaciones (puede ser NULL)

        $res = $stmt->execute();
        if ($res === false) {
            $err = $stmt->errorInfo();
            throw new \Exception("Ejecutar SP falló: " . json_encode($err));
        }

        return $res;
    }

    public function actualizarDocumento($solicitudID, $tipoDocumento, $archivo = null, $observaciones = null)
    {
        // Si hay archivo, incluimos el parámetro @Archivo
        if ($archivo !== null) {
            $sql = "EXEC sp_ActualizarDocumento @SolicitudID = ?, @TipoDocumento = ?, @Archivo = ?, @Observaciones = ?";
        } else {
            // Sin archivo, lo excluimos completamente
            $sql = "EXEC sp_ActualizarDocumento @SolicitudID = ?, @TipoDocumento = ?, @Observaciones = ?";
        }

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(1, (int)$solicitudID, PDO::PARAM_INT);
        $stmt->bindValue(2, $tipoDocumento, PDO::PARAM_STR);

        if ($archivo !== null) {
            $stmt->bindParam(3, $archivo, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
            $stmt->bindValue(4, $observaciones, PDO::PARAM_STR);
        } else {
            $stmt->bindValue(3, $observaciones, PDO::PARAM_STR);
        }

        if (!$stmt->execute()) {
            $err = $stmt->errorInfo();
            throw new \Exception("Error al ejecutar spActualizarDocumento: " . json_encode($err));
        }

        return $stmt->rowCount() > 0;
    }


    // Agregar a SolicitudRepository

    public function obtenerSolicitudPorPracticante($practicanteID) {
        $stmt = $this->conn->prepare("
            SELECT TOP 1 * 
            FROM SolicitudPracticas 
            WHERE PracticanteID = ? 
            ORDER BY FechaSolicitud DESC
        ");
        $stmt->execute([$practicanteID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    public function obtenerSolicitudPorID($id) {
        $sql = "SELECT s.solicitudID, s.fechaSolicitud, s.estadoID, 
                    a.NombreArea AS areaNombre, 
                    p.nombres AS practicanteNombre
                FROM SolicitudPracticas s
                INNER JOIN area a ON s.areaID = a.areaID
                INNER JOIN practicante p ON s.practicanteID = p.practicanteID
                WHERE s.solicitudID = :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Agregar a SolicitudRepository

    public function eliminarDocumento($documentoID)
    {
        try {
            // 🔹 1. Eliminar el documento
            $sql = "DELETE FROM DocumentoSolicitud WHERE DocumentoID = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $documentoID, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {

                // 🔹 2. Obtener el máximo DocumentoID restante
                $sqlMax = "SELECT ISNULL(MAX(DocumentoID), 0) AS MaxID FROM DocumentoSolicitud";
                $stmtMax = $this->conn->prepare($sqlMax);
                $stmtMax->execute();
                $row = $stmtMax->fetch(PDO::FETCH_ASSOC);
                $maxID = $row['MaxID'];

                // 🔹 3. Reiniciar el IDENTITY según el valor máximo actual
                $sqlReseed = "DBCC CHECKIDENT ('DocumentoSolicitud', RESEED, $maxID)";
                $this->conn->exec($sqlReseed);

                return [
                    'success' => true,
                    'message' => 'Documento eliminado'
                ];

            } else {
                return [
                    'success' => false,
                    'message' => 'No se encontró el documento o ya fue eliminado.'
                ];
            }

        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ];
        }
    }



    public function obtenerEstado($solicitudID) {
        $stmt = $this->conn->prepare("
            SELECT e.Abreviatura, e.Descripcion
            FROM SolicitudPracticas sp
            INNER JOIN Estado e ON sp.EstadoID = e.EstadoID
            WHERE sp.SolicitudID = ?
        ");
        $stmt->execute([$solicitudID]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Devolvemos abreviatura y descripción
            return [
                'abreviatura' => $row['Abreviatura'],
                'descripcion' => $row['Descripcion']
            ];
        }
        return null;
    }

    /**
     * Obtener datos necesarios para generar la carta de aceptación
     */
    public function obtenerDatosParaCarta($solicitudID) {
        try {
            $query = "SELECT 
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
                      AND e.Abreviatura = 'APR'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':solicitudID', $solicitudID, PDO::PARAM_INT);
            $stmt->execute();
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Validar que existan los datos mínimos requeridos
            if ($resultado) {
                // Verificar que tenga fechas de entrada y salida
                if (empty($resultado['FechaEntrada']) || empty($resultado['FechaSalida'])) {
                    error_log("Advertencia: Practicante sin fechas asignadas para solicitud ID: $solicitudID");
                    return null;
                }
            }
            
            return $resultado;
            
        } catch (PDOException $e) {
            error_log("Error al obtener datos para carta: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generar carta de aceptación en formato Word
     */
    public function generarCartaWord($datos, $numeroExpediente, $nombreDirector, $cargoDirector) {
        $phpWord = new PhpWord();
        
        // Configuración de la sección
        $section = $phpWord->addSection([
            'marginLeft' => 1440,    // 1 pulgada
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

        // Título del documento
        $section->addText(
            'CARTA DE ACEPTACIÓN',
            ['bold' => true, 'size' => 20, 'name' => 'Arial', 'underline' => 'single'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 500]
        );

        // Fecha
        $fechaActual = $this->obtenerFechaEnTexto();
        $section->addText(
            $fechaActual,
            $normalStyle,
            ['alignment' => 'right', 'spaceAfter' => 240]
        );

        // Destinatario (ahora dinámico)
        $section->addText(strtoupper($nombreDirector), $boldStyle, ['spaceAfter' => 0]);
        $section->addText(strtoupper($cargoDirector), $boldStyle, ['spaceAfter' => 240]);

        // Cuerpo del documento
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

        // Fechas y horario
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

        // Pie de página
        $section->addText('VAMG/svv', $pieStyle, ['spaceAfter' => 0]);
        $section->addText('C.c. Archivo', $pieStyle, ['spaceAfter' => 0]);
        $section->addText('Exp. Nº ' . $numeroExpediente, $pieStyle);

        // Crear directorio si no existe
        $directorioCartas = __DIR__ . '/../../public/cartas/';
        if (!file_exists($directorioCartas)) {
            mkdir($directorioCartas, 0777, true);
        }

        // Nombre del archivo
        $anio = date('Y');
        $nombreArchivo = "CARTA_ACEPTACION_{$anio}_{$nombreCompleto}.docx";
        $nombreArchivo = $this->limpiarNombreArchivo($nombreArchivo);
        $rutaArchivo = $directorioCartas . $nombreArchivo;

        // Guardar el documento
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($rutaArchivo);

        return [
            'ruta' => $rutaArchivo,
            'nombre' => $nombreArchivo,
            'url' => '/MDEGestorPracs/public/cartas/' . $nombreArchivo
        ];
    }

    /**
     * Generar carta de aceptación en formato PDF
     */
    public function generarCartaPDF($datos, $numeroExpediente, $nombreDirector, $cargoDirector) {
        error_log('Si se llamo a la funcion generar carta pdf');
        $nombreCompleto = $datos['Nombres'] . ' ' . $datos['ApellidoPaterno'] . ' ' . $datos['ApellidoMaterno'];
        error_log("Si hay nombre completo: " . $nombreCompleto);
        $fechaEntrada = date('d.m.Y', strtotime($datos['FechaEntrada']));
        error_log("Si hay fecha de entrada: " . $fechaEntrada);
        $fechaSalida = date('d.m.Y', strtotime($datos['FechaSalida']));
        error_log("Si hay fecha de salida: " . $fechaSalida);
        $fechaActual = $this->obtenerFechaEnTexto();
        error_log("Si hay fecha actual: ". $fechaActual);

        error_log("Datos obtenidos: " . print_r($datos, true));
        error_log("Este es el numero de expediente: " . $numeroExpediente);
        error_log("Nombre del director: " . $nombreDirector);
        error_log("Cargo del director: " . $cargoDirector);

        $genero = $datos['Genero'] === 'M' ? "el Sr." : "la Srta.";
        $identificado = $datos['Genero'] === 'M' ? "identificado" : "identificada";
        $admitido = $datos['Genero'] === 'M' ? "admitido" : "admitida";

        // HTML para el PDF
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    font-size: 11pt;
                    line-height: 1.5;
                    margin: 2cm;
                }
                .encabezado {
                    text-align: center;
                    font-weight: bold;
                    font-size: 12pt;
                    margin-bottom: 20px;
                }
                .titulo {
                    text-align: center;
                    font-weight: bold;
                    font-size: 16pt;
                    text-decoration: underline;
                    margin: 20px 20px;
                }
                .fecha {
                    text-align: right;
                    margin: 20px 0;
                }
                .destinatario {
                    font-weight: bold;
                    margin: 20px 0;
                }
                .contenido {
                    text-align: justify;
                    margin: 20px 0;
                }
                .despedida {
                    margin-top: 40px;
                }
                .firma {
                    margin-top: 60px;
                    font-size: 8pt;
                }
                .bold {
                    font-weight: bold;
                }
            </style>
        </head>
        <body>
            <div class="encabezado">
                EL GERENTE DE RECURSOS HUMANOS DE LA MUNICIPALIDAD DISTRITAL DE LA
                ESPERANZA, EXTIENDE:
            </div>
            
            <div class="titulo">
                CARTA DE ACEPTACIÓN
            </div>
            
            <div class="fecha">
                ' . $fechaActual . '
            </div>
            
            <div class="destinatario">
                ' . strtoupper($nombreDirector) . '<br>
                ' . strtoupper($cargoDirector) . '
            </div>
            
            <div class="contenido">
                Tengo el agrado de dirigirme a usted con la finalidad de hacer de su 
                conocimiento que <span class="bold"> ' . $genero . ' ' . strtoupper($nombreCompleto) . '</span>, 
                ' . $identificado . ' con <span class="bold">DNI N° ' . $datos['DNI'] . '</span> 
                estudiante de la carrera profesional de <span class="bold">' . $datos['Carrera'] . '</span> 
                de la ' . $datos['Universidad'] . ', ha sido ' . $admitido . ' para que realice 
                <span class="bold">Programa de Voluntariado Municipal</span>, en el Área de 
                <span class="bold">' . $datos['NombreArea'] . '</span>.
            </div>
            
            <div class="contenido">
                A partir del día <span class="bold">' . $fechaEntrada . ' al ' . $fechaSalida . '</span> 
                los días <span class="bold">lunes a viernes de 08.00 a.m. a 1.30 p.m.</span>
            </div>
            
            <div class="despedida">
                Aprovecho la oportunidad para expresarle mi consideración y estima personal.<br><br>
                Atentamente,
                <br><br><br>
            </div>
            
            <div class="firma">
                VAMG/svv<br>
                C.c. Archivo<br>
                Exp. Nº ' . $numeroExpediente . '
            </div>
        </body>
        </html>';

        // Configurar Dompdf
        try {
            error_log('Antes de instanciar options');
            $options = new Options();
            error_log("Se instancion bien options: " . print_r($options, true));
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Arial');

            $dompdf = new Dompdf($options);
            error_log("Se instancion bien dompdf: " . print_r($dompdf, true));
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            error_log('Se configuró el dompdf correctamente');

        } catch (\Exception $e) {
            error_log("ERROR DOMPDF: " . $e->getMessage());
            error_log("TRACE: " . $e->getTraceAsString());
        }

        // Crear directorio si no existe
        $directorioCartas = __DIR__ . '/../../public/cartas/';
        if (!file_exists($directorioCartas)) {
            mkdir($directorioCartas, 0777, true);
        }

        // Nombre del archivo
        $anio = date('Y');
        $nombreArchivo = "CARTA_ACEPTACION_{$anio}_{$nombreCompleto}.pdf";
        $nombreArchivo = $this->limpiarNombreArchivo($nombreArchivo);
        $rutaArchivo = $directorioCartas . $nombreArchivo;

        // Guardar el PDF
        file_put_contents($rutaArchivo, $dompdf->output());

        return [
            'ruta' => $rutaArchivo,
            'nombre' => $nombreArchivo,
            'url' => '/MDEGestorPracs/public/cartas/' . $nombreArchivo
        ];
    }

    /**
     * Obtener fecha actual en formato texto español
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
     * Limpiar nombre de archivo eliminando caracteres especiales
     */
    private function limpiarNombreArchivo($nombre) {
        $nombre = str_replace(' ', '_', $nombre);
        $nombre = preg_replace('/[^A-Za-z0-9_\-.]/', '', $nombre);
        return $nombre;
    }

    /**
     * Verificar si una solicitud está aprobada y puede generar carta
     */
    public function verificarSolicitudAprobada($solicitudID) {
        try {
            $query = "SELECT 
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
                      WHERE s.SolicitudID = :solicitudID";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':solicitudID', $solicitudID, PDO::PARAM_INT);
            $stmt->execute();
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$resultado) {
                return [
                    'valido' => false,
                    'mensaje' => 'Solicitud no encontrada'
                ];
            }
            
            if ($resultado['Abreviatura'] !== 'APR') {
                return [
                    'valido' => false,
                    'mensaje' => 'La solicitud debe estar en estado Aprobado para generar la carta',
                    'estadoActual' => $resultado['Descripcion']
                ];
            }
            
            return [
                'valido' => true,
                'mensaje' => 'Solicitud válida para generar carta',
                'practicante' => trim($resultado['Nombres'] . ' ' . $resultado['ApellidoPaterno'] . ' ' . $resultado['ApellidoMaterno'])
            ];
            
        } catch (PDOException $e) {
            error_log("Error al verificar solicitud: " . $e->getMessage());
            return [
                'valido' => false,
                'mensaje' => 'Error al verificar la solicitud'
            ];
        }
    }

    /**
     * Obtener lista de solicitudes aprobadas para mostrar en interfaz
     */
    public function listarSolicitudesAprobadas() {
        try {
            $query = "SELECT 
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
                      ORDER BY s.FechaSolicitud DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error al listar solicitudes aprobadas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener solicitud activa del practicante
     * Estados activos: PEN, REV, APR
     */
    public function obtenerSolicitudActivaPorPracticante($practicanteID) {
        try {
            $query = "
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

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':practicanteID', $practicanteID, PDO::PARAM_INT);
            $stmt->execute();

            $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);

            return $solicitud ?: null;
            
        } catch (PDOException $e) {
            throw new \Exception('Error al obtener solicitud activa: ' . $e->getMessage());
        }
    }

    /**
     * Verificar si el practicante tiene solicitudes activas
     */
    public function tieneSolicitudesActivas($practicanteID) {
        try {
            $query = "
                SELECT COUNT(*) AS Total
                FROM SolicitudPracticas s
                INNER JOIN Estado e ON s.EstadoID = e.EstadoID
                WHERE s.PracticanteID = :practicanteID
                    AND e.Abreviatura IN ('PEN', 'REV', 'APR')
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':practicanteID', $practicanteID, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['Total'] > 0;
            
        } catch (PDOException $e) {
            throw new \Exception('Error al validar solicitudes activas: ' . $e->getMessage());
        }
    }

    /**
     * Crear nueva solicitud
     */
    public function crearSolicitudMultiple($practicanteID, $estadoID) {
        try {
            $query = "
                INSERT INTO SolicitudPracticas (FechaSolicitud, EstadoID, PracticanteID, AreaID)
                OUTPUT INSERTED.SolicitudID
                VALUES (GETDATE(), :estadoID, :practicanteID, NULL)
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':estadoID', $estadoID, PDO::PARAM_INT);
            $stmt->bindParam(':practicanteID', $practicanteID, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['SolicitudID'];
            
        } catch (PDOException $e) {
            throw new \Exception('Error al crear solicitud: ' . $e->getMessage());
        }
    }

    /**
     * Obtener historial completo de solicitudes del practicante
     */
    public function obtenerHistorialPorPracticante($practicanteID) {
        try {
            $query = "
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

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':practicanteID', $practicanteID, PDO::PARAM_INT);
            $stmt->execute();

            $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $solicitudes;
            
        } catch (PDOException $e) {
            throw new \Exception('Error al obtener historial: ' . $e->getMessage());
        }
    }

    public function obtenerDocumentosSolicitudActiva($practicanteID) {
        try {
            $query = "
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

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':practicanteID', $practicanteID, PDO::PARAM_INT);
            $stmt->execute();

            $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $documentos;

        } catch (PDOException $e) {
            throw new \Exception('Error al obtener documentos: ' . $e->getMessage());
        }
    }

    /**
     * Obtener documento por ID
     */
    public function obtenerPorID($documentoID) {
        try {
            $query = "
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

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':documentoID', $documentoID, PDO::PARAM_INT);
            $stmt->execute();

            $documento = $stmt->fetch(PDO::FETCH_ASSOC);

            return $documento ?: null;

        } catch (PDOException $e) {
            throw new \Exception('Error al obtener documento: ' . $e->getMessage());
        }
    }

    /**
     * Obtener todos los documentos de una solicitud específica
     */
    public function obtenerPorSolicitud($solicitudID) {
        try {
            $query = "
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

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':solicitudID', $solicitudID, PDO::PARAM_INT);
            $stmt->execute();

            $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $documentos;

        } catch (PDOException $e) {
            throw new \Exception('Error al obtener documentos por solicitud: ' . $e->getMessage());
        }
    }

    /**
     * Crear nuevo documento
     */
    public function crearDocumento($solicitudID, $tipoDocumento, $archivo, $observaciones = null) {
        try {
            $query = "
                INSERT INTO DocumentoSolicitud (SolicitudID, TipoDocumento, Archivo, Observaciones, FechaSubida)
                OUTPUT INSERTED.DocumentoID
                VALUES (:solicitudID, :tipoDocumento, CONVERT(VARBINARY(MAX), :archivo, 1), :observaciones, GETDATE())
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':solicitudID', $solicitudID, PDO::PARAM_INT);
            $stmt->bindParam(':tipoDocumento', $tipoDocumento, PDO::PARAM_STR);
            $stmt->bindParam(':archivo', $archivo, PDO::PARAM_STR);
            $stmt->bindParam(':observaciones', $observaciones, PDO::PARAM_STR);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['DocumentoID'];

        } catch (PDOException $e) {
            throw new \Exception('Error al crear documento: ' . $e->getMessage());
        }
    }

}
