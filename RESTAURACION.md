# Procedimiento de Restauración — MadBarzz

> **Prueba periódica recomendada:** este procedimiento debe ejecutarse en una máquina de prueba al menos una vez cada 3 meses para verificar que los respaldos son válidos y que el proceso funciona correctamente.

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

## 5. Restaurar en un servidor nuevo (instalación fresca)

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
   php artisan key:generate
   # Edita .env con las credenciales de DB, mail, S3, etc.
   ```

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
- [ ] Ejecutar `php artisan migrate:status` — no debe haber migraciones pendientes desconocidas
- [ ] Navegar la app y confirmar que el login, dashboard y datos funcionan
- [ ] Documentar la fecha de la prueba y quién la realizó

---

*Documentación generada: 2026-06-29 — Fase 4 Respaldos Automáticos*
