// documentos.js - Refactorizado con lifecycle management

(function() {
    'use strict';

    // ==================== CONFIGURACIÓN DEL MÓDULO ====================
    
    const MODULE_NAME = 'documentos';
    
    // Estado privado del módulo
    let moduleState = {
        solicitudIDActual: null,
        enviandoSolicitud: false,
        eventListeners: [],
        practicantesCache: null,
        areasCache: null
    };

    // Constantes
    const TIPOS_DOCUMENTO = ['cv', 'dni', 'carnet_vacunacion', 'carta_presentacion'];
    const DOCUMENTOS_OBLIGATORIOS = ["CV", "DNI", "Carnet_Vacunacion"];

    // ==================== CLASE PRINCIPAL DEL MÓDULO ====================
    
    class DocumentosModule {
        constructor() {
            this.state = moduleState;
            this.initialized = false;
        }

        // ============ INICIALIZACIÓN ============
        
        async init() {
            if (this.initialized) {
                console.warn('⚠️ Módulo Documentos ya inicializado');
                return this;
            }

            try {
                // Cargar datos iniciales
                await this.cargarPracticantes();
                await this.cargarAreas();
                
                // Configurar event listeners
                this.configurarEventListeners();
                
                // Marcar como inicializado
                this.initialized = true;             
                return this;
                
            } catch (error) {
                console.error('❌ Error al inicializar módulo Documentos:', error);
                throw error;
            }
        }

        // ============ CARGAR DATOS ============
        
        async cargarPracticantes() {
            try {

                const response = await api.listarNombrePracticantes();
                
                if (!response || !Array.isArray(response.data)) {
                    throw new Error('Respuesta inválida del servidor');
                }
                
                this.state.practicantesCache = response.data;
                
                // Renderizar ambos selects
                this.renderizarSelectPracticantes('selectPracticanteDoc', response.data);
                this.renderizarSelectPracticantes('practicanteDocumento', response.data);
                
            } catch (error) {
                console.error('❌ Error al cargar practicantes:', error);
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error',
                    mensaje: error.message || 'Error al cargar la lista de practicantes'
                });
            }
        }

        async cargarAreas() {
            try {
                const response = await api.listarAreas();
                
                if (!response.success) {
                    throw new Error('Error al cargar áreas');
                }
                
                this.state.areasCache = response.data;
                
                // Renderizar select de áreas
                const selectArea = document.getElementById('areaDestino');
                if (selectArea) {
                    selectArea.innerHTML = '<option value="">Seleccionar área...</option>';
                    response.data.forEach(area => {
                        const option = document.createElement('option');
                        option.value = area.AreaID;
                        option.textContent = area.NombreArea;
                        selectArea.appendChild(option);
                    });
                }
                
            } catch (error) {
                console.error('❌ Error al cargar áreas:', error);
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error',
                    mensaje: error.message || 'Error al cargar las áreas'
                });
            }
        }

        renderizarSelectPracticantes(selectId, practicantes) {
            const select = document.getElementById(selectId);
            
            if (!select) {
                console.warn(`⚠️ Select ${selectId} no encontrado`);
                return;
            }
            
            select.innerHTML = '<option value="">Seleccionar practicante...</option>';
            
            practicantes.forEach(p => {
                const option = document.createElement('option');
                option.value = p.PracticanteID;
                option.textContent = p.NombreCompleto;
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

            // Select de practicante en lista
            const selectPracticanteDoc = document.getElementById('selectPracticanteDoc');
            addListener(selectPracticanteDoc, 'change', (e) => this.handleSeleccionPracticanteDocumento(e));

            // Select de practicante en modal
            const selectPracticanteModal = document.getElementById('practicanteDocumento');
            addListener(selectPracticanteModal, 'change', (e) => this.handleSeleccionPracticanteModal(e));

            // Botón abrir modal
            const btnSubir = document.getElementById('btnSubirDocumento');
            addListener(btnSubir, 'click', () => this.abrirModalSubirDocumento());

            // Cerrar modal (icono)
            const btnCerrarModal = document.querySelector('#modalSubirDocumento .close');
            addListener(btnCerrarModal, 'click', () => this.cerrarModal('modalSubirDocumento'));

            // Cerrar modal (botón cancelar)
            const btnCancelarModal = document.querySelector('#modalSubirDocumento .btn-cancel');
            addListener(btnCancelarModal, 'click', () => this.cerrarModal('modalSubirDocumento'));

            // Detectar cambios en archivos
            TIPOS_DOCUMENTO.forEach(tipo => {
                const input = document.getElementById(`archivo_${tipo}`);
                addListener(input, 'change', (e) => this.handleCambioArchivo(e, tipo));
            });

            // Formulario de subir documentos
            const formSubir = document.getElementById('formSubirDocumentos');
            addListener(formSubir, 'submit', (e) => this.handleSubmitDocumentos(e));

            // Formulario de enviar solicitud
            const formEnviar = document.getElementById('formEnviarSolicitud');
            addListener(formEnviar, 'submit', (e) => this.handleEnviarSolicitud(e));

            // Cerrar modal enviar solicitud cancelar
            const btnCancelarEnviar = document.getElementById('btnCancelarEnviar');
            addListener(btnCancelarEnviar, 'click', () => this.cerrarModalEnviarSolicitud());

            // Botón generar carta
            const btnAbrirCarta = document.getElementById('btnGenerarCarta');
            addListener(btnAbrirCarta, 'click', () => this.abrirDialogCarta());

            // Boton para el metodo generar carta de aceptacion
            const btnGenerarCartaAceptar = document.getElementById('btnGenerarCartaDialog');
            addListener(btnGenerarCartaAceptar, 'click', () => this.generarCartaAceptacion(this.state.solicitudIDActual));

            const btnCancelarCarta = document.getElementById('btnCancelarCarta');
            addListener(btnCancelarCarta, 'click', () => this.cerrarDialogCarta());
        }

        // ============ SELECCIÓN DE PRACTICANTE EN LISTA ============
        
        async handleSeleccionPracticanteDocumento(e) {
            const practicanteID = e.target.value;
            const listaDocumentos = document.getElementById('listaDocumentos');
            
            if (!practicanteID) {
                listaDocumentos.innerHTML = '<p>Seleccione un practicante...</p>';
                this.state.solicitudIDActual = null;
                return;
            }

            try {
                // Obtener solicitud activa
                const result = await api.obtenerSolicitudActiva(practicanteID);
                
                if (result.success && result.data) {
                    this.state.solicitudIDActual = result.data.SolicitudID;
                    const documentos = await api.obtenerDocumentosPorPracticante(practicanteID);
                    await this.renderDocumentos(documentos.data.data, this.state.solicitudIDActual);
                } else {
                    this.state.solicitudIDActual = null;
                    await this.renderDocumentos(null, null);
                }
            } catch (error) {
                console.error('❌ Error al obtener solicitud:', error);
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error',
                    mensaje: error.message || 'Error al obtener la información del practicante'
                });
                this.state.solicitudIDActual = null;
            }
        }

        // ============ SELECCIÓN DE PRACTICANTE EN MODAL ============
        
        async handleSeleccionPracticanteModal(e) {
            const practicanteID = e.target.value;
            const contenedorDocumentos = document.getElementById('contenedorDocumentos');
            const btnGuardar = document.getElementById('btnGuardarDocumentos');
            
            if (!practicanteID) {
                contenedorDocumentos.classList.add('hidden');
                btnGuardar.classList.add('hidden');
                return;
            }

            try {
                const solicitudActivaResult = await api.obtenerSolicitudActiva(practicanteID);
                
                if (solicitudActivaResult.success && solicitudActivaResult.data) {
                    // Ya tiene solicitud activa
                    this.state.solicitudIDActual = solicitudActivaResult.data.SolicitudID;
                    
                    mostrarAlerta({
                        tipo: 'info',
                        titulo: 'Solicitud Existente',
                        mensaje: `Continuará trabajando con la Solicitud ID #${this.state.solicitudIDActual} (Estado: ${solicitudActivaResult.data.EstadoSolicitudDesc})`
                    });
                } else {
                    // No tiene solicitud activa - crear una nueva
                    await this.crearNuevaSolicitud(practicanteID);
                }

                // Configurar solicitud actual
                document.getElementById('solicitudID').value = this.state.solicitudIDActual;
                window.solicitudActualID = this.state.solicitudIDActual;
                
                // Cargar documentos existentes
                await this.cargarDocumentosExistentes(practicanteID);
                
                // Mostrar controles
                contenedorDocumentos.classList.remove('hidden');
                btnGuardar.classList.remove('hidden');

            } catch (error) {
                console.error('❌ Error procesando solicitud:', error);
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error',
                    mensaje: error.message || 'Error al procesar la solicitud del practicante'
                });
                
                // Limpiar selección
                e.target.value = '';
                contenedorDocumentos.classList.add('hidden');
                btnGuardar.classList.add('hidden');
            }
        }

        // ============ CREAR NUEVA SOLICITUD ============
        
        async crearNuevaSolicitud(practicanteID) {
            const historialResponse = await api.obtenerHistorialSolicitudes(practicanteID);
            const tieneHistorial = historialResponse.success && 
                                  historialResponse.data && 
                                  historialResponse.data.length > 0;
            
            let mensaje = 'Este practicante no tiene una solicitud activa. ¿Desea crear una nueva solicitud?';
            let textoConfirm = 'Sí, crear nueva';
            
            if (tieneHistorial) {
                const ultimaSolicitud = historialResponse.data[0];
                mensaje = `Este practicante no tiene una solicitud activa, pero tiene solicitudes anteriores.

                <div style="background: #f0f7ff; padding: 10px; border-radius: 5px; margin: 10px 0;">
                    <strong>Última solicitud:</strong> #${ultimaSolicitud.SolicitudID} (${ultimaSolicitud.EstadoDesc})
                </div>

                ¿Desea crear una nueva solicitud y <strong>transferir los documentos</strong> de la solicitud anterior?

                <div style="color: #666; font-size: 0.9em; margin-top: 10px;">
                    ℹ️ Los documentos se moverán automáticamente a la nueva solicitud para evitar duplicados.
                </div>`;
                textoConfirm = 'Sí, crear y transferir documentos';
            }

            const confirmacion = await mostrarAlerta({
                tipo: 'question',
                titulo: 'Crear Nueva Solicitud',
                html: mensaje,
                showCancelButton: true,
                confirmText: textoConfirm,
                cancelText: 'Cancelar',
                width: '600px'
            });
            
            if (!confirmacion.isConfirmed) {
                throw new Error('Operación cancelada por el usuario');
            }
            
            mostrarAlerta({
                tipo: 'info',
                titulo: 'Procesando...',
                html: tieneHistorial 
                    ? 'Creando solicitud y transfiriendo documentos...' 
                    : 'Creando nueva solicitud...',
                showConfirmButton: false,
                allowOutsideClick: false
            });

            const response = await api.crearNuevaSolicitud(practicanteID, tieneHistorial);
            
            if (!response.success) {
                throw new Error(response.mensaje || response.message || 'Error al crear solicitud');
            }
            
            this.state.solicitudIDActual = response.data.solicitudID;
            
            let mensajeExito = `Nueva solicitud #${this.state.solicitudIDActual} creada exitosamente`;
            
            if (response.data.documentosMigrados && response.data.documentosMigrados > 0) {
                mensajeExito += `\n\n Se transfirieron ${response.data.documentosMigrados} documento(s) de la solicitud anterior.`;
            }
            
            mostrarAlerta({
                tipo: 'success',
                titulo: 'Solicitud Creada',
                mensaje: mensajeExito
            });
        }

        // ============ CARGAR DOCUMENTOS EXISTENTES ============
        
        async cargarDocumentosExistentes(practicanteID) {
            if (!this.state.solicitudIDActual) {
                console.warn('⚠️ No hay solicitudID activa');
                TIPOS_DOCUMENTO.forEach(tipo => {
                    const previewDiv = document.getElementById(`preview_${tipo}`);
                    if (previewDiv) previewDiv.innerHTML = '';
                });
                return;
            }
            
            for (const tipo of TIPOS_DOCUMENTO) {
                try {
                    const result = await api.obtenerDocumentoPorTipoYSolicitud(
                        this.state.solicitudIDActual,
                        tipo
                    );
                    
                    const previewDiv = document.getElementById(`preview_${tipo}`);
                    
                    if (result.success && result.data) {
                        previewDiv.innerHTML = `
                            <div class="archivo-actual">
                                <span>
                                    <i class="fas fa-check-circle" style="color: green;"></i>
                                    Documento subido (${result.data.FechaSubida})
                                </span>
                                <div class="btn-group">
                                    <button type="button" class="btn-view" onclick="documentosModule.verDocumento('${result.data.Archivo}')">
                                        <i class="fas fa-eye"></i> Ver
                                    </button>
                                    <button type="button" class="btn-delete" onclick="documentosModule.eliminarDocumentoModal(${result.data.DocumentoID}, '${tipo}', ${practicanteID})">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </div>
                            </div>
                        `;
                    } else {
                        previewDiv.innerHTML = '';
                    }
                } catch (err) {
                    console.error(`❌ Error al cargar documento ${tipo}:`, err);
                    const previewDiv = document.getElementById(`preview_${tipo}`);
                    if (previewDiv) previewDiv.innerHTML = '';
                }
            }
        }

        // ============ MANEJO DE CAMBIOS EN ARCHIVOS ============
        
        handleCambioArchivo(e, tipo) {
            const input = e.target;
            const preview = document.getElementById(`preview_${tipo}`);
            
            if (!preview) return;
            
            if (input.files.length > 0) {
                const fileName = input.files[0].name;
                const existingPreview = preview.querySelector('.archivo-actual');
                
                if (existingPreview) {
                    preview.innerHTML = `
                        ${existingPreview.outerHTML}
                        <div style="color: orange; padding: 5px; margin-top: 5px;">
                            <i class="fas fa-file"></i> Se reemplazará con: ${fileName}
                        </div>
                    `;
                } else {
                    preview.innerHTML = `
                        <div style="color: #4CAF50; padding: 5px;">
                            <i class="fas fa-file"></i> Nuevo archivo: ${fileName}
                        </div>
                    `;
                }
            }
        }

        // ============ SUBMIT DOCUMENTOS ============
        
        async handleSubmitDocumentos(e) {
            e.preventDefault();

            const selectPracticanteModal = document.getElementById('practicanteDocumento');
            const practicanteID = selectPracticanteModal.value;
            
            if (!practicanteID) {
                mostrarAlerta({tipo: 'info', mensaje: 'Por favor selecciona un practicante'});
                return;
            }

            const btn = document.getElementById('btnGuardarDocumentos');
            
            try {
                await ejecutarUnaVez(btn, async () => {
                    let documentosSubidos = 0;
                    
                    for (const tipo of TIPOS_DOCUMENTO) {
                        const input = document.getElementById(`archivo_${tipo}`);
                        
                        if (input && input.files.length > 0) {
                            const formData = new FormData();
                            formData.append('solicitudID', this.state.solicitudIDActual);
                            formData.append('tipoDocumento', tipo);
                            formData.append('archivoDocumento', input.files[0]);
                            formData.append('practicanteID', practicanteID);

                            const existente = await api.obtenerDocumentoPorTipoYPracticante(practicanteID, tipo);
                            
                            let response;
                            if (existente.success && existente.data) {
                                response = await api.actualizarDocumento(formData);
                            } else {
                                response = await api.subirDocumento(formData);
                            }

                            if (!response.ok) {
                                let errorMsg = `Error al subir el documento ${tipo}`;
                                try {
                                    const errorData = await response.json();
                                    errorMsg = errorData.message || errorData.error || errorMsg;
                                } catch (e) {
                                    const text = await response.text();
                                    if (text) errorMsg = text;
                                }
                                throw new Error(errorMsg);
                            }
                            
                            documentosSubidos++;
                        }
                    }
                    
                    if (documentosSubidos === 0) {
                        throw new Error('No se seleccionó ningún documento para subir');
                    }
                });

                mostrarAlerta({tipo: 'success', titulo: 'Guardado', mensaje: 'Documentos guardados correctamente'});

                await this.cargarDocumentosExistentes(practicanteID);
                
                // Limpiar inputs
                TIPOS_DOCUMENTO.forEach(tipo => {
                    const input = document.getElementById(`archivo_${tipo}`);
                    if (input) input.value = '';
                });

                // Recargar lista si es el mismo practicante
                const selectPracticanteDoc = document.getElementById('selectPracticanteDoc');
                if (selectPracticanteDoc.value === practicanteID) {
                    const documentos = await api.obtenerDocumentosPorPracticante(practicanteID);
                    await this.renderDocumentos(documentos.data.data, this.state.solicitudIDActual);
                }
                
                this.cerrarModal('modalSubirDocumento');

            } catch (err) {
                mostrarAlerta({tipo: 'error', titulo: 'Error', mensaje: err.message});
            }
        }

        // ============ ENVIAR SOLICITUD A ÁREA ============
        
        async handleEnviarSolicitud(e) {
            e.preventDefault();

            if (this.state.enviandoSolicitud) {
                console.warn('⚠️ Ya hay una solicitud en proceso');
                return;
            }

            const btn = document.getElementById('btnEnviarSolicitud');
            const solicitudID = document.getElementById('solicitudEnvioID').value;
            const destinatarioAreaID = document.getElementById('areaDestino').value;
            const contenido = document.getElementById('mensajeSolicitud').value;
            const remitenteAreaID = sessionStorage.getItem('areaID') || 1;

            try {
                this.state.enviandoSolicitud = true;
                
                const result = await ejecutarUnaVez(btn, async () => {
                    const response = await api.enviarSolicitudArea({
                        solicitudID: parseInt(solicitudID),
                        remitenteAreaID: parseInt(remitenteAreaID),
                        destinatarioAreaID: parseInt(destinatarioAreaID),
                        contenido
                    });

                    if (!response.success) throw new Error(response.message);
                    return response;
                });

                mostrarAlerta({
                    tipo: 'success',
                    titulo: 'Solicitud Enviada',
                    mensaje: 'Solicitud enviada correctamente al área'
                });
                
                this.cerrarModalEnviarSolicitud();
                
                // Recargar vista
                const selectPracticanteDoc = document.getElementById('selectPracticanteDoc');
                const practicanteID = selectPracticanteDoc.value;
                if (practicanteID) {
                    const documentos = await api.obtenerDocumentosPorPracticante(practicanteID);
                    await this.renderDocumentos(documentos.data.data, solicitudID, true);
                }
                
                if (window.recargarPracticantes) {
                    window.recargarPracticantes();
                }
            } catch (error) {
                console.error('❌ Error al enviar solicitud:', error);
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error al enviar solicitud',
                    mensaje: error.message
                });
            } finally {
                this.state.enviandoSolicitud = false;
            }
        }

        // ============ RENDER DOCUMENTOS ============
        
        async renderDocumentos(documentos, solicitudID, forzarEnviada = false) {
            const contenedor = document.getElementById('listaDocumentos');
            
            // Filtrar documentos por solicitud actual
            if (documentos && Array.isArray(documentos) && solicitudID) {
                documentos = documentos.filter(doc => 
                    doc.solicitudID === solicitudID || doc.SolicitudID === solicitudID
                );
            }

            if (!documentos || !Array.isArray(documentos) || documentos.length === 0) {
                const practicanteID = document.getElementById('selectPracticanteDoc')?.value;
                contenedor.innerHTML = `
                    <div style="text-align: center; padding: 20px;">
                        <p style="color: #666;">No hay documentos registrados para esta solicitud.</p>
                        ${practicanteID ? `
                            <button class="btn-info" onclick="documentosModule.mostrarHistorialSolicitudes(${practicanteID})" style="margin-top: 10px;">
                                <i class="fas fa-history"></i> Ver Historial de Solicitudes
                            </button>
                        ` : ''}
                    </div>
                `;
                return;
            }

            const normalizar = str =>
                str.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');

            const tiposSubidos = documentos.map(doc => normalizar(doc.tipo));
            const faltantes = DOCUMENTOS_OBLIGATORIOS.filter(req =>
                !tiposSubidos.includes(normalizar(req))
            );
            const todosCompletos = faltantes.length === 0;

            let solicitudEnviada = forzarEnviada;
            let solicitudAprobada = false;
            let solicitudRechazada = false;
            
            if (solicitudID) {
                try {
                    const estadoResponse = await api.verificarEstadoSolicitud(solicitudID);
                    if (estadoResponse.success && estadoResponse.data) {
                        solicitudEnviada = estadoResponse.data.enviada === true || forzarEnviada;
                        solicitudAprobada = estadoResponse.data.aprobada;
                        solicitudRechazada = estadoResponse.data.estado && 
                                            estadoResponse.data.estado.descripcion === 'Negada';
                    }
                } catch (error) {
                    console.warn('No se pudo verificar estado de solicitud:', error);
                }
            }

            const practicanteID = document.getElementById('selectPracticanteDoc')?.value;
            
            const tabla = `
            <div class="table-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h4 style="margin: 0;">Documentos de Solicitud ID #${solicitudID || 'N/A'}</h4>
                    ${practicanteID ? `
                        <button class="btn-info" onclick="documentosModule.mostrarHistorialSolicitudes(${practicanteID})" style="padding: 8px 15px;">
                            <i class="fas fa-history"></i> Ver Historial
                        </button>
                    ` : ''}
                </div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Importancia</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${documentos.map(doc => {
                            const obligatorio = DOCUMENTOS_OBLIGATORIOS.some(req =>
                                normalizar(req) === normalizar(doc.tipo)
                            );

                            return `
                                <tr>
                                    <td>${doc.tipo}</td>
                                    <td style="color: ${obligatorio ? '#FF664A' : '#7575FA'}; font-weight: bold;">
                                        ${obligatorio ? 'Obligatorio' : 'Opcional'}
                                    </td>
                                    <td>
                                        <button class="btn-view" 
                                                onclick="documentosModule.verDocumento('${doc.archivo}')">
                                            <i class="fas fa-eye"></i> Ver
                                        </button>
                                        <button class="btn-delete" onclick="documentosModule.eliminarDocumento(${doc.documentoID}, '${normalizar(doc.tipo)}')">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                    </td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>

                <div class="enviar-solicitud-container" style="margin-top: 20px; text-align: center;">
                    <button id="btnEnviarSolicitudArea" 
                            class="btn-info" 
                            ${todosCompletos && (!solicitudEnviada || solicitudRechazada) ? '' : 'disabled'}
                            onclick="documentosModule.abrirModalEnviarSolicitud(${solicitudID})">
                        <i class="fas fa-${solicitudEnviada && !solicitudRechazada ? 'check' : 'paper-plane'}"></i> 
                        ${solicitudEnviada && !solicitudRechazada ? 'Solicitud Enviada' : 'Enviar Solicitud a Área'}
                    </button>
                    <button id="btnGenerarCarta" 
                            class="btn-success" 
                            ${solicitudAprobada ? '' : 'disabled'}
                            style="margin-left: 10px;"
                            onclick="documentosModule.abrirDialogCarta()">
                        <i class="fas fa-file-contract"></i> Generar Carta de Aceptación
                    </button>
                    ${
                        !todosCompletos
                        ? `<p class='msg-warn'>Faltan documentos obligatorios: ${faltantes.join(', ')}</p>`
                        : solicitudRechazada
                        ? "<p class='msg-warn'>Solicitud <strong>RECHAZADA</strong>. Puede crear una nueva solicitud desde el modal de subir documentos.</p>"
                        : !solicitudEnviada
                        ? "<p class='msg-ok'>Documentos completos. Ahora puede enviar la solicitud.</p>"
                        : solicitudAprobada
                        ? "<p class='msg-ok'>Solicitud <strong>APROBADA</strong>. Puede generar la carta de aceptación.</p>"
                        : "<p class='msg-info'>Solicitud enviada. Esperando aprobación del área.</p>"
                    }
                </div>
            </div>
            `;

            contenedor.innerHTML = tabla;
        }

        // ============ HISTORIAL DE SOLICITUDES ============
        
        async mostrarHistorialSolicitudes(practicanteID) {
            try {
                const response = await api.obtenerHistorialSolicitudes(practicanteID);
                
                if (response.success && response.data && response.data.length > 0) {
                    const getBadgeStyle = (estadoAbrev) => {
                        const estilos = {
                            'PEN': 'background: #6c757d; color: white;',
                            'ENV': 'background: #0dcaf0; color: white;',
                            'REV': 'background: #ffc107; color: black;',
                            'APR': 'background: #198754; color: white;',
                            'FIN': 'background: #28a745; color: white;',
                            'NEG': 'background: #dc3545; color: white;'
                        };
                        return estilos[estadoAbrev] || 'background: #6c757d; color: white;';
                    };

                    const formatFecha = (fecha) => {
                        const [y, m, d] = fecha.split('-');
                        return `${d}/${m}/${y}`;
                    };

                    const html = `
                        <div class="historial-solicitudes" style="max-height: 500px; overflow-y: auto;">
                            <table class="table" style="width: 100%; border-collapse: collapse;">
                                <thead style="position: sticky; top: 0; background: white; z-index: 1;">
                                    <tr>
                                        <th style="padding: 10px; border-bottom: 2px solid #ddd;">ID</th>
                                        <th style="padding: 10px; border-bottom: 2px solid #ddd;">Fecha</th>
                                        <th style="padding: 10px; border-bottom: 2px solid #ddd;">Estado</th>
                                        <th style="padding: 10px; border-bottom: 2px solid #ddd;">Área</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${response.data.map(sol => `
                                        <tr>
                                            <td style="padding: 10px; border-bottom: 1px solid #eee;">#${sol.SolicitudID}</td>
                                            <td style="padding: 10px; border-bottom: 1px solid #eee;">${formatFecha(sol.FechaSolicitud)}</td>
                                            <td style="padding: 10px; border-bottom: 1px solid #eee;">
                                                <span style="padding: 5px 10px; border-radius: 5px; font-size: 12px; font-weight: bold; ${getBadgeStyle(sol.EstadoAbrev)}">
                                                    ${sol.EstadoDesc}
                                                </span>
                                            </td>
                                            <td style="padding: 10px; border-bottom: 1px solid #eee;">${sol.NombreArea || 'Sin asignar'}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;

                    mostrarAlerta({
                        tipo: 'info',
                        titulo: 'Historial de Solicitudes',
                        html: html,
                        width: '700px',
                        confirmText: 'Cerrar'
                    });
                } else {
                    mostrarAlerta({
                        tipo: 'info',
                        titulo: 'Sin Historial',
                        mensaje: 'Este practicante no tiene solicitudes registradas'
                    });
                }
            } catch (error) {
                console.error('Error al cargar historial:', error);
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error',
                    mensaje: 'Error al cargar el historial: ' + error.message
                });
            }
        }

        // ============ MODALES ============
        
        abrirModalSubirDocumento() {
            const modal = document.getElementById('modalSubirDocumento');
            if (modal) {
                modal.classList.remove('hidden');
                modal.style.display = 'flex';
                
                // Resetear modal
                document.getElementById('practicanteDocumento').value = '';
                document.getElementById('contenedorDocumentos').classList.add('hidden');
                document.getElementById('btnGuardarDocumentos').classList.add('hidden');
                
                // Limpiar previews
                TIPOS_DOCUMENTO.forEach(tipo => {
                    const input = document.getElementById(`archivo_${tipo}`);
                    const preview = document.getElementById(`preview_${tipo}`);
                    if (input) input.value = '';
                    if (preview) preview.innerHTML = '';
                });
            }
        }

        abrirModalEnviarSolicitud(solicitudID) {
            document.getElementById('solicitudEnvioID').value = solicitudID;
            this.abrirModal('modalEnviarSolicitud');
        }

        cerrarModalEnviarSolicitud() {
            this.cerrarModal('modalEnviarSolicitud');
            document.getElementById('formEnviarSolicitud').reset();
        }

        abrirDialogCarta() {
            document.getElementById('numeroExpedienteCarta').value = '';
            document.getElementById('formatoDocumentoCarta').value = 'word';
            document.getElementById('mensajeEstadoCarta').classList.remove('visible');
            document.getElementById('dialogCarta').classList.add('active');
        }

        cerrarDialogCarta() {
            document.getElementById('dialogCarta').classList.remove('active');
            const btnGenerar = document.getElementById('btnGenerarCarta');
            const mensajeEstado = document.getElementById('mensajeEstadoCarta');
            if (mensajeEstado) mensajeEstado.style.display = 'none';
            if (btnGenerar) {
                btnGenerar.disabled = false;
                btnGenerar.innerHTML = '<i class="fas fa-file-contract"></i> Generar Carta de Aceptación';
                btnGenerar.style.background = '#4CAF50';
            }
        }

        abrirModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.remove('hidden');
                modal.style.display = 'flex';
            }
        }

        cerrarModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
            }
        }

        // ============ ACCIONES DE DOCUMENTOS ============
        
        verDocumento(base64) {
            const tipoMime = base64.startsWith('JVBER') ? 'application/pdf'
                            : base64.startsWith('/9j/') ? 'image/jpeg'
                            : base64.startsWith('iVBOR') ? 'image/png'
                            : 'application/octet-stream';
            
            const blob = this.b64toBlob(base64, tipoMime);
            const url = URL.createObjectURL(blob);
            window.open(url, '_blank');
        }

        async eliminarDocumentoModal(documentoID, tipo, practicanteID) {
            const confirmacion = await mostrarAlerta({
                tipo: 'warning',
                titulo: '¿Estás seguro?',
                mensaje: '¿Seguro que deseas eliminar este documento?',
                showCancelButton: true
            });
            
            if (!confirmacion.isConfirmed) return;
            
            try {
                const response = await api.eliminarDocumento(documentoID);
                
                if (response.success) {
                    mostrarAlerta({tipo: 'success', mensaje: 'Documento eliminado correctamente'});
                    
                    // Recargar preview en el modal
                    const previewDiv = document.getElementById(`preview_${tipo}`);
                    if (previewDiv) previewDiv.innerHTML = '';
                    
                    // Si está en la lista, recargar
                    const selectPracticanteDoc = document.getElementById('selectPracticanteDoc');
                    if (selectPracticanteDoc.value == practicanteID) {
                        const documentos = await api.obtenerDocumentosPorPracticante(practicanteID);
                        await this.renderDocumentos(documentos.data.data, this.state.solicitudIDActual);
                    }
                } else {
                    mostrarAlerta({tipo: 'error', titulo: 'Error', mensaje: response.message});
                }
            } catch (error) {
                mostrarAlerta({tipo: 'error', titulo: 'Error', mensaje: error.message || 'Error al eliminar documento'});
            }
        }

        async eliminarDocumento(documentoID, tipo) {
            const confirmacion = await mostrarAlerta({
                tipo: 'warning',
                titulo: '¿Estás seguro?',
                mensaje: '¿Seguro que deseas eliminar este documento?',
                showCancelButton: true
            });
            
            if (!confirmacion.isConfirmed) return;
            
            try {
                const response = await api.eliminarDocumento(documentoID);
                
                if (response.success) {
                    mostrarAlerta({tipo: 'success', mensaje: 'Documento eliminado correctamente'});
                    
                    // Recargar la lista
                    const selectPracticanteDoc = document.getElementById('selectPracticanteDoc');
                    const practicanteID = selectPracticanteDoc.value;
                    if (practicanteID) {
                        const documentos = await api.obtenerDocumentosPorPracticante(practicanteID);
                        await this.renderDocumentos(documentos.data.data, this.state.solicitudIDActual);
                    }
                } else {
                    mostrarAlerta({tipo: 'error', titulo: 'Error al eliminar', mensaje: response.message});
                }
            } catch (error) {
                mostrarAlerta({tipo: 'error', titulo: 'Error al eliminar documento', mensaje: error.message || 'Error desconocido'});
            }
        }

        // ============ GENERAR CARTA DE ACEPTACIÓN ============
        
        async generarCartaAceptacion(solicitudID) {
            const inputExpediente = document.getElementById('numeroExpedienteCarta');
            const inputDirector = document.getElementById('nombreDirectorCarta');
            const inputCargo = document.getElementById('cargoDirectorCarta');
            const selectFormato = document.getElementById('formatoDocumentoCarta');
            const btnGenerar = document.getElementById('btnGenerarCarta');
            const mensajeEstado = document.getElementById('mensajeEstadoCarta');

            inputExpediente.focus();

            const mostrarMensaje = (mensaje, tipo) => {
                mensajeEstado.style.display = 'block';
                mensajeEstado.textContent = mensaje;
                
                if (tipo === 'error') {
                    mensajeEstado.style.background = '#ffebee';
                    mensajeEstado.style.color = '#c62828';
                    mensajeEstado.style.border = '1px solid #ef5350';
                } else if (tipo === 'exito') {
                    mensajeEstado.style.background = '#e8f5e9';
                    mensajeEstado.style.color = '#2e7d32';
                    mensajeEstado.style.border = '1px solid #66bb6a';
                } else {
                    mensajeEstado.style.background = '#e3f2fd';
                    mensajeEstado.style.color = '#1565c0';
                    mensajeEstado.style.border = '1px solid #42a5f5';
                }
            };

            const numeroExpediente = inputExpediente.value.trim();
            const nombreDirector = inputDirector.value.trim().toUpperCase();
            const cargoDirector = inputCargo.value.trim().toUpperCase();
            const formato = selectFormato.value;

            // Validaciones
            if (!numeroExpediente) {
                mostrarMensaje('Por favor, ingrese el número de expediente', 'error');
                inputExpediente.focus();
                return;
            }

            const regexExpediente = /^\d{5,6}-\d{4}-\d{1,2}$/;
            if (!regexExpediente.test(numeroExpediente)) {
                mostrarMensaje('Formato de expediente inválido. Use: XXXXX-YYYY-X o XXXXXX-YYYY-X', 'error');
                inputExpediente.focus();
                return;
            }

            if (!nombreDirector) {
                mostrarMensaje('Por favor, ingrese el nombre del director', 'error');
                inputDirector.focus();
                return;
            }

            if (!cargoDirector) {
                mostrarMensaje('Por favor, ingrese el cargo del director', 'error');
                inputCargo.focus();
                return;
            }

            try {
                btnGenerar.disabled = true;
                btnGenerar.textContent = 'Generando...';
                btnGenerar.style.background = '#999';
                mostrarMensaje('Generando carta de aceptación...', 'info');

                console.log(solicitudID);

                const resultado = await api.generarCartaAceptacion(
                    solicitudID,
                    numeroExpediente,
                    formato,
                    nombreDirector,
                    cargoDirector
                );

                if (resultado.success) {
                    mostrarMensaje('Carta generada exitosamente', 'exito');
                
                    if (!resultado.data.archivo || !resultado.data.archivo.url) {
                        throw new Error('El archivo de la carta no está disponible en el servidor.');
                    }

                    const link = document.createElement('a');
                    link.href = resultado.data.archivo.url;
                    link.download = resultado.data.archivo.nombre;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);

                    setTimeout(() => {
                        this.cerrarDialogCarta();
                        
                        if (typeof mostrarNotificacion === 'function') {
                            mostrarNotificacion('Carta descargada correctamente', 'success');
                        }
                    }, 2000);
                } else {
                    console.error('❌ Error en resultado:', resultado);
                    mostrarMensaje('❌ ' + resultado.message, 'error');
                    btnGenerar.disabled = false;
                    btnGenerar.textContent = 'Generar Carta';
                    btnGenerar.style.background = '#4CAF50';
                }

            } catch (error) {
                console.error('💥 Error al generar carta:', error);
                
                let mensajeError = error.message || 'Error desconocido';
                
                if (error.response) {
                    console.error('Response data:', error.response);
                    mensajeError = error.response.message || mensajeError;
                }
                
                mostrarMensaje('❌ Error al generar la carta: ' + mensajeError, 'error');
                btnGenerar.disabled = false;
                btnGenerar.textContent = 'Generar Carta';
                btnGenerar.style.background = '#4CAF50';
            }
        }

        // ============ UTILIDADES ============
        
        b64toBlob(base64, type) {
            const byteCharacters = atob(base64);
            const byteNumbers = Array.from(byteCharacters, c => c.charCodeAt(0));
            const byteArray = new Uint8Array(byteNumbers);
            return new Blob([byteArray], { type });
        }

        // ============ API PÚBLICA ============
        
        async recargar() {
            if (this.initialized) {
                await this.cargarPracticantes();
                await this.cargarAreas();
            }
        }

        obtenerSolicitudActual() {
            return this.state.solicitudIDActual;
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
                this.state.solicitudIDActual = null;
                this.state.enviandoSolicitud = false;
                this.state.practicantesCache = null;
                this.state.areasCache = null;

                // Cerrar modales si están abiertos
                this.cerrarModal('modalSubirDocumento');
                this.cerrarModal('modalEnviarSolicitud');
                
                const dialogCarta = document.getElementById('dialogCarta');
                if (dialogCarta && dialogCarta.classList.contains('active')) {
                    this.cerrarDialogCarta();
                }

                // Limpiar selects
                const selectPracticanteDoc = document.getElementById('selectPracticanteDoc');
                const selectPracticanteModal = document.getElementById('practicanteDocumento');
                
                if (selectPracticanteDoc) {
                    selectPracticanteDoc.innerHTML = '<option value="">Seleccionar practicante...</option>';
                }
                if (selectPracticanteModal) {
                    selectPracticanteModal.innerHTML = '<option value="">Seleccionar practicante...</option>';
                }

                // Limpiar lista de documentos
                const listaDocumentos = document.getElementById('listaDocumentos');
                if (listaDocumentos) {
                    listaDocumentos.innerHTML = '<p>Seleccione un practicante...</p>';
                }

                // Marcar como no inicializado
                this.initialized = false;

            } catch (error) {
                console.error('❌ Error al limpiar módulo Documentos:', error);
            }
        }
    }

    // ==================== REGISTRO DEL MÓDULO ====================
    
    const moduleDefinition = {
        async init() {
            const instance = new DocumentosModule();
            await instance.init();
            
            // Exponer instancia globalmente para compatibilidad
            window.documentosModule = instance;
            
            return instance;
        },
        
        async destroy(instance) {
            if (instance && instance.destroy) {
                await instance.destroy();
                delete window.documentosModule;
            }
        }
    };

    if (window.moduleManager) {
        window.moduleManager.register(MODULE_NAME, moduleDefinition);
    } else {
        console.error('❌ ModuleManager no está disponible para módulo Documentos');
    }

})();