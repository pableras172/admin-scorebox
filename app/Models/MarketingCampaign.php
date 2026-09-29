<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tiptap\Editor;
use Tiptap\Extensions\StarterKit;
use Tiptap\Marks\Link;
use Tiptap\Marks\Underline;
use Tiptap\Nodes\Image;
use Tiptap\Nodes\Table;
use Tiptap\Nodes\TableCell;
use Tiptap\Nodes\TableHeader;
use Tiptap\Nodes\TableRow;

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
     * Interact with the campaign content attribute.
     * Ensures any content (TipTap array document, JSON, or raw HTML) is cleanly converted to an HTML string.
     *
     * @return Attribute<string, mixed>
     */
    protected function content(): Attribute
    {
        return Attribute::make(
            set: fn (mixed $value): string => self::renderTipTapToHtml($value),
        );
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
     * @param  Builder<MarketingCampaign>  $query
     * @return Builder<MarketingCampaign>
     */
    public function scopeDrafts(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope for sent campaigns.
     *
     * @param  Builder<MarketingCampaign>  $query
     * @return Builder<MarketingCampaign>
     */
    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Scope for test campaigns.
     *
     * @param  Builder<MarketingCampaign>  $query
     * @return Builder<MarketingCampaign>
     */
    public function scopeTests(Builder $query): Builder
    {
        return $query->where('is_test', true);
    }

    /**
     * Scope for real (production) campaigns.
     *
     * @param  Builder<MarketingCampaign>  $query
     * @return Builder<MarketingCampaign>
     */
    public function scopeReal(Builder $query): Builder
    {
        return $query->where('is_test', false);
    }

    /**
     * Creates a TipTap editor instance configured with all supported nodes and marks
     * including Images, Links, Underline, and Tables.
     */
    public static function createTipTapEditor(): Editor
    {
        return new Editor([
            'extensions' => [
                new StarterKit,
                new Image,
                new Link,
                new Underline,
                new Table,
                new TableRow,
                new TableCell,
                new TableHeader,
            ],
        ]);
    }

    /**
     * Safely renders TipTap JSON, array document, or HTML content to HTML string.
     * Prevents DOMParser errors on empty or blank input.
     */
    public static function renderTipTapToHtml(mixed $content): string
    {
        if (blank($content)) {
            return '';
        }

        if (is_array($content)) {
            return self::createTipTapEditor()->setContent($content)->getHTML();
        }

        $stringContent = (string) $content;
        $trimmed = trim($stringContent);

        if ($trimmed === '') {
            return '';
        }

        if (str_starts_with($trimmed, '{"type":"doc"')) {
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                return self::createTipTapEditor()->setContent($decoded)->getHTML();
            }
        }

        return $stringContent;
    }

    /**
     * Returns campaign content rendered to HTML.
     * If content was stored as TipTap JSON, it converts it to HTML.
     */
    public function getHtmlContent(): string
    {
        return self::renderTipTapToHtml($this->content);
    }

    /**
     * Resolves an <img> src attribute to an absolute local file path if it points to a local file.
     */
    public static function resolveLocalImagePath(string $src): ?string
    {
        $parsed = parse_url($src, PHP_URL_PATH);
        $path = $parsed ? $parsed : $src;
        $path = ltrim($path, '/');

        // Check if inside public storage (e.g. storage/marketing-campaigns/xxx.gif)
        if (str_starts_with($path, 'storage/')) {
            $storageRelative = substr($path, strlen('storage/'));
            $fullPath = storage_path('app/public/'.$storageRelative);
            if (file_exists($fullPath) && is_file($fullPath)) {
                return $fullPath;
            }
        }

        // Check if inside public/ (e.g. images/headerMail.png)
        $publicFullPath = public_path($path);
        if (file_exists($publicFullPath) && is_file($publicFullPath)) {
            return $publicFullPath;
        }

        return null;
    }

    /**
     * Processes HTML content for email delivery, embedding local images (GIF, JPG, PNG)
     * as CID inline attachments when a mail message is provided, or ensuring absolute URLs.
     */
    public static function processContentForEmail(string $content, mixed $message = null): string
    {
        return (string) preg_replace_callback(
            '/<img\b([^>]*?)\bsrc=["\']([^"\']+)["\']([^>]*?)>/i',
            function (array $matches) use ($message): string {
                $prefix = $matches[1];
                $src = $matches[2];
                $suffix = $matches[3];

                $localFile = self::resolveLocalImagePath($src);

                if ($localFile && file_exists($localFile) && is_object($message) && method_exists($message, 'embed')) {
                    $newSrc = $message->embed($localFile);
                } elseif ($localFile && file_exists($localFile)) {
                    $newSrc = asset(str_replace([public_path().'/', public_path().'\\'], '', $localFile));
                } elseif (! str_starts_with($src, 'http://') && ! str_starts_with($src, 'https://') && ! str_starts_with($src, 'data:') && ! str_starts_with($src, 'cid:')) {
                    $newSrc = url($src);
                } else {
                    $newSrc = $src;
                }

                return "<img{$prefix}src=\"{$newSrc}\"{$suffix}>";
            },
            $content
        );
    }
}
