<?php

declare(strict_types=1);

namespace App\Services\Firestore;

use App\Models\Suggestion;
use Illuminate\Support\Facades\Log;
use Throwable;

class FirestoreSuggestionGateway
{
    private const COLLECTION_NAME = 'suggestions';

    public function __construct(
        private readonly FirestoreClientFactory $factory,
    ) {}

    /**
     * Synchronize suggestions stored directly in Firestore into the local MySQL database.
     * Avoids duplicates by tracking firestore_id.
     *
     * @return int Number of newly imported suggestions.
     */
    public function syncFromFirestore(): int
    {
        $importedCount = 0;

        try {
            $client = $this->factory->make();
            $collection = $client->collection(self::COLLECTION_NAME);

            /** @var iterable<object> $documents */
            $documents = $collection->documents();

            foreach ($documents as $document) {
                if ($document === null || ! method_exists($document, 'data') || ! method_exists($document, 'id')) {
                    continue;
                }

                $docId = (string) $document->id();
                $data = (array) $document->data();

                // If already imported, skip
                if (Suggestion::where('firestore_id', $docId)->exists()) {
                    continue;
                }

                $email = isset($data['email']) && is_string($data['email']) ? trim($data['email']) : '';
                $message = isset($data['message']) && is_string($data['message']) ? trim($data['message']) : '';

                if ($email === '' && $message === '') {
                    continue;
                }

                Suggestion::create([
                    'source' => Suggestion::SOURCE_APP,
                    'firestore_id' => $docId,
                    'uid' => isset($data['uid']) && is_string($data['uid']) ? $data['uid'] : null,
                    'email' => $email !== '' ? $email : 'desconocido@scorebox.app',
                    'name' => isset($data['name']) && is_string($data['name']) ? $data['name'] : (isset($data['displayName']) && is_string($data['displayName']) ? $data['displayName'] : null),
                    'is_premium' => (bool) ($data['isPremium'] ?? $data['is_premium'] ?? false),
                    'type' => isset($data['type']) && is_string($data['type']) ? $data['type'] : Suggestion::TYPE_IDEA,
                    'subject' => isset($data['subject']) && is_string($data['subject']) ? $data['subject'] : (isset($data['title']) && is_string($data['title']) ? $data['title'] : null),
                    'message' => $message,
                    'app_version' => isset($data['appVersion']) && is_string($data['appVersion']) ? $data['appVersion'] : (isset($data['app_version']) && is_string($data['app_version']) ? $data['app_version'] : null),
                    'device_info' => isset($data['deviceInfo']) && is_string($data['deviceInfo']) ? $data['deviceInfo'] : (isset($data['device_info']) && is_string($data['device_info']) ? $data['device_info'] : null),
                    'status' => Suggestion::STATUS_NEW,
                    'created_at' => isset($data['createdAt']) ? now() : now(),
                ]);

                $importedCount++;
            }

            Log::info("FirestoreSuggestionGateway::syncFromFirestore completed. Imported: {$importedCount}");
        } catch (Throwable $e) {
            Log::warning('FirestoreSuggestionGateway::syncFromFirestore failed: '.$e->getMessage());
        }

        return $importedCount;
    }
}
