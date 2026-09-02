# Feature Specification: Capa de acceso a datos Firestore aislada y tipada

**Feature Branch**: `001-firestore-data-layer`

**Created**: 2026-08-30

**Status**: Draft

**Input**: User description: "Implementa una capa de acceso a datos aislada y con tipado estricto para Google Cloud Firestore. Este módulo sirve como único puente entre el backend de Laravel (y los futuros paneles de administración de Filament) y la base de datos Firestore en producción que da soporte a la aplicación Android"

## Clarifications

### Session 2026-08-30

- Q: ¿Cuál debe ser el alcance inicial del módulo de Firestore? → A: Lectura, validación y escrituras controladas para usuarios de la app móvil; Laravel Users sigue residiendo en la base local del backend.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Acceso seguro a usuarios de la app desde Laravel (Priority: P1)

El backend de Laravel debe poder consultar la colección de usuarios de la aplicación Android en Firestore sin depender de llamadas directas al SDK desde las vistas, controladores o paneles de administración.

**Why this priority**: Este módulo es la frontera de integración entre el sistema administrativo y los datos operativos de la app móvil. Sin una capa propia, el acceso a Firestore queda disperso, es más difícil de validar y aumenta el riesgo de errores, fugas de configuración y corrupción de datos.

**Independent Test**: Se puede validar con una prueba de integración que consulte un documento de usuario en Firestore y lo convierta a un modelo tipado sin que la capa de UI conozca el SDK de Google Cloud.

**Acceptance Scenarios**:

1. **Given** un documento válido de ScoreBoxUser en Firestore, **When** el servicio de acceso a datos lo solicita, **Then** el sistema devuelve una representación tipada y consistente para el backend.
2. **Given** una colección con datos incompletos o mal formados, **When** se intente leer o listar usuarios, **Then** el servicio valida la estructura y devuelve un error controlado y registrable.

---

### User Story 2 - Separación clara entre usuarios administrativos y usuarios de la app (Priority: P1)

El sistema debe distinguir explícitamente entre los usuarios del panel administrativo de Laravel y los usuarios de la aplicación móvil almacenados en Firestore.

**Why this priority**: Esta separación es esencial para mantener el origen de verdad correcto y evitar mezclar permisos, perfiles y reglas de negocio entre el backend administrativo y la app Android.

**Independent Test**: Se puede probar con dos escenarios distintos: un usuario admin autenticado en Laravel y un ScoreBoxUser leido desde Firestore, comprobando que cada uno se maneja en su capa y contexto apropiado.

**Acceptance Scenarios**:

1. **Given** un usuario administrativo autenticado en Laravel, **When** se consultan sus permisos de acceso, **Then** la aplicación usa el modelo Laravel User y no el modelo de usuario móvil.
2. **Given** un usuario de la aplicación Android almacenado en Firestore, **When** el backend consulta su perfil, **Then** el sistema lo trata como ScoreBoxUser y no como un usuario del panel administrativo.

---

### User Story 3 - Gestión segura y observable de errores de Firestore (Priority: P2)

Cuando la conexión con Firestore falla, el sistema debe registrar el problema y devolver una respuesta consistente sin exponer detalles internos del entorno ni bloquear el panel administrativo.

**Why this priority**: Las fallas de acceso a servicios externos son inevitables y requieren un tratamiento explícito para evitar un comportamiento impredecible y un diagnóstico difícil.

**Independent Test**: Se puede verificar con una prueba que simula un error de autenticación, permisos o conexión y comprueba que se registra el fallo y se propaga un resultado de dominio consistente.

**Acceptance Scenarios**:

1. **Given** un fallo de conexión o permisos en Firestore, **When** se intenta acceder a los datos, **Then** el sistema registra el error y devuelve un resultado seguro para la capa superior.
2. **Given** un documento inexistente o vacío, **When** se solicita por identificador, **Then** el sistema responde con un resultado nulo o un error definido y documentado.

---

### Edge Cases

