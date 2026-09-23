# Guía de Despliegue a Producción — MadBarzz

Checklist para desplegar en un servidor Linux (pensado para Oracle Cloud,
pero aplicable a cualquier VPS). Marcá cada paso a medida que lo completás.

> Antes de empezar: leé también [RESTAURACION.md](RESTAURACION.md) (backups)
> y la sección "Pendientes de seguridad" al final de este documento.

---

## 1. Provisionar el servidor

- [ ] PHP 8.2 o superior, con extensiones: `mbstring`, `xml`, `curl`, `zip`,
      `bcmath`, `gd` (o `imagick`), `pdo_mysql`, `fileinfo`.
- [ ] MySQL o MariaDB.
- [ ] Composer.
- [ ] Node.js + npm (solo para compilar los assets; puede hacerse en CI y
      subir el build en vez de instalar Node en el servidor).
- [ ] Servidor web (Nginx o Apache) apuntando a `public/` como document root.
- [ ] `unzip` disponible (lo usa `spatie/laravel-backup` al listar/verificar).

## 2. Obtener el código y las dependencias

- [ ] Clonar el repositorio en el servidor.
- [ ] Instalar dependencias sin las de desarrollo, con autoloader optimizado:
  ```bash
  composer install --no-dev --optimize-autoloader
  ```
- [ ] Compilar los assets del frontend:
  ```bash
  npm install
  npm run build
  ```

## 3. Archivo `.env` de producción

- [ ] Copiar `.env.example` a `.env` y completar:
  - `APP_ENV=production`
  - `APP_DEBUG=false` (el `.env.example` ya trae este valor por defecto — no
    tocarlo)
  - `APP_URL=https://tudominio.com` (dominio real, con https)
  - `APP_KEY` — **⚠️ leé primero la sección "Secretos críticos" más abajo antes de tocar esto.**
    Se genera con `php artisan key:generate` (paso 4) SOLO si este servidor es una instalación nueva
    sin datos que restaurar. Si estás migrando o recuperando un sistema que ya existía, hay que
    restaurar la `APP_KEY` ORIGINAL en vez de generar una — ver
    [RESTAURACION.md](RESTAURACION.md) § 5b.
  - `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` reales
  - `MAIL_MAILER` y credenciales reales (no `log`) — necesario para
    verificación de email y avisos
  - `VAPID_PUBLIC_KEY` / `VAPID_PRIVATE_KEY` — generar con
    `\Minishlink\WebPush\VAPID::createVapidKeys()` (ver comentario en
    `.env.example`)
  - `TELEGRAM_BOT_TOKEN` si se usa el canal de Telegram
  - `NOTIF_CANALES` — canales de notificación activos (`email`, `telegram`,
    `whatsapp`, `push`)
  - `SESSION_SECURE_COOKIE=true` — **no está en `.env.example`, agregarla a
    mano en producción** (ver sección de pendientes de seguridad)
  - `LOG_STACK=daily` y `LOG_LEVEL=error` (en vez de los valores locales
    `single` / `debug`)
  - Credenciales de backup externo: `B2_KEY_ID`, `B2_APPLICATION_KEY`,
    `B2_REGION`, `B2_BUCKET`, `B2_ENDPOINT` (ver
    [RESTAURACION.md](RESTAURACION.md) § "Configurar Backblaze B2") y
    `BACKUP_MAIL_TO` con el email que debe recibir alertas de backup fallido
  - **`BACKUP_ARCHIVE_PASSWORD` — OBLIGATORIA antes de activar los backups a
    la nube.** El dump incluye datos personales y huellas dactilares; sin
    esta variable, el ZIP sube a Backblaze B2 sin cifrar. Pasos:
    1. Generar una contraseña fuerte (gestor de contraseñas o
       `openssl rand -base64 32`).
    2. Ponerla en `BACKUP_ARCHIVE_PASSWORD` en el `.env` del servidor.
    3. Guardar una copia de esa contraseña **fuera del servidor** (gestor de
       contraseñas del dueño, papel en un lugar seguro, o drive privado) — si
       se pierde, ningún backup existente se puede volver a abrir, no hay
       forma de recuperarlos.
    4. El cifrado lo activa automáticamente `spatie/laravel-backup` en cuanto
       la variable tiene un valor (ver `'password' => env('BACKUP_ARCHIVE_PASSWORD')`
       en `config/backup.php`) — no hace falta ningún otro cambio de código.

