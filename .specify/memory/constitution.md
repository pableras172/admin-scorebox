<!--
Sync Impact Report
- Version change: template placeholder → 1.1.0
- Modified principles: template placeholder → I. Entrega centrada en Laravel; template placeholder → II. Operaciones seguras por defecto; template placeholder → III. Control de cambios con pruebas primero; template placeholder → IV. Arquitectura centrada en integraciones; template placeholder → V. Sistemas observables y mantenibles
- Added sections: Restricciones adicionales; Flujo de trabajo de desarrollo; VI. Code Standards & Patterns; VII. Security & Environment Governance
- Removed sections: none
- Templates requiring updates: .specify/templates/plan-template.md ⚠ pending; .specify/templates/spec-template.md ⚠ pending; .specify/templates/tasks-template.md ⚠ pending
- Follow-up TODOs: TODO(RATIFICATION_DATE): la fecha original de adopción aún no se ha registrado.
-->

# Constitución de MyMusicalScores Admin

## Principios fundamentales

### I. Entrega centrada en Laravel

Este proyecto DEBE implementarse y mantenerse de acuerdo con las convenciones de Laravel y Filamentphp, no con patrones improvisados. Cada funcionalidad DEBE encajar con el enrutado, el contenedor, la configuración y el modelo de pruebas del framework. La lógica personalizada DEBE aislarse detrás de clases de servicio o límites de dominio cuando de lo contrario evadiría la estructura de la aplicación.

Esta regla garantiza que la aplicación de administración siga siendo predecible, depurable y fácil de extender sin crear soluciones alternativas específicas del framework.

Cuando el proyecto use **Google Cloud Firestore**, debe tratarse como una fuente de datos operativa o de sincronización, no como sustituto automático de la base de datos principal de Laravel. La colección de usuarios de la aplicación móvil publicada en Play Store se almacena en Firestore, mientras que la base de datos local de Laravel solo tendrá, de momento, los usuarios que pueden autenticarse en el panel administrativo. Por tanto, distinguimos dos tipos de usuarios:

- **Users**: usuarios que pueden acceder al panel administrativo de Laravel.
- **ScoreBoxUsers**: usuarios de la aplicación móvil almacenados en Firestore.

La base de datos relacional de Laravel sigue siendo la fuente de verdad para los datos transaccionales del sistema administrativo y para los usuarios autorizados del panel, salvo que se documente explícitamente otra cosa para un caso concreto de integración con Firestore.
### II. Operaciones seguras por defecto

Cualquier cambio que afecte a la autenticación, la configuración, el almacenamiento o las integraciones externas DEBE tratar los secretos y los valores del entorno como entradas controladas. El material sensible DEBE residir en variables de entorno o en almacenamiento seguro, nunca en archivos fuente comprometidos. El acceso a Firebase, a las credenciales de la base de datos y a las APIs externas DEBE limitarse al mínimo conjunto de permisos necesario.

Este es un requisito no negociable porque los sistemas administrativos procesan datos empresariales sensibles y deben cerrarse de forma segura cuando la configuración está incompleta o mal definida.

### III. Control de cambios con pruebas primero

Todos los cambios que afecten al comportamiento DEBEN estar respaldados por una prueba fallida o por una reproducción verificada antes de que se considere completa la implementación. Las correcciones de errores DEBEN incluir una comprobación de regresión. Las nuevas funciones DEBEN incluir pruebas enfocadas que cubran el comportamiento visible para el usuario o el contrato de integración que introducen.

Esto mantiene la estabilidad del proyecto y permite una entrega iterativa sin alejarse del comportamiento conocido como correcto.

### IV. Arquitectura centrada en integraciones

### IV.1. Service Isolation (No Firestore in UI)
* Filament Resources, Pages, Widgets y Forms **nunca** interactúan directamente con el SDK de Firestore.
* Toda operación de lectura, mutación o agregación debe encapsularse en una capa de servicios o repositorios (`App\Services\Firestore\*` o `App\Repositories\*`).
* Los modelos o entidades que use Filament deben representar de forma tipada las respuestas de Firestore.

### IV.2. Production Safety & Data Integrity
* **Protección contra destructividad:** No se permiten eliminaciones masivas destructivas directas en colecciones de producción sin confirmación explícita y auditoría.
* **Manejo de esquemas dinámicos:** Dado que Firestore es NoSQL, el panel debe validar y normalizar la estructura de los datos antes de guardarlos para no corromper el contrato que espera la app móvil.
* **Preservación de tipos:** Se deben manejar explícitamente conversiones de fechas (`Google\Cloud\Core\Timestamp` <-> `Carbon\CarbonInterface`) y tipos numéricos/booleanos.

### IV.3. Performance & Quota Awareness
* **Control de lecturas:** Las consultas a Firestore deben optimizarse mediante límites (`limit`) y paginación por cursores (`startAfter`) para evitar sobrecostes en la factura de Firebase.
* **Índices y consultas:** No implementar ordenamientos o filtros compuestos sin verificar previamente la existencia o necesidad de índices en Firestore.


