# Data Model: Firestore Access Layer

## Entities

### User

Represents a Laravel administrator identity.

| Field | Type | Notes |
|------|------|-------|
| id | int | Primary key in local database |
| name | string | Admin display name |
| email | string | Laravel auth identity |
| password | string | Hashed password |
| email_verified_at | datetime|null | Optional verification status |

Relationships:
- One local admin user may access Filament features.
- This entity is not used to represent Firestore mobile-user records.

### ScoreBoxUser

Represents a user document stored in Firestore for the Android app.

| Field | Type | Validation |
|------|------|------------|
| uid | string | Required, non-empty |
| email | string|null | Optional but must be valid when present |
| displayName | string|null | Optional |
| photoUrl | string|null | Optional, URL when present |
| createdAt | string|Carbon | Must be parseable timestamp |
| updatedAt | string|Carbon | Must be parseable timestamp |
| profile | array|null | Optional nested payload; validated as assoc array |
| active | bool | Default false when omitted |

Relationships:
- A `ScoreBoxUser` belongs to the Firestore mobile-app dataset and can be read via the gateway.
- It is not the same domain as the `User` table used by the admin backend.

### FirestoreDataGateway

Service boundary responsible for all Firestore interaction.

| Method | Purpose |
|--------|---------|
| getById(string $uid): FirestoreResult | Read a single mobile user |
| list(array $filters, int $limit = 25): FirestoreResult | Read a bounded set of user documents |
| update(string $uid, array $changes): FirestoreResult | Controlled update on validated fields |
| create(array $payload): FirestoreResult | Optional future creation path with validation |

### FirestoreResult

Typed outcome object used by callers.

| Field | Type | Notes |
|------|------|-------|
| success | bool | Whether the operation succeeded |
| data | mixed|null | Result payload |
| error | string|null | Error code or message |
| metadata | array | Context for logging and pagination |

## Validation rules

- `uid` is required and must be a non-empty string.
- `email` must be valid when present.
- `createdAt` and `updatedAt` must be parseable datetimes.
- `profile` must be an associative array when provided.
- `active` must be a boolean value after normalization.
- All writes must validate requested fields before they reach Firestore.

## State transitions

`ScoreBoxUser` documents are treated as data-only entities with two lifecycle states:
- `present` when document exists and passes validation
- `invalid` when a document is missing required fields or has incompatible types
- `not_found` when the document is absent
- `error` when Firestore access fails or permission is denied

## Notes

The access layer should normalize Firestore values into strict Laravel-friendly PHP types before returning them to the rest of the backend, especially for timestamps and booleans.
