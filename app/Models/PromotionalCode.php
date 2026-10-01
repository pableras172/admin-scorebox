<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PromotionalCode extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'assigned_email',
        'assigned_uid',
        'marketing_campaign_id',
        'assigned_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
        ];
    }

    /**
     * Relationship with the marketing campaign that distributed this code.
     *
     * @return BelongsTo<MarketingCampaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaign::class, 'marketing_campaign_id');
    }

    /**
     * Check if this code has been assigned to a user.
     */
    public function isAssigned(): bool
    {
        return $this->assigned_email !== null;
    }

    /**
     * Release this code back to the available inventory.
     */
    public function release(): void
    {
        $this->update([
            'assigned_email' => null,
            'assigned_uid' => null,
            'assigned_at' => null,
            'marketing_campaign_id' => null,
        ]);
    }

    /**
     * Scope for available (unassigned) codes.
     *
     * @param  Builder<PromotionalCode>  $query
     * @return Builder<PromotionalCode>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->whereNull('assigned_email');
    }

    /**
     * Scope for assigned codes.
     *
     * @param  Builder<PromotionalCode>  $query
     * @return Builder<PromotionalCode>
     */
    public function scopeAssigned(Builder $query): Builder
    {
        return $query->whereNotNull('assigned_email');
    }
}
