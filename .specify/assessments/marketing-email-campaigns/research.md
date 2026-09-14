# Idea Research: Campañas y Envíos de Correo de Marketing

- **Slug**: marketing-email-campaigns
- **Created**: 2026-09-13
- **Evidence confidence (overall)**: high

## Users & Demand

- **Audiencia potencial identificada**: Los usuarios de la app ScoreBox registrados con correo válido en Firestore representan el público objetivo para fidelización y venta de suscripciones PRO — [source: `App\Models\FirestoreUser` / `FirestoreUserGateway`, coleccion `users`] (confidence: high)
- **Segmentación por valor de usuario**: Existe una diferenciación clara en Firestore entre usuarios gratuitos (`isPremium = false`) y usuarios de pago (`isPremium = true`), lo que permite campañas diferenciadas (ej. promociones de conversión a PRO para gratuitos vs tutoriales avanzados para usuarios PRO) — [source: `App\Filament\Widgets\UsersPremiumVsFree.php`] (confidence: high)
- **Necesidad de educación del usuario**: ScoreBox cuenta con funciones avanzadas (OCR con IA, metrónomo flotante, división de PDFs, copia de seguridad en Drive) que muchos usuarios no descubren en su primera sesión, justificando el envío de secuencias de incorporación (onboarding) y consejos — [source: `MyMusicalScores/README.md`] (confidence: high)
- **Demanda observada de ofertas dinámicas**: El proyecto Android ya contempla promociones y descuentos dinámicos PRO (referenciado en spec `037-dynamic-pro-discounts-promotions`), por lo que contar con un canal de email marketing desde el admin amplifica directamente estas campañas — [source: `specs/037-dynamic-pro-discounts-promotions`] (confidence: high)

## Prior Art

- **Panel Administrativo Actual (`admin`)**: Ya implementa widgets de métricas agregadas (`UsesScoreBox`, `UsersPremiumVsFree`), consulta de usuarios en Firestore (`ScoreBoxUsersTable`) y detalle individual con datos de contacto (`ViewScoreBoxUser`). No cuenta con ningún módulo de mensajería o marketing — [source: `app/Filament/Resources/ScoreBoxUsers/`]
- **Configuración de Correo en Laravel**: El proyecto base cuenta con `config/mail.php` con soporte nativo de drivers (`resend`, `postmark`, `ses`, `smtp`, `log`), pero actualmente tiene configurado el driver por defecto `MAIL_MAILER=log` — [source: `config/mail.php`]
- **Sistema de Colas (`queue`)**: La tabla de jobs en la base de datos local de SQLite ya está migrada y lista para ejecución asíncrona mediante `queue:work` — [source: `config/queue.php`, `database/migrations/0001_01_01_000002_create_jobs_table.php`]
- **Notificaciones Push en Android**: La app cuenta con soporte de notificaciones push móviles (`033-push-notifications`), pero las notificaciones móviles tienen limitaciones de longitud y tasa de apertura diferida en comparación con el correo electrónico para tutoriales detallados y ofertas comerciales — [source: `specs/033-push-notifications`]

## Market & Context

- **Prácticas estándar en apps de música y SaaS**: Las aplicaciones educativas y de productividad musical (ej. forScore, MuseScore, Ultimate Guitar) emplean activamente el email marketing para reactivar usuarios inactivos y comunicar actualizaciones relevantes de catálogo y funciones.
- **Coste de no construirlo**: Dependencia exclusiva del canal de notificaciones push de Android (sujetas a permisos restrictivos en Android 13+) o de que el usuario abra la app por iniciativa propia, perdiendo oportunidades de conversión a la versión de pago y aumentando el abandono (churn).
- **Herramientas de entrega modernas (ESPs)**: Plataformas como Resend, Brevo (Sendinblue) o Mailgun ofrecen APIs modernas compatibles con Laravel, capas gratuitas generosas (de 3.000 emails/mes a 300 emails/día) y gestión nativa de reputación de IP.

