# Quickstart: validation of the Firestore data layer

## Prerequisites

- Docker Compose for the Laravel project is available.
- A service account JSON for Firebase is configured in the Laravel environment.
- Firestore is enabled in the target Google Cloud project and the service account has the necessary permissions.

## Environment variables

Add or confirm the following values in the Laravel environment:

```env
FIREBASE_CREDENTIALS=/path/to/service-account.json
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_FIRESTORE_DATABASE=(default)
FIREBASE_DATABASE_URL=https://your-project-id.firebaseio.com
```

The application code must read these values through the config layer, not via direct `env()` calls inside business logic.

## Validation steps

1. Start the Laravel environment if needed:

```bash
docker compose up -d
```

2. Run the relevant tests for the new boundary:

```bash
docker compose exec laravel.test php artisan test --filter=Firestore
```

3. Validate a direct lookup path:

```bash
docker compose exec laravel.test php artisan tinker --execute="app(App\\Services\\Firestore\\FirestoreUserGateway::class)->getById('sample-user-id');"
```

4. Confirm logging behavior by forcing a permission failure or invalid payload and checking Laravel logs.

## Expected outcome

- A valid Firestore document is returned as a typed `ScoreBoxUser`-like payload.
- An invalid or missing document returns a controlled result instead of an unhandled exception.
- Permission or connectivity issues are logged and surfaced as a safe result object, not as a raw SDK error.
- No direct Firestore SDK calls are used from Filament, Controllers, or views.