### Secretos críticos que el dueño debe custodiar

Dos valores del `.env` de producción, si se pierden o se cambian sin querer, causan una pérdida de
datos **irreversible** — no hay forma de recuperarlos después, ni con soporte técnico ni con más
backups. El dueño del gimnasio (no solo quien administra el servidor) debería tener una copia de
ambos en un lugar seguro y **separado** de donde se guardan los backups de la base de datos —
guardar una clave junto con lo que esa clave protege anula la protección.

- **`APP_KEY`** — la clave con la que Laravel cifra los datos sensibles reversibles en la base de
  datos (a futuro: la plantilla de huella dactilar, una vez aplicado el cifrado planeado en el
  Grupo 2 de la auditoría — hoy todavía no hay ningún dato usando el cast `encrypted`). Si `APP_KEY`
  cambia después de que haya datos cifrados con la clave anterior, esos datos quedan **ilegibles
  para siempre**: no es "la perdí y genero otra", es criptográficamente irrecuperable. Una vez en
  producción con datos cifrados:
  - **Nunca correr `php artisan key:generate` de nuevo** sobre esa instalación.
  - Guardar una copia de la línea completa `APP_KEY=...` del `.env` en un gestor de contraseñas, en
    papel en un lugar seguro, o en un drive privado del dueño — nunca solo en el servidor ni dentro
    de un backup de la base de datos.
  - Si alguna vez hay que recuperar el sistema en otro servidor, hay que restaurar esa misma
    `APP_KEY`, no generar una nueva — ver [RESTAURACION.md](RESTAURACION.md) § 5b.
- **`BACKUP_ARCHIVE_PASSWORD`** — la contraseña que cifra el ZIP de cada backup antes de subirlo a
  Backblaze B2 (ver arriba). Si se pierde, ningún backup existente se puede volver a abrir. Mismo
  criterio de resguardo que `APP_KEY`: copia aparte, fuera del servidor.

Ninguno de estos dos valores debería vivir solo en el `.env` del servidor: si el servidor se pierde
(falla de hardware, ransomware, error humano), se pierde también la única copia, y con ella el
acceso a los datos que protegen.

## 4. Aplicación: clave, migraciones y datos iniciales

- [ ] Generar la clave de la aplicación — **SOLO si este es un servidor nuevo sin datos previos que
  restaurar**:
  ```bash
  php artisan key:generate
  ```
  ⚠️ Si en cambio estás migrando o recuperando un sistema que ya existía (hay un backup de por
  medio), **NO corras este comando** — restaurá la `APP_KEY` original en el `.env` en su lugar (ver
  la sección "Secretos críticos" arriba y [RESTAURACION.md](RESTAURACION.md) § 5b). Generar una
  clave nueva sobre datos que ya tenían algo cifrado con la clave vieja los vuelve ilegibles para
  siempre.
- [ ] Migrar la base de datos (**nunca con `--seed`** — cargaría datos falsos
  y contraseñas fijas de demo en producción):
  ```bash
  php artisan migrate --force
  ```
- [ ] Crear roles, la sede inicial y el primer Administrador real con el
  seeder dedicado (contraseña generada al azar, se muestra una sola vez en
  consola):
  ```bash
  php artisan db:seed --class=ProduccionInicialSeeder
  ```

## 5. Storage y permisos

- [ ] Enlazar el disco público:
  ```bash
  php artisan storage:link
  ```
- [ ] Si es una migración desde otro servidor, copiar/restaurar
  `storage/app/public` y `storage/app/private` (ver
  [RESTAURACION.md](RESTAURACION.md) § 4).
- [ ] Permisos de escritura para el usuario del servidor web:
  ```bash
  chmod -R 775 storage bootstrap/cache
  chown -R www-data:www-data storage bootstrap/cache
  ```

