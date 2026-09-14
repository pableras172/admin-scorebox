# Feature Specification: Distribución de Códigos Promocionales en Campañas de Marketing

**Feature Branch**: `003-promo-code-campaigns`

**Created**: 2026-09-13

**Status**: Draft

**Input**: User description: "Como administrador y para las promociones de la aplicacion usando las campañas de marketing tengo la posibilidad de enviar a los usuarios codigos que dan acceso PRO a la app. Estos codigos los genero en play store y los descargo con un csv que tiene el formato 'Promotion code\n<codigo>...'. El objetivo es tener una tabla para almacenar codigos promocionales y poder crear una campaña cuyo objetivo sea repartir codigos promocionales. La campaña obtendra los codigos promocionales que no se han enviado a nadie y los asignara a usuarios. El email contendra la informacion del codigo sustituyendo {{promotioncode}} en el cuerpo del correo con el primer codigo libre, guardando el email del usuario junto al codigo en la base de datos."

## Clarifications

### Session 2026-09-13

- Q: ¿Cómo se introducen en el panel los códigos promocionales generados en Google Play Console? → A: A través de un nuevo recurso de gestión en Filament (`PromotionalCodeResource`) con soporte para importación masiva directa de archivos CSV (admitiendo el encabezado estándar `Promotion code` de Google Play Console o listas simples de códigos) e inserción individual para casos puntuales.
- Q: ¿Qué sucede si la audiencia seleccionada supera el inventario de códigos promocionales disponibles? → A: El modal de confirmación previo al envío calcula dinámicamente y compara los destinatarios netos frente al stock de códigos sin asignar. Si no hay códigos suficientes, el sistema emite una advertencia crítica y bloquea el inicio del envío masivo hasta que se amplíe el inventario o se restrinja la audiencia, evitando correos incompletos o etiquetas vacías.
- Q: ¿Cómo se comporta el sistema en los envíos de prueba (`is_test = true`)? → A: Para proteger el stock comercial y evitar gastar códigos reales de Google Play en pruebas de correo a los administradores, los envíos de prueba inyectan un código ficticio de demostración (ej. `PROMO-TEST-XXXX`) sin consumir ni reservar códigos reales de la base de datos.
- Q: ¿Se permite enviar un nuevo código a un usuario que ya recibió otro en una campaña anterior? → A: El formulario de la campaña incluye la casilla "Excluir usuarios que ya hayan recibido un código promocional" (marcada por defecto como true). Si está marcada, ningún usuario con código previo recibe la campaña. Si el administrador desmarca la casilla: aquellos usuarios que ya recibieron un código pero siguen sin ser Premium (isPremium = false) reciben de nuevo el correo conteniendo exactamente EL MISMO código que ya se les asignó anteriormente (a modo de recordatorio de canjeo, sin gastar un código nuevo del stock libre); si el usuario ya es Premium (isPremium = true), no se le envía el correo bajo ningún concepto.
- Q: ¿Qué validación preventiva se aplica al contenido del correo en campañas de códigos? → A: Si una campaña se define de tipo "Código Promocional", el formulario y la acción de envío validan que el cuerpo o el asunto del correo incluya obligatoriamente el comodín de sustitución `{{promotioncode}}`.
- Q: ¿Qué datos de identidad del usuario se registran en la tabla al asignar el código? → A: Tanto el correo electrónico (`assigned_email`) como el UID de Firebase Auth (`assigned_uid`, nullable), garantizando trazabilidad unívoca con la cuenta del usuario en Firestore y permitiendo futuras extensiones relacionales.
- Q: ¿Cómo se sustituye la variable {{promotioncode}} en el correo? → A: Como texto alfanumérico plano, permitiendo al administrador diseñar el mensaje en el editor enriquecido con total libertad estética (negrita, tamaño, centrado, etc.) e incrustarlo en botones o enlaces directos de canjeo de Google Play (`https://play.google.com/redeem?code={{promotioncode}}`).
- Q: ¿Cómo se gestiona la integridad del código asignado si el envío del correo falla definitivamente por error de entrega? → A: El código permanece asignado al destinatario para evitar riesgos de doble entrega en caso de fallos de red o respuestas ambiguas. En la tabla de códigos de Filament, el administrador dispone de una acción manual para "Liberar código" que permite desvincular al usuario y devolver el código al stock disponible tras verificar la incidencia.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Carga y Gestión del Inventario de Códigos Promocionales (Priority: P1)