## Data & Constraints

- **Restricción de Lecturas y Cuotas en Firestore**: 
  - Consultar masivamente la colección `users` de Firestore para un envío masivo consume cuotas de lectura de Google Cloud (`document.read`). Cada envío a 1.000 usuarios genera 1.000 lecturas en Firestore si no se optimiza con paginación o sincronización de correos — [source: `admin/.specify/memory/constitution.md`, Principio IV.3]
- **Modelo de Datos Actual de Usuarios**:
  - Los documentos en Firestore contienen `email`, `displayName`, `isPremium`, `country`, `mainInstrument`, `studyType`, `notificationsEnabled` — [source: `App\Services\Firestore\ScoreBoxUser.php`]
  - El campo `notificationsEnabled` en Firestore está reservado para notificaciones de la app móvil y no distingue actualmente preferencias de email marketing.
- **Leyes de Privacidad y Antispam (RGPD / CAN-SPAM / Directivas Google-Yahoo 2024)**:
  - Es mandatorio por ley incluir un mecanismo visible y operativo de baja voluntaria (*unsubscribe*) en cada correo comercial.
  - Se requiere soporte para cabeceras `List-Unsubscribe` con desuscripción en un clic (RFC 8058).
  - El dominio remitente debe tener configurados registros DNS SPF, DKIM y DMARC válidos para evitar que los correos terminen en la carpeta de Spam.

## Evidence Against the Idea

- **Riesgo de clasificación como Spam**: Si los usuarios se registraron en la app sin aceptar explícitamente comunicaciones comerciales por correo, los envíos no solicitados pueden generar altas tasas de quejas por spam, dañando la reputación del dominio remitente (`scorebox.app` o similar).
- **Complejidad de Mantenimiento**: Administrar un motor de email propio (editor de plantillas, cola de envíos, gestión de rebotes, bajas) requiere mantenimiento técnico frente a usar una herramienta externa dedicada (ej. Mailchimp, Brevo o Loops).
- **Coste operativo de Firestore si no hay sincronización**: Realizar barridos frecuentes de la base de usuarios en Firestore para enviar emails puede incrementar la factura de Firebase innecesariamente si la base de usuarios supera decenas de miles.
- **Dependencia de un proveedor SMTP/API externo**: Se requiere configurar y mantener credenciales activas de un proveedor externo (API Key de Resend/Brevo/Mailgun o servidor SMTP) para que los correos salgan de producción.

## Gaps & Open Questions

- [NEEDS CLARIFICATION: ¿Dónde se debe persistir la lista de usuarios desuscritos? Se recomienda encarecidamente una tabla local en la base de datos de Laravel (`email_unsubscribes`) para no alterar el esquema de Firestore de la app Android ni generar costes de escritura].
- [NEEDS CLARIFICATION: ¿Qué proveedor de envío se configurará inicialmente? (Recomendación: Resend o Brevo por su integración directa y capa gratuita suficiente para inicio)].
- [NEEDS CLARIFICATION: ¿Qué tipo de editor se usará en Filament para componer el correo? (Editor RichText/HTML de Filament con plantilla base responsive vs plantilla prediseñada con campos de texto)].
- [NEEDS CLARIFICATION: ¿Cómo se validará el consentimiento previo de los usuarios existentes antes del primer envío comercial masivo para evitar bloqueos por spam?]

## Sources

- Repositorio local `admin/app/Services/Firestore/ScoreBoxUser.php` (modelo de datos de usuarios de la app)
- Repositorio local `admin/config/mail.php` y `config/queue.php` (infraestructura de correo y colas de Laravel)
- Repositorio local `admin/.specify/memory/constitution.md` (principios de protección de Firestore y aislamiento de servicios)
- Especificaciones de proyecto: `specs/033-push-notifications` y `specs/037-dynamic-pro-discounts-promotions`

