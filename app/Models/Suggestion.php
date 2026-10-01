<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Suggestion extends Model
{
    use HasFactory;

    public const STATUS_NEW = 'new';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_PLANNED = 'planned';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_DISMISSED = 'dismissed';

    public const TYPE_IDEA = 'idea';

    public const TYPE_BUG = 'bug';

    public const TYPE_SCORES_REQUEST = 'scores_request';

    public const TYPE_USABILITY = 'usability';

    public const TYPE_OTHER = 'other';

    public const SOURCE_APP = 'app';

    public const SOURCE_WEB = 'web';

    public const SOURCE_ADMIN = 'admin';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'source',
        'uid',
        'email',
        'name',
        'is_premium',
        'type',
        'subject',
        'message',
        'app_version',
        'device_info',
        'status',
        'admin_notes',
        'firestore_id',
        'replied_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_premium' => 'boolean',
            'replied_at' => 'datetime',
        ];
    }

    public function isNew(): bool
    {
        return $this->status === self::STATUS_NEW;
    }

    public function isReplied(): bool
    {
        return $this->replied_at !== null;
    }

    /**
     * Scope for unread/new suggestions.
     *
     * @param  Builder<Suggestion>  $query
     * @return Builder<Suggestion>
     */
    public function scopeNew(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_NEW);
    }

    /**
     * Scope for pending (non-resolved) suggestions.
     *
     * @param  Builder<Suggestion>  $query
     * @return Builder<Suggestion>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_NEW, self::STATUS_IN_REVIEW, self::STATUS_PLANNED]);
    }
}
