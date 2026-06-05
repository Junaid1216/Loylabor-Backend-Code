<?php

use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', function () {
    return to_route('website.user.dashboard');
})->middleware(['auth:web', 'verified', 'maintenance.mode'])->name('dashboard');

Route::get('set-language', [DashboardController::class, 'setLanguage'])->name('set-language')->withoutMiddleware(['auth:web', 'auth:admin']);
Route::get('set-currency', [DashboardController::class, 'setCurrency'])->name('set-currency')->withoutMiddleware(['auth:web', 'auth:admin']);

Route::get('/robots.txt', function () {
    $disallow = getSettingStatus('search_engine_indexing');

    if (!$disallow) {
        return response("User-agent: *\nDisallow: /", 200)
            ->header('Content-Type', 'text/plain');
    }

    return response("User-agent: *\nDisallow: /admin/", 200)
        ->header('Content-Type', 'text/plain');
});

Route::fallback(function () {
    if (request()->is('api') || request()->is('api/*')) {
        return response()->json([
            'success' => false,
            'message' => 'API endpoint not found. Use POST for register/login and send Accept: application/json.',
            'path' => request()->path(),
            'method' => request()->method(),
        ], 404);
    }

    abort(404);
});
