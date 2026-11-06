<?php

use App\Http\Controllers\Api\V1\MobileApiController;
use App\Http\Controllers\Api\V1\MobileAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/register', [MobileAuthController::class, 'register'])->middleware('throttle:5,1')->name('register');
        Route::post('/login', [MobileAuthController::class, 'login'])->middleware('throttle:5,1')->name('login');

        Route::middleware(['auth:sanctum', 'abilities:tourist', 'tourist'])->group(function () {
            Route::get('/me', fn (Request $request) => response()->json([
                'data' => [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                ],
            ]))->name('me');
            Route::post('/logout', [MobileAuthController::class, 'logout'])->name('logout');
        });
    });

    Route::get('/categories', [MobileApiController::class, 'categories'])->name('categories.index');
    Route::get('/map', [MobileApiController::class, 'map'])->name('map.show');
    Route::get('/destinations', [MobileApiController::class, 'destinations'])->name('destinations.index');
    Route::get('/destinations/{destinationId}', [MobileApiController::class, 'showDestination'])->whereNumber('destinationId')->name('destinations.show');
    Route::get('/accommodations', [MobileApiController::class, 'accommodations'])->name('accommodations.index');
    Route::get('/transport-options', [MobileApiController::class, 'transportOptions'])->name('transport-options.index');
    Route::get('/transport-routes', [MobileApiController::class, 'transportRoutes'])->name('transport-routes.index');

    Route::middleware(['auth:sanctum', 'abilities:tourist', 'tourist'])->group(function () {
        Route::post('/trips/recommend', [MobileApiController::class, 'recommend'])
            ->middleware('throttle:10,1')
            ->name('trips.recommend');
        Route::get('/trips', [MobileApiController::class, 'trips'])->name('trips.index');
        Route::post('/trips', [MobileApiController::class, 'storeTrip'])->name('trips.store');
        Route::get('/trips/{tripId}', [MobileApiController::class, 'showTrip'])->whereNumber('tripId')->name('trips.show');
        Route::patch('/trips/{tripId}/actual-cost', [MobileApiController::class, 'updateActualCost'])->whereNumber('tripId')->name('trips.actual-cost.update');
        Route::delete('/trips/{tripId}', [MobileApiController::class, 'destroyTrip'])->whereNumber('tripId')->name('trips.destroy');
        Route::post('/destinations/{destinationId}/reviews', [MobileApiController::class, 'storeReview'])
            ->whereNumber('destinationId')
            ->middleware('throttle:5,1')
            ->name('destinations.reviews.store');
        Route::post('/assistant', [MobileApiController::class, 'assistant'])
            ->middleware('throttle:10,1')
            ->name('assistant.ask');
    });
});
