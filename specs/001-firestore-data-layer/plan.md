# Implementation Plan: Firestore Data Layer

**Branch**: `001-firestore-data-layer` | **Date**: 2026-08-30 | **Spec**: `specs/001-firestore-data-layer/spec.md`

**Input**: Feature specification from `specs/001-firestore-data-layer/spec.md`

## Summary

Se implementará una capa de acceso a datos aislada para Google Cloud Firestore en Laravel, con una separación explícita entre los usuarios administrativos del panel (`App\Models\User`) y los usuarios de la aplicación móvil (`ScoreBoxUser`) almacenados en Firestore. La capa expone servicios tipados y validación de documentos, evitando llamadas directas al SDK desde Filament o controladores y centralizando errores, limitación y diagnósticos operativos.

## Technical Context

**Language/Version**: PHP 8.3, Laravel 13.17

**Primary Dependencies**: Laravel Framework, Filament 5, kreait/laravel-firebase 7.2, google/cloud-firestore 1.55.0, PHPUnit 12

**Storage**: Firestore en producción para usuarios móviles; base relacional de Laravel para acceso administrativo local

**Testing**: PHPUnit, feature tests and unit tests under `tests/`

**Target Platform**: Linux-based Laravel app running via Docker/Sail

**Project Type**: Web application / admin backend with external Firestore integration

**Performance Goals**: Normalized reads under 3s for document lookup and small filtered result sets; no unbounded collection scans

**Constraints**: Strict type safety, no direct SDK access in UI layer, credentials via environment variables only, no Firestore write path without validation

**Scale/Scope**: Initial feature focused on app-user data reads, validation, and controlled writes; future extension to additional Firestore collections should reuse the same service boundary

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- Pass: I. Entrega centrada en Laravel. The integration will be implemented as Laravel services/repositories following the framework conventions instead of ad hoc SDK use.
- Pass: II. Operaciones seguras por defecto. Secrets remain in environment configuration and no hardcoded credential values will be introduced.
- Pass: III. Control de cambios con pruebas primero. The service layer will be covered by failing tests before final implementation, especially validation and error handling.
- Pass: IV. Arquitectura centrada en integraciones. The feature enforces service isolation, validation, type mapping, and controlled Firestore access.
- Pass: V. Sistemas observables y mantenibles. Logging and typed results will be centralized so issues are diagnosable without leaking internals.

No constitution violations require a justified exception for this feature.

## Project Structure

### Documentation (this feature)

```text
specs/001-firestore-data-layer/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output
├── spec.md              # Feature specification
└── tasks.md             # Phase 2 output (not created by this plan step)
```

### Source Code (repository root)

```text
app/
├── Models/
│   └── User.php
├── Providers/
│   └── AppServiceProvider.php
├── Services/
│   └── Firestore/
│       ├── FirestoreClientFactory.php
│       ├── FirestoreUserGateway.php
│       ├── FirestoreUserValidator.php
│       ├── FirestoreResult.php
│       └── ScoreBoxUser.php
├── Support/
│   └── Firestore/
│       └── FirestoreQueryOptions.php
└── Http/Controllers/
    └── (future: only route controllers, never direct SDK calls)

tests/
├── Feature/
│   └── Firestore/
│       └── UserGatewayTest.php
├── Unit/
│   └── Firestore/
│       └── ScoreBoxUserValidatorTest.php
└── TestCase.php

config/
├── firebase.php
└── app.php
```

**Structure Decision**: The feature will be implemented as a Laravel service boundary under `app/Services/Firestore`, keeping `App\Models\User` strictly for the admin panel and Firestore DTOs isolated from Filament resource layers.

## Complexity Tracking

> **No violations** requiring explicit governance exceptions.

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| None | N/A | N/A |
