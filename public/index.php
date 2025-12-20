<?php
require_once __DIR__ . '/../vendor/autoload.php';

date_default_timezone_set('America/Lima');
$envFile = __DIR__ . '/../.env';
$APP_ENV = 'production';
$APP_DEBUG = false;
$APP_URL = null;
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if ($name === 'APP_ENV') $APP_ENV = $value;
        if ($name === 'APP_DEBUG') $APP_DEBUG = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        if ($name === 'APP_URL') $APP_URL = $value;
    }
}

error_reporting(E_ALL);
ini_set('display_startup_errors', $APP_DEBUG ? '1' : '0');
ini_set('display_errors', $APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

// Usar SecurityHeaders para aplicar headers de seguridad
use App\Security\SecurityHeaders;

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// Aplicar todos los headers de seguridad
SecurityHeaders::applyAllHeaders($isHttps);

// Funciones CSRF
function csrf_init() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function csrf_require_for_unsafe_methods() {
    $method = $_SERVER['REQUEST_METHOD'];
    if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
        $tokenHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $tokenHeader)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'CSRF token inválido']);
            exit;
        }
    }
}

csrf_init();

define('BASE_URL', '/MDEGestorPracs/public/');

$allowedOrigin = '*';
if ($APP_URL) {
    $parsed = parse_url($APP_URL);
    if (!empty($parsed['scheme']) && !empty($parsed['host'])) {
        $allowedOrigin = $parsed['scheme'] . '://' . $parsed['host'] . (isset($parsed['port']) ? ':' . $parsed['port'] : '');
    }
}
header('Access-Control-Allow-Origin: ' . $allowedOrigin);
header('Vary: Origin');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$request_uri = $_SERVER['REQUEST_URI'];
$script_name = dirname($_SERVER['SCRIPT_NAME']);
$path = str_replace($script_name, '', $request_uri);
$path = parse_url($path, PHP_URL_PATH);

if ($path[0] !== '/') {
    $path = '/' . $path;
}

if ($path === '/api/csrf-token') {
    header('Content-Type: application/json');
    echo json_encode(['token' => $_SESSION['csrf_token']]);
    exit;
}

csrf_require_for_unsafe_methods();

