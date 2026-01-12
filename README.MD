# Sistema de Requisición — Instituto TECNM Campus Monclova

## Descripción

Sistema web para gestionar requisiciones de material y solicitudes internas del Instituto Tecnológico (TECNM — Campus Monclova). Implementado en PHP (sin framework) con frontend en JavaScript, CSS y HTML.

## Características

- Crear, editar y gestionar requisiciones.
- Flujo de aprobación (solicitante → responsable → administración).
- Historial de requisiciones por usuario y por dependencia.
- Búsqueda y filtrado de requisiciones.
- Interfaz web responsiva.

## Tecnologías

- PHP (backend)
- JavaScript (frontend)
- CSS y HTML

## Requisitos

- PHP 7.4+ (ajusta según la versión que uses)
- Servidor web: Apache o Nginx
- MySQL / MariaDB
- (Opcional) Composer si añades dependencias

## Base de datos

- Nombre por defecto: `sistema_requisiciones_itsm`
- Cuando subas el archivo SQL puedes importarlo con:
  ```bash
  mysql -u <usuario> -p sistema_requisiciones_itsm < ruta/al/archivo.sql
  ```

> Nota: No subas credenciales reales al el repositorio. Usa variables de entorno o un archivo de configuración ignorado por Git.

## Instalación (local)

1. Clonar el repositorio:
   ```bash
   git clone https://github.com/Santellana123/requisici-n-.git
   ```
2. Entrar al directorio:
   ```bash
   cd requisici-n-
   ```
3. (Opcional) Instalar dependencias si usas Composer:
   ```bash
   composer install
   ```
4. Crear la base de datos `sistema_requisiciones_itsm`.
5. Importar el SQL cuando lo tengas disponible.
6. Configurar credenciales (por ejemplo en `config.php` o un `.env`):
   ```env
   DB_HOST=localhost
   DB_NAME=sistema_requisiciones_itsm
   DB_USER=tu_usuario
   DB_PASS=tu_contraseña
   ```
7. Configurar el DocumentRoot de tu servidor para apuntar al directorio público (si aplica).
8. Abrir en el navegador: `http://localhost/`.

## Estructura sugerida del proyecto

- `/public` o `/www` — archivos públicos (index.php, assets)
- `/assets` — CSS, JS, imágenes
- `/app` o `/src` — lógica (controladores, modelos)
- `/config` — archivos de configuración
- `/database` — dumps / migraciones

Ajusta esta sección según la estructura real del proyecto.

## Uso básico

- Registro / inicio de sesión (si está implementado)
- Crear nueva requisición y enviarla para aprobación
- Usuarios con rol “responsable” pueden aprobar o rechazar requisiciones
- Panel de administración para revisar todas las requisiciones

## Buenas prácticas

- No subir credenciales reales. Añade `.env` a `.gitignore`.
- Si utilizas Composer, sube `composer.lock` para garantizar reproducibilidad.
- Coloca el archivo SQL en `/database` cuando lo subas y actualiza la sección de Base de datos.

## Contribuir

1. Haz fork del repositorio
2. Crea una rama `feature/nombre-descriptivo`
3. Realiza commits claros y con mensajes descriptivos
4. Haz push y abre un Pull Request describiendo los cambios y cómo probarlos

## Licencia

Indica la licencia que prefieras (por ejemplo MIT). Si quieres, puedo añadir un archivo `LICENSE` con la licencia MIT.

## Contacto

Autor: Santellana123
Email: (opcional — añade si quieres mostrarlo)

---

Commit generado automáticamente por la asistente a petición del autor.