Como administrador del backoffice, quiero importar un archivo CSV descargado de Google Play Console con códigos de promoción y visualizar su disponibilidad en el panel, para contar con un stock organizado y disponible para futuras acciones comerciales.

**Why this priority**: Es la base imprescindible del sistema; sin un inventario de códigos cargado y validado en la base de datos, no es posible asociar ni despachar promociones en las campañas.

**Independent Test**: Se puede probar accediendo al recurso de Códigos Promocionales en Filament, subiendo un archivo CSV con el formato estándar de Google Play (`Promotion code` seguido de los códigos), y verificando que los registros se crean en estado disponible con su código único e índice limpio.

**Acceptance Scenarios**:

1. **Given** un archivo CSV exportado de Google Play Console con la columna `Promotion code` y una lista de códigos alfanuméricos, **When** el administrador lo sube a través de la acción de importación en Filament, **Then** el sistema procesa el archivo, descarta filas vacías o duplicadas, inserta los nuevos códigos en la tabla local con estado disponible y muestra una notificación con el total de códigos importados con éxito.
2. **Given** un código que ya existe en el inventario, **When** se intenta importar de nuevo en un CSV posterior, **Then** el sistema lo omite sin interrumpir el procesamiento del resto del lote y notifica el número de códigos duplicados omitidos.
3. **Given** la tabla de códigos en Filament, **When** el administrador consulta el listado, **Then** visualiza columnas claras de Código, Estado (Disponible / Asignado), Correo del usuario asignado, Campaña vinculada y Fecha de asignación, con filtros por disponibilidad.

---

### User Story 2 - Configuración de Campañas de Marketing con Códigos Promocionales (Priority: P1)

Como administrador, quiero crear o editar una campaña de marketing seleccionando el tipo "Código Promocional" e insertar la variable `{{promotioncode}}` en el mensaje junto con las instrucciones de canjeo, para comunicar a los usuarios seleccionados cómo desbloquear la app sin coste.

**Why this priority**: Permite al administrador estructurar mensajes dirigidos específicamente a la conversión o fidelización utilizando los códigos de Google Play.

**Independent Test**: Se puede validar creando una campaña en Filament con tipo "Código Promocional", redactando el mensaje con `{{name}}` y `{{promotioncode}}` y verificando que el formulario valida la presencia de la etiqueta obligatoria y presenta información del inventario de códigos disponibles.

**Acceptance Scenarios**:

1. **Given** el formulario de creación de campañas de marketing, **When** el administrador selecciona el tipo de campaña "Código Promocional", **Then** la interfaz despliega indicaciones sobre el uso del marcador `{{promotioncode}}` y muestra un indicador informativo del stock de códigos disponibles actualmente en el sistema.
2. **Given** una campaña de tipo "Código Promocional", **When** el administrador intenta guardarla o enviarla sin incluir `{{promotioncode}}` en el cuerpo del correo, **Then** el sistema muestra un mensaje de validación advirtiendo que debe incluirse el marcador para que los destinatarios reciban su código.
3. **Given** una campaña de tipo estándar (informativa general), **When** se guarda o envía, **Then** no exige el marcador de código promocional ni consume inventario alguno.

---

### User Story 3 - Asignación Atómica y Despacho Individualizado de Códigos (Priority: P1)

Como administrador, quiero ejecutar el envío de una campaña de códigos promocionales para que el despachador en segundo plano asigne de forma unívoca y atómica un código libre a cada destinatario, lo inserte en su correo y deje constancia en la base de datos de qué usuario recibió cada código.

**Why this priority**: Es el flujo operativo de negocio principal. Garantiza que ningún usuario reciba un código repetido ni se asigne el mismo código a dos usuarios distintos en condiciones de concurrencia.

