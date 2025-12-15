# Gestor de Practicantes

## Descripción
El **Gestor de Practicantes** es una aplicación diseñada para gestionar de manera eficiente los practicantes en una organización. Incluye funcionalidades como:
- Gestión de usuarios y roles.
- Registro de asistencias.
- Generación de reportes y certificados.
- Seguridad avanzada con validación de entradas, hashing de contraseñas y protección contra ataques comunes.

---

## Requisitos

### Software Necesario
- **XAMPP** (o cualquier servidor con Apache y MySQL).
- **PHP 7.4**.
- **Composer** para la gestión de dependencias.

### Extensiones PHP Requeridas
- `pdo_sqlsrv`
- `openssl`
- `mbstring`
- `zip`
- `gd`

---

## Instalación

1. Clona el repositorio:
   ```bash
   git clone https://github.com/tu-usuario/gestorPracticantes.git
   ```

2. Navega al directorio del proyecto:
   ```bash
   cd gestorPracticantes
   ```

3. Instala las dependencias con Composer:
   ```bash
   composer install
   ```

4. Configura el archivo `.env`:
   - Copia el archivo `.env.example` y renómbralo a `.env`.
   - Configura las credenciales de la base de datos y otros parámetros necesarios.

5. Importa la base de datos:
   - Usa el archivo `script.sql` para crear las tablas y datos iniciales.

6. Inicia el servidor:
   - Ejecuta el script `iniciar_app.bat` para iniciar Apache, MySQL y abrir el proyecto en el navegador.

---

## Uso

- Accede al sistema desde tu navegador en: `http://localhost/gestorPracticantes/public/`.
- Usa las credenciales iniciales configuradas en la base de datos para iniciar sesión.

---

## Estructura del Proyecto

```
app/
├── controllers/        # Controladores de la aplicación
├── models/             # Modelos de datos
├── repositories/       # Lógica de acceso a datos
├── services/           # Lógica de negocio
├── security/           # Clases de seguridad
├── views/              # Vistas (HTML, PHP)
public/                 # Archivos públicos (index.php, assets)
logs/                   # Archivos de registro
vendor/                 # Dependencias instaladas por Composer
tests/                  # Pruebas unitarias e integración
docs/                   # Documentación del proyecto
```

---

## Contribuciones

1. Haz un fork del repositorio.
2. Crea una nueva rama para tus cambios:
   ```bash
   git checkout -b feature/nueva-funcionalidad
   ```
3. Realiza tus cambios y confirma los commits:
   ```bash
   git commit -m "Descripción de los cambios"
   ```
4. Envía tus cambios:
   ```bash
   git push origin feature/nueva-funcionalidad
   ```
5. Crea un Pull Request en GitHub.

---

## Licencia
Este proyecto está bajo la licencia MIT.