### V. Sistemas observables y mantenibles

Los cambios DEBEN ser comprensibles para el siguiente ingeniero sin necesidad de ingeniería inversa. La logging, la validación y la configuración DEBEN ser lo bastante explícitas para diagnosticar fallos en producción. El código duplicado o difícil de razonar DEBE refactorizarse antes de convertirse en una carga de mantenimiento.

La claridad operativa es esencial porque las herramientas de administración son críticas para el negocio y deben seguir siendo soportables conforme cambia el equipo.

## Restricciones adicionales

- La aplicación DEBE ejecutarse como un sistema de administración basado en Laravel y filamentphp y DEBE conservar las convenciones del framework para la configuración, el enrutado, los modelos y los límites de servicio.
- La integración con Firebase DEBE utilizar configuración basada en variables de entorno y DEBE evitar secretos codificados o valores específicos del proyecto en archivos comprometidos.
- Los cambios en la base de datos y en servicios externos DEBEN revisarse en cuanto a seguridad, integridad de datos y viabilidad de reversión antes del despliegue.
- Los trabajos que afecten a la persistencia de datos, al acceso a archivos o a las integraciones de servicios DEBEN ir acompañados de verificaciones que demuestren que el comportamiento sigue funcionando en el entorno previsto.
- El repositorio DEBE favorecer cambios pequeños y revisables frente a refactorizaciones amplias que mezclen preocupaciones no relacionadas.

## Flujo de trabajo de desarrollo

- Los requisitos y los cambios DEBEN expresarse de forma comprobable y trazable al comportamiento visible para el usuario.
- La implementación DEBE avanzar en pequeños incrementos con validación después de cada cambio significativo.
- Las pull requests o cambios revisables DEBEN confirmar que son coherentes con esta constitución, especialmente en actualizaciones sensibles a la seguridad o a la infraestructura.
- Antes del despliegue, el equipo DEBE verificar que los flujos afectados siguen funcionando de extremo a extremo en el entorno objetivo.
- Los cambios de documentación y de configuración DEBEN mantenerse sincronizados con el código que apoyan.

## Gobernanza

Esta constitución sustituye las prácticas ad hoc de este proyecto. Cualquier cambio en el modelo operativo, la política o los principios del proyecto DEBE registrarse aquí con un incremento de versión y una justificación clara.

Reglas de modificación:
- Las propuestas DEBEN incluir el cambio exacto y la razón por la que es necesario.
- Los cambios en la gobernanza, la práctica de seguridad o la arquitectura DEBEN revisarse antes de aceptarse.
- El proyecto DEBE conservar un registro de la fecha de ratificación y de la fecha de la última modificación.
- Cualquier modificación que elimine o cambie materialmente un principio DEBE tratarse como un cambio de gobernanza importante y explicarse por escrito.

Política de versionado:
- MAYOR: cambios de gobernanza o de principios incompatibles con versiones anteriores
- MENOR: nuevo principio o sección ampliada materialmente
- PATCH: aclaraciones, correcciones de redacción o mejoras no semánticas

Expectativas de cumplimiento:
- Los cambios DEBEN comprobarse frente a esta constitución antes del merge o del despliegue.
- Si un miembro del equipo no puede justificar una desviación con claridad, la desviación DEBE considerarse no conforme hasta revisarla.
- Esta constitución es la base para los gates de planificación, implementación y revisión.


## VI. Code Standards & Patterns

* **PHP Version:** PHP 8.2+ con strict typing activado (`declare(strict_types=1);`) en todas las clases nuevas.
* **DTOs & Typing:** Los documentos de usuario deben mapearse a objetos de transferencia de datos (DTOs) o Value Objects tipados para garantizar que Filament renderice datos seguros.
* **Error Handling:** Fallos de conexión gRPC/Firebase, tokens expirados o permisos denegados de la cuenta de servicio deben capturarse y registrarse en los logs de Laravel (`Log::error`), mostrando notificaciones legibles en la UI de Filament vía `Filament\Notifications\Notification`.
* **Testing:** Cada integración de repositorio debe contar con tests unitarios/feature que utilicen mocks o el emulador local de Firebase para no interactuar con Firestore de producción durante CI/CD.


## VII. Security & Environment Governance

* **Credenciales:** El archivo JSON de la cuenta de servicio de Firebase jamás debe versionarse en Git.
* **Variables de Entorno:** Todas las configuraciones sensibles (`FIREBASE_CREDENTIALS`, `FIREBASE_PROJECT_ID`) deben resolverse exclusivamente mediante `config('firebase.*')` y no con `env()` directo en el código de aplicación.
* **Roles de Acceso al Panel:** Solo usuarios autorizados de Laravel pueden acceder a las rutas de Filament.

**Versión**: 1.1.0 | **Ratificada**: TODO(RATIFICATION_DATE): la fecha original de adopción aún no se ha registrado. | **Última modificación**: 2026-08-30
