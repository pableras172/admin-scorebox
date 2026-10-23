<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\ScoreDocumentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $title
 * @property string|null $composer
 * @property string|null $instrument
 * @property string|null $genre
 * @property int|null $music_genre_id
 * @property int|null $band_type_id
 * @property string|null $difficulty
 * @property string|null $description
 * @property string $file_path
 * @property string $file_name
 * @property int $file_size
 * @property string $mime_type
 * @property bool $is_active
 * @property bool $is_premium_only
 * @property string|null $price
 * @property CarbonInterface|null $published_at
 * @property CarbonInterface|null $publication_end_at
 * @property int $downloads_count
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read string $formatted_file_size
 * @property-read MusicGenre|null $musicGenre
 * @property-read BandType|null $bandType
 */
class ScoreDocument extends Model
{
    /** @use HasFactory<ScoreDocumentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'composer',
        'instrument',
        'genre',
        'music_genre_id',
        'band_type_id',
        'difficulty',
        'description',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'is_active',
        'is_premium_only',
        'price',
        'published_at',
        'publication_end_at',
        'downloads_count',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'is_premium_only' => false,
        'downloads_count' => 0,
        'file_size' => 0,
        'mime_type' => 'application/pdf',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_premium_only' => 'boolean',
            'price' => 'decimal:2',
            'published_at' => 'datetime',
            'publication_end_at' => 'datetime',
            'music_genre_id' => 'integer',
            'band_type_id' => 'integer',
            'file_size' => 'integer',
            'downloads_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<MusicGenre, $this>
     */
    public function musicGenre(): BelongsTo
    {
        return $this->belongsTo(MusicGenre::class, 'music_genre_id');
    }

    /**
     * @return BelongsTo<BandType, $this>
     */
    public function bandType(): BelongsTo
    {
        return $this->belongsTo(BandType::class, 'band_type_id');
    }

    /**
     * @param  Builder<ScoreDocument>  $query
     * @return Builder<ScoreDocument>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to only include currently published scores according to publication dates.
     *
     * @param  Builder<ScoreDocument>  $query
     * @return Builder<ScoreDocument>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $q): void {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->where(function (Builder $q): void {
                $q->whereNull('publication_end_at')
                    ->orWhere('publication_end_at', '>=', now());
            });
    }

    /**
     * @param  Builder<ScoreDocument>  $query
     * @return Builder<ScoreDocument>
     */
    public function scopeFree(Builder $query): Builder
    {
        return $query->where('is_premium_only', false);
    }

    /**
     * @param  Builder<ScoreDocument>  $query
     * @return Builder<ScoreDocument>
     */
    public function scopePremiumOnly(Builder $query): Builder
    {
        return $query->where('is_premium_only', true);
    }

    /**
     * Increment the download counter atomically.
     */
    public function incrementDownloads(): void
    {
        $this->increment('downloads_count');
    }

    /**
     * Get human-readable file size.
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $power), 1).' '.$units[$power];
    }
}
