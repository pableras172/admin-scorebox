<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Firestore\FirestoreAppAnnouncementGateway;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
     * Scope a query to only include the active announcement.
     *
     * @param  Builder<AppAnnouncement>  $query
     * @return Builder<AppAnnouncement>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Activate this announcement and publish it directly to Firestore.
     */
    public function activateAndSync(): bool
    {
        self::where('id', '!=', $this->id)->where('is_active', true)->update(['is_active' => false]);

        $this->update([
            'is_active' => true,
            'synced_to_firestore_at' => now(),
        ]);

        /** @var FirestoreAppAnnouncementGateway $gateway */
        $gateway = app(FirestoreAppAnnouncementGateway::class);
        $result = $gateway->save([
            'enabled' => true,
            'title' => $this->title,
            'message' => $this->message ?? '',
            'type' => $this->type,
            'image_url' => $this->image_url ?? '',
            'action_text' => $this->action_text ?? '',
            'action_url' => $this->action_url ?? '',
            'hide_for_pro' => $this->hide_for_pro,
        ]);

        return $result->isSuccess();
    }

    /**
     * Deactivate this announcement and disable the banner in Firestore.
     */
    public function deactivateAndSync(): bool
    {
        $this->update([
            'is_active' => false,
        ]);

        /** @var FirestoreAppAnnouncementGateway $gateway */
        $gateway = app(FirestoreAppAnnouncementGateway::class);
        $result = $gateway->save([
            'enabled' => false,
            'title' => $this->title,
            'message' => $this->message ?? '',
            'type' => $this->type,
            'image_url' => $this->image_url ?? '',
            'action_text' => $this->action_text ?? '',
            'action_url' => $this->action_url ?? '',
            'hide_for_pro' => $this->hide_for_pro,
        ]);

        return $result->isSuccess();
    }

    /**
     * Deactivate all announcements and turn off the banner in Firestore.
     */
    public static function deactivateAllAndSync(): bool
    {
        self::where('is_active', true)->update(['is_active' => false]);

        /** @var FirestoreAppAnnouncementGateway $gateway */
        $gateway = app(FirestoreAppAnnouncementGateway::class);
        $current = $gateway->get();
        $current['enabled'] = false;
        $result = $gateway->save($current);

        return $result->isSuccess();
    }

    /**
     * Import current live banner from Cloud Firestore into MySQL.
     */
    public static function importFromFirestore(): ?self
    {
        /** @var FirestoreAppAnnouncementGateway $gateway */
        $gateway = app(FirestoreAppAnnouncementGateway::class);
        $data = $gateway->get();

        $title = trim((string) ($data['title'] ?? ''));
        $message = trim((string) ($data['message'] ?? ''));

        if ($title === '' && $message === '') {
            return null;
        }

        $enabled = (bool) ($data['enabled'] ?? false);

        /** @var AppAnnouncement $announcement */
        $announcement = self::where('title', $title)->first() ?? new self;
        $announcement->title = $title ?: 'Aviso recuperado de Firestore';
        $announcement->message = $message;
        $announcement->type = (string) ($data['type'] ?? self::TYPE_INFO);
        $announcement->image_url = (string) ($data['image_url'] ?? '');
        $announcement->action_text = (string) ($data['action_text'] ?? '');
        $announcement->action_url = (string) ($data['action_url'] ?? '');
        $announcement->hide_for_pro = (bool) ($data['hide_for_pro'] ?? false);
        $announcement->is_active = $enabled;
        $announcement->synced_to_firestore_at = now();
        $announcement->save();

        if ($enabled) {
            self::where('id', '!=', $announcement->id)->where('is_active', true)->update(['is_active' => false]);
        }

        return $announcement;
    }
}
