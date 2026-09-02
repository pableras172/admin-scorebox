# Research: Firestore Data Layer

## Decision

The project will use a dedicated Laravel service layer for Firestore access, with one gateway responsible for mobile-user reads and controlled writes and a typed DTO for document mapping.

## Rationale

1. The repository already contains Laravel + Filament + Firebase config, including Firestore support via the `kreait/laravel-firebase` package and the `google/cloud-firestore` dependency.
2. The admin panel uses the local Laravel user model; that is separate from app-user data in Firestore, so the system needs explicit domain boundaries.
3. The constitution requires service isolation and strict validation in all integration layers, especially for external systems like Firebase.
4. Firestore is a NoSQL system with schema drift risk, so validation and mapping should happen before any data reaches the backend or UI.

## Alternatives considered

- Direct Firestore access from Filament resources: rejected because it violates the “no Firestore in UI” rule and makes validation and error handling harder to govern.
- Single generic repository with no DTO mapping: rejected because typed data contracts are required for safe reads and future admin consumption.
- Using the local Laravel database as a source of truth for all users: rejected because the project explicitly distinguishes admin users from mobile app users and the app-level user data already lives in Firestore.

## Key design decisions

- `App\Models\User` remains the Laravel admin-auth model only; no Firestore document should be mapped to that model unless a specific admin integration is intentionally designed.
- `ScoreBoxUser` will be a typed DTO that normalizes Firestore fields like `uid`, `email`, `displayName`, `createdAt`, and optional profile metadata.
- `FirestoreUserGateway` will implement read, validate, and write operations with explicit limits and error wrapping.
- All Firestore failures must be captured via Laravel logging and returned as a typed `FirestoreResult` object.

## Open assumptions to confirm during implementation

- Firestore document IDs will be stable app-user identifiers and can be used as primary keys for lookups.
- Query patterns are limited to exact-ID lookup, filtered list operations, and controlled updates for the initial scope.
- Credentials are expected to be provided through `config('firebase.projects.app.credentials')` and env-backed values; no direct `env()` usage in application code.
