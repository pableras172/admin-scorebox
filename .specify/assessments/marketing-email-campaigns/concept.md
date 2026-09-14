# Concept: Campañas y Envíos de Correo de Marketing

- **Slug**: marketing-email-campaigns
- **Created**: 2026-09-13
- **Recommended option**: Opción A — Envíos Directos desde Filament con Plantilla Base y Supresión Local

## Options

### Option A — Envíos Directos desde Filament con Plantilla Base y Supresión Local (Recomendada)
- **Sketch**: Un recurso intuitivo en Filament ("Campañas de Marketing") donde el administrador compone el asunto y contenido del correo con un editor enriquecido (RichEditor), selecciona el segmento destinatario (Todos, Usuarios Free, Usuarios Premium) y lanza el envío. Laravel encola el trabajo en segundo plano (`jobs` en SQLite) para consultar a los usuarios en Firestore en lotes, excluyendo de inmediato a los correos que figuren en la tabla local de bajas (`email_unsubscribes`). Cada correo enviado incluye un pie de página legal con enlace de desuscripción de un solo clic que registra el correo en la tabla local sin alterar Firestore.
- **Appetite**: `small` (3 a 5 días de desarrollo).
- **Trade-offs**: 
  - *Gana*: Implementación limpia, 100% integrada en Filament, sin costes adicionales de licencias SaaS externas, sin tocar el esquema de Firestore ni la app móvil, y con cumplimiento legal riguroso (RGPD / CAN-SPAM).
  - *Sacrifica*: No incluye métricas de apertura integradas en la interfaz de Filament en esta primera fase (el administrador puede consultar aperturas y clics en el panel del proveedor de correo configurado, ej. Resend o Mailgun).
- **Rabbit holes**: Querer incrustar un diseñador visual de bloques drag-and-drop complejo en Filament en vez de apoyarse en una plantilla Blade limpia y responsiva.

### Option B — Sincronización de Contactos hacia Plataforma Externa (SaaS: Brevo / Loops / Mailchimp)
- **Sketch**: En lugar de enviar desde el panel, Laravel sincroniza periódicamente la lista de usuarios de Firestore hacia una audiencia en una plataforma externa especializada de email marketing (como Brevo, Loops o Mailchimp). El administrador diseña y envía las campañas directamente desde la interfaz web del proveedor externo.
- **Appetite**: `medium` (2 a 3 semanas).
- **Trade-offs**:
  - *Gana*: Diseñador visual avanzado de la plataforma externa, métricas nativas y gestión automática de entregabilidad.
  - *Sacrifica*: Requiere pagar suscripciones recurrentes al crecer los contactos, añade fricción al tener que salir del panel de Filament para redactar y enviar, y requiere mantener webhooks y lógica de sincronización bidireccional para reflejar altas y bajas.
- **Rabbit holes**: Manejo de fallos en la sincronización continua de contactos y límites de coste por volumen de suscriptores en el proveedor externo.

### Option C — Módulo Completo con Métricas de Tracking y Constructor Visual Interno
- **Sketch**: Construir una solución completa de email marketing dentro del panel administrativo, incluyendo diseñador de plantillas personalizadas, motor de seguimiento de aperturas (tracking pixel) y redirección de clics mediante rutas de Laravel, registro de logs individuales por destinatario y panel de analítica.
- **Appetite**: `large` (4 a 6 semanas).
- **Trade-offs**:
  - *Gana*: Máximo control y visualización de todas las métricas sin salir del panel.
  - *Sacrifica*: Esfuerzo de desarrollo desproporcionado para una primera versión; los píxeles de tracking son a menudo bloqueados por clientes de correo modernos (Apple Mail Privacy Protection); riesgo de saturar la base de datos local con logs de tracking innecesarios.
- **Rabbit holes**: Gestión de quejas de spam (FBL), rebotes (bounces) y compatibilidad de renderizado en clientes de correo heredados (Outlook).

## Recommendation

Se recomienda firmemente la **Opción A (Envíos Directos desde Filament con Plantilla Base y Supresión Local)**.

- **Alineación con objetivos**: Permite al administrador redactar y enviar correos de novedades, tutoriales y ofertas en minutos desde Filament, con segmentación por `isPremium` y desuscripción instantánea.
- **Economía y estabilidad**: Respeta la Constitución del proyecto aislando los datos en Firestore, usando las colas nativas de Laravel y manteniendo un coste cero de infraestructura adicional en Firebase.
- **Evolución gradual**: Si en el futuro el volumen exige métricas más avanzadas, la base de datos y la lista de supresión de la Opción A serán 100% compatibles para evolucionar.

## Out of Scope (para la opción recomendada)

- Diseñador drag-and-drop de plantillas (se utilizará el editor de texto enriquecido estándar de Filament con una plantilla Blade envolvente profesional y responsiva).
- Analítica avanzada de clics/aperturas embebida en Filament (se consulta en el dashboard del proveedor de correo).
- Campañas de secuencias automatizadas (drip campaigns / autoresponders automáticos).
- Cualquier cambio en la aplicación móvil Android o en la estructura de documentos de Firestore.

## Assumptions to Validate

- El administrador cuenta con una cuenta activa en un proveedor de envío transaccional/masivo (ej. Resend, Brevo, Mailgun, Amazon SES) con credenciales SMTP o API Key configurables en `.env`.
- El dominio de envío cuenta o contará con los registros DNS básicos recomendados (SPF y DKIM) para garantizar la entregabilidad.
- La tabla SQLite local `email_unsubscribes` es la fuente de verdad definitiva para determinar quién no debe recibir correos de marketing.

