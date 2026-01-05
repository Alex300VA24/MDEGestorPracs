
window.initUsuarios = function() {

    let usuariosData = [];
    let usuarioEditando = null;


    /* INICIALIZACIÓN */
    const inicializar = async () => {
        cargarAreas();
        cargarUsuarios();
        inicializarEventos();
    };

    // Verificar si el DOM ya está cargado o esperar al evento
    if (document.readyState === 'loading') {
        // DOM aún no está listo, esperar al evento
        document.addEventListener('DOMContentLoaded', inicializar);
    } else {
        // DOM ya está listo, ejecutar inmediatamente
        inicializar();
    }

    // ============================================
    // CARGAR DATOS INICIALES
    // ============================================
    async function cargarAreas() {
        try {
            const response = await api.listarAreas();
            if (response.success) {
                const selectArea = document.getElementById('areaUsuario');
                const selectFiltroArea = document.getElementById('filtroAreaUsuario');
                
                selectArea.innerHTML = '<option value="">-- Seleccionar área --</option>';
                selectFiltroArea.innerHTML = '<option value="">Todas las áreas</option>';
                
                response.data.forEach(area => {
                    selectArea.innerHTML += `<option value="${area.AreaID}">${area.NombreArea}</option>`;
                    selectFiltroArea.innerHTML += `<option value="${area.AreaID}">${area.NombreArea}</option>`;
                });
            }
        } catch (error) {
            console.error('Error al cargar áreas:', error);
            await manejarErrorAPI('Error al cargar las áreas', 'error');
        }
    }

    // Actualizar las funciones de carga para usar el nuevo manejador
    async function cargarUsuarios() {
        try {
            const response = await api.listarUsuarios();
            if (response.success) {
                usuariosData = response.data;
                mostrarUsuarios(usuariosData);
                actualizarEstadisticas(usuariosData);
            }
        } catch (error) {
            await manejarErrorAPI(error, 'cargar usuarios');
            document.getElementById('tablaUsuariosBody').innerHTML = `
                <tr>
                    <td colspan="9" class="text-center">
                        <div class="empty-state">
                            <i class="fas fa-exclamation-triangle empty-state-icon" style="color: #ef4444;"></i>
                            <p class="empty-state-text">Error al cargar usuarios</p>
                            <button onclick="cargarUsuarios()" class="btn btn-primary" style="margin-top: 1rem;">
                                <i class="fas fa-sync"></i> Reintentar
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }
    }

    // ============================================
    // MOSTRAR USUARIOS EN TABLA
    // ============================================
    function mostrarUsuarios(usuarios) {
        const tbody = document.getElementById('tablaUsuariosBody');
        
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
                            <button onclick="verUsuario(${usuario.UsuarioID})" 
                                    class="btn-icon btn-info" 
                                    title="Ver detalles">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="editarUsuario(${usuario.UsuarioID})" 
                                    class="btn-icon btn-warning" 
                                    title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="cambiarPasswordUsuario(${usuario.UsuarioID})" 
                                    class="btn-icon btn-primary" 
                                    title="Cambiar contraseña">
                                <i class="fas fa-key"></i>
                            </button>
                            <button onclick="confirmarEliminarUsuario(${usuario.UsuarioID})" 
                                    class="btn-icon btn-danger" 
                                    title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
        
        document.getElementById('totalRegistrosUsuarios').textContent = 
            `${usuarios.length} usuario${usuarios.length !== 1 ? 's' : ''}`;
    }

    // ============================================
    // ACTUALIZAR ESTADÍSTICAS
    // ============================================
    function actualizarEstadisticas(usuarios) {
        const totalUsuarios = usuarios.length;
        const activos = usuarios.filter(u => u.Activo).length;
        const inactivos = usuarios.filter(u => !u.Activo).length;
        const gerentes = usuarios.filter(u => u.NombreCargo === 'Gerente de Área').length;
        
        document.getElementById('totalUsuarios').textContent = totalUsuarios;
        document.getElementById('usuariosActivos').textContent = activos;
        document.getElementById('usuariosInactivos').textContent = inactivos;
        document.getElementById('gerentesArea').textContent = gerentes;
    }

    // ============================================
    // FILTROS
    // ============================================
    function aplicarFiltrosUsuarios() {
        const filtroTexto = document.getElementById('filtroUsuario').value.toLowerCase();
        const filtroRol = document.getElementById('filtroRol').value;
        const filtroArea = document.getElementById('filtroAreaUsuario').value;
        
        const usuariosFiltrados = usuariosData.filter(usuario => {
            const nombreCompleto = `${usuario.Nombres} ${usuario.ApellidoPaterno} ${usuario.ApellidoMaterno}`.toLowerCase();
            const nombreUsuario = usuario.NombreUsuario.toLowerCase();
            
            const cumpleTexto = !filtroTexto || nombreCompleto.includes(filtroTexto) || nombreUsuario.includes(filtroTexto);
            const cumpleRol = !filtroRol || usuario.NombreCargo === obtenerNombreCargo(filtroRol);
            const cumpleArea = !filtroArea || usuario.AreaID == filtroArea;
            
            return cumpleTexto && cumpleRol && cumpleArea;
        });
        
        mostrarUsuarios(usuariosFiltrados);
        actualizarEstadisticas(usuariosFiltrados);
    }

    function obtenerNombreCargo(valor) {
        const cargos = {
            'gerente_rrhh': 'Gerente RRHH',
            'gerente_area': 'Gerente de Área',
            'usuario_area': 'Usuario de Área'
        };
        return cargos[valor] || '';
    }

    // ============================================
    // EVENTOS
    // ============================================
    function inicializarEventos() {
        // Botón nuevo usuario
        document.getElementById('btnNuevoUsuario').addEventListener('click', abrirModalNuevoUsuario);
        
        // Formulario de usuario
        document.getElementById('formUsuario').addEventListener('submit', guardarUsuario);
        
        // Formulario de cambiar contraseña
        document.getElementById('formCambiarPassword').addEventListener('submit', guardarNuevaPassword);
        
        // Validación de contraseñas en tiempo real
        const confirmarPassword = document.getElementById('confirmarPassword');
        const password = document.getElementById('password');
        
        confirmarPassword.addEventListener('input', () => validarPasswordsCoinciden('password', 'confirmarPassword', 'passwordMatch'));
        password.addEventListener('input', () => validarPasswordsCoinciden('password', 'confirmarPassword', 'passwordMatch'));
        
        // Filtros en tiempo real
        document.getElementById('filtroUsuario').addEventListener('input', aplicarFiltrosUsuarios);
    }

    // ============================================
    // MODAL NUEVO USUARIO
    // ============================================
    function abrirModalNuevoUsuario() {
        usuarioEditando = null;
        document.getElementById('formUsuario').reset();
        document.getElementById('tituloUsuarioModal').innerHTML = '<i class="fas fa-user-plus"></i> Nuevo Usuario';
        document.getElementById('usuarioID').value = '';
        document.getElementById('estadoUsuarioGroup').style.display = 'none';
        
        // Hacer contraseña obligatoria
        document.getElementById('password').required = true;
        document.getElementById('confirmarPassword').required = true;
        document.getElementById('passwordLabel').textContent = '*';
        document.getElementById('confirmarPasswordLabel').textContent = '*';
        
        abrirModalUser('modalUsuario');
    }

    // ============================================
    // VER USUARIO
    // ============================================
    async function verUsuario(usuarioID) {
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
            mostrarAlerta({mensaje:'Error al obtener información del usuario', tipo:'error', titulo:'Error'});
        }
    }

    // ============================================
    // EDITAR USUARIO
    // ============================================
    async function editarUsuario(usuarioID) {
        try {
            const response = await api.obtenerUsuario(usuarioID);
            if (response.success) {
                usuarioEditando = response.data;
                
                document.getElementById('tituloUsuarioModal').innerHTML = '<i class="fas fa-user-edit"></i> Editar Usuario';
                document.getElementById('usuarioID').value = usuarioEditando.UsuarioID;
                document.getElementById('nombreUsuario').value = usuarioEditando.NombreUsuario;
                document.getElementById('nombreCompleto').value = `${usuarioEditando.Nombres} ${usuarioEditando.ApellidoPaterno} ${usuarioEditando.ApellidoMaterno}`;
                document.getElementById('cuiUsuario').value = usuarioEditando.DNI + usuarioEditando.CUI; // Últimos 9 dígitos
                document.getElementById('rolUsuario').value = obtenerValorCargo(usuarioEditando.NombreCargo);
                document.getElementById('areaUsuario').value = usuarioEditando.AreaID;
                document.getElementById('estadoUsuario').value = parseInt(usuarioEditando.Activo) ? '1' : '0';
                

                
                // Hacer contraseña opcional en edición
                document.getElementById('password').required = false;
                document.getElementById('confirmarPassword').required = false;
                document.getElementById('password').value = '';
                document.getElementById('confirmarPassword').value = '';
                document.getElementById('passwordLabel').textContent = '(opcional)';
                document.getElementById('confirmarPasswordLabel').textContent = '(opcional)';
                
                document.getElementById('estadoUsuarioGroup').style.display = 'block';
                abrirModalUser('modalUsuario');
            }
        } catch (error) {
            console.error('Error al cargar usuario:', error);
            mostrarAlerta({mensaje:'Error al cargar información del usuario', tipo:'error', titulo:'Error'});
        }
    }

    function obtenerValorCargo(nombreCargo) {
        const cargos = {
            'Gerente RRHH': 'gerente_rrhh',
            'Gerente de Área': 'gerente_area',
            'Usuario de Área': 'usuario_area',
            'Gerente de Sistemas': 'gerente_sistemas'
        };
        return cargos[nombreCargo] || '';
    }

    // ============================================
    // GUARDAR USUARIO
    // ============================================
    async function guardarUsuario(e) {
        e.preventDefault();
        
        // Validar contraseñas si se están cambiando
        const password = document.getElementById('password').value;
        const confirmarPassword = document.getElementById('confirmarPassword').value;

        console.log(password, confirmarPassword);
        
        if (password || confirmarPassword) {
            if (password !== confirmarPassword) {
                mostrarAlerta({tipo:'error', titulo: 'Error', 
                            mensaje: 'Las contraseñas no coinciden', toast:true });
                return;
            }
            if (password.length < 8) {
                mostrarAlerta({tipo:'error', titulo: 'Error', 
                            mensaje: 'La contraseña debe tener almenos 8 caracteres', toast:true });
                return;
            }
        }
        
        // Validar que en modo nuevo la contraseña sea obligatoria
        console.log(usuarioEditando);
        if (!usuarioEditando && !password) {
            mostrarAlerta({tipo:'error', titulo: 'Error', 
                            mensaje: 'La contraseña es obligatoria', toast:true });
            return;
        }
        
        // Separar nombre completo
        const nombreCompleto = document.getElementById('nombreCompleto').value.trim();
        const partesNombre = nombreCompleto.split(' ');
        
        if (partesNombre.length < 3) {
            mostrarAlerta({mensaje:'Ingrese nombre completo (nombres, apellido paterno y materno)', tipo:'info'});
            return;
        }
        
        // Validar DNI/CUI - debe ser exactamente 9 dígitos
        const dni = document.getElementById('cuiUsuario').value.trim();
        if (!/^\d{9}$/.test(dni)) {
            mostrarAlerta({tipo:'error', titulo: 'Error', 
                            mensaje: 'El DNI debe tener exactamente 9 dígitos (DNI + CUI)', toast:true });
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
            activo: usuarioEditando ? document.getElementById('estadoUsuario').value : '1'
        };
        
        // Agregar contraseña solo si se proporcionó
        if (password) {
            datos.password = password;
        }
        
        try {
            let response;
            console.log('Este es usuario editando: ', usuarioEditando);
            if (usuarioEditando) {
                response = await api.actualizarUsuario(usuarioEditando.UsuarioID, datos);
            } else {
                response = await api.crearUsuario(datos);
            }
            
            if (response.success) {
                mostrarAlerta({ tipo:'success', mensaje:response.message, toast:true });
                cerrarModalUsuario();
                cargarUsuarios();
            } else {
                mostrarAlerta({tipo:'error', titulo: 'Error', 
                            mensaje: response.message || 'Error al guardar usuario', toast:true });
            }
        } catch (error) {
            console.error('Error al guardar usuario:', error);
            mostrarAlerta({tipo:'error', titulo: 'Error', 
                            mensaje: error.message || 'Error al guardar usuario', toast:true });
        }
    }

    // ============================================
    // CAMBIAR CONTRASEÑA
    // ============================================
    function cambiarPasswordUsuario(usuarioID) {
        const usuario = usuariosData.find(u => u.UsuarioID === String(usuarioID));
        if (!usuario) return;
        
        document.getElementById('passwordUsuarioID').value = usuarioID;
        document.getElementById('passwordNombreUsuario').value = usuario.NombreUsuario;
        document.getElementById('nuevaPassword').value = '';
        document.getElementById('confirmarNuevaPassword').value = '';
        
        abrirModalUser('modalCambiarPassword');
    }

    async function guardarNuevaPassword(e) {
        e.preventDefault();
        
        const usuarioID = document.getElementById('passwordUsuarioID').value;
        const nuevaPassword = document.getElementById('nuevaPassword').value;
        const confirmarNuevaPassword = document.getElementById('confirmarNuevaPassword').value;
        
        if (nuevaPassword !== confirmarNuevaPassword) {
            mostrarAlerta({mensaje:'Las contraseñas no coinciden', tipo:'error', titulo:'Error'});
            return;
        }
        
        if (nuevaPassword.length < 8) {
            mostrarAlerta({mensaje:'La contraseña debe tener al menos 8 caracteres', tipo:'error', titulo:'Error'});
            return;
        }
        
        try {
            const response = await api.cambiarPasswordUsuario(usuarioID, { password: nuevaPassword });
            if (response.success) {
                mostrarAlerta({mensaje:'Contraseña actualizada correctamente', tipo:'success', titulo:'Actualizado'});
                cerrarModalPassword();
            } else {
                mostrarAlerta({mensaje:response.message || 'Error al cambiar contraseña', tipo:'error', titulo:'Error'});
            }
        } catch (error) {
            console.error('Error al cambiar contraseña:', error);
            mostrarAlerta({mensaje:'Error al cambiar contraseña', tipo:'error', titulo:'Error'});
        }
    }

    // ============================================
    // ELIMINAR USUARIO
    // ============================================
    function confirmarEliminarUsuario(usuarioID) {
        const usuario = usuariosData.find(u => u.UsuarioID === String(usuarioID));

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
                await eliminarUsuario(usuarioID);
            }
        });
    }

    async function eliminarUsuario(usuarioID) {
        try {
            const response = await api.eliminarUsuario(usuarioID);
            if (response.success) {
                mostrarAlerta({mensaje:'Usuario eliminado correctamente', tipo:'success', titulo:'Correcto'});
                cargarUsuarios();
            } else {
                mostrarAlerta({mensaje:response.message || 'Error al eliminar usuario', tipo:'error', titulo:'Error'});
            }
        } catch (error) {
            console.error('Error al eliminar usuario:', error);
            mostrarAlerta({mensaje:'Error al eliminar usuario', tipo:'error', titulo:'Error'});
        }
    }

    // ============================================
    // UTILIDADES
    // ============================================
    function cerrarModalUsuario() {
        cerrarModalUser('modalUsuario');
        document.getElementById('formUsuario').reset();
        usuarioEditando = null;
    }

    function cerrarModalPassword() {
        cerrarModalUser('modalCambiarPassword');
        document.getElementById('formCambiarPassword').reset();
    }

    function validarPasswordsCoinciden(passwordId, confirmarId, messageId) {
        const password = document.getElementById(passwordId).value;
        const confirmar = document.getElementById(confirmarId).value;
        const messageDiv = document.getElementById(messageId);
        
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

    function togglePasswordVisibility(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        
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

    // Funciones auxiliares (asumiendo que ya existen en tu proyecto)
    function abrirModalUser(modalId) {
        document.getElementById(modalId).style.display = 'flex';
    }

    function cerrarModalUser(modalId) {
        document.getElementById(modalId).style.display = 'none';
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




    // Validación del DNI en tiempo real
    document.getElementById('cuiUsuario')?.addEventListener('input', function(e) {
        let valor = e.target.value.replace(/\D/g, ''); // Solo números
        if (valor.length > 9) {
            valor = valor.substring(0, 9);
        }
        e.target.value = valor;
    });

    // Validación del nombre de usuario en tiempo real
    document.getElementById('nombreUsuario')?.addEventListener('input', function(e) {
        let valor = e.target.value.toLowerCase().replace(/[^a-z0-9]/g, '');
        e.target.value = valor;
    });

    // Indicador de fortaleza de contraseña
    function mostrarFortalezaPassword(passwordId, containerId) {
        const password = document.getElementById(passwordId);
        if (!password) return;
        
        password.addEventListener('input', function() {
            const valor = this.value;
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
            
            const container = document.getElementById(containerId);
            if (container && valor.length > 0) {
                container.style.display = 'block';
                container.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem;">
                        <div style="flex: 1; height: 6px; background: #e5e7eb; border-radius: 3px; overflow: hidden;">
                            <div style="width: ${fortaleza * 20}%; height: 100%; background: ${color}; transition: all 0.3s;"></div>
                        </div>
                        <span style="color: ${color}; font-size: 0.875rem; font-weight: 500;">${mensaje}</span>
                    </div>
                `;
            } else if (container) {
                container.style.display = 'none';
            }
        });
    }

    // Agregar después de inicializarEventos()
    document.addEventListener('DOMContentLoaded', function() {
        // ... código existente ...
        
        // Agregar indicador de fortaleza
        const passwordStrengthContainer = document.createElement('div');
        passwordStrengthContainer.id = 'passwordStrength';
        document.getElementById('password')?.parentNode.parentNode.appendChild(passwordStrengthContainer);
        
        mostrarFortalezaPassword('password', 'passwordStrength');
    });

    // Función mejorada para manejar errores de API
    async function manejarErrorAPI(error, operacion) {
        console.error(`Error en ${operacion}:`, error);
        
        let mensaje = 'Ha ocurrido un error inesperado';
        
        if (error.message) {
            mensaje = error.message;
        } else if (!navigator.onLine) {
            mensaje = 'No hay conexión a Internet. Verifica tu conexión.';
        } else if (error.status === 404) {
            mensaje = 'Recurso no encontrado';
        } else if (error.status === 403) {
            mensaje = 'No tienes permisos para esta operación';
        } else if (error.status === 500) {
            mensaje = 'Error del servidor. Intenta de nuevo más tarde.';
        }
        
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: mensaje,
            confirmButtonText: 'Aceptar'
        });
    }

    window.cerrarModalUsuario = cerrarModalUsuario;
    window.verUsuario = verUsuario;
    window.editarUsuario = editarUsuario;
    window.cambiarPasswordUsuario = cambiarPasswordUsuario;
    window.confirmarEliminarUsuario = confirmarEliminarUsuario;
    window.cerrarModalPassword = cerrarModalPassword;
    window.aplicarFiltrosUsuarios = aplicarFiltrosUsuarios;
    window.togglePasswordVisibility = togglePasswordVisibility;

};





