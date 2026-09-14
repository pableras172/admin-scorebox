# Contract: Envío de Campañas de Email Marketing

**Feature**: `002-marketing-email-campaigns` | **Date**: 2026-09-13

## 1. Job de Despacho de Campaña

**Clase**: `App\Jobs\SendMarketingCampaignJob`

### Entrada (Payload del Job)
```php
public int $campaignId;
```

### Contrato de Ejecución
1. **Validación de Estado**:
   - Localiza la campaña mediante `MarketingCampaign::findOrFail($this->campaignId)`.
   - Si la campaña no se encuentra en estado `draft` o `queued`, aborta la ejecución para evitar reenvíos accidentales.
   - Transiciona inmediatamente a `status = 'sending'`.
2. **Extracción de Destinatarios**:
   - Invoca `CampaignAudienceResolver::resolve($campaign->target_segment)`.
   - El resolver consulta `FirestoreUserGateway::list([], 100)` y excluye direcciones presentes en `EmailUnsubscribe`.
   - Asigna `$campaign->recipients_count = $recipients->count()`.
3. **Generación y Despacho**:
   - Itera sobre cada `CampaignRecipient`:
     - Genera URL firmada: `URL::signedRoute('marketing.unsubscribe', ['email' => $recipient->email])`.
     - Reemplaza `{{name}}` en asunto y cuerpo por `$recipient->name`.
     - Envía `MarketingCampaignMailable` a través de `Mail::mailer()->to($recipient->email)`.
     - Incrementa `$campaign->sent_count`.
4. **Finalización y Auditoría**:
   - Si se completan todos los envíos:
     - `$campaign->status = 'sent'`.
     - `$campaign->sent_at = now()`.
     - Guarda en base de datos y emite log `info` con el resumen del envío.
   - Si ocurre un fallo no recuperable:
     - `$campaign->status = 'failed'`.
     - Emite log `error` con la traza de excepción.

### Parámetros de Resiliencia en Cola
- `public int $tries = 3;`
- `public array $backoff = [10, 30, 60];`
- `public int $timeout = 600;`