**Independent Test**: Se puede validar configurando una campaña para usuarios gratuitos con 3 destinatarios de prueba y un inventario de códigos disponibles; al procesar el job asíncrono, se verifica que cada correo recibido contiene un código promocional único y que en la base de datos cada uno de esos 3 códigos pasa a estar asociado al email correspondiente con su fecha y campaña.

**Acceptance Scenarios**:

1. **Given** una campaña de códigos promocionales confirmada para envío, **When** el job asíncrono procesa cada destinatario, **Then** selecciona de forma atómica y con bloqueo transaccional el primer código disponible sin asignar, vincula el email del usuario y el ID de la campaña, actualiza la fecha de asignación y sustituye la variable `{{promotioncode}}` por el código asignado en el cuerpo del correo a enviar.
2. **Given** la entrega del correo al destinatario, **When** el usuario abre el mensaje, **Then** visualiza su código individual exclusivo listo para ser canjeado en Google Play junto con las instrucciones redactadas por el administrador.
3. **Given** una campaña completada, **When** el administrador revisa el inventario de códigos promocionales, **Then** los códigos consumidos muestran el estado "Asignado", el correo del destinatario y la referencia a la campaña que los distribuyó.

---

### User Story 4 - Validación Preventiva de Stock y Simulación en Pruebas (Priority: P2)

Como administrador, quiero que el sistema me avise antes de enviar si no hay códigos suficientes para cubrir toda la audiencia y me permita realizar envíos de prueba a mi correo sin gastar códigos reales de Google Play.

**Why this priority**: Evita la pérdida o desperdicio involuntario de códigos comerciales de Play Store durante las fases de redacción y previene el desastre operativo de dejar a parte de los usuarios sin código en un envío masivo.

**Independent Test**: Se prueba disparando un envío de prueba con la casilla `is_test` marcada; el email debe llegar al administrador con un código simulado como `PROMO-TEST-XXXX` y el inventario de códigos reales debe mantenerse inalterado. Además, al intentar lanzar una campaña real con audiencia mayor al stock, el modal debe bloquear el despacho.

**Acceptance Scenarios**:

1. **Given** una campaña de códigos con 500 destinatarios calculados y un stock de solo 200 códigos disponibles, **When** el administrador hace clic en "Enviar Campaña", **Then** el diálogo modal de confirmación advierte de la falta de 300 códigos y deshabilita el botón de confirmación de envío.
2. **Given** una campaña de códigos en modo prueba (`is_test = true`), **When** el administrador ejecuta el envío de prueba, **Then** el sistema despacha los correos a las direcciones de prueba inyectando un código de demostración simulado (`PROMO-TEST-XXXX`) sin modificar ni decrementar ningún código del inventario real.

---

### User Story 5 - Consulta, Soporte y Auditoría de Códigos Asignados (Priority: P2)

Como administrador o responsable de soporte técnico, quiero buscar un usuario por su correo electrónico o un código específico en el panel, para verificar qué promoción se le otorgó si el usuario reporta incidencias al canjearlo en Google Play.

**Why this priority**: Asegura un soporte posventa eficaz y resolución inmediata de incidencias cuando los usuarios contactan por problemas con su canjeo en la tienda de aplicaciones.

**Independent Test**: Se puede probar buscando un email o código en el buscador de la tabla de Códigos Promocionales y comprobando que se filtra instantáneamente mostrando el registro asociado con todos sus metadatos.

**Acceptance Scenarios**:

1. **Given** un usuario que consulta a soporte técnico porque no encuentra su código o no sabe cómo canjearlo, **When** el administrador busca el email del usuario en la sección de Códigos Promocionales de Filament, **Then** el panel muestra de inmediato el código exacto que le fue asignado, la fecha en que se le envió y la campaña asociada.

---

### Edge Cases

