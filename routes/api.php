<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ScoreDocumentApiController;
use App\Http\Controllers\Api\SuggestionApiController;
use App\Http\Middleware\ValidateScoreBoxApiKey;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/suggestions', [SuggestionApiController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('api.suggestions.store');

    Route::middleware([ValidateScoreBoxApiKey::class, 'throttle:60,1'])->group(function (): void {
        Route::get('/scores', [ScoreDocumentApiController::class, 'index'])->name('api.scores.index');
        Route::get('/scores/{id}', [ScoreDocumentApiController::class, 'show'])->name('api.scores.show');
        Route::get('/scores/{id}/download', [ScoreDocumentApiController::class, 'download'])->name('api.scores.download');
        Route::get('/genres', [ScoreDocumentApiController::class, 'genres'])->name('api.scores.genres');
        Route::get('/band-types', [ScoreDocumentApiController::class, 'bandTypes'])->name('api.scores.band-types');
        Route::get('/authors', [ScoreDocumentApiController::class, 'authors'])->name('api.scores.authors');
        Route::get('/authors/{id}', [ScoreDocumentApiController::class, 'authorDetail'])->name('api.scores.author-detail');
    });
});
