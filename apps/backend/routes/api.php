<?php

use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\GenreController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\MetaController;
use App\Http\Controllers\Api\V1\MovieController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', HealthController::class)->name('health');
    Route::get('/meta', MetaController::class)->name('meta');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/auth/user', UserController::class)->name('auth.user');

        Route::middleware('verified')->group(function (): void {
            Route::get('/movies', [MovieController::class, 'index'])->middleware('throttle:tmdb');
            Route::get('/movies/{tmdbId}', [MovieController::class, 'show'])
                ->whereNumber('tmdbId')->middleware('throttle:tmdb');
            Route::get('/genres', GenreController::class)->middleware('throttle:tmdb');
            Route::apiResource('favorites', FavoriteController::class)
                ->only(['index', 'store', 'destroy'])
                ->middleware('throttle:favorites');
        });
    });
});
