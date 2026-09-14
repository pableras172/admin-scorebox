# Research & Technical Decisions: Campañas de Email Marketing

**Feature**: `002-marketing-email-campaigns` | **Date**: 2026-09-13

## 1. Resolución de Audiencia y Protección de Firestore

### Problema
Para enviar una campaña a "Solo usuarios Free" o "Todos los usuarios", necesitamos obtener las direcciones de correo de los usuarios en Firestore sin saturar la memoria de PHP ni incurrir en costes desmedidos de lectura de Google Cloud.

### Decisión
Se crea el servicio `CampaignAudienceResolver`:
- Invoca `FirestoreUserGateway::list([], $limit)` utilizando paginación por lotes de hasta 100 documentos por solicitud.
- Filtra en memoria los documentos según el `target_segment`:
  - `all`: usuarios con `email` presente y sintácticamente válido.
  - `free`: usuarios con `isPremium === false`.
  - `premium`: usuarios con `isPremium === true`.
- Carga en memoria la lista de exclusión desde SQLite mediante `EmailUnsubscribe::pluck('email')->all()` (convertidos a minúsculas y almacenados en un Set/HashTable para comprobación en tiempo constante `O(1)`).
- Devuelve una colección de objetos tipados `{ email: string, name: string }` aptos para el envío.

## 2. Generación de Enlaces de Desuscripción Firmados

### Problema
El enlace de baja voluntaria debe poder procesarse sin exigir login del usuario (ya que es un usuario móvil que no tiene cuenta en el panel web) pero evitando que atacantes desuscriban arbitrariamente a otros usuarios alterando el parámetro `?email=...`.

### Decisión
Se utilizan **URLs firmadas de Laravel** (`URL::signedRoute`):
```php
URL::signedRoute('marketing.unsubscribe', [
    'email' => strtolower($userEmail)
]);
```
- Laravel genera una firma HMAC-SHA256 utilizando el secreto `APP_KEY` del backend.
- La ruta web está protegida por el middleware nativo `signed` (`Illuminate\Routing\Middleware\ValidateSignature`).
- Si la URL o el parámetro de correo son alterados en un solo carácter, el middleware intercepta la petición y aborta con respuesta segura sin ejecutar ninguna baja.

## 3. Soporte de Cabeceras Estándar RFC 8058 (One-Click Unsubscribe)

### Problema
Los proveedores de correo electrónico (Gmail, Yahoo, Outlook) exigen desde 2024 que los correos masivos incluyan cabeceras técnicas `List-Unsubscribe` para permitir que el cliente de correo muestre el botón de baja en su propia barra de herramientas sin que el correo sea marcado como spam.

### Decisión
En el `MarketingCampaignMailable`:
```php
public function headers(): Headers
{
    $unsubscribeUrl = URL::signedRoute('marketing.unsubscribe', ['email' => $this->recipientEmail]);
    $postUrl = route('marketing.unsubscribe.post', ['email' => $this->recipientEmail]);

    return new Headers(
        text: [
            'List-Unsubscribe' => "<{$unsubscribeUrl}>",
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ],
    );
}
```
Esto garantiza la máxima reputación de entrega y el cumplimiento íntegro de las directivas internacionales.

## 4. Ejecución Asíncrona y Resiliencia en Colas

### Problema
Un envío a cientos de usuarios a través de SMTP o APIs de email puede tardar varios minutos y está expuesto a desconexiones transitorias de red.

### Decisión
- El controlador/acción de Filament crea el registro de campaña en estado `queued` y despacha `SendMarketingCampaignJob::dispatch($campaignId)`.
- El Job utiliza la tabla de base de datos local `jobs` (driver `database`).
- Configuración de tolerancia a fallos:
  - `$tries = 3`: hasta 3 reintentos automáticos.
  - `$backoff = [10, 30, 60]`: retroceso de 10 segundos, 30 segundos y 1 minuto.
- Si un correo individual es rechazado permanentemente por el proveedor, se captura la excepción, se incrementa `failed_count`, se anota en el log de Laravel y se continúa con el siguiente destinatario sin cancelar la campaña.

