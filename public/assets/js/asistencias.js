window.initAsistencias = function() {
    console.log("Asistencias iniciado");
    const inicializar = async () => {
        cargarAsistencias();
        inicializarModal();
    };
    // Verificar si el DOM ya está cargado o esperar al evento
    if (document.readyState === 'loading') {
        // DOM aún no está listo, esperar al evento
        document.addEventListener('DOMContentLoaded', inicializar);
    } else {
        // DOM ya está listo, ejecutar inmediatamente
        inicializar();
    }

    // Configuración de turnos
    const TURNOS = {
        MANANA: {
            id: 1,
            nombre: 'Mañana',
            horaInicio: '08:00:00',
            horaFin: '13:15:00',
            entradaMinima: '08:00:00',
            entradaMaxima: '13:15:00',
            salidaMinima: '08:00:00',
            salidaMaxima: '13:30:00'
        },
        TARDE: {
            id: 2,
            nombre: 'Tarde',
            horaInicio: '14:00:00',
            horaFin: '16:30:00',
            entradaMinima: '14:00:00',
            entradaMaxima: '16:30:00',
            salidaMinima: '14:00:00',
            salidaMaxima: '16:45:00'
        }
    };
    function esHoy(fecha) {
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0); // Resetear horas para comparar solo fechas
        
        // Extraer año, mes y día de la fecha string (formato: YYYY-MM-DD)
        const partes = fecha.split('-');
        const fechaComparar = new Date(partes[0], partes[1] - 1, partes[2]);
        fechaComparar.setHours(0, 0, 0, 0);
        
        return hoy.getTime() === fechaComparar.getTime();
    }
    

    let cronometroInterval = null;
    let tiempoInicio = null;
    let tiempoPausadoTotal = 0;
    let asistenciaActual = null;
    let pausaActivaInicio = null;

    async function cargarAsistencias() {
        try {
            const areaID = sessionStorage.getItem('areaID');
            if (!areaID) {
                console.warn("⚠️ No se encontró área del usuario.");
                mostrarAlerta({
                    tipo: 'warning',
                    titulo: 'Área no encontrada',
                    mensaje: 'No se pudo identificar tu área de trabajo. Por favor, vuelve a iniciar sesión.'
                });
                return;
            }

            const payload = { areaID: parseInt(areaID) };

            const fecha = document.getElementById('fechaFiltro').value;
            
            // Agregar filtros de fecha si existen
            if (fecha) {
                payload.fecha = fecha;
                
            }

            const response = await api.listarAsistencias(payload);

            if (!response || !response.success || !Array.isArray(response.data.data)) {
                console.error("Error: formato de datos inválido", response);
                return;
            }

            const asistencias = response.data.data;
            const tbody = document.getElementById('tableAsistenciasBody');
            tbody.innerHTML = '';

            if (asistencias.length === 0) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center">No hay practicantes vigentes en tu área</td></tr>`;
                return;
            }

            asistencias.forEach(row => {
                const tr = document.createElement('tr');

                let duracion = '-';
                if (row.HoraEntrada && row.HoraSalida) {
                    duracion = calcularDuracionConPausas(row);
                } else if (row.HoraEntrada) {
                    duracion = 'En curso';
                }

                const turnos = row.Turnos ? row.Turnos.split(',').join(', ') : (row.Turno || '-');

                const f = new Date(row.Fecha);
                fechaFormateada = `${String(f.getDate()).padStart(2,'0')}-${String(f.getMonth()+1).padStart(2,'0')}-${f.getFullYear()}`;

                const permitirRegistro = esHoy(row.Fecha);
                
                // Validar si la fecha de inicio de prácticas es posterior a hoy
                const hoy = new Date();
                hoy.setHours(0, 0, 0, 0);
                let fechaInicioPosterior = false;
                if (row.FechaInicioPracticas) {
                    const partesFecha = row.FechaInicioPracticas.split('-');
                    const fechaInicio = new Date(partesFecha[0], partesFecha[1] - 1, partesFecha[2]);
                    fechaInicio.setHours(0, 0, 0, 0);
                    fechaInicioPosterior = fechaInicio > hoy;
                }
                
                const btnDisabled = (!permitirRegistro || fechaInicioPosterior) ? 'disabled' : '';
                const btnTitle = fechaInicioPosterior 
                    ? `El practicante inicia el ${row.FechaInicioPracticas}` 
                    : (permitirRegistro ? 'Registrar asistencia' : 'Solo se puede registrar en el día actual');

                tr.innerHTML = `
                    <td>${row.Fecha}</td>
                    <td>${row.NombreCompleto}</td>
                    <td>${turnos}</td>
                    <td>${row.HoraEntrada || '-'}</td>
                    <td>${row.HoraSalida || '-'}</td>
                    <td>${duracion}</td>
                    <td><span class="badge badge-${getBadgeClass(row.Estado)}">${row.Estado}</span></td>
                    <td>
                        <button class="btn-primary" ${btnDisabled} title="${btnTitle}" onclick='abrirModalAsistencia(${row.PracticanteID}, "${row.NombreCompleto}", "${row.Fecha}", "${row.FechaInicioPracticas || ''}")'>
                            <i class="fas fa-clock"></i> Registrar
                        </button>
                    </td>
                `;

                tbody.appendChild(tr);
            });

            

            actualizarStats(asistencias);

        } catch (err) {
            console.error("Error al cargar asistencias:", err);
            mostrarAlerta({
                tipo: 'error',
                titulo: 'Error',
                mensaje: err.message || 'Error al cargar las asistencias'
            });
        }
    }

    function calcularDuracionConPausas(row) {
        if (!row.HoraEntrada || !row.HoraSalida) return '-';
        
        const entrada = new Date(`1970-01-01T${row.HoraEntrada}`);
        const salida = new Date(`1970-01-01T${row.HoraSalida}`);
        let diffMs = salida - entrada;

        if (row.TiempoPausas) {
            diffMs -= row.TiempoPausas * 1000;
        }

        if (diffMs < 0) diffMs = 0;

        const horas = Math.floor(diffMs / (1000 * 60 * 60));
        const minutos = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
        const segundos = Math.floor((diffMs % (1000 * 60)) / 1000);
        
        return `${horas}h ${minutos}m ${segundos}s`;
    }

    function getBadgeClass(estado) {
        const clases = {
            'Presente': 'success',
            'Ausente': 'danger',
            'En curso': 'info'
        };
        return clases[estado] || 'secondary';
    }

    function inicializarModal() {
        if (!document.getElementById('modalAsistencia')) {
            const modalHTML = `
                <div id="modalAsistencia" class="modal-asistencia">
                    <div class="modal-asistencia-content">
                        <div class="modal-asistencia-header">
                            <h2 id="modalTitulo">Control de Asistencia</h2>
                            <span class="close" onclick="cerrarModal()">&times;</span>
                        </div>
                        <div class="modal-asistencia-body">
                            <div class="practicante-info">
                                <h3 id="nombrePracticante"></h3>
                                <p id="estadoActual"></p>
                            </div>

                            <div class="cronometro-container">
                                <div class="cronometro" id="cronometro">00:00:00</div>
                                <div class="cronometro-label">Tiempo de práctica</div>
                            </div>

                            <div class="turno-selector">
                                <label>Turno:</label>
                                <div class="radio-group">
                                    <label>
                                        <input type="radio" name="turno" value="1" checked>
                                        <span>Mañana (8:00 AM - 1:15 PM)</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="turno" value="2">
                                        <span>Tarde (2:00 PM - 4:30 PM)</span>
                                    </label>
                                </div>
                            </div>

                            <div class="hora-manual">
                                <label>
                                    <input type="checkbox" id="checkHoraManual">
                                    Registrar hora manualmente
                                </label>
                                <input type="time" id="inputHoraManual" step="1" disabled>
                            </div>

                            <div class="pausas-container" id="pausasContainer" style="display:none;">
                                <h4>Pausas registradas</h4>
                                <div id="listaPausas"></div>
                            </div>

                            <div class="modal-asistencia-actions">
                                <button class="btn-success" id="btnRegistrarEntrada" onclick="registrarEntrada()">
                                    <i class="fas fa-sign-in-alt"></i> Registrar Entrada
                                </button>
                                <button class="btn-warning" id="btnPausa" onclick="togglePausa()" style="display:none;">
                                    <i class="fas fa-pause"></i> Pausar
                                </button>
                                <button class="btn-danger" id="btnRegistrarSalida" onclick="registrarSalida()" style="display:none;">
                                    <i class="fas fa-sign-out-alt"></i> Registrar Salida
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHTML);
        }

        document.getElementById('checkHoraManual').addEventListener('change', (e) => {
            const inputHora = document.getElementById('inputHoraManual');
            const radiosTurno = document.querySelectorAll('input[name="turno"]');
            
            inputHora.disabled = !e.target.checked;
            
            radiosTurno.forEach(radio => {
                radio.disabled = e.target.checked;
            });
            
            if (e.target.checked) {
                const horaActual = new Date().toTimeString().slice(0, 8);
                if (horaActual >= TURNOS.TARDE.horaInicio) {
                    document.querySelector('input[name="turno"][value="2"]').checked = true;
                } else {
                    document.querySelector('input[name="turno"][value="1"]').checked = true;
                }
            }
        });

        document.getElementById('inputHoraManual').addEventListener('change', (e) => {
            const horaSeleccionada = e.target.value;
            if (horaSeleccionada) {
                if (horaSeleccionada >= TURNOS.TARDE.horaInicio) {
                    document.querySelector('input[name="turno"][value="2"]').checked = true;
                } else {
                    document.querySelector('input[name="turno"][value="1"]').checked = true;
                }
            }
        });
    }

    // ============================================
    // ABRIR MODAL CON CARGA DESDE API
    // ============================================
    async function abrirModalAsistencia(practicanteID, nombreCompleto, fecha, fechaInicioPracticas = '') {
        try {

            if (fecha && !esHoy(fecha)) {
                mostrarAlerta({
                    tipo: 'warning', 
                    titulo: 'Día no válido', 
                    mensaje: 'Solo se puede registrar asistencia el día actual'
                });
                return;
            }
            
            // Validar si la fecha de inicio de prácticas es posterior a hoy
            if (fechaInicioPracticas) {
                const hoy = new Date();
                hoy.setHours(0, 0, 0, 0);
                const partesFecha = fechaInicioPracticas.split('-');
                const fechaInicio = new Date(partesFecha[0], partesFecha[1] - 1, partesFecha[2]);
                fechaInicio.setHours(0, 0, 0, 0);
                
                if (fechaInicio > hoy) {
                    mostrarAlerta({
                        tipo: 'warning', 
                        titulo: 'Prácticas no iniciadas', 
                        mensaje: `No se puede registrar asistencia. La fecha de inicio de prácticas (${fechaInicioPracticas}) es posterior a la fecha actual.`
                    });
                    return;
                }
            }
            // Resetear estado anterior antes de abrir
            resetearEstadoModal();
            
            const modal = document.getElementById('modalAsistencia');
            document.getElementById('nombrePracticante').textContent = nombreCompleto;
            
            // 🔥 OBTENER DATOS ACTUALIZADOS DESDE LA API
            const response = await api.obtenerAsistenciaCompleta(practicanteID);
            
            if (!response.success) {
                console.error("Error al obtener asistencia:", response.message);
                mostrarModoEntrada();
                asistenciaActual = {
                    practicanteID: practicanteID,
                    asistenciaID: null,
                    horaEntrada: null,
                    horaSalida: null,
                    pausaActiva: false,
                    pausaID: null,
                    pausas: []
                };
                modal.style.display = 'block';
                return;
            }

            const datosAsistencia = response.data;
            
            // Configurar estado actual
            asistenciaActual = {
                practicanteID: practicanteID,
                asistenciaID: datosAsistencia ? datosAsistencia.AsistenciaID : null,
                horaEntrada: datosAsistencia ? datosAsistencia.HoraEntrada : null,
                horaSalida: datosAsistencia ? datosAsistencia.HoraSalida : null,
                pausaActiva: false,
                pausaID: null,
                pausas: datosAsistencia ? datosAsistencia.Pausas : []
            };

            // Determinar modo del modal
            if (!datosAsistencia || !datosAsistencia.HoraEntrada) {
                mostrarModoEntrada();
            } else if (datosAsistencia.HoraEntrada && !datosAsistencia.HoraSalida) {
                mostrarModoSalida(datosAsistencia);
            } else {
                document.getElementById('estadoActual').textContent = 'Asistencia ya registrada para hoy';
                document.getElementById('btnRegistrarEntrada').style.display = 'none';
                document.getElementById('btnRegistrarSalida').style.display = 'none';
                document.getElementById('btnPausa').style.display = 'none';
                document.getElementById('cronometro').textContent = calcularDuracionConPausas(datosAsistencia);
                document.querySelector('.turno-selector').style.display = 'none';
                document.querySelector('.hora-manual').style.display = 'none';
            }

            modal.style.display = 'block';

        } catch (err) {
            mostrarAlerta({tipo:'error', titulo: 'Error', 
                            mensaje: 'Error al cargar informacion de asistencia'});

        }
    }

    function resetearEstadoModal() {
        // Detener cronómetro anterior
        detenerCronometro();
        
        // Resetear variables globales
        tiempoInicio = null;
        tiempoPausadoTotal = 0;
        pausaActivaInicio = null;
        asistenciaActual = null;
        
        // Resetear interfaz
        document.getElementById('cronometro').textContent = '00:00:00';
        document.getElementById('pausasContainer').style.display = 'none';
        document.getElementById('listaPausas').innerHTML = '';
        
        // Resetear botón de pausa
        const btnPausa = document.getElementById('btnPausa');
        btnPausa.innerHTML = '<i class="fas fa-pause"></i> Pausar';
        btnPausa.classList.remove('btn-info');
        btnPausa.classList.add('btn-warning');
        btnPausa.style.display = 'none';
    }

    function mostrarModoEntrada() {
        document.getElementById('estadoActual').textContent = 'Sin registro de entrada';
        document.getElementById('btnRegistrarEntrada').style.display = 'inline-block';
        document.getElementById('btnRegistrarSalida').style.display = 'none';
        document.getElementById('btnPausa').style.display = 'none';
        document.getElementById('cronometro').textContent = '00:00:00';
        document.querySelector('.turno-selector').style.display = 'block';
        document.getElementById('pausasContainer').style.display = 'none';
        
        const horaActual = new Date().toTimeString().slice(0, 8);
        if (horaActual >= TURNOS.TARDE.horaInicio) {
            document.querySelector('input[name="turno"][value="2"]').checked = true;
        } else {
            document.querySelector('input[name="turno"][value="1"]').checked = true;
        }
    }

    function mostrarModoSalida(datosAsistencia) {
        document.getElementById('estadoActual').textContent = `Entrada registrada: ${datosAsistencia.HoraEntrada}`;
        document.getElementById('btnRegistrarEntrada').style.display = 'none';
        document.getElementById('btnRegistrarSalida').style.display = 'inline-block';
        document.getElementById('btnPausa').style.display = 'inline-block';
        document.querySelector('.turno-selector').style.display = 'none';
        //document.querySelector('.hora-manual').style.display = 'none';
        
        // Calcular tiempo pausado total
        tiempoPausadoTotal = 0;
        
        if (datosAsistencia.Pausas && Array.isArray(datosAsistencia.Pausas)) {
            datosAsistencia.Pausas.forEach(pausa => {
                if (pausa.HoraInicio && pausa.HoraFin) {
                    const inicio = new Date(`1970-01-01T${pausa.HoraInicio}`);
                    const fin = new Date(`1970-01-01T${pausa.HoraFin}`);
                    tiempoPausadoTotal += (fin - inicio);
                } else if (pausa.HoraInicio && !pausa.HoraFin) {
                    // Hay una pausa activa
                    asistenciaActual.pausaActiva = true;
                    asistenciaActual.pausaID = pausa.PausaID;
                    pausaActivaInicio = new Date(`1970-01-01T${pausa.HoraInicio}`);
                    
                    const btn = document.getElementById('btnPausa');
                    btn.innerHTML = '<i class="fas fa-play"></i> Reanudar';
                    btn.classList.remove('btn-warning');
                    btn.classList.add('btn-info');
                }
            });
            
            if (datosAsistencia.Pausas.length > 0) {
                mostrarPausas(datosAsistencia.Pausas);
            }
        }
        
        // Iniciar cronómetro
        iniciarCronometroDesdeEntrada(datosAsistencia.HoraEntrada);
    }

    function iniciarCronometroDesdeEntrada(horaEntrada) {
        const entrada = new Date(`1970-01-01T${horaEntrada}`);
        const ahora = new Date();
        const horaActual = new Date(`1970-01-01T${ahora.toTimeString().slice(0, 8)}`);
        
        tiempoInicio = horaActual - entrada;
        
        if (!asistenciaActual.pausaActiva) {
            iniciarCronometro();
        } else {
            const tiempoHastaPausa = pausaActivaInicio - entrada;
            actualizarDisplayCronometro(tiempoHastaPausa);
        }
    }

    function iniciarCronometro() {
        if (cronometroInterval) clearInterval(cronometroInterval);
        
        const inicioConteo = Date.now();
        
        cronometroInterval = setInterval(() => {
            const tiempoTranscurrido = Date.now() - inicioConteo + tiempoInicio - tiempoPausadoTotal;
            actualizarDisplayCronometro(tiempoTranscurrido);
        }, 1000);
    }

    function actualizarDisplayCronometro(ms) {
        if (ms < 0) ms = 0;
        
        const segundos = Math.floor(ms / 1000);
        const h = Math.floor(segundos / 3600);
        const m = Math.floor((segundos % 3600) / 60);
        const s = segundos % 60;
        
        document.getElementById('cronometro').textContent = 
            `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    }

    function detenerCronometro() {
        if (cronometroInterval) {
            clearInterval(cronometroInterval);
            cronometroInterval = null;
        }
    }

    function validarHoraEntrada(hora, turnoID) {
        const turno = turnoID === 1 ? TURNOS.MANANA : TURNOS.TARDE;
        
        if (hora < turno.entradaMinima) {
            return {
                valido: false,
                mensaje: `No puedes registrar entrada antes de las ${turno.entradaMinima.slice(0, 5)} para el turno de ${turno.nombre}`
            };
        }
        
        if (hora > turno.entradaMaxima) {
            return {
                valido: false,
                mensaje: `No puedes registrar entrada después de las ${turno.entradaMaxima.slice(0, 5)} para el turno de ${turno.nombre}`
            };
        }
        
        return { valido: true };
    }

    function validarHoraSalida(hora, turnoID) {
        const turno = turnoID === 1 ? TURNOS.MANANA : TURNOS.TARDE;
        
        if ((turnoID === 1) && (hora < turno.salidaMinima)) {
            return {
                valido: false,
                mensaje: `No puedes registrar salida antes de las ${turno.salidaMinima.slice(0, 5)} para el turno de ${turno.nombre}`
            };
        }
        
        if (hora > turno.salidaMaxima) {
            return {
                valido: false,
                mensaje: `No puedes registrar salida después de las ${turno.salidaMaxima.slice(0, 5)} para el turno de ${turno.nombre}`
            };
        }
        
        return { valido: true };
    }

    function validarSalidaPosteriorEntrada(horaEntrada, horaSalida) {
        const entrada = new Date(`1970-01-01T${horaEntrada}`);
        const salida = new Date(`1970-01-01T${horaSalida}`);
        
        if (salida <= entrada) {
            return {
                valido: false,
                mensaje: 'La hora de salida debe ser posterior a la hora de entrada'
            };
        }
        
        return { valido: true };
    }

    async function registrarEntrada() {
        try {
            const turnoSeleccionado = parseInt(document.querySelector('input[name="turno"]:checked').value);
            let horaRegistro;

            if (document.getElementById('checkHoraManual').checked) {
                horaRegistro = document.getElementById('inputHoraManual').value;
                if (!horaRegistro) {
                    mostrarAlerta({tipo:'error', titulo: 'Error', 
                            mensaje: 'Por favor ingrese una hora válida'});
                    return;
                }
            } else {
                horaRegistro = new Date().toTimeString().slice(0, 8);
            }

            const validacion = validarHoraEntrada(horaRegistro, turnoSeleccionado);
            if (!validacion.valido) {
                mostrarAlerta({tipo:'info',  
                            mensaje: validacion.mensaje});
                return;
            }

            const payload = {
                practicanteID: asistenciaActual.practicanteID,
                turnoID: turnoSeleccionado,
                horaEntrada: horaRegistro
            };

            const res = await api.registrarEntrada(payload);

            if (!res.success) {
                mostrarAlerta({tipo:'error', titulo: 'Error', 
                            mensaje: res.message || 'Ocurrió un error al registrar la entrada.'});
            } else {
                mostrarAlerta({tipo:'success', titulo: 'Registrado', 
                            mensaje: 'Entrada registrada exitosamente a las ' + (res.data?.horaRegistrada || horaRegistro)});
                cerrarModal();
                await cargarAsistencias();
            }

        } catch (err) {
            mostrarAlerta({tipo:'error', titulo: 'Error', 
                            mensaje: err.message || err });
        }
    }

    async function registrarSalida() {
        try {
            let horaRegistro;

            if (document.getElementById('checkHoraManual').checked) {
                horaRegistro = document.getElementById('inputHoraManual').value;
                if (!horaRegistro) {
                    mostrarAlerta({tipo:'info', titulo: 'Error', 
                            mensaje: err.message || err });
                    return;
                }
            } else {
                horaRegistro = new Date().toTimeString().slice(0, 8);
            }

            const validacionPosterior = validarSalidaPosteriorEntrada(
                asistenciaActual.horaEntrada, 
                horaRegistro
            );
            if (!validacionPosterior.valido) {
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error',
                    mensaje: validacionPosterior.mensaje
                });
                return;
            }

            const turnoID = determinarTurnoPorHora(asistenciaActual.horaEntrada);
            const turno = turnoID === 1 ? TURNOS.MANANA : TURNOS.TARDE;
            
            // 🔥 AJUSTE AUTOMÁTICO: Si se pasa del horario máximo, ajustar a la hora de fin del turno
            if (horaRegistro > turno.salidaMaxima) {
                horaRegistro = turno.horaFin;
            }
            
            const validacion = validarHoraSalida(horaRegistro, turnoID);
            if (!validacion.valido) {
                mostrarAlerta({tipo:'info',
                            mensaje: validacion.mensaje});
                return;
            }

            const payload = {
                practicanteID: asistenciaActual.practicanteID,
                horaSalida: horaRegistro
            };

            const res = await api.registrarSalida(payload);

            if (!res.success) {
                mostrarAlerta({tipo:'error', titulo: 'Error', 
                            mensaje: res.message || 'Ocurrió un error al registrar la salida.'});
            } else {
                mostrarAlerta({tipo:'success', titulo: 'Registrado', 
                            mensaje: 'Salida registrada exitosamente a las ' + (res.data?.horaRegistrada || horaRegistro)});
                detenerCronometro();
                cerrarModal();
                await cargarAsistencias();
            }

        } catch (err) {
            mostrarAlerta({tipo:'error', titulo: 'Error', 
                            mensaje: 'Error: ' + (err.message || err)});
        }
    }

    function determinarTurnoPorHora(hora) {
        if (hora >= TURNOS.TARDE.horaInicio) {
            return TURNOS.TARDE.id;
        }
        return TURNOS.MANANA.id;
    }

    async function togglePausa() {
        const btn = document.getElementById('btnPausa');
        
        if (!asistenciaActual.pausaActiva) {
            const motivo = prompt('Motivo de la pausa (opcional):');
            
            if (motivo === null) {
                return;
            }
            
            try {
                const res = await api.iniciarPausa({
                    asistenciaID: asistenciaActual.asistenciaID,
                    motivo: motivo || ''
                });

                if (res.success) {
                    asistenciaActual.pausaActiva = true;
                    asistenciaActual.pausaID = res.data.pausaID;
                    pausaActivaInicio = new Date(`1970-01-01T${res.data.horaInicio}`);
                    detenerCronometro();
                    btn.innerHTML = '<i class="fas fa-play"></i> Reanudar';
                    btn.classList.remove('btn-warning');
                    btn.classList.add('btn-info');
                    mostrarAlerta({tipo:'success', titulo: 'Registrado', 
                            mensaje: 'Pausa Iniciada'});
                } else {
                    mostrarAlerta({tipo:'error', titulo: 'Error', 
                            mensaje: res.message || 'Error al iniciar pausa'});
                }
            } catch (err) {
                mostrarAlerta({tipo:'error', titulo: 'Error', 
                            mensaje: res.message || 'Error al iniciar pausa'});
            }
        } else {
            try {
                const res = await api.finalizarPausa({
                    pausaID: asistenciaActual.pausaID
                });

                if (res.success) {
                    const ahora = new Date();
                    const horaActual = new Date(`1970-01-01T${ahora.toTimeString().slice(0, 8)}`);
                    const duracionPausa = horaActual - pausaActivaInicio;
                    
                    tiempoPausadoTotal += duracionPausa;
                    
                    asistenciaActual.pausaActiva = false;
                    pausaActivaInicio = null;
                    
                    iniciarCronometro();
                    btn.innerHTML = '<i class="fas fa-pause"></i> Pausar';
                    btn.classList.remove('btn-info');
                    btn.classList.add('btn-warning');
                    
                    mostrarAlerta({tipo:'info', 
                            mensaje: 'Pausa Finalizada'});
                    
                    await cargarAsistencias();
                } else {
                    mostrarAlerta({tipo:'error', titulo: 'Error', 
                            mensaje: res.message || 'Error al finalizar pausa'});
                }
            } catch (err) {
                mostrarAlerta({tipo:'error', titulo: 'Error', 
                            mensaje: res.message || 'Error al finalizar pausa'});
            }
        }
    }

    function mostrarPausas(pausas) {
        const container = document.getElementById('pausasContainer');
        const lista = document.getElementById('listaPausas');
        
        if (!pausas || pausas.length === 0) {
            container.style.display = 'none';
            return;
        }
        
        lista.innerHTML = pausas.map(pausa => `
            <div class="pausa-item">
                <span>${pausa.HoraInicio} - ${pausa.HoraFin || 'En curso'}</span>
                <span>${pausa.Motivo || 'Sin motivo'}</span>
            </div>
        `).join('');
        
        container.style.display = 'block';
    }

    function cerrarModal() {
        document.getElementById('modalAsistencia').style.display = 'none';
        resetearEstadoModal();
    }

    function actualizarStats(data) {
        const presentes = data.filter(d => d.HoraEntrada && d.HoraSalida).length;
        const ausentes = data.filter(d => !d.HoraEntrada).length;
        document.getElementById('presentesHoy').textContent = presentes;
        document.getElementById('ausentesHoy').textContent = ausentes;
    }

    window.onclick = function(event) {
        const modal = document.getElementById('modalAsistencia');
        if (event.target === modal) {
            cerrarModal();
        }
    }

    function filtrarPorFecha() {
        const fecha = document.getElementById('fechaFiltro').value;
        
        if (!fecha) {
            mostrarAlerta({tipo:'info', 
                            mensaje: 'Por favor selecciona una Fecha'});
            return;
        }
        
        cargarAsistencias(fecha);
    }

    function limpiarFiltros() {
        document.getElementById('fechaFiltro').value = '';
        cargarAsistencias(); // Sin parámetro = hoy
    }

    window.filtrarPorFecha = filtrarPorFecha;
    window.limpiarFiltros = limpiarFiltros;
    window.abrirModalAsistencia = abrirModalAsistencia;
    window.cerrarModal = cerrarModal;
    window.registrarEntrada = registrarEntrada;
    window.registrarSalida = registrarSalida;
    window.togglePausa = togglePausa;
};


