<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\BandType;
use App\Models\MusicGenre;
use App\Models\ScoreDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScoreDocumentApiController extends Controller
{
    /**
     * List all published score documents with optional search and filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ScoreDocument::query()->published()->with(['author', 'musicGenre', 'bandType']);

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(function (Builder $q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('composer', 'like', "%{$search}%")
                    ->orWhere('instrument', 'like', "%{$search}%")
                    ->orWhere('genre', 'like', "%{$search}%")
                    ->orWhereHas('author', fn (Builder $a): Builder => $a->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('musicGenre', fn (Builder $g): Builder => $g->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('bandType', fn (Builder $b): Builder => $b->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('sku')) {
            $query->where('sku', trim((string) $request->query('sku')));
        }

        if ($request->filled('author_id')) {
            $query->where('author_id', (int) $request->query('author_id'));
        } elseif ($request->filled('author')) {
            $author = trim((string) $request->query('author'));
            $query->where(function (Builder $q) use ($author): void {
                $q->where('composer', 'like', "%{$author}%")
                    ->orWhereHas('author', fn (Builder $a): Builder => $a->where('slug', $author)->orWhere('name', 'like', "%{$author}%"));
            });
        }

        if ($request->filled('music_genre_id')) {
            $query->where('music_genre_id', (int) $request->query('music_genre_id'));
        } elseif ($request->filled('genre')) {
            $genre = trim((string) $request->query('genre'));
            $query->where(function (Builder $q) use ($genre): void {
                $q->where('genre', 'like', "%{$genre}%")
                    ->orWhereHas('musicGenre', fn (Builder $g): Builder => $g->where('slug', $genre)->orWhere('name', 'like', "%{$genre}%"));
            });
        }

        if ($request->filled('band_type_id')) {
            $query->where('band_type_id', (int) $request->query('band_type_id'));
        } elseif ($request->filled('band_type')) {
            $bandType = trim((string) $request->query('band_type'));
            $query->whereHas('bandType', fn (Builder $b): Builder => $b->where('slug', $bandType)->orWhere('name', 'like', "%{$bandType}%"));
        }

        if ($request->filled('instrument')) {
            $instrument = trim((string) $request->query('instrument'));
            $query->where('instrument', 'like', "%{$instrument}%");
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->query('difficulty'));
        }

        if ($request->has('is_premium_only')) {
            $isPremium = filter_var($request->query('is_premium_only'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($isPremium !== null) {
                $query->where('is_premium_only', $isPremium);
            }
        }

        $sortField = match ($request->query('sort_by')) {
            'title' => 'title',
            'composer' => 'composer',
            'downloads_count' => 'downloads_count',
            'file_size' => 'file_size',
            'price' => 'price',
            'published_at' => 'published_at',
            default => 'id',
        };

        $sortOrder = strtolower((string) $request->query('order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortField, $sortOrder);

        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);
        $paginator = $query->paginate($perPage);

        $items = collect($paginator->items())->map(fn (ScoreDocument $score): array => $this->transformScoreDocument($score));

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Get details of a single score document.
     */
    public function show(int $id): JsonResponse
    {
        $score = ScoreDocument::query()->published()->with(['author', 'musicGenre', 'bandType'])->find($id);

        if (! $score) {
            return response()->json([
                'success' => false,
                'message' => 'Documento o partitura no encontrado.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformScoreDocument($score),
        ]);
    }

    /**
     * Download the score document binary file.
     */
    public function download(Request $request, int $id): StreamedResponse|JsonResponse
    {
        $score = ScoreDocument::query()->published()->find($id);

        if (! $score) {
            return response()->json([
                'success' => false,
                'message' => 'Documento o partitura no encontrado o no disponible.',
            ], Response::HTTP_NOT_FOUND);
        }

        // If the document is restricted to PRO users, inspect header or query flag if provided
        if ($score->is_premium_only) {
            $isUserPremium = $request->header('X-User-Is-Premium')
                ?? $request->query('user_is_premium');

            if ($isUserPremium !== null && ! filter_var($isUserPremium, FILTER_VALIDATE_BOOLEAN)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este documento es exclusivo para usuarios con suscripción PRO.',
                ], Response::HTTP_FORBIDDEN);
            }
        }

        if (! Storage::disk('local')->exists($score->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'El archivo no se encuentra disponible en el almacenamiento del servidor.',
            ], Response::HTTP_NOT_FOUND);
        }

        $score->incrementDownloads();

        return Storage::disk('local')->download(
            $score->file_path,
            $score->file_name,
            [
                'Content-Type' => $score->mime_type ?: 'application/pdf',
                'Cache-Control' => 'no-cache, must-revalidate',
            ]
        );
    }

    /**
     * List all active music genres for filtering in client apps.
     */
    public function genres(): JsonResponse
    {
        $genres = MusicGenre::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description']);

        return response()->json([
            'success' => true,
            'data' => $genres,
        ]);
    }

    /**
     * List all active band types / formations for filtering in client apps.
     */
    public function bandTypes(): JsonResponse
    {
        $types = BandType::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description']);

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }

    /**
     * List all active authors/composers for client apps.
     */
    public function authors(Request $request): JsonResponse
    {
        $query = Author::query()->active()->withCount([
            'scoreDocuments' => fn ($q) => $q->published(),
        ]);

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%");
            });
        }

        if ($request->filled('country')) {
            $query->where('country', trim((string) $request->query('country')));
        }

        if ($request->has('featured')) {
            $isFeatured = filter_var($request->query('featured'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($isFeatured !== null) {
                $query->where('is_featured', $isFeatured);
            }
        }

        $query->orderBy('name');

        $perPage = min(max((int) $request->query('per_page', 30), 1), 100);
        $paginator = $query->paginate($perPage);

        $items = collect($paginator->items())->map(fn (Author $author): array => $this->transformAuthor($author));

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Get details of a single author/composer and their published scores.
     */
    public function authorDetail(int $id): JsonResponse
    {
        $author = Author::query()->active()->with([
            'scoreDocuments' => fn ($q) => $q->published()->with(['musicGenre', 'bandType']),
        ])->find($id);

        if (! $author) {
            return response()->json([
                'success' => false,
                'message' => 'Autor o compositor no encontrado o no disponible.',
            ], Response::HTTP_NOT_FOUND);
        }

        $data = $this->transformAuthor($author);
        $data['scores'] = $author->scoreDocuments->map(fn (ScoreDocument $score): array => $this->transformScoreDocument($score));

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformAuthor(Author $author): array
    {
        return [
            'id' => $author->id,
            'name' => $author->name,
            'slug' => $author->slug,
            'country' => $author->country,
            'photo_url' => $author->photo_url,
            'birth_year' => $author->birth_year,
            'death_year' => $author->death_year,
            'website_url' => $author->website_url,
            'spotify_url' => $author->spotify_url,
            'youtube_url' => $author->youtube_url,
            'instagram_url' => $author->instagram_url,
            'social_links' => $author->social_links,
            'bio' => $author->bio,
            'is_featured' => $author->is_featured,
            'scores_count' => $author->score_documents_count ?? $author->scoreDocuments()->published()->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformScoreDocument(ScoreDocument $score): array
    {
        return [
            'id' => $score->id,
            'title' => $score->title,
            'sku' => $score->sku,
            'author_id' => $score->author_id,
            'composer' => $score->author?->name ?? $score->composer,
            'author' => $score->author ? [
                'id' => $score->author->id,
                'name' => $score->author->name,
                'slug' => $score->author->slug,
                'country' => $score->author->country,
                'photo_url' => $score->author->photo_url,
                'birth_year' => $score->author->birth_year,
                'death_year' => $score->author->death_year,
                'website_url' => $score->author->website_url,
                'spotify_url' => $score->author->spotify_url,
                'youtube_url' => $score->author->youtube_url,
                'instagram_url' => $score->author->instagram_url,
                'is_featured' => $score->author->is_featured,
            ] : null,
            'instrument' => $score->instrument,
            'genre' => $score->musicGenre?->name ?? $score->genre,
            'music_genre' => $score->musicGenre ? [
                'id' => $score->musicGenre->id,
                'name' => $score->musicGenre->name,
                'slug' => $score->musicGenre->slug,
            ] : null,
            'band_type' => $score->bandType ? [
                'id' => $score->bandType->id,
                'name' => $score->bandType->name,
                'slug' => $score->bandType->slug,
            ] : null,
            'difficulty' => $score->difficulty,
            'description' => $score->description,
            'price' => $score->price,
            'file_name' => $score->file_name,
            'file_size' => $score->file_size,
            'formatted_file_size' => $score->formatted_file_size,
            'mime_type' => $score->mime_type,
            'is_premium_only' => $score->is_premium_only,
            'published_at' => $score->published_at?->toIso8601String(),
            'publication_end_at' => $score->publication_end_at?->toIso8601String(),
            'downloads_count' => $score->downloads_count,
            'download_url' => route('api.scores.download', ['id' => $score->id]),
            'created_at' => $score->created_at?->toIso8601String(),
            'updated_at' => $score->updated_at?->toIso8601String(),
        ];
    }
}
