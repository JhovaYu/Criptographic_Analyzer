# Caso práctico: cifrado y firma de documentos

Este trabajo amplía la **API de documentos confidenciales** incluida en el repositorio; es independiente de la aplicación MVC de turnos ubicada en `index.php`. La demostración usa datos inventados.

## Qué se implementó

- **Cifrado simétrico:** `api/Helpers/DocumentCrypto.php` usa OpenSSL de PHP con AES-256-GCM. El contenido se guarda como base64 de `IV (12 bytes) + etiqueta de autenticación (16 bytes) + texto cifrado`. Cada guardado usa un IV nuevo. Al consultar, GCM descifra y detecta alteraciones. La clave secreta de 32 bytes procede de `DOCUMENT_AES_KEY`.
- **Firma asimétrica:** se firma el título y el contenido original con una clave privada RSA de 3072 bits, mediante `openssl_sign` y SHA-256. Al leer, `openssl_verify` usa la clave pública para comprobar si el título o el contenido cambiaron. Es una firma RSA con SHA-256; **no cifra** el documento. La clave privada y la pública se leen de las rutas indicadas en el entorno.
- La app muestra el resultado de la verificación. Si falla el descifrado o la configuración, no expone el contenido y muestra un error. Los documentos anteriores a esta actualización se muestran como **anteriores, sin cifrado ni firma**; esta actualización no los convierte automáticamente.
- El título, autor y fecha no se cifran. La firma incluye título y contenido, no el autor ni la fecha. La firma acredita la integridad bajo la clave configurada, no identifica por sí sola a una persona.
- `password_hash()` sigue protegiendo contraseñas: es un hash, no cifrado reversible. El JWT existente utiliza HS256 y no es la función asimétrica de este ejercicio.

## Preparación para demostración local

Necesitas PHP 8 con extensiones `openssl` y `pdo_pgsql`, PostgreSQL y un navegador. Usa una **base nueva**: los otros SQL del repositorio corresponden a otra versión del sistema y `db_backup.sql` contiene datos anteriores. Los comandos siguientes son ejemplos; sustituye el usuario, la ruta de PHP y la contraseña según tu instalación. En Windows puedes ejecutar los comandos en PowerShell o usar pgAdmin para ejecutar los SQL en el orden indicado.

1. Clona el repositorio y abre una terminal en la carpeta del proyecto. Si el cambio está en una rama separada, cámbiate a esa rama antes de seguir.
2. Crea una base PostgreSQL vacía llamada `caso_cripto`. En pgAdmin: clic derecho en Databases → Create → Database. Ejecuta en ella, en este orden:

   1. `schema_v2.sql`
   2. `schema_updates.sql`
   3. `schema_content.sql`
   4. `schema_crypto.sql`

   Alternativa con `psql`: `psql -U postgres -d caso_cripto -f schema_v2.sql` y repite para cada archivo. `schema_updates.sql` es para la primera instalación: no lo ejecutes de nuevo sobre la misma base sin revisar las columnas existentes. En una base ya configurada para la API, respáldala y aplica solo `schema_crypto.sql` después de comprobar su esquema.

3. Genera claves **fuera de la carpeta del proyecto y fuera de la carpeta pública del servidor web**. Ejemplo Linux/macOS:

   ```bash
   php scripts/generar_claves.php "$HOME/claves-caso-cripto"
   ```

   En PowerShell: `php scripts/generar_claves.php "$env:USERPROFILE\claves-caso-cripto"`. El script se niega a sobrescribir claves existentes. Si `php` no está en el PATH, usa la ruta del ejecutable de tu instalación.

4. Copia `.env.example` a `.env`. Configura `DB_HOST`, `DB_PORT`, `DB_NAME=caso_cripto`, `DB_USER` y `DB_PASSWORD`. Copia `JWT_SECRET` y `DOCUMENT_AES_KEY` desde el archivo `secretos.txt` generado; establece las rutas absolutas mostradas por el script en `DOCUMENT_PRIVATE_KEY_PATH` y `DOCUMENT_PUBLIC_KEY_PATH`. No copies las claves al repositorio. Conserva las mismas claves entre reinicios: si se pierde la clave AES, no podrás recuperar los documentos; si se cambia la pareja RSA, la firma anterior no se verificará con la nueva pública.
5. En la misma carpeta ejecuta `php api/seed_admin.php`. Para la demostración local, el código original crea el usuario de prueba `admin@admin.com` con contraseña `admin`. Es una cuenta de prueba débil: no publiques este entorno ni lo uses con datos reales.
6. Inicia el servidor local: `php -S 127.0.0.1:8000 router_demo.php`. Abre `http://127.0.0.1:8000/frontend/index.html`, inicia sesión y entra a documentos desde el panel de administración. Mantén la terminal abierta.
7. Prueba opcional de funciones criptográficas: `php tests/crypto_test.php`.

## Guion para la demo

1. En documentos, crea título `Nota de prueba` y contenido inventado `La reunión será el martes a las 10:00.`. Guarda y muestra cómo aparece recuperado junto a **Firma digital válida**.
2. En pgAdmin, abre Query Tool sobre `caso_cripto` y ejecuta:

   ```sql
   SELECT id, title, content, signature, crypto_version
   FROM documents ORDER BY id DESC LIMIT 1;
   ```

   Explica que `content` es el contenido cifrado codificado en base64, `signature` es la firma y `crypto_version=1` identifica el nuevo formato. Base64 es una codificación, no el cifrado; el cifrado es AES-GCM. El título se ve porque se decidió no cifrarlo.
3. Para demostrar la firma, modifica **solo un documento inventado**. Anota antes el ID y el título original; sustituye `1` por su ID:

   ```sql
   UPDATE documents SET title = 'Título alterado' WHERE id = 1;
   ```

   Recarga la aplicación: debe decir **Firma digital inválida**. Restaura el título original en SQL y recarga; debe volver a válida. La demostración cambia la base de datos, por lo que debe hacerse únicamente en la base de prueba.
4. Muestra `api/Helpers/DocumentCrypto.php`, `api/Controllers/ContentController.php` y `.env.example`. Explica que OpenSSL implementa los algoritmos y que las claves reales se generan aparte, se guardan fuera del proyecto y no se muestran en pantalla ni se suben a GitHub.

## Alcance y precauciones

La firma protege título y contenido de cada registro contra cambios detectables, pero no impide que un administrador de BD borre registros o reemplace uno entero por otro previamente válido. AES-GCM también detecta alteraciones del cifrado. Esta implementación no pretende ser un sistema de producción ni resolver rotación de claves, recuperación de claves, control granular de documentos o identidad por certificado. Mantén el servidor solo en `127.0.0.1`, usa datos ficticios y evita los scripts de semillas antiguas para la práctica.
