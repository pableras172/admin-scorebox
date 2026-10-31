<?php

declare(strict_types=1);

namespace App\Services\Firestore;

use App\Models\MusicianReport;
use Carbon\Carbon;
use Google\Cloud\Core\Timestamp;
use Illuminate\Support\Facades\Log;
use Throwable;

class FirestoreMusicianReportGateway
{
    private const COLLECTION_REPORTS = 'reports_musicians';

    private const COLLECTION_MUSICIANS = 'musicians_directory';

    public function __construct(
        private readonly FirestoreClientFactory $factory,
    ) {}

    /**
     * Synchronize reports stored directly in Firestore into the local MySQL database.
     * Avoids duplicates by tracking firestore_id.
     *
     * @return int Number of newly imported reports.
     */
    public function syncFromFirestore(): int
    {
        $importedCount = 0;

        try {
            $client = $this->factory->make();
            $collection = $client->collection(self::COLLECTION_REPORTS);

            /** @var iterable<object> $documents */
            $documents = $collection->documents();

            foreach ($documents as $document) {
                if ($document === null || ! method_exists($document, 'data') || ! method_exists($document, 'id')) {
                    continue;
                }

                $docId = (string) $document->id();
                $data = (array) $document->data();

                if (MusicianReport::where('firestore_id', $docId)->exists()) {
                    continue;
                }

                $reportedUserId = isset($data['reportedUserId']) && is_string($data['reportedUserId']) ? trim($data['reportedUserId']) : '';
                $reason = isset($data['reason']) && is_string($data['reason']) ? trim($data['reason']) : '';

                if ($reportedUserId === '' && $reason === '') {
                    continue;
                }

                $reportedAt = null;
                if (isset($data['timestamp'])) {
                    if ($data['timestamp'] instanceof Timestamp) {
                        $reportedAt = Carbon::createFromTimestamp($data['timestamp']->get()->getTimestamp());
                    } elseif (is_numeric($data['timestamp'])) {
                        $val = (float) $data['timestamp'];
                        $reportedAt = $val > 10000000000
                            ? Carbon::createFromTimestampMs((int) round($val))
                            : Carbon::createFromTimestamp((int) round($val));
                    }
                }

                MusicianReport::create([
                    'firestore_id' => $docId,
                    'reported_user_id' => $reportedUserId,
                    'reporter_user_id' => isset($data['reporterUserId']) && is_string($data['reporterUserId']) ? trim($data['reporterUserId']) : null,
                    'reason' => $reason !== '' ? $reason : 'Sin motivo especificado',
                    'details' => isset($data['details']) && is_string($data['details']) ? trim($data['details']) : null,
                    'status' => MusicianReport::STATUS_PENDING,
                    'reported_at' => $reportedAt ?? now(),
                ]);

                $importedCount++;
            }

            Log::info("FirestoreMusicianReportGateway::syncFromFirestore completed. Imported: {$importedCount}");
        } catch (Throwable $e) {
            Log::warning('FirestoreMusicianReportGateway::syncFromFirestore failed: '.$e->getMessage());
        }

        return $importedCount;
    }

    /**
     * Moderation action: Hide reported musician from public directory in Firestore.
     */
    public function hideReportedMusician(string $reportedUserId): bool
    {
        try {
            $client = $this->factory->make();
            $client->collection(self::COLLECTION_MUSICIANS)
                ->document($reportedUserId)
                ->set([
                    'isVisible' => false,
                    'updatedAt' => (int) round(microtime(true) * 1000),
                ], ['merge' => true]);

            Log::info("Musician profile [{$reportedUserId}] hidden by moderation.");

            return true;
        } catch (Throwable $e) {
            Log::error("Failed to hide musician [{$reportedUserId}] in Firestore: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Moderation action: Restore musician visibility in public directory in Firestore.
     */
    public function restoreReportedMusician(string $reportedUserId): bool
    {
        try {
            $client = $this->factory->make();
            $client->collection(self::COLLECTION_MUSICIANS)
                ->document($reportedUserId)
                ->set([
                    'isVisible' => true,
                    'updatedAt' => (int) round(microtime(true) * 1000),
                ], ['merge' => true]);

            Log::info("Musician profile [{$reportedUserId}] restored by moderation.");

            return true;
        } catch (Throwable $e) {
            Log::error("Failed to restore musician [{$reportedUserId}] in Firestore: ".$e->getMessage());

            return false;
        }
    }
}
