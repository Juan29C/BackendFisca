# 🎓 Explicación del Sistema de Almacenamiento

## ❓ Preguntas Frecuentes Respondidas

### 1. ¿Por qué escribir `$disk = config('filesystems.default')` en cada método?

**Respuesta corta**: Porque Laravel necesita saber QUÉ disco usar.

**Explicación detallada**:

```php
// ❌ INCORRECTO - Laravel no sabe qué disco usar
Storage::put('archivo.pdf', $contenido);

// ✅ CORRECTO - Le decimos explícitamente el disco
$disk = config('filesystems.default');  // Lee FILESYSTEM_DISK del .env
Storage::disk($disk)->put('archivo.pdf', $contenido);
```

**¿Por qué no usar `Storage::` directamente?**
- `Storage::put()` usa el disco 'local' por defecto (storage/app/private)
- Necesitamos discos públicos ('public', 'uploads', 's3')
- Por eso especificamos el disco con `Storage::disk($disk)`

---

### 2. ¿A cuál hace caso: 'local' en filesystems.php o 'uploads' en .env?

**Respuesta**: ¡Siempre al `.env`!

**Cómo funciona `env()`**:

```php
// En config/filesystems.php línea 16:
'default' => env('FILESYSTEM_DISK', 'local')
             ↑                      ↑
             Lee del .env           Fallback si no existe
```

**Orden de ejecución**:
1. Busca `FILESYSTEM_DISK` en `.env`
2. Si existe → usa ese valor ✅
3. Si NO existe → usa el fallback 'local'

**En tu caso**:
```env
# .env
FILESYSTEM_DISK=uploads  ← Esto es lo que se usa
```

```php
// filesystems.php
'default' => env('FILESYSTEM_DISK', 'local')
//           ↓
//           Lee .env y encuentra 'uploads' ✅
```

**Resultado**: `config('filesystems.default')` = `'uploads'`

---

### 3. ¿Por qué hay un try-catch en los Resources?

```php
public function toArray($request): array
{
    $url = null;
    if ($this->ruta) {
        try {
            $disk = config('filesystems.default');
            $url = Storage::disk($disk)->url($this->ruta);
        } catch (\Exception $e) {
            // Fallback por si el disco no soporta URLs públicas
            $url = asset('storage/' . $this->ruta);
        }
    }
    
    return [
        // ...
        'url' => $url,
    ];
}
```

**Razones del try-catch**:

1. **Seguridad**: Si el disco no existe o está mal configurado, no rompe la aplicación
2. **Compatibilidad**: Algunos discos personalizados pueden no tener método `url()`
3. **Fallback robusto**: Si falla, usa `asset()` que siempre funciona localmente

---

## 🔄 Flujo Completo del Sistema

### Paso 1: Configuración en .env
```env
FILESYSTEM_DISK=uploads
```

### Paso 2: Config lee el .env
```php
// config/filesystems.php
'default' => env('FILESYSTEM_DISK', 'local')
// Resultado: 'uploads'
```

### Paso 3: Servicio usa la config
```php
// DocumentoCoactivoService.php
$disk = config('filesystems.default');  // = 'uploads'
Storage::disk($disk)->put(...);         // Guarda en public/storage/
```

### Paso 4: Resource genera URL
```php
// DocumentoCoactivoResource.php
$disk = config('filesystems.default');   // = 'uploads'
$url = Storage::disk($disk)->url($ruta); // = http://127.0.0.1:8000/storage/archivo.pdf
```

### Paso 5: Frontend recibe la URL
```json
{
  "id_documento_coactivo": 1,
  "url": "http://127.0.0.1:8000/storage/documentos-coactivos/12345678/archivo.pdf"
}
```

---

## 🎯 Ejemplo Práctico Completo

### Escenario: Subir documento coactivo

**1. Usuario sube archivo desde el frontend**

