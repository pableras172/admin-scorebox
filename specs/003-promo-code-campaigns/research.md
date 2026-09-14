# Research & Technical Decisions: Distribución de Códigos Promocionales

**Feature**: `003-promo-code-campaigns` | **Date**: 2026-09-13

## 1. Importación Masiva y Procesamiento Eficiente del CSV de Google Play

### Problema
Google Play Console permite exportar códigos promocionales en archivos `.csv` con la cabecera `Promotion code` seguida de cientos o miles de códigos alfanuméricos de 23-25 caracteres (ej. `4DUJQ6ASZ392Z0EARXBLL64`). La carga debe ser rápida, admitir archivos con ligeras variaciones de cabecera o espacios, evitar bloqueos de memoria y descartar duplicados de forma idempotente.

### Decisión
Implementar una acción de importación personalizada en la cabecera de la tabla de Filament (`HeaderAction::make('importCsv')` en `PromotionalCodesTable`):
- Utiliza un formulario modal con un campo `FileUpload` aceptando extensiones `.csv` y `.txt`.
- Procesa el archivo en streaming mediante `SplFileObject` línea por línea, evitando cargar todo el archivo en la memoria RAM de PHP (`O(1)` memory consumption).
- Normaliza cada línea: recorte de espacios (`trim`), eliminación de comillas y conversión a mayúsculas.
- Detecta y descarta automáticamente la fila de encabezado (por ejemplo si contiene "promotion", "code" o "código").
- Inserción masiva por lotes utilizando `DB::table('promotional_codes')->insertOrIgnore($batch)` en bloques de 500 registros con marcas de tiempo (`created_at`, `updated_at`).
- Notifica al administrador el resultado con métricas claras: *"X códigos importados correctamente, Y códigos duplicados o vacíos omitidos"*.

### Alternativas Consideradas
- **Filament standard ImportAction con Jobs**: Requiere configuración de esquemas complejos de mapeo columna por columna en colas asíncronas, excesivo para un CSV de una sola columna y añade latencia innecesaria para lotes de 1.000 a 5.000 códigos.
- **Inserción individual con Eloquent**: Generaría miles de queries individuales a la base de datos (lento e ineficiente). Se descarta en favor de `insertOrIgnore` en bloques.

---

## 2. Asignación Atómica y Segura de Códigos Promocionales en Cola

### Problema
Al enviar una campaña a cientos de usuarios, el proceso asíncrono (`SendMarketingCampaignJob`) debe asignar un código único a cada destinatario sin que existan condiciones de carrera ni riesgos de asignar el mismo código dos veces si se reintenta el job o se ejecutan múltiples workers concurrentemente. Además, debe satisfacer la regla acordada para usuarios que ya recibieron código anteriormente.

### Decisión
1. **Lógica de Asignación por Destinatario**:
   - Se evalúa si el destinatario ya cuenta con un código en `promotional_codes`:
     ```php
     $existingCode = PromotionalCode::where('assigned_email', $recipient->email)->first();
     ```
   - Si existe código previo y la campaña tiene `exclude_previous_promo_recipients = false`:
     - Si `$recipient->isPremium === false`, se reutiliza su código existente: `$codeToInject = $existingCode->code` (sin consumir nuevo stock libre).
     - Si `$recipient->isPremium === true`, se omite el envío a este destinatario.
   - Si no existe código previo:
     - Se reserva atómicamente un código libre usando una transacción de base de datos con bloqueo pesimista (`lockForUpdate`):
       ```php
       $assignedRecord = DB::transaction(function () use ($recipient, $campaign) {
           $code = PromotionalCode::whereNull('assigned_email')
               ->lockForUpdate()
               ->first();

           if (! $code) {
               return null;
           }

           $code->update([
               'assigned_email' => $recipient->email,
               'assigned_uid' => $recipient->uid ?? null,
               'marketing_campaign_id' => $campaign->id,
               'assigned_at' => now(),
           ]);

           return $code;
       });
       ```
2. Si por algún motivo sobrevenido se agotan los códigos libres (`$assignedRecord === null`), el job detiene el envío para los destinatarios restantes, marca la campaña con advertencia y registra el evento en los logs de auditoría sin despachar correos vacíos.

### Alternativas Consideradas
- **Preasignación masiva antes del envío**: Asignar todos los códigos en lote antes de comenzar a enviar correos. Desventaja: si la campaña se cancela o falla en el primer correo, cientos de códigos quedarían "quemados" y asociados a usuarios que nunca recibieron el mensaje. La asignación transaccional por destinatario es mucho más robusta y segura.

---

## 3. Aislamiento y Simulación en Modo de Prueba (`is_test = true`)

### Problema
Los administradores necesitan probar la visualización y maquetación de los correos en sus propias bandejas de entrada antes de lanzar la campaña, pero sin gastar licencias comerciales reales de Google Play.

### Decisión
En `SendMarketingCampaignJob`, si `$campaign->is_test` es verdadero:
- No se consulta ni se muta la tabla `promotional_codes`.
- Se genera al vuelo un código simulado determinista con prefijo identificable: `'PROMO-TEST-' . strtoupper(Str::random(8))`.
- Se sustituye dicho código en `{{promotioncode}}` tanto en el cuerpo como en posibles enlaces directos.
- La tabla de códigos y el stock real permanecen al 100% inalterados.

---

## 4. Sustitución de `{{promotioncode}}` y Enlace Directo de Google Play

### Problema
El usuario final necesita poder canjear el código sin fricciones en su móvil Android.

### Decisión
- Se realiza sustitución en texto plano del marcador `{{promotioncode}}` por el código alfanumérico.
- El administrador puede escribir en el editor enriquecido texto libre, por ejemplo:
  ```html
  <p>Tu código de activación es: <strong>{{promotioncode}}</strong></p>
  <p><a href="https://play.google.com/redeem?code={{promotioncode}}">Canjear automáticamente en Play Store</a></p>
  ```
- Al ser texto plano, la sustitución con `str_replace` es limpia y funciona tanto en elementos HTML visuales como dentro del atributo `href` de enlaces.

---

## 5. Prevención y Bloqueo por Déficit de Stock en la Interfaz

### Problema
Evitar que el administrador inicie un envío masivo si no dispone de suficientes códigos para abastecer a la audiencia seleccionada.

### Decisión
En la acción `send` de `MarketingCampaignsTable`:
- Para campañas donde `campaign_type === 'promotional_code'` y `!$record->is_test`:
  - Se calcula el número de destinatarios que requerirán un código nuevo (excluyendo desuscritos y, si aplica, los que ya tienen código previo).
  - Se cuenta el stock libre: `PromotionalCode::whereNull('assigned_email')->count()`.
  - Si `disponibles < necesarios`, el modal:
    - Informa del déficit: *"Stock insuficiente: se necesitan X códigos libres pero solo hay Y disponibles (faltan Z códigos)."*
    - Deshabilita la confirmación del envío.

