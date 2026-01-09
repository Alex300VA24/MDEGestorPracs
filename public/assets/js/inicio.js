// inicio.js - Módulo del Dashboard (Inicio)

(function() {
    'use strict';

    // ==================== CONFIGURACIÓN DEL MÓDULO ====================
    
    const MODULE_NAME = 'inicio';
    
    // Estado privado del módulo
    let moduleState = {
        intervalActualizacion: null,
        ultimaActualizacion: null,
        datosCache: null
    };

    // ==================== CLASE PRINCIPAL DEL MÓDULO ====================
    
    class InicioModule {
        constructor() {
            this.state = moduleState;
            this.initialized = false;
            this.INTERVALO_ACTUALIZACION = 60000; // 1 minuto
        }

        // ============ INICIALIZACIÓN ============
        
        async init() {
            if (this.initialized) {
                console.warn('⚠️ Módulo Inicio ya inicializado');
                return this;
            }

            try {
                // Cargar datos del dashboard
                await this.cargarDatosInicio();
                
                // Configurar actualización automática (opcional)
                this.configurarActualizacionAutomatica();
                
                this.initialized = true;
                
                return this;
                
            } catch (error) {
                console.error('❌ Error al inicializar módulo Inicio:', error);
                throw error;
            }
        }

        // ============ CARGA DE DATOS ============
        
        async cargarDatosInicio() {
            try {
                const areaID = sessionStorage.getItem('areaID');
                const nombreArea = sessionStorage.getItem('nombreArea');

                // Si es RRHH, no enviar areaID (ver todo)
                const params = (nombreArea === 'Gerencia de Recursos Humanos' || !areaID) 
                    ? {} 
                    : { areaID: areaID };

                const response = await api.obtenerDatosInicio(params);

                if (!response.success || !response.data) {
                    throw new Error('Respuesta inválida del servidor');
                }

                const data = response.data;
                
                // Guardar en caché
                this.state.datosCache = data;
                this.state.ultimaActualizacion = new Date();

                // Renderizar datos
                this.renderizarEstadisticas(data);
                this.renderizarActividadReciente(data.actividadReciente);

            } catch (error) {
                console.error('❌ Error al cargar datos del dashboard:', error);
                this.mostrarErrorCarga();
                throw error;
            }
        }

        // ============ RENDERIZADO ============
        
        renderizarEstadisticas(data) {
            const estadisticas = {
                'totalPracticantes': data.totalPracticantes || 0,
                'pendientesAprobacion': data.pendientesAprobacion || 0,
                'practicantesActivos': data.practicantesActivos || 0,
                'asistenciaHoy': data.asistenciaHoy || 0
            };

            Object.entries(estadisticas).forEach(([id, valor]) => {
                const elemento = document.getElementById(id);
                if (elemento) {
                    // Animación de contador (opcional)
                    this.animarContador(elemento, valor);
                }
            });
        }

        animarContador(elemento, valorFinal) {
            const valorActual = parseInt(elemento.textContent) || 0;
            
            if (valorActual === valorFinal) {
                return;
            }

            const duracion = 500; // ms
            const pasos = 20;
            const incremento = (valorFinal - valorActual) / pasos;
            const tiempoPorPaso = duracion / pasos;

            let paso = 0;
            const intervalo = setInterval(() => {
                paso++;
                const nuevoValor = Math.round(valorActual + (incremento * paso));
                elemento.textContent = nuevoValor;

                if (paso >= pasos) {
                    elemento.textContent = valorFinal;
                    clearInterval(intervalo);
                }
            }, tiempoPorPaso);
        }

        renderizarActividadReciente(actividades) {
            const actividadDiv = document.getElementById('actividadReciente');
            
            if (!actividadDiv) {
                console.warn('⚠️ Contenedor de actividad reciente no encontrado');
                return;
            }

            actividadDiv.innerHTML = '';

            if (!actividades || actividades.length === 0) {
                actividadDiv.innerHTML = `
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No hay actividad reciente.</p>
                    </div>
                `;
                return;
            }

            actividades.forEach(act => {
                const itemDiv = document.createElement('div');
                itemDiv.classList.add('actividad-item');
                
                const tiempo = this.formatearTiempo(act.MinutosTranscurridos);
                const colorClass = this.obtenerClaseColor(act.TipoActividad);
                const iconoDefault = this.obtenerIconoDefault(act.TipoActividad);
                
                itemDiv.innerHTML = `
                    <div class="d-flex align-items-start">
                        <div class="activity-icon me-3 ${colorClass}">
                            <i class="fas fa-${act.Icono || iconoDefault} fa-lg"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="mb-1">${this.escapeHtml(act.Descripcion)}</p>
                            <small class="text-muted">
                                <i class="far fa-clock"></i> Hace ${tiempo}
                            </small>
                        </div>
                    </div>
                `;
                
                actividadDiv.appendChild(itemDiv);
            });
        }

        mostrarErrorCarga() {
            // Mostrar error en estadísticas
            ['totalPracticantes', 'pendientesAprobacion', 'practicantesActivos', 'asistenciaHoy'].forEach(id => {
                const elemento = document.getElementById(id);
                if (elemento) {
                    elemento.textContent = '-';
                }
            });

            // Mostrar error en actividad reciente
            const actividadDiv = document.getElementById('actividadReciente');
            if (actividadDiv) {
                actividadDiv.innerHTML = `
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle"></i>
                        Error al cargar la actividad reciente
                    </div>
                `;
            }

            mostrarAlerta({
                tipo: 'error',
                titulo: 'Error',
                mensaje: 'No se pudieron cargar los datos del dashboard'
            });
        }

        // ============ ACTUALIZACIÓN AUTOMÁTICA ============
        
        configurarActualizacionAutomatica() {
            // Limpiar intervalo anterior si existe
            if (this.state.intervalActualizacion) {
                clearInterval(this.state.intervalActualizacion);
            }

            // Configurar nuevo intervalo
            this.state.intervalActualizacion = setInterval(async () => {
                try {
                    await this.cargarDatosInicio();
                } catch (error) {
                    console.error('❌ Error en actualización automática:', error);
                }
            }, this.INTERVALO_ACTUALIZACION);
        }

        // ============ UTILIDADES ============
        
        formatearTiempo(minutos) {
            if (minutos < 1) return "justo ahora";
            if (minutos < 60) return `${minutos} minuto${minutos > 1 ? 's' : ''}`;
            
            const horas = Math.floor(minutos / 60);
            if (horas < 24) return `${horas} hora${horas > 1 ? 's' : ''}`;
            
            const dias = Math.floor(horas / 24);
            return `${dias} día${dias > 1 ? 's' : ''}`;
        }

        obtenerClaseColor(tipo) {
            const colores = {
                'INSERT': 'text-success',
                'UPDATE': 'text-warning',
                'DELETE': 'text-danger',
                'LOGIN': 'text-info',
                'LOGOUT': 'text-secondary'
            };
            return colores[tipo] || 'text-info';
        }

        obtenerIconoDefault(tipo) {
            const iconos = {
                'INSERT': 'plus-circle',
                'UPDATE': 'edit',
                'DELETE': 'trash',
                'LOGIN': 'sign-in-alt',
                'LOGOUT': 'sign-out-alt'
            };
            return iconos[tipo] || 'circle';
        }

        escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // ============ API PÚBLICA ============
        
        async recargar() {
            if (this.initialized) {
                await this.cargarDatosInicio();
            }
        }

        obtenerDatos() {
            return this.state.datosCache;
        }

        // ============ LIMPIEZA ============
        
        async destroy() {

            try {
                // Limpiar intervalo de actualización
                if (this.state.intervalActualizacion) {
                    clearInterval(this.state.intervalActualizacion);
                    this.state.intervalActualizacion = null;
                }

                // Limpiar caché
                this.state.datosCache = null;
                this.state.ultimaActualizacion = null;

                // Marcar como no inicializado
                this.initialized = false;

            } catch (error) {
                console.error('❌ Error al limpiar módulo Inicio:', error);
            }
        }
    }

    // ==================== REGISTRO DEL MÓDULO ====================
    
    const moduleDefinition = {
        async init() {
            const instance = new InicioModule();
            await instance.init();
            return instance;
        },
        
        async destroy(instance) {
            if (instance && instance.destroy) {
                await instance.destroy();
            }
        }
    };

    // Registrar cuando el script se cargue
    if (window.moduleManager) {
        window.moduleManager.register(MODULE_NAME, moduleDefinition);
    } else {
        console.error('❌ ModuleManager no disponible para módulo Inicio');
    }

})();