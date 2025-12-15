<div class="certificados-container">
        <div class="page-header">
            <h1><i class="fas fa-certificate"></i> Certificados de Prácticas</h1>
            <p class="page-subtitle">Generar y descargar certificados de prácticas preprofesionales</p>
        </div>

        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon text-success">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number" id="totalVigentes">0</div>
                    <div class="stat-label">Practicantes Vigentes</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon text-info">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number" id="totalFinalizados">0</div>
                    <div class="stat-label">Practicantes Finalizados</div>
                </div>
            </div>
        </div>

        <!-- Búsqueda de practicante -->
        <div class="search-section">
            <h3><i class="fas fa-search"></i> Seleccionar Practicante</h3>
            <div class="alert-box alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Información:</strong> Al generar un certificado, el practicante pasará automáticamente al estado "Finalizado" y no podrá registrar más asistencias.
            </div>
            <div class="search-form">
                <div class="form-group">
                    <label for="selectPracticante">Practicante:</label>
                    <select id="selectPracticante">
                        <option value="">-- Seleccione un practicante --</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Información del practicante -->
        <div class="info-section" id="infoSection">
            <div class="practicante-header">
                <div class="practicante-info">
                    <h2 id="nombreCompleto"></h2>
                    <span class="badge" id="estadoBadge"></span>
                </div>
                <div class="practicante-actions">
                    <button class="btn btn-primary" id="btnAbrirDialog" title="Generar certificado">
                        <i class="fas fa-file-contract"></i>
                        Generar Certificado
                    </button>
                </div>
            </div>

            <div class="details-grid">
                <div class="detail-item">
                    <label><i class="fas fa-id-card"></i> DNI:</label>
                    <div class="value" id="dni"></div>
                </div>
                <div class="detail-item">
                    <label><i class="fas fa-graduation-cap"></i> Carrera:</label>
                    <div class="value" id="carrera"></div>
                </div>
                <div class="detail-item">
                    <label><i class="fas fa-university"></i> Universidad:</label>
                    <div class="value" id="universidad"></div>
                </div>
                <div class="detail-item">
                    <label><i class="fas fa-building"></i> Área:</label>
                    <div class="value" id="area"></div>
                </div>
                <div class="detail-item">
                    <label><i class="fas fa-calendar-plus"></i> Fecha de Inicio:</label>
                    <div class="value" id="fechaInicio"></div>
                </div>
                <div class="detail-item">
                    <label><i class="fas fa-calendar-check"></i> Fecha de Término:</label>
                    <div class="value" id="fechaTermino"></div>
                </div>
                <div class="detail-item">
                    <label><i class="fas fa-clock"></i> Total de Horas:</label>
                    <div class="value" id="totalHoras"></div>
                </div>
                <div class="detail-item">
                    <label><i class="fas fa-info-circle"></i> Estado:</label>
                    <div class="value" id="estado"></div>
                </div>
            </div>

            <div class="empty-state" id="emptyState">
                <i class="fas fa-user-slash"></i>
                <p>Seleccione un practicante para ver su información</p>
            </div>
        </div>
    </div>
</div>


<!-- Dialog para generar certificado -->
<div class="dialog-overlay" id="dialogCertificado">
    <div class="dialog-content">
        <h3><i class="fas fa-file-contract"></i> Generar Certificado de Horas</h3>
        
        <div class="alert-box alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Advertencia:</strong> Esta acción cambiará el estado del practicante a "Finalizado".
        </div>

        <div class="form-group">
            <label for="numeroExpediente">Número de Expediente:</label>
            <input 
                type="text" 
                id="numeroExpedienteCertificado" 
                placeholder="Ej: 21600-2025-1"
                maxlength="14"
            />
            <small>Formato: XXXXX-YYYY-X</small>
        </div>

        <div class="form-group">
            <label for="formatoDocumento">Formato del documento:</label>
            <select id="formatoDocumentoCertificado">
                <option value="word">Word (.docx)</option>
                <option value="pdf">PDF (.pdf)</option>
            </select>
        </div>

        <div class="mensaje-estado" id="mensajeEstadoCertificado"></div>

        <div class="dialog-buttons">
            <button class="btn btn-secondary" id="btnCancelarCertificado">
                <i class="fas fa-times"></i>
                Cancelar
            </button>
            <button class="btn btn-primary" id="btnGenerarCertificado">
                <i class="fas fa-download"></i>
                Generar Certificado
            </button>
        </div>
    </div>
</div>


