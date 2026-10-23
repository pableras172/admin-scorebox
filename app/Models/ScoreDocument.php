<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ScoreDocumentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string|null $composer
 * @property string|null $instrument
 * @property string|null $genre
 * @property string|null $difficulty
 * @property string|null $description
 * @property string $file_path
 * @property string $file_name
 * @property int $file_size
 * @property string $mime_type
 * @property bool $is_active
 * @property bool $is_premium_only
 * @property int $downloads_count
 * @property CarbonImmutable|Carbon|null $created_at
 * @property CarbonImmutable|Carbon|null $updated_at
 * @property-read string $formatted_file_size
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
        'difficulty',
        'description',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'is_active',
        'is_premium_only',
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
            'file_size' => 'integer',
            'downloads_count' => 'integer',
        ];
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
