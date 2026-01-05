<?php
// config/app.php

$envFile = __DIR__ . '/../.env';
$env = [
    'APP_ENV' => 'production',
    'APP_DEBUG' => false,
    'APP_URL' => null,
];

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if ($name === 'APP_ENV') $env['APP_ENV'] = $value;
        if ($name === 'APP_DEBUG') $env['APP_DEBUG'] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        if ($name === 'APP_URL') $env['APP_URL'] = $value;
    }
}

$allowedOrigin = '*';
if ($env['APP_URL']) {
    $parsed = parse_url($env['APP_URL']);
    if (!empty($parsed['scheme']) && !empty($parsed['host'])) {
        $allowedOrigin = $parsed['scheme'] . '://' . $parsed['host'] 
                      . (isset($parsed['port']) ? ':' . $parsed['port'] : '');
    }
}

// Definir BASE_URL como constante global
define('BASE_URL', '/MDEGestorPracs/public/');

return [
    'env' => $env['APP_ENV'],
    'debug' => $env['APP_DEBUG'],
    'url' => $env['APP_URL'],
    'base_url' => BASE_URL,
    
    'cors' => [
        'allowed_origin' => $allowedOrigin,
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-CSRF-Token'],
    ],
    
    'session' => [
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'httponly' => true,
        'samesite' => 'Strict'
    ]
];