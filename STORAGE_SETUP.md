# 📦 Sistema de Almacenamiento - Guía Completa

## ✅ Configuración Simplificada

El sistema usa la variable estándar de Laravel `FILESYSTEM_DISK` para controlar dónde se guardan todos los archivos.

## 🎯 Opciones de Discos

### 1. **public** (Desarrollo con symlink)
```env
FILESYSTEM_DISK=public
```
- **Ubicación física**: `storage/app/public/`
- **Requiere**: `php artisan storage:link`
- **URL pública**: `http://tudominio.com/storage/archivo.pdf`
- **Uso**: Desarrollo local con XAMPP/Laragon

### 2. **uploads** (Producción sin symlink) ⭐ RECOMENDADO
```env
FILESYSTEM_DISK=uploads
```
- **Ubicación física**: `public/storage/`
- **Requiere**: Nada, acceso directo
- **URL pública**: `http://tudominio.com/storage/archivo.pdf`
- **Uso**: Producción (hosting compartido, servidores sin acceso SSH)

### 3. **s3** (Cloud Storage - Futuro)
```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=tu_key
AWS_SECRET_ACCESS_KEY=tu_secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=nombre-bucket
```
- **Ubicación física**: Amazon S3
- **Requiere**: `composer require league/flysystem-aws-s3-v3`
- **URL pública**: `https://bucket.s3.amazonaws.com/archivo.pdf`
- **Uso**: Alta disponibilidad, CDN, backups automáticos

---

## 🔄 Cambiar de Disco

### Paso 1: Editar `.env`
```env
FILESYSTEM_DISK=uploads  # ← Cambiar aquí
```

### Paso 2: Limpiar caché
```bash
php artisan config:clear
```

### Paso 3: Mover archivos existentes (opcional)

**De `public` a `uploads`:**
```powershell
Move-Item -Path .\storage\app\public\* -Destination .\public\storage\ -Force
```

**De `uploads` a `public`:**
```powershell
Move-Item -Path .\public\storage\* -Destination .\storage\app\public\ -Force
```

---

## 🧪 Probar Configuración

Ejecuta el script de prueba:
```bash
php test-storage.php
```

Verás:
- ✓ Disco configurado
- ✓ Ruta física
- ✓ Creación de archivos
- ✓ URLs públicas
- ✓ Eliminación

---

## 📂 Estructura de Archivos

### Expedientes (DocumentoService)
```
{disk}/expedientes/{dni|ruc}/archivo_xxxxx.pdf
```

### Coactivos (DocumentoCoactivoService)
```
{disk}/documentos-coactivos/{dni|ruc}/archivo_xxxxx.pdf
```

### Resoluciones (ResolucionService)
```
{disk}/resoluciones/RES-XXXXXX-YYYY.docx
```

---

## 💻 Código en los Servicios

Todos los servicios usan el mismo patrón:

```php
$disk = config('filesystems.default');
Storage::disk($disk)->put($ruta, $contenido);
```

**Archivos actualizados:**
- ✅ `app/Services/DocumentoService.php`
- ✅ `app/Services/DocumentoCoactivoService.php`
- ✅ `app/Services/ResolucionService.php`
- ✅ `app/Repositories/ExpedienteRepository.php`
- ✅ `app/Http/Resources/DocumentoCoactivoResource.php`

---

## 🌐 URLs en el Frontend

Las URLs se generan automáticamente según el disco:

### Disco `public` o `uploads`:
```json
{
  "url": "http://127.0.0.1:8000/storage/documentos-coactivos/12345678/archivo.pdf"
}
```

### Disco `s3`:
```json
{
  "url": "https://bucket.s3.amazonaws.com/documentos-coactivos/12345678/archivo.pdf"
}
```

**El frontend no necesita cambios**, las URLs funcionan automáticamente.

---

## 🚀 Migración a S3 (Futuro)

### 1. Instalar dependencia
```bash
composer require league/flysystem-aws-s3-v3 "^3.0"
```

### 2. Configurar `.env`
```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=AKIA...
AWS_SECRET_ACCESS_KEY=wJalrXUt...
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=mi-bucket-fiscalizacion
```

### 3. Limpiar caché
```bash
php artisan config:clear
```

### 4. Subir archivos existentes
```bash
# Crear comando artisan para migración
php artisan make:command MigrateStorageToS3
```

**¡No necesitas cambiar código!** Todo sigue funcionando igual.

---

## ❓ Preguntas Frecuentes

### ¿Por qué no modificar `FILESYSTEM_DISK` directamente desde el inicio?
**Respuesta**: Es exactamente lo que hacemos ahora. `FILESYSTEM_DISK` es la variable estándar de Laravel.

### ¿Qué pasa si cambio a S3 en producción?
**Respuesta**: Solo cambias `.env`, ejecutas `config:clear`, y subes archivos a S3. El código funciona sin modificaciones.

### ¿Las URLs cambian según el disco?
**Respuesta**: Sí, Laravel genera la URL correcta automáticamente:
- Local: `http://127.0.0.1:8000/storage/...`
- S3: `https://bucket.s3.amazonaws.com/...`

### ¿Necesito modificar el frontend?
**Respuesta**: No. El backend siempre devuelve la URL completa en los recursos JSON.

---

## 🛠️ Troubleshooting

### Error: "File not found"
```bash
# Verificar que el disco existe
php artisan tinker
>>> config('filesystems.default')
=> "uploads"

# Verificar permisos (Linux)
chmod -R 775 public/storage
chown -R www-data:www-data public/storage
```

### Error: "Disk [uploads] does not have a configured driver"
```bash
# Limpiar caché
php artisan config:clear
php artisan cache:clear
```

### Archivos no aparecen en la URL pública
```bash
# Verificar APP_URL en .env
APP_URL=http://127.0.0.1:8000

# Probar URL manualmente
http://127.0.0.1:8000/storage/test.txt
```

---

## ✅ Ventajas del Sistema Actual

1. **Simple**: Una sola variable controla todo
2. **Estándar Laravel**: Usa `FILESYSTEM_DISK` oficial
3. **Sin código duplicado**: Todos los servicios usan `config('filesystems.default')`
4. **Fácil migración**: Cambias `.env` y listo
5. **Flexible**: Soporta local, cloud, FTP, SFTP, etc.
6. **Sin cambios en frontend**: URLs se generan automáticamente

---

## 📝 Resumen Rápido

| Entorno | Variable | Valor | Requiere |
|---------|----------|-------|----------|
| Desarrollo | `FILESYSTEM_DISK` | `public` | `php artisan storage:link` |
| Producción | `FILESYSTEM_DISK` | `uploads` | Nada |
| Cloud | `FILESYSTEM_DISK` | `s3` | Credenciales AWS |

**Después de cambiar**: `php artisan config:clear`

---

**Última actualización**: Diciembre 2025
