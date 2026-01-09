// certificados.js - Refactorizado con lifecycle management

(function() {
    'use strict';

    // ==================== CONFIGURACIÓN DEL MÓDULO ====================
    
    const MODULE_NAME = 'certificados';
    
    // Estado privado del módulo
    let moduleState = {
        practicanteSeleccionado: null,
        eventListeners: [],
        estadisticasCache: null,
        practicantesCache: null
    };

    // ==================== CLASE PRINCIPAL DEL MÓDULO ====================
    
    class CertificadosModule {
        constructor() {
            this.state = moduleState;
            this.initialized = false;
        }

        // ============ INICIALIZACIÓN ============
        
        async init() {
            if (this.initialized) {
                console.warn('⚠️ Módulo Certificados ya inicializado');
                return this;
            }

            try {
                // Cargar datos iniciales
                await this.cargarEstadisticas();
                await this.cargarPracticantes();
                
                // Configurar event listeners
                this.configurarEventListeners();
                
                // Marcar como inicializado
                this.initialized = true;

                return this;
                
            } catch (error) {
                console.error('❌ Error al inicializar módulo Certificados:', error);
                throw error;
            }
        }

        // ============ CARGAR DATOS ============
        
        async cargarEstadisticas() {
            try {
                
                const response = await api.obtenerEstadisticasCertificados();
                
                if (!response || !response.data) {
                    throw new Error('Respuesta inválida del servidor');
                }
                
                this.state.estadisticasCache = response.data;
                
                // Actualizar UI
                const totalVigentes = document.getElementById('totalVigentes');
                const totalFinalizados = document.getElementById('totalFinalizados');
                
                if (totalVigentes) {
                    totalVigentes.textContent = response.data.totalVigentes || 0;
                }
                if (totalFinalizados) {
                    totalFinalizados.textContent = response.data.totalFinalizados || 0;
                }
                
            } catch (error) {
                console.error('❌ Error al cargar estadísticas:', error);
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error',
                    mensaje: 'No se pudieron cargar las estadísticas de certificados'
                });
            }
        }

        async cargarPracticantes() {
            try {
                
                const response = await api.listarPracticantesParaCertificado();
                
                if (!response || !response.data) {
                    throw new Error('Respuesta inválida del servidor');
                }
                
                this.state.practicantesCache = response.data;
                
                // Renderizar select
                this.renderizarSelectPracticantes(response.data);
                
            } catch (error) {
                console.error('❌ Error al cargar practicantes:', error);
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error',
                    mensaje: error.message || 'Error al cargar la lista de practicantes'
                });
            }
        }

        renderizarSelectPracticantes(practicantes) {
            const select = document.getElementById('selectPracticante');
            
            if (!select) {
                console.warn('⚠️ Select de practicantes no encontrado');
                return;
            }
            
            select.innerHTML = '<option value="">-- Seleccione un practicante --</option>';
            
            practicantes.forEach(p => {
                const option = document.createElement('option');
                option.value = p.PracticanteID;
                option.textContent = `${p.NombreCompleto} (${p.Estado})`;
                option.dataset.practicante = JSON.stringify(p);
                select.appendChild(option);
            });
        }

        // ============ EVENT LISTENERS ============
        
        configurarEventListeners() {
            const addListener = (element, event, handler, options) => {
                if (!element) return;
                
                element.addEventListener(event, handler, options);
                
                this.state.eventListeners.push({
                    element,
                    event,
                    handler,
                    options
                });
            };

            // Select de practicante
            const selectPracticante = document.getElementById('selectPracticante');
            addListener(selectPracticante, 'change', (e) => this.handleSeleccionPracticante(e));

            // Botones del dialog
            const btnAbrirDialog = document.getElementById('btnAbrirDialog');
            addListener(btnAbrirDialog, 'click', () => this.abrirDialogCertificado());

            const btnCancelar = document.getElementById('btnCancelarCertificado');
            addListener(btnCancelar, 'click', () => this.cerrarDialogCertificado());

            const btnGenerar = document.getElementById('btnGenerarCertificado');
            addListener(btnGenerar, 'click', () => this.generarCertificado());

            // Cerrar dialog al hacer clic fuera
            const dialog = document.getElementById('dialogCertificado');
            addListener(dialog, 'click', (e) => {
                if (e.target === dialog) {
                    this.cerrarDialogCertificado();
                }
            });
        }

        // ============ SELECCIÓN DE PRACTICANTE ============
        
        async handleSeleccionPracticante(e) {
            const practicanteID = e.target.value;
            
            if (!practicanteID) {
                this.ocultarInformacion();
                return;
            }

            try {
                const option = e.target.options[e.target.selectedIndex];
                this.state.practicanteSeleccionado = JSON.parse(option.dataset.practicante);
                
                // Obtener información completa
                const response = await api.obtenerInformacionCertificado(practicanteID);
                
                if (!response || !response.data) {
                    throw new Error('No se pudo obtener la información');
                }
                
                this.mostrarInformacion(response.data);
                
            } catch (error) {
                console.error('❌ Error al cargar información:', error);
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error',
                    mensaje: 'Error al cargar la información del practicante'
                });
            }
        }

        // ============ MOSTRAR/OCULTAR INFORMACIÓN ============
        
        mostrarInformacion(data) {
            const infoSection = document.getElementById('infoSection');
            const emptyState = document.getElementById('emptyState');
            
            if (infoSection) {
                infoSection.classList.add('visible');
            }
            if (emptyState) {
                emptyState.style.display = 'none';
            }
            
            // Actualizar campos de información
            this.actualizarCampo('nombreCompleto', data.NombreCompleto);
            this.actualizarCampo('dni', data.DNI);
            this.actualizarCampo('carrera', data.Carrera);
            this.actualizarCampo('universidad', data.Universidad);
            this.actualizarCampo('area', data.Area || 'Sin área asignada');
            this.actualizarCampo('fechaInicio', this.formatearFecha(data.FechaEntrada));
            this.actualizarCampo('fechaTermino', data.FechaSalida ? this.formatearFecha(data.FechaSalida) : 'Vigente');
            this.actualizarCampo('totalHoras', (data.TotalHoras || 0) + ' horas');
            this.actualizarCampo('estado', data.Estado);

            // Badge de estado
            const badge = document.getElementById('estadoBadge');
            if (badge) {
                badge.textContent = data.Estado;
                badge.className = 'badge ' + (data.Estado === 'Vigente' ? 'vigente' : 'finalizado');
            }

            // Habilitar/deshabilitar botón según estado
            const btnGenerar = document.getElementById('btnAbrirDialog');
            if (btnGenerar) {
                if (data.EstadoAbrev === 'VIG' || data.EstadoAbrev === 'FIN') {
                    btnGenerar.disabled = false;
                    btnGenerar.title = 'Generar certificado y finalizar practicante';
                } else {
                    btnGenerar.disabled = true;
                    btnGenerar.title = 'El practicante ya finalizó sus prácticas';
                }
            }
        }

        actualizarCampo(id, valor) {
            const elemento = document.getElementById(id);
            if (elemento) {
                elemento.textContent = valor || '-';
            }
        }

        ocultarInformacion() {
            const infoSection = document.getElementById('infoSection');
            if (infoSection) {
                infoSection.classList.remove('visible');
            }
            
            this.state.practicanteSeleccionado = null;
        }

        // ============ DIALOG CERTIFICADO ============
        
        abrirDialogCertificado() {
            if (!this.state.practicanteSeleccionado) {
                console.warn('⚠️ No hay practicante seleccionado');
                return;
            }
            
            // Limpiar campos
            const numeroExpediente = document.getElementById('numeroExpedienteCertificado');
            const formatoDocumento = document.getElementById('formatoDocumentoCertificado');
            const mensajeEstado = document.getElementById('mensajeEstadoCertificado');
            
            if (numeroExpediente) numeroExpediente.value = '';
            if (formatoDocumento) formatoDocumento.value = 'word';
            if (mensajeEstado) mensajeEstado.classList.remove('visible');
            
            // Abrir dialog
            const dialog = document.getElementById('dialogCertificado');
            if (dialog) {
                dialog.classList.add('active');
            }
        }

        cerrarDialogCertificado() {
            const dialog = document.getElementById('dialogCertificado');
            if (dialog) {
                dialog.classList.remove('active');
            }
        }

        // ============ GENERAR CERTIFICADO ============
        
        async generarCertificado() {
            const numeroExpedienteInput = document.getElementById('numeroExpedienteCertificado');
            const formatoInput = document.getElementById('formatoDocumentoCertificado');
            
            if (!numeroExpedienteInput || !formatoInput) {
                console.error('❌ Campos del formulario no encontrados');
                return;
            }
            
            const numeroExpediente = numeroExpedienteInput.value.trim();
            const formato = formatoInput.value;
            
            // Validar número de expediente
            if (!numeroExpediente) {
                this.mostrarMensajeDialog('Por favor ingrese el número de expediente', 'error');
                return;
            }

            // Regex que acepta 5 o 6 dígitos al inicio
            const regexExpediente = /^\d{5,6}-\d{4}-\d{1}$/;
            if (!regexExpediente.test(numeroExpediente)) {
                this.mostrarMensajeDialog('Formato de expediente inválido. Use: XXXXX-YYYY-X o XXXXXX-YYYY-X', 'error');
                return;
            }

            // Confirmar acción
            const resultado = await mostrarAlerta({
                tipo: 'info',
                titulo: '¿Deseas continuar?',
                mensaje: "Al generar el certificado, el practicante será marcado como FINALIZADO y ya no podrá registrar más asistencias.",
                showCancelButton: true
            });

            if (!resultado.isConfirmed) {
                return;
            }

            const btnGenerar = document.getElementById('btnGenerarCertificado');
            
            try {
                // Deshabilitar botón
                if (btnGenerar) {
                    btnGenerar.disabled = true;
                    btnGenerar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
                }

                const response = await api.generarCertificadoHoras({
                    practicanteID: this.state.practicanteSeleccionado.PracticanteID,
                    numeroExpediente,
                    formato
                });

                if (response.success) {
                    this.mostrarMensajeDialog(response.message, 'success');
                    
                    // Descargar el archivo
                    if (response.data && response.data.data) {
                        const link = document.createElement('a');
                        link.href = response.data.data.url;
                        link.download = response.data.data.nombreArchivo;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    }

                    // Esperar y recargar
                    setTimeout(async () => {
                        this.cerrarDialogCertificado();
                        
                        // Recargar datos
                        await this.cargarEstadisticas();
                        await this.cargarPracticantes();
                        
                        // Limpiar selección
                        const select = document.getElementById('selectPracticante');
                        if (select) select.value = '';
                        
                        this.ocultarInformacion();
                    }, 2000);
                    
                } else {
                    this.mostrarMensajeDialog(response.message || 'Error al generar el certificado', 'error');
                }
                
            } catch (error) {
                console.error('❌ Error al generar certificado:', error);
                this.mostrarMensajeDialog(error.message || 'Error al generar el certificado', 'error');
                
            } finally {
                // Rehabilitar botón
                if (btnGenerar) {
                    btnGenerar.disabled = false;
                    btnGenerar.innerHTML = '<i class="fas fa-download"></i> Generar Certificado';
                }
            }
        }

        // ============ UTILIDADES ============
        
        mostrarMensajeDialog(mensaje, tipo) {
            const mensajeDiv = document.getElementById('mensajeEstadoCertificado');
            
            if (!mensajeDiv) {
                console.warn('⚠️ Contenedor de mensajes no encontrado');
                return;
            }
            
            mensajeDiv.textContent = mensaje;
            mensajeDiv.className = `mensaje-estado ${tipo} visible`;
            
            // Auto-ocultar después de 5 segundos
            setTimeout(() => {
                mensajeDiv.classList.remove('visible');
            }, 5000);
        }

        formatearFecha(fecha) {
            if (!fecha) return 'No especificada';

            try {
                const [year, month, day] = fecha.split('-');
                return `${day}/${month}/${year}`;
            } catch (error) {
                console.error('Error al formatear fecha:', error);
                return fecha;
            }
        }

        // ============ API PÚBLICA ============
        
        async recargar() {
            if (this.initialized) {
                await this.cargarEstadisticas();
                await this.cargarPracticantes();
            }
        }

        obtenerEstadisticas() {
            return this.state.estadisticasCache;
        }

        obtenerPracticantes() {
            return this.state.practicantesCache;
        }

        // ============ LIMPIEZA ============
        
        async destroy() {

            try {
                // Limpiar event listeners
                this.state.eventListeners.forEach(({ element, event, handler, options }) => {
                    if (element) {
                        element.removeEventListener(event, handler, options);
                    }
                });
                this.state.eventListeners = [];

                // Limpiar estado
                this.state.practicanteSeleccionado = null;
                this.state.estadisticasCache = null;
                this.state.practicantesCache = null;

                // Ocultar información si está visible
                this.ocultarInformacion();
                
                // Cerrar dialog si está abierto
                const dialog = document.getElementById('dialogCertificado');
                if (dialog && dialog.classList.contains('active')) {
                    this.cerrarDialogCertificado();
                }

                // Marcar como no inicializado
                this.initialized = false;

            } catch (error) {
                console.error('❌ Error al limpiar módulo Certificados:', error);
            }
        }
    }

    // ==================== REGISTRO DEL MÓDULO ====================
    
    const moduleDefinition = {
        async init() {
            const instance = new CertificadosModule();
            await instance.init();
            return instance;
        },
        
        async destroy(instance) {
            if (instance && instance.destroy) {
                await instance.destroy();
            }
        }
    };

    if (window.moduleManager) {
        window.moduleManager.register(MODULE_NAME, moduleDefinition);
    } else {
        console.error('❌ ModuleManager no está disponible para módulo Certificados');
    }

})();