**2. Backend recibe y procesa (DocumentoCoactivoService.php)**:
```php
public function uploadSingle(int $idCoactivo, array $data): DocumentoCoactivo
{
    // Lee configuración del .env
    $disk = config('filesystems.default');  // = 'uploads'
    
    // Guarda en public/storage/documentos-coactivos/...
    $path = $file->storeAs($baseFolder, $uniqueName, $disk);
    
    // Guarda registro en BD
    $documento = DocumentoCoactivo::create([
        'ruta' => $path,  // documentos-coactivos/12345678/archivo_123456.pdf
        // ...
    ]);
    
    return $documento;
}
```

**3. Frontend solicita lista de documentos**

**4. Backend responde (DocumentoCoactivoResource.php)**:
```php
public function toArray(Request $request): array
{
    // Lee configuración del .env
    $disk = config('filesystems.default');  // = 'uploads'
    
    // Genera URL pública
    $url = Storage::disk($disk)->url($this->ruta);
    // = http://127.0.0.1:8000/storage/documentos-coactivos/12345678/archivo_123456.pdf
    
    return [
        'id_documento_coactivo' => $this->id_doc_coactivo,
        'ruta' => $this->ruta,
        'url' => $url,  // ← Frontend usa esto
        // ...
    ];
}
```

**5. Frontend muestra enlace**:
```jsx
<a href={documento.url} download>
  Descargar {documento.nombreDocumento}
</a>
```

---

## 📊 Tabla Comparativa: ¿Qué pasa según el disco?

| Disco | Ubicación Física | URL Generada | Requiere |
|-------|-----------------|--------------|----------|
| `public` | `storage/app/public/` | `http://127.0.0.1:8000/storage/archivo.pdf` | `php artisan storage:link` |
| `uploads` | `public/storage/` | `http://127.0.0.1:8000/storage/archivo.pdf` | Nada |
| `s3` | Amazon S3 | `https://bucket.s3.amazonaws.com/archivo.pdf` | Credenciales AWS |

**URLs idénticas** para `public` y `uploads` → Frontend no necesita cambios

---

## 🧪 Verificar Configuración Actual

```bash
# Opción 1: Ver en terminal
php artisan tinker
>>> config('filesystems.default')
=> "uploads"

# Opción 2: Script de prueba
php test-storage.php
# Muestra: ✓ Disco configurado: uploads
```

---

## 🔧 Archivos Actualizados con el Patrón

Todos estos archivos usan `$disk = config('filesystems.default')`:

### Servicios:
- ✅ `app/Services/DocumentoCoactivoService.php` (4 métodos)
- ✅ `app/Services/DocumentoService.php` (4 métodos)
- ✅ `app/Services/ResolucionService.php` (1 método)

### Repositorios:
- ✅ `app/Repositories/ExpedienteRepository.php` (1 método)

### Resources:
- ✅ `app/Http/Resources/DocumentoCoactivoResource.php`
- ✅ `app/Http/Resources/DocumentoResource.php`
- ✅ `app/Http/Resources/DocumentosExpedienteResource.php`

---

## 💡 Resumen Mental Rápido

**3 conceptos clave**:

1. **`.env` manda**:
   - `FILESYSTEM_DISK=uploads` es la fuente de verdad
   - El 'local' en filesystems.php es solo fallback

2. **Config lee del .env**:
   - `config('filesystems.default')` lee `FILESYSTEM_DISK`
   - Siempre obtiene 'uploads' en tu caso

3. **Servicios/Resources usan config**:
   - `$disk = config('filesystems.default')`
   - `Storage::disk($disk)->...`
   - Funciona con cualquier disco (public, uploads, s3)

---

## 🚀 Cambiar de Disco (Sin tocar código)

```bash
# 1. Editar .env
FILESYSTEM_DISK=s3  # ← Solo cambias esto

# 2. Limpiar cache
php artisan config:clear

# 3. Listo - el código sigue funcionando igual
```

**Magia**: Todos los `config('filesystems.default')` ahora devuelven 's3' automáticamente.

---

**Creado**: Diciembre 2025  
**Propósito**: Documentar decisiones de arquitectura del sistema de almacenamiento
