<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\AuthorFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $country
 * @property string|null $website_url
 * @property string|null $instagram_url
 * @property string|null $youtube_url
 * @property string|null $spotify_url
 * @property array<string, mixed>|null $social_links
 * @property string|null $bio
 * @property string|null $photo_path
 * @property int|null $birth_year
 * @property int|null $death_year
 * @property bool $is_featured
 * @property bool $is_active
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read string|null $photo_url
 */
class Author extends Model
{
    /** @use HasFactory<AuthorFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'country',
        'website_url',
        'instagram_url',
        'youtube_url',
        'spotify_url',
        'social_links',
        'bio',
        'photo_path',
        'birth_year',
        'death_year',
        'is_featured',
        'is_active',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'is_featured' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'birth_year' => 'integer',
            'death_year' => 'integer',
            'social_links' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    /**
     * @param  Builder<Author>  $query
     * @return Builder<Author>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Author>  $query
     * @return Builder<Author>
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * @return HasMany<ScoreDocument, $this>
     */
    public function scoreDocuments(): HasMany
    {
        return $this->hasMany(ScoreDocument::class, 'author_id');
    }

    /**
     * Public URL for the author photo if stored.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (blank($this->photo_path)) {
            return null;
        }

        if (str_starts_with($this->photo_path, 'http://') || str_starts_with($this->photo_path, 'https://')) {
            return $this->photo_path;
        }

        return asset('storage/'.ltrim($this->photo_path, '/'));
    }
}
