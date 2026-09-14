# Idea Intake: Campañas y Envíos de Correo de Marketing

- **Slug**: marketing-email-campaigns
- **Created**: 2026-09-13
- **Source**: pasted text
- **Type**: new-capability

## Idea (as captured)

> "El panel administrativo contara con una nueva funcionalidad con propositos de marketing. El primer proposito debera ser poder enviar correos a los usuarios con ejemplos de funcionamiento, ofertas, novedades de la app.
> Debera contar con una opcion para que los usuarios puedan desuscribirse de las novedades. Los emails se pueden enviar a distintos grupos de usuarios:
>
> todos, usuarios premium, usuarios free...etc"

## Restated

Se propone incorporar en el panel administrativo un módulo de marketing para el envío segmentado de correos electrónicos (novedades, ofertas y tutoriales de uso) a los usuarios registrados de ScoreBox. La funcionalidad contemplará la selección de audiencias objetivo (todos, usuarios premium, usuarios free, etc.) y un mecanismo de desuscripción voluntaria para respetar las preferencias de privacidad y comunicación.

## Origin & Context

- **Raised by**: Administrador de ScoreBox (pableras172)
- **Trigger**: Necesidad de potenciar la retención, comunicación de novedades de producto y fomento de suscripciones Premium mediante campañas de correo directamente gestionadas desde el panel administrativo.

## First-Glance Unknowns

- [NEEDS CLARIFICATION: ¿Qué proveedor o servicio de correo (ESP) se utilizará para la entrega fiable de correos masivos (ej. Resend, Mailgun, AWS SES, Brevo, SMTP)?]
- [NEEDS CLARIFICATION: ¿Dónde se persistirá la preferencia de desuscripción de marketing de cada usuario (atributo en Firestore del usuario, tabla de supresión local en SQLite/MySQL de Laravel o lista de exclusión en el proveedor de emails)?]
- [NEEDS CLARIFICATION: ¿Cómo se compondrán los correos en la interfaz de Filament (editor enriquecido RichEditor/Markdown en el panel, plantillas fijas en Blade o plantillas remotas)?]
- [NEEDS CLARIFICATION: ¿Cómo se gestionará el procesamiento masivo en segundo plano mediante Laravel Queues para evitar bloqueos y minimizar el número de lecturas en Firestore?]
- [NEEDS CLARIFICATION: ¿Qué filtros adicionales de segmentación podrían ser convenientes más allá del estado de suscripción (ej. país, instrumento principal, fecha de registro)?]

