<?php

declare(strict_types=1);

namespace App\Services\Firestore;

use Illuminate\Support\Facades\Log;
use Throwable;

class FirestoreAppAnnouncementGateway
{
    private const COLLECTION_NAME = 'app_config';

    private const DOCUMENT_NAME = 'announcement';

    public function __construct(
        private readonly FirestoreClientFactory $factory,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function get(): array
    {
        $default = [
            'enabled' => false,
            'title' => '',
            'message' => '',
            'type' => 'info',
            'action_text' => '',
            'action_url' => '',
            'updated_at' => null,
        ];

        try {
            $client = $this->factory->make();
            $snapshot = $client
                ->collection(self::COLLECTION_NAME)
                ->document(self::DOCUMENT_NAME)
                ->snapshot();

            if ($snapshot !== null && method_exists($snapshot, 'data')) {
                $data = $snapshot->data();
                if (is_array($data)) {
                    return [
                        'enabled' => (bool) ($data['enabled'] ?? false),
                        'title' => (string) ($data['title'] ?? ''),
                        'message' => (string) ($data['message'] ?? ''),
                        'type' => (string) ($data['type'] ?? 'info'),
                        'action_text' => (string) ($data['action_text'] ?? $data['actionText'] ?? ''),
                        'action_url' => (string) ($data['action_url'] ?? $data['actionUrl'] ?? ''),
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
     * @param  array<string, mixed>  $data
     */
    public function save(array $data): FirestoreResult
    {
        try {
            $payload = [
                'enabled' => (bool) ($data['enabled'] ?? false),
                'title' => (string) ($data['title'] ?? ''),
                'message' => (string) ($data['message'] ?? ''),
                'type' => (string) ($data['type'] ?? 'info'),
                'action_text' => (string) ($data['action_text'] ?? ''),
                'action_url' => (string) ($data['action_url'] ?? ''),
                'updated_at' => now()->toIso8601String(),
            ];

            $client = $this->factory->make();
            $client
                ->collection(self::COLLECTION_NAME)
                ->document(self::DOCUMENT_NAME)
                ->set($payload);

            Log::info('App announcement saved to Firestore.', $payload);

            return FirestoreResult::success($payload, [
                'collection' => self::COLLECTION_NAME,
                'document' => self::DOCUMENT_NAME,
            ]);
        } catch (Throwable $e) {
            Log::error('FirestoreAppAnnouncementGateway::save failed: '.$e->getMessage());

            return FirestoreResult::failure('FIRESTORE_ERROR', $e->getMessage());
        }
    }
}