- **Inventario insuficiente de códigos durante el despacho**: Si por alguna discrepancia durante la ejecución concurrente se agotan los códigos libres antes de terminar la audiencia, el job debe pausar el despacho para los destinatarios restantes, marcar la campaña con aviso de stock agotado y notificar la incidencia en el log de auditoría sin dejar correos enviados con códigos vacíos.
- **Fallo en el envío SMTP tras asignar el código**: Si el envío del correo electrónico falla definitivamente por problemas de entrega (rebote o buzón inexistente), el código asignado permanece registrado y vinculado al usuario en la base de datos para prevenir riesgos de doble entrega por timeouts. El administrador dispone en la tabla de Filament de una acción individual para 'Liberar código' si confirma que la dirección de correo es inoperativa y desea reintegrar el código al stock disponible.
- **Formato heterogéneo del CSV**: Si el archivo CSV exportado contiene espacios adicionales en los códigos, cabeceras en mayúsculas/minúsculas (`Promotion code`, `promotion_code`, `code`) o saltos de línea al final, el procesador de importación debe limpiar automáticamente los espacios en blanco y normalizar las columnas.
- **Destinatario que ya recibió un código promocional con anterioridad**: Si la casilla de exclusión de usuarios previos está activa, se omiten todos los usuarios con código previo sin excepción. Si la casilla está inactiva, aquellos destinatarios que aún no sean Premium (`isPremium = false`) recibirán el correo con el mismo código que ya tenían asignado como recordatorio sin consumir stock libre, mientras que los que ya sean Premium (`isPremium = true`) se omiten de la entrega.
- **Campaña de código promocional sin etiqueta `{{promotioncode}}`**: El sistema debe impedir el envío y alertar al usuario si detecta que la plantilla de correo no incluye el marcador de sustitución del código.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE proporcionar una tabla relacional en la base de datos local (`promotional_codes`) para registrar los códigos de promoción, incluyendo los campos: código (`code`, cadena única), correo del usuario asignado (`assigned_email`, nulable), identificador de usuario en Firebase (`assigned_uid`, nulable), identificador de campaña asociada (`marketing_campaign_id`, clave foránea nulable), fecha de asignación (`assigned_at`, timestamp nulable), fecha de creación y actualización.
- **FR-002**: El sistema DEBE incluir un recurso completo en Filament (`PromotionalCodeResource`) para visualizar, filtrar, buscar y gestionar el inventario de códigos promocionales.
- **FR-003**: La tabla de códigos en Filament DEBE ofrecer una acción de importación masiva de archivos CSV que acepte el formato de exportación de Google Play Console (cabecera `Promotion code` o columnas simples de texto), recortando espacios en blanco y descartando filas vacías.
- **FR-004**: El proceso de importación DEBE omitir de forma idempotente los códigos que ya existan en la base de datos, notificando al usuario el número de registros importados y el número de duplicados ignorados.
- **FR-005**: El sistema DEBE permitir clasificar las campañas de marketing (`marketing_campaigns`) mediante un campo de tipo de campaña (`campaign_type`), distinguiendo al menos entre campaña estándar (`standard`) y campaña de código promocional (`promotional_code`).
- **FR-006**: En el formulario de Filament de Campañas de Marketing, el administrador DEBE poder seleccionar el tipo de campaña; al elegir "Código Promocional", el formulario debe presentar textos de ayuda explicativos sobre la variable `{{promotioncode}}` y mostrar el stock actual de códigos disponibles.
- **FR-007**: El sistema DEBE validar que toda campaña de tipo "Código Promocional" contenga el comodín de sustitución `{{promotioncode}}` en el contenido o en el asunto del mensaje antes de permitir su despacho.
- **FR-008**: En el diálogo modal de confirmación de envío de una campaña de código promocional, el sistema DEBE calcular el número de códigos disponibles y contrastarlo con el número de destinatarios de la audiencia; si el stock disponible es menor que la audiencia neta, el sistema DEBE alertar del déficit y bloquear la confirmación del envío masivo.
- **FR-009**: El job asíncrono de despacho (`SendMarketingCampaignJob`) DEBE asignar a cada destinatario un código promocional libre de forma atómica y concurrente (usando bloqueo pesimista en base de datos), actualizando inmediatamente el registro del código con el email del destinatario (`assigned_email`), el UID del usuario (`assigned_uid`), el ID de la campaña y la marca temporal de asignación.
- **FR-010**: El generador de correos DEBE reemplazar dinámicamente la variable `{{promotioncode}}` por el código promocional en texto alfanumérico plano, siendo compatible tanto con el cuerpo del texto como con enlaces o botones de canjeo directo de Google Play (`https://play.google.com/redeem?code={{promotioncode}}`).
- **FR-011**: Cuando una campaña de códigos se despache en modo de prueba (`is_test = true`), el sistema DEBE inyectar un código promocional ficticio de demostración (ej. `PROMO-TEST-XXXX`) a los destinatarios de prueba, sin consumir, reservar ni modificar ningún registro del inventario real de códigos.
- **FR-012**: En campañas de tipo "Código Promocional", el formulario DEBE incluir una casilla de configuración `exclude_previous_promo_recipients` ("Excluir usuarios que ya hayan recibido un código promocional"), marcada por defecto como `true`.
  - Si la casilla está marcada: el sistema DEBE excluir estrictamente de la audiencia a cualquier usuario que ya cuente con un código asignado previamente en la tabla `promotional_codes` (incluso si `isPremium` es falso).
  - Si la casilla está desmarcada:
    - Si el usuario que ya recibió un código sigue sin ser Premium (`isPremium == false`), el sistema DEBE reenviarle el correo conteniendo exactamente EL MISMO código que ya se le asignó con anterioridad (a modo de recordatorio de canjeo), sin consumir ningún código nuevo del inventario libre.
    - Si el usuario que ya recibió un código ya se convirtió en Premium (`isPremium == true`), el sistema DEBE omitirlo y no enviarle el correo.
