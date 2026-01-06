window.initCertificados = function() {
    console.log("Certificados iniciado");
    const inicializar = async () => {
        let practicanteSeleccionado = null;
        console.log('entro a inicia');
        // Cargar estadísticas y practicantes
        await cargarEstadisticas();
        await cargarPracticantes();

        // Event Listeners
        document.getElementById('selectPracticante').addEventListener('change', handleSeleccionPracticante);
        document.getElementById('btnAbrirDialog').addEventListener('click', abrirDialogCertificado);
        document.getElementById('btnCancelarCertificado').addEventListener('click', cerrarDialogCertificado);
        document.getElementById('btnGenerarCertificado').addEventListener('click', generarCertificado);

        // Cargar estadísticas
        async function cargarEstadisticas() {
            try {
                const data = await api.obtenerEstadisticasCertificados();
                document.getElementById('totalVigentes').textContent = data.data.totalVigentes || 0;
                document.getElementById('totalFinalizados').textContent = data.data.totalFinalizados || 0;
            } catch (error) {
                console.error('Error al cargar estadísticas:', error);
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error',
                    mensaje: 'No se pudieron cargar las estadísticas de certificados'
                });
            }
        }

        // Cargar lista de practicantes
        async function cargarPracticantes() {
            try {
                const data = await api.listarPracticantesParaCertificado();
                const select = document.getElementById('selectPracticante');
                
                select.innerHTML = '<option value="">-- Seleccione un practicante --</option>';
                
                data.data.forEach(p => {
                    const option = document.createElement('option');
                    option.value = p.PracticanteID;
                    option.textContent = `${p.NombreCompleto} (${p.Estado})`;
                    option.dataset.practicante = JSON.stringify(p);
                    select.appendChild(option);
                });
            } catch (error) {
                console.error('Error al cargar practicantes:', error);
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error',
                    mensaje: error.message || 'Error al cargar la lista de practicantes'
                });
            }
        }

        // Manejar selección de practicante
        async function handleSeleccionPracticante(e) {
            const practicanteID = e.target.value;
            
            if (!practicanteID) {
                ocultarInformacion();
                return;
            }

            try {
                const option = e.target.options[e.target.selectedIndex];
                practicanteSeleccionado = JSON.parse(option.dataset.practicante);
                
                // Obtener información completa
                const data = await api.obtenerInformacionCertificado(practicanteID);
                mostrarInformacion(data.data);
            } catch (error) {
                console.error('Error al cargar información:', error);
                mostrarAlerta({tipo:'error', titulo: 'Error', 
                            mensaje: 'Error al cargar la información del practicante' });
            }
        }

        // Mostrar información del practicante
        function mostrarInformacion(data) {
            document.getElementById('infoSection').classList.add('visible');
            document.getElementById('emptyState').style.display = 'none';
            
            document.getElementById('nombreCompleto').textContent = data.NombreCompleto;
            document.getElementById('dni').textContent = data.DNI;
            document.getElementById('carrera').textContent = data.Carrera;
            document.getElementById('universidad').textContent = data.Universidad;
            document.getElementById('area').textContent = data.Area || 'Sin área asignada';
            document.getElementById('fechaInicio').textContent = formatearFecha(data.FechaEntrada);
            document.getElementById('fechaTermino').textContent = data.FechaSalida ? formatearFecha(data.FechaSalida) : 'Vigente';
            document.getElementById('totalHoras').textContent = data.TotalHoras + ' horas';
            document.getElementById('estado').textContent = data.Estado;

            const badge = document.getElementById('estadoBadge');
            badge.textContent = data.Estado;
            badge.className = 'badge ' + (data.Estado === 'Vigente' ? 'vigente' : 'finalizado');

            // Habilitar/deshabilitar botón según estado
            const btnGenerar = document.getElementById('btnAbrirDialog');
            if (data.EstadoAbrev === 'VIG' || data.EstadoAbrev === 'FIN') {
                btnGenerar.disabled = false;
                btnGenerar.title = 'Generar certificado y finalizar practicante';
            } else {
                btnGenerar.disabled = true;
                btnGenerar.title = 'El practicante ya finalizó sus prácticas';
            }
        }

        // Ocultar información
        function ocultarInformacion() {
            document.getElementById('infoSection').classList.remove('visible');
            practicanteSeleccionado = null;
        }

        // Abrir dialog
        function abrirDialogCertificado() {
            if (!practicanteSeleccionado) return;
            
            document.getElementById('numeroExpedienteCertificado').value = '';
            document.getElementById('formatoDocumentoCertificado').value = 'word';
            document.getElementById('mensajeEstadoCertificado').classList.remove('visible');
            document.getElementById('dialogCertificado').classList.add('active');
        }

        // Cerrar dialog
        function cerrarDialogCertificado() {
            document.getElementById('dialogCertificado').classList.remove('active');
        }

        // Generar certificado
        async function generarCertificado() {
            const numeroExpediente = document.getElementById('numeroExpedienteCertificado').value.trim();
            const formato = document.getElementById('formatoDocumentoCertificado').value;
            
            if (!numeroExpediente) {
                mostrarMensajeDialog('Por favor ingrese el número de expediente', 'error');
                return;
            }

            // Regex que acepta 5 o 6 dígitos al inicio
            const regexExpediente = /^\d{5,6}-\d{4}-\d{1}$/;
            if (!regexExpediente.test(numeroExpediente)) {
                mostrarMensajeDialog('Formato de expediente inválido. Use: XXXXX-YYYY-X o XXXXXX-YYYY-X', 'error');
                return;
            }


            if (!(await mostrarAlerta({
                tipo: 'info',
                titulo: '¿Deseas continuar?',
                mensaje: "Al generar el certificado, el practicante será marcado como FINALIZADO y ya no podrá registrar más asistencias.",
                showCancelButton: true
            })).isConfirmed) {
                return; // CANCELADO → salir sin continuar
            }

            const btnGenerar = document.getElementById('btnGenerarCertificado');
            btnGenerar.disabled = true;
            btnGenerar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';

            try {
                const response = await api.generarCertificadoHoras({
                    practicanteID: practicanteSeleccionado.PracticanteID,
                    numeroExpediente,
                    formato
                });

                if (response.success) {
                    mostrarMensajeDialog(response.message, 'success');
                    
                    // Descargar el archivo
                    const link = document.createElement('a');
                    link.href = response.url;
                    link.download = response.nombreArchivo;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);

                    setTimeout(async () => {
                        cerrarDialogCertificado();
                        // Recargar estadísticas y lista
                        await cargarEstadisticas();
                        await cargarPracticantes();
                        // Limpiar selección
                        document.getElementById('selectPracticante').value = '';
                        ocultarInformacion();
                    }, 2000);
                } else {
                    mostrarMensajeDialog(response.message || 'Error al generar el certificado', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarMensajeDialog(error.message ||'Error al generar el certificado', 'error');
            } finally{
                btnGenerar.disabled = false;
                btnGenerar.innerHTML = '<i class="fas fa-download"></i> Generar Certificado';
            }
        }

        // Mostrar mensaje en el dialog
        function mostrarMensajeDialog(mensaje, tipo) {
            const mensajeDiv = document.getElementById('mensajeEstadoCertificado');
            mensajeDiv.textContent = mensaje;
            mensajeDiv.className = `mensaje-estado ${tipo} visible`;
            
            setTimeout(() => {
                mensajeDiv.classList.remove('visible');
            }, 5000);
        }

        // Formatear fecha
        function formatearFecha(fecha) {
            if (!fecha) return 'No especificada';

            const [year, month, day] = fecha.split('-');
            return `${day}/${month}/${year}`;
        }

        // Cerrar dialog al hacer clic fuera
        document.getElementById('dialogCertificado').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarDialogCertificado();
            }
        });
    };
    // Verificar si el DOM ya está cargado o esperar al evento
    if (document.readyState === 'loading') {
        // DOM aún no está listo, esperar al evento
        console.log('entra al if');
        document.addEventListener('DOMContentLoaded', inicializar);
    } else {
        console.log('entra al else');
        // DOM ya está listo, ejecutar inmediatamente
        inicializar();

    }
}