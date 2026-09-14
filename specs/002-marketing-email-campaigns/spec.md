# Feature Specification: Módulo de Campañas de Email Marketing y Desuscripción

**Feature Branch**: `002-marketing-email-campaigns`

**Created**: 2026-09-13

**Status**: Draft

**Input**: User description: "Implementar en el panel administrativo de Filament un módulo de marketing para el envío asíncrono y segmentado de correos electrónicos a usuarios de ScoreBox (Todos, Free, Premium) con sistema de desuscripción voluntaria en un solo clic y lista de supresión local."

## Clarifications

### Session 2026-09-13

- Q: ¿Qué salvaguarda o control debe presentar Filament antes de ejecutar el envío masivo de la campaña? → A: Modal de confirmación con cálculo dinámico previo de destinatarios (muestra cuántos usuarios recibirán el correo según el segmento tras restar los ya desuscritos).
- Q: ¿Cómo debe generarse y validarse el enlace de desuscripción voluntaria? → A: Mediante URLs firmadas nativas de Laravel (`URL::signedRoute`) basadas en firma criptográfica HMAC-SHA256 con el `APP_KEY`, garantizando seguridad contra manipulaciones sin requerir persistencia de tokens previos.
- Q: ¿Cómo debe gestionar el sistema los fallos transitorios o permanentes durante el envío? → A: Mediante hasta 3 reintentos automáticos con retroceso exponencial en el job de cola; si un destinatario falla definitivamente, se registra en log y el envío continúa con el resto de la audiencia sin abortar la campaña.
- Q: ¿Debe el contenido del correo soportar personalización básica del destinatario? → A: Sí, soporte para variable `{{name}}` en el asunto y cuerpo del mensaje, sustituyéndose dinámicamente por el `displayName` del usuario de Firestore con un valor por defecto ("músico") en caso de estar ausente o vacío.
- Q: ¿Cómo debe ser el flujo de interacción en la pantalla web de desuscripción? → A: Desuscripción inmediata y automática en un solo clic al acceder al enlace firmado, mostrando una pantalla amigable de confirmación con opción para reactivar la suscripción en caso de clic accidental.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Creación y Envío Segmentado de Campañas de Correo (Priority: P1)

Como administrador del panel, quiero redactar un correo con asunto y contenido enriquecido y seleccionar la audiencia objetivo (todos los usuarios, solo usuarios gratuitos o solo usuarios premium), para lanzar comunicaciones y ofertas a la comunidad de ScoreBox de forma ágil y segura sin bloquear la interfaz.

**Why this priority**: Es la funcionalidad central que habilita el canal de marketing directo desde el panel administrativo, permitiendo promocionar la app, educar a los músicos y dinamizar las conversiones a la modalidad Premium.

**Independent Test**: Se puede probar creando una campaña con audiencia "Solo usuarios Free" en Filament y ejecutando el envío en pruebas; el sistema debe encolar la tarea asíncrona, consultar únicamente a los usuarios no premium de Firestore, omitir a los desuscritos y marcar la campaña como completada con el recuento exacto de destinatarios.

**Acceptance Scenarios**:

1. **Given** una campaña redactada con asunto, cuerpo y audiencia "Solo usuarios Free", **When** el administrador pulsa la acción de enviar, **Then** el sistema calcula y muestra en el diálogo modal el recuento dinámico exacto de destinatarios que recibirán el correo (restando los desuscritos) y, tras confirmar, transiciona su estado a en cola (`queued`), delega el procesamiento a la cola de Laravel (`database`) y notifica al usuario en la interfaz.
2. **Given** el job de envío en segundo plano en ejecución, **When** consulta a los usuarios mediante `FirestoreUserGateway`, **Then** obtiene los documentos en lotes controlados (hasta 100 por petición), filtra por el estado de suscripción seleccionado y descarta cualquier dirección de correo presente en la lista local de supresión.
3. **Given** la finalización del procesamiento de todos los lotes, **When** el administrador consulta el listado de campañas en Filament, **Then** la campaña muestra el estado "Enviada" (`sent`), la fecha/hora de envío y el número total de correos efectivamente procesados.

---

### User Story 2 - Desuscripción Voluntaria en Un Solo Clic (Opt-out / Unsubscribe) (Priority: P1)

Como músico usuario de ScoreBox que recibe un correo de marketing, quiero pulsar en el enlace de desuscripción presente en el pie del email para darme de baja inmediata y voluntariamente de futuras comunicaciones comerciales, sin necesidad de iniciar sesión ni introducir contraseñas.

**Why this priority**: Es un requisito legal y ético de máxima prioridad (RGPD, CAN-SPAM y directivas de Google/Yahoo). Sin un mecanismo de baja automático, el dominio corre el riesgo inminente de ser bloqueado por spam.