- ¿Qué ocurre cuando un documento de Firestore no incluye campos requeridos para el modelo tipado?
- ¿Cómo responde el sistema si la cuenta de servicio de Firebase no tiene permisos suficientes para una colección concreta?
- ¿Qué ocurre si un documento tiene fechas, cadenas o tipos numéricos con formato incompatible?
- ¿Cómo gestionan los servicios de acceso a datos un conjunto vacío, una paginación nula o un límite demasiado alto?

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE exponer una capa única de acceso a Firestore para las lecturas, validaciones y escrituras controladas de los datos de la app móvil, sin permitir llamadas directas al SDK desde Filament, controladores o vistas.
- **FR-002**: El sistema DEBE definir modelos o DTOs tipados para los documentos de Firestore que representen usuarios, perfiles o entidades de negocio relevantes para la aplicación Android.
- **FR-003**: El sistema DEBE distinguir claramente entre los usuarios administrativos del backend Laravel (`Users`) y los usuarios de la app móvil (`ScoreBoxUsers`) y mantener este criterio en la capa de acceso a datos.
- **FR-004**: El sistema DEBE validar la estructura de cada documento antes de devolverlo o persistirlo para evitar corrupción de tipos, datos nulos o esquemas incompatibles.
- **FR-005**: El sistema DEBE capturar y registrar errores de conexión, autenticación, permisos, carga o formato de documentos sin exponer secretos ni detalles del entorno a la capa de presentación.
- **FR-006**: El sistema DEBE limitar lecturas y consultas mediante paginación, filtros controlados y límites explícitos para reducir riesgo de sobrecoste y mantener una operación sostenible en producción.
- **FR-007**: El sistema DEBE usar configuración externa y variables de entorno para la autenticación de Firebase y nunca codificar credenciales ni valores sensibles en código fuente.
- **FR-008**: El sistema DEBE permitir que futuras pantallas y paneles de administración consuman los datos a través de la misma capa sin conocer detalles de implementación de Firestore.

### Key Entities *(include if feature involves data)*

- **User**: Usuario del backend administrativo de Laravel con acceso al panel y permisos del sistema.
- **ScoreBoxUser**: Usuario de la aplicación Android almacenado en Firestore, con identidad y perfil de producto móvil.
- **FirestoreDataGateway**: Módulo único de acceso a datos responsable de consultar, validar y mapear documentos de Firestore.
- **FirestoreResult**: Resultado tipado que encapsula éxito, valor, errores y contexto de diagnóstico para la capa superior.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de las operaciones de lectura y escritura de datos de producción de Firestore pasan por la capa aislada del módulo y no desde componentes de UI o servicios no autorizados.
- **SC-002**: Los usuarios de la app móvil y los usuarios administrativos pueden distinguirse de forma inequívoca sin ambigüedad en el backend.
- **SC-003**: Las consultas a Firestore con datos válidos se resuelven y se transforman en estructuras tipadas en menos de 3 segundos en la mayoría de casos de uso normales.
- **SC-004**: El sistema registra correctamente errores de acceso a Firestore sin exponer secretos ni impedir el uso del panel administrativo en caso de degradación parcial.
- **SC-005**: La capa de acceso a datos es reutilizable por futuros paneles de administración de Filament sin requerir cambios en la lógica de negocio ni en la estructura de los documentos.

## Assumptions

- El proyecto usa Firestore en producción para la app Android y esta colección ya existe con un modelo funcional previo.
- La base de datos local de Laravel se usa para usuarios administrativos y autenticación del panel, mientras que Firestore contiene principalmente perfiles de clientes de la app móvil.
- La capa de acceso a datos tendrá que soportar, en el alcance inicial, lectura, validación y escrituras controladas para usuarios móviles y perfiles asociados; la ampliación a otros casos de negocio seguirá documentándose por separado.
- Los datos de Firestore pueden ser parcialmente dinámicos, por lo que la validación y el mapeado tipado serán parte central del diseño.
