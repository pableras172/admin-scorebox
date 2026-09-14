<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class MarketingCampaign extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_TEST_SENT = 'test_sent';
    public const STATUS_FAILED = 'failed';

    public const TYPE_STANDARD = 'standard';
    public const TYPE_PROMOTIONAL_CODE = 'promotional_code';

    public const SEGMENT_ALL = 'all';
    public const SEGMENT_FREE = 'free';
    public const SEGMENT_PREMIUM = 'premium';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'subject',
        'content',
        'target_segment',
        'campaign_type',
        'exclude_previous_promo_recipients',
        'is_test',
        'status',
        'recipients_count',
        'sent_count',
        'failed_count',
        'sent_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'campaign_type' => self::TYPE_STANDARD,
        'exclude_previous_promo_recipients' => true,
        'is_test' => true,
        'status' => self::STATUS_DRAFT,
        'target_segment' => self::SEGMENT_ALL,
        'recipients_count' => 0,
        'sent_count' => 0,
        'failed_count' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_test' => 'boolean',
            'exclude_previous_promo_recipients' => 'boolean',
            'recipients_count' => 'integer',
            'sent_count' => 'integer',
            'failed_count' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * Determine if this campaign is a test campaign.
     */
     public function isTest(): bool
     {
         return (bool) $this->is_test;
     }

    /**
     * Determine if this campaign distributes promotional codes.
     */
    public function isPromotionalCode(): bool
    {
        return $this->campaign_type === self::TYPE_PROMOTIONAL_CODE;
    }

    /**
     * Relationship with promotional codes distributed by this campaign.
     *
     * @return HasMany<PromotionalCode, $this>
     */
    public function promotionalCodes(): HasMany
    {
        return $this->hasMany(PromotionalCode::class, 'marketing_campaign_id');
    }

    /**
     * Convert this test campaign into a real production campaign ready to dispatch.
     */
    public function convertToReal(string $targetSegment): void
    {
        $this->update([
            'is_test' => false,
            'target_segment' => $targetSegment,
            'status' => self::STATUS_DRAFT,
            'recipients_count' => 0,
            'sent_count' => 0,
            'failed_count' => 0,
            'sent_at' => null,
        ]);
    }

    /**
     * Scope for draft campaigns.
     *
     * @param Builder<MarketingCampaign> $query
     * @return Builder<MarketingCampaign>
     */
    public function scopeDrafts(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope for sent campaigns.
     *
     * @param Builder<MarketingCampaign> $query
     * @return Builder<MarketingCampaign>
     */
    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Scope for test campaigns.
     *
     * @param Builder<MarketingCampaign> $query
     * @return Builder<MarketingCampaign>
     */
    public function scopeTests(Builder $query): Builder
    {
        return $query->where('is_test', true);
    }

    /**
     * Scope for real (production) campaigns.
     *
     * @param Builder<MarketingCampaign> $query
     * @return Builder<MarketingCampaign>
     */
    public function scopeReal(Builder $query): Builder
    {
        return $query->where('is_test', false);
    }

    /**
     * Returns campaign content rendered to HTML.
     * If content was stored as TipTap JSON, it converts it to HTML.
     */
    public function getHtmlContent(): string
    {
        $content = (string) $this->content;
        if (str_starts_with(trim($content), '{"type":"doc"')) {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                return (new \Tiptap\Editor())->setContent($decoded)->getHTML();
            }
        }

        return $content;
    }
}
