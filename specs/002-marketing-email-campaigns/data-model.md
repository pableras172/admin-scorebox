# Data Model: Campañas de Email Marketing y Desuscripción

**Feature**: `002-marketing-email-campaigns` | **Date**: 2026-09-13

## 1. Tablas en Base de Datos Local (SQLite)

### Tabla `marketing_campaigns`

Almacena las campañas redactadas y enviadas por el administrador en el panel de Filament.

| Columna | Tipo | Nulo | Por defecto | Descripción |
| :--- | :--- | :---: | :---: | :--- |
| `id` | `BIGINT UNSIGNED` | No | Auto-inc | Identificador único de la campaña |
| `subject` | `VARCHAR(255)` | No | - | Asunto del correo electrónico |
| `content` | `LONGTEXT` | No | - | Contenido del mensaje (HTML generado por RichEditor) |
| `target_segment` | `VARCHAR(50)` | No | `'all'` | Audiencia objetivo: `'all'`, `'free'`, `'premium'` |
| `status` | `VARCHAR(50)` | No | `'draft'` | Estado: `'draft'`, `'queued'`, `'sending'`, `'sent'`, `'failed'` |
| `recipients_count` | `INTEGER` | No | `0` | Número total de destinatarios calculados para el envío |
| `sent_count` | `INTEGER` | No | `0` | Número de correos entregados exitosamente |
| `failed_count` | `INTEGER` | No | `0` | Número de correos con fallo de entrega |
| `sent_at` | `TIMESTAMP` | Sí | `NULL` | Fecha y hora en la que se completó el envío |
| `created_at` | `TIMESTAMP` | Sí | `NULL` | Fecha de creación del borrador |
| `updated_at` | `TIMESTAMP` | Sí | `NULL` | Fecha de última modificación |

### Tabla `email_unsubscribes`

Lista de supresión local para garantizar que ningún usuario dado de baja vuelva a recibir comunicaciones de marketing.

| Columna | Tipo | Nulo | Por defecto | Descripción |
| :--- | :--- | :---: | :---: | :--- |
| `id` | `BIGINT UNSIGNED` | No | Auto-inc | Identificador único del registro de baja |
| `email` | `VARCHAR(255)` | No | - | Dirección de correo excluida (índice único, minúsculas) |
| `reason` | `VARCHAR(255)` | Sí | `NULL` | Motivo de la baja si fue aportado |
| `source` | `VARCHAR(50)` | No | `'link'` | Origen de la baja: `'link'` (enlace email), `'manual'` (panel admin), `'header'` (RFC 8058) |
| `unsubscribed_at` | `TIMESTAMP` | No | `CURRENT_TIMESTAMP` | Fecha y hora en la que se efectuó la desuscripción |
| `created_at` | `TIMESTAMP` | Sí | `NULL` | Auditoría de creación |
| `updated_at` | `TIMESTAMP` | Sí | `NULL` | Auditoría de actualización |

---

## 2. Modelos Eloquent

### `App\Models\MarketingCampaign`
- **Casts**:
  - `status` => `string`
  - `target_segment` => `string`
  - `recipients_count` => `integer`
  - `sent_count` => `integer`
  - `failed_count` => `integer`
  - `sent_at` => `datetime`
- **Scopes útiles**:
  - `scopeDrafts($query)`: campañas en borrador.
  - `scopeSent($query)`: campañas enviadas con éxito.
- **Constantes de estado**:
  - `STATUS_DRAFT = 'draft'`
  - `STATUS_QUEUED = 'queued'`
  - `STATUS_SENDING = 'sending'`
  - `STATUS_SENT = 'sent'`
  - `STATUS_FAILED = 'failed'`

### `App\Models\EmailUnsubscribe`
- **Casts**:
  - `unsubscribed_at` => `datetime`
- **Métodos estáticos clave**:
  - `isUnsubscribed(string $email): bool`: Comprueba si una dirección está en la lista de supresión.
  - `unsubscribe(string $email, string $source = 'link'): self`: Registra la baja de forma idempotente (`firstOrCreate`).
  - `resubscribe(string $email): bool`: Elimina la dirección de la lista si el usuario revierte la baja.

---

## 3. Estructuras de Datos en Memoria (DTOs)

### `App\Services\Marketing\CampaignRecipient`
```php
readonly class CampaignRecipient
{
    public function __construct(
        public string $email,
        public string $name,
        public bool $isPremium,
    ) {}
}
```
Representa a un destinatario válido filtrado y listo para recibir el correo tras superar la lista de supresión.

