<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Firestore\FirestoreAppAnnouncementGateway;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class AppAnnouncement extends Model
{
    use HasFactory;

    public const TYPE_INFO = 'info';

    public const TYPE_WARNING = 'warning';

    public const TYPE_SUCCESS = 'success';

    public const TYPE_PROMO = 'promo';

    public const TYPE_AD = 'ad';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'firestore_id',
        'title',
        'message',
        'type',
        'image_url',
        'action_text',
        'action_url',
        'hide_for_pro',
        'is_active',
        'synced_to_firestore_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hide_for_pro' => 'boolean',
            'is_active' => 'boolean',
            'synced_to_firestore_at' => 'datetime',
        ];
    }

    /**
     * Scope a query to only include active announcements.
     *
     * @param  Builder<AppAnnouncement>  $query
     * @return Builder<AppAnnouncement>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get or generate a clean Firestore document ID (slug).
     */
    public function getOrGenerateFirestoreId(): string
    {
        if (! empty($this->firestore_id)) {
            return $this->firestore_id;
        }

        $slug = Str::slug($this->title ?: 'anuncio', '_') ?: 'anuncio_'.time();
        $this->firestore_id = $slug;

        return $slug;
    }

    /**
     * Save/sync this announcement to Firestore announcements collection.
     */
    public function syncToFirestore(): bool
    {
        $docId = $this->getOrGenerateFirestoreId();

        /** @var FirestoreAppAnnouncementGateway $gateway */
        $gateway = app(FirestoreAppAnnouncementGateway::class);
        $result = $gateway->save([
            'firestore_id' => $docId,
            'enabled' => $this->is_active,
            'title' => $this->title,
            'message' => $this->message ?? '',
            'type' => $this->type,
            'image_url' => $this->image_url ?? '',
            'action_text' => $this->action_text ?? '',
            'action_url' => $this->action_url ?? '',
            'hide_for_pro' => $this->hide_for_pro,
        ], $docId);

        if ($result->isSuccess()) {
            $this->updateQuietly([
                'firestore_id' => $docId,
                'synced_to_firestore_at' => now(),
            ]);

            return true;
        }

        return false;
    }

    /**
     * Activate this announcement and publish it directly to Firestore.
     */
    public function activateAndSync(): bool
    {
        $this->is_active = true;
        $this->save();

        return $this->syncToFirestore();
    }

    /**
     * Deactivate this announcement and disable it in Firestore.
     */
    public function deactivateAndSync(): bool
    {
        $this->is_active = false;
        $this->save();

        return $this->syncToFirestore();
    }

    /**
     * Delete from Firestore document.
     */
    public function deleteFromFirestore(): bool
    {
        if (empty($this->firestore_id)) {
            return true;
        }

        /** @var FirestoreAppAnnouncementGateway $gateway */
        $gateway = app(FirestoreAppAnnouncementGateway::class);
        $result = $gateway->delete($this->firestore_id);

        return $result->isSuccess();
    }

    /**
     * Deactivate all announcements in MySQL and Firestore.
     */
    public static function deactivateAllAndSync(): bool
    {
        $actives = self::where('is_active', true)->get();
        foreach ($actives as $active) {
            $active->deactivateAndSync();
        }

        return true;
    }

    /**
     * Import/sync all announcements from Firestore collection 'announcements' into MySQL.
     *
     * @return int Count of imported/updated records
     */
    public static function importAllFromFirestore(): int
    {
        /** @var FirestoreAppAnnouncementGateway $gateway */
        $gateway = app(FirestoreAppAnnouncementGateway::class);
        $all = $gateway->getAll();

        if (empty($all)) {
            return 0;
        }

        $count = 0;

        foreach ($all as $docId => $data) {
            $title = trim((string) ($data['title'] ?? '')) ?: $docId;

            /** @var AppAnnouncement $record */
            $record = self::where('firestore_id', $docId)->first()
                ?? self::where('title', $title)->first()
                ?? new self;

            $record->firestore_id = $docId;
            $record->title = $title;
            $record->message = (string) ($data['message'] ?? '');
            $record->type = (string) ($data['type'] ?? self::TYPE_INFO);
            $record->image_url = (string) ($data['image_url'] ?? '');
            $record->action_text = (string) ($data['action_text'] ?? '');
            $record->action_url = (string) ($data['action_url'] ?? '');
            $record->hide_for_pro = (bool) ($data['hide_for_pro'] ?? false);
            $record->is_active = (bool) ($data['enabled'] ?? false);
            $record->synced_to_firestore_at = now();
            $record->save();

            $count++;
        }

        return $count;
    }

    /**
     * Import from Firestore and return the active or first record.
     */
    public static function importFromFirestore(): ?self
    {
        self::importAllFromFirestore();

        return self::active()->first() ?? self::first();
    }
}
