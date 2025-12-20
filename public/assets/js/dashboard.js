document.addEventListener('DOMContentLoaded', async () => {
    const BASE_URL = window.location.origin + '/MDEGestorPracs/public/';

    // Navegación
    window.showPage = function (pageId, element) {
        // 🔹 Oculta todas las páginas y activa la actual
        document.querySelectorAll('.page-content').forEach(p => p.classList.remove('active'));
        const pageEl = document.getElementById('page' + capitalize(pageId));
        if (pageEl) pageEl.classList.add('active');

        // Buscar el elemento ".option" si no fue pasado
        const optionEl = element ||
                        document.querySelector(`.option[data-page="${pageId}"]`) ||
                        document.querySelector(`#btn${capitalize(pageId)}`);

        // Limpiar clases .active en el menú y marcar la opción encontrada (si existe)
        document.querySelectorAll('.option').forEach(o => o.classList.remove('active'));
        if (optionEl) optionEl.classList.add('active');

        const initFunctions = {
            inicio: cargarInicio,
            practicantes: window.initPracticantes,
            documentos: window.initDocumentos,
            asistencias: window.initAsistencias,
            reportes: window.initReportes,
            certificados: window.initCertificados,
            usuarios: window.initUsuarios,
            mensajes: window.initMensajes
        };

        // Ejecutar init asociado solo si existe
        if (initFunctions[pageId]) {
            initFunctions[pageId]();
        }


        // 🔹 Si el usuario va al inicio, cargamos los datos
        if (pageId === 'inicio') {
            cargarInicio();
        }
        
        // Guardar la página actual en localStorage SIEMPRE
        localStorage.setItem("currentPage", pageId);
    };

    


    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
    
    
    // Restaurar la última página visitada (fallback robusto)
    (function restoreLastPage() {
        const lastPage = localStorage.getItem("currentPage") || 'inicio';

        // buscamos el botón/menú asociado
        const option = document.querySelector(`.option[data-page="${lastPage}"]`) ||
                    document.querySelector(`#btn${capitalize(lastPage)}`);

        const pageEl = document.getElementById('page' + capitalize(lastPage));

        if (pageEl) {
            // usamos showPage, que marca la opción activa y carga el contenido si es "inicio"
            showPage(lastPage, option);
        } else {
            // fallback: mostrar inicio
            const defaultOption = document.querySelector(`.option[data-page="inicio"]`) ||
                                document.querySelector(`#btnInicio`);
            showPage('inicio', defaultOption);
        }

    })();

    // Logout
    document.getElementById('btnLogout').addEventListener('click', () => {
        const modal = document.getElementById('logoutModal');
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
    });

    document.getElementById('cancelLogout').addEventListener('click', () => {
        const modal = document.getElementById('logoutModal');
        modal.style.display = 'none';
        modal.classList.add('hidden');
    });

    document.getElementById('confirmLogout').addEventListener('click', async () => {
        const modal = document.getElementById('logoutModal');
        modal.style.display = 'none';
        modal.classList.add('hidden');
        try {
            await api.logout();
            // Limpiar la página guardada para que en el próximo inicio no restaure la última página
            localStorage.removeItem('currentPage');
            window.location.href = BASE_URL + 'login';
        } catch (error) {
            mostrarAlerta({tipo: 'error', titulo:'Error', mensaje: 'Error al cerrar sesión'});
        }
    });


    // Ya no necesitamos el window.load para restaurar la página, lo hicimos arriba.
});


// Reemplazar la función cargarInicio() completa:

