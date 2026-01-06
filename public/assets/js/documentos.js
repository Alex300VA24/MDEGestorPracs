window.recargarDocumentos = function() {
    console.log("Recargando módulo de documentos...");
    window.documentosInicializado = false;
    window.initDocumentos();
    
};

window.initDocumentos = function() {
    console.log("Documentos iniciado");
    let enviandoSolicitud = false;
    if (window.documentosInicializado) {
        console.warn("initDocumentos ya fue ejecutado");
        return;
    }
    window.documentosInicializado = true;
    // ===================================== Documentos ================================================
    const inicializar = async () => {
        console.log('Inicializando módulo de documentos...');
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

            if (!practicantes || !Array.isArray(practicantes.data)) {
                console.warn("La respuesta de la API no es un array válido de practicantes.");
                return; 
            }

            selectPracticanteDoc.innerHTML = '<option value="">Seleccionar practicante...</option>';
            selectPracticanteModal.innerHTML = '<option value="">Seleccionar practicante...</option>';

            practicantes.data.forEach(p => {
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

        // 🔹 ========== CAMBIO PRINCIPAL: Evento de selección de practicante en modal ==========
        selectPracticanteModal.addEventListener("change", async (e) => {
    const practicanteID = e.target.value;
    
    if (!practicanteID) {
        contenedorDocumentos.style.display = "none";
        btnGuardar.style.display = "none";
        return;
    }

    try {
        // 1️⃣ Intentar obtener solicitud activa
        console.log('🔍 Buscando solicitud activa para practicante:', practicanteID);
        const solicitudActivaResult = await api.obtenerSolicitudActiva(practicanteID);
        
        if (solicitudActivaResult.success && solicitudActivaResult.data) {
            // ✅ Ya tiene solicitud activa
            solicitudIDActual = solicitudActivaResult.data.SolicitudID;
            
            console.log('✅ Solicitud activa encontrada:', solicitudIDActual);
            
            mostrarAlerta({
                tipo: 'info',
                titulo: 'Solicitud Existente',
                mensaje: `Continuará trabajando con la solicitud #${solicitudIDActual} (Estado: ${solicitudActivaResult.data.EstadoDesc})`
            });
        } else {
            // 2️⃣ No tiene solicitud activa, verificar si tiene solicitudes anteriores
            console.log('⚠️ No hay solicitud activa. Verificando historial...');
            
            const historialResponse = await api.obtenerHistorialSolicitudes(practicanteID);
            const tieneHistorial = historialResponse.success && 
                                  historialResponse.data && 
                                  historialResponse.data.length > 0;
            
            let mensaje = 'Este practicante no tiene una solicitud activa. ¿Desea crear una nueva solicitud?';
            let textoConfirm = 'Sí, crear nueva';
            
            // 🔑 Si tiene historial, ofrecer migrar documentos
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
                html: mensaje, // 🔑 Usar html en lugar de mensaje
                showCancelButton: true,
                confirmText: textoConfirm,
                cancelText: 'Cancelar',
                width: '600px'
            });
            
            if (!confirmacion.isConfirmed) {
                // ❌ Usuario canceló
                selectPracticanteModal.value = "";
                contenedorDocumentos.classList.add('hidden');
                btnGuardar.classList.add('hidden');
                return;
            }
            
            // 3️⃣ Crear nueva solicitud (con migración si tiene historial)
            console.log('📝 Creando nueva solicitud con migración:', tieneHistorial);
            
            mostrarAlerta({
                tipo: 'info',
                titulo: 'Procesando...',
                html: tieneHistorial 
                    ? 'Creando solicitud y transfiriendo documentos...' 
                    : 'Creando nueva solicitud...',
                showConfirmButton: false,
                allowOutsideClick: false
            });
            
            console.log();
            const response = await api.crearNuevaSolicitud(
                practicanteID, 
                tieneHistorial // migrar solo si tiene historial
            );
            
            if (!response.success) {
                throw new Error(response.mensaje || response.message || "Error al crear solicitud");
            }
            
            solicitudIDActual = response.data.solicitudID;
            
            console.log('✅ Nueva solicitud creada:', solicitudIDActual);
            
            // 🔑 Mensaje personalizado según si hubo migración
            let mensajeExito = `Nueva solicitud #${solicitudIDActual} creada exitosamente`;
            
            if (response.data.documentosMigrados && response.data.documentosMigrados > 0) {
                mensajeExito += `\n\n✅ Se transfirieron ${response.data.documentosMigrados} documento(s) de la solicitud anterior.`;
            }
            
            mostrarAlerta({
                tipo: 'success',
                titulo: 'Solicitud Creada',
                mensaje: mensajeExito
            });
        }

        // 4️⃣ Configurar solicitud actual
        document.getElementById("solicitudID").value = solicitudIDActual;
        window.solicitudActualID = solicitudIDActual;
        
        // 5️⃣ Cargar documentos existentes (ahora incluye los migrados)
        await cargarDocumentosExistentes(practicanteID);
        
        // 6️⃣ Mostrar controles
        contenedorDocumentos.classList.remove('hidden');
        btnGuardar.classList.remove('hidden');

    } catch (error) {
        console.error('❌ Error procesando solicitud:', error);
        mostrarAlerta({
            tipo: 'error', 
            titulo: 'Error', 
            mensaje: error.message || 'Error al procesar la solicitud del practicante'
        });
        // Limpiar selección en caso de error
        selectPracticanteModal.value = "";
        contenedorDocumentos.classList.add('hidden');
        btnGuardar.classList.add('hidden');
    }
});

        // 🔹 Cargar documentos existentes en los previews
        async function cargarDocumentosExistentes(practicanteID) {
    console.log('🔍 Cargando documentos para solicitud:', solicitudIDActual);
    
    if (!solicitudIDActual) {
        console.warn('⚠️ No hay solicitudID activa');
        // Limpiar todos los previews
        tiposDocumento.forEach(tipo => {
            const previewDiv = document.getElementById(`preview_${tipo}`);
            if (previewDiv) previewDiv.innerHTML = "";
        });
        return;
    }
    
    for (const tipo of tiposDocumento) {
        try {
            // 🔑 CAMBIO: Buscar por solicitudID + tipo
            const result = await api.obtenerDocumentoPorTipoYSolicitud(
                solicitudIDActual,  // ← Usar solicitud actual
                tipo
            );
            
            const previewDiv = document.getElementById(`preview_${tipo}`);
            
            if (result.success && result.data) {
                console.log(`✅ Documento ${tipo} encontrado para solicitud #${solicitudIDActual}`);
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
                console.log(`⚠️ No hay documento ${tipo} para solicitud #${solicitudIDActual}`);
                previewDiv.innerHTML = "";
            }
        } catch (err) {
            console.error(`❌ Error al cargar documento ${tipo}:`, err);
            const previewDiv = document.getElementById(`preview_${tipo}`);
            if (previewDiv) previewDiv.innerHTML = "";
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
                    
                    for (const tipo of tiposDocumento) {
                        const input = document.getElementById(`archivo_${tipo}`);
                        
                        if (input && input.files.length > 0) {
                            const formData = new FormData();
                            formData.append('solicitudID', solicitudIDActual);
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
                                    // El body no era JSON
                                    const text = await response.text();
                                    if (text) errorMsg = text;
                                }

                                throw new Error(errorMsg);
                            }

                            
                            documentosSubidos++;
                        }
                    }
                    
                    if (documentosSubidos === 0) {
                        throw new Error("No se seleccionó ningún documento para subir");
                    }
                });

                mostrarAlerta({tipo:'success', titulo:'Guardado', mensaje: "Documentos guardados correctamente"});

                await cargarDocumentosExistentes(practicanteID);
                
                tiposDocumento.forEach(tipo => {
                    const input = document.getElementById(`archivo_${tipo}`);
                    if (input) input.value = "";
                });

                if (selectPracticanteDoc.value === practicanteID) {
                    const documentos = await getDocumentosPorPracticante(practicanteID);
                    await renderDocumentos(documentos.data, solicitudIDActual);
                }
                closeModal("modalSubirDocumento");

            } catch (err) {
                mostrarAlerta({tipo:'error', titulo:'Error', mensaje: err.message});
            }
        });

        // 🔹 ========== CAMBIO: Evento de selección de practicante en lista ==========
        selectPracticanteDoc.addEventListener("change", async () => {
            const id = selectPracticanteDoc.value;
            console.log('Este es el id del practicante', id);
            if (!id) {
                listaDocumentos.innerHTML = "<p>Seleccione un practicante...</p>";
                solicitudIDActual = null;
                return;
            }

            try {
                // Obtener solicitud activa para mostrar documentos
                const result = await api.obtenerSolicitudActiva(id);
                
                if (result.success && result.data) {
                    solicitudIDActual = result.data.SolicitudID;
                    console.log('📋 Mostrando documentos de solicitud activa:', solicitudIDActual);
                } else {
                    solicitudIDActual = null;
                    console.log('⚠️ No hay solicitud activa para mostrar documentos');
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
            console.log('este es documentos: ', documentos, solicitudIDActual);
            await renderDocumentos(documentos.data, solicitudIDActual);
        });

        document.getElementById('btnGenerarCarta').addEventListener('click', () => generarCartaAceptacion(solicitudIDActual));
    };

    // 🔹 ========== NUEVA FUNCIÓN: Mostrar historial de solicitudes ==========
    window.mostrarHistorialSolicitudes = async function(practicanteID) {
        
        try {

            console.log('🚨 SE EJECUTA mostrarHistorialSolicitudes', practicanteID);
            const response = await api.obtenerHistorialSolicitudes(practicanteID);
            console.log('este es reponse de historial', response);
            
            if (response.success && response.data && response.data.length > 0) {
                const getBadgeColor = (estadoAbrev) => {
                    const colores = {
                        'PEN': 'secondary',
                        'ENV': 'info',
                        'REV': 'warning',
                        'APR': 'success',
                        'FIN': 'success',
                        'NEG': 'danger'
                    };
                    return colores[estadoAbrev] || 'secondary';
                };

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
                console.log('Este es el response.data:', response.data);
                console.log(response.data.map(sol => sol.SolicitudID));

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
            
            console.log(html);    
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
    };

    // Verificar si el DOM ya está cargado o esperar al evento
    if (document.readyState === 'loading') {
        // DOM aún no está listo, esperar al evento
        document.addEventListener('DOMContentLoaded', inicializar);
    } else {
        // DOM ya está listo, ejecutar inmediatamente
        inicializar();
    }

    // 🔹 Ver documento
    window.verDocumento = function(base64) {
        console.log('Visualizando documento...');
        console.log('Base64 recibido:', base64.substring(0, 30) + '...');
        const tipoMime = base64.startsWith("JVBER") ? "application/pdf"
                        : base64.startsWith("/9j/") ? "image/jpeg"
                        : base64.startsWith("iVBOR") ? "image/png"
                        : "application/octet-stream";
        
        const blob = b64toBlob(base64, tipoMime);
        console.log('Blob creado:', blob);
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
                    await renderDocumentos(documentos.data, window.solicitudActualID);
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
            mensaje: "¿Seguro que deseas eliminar este documento?",
            showCancelButton: true
        })).isConfirmed) {
            return; // CANCELADO → salir sin continuar
        }
        
        try {
            console.log('Eliminando documento ID:', documentoID);
            const response = await api.eliminarDocumento(documentoID);
            
            if (response.success) {
                mostrarAlerta({tipo:'success', mensaje: "Documento eliminado correctamente "});
                
                // Recargar la lista
                const practicanteID = document.getElementById("selectPracticanteDoc").value;
                if (practicanteID) {
                    const documentos = await getDocumentosPorPracticante(practicanteID);
                    await renderDocumentos(documentos.data, window.solicitudActualID);
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

        // 🔹 Verificar si ya se está enviando
        if (enviandoSolicitud) {
            console.warn("⚠️ Ya hay una solicitud en proceso");
            return;
        }

        const btn = document.getElementById("btnEnviarSolicitud");
        const solicitudID = document.getElementById("solicitudEnvioID").value;
        const destinatarioAreaID = document.getElementById("areaDestino").value;
        const contenido = document.getElementById("mensajeSolicitud").value;
        const remitenteAreaID = sessionStorage.getItem('areaID') || 1;

        try {
            enviandoSolicitud = true; // 🔹 Activar bandera
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
            
            // 🔹 OPCIÓN 2: Recargar la vista con el estado actualizado
            const practicanteID = document.getElementById("selectPracticanteDoc").value;
            if (practicanteID) {
                const documentos = await getDocumentosPorPracticante(practicanteID);
                await renderDocumentos(documentos.data, solicitudID, true); // true = forzar como enviada
            }
            
            if (window.recargarPracticantes) {
                window.recargarPracticantes();
            }
        } catch(error) {
            console.error('❌ Error al enviar solicitud:', error);
            mostrarAlerta({tipo:'error', titulo:'Error al enviar solicitud', mensaje: error.message});
        }
    });

    // 🔹 Actualizar lista de practicantes dinámicamente
    window.actualizarListaPracticantes = async function() {
        try {
            const practicantes = await api.listarNombrePracticantes();

            if (!practicantes || !Array.isArray(practicantes)) {
                console.warn("La respuesta de la API no es un array válido de practicantes.");
                return; 
            }

            // Limpiar opciones existentes
            selectPracticanteDoc.innerHTML = "";
            selectPracticanteModal.innerHTML = "";

            // Agregar nuevas opciones
            practicantes.forEach(p => {
                const option1 = new Option(p.NombreCompleto, p.PracticanteID);
                const option2 = new Option(p.NombreCompleto, p.PracticanteID);
                selectPracticanteDoc.add(option1);
                selectPracticanteModal.add(option2);
            });

        } catch (err) {
            console.error("Error actualizando practicantes:", err);
            mostrarAlerta({
                tipo: 'error',
                titulo: 'Error',
                mensaje: err.message || 'Error al actualizar la lista de practicantes'
            });
        }
    };

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

    async function getDocumentosPorPracticante(practicanteID, solicitudID = null) {
        try {
            console.log('🔍 Obteniendo documentos:', { practicanteID, solicitudID });
            
            // La API ya filtra por solicitud activa en obtenerDocumentosPorPracticante
            const data = await api.obtenerDocumentosPorPracticante(practicanteID);
            console.log('📋 Documentos obtenidos:', data);
            
            if (!data || !Array.isArray(data.data.data)) {
                return { data: [] };
            }

            return data.data;
        } catch (e) {
            console.error("❌ Error obteniendo documentos:", e);
            return { data: [] };
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
        console.log('🚨 SE EJECUTA renderDocumentos', { documentos, solicitudID, forzarEnviada });
        // 🔑 FILTRAR documentos por solicitud actual
        if (documentos && Array.isArray(documentos) && solicitudID) {
            const totalOriginal = documentos.length;
            
            documentos = documentos.filter(doc => {
                const perteneceASolicitud = doc.solicitudID === solicitudID || 
                                        doc.SolicitudID === solicitudID;
                return perteneceASolicitud;
            });
            
            console.log(`📋 Documentos filtrados: ${documentos.length} de ${totalOriginal} (solicitud #${solicitudID})`);
        }

        if (!documentos || !Array.isArray(documentos) || documentos.length === 0) {
            const practicanteID = document.getElementById('selectPracticanteDoc')?.value;
            contenedor.innerHTML = `
                <div style="text-align: center; padding: 20px;">
                    <p style="color: #666;">No hay documentos registrados para esta solicitud.</p>
                    ${practicanteID ? `
                        <button class="btn-info" onclick="mostrarHistorialSolicitudes(${practicanteID})" style="margin-top: 10px;">
                            <i class="fas fa-history"></i> Ver Historial de Solicitudes
                        </button>
                    ` : ''}
                </div>
            `;
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
                console.warn("No se pudo verificar estado de solicitud:", error);
            }
        }

        const practicanteID = document.getElementById('selectPracticanteDoc')?.value;
        
        const tabla = `
        <div class="table-container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h4 style="margin: 0;">Documentos de Solicitud #${solicitudID || 'N/A'}</h4>
                ${practicanteID ? `
                    <button class="btn-info" onclick="mostrarHistorialSolicitudes(${practicanteID})" style="padding: 8px 15px;">
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
                        ${todosCompletos && (!solicitudEnviada || solicitudRechazada) ? '' : 'disabled'}
                        onclick="abrirModalEnviarSolicitud(${solicitudID})">
                    <i class="fas fa-${solicitudEnviada && !solicitudRechazada ? 'check' : 'paper-plane'}"></i> 
                    ${solicitudEnviada && !solicitudRechazada ? 'Solicitud Enviada' : 'Enviar Solicitud a Área'}
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
        const inputDirector = document.getElementById('nombreDirectorCarta');
        const inputCargo = document.getElementById('cargoDirectorCarta');
        const selectFormato = document.getElementById('formatoDocumentoCarta');
        const btnGenerar = document.getElementById('btnGenerarCarta');
        const mensajeEstado = document.getElementById('mensajeEstadoCarta');

        // Enfocar el primer input
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
        const nombreDirector = inputDirector.value.trim().toUpperCase();
        const cargoDirector = inputCargo.value.trim().toUpperCase();
        const formato = selectFormato.value;

        // Validaciones
        if (!numeroExpediente) {
            mostrarMensaje('Por favor, ingrese el número de expediente', 'error');
            inputExpediente.focus();
            return;
        }

        // Regex que acepta 5 o 6 dígitos al inicio
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
            // Deshabilitar botón y mostrar estado de carga
            btnGenerar.disabled = true;
            btnGenerar.textContent = 'Generando...';
            btnGenerar.style.background = '#999';
            mostrarMensaje('Generando carta de aceptación...', 'info');

            console.log({
                solicitudID,
                numeroExpediente,
                nombreDirector,
                cargoDirector,
                formato
            });

            // Llamar a la API con los nuevos parámetros
            const resultado = await api.generarCartaAceptacion(
                solicitudID,
                numeroExpediente,
                formato,
                nombreDirector,
                cargoDirector
            );

            console.log("Este es el resultado: ", resultado);

            if (resultado.success) {
                mostrarMensaje('Carta generada exitosamente', 'exito');
                
                console.log("Este es el archivo: ", resultado.archivo);
                console.log('Esta es la url: ', resultado.archivo.url);
                // 🔹 Validar URL antes de descargar
                if (!resultado.archivo || !resultado.archivo.url) {
                    throw new Error('El archivo de la carta no está disponible en el servidor.');
                }

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
        const handleEnter = (e) => {
            if (e.key === 'Enter') {
                btnGenerar.click();
            }
        };
        
        inputExpediente.addEventListener('keypress', handleEnter);
        inputDirector.addEventListener('keypress', handleEnter);
        inputCargo.addEventListener('keypress', handleEnter);
    }

    window.closeModal = closeModal;
    window.abrirModalEnviarSolicitud = abrirModalEnviarSolicitud;
    window.cerrarModalEnviarSolicitud = cerrarModalEnviarSolicitud;
    window.abrirDialogCarta = abrirDialogCarta;
    window.cerrarDialogCarta = cerrarDialogCarta;
};


