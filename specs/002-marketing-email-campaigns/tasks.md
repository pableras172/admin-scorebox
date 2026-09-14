# Tasks: Módulo de Campañas de Email Marketing y Desuscripción

**Input**: Design documents from `/specs/002-marketing-email-campaigns/`

**Prerequisites**: `plan.md`, `spec.md`, `research.md`, `data-model.md`, `contracts/`

**Organization**: Tareas organizadas por fases y por Historias de Usuario (US1, US2, US3) para permitir implementación y pruebas independientes.

## Format: `[ID] [P?] [Story] Description`
- **[P]**: Ejecutable en paralelo (archivos independientes, sin dependencias previas bloqueantes).
- **[Story]**: Historia de usuario a la que pertenece la tarea (US1, US2, US3).

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Preparar la estructura de carpetas, migraciones de base de datos local y dependencias compartidas.

- [x] T001 Crear las migraciones de base de datos para las tablas `marketing_campaigns` y `email_unsubscribes` en `database/migrations/2026_09_13_000001_create_marketing_campaigns_table.php` y `database/migrations/2026_09_13_000002_create_email_unsubscribes_table.php`
- [x] T002 [P] Crear la estructura de directorios en `app/Filament/Resources/MarketingCampaigns/`, `app/Filament/Resources/EmailUnsubscribes/`, `app/Services/Marketing/`, `resources/views/emails/`, `resources/views/marketing/` y `tests/Feature/Marketing/`
- [x] T003 Ejecutar las migraciones de base de datos con `php artisan migrate` para aprovisionar las tablas en SQLite

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Modelos Eloquent, DTOs y servicios base que bloquean la implementación de las historias de usuario.

**⚠️ CRITICAL**: No comenzar las fases de historias de usuario hasta completar esta fase fundacional.

- [x] T004 Implementar el modelo Eloquent `MarketingCampaign` con constantes de estado (`draft`, `queued`, `sending`, `sent`, `failed`), casts y scopes en `app/Models/MarketingCampaign.php`
- [x] T005 [P] Implementar el modelo Eloquent `EmailUnsubscribe` con métodos estáticos de conveniencia (`isUnsubscribed`, `unsubscribe`, `resubscribe`) en `app/Models/EmailUnsubscribe.php`
- [x] T006 [P] Implementar el DTO inmutable `CampaignRecipient` (`email`, `name`, `isPremium`) en `app/Services/Marketing/CampaignRecipient.php`
- [x] T007 Implementar el servicio `CampaignAudienceResolver` para consultar `FirestoreUserGateway::list()`, filtrar por segmento (`all`, `free`, `premium`) y excluir correos presentes en `EmailUnsubscribe` en `app/Services/Marketing/CampaignAudienceResolver.php`
- [x] T008 [P] Diseñar la plantilla Blade base responsive para correos de marketing en `resources/views/emails/marketing-campaign.blade.php`
- [x] T009 [P] Implementar la clase Mailable `MarketingCampaignMailable` con inyección de cabeceras RFC 8058 (`List-Unsubscribe` / `List-Unsubscribe-Post`) y enlace firmado en `app/Mail/MarketingCampaignMailable.php`

**Checkpoint**: Base de datos, modelos y servicios base listos.

---

## Phase 3: User Story 1 - Creación y Envío Segmentado de Campañas de Correo (Priority: P1) 🎯 MVP

**Goal**: Permitir al administrador redactar campañas en Filament, ver el recuento dinámico previo de destinatarios, seleccionar la audiencia (`all`, `free`, `premium`) y lanzar el envío asíncrono vía Laravel Queue.

**Independent Test**: Crear una campaña en Filament, abrir el modal de confirmación, verificar el recuento dinámico previo, confirmar el envío y comprobar que el Job procesa los correos en lotes y actualiza el estado a `sent`.

### Tests for User Story 1

- [x] T010 [P] [US1] Crear prueba unitaria para `CampaignAudienceResolver` que verifique el filtrado por segmento y la exclusión de desuscritos en `tests/Unit/Marketing/CampaignAudienceResolverTest.php`
- [x] T011 [P] [US1] Crear prueba de integración para `SendMarketingCampaignJob` simulando el procesamiento por lotes y reemplazo de `{{name}}` en `tests/Feature/Marketing/SendMarketingCampaignJobTest.php`

### Implementation for User Story 1

