// dashboard.js - SOLO manejo de navegación y logout

document.addEventListener('DOMContentLoaded', async () => {
    const BASE_URL = window.location.origin + '/MDEGestorPracs/public/';

    // ==================== NAVEGACIÓN ====================
    
    window.showPage = async function(pageId, element) {
        try {

            // 1. Ocultar todas las páginas
            document.querySelectorAll('.page-content').forEach(p => p.classList.remove('active'));
            
            const pageEl = document.getElementById('page' + capitalize(pageId));
            if (!pageEl) {
                console.error(`❌ Página "${pageId}" no encontrada`);
                return;
            }
            
            pageEl.classList.add('active');

            // 2. Actualizar menú activo
            const optionEl = element ||
                document.querySelector(`.option[data-page="${pageId}"]`) ||
                document.querySelector(`#btn${capitalize(pageId)}`);

            document.querySelectorAll('.option').forEach(o => o.classList.remove('active'));
            if (optionEl) optionEl.classList.add('active');

            // 3. 🔹 Cargar módulo correspondiente usando ModuleManager
            if (window.moduleManager) {
                const success = await window.moduleManager.load(pageId);
                
                if (!success) {
                    console.error(`❌ No se pudo cargar el módulo "${pageId}"`);
                    mostrarAlerta({
                        tipo: 'error',
                        titulo: 'Error',
                        mensaje: `No se pudo cargar el módulo ${pageId}`
                    });
                }
            } else {
                console.error('❌ ModuleManager no está disponible');
            }

            // 4. Guardar página actual
            localStorage.setItem("currentPage", pageId);

        } catch (error) {
            console.error(`❌ Error al cambiar a página "${pageId}":`, error);
            mostrarAlerta({
                tipo: 'error',
                titulo: 'Error de navegación',
                mensaje: 'No se pudo cargar la página solicitada'
            });
        }
    };

    // ==================== HELPERS ====================
    
    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    // ==================== RESTAURAR ÚLTIMA PÁGINA ====================
    
    (function restoreLastPage() {
        const lastPage = localStorage.getItem("currentPage") || 'inicio';
        
        const option = document.querySelector(`.option[data-page="${lastPage}"]`) ||
            document.querySelector(`#btn${capitalize(lastPage)}`);

        // Simular click en la opción del menú para cargar la página
        if (option) {
            option.click();
        } else {
            // Fallback: ir a inicio
            showPage('inicio');
        }
    })();

    // ==================== LOGOUT ====================
    
    const btnLogout = document.getElementById('btnLogout');
    const cancelLogout = document.getElementById('cancelLogout');
    const confirmLogout = document.getElementById('confirmLogout');
    const logoutModal = document.getElementById('logoutModal');

    if (btnLogout) {
        btnLogout.addEventListener('click', () => {
            if (logoutModal) {
                logoutModal.classList.remove('hidden');
                logoutModal.style.display = 'flex';
            }
        });
    }

    if (cancelLogout) {
        cancelLogout.addEventListener('click', () => {
            if (logoutModal) {
                logoutModal.style.display = 'none';
                logoutModal.classList.add('hidden');
            }
        });
    }

    if (confirmLogout) {
        confirmLogout.addEventListener('click', async () => {
            if (logoutModal) {
                logoutModal.style.display = 'none';
                logoutModal.classList.add('hidden');
            }
            
            try {
                await api.logout();
                
                // Limpiar todos los módulos antes de salir
                if (window.moduleManager) {
                    const currentModule = window.moduleManager.currentModule;
                    if (currentModule) {
                        await window.moduleManager.unload(currentModule);
                    }
                }
                
                localStorage.removeItem('currentPage');
                window.location.href = BASE_URL + 'login';
                
            } catch (error) {
                console.error('❌ Error al cerrar sesión:', error);
                mostrarAlerta({
                    tipo: 'error',
                    titulo: 'Error',
                    mensaje: 'Error al cerrar sesión'
                });
            }
        });
    }

    // ==================== UTILIDAD GLOBAL ====================
    
    window.ejecutarUnaVez = async function(boton, accionAsync) {
        if (!boton) {
            return await accionAsync();
        }

        if (boton.disabled) {
            console.warn('⚠️ Botón ya está deshabilitado');
            return;
        }

        boton.disabled = true;
        const textoOriginal = boton.innerHTML;
        boton.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Procesando...`;

        try {
            const resultado = await accionAsync();
            return resultado;
        } catch (error) {
            console.error("❌ Error:", error);
            throw error;
        } finally {
            boton.disabled = false;
            boton.innerHTML = textoOriginal;
        }
    };
});