**Independent Test**: Se puede validar enviando un correo de prueba, haciendo clic en el enlace de baja generado con token seguro y verificando que el email se inserta en la tabla local de bajas y queda excluido en cualquier campaña posterior.

**Acceptance Scenarios**:

1. **Given** un correo de marketing recibido por un usuario, **When** el usuario hace clic en el enlace "Darse de baja" (generado como URL firmada segura de Laravel), **Then** el sistema procesa la baja de forma inmediata en la base de datos de supresión, abre una página web pública limpia de ScoreBox confirmando la baja y ofrece un botón para revertir la acción si fue un clic accidental.
2. **Given** una dirección de correo registrada en la tabla de supresión local, **When** el administrador ejecuta un nuevo envío masivo dirigido a "Todos los usuarios", **Then** el despachador de correos omite rigurosamente a ese destinatario.
3. **Given** un gestor de correo que soporta bajas automáticas mediante cabeceras estándar (RFC 8058 `List-Unsubscribe`), **When** el cliente de correo envía una solicitud POST con un solo clic, **Then** el sistema procesa la baja de forma transparente y sin fricción.
4. **Given** un usuario que se dio de baja accidentalmente, **When** pulsa el botón "Volver a suscribirme" en la página de confirmación, **Then** el sistema elimina el email de la tabla de supresión y confirma la reactivación satisfactoria.

---

### User Story 3 - Supervisión de Bajas y Campañas en Filament (Priority: P2)

Como administrador del panel, quiero visualizar la lista de usuarios desuscritos y poder registrar bajas manuales si un usuario lo solicita a través del canal de soporte, manteniendo la lista de supresión siempre al día.

**Why this priority**: Permite al administrador atender requerimientos de soporte técnico o derechos de privacidad (derecho de oposición) de forma directa desde la interfaz.

**Independent Test**: Se puede probar accediendo a la vista de "Bajas de Marketing", añadiendo manualmente un correo de prueba y verificando que el sistema lo almacena y previene envíos hacia él.

**Acceptance Scenarios**:

1. **Given** la sección de bajas en el panel de Filament, **When** el administrador accede a la vista, **Then** puede visualizar la tabla con los emails desuscritos, la fecha de solicitud y el canal (enlace web o manual).
2. **Given** un usuario que solicita la baja por correo de soporte, **When** el administrador introduce manualmente su dirección en la lista de supresión de Filament, **Then** el sistema valida el correo, lo guarda y queda inmediatamente protegido de futuros envíos.

---

### Edge Cases

