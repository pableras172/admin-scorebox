<?php

declare(strict_types=1);

namespace App\Services\Firestore;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class FirestoreAppAnnouncementGateway
{
    public const COLLECTION_NAME = 'announcements';

    public function __construct(
        private readonly FirestoreClientFactory $factory,
    ) {}

    /**
     * Get all announcements from Firestore collection 'announcements'.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAll(): array
    {
        $results = [];

        try {
            $client = $this->factory->make();
            $documents = $client->collection(self::COLLECTION_NAME)->documents();

            foreach ($documents as $doc) {
                if (method_exists($doc, 'exists') && ! $doc->exists()) {
                    continue;
                }

                $data = method_exists($doc, 'data') ? $doc->data() : [];
                if (is_array($data)) {
                    $docId = method_exists($doc, 'id') ? $doc->id() : (string) ($data['id'] ?? '');
                    $type = (string) ($data['type'] ?? 'info');
                    $hideForPro = (bool) ($data['hide_for_pro'] ?? $data['hideForPro'] ?? ($type === 'ad'));

                    $results[$docId] = [
                        'id' => $docId,
                        'enabled' => (bool) ($data['enabled'] ?? false),
                        'title' => (string) ($data['title'] ?? ''),
                        'message' => (string) ($data['message'] ?? ''),
                        'type' => $type,
                        'action_text' => (string) ($data['action_text'] ?? $data['actionText'] ?? ''),
                        'action_url' => (string) ($data['action_url'] ?? $data['actionUrl'] ?? ''),
                        'image_url' => (string) ($data['image_url'] ?? $data['imageUrl'] ?? ''),
                        'hide_for_pro' => $hideForPro,
                        'updated_at' => (string) ($data['updated_at'] ?? ''),
                    ];
                }
            }
        } catch (Throwable $e) {
            Log::warning('FirestoreAppAnnouncementGateway::getAll failed: '.$e->getMessage());
        }

        return $results;
    }

    /**
     * Get a specific announcement by document ID, or the first available one if omitted.
     *
     * @return array<string, mixed>
     */
    public function get(?string $documentId = null): array
    {
        $default = [
            'id' => $documentId ?? '',
            'enabled' => false,
            'title' => '',
            'message' => '',
            'type' => 'info',
            'action_text' => '',
            'action_url' => '',
            'image_url' => '',
            'hide_for_pro' => false,
            'updated_at' => null,
        ];

        try {
            $client = $this->factory->make();

            if (! empty($documentId)) {
                $snapshot = $client->collection(self::COLLECTION_NAME)->document($documentId)->snapshot();
                if ($snapshot !== null && method_exists($snapshot, 'data')) {
                    $data = $snapshot->data();
                    if (is_array($data)) {
                        $type = (string) ($data['type'] ?? 'info');
                        $hideForPro = (bool) ($data['hide_for_pro'] ?? $data['hideForPro'] ?? ($type === 'ad'));

                        return [
                            'id' => $documentId,
                            'enabled' => (bool) ($data['enabled'] ?? false),
                            'title' => (string) ($data['title'] ?? ''),
                            'message' => (string) ($data['message'] ?? ''),
                            'type' => $type,
                            'action_text' => (string) ($data['action_text'] ?? $data['actionText'] ?? ''),
                            'action_url' => (string) ($data['action_url'] ?? $data['actionUrl'] ?? ''),
                            'image_url' => (string) ($data['image_url'] ?? $data['imageUrl'] ?? ''),
                            'hide_for_pro' => $hideForPro,
                            'updated_at' => (string) ($data['updated_at'] ?? ''),
                        ];
                    }
                }

                return $default;
            }

            // If no documentId, retrieve all and pick the first enabled, or first item
            $all = $this->getAll();
            foreach ($all as $item) {
                if ($item['enabled']) {
                    return $item;
                }
            }

            if (! empty($all)) {
                return reset($all);
            }

            // Fallback for mocks or single-document access
            $fallback = $client->collection(self::COLLECTION_NAME)->document('announcement')->snapshot();
            if ($fallback !== null && method_exists($fallback, 'data')) {
                $data = $fallback->data();
                if (is_array($data) && ! empty($data)) {
                    $type = (string) ($data['type'] ?? 'info');
                    $hideForPro = (bool) ($data['hide_for_pro'] ?? $data['hideForPro'] ?? ($type === 'ad'));

                    return [
                        'id' => 'announcement',
                        'enabled' => (bool) ($data['enabled'] ?? false),
                        'title' => (string) ($data['title'] ?? ''),
                        'message' => (string) ($data['message'] ?? ''),
                        'type' => $type,
                        'action_text' => (string) ($data['action_text'] ?? $data['actionText'] ?? ''),
                        'action_url' => (string) ($data['action_url'] ?? $data['actionUrl'] ?? ''),
                        'image_url' => (string) ($data['image_url'] ?? $data['imageUrl'] ?? ''),
                        'hide_for_pro' => $hideForPro,
                        'updated_at' => (string) ($data['updated_at'] ?? ''),
                    ];
                }
            }
        } catch (Throwable $e) {
            Log::warning('FirestoreAppAnnouncementGateway::get failed: '.$e->getMessage());
        }

        return $default;
    }

    /**
     * Save announcement document to Firestore collection 'announcements'.
     *
     * @param  array<string, mixed>  $data
     */
    public function save(array $data, ?string $documentId = null): FirestoreResult
    {
        try {
            $docId = $documentId ?? (string) ($data['firestore_id'] ?? ($data['id'] ?? ''));
            if ($docId === '') {
                $docId = Str::slug((string) ($data['title'] ?? 'anuncio'), '_') ?: 'anuncio_'.time();
            }

            $type = (string) ($data['type'] ?? 'info');
            $hideForPro = (bool) ($data['hide_for_pro'] ?? ($type === 'ad'));

            $payload = [
                'action_text' => (string) ($data['action_text'] ?? ''),
                'action_url' => (string) ($data['action_url'] ?? ''),
                'enabled' => (bool) ($data['enabled'] ?? ($data['is_active'] ?? false)),
                'hide_for_pro' => $hideForPro,
                'image_url' => (string) ($data['image_url'] ?? ''),
                'message' => (string) ($data['message'] ?? ''),
                'title' => (string) ($data['title'] ?? ''),
                'type' => $type,
                'updated_at' => now()->toIso8601String(),
            ];

            $client = $this->factory->make();
            $client
                ->collection(self::COLLECTION_NAME)
                ->document($docId)
                ->set($payload);

            Log::info("App announcement saved to Firestore announcements/{$docId}", $payload);

            return FirestoreResult::success($payload, [
                'collection' => self::COLLECTION_NAME,
                'document' => $docId,
            ]);
        } catch (Throwable $e) {
            Log::error('FirestoreAppAnnouncementGateway::save failed: '.$e->getMessage());

            return FirestoreResult::failure('FIRESTORE_ERROR', $e->getMessage());
        }
    }

    /**
     * Delete an announcement from Firestore.
     */
    public function delete(string $documentId): FirestoreResult
    {
        try {
            $client = $this->factory->make();
            $client->collection(self::COLLECTION_NAME)->document($documentId)->delete();

            return FirestoreResult::success([], [
                'collection' => self::COLLECTION_NAME,
                'document' => $documentId,
            ]);
        } catch (Throwable $e) {
            Log::error("FirestoreAppAnnouncementGateway::delete failed for {$documentId}: ".$e->getMessage());

            return FirestoreResult::failure('FIRESTORE_ERROR', $e->getMessage());
        }
    }
}
