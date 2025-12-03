# Endpoint: Generar Entrega de Cheque

## 📋 Información General

**Método:** `POST`  
**URL:** `/v1/auth/coactivos/{coactivoId}/documentos/generar-entrega-cheque`  
**Auth:** Requerido (JWT - Rol: Coactivo)  
**Content-Type:** `application/json`

---

## 🎯 Descripción

Genera un documento Word de "Entrega de Cheque" (RESOLUCIÓN DE EJECUCION COACTIVA Nº 03) para solicitar a una entidad bancaria que entregue el monto retenido mediante cheque de gerencia o certificado.

---

## 📥 Request Body

```json
{
  "id_entidad_bancaria": 2,
  "fecha_recepcion_bancaria": "2024-01-15",
  "monto_retencion": 1500.50,
  "cod_orden_bancario": "ORD-2024-00123"
}
```

### Campos requeridos:

| Campo | Tipo | Descripción | Ejemplo |
|-------|------|-------------|---------|
| `id_entidad_bancaria` | integer | ID de la entidad bancaria (del endpoint `/entidades-bancarias`) | `2` |
| `fecha_recepcion_bancaria` | date | Fecha de recepción del documento bancario | `"2024-01-15"` |
| `monto_retencion` | decimal | Monto retenido por el banco | `1500.50` |
| `cod_orden_bancario` | string | Código de la orden bancaria de retención | `"ORD-2024-00123"` |

> **⚠️ Nota:** La `fecha_resolucion_dos` se obtiene **automáticamente** de la base de datos buscando el documento coactivo con `id_tipo_doc_coactivo = 2` del expediente actual.

---

## 📤 Response

### Éxito (200 OK)

Descarga directa del archivo `.docx`

**Headers:**
```
Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document
Content-Disposition: attachment; filename="entrega_cheque_123_1234567890.docx"
```

### Error (500)

```json
{
  "ok": false,
  "error": "No se encontró el administrado asociado al expediente coactivo"
}
```

---

## 📝 Variables del Documento

El documento generado incluye las siguientes variables reemplazadas:

| Variable | Fuente | Ejemplo |
|----------|--------|---------|
| `${cod_expediente_coactivo}` | Base de datos (Coactivo) | `"COA-2024-00001"` |
| `${nombre_completo}` | Base de datos (Administrado) | `"JUAN PÉREZ GARCÍA"` |
| `${documento}` | Base de datos (Administrado) | `"12345678"` o `"20123456789"` |
| `${direccion}` | Base de datos (Administrado) | `"AV. PRINCIPAL 123"` |
| `${entidad_bancaria_nombre}` | Base de datos (EntidadBancaria) | `"BANCO DE CREDITO DEL PERÚ"` |
| `${fecha_actual}` | Fecha actual del sistema | `"15 ENERO DEL 2024"` |
| `${fecha_recepcion_bancaria}` | Request body | `"15/01/2024"` |
| `${fecha_resolucion_dos}` | **Base de datos (DocumentoCoactivo tipo 2)** | `"20/12/2023"` |
| `${monto_retencion}` | Request body | `"1,500.50"` |
| `${cod_orden_bancario}` | Request body | `"ORD-2024-00123"` |
| `${ejecutor_coactivo}` | Base de datos (Coactivo) | `"MUNICIPALIDAD DISTRITAL DE NUEVO CHIMBOTE"` |

---

## 🔧 Ejemplo de Uso

### cURL

```bash
curl -X POST "http://127.0.0.1:8000/api/v1/auth/coactivos/1/documentos/generar-entrega-cheque" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "id_entidad_bancaria": 2,
    "fecha_recepcion_bancaria": "2024-01-15",
    "monto_retencion": 1500.50,
    "cod_orden_bancario": "ORD-2024-00123"
  }' \
  --output entrega_cheque.docx
```

### JavaScript (Fetch)

```javascript
const response = await fetch('/api/v1/auth/coactivos/1/documentos/generar-entrega-cheque', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    id_entidad_bancaria: 2,
    fecha_recepcion_bancaria: '2024-01-15',
    monto_retencion: 1500.50,
    cod_orden_bancario: 'ORD-2024-00123'
  })
});

const blob = await response.blob();
const url = window.URL.createObjectURL(blob);
const a = document.createElement('a');
a.href = url;
a.download = 'entrega_cheque.docx';
a.click();
```

