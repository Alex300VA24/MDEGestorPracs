<script src="<?= BASE_URL ?>assets/js/sweetalert2/dist/sweetalert2.all.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/errorHandler.js"></script>

<script>
// ============ MANEJADOR GLOBAL DE ERRORES ============
// Manejador de rechazos de promesas no capturadas
window.addEventListener('unhandledrejection', event => {
    console.error('❌ Promise rejection no manejada:', event.reason);
    const mensaje = event.reason?.message || String(event.reason);
    mostrarAlerta({
        tipo: 'error',
        titulo: 'Error inesperado',
        mensaje: mensaje
    });
});

// Manejador de errores globales
window.addEventListener('error', event => {
    console.error('❌ Error global:', event.error);
    if (event.error && event.error.message) {
        mostrarAlerta({
            tipo: 'error',
            titulo: 'Error',
            mensaje: event.error.message
        });
    }
});
</script>

<script src="<?= BASE_URL ?>assets/js/api.js"></script>
<script src="<?= BASE_URL ?>assets/js/dashboard.js"></script>
<script src="<?= BASE_URL ?>assets/js/practicantes.js"></script>
<script src="<?= BASE_URL ?>assets/js/documentos.js"></script>
<script src="<?= BASE_URL ?>assets/js/asistencias.js"></script>
<script src="<?= BASE_URL ?>assets/js/reportes.js"></script>
<script src="<?= BASE_URL ?>assets/js/certificados.js"></script>
<script src="<?= BASE_URL ?>assets/js/usuarios.js"></script>

<script src="<?= BASE_URL ?>assets/js/bootstrap/js/bootstrap.bundle.min.js"></script>

</body>
</html>
