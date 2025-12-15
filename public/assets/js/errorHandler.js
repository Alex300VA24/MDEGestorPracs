/**
 * Error Handler - Manejador global de errores
 * Mejora la captura y visualización de errores en la aplicación
 */

// Crear instancia global del API si no existe
if (typeof api === 'undefined') {
    window.api = {};
}

/**
 * Mostrar alerta si la función de SweetAlert existe
 */
function mostrarAlertaSegura(config) {
    // Si SweetAlert no está disponible aún, registrar error en consola
    if (typeof Swal === 'undefined') {
        console.warn('SweetAlert no disponible. Mensaje:', config.mensaje);
        return;
    }
    
    return mostrarAlerta({
        tipo: config.tipo || 'error',
        titulo: config.titulo || 'Error',
        mensaje: config.mensaje || 'Ha ocurrido un error inesperado'
    });
}

/**
 * Envolver una función async para capturar errores automáticamente
 */
function conManejadorErrores(funcionAsync) {
    return async function(...args) {
        try {
            return await funcionAsync.apply(this, args);
        } catch (error) {
            console.error('Error en función:', error);
            mostrarAlertaSegura({
                tipo: 'error',
                titulo: 'Error',
                mensaje: error.message || 'Ha ocurrido un error inesperado'
            });
            throw error; // Re-lanzar el error para que el código pueda seguir con su propio manejo
        }
    };
}

/**
 * Wrapper para fetch que mejora el manejo de errores
 */
async function fetchSeguro(url, opciones = {}) {
    try {
        const respuesta = await fetch(url, opciones);
        
        // Intentar parsear como JSON
        const contenido = await respuesta.text();
        let datos = {};
        
        try {
            datos = contenido ? JSON.parse(contenido) : {};
        } catch (e) {
            console.error('Error al parsear JSON:', e);
            datos = { error: 'Respuesta inválida del servidor' };
        }
        
        if (!respuesta.ok) {
            const mensajeError = datos.message || datos.error || `Error HTTP ${respuesta.status}`;
            const error = new Error(mensajeError);
            error.status = respuesta.status;
            error.datos = datos;
            throw error;
        }
        
        return datos;
    } catch (error) {
        console.error('Error en fetchSeguro:', error);
        throw error;
    }
}

/**
 * Registrar handlers globales para errores no capturados
 */
(function() {
    // Manejar rechazo de promesas no capturadas
    window.addEventListener('unhandledrejection', event => {
        console.error('❌ Promesa rechazada no manejada:', event.reason);
        
        // Esperar a que SweetAlert se cargue
        setTimeout(() => {
            if (typeof Swal !== 'undefined') {
                const mensaje = event.reason?.message || String(event.reason);
                mostrarAlertaSegura({
                    tipo: 'error',
                    titulo: 'Error inesperado',
                    mensaje: mensaje
                });
            }
        }, 100);
    });

    // Manejar errores no capturados
    window.addEventListener('error', event => {
        console.error('❌ Error global:', event.error);
        if (event.error && event.error.message && !event.error.handled) {
            event.error.handled = true;
            
            setTimeout(() => {
                if (typeof Swal !== 'undefined') {
                    mostrarAlertaSegura({
                        tipo: 'error',
                        titulo: 'Error',
                        mensaje: event.error.message
                    });
                }
            }, 100);
        }
    });
})();

// Exportar funciones globales
window.conManejadorErrores = conManejadorErrores;
window.fetchSeguro = fetchSeguro;
window.mostrarAlertaSegura = mostrarAlertaSegura;
