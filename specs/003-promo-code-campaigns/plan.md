# Implementation Plan: Distribución de Códigos Promocionales en Campañas de Marketing

**Branch**: `003-promo-code-campaigns` | **Date**: 2026-09-13 | **Spec**: [`specs/003-promo-code-campaigns/spec.md`](file:///c:/Users/pable/AndroidStudioProjects/MyMusicalScores/admin/specs/003-promo-code-campaigns/spec.md)

**Input**: Feature specification from `specs/003-promo-code-campaigns/spec.md`

## Summary

Se implementará un sistema integral para la gestión y distribución de códigos promocionales de Google Play Store dentro del módulo de marketing del panel administrativo de Filament (Laravel 13). La solución comprende una tabla local relacional `promotional_codes` para almacenar el inventario de códigos con soporte de importación masiva directa de archivos CSV (formato Google Play Console), la tipificación de campañas (`standard` vs `promotional_code`), validación preventiva de stock en el modal de confirmación en Filament, asignación atómica y concurrente con bloqueo pesimista en base de datos (`SELECT ... FOR UPDATE`), soporte de sustitución en texto plano del comodín `{{promotioncode}}` compatible con enlaces directos de canjeo de Play Store, simulación en envíos de prueba (`is_test = true`), lógica de exclusión y reenvío selectivo a usuarios no premium, y acciones de auditoría y liberación manual de códigos para soporte técnico.

## Technical Context

**Language/Version**: PHP 8.3+, Laravel 13.17

**Primary Dependencies**: Laravel Framework (Mail, Queue, Notifications, DB Transactions), Filament 5, League/Csv o lectura nativa en streaming (`SplFileObject`), kreait/laravel-firebase 7.2, PHPUnit 12

**Storage**: Base de datos local MySQL/SQLite (`promotional_codes`, `marketing_campaigns`, `email_unsubscribes`, `jobs`) para la persistencia administrativa y de inventario; Google Cloud Firestore como fuente de datos de lectura de los usuarios móviles de ScoreBox mediante `FirestoreUserGateway`

**Testing**: PHPUnit 12 (`tests/Feature/Marketing/PromotionalCode*`, `tests/Feature/Marketing/SendMarketingCampaignJobTest.php`)

**Target Platform**: Servidor web / backend administrativo Laravel sobre PHP 8.3+

**Project Type**: Panel de control administrativo y backoffice (Filament PHP) con integración de Firestore y entrega de correos

**Performance Goals**:
- Importación masiva de hasta 5.000 códigos vía CSV en menos de 5 segundos mediante inserción por fragmentos (`insertOrIgnore`).
- Asignación atómica en colas sin contención de bloqueos (< 50ms por transacción).
- Despacho asíncrono sin bloquear la interfaz de Filament.

**Constraints**:
- **Aislamiento absoluto de Firestore (Principio 0 y IV)**: Toda la persistencia, stock y asignación de códigos promocionales reside en la base de datos local de Laravel. Cero escrituras o alteraciones en Firestore.
- **Unicidad e integridad**: Garantía estricta de que un mismo código no puede asignarse a dos destinatarios distintos.
- **Tolerancia a fallos**: Códigos asignados permanecen vinculados ante fallos SMTP para evitar doble despacho por timeout, con acción administrativa para liberarlos si el correo es inválido.
- **Modo prueba blindado**: Cero consumo de códigos reales durante envíos de prueba (`is_test = true`).

**Scale/Scope**: Lotes de miles de códigos promocionales; campañas dirigidas a audiencias de usuarios Free; filtros y búsquedas instantáneas en Filament.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **Pass: Principio 0. Ámbito del Proyecto (Admin & Marketing vs App Android)**: La funcionalidad pertenece estrictamente al backoffice administrativo (`admin/`). No se modifica el código fuente de la app Android (`../app/`).
- **Pass: I. Entrega centrada en Laravel**: Se utilizan migraciones nativas de Laravel, modelos Eloquent con relaciones y scopes, recursos y tablas de Filament v5, transacciones con bloqueo pesimista y Mailables estándar.
- **Pass: II. Operaciones seguras por defecto**: Sin credenciales ni secretos en código; validaciones de entrada en la carga de CSV; sanitización de parámetros.
- **Pass: III. Control de cambios con pruebas primero**: Se diseñan pruebas unitarias y de integración para la importación CSV, la reserva concurrente de códigos, el comportamiento en modo prueba y la prevención de déficit de stock.
- **Pass: IV. Arquitectura centrada en integraciones**:
  - IV.1: Filament interactúa exclusivamente con los modelos locales y `FirestoreUserGateway`.
  - IV.2: Cero escrituras destructivas o modificaciones de esquema en la base de datos de producción de Firestore.
  - IV.3: Control de cuotas y paginación de Firestore preservado.
- **Pass: V. Sistemas observables y mantenibles**: Auditoría completa con `assigned_email`, `assigned_uid`, `assigned_at`, logs estructurados y notificaciones claras al administrador.

No hay violaciones constitucionales ni desviaciones de complejidad.

## Project Structure

### Documentation (this feature)

```text
specs/003-promo-code-campaigns/
├── plan.md              # Este plan de implementación arquitectónico
├── research.md          # Investigación técnica y decisiones de diseño
├── data-model.md        # Esquema de base de datos local, migraciones y DTOs
├── quickstart.md        # Guía de verificación end-to-end
├── contracts/           # Contratos de despacho e importación CSV
│   ├── promotional-code-dispatch-contract.md
│   └── promotional-code-import-contract.md
├── checklists/
│   └── requirements.md  # Checklist de calidad de requisitos de la especificación
└── spec.md              # Especificación funcional refinada
```

### Source Code (repository root: admin/)

```text
app/
├── Filament/
│   └── Resources/
│       ├── MarketingCampaigns/
│       │   ├── MarketingCampaignResource.php
│       │   ├── Schemas/
│       │   │   └── MarketingCampaignForm.php        # Selector de tipo, stock y checkbox de exclusión
│       │   └── Tables/
│       │       └── MarketingCampaignsTable.php      # Validación de stock en modal de envío
│       └── PromotionalCodes/
│           ├── PromotionalCodeResource.php          # Nuevo recurso Filament para códigos
│           ├── Pages/
│           │   └── ListPromotionalCodes.php
│           └── Tables/
│               └── PromotionalCodesTable.php        # Tabla, acción de importar CSV y liberar código
├── Jobs/
│   └── SendMarketingCampaignJob.php                 # Asignación atómica, simulación en test y {{promotioncode}}
├── Models/
│   ├── MarketingCampaign.php                        # Constantes de tipo, relación hasMany promotionalCodes
│   └── PromotionalCode.php                          # Modelo Eloquent, scopes available/assigned, release()
└── Services/
    └── Marketing/
        └── CampaignRecipient.php                    # Soporte de propiedad opcional $uid

database/
└── migrations/
    ├── 2026_09_13_000004_create_promotional_codes_table.php
    └── 2026_09_13_000005_add_promo_code_fields_to_marketing_campaigns_table.php

tests/
├── Feature/
│   └── Marketing/
│       ├── PromotionalCodeImportTest.php            # Prueba de importación CSV y descarte de duplicados
│       ├── PromotionalCodeManagementTest.php        # Pruebas de CRUD, filtros y acción de liberar
│       └── SendMarketingCampaignJobTest.php         # Ampliación para asignación atómica y test mode
└── Unit/
    └── Marketing/
        └── PromotionalCodeModelTest.php             # Scopes, disponibilidad y relaciones
```

**Structure Decision**: Arquitectura modular estándar de Laravel/Filament. El inventario de códigos promocionales se encapsula en su propio recurso de Filament (`PromotionalCodeResource`) y se integra limpiamente con el despachador asíncrono ya existente (`SendMarketingCampaignJob`) sin romper las campañas de marketing estándar previamente implementadas.

## Complexity Tracking

*No hay violaciones constitucionales ni deuda técnica injustificada.*
