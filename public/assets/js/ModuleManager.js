// moduleManager.js - Sistema de gestión de ciclo de vida de módulos

class ModuleManager {
    constructor() {
        this.modules = new Map();
        this.currentModule = null;
        this.initialized = false;
    }

    /**
     * Registra un nuevo módulo
     * @param {string} name - Nombre del módulo
     * @param {Object} moduleDefinition - Definición del módulo con métodos init, destroy, etc.
     */
    register(name, moduleDefinition) {
        if (this.modules.has(name)) {
            console.warn(`⚠️ Módulo "${name}" ya está registrado. Se sobrescribirá.`);
        }
        
        this.modules.set(name, {
            definition: moduleDefinition,
            instance: null,
            state: 'unloaded' // unloaded, loading, loaded, error
        });
        
    }

    /**
     * Carga e inicializa un módulo
     */
    async load(moduleName) {
        const module = this.modules.get(moduleName);
        
        if (!module) {
            console.error(`❌ Módulo "${moduleName}" no encontrado`);
            return false;
        }

        // Si ya está cargado, no hacer nada
        if (module.state === 'loaded' && module.instance) {
            return true;
        }

        // Si está en proceso de carga, esperar
        if (module.state === 'loading') {
            return false;
        }

        try {
            module.state = 'loading';

            // Destruir módulo anterior si existe
            if (this.currentModule && this.currentModule !== moduleName) {
                await this.unload(this.currentModule);
            }

            // Crear instancia del módulo
            const instance = await module.definition.init();
            
            module.instance = instance;
            module.state = 'loaded';
            this.currentModule = moduleName;

            return true;

        } catch (error) {
            module.state = 'error';
            console.error(`❌ Error al cargar módulo "${moduleName}":`, error);
            
            mostrarAlerta({
                tipo: 'error',
                titulo: 'Error al cargar módulo',
                mensaje: `No se pudo cargar ${moduleName}: ${error.message}`
            });
            
            return false;
        }
    }

    /**
     * Descarga y limpia un módulo
     */
    async unload(moduleName) {
        const module = this.modules.get(moduleName);
        
        if (!module || !module.instance) {
            return;
        }

        try {

            // Llamar al método destroy si existe
            if (module.definition.destroy && module.instance) {
                await module.definition.destroy(module.instance);
            }

            module.instance = null;
            module.state = 'unloaded';

            if (this.currentModule === moduleName) {
                this.currentModule = null;
            }

        } catch (error) {
            console.error(`❌ Error al limpiar módulo "${moduleName}":`, error);
        }
    }

    /**
     * Recarga un módulo
     */
    async reload(moduleName) {
        await this.unload(moduleName);
        return await this.load(moduleName);
    }

    /**
     * Obtiene la instancia de un módulo cargado
     */
    getInstance(moduleName) {
        const module = this.modules.get(moduleName);
        return module?.instance || null;
    }
}

// Instancia global del gestor
window.moduleManager = new ModuleManager();