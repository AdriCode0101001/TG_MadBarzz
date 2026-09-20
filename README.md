# MadBarzz

Sistema de gestión para un gimnasio de calistenia: clientes y membresías, pagos,
control de asistencia biométrico, rutinas, portal del cliente, sitio público y
notificaciones automáticas. Desarrollado como Trabajo de Grado (TG), pensado
para desplegarse en Oracle Cloud tras un período de prueba.

## Índice

- [¿Qué es MadBarzz?](#qué-es-madbarzz)
- [Stack tecnológico](#stack-tecnológico)
- [Arquitectura general](#arquitectura-general)
- [Roles del sistema](#roles-del-sistema)
- [Requisitos e instalación (desarrollo local)](#requisitos-e-instalación-desarrollo-local)
- [Comandos útiles](#comandos-útiles)
- [Tests](#tests)
- [Backups y restauración](#backups-y-restauración)
- [Despliegue a producción](#despliegue-a-producción)

## ¿Qué es MadBarzz?

MadBarzz es una aplicación web para administrar un gimnasio de calistenia de
principio a fin:

- **Clientes y membresías**: alta de clientes, tipos de membresía, asignación
  y renovación, estado de vigencia (activa/inactiva), pago en cuotas con saldo
  pendiente.
- **Pagos**: registro de pagos (efectivo o QR), comprobantes, reportes
  mensuales, búsqueda por cliente/fecha.
- **Asistencia biométrica**: un agente externo en C# con lector de huella
  ZK9500 enrola clientes y marca asistencia contra la API del sistema; también
  existe un registro de asistencia alternativo por QR.
- **Rutinas**: calendario de grupos musculares y rutinas de ejercicio armadas
  por Administrador/Entrenador, con un catálogo de ejercicios importado de un
  dataset externo.
- **Portal del cliente**: cada cliente ve su membresía, sus pagos, sus
  asistencias, su rutina del día, descarga su carnet y puede vincular
  Telegram y notificaciones push del navegador.
- **Sitio público**: página de inicio con información del gimnasio, horarios,
  entrenadores y membresías, y formulario de auto-registro de clientes.
- **Avisos y notificaciones**: avisos generales a todos los clientes (con
  campanita en el portal) y notificaciones automáticas de vencimiento de
  membresía o saldo pendiente por email, Telegram, WhatsApp o push, según lo
  que esté configurado.
- **Bitácora de auditoría**: registro (vía Spatie Activitylog) de altas,
  bajas y cambios relevantes, visible solo para el Administrador.
- **Backups automáticos**: respaldo diario de base de datos y archivos
  privados, con copia externa y monitoreo de fallos (ver
  [RESTAURACION.md](RESTAURACION.md)).

## Stack tecnológico

- **Backend**: PHP 8.2+, Laravel 12.
- **Base de datos**: MySQL/MariaDB (desarrollo y producción); SQLite en
  memoria para los tests automatizados.
- **Frontend**: Blade + Bootstrap 5 (panel privado) y Tailwind CSS (algunas
  vistas/Breeze), Alpine.js para interactividad ligera, Vite como bundler.
- **Paneles administrativos**: Filament 3 (panel de estadísticas).
- **Autenticación/API**: Laravel Breeze (web) y Laravel Sanctum (tokens para
  el portal del cliente vía API); el agente biométrico usa un token propio
  (SHA-256) por sede, no Sanctum.
- **Agente biométrico**: aplicación de escritorio en C# (repositorio aparte),
  que habla con un lector de huellas ZK9500 y consume la API
  `/api/agente/*` de este proyecto.
- **Integraciones**:
  - Web Push (VAPID, vía `minishlink/web-push`) para notificaciones al
    navegador/celular sin instalar una app.
  - Telegram Bot API para avisos por chat.
  - WhatsApp Cloud API / Twilio como canales alternativos de notificación.
  - Email (mailer configurable) para verificación de cuenta y avisos.
  - PDF (`barryvdh/laravel-dompdf`) para reportes y carnet del cliente.
  - Códigos QR (`endroid/qr-code`) para el pago por QR y la asistencia por QR.
  - `spatie/laravel-backup` + `league/flysystem-aws-s3-v3` para backups
    locales y a almacenamiento externo compatible con S3 (Backblaze B2).
  - `spatie/laravel-activitylog` para la bitácora de auditoría.

## Arquitectura general

```
                        ┌─────────────────────┐
                        │   Navegador (web)    │
                        │ Blade + Bootstrap +  │
                        │      Alpine.js       │
                        └──────────┬───────────┘
                                   │ HTTP (sesión web)
                                   ▼
   ┌───────────────────────────────────────────────────────────┐
   │                     Laravel 12 (este repo)                 │
   │  Controllers / Services / Models / Filament (panel Admin)  │
   │                                                             │
   │  routes/web.php   → panel privado + portal cliente + home  │
   │  routes/api.php   → agente biométrico + portal cliente API │
   │  routes/console.php → tareas programadas (schedule:run)    │
   └───────┬───────────────────┬───────────────────┬───────────┘
           │                   │                   │
           ▼                   ▼                   ▼
   ┌───────────────┐   ┌───────────────┐   ┌────────────────────┐
   │  MySQL /       │   │  Integraciones │   │  Almacenamiento    │
   │  MariaDB       │   │  externas:     │   │  (storage/):       │
   │  (Calistenia_M)│   │  - Email       │   │  - archivos        │
   └───────────────┘   │  - Telegram    │   │    privados         │
                        │  - WhatsApp/   │   │  - backups locales  │
                        │    Twilio      │   │  - copia externa     │
                        │  - Web Push    │   │    (S3 / Backblaze  │
                        └───────────────┘   │    B2)               │
                                             └────────────────────┘
                                   ▲
                                   │ HTTPS + token de agente (SHA-256)
                                   │ GET/POST /api/agente/*
                        ┌──────────┴───────────┐
                        │  Agente biométrico    │
                        │  (C#, escritorio,     │
                        │  repo aparte) + lector │
                        │  de huellas ZK9500     │
                        └───────────────────────┘
```

El agente biométrico es un proceso externo: no vive en este repositorio. Se
autentica contra `routes/api.php` con un token propio por sede, generado con
`php artisan agente:token` (ver más abajo), y usa esa API para listar
clientes, enrolar huellas y registrar asistencia.

## Roles del sistema

El acceso está controlado por rol (middleware `role:`, ver `routes/web.php`)
más aislamiento por sede (todo registro pertenece a una `Sede`, y las
consultas se filtran automáticamente por la sede del usuario logueado):

- **Administrador**: acceso completo. Gestiona usuarios, roles, personal,
  catálogo de membresías/descuentos/grupos musculares/ejercicios, horarios de
  entrenadores, promociones, imágenes de la home pública, QR de pago,
  reportes, bitácora de auditoría y bloqueos de login. Es el único rol que
  puede borrar personal y ver reportes.
- **Recepcionista**: gestión del día a día del gimnasio — altas/edición de
  clientes y personal (sin borrar personal), registro y edición de pagos,
  envío de notificaciones y avisos, lectura de horarios.
- **Entrenador**: registra clientes y pagos, marca asistencia (QR o vía
  agente biométrico), arma sus propias rutinas del calendario de grupos
  musculares.
- **Cliente**: rol de autoservicio vía el portal — ve su membresía, su
  historial de pagos y asistencias, su rutina del día, descarga su carnet, y
  puede vincular Telegram o activar notificaciones push. Requiere correo
  verificado para acceder al portal.

## Requisitos e instalación (desarrollo local)

Requisitos:

- PHP 8.2 o superior, con las extensiones habituales de Laravel.
- Composer.
- Node.js + npm (para compilar los assets con Vite).
- MySQL o MariaDB.

Pasos:

```bash
# 1. Clonar el repositorio
git clone <url-del-repo> madbarzz
cd madbarzz

# 2. Instalar dependencias PHP y JS
composer install
npm install

# 3. Configurar el entorno
cp .env.example .env
php artisan key:generate

# 4. Editar .env: al menos DB_DATABASE, DB_USERNAME, DB_PASSWORD
#    (y el puerto de MySQL si no es el 3306 por defecto, común en XAMPP)

# 5. Crear la base de datos (vacía) y migrar
php artisan migrate

# 6. Cargar datos de DEMO para desarrollo (roles, sede, usuarios de prueba,
#    70 clientes falsos, etc. — NUNCA usar esto en producción)
php artisan db:seed

# 7. Enlazar el storage público y compilar assets
php artisan storage:link
npm run dev
```

Para levantar todo el entorno de desarrollo (servidor, cola, logs y Vite) en
un solo comando:

```bash
composer run dev
```

O por separado, solo el servidor:

```bash
php artisan serve
```

> En este proyecto, en Windows/XAMPP se usa `php artisan serve`
> directamente (no el Apache de XAMPP) para levantar la aplicación.

## Comandos útiles

```bash
# Generar el token del agente biométrico para una sede
php artisan agente:token {sede_id} --nombre="Agente Sede Central"

# Backup manual inmediato
php artisan backup:run --disable-notifications

# Enviar ahora las notificaciones de vencimiento/saldo pendiente (normalmente las dispara el scheduler)
php artisan notificaciones:vencimientos
php artisan notificaciones:saldo-pendiente
```

## Tests

El proyecto usa PHPUnit con SQLite en memoria (no toca la base de datos de
desarrollo):

```bash
php artisan test
# o
composer test
```

## Backups y restauración

Los respaldos automáticos (base de datos + archivos privados), su ubicación,
la política de retención y el procedimiento paso a paso de restauración están
documentados en [RESTAURACION.md](RESTAURACION.md).

## Despliegue a producción

El checklist completo para desplegar en un servidor Linux (Oracle Cloud u
otro) está en [DESPLIEGUE.md](DESPLIEGUE.md).
