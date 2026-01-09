<div class="documentos-container">
    <div class="page-header">
        <h1><i class="fas fa-file-alt"></i> Gestión de Documentos</h1>
        <p class="page-subtitle">Administrar documentos requeridos y cartas de aceptación</p>
    </div>

    <div class="action-buttons">
        <button class="btn-primary" id="btnSubirDocumento">
            <i class="fas fa-upload"></i> Subir Documentos
        </button>
    </div>

    <div class="page-header">
        <h3 class="card-title">Documentos Requeridos por Practicante</h3>
        <div id="documentosRequeridos">
            <div class="form-group">
                <label for="selectPracticanteDoc">Seleccionar Practicante:</label>
                <select id="selectPracticanteDoc">
                    <option value="">Seleccionar...</option>
                </select>
            </div>
            <div id="listaDocumentos">
                <!-- Contenido dinámico -->
            </div>
        </div>
    </div>
</div>

<!-- Modal para enviar solicitud -->
<div id="modalEnviarSolicitud" class="modal hidden">
    <div class="modal-content">
        <h3>Enviar Solicitud a Área</h3>
        <form id="formEnviarSolicitud">
            <input type="hidden" id="solicitudEnvioID">
            
            <div class="form-group">
                <label>Área Destino:</label>
                <select id="areaDestino" required>
                    <option value="">Seleccionar área...</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Mensaje:</label>
                <textarea id="mensajeSolicitud" rows="4" required 
                    placeholder="Descripción de la solicitud..."></textarea>
            </div>
            
            <button type="submit" class="btn-success" id="btnEnviarSolicitud">Enviar Solicitud</button>
            <button type="button" id="btnCancelarEnviar">Cancelar</button>
        </form>
    </div>
</div>

<!-- Modal: Subir Documentos -->
<div id="modalSubirDocumento" class="modal hidden">
    <div class="modal-content-custom">
        <div class="modal-header-custom">
            <h2>Subir Documentos del Practicante</h2>
            <button class="close">&times;</button>
        </div>
        <div class="modal-body-custom">
            <form id="formSubirDocumentos" enctype="multipart/form-data">
                <!-- Selector de Practicante -->
                <div class="form-group">
                    <label for="practicanteDocumento">Practicante:</label>
                    <select id="practicanteDocumento" name="practicanteID" required>
                        <option value="">Seleccionar practicante...</option>
                    </select>
                </div>

                <input type="hidden" id="solicitudID" name="solicitudID">
                <div id="contenedorDocumentos" class="hidden">
                    <!-- CV -->
                    <div class="form-group">
                    <div class="documento-item">
                        <div class="documento-header">
                            <label class="documento-label obligatorio">
                                <i class="fas fa-file-alt"></i> Curriculum Vitae (CV)
                            </label>
                        </div>
                        <div class="documento-body">
                            <input type="file" id="archivo_cv" name="archivo_cv" accept=".pdf,.doc,.docx">
                            <div id="preview_cv" class="preview-documento"></div>
                        </div>
                    </div>

                    <!-- DNI -->
                    <div class="documento-item">
                        <div class="documento-header">
                            <label class="documento-label obligatorio">
                                <i class="fas fa-id-card"></i> DNI
                            </label>
                        </div>
                        <div class="documento-body">
                            <input type="file" id="archivo_dni" name="archivo_dni" accept=".pdf,.jpg,.png">
                            <div id="preview_dni" class="preview-documento"></div>
                        </div>
                    </div>

                    <!-- Carnet de Vacunación -->
                    <div class="documento-item">
                        <div class="documento-header">
                            <label class="documento-label obligatorio">
                                <i class="fas fa-syringe"></i> Carnet de Vacunación COVID
                            </label>
                        </div>
                        <div class="documento-body">
                            <input type="file" id="archivo_carnet_vacunacion" name="archivo_carnet_vacunacion" accept=".pdf,.jpg,.png">
                            <div id="preview_carnet_vacunacion" class="preview-documento"></div>
                        </div>
                    </div>

                    <!-- Carta de Presentación -->
                    <div class="documento-item">
                        <div class="documento-header">
                            <label class="documento-label opcional">
                                <i class="fas fa-envelope"></i> Carta de Presentación de la Universidad
                            </label>
                        </div>
                        <div class="documento-body">
                            <input type="file" id="archivo_carta_presentacion" name="archivo_carta_presentacion" accept=".pdf,.doc,.docx">
                            <div id="preview_carta_presentacion" class="preview-documento"></div>
                        </div>
                    </div>
                    </div>              
                </div>

                <div class="action-buttons">
                    <button type="submit" class="btn-success hidden" id="btnGuardarDocumentos">
                        <i class="fas fa-save"></i> Guardar Documentos
                    </button>
                    <button type="button" class="btn-cancel">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Dialog para generar carta -->
<div class="dialog-overlay" id="dialogCarta">
    <div class="dialog-content">
        <h3><i class="fas fa-file-contract"></i> Generar Carta de Aceptación</h3>

        <div class="form-group">
            <label for="numeroExpedienteCarta">Número de Expediente:</label>
            <input 
                type="text" 
                id="numeroExpedienteCarta" 
                placeholder="Ej: 21600-2025-1"
                maxlength="14"
            />
            <small>Formato: XXXXX-YYYY-X</small>
        </div>

        <div class="form-group">
            <label for="nombreDirectorCarta">Nombre del Director:</label>
            <input 
                type="text" 
                id="nombreDirectorCarta" 
                placeholder="Ej: VILCA GAVIDIA LUIS AGUSTIN"
                style="text-transform: uppercase;"
            />
            <small>Nombre completo del Director de Escuela/Facultad</small>
        </div>

        <div class="form-group">
            <label for="cargoDirectorCarta">Cargo:</label>
            <input 
                type="text" 
                id="cargoDirectorCarta" 
                placeholder="Ej: DIRECTOR DE ESCUELA"
                value="DIRECTOR"
                style="text-transform: uppercase;"
            />
            <small>Cargo o título del destinatario</small>
        </div>

        <div class="form-group">
            <label for="formatoDocumentoCarta">Formato del documento:</label>
            <select id="formatoDocumentoCarta">
                <option value="word">Word (.docx)</option>
                <option value="pdf">PDF (.pdf)</option>
            </select>
        </div>

        <div class="mensaje-estado" id="mensajeEstadoCarta"></div>

        <div class="dialog-buttons">
            <button class="btn btn-secondary" id="btnCancelarCarta">
                <i class="fas fa-times"></i>
                Cancelar
            </button>
            <button class="btn btn-primary" id="btnGenerarCartaDialog">
                <i class="fas fa-download"></i>
                Generar Carta
            </button>
        </div>
    </div>
</div>
