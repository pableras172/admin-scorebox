# Decision: Campañas y Envíos de Correo de Marketing

- **Slug**: marketing-email-campaigns
- **Decided**: 2026-09-13
- **Verdict**: go
- **Artifacts reviewed**: intake.md | research.md | problem.md | concept.md

## Scorecard

| Criterion | Rating | Justification |
| :--- | :--- | :--- |
| **Problem validity** | strong | La necesidad de comunicar ofertas, tutoriales y novedades a los usuarios de ScoreBox es real y crítica para la retención y monetización de la app móvil. |
| **Evidence strength** | strong | Verificados los datos disponibles en Firestore (`email`, `isPremium`), la infraestructura de colas y correo en Laravel, y los requisitos legales internacionales. |
| **Value vs. inaction** | strong | El coste de no construirlo es mantener pasiva la conversión a PRO; construirlo abre un canal directo de marketing con impacto inmediato en el negocio. |
| **Feasibility / appetite** | strong | La Opción A (enfoque lean) tiene un apetito `small` (3 a 5 días de desarrollo), apoyado 100% en componentes probados de Laravel y Filament. |
| **Strategic fit** | strong | Se alinea al 100% con la Constitución del proyecto (`admin/.specify/memory/constitution.md`, Principio 0) como herramienta interna de marketing y backoffice. |
| **Risk posture** | strong | Riesgo de spam mitigado mediante desuscripción obligatoria en un clic (`email_unsubscribes`); riesgo de sobrecoste en Firestore mitigado con colas asíncronas y lecturas por lotes. |

## Verdict & Rationale

**Veredicto: GO 🟢**

La idea supera con creces el listón de evaluación. El problema está sólidamente justificado, la evidencia técnica confirma la viabilidad inmediata sobre la infraestructura actual de Laravel 13 y Filament v5, y el apetito técnico es pequeño y acotado. La propuesta protege rigurosamente la base de datos de producción de la app Android al gestionar las desuscripciones de forma local en el panel sin alterar Firestore. La idea está lista para pasar a la fase de especificación formal en Spec-Driven Development.

## If go — Handoff to `/speckit.specify`

- **Problem**: El administrador de ScoreBox carece de un canal directo, controlado y segmentado desde su panel para comunicar novedades, ofertas y tutoriales a los usuarios de la app móvil, frenando el engagement y la conversión a Premium.
- **Chosen approach**: Opción A — Envíos Directos desde Filament con Plantilla Base y Supresión Local (Laravel Queue + tabla local SQLite `email_unsubscribes` + Filament Resource de Campañas).
- **In scope**:
  - Recurso en Filament para redactar y programar campañas (asunto, contenido enriquecido, selector de audiencia: Todos, Usuarios Free, Usuarios Premium).
  - Procesamiento asíncrono con Laravel Queue (tabla `jobs`) para procesar envíos por lotes sin bloquear la interfaz ni saturar Firestore.
  - Tabla local `email_unsubscribes` para registrar bajas y excluir automáticamente a los usuarios desuscritos de todos los envíos.
  - Endpoint y vista web simple de desuscripción voluntaria en un solo clic (`/marketing/unsubscribe/{token}`).
  - Plantilla base HTML responsive en Blade con encabezados antispam legales (RFC 8058 `List-Unsubscribe`).
  - Historial de campañas enviadas en Filament con estado del envío (borrador, en cola, completado, fecha y total de destinatarios).
- **Out of scope**:
  - Constructor visual drag-and-drop de emails.
  - Tracking pixel interno de aperturas/clics embebido en la aplicación (se consulta en el panel del ESP).
  - Automatizaciones multi-etapa complejas (drip marketing).
  - Cualquier cambio en el código fuente de la app Android o en los documentos de Firestore.
- **Success metrics**:
  - 100% de los envíos procesados en segundo plano sin timeouts en Filament.
  - 100% de efectividad en la supresión de bajas de marketing.
  - Tasa de quejas de spam < 0.1% y entregabilidad fiable.
  - Cero escrituras destructivas o cambios de esquema en Firestore.
- **Carried-forward open questions**:
  - Configuración de las credenciales del proveedor de correo (ej. Resend, Brevo, Mailgun) en `.env`.

