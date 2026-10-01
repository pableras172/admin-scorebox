<?php

use App\Http\Controllers\Marketing\UnsubscribeController;
use App\Http\Controllers\SuggestionWebController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::get('/sugerencias', [SuggestionWebController::class, 'create'])->name('suggestions.create');
Route::post('/sugerencias', [SuggestionWebController::class, 'store'])->name('suggestions.store');

Route::prefix('marketing')->name('marketing.')->group(function (): void {
    Route::get('/unsubscribe', [UnsubscribeController::class, 'unsubscribe'])
        ->middleware('signed')
        ->name('unsubscribe');

    Route::post('/unsubscribe', [UnsubscribeController::class, 'unsubscribePost'])
        ->middleware('signed')
        ->name('unsubscribe.post');

    Route::post('/resubscribe', [UnsubscribeController::class, 'resubscribe'])
        ->middleware('signed')
        ->name('resubscribe');
});
