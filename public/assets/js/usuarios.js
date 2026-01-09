// usuarios.js - Refactorizado con lifecycle management

(function() {
    'use strict';

    // ==================== CONFIGURACIÓN DEL MÓDULO ====================
    
    const MODULE_NAME = 'usuarios';
    
    // Estado privado del módulo
    let moduleState = {
        usuariosData: [],
        usuarioEditando: null,
        eventListeners: [],
        areasCache: null
    };

    // ==================== CLASE PRINCIPAL DEL MÓDULO ====================
    
    class UsuariosModule {
        constructor() {
            this.state = moduleState;
            this.initialized = false;
        }

        // ============ INICIALIZACIÓN ============
        
        async init() {
            if (this.initialized) {
                console.warn('⚠️ Módulo Usuarios ya inicializado');
                return this;
            }

            try {
                // Cargar datos iniciales
                await this.cargarAreas();
                await this.cargarUsuarios();
                
                // Configurar event listeners
                this.configurarEventListeners();
                
                // Marcar como inicializado
                this.initialized = true;

                return this;
                
            } catch (error) {
                console.error('❌ Error al inicializar módulo Usuarios:', error);
                throw error;
            }
        }

        // ============ CARGAR DATOS ============
        
        async cargarAreas() {
            try {
                
                const response = await api.listarAreas();
                
                if (!response.success) {
                    throw new Error('Error al cargar áreas');
                }
                
                this.state.areasCache = response.data;
                
                // Renderizar selects de áreas
                const selectArea = document.getElementById('areaUsuario');
                const selectFiltroArea = document.getElementById('filtroAreaUsuario');
                
                if (selectArea) {
                    selectArea.innerHTML = '<option value="">-- Seleccionar área --</option>';
                    response.data.forEach(area => {
                        selectArea.innerHTML += `<option value="${area.AreaID}">${area.NombreArea}</option>`;
                    });
                }
                
                if (selectFiltroArea) {
                    selectFiltroArea.innerHTML = '<option value="">Todas las áreas</option>';
                    response.data.forEach(area => {
                        selectFiltroArea.innerHTML += `<option value="${area.AreaID}">${area.NombreArea}</option>`;
                    });
                }
                
            } catch (error) {
                console.error('❌ Error al cargar áreas:', error);
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error',
                    mensaje: 'Error al cargar las áreas'
                });
            }
        }

        async cargarUsuarios() {
            try {
                const response = await api.listarUsuarios();
                
                if (!response.success) {
                    throw new Error('Error al cargar usuarios');
                }
                
                this.state.usuariosData = response.data;
                this.mostrarUsuarios(response.data);
                this.actualizarEstadisticas(response.data);
                
            } catch (error) {
                console.error('❌ Error al cargar usuarios:', error);
                
                const tbody = document.getElementById('tablaUsuariosBody');
                if (tbody) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="9" class="text-center">
                                <div class="empty-state">
                                    <i class="fas fa-exclamation-triangle empty-state-icon" style="color: #ef4444;"></i>
                                    <p class="empty-state-text">Error al cargar usuarios</p>
                                    <button onclick="usuariosModule.cargarUsuarios()" class="btn btn-primary" style="margin-top: 1rem;">
                                        <i class="fas fa-sync"></i> Reintentar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                }
                
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error',
                    mensaje: error.message || 'Error al cargar usuarios'
                });
            }
        }

        // ============ MOSTRAR USUARIOS ============
        
        mostrarUsuarios(usuarios) {
            const tbody = document.getElementById('tablaUsuariosBody');
            
            if (!tbody) {
                console.warn('⚠️ Tabla de usuarios no encontrada');
                return;
            }
            
            if (!usuarios || usuarios.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center">
                            <div class="empty-state">
                                <i class="fas fa-users empty-state-icon"></i>
                                <p class="empty-state-text">No hay usuarios registrados</p>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = usuarios.map(usuario => {
                const estadoBadge = parseInt(usuario.Activo) 
                    ? '<span class="badge badge-success"><i class="fas fa-check"></i> Activo</span>'
                    : '<span class="badge badge-danger"><i class="fas fa-times"></i> Inactivo</span>';
                
                const nombreCompleto = `${usuario.Nombres} ${usuario.ApellidoPaterno} ${usuario.ApellidoMaterno}`;
                const fechaRegistro = usuario.FechaRegistro ? new Date(usuario.FechaRegistro).toLocaleDateString('es-PE') : '-';
                
                return `
                    <tr>
                        <td><strong>${usuario.NombreUsuario}</strong></td>
                        <td>${nombreCompleto}</td>
                        <td>${usuario.DNI}</td>
                        <td>${usuario.NombreCargo || '-'}</td>
                        <td>${usuario.NombreArea || '-'}</td>
                        <td>${fechaRegistro}</td>
                        <td>${estadoBadge}</td>
                        <td>
                            <div class="action-buttons" style="gap: 0.5rem;">
                                <button onclick="usuariosModule.verUsuario(${usuario.UsuarioID})" 
                                        class="btn-icon btn-info" 
                                        title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="usuariosModule.editarUsuario(${usuario.UsuarioID})" 
                                        class="btn-icon btn-warning" 
                                        title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="usuariosModule.cambiarPasswordUsuario(${usuario.UsuarioID})" 
                                        class="btn-icon btn-primary" 
                                        title="Cambiar contraseña">
                                    <i class="fas fa-key"></i>
                                </button>
                                <button onclick="usuariosModule.confirmarEliminarUsuario(${usuario.UsuarioID})" 
                                        class="btn-icon btn-danger" 
                                        title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
            
            const totalRegistros = document.getElementById('totalRegistrosUsuarios');
            if (totalRegistros) {
                totalRegistros.textContent = `${usuarios.length} usuario${usuarios.length !== 1 ? 's' : ''}`;
            }
        }

        // ============ ESTADÍSTICAS ============
        
        actualizarEstadisticas(usuarios) {
            const totalUsuarios = usuarios.length;
            const activos = usuarios.filter(u => u.Activo).length;
            const inactivos = usuarios.filter(u => !u.Activo).length;
            const gerentes = usuarios.filter(u => u.NombreCargo === 'Gerente de Área').length;
            
            const totalEl = document.getElementById('totalUsuarios');
            const activosEl = document.getElementById('usuariosActivos');
            const inactivosEl = document.getElementById('usuariosInactivos');
            const gerentesEl = document.getElementById('gerentesArea');
            
            if (totalEl) totalEl.textContent = totalUsuarios;
            if (activosEl) activosEl.textContent = activos;
            if (inactivosEl) inactivosEl.textContent = inactivos;
            if (gerentesEl) gerentesEl.textContent = gerentes;
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

            // Botón nuevo usuario
            const btnNuevo = document.getElementById('btnNuevoUsuario');
            addListener(btnNuevo, 'click', () => this.abrirModalNuevoUsuario());

            // Formulario de usuario
            const formUsuario = document.getElementById('formUsuario');
            addListener(formUsuario, 'submit', (e) => this.guardarUsuario(e));

            // Formulario cambiar contraseña
            const formPassword = document.getElementById('formCambiarPassword');
            addListener(formPassword, 'submit', (e) => this.guardarNuevaPassword(e));

            // Validación de contraseñas en tiempo real
            const password = document.getElementById('password');
            const confirmarPassword = document.getElementById('confirmarPassword');
            
            addListener(password, 'input', () => this.validarPasswordsCoinciden('password', 'confirmarPassword', 'passwordMatch'));
            addListener(confirmarPassword, 'input', () => this.validarPasswordsCoinciden('password', 'confirmarPassword', 'passwordMatch'));

            // Filtros
            const filtroUsuario = document.getElementById('filtroUsuario');
            addListener(filtroUsuario, 'input', () => this.aplicarFiltrosUsuarios());

            const filtroRol = document.getElementById('filtroRol');
            addListener(filtroRol, 'change', () => this.aplicarFiltrosUsuarios());

            const filtroArea = document.getElementById('filtroAreaUsuario');
            addListener(filtroArea, 'change', () => this.aplicarFiltrosUsuarios());

            // Validación DNI
            const cuiUsuario = document.getElementById('cuiUsuario');
            addListener(cuiUsuario, 'input', (e) => {
                let valor = e.target.value.replace(/\D/g, '');
                if (valor.length > 9) {
                    valor = valor.substring(0, 9);
                }
                e.target.value = valor;
            });

            // Validación nombre de usuario
            const nombreUsuario = document.getElementById('nombreUsuario');
            addListener(nombreUsuario, 'input', (e) => {
                let valor = e.target.value.toLowerCase().replace(/[^a-z0-9]/g, '');
                e.target.value = valor;
            });

            // Indicador de fortaleza de contraseña
            this.configurarFortalezaPassword();

            // Cerrar modal usuario
            const btnCerrarUsuario = document.getElementById('btnCerrarModalUsuario');
            addListener(btnCerrarUsuario, 'click', () => this.cerrarModalUsuario());

            // Cerrar modal con cancelar
            const btnCancelarUsuario = document.getElementById('btnCancelarUsuario');
            addListener(btnCancelarUsuario, 'click', () => this.cerrarModalUsuario());

            // Cerrar modal cambiar contraseña
            const btnCerrarPassword = document.getElementById('btnCerrarModalPassword');
            addListener(btnCerrarPassword, 'click', () => this.cerrarModalPassword());

            // Cerrar modal cambiar contraseña con cancelar
            const btnCancelarPassword = document.getElementById('btnCancelarPassword');
            addListener(btnCancelarPassword, 'click', () => this.cerrarModalPassword());
        }

        configurarFortalezaPassword() {
            const password = document.getElementById('password');
            if (!password) return;

            // Crear contenedor si no existe
            let container = document.getElementById('passwordStrength');
            if (!container) {
                container = document.createElement('div');
                container.id = 'passwordStrength';
                container.style.display = 'none';
                password.parentNode.parentNode.appendChild(container);
            }

            const handler = () => {
                const valor = password.value;
                let fortaleza = 0;
                let mensaje = '';
                let color = '';
                
                if (valor.length >= 8) fortaleza++;
                if (/[a-z]/.test(valor)) fortaleza++;
                if (/[A-Z]/.test(valor)) fortaleza++;
                if (/[0-9]/.test(valor)) fortaleza++;
                if (/[^a-zA-Z0-9]/.test(valor)) fortaleza++;
                
                switch(fortaleza) {
                    case 0:
                    case 1:
                        mensaje = 'Muy débil';
                        color = '#ef4444';
                        break;
                    case 2:
                        mensaje = 'Débil';
                        color = '#f59e0b';
                        break;
                    case 3:
                        mensaje = 'Media';
                        color = '#eab308';
                        break;
                    case 4:
                        mensaje = 'Fuerte';
                        color = '#10b981';
                        break;
                    case 5:
                        mensaje = 'Muy fuerte';
                        color = '#059669';
                        break;
                }
                
                if (valor.length > 0) {
                    container.style.display = 'block';
                    container.innerHTML = `
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem;">
                            <div style="flex: 1; height: 6px; background: #e5e7eb; border-radius: 3px; overflow: hidden;">
                                <div style="width: ${fortaleza * 20}%; height: 100%; background: ${color}; transition: all 0.3s;"></div>
                            </div>
                            <span style="color: ${color}; font-size: 0.875rem; font-weight: 500;">${mensaje}</span>
                        </div>
                    `;
                } else {
                    container.style.display = 'none';
                }
            };

            password.addEventListener('input', handler);
            
            this.state.eventListeners.push({
                element: password,
                event: 'input',
                handler: handler
            });
        }

        // ============ FILTROS ============
        
        aplicarFiltrosUsuarios() {
            const filtroTexto = document.getElementById('filtroUsuario')?.value.toLowerCase() || '';
            const filtroRol = document.getElementById('filtroRol')?.value || '';
            const filtroArea = document.getElementById('filtroAreaUsuario')?.value || '';
            
            const usuariosFiltrados = this.state.usuariosData.filter(usuario => {
                const nombreCompleto = `${usuario.Nombres} ${usuario.ApellidoPaterno} ${usuario.ApellidoMaterno}`.toLowerCase();
                const nombreUsuario = usuario.NombreUsuario.toLowerCase();
                
                const cumpleTexto = !filtroTexto || nombreCompleto.includes(filtroTexto) || nombreUsuario.includes(filtroTexto);
                const cumpleRol = !filtroRol || usuario.NombreCargo === this.obtenerNombreCargo(filtroRol);
                const cumpleArea = !filtroArea || usuario.AreaID == filtroArea;
                
                return cumpleTexto && cumpleRol && cumpleArea;
            });
            
            this.mostrarUsuarios(usuariosFiltrados);
            this.actualizarEstadisticas(usuariosFiltrados);
        }

        obtenerNombreCargo(valor) {
            const cargos = {
                'gerente_rrhh': 'Gerente RRHH',
                'gerente_area': 'Gerente de Área',
                'usuario_area': 'Usuario de Área'
            };
            return cargos[valor] || '';
        }

        obtenerValorCargo(nombreCargo) {
            const cargos = {
                'Gerente RRHH': 'gerente_rrhh',
                'Gerente de Área': 'gerente_area',
                'Usuario de Área': 'usuario_area',
                'Gerente de Sistemas': 'gerente_sistemas'
            };
            return cargos[nombreCargo] || '';
        }

        // ============ MODAL NUEVO USUARIO ============
        
        abrirModalNuevoUsuario() {
            this.state.usuarioEditando = null;
            document.getElementById('formUsuario').reset();
            document.getElementById('tituloUsuarioModal').innerHTML = '<i class="fas fa-user-plus"></i> Nuevo Usuario';
            document.getElementById('usuarioID').value = '';
            document.getElementById('estadoUsuarioGroup').style.display = 'none';
            
            // Hacer contraseña obligatoria
            document.getElementById('password').required = true;
            document.getElementById('confirmarPassword').required = true;
            document.getElementById('passwordLabel').textContent = '*';
            document.getElementById('confirmarPasswordLabel').textContent = '*';
            
            this.abrirModal('modalUsuario');
        }

        // ============ VER USUARIO ============
        
        async verUsuario(usuarioID) {
            try {
                const response = await api.obtenerUsuario(usuarioID);
                if (response.success) {
                    const usuario = response.data;
                    const nombreCompleto = `${usuario.Nombres} ${usuario.ApellidoPaterno} ${usuario.ApellidoMaterno}`;
                    
                    Swal.fire({
                        title: '<i class="fas fa-user"></i> Información del Usuario',
                        html: `
                            <div style="text-align: left; padding: 1rem;">
                                <div style="margin-bottom: 1rem;">
                                    <strong><i class="fas fa-id-badge"></i> ID:</strong> ${usuario.UsuarioID}
                                </div>
                                <div style="margin-bottom: 1rem;">
                                    <strong><i class="fas fa-user"></i> Usuario:</strong> ${usuario.NombreUsuario}
                                </div>
                                <div style="margin-bottom: 1rem;">
                                    <strong><i class="fas fa-id-card"></i> Nombre Completo:</strong> ${nombreCompleto}
                                </div>
                                <div style="margin-bottom: 1rem;">
                                    <strong><i class="fas fa-id-badge"></i> DNI:</strong> ${usuario.DNI}
                                </div>
                                <div style="margin-bottom: 1rem;">
                                    <strong><i class="fas fa-user-tag"></i> Cargo:</strong> ${usuario.NombreCargo || '-'}
                                </div>
                                <div style="margin-bottom: 1rem;">
                                    <strong><i class="fas fa-building"></i> Área:</strong> ${usuario.NombreArea || '-'}
                                </div>
                                <div style="margin-bottom: 1rem;">
                                    <strong><i class="fas fa-calendar"></i> Fecha Registro:</strong> ${new Date(usuario.FechaRegistro).toLocaleDateString('es-PE')}
                                </div>
                                <div style="margin-bottom: 1rem;">
                                    <strong><i class="fas fa-toggle-on"></i> Estado:</strong> 
                                    ${parseInt(usuario.Activo) ? '<span style="color: #28a745;">✓ Activo</span>' : '<span style="color: #dc3545;">✗ Inactivo</span>'}
                                </div>
                            </div>
                        `,
                        confirmButtonText: 'Cerrar',
                        width: '500px'
                    });
                }
            } catch (error) {
                console.error('Error al obtener usuario:', error);
                mostrarAlerta({
                    mensaje: 'Error al obtener información del usuario',
                    tipo: 'error',
                    titulo: 'Error'
                });
            }
        }

        // ============ EDITAR USUARIO ============
        
        async editarUsuario(usuarioID) {
            try {
                const response = await api.obtenerUsuario(usuarioID);
                if (response.success) {
                    this.state.usuarioEditando = response.data;
                    const usuario = response.data;
                    
                    document.getElementById('tituloUsuarioModal').innerHTML = '<i class="fas fa-user-edit"></i> Editar Usuario';
                    document.getElementById('usuarioID').value = usuario.UsuarioID;
                    document.getElementById('nombreUsuario').value = usuario.NombreUsuario;
                    document.getElementById('nombreCompleto').value = `${usuario.Nombres} ${usuario.ApellidoPaterno} ${usuario.ApellidoMaterno}`;
                    document.getElementById('cuiUsuario').value = usuario.DNI + usuario.CUI;
                    document.getElementById('rolUsuario').value = this.obtenerValorCargo(usuario.NombreCargo);
                    document.getElementById('areaUsuario').value = usuario.AreaID;
                    document.getElementById('estadoUsuario').value = parseInt(usuario.Activo) ? '1' : '0';
                    
                    // Hacer contraseña opcional en edición
                    document.getElementById('password').required = false;
                    document.getElementById('confirmarPassword').required = false;
                    document.getElementById('password').value = '';
                    document.getElementById('confirmarPassword').value = '';
                    document.getElementById('passwordLabel').textContent = '(opcional)';
                    document.getElementById('confirmarPasswordLabel').textContent = '(opcional)';
                    
                    document.getElementById('estadoUsuarioGroup').style.display = 'block';
                    this.abrirModal('modalUsuario');
                }
            } catch (error) {
                console.error('Error al cargar usuario:', error);
                mostrarAlerta({
                    mensaje: 'Error al cargar información del usuario',
                    tipo: 'error',
                    titulo: 'Error'
                });
            }
        }

        // ============ GUARDAR USUARIO ============
        
        async guardarUsuario(e) {
            e.preventDefault();
            
            // Validar contraseñas
            const password = document.getElementById('password').value;
            const confirmarPassword = document.getElementById('confirmarPassword').value;
            
            if (password || confirmarPassword) {
                if (password !== confirmarPassword) {
                    mostrarAlerta({
                        tipo: 'error',
                        titulo: 'Error',
                        mensaje: 'Las contraseñas no coinciden',
                        toast: true
                    });
                    return;
                }
                if (password.length < 8) {
                    mostrarAlerta({
                        tipo: 'error',
                        titulo: 'Error',
                        mensaje: 'La contraseña debe tener al menos 8 caracteres',
                        toast: true
                    });
                    return;
                }
            }
            
            // Validar contraseña obligatoria en modo nuevo
            if (!this.state.usuarioEditando && !password) {
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error',
                    mensaje: 'La contraseña es obligatoria',
                    toast: true
                });
                return;
            }
            
            // Separar nombre completo
            const nombreCompleto = document.getElementById('nombreCompleto').value.trim();
            const partesNombre = nombreCompleto.split(' ');
            
            if (partesNombre.length < 3) {
                mostrarAlerta({
                    mensaje: 'Ingrese nombre completo (nombres, apellido paterno y materno)',
                    tipo: 'info'
                });
                return;
            }
            
            // Validar DNI/CUI
            const dni = document.getElementById('cuiUsuario').value.trim();
            if (!/^\d{9}$/.test(dni)) {
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error',
                    mensaje: 'El DNI debe tener exactamente 9 dígitos (DNI + CUI)',
                    toast: true
                });
                return;
            }
            
            const datos = {
                nombreUsuario: document.getElementById('nombreUsuario').value.trim(),
                nombres: partesNombre.slice(0, -2).join(' '),
                apellidoPaterno: partesNombre[partesNombre.length - 2],
                apellidoMaterno: partesNombre[partesNombre.length - 1],
                dni: dni,
                cargo: document.getElementById('rolUsuario').value,
                areaID: document.getElementById('areaUsuario').value,
                activo: this.state.usuarioEditando ? document.getElementById('estadoUsuario').value : '1'
            };
            
            if (password) {
                datos.password = password;
            }
            
            try {
                let response;
                if (this.state.usuarioEditando) {
                    response = await api.actualizarUsuario(this.state.usuarioEditando.UsuarioID, datos);
                } else {
                    response = await api.crearUsuario(datos);
                }
                
                if (response.success) {
                    mostrarAlerta({
                        tipo: 'success',
                        mensaje: response.message,
                        toast: true
                    });
                    this.cerrarModalUsuario();
                    await this.cargarUsuarios();
                } else {
                    mostrarAlerta({
                        tipo: 'error',
                        titulo: 'Error',
                        mensaje: response.message || 'Error al guardar usuario',
                        toast: true
                    });
                }
            } catch (error) {
                console.error('Error al guardar usuario:', error);
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error',
                    mensaje: error.message || 'Error al guardar usuario',
                    toast: true
                });
            }
        }

        // ============ CAMBIAR CONTRASEÑA ============
        
        cambiarPasswordUsuario(usuarioID) {
            const usuario = this.state.usuariosData.find(u => u.UsuarioID === String(usuarioID));
            if (!usuario) return;
            
            document.getElementById('passwordUsuarioID').value = usuarioID;
            document.getElementById('passwordNombreUsuario').value = usuario.NombreUsuario;
            document.getElementById('nuevaPassword').value = '';
            document.getElementById('confirmarNuevaPassword').value = '';
            
            this.abrirModal('modalCambiarPassword');
        }

        async guardarNuevaPassword(e) {
            e.preventDefault();
            
            const usuarioID = document.getElementById('passwordUsuarioID').value;
            const nuevaPassword = document.getElementById('nuevaPassword').value;
            const confirmarNuevaPassword = document.getElementById('confirmarNuevaPassword').value;
            
            if (nuevaPassword !== confirmarNuevaPassword) {
                mostrarAlerta({
                    mensaje: 'Las contraseñas no coinciden',
                    tipo: 'error',
                    titulo: 'Error'
                });
                return;
            }
            
            if (nuevaPassword.length < 8) {
                mostrarAlerta({
                    mensaje: 'La contraseña debe tener al menos 8 caracteres',
                    tipo: 'error',
                    titulo: 'Error'
                });
                return;
            }
            
            try {
                const response = await api.cambiarPasswordUsuario(usuarioID, { password: nuevaPassword });
                if (response.success) {
                    mostrarAlerta({
                        mensaje: 'Contraseña actualizada correctamente',
                        tipo: 'success',
                        titulo: 'Actualizado'
                    });
                    this.cerrarModalPassword();
                } else {
                    mostrarAlerta({
                        mensaje: response.message || 'Error al cambiar contraseña',
                        tipo: 'error',
                        titulo: 'Error'
                    });
                }
            } catch (error) {
                console.error('Error al cambiar contraseña:', error);
                mostrarAlerta({
                    mensaje: 'Error al cambiar contraseña',
                    tipo: 'error',
                    titulo: 'Error'
                });
            }
        }

        // ============ ELIMINAR USUARIO ============
        
        confirmarEliminarUsuario(usuarioID) {
            const usuario = this.state.usuariosData.find(u => u.UsuarioID === String(usuarioID));

            if (!usuario) return;

            Swal.fire({
                title: '¿Eliminar usuario?',
                html: `¿Está seguro de eliminar al usuario <strong>${usuario.NombreUsuario}</strong>?<br><br>
                    <span style="color: #dc3545;">Esta acción no se puede deshacer.</span>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    await this.eliminarUsuario(usuarioID);
                }
            });
        }

        async eliminarUsuario(usuarioID) {
            try {
                const response = await api.eliminarUsuario(usuarioID);
                if (response.success) {
                    mostrarAlerta({
                        mensaje: 'Usuario eliminado correctamente',
                        tipo: 'success',
                        titulo: 'Correcto'
                    });
                    await this.cargarUsuarios();
                } else {
                    mostrarAlerta({
                        mensaje: response.message || 'Error al eliminar usuario',
                        tipo: 'error',
                        titulo: 'Error'
                    });
                }
            } catch (error) {
                console.error('Error al eliminar usuario:', error);
                mostrarAlerta({
                    mensaje: 'Error al eliminar usuario',
                    tipo: 'error',
                    titulo: 'Error'
                });
            }
        }

        // ============ UTILIDADES ============
        
        validarPasswordsCoinciden(passwordId, confirmarId, messageId) {
            const password = document.getElementById(passwordId)?.value || '';
            const confirmar = document.getElementById(confirmarId)?.value || '';
            const messageDiv = document.getElementById(messageId);
            
            if (!messageDiv) return;
            
            if (confirmar.length === 0) {
                messageDiv.style.display = 'none';
                return;
            }
            
            messageDiv.style.display = 'block';
            
            if (password === confirmar) {
                messageDiv.innerHTML = '<span style="color: #28a745;"><i class="fas fa-check"></i> Las contraseñas coinciden</span>';
            } else {
                messageDiv.innerHTML = '<span style="color: #dc3545;"><i class="fas fa-times"></i> Las contraseñas no coinciden</span>';
            }
        }

        togglePasswordVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (!input || !icon) return;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        }

        // ============ MODALES ============
        
        abrirModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'flex';
            }
        }

        cerrarModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
            }
        }

        cerrarModalUsuario() {
            this.cerrarModal('modalUsuario');
            const form = document.getElementById('formUsuario');
            if (form) form.reset();
            this.state.usuarioEditando = null;
            
            // Limpiar indicador de fortaleza
            const passwordStrength = document.getElementById('passwordStrength');
            if (passwordStrength) {
                passwordStrength.style.display = 'none';
            }
        }

        cerrarModalPassword() {
            this.cerrarModal('modalCambiarPassword');
            const form = document.getElementById('formCambiarPassword');
            if (form) form.reset();
        }

        // ============ API PÚBLICA ============
        
        async recargar() {
            if (this.initialized) {
                await this.cargarAreas();
                await this.cargarUsuarios();
            }
        }

        obtenerUsuarios() {
            return this.state.usuariosData;
        }

        obtenerAreas() {
            return this.state.areasCache;
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
                this.state.usuariosData = [];
                this.state.usuarioEditando = null;
                this.state.areasCache = null;

                // Cerrar modales si están abiertos
                const modalUsuario = document.getElementById('modalUsuario');
                const modalPassword = document.getElementById('modalCambiarPassword');
                
                if (modalUsuario && modalUsuario.style.display === 'flex') {
                    this.cerrarModalUsuario();
                }
                if (modalPassword && modalPassword.style.display === 'flex') {
                    this.cerrarModalPassword();
                }

                // Limpiar tabla
                const tbody = document.getElementById('tablaUsuariosBody');
                if (tbody) {
                    tbody.innerHTML = '';
                }

                // Resetear estadísticas
                const totalEl = document.getElementById('totalUsuarios');
                const activosEl = document.getElementById('usuariosActivos');
                const inactivosEl = document.getElementById('usuariosInactivos');
                const gerentesEl = document.getElementById('gerentesArea');
                
                if (totalEl) totalEl.textContent = '0';
                if (activosEl) activosEl.textContent = '0';
                if (inactivosEl) inactivosEl.textContent = '0';
                if (gerentesEl) gerentesEl.textContent = '0';

                // Limpiar filtros
                const filtroUsuario = document.getElementById('filtroUsuario');
                const filtroRol = document.getElementById('filtroRol');
                const filtroArea = document.getElementById('filtroAreaUsuario');
                
                if (filtroUsuario) filtroUsuario.value = '';
                if (filtroRol) filtroRol.value = '';
                if (filtroArea) filtroArea.value = '';

                // Limpiar selects
                const selectArea = document.getElementById('areaUsuario');
                const selectFiltroArea = document.getElementById('filtroAreaUsuario');
                
                if (selectArea) {
                    selectArea.innerHTML = '<option value="">-- Seleccionar área --</option>';
                }
                if (selectFiltroArea) {
                    selectFiltroArea.innerHTML = '<option value="">Todas las áreas</option>';
                }

                // Remover indicador de fortaleza de contraseña
                const passwordStrength = document.getElementById('passwordStrength');
                if (passwordStrength) {
                    passwordStrength.remove();
                }

                // Marcar como no inicializado
                this.initialized = false;

            } catch (error) {
                console.error('❌ Error al limpiar módulo Usuarios:', error);
            }
        }
    }

    // ==================== REGISTRO DEL MÓDULO ====================
    
    const moduleDefinition = {
        async init() {
            const instance = new UsuariosModule();
            await instance.init();
            
            // Exponer instancia globalmente para compatibilidad
            window.usuariosModule = instance;
            
            return instance;
        },
        
        async destroy(instance) {
            if (instance && instance.destroy) {
                await instance.destroy();
                delete window.usuariosModule;
            }
        }
    };

    if (window.moduleManager) {
        window.moduleManager.register(MODULE_NAME, moduleDefinition);
    } else {
        console.error('❌ ModuleManager no está disponible para módulo Usuarios');
    }

})();