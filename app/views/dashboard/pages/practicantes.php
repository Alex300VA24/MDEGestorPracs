<!-- VISTA DE SECCION PRACTICANTES -->

<div class="practicantes-container">

    <div class="page-header">
        <h1><i class="fas fa-users"></i> Gestión de Practicantes</h1>
        <p class="page-subtitle">Administrar solicitudes y estados de practicantes</p>
    </div>

    <div class="action-buttons">
        <button class="btn-primary" id="btnNuevoPracticante"><i class="fas fa-plus"></i> Nuevo Practicante</button>
        <button class="btn-info" id="btnMensajes"><i class="fas fa-envelope"></i> Mensajes</button>
    </div>

    <!-- Filtros -->
    <div class="content-card">
        <h3 class="card-title">Filtros</h3>
        <div class="filters-row">
            <div class="filters-col">
                <label for="filtroNombre">Buscar por nombre:</label>
                <input type="text" id="filtroNombre" placeholder="Ingrese nombre...">
            </div>
            <div class="filters-col">
                <label for="filtroArea">Filtrar por área:</label>
                <select id="filtroArea">
                    <option value="">Todas las áreas</option>
                </select>
            </div>
            <button class="btn-primary filters-apply" id="btnAplicarFiltros">
                <i class="fas fa-filter"></i> Aplicar Filtros
            </button>
        </div>
    </div>

    <div class="content-card">
        <h3 class="card-title">Lista de Practicantes</h3>
        <div class="table-container">
            <table id="tablaPracticantes" class="table">
                <thead>
                    <tr>
                        <th>DNI</th>
                        <th>Nombre Completo</th>
                        <th>Carrera</th>
                        <th>Universidad</th>
                        <th>Fecha Registro</th>
                        <th>Area</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Se llenará dinámicamente con JS -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal existente de Practicante -->
<div id="PracticanteModal" class="modal hidden">
  <div class="modal-content">
    <h3 id="tituloModalPracticante">Nuevo Practicante</h3>
    <form id="formPracticante">
      <input type="hidden" id="practicanteID" name="ID">
      
      <input type="text" id="DNI" name="DNI" placeholder="Ej: 12345678" required maxlength="8" pattern="\d{8}" title="Ingrese exactamente 8 dígitos">
      
      <input type="text" id="Nombres" name="Nombres" placeholder="Ej: Juan Carlos" required>
      
      <input type="text" id="ApellidoPaterno" name="ApellidoPaterno" placeholder="Ej: García" required>
      
      <input type="text" id="ApellidoMaterno" name="ApellidoMaterno" placeholder="Ej: Rodríguez" required>
      
      <select id="genero" name="genero" required>
        <option value="">Seleccione género</option>
        <option value="M">Masculino</option>
        <option value="F">Femenino</option>
      </select>
      
      <input type="text" id="Carrera" name="Carrera" placeholder="Ej: Ingeniería de Sistemas, Licenciatura en Administración" required minlength="15" title="Escriba el nombre completo de la carrera (mínimo 15 caracteres)">
      
      <input type="email" id="Email" name="Email" placeholder="Ej: correo@ejemplo.com" required pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}" title="Ingrese un correo válido con @ y dominio">
      
      <input type="tel" id="Telefono" name="Telefono" placeholder="Ej: +51 987654321" required pattern="\+51\s?\d{9}" title="Ingrese +51 seguido de 9 dígitos">
      
      <input type="text" id="Direccion" name="Direccion" placeholder="Ej: Av. España 123, Urb. El Golf - Trujillo" required>
      
      <input type="text" id="Universidad" name="Universidad" placeholder="Ej: Universidad Nacional de Trujillo, Universidad Privada Antenor Orrego" required minlength="20" title="Escriba el nombre completo de la universidad (mínimo 20 caracteres)">
      
      <button type="submit">Guardar</button>
      <button type="button" onclick="cerrarModalPracticante()">Cancelar</button>
    </form>
  </div>
</div>

<!-- Modal de Mensajes -->
<div id="modalMensajes" class="modal hidden">
    <div class="modal-content">
        <h3><i class="fas fa-inbox"></i> Solicitudes Recibidas</h3>
        
        <div id="listaMensajes" class="mensajes-lista">
            <!-- Se llenará dinámicamente -->
        </div>
        
        <div class="modal-footer">
            <button type="button" onclick="cerrarModalMensajes()" class="btn-cancel">
                <i class="fas fa-times"></i> Cerrar
            </button>
        </div>
    </div>
</div>

<!-- Modal para Aceptar/Rechazar Practicante -->
<div id="modalAceptarPracticante" class="modal hidden">
    <div class="modal-content">
        <h3><i class="fas fa-user-check"></i> Gestionar Solicitud de Practicante</h3>
        
        <form id="formAceptarPracticante">
            <input type="hidden" id="aceptarPracticanteID">
            <input type="hidden" id="aceptarSolicitudID">
            
            <!-- Información del Practicante -->
            <div id="infoPracticante">
                <!-- Se llenará dinámicamente -->
                
            </div>
            
            <!-- Decisión -->
            <div class="form-group">
                <label>
                    <i class="fas fa-tasks"></i> Decisión sobre la Solicitud
                </label>
                <select id="decisionAceptacion" required>
                    <option value="">-- Seleccionar decisión --</option>
                    <option value="aceptar">✓ Aceptar Practicante</option>
                    <option value="rechazar">✗ Rechazar Solicitud</option>
                </select>
            </div>
            
            <!-- Campos de Aceptación (aparecen cuando se selecciona "aceptar") -->
            <div id="camposAceptacion" class="hidden">
                <h4 class="section-subtitle text-primary">
                    <i class="fas fa-calendar-check"></i> Periodo de Prácticas
                </h4>
                
                <div class="form-group">
                    <label>
                        <i class="fas fa-calendar-plus"></i> Fecha de Entrada
                    </label>
                    <input type="date" id="fechaEntrada" min="">
                </div>
                
                <div class="form-group">
                    <label>
                        <i class="fas fa-calendar-minus"></i> Fecha de Salida
                    </label>
                    <input type="date" id="fechaSalida" min="">
                </div>
            </div>
            
            <!-- Mensaje de Respuesta -->
            <div class="form-group">
                <label>
                    <i class="fas fa-comment-dots"></i> Mensaje de Respuesta
                </label>
                <textarea 
                    id="mensajeRespuesta" 
                    rows="4" 
                    placeholder="Escriba un mensaje para el practicante explicando su decisión..."
                    required
                ></textarea>
            </div>
            
            <!-- Botones de Acción -->
            <div class="modal-footer">
                <button type="button" onclick="cerrarModalAceptar()" class="btn-cancel">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" class="btn-success" id="btnEnviarRespuesta">
                    <i class="fas fa-paper-plane"></i> Enviar Respuesta
                </button>
            </div>
        </form>
    </div>
</div>