async function cargarInicio() {
    try {
        // Recuperar área del usuario logueado
        const areaID = sessionStorage.getItem('areaID');
        const nombreArea = sessionStorage.getItem('nombreArea');
        
        console.log('🔍 En cargarInicio - areaID:', areaID, 'nombreArea:', nombreArea);

        // Si RRHH, no se envía áreaID (deja ver todo)
        const params = (nombreArea === 'Gerencia de Recursos Humanos' || !areaID) 
            ? {} 
            : { areaID: areaID };
        
        console.log('📤 Parámetros enviados a API:', params);

        // Llamada al backend
        const response = await api.obtenerDatosInicio(params);

        if (!response.success || !response.data) {
            console.error("Formato inválido en la respuesta del backend:", response);
            return;
        }

        const data = response.data;

        // === Actualizar estadísticas ===
        document.getElementById('totalPracticantes').textContent = data.totalPracticantes || 0;
        document.getElementById('pendientesAprobacion').textContent = data.pendientesAprobacion || 0;
        document.getElementById('practicantesActivos').textContent = data.practicantesActivos || 0;
        document.getElementById('asistenciaHoy').textContent = data.asistenciaHoy || 0;

        // === Actividad reciente ===
        const actividadDiv = document.getElementById('actividadReciente');
        actividadDiv.innerHTML = '';

        console.log(data.actividadReciente);

        if (data.actividadReciente && data.actividadReciente.length > 0) {
            data.actividadReciente.forEach(act => {
                const div = document.createElement('div');
                div.classList.add('actividad-item');
                
                // Formatear tiempo
                const tiempo = formatearTiempo(act.MinutosTranscurridos);
                
                // Clase de color según tipo
                const colorClass = obtenerClaseColor(act.TipoActividad);
                
                div.innerHTML = `
                    <div class="d-flex align-items-start">
                        <div class="activity-icon me-3 ${colorClass}">
                            <i class="fas fa-${act.Icono || 'circle'} fa-lg"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="mb-1">${escapeHtml(act.Descripcion)}</p>
                            <small class="text-muted">
                                <i class="far fa-clock"></i> Hace ${tiempo}
                            </small>
                        </div>
                    </div>
                `;
                actividadDiv.appendChild(div);
            });
        } else {
            actividadDiv.innerHTML = '<p class="text-muted text-center py-3">No hay actividad reciente.</p>';
        }

    } catch (error) {
        console.error('❌ Error al cargar el inicio:', error);
        mostrarAlerta({
            tipo: 'error',
            titulo: 'Error',
            mensaje: 'No se pudieron cargar los datos del dashboard'
        });
    }
}

// Función para formatear tiempo transcurrido
function formatearTiempo(minutos) {
    if (minutos < 1) return "justo ahora";
    if (minutos < 60) return `${minutos} minuto${minutos > 1 ? 's' : ''}`;
    
    const horas = Math.floor(minutos / 60);
    if (horas < 24) return `${horas} hora${horas > 1 ? 's' : ''}`;
    
    const dias = Math.floor(horas / 24);
    return `${dias} día${dias > 1 ? 's' : ''}`;
}

// Función para obtener clase de color según tipo de actividad
function obtenerClaseColor(tipo) {
    switch(tipo) {
        case 'INSERT': return 'text-success';
        case 'UPDATE': return 'text-warning';
        case 'DELETE': return 'text-danger';
        default: return 'text-info';
    }
}

// Función para escapar HTML y prevenir XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

async function ejecutarUnaVez(boton, accionAsync) {
    if (!boton) {
        console.warn("ejecutarUnaVez: botón no encontrado");
        return await accionAsync();
    }

    boton.disabled = true;
    const textoOriginal = boton.innerHTML;
    boton.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Procesando...`;

    try {
        const resultado = await accionAsync();
        return resultado;
    } catch (error) {
        console.error("❌ Error en ejecutarUnaVez:", error);
        throw error;
    } finally {
        boton.disabled = false;
        boton.innerHTML = textoOriginal;
    }
}

// Función global para mostrar alertas
window.mostrarAlerta = function({
    tipo = "info",
    titulo = "",
    mensaje = "",
    showConfirmButton = true,
    showCancelButton = false,
    confirmText = "Aceptar",
    cancelText = "Cancelar",
    input = null,
    inputPlaceholder = "",
    inputValue = "",
    html = null,
    allowOutsideClick = false,
    allowEscapeKey = false,
    width = "32em",
    callback = null
}) {
    if (!_alertMixin && typeof Swal !== "undefined") {
        _alertMixin = Swal.mixin({
            buttonsStyling: false,
            customClass: {
                confirmButton: "btn btn-primary",
                cancelButton: "btn btn-secondary",
                denyButton: "btn btn-warning",
                actions: "swal2-actions",
            }
        });
    }

    const instance = _alertMixin || Swal;

    const config = {
        icon: tipo,
        title: titulo,
        position: "center",
        showConfirmButton,
        showCancelButton,
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        input,
        inputPlaceholder,
        inputValue,
        backdrop: true,
        allowOutsideClick,
        allowEscapeKey,
        width,
        heightAuto: false // 🔑 CLAVE
    };


    // 🔑 CLAVE: solo UNO
    if (html) {
        config.html = html;
    } else {
        config.text = mensaje;
    }

    return instance.fire(config).then((result) => {
        if (callback) callback(result);
        return result;
    });
};










