<?php
namespace App\Services;

use App\Repositories\CertificadoRepository;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;
use Dompdf\Dompdf;
use Dompdf\Options;

class CertificadoService extends BaseService {
    
    private const DIRECTORIO_CERTIFICADOS = __DIR__ . '/../../public/certificados/';
    private const URL_BASE_CERTIFICADOS = '/MDEGestorPracs/public/certificados/';
    
    private const MESES = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
    ];
    
    public function __construct() {
        $this->repository = new CertificadoRepository();
    }

    /**
     * Obtener estadísticas de certificados
     */
    public function obtenerEstadisticas() {
        $estadisticas = $this->repository->obtenerEstadisticas();
        
        return $this->successResult([
            'totalVigentes' => (int) ($estadisticas['totalVigentes'] ?? 0),
            'totalFinalizados' => (int) ($estadisticas['totalFinalizados'] ?? 0)
        ], 'Estadísticas obtenidas correctamente');
    }

    /**
     * Listar practicantes elegibles para certificado
     */
    public function listarPracticantesParaCertificado() {
        $practicantes = $this->repository->listarPracticantesParaCertificado();
        
        return $this->successResult(
            $practicantes,
            'Practicantes obtenidos correctamente'
        );
    }

    /**
     * Obtener información completa del practicante para certificado
     */
    public function obtenerInformacionCompleta($practicanteID) {
        // Validaciones
        $this->validateId($practicanteID, 'PracticanteID');
        
        $info = $this->repository->obtenerInformacionCompleta($practicanteID);
        
        if (!$info) {
            throw new \Exception('Practicante no encontrado');
        }
        
        return $this->successResult($info, 'Información obtenida correctamente');
    }

    /**
     * Generar certificado en formato PDF o Word
     */
    public function generarCertificado($practicanteID, $numeroExpediente, $formato) {
        // Validaciones
        $this->validateId($practicanteID, 'PracticanteID');
        $this->validateRequiredFields(
            ['numeroExpediente' => $numeroExpediente],
            ['numeroExpediente']
        );
        $this->validateInList($formato, ['pdf', 'word'], 'Formato');
        
        // Obtener información del practicante
        $info = $this->repository->obtenerInformacionCompleta($practicanteID);
        
        if (!$info) {
            throw new \Exception('Practicante no encontrado');
        }

        // Validaciones de negocio
        $this->validarElegibilidadCertificado($info);

        // Crear directorio si no existe
        $this->crearDirectorioCertificados();

        // Generar nombre del archivo
        $nombreArchivo = $this->generarNombreArchivo($info['NombreCompleto'], $formato);
        $rutaArchivo = self::DIRECTORIO_CERTIFICADOS . $nombreArchivo;

        // Generar certificado según formato
        if ($formato === 'pdf') {
            $this->generarPDF($info, $numeroExpediente, $rutaArchivo);
        } else {
            $this->generarWord($info, $numeroExpediente, $rutaArchivo);
        }

        // Cambiar estado del practicante a Finalizado
        $this->repository->cambiarEstadoAFinalizado($practicanteID);
        
        // Registrar certificado generado
        $this->repository->registrarCertificadoGenerado(
            $practicanteID, 
            $nombreArchivo, 
            $numeroExpediente
        );

        return $this->successResult([
            'nombreArchivo' => $nombreArchivo,
            'url' => self::URL_BASE_CERTIFICADOS . $nombreArchivo
        ], 'Certificado generado exitosamente. El practicante ha sido marcado como Finalizado.');
    }

    /**
     * Obtener historial de certificados generados
     */
    public function obtenerHistorialCertificados($practicanteID = null) {
        if ($practicanteID) {
            $this->validateId($practicanteID, 'PracticanteID');
        }
        
        $historial = $this->repository->obtenerHistorialCertificados($practicanteID);
        
        return $this->successResult(
            $historial,
            'Historial obtenido correctamente'
        );
    }

    /**
     * Validar elegibilidad del practicante para certificado
     */
    private function validarElegibilidadCertificado($info) {
        if (!in_array($info['EstadoAbrev'], ['VIG', 'FIN'])) {
            throw new \Exception('Solo se pueden generar certificados para practicantes vigentes o finalizados');
        }

        if ($info['TotalHoras'] <= 0) {
            throw new \Exception('El practicante no tiene horas acumuladas');
        }

        if (!$info['UltimaAsistencia']) {
            throw new \Exception('El practicante no tiene asistencias registradas');
        }
    }

    /**
     * Crear directorio de certificados si no existe
     */
    private function crearDirectorioCertificados() {
        if (!file_exists(self::DIRECTORIO_CERTIFICADOS)) {
            if (!mkdir(self::DIRECTORIO_CERTIFICADOS, 0777, true)) {
                throw new \Exception('No se pudo crear el directorio de certificados');
            }
        }
    }

    /**
     * Generar nombre del archivo del certificado
     */
    private function generarNombreArchivo($nombreCompleto, $formato) {
        $anio = date('Y');
        $nombreCompleto = strtoupper($this->sanitizeString($nombreCompleto));
        $extension = ($formato === 'pdf') ? 'pdf' : 'docx';
        
        return "CERTIFICADO_{$anio}_{$nombreCompleto}.{$extension}";
    }

    /**
     * Generar certificado en formato Word
     */
    private function generarWord($info, $numeroExpediente, $rutaArchivo) {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'marginLeft' => Converter::cmToTwip(2.5),
            'marginRight' => Converter::cmToTwip(2.5),
            'marginTop' => Converter::cmToTwip(2),
            'marginBottom' => Converter::cmToTwip(2)
        ]);

        // Título
        $section->addText(
            'EL GERENTE DE RECURSOS HUMANOS DE LA MUNICIPALIDAD DISTRITAL DE LA ESPERANZA, QUE SUSCRIBE:',
            ['bold' => true, 'size' => 11],
            ['alignment' => 'center', 'spaceAfter' => 300]
        );

        // CERTIFICA
        $section->addText(
            'CERTIFICA:',
            ['bold' => true, 'size' => 26, 'underline' => 'single'],
            ['alignment' => 'center', 'spaceAfter' => 400]
        );

        // Datos del practicante
        $tratamiento = ($info['Genero'] === 'F') ? 'la SRTA.' : 'el SR.';
        $nombreCompleto = strtoupper($info['NombreCompleto']);
        $carrera = strtoupper($info['Carrera']);
        $universidad = strtoupper($info['Universidad']);
        $area = strtoupper($info['Area'] ?: 'ADMINISTRACIÓN GENERAL');

        // Texto principal
        $textoPrincipal = "Que, {$tratamiento} {$nombreCompleto} identificado con DNI N° {$info['DNI']} " .
                         "estudiante de la Carrera Profesional de {$carrera} en la {$universidad} " .
                         "ha realizado su Voluntariado Municipal en la {$area} en la Municipalidad " .
                         "Distrital de la Esperanza (RUC N° 20164091547).";

        $section->addText(
            $textoPrincipal,
            ['size' => 11],
            ['alignment' => 'both', 'spaceAfter' => 500, 'indentation' => ['firstLine' => Converter::cmToTwip(1)]]
        );

        // MODALIDAD PRESENCIAL
        $section->addText(
            'MODALIDAD PRESENCIAL',
            ['bold' => true, 'size' => 11],
            ['alignment' => 'left', 'spaceAfter' => 120]
        );

        // Fechas y horas
        $fechaInicio = $this->formatearFechaLarga($info['FechaEntrada']);
        $fechaTermino = $this->formatearFechaLarga($info['UltimaAsistencia']);

        $section->addText(
            "INICIO             :  {$fechaInicio}",
            ['bold' => true, 'size' => 11],
            ['alignment' => 'left', 'spaceAfter' => 40]
        );

        $section->addText(
            "TÉRMINO      :  {$fechaTermino}",
            ['bold' => true, 'size' => 11],
            ['alignment' => 'left', 'spaceAfter' => 200]
        );

        $section->addText(
            "HORAS            : {$info['TotalHoras']}",
            ['bold' => true, 'size' => 11],
            ['alignment' => 'left', 'spaceAfter' => 600]
        );

        // Texto de cierre
        $section->addText(
            'Durante su permanencia como voluntario en esta entidad, ha demostrado gran espíritu ' .
            'de colaboración, responsabilidad e identificación, contribuyendo en las actividades ' .
            'encomendadas a satisfacción de esta Comuna.',
            ['size' => 11],
            ['alignment' => 'both', 'spaceAfter' => 480]
        );

        // Fecha actual
        $fechaActual = $this->formatearFechaCompleta();
        $section->addText(
            $fechaActual,
            ['size' => 11],
            ['alignment' => 'right', 'spaceAfter' => 1800]
        );

        // Pie de página
        $section->addText('VAMG/svv', ['size' => 7], ['alignment' => 'left', 'spaceAfter' => 20]);
        $section->addText('Cc. Archivo', ['size' => 7], ['alignment' => 'left', 'spaceAfter' => 20]);
        $section->addText("Exp. N° {$numeroExpediente}", ['size' => 7], ['alignment' => 'left']);

        // Guardar archivo
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($rutaArchivo);
        
        $this->log("Certificado Word generado: {$rutaArchivo}", 'INFO');
    }

    /**
     * Generar certificado en formato PDF
     */
    private function generarPDF($info, $numeroExpediente, $rutaArchivo) {
        // Datos formateados
        $tratamiento = ($info['Genero'] === 'F') ? 'la SRTA.' : 'el SR.';
        $nombreCompleto = strtoupper($info['NombreCompleto']);
        $carrera = strtoupper($info['Carrera']);
        $universidad = strtoupper($info['Universidad']);
        $area = strtoupper($info['Area'] ?: 'ADMINISTRACIÓN GENERAL');
        $fechaInicio = $this->formatearFechaLarga($info['FechaEntrada']);
        $fechaTermino = $this->formatearFechaLarga($info['UltimaAsistencia']);
        $fechaActual = $this->formatearFechaCompleta();

        // HTML del certificado
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                @page {
                    margin: 2.5cm;
                }
                body {
                    font-family: Arial, sans-serif;
                    font-size: 11pt;
                    line-height: 1.6;
                }
                .titulo {
                    font-weight: bold;
                    text-align: center;
                    margin-bottom: 20px;
                }
                .certifica {
                    font-weight: bold;
                    text-decoration: underline;
                    text-align: center;
                    font-size: 25pt;
                    margin-bottom: 20px;
                }
                .texto-principal {
                    text-align: justify;
                    margin-bottom: 20px;
                    text-indent: 1cm;
                }
                .modalidad {
                    font-weight: bold;
                    margin-bottom: 10px;
                }
                .detalles {
                    font-weight: bold;
                    margin-bottom: 5px;
                }
                .cierre {
                    text-align: justify;
                    margin-top: 20px;
                    margin-bottom: 40px;
                }
                .fecha {
                    margin-bottom: 150px;
                    text-align: right;
                }
                .pie {
                    font-size: 7pt;
                    margin-bottom: 0px;
                }
            </style>
        </head>
        <body>
            <p class="titulo">
                EL GERENTE DE RECURSOS HUMANOS DE LA MUNICIPALIDAD DISTRITAL DE LA ESPERANZA, QUE SUSCRIBE:
            </p>
            
            <p class="certifica">CERTIFICA:</p>
            
            <p class="texto-principal">
                Que, ' . htmlspecialchars($tratamiento) . ' ' . htmlspecialchars($nombreCompleto) . ' identificado con DNI N° ' . htmlspecialchars($info['DNI']) . ' 
                estudiante de la Carrera Profesional de ' . htmlspecialchars($carrera) . ' en la ' . htmlspecialchars($universidad) . ' 
                ha realizado su Voluntariado Municipal en la ' . htmlspecialchars($area) . ' en la Municipalidad 
                Distrital de la Esperanza (RUC N° 20164091547).
            </p>
            
            <p class="modalidad">MODALIDAD PRESENCIAL</p>
            
            <p class="detalles">INICIO&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:&nbsp;&nbsp;' . htmlspecialchars($fechaInicio) . '</p>
            <p class="detalles">TÉRMINO&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:&nbsp;&nbsp;' . htmlspecialchars($fechaTermino) . '</p>
            <br>
            <p class="detalles">HORAS&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: ' . htmlspecialchars($info['TotalHoras']) . '</p>
            
            <p class="cierre">
                Durante su permanencia como voluntario en esta entidad, ha demostrado gran espíritu 
                de colaboración, responsabilidad e identificación, contribuyendo en las actividades 
                encomendadas a satisfacción de esta Comuna.
            </p>
            
            <p class="fecha">' . htmlspecialchars($fechaActual) . '</p>
            
            <p class="pie">VAMG/svv</p>
            <p class="pie">Cc. Archivo</p>
            <p class="pie">Exp. N° ' . htmlspecialchars($numeroExpediente) . '</p>
        </body>
        </html>';

        // Configurar Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Guardar el PDF
        if (!file_put_contents($rutaArchivo, $dompdf->output())) {
            throw new \Exception('No se pudo guardar el archivo PDF');
        }
        
        $this->log("Certificado PDF generado: {$rutaArchivo}", 'INFO');
    }

    /**
     * Formatear fecha larga (DD.MM.YYYY)
     */
    private function formatearFechaLarga($fecha) {
        if (!$fecha) return '';

        // Extraer solo YYYY-MM-DD
        $fechaSolo = substr($fecha, 0, 10);
        [$y, $m, $d] = explode('-', $fechaSolo);
        
        return sprintf('%02d.%02d.%s', $d, $m, $y);
    }

    /**
     * Formatear fecha completa (La Esperanza, DD de mes de YYYY)
     */
    private function formatearFechaCompleta() {
        $dia = date('d');
        $mes = self::MESES[(int)date('m')];
        $anio = date('Y');
        
        return "La Esperanza, {$dia} de {$mes} de {$anio}";
    }
}