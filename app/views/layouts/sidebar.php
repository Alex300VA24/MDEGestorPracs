<div class="sideBar">
    <div>
        <div class="sidebar-header">
            <img class="sidebar-logo" src="<?= BASE_URL ?>assets/images/logo.png" alt="Logo">
            <div class="sidebar-title">MDE</div>
            <div class="sidebar-subtitle">Gestión de <?= getenv('ETIQUETA') ?>s</div>
        </div>

        <?php
        // === Obtener datos de sesión ===
        $cargo = $_SESSION['nombreCargo'] ?? '';
        $area = $_SESSION['nombreArea'] ?? '';

        // === Definir permisos según el cargo ===
        $accesoTotal = ($cargo === 'Gerente RRHH');
        $accesoArea = ($cargo === 'Gerente de Área');
        $accesoBasico = ($cargo === 'Usuario de Área');
        $accesoAreaUsuario = ($cargo === 'Gerente de Sistemas');
        ?>

        <!-- 🔹 Todos los usuarios ven Inicio -->
        <div class="option active" onclick="showPage('inicio', this)">
            <div class="containerIconOption"><i class="fas fa-home"></i></div>
            <p>Inicio</p>
        </div>

        <?php if ($accesoArea || $accesoTotal || $accesoAreaUsuario): ?>
            <div class="option" onclick="showPage('practicantes', this)">
                <div class="containerIconOption"><i class="fas fa-users"></i></div>
                <p>Practicantes</p>
            </div>
        <?php endif; ?>

        <!-- 🔹 Acceso básico: Practicantes y Asistencias -->
        <?php if ($accesoBasico || $accesoArea || $accesoTotal || $accesoAreaUsuario): ?>

            <div class="option" onclick="showPage('asistencias', this)">
                <div class="containerIconOption"><i class="fas fa-calendar-check"></i></div>
                <p>Asistencias</p>
            </div>
        <?php endif; ?>

        <!-- 🔹 Gerente de RRHH -->
        <?php if ($accesoTotal): ?>
            <div class="option" onclick="showPage('documentos', this)">
                <div class="containerIconOption"><i class="fas fa-file-alt"></i></div>
                <p>Documentos</p>
            </div>
        <?php endif; ?>

        <!-- 🔹 Solo Gerente RRHH -->
        <?php if ($accesoTotal): ?>
            <div class="option" onclick="showPage('reportes', this)">
                <div class="containerIconOption"><i class="fas fa-chart-bar"></i></div>
                <p>Reportes</p>
            </div>

            <div class="option" onclick="showPage('certificados', this)">
                <div class="containerIconOption"><i class="fas fa-certificate"></i></div>
                <p>Certificados</p>
            </div>
        <?php endif; ?>

        <?php if ($accesoAreaUsuario): ?>
            <div class="option" onclick="showPage('usuarios', this)">
                <div class="containerIconOption"><i class="fas fa-users-cog"></i></div>
                <p>Usuarios</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- === Modal de Confirmación de Cierre de Sesión === -->
    <div id="logoutModal" class="modal-overlay hidden">
        <div class="modal-content">
            <h3>¿Estás seguro que quieres cerrar sesión?</h3>
            <p>Tu sesión actual se cerrará y volverás a la pantalla de inicio de sesión.</p>
            <div class="modal-buttons">
                <button id="cancelLogout" class="btn-cancel">Cancelar</button>
                <button id="confirmLogout" class="btn-logout">Cerrar sesión</button>
            </div>
        </div>
    </div>
    

    <!-- === Información del usuario === -->
    <div class="user-info">
        <div><strong id="currentUserName"><?= htmlspecialchars($_SESSION['nombreUsuario'] ?? '') ?></strong></div>
        <div id="currentUserRole">
            <?= htmlspecialchars($cargo) ?><?= $area ? " - " . htmlspecialchars($area) : '' ?>
        </div>

        <button class="logout-btn" id="btnLogout">
            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
        </button>
    </div>
</div>
