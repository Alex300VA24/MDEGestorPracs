
<!-- === PÁGINA REPORTES === -->
<div id="reportes-container" class="reportes-container">

    <div class="page-header">
        <h1><i class="fas fa-chart-bar"></i> Reportes y Estadísticas</h1>
        <p class="page-subtitle">Generar informes detallados del sistema</p>
    </div>

    <!-- Sección de Reportes por Categoría -->
    <div class="reportes-sections">

        <!-- REPORTES DE PRACTICANTES -->
        <div class="content-card">
            <h3 class="card-title">
                <i class="fas fa-users"></i> Reportes de Practicantes
            </h3>
            <div class="reportes-grid">
                <button class="reporte-btn" onclick="generarReportePracticantesActivos()">
                    <i class="fas fa-user-check"></i>
                    <span>Practicantes Vigentes</span>
                    <small>Lista de practicantes en proceso</small>
                </button>
                <button class="reporte-btn" onclick="generarReportePracticantesCompletados()">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Practicantes Finalizados</span>
                    <small>Histórico de prácticas finalizadas</small>
                </button>
                <button class="reporte-btn" onclick="generarReportePorArea()">
                    <i class="fas fa-building"></i>
                    <span>Practicantes por Área</span>
                    <small>Distribución por departamento</small>
                </button>
                <button class="reporte-btn" onclick="generarReportePorUniversidad()">
                    <i class="fas fa-university"></i>
                    <span>Por Universidad</span>
                    <small>Agrupado por institución</small>
                </button>
            </div>
        </div>
        
        <!-- REPORTES DE ASISTENCIA -->
        <div class="content-card">
            <h3 class="card-title">
                <i class="fas fa-calendar-check"></i> Reportes de Asistencia
            </h3>
            <div class="reportes-grid">
                <button class="reporte-btn" onclick="generarReporteAsistenciaPracticante()">
                    <i class="fas fa-user-clock"></i>
                    <span>Asistencias por Practicante</span>
                    <small>Historial completo de un practicante</small>
                </button>
                <button class="reporte-btn" onclick="generarReporteAsistenciaDia()">
                    <i class="fas fa-calendar-day"></i>
                    <span>Asistencias del Día</span>
                    <small>Registro diario de asistencias</small>
                </button>
                <button class="reporte-btn" onclick="generarReporteAsistenciaMensual()">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Asistencias Mensuales</span>
                    <small>Resumen mensual por practicante</small>
                </button>
                <button class="reporte-btn" onclick="generarReporteAsistenciaAnual()">
                    <i class="fas fa-calendar"></i>
                    <span>Asistencias Anuales</span>
                    <small>Resumen anual por practicante</small>
                </button>
            </div>
        </div>

        <!-- REPORTES ESTADÍSTICOS -->
        <div class="content-card">
            <h3 class="card-title">
                <i class="fas fa-chart-pie"></i> Reportes Estadísticos
            </h3>
            <div class="reportes-grid">
                <button class="reporte-btn" onclick="generarReporteEstadisticasGenerales()">
                    <i class="fas fa-chart-line"></i>
                    <span>Estadísticas Generales</span>
                    <small>Dashboard completo del sistema</small>
                </button>
                <button class="reporte-btn" onclick="generarReportePromedioHoras()">
                    <i class="fas fa-chart-bar"></i>
                    <span>Promedio de Horas</span>
                    <small>Análisis de horas trabajadas</small>
                </button>
                <button class="reporte-btn" onclick="generarReporteComparativoAreas()">
                    <i class="fas fa-balance-scale"></i>
                    <span>Comparativo por Áreas</span>
                    <small>Rendimiento entre departamentos</small>
                </button>
                <button class="reporte-btn" onclick="generarReporteCompleto()">
                    <i class="fas fa-file-pdf"></i>
                    <span>Reporte General Completo</span>
                    <small>Documento PDF con toda la información</small>
                </button>
            </div>
        </div>
    </div>

    <!-- Área de Visualización de Resultados -->
    <div class="content-card hidden" id="resultadosReporte">
        <div class="card-header-flex">
            <h3 class="card-title"><i class="fas fa-table"></i> Resultados del Reporte</h3>
            <div class="action-buttons-inline">
                <button class="btn-success" onclick="exportarExcel()">
                    <i class="fas fa-file-excel"></i> Exportar Excel
                </button>
                <button class="btn-primary" onclick="exportarPDF()">
                    <i class="fas fa-file-pdf"></i> Exportar PDF
                </button>
                <button class="btn-secondary" onclick="imprimirReporte()">
                    <i class="fas fa-print"></i> Imprimir
                </button>
            </div>
        </div>
        <div id="tablaResultados"></div>
    </div>
</div>


