<?php

declare(strict_types=1);

namespace App\Services\Firestore;

use Carbon\Carbon;
use Google\Cloud\Core\Timestamp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class FirestoreMusiciansDirectoryGateway
{
    private const COLLECTION_NAME = 'musicians_directory';

    private const CACHE_TTL_LIST = 300; // 5 minutes

    private const CACHE_TTL_DOC = 300;

    public function __construct(
        private readonly FirestoreClientFactory $factory,
    ) {}

    /**
     * Retrieve all musician profiles from Firestore.
     */
    public function all(bool $fresh = false): FirestoreResult
    {
        $cacheKey = 'firestore:musicians:all';

        if (! $fresh && Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if ($cached instanceof FirestoreResult && $cached->isSuccess()) {
                return $cached;
            }
        }

        try {
            $client = $this->factory->make();
            $collection = $client->collection(self::COLLECTION_NAME);

            /** @var iterable<object> $documents */
            $documents = $collection->documents();

            $profiles = [];

            foreach ($documents as $doc) {
                if ($doc === null || ! method_exists($doc, 'data') || ! method_exists($doc, 'id')) {
                    continue;
                }

                $data = (array) $doc->data();
                $id = (string) $doc->id();

                $profiles[] = $this->mapDocumentData($id, $data);
            }

            $result = FirestoreResult::success($profiles, [
                'count' => count($profiles),
                'collection' => self::COLLECTION_NAME,
            ]);

            Cache::put($cacheKey, $result, now()->addSeconds(self::CACHE_TTL_LIST));

            return $result;
        } catch (Throwable $e) {
            Log::error('Firestore musicians_directory lookup failed: '.$e->getMessage());

            return FirestoreResult::failure('FIRESTORE_ERROR', 'No se pudieron recuperar los perfiles del directorio: '.$e->getMessage(), [
                'collection' => self::COLLECTION_NAME,
            ]);
        }
    }

    /**
     * Retrieve a specific musician profile by userId.
     */
    public function getById(string $userId, bool $fresh = false): FirestoreResult
    {
        $cacheKey = 'firestore:musicians:doc:'.$userId;

        if (! $fresh && Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if ($cached instanceof FirestoreResult && $cached->isSuccess()) {
                return $cached;
            }
        }

        try {
            $client = $this->factory->make();
            $snapshot = $client->collection(self::COLLECTION_NAME)->document($userId)->snapshot();

            if (! $snapshot->exists()) {
                return FirestoreResult::failure('NOT_FOUND', "El perfil del músico [{$userId}] no existe en Firestore.", [
                    'userId' => $userId,
                ]);
            }

            $data = (array) $snapshot->data();
            $profile = $this->mapDocumentData($userId, $data);

            $result = FirestoreResult::success($profile, [
                'userId' => $userId,
                'collection' => self::COLLECTION_NAME,
            ]);

            Cache::put($cacheKey, $result, now()->addSeconds(self::CACHE_TTL_DOC));

            return $result;
        } catch (Throwable $e) {
            Log::error("Firestore musicians_directory lookup for [{$userId}] failed: ".$e->getMessage());

            return FirestoreResult::failure('FIRESTORE_ERROR', $e->getMessage(), [
                'userId' => $userId,
            ]);
        }
    }

    /**
     * Update musician profile attributes in Firestore.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(string $userId, array $data): FirestoreResult
    {
        if (trim($userId) === '') {
            return FirestoreResult::failure('INVALID_ID', 'El ID de usuario no puede estar vacío.');
        }

        try {
            $client = $this->factory->make();
            $data['updatedAt'] = (int) round(microtime(true) * 1000);

            $client->collection(self::COLLECTION_NAME)
                ->document($userId)
                ->set($data, ['merge' => true]);

            $this->clearCache();
            Cache::forget('firestore:musicians:doc:'.$userId);

            Log::info("Updated musician profile [{$userId}] in Firestore.");

            return FirestoreResult::success($data, [
                'userId' => $userId,
                'collection' => self::COLLECTION_NAME,
            ]);
        } catch (Throwable $e) {
            Log::error("Failed to update musician profile [{$userId}]: ".$e->getMessage());

            return FirestoreResult::failure('FIRESTORE_ERROR', 'Error al actualizar el perfil en Firestore: '.$e->getMessage(), [
                'userId' => $userId,
            ]);
        }
    }

    public function toggleVisibility(string $userId, bool $visible): FirestoreResult
    {
        return $this->update($userId, ['isVisible' => $visible]);
    }

    public function toggleFeatured(string $userId, bool $featured): FirestoreResult
    {
        return $this->update($userId, ['isFeatured' => $featured]);
    }

    public function toggleVerified(string $userId, bool $verified): FirestoreResult
    {
        return $this->update($userId, ['isVerified' => $verified]);
    }

    public function clearCache(): void
    {
        Cache::forget('firestore:musicians:all');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mapDocumentData(string $id, array $data): array
    {
        $userId = isset($data['userId']) && is_string($data['userId']) && trim($data['userId']) !== ''
            ? trim($data['userId'])
            : $id;

        $created = $this->normalizeDate($data['createdAt'] ?? null);
        $updated = $this->normalizeDate($data['updatedAt'] ?? null);

        $roles = isset($data['roles']) && is_array($data['roles']) ? array_values($data['roles']) : [];
        $secondary = isset($data['secondaryInstruments']) && is_array($data['secondaryInstruments']) ? array_values($data['secondaryInstruments']) : [];
        $styles = isset($data['musicalStyles']) && is_array($data['musicalStyles']) ? array_values($data['musicalStyles']) : [];

        return [
            'userId' => $userId,
            'isVisible' => (bool) ($data['isVisible'] ?? false),
            'displayName' => isset($data['displayName']) && is_string($data['displayName']) ? trim($data['displayName']) : 'Sin nombre',
            'photoUrl' => isset($data['photoUrl']) && is_string($data['photoUrl']) ? trim($data['photoUrl']) : null,
            'primaryInstrumentId' => isset($data['primaryInstrumentId']) && is_string($data['primaryInstrumentId']) ? trim($data['primaryInstrumentId']) : null,
            'primaryInstrumentName' => isset($data['primaryInstrumentName']) && is_string($data['primaryInstrumentName']) ? trim($data['primaryInstrumentName']) : null,
            'secondaryInstruments' => $secondary,
            'countryCode' => isset($data['countryCode']) && is_string($data['countryCode']) ? trim($data['countryCode']) : null,
            'countryName' => isset($data['countryName']) && is_string($data['countryName']) ? trim($data['countryName']) : null,
            'city' => isset($data['city']) && is_string($data['city']) ? trim($data['city']) : null,
            'roles' => $roles,
            'musicalStyles' => $styles,
            'bio' => isset($data['bio']) && is_string($data['bio']) ? trim($data['bio']) : null,
            'instagramHandle' => isset($data['instagramHandle']) && is_string($data['instagramHandle']) ? trim($data['instagramHandle']) : null,
            'whatsappPhone' => isset($data['whatsappPhone']) && is_string($data['whatsappPhone']) ? trim($data['whatsappPhone']) : null,
            'email' => isset($data['email']) && is_string($data['email']) ? trim($data['email']) : null,
            'websiteUrl' => isset($data['websiteUrl']) && is_string($data['websiteUrl']) ? trim($data['websiteUrl']) : null,
            'isPro' => (bool) ($data['isPro'] ?? false),
            'isFeatured' => (bool) ($data['isFeatured'] ?? false),
            'isVerified' => (bool) ($data['isVerified'] ?? false),
            'createdAt' => $created,
            'updatedAt' => $updated,
        ];
    }

    private function normalizeDate(mixed $val): ?string
    {
        if ($val === null) {
            return null;
        }

        if ($val instanceof Timestamp) {
            return Carbon::createFromTimestamp($val->get()->getTimestamp())->toIso8601String();
        }

        if (is_numeric($val)) {
            $num = (float) $val;

            return $num > 10000000000
                ? Carbon::createFromTimestampMs((int) round($num))->toIso8601String()
                : Carbon::createFromTimestamp((int) round($num))->toIso8601String();
        }

        if (is_string($val)) {
            return $val;
        }

        return null;
    }
}
