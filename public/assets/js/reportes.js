// reportes.js - Refactorizado con lifecycle management

(function() {
    'use strict';

    // ==================== CONFIGURACIÓN DEL MÓDULO ====================
    
    const MODULE_NAME = 'reportes';
    
    // Estado privado del módulo
    let moduleState = {
        datosReporteActual: null,
        tipoReporteActual: null,
        cargandoDesde: null,
        eventListeners: [],
        modalesAbiertos: []
    };

    // Constantes
    const MIN_TIEMPO_CARGA = 1500; // 1.5 segundos

    // ==================== CLASE PRINCIPAL DEL MÓDULO ====================
    
    class ReportesModule {
        constructor() {
            this.state = moduleState;
            this.initialized = false;
        }

        // ============ INICIALIZACIÓN ============
        
        async init() {
            if (this.initialized) {
                console.warn('⚠️ Módulo Reportes ya inicializado');
                return this;
            }

            try {
                // Configurar event listeners si hay botones de exportación
                this.configurarEventListeners();
                
                // Ocultar resultados inicialmente
                const resultadosDiv = document.getElementById('resultadosReporte');
                if (resultadosDiv) {
                    resultadosDiv.style.display = 'none';
                }
                
                this.initialized = true;
                return this;
                
            } catch (error) {
                console.error('❌ Error al inicializar módulo Reportes:', error);
                throw error;
            }
        }

        // ============ EVENT LISTENERS ============
        
        configurarEventListeners() {
            // Los botones de reportes se manejan mediante onclick en el HTML
            // Aquí solo configuramos los botones de exportación si existen
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

            // Botones de exportación (si existen en el DOM)
            const btnExportarPDF = document.getElementById('btnExportarPDF');
            const btnExportarExcel = document.getElementById('btnExportarExcel');
            const btnExportarWord = document.getElementById('btnExportarWord');
            const btnImprimir = document.getElementById('btnImprimir');

            if (btnExportarPDF) addListener(btnExportarPDF, 'click', () => this.exportarPDF());
            if (btnExportarExcel) addListener(btnExportarExcel, 'click', () => this.exportarExcel());
            if (btnExportarWord) addListener(btnExportarWord, 'click', () => this.exportarWord());
            if (btnImprimir) addListener(btnImprimir, 'click', () => this.imprimirReporte());
        }

        // ==================== REPORTES DE PRACTICANTES ====================

        async generarReportePracticantesActivos() {
            try {
                this.mostrarCargando('Generando reporte de practicantes vigentes...');
                
                const response = await api.reportePracticantesActivos();
                
                if (response.success) {
                    this.state.datosReporteActual = response.data;
                    this.state.tipoReporteActual = 'practicantes_activos';
                    
                    this.renderizarReportePracticantes(response.data, 'practicantes-activos');
                } else {
                    this.mostrarError('Error al generar el reporte');
                }
            } catch (error) {
                console.error('❌ Error:', error);
                this.mostrarError('Error al generar el reporte de practicantes vigentes');
            } finally {
                await this.ocultarCargando();
                this.scrollToResultados();
            }
        }

        async generarReportePracticantesCompletados() {
            try {
                this.mostrarCargando('Generando reporte de prácticas completadas...');
                
                const response = await api.reportePracticantesCompletados();
                
                if (response.success) {
                    this.state.datosReporteActual = response.data;
                    this.state.tipoReporteActual = 'practicantes_completados';
                    
                    this.renderizarReportePracticantes(response.data, 'practicantes-completados');
                } else {
                    this.mostrarError('Error al generar el reporte');
                }
            } catch (error) {
                console.error('❌ Error:', error);
                this.mostrarError('Error al generar el reporte de prácticas completadas');
            } finally {
                await this.ocultarCargando();
                this.scrollToResultados();
            }
        }

        async generarReportePorArea() {
            try {
                const areaID = await this.mostrarModalSeleccionArea();
                
                this.mostrarCargando('Generando reporte por área...');
                
                const response = await api.reportePorArea(areaID);
                
                if (response.success) {
                    this.state.datosReporteActual = response.data;
                    this.state.tipoReporteActual = 'por_area';
                    
                    this.renderizarReportePorArea(response.data);
                } else {
                    this.mostrarError('Error al generar el reporte');
                }
            } catch (error) {
                console.error('❌ Error:', error);
                this.mostrarError('Error al generar el reporte por área');
            } finally {
                await this.ocultarCargando();
                this.scrollToResultados();
            }
        }

        async generarReportePorUniversidad() {
            try {
                this.mostrarCargando('Generando reporte por universidad...');
                
                const response = await api.reportePorUniversidad();
                
                if (response.success) {
                    this.state.datosReporteActual = response.data;
                    this.state.tipoReporteActual = 'por_universidad';
                    
                    this.renderizarReportePorUniversidad(response.data);
                } else {
                    this.mostrarError('Error al generar el reporte');
                }
            } catch (error) {
                console.error('❌ Error:', error);
                this.mostrarError('Error al generar el reporte por universidad');
            } finally {
                await this.ocultarCargando();
                this.scrollToResultados();
            }
        }

        // ==================== REPORTES DE ASISTENCIA ====================

        async generarReporteAsistenciaPracticante() {
            try {
                const params = await this.mostrarModalSeleccionPracticante();
                
                if (!params) return;
                
                this.mostrarCargando('Generando reporte de asistencias...');
                
                const response = await api.reporteAsistenciaPracticante(
                    params.practicanteID,
                    params.fechaInicio,
                    params.fechaFin
                );
                
                if (response.success) {
                    this.state.datosReporteActual = response.data;
                    this.state.tipoReporteActual = 'asistencia_practicante';
                    
                    this.renderizarReporteAsistenciaPracticante(response.data);
                } else {
                    this.mostrarError('Error al generar el reporte');
                }
            } catch (error) {
                console.error('❌ Error:', error);
                this.mostrarError('Error al generar el reporte de asistencias');
            } finally {
                await this.ocultarCargando();
                this.scrollToResultados();
            }
        }

        async generarReporteAsistenciaDia() {
            try {
                const fecha = await this.mostrarModalSeleccionFecha() || new Date().toISOString().split('T')[0];
                
                this.mostrarCargando('Generando reporte del día...');
                
                const response = await api.reporteAsistenciaDelDia(fecha);
                
                if (response.success) {
                    this.state.datosReporteActual = response.data;
                    this.state.tipoReporteActual = 'asistencia_dia';
                    
                    this.renderizarReporteAsistenciaDia(response.data);
                } else {
                    this.mostrarError('Error al generar el reporte');
                }
            } catch (error) {
                console.error('❌ Error:', error);
                this.mostrarError('Error al generar el reporte del día');
            } finally {
                await this.ocultarCargando();
                this.scrollToResultados();
            }
        }

        async generarReporteAsistenciaMensual() {
            try {
                const params = await this.mostrarModalSeleccionMes();
                
                if (!params) return;
                
                this.mostrarCargando('Generando reporte mensual...');
                
                const response = await api.reporteAsistenciaMensual(params.mes, params.anio);
                
                if (response.success) {
                    this.state.datosReporteActual = response.data;
                    this.state.tipoReporteActual = 'asistencia_mensual';
                    
                    this.renderizarReporteAsistenciaMensual(response.data);
                } else {
                    this.mostrarError('Error al generar el reporte');
                }
            } catch (error) {
                console.error('❌ Error:', error);
                this.mostrarError('Error al generar el reporte mensual');
            } finally {
                await this.ocultarCargando();
                this.scrollToResultados();
            }
        }

        async generarReporteAsistenciaAnual() {
            try {
                const params = await this.mostrarModalSeleccionYear();
                
                if (!params) return;
                
                this.mostrarCargando('Generando reporte anual...');
                
                const response = await api.reporteAsistenciaAnual(params.anio);
                
                if (response.success) {
                    this.state.datosReporteActual = response.data;
                    this.state.tipoReporteActual = 'asistencia_anual';
                    
                    this.renderizarReporteAsistenciaAnual(response.data);
                } else {
                    this.mostrarError('Error al generar el reporte');
                }
            } catch (error) {
                console.error('❌ Error:', error);
                this.mostrarError('Error al generar el reporte anual');
            } finally {
                await this.ocultarCargando();
                this.scrollToResultados();
            }
        }

        async generarReporteHorasAcumuladas() {
            try {
                this.mostrarCargando('Generando reporte de horas acumuladas...');
                
                const response = await api.reporteHorasAcumuladas();
                
                if (response.success) {
                    this.state.datosReporteActual = response.data;
                    this.state.tipoReporteActual = 'horas_acumuladas';
                    
                    this.renderizarReporteHorasAcumuladas(response.data);
                } else {
                    this.mostrarError('Error al generar el reporte');
                }
            } catch (error) {
                console.error('❌ Error:', error);
                this.mostrarError('Error al generar el reporte de horas acumuladas');
            } finally {
                await this.ocultarCargando();
                this.scrollToResultados();
            }
        }

        // ==================== REPORTES ESTADÍSTICOS ====================

        async generarReporteEstadisticasGenerales() {
            try {
                this.mostrarCargando('Generando estadísticas generales...');
                
                const response = await api.reporteEstadisticasGenerales();
                
                if (response.success) {
                    this.state.datosReporteActual = response.data;
                    this.state.tipoReporteActual = 'estadisticas_generales';
                    
                    this.renderizarReporteEstadisticas(response.data);
                } else {
                    this.mostrarError('Error al generar el reporte');
                }
            } catch (error) {
                console.error('❌ Error:', error);
                this.mostrarError('Error al generar las estadísticas generales');
            } finally {
                await this.ocultarCargando();
                this.scrollToResultados();
            }
        }

        async generarReportePromedioHoras() {
            try {
                this.mostrarCargando('Generando reporte de promedio de horas...');
                
                const response = await api.reportePromedioHoras();
                
                if (response.success) {
                    this.state.datosReporteActual = response.data;
                    this.state.tipoReporteActual = 'promedio_horas';
                    
                    this.renderizarReportePromedioHoras(response.data);
                } else {
                    this.mostrarError('Error al generar el reporte');
                }
            } catch (error) {
                console.error('❌ Error:', error);
                this.mostrarError('Error al generar el reporte de promedio de horas');
            } finally {
                await this.ocultarCargando();
                this.scrollToResultados();
            }
        }

        async generarReporteComparativoAreas() {
            try {
                this.mostrarCargando('Generando reporte comparativo de áreas...');
                
                const response = await api.reporteComparativoAreas();
                
                if (response.success) {
                    this.state.datosReporteActual = response.data;
                    this.state.tipoReporteActual = 'comparativo_areas';
                    
                    this.renderizarReporteComparativoAreas(response.data);
                } else {
                    this.mostrarError('Error al generar el reporte');
                }
            } catch (error) {
                console.error('❌ Error:', error);
                this.mostrarError('Error al generar el reporte comparativo de áreas');
            } finally {
                await this.ocultarCargando();
                this.scrollToResultados();
            }
        }

        async generarReporteCompleto() {
            try {
                this.mostrarCargando('Generando reporte completo...');
                
                const response = await api.reporteCompleto();
                
                if (response.success) {
                    this.state.datosReporteActual = response.data;
                    this.state.tipoReporteActual = 'completo';
                    
                    this.renderizarReporteCompleto(response.data);
                } else {
                    this.mostrarError('Error al generar el reporte');
                }
            } catch (error) {
                console.error('❌ Error:', error);
                this.mostrarError('Error al generar el reporte completo');
            } finally {
                await this.ocultarCargando();
                this.scrollToResultados();
            }
        }

        // ==================== FUNCIONES DE RENDERIZADO ====================

        renderizarReportePracticantes(datos, idSeccion) {
            const resultadosDiv = document.getElementById('resultadosReporte');
            const tablaDiv = document.getElementById('tablaResultados');
            
            if (!tablaDiv || !resultadosDiv) {
                console.error('❌ Contenedores de reporte no encontrados');
                return;
            }
            
            let html = `
                <div class="reporte-header">
                    <h4>${datos.titulo}</h4>
                    <p class="text-muted">Total de practicantes: ${datos.total}</p>
                    <p class="text-muted">Fecha: ${new Date(datos.fecha).toLocaleString('es-PE')}</p>
                </div>
                
                <div class="table-container">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Nombre Completo</th>
                                <th>DNI</th>
                                <th>Email</th>
                                <th>Universidad</th>
                                <th>Carrera</th>
                                <th>Área</th>
                                <th>Fecha Entrada</th>
                                <th>Fecha Salida</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            if (datos.practicantes && datos.practicantes.length > 0) {
                datos.practicantes.forEach(p => {
                    html += `
                        <tr>
                            <td>${p.NombreCompleto}</td>
                            <td>${p.DNI}</td>
                            <td>${p.Email}</td>
                            <td>${p.Universidad}</td>
                            <td>${p.Carrera}</td>
                            <td>${p.AreaNombre || 'N/A'}</td>
                            <td>${this.formatearFecha(p.FechaEntrada)}</td>
                            <td>${this.formatearFecha(p.FechaSalida)}</td>
                            <td><span class="badge bg-${p.Estado === 'Vigente' ? 'success' : 'secondary'}">${p.Estado}</span></td>
                        </tr>
                    `;
                });
            } else {
                html += '<tr><td colspan="9" class="text-center">No hay datos para mostrar</td></tr>';
            }
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            tablaDiv.innerHTML = html;
            resultadosDiv.classList.remove('hidden');
            resultadosDiv.style.display = 'block';
        }

        renderizarReportePorArea(datos) {
            const resultadosDiv = document.getElementById('resultadosReporte');
            const tablaDiv = document.getElementById('tablaResultados');
            
            if (!tablaDiv || !resultadosDiv) return;
            
            let html = `
                <div class="reporte-header">
                    <h4>${datos.titulo}</h4>
                    <p class="text-muted">Fecha: ${new Date(datos.fecha).toLocaleString('es-PE')}</p>
                </div>
            `;
            
            if (datos.areas && datos.areas.length > 0) {
                datos.areas.forEach(area => {
                    html += `
                        <div class="area-section mb-4">
                            <h5 class="area-title">${area.AreaNombre}</h5>
                            <div class="area-stats">
                                <span class="badge bg-primary">Total: ${area.TotalPracticantes}</span>
                                <span class="badge bg-success">Activos: ${area.Activos}</span>
                                <span class="badge bg-secondary">Completados: ${area.Completados}</span>
                            </div>
                            
                            <div class="table-container">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>DNI</th>
                                            <th>Universidad</th>
                                            <th>Estado</th>
                                            <th>Fecha Entrada</th>
                                            <th>Fecha Salida</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                    `;
                    
                    if (area.practicantes && area.practicantes.length > 0) {
                        area.practicantes.forEach(p => {
                            html += `
                                <tr>
                                    <td>${p.NombreCompleto}</td>
                                    <td>${p.DNI}</td>
                                    <td>${p.Universidad}</td>
                                    <td><span class="badge bg-${p.Estado === 'En Proceso' ? 'success' : 'secondary'}">${p.Estado}</span></td>
                                    <td>${this.formatearFecha(p.FechaEntrada)}</td>
                                    <td>${this.formatearFecha(p.FechaSalida)}</td>
                                </tr>
                            `;
                        });
                    } else {
                        html += '<tr><td colspan="6" class="text-center">No hay practicantes en esta área</td></tr>';
                    }
                    
                    html += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                });
            } else {
                html += '<p class="text-center">No hay datos para mostrar</p>';
            }
            
            tablaDiv.innerHTML = html;
            resultadosDiv.classList.remove('hidden');
            resultadosDiv.style.display = 'block';
        }

        renderizarReportePorUniversidad(datos) {
            const resultadosDiv = document.getElementById('resultadosReporte');
            const tablaDiv = document.getElementById('tablaResultados');
            
            if (!tablaDiv || !resultadosDiv) return;
            
            let html = `
                <div class="reporte-header">
                    <h4>${datos.titulo}</h4>
                    <p class="text-muted">Fecha: ${new Date(datos.fecha).toLocaleString('es-PE')}</p>
                </div>
            `;
            
            if (datos.universidades && datos.universidades.length > 0) {
                datos.universidades.forEach(uni => {
                    html += `
                        <div class="universidad-section mb-4">
                            <h5 class="universidad-title">${uni.Universidad}</h5>
                            <div class="universidad-stats">
                                <span class="badge bg-primary">Total: ${uni.TotalPracticantes}</span>
                                <span class="badge bg-success">Vigentes: ${uni.Activos}</span>
                                <span class="badge bg-secondary">Finalizados: ${uni.Completados}</span>
                            </div>
                            
                            <div class="table-container">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>DNI</th>
                                            <th>Carrera</th>
                                            <th>Área</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                    `;
                    
                    if (uni.practicantes && uni.practicantes.length > 0) {
                        uni.practicantes.forEach(p => {
                            html += `
                                <tr>
                                    <td>${p.NombreCompleto}</td>
                                    <td>${p.DNI}</td>
                                    <td>${p.Carrera}</td>
                                    <td>${p.AreaNombre || 'N/A'}</td>
                                    <td><span class="badge bg-${p.Estado === 'En Proceso' ? 'success' : 'secondary'}">${p.Estado || 'Sin asignar'}</span></td>
                                </tr>
                            `;
                        });
                    } else {
                        html += '<tr><td colspan="5" class="text-center">No hay practicantes de esta universidad</td></tr>';
                    }
                    
                    html += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                });
            } else {
                html += '<p class="text-center">No hay datos para mostrar</p>';
            }
            
            tablaDiv.innerHTML = html;
            resultadosDiv.classList.remove('hidden');
            resultadosDiv.style.display = 'block';
        }

        // Continuaré con los demás métodos de renderizado en la siguiente respuesta...
        // (Por límite de caracteres)

        renderizarReporteAsistenciaPracticante(datos) {
            const resultadosDiv = document.getElementById('resultadosReporte');
            const tablaDiv = document.getElementById('tablaResultados');
            
            if (!tablaDiv || !resultadosDiv) return;
            
            let html = `
                <div class="reporte-header">
                    <h4>${datos.titulo}</h4>
                    <div class="practicante-info">
                        <p><strong>Practicante:</strong> ${datos.practicante.NombreCompleto}</p>
                        <p><strong>DNI:</strong> ${datos.practicante.DNI}</p>
                        <p><strong>Universidad:</strong> ${datos.practicante.Universidad}</p>
                        <p><strong>Área:</strong> ${datos.practicante.AreaNombre || 'N/A'}</p>
                    </div>
                    <div class="stats-summary">
                        <span class="badge bg-info">Total Asistencias: ${datos.totalAsistencias}</span>
                        <span class="badge bg-success">Total Horas: ${parseFloat(datos.totalHoras).toFixed(2)}</span>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Turno</th>
                                <th>Hora Entrada</th>
                                <th>Hora Salida</th>
                                <th>Horas Trabajadas</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            if (datos.asistencias && datos.asistencias.length > 0) {
                datos.asistencias.forEach(a => {
                    html += `
                        <tr>
                            <td>${this.formatearFecha(a.Fecha)}</td>
                            <td>${a.TurnoNombre}</td>
                            <td>${a.HoraEntrada || 'N/A'}</td>
                            <td>${a.HoraSalida || 'En proceso'}</td>
                            <td>${a.HorasTrabajadas ? parseFloat(a.HorasTrabajadas).toFixed(2) + ' hrs' : '-'}</td>
                        </tr>
                    `;
                });
            } else {
                html += '<tr><td colspan="5" class="text-center">No hay asistencias registradas</td></tr>';
            }
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            tablaDiv.innerHTML = html;
            resultadosDiv.classList.remove('hidden');
            resultadosDiv.style.display = 'block';
        }

        renderizarReporteAsistenciaDia(datos) {
            const resultadosDiv = document.getElementById('resultadosReporte');
            const tablaDiv = document.getElementById('tablaResultados');
            
            if (!tablaDiv || !resultadosDiv) return;
            
            let html = `
                <div class="reporte-header">
                    <h4>${datos.titulo}</h4>
                    <p class="text-muted">Fecha: ${this.formatearFecha(datos.fecha)}</p>
                    <p class="text-muted">Total de practicantes: ${datos.totalPracticantes}</p>
                </div>
                
                <div class="table-container">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Practicante</th>
                                <th>DNI</th>
                                <th>Área</th>
                                <th>Turno</th>
                                <th>Hora Entrada</th>
                                <th>Hora Salida</th>
                                <th>Horas Trabajadas</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            if (datos.asistencias && datos.asistencias.length > 0) {
                datos.asistencias.forEach(a => {
                    html += `
                        <tr>
                            <td>${a.NombreCompleto}</td>
                            <td>${a.DNI}</td>
                            <td>${a.AreaNombre || 'N/A'}</td>
                            <td>${a.TurnoNombre}</td>
                            <td>${a.HoraEntrada}</td>
                            <td>${a.HoraSalida || 'En proceso'}</td>
                            <td>${a.HorasTrabajadas ? parseFloat(a.HorasTrabajadas).toFixed(2) + ' hrs' : '-'}</td>
                        </tr>
                    `;
                });
            } else {
                html += '<tr><td colspan="7" class="text-center">No hay asistencias para este día</td></tr>';
            }
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            tablaDiv.innerHTML = html;
            resultadosDiv.classList.remove('hidden');
            resultadosDiv.style.display = 'block';
        }

        renderizarReporteAsistenciaMensual(datos) {
            const resultadosDiv = document.getElementById('resultadosReporte');
            const tablaDiv = document.getElementById('tablaResultados');
            
            if (!tablaDiv || !resultadosDiv) return;
            
            const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            
            let html = `
                <div class="reporte-header">
                    <h4>${datos.titulo}</h4>
                    <p class="text-muted">Periodo: ${meses[datos.mes - 1]} ${datos.anio}</p>
                </div>
                
                <div class="table-container">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Practicante</th>
                                <th>Área</th>
                                <th>Días Asistidos</th>
                                <th>Total Horas</th>
                                <th>Promedio Diario</th>
                            </tr>
                        </thead>
                    <tbody>
        `;
        
        if (datos.resumen && datos.resumen.length > 0) {
            datos.resumen.forEach(r => {
                const promedio = r.diasAsistidos > 0 ? (r.totalHoras / r.diasAsistidos).toFixed(2) : 0;
                html += `
                    <tr>
                        <td>${r.practicante}</td>
                        <td>${r.area || 'N/A'}</td>
                        <td>${r.diasAsistidos}</td>
                        <td>${r.totalHoras.toFixed(2)} hrs</td>
                        <td>${promedio} hrs</td>
                    </tr>
                `;
            });
        } else {
            html += '<tr><td colspan="5" class="text-center">No hay datos para este mes</td></tr>';
        }
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        tablaDiv.innerHTML = html;
        resultadosDiv.classList.remove('hidden');
        resultadosDiv.style.display = 'block';
    }

    renderizarReporteAsistenciaAnual(datos) {
        const resultadosDiv = document.getElementById('resultadosReporte');
        const tablaDiv = document.getElementById('tablaResultados');
        
        if (!tablaDiv || !resultadosDiv) return;
        
        let html = `
            <div class="reporte-header">
                <h4>${datos.titulo}</h4>
                <p class="text-muted">Año: ${datos.anio}</p>
            </div>
            
            <div class="table-container">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Practicante</th>
                            <th>Área</th>
                            <th>Días Asistidos</th>
                            <th>Total Horas</th>
                            <th>Promedio Horas / Día</th>
                            <th>Meses Asistidos</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        if (datos.resumen && datos.resumen.length > 0) {
            datos.resumen.forEach(r => {
                const promedio = r.diasAsistidos > 0 
                    ? (r.totalHoras / r.diasAsistidos).toFixed(2) 
                    : 0;

                html += `
                    <tr>
                        <td>${r.practicante}</td>
                        <td>${r.area || 'N/A'}</td>
                        <td>${r.diasAsistidos}</td>
                        <td>${r.totalHoras.toFixed(2)} hrs</td>
                        <td>${promedio} hrs</td>
                        <td>${r.mesesAsistidos || '—'}</td>
                    </tr>
                `;
            });
        } else {
            html += '<tr><td colspan="6" class="text-center">No hay datos para este año</td></tr>';
        }
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        tablaDiv.innerHTML = html;
        resultadosDiv.classList.remove('hidden');
        resultadosDiv.style.display = 'block';
    }

    renderizarReporteHorasAcumuladas(datos) {
        const resultadosDiv = document.getElementById('resultadosReporte');
        const tablaDiv = document.getElementById('tablaResultados');
        
        if (!tablaDiv || !resultadosDiv) return;
        
        let html = `
            <div class="reporte-header">
                <h4>${datos.titulo}</h4>
                <p class="text-muted">Fecha: ${new Date(datos.fecha).toLocaleString('es-PE')}</p>
            </div>
            
            <div class="table-container">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Practicante</th>
                            <th>DNI</th>
                            <th>Área</th>
                            <th>Total Asistencias</th>
                            <th>Total Horas</th>
                            <th>Promedio Horas/Día</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        if (datos.practicantes && datos.practicantes.length > 0) {
            datos.practicantes.forEach(p => {
                html += `
                    <tr>
                        <td>${p.NombreCompleto}</td>
                        <td>${p.DNI}</td>
                        <td>${p.AreaNombre || 'N/A'}</td>
                        <td>${p.TotalAsistencias}</td>
                        <td><strong>${parseFloat(p.TotalHoras).toFixed(2)} hrs</strong></td>
                        <td>${parseFloat(p.PromedioHoras).toFixed(2)} hrs</td>
                    </tr>
                `;
            });
        } else {
            html += '<tr><td colspan="6" class="text-center">No hay datos para mostrar</td></tr>';
        }
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        tablaDiv.innerHTML = html;
        resultadosDiv.classList.remove('hidden');
        resultadosDiv.style.display = 'block';
    }

    renderizarReporteEstadisticas(datos) {
        const resultadosDiv = document.getElementById('resultadosReporte');
        const tablaDiv = document.getElementById('tablaResultados');
        
        if (!tablaDiv || !resultadosDiv) return;
        
        let html = `
            <div class="reporte-header">
                <h4>${datos.titulo}</h4>
                <p class="text-muted">Fecha: ${new Date(datos.fecha).toLocaleString('es-PE')}</p>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary"><i class="fas fa-users"></i></div>
                        <div class="stat-info">
                            <h3>${datos.totalPracticantesActivos}</h3>
                            <p>Practicantes Vigentes</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-success"><i class="fas fa-graduation-cap"></i></div>
                        <div class="stat-info">
                            <h3>${datos.totalPracticantesCompletados}</h3>
                            <p>Prácticantes Finalizados</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-info"><i class="fas fa-building"></i></div>
                        <div class="stat-info">
                            <h3>${datos.totalAreas}</h3>
                            <p>Áreas Totales</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning"><i class="fas fa-clock"></i></div>
                        <div class="stat-info">
                            <h3>${parseFloat(datos.promedioHorasDiarias).toFixed(2)}</h3>
                            <p>Promedio Horas/Día</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <h5 class="mt-4">Distribución por Área</h5>
            <div class="table-container">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Área</th>
                            <th>Cantidad de Practicantes</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        if (datos.distribucionPorArea && datos.distribucionPorArea.length > 0) {
            datos.distribucionPorArea.forEach(area => {
                html += `
                    <tr>
                        <td>${area.area}</td>
                        <td><span class="badge bg-primary">${area.cantidad}</span></td>
                    </tr>
                `;
            });
        } else {
            html += '<tr><td colspan="2" class="text-center">No hay datos</td></tr>';
        }
        
        html += `
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                <p><strong>Asistencias del mes actual:</strong> ${datos.asistenciasMesActual}</p>
            </div>
        `;
        
        tablaDiv.innerHTML = html;
        resultadosDiv.classList.remove('hidden');
        resultadosDiv.style.display = 'block';
    }

    renderizarReportePromedioHoras(datos) {
        const resultadosDiv = document.getElementById('resultadosReporte');
        const tablaDiv = document.getElementById('tablaResultados');
        
        if (!tablaDiv || !resultadosDiv) return;
        
        let html = `
            <div class="reporte-header">
                <h4>${datos.titulo}</h4>
                <p class="text-muted">Fecha: ${new Date(datos.fecha).toLocaleString('es-PE')}</p>
            </div>
            
            <div class="table-container">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Posición</th>
                            <th>Practicante</th>
                            <th>Área</th>
                            <th>Total Asistencias</th>
                            <th>Total Horas</th>
                            <th>Promedio Horas/Día</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        if (datos.practicantes && datos.practicantes.length > 0) {
            datos.practicantes.forEach((p, index) => {
                html += `
                    <tr>
                        <td><strong>#${index + 1}</strong></td>
                        <td>${p.NombreCompleto}</td>
                        <td>${p.AreaNombre || 'N/A'}</td>
                        <td>${p.TotalAsistencias}</td>
                        <td>${parseFloat(p.TotalHoras).toFixed(2)} hrs</td>
                        <td><span class="badge bg-success">${parseFloat(p.PromedioHoras).toFixed(2)} hrs</span></td>
                    </tr>
                `;
            });
        } else {
            html += '<tr><td colspan="6" class="text-center">No hay datos para mostrar</td></tr>';
        }
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        tablaDiv.innerHTML = html;
        resultadosDiv.classList.remove('hidden');
        resultadosDiv.style.display = 'block';
    }

    renderizarReporteComparativoAreas(datos) {
        const resultadosDiv = document.getElementById('resultadosReporte');
        const tablaDiv = document.getElementById('tablaResultados');
        
        if (!tablaDiv || !resultadosDiv) return;
        
        let html = `
            <div class="reporte-header">
                <h4>${datos.titulo}</h4>
                <p class="text-muted">Fecha: ${new Date(datos.fecha).toLocaleString('es-PE')}</p>
            </div>
            
            <div class="table-container">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Área</th>
                            <th>Total Practicantes</th>
                            <th>Vigentes</th>
                            <th>Total Asistencias</th>
                            <th>Total Horas</th>
                            <th>Promedio Horas</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        if (datos.areas && datos.areas.length > 0) {
            datos.areas.forEach(area => {
                html += `
                    <tr>
                        <td><strong>${area.AreaNombre}</strong></td>
                        <td>${area.TotalPracticantes}</td>
                        <td><span class="badge bg-success">${area.Activos}</span></td>
                        <td>${area.TotalAsistencias}</td>
                        <td>${parseFloat(area.TotalHoras).toFixed(2)} hrs</td>
                        <td>${parseFloat(area.PromedioHoras).toFixed(2)} hrs</td>
                    </tr>
                `;
            });
        } else {
            html += '<tr><td colspan="6" class="text-center">No hay datos para mostrar</td></tr>';
        }
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        tablaDiv.innerHTML = html;
        resultadosDiv.classList.remove('hidden');
        resultadosDiv.style.display = 'block';
    }

    renderizarReporteCompleto(datos) {
        const resultadosDiv = document.getElementById('resultadosReporte');
        const tablaDiv = document.getElementById('tablaResultados');
        
        if (!tablaDiv || !resultadosDiv) return;
        
        let html = `
            <div class="reporte-header">
                <h4>${datos.titulo}</h4>
                <p class="text-muted">Fecha: ${new Date(datos.fecha).toLocaleString('es-PE')}</p>
            </div>
            
            <!-- Sección de Estadísticas -->
            <div class="report-section">
                <h5><i class="fas fa-chart-pie"></i> Estadísticas Generales</h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="stat-box">
                            <span class="stat-label">Practicantes Vigentes:</span>
                            <span class="stat-value">${datos.estadisticas.totalPracticantesActivos}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-box">
                            <span class="stat-label">Prácticas Completadas:</span>
                            <span class="stat-value">${datos.estadisticas.totalPracticantesCompletados}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-box">
                            <span class="stat-label">Promedio Horas/Día:</span>
                            <span class="stat-value">${parseFloat(datos.estadisticas.promedioHorasDiarias).toFixed(2)}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sección de Practicantes -->
            <div class="report-section">
                <h5><i class="fas fa-users"></i> Practicantes Vigentes</h5>
                <p>Total: ${datos.practicantes.total}</p>
            </div>
            
            <!-- Sección de Asistencias del día -->
            <div class="report-section">
                <h5><i class="fas fa-calendar-check"></i> Asistencias de Hoy</h5>
                <p>Total de practicantes presentes: ${datos.asistencias.totalPracticantes}</p>
            </div>
        `;
        
        tablaDiv.innerHTML = html;
        resultadosDiv.style.display = 'block';
    }

    // ==================== EXPORTACIONES ====================

    async exportarPDF() {
        if (!this.state.datosReporteActual || !this.state.tipoReporteActual) {
            this.mostrarError('No hay datos de reporte para exportar');
            return;
        }
        
        try {
            this.mostrarCargando('Generando PDF...');

            const response = await fetch('/gestorPracticantes/public/api/reportes/exportar-pdf', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    tipoReporte: this.state.tipoReporteActual,
                    datos: this.state.datosReporteActual
                })
            });

            if (!response.ok) {
                throw new Error('Error al generar el PDF');
            }

            const blob = await response.blob();

            await this.ocultarCargando();
            await new Promise(resolve => requestAnimationFrame(resolve));

            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `reporte_${this.state.tipoReporteActual}_${new Date().toISOString().split('T')[0]}.pdf`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);

            this.mostrarExito('PDF generado exitosamente');

        } catch (error) {
            console.error('❌ Error:', error);
            this.mostrarError('Error al exportar a PDF');
        }
    }

    async exportarExcel() {
        if (!this.state.datosReporteActual || !this.state.tipoReporteActual) {
            this.mostrarError('No hay datos de reporte para exportar');
            return;
        }
        
        try {
            this.mostrarCargando('Generando Excel...');

            const response = await fetch('/gestorPracticantes/public/api/reportes/exportar-excel', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    tipoReporte: this.state.tipoReporteActual,
                    datos: this.state.datosReporteActual
                })
            });

            if (!response.ok) {
                throw new Error('Error al generar el Excel');
            }

            const blob = await response.blob();

            await this.ocultarCargando();

            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `reporte_${this.state.tipoReporteActual}_${new Date().toISOString().split('T')[0]}.xlsx`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            this.mostrarExito('Excel generado exitosamente');

        } catch (error) {
            console.error('❌ Error:', error);
            await this.ocultarCargando();
            this.mostrarError('Error al exportar a Excel');
        }
    }

    async exportarWord() {
        if (!this.state.datosReporteActual || !this.state.tipoReporteActual) {
            this.mostrarError('No hay datos de reporte para exportar');
            return;
        }
        
        try {
            this.mostrarCargando('Generando Word...');
            
            const response = await fetch('/gestorPracticantes/public/api/reportes/exportar-word', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    tipoReporte: this.state.tipoReporteActual,
                    datos: this.state.datosReporteActual
                })
            });
            
            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `reporte_${this.state.tipoReporteActual}_${new Date().toISOString().split('T')[0]}.docx`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
                this.mostrarExito('Word generado exitosamente');
            } else {
                throw new Error('Error al generar el Word');
            }
        } catch (error) {
            console.error('❌ Error:', error);
            this.mostrarError('Error al exportar a Word');
        } finally {
            await this.ocultarCargando();
        }
    }

    imprimirReporte() {
        if (!this.state.datosReporteActual) {
            this.mostrarError('No hay datos de reporte para imprimir');
            return;
        }
        
        const contenido = document.getElementById('tablaResultados')?.innerHTML;
        
        if (!contenido) {
            this.mostrarError('No se encontró el contenido para imprimir');
            return;
        }
        
        const ventanaImpresion = window.open('', '_blank');
        
        ventanaImpresion.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Reporte - ${this.state.tipoReporteActual}</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        margin: 20px;
                    }
                    .reporte-header {
                        text-align: center;
                        margin-bottom: 30px;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                    }
                    th, td {
                        border: 1px solid #ddd;
                        padding: 8px;
                        text-align: left;
                    }
                    th {
                        background-color: #f2f2f2;
                    }
                    .badge {
                        padding: 5px 10px;
                        border-radius: 3px;
                        font-weight: bold;
                    }
                    .bg-success { background-color: #28a745; color: white; }
                    .bg-warning { background-color: #ffc107; color: black; }
                    .bg-primary { background-color: #007bff; color: white; }
                    .bg-secondary { background-color: #6c757d; color: white; }
                    .bg-info { background-color: #17a2b8; color: white; }
                    @media print {
                        body { margin: 0; }
                    }
                </style>
            </head>
            <body>
                ${contenido}
            </body>
            </html>
        `);
        
        ventanaImpresion.document.close();
        ventanaImpresion.focus();
        
        setTimeout(() => {
            ventanaImpresion.print();
            ventanaImpresion.close();
        }, 500);
    }

    // ==================== MODALES DE SELECCIÓN ====================

    async mostrarModalSeleccionArea() {
        return new Promise(async (resolve) => {
            try {
                const areas = await api.listarAreas();
                const listaAreas = Array.isArray(areas) ? areas : areas?.data || [];

                const modalHTML = `
                    <div class="modal fade" id="modalSeleccionArea" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Seleccionar Área</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label>Área (Opcional - Dejar vacío para todas)</label>
                                        <select class="form-control" id="selectArea">
                                            <option value="">Todas las áreas</option>
                                            ${listaAreas.map(a => `<option value="${a.AreaID}">${a.NombreArea}</option>`).join('')}
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="button" class="btn btn-primary" id="btnConfirmarArea">Generar Reporte</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                document.body.insertAdjacentHTML('beforeend', modalHTML);

                const modalElement = document.getElementById('modalSeleccionArea');
                const modal = new bootstrap.Modal(modalElement);
                
                this.state.modalesAbiertos.push(modalElement);
                
                modal.show();
                
                document.getElementById('btnConfirmarArea').addEventListener('click', () => {
                    const areaID = document.getElementById('selectArea').value;
                    modal.hide();
                    resolve(areaID || null);
                });

                modalElement.addEventListener('hidden.bs.modal', () => {
                    modalElement.remove();
                    const index = this.state.modalesAbiertos.indexOf(modalElement);
                    if (index > -1) {
                        this.state.modalesAbiertos.splice(index, 1);
                    }
                });

            } catch (error) {
                console.error('❌ Error:', error);
                resolve(null);
            }
        });
    }

    async mostrarModalSeleccionPracticante() {
        return new Promise(async (resolve) => {
            try {
                const practicantes = await api.listarNombrePracticantes();
                
                const modalHTML = `
                    <div class="modal fade" id="modalSeleccionPracticante" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Seleccionar Practicante y Periodo</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group mb-3">
                                        <label>Practicante *</label>
                                        <select class="form-control" id="selectPracticanteReporte" required>
                                            <option value="">Seleccione un practicante</option>
                                            ${practicantes.data.map(p => `<option value="${p.PracticanteID}">${p.NombreCompleto}</option>`).join('')}
                                        </select>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label>Fecha Inicio (Opcional)</label>
                                        <input type="date" class="form-control" id="fechaInicio">
                                    </div>
                                    <div class="form-group">
                                        <label>Fecha Fin (Opcional)</label>
                                        <input type="date" class="form-control" id="fechaFin">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="button" class="btn btn-primary" id="btnConfirmarPracticante">Generar Reporte</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                document.body.insertAdjacentHTML('beforeend', modalHTML);
                
                const modalElement = document.getElementById('modalSeleccionPracticante');
                const modal = new bootstrap.Modal(modalElement);
                
                this.state.modalesAbiertos.push(modalElement);
                
                modal.show();
                
                document.getElementById('btnConfirmarPracticante').addEventListener('click', () => {
                    const practicanteID = document.getElementById('selectPracticanteReporte').value;
                    const fechaInicio = document.getElementById('fechaInicio').value;
                    const fechaFin = document.getElementById('fechaFin').value;
                    
                    if (!practicanteID) {
                        mostrarAlerta({tipo:'info', mensaje:'Debe seleccionar un practicante'});
                        return;
                    }
                    
                    modal.hide();
                    resolve({ practicanteID, fechaInicio: fechaInicio || null, fechaFin: fechaFin || null });
                });
                
                modalElement.addEventListener('hidden.bs.modal', () => {
                    modalElement.remove();
                    const index = this.state.modalesAbiertos.indexOf(modalElement);
                    if (index > -1) {
                        this.state.modalesAbiertos.splice(index, 1);
                    }
                });
            } catch (error) {
                console.error('❌ Error:', error);
                resolve(null);
            }
        });
    }

    async mostrarModalSeleccionFecha() {
        return new Promise((resolve) => {
            const modalHTML = `
                <div class="modal fade" id="modalSeleccionFecha" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Seleccionar Fecha</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label>Fecha (Por defecto: Hoy)</label>
<input type="date" class="form-control" id="inputFecha" value="${new Date().toISOString().split('T')[0]}">
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
<button type="button" class="btn btn-primary" id="btnConfirmarFecha">Generar Reporte</button>
</div>
</div>
</div>
</div>
`;
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            
            const modalElement = document.getElementById('modalSeleccionFecha');
            const modal = new bootstrap.Modal(modalElement);
            
            this.state.modalesAbiertos.push(modalElement);
            
            modal.show();
            
            document.getElementById('btnConfirmarFecha').addEventListener('click', () => {
                const fecha = document.getElementById('inputFecha').value;
                modal.hide();
                resolve(fecha);
            });
            
            modalElement.addEventListener('hidden.bs.modal', () => {
                modalElement.remove();
                const index = this.state.modalesAbiertos.indexOf(modalElement);
                if (index > -1) {
                    this.state.modalesAbiertos.splice(index, 1);
                }
            });
        });
    }

    async mostrarModalSeleccionMes() {
        return new Promise((resolve) => {
            const fechaActual = new Date();
            const mesActual = fechaActual.getMonth() + 1;
            const anioActual = fechaActual.getFullYear();
            
            const modalHTML = `
                <div class="modal fade" id="modalSeleccionMes" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Seleccionar Mes y Año</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group mb-3">
                                    <label>Mes</label>
                                    <select class="form-control" id="selectMes">
                                        <option value="1" ${mesActual === 1 ? 'selected' : ''}>Enero</option>
                                        <option value="2" ${mesActual === 2 ? 'selected' : ''}>Febrero</option>
                                        <option value="3" ${mesActual === 3 ? 'selected' : ''}>Marzo</option>
                                        <option value="4" ${mesActual === 4 ? 'selected' : ''}>Abril</option>
                                        <option value="5" ${mesActual === 5 ? 'selected' : ''}>Mayo</option>
                                        <option value="6" ${mesActual === 6 ? 'selected' : ''}>Junio</option>
                                        <option value="7" ${mesActual === 7 ? 'selected' : ''}>Julio</option>
                                        <option value="8" ${mesActual === 8 ? 'selected' : ''}>Agosto</option>
                                        <option value="9" ${mesActual === 9 ? 'selected' : ''}>Septiembre</option>
                                        <option value="10" ${mesActual === 10 ? 'selected' : ''}>Octubre</option>
                                        <option value="11" ${mesActual === 11 ? 'selected' : ''}>Noviembre</option>
                                        <option value="12" ${mesActual === 12 ? 'selected' : ''}>Diciembre</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Año</label>
                                    <input type="number" class="form-control" id="inputAnio" value="${anioActual}" min="2020" max="${anioActual + 1}">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-primary" id="btnConfirmarMes">Generar Reporte</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            
            const modalElement = document.getElementById('modalSeleccionMes');
            const modal = new bootstrap.Modal(modalElement);
            
            this.state.modalesAbiertos.push(modalElement);
            
            modal.show();
            
            document.getElementById('btnConfirmarMes').addEventListener('click', () => {
                const mes = document.getElementById('selectMes').value;
                const anio = document.getElementById('inputAnio').value;
                modal.hide();
                resolve({ mes, anio });
            });
            
            modalElement.addEventListener('hidden.bs.modal', () => {
                modalElement.remove();
                const index = this.state.modalesAbiertos.indexOf(modalElement);
                if (index > -1) {
                    this.state.modalesAbiertos.splice(index, 1);
                }
            });
        });
    }

    async mostrarModalSeleccionYear() {
        return new Promise((resolve) => {
            const fechaActual = new Date();
            const anioActual = fechaActual.getFullYear();
            
            const modalHTML = `
                <div class="modal fade" id="modalSeleccionYear" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Seleccionar Año</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label>Año</label>
                                    <input type="number" class="form-control" id="inputAnio" value="${anioActual}" min="2020" max="${anioActual + 1}">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-primary" id="btnConfirmarYear">Generar Reporte</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            
            const modalElement = document.getElementById('modalSeleccionYear');
            const modal = new bootstrap.Modal(modalElement);
            
            this.state.modalesAbiertos.push(modalElement);
            
            modal.show();
            
            document.getElementById('btnConfirmarYear').addEventListener('click', () => {
                const anio = document.getElementById('inputAnio').value;
                modal.hide();
                resolve({ anio });
            });
            
            modalElement.addEventListener('hidden.bs.modal', () => {
                modalElement.remove();
                const index = this.state.modalesAbiertos.indexOf(modalElement);
                if (index > -1) {
                    this.state.modalesAbiertos.splice(index, 1);
                }
            });
        });
    }

    // ==================== UTILIDADES ====================

    formatearFecha(fecha) {
        if (!fecha) return 'N/A';
        try {
            const date = new Date(fecha);
            return date.toLocaleDateString('es-PE', { 
                year: 'numeric', 
                month: '2-digit', 
                day: '2-digit' 
            });
        } catch (error) {
            return fecha;
        }
    }

    mostrarCargando(mensaje = 'Cargando...') {
        this.state.cargandoDesde = Date.now();

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: mensaje,
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
        }
    }

    ocultarCargando() {
        return new Promise(resolve => {
            const tiempoTranscurrido = Date.now() - this.state.cargandoDesde;

            if (tiempoTranscurrido < MIN_TIEMPO_CARGA) {
                setTimeout(() => {
                    this.ocultarCargando().then(resolve);
                }, MIN_TIEMPO_CARGA - tiempoTranscurrido);
                return;
            }

            if (typeof Swal !== 'undefined') {
                Swal.close();
                setTimeout(resolve, 300);
            } else {
                resolve();
            }
        });
    }

    mostrarExito(mensaje) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: mensaje,
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            alert(mensaje);
        }
    }

    mostrarError(mensaje) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: mensaje
            });
        } else {
            alert(mensaje);
        }
    }

    scrollToResultados() {
        const resultados = document.getElementById('resultadosReporte');
        if (resultados && resultados.style.display !== 'none') {
            resultados.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    // ============ API PÚBLICA ============
    
    async recargar() {
        // Limpiar resultados si el módulo se recarga
        const resultadosDiv = document.getElementById('resultadosReporte');
        if (resultadosDiv) {
            resultadosDiv.style.display = 'none';
        }
        
        this.state.datosReporteActual = null;
        this.state.tipoReporteActual = null;
    }

    obtenerDatosActuales() {
        return {
            datos: this.state.datosReporteActual,
            tipo: this.state.tipoReporteActual
        };
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

            // Cerrar modales abiertos
            this.state.modalesAbiertos.forEach(modal => {
                if (modal && modal.parentElement) {
                    modal.remove();
                }
            });
            this.state.modalesAbiertos = [];

            // Limpiar estado
            this.state.datosReporteActual = null;
            this.state.tipoReporteActual = null;
            this.state.cargandoDesde = null;

            // Ocultar resultados
            const resultadosDiv = document.getElementById('resultadosReporte');
            if (resultadosDiv) {
                resultadosDiv.style.display = 'none';
            }

            // Marcar como no inicializado
            this.initialized = false;

        } catch (error) {
            console.error('❌ Error al limpiar módulo Reportes:', error);
        }
    }
}

// ==================== REGISTRO DEL MÓDULO ====================

const moduleDefinition = {
    async init() {
        const instance = new ReportesModule();
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
    
    // Exponer métodos globalmente para los onclick en el HTML
    const instance = new ReportesModule();
    
    window.generarReportePracticantesActivos = () => {
        const mod = window.moduleManager.getInstance(MODULE_NAME);
        if (mod) mod.generarReportePracticantesActivos();
    };
    
    window.generarReportePracticantesCompletados = () => {
        const mod = window.moduleManager.getInstance(MODULE_NAME);
        if (mod) mod.generarReportePracticantesCompletados();
    };
    
    window.generarReportePorArea = () => {
        const mod = window.moduleManager.getInstance(MODULE_NAME);
        if (mod) mod.generarReportePorArea();
    };
    
    window.generarReportePorUniversidad = () => {
        const mod = window.moduleManager.getInstance(MODULE_NAME);
        if (mod) mod.generarReportePorUniversidad();
    };
    
    window.generarReporteAsistenciaPracticante = () => {
        const mod = window.moduleManager.getInstance(MODULE_NAME);
        if (mod) mod.generarReporteAsistenciaPracticante();
    };
    
    window.generarReporteAsistenciaDia = () => {
        const mod = window.moduleManager.getInstance(MODULE_NAME);
        if (mod) mod.generarReporteAsistenciaDia();
    };
    
    window.generarReporteAsistenciaMensual = () => {
        const mod = window.moduleManager.getInstance(MODULE_NAME);
        if (mod) mod.generarReporteAsistenciaMensual();
    };
    
    window.generarReporteAsistenciaAnual = () => {
        const mod = window.moduleManager.getInstance(MODULE_NAME);
        if (mod) mod.generarReporteAsistenciaAnual();
    };
    
    window.generarReporteHorasAcumuladas = () => {
        const mod = window.moduleManager.getInstance(MODULE_NAME);
        if (mod) mod.generarReporteHorasAcumuladas();
    };
    
    window.generarReporteEstadisticasGenerales = () => {
        const mod = window.moduleManager.getInstance(MODULE_NAME);
        if (mod) mod.generarReporteEstadisticasGenerales();
    };
    
    window.generarReportePromedioHoras = () => {
        const mod = window.moduleManager.getInstance(MODULE_NAME);
        if (mod) mod.generarReportePromedioHoras();
    };
    
    window.generarReporteComparativoAreas = () => {
        const mod = window.moduleManager.getInstance(MODULE_NAME);
        if (mod) mod.generarReporteComparativoAreas();
    };
    
    window.generarReporteCompleto = () => {
        const mod = window.moduleManager.getInstance(MODULE_NAME);
        if (mod) mod.generarReporteCompleto();
    };
    
    window.exportarPDF = () => {
        const mod = window.moduleManager.getInstance(MODULE_NAME);
        if (mod) mod.exportarPDF();
    };
    
    window.exportarExcel = () => {
        const mod = window.moduleManager.getInstance(MODULE_NAME);
        if (mod) mod.exportarExcel();
    };
    
    window.exportarWord = () => {
        const mod = window.moduleManager.getInstance(MODULE_NAME);
        if (mod) mod.exportarWord();
    };
    
    window.imprimirReporte = () => {
        const mod = window.moduleManager.getInstance(MODULE_NAME);
        if (mod) mod.imprimirReporte();
    };
    
} else {
    console.error('❌ ModuleManager no está disponible para módulo Reportes');
}
})();