// Rutas de la API
switch (true) {
    // ============================================
    // RUTAS DE USUARIO/AUTH
    // ============================================
    case preg_match('#^/api/login$#', $path):
        $controller = new \App\Controllers\UsuarioController();
        $controller->login();
        break;
        
    case preg_match('#^/api/validar-cui$#', $path):
        $controller = new \App\Controllers\UsuarioController();
        $controller->validarCUI();
        break;
        
    case preg_match('#^/api/logout$#', $path):
        $controller = new \App\Controllers\UsuarioController();
        $controller->logout();
        break;

    // ============================================
    // RUTA DE INICIO/DASHBOARD
    // ============================================
    case preg_match('#^/api/inicio$#', $path):
        $controller = new \App\Controllers\DashboardController();
        $controller->obtenerDatosInicio();
        break;

    // ============================================
    // RUTAS DE PRACTICANTES
    // ============================================
    case preg_match('#^/api/practicantes/filtrar$#', $path):
        $controller = new \App\Controllers\PracticanteController();
        $controller->filtrarPracticantes();
        break;
    
    case $path === '/api/practicantes/aceptar':
        $controller = new \App\Controllers\PracticanteController();
        $controller->aceptarPracticante();
        break;
    
    case $path === '/api/practicantes/rechazar':
        $controller = new \App\Controllers\PracticanteController();
        $controller->rechazarPracticante();
        break;

    case preg_match('#^/api/practicantes$#', $path):
        $controller = new \App\Controllers\PracticanteController();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $controller->listarPracticantes();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->registrarPracticante();
        }
        break;
    
    case preg_match('#^/api/practicantes/(\d+)$#', $path, $matches):
        $controller = new \App\Controllers\PracticanteController();
        $practicanteID = $matches[1];

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $controller->obtener($practicanteID);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            $controller->actualizar($practicanteID);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $controller->eliminar($practicanteID);
        }
        break;

    // ============================================
    // RUTAS DE SOLICITUDES / DOCUMENTOS
    // ============================================
    case $path === '/api/practicantes/listar-nombres':
        $controller = new \App\Controllers\PracticanteController();
        $controller->listarNombresPracticantes();
        break;

    case $path === '/api/solicitudes/por-practicante':
        $controller = new \App\Controllers\SolicitudController();
        $controller->obtenerSolicitudPorPracticante();
        break;

    case preg_match('#^/api/solicitudes/documentos$#', $path):
        header('Content-Type: application/json; charset=utf-8');
        $controller = new \App\Controllers\SolicitudController();
        $controller->obtenerDocumentosPorPracticante();
        break;

    case $path === '/api/solicitudes/crearSolicitud':
        $controller = new \App\Controllers\SolicitudController();
        $controller->crearSolicitud();
        break;

    case $path === '/api/solicitudes/subirDocumento':
        $controller = new \App\Controllers\SolicitudController();
        $controller->subirDocumento();
        break;
    
    case $path === '/api/solicitudes/actualizarDocumento':
        $controller = new \App\Controllers\SolicitudController();
        $controller->actualizarDocumento();
        break;
    
    case $path === '/api/solicitudes/obtenerPorTipoYPracticante':
        $controller = new \App\Controllers\SolicitudController();
        $controller->obtenerDocumentoPorTipoYPracticante();
        break;
    
    case $path === '/api/solicitudes/estado':
        $controller = new \App\Controllers\SolicitudController();
        $solicitudID = $_GET['solicitudID'] ?? null; // 👈 obtiene el parámetro de la URL
        $controller->verificarEstado($solicitudID);
        break;
    
    case $path === '/api/solicitudes/obtenerSolicitud':
        $controller = new \App\Controllers\SolicitudController();
        $controller->obtenerSolicitudPorID();
        break;
    
    case $path === '/api/solicitudes/eliminarDocumento':
        $controller = new \App\Controllers\SolicitudController();
        $controller->eliminarDocumento();
        break;

    // Agregar esta ruta en la sección de ASISTENCIAS
    case $path === '/api/asistencias/obtener':
        $controller = new \App\Controllers\AsistenciaController();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $controller->obtenerAsistenciaCompleta();
        }
        break;
    
    case $path === '/api/solicitudes/generarCartaAceptacion':
        $controller = new \App\Controllers\SolicitudController();
        $controller->generarCartaAceptacion();
        break;

    case $path === '/api/solicitudes/verificarSolicitudParaCarta':
        $controller = new \App\Controllers\SolicitudController();
        $controller->verificarSolicitudParaCarta();
        break;

    case $path === '/api/solicitudes/listarSolicitudesAprobadas':
        $controller = new \App\Controllers\SolicitudController();
        $controller->listarSolicitudesAprobadas();
        break;

    // ============================================
    // RUTAS DE MENSAJES
    // ============================================
    case $path === '/api/mensajes/enviar':
        $controller = new \App\Controllers\MensajeController();
        $controller->enviarSolicitud();
        break;
    
    case $path === '/api/mensajes/responder':
        $controller = new \App\Controllers\MensajeController();
        $controller->responderSolicitud();
        break;
    
    case preg_match('#^/api/mensajes/(\d+)$#', $path, $matches):
        $controller = new \App\Controllers\MensajeController();

        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $controller->eliminarMensaje($matches[1]);
        } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $controller->listarMensajes($matches[1]);
        } else {
            header('Content-Type: application/json', true, 405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
        }
        break;

    // ============================================
    // RUTAS DE ÁREAS
    // ============================================
    case $path === '/api/areas':
        $controller = new \App\Controllers\AreaController();
        $controller->listar();
        break;

    // ============================================
    // RUTAS DE TURNOS
    // ============================================
    case $path === '/api/turnos':
        $controller = new \App\Controllers\TurnoController();
        $controller->listar();
        break;
    
    case preg_match('#^/api/turnos/practicante/(\d+)$#', $path, $matches):
        $controller = new \App\Controllers\TurnoController();
        $controller->obtenerPorPracticante($matches[1]);
        break;

    // ============================================
    // RUTAS DE ASISTENCIAS (Reemplazar la sección existente)
    // ============================================
    case $path === '/api/asistencias':
        $controller = new \App\Controllers\AsistenciaController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->listarAsistencias();
        }
        break;

    case $path === '/api/asistencias/entrada':
        $controller = new \App\Controllers\AsistenciaController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->registrarEntrada();
        }
        break;

    case $path === '/api/asistencias/salida':
        $controller = new \App\Controllers\AsistenciaController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->registrarSalida();
        }
        break;

    case $path === '/api/asistencias/pausa/iniciar':
        $controller = new \App\Controllers\AsistenciaController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->iniciarPausa();
        }
        break;

    case $path === '/api/asistencias/pausa/finalizar':
        $controller = new \App\Controllers\AsistenciaController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->finalizarPausa();
        }
        break;
    
    // ============================================
    // RUTAS DE REPORTES
    // ============================================
    case $path === '/api/reportes/practicantes-activos':
        $controller = new \App\Controllers\ReportesController();
        $controller->practicantesActivos();
        break;

    case $path === '/api/reportes/practicantes-completados':
        $controller = new \App\Controllers\ReportesController();
        $controller->practicantesCompletados();
        break;

    case $path === '/api/reportes/por-area':
        $controller = new \App\Controllers\ReportesController();
        $controller->practicantesPorArea();
        break;

    case $path === '/api/reportes/por-universidad':
        $controller = new \App\Controllers\ReportesController();
        $controller->practicantesPorUniversidad();
        break;

    case $path === '/api/reportes/asistencia-practicante':
        $controller = new \App\Controllers\ReportesController();
        $controller->asistenciaPorPracticante();
        break;

    case $path === '/api/reportes/asistencia-dia':
        $controller = new \App\Controllers\ReportesController();
        $controller->asistenciaDelDia();
        break;

    case $path === '/api/reportes/asistencia-mensual':
        $controller = new \App\Controllers\ReportesController();
        $controller->asistenciaMensual();
        break;
    
    case $path === '/api/reportes/asistencia-anual':
        $controller = new \App\Controllers\ReportesController();
        $controller->asistenciaAnual();
        break;

    case $path === '/api/reportes/horas-acumuladas':
        $controller = new \App\Controllers\ReportesController();
        $controller->horasAcumuladas();
        break;

    case $path === '/api/reportes/estadisticas-generales':
        $controller = new \App\Controllers\ReportesController();
        $controller->estadisticasGenerales();
        break;

    case $path === '/api/reportes/promedio-horas':
        $controller = new \App\Controllers\ReportesController();
        $controller->promedioHoras();
        break;

    case $path === '/api/reportes/comparativo-areas':
        $controller = new \App\Controllers\ReportesController();
        $controller->comparativoAreas();
        break;

    case $path === '/api/reportes/completo':
        $controller = new \App\Controllers\ReportesController();
        $controller->reporteCompleto();
        break;

    // Exportaciones
    case $path === '/api/reportes/exportar-pdf':
        $controller = new \App\Controllers\ReportesController();
        $controller->exportarPDF();
        break;

    case $path === '/api/reportes/exportar-excel':
        $controller = new \App\Controllers\ReportesController();
        $controller->exportarExcel();
        break;

    case $path === '/api/reportes/exportar-word':
        $controller = new \App\Controllers\ReportesController();
        $controller->exportarWord();
        break;
    
    // ============================================
    // RUTAS DE USUARIOS
    // ============================================
    case $path === '/api/usuarios':
        $controller = new \App\Controllers\UsuarioController();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $controller->listar();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->crear();
        }
        break;

    case preg_match('#^/api/usuarios/(\d+)$#', $path, $matches):
        $controller = new \App\Controllers\UsuarioController();
        $usuarioID = $matches[1];

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $controller->obtener($usuarioID);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            $controller->actualizar($usuarioID);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $controller->eliminar($usuarioID);
        }
        break;

    case preg_match('#^/api/usuarios/(\d+)/password$#', $path, $matches):
        $controller = new \App\Controllers\UsuarioController();
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            $controller->cambiarPassword($matches[1]);
        }
        break;

    case $path === '/api/usuarios/filtrar':
        $controller = new \App\Controllers\UsuarioController();
        $controller->filtrar();
        break;
    

    // ============================================
    // RUTAS DE CERTIFICADOS
    // ============================================
    case $path === '/api/certificados/estadisticas':
        $controller = new \App\Controllers\CertificadoController();
        $controller->obtenerEstadisticas();
        break;

    case $path === '/api/certificados/listar-practicantes':
        $controller = new \App\Controllers\CertificadoController();
        $controller->listarPracticantesParaCertificado();
        break;

    case preg_match('#^/api/certificados/informacion/(\d+)$#', $path, $matches):
        $controller = new \App\Controllers\CertificadoController();
        $controller->obtenerInformacionCertificado($matches[1]);
        break;

    case $path === '/api/certificados/generar':
        $controller = new \App\Controllers\CertificadoController();
        $controller->generarCertificado();
        break;

    // 🆕 NUEVOS ENDPOINTS PARA MÚLTIPLES SOLICITUDES
    case preg_match('#^/api/solicitudes/activa/(\d+)$#', $path, $matches):
        $controller = new \App\Controllers\SolicitudController();
        $controller->obtenerSolicitudActiva($matches[1]);
        break;

    case $path === '/api/solicitudes/crear':
        $controller = new \App\Controllers\SolicitudController();
        $controller->crearNuevaSolicitud();
        break;

    case preg_match('#^/api/solicitudes/historial/(\d+)$#', $path, $matches):
        $controller = new \App\Controllers\SolicitudController();
        $controller->obtenerHistorialSolicitudes($matches[1]);
        break;

    case $path === '/api/solicitudes/obtenerPorTipoYSolicitud':
        $controller = new \App\Controllers\SolicitudController();
        $controller->obtenerDocumentoPorTipoYSolicitud();
        break;

    // ============================================
    // RUTAS DE VISTAS
    // ============================================
    case $path === '/' || $path === '/login':
        require __DIR__ . '/../app/views/login.php';
        break;
        
    case $path === '/dashboard':
        if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
            require __DIR__ . '/../app/views/login.php';
            exit;
        }
        require __DIR__ . '/../app/views/dashboard/index.php';
        break;
    
    // ============================================
    // RUTA 404
    // ============================================
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Ruta no encontrada: ' . $path]);
        break;
}
