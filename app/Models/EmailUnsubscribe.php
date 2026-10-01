<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EmailUnsubscribe extends Model
{
    use HasFactory;

    public const SOURCE_LINK = 'link';

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_HEADER = 'header';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'reason',
        'source',
        'unsubscribed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unsubscribed_at' => 'datetime',
        ];
    }

    /**
     * Determine if an email is in the suppression list.
     */
    public static function isUnsubscribed(string $email): bool
    {
        return self::where('email', strtolower(trim($email)))->exists();
    }

    /**
     * Idempotently unsubscribe an email address.
     */
    public static function unsubscribe(string $email, string $source = self::SOURCE_LINK, ?string $reason = null): self
    {
        return self::firstOrCreate(
            ['email' => strtolower(trim($email))],
            [
                'reason' => $reason,
                'source' => $source,
                'unsubscribed_at' => now(),
            ]
        );
    }

    /**
     * Resubscribe an email address by removing it from the suppression list.
     */
    public static function resubscribe(string $email): bool
    {
        return (bool) self::where('email', strtolower(trim($email)))->delete();
    }
}
