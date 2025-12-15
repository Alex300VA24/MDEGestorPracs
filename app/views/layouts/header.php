<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header('Location: ' . BASE_URL . 'login');
    exit;
}

$nombreUsuario = $_SESSION['nombreUsuario'] ?? 'Usuario';
$nombreCargo   = $_SESSION['nombreCargo'] ?? 'Sin cargo';
$nombreArea    = $_SESSION['nombreArea'] ?? 'Sin área';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Gestión de Practicantes</title>
    <link rel="stylesheet" href='<?= BASE_URL ?>assets/css/fontawesome/css/all.min.css'">
    <link rel="stylesheet" href='<?=  BASE_URL ?>assets/css/bootstrap/css/bootstrap.min.css'">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/dashboard.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/inicio.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/practicantes.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/documentos.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/asistencias.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/reportes.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/certificados.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/usuarios.css">
    
</head>
<body>
