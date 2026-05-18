<?php

use App\Http\Controllers\Api\Admin\AdminTourController;
use App\Http\Controllers\Api\Admin\TourGenerationController;
use App\Http\Controllers\Api\Admin\AdminTourPhotoController;
use App\Http\Controllers\Api\Admin\AdminTourVariantController;
use App\Http\Controllers\Api\Admin\AdminTourWaypointController;
use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\SettingsController;
use App\Http\Controllers\Api\RouteProxyController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\TourController;
use App\Http\Controllers\Api\TourTypeController;
use Illuminate\Support\Facades\Route;

Route::get('/tour-types', [TourTypeController::class, 'index']);
Route::get('/settings', [SettingsController::class, 'publicIndex']);
Route::get('/translations', [SettingsController::class, 'publicTranslations']);

Route::get('/route', [RouteProxyController::class, 'index']);

Route::get('/tours', [TourController::class, 'index']);
Route::get('/tours/meta', [TourController::class, 'meta']);
Route::get('/tours/search', SearchController::class);
Route::get('/tours/suggest', [TourController::class, 'suggest']);
Route::get('/tours/{slug}', [TourController::class, 'show']);

Route::prefix('admin')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::post('/tour-types', [TourTypeController::class, 'store']);
        Route::put('/tour-types/{id}', [TourTypeController::class, 'update']);
        Route::delete('/tour-types/{id}', [TourTypeController::class, 'destroy']);

        Route::post('/tours/generate', [TourGenerationController::class, 'generate']);
        Route::get('/tours/{slug}', [AdminTourController::class, 'show']);
        Route::post('/tours', [AdminTourController::class, 'store']);
        Route::put('/tours/{id}', [AdminTourController::class, 'update']);
        Route::delete('/tours/{id}', [AdminTourController::class, 'destroy']);

        Route::post('/tours/{id}/photos', [AdminTourPhotoController::class, 'store']);
        Route::put('/tours/{id}/photos/reorder', [AdminTourPhotoController::class, 'reorder']);
        Route::delete('/tours/{tourId}/photos/{photoId}', [AdminTourPhotoController::class, 'destroy']);

        Route::put('/tours/{id}/waypoints', [AdminTourWaypointController::class, 'sync']);

        Route::post('/tour-variants', [AdminTourVariantController::class, 'store']);
        Route::put('/tour-variants/{id}', [AdminTourVariantController::class, 'update']);
        Route::delete('/tour-variants/{id}', [AdminTourVariantController::class, 'destroy']);

        Route::get('/settings', [SettingsController::class, 'index']);
        Route::put('/settings', [SettingsController::class, 'update']);
        Route::post('/settings/og-image', [SettingsController::class, 'uploadOgImage']);

        Route::put('/translations', [SettingsController::class, 'updateTranslations']);
    });
});
