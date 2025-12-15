
window.initDocumentos = function() {
    console.log("Documentos iniciado");
    if (window.documentosInicializado) {
        console.warn("initDocumentos ya fue ejecutado");
        return;
    }
    window.documentosInicializado = true;
    // ===================================== Documentos ====================================================
    const inicializar = async () => {
        console.log('Esto no se ejecuta');
        const selectPracticanteDoc = document.getElementById("selectPracticanteDoc");
        const selectPracticanteModal = document.getElementById("practicanteDocumento");
        const listaDocumentos = document.getElementById("listaDocumentos");
        const contenedorDocumentos = document.getElementById("contenedorDocumentos");
        const btnGuardar = document.getElementById("btnGuardarDocumentos");
        cargarAreasParaSolicitud();



        let solicitudIDActual = null;
        const tiposDocumento = ['cv', 'dni', 'carnet_vacunacion', 'carta_presentacion'];

        // 🔹 Cargar practicantes en ambos select
        try {
            const practicantes = await api.listarNombrePracticantes();

            console.log(practicantes);
            if (!practicantes || !Array.isArray(practicantes)) {
                console.warn("La respuesta de la API no es un array válido de practicantes.");
                return; 
            }

            practicantes.forEach(p => {
                const option1 = new Option(p.NombreCompleto, p.PracticanteID);
                const option2 = new Option(p.NombreCompleto, p.PracticanteID);
                selectPracticanteDoc.add(option1);
                selectPracticanteModal.add(option2);
            });

        } catch (err) {
            console.error("Error cargando practicantes:", err);
            mostrarAlerta({
                tipo: 'error',
                titulo: 'Error',
                mensaje: err.message || 'Error al cargar la lista de practicantes'
            });
        }

        // 🔹 Botón abrir modal
        document.getElementById("btnSubirDocumento").addEventListener("click", () => {
            openModal("modalSubirDocumento");
        });

        // 🔹 Cuando se selecciona practicante en el modal
        selectPracticanteModal.addEventListener("change", async (e) => {
            const practicanteID = e.target.value;
            
            if (!practicanteID) {
                contenedorDocumentos.style.display = "none";
                btnGuardar.style.display = "none";
                return;
            }

            // Obtener o crear solicitud
            try {
                const result = await api.getPracticante(practicanteID);
                
                if (result.success && result.data && result.data.SolicitudID) {
                    solicitudIDActual = result.data.SolicitudID;
                } else {
                    // Crear nueva solicitud
                    const response = await api.crearSolicitud(practicanteID);

                    if (!response.success) throw new Error("Error al crear solicitud");

                    solicitudIDActual = response.solicitudID;
                }

                document.getElementById("solicitudID").value = solicitudIDActual;
                window.solicitudActualID = solicitudIDActual;
                
                // Cargar documentos existentes
                await cargarDocumentosExistentes(practicanteID);
                
                contenedorDocumentos.classList.remove('hidden');
                btnGuardar.classList.remove('hidden');

            } catch (error) {
                mostrarAlerta({tipo:'error', titulo:'Error', mensaje: 'Error al procesar la solicitud del practicante'});
            }
        });

        // 🔹 Cargar documentos existentes en los previews
        async function cargarDocumentosExistentes(practicanteID) {
            for (const tipo of tiposDocumento) {
                try {
                    const result = await api.obtenerDocumentoPorTipoYPracticante(practicanteID, tipo);
                    const previewDiv = document.getElementById(`preview_${tipo}`);
                    
                    if (result.success && result.data) {
                        previewDiv.innerHTML = `
                            <div class="archivo-actual">
                                <span>
                                    <i class="fas fa-check-circle" style="color: green;"></i>
                                    Documento subido (${result.data.FechaSubida})
                                </span>
                                <div class="btn-group">
                                    <button type="button" class="btn-view" onclick="verDocumento('${result.data.Archivo}')">
                                        <i class="fas fa-eye"></i> Ver
                                    </button>
                                    <button type="button" class="btn-delete" onclick="eliminarDocumentoModal(${result.data.DocumentoID}, '${tipo}', ${practicanteID})">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </div>
                            </div>
                        `;
                    } else {
                        previewDiv.innerHTML = "";
                    }
                } catch (err) {
                    console.error(`Error al cargar documento ${tipo}:`, err);
                }
            }
        }

        // 🔹 Detectar cambios en archivos
        tiposDocumento.forEach(tipo => {
            const input = document.getElementById(`archivo_${tipo}`);
            const preview = document.getElementById(`preview_${tipo}`);
            
            if (input) {
                input.addEventListener("change", () => {
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
                });
            }
        });

        // 🔹 Enviar formulario de documentos
        document.getElementById("formSubirDocumentos").addEventListener("submit", async (e) => {
            e.preventDefault();

            const practicanteID = selectPracticanteModal.value;
            if (!practicanteID) {
                mostrarAlerta({tipo:'info', mensaje: 'Por favor selecciona un practicante'});
                return;
            }

            const btn = document.getElementById("btnGuardarDocumentos");
            
            try {
                await ejecutarUnaVez(btn, async () => {
                    let documentosSubidos = 0;
                    
                    // Subir cada documento que tenga archivo seleccionado
                    for (const tipo of tiposDocumento) {
                        const input = document.getElementById(`archivo_${tipo}`);
                        
                        if (input && input.files.length > 0) {
                            const formData = new FormData();
                            formData.append('solicitudID', solicitudIDActual);
                            formData.append('tipoDocumento', tipo);
                            formData.append('archivoDocumento', input.files[0]);
                            formData.append('practicanteID', practicanteID);

                            // Verificar si existe documento previo
                            const existente = await api.obtenerDocumentoPorTipoYPracticante(practicanteID, tipo);
                            
                            let response;
                            if (existente.success && existente.data) {
                                response = await api.actualizarDocumento(formData);
                            } else {
                                response = await api.subirDocumento(formData);
                            }

                            if (!response.ok) {
                                throw new Error(`Error al subir ${tipo}`);
                            }
                            
                            documentosSubidos++;
                        }
                    }
                    
                    if (documentosSubidos === 0) {
                        throw new Error("No se seleccionó ningún documento para subir");
                    }
                });

                mostrarAlerta({tipo:'success', titulo:'Guardado', mensaje: "Documentos guardados correctamente"});

                
                // Recargar previews y limpiar inputs
                await cargarDocumentosExistentes(practicanteID);
                
                tiposDocumento.forEach(tipo => {
                    const input = document.getElementById(`archivo_${tipo}`);
                    if (input) input.value = "";
                });


                // Actualizar lista si está seleccionado el mismo practicante
                if (selectPracticanteDoc.value === practicanteID) {
                    const documentos = await getDocumentosPorPracticante(practicanteID);
                    await renderDocumentos(documentos, solicitudIDActual);
                }
                closeModal("modalSubirDocumento");

            } catch (err) {
                mostrarAlerta({tipo:'error', titulo:'Error', mensaje: "Error al guardar los documentos: " + err.message});
            }
        });

        // 🔹 Cuando se selecciona practicante en la vista de lista
        selectPracticanteDoc.addEventListener("change", async () => {
            const id = selectPracticanteDoc.value;
            if (!id) {
                listaDocumentos.innerHTML = "<p>Seleccione un practicante...</p>";
                solicitudIDActual = null;
                return;
            }

            try {
                const result = await api.getPracticante(id);
                
                if (result.success && result.data && result.data.SolicitudID) {
                    solicitudIDActual = result.data.SolicitudID;
                } else {
                    solicitudIDActual = null;
                }
            } catch (error) {
                console.error("Error al obtener solicitud:", error);
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error',
                    mensaje: error.message || 'Error al obtener la información del practicante'
                });
                solicitudIDActual = null;
            }

            const documentos = await getDocumentosPorPracticante(id);
            await renderDocumentos(documentos, solicitudIDActual);
        });


        document.getElementById('btnGenerarCarta').addEventListener('click', () => generarCartaAceptacion(solicitudIDActual));
    };

    // Verificar si el DOM ya está cargado o esperar al evento
    if (document.readyState === 'loading') {
        // DOM aún no está listo, esperar al evento
        document.addEventListener('DOMContentLoaded', inicializar);
    } else {
        // DOM ya está listo, ejecutar inmediatamente
        inicializar();
    }

    console.log('algo');

    // 🔹 Ver documento
    window.verDocumento = function(base64) {
        const tipoMime = base64.startsWith("JVBER") ? "application/pdf"
                        : base64.startsWith("/9j/") ? "image/jpeg"
                        : base64.startsWith("iVBOR") ? "image/png"
                        : "application/octet-stream";
        
        const blob = b64toBlob(base64, tipoMime);
        const url = URL.createObjectURL(blob);
        window.open(url, "_blank");
    };

    // 🔹 Eliminar documento desde el modal
    window.eliminarDocumentoModal = async function(documentoID, tipo, practicanteID) {
        if (!(await mostrarAlerta({
            tipo: 'warning',
            titulo: '¿Estás seguro?',
            mensaje: "¿Seguro que deseas eliminar este documento?",
            showCancelButton: true
        })).isConfirmed) {
            return; // CANCELADO → salir sin continuar
        }
        
        try {
            const response = await api.eliminarDocumento(documentoID);
            
            if (response.success) {
                mostrarAlerta({tipo:'success', mensaje: "Documento eliminado correctamente "});
                
                // Recargar preview en el modal
                const previewDiv = document.getElementById(`preview_${tipo}`);
                if (previewDiv) previewDiv.innerHTML = "";
                
                // Si está en la lista, recargar
                if (document.getElementById("selectPracticanteDoc").value == practicanteID) {
                    const documentos = await getDocumentosPorPracticante(practicanteID);
                    await renderDocumentos(documentos, window.solicitudActualID);
                }
            } else {
                mostrarAlerta({tipo:'error', titulo:'Error', mensaje: response.message});
            }
        } catch (error) {
            mostrarAlerta({tipo:'error', titulo:'Error', mensaje: error.message || "Error al eliminar documento"});
        }
    };

    // 🔹 Eliminar documento desde la tabla
    window.eliminarDocumento = async function(documentoID, tipo) {
        if (!(await mostrarAlerta({
            tipo: 'warning',
            titulo: '¿Estás seguro?',
            mensaje: "¿Seguro que deseas eliminar este documenti?",
            showCancelButton: true
        })).isConfirmed) {
            return; // CANCELADO → salir sin continuar
        }
        
        try {
            const response = await api.eliminarDocumento(documentoID);
            
            if (response.success) {
                mostrarAlerta({tipo:'success', mensaje: "Documento eliminado correctamente "});
                
                // Recargar la lista
                const practicanteID = document.getElementById("selectPracticanteDoc").value;
                if (practicanteID) {
                    const documentos = await getDocumentosPorPracticante(practicanteID);
                    await renderDocumentos(documentos, window.solicitudActualID);
                }
            } else {
                mostrarAlerta({tipo:'error', titulo:'Error al eliminar', mensaje: response.message});
            }
        } catch (error) {
            mostrarAlerta({tipo:'error', titulo:'Error al eliminar documento', mensaje: error.message || 'Error desconocido'});
        }
    };

    // Utilidades
    function b64toBlob(base64, type) {
        const byteCharacters = atob(base64);
        const byteNumbers = Array.from(byteCharacters, c => c.charCodeAt(0));
        const byteArray = new Uint8Array(byteNumbers);
        return new Blob([byteArray], { type });
    }

    async function cargarAreasParaSolicitud() {
        try {
            const response = await api.listarAreas();
            
            if (response.success) {
                const selectArea = document.getElementById("areaDestino");
                if (selectArea) {
                    selectArea.innerHTML = '<option value="">Seleccionar área...</option>';
                    response.data.forEach(area => {
                        const option = document.createElement('option');
                        option.value = area.AreaID;
                        option.textContent = area.NombreArea;
                        selectArea.appendChild(option);
                    });
                }
            }
        } catch (error) {
            console.error("Error al cargar áreas:", error);
            mostrarAlerta({
                tipo: 'error',
                titulo: 'Error',
                mensaje: error.message || 'Error al cargar las áreas'
            });
        }
    }

    function abrirModalEnviarSolicitud(solicitudID) {
        document.getElementById("solicitudEnvioID").value = solicitudID;
        openModal("modalEnviarSolicitud");
    }

    function cerrarModalEnviarSolicitud() {
        closeModal("modalEnviarSolicitud");
        document.getElementById("formEnviarSolicitud").reset();
    }

    document.getElementById("formEnviarSolicitud")?.addEventListener("submit", async (e) => {
        e.preventDefault();

        const btn = document.getElementById("btnEnviarSolicitud");
        const solicitudID = document.getElementById("solicitudEnvioID").value;
        const destinatarioAreaID = document.getElementById("areaDestino").value;
        const contenido = document.getElementById("mensajeSolicitud").value;
        const remitenteAreaID = sessionStorage.getItem('areaID') || 1;

        try {
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

            mostrarAlerta({tipo:'success', titulo:'Solicitud Enviada', mensaje: "Solicitud enviada correctamente al área"});
            cerrarModalEnviarSolicitud();

        } catch (error) {
            console.error('❌ Error al enviar solicitud:', error);
            mostrarAlerta({tipo:'error', titulo:'Error al enviar solicitud', mensaje: error.message});
        }
    });

    function openModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.remove('hidden');
            modal.style.display = "flex";
            
            // Resetear el modal de documentos cuando se abre
            if (id === "modalSubirDocumento") {
                document.getElementById("practicanteDocumento").value = "";
                document.getElementById("contenedorDocumentos").classList.add("hidden");
                document.getElementById("btnGuardarDocumentos").classList.add("hidden");
                
                // Limpiar previews
                const tiposDocumento = ['cv', 'dni', 'carnet_vacunacion', 'carta_presentacion'];
                tiposDocumento.forEach(tipo => {
                    const input = document.getElementById(`archivo_${tipo}`);
                    const preview = document.getElementById(`preview_${tipo}`);
                    if (input) input.value = "";
                    if (preview) preview.innerHTML = "";
                });
                
            }
        }
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.style.display = "none";
            modal.classList.add('hidden');
        }
    }

    async function getDocumentosPorPracticante(practicanteID) {
        try {
            const data = await api.obtenerDocumentosPorPracticante(practicanteID);
            
            if (!data || !Array.isArray(data)) {
                return [];
            }

            return data;
        } catch (e) {
            console.error("Error obteniendo documentos:", e);
            return [];
        }
    }

    function descargarArchivo(base64, nombre) {
        let tipoMime = "application/octet-stream";
        let extension = "bin";

        if (base64.startsWith("JVBER")) {
            tipoMime = "application/pdf";
            extension = "pdf";
        } else if (base64.startsWith("UEsDB")) {
            tipoMime = "application/vnd.openxmlformats-officedocument.wordprocessingml.document";
            extension = "docx";
        } else if (base64.startsWith("/9j/")) {
            tipoMime = "image/jpeg";
            extension = "jpg";
        } else if (base64.startsWith("iVBOR")) {
            tipoMime = "image/png";
            extension = "png";
        }

        const link = document.createElement("a");
        link.href = `data:${tipoMime};base64,${base64}`;
        link.download = `${nombre}.${extension}`;
        link.click();
    }

    async function renderDocumentos(documentos, solicitudID, forzarEnviada = false) {
        const contenedor = document.getElementById("listaDocumentos");

        if (!documentos || !Array.isArray(documentos) || documentos.length === 0) {
            contenedor.innerHTML = "<p>No hay documentos registrados.</p>";
            return;
        }

        const obligatorios = ["CV", "DNI", "Carnet_Vacunacion"];
        const normalizar = str =>
            str.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");

        const tiposSubidos = documentos.map(doc => normalizar(doc.tipo));
        const faltantes = obligatorios.filter(req =>
            !tiposSubidos.includes(normalizar(req))
        );
        const todosCompletos = faltantes.length === 0;

        let solicitudEnviada = forzarEnviada;
        let solicitudAprobada = false;
        
        if (solicitudID) {
            try {
                const estadoResponse = await api.verificarEstadoSolicitud(solicitudID);
                if (estadoResponse.success && estadoResponse.data) {
                    solicitudEnviada = estadoResponse.data.enviada === true || forzarEnviada;
                    solicitudAprobada = estadoResponse.data.aprobada;
                }
            } catch (error) {
                console.warn("No se pudo verificar estado de solicitud:", error);
            }
        }

        const tabla = `
        <div class="table-container">
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
                        const obligatorio = obligatorios.some(req =>
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
                                            onclick="verDocumento('${doc.archivo}')">
                                        <i class="fas fa-eye"></i> Ver
                                    </button>
                                    <button class="btn-delete" onclick="eliminarDocumento(${doc.documentoID}, '${normalizar(doc.tipo)}')">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </td>
                            </tr>
                        `;
                    }).join("")}
                </tbody>
            </table>

            <div class="enviar-solicitud-container" style="margin-top: 20px; text-align: center;">
                <button id="btnEnviarSolicitudArea" 
                        class="btn-info" 
                        ${todosCompletos && !solicitudEnviada ? '' : 'disabled'}
                        onclick="abrirModalEnviarSolicitud(${solicitudID})">
                    <i class="fas fa-${solicitudEnviada ? 'check' : 'paper-plane'}"></i> 
                    ${solicitudEnviada ? 'Solicitud Enviada' : 'Enviar Solicitud a Área'}
                </button>
                <button id="btnGenerarCarta" 
                        class="btn-success" 
                        ${solicitudAprobada ? '' : 'disabled'}
                        style="margin-left: 10px;"
                        onclick="abrirDialogCarta()">
                    <i class="fas fa-file-contract"></i> Generar Carta de Aceptación
                </button>
                ${
                    !todosCompletos
                    ? `<p class='msg-warn'>Faltan documentos obligatorios: ${faltantes.join(", ")}</p>`
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

    function abrirDialogCarta() {
        document.getElementById('numeroExpedienteCarta').value = '';
        document.getElementById('formatoDocumentoCarta').value = 'word';
        document.getElementById('mensajeEstadoCarta').classList.remove('visible');
        document.getElementById('dialogCarta').classList.add('active');
    }

    // Cerrar dialog
    function cerrarDialogCarta() {
        document.getElementById('dialogCarta').classList.remove('active');
        const btnGenerar = document.getElementById('btnGenerarCarta');
        const mensajeEstado = document.getElementById('mensajeEstadoCarta');
        mensajeEstado.style.display = 'none';
        btnGenerar.disabled = false;
        btnGenerar.innerHTML = '<i class="fas fa-file-contract"></i> Generar Carta de Aceptación';
        btnGenerar.style.background = '#4CAF50';
    }

    // Función mejorada para generar carta de aceptación
    async function generarCartaAceptacion(solicitudID) {
        
        const inputExpediente = document.getElementById('numeroExpedienteCarta');
        const selectFormato = document.getElementById('formatoDocumentoCarta');
        const btnGenerar = document.getElementById('btnGenerarCarta');
        const mensajeEstado = document.getElementById('mensajeEstadoCarta');

        // Enfocar el input
        inputExpediente.focus();

        // Función para mostrar mensajes
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
        const formato = selectFormato.value;

        // Validación
        if (!numeroExpediente) {
            mostrarMensaje('Por favor, ingrese el número de expediente', 'error');
            inputExpediente.focus();
            return;
        }

        // Validar formato del expediente (opcional)
        const regexExpediente = /^\d{6}-\d{4}-\d{1,2}$/;
        if (!regexExpediente.test(numeroExpediente)) {
            mostrarMensaje('Formato de expediente inválido. Use: XXXXX-YYYY-X', 'error');
            inputExpediente.focus();
            return;
        }

        try {
            // Deshabilitar botón y mostrar estado de carga
            btnGenerar.disabled = true;
            btnGenerar.textContent = 'Generando...';
            btnGenerar.style.background = '#999';
            mostrarMensaje('Generando carta de aceptación...', 'info');

            console.log(solicitudID, numeroExpediente, formato);

            // Llamar a la API
            const resultado = await api.generarCartaAceptacion(
                solicitudID,
                numeroExpediente,
                formato
            );

            if (resultado.success) {
                mostrarMensaje('Carta generada exitosamente', 'exito');
                
                // Descargar el archivo automáticamente
                const link = document.createElement('a');
                link.href = resultado.archivo.url;
                link.download = resultado.archivo.nombre;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                // Cerrar el diálogo después de 2 segundos
                setTimeout(() => {
                    cerrarDialogCarta();
                    
                    // Mostrar notificación de éxito (opcional)
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
            
            // Intentar obtener más detalles del error
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
        

        // Permitir generar con Enter
        inputExpediente.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                btnGenerar.click();
            }
        });
    }
    function mostrarAlerta({
        tipo = "info",
        titulo = "",
        mensaje = "",
        showConfirmButton = true,
        showCancelButton = false,
        confirmText = "Aceptar",
        cancelText = "Cancelar",
        input = null,
        inputPlaceholder = "",
        inputValue = "",
        callback = null
    }) {
        
        // IMPORTANTE: devolver la promesa
        return Swal.fire({
            icon: tipo,
            title: titulo,
            text: mensaje,
            position: "center",
            showConfirmButton,
            showCancelButton,
            confirmButtonText: confirmText,
            cancelButtonText: cancelText,
            input,
            inputPlaceholder,
            inputValue,
            backdrop: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
        }).then((result) => {
            if (callback) callback(result);
            return result; // También devolver el resultado
        });
    }

    window.closeModal = closeModal;
    window.abrirModalEnviarSolicitud = abrirModalEnviarSolicitud;
    window.cerrarModalEnviarSolicitud = cerrarModalEnviarSolicitud;
    window.abrirDialogCarta = abrirDialogCarta;
    window.cerrarDialogCarta = cerrarDialogCarta;
};


