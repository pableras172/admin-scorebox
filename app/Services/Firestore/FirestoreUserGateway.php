<?php

declare(strict_types=1);

namespace App\Services\Firestore;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

final class FirestoreUserGateway
{
    private const COLLECTION_NAME = 'users';

    private const MAX_LIST_LIMIT = 100;

    public function __construct(
        private readonly FirestoreClientFactory $factory,
    ) {}

    public function getById(string $uid): FirestoreResult
    {
        try {
            $client = $this->factory->make();

            $snapshot = $client
                ->collection(self::COLLECTION_NAME)
                ->document($uid)
                ->snapshot();

            if ($snapshot === null || ! method_exists($snapshot, 'data')) {
                return FirestoreResult::failure('NOT_FOUND', 'The requested Firestore user was not found.', [
                    'uid' => $uid,
                ]);
            }

            $data = $snapshot->data();
            if ((! array_key_exists('uid', $data) || ! is_string($data['uid']) || trim($data['uid']) === '') && method_exists($snapshot, 'id')) {
                $data['uid'] = $snapshot->id();
            }

            $normalized = FirestoreUserValidator::validate($data);

            return FirestoreResult::success(
                ScoreBoxUser::fromArray($normalized)->toArray(),
                ['uid' => $uid, 'collection' => self::COLLECTION_NAME],
            );
        } catch (InvalidArgumentException $exception) {
            Log::warning('Firestore user validation failed.', [
                'uid' => $uid,
                'error' => $exception->getMessage(),
            ]);

            return FirestoreResult::failure('INVALID_DOCUMENT', $exception->getMessage(), [
                'uid' => $uid,
            ]);
        } catch (RuntimeException|\Throwable $exception) {
            Log::error('Firestore user lookup failed.', [
                'uid' => $uid,
                'error' => $exception->getMessage(),
            ]);

            return FirestoreResult::failure('FIRESTORE_ERROR', 'Unable to read the Firestore user at this time.', [
                'uid' => $uid,
            ]);
        }
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function countAll(): FirestoreResult
    {
        try {
            $client = $this->factory->make();
            $collection = $client->collection(self::COLLECTION_NAME);
            $total = 0;

            foreach ($collection->documents() as $document) {
                if ($document !== null) {
                    $total++;
                }
            }

            return FirestoreResult::success($total, [
                'collection' => self::COLLECTION_NAME,
                'count' => $total,
            ]);
        } catch (RuntimeException|\Throwable $exception) {
            Log::error('Firestore user count failed.', [
                'collection' => self::COLLECTION_NAME,
                'error' => $exception->getMessage(),
            ]);

            return FirestoreResult::failure('FIRESTORE_ERROR', 'Unable to count Firestore users at this time.', [
                'collection' => self::COLLECTION_NAME,
            ]);
        }
    }

    public function countByPremiumStatus(): FirestoreResult
    {
        try {
            $client = $this->factory->make();

            $premiumCount = 0;
            foreach ($client->collection(self::COLLECTION_NAME)->where('isPremium', '==', true)->documents() as $document) {
                if ($document !== null) {
                    $premiumCount++;
                }
            }

            $freeCount = 0;
            foreach ($client->collection(self::COLLECTION_NAME)->where('isPremium', '==', false)->documents() as $document) {
                if ($document !== null) {
                    $freeCount++;
                }
            }

            return FirestoreResult::success([
                'premium' => $premiumCount,
                'free' => $freeCount,
            ], [
                'collection' => self::COLLECTION_NAME,
                'premium_count' => $premiumCount,
                'free_count' => $freeCount,
            ]);
        } catch (RuntimeException|\Throwable $exception) {
            Log::error('Firestore premium/free user count failed.', [
                'collection' => self::COLLECTION_NAME,
                'error' => $exception->getMessage(),
            ]);

            return FirestoreResult::failure('FIRESTORE_ERROR', 'Unable to count Firestore users by premium status.', [
                'collection' => self::COLLECTION_NAME,
            ]);
        }
    }

    public function list(array $filters = [], int $limit = 25): FirestoreResult
    {
        if ($limit < 1 || $limit > self::MAX_LIST_LIMIT) {
            return FirestoreResult::failure('INVALID_LIMIT', 'The Firestore query limit must be between 1 and 100.', [
                'limit' => $limit,
            ]);
        }

        try {
            Log::info('Firestore user list request started.', [
                'collection' => self::COLLECTION_NAME,
                'filters' => $filters,
                'limit' => $limit,
            ]);

            $client = $this->factory->make();
            $query = $client->collection(self::COLLECTION_NAME);

            foreach ($filters as $field => $value) {
                $query = $query->where((string) $field, '==', $value);
            }

            $documents = $query
                ->orderBy('createdAt', 'DESC')
                ->limit($limit)
                ->documents();
            $users = [];

            foreach ($documents as $index => $document) {
                $payload = is_array($document) ? $document : $document->data();

                if (! is_array($document) && (! array_key_exists('uid', $payload) || ! is_string($payload['uid']) || trim($payload['uid']) === '') && method_exists($document, 'id')) {
                    $payload['uid'] = $document->id();
                }

                Log::info('Firestore user document received.', [
                    'index' => $index,
                    'document_keys' => array_keys($payload),
                    'document_preview' => $this->maskSensitiveFields($payload),
                ]);

                $normalized = FirestoreUserValidator::validate($payload);
                $users[] = ScoreBoxUser::fromArray($normalized)->toArray();
            }

            Log::info('Firestore user list request completed.', [
                'collection' => self::COLLECTION_NAME,
                'requested_limit' => $limit,
                'returned_count' => count($users),
                'sample' => array_slice($users, 0, 2),
            ]);

            return FirestoreResult::success($users, [
                'limit' => $limit,
                'count' => count($users),
                'collection' => self::COLLECTION_NAME,
            ]);
        } catch (InvalidArgumentException $exception) {
            Log::warning('Firestore user list validation failed.', [
                'filters' => $filters,
                'limit' => $limit,
                'error' => $exception->getMessage(),
            ]);

            return FirestoreResult::failure('INVALID_DOCUMENT', $exception->getMessage(), [
                'filters' => $filters,
                'limit' => $limit,
            ]);
        } catch (RuntimeException|\Throwable $exception) {
            Log::error('Firestore user list lookup failed.', [
                'filters' => $filters,
                'limit' => $limit,
                'error' => $exception->getMessage(),
            ]);

            return FirestoreResult::failure('FIRESTORE_ERROR', 'Unable to list Firestore users at this time.', [
                'filters' => $filters,
                'limit' => $limit,
            ]);
        }
    }

    private function maskSensitiveFields(array $payload): array
    {
        $masked = $payload;

        foreach (['email', 'photoUrl', 'displayName', 'uid'] as $field) {
            if (array_key_exists($field, $masked) && is_string($masked[$field])) {
                $masked[$field] = str_starts_with($masked[$field], 'http')
                    ? '[url]'
                    : (str_contains($masked[$field], '@') ? '[email]' : '[redacted]');
            }
        }

        return $masked;
    }

    /**
     * @param array<string, mixed> $changes
     */
    public function update(string $uid, array $changes): FirestoreResult
    {
        if ($uid === '' || trim($uid) === '') {
            return FirestoreResult::failure('INVALID_DOCUMENT', 'The Firestore user uid cannot be empty.', [
                'uid' => $uid,
            ]);
        }

        if ($changes === []) {
            return FirestoreResult::failure('INVALID_DOCUMENT', 'No Firestore user fields were provided for update.', [
                'uid' => $uid,
            ]);
        }

        try {
            $allowedKeys = ['email', 'displayName', 'photoUrl', 'active', 'profile'];
            $invalidKeys = array_diff(array_keys($changes), $allowedKeys);

            if ($invalidKeys !== []) {
                throw new InvalidArgumentException(sprintf(
                    'The update payload contains unsupported fields: %s.',
                    implode(', ', $invalidKeys)
                ));
            }

            $payload = ['uid' => $uid] + $changes;
            $normalized = FirestoreUserValidator::validate($payload);
            $sanitized = [];

            foreach (['email', 'displayName', 'photoUrl', 'active', 'profile'] as $field) {
                if (array_key_exists($field, $normalized)) {
                    $sanitized[$field] = $normalized[$field];
                }
            }

            $client = $this->factory->make();
            $client
                ->collection(self::COLLECTION_NAME)
                ->document($uid)
                ->set($sanitized);

            return FirestoreResult::success($sanitized, [
                'uid' => $uid,
                'collection' => self::COLLECTION_NAME,
                'operation' => 'update',
            ]);
        } catch (InvalidArgumentException $exception) {
            Log::warning('Firestore user update validation failed.', [
                'uid' => $uid,
                'changes' => $changes,
                'error' => $exception->getMessage(),
            ]);

            return FirestoreResult::failure('INVALID_DOCUMENT', $exception->getMessage(), [
                'uid' => $uid,
                'changes' => $changes,
            ]);
        } catch (RuntimeException|\Throwable $exception) {
            Log::error('Firestore user update failed.', [
                'uid' => $uid,
                'changes' => $changes,
                'error' => $exception->getMessage(),
            ]);

            return FirestoreResult::failure('FIRESTORE_ERROR', 'Unable to update the Firestore user at this time.', [
                'uid' => $uid,
                'changes' => $changes,
            ]);
        }
    }
}
