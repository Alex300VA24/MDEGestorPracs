// asistencias.js - Refactorizado con lifecycle management

(function() {
    'use strict';

    // ==================== CONFIGURACIÓN DEL MÓDULO ====================
    
    const MODULE_NAME = 'asistencias';
    
    // Constantes de turnos
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

    // Estado privado del módulo
    let moduleState = {
        cronometroInterval: null,
        tiempoInicio: null,
        tiempoPausadoTotal: 0,
        asistenciaActual: null,
        pausaActivaInicio: null,
        eventListeners: [],
        asistenciasCache: null
    };

    // ==================== CLASE PRINCIPAL DEL MÓDULO ====================
    
    class AsistenciasModule {
        constructor() {
            this.state = moduleState;
            this.initialized = false;
        }

        // ============ INICIALIZACIÓN ============
        
        async init() {
            if (this.initialized) {
                console.warn('⚠️ Módulo Asistencias ya inicializado');
                return this;
            }

            try {
                // Inicializar modal
                this.inicializarModal();
                
                // Cargar datos iniciales
                await this.cargarAsistencias();
                
                // Configurar event listeners
                this.configurarEventListeners();
                
                // Marcar como inicializado
                this.initialized = true;
                
                return this;
                
            } catch (error) {
                console.error('❌ Error al inicializar módulo Asistencias:', error);
                throw error;
            }
        }

        // ============ CARGAR DATOS ============
        
        async cargarAsistencias() {
            try {
                const areaID = sessionStorage.getItem('areaID');
                if (!areaID) {
                    console.warn('⚠️ No se encontró área del usuario.');
                    mostrarAlerta({
                        tipo: 'warning',
                        titulo: 'Área no encontrada',
                        mensaje: 'No se pudo identificar tu área de trabajo. Por favor, vuelve a iniciar sesión.'
                    });
                    return;
                }

                const payload = { areaID: parseInt(areaID) };

                const fecha = document.getElementById('fechaFiltro')?.value;
                if (fecha) {
                    payload.fecha = fecha;
                }

                const response = await api.listarAsistencias(payload);

                if (!response || !response.success || !Array.isArray(response.data)) {
                    console.error('Error: formato de datos inválido', response);
                    return;
                }

                this.state.asistenciasCache = response.data;
                this.renderizarTablaAsistencias(response.data);
                this.actualizarStats(response.data);

            } catch (error) {
                console.error('❌ Error al cargar asistencias:', error);
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error',
                    mensaje: error.message || 'Error al cargar las asistencias'
                });
            }
        }

        // ============ RENDERIZAR TABLA ============
        
        renderizarTablaAsistencias(asistencias) {
            const tbody = document.getElementById('tableAsistenciasBody');
            
            if (!tbody) {
                console.warn('⚠️ Tabla de asistencias no encontrada');
                return;
            }

            tbody.innerHTML = '';

            if (asistencias.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center">No hay practicantes vigentes en tu área</td>
                    </tr>
                `;
                return;
            }

            asistencias.forEach(row => {
                const tr = document.createElement('tr');

                let duracion = '-';
                if (row.HoraEntrada && row.HoraSalida) {
                    duracion = this.calcularDuracionConPausas(row);
                } else if (row.HoraEntrada) {
                    duracion = 'En curso';
                }

                const turnos = row.Turnos ? row.Turnos.split(',').join(', ') : (row.Turno || '-');

                const f = new Date(row.Fecha);
                const fechaFormateada = `${String(f.getDate()).padStart(2,'0')}-${String(f.getMonth()+1).padStart(2,'0')}-${f.getFullYear()}`;

                const permitirRegistro = this.esHoy(row.Fecha);
                
                // Validar fecha de inicio de prácticas
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
                    <td><span class="badge badge-${this.getBadgeClass(row.Estado)}">${row.Estado}</span></td>
                    <td>
                        <button class="btn-primary" ${btnDisabled} title="${btnTitle}" 
                                onclick='asistenciasModule.abrirModalAsistencia(${row.PracticanteID}, "${row.NombreCompleto}", "${row.Fecha}", "${row.FechaInicioPracticas || ''}")'>
                            <i class="fas fa-clock"></i> Registrar
                        </button>
                    </td>
                `;

                tbody.appendChild(tr);
            });
        }

        // ============ INICIALIZAR MODAL ============
        
        inicializarModal() {
            if (document.getElementById('modalAsistencia')) {
                return;
            }

            const modalHTML = `
                <div id="modalAsistencia" class="modal-asistencia">
                    <div class="modal-asistencia-content">
                        <div class="modal-asistencia-header">
                            <h2 id="modalTitulo">Control de Asistencia</h2>
                            <span class="close" onclick="asistenciasModule.cerrarModal()">&times;</span>
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
                                <button class="btn-success" id="btnRegistrarEntrada" onclick="asistenciasModule.registrarEntrada()">
                                    <i class="fas fa-sign-in-alt"></i> Registrar Entrada
                                </button>
                                <button class="btn-warning" id="btnPausa" onclick="asistenciasModule.togglePausa()" style="display:none;">
                                    <i class="fas fa-pause"></i> Pausar
                                </button>
                                <button class="btn-danger" id="btnRegistrarSalida" onclick="asistenciasModule.registrarSalida()" style="display:none;">
                                    <i class="fas fa-sign-out-alt"></i> Registrar Salida
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHTML);
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

            // Checkbox hora manual
            const checkHoraManual = document.getElementById('checkHoraManual');
            addListener(checkHoraManual, 'change', (e) => this.handleHoraManualChange(e));

            // Input hora manual
            const inputHoraManual = document.getElementById('inputHoraManual');
            addListener(inputHoraManual, 'change', (e) => this.handleHoraManualInput(e));

            // Click fuera del modal
            addListener(window, 'click', (e) => {
                const modal = document.getElementById('modalAsistencia');
                if (e.target === modal) {
                    this.cerrarModal();
                }
            });

            // Para btn filtrar y limpiar filtros
            const btnFiltrar = document.getElementById('btnFiltrarFecha');
            addListener(btnFiltrar, 'click', () => this.filtrarPorFecha());

            const btnLimpiar = document.getElementById('btnLimpiarFiltros');
            addListener(btnLimpiar, 'click', () => this.limpiarFiltros());
        }

        handleHoraManualChange(e) {
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
        }

        handleHoraManualInput(e) {
            const horaSeleccionada = e.target.value;
            if (horaSeleccionada) {
                if (horaSeleccionada >= TURNOS.TARDE.horaInicio) {
                    document.querySelector('input[name="turno"][value="2"]').checked = true;
                } else {
                    document.querySelector('input[name="turno"][value="1"]').checked = true;
                }
            }
        }

        // ============ ABRIR MODAL ============
        
        async abrirModalAsistencia(practicanteID, nombreCompleto, fecha, fechaInicioPracticas = '') {
            try {
                if (fecha && !this.esHoy(fecha)) {
                    mostrarAlerta({
                        tipo: 'warning',
                        titulo: 'Día no válido',
                        mensaje: 'Solo se puede registrar asistencia el día actual'
                    });
                    return;
                }
                
                // Validar fecha de inicio de prácticas
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

                // Resetear estado anterior
                this.resetearEstadoModal();
                
                const modal = document.getElementById('modalAsistencia');
                document.getElementById('nombrePracticante').textContent = nombreCompleto;
                
                // Obtener datos desde la API
                const response = await api.obtenerAsistenciaCompleta(practicanteID);
                
                if (!response.success) {
                    console.error('Error al obtener asistencia:', response.message);
                    this.mostrarModoEntrada();
                    this.state.asistenciaActual = {
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
                this.state.asistenciaActual = {
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
                    this.mostrarModoEntrada();
                } else if (datosAsistencia.HoraEntrada && !datosAsistencia.HoraSalida) {
                    this.mostrarModoSalida(datosAsistencia);
                } else {
                    document.getElementById('estadoActual').textContent = 'Asistencia ya registrada para hoy';
                    document.getElementById('btnRegistrarEntrada').style.display = 'none';
                    document.getElementById('btnRegistrarSalida').style.display = 'none';
                    document.getElementById('btnPausa').style.display = 'none';
                    document.getElementById('cronometro').textContent = this.calcularDuracionConPausas(datosAsistencia);
                    document.querySelector('.turno-selector').style.display = 'none';
                    document.querySelector('.hora-manual').style.display = 'none';
                }

                modal.style.display = 'block';

            } catch (error) {
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error',
                    mensaje: 'Error al cargar información de asistencia'
                });
            }
        }

        // ============ RESETEAR ESTADO MODAL ============
        
        resetearEstadoModal() {
            this.detenerCronometro();
            
            this.state.tiempoInicio = null;
            this.state.tiempoPausadoTotal = 0;
            this.state.pausaActivaInicio = null;
            this.state.asistenciaActual = null;
            
            document.getElementById('cronometro').textContent = '00:00:00';
            document.getElementById('pausasContainer').style.display = 'none';
            document.getElementById('listaPausas').innerHTML = '';
            
            const btnPausa = document.getElementById('btnPausa');
            btnPausa.innerHTML = '<i class="fas fa-pause"></i> Pausar';
            btnPausa.classList.remove('btn-info');
            btnPausa.classList.add('btn-warning');
            btnPausa.style.display = 'none';
        }

        // ============ MODOS DEL MODAL ============
        
        mostrarModoEntrada() {
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

        mostrarModoSalida(datosAsistencia) {
            document.getElementById('estadoActual').textContent = `Entrada registrada: ${datosAsistencia.HoraEntrada}`;
            document.getElementById('btnRegistrarEntrada').style.display = 'none';
            document.getElementById('btnRegistrarSalida').style.display = 'inline-block';
            document.getElementById('btnPausa').style.display = 'inline-block';
            document.querySelector('.turno-selector').style.display = 'none';
            
            // Calcular tiempo pausado total
            this.state.tiempoPausadoTotal = 0;
            
            if (datosAsistencia.Pausas && Array.isArray(datosAsistencia.Pausas)) {
                datosAsistencia.Pausas.forEach(pausa => {
                    if (pausa.HoraInicio && pausa.HoraFin) {
                        const inicio = new Date(`1970-01-01T${pausa.HoraInicio}`);
                        const fin = new Date(`1970-01-01T${pausa.HoraFin}`);
                        this.state.tiempoPausadoTotal += (fin - inicio);
                    } else if (pausa.HoraInicio && !pausa.HoraFin) {
                        // Hay una pausa activa
                        this.state.asistenciaActual.pausaActiva = true;
                        this.state.asistenciaActual.pausaID = pausa.PausaID;
                        this.state.pausaActivaInicio = new Date(`1970-01-01T${pausa.HoraInicio}`);
                        
                        const btn = document.getElementById('btnPausa');
                        btn.innerHTML = '<i class="fas fa-play"></i> Reanudar';
                        btn.classList.remove('btn-warning');
                        btn.classList.add('btn-info');
                    }
                });
                
                if (datosAsistencia.Pausas.length > 0) {
                    this.mostrarPausas(datosAsistencia.Pausas);
                }
            }
            
            // Iniciar cronómetro
            this.iniciarCronometroDesdeEntrada(datosAsistencia.HoraEntrada);
        }

        // ============ CRONÓMETRO ============
        
        iniciarCronometroDesdeEntrada(horaEntrada) {
            const entrada = new Date(`1970-01-01T${horaEntrada}`);
            const ahora = new Date();
            const horaActual = new Date(`1970-01-01T${ahora.toTimeString().slice(0, 8)}`);
            
            this.state.tiempoInicio = horaActual - entrada;
            
            if (!this.state.asistenciaActual.pausaActiva) {
                this.iniciarCronometro();
            } else {
                const tiempoHastaPausa = this.state.pausaActivaInicio - entrada;
                this.actualizarDisplayCronometro(tiempoHastaPausa);
            }
        }

        iniciarCronometro() {
            if (this.state.cronometroInterval) {
                clearInterval(this.state.cronometroInterval);
            }
            
            const inicioConteo = Date.now();
            
            this.state.cronometroInterval = setInterval(() => {
                const tiempoTranscurrido = Date.now() - inicioConteo + 
                                          this.state.tiempoInicio - 
                                          this.state.tiempoPausadoTotal;
                this.actualizarDisplayCronometro(tiempoTranscurrido);
            }, 1000);
        }

        actualizarDisplayCronometro(ms) {
            if (ms < 0) ms = 0;
            
            const segundos = Math.floor(ms / 1000);
            const h = Math.floor(segundos / 3600);
            const m = Math.floor((segundos % 3600) / 60);
            const s = segundos % 60;
            
            document.getElementById('cronometro').textContent = 
                `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        }

        detenerCronometro() {
            if (this.state.cronometroInterval) {
                clearInterval(this.state.cronometroInterval);
                this.state.cronometroInterval = null;
            }
        }

        // ============ VALIDACIONES ============
        
        validarHoraEntrada(hora, turnoID) {
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

        validarHoraSalida(hora, turnoID) {
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

        validarSalidaPosteriorEntrada(horaEntrada, horaSalida) {
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

        // ============ REGISTRAR ENTRADA ============
        
        async registrarEntrada() {
            try {
                const turnoSeleccionado = parseInt(document.querySelector('input[name="turno"]:checked').value);
                let horaRegistro;

                if (document.getElementById('checkHoraManual').checked) {
                    horaRegistro = document.getElementById('inputHoraManual').value;
                    if (!horaRegistro) {
                        mostrarAlerta({
                            tipo: 'error',
                            titulo: 'Error',
                            mensaje: 'Por favor ingrese una hora válida'
                        });
                        return;
                    }
                } else {
                    horaRegistro = new Date().toTimeString().slice(0, 8);
                }

                const validacion = this.validarHoraEntrada(horaRegistro, turnoSeleccionado);
                if (!validacion.valido) {
                    mostrarAlerta({
                        tipo: 'info',
                        mensaje: validacion.mensaje
                    });
                    return;
                }

                const payload = {
                    practicanteID: this.state.asistenciaActual.practicanteID,
                    turnoID: turnoSeleccionado,
                    horaEntrada: horaRegistro
                };

                const res = await api.registrarEntrada(payload);

                if (!res.success) {
                    mostrarAlerta({
                        tipo: 'error',
                        titulo: 'Error',
                        mensaje: res.message || 'Ocurrió un error al registrar la entrada.'
                    });
                } else {
                    mostrarAlerta({
                        tipo: 'success',
                        titulo: 'Registrado',
                        mensaje: 'Entrada registrada exitosamente a las ' + (res.data?.horaRegistrada || horaRegistro)
                    });
                    this.cerrarModal();
                    await this.cargarAsistencias();
                }

            } catch (error) {
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error',
                    mensaje: error.message || error
                });
            }
        }

        // ============ REGISTRAR SALIDA ============
        
        async registrarSalida() {
            try {
                let horaRegistro;

                if (document.getElementById('checkHoraManual').checked) {
                    horaRegistro = document.getElementById('inputHoraManual').value;
                    if (!horaRegistro) {
                        mostrarAlerta({
                            tipo: 'info',
                            titulo: 'Error',
                            mensaje: 'Por favor ingrese una hora válida'
                        });
                        return;
                    }
                } else {
                    horaRegistro = new Date().toTimeString().slice(0, 8);
                }

                const validacionPosterior = this.validarSalidaPosteriorEntrada(
                    this.state.asistenciaActual.horaEntrada,
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

                const turnoID = this.determinarTurnoPorHora(this.state.asistenciaActual.horaEntrada);
                const turno = turnoID === 1 ? TURNOS.MANANA : TURNOS.TARDE;
                
                // Ajuste automático si se pasa del horario máximo
                if (horaRegistro > turno.salidaMaxima) {
                    horaRegistro = turno.horaFin;
                }
                
                const validacion = this.validarHoraSalida(horaRegistro, turnoID);
                if (!validacion.valido) {
                    mostrarAlerta({
                        tipo: 'info',
                        mensaje: validacion.mensaje
                    });
                    return;
                }

                const payload = {
                    practicanteID: this.state.asistenciaActual.practicanteID,
                    horaSalida: horaRegistro
                };

                const res = await api.registrarSalida(payload);

                if (!res.success) {
                    mostrarAlerta({
                        tipo: 'error',
                        titulo: 'Error',
                        mensaje: res.message || 'Ocurrió un error al registrar la salida.'
                    });
                } else {
                    mostrarAlerta({
                        tipo: 'success',
                        titulo: 'Registrado',
                        mensaje: 'Salida registrada exitosamente a las ' + (res.data?.horaRegistrada || horaRegistro)
                    });
                    this.detenerCronometro();
                    this.cerrarModal();
                    await this.cargarAsistencias();
                }

            } catch (error) {
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error',
                    mensaje: 'Error: ' + (error.message || error)
                });
            }
        }

        // ============ PAUSAS ============
        
        async togglePausa() {
            const btn = document.getElementById('btnPausa');
            
            if (!this.state.asistenciaActual.pausaActiva) {
                const motivo = prompt('Motivo de la pausa (opcional):');
                
                if (motivo === null) {
                    return;
                }
                
                try {
                    const res = await api.iniciarPausa({
                        asistenciaID: this.state.asistenciaActual.asistenciaID,
                        motivo: motivo || ''
                    });

                    if (res.success) {
                        this.state.asistenciaActual.pausaActiva = true;
                        this.state.asistenciaActual.pausaID = res.data.pausaID;
                        this.state.pausaActivaInicio = new Date(`1970-01-01T${res.data.horaInicio}`);
                        this.detenerCronometro();
                        btn.innerHTML = '<i class="fas fa-play"></i> Reanudar';
                        btn.classList.remove('btn-warning');
                        btn.classList.add('btn-info');
                        mostrarAlerta({
                            tipo: 'success',
                            titulo: 'Registrado',
                            mensaje: 'Pausa Iniciada'
                        });
                    } else {
                        mostrarAlerta({
                            tipo: 'error',
                            titulo: 'Error',
                            mensaje: res.message || 'Error al iniciar pausa'
                        });
                    }
                } catch (error) {
                    mostrarAlerta({
                        tipo: 'error',
                        titulo: 'Error',
                        mensaje: error.message || 'Error al iniciar pausa'
                    });
                }
            } else {
                try {
                    const res = await api.finalizarPausa({
                        pausaID: this.state.asistenciaActual.pausaID
                    });

                    if (res.success) {
                        const ahora = new Date();
                        const horaActual = new Date(`1970-01-01T${ahora.toTimeString().slice(0, 8)}`);
                        const duracionPausa = horaActual - this.state.pausaActivaInicio;
                        
                        this.state.tiempoPausadoTotal += duracionPausa;
                        
                        this.state.asistenciaActual.pausaActiva = false;
                        this.state.pausaActivaInicio = null;
                        
                        this.iniciarCronometro();
                        btn.innerHTML = '<i class="fas fa-pause"></i> Pausar';
                        btn.classList.remove('btn-info');
                        btn.classList.add('btn-warning');
                        
                        mostrarAlerta({
                            tipo: 'info',
                            mensaje: 'Pausa Finalizada'
                        });
                        
                        await this.cargarAsistencias();
                    } else {
                        mostrarAlerta({
                            tipo: 'error',
                            titulo: 'Error',
                            mensaje: res.message || 'Error al finalizar pausa'
                        });
                    }
                } catch (error) {
                    mostrarAlerta({
                        tipo: 'error',
                        titulo: 'Error',
                        mensaje: error.message || 'Error al finalizar pausa'
                    });
                }
            }
        }

        mostrarPausas(pausas) {
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

        // ============ FILTROS ============
        
        async filtrarPorFecha() {
            const fecha = document.getElementById('fechaFiltro')?.value;
            
            if (!fecha) {
                mostrarAlerta({
                    tipo: 'info',
                    mensaje: 'Por favor selecciona una Fecha'
                });
                return;
            }
            
            const fechaLabel = document.getElementById('asistenciaFechaID');
            if (fechaLabel) {
                fechaLabel.textContent = `Registro de Asistencia - ${fecha}`;
            }
            
            await this.cargarAsistencias();
        }

        async limpiarFiltros() {
            const fechaFiltro = document.getElementById('fechaFiltro');
            if (fechaFiltro) {
                fechaFiltro.value = '';
            }
            
            const fechaLabel = document.getElementById('asistenciaFechaID');
            if (fechaLabel) {
                fechaLabel.textContent = 'Registro de Asistencia - Hoy';
            }
            
            await this.cargarAsistencias();
        }

        // ============ CERRAR MODAL ============
        
        cerrarModal() {
            const modal = document.getElementById('modalAsistencia');
            if (modal) {
                modal.style.display = 'none';
            }
            this.resetearEstadoModal();
        }

        // ============ UTILIDADES ============
        
        esHoy(fecha) {
            const hoy = new Date();
            hoy.setHours(0, 0, 0, 0);
            
            const partes = fecha.split('-');
            const fechaComparar = new Date(partes[0], partes[1] - 1, partes[2]);
            fechaComparar.setHours(0, 0, 0, 0);
            
            return hoy.getTime() === fechaComparar.getTime();
        }

        calcularDuracionConPausas(row) {
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

        getBadgeClass(estado) {
            const clases = {
                'Presente': 'success',
                'Ausente': 'danger',
                'En curso': 'info'
            };
            return clases[estado] || 'secondary';
        }

        determinarTurnoPorHora(hora) {
            if (hora >= TURNOS.TARDE.horaInicio) {
                return TURNOS.TARDE.id;
            }
            return TURNOS.MANANA.id;
        }

        actualizarStats(data) {
            const presentes = data.filter(d => d.HoraEntrada && d.HoraSalida).length;
            const ausentes = data.filter(d => !d.HoraEntrada).length;
            
            const presentesEl = document.getElementById('presentesHoy');
            const ausentesEl = document.getElementById('ausentesHoy');
            
            if (presentesEl) presentesEl.textContent = presentes;
            if (ausentesEl) ausentesEl.textContent = ausentes;
        }

        // ============ API PÚBLICA ============
        
        async recargar() {
            if (this.initialized) {
                await this.cargarAsistencias();
            }
        }

        obtenerAsistencias() {
            return this.state.asistenciasCache;
        }

        // ============ LIMPIEZA ============
        
        async destroy() {
            try {
                // Detener cronómetro
                this.detenerCronometro();

                // Limpiar event listeners
                this.state.eventListeners.forEach(({ element, event, handler, options }) => {
                    if (element) {
                        element.removeEventListener(event, handler, options);
                    }
                });
                this.state.eventListeners = [];

                // Limpiar estado
                this.state.tiempoInicio = null;
                this.state.tiempoPausadoTotal = 0;
                this.state.asistenciaActual = null;
                this.state.pausaActivaInicio = null;
                this.state.asistenciasCache = null;

                // Cerrar modal si está abierto
                const modal = document.getElementById('modalAsistencia');
                if (modal && modal.style.display === 'block') {
                    this.cerrarModal();
                }

                // Remover modal del DOM
                if (modal) {
                    modal.remove();
                }

                // Limpiar tabla
                const tbody = document.getElementById('tableAsistenciasBody');
                if (tbody) {
                    tbody.innerHTML = '';
                }

                // Resetear stats
                const presentesEl = document.getElementById('presentesHoy');
                const ausentesEl = document.getElementById('ausentesHoy');
                if (presentesEl) presentesEl.textContent = '0';
                if (ausentesEl) ausentesEl.textContent = '0';

                // Marcar como no inicializado
                this.initialized = false;

            } catch (error) {
                console.error('❌ Error al limpiar módulo Asistencias:', error);
            }
        }
    }

    // ==================== REGISTRO DEL MÓDULO ====================
    
    const moduleDefinition = {
        async init() {
            const instance = new AsistenciasModule();
            await instance.init();
            
            // Exponer instancia globalmente para compatibilidad
            window.asistenciasModule = instance;
            
            return instance;
        },
        
        async destroy(instance) {
            if (instance && instance.destroy) {
                await instance.destroy();
                delete window.asistenciasModule;
            }
        }
    };

    if (window.moduleManager) {
        window.moduleManager.register(MODULE_NAME, moduleDefinition);
    } else {
        console.error('❌ ModuleManager no está disponible para módulo Asistencias');
    }

})();