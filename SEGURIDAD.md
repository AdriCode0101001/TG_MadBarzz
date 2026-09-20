# Seguridad — MadBarzz

Resumen de las medidas de seguridad implementadas en el sistema, a raíz de
varias rondas de auditoría de seguridad y de una revisión de
production-readiness. Pensado como referencia rápida (por ejemplo, para la
sección de seguridad de la tesis); no reemplaza el detalle de cada hallazgo.

## Autenticación y control de acceso

- **Login con bloqueo progresivo**: `LoginLockoutService` bloquea por
  combinación login+IP tras varios intentos fallidos, con backoff creciente
  (3 → 6 → 12 → 15 minutos). Aplica tanto al login web como al de la API
  (`AuthApiController`), no solo al formulario Blade.
- **Límite de peticiones por IP** (`bootstrap/app.php`) como respaldo
  volumétrico, independiente del bloqueo progresivo por cuenta:
  - `login`: 30/min por IP.
  - `register`: 5/min por IP.
  - `password-email` (recuperación de contraseña): 5/min por IP.
- **Recuperación de contraseña con respuesta neutral**: no revela si un
  email existe o no en el sistema.
- **Contraseñas**: longitud mínima de 8 caracteres, hasheadas con bcrypt
  (`BCRYPT_ROUNDS`).
- **Autorización por rol**: middleware `role:` (`EnsureRole`) en
  `routes/web.php`, que solo deja pasar si el usuario autenticado tiene uno
  de los roles permitidos para esa ruta (Administrador, Recepcionista,
  Entrenador, Cliente). Ver la tabla de roles en el [README](README.md).
- **Aislamiento por sede**: los modelos que pertenecen a una sede usan
  `BelongsToSede` + `SedeScope`, que filtran automáticamente las consultas
  por la sede del usuario logueado — el personal de una sede no ve datos de
  otra sin que cada controlador tenga que acordarse de filtrar a mano.
- **Verificación de email obligatoria** para el portal del Cliente (rol
  `Cliente` + middleware `verified`): un cliente que se autoregistra puede
  loguearse, pero no accede a membresía/pagos/asistencias/rutina hasta
  verificar su correo.
- **Panel de bloqueos de login**: el Administrador puede ver y liberar
  manualmente bloqueos activos (`login_lockouts`).

## API y agente biométrico

- **Agente biométrico con token propio por sede**: el agente C# no usa las
  credenciales de ningún usuario ni Sanctum — se autentica con un token
  aleatorio de 60 caracteres, generado una sola vez (`php artisan
  agente:token`) y almacenado **hasheado con SHA-256** en `agent_tokens`
  (nunca en texto plano en la base de datos).
- **Portal del cliente vía API** protegido con Laravel Sanctum
  (`auth:sanctum`), independiente del token del agente.
- **Límite específico en `/api/login`** (`throttle:login`), además del
  bloqueo progresivo por cuenta ya mencionado.

## Datos biométricos

- Las plantillas de huella (`huellas.template`) solo se leen/escriben a
  través de la API del agente, protegida por el token de sede — no hay
  ninguna ruta pública que las exponga.
- Se eliminó un flujo legado de registro de asistencia por
  `huella_digital` en **texto plano** sin límite de intentos (hallazgo de
  auditoría), que había quedado desconectado del menú desde antes del agente
  biométrico actual. La asistencia real hoy pasa únicamente por
  `AsistenciaService`, vía el agente C# o por QR (`AsistenciaQrController`).

## Archivos privados

- Se eliminó una ruta genérica (`GET /archivos/privados/{path}`) que servía
  cualquier archivo del disco privado solo verificando que el usuario
  estuviera logueado, sin comprobar que el archivo le perteneciera
  (hallazgo de auditoría). Las descargas legítimas de archivos privados
  (comprobantes de pago, QR de pago, fotos de perfil) tienen cada una su
  propia ruta con verificación de propiedad.

## Bitácora de auditoría

- `spatie/laravel-activitylog` registra altas, bajas y cambios relevantes.
  Solo el Administrador puede ver la bitácora (`/bitacora`).
- Limpieza automática programada (`activitylog:clean`, diaria) según la
  política de retención de `config/activitylog.php`, para que la tabla no
  crezca indefinidamente.
- Comando dedicado (`LimpiarCamposSensiblesBitacoraCommand`) para depurar
  campos sensibles que puedan haber quedado registrados en el historial.

## Configuración segura por defecto

- `.env.example` trae `APP_DEBUG=false` por defecto: si se copia tal cual a
  producción sin tocar nada, no se filtran stack traces, queries SQL ni
  variables de entorno ante un error.
- `LOG_STACK=daily` / `LOG_LEVEL=error` documentados como el valor a usar en
  producción (en vez de `single`/`debug`, pensados para desarrollo).
- `DatabaseSeeder` se niega a correr si `APP_ENV=production`, para evitar
  que alguien ensucie una base real con datos de demo (clientes falsos,
  contraseñas fijas) por costumbre (`migrate --seed`). La preparación de una
  base de producción usa un seeder aparte y mínimo (`ProduccionInicialSeeder`)
  que genera una contraseña aleatoria para el primer Administrador.

## Backups

- Respaldo diario automático de base de datos y archivos privados, con
  copia a almacenamiento externo (Backblaze B2 vía S3) y monitoreo de
  fallos por email. Detalle completo, política de retención y procedimiento
  de restauración en [RESTAURACION.md](RESTAURACION.md).

## Rendimiento como factor de disponibilidad

- Paginación en los listados administrativos e índices en las columnas más
  consultadas (`membresia_cliente.fecha_fin`/`estado`, `pagos.fecha_pago`),
  para que el sistema siga respondiendo con normalidad a medida que
  crece el volumen de datos con los años.

## Pendientes conocidos (ver [DESPLIEGUE.md](DESPLIEGUE.md) § 9-10)

- `SESSION_SECURE_COOKIE=true` en producción (no viene seteada por defecto).
- Headers de seguridad HTTP (HSTS, `X-Content-Type-Options`,
  `X-Frame-Options`, `Referrer-Policy`).
- CORS restringido para la API si se consume desde otro dominio.
- CAPTCHA en el registro público de clientes.
- Confirmar licencia comercial del dataset de ejercicios (Gym Visual) antes
  de un lanzamiento con clientes reales.