- **FR-013**: La tabla de códigos promocionales en Filament DEBE disponer de filtros por estado (Todos, Disponibles, Asignados) y buscador rápido por código alfanumérico y por email asignado.
- **FR-014**: Toda la persistencia y control de inventario de códigos promocionales DEBE residir con exclusividad en la base de datos relacional local de Laravel, garantizando cero modificaciones de esquema o escrituras en Google Cloud Firestore (Principio 0 y Principio IV de la Constitución del proyecto).
- **FR-015**: La tabla de códigos promocionales en Filament DEBE incorporar una acción individual "Liberar código" para registros en estado asignado, permitiendo al administrador (previa confirmación) desvincular el usuario y la campaña (`assigned_email = null`, `assigned_uid = null`, `assigned_at = null`, `marketing_campaign_id = null`) y devolver el código al stock de disponibles.

### Key Entities

- **PromotionalCode**: Registro de código promocional de Google Play (`id`, `code`, `assigned_email`, `assigned_uid`, `marketing_campaign_id`, `assigned_at`, timestamps).
- **MarketingCampaign**: Campaña de marketing ampliada con `campaign_type` (`standard`, `promotional_code`) para determinar la lógica de inyección de códigos y validación de stock.
- **ScoreBoxUser**: Destinatarios de la aplicación ScoreBox leídos de Firestore y filtrados por segmento de suscripción y lista de supresión.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de los códigos asignados durante una campaña son exclusivos y unívocos (0 códigos duplicados asignados a distintos usuarios).
- **SC-002**: 0 correos entregados con el marcador `{{promotioncode}}` en blanco o sin sustituir gracias a la validación atómica y previa de disponibilidad de stock.
- **SC-003**: Cero consumo de códigos promocionales comerciales durante la ejecución de campañas o envíos de prueba (`is_test = true`).
- **SC-004**: La importación masiva de hasta 5.000 códigos promocionales vía CSV se completa en menos de 5 segundos en la interfaz administrativa.
- **SC-005**: 100% de aislamiento respecto a Firestore: ninguna operación de inventario de códigos promocionales altera ni escribe en la base de datos de producción de la aplicación móvil Android.

## Assumptions

- Los códigos promocionales son generados previamente por el administrador en la consola de Google Play Console para suscripciones o compras dentro de la app ScoreBox y exportados en formato CSV.
- El canjeo efectivo del código lo realiza el usuario final directamente en la aplicación Google Play Store siguiendo las instrucciones que el administrador redacte en el cuerpo del correo de la campaña.
- El formato habitual del CSV contiene una cabecera con el texto `Promotion code` o similar y una fila por cada código alfanumérico generado por Google.
- Los envíos se gestionan a través de la infraestructura de colas de Laravel configurada en el sistema, asegurando la escalabilidad del despacho de miles de códigos sin impactar el tiempo de respuesta del panel Filament.
