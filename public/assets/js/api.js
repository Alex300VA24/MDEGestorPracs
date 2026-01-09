/**
 * Cliente API para MDEGestorPracs
 * Maneja todas las peticiones HTTP con CSRF automático
 */
class API {
    constructor(baseURL = '/MDEGestorPracs/public/api') {
        this.baseURL = baseURL;
        this.csrfToken = null;
    }

    // ==================== CORE HTTP ====================

    /**
     * Petición HTTP genérica
     */
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const method = (options.method || 'GET').toUpperCase();

        // Obtener CSRF token para métodos que modifican datos
        if (method !== 'GET' && method !== 'OPTIONS') {
            await this.ensureCSRF();
        }

        const config = {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                ...(this.csrfToken && method !== 'GET' ? { 'X-CSRF-Token': this.csrfToken } : {}),
                ...options.headers
            }
        };

        try {
            const response = await fetch(url, config);
            const text = await response.text();

            let data = {};
            try {
                data = text ? JSON.parse(text) : {};
            } catch (e) {
                console.error('❌ Error parsing JSON:', e);
                console.error('Response text:', text.substring(0, 500));
                throw new Error(`Respuesta inválida del servidor: ${text.substring(0, 100)}...`);
            }

            if (!response.ok) {
                const error = new Error(data.message || `HTTP ${response.status}`);
                error.status = response.status;
                error.data = data;
                throw error;
            }

            return data;
        } catch (error) {
            console.error('❌ API Error:', error);
            throw error;
        }
    }

    /**
     * Obtener token CSRF (solo una vez por sesión)
     */
    async ensureCSRF() {
        if (this.csrfToken) return;
        
        try {
            const response = await fetch(`${this.baseURL}/csrf-token`, { method: 'GET' });
            const data = await response.json();
            
            if (data && data.token) {
                this.csrfToken = data.token;
            } else {
                console.warn('⚠️ No se pudo obtener CSRF token');
            }
        } catch (e) {
            console.error('❌ Error obteniendo CSRF token:', e);
        }
    }

    // ==================== MÉTODOS HTTP ====================

    async get(endpoint) {
        return this.request(endpoint, { method: 'GET' });
    }

    async post(endpoint, data) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    async put(endpoint, data) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }

    async delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    }

    // ==================== AUTENTICACIÓN ====================

    async login(nombreUsuario, password) {
        // Login no necesita CSRF porque está excluido en el backend
        const response = await this.post('/login', { nombreUsuario, password });
        
        // Después de login exitoso, obtener CSRF para futuras peticiones
        if (response.success) {
            await this.ensureCSRF();
        }
        
        return response;
    }

    async validarCUI(id, cui) {
        return this.post('/validar-cui', { id, cui });
    }

    async logout() {
        return this.post('/logout');
    }

    // ==================== DASHBOARD ====================

    async obtenerDatosInicio(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        return this.get(`/inicio${queryString ? '?' + queryString : ''}`);
    }

    // ==================== PRACTICANTES ====================

    async getPracticantes() {
        return this.get('/practicantes');
    }

    async crearPracticante(data) {
        return this.post('/practicantes', data);
    }

    async filtrarPracticantes(filtros = {}) {
        return this.post('/practicantes/filtrar', filtros);
    }

    async aceptarPracticante(data) {
        return this.post('/practicantes/aceptar', data);
    }

    async rechazarPracticante(data) {
        return this.post('/practicantes/rechazar', data);
    }

    async listarNombrePracticantes() {
        return this.get('/practicantes/listar-nombres');
    }
    async getPracticante(id) {
        return this.get(`/practicantes/${id}`);
    }
    async actualizarPracticante(id, data) {
        return this.put(`/practicantes/${id}`, data);
    }

    async eliminarPracticante(id) {
        return this.delete(`/practicantes/${id}`);
    }

    // ==================== SOLICITUDES ====================

    async obtenerSolicitudPorPracticante(practicanteID) {
        return this.get(`/solicitudes/por-practicante?practicanteID=${practicanteID}`);
    }

    async obtenerSolicitudActiva(practicanteID) {
        return this.get(`/solicitudes/activa/${practicanteID}`);
    }

    async crearNuevaSolicitud(practicanteID, migrarDocumentos = false) {
        return this.post('/solicitudes/crear', { practicanteID, migrarDocumentos });
    }

    async obtenerHistorialSolicitudes(practicanteID) {
        return this.get(`/solicitudes/historial/${practicanteID}`);
    }

    async verificarEstadoSolicitud(solicitudID) {
        return this.get(`/solicitudes/estado/${solicitudID}`);
    }

    async verificarSolicitudParaCarta(solicitudID) {
        return this.get(`/solicitudes/verificarSolicitudParaCarta?solicitudID=${solicitudID}`);
    }

    async listarSolicitudesAprobadas() {
        return this.get('/solicitudes/listarSolicitudesAprobadas');
    }

    // ==================== DOCUMENTOS ====================

    async obtenerDocumentosPorPracticante(practicanteID) {
        return this.get(`/solicitudes/documentos?practicanteID=${practicanteID}`);
    }

    async obtenerDocumentoPorTipoYPracticante(practicanteID, tipoDocumento) {
        return this.get(`/solicitudes/obtenerPorTipoYPracticante?practicanteID=${practicanteID}&tipoDocumento=${tipoDocumento}`);
    }

    async obtenerDocumentoPorTipoYSolicitud(solicitudID, tipoDocumento) {
        return this.get(`/solicitudes/obtenerPorTipoYSolicitud?solicitudID=${solicitudID}&tipoDocumento=${tipoDocumento}`);
    }

    async subirDocumento(formData) {
        await this.ensureCSRF();
        return fetch(`${this.baseURL}/solicitudes/subirDocumento`, {
            method: "POST",
            body: formData,
            headers: this.csrfToken ? { 'X-CSRF-Token': this.csrfToken } : {}
        });
    }

    async actualizarDocumento(formData) {
        await this.ensureCSRF();
        return fetch(`${this.baseURL}/solicitudes/actualizarDocumento`, {
            method: "POST",
            body: formData,
            headers: this.csrfToken ? { 'X-CSRF-Token': this.csrfToken } : {}
        });
    }

    async eliminarDocumento(documentoID) {
        return this.delete('/solicitudes/eliminarDocumento/' + documentoID);
    }

    async generarCartaAceptacion(solicitudID, numeroExpediente, formato, nombreDirector, cargoDirector) {
        const response = await this.post('/solicitudes/generarCartaAceptacion', {
            solicitudID,
            numeroExpediente,
            formato,
            nombreDirector,
            cargoDirector
        });

        return response;
    }

    // ==================== MENSAJES ====================

    async enviarSolicitudArea(data) {
        return this.post('/mensajes/enviar', data);
    }

    async responderSolicitud(data) {
        return this.post('/mensajes/responder', data);
    }

    async listarMensajes(areaID) {
        return this.get(`/mensajes/${areaID}`);
    }

    async eliminarMensaje(id) {
        return this.delete(`/mensajes/${id}`);
    }

    // ==================== ÁREAS Y TURNOS ====================

    async listarAreas() {
        return this.get('/areas');
    }

    async obtenerTurnosPracticante(practicanteID) {
        return this.get(`/turnos/practicante/${practicanteID}`);
    }

    // ==================== ASISTENCIAS ====================

    async listarAsistencias(data) {
        return this.post('/asistencias', data);
    }

    async obtenerAsistenciaCompleta(practicanteID) {
        return this.get(`/asistencias/obtener?practicanteID=${practicanteID}`);
    }

    async registrarEntrada(data) {
        return this.post('/asistencias/entrada', data);
    }

    async registrarSalida(data) {
        return this.post('/asistencias/salida', data);
    }

    async iniciarPausa(data) {
        return this.post('/asistencias/pausa/iniciar', data);
    }

    async finalizarPausa(data) {
        return this.post('/asistencias/pausa/finalizar', data);
    }

    // ==================== REPORTES ====================

    async reportePracticantesActivos() {
        return this.get('/reportes/practicantes-activos');
    }

    async reportePracticantesCompletados() {
        return this.get('/reportes/practicantes-completados');
    }

    async reportePorArea(areaID = null) {
        return this.get(areaID ? `/reportes/por-area?areaID=${areaID}` : '/reportes/por-area');
    }

    async reportePorUniversidad() {
        return this.get('/reportes/por-universidad');
    }

    async reporteAsistenciaPracticante(practicanteID, fechaInicio = null, fechaFin = null) {
        let endpoint = `/reportes/asistencia-practicante?practicanteID=${practicanteID}`;
        if (fechaInicio) endpoint += `&fechaInicio=${fechaInicio}`;
        if (fechaFin) endpoint += `&fechaFin=${fechaFin}`;
        return this.get(endpoint);
    }

    async reporteAsistenciaDelDia(fecha = null) {
        return this.get(fecha ? `/reportes/asistencia-dia?fecha=${fecha}` : '/reportes/asistencia-dia');
    }

    async reporteAsistenciaMensual(mes, anio) {
        return this.get(`/reportes/asistencia-mensual?mes=${mes}&anio=${anio}`);
    }

    async reporteAsistenciaAnual(anio) {
        return this.get(`/reportes/asistencia-anual?anio=${anio}`);
    }

    async reporteHorasAcumuladas(practicanteID = null) {
        return this.get(practicanteID ? `/reportes/horas-acumuladas?practicanteID=${practicanteID}` : '/reportes/horas-acumuladas');
    }

    async reporteEstadisticasGenerales() {
        return this.get('/reportes/estadisticas-generales');
    }

    async reportePromedioHoras() {
        return this.get('/reportes/promedio-horas');
    }

    async reporteComparativoAreas() {
        return this.get('/reportes/comparativo-areas');
    }

    async reporteCompleto() {
        return this.get('/reportes/completo');
    }

    async exportarPDF(tipoReporte, datos) {
        return this.post('/reportes/exportar-pdf', { tipoReporte, datos });
    }

    async exportarExcel(tipoReporte, datos) {
        return this.post('/reportes/exportar-excel', { tipoReporte, datos });
    }

    async exportarWord(tipoReporte, datos) {
        return this.post('/reportes/exportar-word', { tipoReporte, datos });
    }

    // ==================== CERTIFICADOS ====================

    async obtenerEstadisticasCertificados() {
        return this.get('/certificados/estadisticas');
    }

    async listarPracticantesParaCertificado() {
        return this.get('/certificados/listar-practicantes');
    }

    async obtenerInformacionCertificado(practicanteID) {
        return this.get(`/certificados/informacion/${practicanteID}`);
    }

    async generarCertificadoHoras(data) {
        return this.post('/certificados/generar', data);
    }

    // ==================== USUARIOS ====================

    async listarUsuarios() {
        return this.get('/usuarios');
    }

    async obtenerUsuario(usuarioID) {
        return this.get(`/usuarios/${usuarioID}`);
    }

    async crearUsuario(data) {
        return this.post('/usuarios', data);
    }

    async actualizarUsuario(usuarioID, data) {
        return this.put(`/usuarios/${usuarioID}`, data);
    }

    async eliminarUsuario(usuarioID) {
        return this.delete(`/usuarios/${usuarioID}`);
    }

    async cambiarPasswordUsuario(usuarioID, data) {
        return this.put(`/usuarios/${usuarioID}/password`, data);
    }

    async filtrarUsuarios(filtros = {}) {
        return this.post('/usuarios/filtrar', filtros);
    }
}

// ==================== INSTANCIA GLOBAL ====================
const api = new API();

// ==================== HELPER DE ALERTAS ====================
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
    const mixin = Swal.mixin({
        buttonsStyling: false,
        customClass: {
            confirmButton: "btn btn-primary",
            cancelButton: "btn btn-secondary",
            denyButton: "btn btn-warning",
            actions: "swal2-actions"
        }
    });

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
        heightAuto: false
    };

    if (html) {
        config.html = html;
    } else {
        config.text = mensaje;
    }

    return mixin.fire(config).then(result => {
        if (callback) callback(result);
        return result;
    });
};