## 6. Cachés de Laravel

- [ ] Cachear configuración, rutas y vistas:
  ```bash
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```
- [ ] Recordar: si volvés a tocar `.env` después de esto, hay que correr
  `php artisan config:cache` de nuevo (o `config:clear` si vas a depurar).

## 7. Tareas programadas y cola de trabajos

- [ ] Agregar el cron del scheduler (una sola línea, corre todo lo definido
  en `routes/console.php`: backups, limpieza de bitácora, notificaciones de
  vencimiento/saldo pendiente):
  ```
  * * * * * cd /var/www/madbarzz && php artisan schedule:run >> /dev/null 2>&1
  ```
- [ ] **Levantar un worker de colas persistente** (Supervisor o systemd) —
  ⚠️ **sin esto, las notificaciones (email/Telegram/WhatsApp/push) y otros
  jobs en cola no se envían nunca**, quedan encolados sin procesar:
  ```bash
  php artisan queue:work --tries=3
  ```
  Ejemplo mínimo de unit de systemd:
  ```ini
  [Unit]
  Description=MadBarzz queue worker
  After=network.target

  [Service]
  User=www-data
  WorkingDirectory=/var/www/madbarzz
  ExecStart=/usr/bin/php artisan queue:work --tries=3 --sleep=3
  Restart=always

  [Install]
  WantedBy=multi-user.target
  ```

## 8. Configurar el agente biométrico

- [ ] Generar el token del agente para la sede correspondiente:
  ```bash
  php artisan agente:token {sede_id} --nombre="Agente Sede Central"
  ```
  El comando muestra el token en texto plano **una sola vez** — copiarlo en
  ese momento (se guarda hasheado en `agent_tokens.token`, no se puede volver
  a mostrar).
- [ ] En la aplicación de escritorio (C#) del gimnasio, configurar:
  - URL del servidor (`https://tudominio.com/api/agente`)
  - El token generado, como header `Authorization: Bearer <token>`
- [ ] Probar que el agente lista clientes (`GET /api/agente/clientes`) antes
  de dejarlo enrolando huellas en producción.

## 9. Pendientes de seguridad para producción (de la auditoría)

Estos puntos fueron identificados en la revisión de seguridad y **deben
resolverse antes o poco después del lanzamiento**:

- [ ] `SESSION_SECURE_COOKIE=true` en el `.env` de producción (no viene
  seteada por defecto; sin esto la cookie de sesión viaja también por HTTP).
- [ ] Headers de seguridad HTTP (`Strict-Transport-Security`,
  `X-Content-Type-Options`, `X-Frame-Options` / `frame-ancestors`,
  `Referrer-Policy`) — el proyecto no los agrega todavía, evaluar middleware
  propio o a nivel del servidor web/proxy.
- [ ] CORS restringido — no hay configuración de CORS publicada
  (`config/cors.php`) en el repo; si el portal del cliente vía API
  (`routes/api.php`) va a ser consumido desde otro dominio/app, restringir
  orígenes explícitamente en vez de dejar el default de Laravel.
- [ ] CAPTCHA (Cloudflare Turnstile u otro) en el registro público de
  clientes, como capa adicional al límite de 5 registros/minuto por IP ya
  existente.

## 10. Pendiente legal

- [ ] Confirmar la licencia comercial del dataset de ejercicios (Gym Visual,
  usado por `php artisan ejercicios:importar` / `EjercicioController`) antes
  de operar el sistema con clientes reales, si el dataset se distribuye o
  expone públicamente.

## 11. Pruebas post-despliegue

Antes de considerar el despliegue terminado, verificar manualmente:

- [ ] Login con el Administrador creado en el paso 4.
- [ ] Registro público de un cliente de prueba + verificación de email real
  (confirma que `MAIL_MAILER` quedó bien configurado).
- [ ] Registrar un pago de prueba (confirma DB + generación de comprobante).
- [ ] El agente biométrico marcando una asistencia real contra
  `/api/agente/asistencia`.
- [ ] Backup manual exitoso:
  ```bash
  php artisan backup:run --disable-notifications
  php artisan backup:list
  ```
