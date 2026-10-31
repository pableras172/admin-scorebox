<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MusicianReport extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_REVIEWED = 'reviewed';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_DISMISSED = 'dismissed';

    public const ACTION_HIDDEN = 'hidden_profile';

    public const ACTION_RESTORED = 'restored_profile';

    public const ACTION_DISMISSED = 'dismissed';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'firestore_id',
        'reported_user_id',
        'reporter_user_id',
        'reason',
        'details',
        'status',
        'reported_at',
        'admin_notes',
        'action_taken',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<MusicianReport>  $query
     * @return Builder<MusicianReport>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * @param  Builder<MusicianReport>  $query
     * @return Builder<MusicianReport>
     */
    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }
}
