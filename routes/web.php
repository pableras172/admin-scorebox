<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('marketing')->name('marketing.')->group(function (): void {
    Route::get('/unsubscribe', [\App\Http\Controllers\Marketing\UnsubscribeController::class, 'unsubscribe'])
        ->middleware('signed')
        ->name('unsubscribe');

    Route::post('/unsubscribe', [\App\Http\Controllers\Marketing\UnsubscribeController::class, 'unsubscribePost'])
        ->middleware('signed')
        ->name('unsubscribe.post');

    Route::post('/resubscribe', [\App\Http\Controllers\Marketing\UnsubscribeController::class, 'resubscribe'])
        ->middleware('signed')
        ->name('resubscribe');
});

