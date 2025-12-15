// ===================================== MENSAJES Y PRACTICANTES ====================================

// 🔹 Variables globales
let turnosDisponibles = [];
const areaID = () => sessionStorage.getItem('areaID');
const esRRHH = () => sessionStorage.getItem('rolArea') === 'RRHH';

// ===================================== INICIALIZACIÓN ====================================
document.addEventListener('DOMContentLoaded', async () => {
    await inicializarModulo();
    configurarEventListeners();
});

async function inicializarModulo() {
    await cargarAreasFiltro();
}

// ===================================== MENSAJES ====================================

// 🔹 Abrir modal de mensajes
document.getElementById('btnMensajes')?.addEventListener('click', async () => {
    try {
        const areaUsuario = areaID();
        
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
    if (!confirm('¿Seguro que deseas eliminar este mensaje?')) return;

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
    return `
        <td>${p.PracticanteID}</td>
        <td>${p.DNI}</td>
        <td>${p.Nombres} ${p.ApellidoPaterno} ${p.ApellidoMaterno}</td>
        <td>${p.Carrera}</td>
        <td>${p.Universidad}</td>
        <td>${p.FechaRegistro ? new Date(p.FechaRegistro).toLocaleDateString() : '-'}</td>
        <td>${p.NombreArea || '-'}</td>
        <td><span class="badge ${obtenerClaseEstado(p.EstadoDescripcion)}">${p.EstadoDescripcion || 'Pendiente'}</span></td>
        <td>
            <button onclick="verPracticante(${p.PracticanteID})" 
                    class="btn-info btn-sm" 
                    title="Ver">
                <i class="fas fa-eye"></i>
            </button>
            <button onclick="editarPracticante(${p.PracticanteID})" 
                    class="btn-warning btn-sm" 
                    title="Editar">
                <i class="fas fa-edit"></i>
            </button>
            <button onclick="eliminarPracticante(${p.PracticanteID})" 
                    class="btn-danger btn-sm" 
                    title="Eliminar">
                <i class="fas fa-trash"></i>
            </button>
            ${mostrarBotonAceptar ? `
                <button onclick="abrirModalAceptar(${p.PracticanteID})" 
                        class="btn-success btn-sm" 
                        title="Aceptar/Rechazar">
                    <i class="fas fa-check"></i>
                </button>
            ` : ''}
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
        camposAceptacion.style.display = 'block';
        
        // Establecer campos como requeridos
        ['fechaEntrada', 'fechaSalida'].forEach(id => {
            const campo = document.getElementById(id);
            if (campo) campo.required = true;
        });
        
    } else {
        camposAceptacion.style.display = 'none';
        
        // Quitar campos requeridos
        ['fechaEntrada', 'fechaSalida'].forEach(id => {
            const campo = document.getElementById(id);
            if (campo) campo.required = false;
        });
        
        // Limpiar turnos
        if (contenedorTurnos) {
            contenedorTurnos.innerHTML = '';
        }
    }
}

// 🔹 Procesar aceptación de practicante
async function procesarAceptacion(practicanteID, solicitudID, mensajeRespuesta) {
    const fechaEntrada = document.getElementById('fechaEntrada')?.value;
    const fechaSalida = document.getElementById('fechaSalida')?.value;

    if (!fechaEntrada || !fechaSalida) {
        mostrarAlerta({tipo:'info', mensaje:'Debes ingresar las fechas de entrada y salida.'});
        
        return false;
    }

    // Convertir a objetos Date
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0); // Para comparar solo fechas sin hora

    const entrada = new Date(fechaEntrada);
    const salida = new Date(fechaSalida);

    // Validar que fechaEntrada no sea anterior a hoy
    if (entrada < hoy) {
        mostrarAlerta({tipo:'warning', mensaje:'La fecha de entrada no puede ser anterior a hoy.'});
        return false;
    }

    // Validar que fechaEntrada no sea mayor que fechaSalida
    if (entrada > salida) {
        mostrarAlerta({tipo:'warning', mensaje:'La fecha de entrada no puede ser mayor a la fecha de salida.'});
        return false;
    }

    const btn = document.getElementById("btnEnviarRespuesta");

    try {
        const result = await ejecutarUnaVez(btn, async () => {
            const response = await api.aceptarPracticante({
                practicanteID: parseInt(practicanteID),
                solicitudID: parseInt(solicitudID),
                areaID: parseInt(areaID()),
                fechaEntrada,
                fechaSalida,
                mensajeRespuesta
            });
            if (!response.success) throw new Error(response.message || "Error al aceptar solicitud");
            return response;
        });

        if (result.success) {
            mostrarAlerta({tipo:'success', titulo:'Aceptado', mensaje:'Practicante aceptado correctamente'});
            cerrarModalAceptar();
            return true;
        } else {
            mostrarAlerta({tipo:'error', titulo:'Error', mensaje:response.message});
            return false;
        }
    } catch (error) {
        mostrarAlerta({tipo:'error', titulo:'Error', mensaje:'Error al aceptar practicante'});
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

// ===================================== EVENT LISTENERS ====================================

function configurarEventListeners() {
    // Botón agregar turno
    const btnAgregarTurno = document.getElementById('btnAgregarTurno');
    if (btnAgregarTurno) {
        btnAgregarTurno.addEventListener('click', agregarTurno);
    }
    
    // Select de decisión
    const decisionSelect = document.getElementById('decisionAceptacion');
    if (decisionSelect) {
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
