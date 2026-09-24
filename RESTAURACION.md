# Procedimiento de Restauración — MadBarzz

> **Prueba periódica recomendada:** este procedimiento debe ejecutarse en una máquina de prueba al menos una vez cada 3 meses para verificar que los respaldos son válidos y que el proceso funciona correctamente.

> ⚠️ **Antes de tocar nada: ¿es una instalación nueva o estás recuperando un sistema existente?**
> Las plantillas de huella dactilar (`huellas.template`) se guardan cifradas con la `APP_KEY`
> (cast `encrypted` de Laravel, desde el Grupo 2 de la auditoría). Si el sistema que estás
> restaurando ya tiene huellas enroladas, **generar una `APP_KEY` nueva vuelve esos datos ilegibles
> para siempre, sin forma de recuperarlos**. La sección 5 más abajo separa los dos casos (instalación
> realmente nueva vs. recuperar uno existente) — leela ANTES de correr `php artisan key:generate`.

---

## 1. Ubicación de los respaldos

Los backups se guardan automáticamente en:

```
storage/app/backups/Laravel/
```

Cada archivo tiene el formato `YYYY-MM-DD-HH-MM-SS.zip` (hora UTC). En producción, también estarán disponibles en el bucket S3/Spaces configurado.

**Backup manual inmediato:**
```bash
php artisan backup:run --disable-notifications
```

**Listar backups disponibles:**
```bash
php artisan backup:list
```

---

## 2. Requisitos previos

- PHP 8.2+, Composer instalado
- MySQL/MariaDB accesible
- `unzip` (Linux) o cualquier descompresor ZIP (Windows)
- Credenciales de la base de datos
- Si el ZIP del backup está cifrado: la contraseña `BACKUP_ARCHIVE_PASSWORD` (ver sección 3a)
- **Si estás recuperando un sistema que ya existía** (no una instalación nueva desde cero): la
  `APP_KEY` ORIGINAL de ese sistema, guardada aparte del backup (ver § "Secretos críticos" abajo y
  sección 5b). Sin ella, no hace falta para leer la base de datos en sí, pero sí para las plantillas
  de huella dactilar, que se guardan cifradas con el cast `encrypted` de Laravel.

### Secretos críticos que hay que tener a mano ANTES de restaurar

Dos valores, si no los tenés guardados aparte del backup, pueden hacer que la restauración sea
inútil o que se pierdan datos que no se pueden recuperar de ninguna otra forma:

- **`BACKUP_ARCHIVE_PASSWORD`** — sin ella no podés abrir el ZIP del backup si está cifrado.
- **`APP_KEY`** — la clave original con la que Laravel cifró los datos sensibles de esa instalación
  (las plantillas de huella dactilar). Restaurar la base de datos con una `APP_KEY` distinta a la que
  cifró esos datos los deja ilegibles para siempre — no es un problema que se resuelva reintentando
  ni con soporte técnico.

Ambos deberían estar guardados por el dueño del gimnasio en un lugar seguro y separado del propio
backup (gestor de contraseñas, papel, drive privado) — ver también
[DESPLIEGUE.md](DESPLIEGUE.md) § "Secretos críticos que el dueño debe custodiar".

---

## 3. Restaurar la base de datos

### 3a. Extraer el archivo SQL del ZIP

```bash
# Linux/macOS
unzip 2026-06-29-23-30-10.zip -d backup_extraido/

# Windows (PowerShell)
Expand-Archive -Path "2026-06-29-23-30-10.zip" -DestinationPath "backup_extraido"
```

> Si el ZIP está encriptado con contraseña (`BACKUP_ARCHIVE_PASSWORD`), usa:
> ```bash
> unzip -P "tu_contraseña" 2026-06-29-23-30-10.zip -d backup_extraido/
> ```

Dentro del ZIP encontrarás un subdirectorio `db-dumps/` con el archivo `mysql-Calistenia_M.sql`.

### 3b. Importar la base de datos

**Opción A — MySQL CLI:**
```bash
# Puerto estándar (Linux/producción)
mysql -h 127.0.0.1 -u root -p Calistenia_M < backup_extraido/db-dumps/mysql-Calistenia_M.sql

# Puerto 3307 (XAMPP Windows local)
mysql -h 127.0.0.1 -P 3307 -u root Calistenia_M < "backup_extraido\db-dumps\mysql-Calistenia_M.sql"
```

**Opción B — phpMyAdmin:**
1. Abre phpMyAdmin → selecciona la base de datos `Calistenia_M`
2. Pestaña **Importar** → selecciona el archivo SQL extraído
3. Clic en **Continuar**

### 3c. Verificar integridad

```sql
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM clientes;
SELECT COUNT(*) FROM pagos;
SHOW TABLES;
```

---

## 4. Restaurar archivos privados

Los archivos subidos por usuarios (fotos de perfil, comprobantes QR) se encuentran en:

```
storage/app/private/
```

En el ZIP están bajo la ruta relativa `app/private/` (relativa a `storage/`).

```bash
# Linux/macOS
unzip 2026-06-29-23-30-10.zip "app/private/*" -d /var/www/madbarzz/storage/

# Windows (PowerShell)
Expand-Archive -Path "2026-06-29-23-30-10.zip" -DestinationPath "C:\xampp\htdocs\TG_MadBarzz\storage"
```

