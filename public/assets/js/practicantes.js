
window.initPracticantes = function() {
    console.log("Practicantes iniciado");
    // TODO: aquí va tu código que antes se ejecutaba automáticamente


    let modoEdicion = false;
    let idEdicion = null;

    const inicializar = async () => {
        // Aquí defines el área del usuario logueado (puede venir del backend o sesión)
        const nombreAreaUsuario = sessionStorage.getItem('nombreArea'); 
        // Ejemplo: "RRHH", "Sistemas", "Contabilidad", etc.

        // Obtenemos el div del filtro de área
        const filtroAreaDiv = document.getElementById('filtroArea').closest('div');
        const btnNuevoPracticante = document.getElementById('btnNuevoPracticante');

        // Si el usuario NO es de RRHH, ocultamos el filtro de área y el boton de crear
        if (nombreAreaUsuario !== 'Gerencia de Recursos Humanos') {
            filtroAreaDiv.style.display = 'none';
            btnNuevoPracticante.style.display = 'none';
        }
        await inicializarModulo();
        configurarEventListeners();
        

    };
    // Verificar si el DOM ya está cargado o esperar al evento
    if (document.readyState === 'loading') {
        // DOM aún no está listo, esperar al evento
        document.addEventListener('DOMContentLoaded', inicializar);
    } else {
        // DOM ya está listo, ejecutar inmediatamente
        inicializar();
    }


    // --- FUNCIONES MODALES ---
    function abrirModal() {
        const el = document.getElementById("PracticanteModal");
        if (el) {
            el.classList.remove('hidden');
            el.style.display = "flex";
        }
    }

    function cerrarModalPracticante() {
        const el = document.getElementById("PracticanteModal");
        if (el) {
            el.style.display = "none";
            el.classList.add('hidden');
        }
        document.getElementById("formPracticante").reset();
    }

    // 🔹 Abrir modal para NUEVO
    function abrirModalNuevoPracticante() {
        modoEdicion = false;
        document.getElementById("tituloModalPracticante").textContent = "Nuevo Practicante";
        document.getElementById("formPracticante").reset();
        document.getElementById("practicanteID").value = "";
        abrirModal();
    }

    // Cargar áreas para filtro
    async function cargarAreasFiltro() {
        try {
            const response = await api.listarAreas();
            const data = response;
            const select = document.getElementById('filtroArea');
            const selectEnvio = document.getElementById('areaDestino');
            
            data.data.forEach(area => {
                const option = document.createElement('option');
                option.value = area.AreaID;
                option.textContent = area.NombreArea;
                select.appendChild(option.cloneNode(true));
                if(selectEnvio) selectEnvio.appendChild(option);
            });
        } catch (error) {
            console.error('Error al cargar áreas:', error);
            mostrarAlerta({
                tipo: 'error',
                titulo: 'Error al cargar áreas',
                mensaje: error.message || 'No se pudieron cargar las áreas'
            });
        }
    }

    /* Captura evento del boton Aplicar filtros a la tabla de Practicantes */
    document.getElementById('btnAplicarFiltros')?.addEventListener('click', async () => {
        const nombre = document.getElementById('filtroNombre').value || '';
        const areaID = document.getElementById('filtroArea').value;

        const filtros = { nombre: nombre || null,
                        areaID: areaID || null };
        
        try {
            const response = await api.filtrarPracticantes(filtros);
            
            if(response.success) {
                actualizarTablaPracticantes(response.data);
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
    });

    // Actualizar tabla de practicantes con el mismo filtrado que cargarPracticantes
    function actualizarTablaPracticantes(practicantes) {
        const tbody = document.querySelector('#tablaPracticantes tbody');
        if (!tbody) {
            console.error('❌ No se encontró el tbody de la tabla de practicantes.');
            return;
        }

        tbody.innerHTML = '';

        if (!practicantes || practicantes.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center">No hay practicantes registrados</td></tr>`;
            return;
        }

        // Obtener datos del usuario
        const nombreAreaUsuario = sessionStorage.getItem('nombreArea');
        const esRRHH = nombreAreaUsuario === 'Gerencia de Recursos Humanos';

        // Filtrar practicantes según el área si NO es RRHH
        const practicantesFiltrados = esRRHH
            ? practicantes
            : practicantes.filter(p => {
                const areaPracticante = p.NombreArea || p.Area;
                return areaPracticante === nombreAreaUsuario;
            });

        if (practicantesFiltrados.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center">No hay practicantes registrados para tu área</td></tr>`;
            return;
        }

        practicantesFiltrados.forEach(p => {
            const fila = document.createElement('tr');

            // Normalizar nombres y propiedades
            const estadoDescripcion = p.EstadoDescripcion || p.Estado || 'Pendiente';
            const areaNombre = p.NombreArea || p.Area || '-';
            const nombreCompleto = p.NombreCompleto || `${p.Nombres || ''} ${p.ApellidoPaterno || ''} ${p.ApellidoMaterno || ''}`.trim();

            // Badge de estado
            const estadoClass = estadoDescripcion.toLowerCase();
            const estadoBadge = `<span class="status-badge status-${estadoClass}">${estadoDescripcion.toUpperCase()}</span>`;

            // Mostrar botón de aceptar solo si pertenece al área del usuario y es “Pendiente”
            const mostrarBotonAceptar = !esRRHH && areaNombre === nombreAreaUsuario && estadoDescripcion === 'Pendiente';

            // Construir fila
            fila.innerHTML = `
                <td>${p.DNI}</td>
                <td>${nombreCompleto}</td>
                <td>${p.Carrera || '-'}</td>
                <td>${p.Universidad || '-'}</td>
                <td>${p.FechaRegistro ? new Date(p.FechaRegistro).toLocaleDateString() : '-'}</td>
                <td>${areaNombre}</td>
                <td>${estadoBadge}</td>
                <td>
                    <button class="btn-primary" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-success" title="Ver">
                        <i class="fas fa-eye"></i>
                    </button>
                    ${mostrarBotonAceptar ? `
                        <button class="btn-warning" title="Aceptar">
                            <i class="fas fa-check"></i>
                        </button>` : ''}
                    <button class="btn-danger" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;

            tbody.appendChild(fila);

            // Asignar eventos
            fila.querySelector('.btn-primary').addEventListener('click', () => abrirModalEditarPracticante(p.PracticanteID));
            fila.querySelector('.btn-success').addEventListener('click', () => verPracticante(p.PracticanteID));
            fila.querySelector('.btn-danger').addEventListener('click', () => eliminarPracticante(p.PracticanteID));
            if (mostrarBotonAceptar) {
                fila.querySelector('.btn-warning').addEventListener('click', () => abrirModalAceptar(p.PracticanteID));
            }
        });
    }



    // 🔹 Abrir modal para EDITAR
    async function abrirModalEditarPracticante(id) {
        try {
            modoEdicion = true;
            document.getElementById("tituloModalPracticante").textContent = "Editar Practicante";

            const res = await api.getPracticante(id);
            idEdicion = id;
            const p = res.data;

            // Rellenar campos del formulario
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

            abrirModal();
        } catch (err) {
            mostrarAlerta({tipo:'error', titulo: 'Error', 
                    mensaje: "Error al obtener datos del practicante"});
        }
    }

    // Metodo para ver informacion del Practicante
    async function verPracticante(id) {
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
            overlay.addEventListener('click', (e) => { if (e.target === overlay) removeOverlay(); });
            closeBtn.addEventListener('click', removeOverlay);
            cerrarBtn.addEventListener('click', removeOverlay);

        } catch (err) {
            mostrarAlerta({tipo:'error', titulo: 'Error', 
                    mensaje: "Error al obtener practicante: " + err.message});
        }
    }


    async function eliminarPracticante(id) {

        if (!(await mostrarAlerta({
            tipo: 'warning',
            titulo: '¿Estás seguro?',
            mensaje: "¿Seguro que deseas eliminar este practicante?",
            showCancelButton: true
        })).isConfirmed) {
            return; // CANCELADO → salir sin continuar
        }

        // 🟢 CONFIRMADO → seguimos con el flujo
        try {
            const res = await api.delete(`/practicantes/${id}`);
            cargarPracticantes();
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


    // --- FUNCIÓN PARA CARGAR LA TABLA ---
    // Cargar practicantes desde el backend y renderizar tabla
    async function cargarPracticantes() {
        try {
            const response = await api.getPracticantes();
            const practicantes = response.data || [];

            const tbody = document.querySelector('#tablaPracticantes tbody');
            if (!tbody) {
                console.error('❌ No se encontró el tbody de la tabla de practicantes.');
                return;
            }

            tbody.innerHTML = '';

            if (practicantes.length === 0) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center">No hay practicantes registrados</td></tr>`;
                return;
            }

            // Obtener datos del usuario en sesión
            const nombreAreaUsuario = sessionStorage.getItem('nombreArea');
            const areaIDUsuario = parseInt(sessionStorage.getItem('areaID')) || null;
            const esRRHH = nombreAreaUsuario === 'Gerencia de Recursos Humanos';

            console.log({nombreAreaUsuario, areaIDUsuario, esRRHH});
            // Filtrar practicantes según el área si NO es RRHH
            const practicantesFiltrados = esRRHH
                ? practicantes
                : practicantes.filter(p => {
                    // Usar AreaID si está disponible, sino comparar por nombre
                    if (areaIDUsuario && p.AreaID) {
                        return p.AreaID === areaIDUsuario;
                    }
                    const areaPracticante = p.NombreArea || p.Area;
                    return areaPracticante === nombreAreaUsuario;
                });

            if (practicantesFiltrados.length === 0) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center">No hay practicantes registrados para tu área</td></tr>`;
                return;
            }

            practicantesFiltrados.forEach(p => {
                const fila = document.createElement('tr');

                // Normalizar nombres y propiedades
                const estadoDescripcion = p.EstadoDescripcion || p.Estado || 'Pendiente';
                const areaNombre = p.NombreArea || p.Area || '-';
                const nombreCompleto = p.NombreCompleto || `${p.Nombres || ''} ${p.ApellidoPaterno || ''} ${p.ApellidoMaterno || ''}`.trim();

                // Badge de estado
                const estadoClass = estadoDescripcion.toLowerCase();
                const estadoBadge = `<span class="status-badge status-${estadoClass}">${estadoDescripcion.toUpperCase()}</span>`;

                // Mostrar botón de aceptar solo si pertenece al área del usuario y es “Pendiente”
                const mostrarBotonAceptar = !esRRHH && areaNombre === nombreAreaUsuario && estadoDescripcion === 'Pendiente';
                // Construir fila
                fila.innerHTML = `
                    <td>${p.DNI}</td>
                    <td>${nombreCompleto}</td>
                    <td>${p.Carrera || '-'}</td>
                    <td>${p.Universidad || '-'}</td>
                    <td>${p.FechaRegistro ? new Date(p.FechaRegistro).toLocaleDateString() : '-'}</td>
                    <td>${areaNombre}</td>
                    <td>${estadoBadge}</td>
                    <td>
                        <button class="btn-primary" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-success" title="Ver">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${mostrarBotonAceptar ? `
                            <button class="btn-warning" title="Aceptar">
                                <i class="fas fa-check"></i>
                            </button>` : ''}
                        <button class="btn-danger" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;

                tbody.appendChild(fila);

                // Asignar eventos
                fila.querySelector('.btn-primary').addEventListener('click', () => abrirModalEditarPracticante(p.PracticanteID));
                fila.querySelector('.btn-success').addEventListener('click', () => verPracticante(p.PracticanteID));
                fila.querySelector('.btn-danger').addEventListener('click', () => eliminarPracticante(p.PracticanteID));
                if (mostrarBotonAceptar) {
                    fila.querySelector('.btn-warning').addEventListener('click', () => abrirModalAceptar(p.PracticanteID));
                }
            });

        } catch (error) {
            console.error('❌ Error al listar practicantes:', error);
            const tbody = document.querySelector('#tablaPracticantes tbody');
            if (tbody) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">Error al cargar los practicantes</td></tr>`;
            }
            mostrarAlerta({
                tipo: 'error',
                titulo: 'Error al cargar',
                mensaje: error.message || 'Error al cargar los practicantes'
            });
        }
    }
    // ===================================== MENSAJES Y PRACTICANTES ====================================

    // 🔹 Variables globales
    let turnosDisponibles = [];
    const areaID = () => sessionStorage.getItem('areaID');
    const esRRHH = () => sessionStorage.getItem('rolArea') === 'RRHH';


    async function inicializarModulo() {
        await cargarAreasFiltro();
    }

    // ===================================== MENSAJES ====================================

    // 🔹 Abrir modal de mensajes
    document.getElementById('btnMensajes')?.addEventListener('click', async () => {
        try {
            const areaUsuario = areaID();
            
            // Validar que areaID existe antes de hacer la llamada
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
                mostrarMensajes(response.data);
                openModal('modalMensajes');
            } else {
                mostrarAlerta({tipo: 'error', titulo: 'Error', 
                    mensaje: 'Error al cargar mensajes: ' + response.message});

            }
        } catch (error) {
            mostrarAlerta({tipo: 'error', titulo: 'Error', 
                    mensaje: 'No se pudieron cargar los mensajes'});
        }
    });

    // 🔹 Mostrar lista de mensajes
    function mostrarMensajes(mensajes) {
        const container = document.getElementById('listaMensajes');
        
        if (!mensajes || mensajes.length === 0) {
            container.innerHTML = '<p class="empty-message">No hay mensajes disponibles</p>';
            return;
        }
        
        container.innerHTML = mensajes.map(msg => crearMensajeHTML(msg)).join('');
    }

    // 🔹 Crear HTML de un mensaje
    function crearMensajeHTML(msg) {
        const estadoSolicitud = msg.EstadoSolicitud || 'En revisión';
        const estadoClass = obtenerClaseEstado(estadoSolicitud);
        
        return `
            <div class="mensaje-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px; position: relative;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <strong>${msg.NombrePracticante}</strong>
                    <div>
                        <span class="badge ${estadoClass}">${estadoSolicitud}</span>
                        <button class="btn-eliminar" 
                                title="Eliminar mensaje" 
                                onclick="eliminarMensaje(${msg.MensajeID})"
                                style="margin-left: 10px;">
                            🗑️
                        </button>
                    </div>
                </div>
                <p><strong>De:</strong> ${msg.AreaRemitente} <strong>Para:</strong> ${msg.AreaDestino}</p>
                <p>${msg.Contenido}</p>
                <small>${new Date(msg.FechaEnvio).toLocaleString()}</small>
                ${msg.TipoMensaje === 'solicitud' && !msg.Leido ? 
                    `<button onclick="responderSolicitud(${msg.MensajeID}, ${msg.SolicitudID})" 
                            class="btn-primary" 
                            style="margin-top: 10px;">
                        Responder
                    </button>` : ''}
            </div>
        `;
    }

    // 🔹 Eliminar mensaje
    async function eliminarMensaje(mensajeID) {
        if (!(await mostrarAlerta({
            tipo: 'warning',
            titulo: '¿Estás seguro?',
            mensaje: "¿Seguro que deseas eliminar este mensaje?",
            showCancelButton: true
        })).isConfirmed) {
            return; // CANCELADO → salir sin continuar
        }

        try {
            const respuesta = await api.eliminarMensaje(mensajeID);
            
            if (respuesta.success) {
                mostrarAlerta({tipo: 'success', titulo: 'Eliminado', 
                    mensaje: respuesta.message});
                
                
                // Recargar mensajes
                const response = await api.listarMensajes(areaID());
                if (response.success) {
                    mostrarMensajes(response.data);
                }
            } else {
                mostrarAlerta({tipo: 'error', titulo: 'Error', 
                    mensaje: respuesta.message || "No se pudo eliminar el mensaje."});
            }
        } catch (error) {
            mostrarAlerta({tipo: 'error', titulo: 'Error', 
                    mensaje: "Error al eliminar mensaje."});
        }
    }

    // 🔹 Cerrar modal de mensajes
    function cerrarModalMensajes() {
        closeModal('modalMensajes');
    }

    // ===================================== PRACTICANTES ====================================

    // 🔹 Actualizar tabla de practicantes
    function actualizarTablaPracticantes(practicantes) {
        const tbody = document.querySelector('#tablaPracticantes tbody');
        
        if (!tbody) {
            console.warn('No se encontró el tbody de la tabla de practicantes');
            return;
        }
        
        tbody.innerHTML = '';
        
        if (!practicantes || practicantes.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center">No hay practicantes registrados</td></tr>';
            return;
        }
        
        const areaUsuario = areaID();
        const esAreaRRHH = esRRHH();
        
        practicantes.forEach(p => {
            const tr = document.createElement('tr');
            const mostrarBotonAceptar = !esAreaRRHH && 
                                    p.AreaID == areaUsuario && 
                                    p.EstadoDescripcion === 'Pendiente';
            
            tr.innerHTML = crearFilaPracticante(p, mostrarBotonAceptar);
            tbody.appendChild(tr);
        });
    }

    // 🔹 Crear HTML de fila de practicante
    function crearFilaPracticante(p, mostrarBotonAceptar) {
        const estadoBadge = `<span class="status-badge status-${p.EstadoDescripcion.toLowerCase()}">${p.EstadoDescripcion || 'Pendiente'}</span>`;
        return `
            <td>${p.DNI}</td>
            <td>${p.Nombres} ${p.ApellidoPaterno} ${p.ApellidoMaterno}</td>
            <td>${p.Carrera}</td>
            <td>${p.Universidad}</td>
            <td>${p.FechaRegistro ? new Date(p.FechaRegistro).toLocaleDateString() : '-'}</td>
            <td>${p.NombreArea || '-'}</td>
            <td>${estadoBadge}</td>
            <td>
                <button onclick="editarPracticante(${p.PracticanteID})" 
                        class="btn-primary" 
                        title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="verPracticante(${p.PracticanteID})" 
                        class="btn-success" 
                        title="Ver">
                    <i class="fas fa-eye"></i>
                </button>
                ${mostrarBotonAceptar ? `
                    <button onclick="abrirModalAceptar(${p.PracticanteID})" 
                            class="btn-warning" 
                            title="Aceptar/Rechazar">
                        <i class="fas fa-check"></i>
                    </button>
                ` : ''}
                <button onclick="eliminarPracticante(${p.PracticanteID})" 
                        class="btn-danger" 
                        title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
    }

    // ===================================== MODAL ACEPTAR/RECHAZAR ====================================

    // 🔹 Abrir modal para aceptar/rechazar practicante
    async function abrirModalAceptar(practicanteID) {
        try {
            // Obtener solicitud del practicante
            const responseSolicitud = await api.obtenerSolicitudPorPracticante(practicanteID);
            
            if (!responseSolicitud.success || !responseSolicitud.data) {
                mostrarAlerta({tipo: 'error', titulo: 'Error', 
                    mensaje: "No se pudo obtener la solicitud del practicante."});
                return;
            }
            
            const solicitudID = responseSolicitud.data.SolicitudID;
            
            // Asignar valores a los campos ocultos
            document.getElementById('aceptarPracticanteID').value = practicanteID;
            document.getElementById('aceptarSolicitudID').value = solicitudID;
            
            // Cargar información del practicante
            const responsePracticante = await api.getPracticante(practicanteID);
            
            if (responsePracticante.success) {
                mostrarInfoPracticante(responsePracticante.data);
            }
            
            // Abrir modal
            openModal('modalAceptarPracticante');
            
        } catch (error) {
            mostrarAlerta({tipo: 'error', titulo: 'Error', 
                    mensaje: "Error al cargar información del practicante"});
        }
    }

    // 🔹 Mostrar información del practicante
    function mostrarInfoPracticante(p) {
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

    // 🔹 Cerrar modal de aceptar
    function cerrarModalAceptar() {
        closeModal('modalAceptarPracticante');
        document.getElementById('formAceptarPracticante')?.reset();
        document.getElementById('camposAceptacion').style.display = 'none';
    }

    // ===================================== TURNOS ====================================


    // ===================================== FORMULARIO ACEPTAR/RECHAZAR ====================================

    // 🔹 Manejar cambio de decisión (aceptar/rechazar)
    function manejarCambioDecision() {
        const decision = document.getElementById('decisionAceptacion');
        const camposAceptacion = document.getElementById('camposAceptacion');
        
        if (!decision || !camposAceptacion) return;

        if (decision.value === 'aceptar') {
            // Mostrar campos de aceptación
            camposAceptacion.classList.remove('hidden');
            camposAceptacion.style.display = 'block';
            
            // Establecer campos como requeridos
            ['fechaEntrada', 'fechaSalida'].forEach(id => {
                const campo = document.getElementById(id);
                if (campo) campo.required = true;
            });
            
        } else {
            // Ocultar campos de aceptación
            camposAceptacion.classList.add('hidden');
            camposAceptacion.style.display = 'none';
            
            // Quitar campos requeridos
            ['fechaEntrada', 'fechaSalida'].forEach(id => {
                const campo = document.getElementById(id);
                if (campo) campo.required = false;
            });
        }
    }

    // 🔹 Procesar aceptación de practicante
    async function procesarAceptacion(practicanteID, solicitudID, mensajeRespuesta) {
        const fechaEntradaVal = document.getElementById('fechaEntrada')?.value;
        const fechaSalidaVal  = document.getElementById('fechaSalida')?.value;

        if (!fechaEntradaVal || !fechaSalidaVal) {
            mostrarAlerta({tipo:'info', mensaje:'Debes ingresar las fechas de entrada y salida.'});
            return false;
        }

        // Helper: parsear "YYYY-MM-DD" a Date local (evita offset UTC)
        function parseFechaInput(dateStr) {
            const m = dateStr.match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (!m) return new Date(NaN);
            const year  = Number(m[1]);
            const month = Number(m[2]) - 1; // months 0-11
            const day   = Number(m[3]);
            return new Date(year, month, day);
        }

        const entrada = parseFechaInput(fechaEntradaVal);
        const salida  = parseFechaInput(fechaSalidaVal);

        // Validar que las fechas sean válidas
        if (isNaN(entrada) || isNaN(salida)) {
            mostrarAlerta({tipo:'error', mensaje:'Formato de fecha inválido.'});
            return false;
        }

        // Fecha "hoy" sin hora
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);

        // Validaciones requeridas:
        // 1) fechaEntrada no puede ser anterior a hoy
        if (entrada < hoy) {
            mostrarAlerta({tipo:'warning', mensaje:'La fecha de entrada no puede ser anterior a hoy.'});
            return false;
        }

        // 2) fechaSalida no puede ser menor que fechaEntrada
        if (salida < entrada) {
            mostrarAlerta({tipo:'warning', mensaje:'La fecha de salida no puede ser anterior a la fecha de entrada.'});
            return false;
        }

        // (Opcional) 3) si quieres evitar reservas de 0 noches, exigir salida > entrada:
        if (salida <= entrada) {
            mostrarAlerta({tipo:'warning', mensaje:'La fecha de salida debe ser posterior a la fecha de entrada.'});
            return false;
        }




        const btn = document.getElementById("btnEnviarRespuesta");

        try {
            const result = await ejecutarUnaVez(btn, async () => {
                console.log(
                    'Se envia: ', {
                        practicanteID,
                        solicitudID,
                        fechaEntradaVal,
                        fechaSalidaVal
                    }
                );
                console.log(entrada, salida);
                const response = await api.aceptarPracticante({
                    practicanteID: parseInt(practicanteID),
                    solicitudID: parseInt(solicitudID),
                    areaID: parseInt(areaID()),
                    fechaEntradaVal,
                    fechaSalidaVal,
                    mensajeRespuesta
                });
                console.log(response);
                if (!response.success) throw new Error(response.message || "Error al aceptar solicitud");
                return response;
            });

            if (result.success) {
                mostrarAlerta({tipo:'success', titulo:'Aceptado', mensaje:'Practicante aceptado correctamente'});
                cargarPracticantes();
                cerrarModalAceptar();
                return true;
            } else {
                mostrarAlerta({tipo:'error', titulo:'Error', mensaje:response.message});
                return false;
            }
        } catch (error) {
            mostrarAlerta({tipo:'error', titulo:'Error', mensaje:'Error al aceptar practicante' + error});
            return false;
        }
    }

    // 🔹 Procesar rechazo de practicante
    async function procesarRechazo(practicanteID, solicitudID, mensajeRespuesta) {
        const btn = document.getElementById("btnEnviarRespuesta");
        try {
            const result = await ejecutarUnaVez(btn, async () => {
                const response = await api.rechazarPracticante({
                    practicanteID: parseInt(practicanteID),
                    solicitudID: parseInt(solicitudID),
                    mensajeRespuesta
                });
                if (!response.success) throw new Error(response.message || "Error al recharzar solicitud");
                return response;
            });

            if (result.success) {
                mostrarAlerta({tipo:'info', mensaje:'Practicante rechazado correctamente'});
                cerrarModalAceptar();
                return true;
            } else {
                mostrarAlerta({tipo:'error', titulo:'Error', mensaje:response.message});
                return false;
            }
        } catch (error) {
            mostrarAlerta({tipo:'error', titulo:'Error', mensaje:'Error al rechazar practicante'});
            return false;
        }
    }

    function configurarValidacionesInputs() {
        const dniInput = document.getElementById('DNI');
        const telefonoInput = document.getElementById('Telefono');

        // Formateo DNI: solo números, máximo 8 dígitos
        if (dniInput) {
            dniInput.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '').slice(0, 8);
            });
        }

        // Formateo de teléfono con +51
        if (telefonoInput) {
            telefonoInput.addEventListener('focus', function () {
                if (!this.value.startsWith('+51')) {
                    this.value = '+51 ';
                }
            });

            telefonoInput.addEventListener('input', function () {
                let numeros = this.value.replace(/\D/g, '').slice(0, 11); // 11 = 51 + 9 dígitos
                if (!numeros.startsWith("51")) numeros = "51" + numeros;
                this.value = "+51 " + numeros.slice(2);
            });
        }
    }


    function validarFormularioPracticante() {
        const dniInput = document.getElementById('DNI');
        const carreraInput = document.getElementById('Carrera');
        const universidadInput = document.getElementById('Universidad');
        const telefonoInput = document.getElementById('Telefono');
        const emailInput = document.getElementById('Email');

        // --- Validar DNI ---
        if (dniInput && dniInput.value.replace(/\D/g, '').length !== 8) {
            mostrarAlerta({
                tipo: 'warning',
                titulo: 'DNI inválido',
                mensaje: 'El DNI debe tener exactamente 8 dígitos'
            });
            dniInput.focus();
            return false;
        }

        // --- Validar teléfono (+51 + 9 dígitos) ---
        if (telefonoInput) {
            const telefonoNumeros = telefonoInput.value.replace(/\D/g, '');
            if (telefonoNumeros.length !== 11) { // "51" + 9 dígitos = 11
                mostrarAlerta({
                    tipo: 'warning',
                    titulo: 'Teléfono inválido',
                    mensaje: 'El teléfono debe tener 9 dígitos después del +51'
                });
                telefonoInput.focus();
                return false;
            }
        }

        // --- Validar correo (JS) ---
        if (emailInput) {
            // regex JS seguro
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

        // --- Validación de abreviaciones en carrera ---
        const abreviacionesCarrera = ['ing.', 'lic.', 'adm.', 'cont.', 'arq.', 'med.', 'der.', 'psic.'];
        const carreraValor = (carreraInput && carreraInput.value || '').toLowerCase().trim();
        if (carreraInput && abreviacionesCarrera.some(abrev => carreraValor.includes(abrev))) {
            mostrarAlerta({
                tipo: 'warning',
                titulo: 'Nombre incompleto',
                mensaje: 'Escriba el nombre completo de la carrera sin abreviaciones.'
            });
            carreraInput.focus();
            return false;
        }

        if (carreraInput && carreraValor.length < 8) { // mínimo coherente: 15
            mostrarAlerta({
                tipo: 'warning',
                titulo: 'Carrera incompleta',
                mensaje: 'La carrera debe tener mínimo 8 caracteres.'
            });
            carreraInput.focus();
            return false;
        }

        // --- Validación de siglas en universidad ---
        const siglasComunes = ['unt','upao','upt','ucv','upc','upn','ulima','pucp','usmp','uap','utp','unfv','unmsm'];
        const excepcionesPalabrasCortas = ['de','la','del','y','e','en','para','por','el','los','las'];
        const universidadValorRaw = (universidadInput && universidadInput.value) || '';
        const universidadValor = universidadValorRaw.toLowerCase().trim();

        // 1) Comprueba si es exactamente una sigla común:
        if (siglasComunes.includes(universidadValor.replace(/\./g, ''))) {
            mostrarAlerta({
                tipo: 'warning',
                titulo: 'Universidad incompleta',
                mensaje: 'Por favor, escriba el nombre completo de la universidad.'
            });
            universidadInput.focus();
            return false;
        }

        // 2) Comprueba palabras cortas (pero ignora preposiciones comunes)
        const palabras = universidadValor.split(/\s+/).filter(Boolean);
        const tienePalabraCortaNoPermitida = palabras.some(p => (p.length <= 3) && !excepcionesPalabrasCortas.includes(p));
        if (tienePalabraCortaNoPermitida) {
            mostrarAlerta({
                tipo: 'warning',
                titulo: 'Universidad incompleta',
                mensaje: 'Por favor, escriba el nombre completo de la universidad (evite usar solo siglas o palabras muy cortas).'
            });
            universidadInput.focus();
            return false;
        }

        // 3) Comprobar longitud final (con trim)
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



    // ===================================== EVENT LISTENERS ====================================

    function configurarEventListeners() {
        
        // Botón nuevo practicante
        const btnNuevo = document.getElementById("btnNuevoPracticante");
        if (btnNuevo) {
            btnNuevo.removeEventListener("click", abrirModalNuevoPracticante);
            btnNuevo.addEventListener("click", abrirModalNuevoPracticante);
        }

        // ========== LLAMAR A LAS VALIDACIONES ==========
        configurarValidacionesInputs();

        // Formulario practicante
        const formPracticante = document.getElementById("formPracticante");
        if (formPracticante) {
            formPracticante.onsubmit = null; // Limpiar handler previo
            
            formPracticante.addEventListener("submit", async (e) => {
                e.preventDefault();
                
                // ========== VALIDAR ANTES DE ENVIAR ==========
                if (!validarFormularioPracticante()) {
                    return; // Si no pasa las validaciones, no continuar
                }
                
                const formData = Object.fromEntries(new FormData(e.target).entries());
                console.log(formData);
                let res;

                try {
                    if (modoEdicion) {
                        res = await api.actualizarPracticante(idEdicion, formData);
                    } else {
                        res = await api.crearPracticante(formData);
                    }

                    if (res.success) {
                        message = modoEdicion ? "Practicante actualizado correctamente" : "Practicante registrado con éxito"
                        titulo = modoEdicion ? "Actualizado" : "Registrado"
                        mostrarAlerta({tipo:'success', titulo: titulo, mensaje: message});
                        cerrarModalPracticante();
                        await cargarPracticantes();
                    } else {
                        mostrarAlerta({tipo:'error', titulo: 'Error', mensaje: res.message});
                    }
                } catch (error) {
                    mostrarAlerta({tipo:'error', titulo: 'Error', mensaje: "Error en formulario: " + error.message});
                }
            });
        }

        // Select de decisión
        const decisionSelect = document.getElementById('decisionAceptacion');
        if (decisionSelect) {
            decisionSelect.removeEventListener('change', manejarCambioDecision);
            decisionSelect.addEventListener('change', manejarCambioDecision);
        }

        
        // Formulario de aceptar/rechazar
        const formAceptar = document.getElementById('formAceptarPracticante');
        if (formAceptar) {
            formAceptar.addEventListener('submit', manejarSubmitAceptar);
        }
        
        // Botón enviar solicitud a área
        const btnEnviarSolicitud = document.getElementById('btnEnviarSolicitudArea');
        if (btnEnviarSolicitud) {
            btnEnviarSolicitud.addEventListener('click', () => {
                openModal('modalEnviarSolicitud');
            });
        }
        
    }

    // 🔹 Manejar submit del formulario de aceptar/rechazar
    async function manejarSubmitAceptar(e) {
        e.preventDefault();

        const decision = document.getElementById('decisionAceptacion')?.value;
        const practicanteID = document.getElementById('aceptarPracticanteID')?.value;
        const solicitudID = document.getElementById('aceptarSolicitudID')?.value;
        const mensajeRespuesta = document.getElementById('mensajeRespuesta')?.value;

        if (!decision || !practicanteID || !solicitudID) {
            mostrarAlerta({tipo:'info', mensaje:'Faltan datos en el formulario'});
            return;
        }

        if (decision === 'aceptar') {
            await procesarAceptacion(practicanteID, solicitudID, mensajeRespuesta);
        } else if (decision === 'rechazar') {
            await procesarRechazo(practicanteID, solicitudID, mensajeRespuesta);
        }
    }

    // ===================================== UTILIDADES ====================================

    // 🔹 Obtener clase CSS según estado
    function obtenerClaseEstado(estado) {
        const estados = {
            'Vigente': 'badge-success',
            'Aprobado': 'badge-success',
            'Rechazado': 'badge-danger',
            'Pendiente': 'badge-warning',
            'En revisión': 'badge-warning'
        };
        return estados[estado] || 'badge-secondary';
    }

    // 🔹 Abrir modal genérico
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
        }
    }

    // 🔹 Cerrar modal genérico
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            modal.classList.add('hidden');
        }
    }

    // 🔹 Cerrar modal de enviar solicitud
    function cerrarModalEnviarSolicitud() {
        closeModal('modalEnviarSolicitud');
        document.getElementById('formEnviarSolicitud')?.reset();
    }

    // ===================================== FUNCIONES GLOBALES ====================================
    // Estas funciones deben estar disponibles globalmente para los onclick en HTML

    window.eliminarMensaje = eliminarMensaje;
    window.abrirModalAceptar = abrirModalAceptar;
    window.cerrarModalAceptar = cerrarModalAceptar;
    window.cerrarModalMensajes = cerrarModalMensajes;
    window.cerrarModalEnviarSolicitud = cerrarModalEnviarSolicitud;
    window.actualizarTablaPracticantes = actualizarTablaPracticantes;
    window.cerrarModalPracticante = cerrarModalPracticante;


    cargarPracticantes();
};