### Axios

```javascript
const response = await axios.post(
  '/api/v1/auth/coactivos/1/documentos/generar-entrega-cheque',
  {
    id_entidad_bancaria: 2,
    fecha_recepcion_bancaria: '2024-01-15',
    monto_retencion: 1500.50,
    cod_orden_bancario: 'ORD-2024-00123'
  },
  {
    headers: {
      Authorization: `Bearer ${token}`
    },
    responseType: 'blob'
  }
);

const url = window.URL.createObjectURL(new Blob([response.data]));
const link = document.createElement('a');
link.href = url;
link.setAttribute('download', 'entrega_cheque.docx');
document.body.appendChild(link);
link.click();
link.remove();
```

---

## 🚨 Errores Comunes

| Código | Mensaje | Solución |
|--------|---------|----------|
| 404 | `Coactivo no encontrado` | Verificar que el `coactivoId` sea válido |
| 404 | `EntidadBancaria no encontrada` | Verificar que el `id_entidad_bancaria` exista |
| 500 | `No se encontró el administrado` | El expediente coactivo no tiene administrado vinculado |
| 500 | `No se encontró el documento de Resolución DOS` | El expediente coactivo no tiene un documento con `id_tipo_doc_coactivo = 2` |
| 500 | `Plantilla no encontrada` | Verificar que `entrega_cheque.docx` exista en `storage/app/plantillas/` |
| 422 | `Validation errors` | Revisar formato de fechas (YYYY-MM-DD) y valores numéricos |

---

## 📋 Validaciones

### id_entidad_bancaria
- ✅ Requerido
- ✅ Debe ser un número entero
- ✅ Debe existir en la tabla `entidad_bancaria`

### fecha_recepcion_bancaria
- ✅ Requerido
- ✅ Debe ser una fecha válida (formato: YYYY-MM-DD)

### monto_retencion
- ✅ Requerido
- ✅ Debe ser numérico
- ✅ Debe ser mayor a 0

### cod_orden_bancario
- ✅ Requerido
- ✅ Máximo 100 caracteres

---

## 🏦 Entidades Bancarias Disponibles

Para obtener la lista de entidades bancarias:

**GET** `/v1/auth/entidades-bancarias`

Ejemplo de respuesta:
```json
{
  "ok": true,
  "data": [
    {
      "id_entidad_bancaria": 1,
      "nombre": "BBVA BANCO CONTINENTAL"
    },
    {
      "id_entidad_bancaria": 2,
      "nombre": "BANCO DE CREDITO DEL PERÚ"
    },
    {
      "id_entidad_bancaria": 3,
      "nombre": "BANCO INTERNACIONAL DEL PERU - INTERBANK"
    }
  ]
}
```

---

## 📄 Plantilla Requerida

**Ubicación:** `storage/app/plantillas/entrega_cheque.docx`

**Variables en la plantilla:**
- `${cod_expediente_coactivo}`
- `${nombre_completo}`
- `${documento}`
- `${direccion}`
- `${entidad_bancaria_nombre}`
- `${fecha_actual}`
- `${fecha_recepcion_bancaria}`
- `${fecha_resolucion_dos}`
- `${monto_retencion}`
- `${cod_orden_bancario}`
- `${ejecutor_coactivo}`

---

## ✅ Checklist de Implementación

### Backend
- [x] Request validator creado (`GenerarEntregaChequeRequest.php`)
- [x] Método en servicio (`generarEntregaCheque()`)
- [x] Endpoint en controller (`generarEntregaCheque()`)
- [x] Ruta registrada en `api.php`
- [x] Plantilla configurada en `config/templates.php`

### Frontend
- [ ] Formulario con campos requeridos
- [ ] Selector de entidad bancaria (dropdown/select)
- [ ] Date pickers para fechas
- [ ] Input numérico para monto
- [ ] Validación de formulario
- [ ] Descarga automática del documento generado
- [ ] Manejo de errores

---

**Creado:** Diciembre 2025  
**Versión API:** v1
