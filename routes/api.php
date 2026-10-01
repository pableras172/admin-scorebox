<?php

declare(strict_types=1);

use App\Http\Controllers\Api\SuggestionApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/suggestions', [SuggestionApiController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('api.suggestions.store');
});
