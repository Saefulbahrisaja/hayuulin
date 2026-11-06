<?php

use App\Http\Controllers\AdminCatalogController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminMlController;
use App\Http\Controllers\AdminOpenDataController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DestinationReviewController;
use App\Http\Controllers\TourismController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\LanguageController;
use App\Services\SmartTourismAssistant;
use Illuminate\Support\Facades\Route;

Route::get('/', [TourismController::class, 'index'])->name('home');
Route::post('/bahasa', [LanguageController::class, 'update'])->name('language.update');
Route::post('/rencana', [TourismController::class, 'recommend'])->name('trips.recommend');
Route::post('/asisten', function (\Illuminate\Http\Request $request, SmartTourismAssistant $assistant) {
    $validated = $request->validate(['question' => ['required', 'string', 'min:3', 'max:500']]);

    return redirect()->route('home')->with('assistant_answer', $assistant->answer($validated['question']))
        ->with('assistant_question', $validated['question'])->withFragment('asisten');
})->middleware('throttle:10,1')->name('assistant.ask');
Route::get('/login', [AuthController::class, 'showLogin'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest')->name('login.store');
Route::get('/daftar', [AuthController::class, 'showRegister'])->middleware('guest')->name('register');
Route::post('/daftar', [AuthController::class, 'register'])->middleware('guest')->name('register.store');
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/rencana/durasi', [TripController::class, 'updateDraft'])->name('trips.draft.update');
    Route::post('/perjalanan/{trip}/biaya-aktual', [TripController::class, 'updateActualCost'])->name('trips.actual-cost.update');
    Route::post('/destinasi/{destination}/ulasan', [DestinationReviewController::class, 'store'])->middleware('throttle:5,1')->name('destinations.reviews.store');
    Route::get('/perjalanan', [TripController::class, 'index'])->name('trips.index');
    Route::post('/perjalanan', [TripController::class, 'store'])->name('trips.store');
    Route::get('/perjalanan/{trip}', [TripController::class, 'show'])->name('trips.show');
    Route::delete('/perjalanan/{trip}', [TripController::class, 'destroy'])->name('trips.destroy');
});
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');
    Route::get('/users/create', [AdminController::class, 'createUser'])->name('users.create');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::get('/users/{user}/edit', [AdminController::class, 'editUser'])->name('users.edit');
    Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::patch('/users/{user}/admin', [AdminController::class, 'toggleAdmin'])->name('users.toggle-admin');
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');
    Route::get('/open-data', [AdminOpenDataController::class, 'index'])->name('open-data.index');
    Route::post('/open-data/preview', [AdminOpenDataController::class, 'preview'])->middleware('throttle:2,1')->name('open-data.preview');
    Route::post('/open-data/import', [AdminOpenDataController::class, 'import'])->name('open-data.import');
    Route::get('/machine-learning', [AdminMlController::class, 'index'])->name('machine-learning.index');
    Route::post('/machine-learning/kunjungan', [AdminMlController::class, 'storeVisitMetric'])->name('machine-learning.visits.store');
    Route::get('/catalog/{resource}', [AdminCatalogController::class, 'index'])->name('catalog.index');
    Route::get('/catalog/{resource}/create', [AdminCatalogController::class, 'create'])->name('catalog.create');
    Route::post('/catalog/{resource}', [AdminCatalogController::class, 'store'])->name('catalog.store');
    Route::get('/catalog/{resource}/{record}/edit', [AdminCatalogController::class, 'edit'])->whereNumber('record')->name('catalog.edit');
    Route::put('/catalog/{resource}/{record}', [AdminCatalogController::class, 'update'])->whereNumber('record')->name('catalog.update');
    Route::delete('/catalog/{resource}/{record}', [AdminCatalogController::class, 'destroy'])->whereNumber('record')->name('catalog.destroy');
});
