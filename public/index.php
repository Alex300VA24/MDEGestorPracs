<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use App\Core\Request;
use App\Middleware\SecurityMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\AuthMiddleware;

date_default_timezone_set('America/Lima');
$config = require __DIR__ . '/../config/app.php';

error_reporting(E_ALL);
ini_set('display_startup_errors', $config['debug'] ? '1' : '0');
ini_set('display_errors', $config['debug'] ? '1' : '0');
ini_set('log_errors', '1');

SecurityMiddleware::initialize($config);

$request = new Request();
$router = new Router();

// Middleware global
$router->addMiddleware(new SecurityMiddleware());
$router->addMiddleware(new CsrfMiddleware());

// ============================================
// RUTAS PÚBLICAS
// ============================================
$router->get('/api/csrf-token', function() {
    header('Content-Type: application/json');
    echo json_encode(['token' => $_SESSION['csrf_token'] ?? '']);
});

$router->post('/api/login', 'UsuarioController@login', ['skip_csrf' => true]);
$router->post('/api/validar-cui', 'UsuarioController@validarCUI');
$router->post('/api/logout', 'UsuarioController@logout');

// ============================================
// RUTAS PROTEGIDAS
// ============================================
$router->group(['middleware' => AuthMiddleware::class], function($router) {
    
    $router->get('/api/inicio', 'DashboardController@obtenerDatosInicio');
    
    // PRACTICANTES 
    $router->group(['prefix' => '/api/practicantes'], function($router) {
        $router->get('', 'PracticanteController@listarPracticantes');  
        $router->post('', 'PracticanteController@registrarPracticante');  
        $router->post('/filtrar', 'PracticanteController@filtrarPracticantes');
        $router->post('/aceptar', 'PracticanteController@aceptarPracticante');
        $router->post('/rechazar', 'PracticanteController@rechazarPracticante');
        $router->get('/listar-nombres', 'PracticanteController@listarNombresPracticantes');
        $router->get('/{id}', 'PracticanteController@obtener');
        $router->put('/{id}', 'PracticanteController@actualizar');
        $router->delete('/{id}', 'PracticanteController@eliminar');
    });
    
    // DOCUMENTOS /SOLICITUDES
    $router->group(['prefix' => '/api/solicitudes'], function($router) {
        $router->get('/por-practicante', 'SolicitudController@obtenerSolicitudPorPracticante');
        $router->get('/documentos', 'SolicitudController@obtenerDocumentosPorPracticante');
        $router->post('/crearSolicitud', 'SolicitudController@crearSolicitud');
        $router->post('/subirDocumento', 'SolicitudController@subirDocumento');
        $router->put('/actualizarDocumento', 'SolicitudController@actualizarDocumento');
        $router->get('/obtenerPorTipoYPracticante', 'SolicitudController@obtenerDocumentoPorTipoYPracticante');
        $router->get('/obtenerSolicitud', 'SolicitudController@obtenerSolicitudPorID');
        $router->delete('/eliminarDocumento', 'SolicitudController@eliminarDocumento');
        $router->post('/generarCartaAceptacion', 'SolicitudController@generarCartaAceptacion');
        $router->get('/verificarSolicitudParaCarta', 'SolicitudController@verificarSolicitudParaCarta');
        $router->get('/listarSolicitudesAprobadas', 'SolicitudController@listarSolicitudesAprobadas');
        $router->post('/crear', 'SolicitudController@crearNuevaSolicitud');
        $router->get('/obtenerPorTipoYSolicitud', 'SolicitudController@obtenerDocumentoPorTipoYSolicitud');
        $router->get('/estado/{id}', 'SolicitudController@verificarEstado');
        $router->get('/activa/{id}', 'SolicitudController@obtenerSolicitudActiva');
        $router->get('/historial/{id}', 'SolicitudController@obtenerHistorialSolicitudes');
    });
    
    // ✅ MENSAJES
    $router->group(['prefix' => '/api/mensajes'], function($router) {
        $router->post('/enviar', 'MensajeController@enviarSolicitud');
        $router->post('/responder', 'MensajeController@responderSolicitud');
        $router->get('/{id}', 'MensajeController@listarMensajes');
        $router->delete('/{id}', 'MensajeController@eliminarMensaje');
    });
    
    // ✅ ÁREAS Y TURNOS
    $router->get('/api/areas', 'AreaController@listar');
    $router->get('/api/turnos', 'TurnoController@listar');
    $router->get('/api/turnos/practicante/{id}', 'TurnoController@obtenerPorPracticante');
    
    // ✅ ASISTENCIAS
    $router->group(['prefix' => '/api/asistencias'], function($router) {
        $router->post('', 'AsistenciaController@listarAsistencias');  // ✅ Sin barra
        $router->get('/obtener', 'AsistenciaController@obtenerAsistenciaCompleta');
        $router->post('/entrada', 'AsistenciaController@registrarEntrada');
        $router->post('/salida', 'AsistenciaController@registrarSalida');
        $router->post('/pausa/iniciar', 'AsistenciaController@iniciarPausa');
        $router->post('/pausa/finalizar', 'AsistenciaController@finalizarPausa');
    });
    
    // ✅ REPORTES
    $router->group(['prefix' => '/api/reportes'], function($router) {
        $router->get('/practicantes-activos', 'ReportesController@practicantesActivos');
        $router->get('/practicantes-completados', 'ReportesController@practicantesCompletados');
        $router->get('/por-area', 'ReportesController@practicantesPorArea');
        $router->get('/por-universidad', 'ReportesController@practicantesPorUniversidad');
        $router->get('/asistencia-practicante', 'ReportesController@asistenciaPorPracticante');
        $router->get('/asistencia-dia', 'ReportesController@asistenciaDelDia');
        $router->get('/asistencia-mensual', 'ReportesController@asistenciaMensual');
        $router->get('/asistencia-anual', 'ReportesController@asistenciaAnual');
        $router->get('/horas-acumuladas', 'ReportesController@horasAcumuladas');
        $router->get('/estadisticas-generales', 'ReportesController@estadisticasGenerales');
        $router->get('/promedio-horas', 'ReportesController@promedioHoras');
        $router->get('/comparativo-areas', 'ReportesController@comparativoAreas');
        $router->get('/completo', 'ReportesController@reporteCompleto');
        $router->post('/exportar-pdf', 'ReportesController@exportarPDF');
        $router->post('/exportar-excel', 'ReportesController@exportarExcel');
        $router->post('/exportar-word', 'ReportesController@exportarWord');
    });
    
    // ✅ USUARIOS - ARREGLADO: '' en lugar de '/'
    $router->group(['prefix' => '/api/usuarios'], function($router) {
        $router->get('', 'UsuarioController@listar');  // ✅ Sin barra
        $router->post('', 'UsuarioController@crear');  // ✅ Sin barra
        $router->post('/filtrar', 'UsuarioController@filtrar');
        $router->get('/{id}', 'UsuarioController@obtener');
        $router->put('/{id}', 'UsuarioController@actualizar');
        $router->delete('/{id}', 'UsuarioController@eliminar');
        $router->put('/{id}/password', 'UsuarioController@cambiarPassword');
    });
    
    // ✅ CERTIFICADOS
    $router->group(['prefix' => '/api/certificados'], function($router) {
        $router->get('/estadisticas', 'CertificadoController@obtenerEstadisticas');
        $router->get('/listar-practicantes', 'CertificadoController@listarPracticantesParaCertificado');
        $router->post('/generar', 'CertificadoController@generarCertificado');
        $router->get('/informacion/{id}', 'CertificadoController@obtenerInformacionCertificado');
    });
});

// ============================================
// VISTAS
// ============================================
$router->get('/', function() {
    require __DIR__ . '/../app/views/login.php';
});

$router->get('/login', function() {
    require __DIR__ . '/../app/views/login.php';
});

$router->get('/dashboard', function() {
    if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
        require __DIR__ . '/../app/views/login.php';
        exit;
    }
    require __DIR__ . '/../app/views/dashboard/index.php';
});

$router->dispatch($request);