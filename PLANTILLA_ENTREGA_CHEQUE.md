# Plantilla: entrega_cheque.docx

## 📝 Ubicación
`storage/app/plantillas/entrega_cheque.docx`

## 🎯 Variables a incluir en el documento Word

Crea un documento Word con el siguiente contenido y reemplaza las variables con `${nombre_variable}`:

---

**RESOLUCIÓN DE EJECUCION COACTIVA Nº 03**

**EXPEDIENTE NÚMERO:** `${cod_expediente_coactivo}`  
**EJECUTANTE:** MUNICIPALIDAD DISTRITAL DE NUEVO CHIMBOTE.  
**OBLIGADO:** `${nombre_completo}`.  
**MATERIA:** NO TRIBUTARIA.  
**RUC/DNI:** `${documento}`  
**DOMICILIO:** `${direccion}`.  
**ENTIDAD BANCARIA:** `${entidad_bancaria_nombre}`.

Nuevo Chimbote, `${fecha_actual}`.

---

### CÉDULA DE NOTIFICACIÓN

Hago saber a Usted que el señor ejecutor coactivo, ha resuelto lo siguiente:

**VISTO:** El documento de la entidad financiera `${entidad_bancaria_nombre}`, de fecha de recepción `${fecha_recepcion_bancaria}` y 

**ATENDIENDO:** Que, con resolución DOS de fecha `${fecha_resolucion_dos}`, se dispuso trabar la medida de embargo definitivo en forma de retención sobre las cuentas, depósitos y otros que tuviera el obligado `${nombre_completo}`, en las Entidades financieras y bancarias de esta ciudad; que el `${entidad_bancaria_nombre}`, informa que ha procedido a retener la suma ascendente a S/. `${monto_retencion}` Soles, ordenado por nuestro Despacho de la cuenta del obligado que mantiene en la entidad financiera, por tal motivo es pertinente solicitar al `${entidad_bancaria_nombre}`, cumpla con poner a disposición de esta Ejecutoria Coactiva el monto materia de embargo por la suma a S/. `${monto_retencion}` Soles.

---

**EN CONSECUENCIA:** SE RESUELVE DISPONER que el `${entidad_bancaria_nombre}`, cumpla con poner a disposición de esta Gerencia de Ejecución Coactiva de la Municipalidad Distrital de Nuevo Chimbote, del obligado `${nombre_completo}`, con DNI N° `${documento}`. En cheque de Gerencia y/o cheque Certificado a nombre de la DIRECCION GENERAL DEL TESORO PÚBLICO, sobre el monto ascendente a S/. `${monto_retencion}` Soles; retenido mediante Orden N° `${cod_orden_bancario}`, Fdo. Abog. `${ejecutor_coactivo}` - Ejecutor Coactivo.

---

## ✅ Pasos para crear la plantilla:

1. Abre Microsoft Word
2. Copia el texto anterior
3. Reemplaza cada valor entre comillas con la variable correspondiente usando formato `${variable}`
4. Ejemplo:
   - ❌ `"COA-2024-00001"`
   - ✅ `${cod_expediente_coactivo}`
5. Aplica formato (negrita, mayúsculas, etc.) según tu necesidad
6. Guarda como: `entrega_cheque.docx`
7. Coloca el archivo en: `storage/app/plantillas/entrega_cheque.docx`

## 📋 Lista de Variables

| Variable | Descripción |
|----------|-------------|
| `${cod_expediente_coactivo}` | Código del expediente coactivo |
| `${nombre_completo}` | Nombre completo del administrado (MAYÚSCULAS) |
| `${documento}` | DNI o RUC del administrado |
| `${direccion}` | Domicilio del administrado (MAYÚSCULAS) |
| `${entidad_bancaria_nombre}` | Nombre de la entidad bancaria (MAYÚSCULAS) |
| `${fecha_actual}` | Fecha actual (formato: "15 ENERO DEL 2024") |
| `${fecha_recepcion_bancaria}` | Fecha recepción bancaria (formato: "15/01/2024") |
| `${fecha_resolucion_dos}` | Fecha resolución DOS (formato: "20/12/2023") |
| `${monto_retencion}` | Monto retenido (formato: "1,500.50") |
| `${cod_orden_bancario}` | Código de orden bancaria |
| `${ejecutor_coactivo}` | Nombre del ejecutor coactivo (MAYÚSCULAS) |

## ⚠️ Importante

- Todas las variables deben estar escritas EXACTAMENTE como se muestran (con las llaves `${}`)
- Respeta mayúsculas y minúsculas en los nombres de variables
- No agregues espacios dentro de las llaves
- El sistema reemplazará automáticamente cada variable con su valor real

---

**Nota:** Una vez creada la plantilla, prueba el endpoint para verificar que todas las variables se reemplazan correctamente.
