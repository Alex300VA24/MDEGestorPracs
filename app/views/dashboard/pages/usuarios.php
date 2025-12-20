<div class="usuarios-container">

    <div class="page-header">
        
        <h1><i class="fas fa-users-cog"></i> Gestión de Usuarios</h1>
        <p class="page-subtitle">Administrar usuarios y permisos del sistema</p>
    </div>
    
    <!-- ============================================
         BOTONES DE ACCIÓN PRINCIPAL
         ============================================ -->
    <div class="action-buttons">
        <button class="btn btn-primary" id="btnNuevoUsuario">
            <i class="fas fa-user-plus"></i>
            Nuevo Usuario
        </button>
    </div>

    <!-- ============================================
         ESTADÍSTICAS DE USUARIOS
         ============================================ -->
    <div class="stats-grid">
        <!-- Total de Usuarios -->
        <div class="stat-card">
            <div class="stat-icon text-primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-number" id="totalUsuarios">0</div>
            <div class="stat-label">Total Usuarios</div>
        </div>

        <!-- Usuarios Activos -->
        <div class="stat-card">
            <div class="stat-icon text-success">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-number" id="usuariosActivos">0</div>
            <div class="stat-label">Usuarios Activos</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon text-danger">
                <i class="fas fa-user-times"></i>
            </div>
            <div class="stat-number" id="usuariosInactivos">0</div>
            <div class="stat-label">Usuarios Inactivos</div>
        </div>

        <!-- Gerentes de Área -->
        <div class="stat-card">
            <div class="stat-icon text-warning">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="stat-number" id="gerentesArea">0</div>
            <div class="stat-label">Gerentes de Área</div>
        </div>
    </div>

    <!-- ============================================
         FILTROS DE BÚSQUEDA
         ============================================ -->
    <div class="content-card">
        <h3 class="card-title">
            <i class="fas fa-filter"></i>
            Filtros
        </h3>
        <div class="card-body">
            <div class="practicantes-form filters-grid-usuarios">
                <!-- Filtro por Nombre/Usuario -->
                <div class="form-group mb-0 pb-2">
                    <label for="filtroUsuario" class="form-label">
                        <i class="fas fa-search"></i>
                        Buscar:
                    </label>
                    <input type="text" 
                           id="filtroUsuario" 
                           class="form-control" 
                           placeholder="Nombre o usuario..."
                           autocomplete="off">
                </div>

                <!-- Filtro por Rol -->
                <div class="form-group mb-0 pb-2">
                    <label for="filtroRol" class="form-label">
                        <i class="fas fa-user-tag"></i>
                        Cargo:
                    </label>
                    <select id="filtroRol" class="form-control">
                        <option value="">Todos los roles</option>
                        <option value="gerente_rrhh">Gerente RRHH</option>
                        <option value="gerente_area">Gerente de Área</option>
                        <option value="usuario_area">Usuario de Área</option>
                    </select>
                </div>

                <!-- Filtro por Área -->
                <div class="form-group mb-0 pb-2">
                    <label for="filtroAreaUsuario" class="form-label">
                        <i class="fas fa-building"></i>
                        Área:
                    </label>
                    <select id="filtroAreaUsuario" class="form-control">
                        <option value="">Todas las áreas</option>
                        <!-- Se llenará dinámicamente -->
                    </select>
                </div>
                <!-- Botón Aplicar -->
                <button class="btn-primary" onclick="aplicarFiltrosUsuarios()" type="button">
                    <i class="fas fa-filter"></i>
                    Aplicar Filtros
                </button>
            </div>
        </div>
    </div>

    <!-- ============================================
         TABLA DE USUARIOS
         ============================================ -->
    <div class="content-card">
        <div class="card-title">
            <span>
                <i class="fas fa-list"></i>
                Lista de Usuarios
            </span>
            <span class="badge badge-info" id="totalRegistrosUsuarios">
                0 usuarios
            </span>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-user"></i> Usuario</th>
                            <th><i class="fas fa-id-card"></i> Nombre Completo</th>
                            <th><i class="fas fa-id-badge"></i> CUI</th>
                            <th><i class="fas fa-user-tag"></i> Cargo</th>
                            <th><i class="fas fa-building"></i> Área</th>
                            <th><i class="fas fa-calendar-plus"></i> Fecha Registro</th>
                            <th><i class="fas fa-toggle-on"></i> Estado</th>
                            <th><i class="fas fa-cog"></i> Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaUsuariosBody">
                        <tr>
                            <td colspan="9" class="text-center">
                                <div class="empty-state">
                                    <i class="fas fa-spinner fa-spin empty-state-icon"></i>
                                    <p class="empty-state-text">Cargando usuarios...</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ============================================
     MODAL: NUEVO/EDITAR USUARIO
     ============================================ -->
