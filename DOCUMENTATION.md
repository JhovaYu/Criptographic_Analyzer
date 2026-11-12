# Documentación del Sistema de Gestión de Turnos para Eventos

Este proyecto es una plataforma web para la gestión de turnos (fila de oradores) en múltiples eventos de forma simultánea. Está construido utilizando **PHP Nativo**, aplicando el patrón de diseño **MVC** (Modelo-Vista-Controlador) y **Singleton** para la conexión a base de datos **PostgreSQL**.

## 1. Arquitectura del Proyecto

El sistema no utiliza un framework backend completo (como Laravel), sino un enfoque MVC artesanal estructurado de la siguiente manera:

- **`index.php`**: El punto de entrada único (Front Controller). Se encarga de capturar el parámetro `action` por GET/POST y rutear a los controladores pertinentes (`UserController`, `EventController`, `AdminController`, `TurnoController`).
- **`config/`**: Contiene la lógica transversal.
  - `Database.php`: Usa el patrón **Singleton** para garantizar que solo exista una única conexión a la base de datos PostgreSQL a lo largo del ciclo de vida de la petición.
- **`models/`**: Contiene la lógica de negocio y las consultas directas a la base de datos.
  - `User.php`: Lógica de autenticación, registro, perfil y sesiones.
  - `Event.php`: Operaciones de creación (CRUD), acceso a eventos usando el `event_code`, comprobación de contraseñas y filtrado de eventos activos/eliminados (soft delete).
  - `Turno.php`: Lógica de encolamiento, posiciones relativas, y permisos para vaciar, eliminar o avanzar turnos.
- **`controllers/`**: Reciben los inputs del usuario, orquestan a los modelos, gestionan redirecciones y cargan las vistas.
- **`views/`**: Interfaces gráficas (HTML/CSS) mezcladas con pequeñas incrustaciones PHP para el renderizado. Emplean una **Estética Oscura Unificada** (Premium UI, interacciones glassmorphism, flexbox/grid moderno).

## 2. Base de Datos (`schema.sql`)

La persistencia de datos descansa en 5 tablas fundamentales en PostgreSQL:

1. **`roles`**: Catálogo que define 1=Administrador, 2=Moderador, 3=Asistente.
2. **`users`**: Centraliza a los usuarios. Utiliza contraseñas encriptadas (`password_hash`) y mantiene un historial y control de sesiones (`session_token`).
3. **`events`**: Contiene la información del evento. Cuenta con un **Código de evento único autogenerado (`event_code`)**, contraseña opcional, fechas de vigencia, un switch de estado simulado y un indicador de `is_active` (para hacer "soft deletes").
4. **`event_participants`**: Tabla asociativa (Pivote). Relaciona a un `user_id` con un `event_id` y define qué rol jugará el usuario *específicamente dentro de ese evento*. 
5. **`turnos`**: Registra la fila de oradores (quién pide la palabra, en qué evento, y el timestamp de cuándo lo solicitó).

## 3. Roles y Permisos (Contexto Jerárquico)

### Administrador (Rol 1)
- **Acceso**: Entra directamente a `panel_admin.php`.
- **Habilidades**: Posee privilegios globales. Puede ver *todos* los eventos del sistema, activos o en la papelera, mediante la integración de **DataTables** (permite agrupar dinámicamente y filtrar).
- **Acciones Extremas**: Es el único rol capaz de aplicar un "Hard Delete" (Borrado físico de la BD) o restaurar un evento.
- **Intervención**: Puede entrar a cualquier evento como moderador para vigilar o administrar la fila. Las credenciales completas de la sala se muestran en su Dashboard.

### Moderador (Rol 2)
- **Acceso**: Entra a un Lobby unificado (`lobby.php`).
- **Habilidades**: Puede **crear eventos**. Al hacerlo, se autogenera un código único encriptado que pueden compartir con los asistentes, evitando colisiones `500`. 
- **Gestión**: Una vez dentro del evento (`panel_moderador.php`), tienen control de la fila en tiempo real. Pueden "Dar palabra al siguiente", "Expulsar turno", y "Limpiar la fila entera". Poseen un "Soft Delete" para borrar sus eventos de forma lógica de la lista global.

### Asistente (Rol 3)
- **Acceso**: Es el rol por defecto en registros públicos. Entran al mismo `lobby.php` que los moderadores, pero solo ven los eventos a los que se han unido.
- **Unirse**: Utilizan el código del evento autogenerado para entrar. Si tiene contraseña (opcional), deben proporcionarla.
- **Interacción**: En `panel_asistente.php`, visualizan su posición en la fila frente a otros usuarios, pueden "Pedir la palabra" y configurar la información pública visible para el moderador (Nombre, Institución, Cargo, Suplencia).

## 4. Funcionamiento de la API de Turnos (Tiempo Real)

Para lograr un sistema asíncrono y en "tiempo real" sin sobrecargar el uso de recargas del navegador, se implementó `TurnoController.php` como un backend pseudo-API (`action=api`). 

Las vistas frontend utilizan `Javascript Vanilla` y la función moderna `fetch()` para realizar *Long polling / Polling regular* (refrescando cada 1.5 - 2 segundos) contra las rutas de la API, retornando JSON. Esto permite a los Moderadores ver instantáneamente cuando alguien pulsa "Pedir Palabra", y a los Asistentes ver cómo avanza su número de fila dinámicamente sin que la experiencia del usuario se vea pausada.

## 5. Notas Críticas de Implementación (Para Agentes AI)

- **Evitar Duplicidad en Foreach**: Durante el procesamiento de eventos o iteración, si se utiliza el pase por referencia (`&$evento`) en un loop de PHP 8, es vital **NO** reutilizar la variable no referenciada en la capa de las vistas para no causar un bug histórico de sobrescritura que corrompe la última línea impresa de las tablas. Utiliza siempre el index (`$events[$key]`) cuando pretendas alterar un JSON de la capa de control de base de datos.
- **Seguridad en Credenciales**: Se utiliza `password_hash()` de forma integral. El generador de códigos de eventos usa `substr(md5(uniqid(rand(), true)), 0, 6)` garantizando unicidad en cadenas cortas estilo `EVT-XQZ43L`.
- **Limpieza de Caché**: Antes de entrar al lobby o al dashboard general, se ejecuta explícitamente `unset($_SESSION['current_event_id'])` para garantizar que no existan conflictos lógicos (fantasma) con el último evento visitado de otras sesiones.
- **Aesthetic First**: Este proyecto tiene reglas explícitas de cumplir con diseños limpios y vanguardistas. Cualquier formulario o panel debe emplear los Custom Properties `--card-bg`, el tipo de fuente de **Google Fonts (Inter)** y usar layouts responsivos `Grid / Flexbox` transparentes, tal y como se hace en el resto de controladores actuales.

---
**Proyecto Mantenido usando MVC nativo y conectores PDO.**
