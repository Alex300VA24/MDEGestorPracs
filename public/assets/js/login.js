document.addEventListener('DOMContentLoaded', function() {
    const formLogin = document.getElementById('formLogin');
    const formValidarCUI = document.getElementById('validarCUIForm');
    const modalCUI = document.getElementById('modalValidarCUI');
    const btnCancelarCUI = document.getElementById('btnCancelarCUI');
    const btnLogin = document.getElementById('btnLogin');

    // Login
    formLogin.addEventListener('submit', async function(e) {
        e.preventDefault();
        const nombreUsuario = document.getElementById('username').value;
        const password = document.getElementById('password').value;

        try {
            btnLogin.disabled = true;
            btnLogin.textContent = 'Ingresando...';
            
            const response = await api.login(nombreUsuario, password);
            
            if (response.success && response.data.requireCUI) {
                // Mostrar modal de CUI
                modalCUI.style.display = 'block';
                sessionStorage.setItem('usuarioID', response.data.usuarioID);
                //sessionStorage.setItem('areaID', response.data.area.areaID);
                //sessionStorage.setItem('nombreArea', response.data.area.nombreArea);
            }
        } catch (error) {
            mostrarAlerta({tipo:'error', titulo: 'Error', mensaje:error.message });
            btnLogin.disabled = false;
            btnLogin.textContent = 'Ingresar';
        }
    });

    // Validar CUI
    formValidarCUI.addEventListener('submit', async function(e) {
        e.preventDefault();

        const cui = document.getElementById('cui').value;
        if (!cui || cui.length < 1) {
            mostrarAlerta({tipo:'info', mensaje:'Por favor ingrese un CUI válido'});
            return;
        }

        try {
            const btnConfirmar = document.getElementById('btnConfirmarCUI');
            btnConfirmar.disabled = true;
            btnConfirmar.value = 'Validando...';
            
            usuarioID = sessionStorage.getItem('usuarioID');
            const response = await api.validarCUI(usuarioID, cui);
            if (response.success) {
                
                // Renovar datos del usuario desde la respuesta del servidor
                if (response.data && response.data.area) {
                    sessionStorage.setItem('areaID', response.data.area.areaID);
                    sessionStorage.setItem('nombreArea', response.data.area.nombreArea);
                } else {
                    console.warn('⚠️ No hay datos de área en la respuesta:', response.data);
                }

                if (response.data && response.data.usuarioID) {
                    sessionStorage.setItem('usuarioID', response.data.usuarioID);
                }
                if (response.data && response.data.cargoID) {
                    sessionStorage.setItem('cargoID', response.data.cargoID);
                }
                if (response.data && response.data.nombreCargo) {
                    sessionStorage.setItem('nombreCargo', response.data.nombreCargo);
                }
                
                // Limpiar la página guardada para que cargue con página por defecto en dashboard
                localStorage.removeItem('currentPage');
                window.location.href = 'dashboard';
            }
        } catch (error) {
            mostrarAlerta({tipo:'error', titulo: 'Error', mensaje:error.message });
            const btnConfirmar = document.getElementById('btnConfirmarCUI');
            btnConfirmar.disabled = false;
            btnConfirmar.value = 'Confirmar';
        }
    });

    // Cancelar CUI
    if (btnCancelarCUI) {
        btnCancelarCUI.addEventListener('click', function() {
            if (modalCUI) {
                modalCUI.style.display = 'none';
            } else {
                console.warn('No se encontró el modal con id="modalValidarCUI"');
            }
            sessionStorage.clear();
            btnLogin.disabled = false;
            btnLogin.textContent = 'Ingresar';
        });
    }
});