<div id="modalUsuario" class="modal">
    <div class="modal-content-custom modal-content-lg">
        <div class="modal-header-custom">
            <h3 class="modal-title" id="tituloUsuarioModal">
                <i class="fas fa-user-plus"></i>
                Nuevo Usuario
            </h3>
            <button class="close" onclick="cerrarModalUsuario()" type="button">
                &times;
            </button>
        </div>

        <form id="formUsuario">
            <div class="modal-body-custom">
                <!-- ID Oculto -->
                <input type="hidden" id="usuarioID" name="usuarioID">

                <!-- ============================================
                     DATOS DE ACCESO
                     ============================================ -->
                <div class="section-header">
                    <i class="fas fa-key"></i>
                    Datos de Acceso
                </div>
                
                <!-- Usuario -->
                <div class="form-group">
                    <label for="nombreUsuario" class="form-label">
                        <i class="fas fa-user"></i>
                        Nombre de Usuario: *
                    </label>
                    <input type="text" 
                           id="nombreUsuario" 
                           name="usuario"
                           class="form-control" 
                           placeholder="Ej: jperez"
                           autocomplete="off"
                           required>
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i>
                        Solo letras minúsculas y números, sin espacios
                    </small>
                </div>

                <!-- Contraseña -->
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i>
                        Contraseña: <span id="passwordLabel">*</span>
                    </label>
                    <div class="input-with-icon">
                        <input type="password" 
                               id="password" 
                               name="password"
                               class="form-control" 
                               placeholder="••••••••">
                        <i class="fas fa-eye-slash toggle-password" 
                           id="togglePassword1"
                           onclick="togglePasswordVisibility('password', 'togglePassword1')"
                           ></i>
                    </div>
                    <small class="text-muted">
                        <i class="fas fa-shield-alt"></i>
                        Mínimo 8 caracteres, incluir mayúsculas, números y símbolos
                    </small>
                    <div id="passwordStrength" class="hidden"></div>
                </div>

                <!-- Confirmar Contraseña -->
                <div class="form-group">
                    <label for="confirmarPassword" class="form-label">
                        <i class="fas fa-lock"></i>
                        Confirmar Contraseña: <span id="confirmarPasswordLabel">*</span>
                    </label>
                    <div class="input-with-icon">
                        <input type="password" 
                               id="confirmarPassword" 
                               name="confirmarPassword"
                           class="form-control" 
                               placeholder="••••••••"
                               >
                        <i class="fas fa-eye-slash toggle-password" 
                           id="togglePassword2"
                           onclick="togglePasswordVisibility('confirmarPassword', 'togglePassword2')"></i>
                    </div>
                    <div id="passwordMatch" class="mt-2 hidden"></div>
                </div>

                <!-- ============================================
                     DATOS PERSONALES
                     ============================================ -->
                <div class="section-header">
                    <i class="fas fa-id-card"></i>
                    Datos Personales
                </div>

                <!-- Nombre Completo -->
                <div class="form-group">
                    <label for="nombreCompleto" class="form-label">
                        <i class="fas fa-user"></i>
                        Nombre Completo: *
                    </label>
                    <input type="text" 
                           id="nombreCompleto" 
                           name="nombreCompleto"
                           class="form-control" 
                           placeholder="Ej: Juan Carlos Pérez García"
                           required>
                </div>

                <!-- DNI + CUI -->
                <div class="form-group">
                    <label for="cuiUsuario" class="form-label">
                        <i class="fas fa-id-badge"></i>
                        Documento de Identidad (DNI): *
                    </label>
                    <input type="text" 
                           id="cuiUsuario" 
                           name="cui"
                           class="form-control" 
                           placeholder="9 dígitos del DNI"
                           maxlength="9"
                           required>
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i>
                        Ingrese los 9 dígitos del DNI (incluye el CUI)
                    </small>
                </div>

                <!-- ============================================
                     PERMISOS Y ASIGNACIÓN
                     ============================================ -->
                <div class="section-header">
                    <i class="fas fa-user-shield"></i>
                    Permisos y Asignación
                </div>

                <div class="practicantes-form">
                    <!-- Rol -->
                    <div class="form-group">
                        <label for="rolUsuario" class="form-label">
                            <i class="fas fa-user-tag"></i>
                            Rol del Usuario: *
                        </label>
                        <select id="rolUsuario" 
                                name="rol"
                                class="form-control"
                                required>
                            <option value="">-- Seleccionar rol --</option>
                            <option value="gerente_rrhh">Gerente RRHH</option>
                            <option value="gerente_area">Gerente de Área</option>
                            <option value="usuario_area">Usuario de Área</option>
                            <option value="gerente_sistemas">Gerente de Sistemas</option>
                        </select>
                    </div>

                    <!-- Área -->
                    <div class="form-group">
                        <label for="areaUsuario" class="form-label">
                            <i class="fas fa-building"></i>
                            Área: *
                        </label>
                        <select id="areaUsuario" 
                                name="areaID"
                                class="form-control"
                                required>
                            <option value="">-- Seleccionar área --</option>
                            <!-- Se llenará dinámicamente -->
                        </select>
                    </div>
                </div>

                <!-- Descripción de Roles -->
                <div class="alert alert-info">
                    <strong><i class="fas fa-info-circle"></i> Descripción de Roles:</strong>
                    <ul>
                        <li><strong>Gerente RRHH:</strong> Acceso total al sistema</li>
                        <li><strong>Gerente de Área:</strong> Gestiona practicantes de su área</li>
                        <li><strong>Usuario de Área:</strong> Registra asistencias y consulta información</li>
                    </ul>
                </div>

                <!-- Estado (solo en edición) -->
                <div class="form-group" id="estadoUsuarioGroup">
                    <label for="estadoUsuario" class="form-label">
                        <i class="fas fa-toggle-on"></i>
                        Estado:
                    </label>
                    <select id="estadoUsuario" name="estado" class="form-control">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>

                <div class="modal-footer-custom">
                    <button type="button" 
                            onclick="cerrarModalUsuario()" 
                            class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i>
                        Guardar Usuario
                    </button>
                </div>
            </div>         
        </form>
    </div>
