# Implementation Plan: Módulo de Campañas de Email Marketing y Desuscripción

**Branch**: `002-marketing-email-campaigns` | **Date**: 2026-09-13 | **Spec**: `specs/002-marketing-email-campaigns/spec.md`

**Input**: Feature specification from `specs/002-marketing-email-campaigns/spec.md`

## Summary

Se implementará un módulo de campañas de email marketing en el panel administrativo de Filament (Laravel 13) para enviar comunicaciones segmentadas (novedades, ofertas y tutoriales) a los usuarios de ScoreBox leídos desde Firestore. El módulo operará de forma 100% asíncrona mediante colas de Laravel (`database` driver), incorporará un modal de confirmación con cálculo dinámico de destinatarios, soporte de personalización `{{name}}`, un sistema de baja voluntaria en un solo clic mediante URLs firmadas (`URL::signedRoute`) y una lista de supresión local (`email_unsubscribes`) en SQLite que garantiza coste cero en Firebase y nulo impacto en la app móvil Android.

## Technical Context

**Language/Version**: PHP 8.3, Laravel 13.17

**Primary Dependencies**: Laravel Framework (Mail, Queue, Notifications), Filament 5, google/cloud-firestore 1.55, kreait/laravel-firebase 7.2, PHPUnit 12

**Storage**: Base de datos local SQLite (`marketing_campaigns`, `email_unsubscribes`, `jobs`) para la persistencia del panel; Google Cloud Firestore como fuente de datos de lectura de los usuarios móviles de ScoreBox

**Testing**: PHPUnit 12 (`tests/Feature/Marketing/*`, `tests/Unit/Marketing/*`)

**Target Platform**: Servidor web / backend administrativo Laravel sobre PHP 8.3+

**Project Type**: Panel de control administrativo y backoffice (Filament PHP) con integración de Firestore y entrega de correos

**Performance Goals**: Despacho instantáneo desde Filament (< 1s en UI tras confirmación); procesamiento por lotes en segundo plano de 100 usuarios por fragmento sin timeouts; respuesta en endpoint de desuscripción < 500ms

**Constraints**:
- Aislamiento absoluto de Firestore (Principio IV): ninguna llamada al SDK de Firebase desde Filament ni desde el Job, solo a través de `FirestoreUserGateway`.
- No modificar el esquema de Firestore de la app Android ni persistir datos de marketing en la base de datos de los clientes móviles.
- Bajas en 1 clic conformes con RFC 8058 (`List-Unsubscribe` / `List-Unsubscribe-Post`) y normativas RGPD / CAN-SPAM.
- Enlaces de desuscripción firmados criptográficamente (`URL::signedRoute`) inmunes a manipulación.

**Scale/Scope**: Audiencias de cientos a miles de usuarios móviles; lotes paginados de 100 usuarios; tabla de supresión indexada por email.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **Pass: Principio 0. Ámbito del Proyecto (Admin & Marketing vs App Android)**: La funcionalidad es 100% de backoffice/marketing y no altera el código fuente de `../app/`.
- **Pass: I. Entrega centrada en Laravel**: Emplea recursos estándar de Filament v5, Mailables nativos de Laravel, Jobs encolados y migraciones Eloquent.
- **Pass: II. Operaciones seguras por defecto**: URLs firmadas con `APP_KEY`, sin credenciales hardcodeadas, configuración de correo delegada en `.env` / `config/mail.php`.
- **Pass: III. Control de cambios con pruebas primero**: Se estructuran pruebas unitarias y de integración para la supresión de correos, generación de URLs firmadas y despacho de jobs.
- **Pass: IV. Arquitectura centrada en integraciones**:
  - IV.1: Sin llamadas a Firestore en la UI; las consultas de destinatarios se canalizan mediante `FirestoreUserGateway`.
  - IV.2: Protección de datos de producción: cero escrituras en Firestore; las bajas se almacenan en la tabla local SQLite `email_unsubscribes`.
  - IV.3: Paginación y control de cuotas para evitar lecturas masivas no acotadas.
- **Pass: V. Sistemas observables y mantenibles**: Auditoría completa con campos `recipients_count`, `sent_count`, `failed_count`, marcas de tiempo y logs de errores estructurados.

No hay violaciones constitucionales que requieran excepciones de complejidad.

## Project Structure

### Documentation (this feature)

```text
specs/002-marketing-email-campaigns/
├── plan.md              # Este documento de planificación
├── research.md          # Investigación técnica y decisiones previas
├── data-model.md        # Esquema de base de datos local y DTOs
├── quickstart.md        # Guía rápida de uso y verificación
├── contracts/           # Contratos de API, endpoints y eventos
│   ├── marketing-campaign-contract.md
│   └── unsubscribe-contract.md
├── checklists/
│   └── requirements.md  # Validación de requisitos de la especificación
└── spec.md              # Especificación funcional y de negocio
```

### Source Code (repository root: admin/)

```text
app/
├── Filament/
│   └── Resources/
│       ├── MarketingCampaigns/
│       │   ├── MarketingCampaignResource.php
│       │   ├── Pages/
│       │   │   ├── ListMarketingCampaigns.php
│       │   │   ├── CreateMarketingCampaign.php
│       │   │   ├── EditMarketingCampaign.php
│       │   │   └── ViewMarketingCampaign.php
│       │   ├── Schemas/
│       │   │   └── MarketingCampaignForm.php
│       │   └── Tables/
│       │       └── MarketingCampaignsTable.php
│       └── EmailUnsubscribes/
│           ├── EmailUnsubscribeResource.php
│           └── Tables/
│               └── EmailUnsubscribesTable.php
├── Http/
│   └── Controllers/
│       └── Marketing/
│           └── UnsubscribeController.php
├── Jobs/
│   └── SendMarketingCampaignJob.php
├── Mail/
│   └── MarketingCampaignMailable.php
├── Models/
│   ├── MarketingCampaign.php
│   └── EmailUnsubscribe.php
└── Services/
    └── Marketing/
        └── CampaignAudienceResolver.php

database/
└── migrations/
    ├── 2026_09_13_000001_create_marketing_campaigns_table.php
    └── 2026_09_13_000002_create_email_unsubscribes_table.php

resources/
└── views/
    ├── emails/
    │   └── marketing-campaign.blade.php
    └── marketing/
        ├── unsubscribed.blade.php
        └── resubscribed.blade.php

routes/
└── web.php (añade rutas públicas firmadas de desuscripción)

tests/
├── Feature/
│   └── Marketing/
│       ├── MarketingCampaignTest.php
│       ├── UnsubscribeFlowTest.php
│       └── SendMarketingCampaignJobTest.php
└── Unit/
    └── Marketing/
        └── CampaignAudienceResolverTest.php
```

**Structure Decision**: Aplicación monolítica Laravel administrativa con separación por capas: modelos locales Eloquent para campañas y bajas, recursos de Filament para UI, servicios de resolución de audiencia sobre `FirestoreUserGateway`, jobs encolados para entrega masiva y vistas Blade para emails y confirmación de baja.

## Complexity Tracking

*No hay violaciones constitucionales ni desviaciones de complejidad que justificar.*
