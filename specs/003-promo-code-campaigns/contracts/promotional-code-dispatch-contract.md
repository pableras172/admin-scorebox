# Contract: Asignación y Despacho de Códigos Promocionales

**Feature**: `003-promo-code-campaigns` | **Date**: 2026-09-13

## 1. Job de Despacho de Campaña

**Clase**: `App\Jobs\SendMarketingCampaignJob`

### Entrada
- `public int $campaignId`: Identificador de la campaña en base de datos.

### Contrato de Ejecución para Campañas de Tipo `promotional_code`

1. **Resolución de Audiencia Inicial**:
   - Obtiene los destinatarios mediante `CampaignAudienceResolver::resolve($campaign->target_segment, (bool) $campaign->is_test)`.
   - Si `$campaign->isPromotionalCode()` y `!$campaign->is_test`:
     - Se comprueba la regla de exclusión de usuarios que ya recibieron código (`$campaign->exclude_previous_promo_recipients`):
       - Si es `true`: Se descartan de la lista todos los destinatarios cuyos emails existan en `PromotionalCode::pluck('assigned_email')`.
       - Si es `false`:
         - Para cada destinatario que ya tenga un código en `promotional_codes`:
           - Si `$recipient->isPremium === true`, se omite.
           - Si `$recipient->isPremium === false`, se mantiene en la lista para reenvío del mismo código.

2. **Validación de Stock Previo al Bucle**:
   - Calcula cuántos destinatarios de la lista requieren asignación de un código nuevo (los que no tienen código previo).
   - Comprueba `PromotionalCode::whereNull('assigned_email')->count()`.
   - Si los códigos libres son insuficientes, marca la campaña como `failed` con mensaje explicativo en log y aborta el proceso sin enviar ningún correo.

3. **Bucle de Envío e Inyección de Código**:
   - Por cada destinatario:
     - **Caso Modo Prueba (`is_test = true`)**:
       - Inyecta un código simulado determinista: `'PROMO-TEST-' . strtoupper(Str::random(8))`.
       - No consulta ni modifica la tabla `promotional_codes`.
     - **Caso Modo Producción (`is_test = false`)**:
       - Si el destinatario ya tenía un código asignado previamente y `isPremium === false`:
         - Se recupera su código existente: `$codeToInject = $existingPromoCode->code`.
       - Si el destinatario no tenía código:
         - Mediante transacción de base de datos con `lockForUpdate()`, se selecciona el primer código disponible sin asignar (`assigned_email IS NULL`), se actualiza atómicamente con `assigned_email = $recipient->email`, `assigned_uid = $recipient->uid`, `marketing_campaign_id = $campaign->id`, `assigned_at = now()`, y se obtiene `$codeToInject = $codeRecord->code`.
     - **Sustitución de Variables**:
       - `$personalizedSubject = str_replace(['{{name}}', '{{promotioncode}}'], [$recipient->name, $codeToInject], $campaign->subject);`
       - `$personalizedContent = str_replace(['{{name}}', '{{promotioncode}}'], [e($recipient->name), $codeToInject], $campaign->content);`
     - **Envío Mailable**:
       - `Mail::to($recipient->email)->send(new MarketingCampaignMailable(...));`
       - Incrementa `$sentCount`.
     - **Captura de Fallos de Entrega**:
       - Si `Mail::send` arroja una excepción, se incrementa `$failedCount` y se registra en log. El código asignado permanece vinculado al usuario en la base de datos para evitar reasignaciones duplicadas por errores de red.