</div>
<!-- ============================================
     MODAL: CAMBIAR CONTRASEÑA
     ============================================ -->
<div id="modalCambiarPassword" class="modal">
    <div class="modal-content-custom modal-content-sm">
        <div class="modal-header-custom">
            <h3 class="modal-title">
                <i class="fas fa-key"></i>
                Cambiar Contraseña
            </h3>
            <button class="close" onclick="cerrarModalPassword()" type="button">
                &times;
            </button>
        </div>

        <form id="formCambiarPassword">
            <div class="modal-body-custom">
                <input type="hidden" id="passwordUsuarioID">

                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Atención:</strong> Esta acción cambiará la contraseña del usuario seleccionado.
                </div>

                <!-- Usuario (solo lectura) -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-user"></i>
                        Usuario:
                    </label>
                    <input type="text" 
                           id="passwordNombreUsuario"
                           class="form-control" 
                           readonly>
                </div>

                <!-- Nueva Contraseña -->
                <div class="form-group">
                    <label for="nuevaPassword" class="form-label">
                        <i class="fas fa-lock"></i>
                        Nueva Contraseña: *
                    </label>
                    <div class="input-with-icon">
                        <input type="password" 
                               id="nuevaPassword" 
                               class="form-control" 
                               placeholder="••••••••"
                               required>
                        <i class="fas fa-eye-slash toggle-password" 
                           id="togglePassword3"
                           onclick="togglePasswordVisibility('nuevaPassword', 'togglePassword3')"></i>
                    </div>
                </div>

                <!-- Confirmar Nueva Contraseña -->
                <div class="form-group">
                    <label for="confirmarNuevaPassword" class="form-label">
                        <i class="fas fa-lock"></i>
                        Confirmar Nueva Contraseña: *
                    </label>
                    <div class="input-with-icon">
                        <input type="password" 
                               id="confirmarNuevaPassword" 
                               class="form-control" 
                               placeholder="••••••••"
                               required>
                        <i class="fas fa-eye-slash toggle-password" 
                           id="togglePassword4"
                           onclick="togglePasswordVisibility('confirmarNuevaPassword', 'togglePassword4')"></i>
                    </div>
                    <div id="passwordMatch2" class="mt-2 hidden"></div>
                </div>
                <div class="modal-footer-custom">
                <button type="button" 
                        onclick="cerrarModalPassword()" 
                        class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-key"></i>
                    Cambiar Contraseña
                </button>
            </div>
            </div>
        </form>
    </div>
</div>


