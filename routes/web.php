<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Public story share route - no authentication required
Route::get('/story/{shareId}', function($shareId) {
    $controller = new App\Http\Controllers\Api\V1\User\Story\StoryController();
    $request = request(); // Use the actual request to preserve headers
    $request->merge(['share_url' => $shareId]);
    return $controller->viewSharedStory($request);
})->name('story.share');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
