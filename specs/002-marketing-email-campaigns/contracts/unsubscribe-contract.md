# Contract: Endpoints y Flujo de Desuscripción Voluntaria

**Feature**: `002-marketing-email-campaigns` | **Date**: 2026-09-13

## 1. Endpoint Web de Desuscripción en 1 Clic

**Método**: `GET`  
**Ruta**: `/marketing/unsubscribe`  
**Nombre de ruta**: `marketing.unsubscribe`  
**Middleware**: `signed` (`Illuminate\Routing\Middleware\ValidateSignature`)

### Parámetros de Consulta (Query String)
- `email` (string, obligatorio, en minúsculas): Dirección de correo del destinatario.
- `signature` (string, obligatorio): Firma HMAC generada por Laravel.

### Comportamiento
1. Si la firma es inválida o ha expirado:
   - Responde con HTTP 403 / vista amigable de firma inválida.
2. Si la firma es válida:
   - Registra en `email_unsubscribes` (idempotente vía `firstOrCreate`):
     ```php
     EmailUnsubscribe::firstOrCreate(
         ['email' => strtolower($email)],
         ['reason' => 'user_click', 'source' => 'link', 'unsubscribed_at' => now()]
     );
     ```
   - Renderiza la vista `marketing/unsubscribed.blade.php` con código HTTP 200.
   - La vista muestra:
     - Título: *"Te has dado de baja correctamente"*.
     - Mensaje: *"No recibirás más correos comerciales ni novedades de ScoreBox a la dirección {email}"*.
     - Botón de reversión: Formulario `POST` a `/marketing/resubscribe` con campo oculto firmado para reactivar si fue por error.

---

## 2. Endpoint de Reversión (Volver a Suscribirse)

**Método**: `POST`  
**Ruta**: `/marketing/resubscribe`  
**Nombre de ruta**: `marketing.resubscribe`  
**Middleware**: `signed`

### Comportamiento
- Busca el email en `email_unsubscribes` y lo elimina (`delete()`).
- Renderiza la vista `marketing/resubscribed.blade.php` con mensaje de confirmación de reactivación.

---

## 3. Endpoint RFC 8058 (One-Click Unsubscribe por POST)

**Método**: `POST`  
**Ruta**: `/marketing/unsubscribe`  
**Nombre de ruta**: `marketing.unsubscribe.post`  
**Middleware**: `signed`

### Cabeceras de Petición
- `List-Unsubscribe=One-Click` en el cuerpo de la petición según estándar RFC 8058.

### Comportamiento
- Inserta el registro con `source = 'header'`.
- Devuelve respuesta HTTP `200 OK` sin contenido HTML para clientes de correo automatizados (Gmail/Yahoo).

