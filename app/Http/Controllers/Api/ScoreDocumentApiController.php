<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        $query = ScoreDocument::query()->active();

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(function (Builder $q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('composer', 'like', "%{$search}%")
                    ->orWhere('instrument', 'like', "%{$search}%")
                    ->orWhere('genre', 'like', "%{$search}%");
            });
        }

        if ($request->filled('instrument')) {
            $instrument = trim((string) $request->query('instrument'));
            $query->where('instrument', 'like', "%{$instrument}%");
        }

        if ($request->filled('genre')) {
            $genre = trim((string) $request->query('genre'));
            $query->where('genre', 'like', "%{$genre}%");
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
        $score = ScoreDocument::query()->active()->find($id);

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
        $score = ScoreDocument::query()->active()->find($id);

        if (! $score) {
            return response()->json([
                'success' => false,
                'message' => 'Documento o partitura no encontrado o inactivo.',
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
     * @return array<string, mixed>
     */
    private function transformScoreDocument(ScoreDocument $score): array
    {
        return [
            'id' => $score->id,
            'title' => $score->title,
            'composer' => $score->composer,
            'instrument' => $score->instrument,
            'genre' => $score->genre,
            'difficulty' => $score->difficulty,
            'description' => $score->description,
            'file_name' => $score->file_name,
            'file_size' => $score->file_size,
            'formatted_file_size' => $score->formatted_file_size,
            'mime_type' => $score->mime_type,
            'is_premium_only' => $score->is_premium_only,
            'downloads_count' => $score->downloads_count,
            'download_url' => route('api.scores.download', ['id' => $score->id]),
            'created_at' => $score->created_at?->toIso8601String(),
            'updated_at' => $score->updated_at?->toIso8601String(),
        ];
    }
}
