<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['authenticated']) && $_SESSION['authenticated']) {
    header('Location: ' . BASE_URL . 'dashboard');
    exit;
}

// Variable para etiquetas
$etiqueta = 'Practicante';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Gestión de Practicantes</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/login.css">
    <link rel="stylesheet" href='<?= BASE_URL ?>assets/css/fontawesome/css/all.min.css'">
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <img class="logo-login" src="<?= BASE_URL ?>assets/images/logo.png" alt="logo MDE">
        </div>
        <div class="login-right">
            <h2 class="login-title">SISTEMA DE GESTIÓN DE PRACTICANTES</h2>
            <p>Ingresa tus datos para iniciar sesión</p>
            <form id="formLogin" method="post">
                <label for="username">Usuario:</label>
                <input type="text" id="username" name="username" autocomplete="off" placeholder="ejemplo: alopezv" required>

                <label for="password">Contraseña:</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" placeholder="******" required>
                    <i id="togglePassword" class="fas fa-eye-slash"></i>
                </div>
                <br>

                <button id="btnLogin" class="login-btn" type="submit">
                    <i class="fas fa-sign-in-alt"></i>
                    Ingresar
                </button>
            </form>
        </div>
    </div>

    <!-- Modal CUI -->
    <div id="modalValidarCUI" class="modalCUI modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Autenticación de Doble Factor - CUI</h5>
                </div>
                <form class="formValidarCUI" id="validarCUIForm" method="post">
                    <div class="modal-body">
                        <div class="containerGuiaCUI">
                            <p>
                                Busca tu código único de Identificación (CUI) en tu DNI e ingrésalo en el cuadro de abajo.
                            </p>
                            <img src="<?= BASE_URL ?>assets/images/dniGuiCUI.svg" alt="Guía CUI DNI">
                        </div>

                        <div class="containerCUI">
                            <label for="cui" class="form-label">Código único de Identificación (CUI):</label>
                            <input type="text" id="cui" maxlength="1" autocomplete="off" required>
                        </div>
                    </div>
                    <div class="containerButtonsModals">
                        <input type="submit" id="btnConfirmarCUI" class="btn btn-submit" value="Confirmar">
                        <input type="button" id="btnCancelarCUI" class="btn btn-cancel" value="Cancelar">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="<?= BASE_URL ?>assets/js/api.js"></script>
    <script src="<?= BASE_URL ?>assets/js/login.js"></script>
    <script src="<?= BASE_URL ?>assets/js/sweetalert2/dist/sweetalert2.all.min.js"></script>

    <script>
        // Toggle password
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
            this.classList.toggle('fa-eye');
        });
    </script>
</body>
</html>
