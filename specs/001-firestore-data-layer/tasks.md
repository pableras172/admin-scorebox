# Tasks: Firestore Data Layer

**Input**: Design documents from `/specs/001-firestore-data-layer/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Preparar la estructura base para la integración de Firestore y sus pruebas.

- [X] T001 Create Firestore service directory structure in app/Services/Firestore/
- [X] T002 [P] Create Firestore test directories in tests/Feature/Firestore/ and tests/Unit/Firestore/
- [X] T003 [P] Review and align Firebase config contract in config/firebase.php

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Establecer la capa base de dominio, validación y resultados tipados antes de implementar historias de negocio.

- [X] T004 Define Firestore result envelope in app/Services/Firestore/FirestoreResult.php
- [X] T005 [P] Implement typed mobile user DTO in app/Services/Firestore/ScoreBoxUser.php
- [X] T006 [P] Implement Firestore payload validator in app/Services/Firestore/FirestoreUserValidator.php
- [X] T007 Register Firestore service bindings and logging hooks in app/Providers/AppServiceProvider.php
- [X] T008 Add safe Firestore configuration guardrails in config/firebase.php

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - Acceso seguro a usuarios de la app desde Laravel (Priority: P1) 🎯 MVP

**Goal**: Permitir que el backend de Laravel lea usuarios de Firestore sin depender del SDK desde UI o controladores.

**Independent Test**: Verificar que un documento válido se transforma en un modelo tipado y que un documento inválido devuelve un resultado controlado.

### Tests for User Story 1

- [X] T009 [P] [US1] Add failing feature test for valid Firestore user retrieval in tests/Feature/Firestore/FirestoreUserGatewayTest.php
- [X] T010 [P] [US1] Add failing validator unit tests in tests/Unit/Firestore/ScoreBoxUserValidatorTest.php

### Implementation for User Story 1

- [X] T011 [US1] Implement Firestore client factory in app/Services/Firestore/FirestoreClientFactory.php
- [X] T012 [US1] Implement read methods and data mapping in app/Services/Firestore/FirestoreUserGateway.php
- [X] T013 [US1] Add validation and normalization before returning data in app/Services/Firestore/FirestoreUserValidator.php
- [X] T014 [US1] Add controlled exception handling and log capture in app/Services/Firestore/FirestoreUserGateway.php
- [X] T015 [US1] Expose the gateway through the Laravel container in app/Providers/AppServiceProvider.php

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently

---

## Phase 4: User Story 2 - Separación clara entre usuarios administrativos y usuarios de la app (Priority: P1)

**Goal**: Garantizar que el backend distingue explícitamente `User` de Laravel y `ScoreBoxUser` de Firestore.

**Independent Test**: Verificar que la capa de lectura separa dominios y no mezcla un usuario administrador con un usuario móvil.

### Tests for User Story 2

- [ ] T016 [P] [US2] Add failing boundary test for admin vs mobile user separation in tests/Feature/Firestore/FirestoreUserGatewayTest.php

### Implementation for User Story 2

- [ ] T017 [US2] Create explicit domain boundary helper in app/Services/Firestore/FirestoreUserContext.php
- [ ] T018 [US2] Enforce separation logic in app/Services/Firestore/FirestoreUserGateway.php
- [ ] T019 [US2] Document the admin-only contract for `App\Models\User` in app/Models/User.php

**Checkpoint**: At this point, User Stories 1 and 2 should both work independently

---

## Phase 5: User Story 3 - Gestión segura y observable de errores de Firestore (Priority: P2)

**Goal**: Producir una respuesta segura ante errores de conexión, permisos o documentos inválidos.

**Independent Test**: Simular un error de permisos o fallo de conexión y confirmar que se registra y vuelve a la capa superior como resultado tipado.

### Tests for User Story 3

- [ ] T020 [P] [US3] Add failing error-handling feature test in tests/Feature/Firestore/FirestoreUserGatewayTest.php

### Implementation for User Story 3

- [ ] T021 [US3] Implement error mapping and logging in app/Services/Firestore/FirestoreUserGateway.php
- [ ] T022 [US3] Add limit and pagination guard for list queries in app/Services/Firestore/FirestoreUserGateway.php
- [ ] T023 [US3] Add controlled write validation path in app/Services/Firestore/FirestoreUserGateway.php

**Checkpoint**: All user stories should now be independently functional

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Revisión final, compatibilidad y validación del flujo completo.

- [ ] T024 [P] Sync documentation and operational notes in README.md
- [ ] T025 [P] Run linting and static review for app/Services/Firestore/ and tests/
- [ ] T026 Final smoke validation following specs/001-firestore-data-layer/quickstart.md

---

## Dependencies & Execution Order

### Phase Dependencies

- Setup (Phase 1): no dependencies
- Foundational (Phase 2): depends on Setup completion and blocks all user stories
- User Story 1: depends on Phase 2
- User Story 2: depends on Phase 2 and can run in parallel with Story 1 if needed
- User Story 3: depends on Phase 2 and can be developed in parallel with Stories 1 and 2
- Polish: depends on all target stories being complete

### Parallel Opportunities

- T002 and T003 can run in parallel during Setup
- T005 and T006 can run in parallel during Foundational
- The tests for each story can be created in parallel with implementation work on that same story
- Story 1, Story 2, and Story 3 can be developed in parallel once the foundational layer is complete

### MVP Scope

The recommended MVP is User Story 1 only: safe read access and validation for mobile app users.

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational
3. Complete Phase 3: User Story 1
4. Validate independently before extending scope

### Incremental Delivery

1. Setup + Foundational
2. User Story 1 (MVP)
3. User Story 2 (domain separation)
4. User Story 3 (error handling and safety)
5. Final polish and validation
