# Contract: Firestore mobile-user access

## Purpose

This contract defines the expected boundary for interacting with Firestore user documents from the Laravel backend.

## Service interface

```php
interface FirestoreUserGateway
{
    public function getById(string $uid): FirestoreResult;
    public function list(array $filters = [], int $limit = 25): FirestoreResult;
    public function update(string $uid, array $changes): FirestoreResult;
}
```

## Payload contract

A successful read returns a normalized structure equivalent to:

```json
{
  "success": true,
  "data": {
    "uid": "abc123",
    "email": "user@example.com",
    "displayName": "Example User",
    "photoUrl": "https://example.com/avatar.png",
    "active": true,
    "createdAt": "2026-08-30T10:00:00Z",
    "updatedAt": "2026-08-30T10:15:00Z",
    "profile": {
      "language": "es"
    }
  },
  "error": null,
  "metadata": {
    "source": "firestore",
    "collection": "users"
  }
}
```

## Error contract

On validation or permission error, the result must provide structured metadata and a non-sensitive error reason:

```json
{
  "success": false,
  "data": null,
  "error": "USER_NOT_FOUND",
  "metadata": {
    "source": "firestore",
    "collection": "users",
    "attemptedUid": "abc123"
  }
}
```

## Constraints

- The UI layer must never call the Firestore SDK directly.
- Query results are limited by pagination and explicit filters.
- All writes require validation before persistence.
- Logs must record the diagnostic details without exposing service-account secrets or project configuration values.