- **Usuario sin correo electrónico o formato no válido en Firestore**: Si un documento de usuario en Firestore no contiene un email válido, el sistema debe registrar un aviso en los logs y saltar al siguiente destinatario sin interrumpir el lote ni abortar la campaña.
- **Interrupción o fallo de red durante el envío masivo**: Si el proveedor de correo devuelve un fallo temporal de red o límite de tasa por segundo, el job de Laravel aplica hasta 3 reintentos automáticos con retroceso exponencial (backoff). Si una dirección de correo falla de forma permanente tras agotar los reintentos, se aísla registrando el error en el log de auditoría y el proceso continúa con los restantes destinatarios sin interrumpir el envío global.
- **Acceso múltiple al enlace de desuscripción**: Si un usuario pulsa repetidamente el enlace de baja, la acción debe ser idempotente (confirmar la desuscripción sin generar errores ni duplicar registros en la base de datos).
- **Segmento sin destinatarios**: Si la audiencia seleccionada no tiene usuarios disponibles (o todos están en la lista de supresión), la campaña debe finalizar limpiamente indicando 0 destinatarios enviados.
- **Protección contra tokens manipulados**: Si el token de la URL de desuscripción es inválido o está corrupto, el sistema debe mostrar una pantalla de aviso amigable sin desvelar detalles de implementación interna.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE proporcionar un recurso en Filament (`MarketingCampaignResource`) para listar, crear, previsualizar y disparar campañas de correo electrónico.
- **FR-002**: El formulario de creación de campaña DEBE requerir un Asunto (`subject`), un selector de Audiencia objetivo (`target_segment`) con las opciones "Todos los usuarios", "Solo usuarios Free" y "Solo usuarios Premium", y un Contenido (`content`) editable mediante editor de texto enriquecido.
- **FR-003**: El sistema DEBE almacenar las campañas en una tabla local de la base de datos (`marketing_campaigns`) con atributos de estado (`draft`, `queued`, `sending`, `sent`, `failed`), recuento de destinatarios procesados y marcas temporales de auditoría (`sent_at`, `created_at`, `updated_at`).
- **FR-004**: El sistema DEBE ejecutar el envío de correos de forma 100% asíncrona mediante un Job de Laravel (`SendMarketingCampaignJob`) procesado en la cola del sistema (`jobs`), garantizando que la interfaz administrativa de Filament nunca sufra bloqueos ni timeouts.
- **FR-005**: El sistema DEBE consultar los destinatarios en Firestore a través de la capa tipada `FirestoreUserGateway`, aplicando paginación por lotes para no sobrepasar la memoria del servidor ni los límites de consumo de Google Cloud.
- **FR-006**: El sistema DEBE mantener una tabla local de base de datos (`email_unsubscribes`) para registrar de forma permanente los correos que no deben recibir comunicaciones de marketing.
- **FR-007**: El sistema DEBE verificar y excluir obligatoriamente de cualquier envío a todas las direcciones presentes en la tabla `email_unsubscribes`.
- **FR-008**: Cada correo electrónico generado DEBE construirse a partir de una plantilla Blade limpia y adaptable (responsive), incorporando en el pie de página la identidad de ScoreBox y un enlace seguro de desuscripción voluntaria en un clic generado mediante URLs firmadas de Laravel (`URL::signedRoute`).
- **FR-009**: El sistema DEBE inyectar en todos los correos las cabeceras estándar `List-Unsubscribe` y `List-Unsubscribe-Post` (conforme a RFC 8058) para maximizar la reputación del remitente frente a proveedores como Gmail y Yahoo.
- **FR-010**: El sistema DEBE exponer una ruta pública web protegida por validación de firma (`signed`) para procesar la desuscripción de forma instantánea tras verificar la autenticidad del enlace, presentando una pantalla de confirmación clara y estética al usuario.
- **FR-011**: El sistema DEBE incluir un recurso o página en Filament para consultar la lista de usuarios desuscritos y permitir el alta manual de correos en la lista de supresión.
- **FR-012**: El sistema DEBE operar de forma no destructiva sobre Firestore, sin alterar ni añadir campos obligatorios a los documentos de los usuarios móviles, preservando la compatibilidad íntegramente con la app Android.
- **FR-013**: La acción de envío en Filament DEBE desplegar un modal de confirmación interactivo que calcule y presente al administrador el número exacto de destinatarios que recibirán la campaña (restando los usuarios presentes en la lista de supresión local).
- **FR-014**: El job de despacho de correos (`SendMarketingCampaignJob`) DEBE implementar una política de tolerancia a fallos con hasta 3 reintentos automáticos y retroceso exponencial ante fallos transitorios con el proveedor de correo, aislando errores individuales para no detener el progreso global de la campaña.
- **FR-015**: El motor de generación de correos DEBE admitir la variable de sustitución `{{name}}` tanto en el asunto como en el cuerpo del mensaje, reemplazándola dinámicamente por el `displayName` del usuario de Firestore, o por un valor genérico de cortesía (por defecto 'músico') si el campo está ausente o vacío.
- **FR-016**: La página web pública de confirmación de desuscripción DEBE incorporar una opción de reversión voluntaria (*'Volver a suscribirme'*) que permita al usuario restablecer su suscripción y retirar su correo de la tabla de supresión de forma inmediata si la baja fue accidental.

### Key Entities

- **MarketingCampaign**: Campaña de marketing gestionada por el administrador (id, subject, content, target_segment, status, recipients_count, sent_at).
- **EmailUnsubscribe**: Registro local de supresión de envíos (id, email, token, unsubscribed_at, source).
- **ScoreBoxUser**: Entidad de usuario móvil leída de Firestore con sus campos de identidad y suscripción (`uid`, `email`, `isPremium`, `displayName`).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de los envíos de campañas se realizan en segundo plano, permitiendo al administrador continuar operando en Filament inmediatamente tras confirmar el envío (< 1 segundo de respuesta en UI).
- **SC-002**: El 100% de las solicitudes de desuscripción se procesan y hacen efectivas en menos de 2 segundos tras pulsar el enlace, garantizando que el usuario jamás vuelva a recibir una campaña posterior.
- **SC-003**: Cero escrituras destructivas o modificaciones de esquema en la base de datos de producción de Firestore de la app móvil.
- **SC-004**: Los correos enviados se entregan con un diseño responsive legible y con las cabeceras legales de desuscripción (RFC 8058) activas.
- **SC-005**: Tasa de rebote de correos controlada (< 2%) y tasa de quejas de spam proyectada < 0.1% mediante el filtrado preventivo y las bajas con un solo clic.

## Assumptions

- La entrega de correos utiliza la configuración estándar de `config/mail.php` respaldada por variables en `.env` (compatible con Resend, Brevo, Mailgun, Amazon SES o servidores SMTP habituales).
- La gestión de colas de Laravel se ejecuta mediante el driver `database` soportado por la tabla `jobs` existente en el proyecto.
- Los usuarios en Firestore que no cuenten con dirección de correo o cuyo correo no tenga formato estándar son descartados automáticamente durante la preparación del lote.