- [x] T012 [US1] Implementar el Job de cola `SendMarketingCampaignJob` con `$tries = 3`, `$backoff = [10, 30, 60]`, lectura por lotes de Firestore, exclusión de bajas, reemplazo de `{{name}}` y actualización de métricas en `app/Jobs/SendMarketingCampaignJob.php`
- [x] T013 [P] [US1] Crear el esquema de formulario de campaña `MarketingCampaignForm` con campos para asunto, selector de segmento y contenido con RichEditor en `app/Filament/Resources/MarketingCampaigns/Schemas/MarketingCampaignForm.php`
- [x] T014 [US1] Crear la tabla `MarketingCampaignsTable` con badges de estado, recuentos y la acción de cabecera/registro "Enviar Campaña" con modal interactivo de cálculo dinámico de destinatarios en `app/Filament/Resources/MarketingCampaigns/Tables/MarketingCampaignsTable.php`
- [x] T015 [US1] Implementar las páginas del recurso Filament: `ListMarketingCampaigns`, `CreateMarketingCampaign`, `EditMarketingCampaign` y `ViewMarketingCampaign` en `app/Filament/Resources/MarketingCampaigns/Pages/`
- [x] T016 [US1] Registrar y configurar el recurso `MarketingCampaignResource` bajo el grupo de navegación "Marketing" en `app/Filament/Resources/MarketingCampaigns/MarketingCampaignResource.php`

**Checkpoint**: User Story 1 (MVP) completamente operativa e independientemente verificable.

---

## Phase 4: User Story 2 - Desuscripción Voluntaria en Un Solo Clic (Priority: P1)

**Goal**: Permitir que cualquier usuario que reciba un correo pueda darse de baja inmediatamente pulsando el enlace firmado del pie del correo, revocar la baja si fue accidental, y responder a cabeceras POST RFC 8058.

**Independent Test**: Generar una URL firmada de baja, acceder por GET verificando que se inserta el registro en `email_unsubscribes` y se muestra la vista con botón de reversión; probar el botón de reversión y verificar que el email se elimina de la lista.

### Tests for User Story 2

- [x] T017 [P] [US2] Crear prueba de Feature para el flujo de desuscripción y reversión (GET firmado, POST RFC 8058 y reversión) en `tests/Feature/Marketing/UnsubscribeFlowTest.php`

### Implementation for User Story 2

- [x] T018 [US2] Implementar el controlador `UnsubscribeController` con métodos `unsubscribe` (GET firmado), `resubscribe` (POST firmado) y `unsubscribePost` (POST RFC 8058) en `app/Http/Controllers/Marketing/UnsubscribeController.php`
- [x] T019 [P] [US2] Crear la vista Blade de confirmación de desuscripción con botón de reversión en `resources/views/marketing/unsubscribed.blade.php`
- [x] T020 [P] [US2] Crear la vista Blade de confirmación de re-suscripción en `resources/views/marketing/resubscribed.blade.php`
- [x] T021 [US2] Registrar las rutas públicas de marketing en `routes/web.php` con middleware `signed`

**Checkpoint**: Flujo de baja en 1 clic y reversión voluntaria 100% funcional.

---

## Phase 5: User Story 3 - Supervisión y Gestión de Bajas desde el Panel (Priority: P2)

**Goal**: Permitir al administrador visualizar el listado completo de correos desuscritos en Filament y añadir o eliminar bajas manualmente.

**Independent Test**: Acceder a "Bajas de Marketing" en Filament, registrar una baja manual de prueba y comprobar que se refleja en la lista y es respetada por el resolver de audiencias.

### Implementation for User Story 3

- [x] T022 [P] [US3] Crear la tabla `EmailUnsubscribesTable` con columnas de email, fecha de baja, motivo, origen y acción para registrar baja manual en `app/Filament/Resources/EmailUnsubscribes/Tables/EmailUnsubscribesTable.php`
- [x] T023 [US3] Crear el recurso `EmailUnsubscribeResource` y su página `ListEmailUnsubscribes` bajo el grupo de navegación "Marketing" en `app/Filament/Resources/EmailUnsubscribes/EmailUnsubscribeResource.php`

**Checkpoint**: Supervisión y gestión manual de bajas completada en Filament.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Verificación global, pruebas automatizadas y cierre de ciclo.

- [x] T024 [P] Ejecutar la suite completa de pruebas con `php artisan test --filter=Marketing` y verificar que todos los tests pasen en verde
- [x] T025 [P] Verificar registros de logs de auditoría ante fallos simulados y validar diseño responsive de la plantilla Blade de correo

---

## Dependencies & Execution Order

```text
Phase 1 (Setup)
    │
    ▼
Phase 2 (Foundational: Migraciones, Modelos, DTO, Resolver, Mailable)
    │
    ├──────────────────────────┐
    ▼                          ▼
Phase 3 (US1: Campañas MVP)   Phase 4 (US2: Desuscripción)
    │                          │
    └───────────┬──────────────┘
                ▼
Phase 5 (US3: Panel de Bajas)
    │
    ▼
Phase 6 (Polish & Tests)
```

## Parallel Execution Opportunities

- **Fase 2**: Los modelos `MarketingCampaign` (T004) y `EmailUnsubscribe` (T005), junto con `CampaignRecipient` (T006) y la vista Blade (T008), pueden desarrollarse en paralelo al no tener dependencias mutuas.
- **Fase 3**: Los tests (T010, T011) pueden escribirse en paralelo a los esquemas de formulario de Filament (T013).
- **Fase 4**: Las vistas Blade `unsubscribed.blade.php` (T019) y `resubscribed.blade.php` (T020) pueden diseñarse en paralelo al controlador (T018).

