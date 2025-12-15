<?php
namespace App\Services;

use App\Repositories\CertificadoRepository;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;
use Dompdf\Dompdf;
use Dompdf\Options;

class CertificadoService {
    private $repository;
    
    public function __construct() {
        $this->repository = new CertificadoRepository();
    }

    public function obtenerEstadisticas() {
        return $this->repository->obtenerEstadisticas();
    }

    public function listarPracticantesParaCertificado() {
        return $this->repository->listarPracticantesParaCertificado();
    }

    public function obtenerInformacionCompleta($practicanteID) {
        return $this->repository->obtenerInformacionCompleta($practicanteID);
    }

    public function generarCertificado($practicanteID, $numeroExpediente, $formato) {
        // Obtener información del practicante
        $info = $this->repository->obtenerInformacionCompleta($practicanteID);
        
        if (!$info) {
            throw new \Exception('Practicante no encontrado');
        }

        if ($info['EstadoAbrev'] !== 'VIG' && $info['EstadoAbrev'] !== 'FIN') {
            throw new \Exception('Solo se pueden generar certificados para practicantes vigentes o finalizados');
        }

        // Verificar que tenga horas acumuladas
        if ($info['TotalHoras'] <= 0) {
            throw new \Exception('El practicante no tiene horas acumuladas');
        }

        // Verificar que tenga al menos una asistencia registrada
        if (!$info['UltimaAsistencia']) {
            throw new \Exception('El practicante no tiene asistencias registradas');
        }

        // Generar nombre del archivo
        $anio = date('Y');
        $nombreCompleto = strtoupper($info['NombreCompleto']);
        
        // Crear directorio si no existe
        $rutaCertificados = __DIR__ . '/../../public/certificados/';
        if (!file_exists($rutaCertificados)) {
            mkdir($rutaCertificados, 0777, true);
        }

        $nombreArchivo = "CERTIFICADO {$anio} {$nombreCompleto}";
        
        if ($formato === 'pdf') {
            $nombreArchivo .= '.pdf';
            $rutaArchivo = $rutaCertificados . $nombreArchivo;
            $this->generarPDF($info, $numeroExpediente, $rutaArchivo);
        } else {
            $nombreArchivo .= '.docx';
            $rutaArchivo = $rutaCertificados . $nombreArchivo;
            $this->generarWord($info, $numeroExpediente, $rutaArchivo);
        }

        // Cambiar estado del practicante a Finalizado (usa la última fecha de asistencia)
        $this->repository->cambiarEstadoAFinalizado($practicanteID);


        return [
            'success' => true,
            'message' => 'Certificado generado exitosamente. El practicante ha sido marcado como Finalizado.',
            'nombreArchivo' => $nombreArchivo,
            'url' => '/gestorPracticantes/public/certificados/' . $nombreArchivo
        ];
    }

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

        // Determinar género
        $tratamiento = ($info['Genero'] === 'F') ? 'la SRTA.' : 'el SR.';

        // Texto principal
        $nombreCompleto = strtoupper($info['NombreCompleto']);
        $carrera = strtoupper($info['Carrera']);
        $universidad = strtoupper($info['Universidad']);
        $area = strtoupper($info['Area'] ?: 'ADMINISTRACIÓN GENERAL');

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
        // Usar la última fecha de asistencia como fecha de término
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
        $section->addText(
            'VAMG/svv',
            ['size' => 7],
            ['alignment' => 'left', 'spaceAfter' => 20]
        );

        $section->addText(
            'Cc. Archivo',
            ['size' => 7],
            ['alignment' => 'left', 'spaceAfter' => 20]
        );

        $section->addText(
            "Exp. N° {$numeroExpediente}",
            ['size' => 7],
            ['alignment' => 'left']
        );

        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($rutaArchivo);
    }

    private function generarPDF($info, $numeroExpediente, $rutaArchivo) {
        // Determinar género
        $tratamiento = ($info['Genero'] === 'F') ? 'la SRTA.' : 'el SR.';
        
        // Datos formateados
        $nombreCompleto = strtoupper($info['NombreCompleto']);
        $carrera = strtoupper($info['Carrera']);
        $universidad = strtoupper($info['Universidad']);
        $area = strtoupper($info['Area'] ?: 'ADMINISTRACIÓN GENERAL');
        $fechaInicio = $this->formatearFechaLarga($info['FechaEntrada']);
        // Usar la última fecha de asistencia como fecha de término
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
                .center {
                    text-align: center;
                }
                .bold {
                    font-weight: bold;
                }
                .underline {
                    text-decoration: underline;
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
                Que, ' . $tratamiento . ' ' . $nombreCompleto . ' identificado con DNI N° ' . $info['DNI'] . ' 
                estudiante de la Carrera Profesional de ' . $carrera . ' en la ' . $universidad . ' 
                ha realizado su Voluntariado Municipal en la ' . $area . ' en la Municipalidad 
                Distrital de la Esperanza (RUC N° 20164091547).
            </p>
            
            <p class="modalidad">MODALIDAD PRESENCIAL</p>
            
            <p class="detalles">INICIO&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:&nbsp;&nbsp;' . $fechaInicio . '</p>
            <p class="detalles">TÉRMINO&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:&nbsp;&nbsp;' . $fechaTermino . '</p>
            <br>
            <p class="detalles">HORAS&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: ' . $info['TotalHoras'] . '</p>
            
            <p class="cierre">
                Durante su permanencia como voluntario en esta entidad, ha demostrado gran espíritu 
                de colaboración, responsabilidad e identificación, contribuyendo en las actividades 
                encomendadas a satisfacción de esta Comuna.
            </p>
            
            <p class="fecha">' . $fechaActual . '</p>
            
            <p class="pie">VAMG/svv</p>
            <p class="pie">Cc. Archivo</p>
            <p class="pie">Exp. N° ' . $numeroExpediente . '</p>
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
        file_put_contents($rutaArchivo, $dompdf->output());
    }

    private function formatearFechaLarga($fecha) {
        if (!$fecha) return '';

        // Manejar tanto formato DateTime (YYYY-MM-DD HH:MM:SS) como Date (YYYY-MM-DD)
        $fechaSolo = substr($fecha, 0, 10); // Extraer solo YYYY-MM-DD
        [$y, $m, $d] = explode('-', $fechaSolo);
        
        return "$d.$m.$y";
    }

    private function formatearFechaCompleta() {
        $meses = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
        ];
        
        $dia = date('d');
        $mes = $meses[(int)date('m')];
        $anio = date('Y');
        
        return "La Esperanza, {$dia} de {$mes} de {$anio}";
    }
}