Asegúrate de que el servidor web tenga permisos de lectura sobre `storage/app/private/`:

```bash
# Linux
chmod -R 755 storage/app/private/
chown -R www-data:www-data storage/app/private/
```

---

## 5. Restaurar en un servidor nuevo

Hay DOS escenarios distintos acá, y confundirlos es el error más caro de todo este documento:

- **5a — Instalación realmente nueva**: un gimnasio/instancia que arranca de cero, sin backup previo
  que restaurar (no aplica esta sección "5" en general, pero se incluye la generación de `APP_KEY`
  para dejarlo explícito).
- **5b — Recuperar un sistema EXISTENTE** en un servidor nuevo (el caso real de esta guía: el
  servidor viejo se rompió, se migra de proveedor, etc.) — hay un backup de por medio y por lo tanto
  hay que preservar la `APP_KEY` original, NO generar una nueva.

### 5a. Instalación nueva, sin datos que restaurar

Solo si es la primera vez que este sistema corre en cualquier lado (no hay backup, no hay clientes
cargados todavía):

```bash
git clone <repo-url> madbarzz
cd madbarzz
composer install --no-dev --optimize-autoloader
npm install && npm run build
cp .env.example .env
php artisan key:generate
# Edita .env con las credenciales de DB, mail, S3, etc.
php artisan migrate --force
```

Acá `key:generate` es correcto: todavía no existe ningún dato cifrado con ninguna clave, así que no
hay nada que se vuelva ilegible.

### 5b. Recuperar un sistema existente (el caso de un backup real)

Este es el procedimiento cuando SÍ hay un backup de por medio — el escenario típico de esta guía.

1. Clona el repositorio:
   ```bash
   git clone <repo-url> madbarzz
   cd madbarzz
   ```

2. Instala dependencias:
   ```bash
   composer install --no-dev --optimize-autoloader
   npm install && npm run build
   ```

3. Configura el entorno:
   ```bash
   cp .env.example .env
   # Edita .env con las credenciales de DB, mail, S3, etc.
   ```
   ⚠️ **NO corras `php artisan key:generate` acá.** En vez de generar una clave nueva, pegá la
   `APP_KEY` ORIGINAL de la instancia que estás recuperando (la que guardaste aparte del backup, ver
   § "Secretos críticos" en la sección 2) directamente en la línea `APP_KEY=` del `.env` nuevo.
   Generar una clave nueva sobre datos que ya existían con la clave vieja vuelve ilegible para
   siempre cualquier dato cifrado con el cast `encrypted` de Laravel (la plantilla de huella
   dactilar del cliente) — no es reversible ni con soporte técnico ni con más backups.

4. Crea la base de datos vacía e importa el backup (ver sección 3).

5. Restaura los archivos privados (ver sección 4).

6. Ejecuta las migraciones pendientes (si las hay después de la fecha del backup):
   ```bash
   php artisan migrate --force
   ```

7. Limpia cachés:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

8. Verifica que la app carga y que los archivos privados son accesibles desde la interfaz.

---

## 6. Descarga desde S3 / DigitalOcean Spaces

Si el backup local no está disponible, descárgalo desde el almacenamiento externo:

```bash
# Con AWS CLI
aws s3 cp s3://<bucket>/Laravel/2026-06-29-23-30-10.zip ./

# O desde el panel web de tu proveedor (DigitalOcean Spaces, Wasabi, etc.)
```

---

## 7. Monitoreo y alertas

El sistema ejecuta automáticamente:

| Hora (servidor) | Comando                | Propósito                              |
|-----------------|------------------------|----------------------------------------|
| 02:00           | `backup:run`           | Genera el backup diario                |
| 02:30           | `backup:clean`         | Elimina backups según política retención|
| 03:00           | `backup:monitor`       | Alerta si el backup tiene más de 1 día |

Si un backup falla, se envía un email a `BACKUP_MAIL_TO` configurado en `.env`.

**Política de retención:**
- Últimos 7 días: se guardan todos los backups
- Semana 2–4: un backup por día
- Mes 2–3: un backup por semana
- Límite de almacenamiento: 2 GB locales

---

## 8. Checklist de prueba periódica (cada 3 meses)

En una máquina de prueba separada, verificar:

- [ ] Descargar el ZIP de backup más reciente
- [ ] Extraer y verificar que el SQL no está vacío (`wc -l` o tamaño > 0)
- [ ] Importar la BD en un servidor de prueba y verificar tablas
- [ ] Restaurar `storage/app/private/` y verificar que las imágenes cargan
- [ ] Usar la `APP_KEY` ORIGINAL de producción en el `.env` de prueba (sección 5b) — **no generar una
  nueva con `key:generate`**, ni siquiera en esta máquina de prueba: como las huellas dactilares están
  cifradas con el cast `encrypted`, esta es la forma de confirmar que el procedimiento de restauración
  realmente las deja legibles, no solo que la BD importó.
- [ ] Ejecutar `php artisan migrate:status` — no debe haber migraciones pendientes desconocidas
- [ ] Navegar la app y confirmar que el login, dashboard y datos funcionan
- [ ] Documentar la fecha de la prueba y quién la realizó

---

*Documentación generada: 2026-06-29 — Fase 4 Respaldos Automáticos*
