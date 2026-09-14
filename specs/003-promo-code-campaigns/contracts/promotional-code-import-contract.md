# Contract: Importación Masiva de Códigos Promocionales (CSV)

**Feature**: `003-promo-code-campaigns` | **Date**: 2026-09-13

## 1. Acción de Importación en Filament

**Ubicación**: Cabecera de la tabla en `App\Filament\Resources\PromotionalCodes\Tables\PromotionalCodesTable`
**Identificador de Acción**: `importCsv`

### Entrada (Formulario Modal)
- `file`: Archivo cargado por el usuario (`FileUpload`).
  - Extensiones admitidas: `.csv`, `.txt`
  - MIME types: `text/csv`, `text/plain`
  - Obligatorio (`required`)

### Formato Soportado de Archivo
1. **CSV estándar de Google Play Console**:
   ```csv
   Promotion code
   4DUJQ6ASZ392Z0EARXBLL64
   1V9L9TGAP3FZT5RRFCQXW8L
   HCMNAPQA2XW42ZQC44GWYZX
   ```
2. **Lista simple de códigos (sin cabecera)**:
   ```text
   4DUJQ6ASZ392Z0EARXBLL64
   1V9L9TGAP3FZT5RRFCQXW8L
   HCMNAPQA2XW42ZQC44GWYZX
   ```

### Reglas de Procesamiento
1. **Normalización por Línea**:
   - `trim()` para eliminar retornos de carro `\r\n` y espacios.
   - Eliminación de comillas dobles o simples circundantes.
   - Descarte de líneas vacías.
   - Detección de cabecera: Si la línea coincide (case-insensitive) con patrones como `"promotion code"`, `"code"` o `"código"`, se omite.
2. **Inserción Idempotente**:
   - Agrupación en fragmentos de 500 registros.
   - Inserción con `DB::table('promotional_codes')->insertOrIgnore($chunk)`.
3. **Respuesta y Notificación**:
   - Calcula:
     - `totalProcessed`: Total de códigos leídos del archivo.
     - `insertedCount`: Registros efectivamente creados.
     - `skippedCount`: Duplicados u omitidos (`totalProcessed - insertedCount`).
   - Notificación Filament de éxito:
     - Título: *"Importación completada"*
     - Cuerpo: *"Se han importado {X} códigos nuevos. {Y} códigos duplicados fueron omitidos."*

