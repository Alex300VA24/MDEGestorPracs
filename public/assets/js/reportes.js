// reportes.js - Lógica completa para el sistema de reportes
window.initReportes = function() {
    console.log("Reportes iniciado");
    
    let datosReporteActual = null;
    let tipoReporteActual = null;

    // ==================== REPORTES DE PRACTICANTES ====================

    async function generarReportePracticantesActivos() {
        try {
            mostrarCargando('Generando reporte de practicantes vigentes...');
            
            const response = await api.reportePracticantesActivos();
            
            if (response.success) {
                datosReporteActual = response.data;
                tipoReporteActual = 'practicantes_activos';
                
                renderizarReportePracticantes(response.data, 'practicantes-activos');
            } else {
                mostrarError('Error al generar el reporte');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarError('Error al generar el reporte de practicantes vigentes');
        } finally {
            await ocultarCargando(); // 👈 Ahora espera a que cierre completamente
            
            // Hacer scroll después de que Swal se cerró
            const resultados = document.getElementById('resultadosReporte');
            if (resultados && resultados.style.display !== 'none') {
                resultados.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }

    async function generarReportePracticantesCompletados() {
        try {
            mostrarCargando('Generando reporte de prácticas completadas...');
            
            const response = await api.reportePracticantesCompletados();
            console.log(response);
            
            if (response.success) {
                datosReporteActual = response.data;
                tipoReporteActual = 'practicantes_completados';
                
                renderizarReportePracticantes(response.data, 'practicantes-completados');
            } else {
                mostrarError('Error al generar el reporte');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarError('Error al generar el reporte de prácticas completadas');
        } finally {
            await ocultarCargando(); // 👈 Ahora espera a que cierre completamente
            
            // Hacer scroll después de que Swal se cerró
            const resultados = document.getElementById('resultadosReporte');
            if (resultados && resultados.style.display !== 'none') {
                resultados.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }

    async function generarReportePorArea() {
        try {
            // Mostrar modal para seleccionar área (opcional)
            const areaID = await mostrarModalSeleccionArea();
            
            mostrarCargando('Generando reporte por área...');
            
            const response = await api.reportePorArea(areaID);
            
            if (response.success) {
                datosReporteActual = response.data;
                tipoReporteActual = 'por_area';
                
                renderizarReportePorArea(response.data);
            } else {
                mostrarError('Error al generar el reporte');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarError('Error al generar el reporte por área');
        } finally {
            await ocultarCargando(); // 👈 Ahora espera a que cierre completamente
            
            // Hacer scroll después de que Swal se cerró
            const resultados = document.getElementById('resultadosReporte');
            if (resultados && resultados.style.display !== 'none') {
                resultados.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }

    async function generarReportePorUniversidad() {
        try {
            mostrarCargando('Generando reporte por universidad...');
            
            const response = await api.reportePorUniversidad();
            
            if (response.success) {
                datosReporteActual = response.data;
                tipoReporteActual = 'por_universidad';
                
                renderizarReportePorUniversidad(response.data);
            } else {
                mostrarError('Error al generar el reporte');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarError('Error al generar el reporte por universidad');
        } finally {
            await ocultarCargando(); // 👈 Ahora espera a que cierre completamente
            
            // Hacer scroll después de que Swal se cerró
            const resultados = document.getElementById('resultadosReporte');
            if (resultados && resultados.style.display !== 'none') {
                resultados.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }

    // ==================== REPORTES DE ASISTENCIA ====================

    async function generarReporteAsistenciaPracticante() {
        try {
            // Mostrar modal para seleccionar practicante y fechas
            const params = await mostrarModalSeleccionPracticante();
            
            if (!params) return;
            
            mostrarCargando('Generando reporte de asistencias...');
            
            const response = await api.reporteAsistenciaPracticante(
                params.practicanteID,
                params.fechaInicio,
                params.fechaFin
            );
            
            if (response.success) {
                datosReporteActual = response.data;
                tipoReporteActual = 'asistencia_practicante';
                
                renderizarReporteAsistenciaPracticante(response.data);
            } else {
                mostrarError('Error al generar el reporte');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarError('Error al generar el reporte de asistencias');
        } finally {
            ocultarCargando();
        }
    }

    async function generarReporteAsistenciaDia() {
        try {
            // Mostrar modal para seleccionar fecha (opcional, por defecto hoy)
            const fecha = await mostrarModalSeleccionFecha() || new Date().toISOString().split('T')[0];
            
            mostrarCargando('Generando reporte del día...');
            
            const response = await api.reporteAsistenciaDelDia(fecha);
            
            if (response.success) {
                datosReporteActual = response.data;
                tipoReporteActual = 'asistencia_dia';
                
                renderizarReporteAsistenciaDia(response.data);
            } else {
                mostrarError('Error al generar el reporte');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarError('Error al generar el reporte del día');
        } finally {
            ocultarCargando();
        }
    }

    async function generarReporteAsistenciaMensual() {
        try {
            // Mostrar modal para seleccionar mes y año
            const params = await mostrarModalSeleccionMes();
            
            if (!params) return;
            
            mostrarCargando('Generando reporte mensual...');
            
            const response = await api.reporteAsistenciaMensual(params.mes, params.anio);
            
            if (response.success) {
                datosReporteActual = response.data;
                tipoReporteActual = 'asistencia_mensual';
                
                renderizarReporteAsistenciaMensual(response.data);
            } else {
                mostrarError('Error al generar el reporte');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarError('Error al generar el reporte mensual');
        } finally {
            ocultarCargando();
        }
    }

    async function generarReporteAsistenciaAnual() {
        try {
            // Mostrar modal para seleccionar mes y año
            const params = await mostrarModalSeleccionYear();
            
            if (!params) return;
            
            mostrarCargando('Generando reporte mensual...');
            
            const response = await api.reporteAsistenciaAnual(params.anio);
            
            if (response.success) {
                datosReporteActual = response.data;
                tipoReporteActual = 'asistencia_anual';
                
                renderizarReporteAsistenciaAnual(response.data);
            } else {
                mostrarError('Error al generar el reporte');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarError('Error al generar el reporte anual');
        } finally {
            ocultarCargando();
        }
    }

    async function generarReporteHorasAcumuladas() {
        try {
            mostrarCargando('Generando reporte de horas acumuladas...');
            
            const response = await api.reporteHorasAcumuladas();
            
            if (response.success) {
                datosReporteActual = response.data;
                tipoReporteActual = 'horas_acumuladas';
                
                renderizarReporteHorasAcumuladas(response.data);
            } else {
                mostrarError('Error al generar el reporte');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarError('Error al generar el reporte de horas acumuladas');
        } finally {
            ocultarCargando();
        }
    }

    // ==================== REPORTES ESTADÍSTICOS ====================

    async function generarReporteEstadisticasGenerales() {
        try {
            mostrarCargando('Generando estadísticas generales...');
            
            const response = await api.reporteEstadisticasGenerales();
            console.log(response);
            
            if (response.success) {
                datosReporteActual = response.data;
                tipoReporteActual = 'estadisticas_generales';
                
                renderizarReporteEstadisticas(response.data);
            } else {
                mostrarError('Error al generar el reporte');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarError('Error al generar las estadísticas generales');
        } finally {
            await await ocultarCargando(); // 👈 Ahora espera a que cierre completamente
            
            // Hacer scroll después de que Swal se cerró
            const resultados = document.getElementById('resultadosReporte');
            if (resultados && resultados.style.display !== 'none') {
                resultados.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }

    async function generarReportePromedioHoras() {
        try {
            mostrarCargando('Generando reporte de promedio de horas...');
            
            const response = await api.reportePromedioHoras();
            
            if (response.success) {
                datosReporteActual = response.data;
                tipoReporteActual = 'promedio_horas';
                
                renderizarReportePromedioHoras(response.data);
            } else {
                mostrarError('Error al generar el reporte');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarError('Error al generar el reporte de promedio de horas');
        } finally {
            await ocultarCargando(); // 👈 Ahora espera a que cierre completamente
            
            // Hacer scroll después de que Swal se cerró
            const resultados = document.getElementById('resultadosReporte');
            if (resultados && resultados.style.display !== 'none') {
                resultados.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }

    async function generarReporteComparativoAreas() {
        try {
            mostrarCargando('Generando reporte comparativo de áreas...');
            
            const response = await api.reporteComparativoAreas();
            
            if (response.success) {
                datosReporteActual = response.data;
                tipoReporteActual = 'comparativo_areas';
                
                renderizarReporteComparativoAreas(response.data);
            } else {
                mostrarError('Error al generar el reporte');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarError('Error al generar el reporte comparativo de áreas');
        } finally {
            await await await ocultarCargando(); // 👈 Ahora espera a que cierre completamente
            
            // Hacer scroll después de que Swal se cerró
            const resultados = document.getElementById('resultadosReporte');
            if (resultados && resultados.style.display !== 'none') {
                resultados.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }

    async function generarReporteCompleto() {
        try {
            mostrarCargando('Generando reporte completo...');
            
            const response = await api.reporteCompleto();
            console.log(response);
            
            if (response.success) {
                datosReporteActual = response.data;
                tipoReporteActual = 'completo';
                
                renderizarReporteCompleto(response.data);
            } else {
                mostrarError('Error al generar el reporte');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarError('Error al generar el reporte completo');
        } finally {
            await ocultarCargando(); // 👈 Ahora espera a que cierre completamente
            
            // Hacer scroll después de que Swal se cerró
            const resultados = document.getElementById('resultadosReporte');
            if (resultados && resultados.style.display !== 'none') {
                resultados.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }

    // ==================== FUNCIONES DE RENDERIZADO ====================

    function renderizarReportePracticantes(datos, idSeccion) {
        const resultadosDiv = document.getElementById('resultadosReporte');
        const tablaDiv = document.getElementById('tablaResultados');
        
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
                        <td>${formatearFecha(p.FechaEntrada)}</td>
                        <td>${formatearFecha(p.FechaSalida)}</td>
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
        
        // Scroll suave al resultado
        resultadosDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function renderizarReportePorArea(datos) {
        const resultadosDiv = document.getElementById('resultadosReporte');
        const tablaDiv = document.getElementById('tablaResultados');
        
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
                                <td>${formatearFecha(p.FechaEntrada)}</td>
                                <td>${formatearFecha(p.FechaSalida)}</td>
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
        resultadosDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function renderizarReportePorUniversidad(datos) {
        const resultadosDiv = document.getElementById('resultadosReporte');
        const tablaDiv = document.getElementById('tablaResultados');
        
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
        resultadosDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function renderizarReporteAsistenciaPracticante(datos) {
        const resultadosDiv = document.getElementById('resultadosReporte');
        const tablaDiv = document.getElementById('tablaResultados');
        
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
                        <td>${formatearFecha(a.Fecha)}</td>
                        <td>${a.TurnoNombre}</td>
                        <td>${a.HoraEntrada || 'N/A'}</td>
                        <td>${a.HoraSalida || 'En proceso'}</td>
                        <td>${a.HorasTrabajadas ? parseFloat(a.HorasTrabajadas).toFixed(2) + ' hrs' : '-'}</td>
                    </tr>
                `;
            });
        } else {
            html += '<tr><td colspan="6" class="text-center">No hay asistencias registradas</td></tr>';
        }
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        tablaDiv.innerHTML = html;
        resultadosDiv.classList.remove('hidden');
        resultadosDiv.style.display = 'block';
        resultadosDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function renderizarReporteAsistenciaDia(datos) {
        const resultadosDiv = document.getElementById('resultadosReporte');
        const tablaDiv = document.getElementById('tablaResultados');
        
        let html = `
            <div class="reporte-header">
                <h4>${datos.titulo}</h4>
                <p class="text-muted">Fecha: ${formatearFecha(datos.fecha)}</p>
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
            html += '<tr><td colspan="8" class="text-center">No hay asistencias para este día</td></tr>';
        }
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        tablaDiv.innerHTML = html;
        resultadosDiv.classList.remove('hidden');
        resultadosDiv.style.display = 'block';
        resultadosDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function renderizarReporteAsistenciaMensual(datos) {
        const resultadosDiv = document.getElementById('resultadosReporte');
        const tablaDiv = document.getElementById('tablaResultados');
        
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
        resultadosDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function renderizarReporteAsistenciaAnual(datos) {
        const resultadosDiv = document.getElementById('resultadosReporte');
        const tablaDiv = document.getElementById('tablaResultados');
        
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
        resultadosDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }


    function renderizarReporteHorasAcumuladas(datos) {
        const resultadosDiv = document.getElementById('resultadosReporte');
        const tablaDiv = document.getElementById('tablaResultados');
        
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
        resultadosDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function renderizarReporteEstadisticas(datos) {
        const resultadosDiv = document.getElementById('resultadosReporte');
        const tablaDiv = document.getElementById('tablaResultados');
        
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
        resultadosDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function renderizarReportePromedioHoras(datos) {
        const resultadosDiv = document.getElementById('resultadosReporte');
        const tablaDiv = document.getElementById('tablaResultados');
        
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
        resultadosDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function renderizarReporteComparativoAreas(datos) {
        const resultadosDiv = document.getElementById('resultadosReporte');
        const tablaDiv = document.getElementById('tablaResultados');
        
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
        resultadosDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function renderizarReporteCompleto(datos) {
        console.log(datos);
        const resultadosDiv = document.getElementById('resultadosReporte');
        const tablaDiv = document.getElementById('tablaResultados');
        
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
        resultadosDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // ==================== EXPORTACIONES ====================

    async function exportarPDF() {
    if (!datosReporteActual || !tipoReporteActual) {
        mostrarError('No hay datos de reporte para exportar');
        return;
    }
    
    try {
        mostrarCargando('Generando PDF...');

        const response = await fetch('/gestorPracticantes/public/api/reportes/exportar-pdf', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                tipoReporte: tipoReporteActual,
                datos: datosReporteActual
            })
        });

        if (!response.ok) {
            throw new Error('Error al generar el PDF');
        }

        const blob = await response.blob();

        // 🔥 OCULTAMOS EL LOADING ANTES DE DESCARGAR
        ocultarCargando();

        // 🔥 Forzar actualización de la UI antes de abrir la descarga
        await new Promise(resolve => requestAnimationFrame(resolve));

        // 🔥 Lanzamos la descarga
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `reporte_${tipoReporteActual}_${new Date().toISOString().split('T')[0]}.pdf`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);

        // Mensaje de éxito DESPUÉS del click
        mostrarExito('PDF generado exitosamente');

    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error al exportar a PDF');
    }
}


    async function exportarExcel() {
        if (!datosReporteActual || !tipoReporteActual) {
            mostrarError('No hay datos de reporte para exportar');
            return;
        }
        
        try {
            mostrarCargando('Generando Excel...');

            const response = await fetch('/gestorPracticantes/public/api/reportes/exportar-excel', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    tipoReporte: tipoReporteActual,
                    datos: datosReporteActual
                })
            });

            if (!response.ok) {
                throw new Error('Error al generar el Excel');
            }

            const blob = await response.blob();

            // ❗ Ocultamos el cargando ANTES de iniciar la descarga
            ocultarCargando();

            // Generar la descarga
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `reporte_${tipoReporteActual}_${new Date().toISOString().split('T')[0]}.xlsx`;
            document.body.appendChild(a);

            // Forzar descarga
            a.click();

            // Liberar memoria
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            // 🚀 Mostrar éxito después de que se dispara la descarga
            mostrarExito('Excel generado exitosamente');

        } catch (error) {
            console.error('Error:', error);
            ocultarCargando();
            mostrarError('Error al exportar a Excel');
        }
    }


    async function exportarWord() {
        if (!datosReporteActual || !tipoReporteActual) {
            mostrarError('No hay datos de reporte para exportar');
            return;
        }
        
        try {
            mostrarCargando('Generando Word...');
            
            const response = await fetch('/gestorPracticantes/public/api/reportes/exportar-word', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    tipoReporte: tipoReporteActual,
                    datos: datosReporteActual
                })
            });
            
            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `reporte_${tipoReporteActual}_${new Date().toISOString().split('T')[0]}.docx`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
                mostrarExito('Word generado exitosamente');
            } else {
                throw new Error('Error al generar el Word');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarError('Error al exportar a Word');
        } finally {
            ocultarCargando();
        }
    }

    function imprimirReporte() {
        if (!datosReporteActual) {
            mostrarError('No hay datos de reporte para imprimir');
            return;
        }
        
        const contenido = document.getElementById('tablaResultados').innerHTML;
        const ventanaImpresion = window.open('', '_blank');
        
        ventanaImpresion.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Reporte - ${tipoReporteActual}</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        margin: 20px;
                    }
                    .reporte-header {
                        text-align: center;
                        margin-bottom: 30px;
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

    async function mostrarModalSeleccionArea() {
        return new Promise(async (resolve) => {
            try {
                const areas = await api.listarAreas();
                console.log("AREAS:", areas);

                // si viene como {data: [...]} lo normalizamos
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

                const modal = new bootstrap.Modal(document.getElementById('modalSeleccionArea'));
                modal.show();
                
                document.getElementById('btnConfirmarArea').addEventListener('click', () => {
                    const areaID = document.getElementById('selectArea').value;
                    modal.hide();
                    resolve(areaID || null);
                });

                document.getElementById('modalSeleccionArea').addEventListener('hidden.bs.modal', () => {
                    document.getElementById('modalSeleccionArea').remove();
                });

            } catch (error) {
                console.error('Error:', error);
                resolve(null);
            }
        });
    }


    async function mostrarModalSeleccionPracticante() {
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
                                            ${practicantes.map(p => `<option value="${p.PracticanteID}">${p.NombreCompleto}</option>`).join('')}
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
                const modal = new bootstrap.Modal(document.getElementById('modalSeleccionPracticante'));
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
                
                document.getElementById('modalSeleccionPracticante').addEventListener('hidden.bs.modal', () => {
                    document.getElementById('modalSeleccionPracticante').remove();
                });
            } catch (error) {
                console.error('Error:', error);
                resolve(null);
            }
        });
    }

    async function mostrarModalSeleccionFecha() {
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
            const modal = new bootstrap.Modal(document.getElementById('modalSeleccionFecha'));
            modal.show();
            
            document.getElementById('btnConfirmarFecha').addEventListener('click', () => {
                const fecha = document.getElementById('inputFecha').value;
                modal.hide();
                resolve(fecha);
            });
            
            document.getElementById('modalSeleccionFecha').addEventListener('hidden.bs.modal', () => {
                document.getElementById('modalSeleccionFecha').remove();
            });
        });
    }

    async function mostrarModalSeleccionMes() {
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
            const modal = new bootstrap.Modal(document.getElementById('modalSeleccionMes'));
            modal.show();
            
            document.getElementById('btnConfirmarMes').addEventListener('click', () => {
                const mes = document.getElementById('selectMes').value;
                const anio = document.getElementById('inputAnio').value;
                modal.hide();
                resolve({ mes, anio });
            });
            
            document.getElementById('modalSeleccionMes').addEventListener('hidden.bs.modal', () => {
                document.getElementById('modalSeleccionMes').remove();
            });
        });
    }

    async function mostrarModalSeleccionYear() {
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
                                <div class="form-group mb-3">
                                </div>
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
            const modal = new bootstrap.Modal(document.getElementById('modalSeleccionYear'));
            modal.show();
            
            document.getElementById('btnConfirmarYear').addEventListener('click', () => {
                const anio = document.getElementById('inputAnio').value;
                modal.hide();
                resolve({ anio });
            });
            
            document.getElementById('modalSeleccionYear').addEventListener('hidden.bs.modal', () => {
                document.getElementById('modalSeleccionYear').remove();
            });
        });
    }

    // ==================== FUNCIONES AUXILIARES ====================

    function formatearFecha(fecha) {
        if (!fecha) return 'N/A';
        const date = new Date(fecha);
        return date.toLocaleDateString('es-PE', { 
            year: 'numeric', 
            month: '2-digit', 
            day: '2-digit' 
        });
    }

    let cargandoDesde = null;
    const MIN_TIEMPO = 1500; // 1.5 segundos

    function mostrarCargando(mensaje = 'Cargando...') {
        cargandoDesde = Date.now();

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: mensaje,
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
        } else {
            console.log(mensaje);
        }
    }

    function ocultarCargando() {
        return new Promise(resolve => {
            const tiempoTranscurrido = Date.now() - cargandoDesde;

            if (tiempoTranscurrido < MIN_TIEMPO) {
                setTimeout(() => {
                    ocultarCargando().then(resolve);
                }, MIN_TIEMPO - tiempoTranscurrido);
                return;
            }

            if (typeof Swal !== 'undefined') {
                Swal.close();
                // Esperar a que la animación de cierre termine
                setTimeout(resolve, 300);
            } else {
                resolve();
            }
        });
    }


    function mostrarExito(mensaje) {
        // Implementar según tu sistema de notificaciones
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

    function mostrarError(mensaje) {
        // Implementar según tu sistema de notificaciones
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

    window.generarReportePracticantesActivos = generarReportePracticantesActivos;
    window.generarReporteAsistenciaAnual = generarReporteAsistenciaAnual;
    window.generarReporteAsistenciaMensual = generarReporteAsistenciaMensual;
    window.generarReporteAsistenciaDia = generarReporteAsistenciaDia;
    window.generarReporteAsistenciaPracticante = generarReporteAsistenciaPracticante;
    window.generarReporteComparativoAreas = generarReporteComparativoAreas;
    window.generarReporteCompleto = generarReporteCompleto;
    window.generarReporteEstadisticasGenerales = generarReporteEstadisticasGenerales;
    window.generarReporteHorasAcumuladas = generarReporteHorasAcumuladas;
    window.generarReportePorArea = generarReportePorArea;
    window.generarReportePorUniversidad = generarReportePorUniversidad;
    window.generarReportePracticantesCompletados = generarReportePracticantesCompletados;
    window.generarReportePromedioHoras = generarReportePromedioHoras;

    window.exportarExcel = exportarExcel;
    window.exportarPDF = exportarPDF;
    window.exportarWord = exportarWord;
    window.imprimirReporte = imprimirReporte;

};


