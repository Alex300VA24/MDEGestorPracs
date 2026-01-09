// practicantes.js - Versión completa con todos los métodos

(function() {
    'use strict';

    const MODULE_NAME = 'practicantes';
    
    let moduleState = {
        modoEdicion: false,
        idEdicion: null,
        eventListeners: [],
        intervalos: [],
        timeouts: [],
        practicantesCache: null,
        lastFetchTime: null
    };

    class PracticantesModule {
        constructor() {
            this.state = moduleState;
            this.initialized = false;
        }

        // ============ INICIALIZACIÓN ============
        
        async init() {
            if (this.initialized) {
                console.warn('⚠️ Módulo Practicantes ya inicializado');
                return this;
            }

            try {
                await this.configurarPermisos();
                await this.cargarDatosIniciales();
                this.configurarEventListeners();
                
                this.initialized = true;
                return this;
                
            } catch (error) {
                console.error('❌ Error al inicializar módulo Practicantes:', error);
                throw error;
            }
        }

        // ============ CONFIGURACIÓN INICIAL ============
        
        async configurarPermisos() {
            const nombreArea = sessionStorage.getItem('nombreArea');
            const esRRHH = nombreArea === 'Gerencia de Recursos Humanos';

            const filtroAreaDiv = document.getElementById('filtroArea')?.closest('div');
            const btnNuevo = document.getElementById('btnNuevoPracticante');

            if (!esRRHH) {
                filtroAreaDiv?.style.setProperty('display', 'none');
                btnNuevo?.style.setProperty('display', 'none');
            } else {
                filtroAreaDiv?.style.removeProperty('display');
                btnNuevo?.style.removeProperty('display');
            }
        }

        async cargarDatosIniciales() {
            await this.cargarAreasFiltro();
            await this.cargarPracticantes();
        }

        // ============ CARGAR ÁREAS PARA FILTROS ============
        
        async cargarAreasFiltro() {
            try {
                
                const response = await api.listarAreas();
                
                if (!response || !response.data) {
                    throw new Error('Respuesta inválida al cargar áreas');
                }
                
                const areas = response.data;
                const selectFiltro = document.getElementById('filtroArea');
                const selectEnvio = document.getElementById('areaDestino');
                
                // Limpiar opciones existentes (excepto la primera)
                if (selectFiltro) {
                    while (selectFiltro.options.length > 1) {
                        selectFiltro.remove(1);
                    }
                }
                
                if (selectEnvio) {
                    while (selectEnvio.options.length > 1) {
                        selectEnvio.remove(1);
                    }
                }
                
                // Agregar opciones de áreas
                areas.forEach(area => {
                    if (selectFiltro) {
                        const option = document.createElement('option');
                        option.value = area.AreaID;
                        option.textContent = area.NombreArea;
                        selectFiltro.appendChild(option);
                    }
                    
                    if (selectEnvio) {
                        const option = document.createElement('option');
                        option.value = area.AreaID;
                        option.textContent = area.NombreArea;
                        selectEnvio.appendChild(option);
                    }
                });
            } catch (error) {
                console.error('❌ Error al cargar áreas:', error);
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error al cargar áreas',
                    mensaje: error.message || 'No se pudieron cargar las áreas'
                });
            }
        }

        // ============ CARGAR PRACTICANTES ============
        
        async cargarPracticantes() {
            const ahora = Date.now();
            if (this.state.lastFetchTime && (ahora - this.state.lastFetchTime) < 1000) {
                return;
            }

            try {
                
                const response = await api.getPracticantes();
                const practicantes = response.data || [];

                this.state.practicantesCache = practicantes;
                this.state.lastFetchTime = ahora;

                this.renderizarTablaPracticantes(practicantes);
            } catch (error) {
                console.error('❌ Error al cargar practicantes:', error);
                this.mostrarErrorTabla();
                throw error;
            }
        }

        // ============ RENDERIZAR TABLA ============
        
        renderizarTablaPracticantes(practicantes) {
            const tbody = document.querySelector('#tablaPracticantes tbody');
            if (!tbody) {
                console.error('❌ No se encontró el tbody');
                return;
            }

            tbody.innerHTML = '';

            if (!practicantes || practicantes.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center">No hay practicantes registrados</td></tr>';
                return;
            }

            const nombreArea = sessionStorage.getItem('nombreArea');
            const areaIDUsuario = parseInt(sessionStorage.getItem('areaID')) || null;
            const esRRHH = nombreArea === 'Gerencia de Recursos Humanos';
            
            const practicantesFiltrados = esRRHH 
                ? practicantes 
                : practicantes.filter(p => {
                    if (areaIDUsuario && p.AreaID) {
                        return p.AreaID === areaIDUsuario;
                    }
                    const areaPracticante = p.NombreArea || p.Area;
                    return areaPracticante === nombreArea;
                });

            if (practicantesFiltrados.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center">No hay practicantes en tu área</td></tr>';
                return;
            }

            practicantesFiltrados.forEach(p => {
                const fila = this.crearFilaPracticante(p, esRRHH, nombreArea);
                tbody.appendChild(fila);
            });
        }

        crearFilaPracticante(p, esRRHH, nombreArea) {
            const fila = document.createElement('tr');
            
            const estadoDescripcion = p.EstadoDescripcion || p.Estado || 'Pendiente';
            const areaNombre = p.NombreArea || p.Area || '-';
            const nombreCompleto = p.NombreCompleto || 
                `${p.Nombres || ''} ${p.ApellidoPaterno || ''} ${p.ApellidoMaterno || ''}`.trim();

            const estadoBadge = `<span class="status-badge status-${estadoDescripcion.toLowerCase()}">${estadoDescripcion.toUpperCase()}</span>`;
            const mostrarBotonAceptar = (areaNombre === nombreArea) && estadoDescripcion === 'Pendiente';

            fila.innerHTML = `
                <td>${p.DNI}</td>
                <td>${nombreCompleto}</td>
                <td>${p.Carrera || '-'}</td>
                <td>${p.Universidad || '-'}</td>
                <td>${p.FechaRegistro ? new Date(p.FechaRegistro).toLocaleDateString() : '-'}</td>
                <td>${areaNombre}</td>
                <td>${estadoBadge}</td>
                <td class="action-buttons">
                    ${esRRHH ? '<button class="btn-primary btn-editar" title="Editar"><i class="fas fa-edit"></i></button>' : ''}
                    <button class="btn-success btn-ver" title="Ver"><i class="fas fa-eye"></i></button>
                    ${mostrarBotonAceptar ? '<button class="btn-warning btn-aceptar" title="Aceptar"><i class="fas fa-check"></i></button>' : ''}
                    ${esRRHH ? '<button class="btn-danger btn-eliminar" title="Eliminar"><i class="fas fa-trash"></i></button>' : ''}
                </td>
            `;

            if (esRRHH) {
                const btnEditar = fila.querySelector('.btn-editar');
                const btnEliminar = fila.querySelector('.btn-eliminar');
                
                if (btnEditar) {
                    btnEditar.addEventListener('click', () => this.abrirModalEditar(p.PracticanteID));
                }
                if (btnEliminar) {
                    btnEliminar.addEventListener('click', () => this.eliminarPracticante(p.PracticanteID));
                }
            }

            const btnVer = fila.querySelector('.btn-ver');
            if (btnVer) {
                btnVer.addEventListener('click', () => this.verPracticante(p.PracticanteID));
            }

            if (mostrarBotonAceptar) {
                const btnAceptar = fila.querySelector('.btn-aceptar');
                if (btnAceptar) {
                    btnAceptar.addEventListener('click', () => this.abrirModalAceptar(p.PracticanteID));
                }
            }

            return fila;
        }

        mostrarErrorTabla() {
            const tbody = document.querySelector('#tablaPracticantes tbody');
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Error al cargar practicantes</td></tr>';
            }
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

            // Botón nuevo practicante
            const btnNuevo = document.getElementById('btnNuevoPracticante');
            addListener(btnNuevo, 'click', () => this.abrirModalNuevo());

            // Botón aplicar filtros
            const btnAplicarFiltros = document.getElementById('btnAplicarFiltros');
            addListener(btnAplicarFiltros, 'click', () => this.aplicarFiltros());

            // Formulario practicante
            const formPracticante = document.getElementById('formPracticante');
            addListener(formPracticante, 'submit', (e) => this.handleSubmitPracticante(e));

            // Cerrar modal
            const btnCerrarModal = document.querySelector('#PracticanteModal .modal-close');
            addListener(btnCerrarModal, 'click', () => this.cerrarModal());

            // Modal aceptar/rechazar
            const selectDecision = document.getElementById('decisionAceptacion');
            addListener(selectDecision, 'change', () => this.manejarCambioDecision());

            const formAceptar = document.getElementById('formAceptarPracticante');
            addListener(formAceptar, 'submit', (e) => this.handleSubmitAceptar(e));

            // === MENSAJES ===
    
            const btnMensajes = document.getElementById('btnMensajes');
            addListener(btnMensajes, 'click', () => this.abrirModalMensajes());

            const btnCerrarMensajes = document.getElementById('btnCerrarMensajes');
            addListener(btnCerrarMensajes, 'click', () => this.cerrarModalMensajes());

            // Event delegation para botones de eliminar mensaje
            const listaMensajes = document.getElementById('listaMensajes');
            if (listaMensajes) {
                const handlerEliminar = (e) => {
                    if (e.target.closest('.btn-eliminar')) {
                        const mensajeID = e.target.closest('.btn-eliminar').dataset.mensajeId;
                        if (mensajeID) {
                            this.eliminarMensaje(parseInt(mensajeID));
                        }
                    }
                };
                
                addListener(listaMensajes, 'click', handlerEliminar);
            }

            // Event delegation para botones de responder
            if (listaMensajes) {
                const handlerResponder = (e) => {
                    if (e.target.closest('.responder-solicitud')) {
                        const btn = e.target.closest('.responder-solicitud');
                        const mensajeID = btn.dataset.mensajeId;
                        const solicitudID = btn.dataset.solicitudId;
                        if (mensajeID && solicitudID) {
                            this.responderSolicitud(parseInt(mensajeID), parseInt(solicitudID));
                        }
                    }
                };
                
                addListener(listaMensajes, 'click', handlerResponder);
            }

            // === ENVIAR SOLICITUD A ÁREA ===
            
            const btnEnviarSolicitud = document.getElementById('btnEnviarSolicitudArea');
            addListener(btnEnviarSolicitud, 'click', () => this.abrirModalEnviarSolicitud());

            const btnCerrarEnvio = document.getElementById('btnCerrarEnvioSolicitud');
            addListener(btnCerrarEnvio, 'click', () => this.cerrarModalEnviarSolicitud());

            const btnConfirmarEnvio = document.getElementById('btnConfirmarEnvioSolicitud');
            addListener(btnConfirmarEnvio, 'click', () => this.enviarSolicitudArea());

            // Cerrar modal de mensajes al hacer clic fuera
            const modalMensajes = document.getElementById('modalMensajes');
            if (modalMensajes) {
                const handlerClickOutside = (e) => {
                    if (e.target === modalMensajes) {
                        this.cerrarModalMensajes();
                    }
                };
                addListener(modalMensajes, 'click', handlerClickOutside);
            }

            // Cerrar modal de enviar solicitud al hacer clic fuera
            const modalEnviarSolicitud = document.getElementById('modalEnviarSolicitud');
            if (modalEnviarSolicitud) {
                const handlerClickOutside = (e) => {
                    if (e.target === modalEnviarSolicitud) {
                        this.cerrarModalEnviarSolicitud();
                    }
                };
                addListener(modalEnviarSolicitud, 'click', handlerClickOutside);
            }

            console.log(`✅ ${this.state.eventListeners.length} event listeners configurados`);

            // Validaciones
            this.configurarValidacionesInputs();
        }

        configurarValidacionesInputs() {
            const dniInput = document.getElementById('DNI');
            const telefonoInput = document.getElementById('Telefono');

            if (dniInput) {
                const handlerDNI = function() {
                    this.value = this.value.replace(/\D/g, '').slice(0, 8);
                };
                dniInput.addEventListener('input', handlerDNI);
                this.state.eventListeners.push({
                    element: dniInput,
                    event: 'input',
                    handler: handlerDNI
                });
            }

            if (telefonoInput) {
                const handlerFocus = function() {
                    if (!this.value.startsWith('+51')) {
                        this.value = '+51 ';
                    }
                };
                
                const handlerInput = function() {
                    let numeros = this.value.replace(/\D/g, '').slice(0, 11);
                    if (!numeros.startsWith("51")) numeros = "51" + numeros;
                    this.value = "+51 " + numeros.slice(2);
                };

                telefonoInput.addEventListener('focus', handlerFocus);
                telefonoInput.addEventListener('input', handlerInput);
                
                this.state.eventListeners.push(
                    { element: telefonoInput, event: 'focus', handler: handlerFocus },
                    { element: telefonoInput, event: 'input', handler: handlerInput }
                );
            }
        }

        // ============ FILTROS ============
        
        async aplicarFiltros() {
            const nombre = document.getElementById('filtroNombre')?.value || '';
            let areaID = document.getElementById('filtroArea')?.value;

            const areaIDUsuario = parseInt(sessionStorage.getItem('areaID')) || null;
            const nombreAreaUsuario = sessionStorage.getItem('nombreArea');
            const esRRHH = nombreAreaUsuario === 'Gerencia de Recursos Humanos';

            if (!esRRHH && (!areaID || areaID === 'null')) {
                areaID = areaIDUsuario;
            }

            const filtros = { 
                nombre: nombre || null,
                areaID: areaID || null 
            };

            try {
                const response = await api.filtrarPracticantes(filtros);

                if (response.success) {
                    this.renderizarTablaPracticantes(response.data);
                } else {
                    mostrarAlerta({
                        tipo: 'error',
                        titulo: 'Error',
                        mensaje: response.message || 'Error al filtrar practicantes'
                    });
                }
            } catch (error) {
                console.error('Error al filtrar:', error);
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error al filtrar',
                    mensaje: error.message || 'No se pudieron cargar los practicantes'
                });
            }
        }

        // ============ MODALES ============
        
        abrirModalNuevo() {
            this.state.modoEdicion = false;
            this.state.idEdicion = null;
            
            document.getElementById("tituloModalPracticante").textContent = "Nuevo Practicante";
            document.getElementById("formPracticante").reset();
            document.getElementById("practicanteID").value = "";
            
            this.abrirModal();
        }

        async abrirModalEditar(id) {
            try {
                this.state.modoEdicion = true;
                this.state.idEdicion = id;
                
                document.getElementById("tituloModalPracticante").textContent = "Editar Practicante";

                const res = await api.getPracticante(id);
                const p = res.data;

                document.getElementById("practicanteID").value = p.ID || "";
                document.getElementById("Nombres").value = p.Nombres || "";
                document.getElementById("ApellidoPaterno").value = p.ApellidoPaterno || "";
                document.getElementById("ApellidoMaterno").value = p.ApellidoMaterno || "";
                document.getElementById("DNI").value = p.DNI || "";
                document.getElementById("Carrera").value = p.Carrera || "";
                document.getElementById("Universidad").value = p.Universidad || "";
                document.getElementById("Direccion").value = p.Direccion || "";
                document.getElementById("Telefono").value = p.Telefono || "";
                document.getElementById("Email").value = p.Email || "";

                this.abrirModal();
            } catch (err) {
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error',
                    mensaje: "Error al obtener datos del practicante"
                });
            }
        }

        abrirModal() {
            const el = document.getElementById("PracticanteModal");
            if (el) {
                el.classList.remove('hidden');
                el.style.display = "flex";
            }
        }

        cerrarModal() {
            const el = document.getElementById("PracticanteModal");

            if (el) {
                el.style.display = "none";
                el.classList.add('hidden');
            }
            document.getElementById("formPracticante")?.reset();
            
            this.state.modoEdicion = false;
            this.state.idEdicion = null;
        }

        // ============ VER PRACTICANTE ============
        
        async verPracticante(id) {
            try {
                const res = await api.getPracticante(id);
                const p = res.data;

                let genero = p.Genero === 'M' ? 'Masculino' : 'Femenino';
                const fecha = (v) => v && v !== "0000-00-00" ? v : "-";
                
                const modalHTML = `
                <div id="modalVerPracticante" class="modal-ver-practicante">
                    <div class="modal-ver-container">
                        <div class="modal-ver-header">
                            <h3>Detalles del Practicante</h3>
                            <button class="modal-ver-close" aria-label="Cerrar">&times;</button>
                        </div>
                        <div class="modal-ver-body">
                            <h4 class="modal-ver-nombre">${p.Nombres} ${p.ApellidoPaterno} ${p.ApellidoMaterno}</h4>
                            <div class="modal-ver-grid">
                                <p><strong>Género:</strong> ${genero}</p>
                                <p><strong>DNI:</strong> ${p.DNI || '-'}</p>
                                <p><strong>Carrera:</strong> ${p.Carrera || '-'}</p>
                                <p><strong>Universidad:</strong> ${p.Universidad || '-'}</p>
                                <p><strong>Área:</strong> ${p.Area || p.NombreArea || '-'}</p>
                                <p><strong>Dirección:</strong> ${p.Direccion || '-'}</p>
                                <p><strong>Teléfono:</strong> ${p.Telefono || '-'}</p>
                                <p class="col-span"><strong>Email:</strong> ${p.Email || '-'}</p>
                                <p><strong>Fecha Registro:</strong> ${fecha(p.FechaRegistro)}</p>
                                <p><strong>Fecha Entrada:</strong> ${fecha(p.FechaEntrada)}</p>
                                <p><strong>Fecha Salida:</strong> ${fecha(p.FechaSalida)}</p>
                                <p class="col-span estado">
                                    <strong>Estado:</strong>
                                    <span class="badge ${p.Estado === 'Activo' ? 'activo' : 'inactivo'}">
                                        ${p.Estado || p.EstadoDescripcion || 'Pendiente'}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="modal-ver-footer">
                            <button id="btnCerrarVerPracticante" class="btn btn-primary">Cerrar</button>
                        </div>
                    </div>
                </div>`;
                
                document.body.insertAdjacentHTML('beforeend', modalHTML);
                
                const overlay = document.getElementById('modalVerPracticante');
                const closeBtn = overlay.querySelector('.modal-ver-close');
                const cerrarBtn = overlay.querySelector('#btnCerrarVerPracticante');
                const removeOverlay = () => overlay && overlay.remove();
                
                overlay.addEventListener('click', (e) => { 
                    if (e.target === overlay) removeOverlay(); 
                });
                closeBtn.addEventListener('click', removeOverlay);
                cerrarBtn.addEventListener('click', removeOverlay);

            } catch (err) {
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error',
                    mensaje: "Error al obtener practicante: " + err.message
                });
            }
        }

        // ============ ELIMINAR PRACTICANTE ============
        
        async eliminarPracticante(id) {
            const resultado = await mostrarAlerta({
                tipo: 'warning',
                titulo: '¿Estás seguro?',
                mensaje: "¿Seguro que deseas eliminar este practicante?",
                showCancelButton: true
            });

            if (!resultado.isConfirmed) {
                return;
            }

            try {
                const res = await api.delete(`/practicantes/${id}`);
                await this.cargarPracticantes();
                
                mostrarAlerta({
                    tipo: 'success',
                    titulo: 'Eliminado',
                    mensaje: res.message
                });
            } catch (err) {
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error',
                    mensaje: "Error al eliminar: " + err.message
                });
            }
        }

        // ============ MODAL ACEPTAR/RECHAZAR ============
        
        async abrirModalAceptar(practicanteID) {
            try {
                const responseSolicitud = await api.obtenerSolicitudPorPracticante(practicanteID);
                
                if (!responseSolicitud.success || !responseSolicitud.data) {
                    mostrarAlerta({
                        tipo: 'error',
                        titulo: 'Error',
                        mensaje: "No se pudo obtener la solicitud del practicante."
                    });
                    return;
                }
                
                const solicitudID = responseSolicitud.data.SolicitudID;
                
                document.getElementById('aceptarPracticanteID').value = practicanteID;
                document.getElementById('aceptarSolicitudID').value = solicitudID;
                
                const responsePracticante = await api.getPracticante(practicanteID);
                
                if (responsePracticante.success) {
                    this.mostrarInfoPracticante(responsePracticante.data);
                }
                
                this.openModalGenerico('modalAceptarPracticante');
                
            } catch (error) {
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error',
                    mensaje: "Error al cargar información del practicante"
                });
            }
        }

        mostrarInfoPracticante(p) {
            const infoContainer = document.getElementById('infoPracticante');
            
            if (!infoContainer) {
                console.warn('No se encontró el contenedor de información del practicante');
                return;
            }
            
            infoContainer.innerHTML = `
                <h4>${p.Nombres} ${p.ApellidoPaterno} ${p.ApellidoMaterno}</h4>
                <p><strong>DNI:</strong> ${p.DNI}</p>
                <p><strong>Carrera:</strong> ${p.Carrera}</p>
                <p><strong>Universidad:</strong> ${p.Universidad}</p>
                <p><strong>Email:</strong> ${p.Email}</p>
                <p><strong>Teléfono:</strong> ${p.Telefono}</p>
            `;
        }

        manejarCambioDecision() {
            const decision = document.getElementById('decisionAceptacion');
            const camposAceptacion = document.getElementById('camposAceptacion');
            
            if (!decision || !camposAceptacion) return;

            if (decision.value === 'aceptar') {
                camposAceptacion.classList.remove('hidden');
                camposAceptacion.style.display = 'block';
                
                ['fechaEntrada', 'fechaSalida'].forEach(id => {
                    const campo = document.getElementById(id);
                    if (campo) campo.required = true;
                });
                
            } else {
                camposAceptacion.classList.add('hidden');
                camposAceptacion.style.display = 'none';
                
                ['fechaEntrada', 'fechaSalida'].forEach(id => {
                    const campo = document.getElementById(id);
                    if (campo) campo.required = false;
                });
            }
        }

        async handleSubmitAceptar(e) {
            e.preventDefault();

            const decision = document.getElementById('decisionAceptacion')?.value;
            const practicanteID = document.getElementById('aceptarPracticanteID')?.value;
            const solicitudID = document.getElementById('aceptarSolicitudID')?.value;
            const mensajeRespuesta = document.getElementById('mensajeRespuesta')?.value;

            if (!decision || !practicanteID || !solicitudID) {
                mostrarAlerta({
                    tipo: 'info',
                    mensaje: 'Faltan datos en el formulario'
                });
                return;
            }

            if (decision === 'aceptar') {
                await this.procesarAceptacion(practicanteID, solicitudID, mensajeRespuesta);
            } else if (decision === 'rechazar') {
                await this.procesarRechazo(practicanteID, solicitudID, mensajeRespuesta);
            }
        }

        async procesarAceptacion(practicanteID, solicitudID, mensajeRespuesta) {
            const fechaEntradaVal = document.getElementById('fechaEntrada')?.value;
            const fechaSalidaVal = document.getElementById('fechaSalida')?.value;

            if (!fechaEntradaVal || !fechaSalidaVal) {
                mostrarAlerta({
                    tipo: 'info',
                    mensaje: 'Debes ingresar las fechas de entrada y salida.'
                });
                return false;
            }

            function parseFechaInput(dateStr) {
                const m = dateStr.match(/^(\d{4})-(\d{2})-(\d{2})$/);
                if (!m) return new Date(NaN);
                const year = Number(m[1]);
                const month = Number(m[2]) - 1;
                const day = Number(m[3]);
                return new Date(year, month, day);
            }

            const entrada = parseFechaInput(fechaEntradaVal);
            const salida = parseFechaInput(fechaSalidaVal);

            if (isNaN(entrada) || isNaN(salida)) {
                mostrarAlerta({
                    tipo: 'error',
                    mensaje: 'Formato de fecha inválido.'
                });
                return false;
            }

            const hoy = new Date();
            hoy.setHours(0, 0, 0, 0);

            if (entrada < hoy) {
                mostrarAlerta({
                    tipo: 'warning',
                    mensaje: 'La fecha de entrada no puede ser anterior a hoy.'
                });
                return false;
            }

            if (salida <= entrada) {
                mostrarAlerta({
                    tipo: 'warning',
                    mensaje: 'La fecha de salida debe ser posterior a la fecha de entrada.'
                });
                return false;
            }

            const btn = document.getElementById("btnEnviarRespuesta");
            const areaID = sessionStorage.getItem('areaID');

            try {
                const result = await ejecutarUnaVez(btn, async () => {
                    const response = await api.aceptarPracticante({
                        practicanteID: parseInt(practicanteID),
                        solicitudID: parseInt(solicitudID),
                        areaID: parseInt(areaID),
                        fechaEntradaVal,
                        fechaSalidaVal,
                        mensajeRespuesta
                    });

                    if (!response.success) {
                        throw new Error(response.message || "Error al aceptar solicitud");
                    }
                    return response;
                });

                if (result.success) {
                    mostrarAlerta({
                        tipo: 'success',
                        titulo: 'Aceptado',
                        mensaje: 'Practicante aceptado correctamente'
                    });
                    this.cerrarModalAceptar();
                    await this.cargarPracticantes();
                    return true;
                } else {
                    mostrarAlerta({
                        tipo: 'error',
                        titulo: 'Error',
                        mensaje: result.message
                    });
                    return false;
                }
            } catch (error) {
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error',
                    mensaje: 'Error al aceptar practicante: ' + error.message
                });
                return false;
            }
        }

        async procesarRechazo(practicanteID, solicitudID, mensajeRespuesta) {
            const btn = document.getElementById("btnEnviarRespuesta");
            
            try {
                const result = await ejecutarUnaVez(btn, async () => {
                    const response = await api.rechazarPracticante({
                        practicanteID: parseInt(practicanteID),
                        solicitudID: parseInt(solicitudID),
                        mensajeRespuesta
                    });
                    
                    if (!response.success) {
                        throw new Error(response.message || "Error al rechazar solicitud");
                    }
                    return response;
                });

                if (result.success) {
                    mostrarAlerta({
                        tipo: 'info',
                        mensaje: 'Practicante rechazado correctamente'
                    });
                    this.cerrarModalAceptar();
                    await this.cargarPracticantes();
                    return true;
                } else {
                    mostrarAlerta({
                        tipo: 'error',
                        titulo: 'Error',
                        mensaje: result.message
                    });
                    return false;
                }
            } catch (error) {
                mostrarAlerta({
                tipo: 'error',
                titulo: 'Error',
                mensaje: 'Error al rechazar practicante: ' + error.message
            });
            return false;
        }
    }

    cerrarModalAceptar() {
        this.closeModalGenerico('modalAceptarPracticante');
        document.getElementById('formAceptarPracticante')?.reset();
        const camposAceptacion = document.getElementById('camposAceptacion');
        if (camposAceptacion) {
            camposAceptacion.style.display = 'none';
        }
    }

    // ==================== MENSAJES ====================

    async cargarMensajes() {
        try {
            const areaUsuario = sessionStorage.getItem('areaID');
            
            if (!areaUsuario) {
                mostrarAlerta({
                    tipo: 'warning',
                    titulo: 'Área no encontrada',
                    mensaje: 'No se pudo identificar tu área de trabajo. Por favor, vuelve a iniciar sesión.'
                });
                return;
            }
            
            const response = await api.listarMensajes(areaUsuario);
            
            if (response.success) {
                this.mostrarMensajes(response.data);
            } else {
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error',
                    mensaje: 'Error al cargar mensajes: ' + response.message
                });
            }
        } catch (error) {
            console.error('❌ Error al cargar mensajes:', error);
            mostrarAlerta({
                tipo: 'error',
                titulo: 'Error',
                mensaje: 'No se pudieron cargar los mensajes'
            });
        }
    }

    mostrarMensajes(mensajes) {
        const container = document.getElementById('listaMensajes');
        
        if (!container) {
            console.warn('⚠️ Contenedor de mensajes no encontrado');
            return;
        }
        
        if (!mensajes || mensajes.length === 0) {
            container.innerHTML = '<p class="empty-message">No hay mensajes disponibles</p>';
            return;
        }
        
        container.innerHTML = mensajes.map(msg => this.crearMensajeHTML(msg)).join('');
    }

    crearMensajeHTML(msg) {
        const estadoSolicitud = msg.EstadoSolicitud || 'En revisión';
        const estadoClass = this.obtenerClaseEstado(estadoSolicitud);
        
        return `
            <div class="mensaje-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px; position: relative;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <strong>${msg.NombrePracticante}</strong>
                    <div>
                        <span class="badge ${estadoClass}">${estadoSolicitud}</span>
                        <button class="btn-eliminar" 
                                title="Eliminar mensaje" 
                                data-mensaje-id="${msg.MensajeID}"
                                style="margin-left: 10px; background: none; border: none; cursor: pointer; font-size: 1.2em;">
                            🗑️
                        </button>
                    </div>
                </div>
                <p><strong>De:</strong> ${msg.AreaRemitente} <strong>Para:</strong> ${msg.AreaDestino}</p>
                <p>${msg.Contenido}</p>
                <small>${new Date(msg.FechaEnvio).toLocaleString()}</small>
                ${msg.TipoMensaje === 'solicitud' && !msg.Leido ? 
                    `<button class="btn-primary responder-solicitud" 
                            data-mensaje-id="${msg.MensajeID}" 
                            data-solicitud-id="${msg.SolicitudID}"
                            style="margin-top: 10px;">
                        Responder
                    </button>` : ''}
            </div>
        `;
    }

    abrirModalMensajes() {
        this.cargarMensajes();
        this.openModalGenerico('modalMensajes');
    }

    cerrarModalMensajes() {
        this.closeModalGenerico('modalMensajes');
    }

    async eliminarMensaje(mensajeID) {
        const resultado = await mostrarAlerta({
            tipo: 'warning',
            titulo: '¿Estás seguro?',
            mensaje: "¿Seguro que deseas eliminar este mensaje?",
            showCancelButton: true
        });

        if (!resultado.isConfirmed) {
            return;
        }

        try {
            const respuesta = await api.eliminarMensaje(mensajeID);
            
            if (respuesta.success) {
                mostrarAlerta({
                    tipo: 'success',
                    titulo: 'Eliminado',
                    mensaje: respuesta.message
                });
                
                // Recargar mensajes
                await this.cargarMensajes();
            } else {
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error',
                    mensaje: respuesta.message || "No se pudo eliminar el mensaje."
                });
            }
        } catch (error) {
            console.error('❌ Error al eliminar mensaje:', error);
            mostrarAlerta({
                tipo: 'error',
                titulo: 'Error',
                mensaje: "Error al eliminar mensaje."
            });
        }
    }

    async responderSolicitud(mensajeID, solicitudID) {
        // Aquí puedes implementar la lógica para responder a una solicitud
        console.log('Responder solicitud:', { mensajeID, solicitudID });
        
        // Ejemplo: abrir modal de respuesta
        mostrarAlerta({
            tipo: 'info',
            mensaje: 'Funcionalidad de responder solicitud en desarrollo'
        });
    }

    obtenerClaseEstado(estado) {
        const estados = {
            'Vigente': 'badge-success',
            'Aprobado': 'badge-success',
            'Rechazado': 'badge-danger',
            'Pendiente': 'badge-warning',
            'En revisión': 'badge-warning'
        };
        return estados[estado] || 'badge-secondary';
    }

    // ==================== ENVIAR SOLICITUD A ÁREA ====================

    abrirModalEnviarSolicitud() {
        this.openModalGenerico('modalEnviarSolicitud');
    }

    cerrarModalEnviarSolicitud() {
        this.closeModalGenerico('modalEnviarSolicitud');
        const form = document.getElementById('formEnviarSolicitud');
        if (form) form.reset();
    }

    async enviarSolicitudArea() {
        const practicanteID = document.getElementById('practicanteEnvio')?.value;
        const areaDestinoID = document.getElementById('areaDestino')?.value;
        const mensaje = document.getElementById('mensajeEnvio')?.value;

        if (!practicanteID || !areaDestinoID) {
            mostrarAlerta({
                tipo: 'warning',
                mensaje: 'Debe seleccionar un practicante y un área destino'
            });
            return;
        }

        const btnEnviar = document.getElementById('btnConfirmarEnvioSolicitud');

        try {
            if (btnEnviar) {
                btnEnviar.disabled = true;
                btnEnviar.textContent = 'Enviando...';
            }

            const response = await api.enviarSolicitudArea({
                practicanteID: parseInt(practicanteID),
                areaDestinoID: parseInt(areaDestinoID),
                mensaje: mensaje || 'Solicitud de practicante'
            });

            if (response.success) {
                mostrarAlerta({
                    tipo: 'success',
                    titulo: 'Enviado',
                    mensaje: 'Solicitud enviada correctamente'
                });
                
                this.cerrarModalEnviarSolicitud();
            } else {
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error',
                    mensaje: response.message || 'Error al enviar la solicitud'
                });
            }
        } catch (error) {
            console.error('❌ Error al enviar solicitud:', error);
            mostrarAlerta({
                tipo: 'error',
                titulo: 'Error',
                mensaje: 'Error al enviar la solicitud'
            });
        } finally {
            if (btnEnviar) {
                btnEnviar.disabled = false;
                btnEnviar.textContent = 'Enviar Solicitud';
            }
        }
    }

    // ============ SUBMIT PRACTICANTE ============
    
    async handleSubmitPracticante(e) {
        e.preventDefault();
        
        const btnSubmit = e.target.querySelector('button[type="submit"]');
        
        if (btnSubmit?.disabled) {
            console.warn('⚠️ Formulario ya está procesándose');
            return;
        }

        if (!this.validarFormulario()) {
            return;
        }

        const formData = Object.fromEntries(new FormData(e.target).entries());

        try {
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.dataset.textoOriginal = btnSubmit.textContent;
                btnSubmit.textContent = this.state.modoEdicion ? 'Actualizando...' : 'Registrando...';
            }

            let response;
            if (this.state.modoEdicion) {
                response = await api.actualizarPracticante(this.state.idEdicion, formData);
            } else {
                response = await api.crearPracticante(formData);
            }

            if (response.success) {
                const titulo = this.state.modoEdicion ? 'Actualizado' : 'Registrado';
                const mensaje = this.state.modoEdicion 
                    ? 'Practicante actualizado correctamente'
                    : 'Practicante registrado con éxito';

                await mostrarAlerta({ tipo: 'success', titulo, mensaje });
                
                this.cerrarModal();
                await this.cargarPracticantes();
                
                // Notificar a otros módulos
                if (window.moduleManager) {
                    const docModule = window.moduleManager.getInstance('documentos');
                    if (docModule?.recargar) {
                        docModule.recargar();
                    }
                }
            } else {
                mostrarAlerta({ tipo: 'error', titulo: 'Error', mensaje: response.message });
            }

        } catch (error) {
            console.error('❌ Error en submit:', error);
            mostrarAlerta({ 
                tipo: 'error', 
                titulo: 'Error', 
                mensaje: error.message || 'Error al guardar practicante' 
            });
            
        } finally {
            if (btnSubmit) {
                btnSubmit.disabled = false;
                btnSubmit.textContent = btnSubmit.dataset.textoOriginal || 'Guardar';
            }
        }
    }

    // ============ VALIDACIÓN ============
    
    validarFormulario() {
        const dniInput = document.getElementById('DNI');
        const telefonoInput = document.getElementById('Telefono');
        const emailInput = document.getElementById('Email');
        const carreraInput = document.getElementById('Carrera');
        const universidadInput = document.getElementById('Universidad');

        if (dniInput && dniInput.value.replace(/\D/g, '').length !== 8) {
            mostrarAlerta({
                tipo: 'warning',
                titulo: 'DNI inválido',
                mensaje: 'El DNI debe tener exactamente 8 dígitos'
            });
            dniInput.focus();
            return false;
        }

        if (telefonoInput) {
            const telefonoNumeros = telefonoInput.value.replace(/\D/g, '');
            if (telefonoNumeros.length !== 11) {
                mostrarAlerta({
                    tipo: 'warning',
                    titulo: 'Teléfono inválido',
                    mensaje: 'El teléfono debe tener 9 dígitos después del +51'
                });
                telefonoInput.focus();
                return false;
            }
        }

        if (emailInput) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(emailInput.value.trim())) {
                mostrarAlerta({
                    tipo: 'warning',
                    titulo: 'Correo inválido',
                    mensaje: 'Por favor ingrese un correo electrónico válido'
                });
                emailInput.focus();
                return false;
            }
        }

        const abreviacionesCarrera = ['ing.', 'lic.', 'adm.', 'cont.', 'arq.', 'med.', 'der.', 'psic.'];
        const carreraValor = (carreraInput?.value || '').toLowerCase().trim();
        
        if (carreraInput && abreviacionesCarrera.some(abrev => carreraValor.includes(abrev))) {
            mostrarAlerta({
                tipo: 'warning',
                titulo: 'Nombre incompleto',
                mensaje: 'Escriba el nombre completo de la carrera sin abreviaciones.'
            });
            carreraInput.focus();
            return false;
        }

        if (carreraInput && carreraValor.length < 8) {
            mostrarAlerta({
                tipo: 'warning',
                titulo: 'Carrera incompleta',
                mensaje: 'La carrera debe tener mínimo 8 caracteres.'
            });
            carreraInput.focus();
            return false;
        }

        const siglasComunes = ['unt','upao','upt','ucv','upc','upn','ulima','pucp','usmp','uap','utp','unfv','unmsm'];
        const excepcionesPalabras = ['de','la','del','y','e','en','para','por','el','los','las'];
        const universidadValor = (universidadInput?.value || '').toLowerCase().trim();

        if (siglasComunes.includes(universidadValor.replace(/\./g, ''))) {
            mostrarAlerta({
                tipo: 'warning',
                titulo: 'Universidad incompleta',
                mensaje: 'Por favor, escriba el nombre completo de la universidad.'
            });
            universidadInput.focus();
            return false;
        }

        const palabras = universidadValor.split(/\s+/).filter(Boolean);
        const tienePalabraCorta = palabras.some(p => p.length <= 3 && !excepcionesPalabras.includes(p));
        
        if (tienePalabraCorta) {
            mostrarAlerta({
                tipo: 'warning',
                titulo: 'Universidad incompleta',
                mensaje: 'Por favor, escriba el nombre completo de la universidad.'
            });
            universidadInput.focus();
            return false;
        }

        if (universidadValor.length < 20) {
            mostrarAlerta({
                tipo: 'warning',
                titulo: 'Universidad incompleta',
                mensaje: 'Debe tener mínimo 20 caracteres.'
            });
            universidadInput.focus();
            return false;
        }

        return true;
    }

    // ============ UTILIDADES DE MODAL ============
    
    openModalGenerico(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
        }
    }

    closeModalGenerico(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            modal.classList.add('hidden');
        }
    }

    // ============ API PÚBLICA ============
    
    async recargar() {
        if (this.initialized) {
            await this.cargarPracticantes();
        }
    }

    obtenerPracticantes() {
        return this.state.practicantesCache;
    }

    // ============ LIMPIEZA ============
    
    async destroy() {

        try {
            this.state.eventListeners.forEach(({ element, event, handler, options }) => {
                if (element) {
                    element.removeEventListener(event, handler, options);
                }
            });
            this.state.eventListeners = [];

            this.state.intervalos.forEach(clearInterval);
            this.state.intervalos = [];

            this.state.timeouts.forEach(clearTimeout);
            this.state.timeouts = [];

            this.state.practicantesCache = null;
            this.state.lastFetchTime = null;
            this.state.modoEdicion = false;
            this.state.idEdicion = null;

            this.initialized = false;

        } catch (error) {
            console.error('❌ Error al limpiar módulo:', error);
        }
    }
}

// ==================== REGISTRO DEL MÓDULO ====================

const moduleDefinition = {
    async init() {
        const instance = new PracticantesModule();
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
    console.error('❌ ModuleManager no está disponible');
}
})();