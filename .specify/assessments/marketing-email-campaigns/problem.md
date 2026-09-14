# Problem Definition: Campañas y Envíos de Correo de Marketing

- **Slug**: marketing-email-campaigns
- **Created**: 2026-09-13
- **Inputs used**: intake.md | research.md

## Problem Statement

Actualmente, el administrador de ScoreBox carece de un canal directo, controlado y segmentado desde su panel de control para comunicarse con los usuarios registrados en la app móvil. Esta desconexión impide educar a los usuarios en las funciones avanzadas de la app (OCR, metrónomo, sincronización), anunciar actualizaciones y ofrecer incentivos de suscripción personalizados, lo que resulta en un menor engagement y un ritmo inferior de conversión de usuarios gratuitos a la modalidad Premium.

## Affected Users & Stakeholders

- **Usuarios Gratuitos (Free)**: Músicos que descargan la app pero a menudo no descubren todas las herramientas clave de ScoreBox ni conocen las promociones u ofertas para dar el salto a Premium.
- **Usuarios de Pago (Premium / PRO)**: Usuarios que no reciben comunicaciones de novedades sobre nuevas versiones o tutoriales de aprovechamiento de las ventajas exclusivas por las que han pagado.
- **Usuarios en General (Destinatarios)**: Músicos con riesgo de percibir comunicaciones intrusivas si no disponen de un mecanismo claro, inmediato y garantizado para desuscribirse de correos informativos o comerciales.
- **Administrador del Ecosistema (pableras172)**: Necesita una herramienta centralizada y ágil en el panel web para lanzar comunicaciones a grupos específicos de usuarios sin recurrir a scripts manuales, consultas directas a la base de datos ni herramientas externas desconectadas de Firestore.

## Goals

- Habilitar desde el panel administrativo de Laravel/Filament el envío de correos electrónicos segmentados (novedades, ofertas, guías de uso).
- Proveer segmentación nativa por estado de suscripción (todos los usuarios, solo usuarios gratuitos, solo usuarios premium).
- Implementar un mecanismo de baja voluntaria (*unsubscribe*) directo y confiable que cumpla con los estándares legales (RGPD / CAN-SPAM) y garantice que ningún usuario desuscrito vuelva a recibir correos de marketing.
- Ejecutar los envíos de forma completamente asíncrona (mediante colas en segundo plano) protegiendo la experiencia del administrador y las cuotas de lectura de Firestore.

## Non-Goals

- **No crear un motor de automatización masiva complejo**: No se persigue en esta fase crear secuencias automáticas condicionales multi-paso (drip campaigns) o disparadores basados en eventos de comportamiento en tiempo real dentro de la app móvil.
- **No alterar el código ni esquema de la app Android**: Queda estrictamente fuera de alcance modificar los clientes Android (`../app/`) o forzar modificaciones en los documentos de Firestore que puedan desestabilizar la aplicación móvil en producción.
- **No reemplazar las notificaciones push móviles**: Este módulo no sustituye a Firebase Cloud Messaging (FCM) para alertas instantáneas en el dispositivo, sino que actúa como canal complementario de comunicación enriquecida y marketing.
- **No diseñar un editor de maquetación drag-and-drop avanzado**: Se priorizará un editor de texto enriquecido (RichText/Markdown) o plantillas responsivas limpias en lugar de un constructor visual de bloques pesados.

## Success Metrics

- **Confiabilidad técnica**: 100% de los envíos masivos procesados en segundo plano sin bloqueos de interfaz ni timeouts en el panel de Filament (baseline actual: sin envíos disponibles).
- **Efectividad del opt-out**: 100% de cumplimiento en las solicitudes de desuscripción (los usuarios dados de baja quedan excluidos inmediatamente de las siguientes campañas).
- **Entregabilidad y salud de dominio**: Tasa de rebote < 2% y quejas de spam < 0.1%, respetando las directivas vigentes de Google y Yahoo para remitentes.
- **Conversión y retención**: Tasa de apertura (Open Rate) objetivo > 25% y un incremento medible en la activación y conversión de usuarios Free hacia ofertas PRO.

## Cost of Inaction

Si no se implementa esta funcionalidad:
- El canal de comunicación con los usuarios seguirá restringido exclusivamente a las notificaciones móviles (que frecuentemente son silenciadas o desactivadas por el usuario en Android).
- La conversión a la versión Premium se mantendrá pasiva, dependiendo únicamente de que el usuario decida explorar la pantalla de suscripción dentro de la app por sí mismo.
- Los usuarios que instalen la app y encuentren dudas iniciales corren el riesgo de desinstalarla sin llegar a descubrir el valor de las funciones de ScoreBox.

## Open Questions

- [NEEDS CLARIFICATION: ¿El registro de bajas (desuscripciones) se gestionará en una tabla local SQLite del panel (`email_unsubscribes`) para máximo aislamiento y coste cero en Firebase?]
- [NEEDS CLARIFICATION: ¿Qué proveedor de entrega de correo (ESP) se seleccionará inicialmente para configurar las credenciales SMTP/API en el archivo `.env` (ej. Resend, Brevo, Mailgun)?]
- [NEEDS CLARIFICATION: ¿Se requiere un historial en Filament de campañas enviadas con fecha, asunto y número de destinatarios?]

