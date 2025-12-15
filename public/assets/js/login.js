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
                sessionStorage.setItem('areaID', response.data.area.areaID);
                sessionStorage.setItem('nombreArea', response.data.area.nombreArea);
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
            
            const response = await api.validarCUI(cui);
            if (response.success) {
                console.log('✅ Validación CUI exitosa:', response.data);
                
                // Renovar datos del usuario desde la respuesta del servidor
                if (response.data && response.data.area) {
                    sessionStorage.setItem('areaID', response.data.area.areaID);
                    sessionStorage.setItem('nombreArea', response.data.area.nombreArea);
                    console.log('📦 Guardado en sessionStorage - areaID:', response.data.area.areaID, 'nombreArea:', response.data.area.nombreArea);
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
                
                console.log('🔍 Estado de sessionStorage antes de redirect:', {
                    areaID: sessionStorage.getItem('areaID'),
                    nombreArea: sessionStorage.getItem('nombreArea'),
                    usuarioID: sessionStorage.getItem('usuarioID')
                });
                
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

window.mostrarAlerta = function({
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
    
    return Swal.fire({
        icon: tipo,
        title: titulo,
        text: mensaje,
        position: "center",  // siempre centrado
        showConfirmButton,
        showCancelButton,
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        input,
        inputPlaceholder,
        inputValue,
        
        // Esto garantiza que esté encima de cualquier modal
        backdrop: true,
        allowOutsideClick: false,
        allowEscapeKey: false,
    }).then((result) => {
        if (callback) callback(result);
        return result;
    });
};
