<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Main Web Routes
|--------------------------------------------------------------------------
|
| This file includes all route files for the application.
| Routes are separated into admin, frontend, and auth.
|
*/

// Include route files
require __DIR__.'/auth.php';
require __DIR__.'/frontend.php';
require __DIR__.'/admin.php';

// Debug routes (only in local environment)
if (app()->environment('local')) {
    require __DIR__.'/debug.php';
}

// PHP Configuration Check (only in local environment)
if (app()->environment('local')) {
    Route::get('/check-php-config', function () {
        return response()->file(public_path('../check-php-config.php'));
    })->name('check.php.config');
}

// Webhook routes (no authentication or CSRF required)
Route::post('/webhook/stripe', [\App\Http\Controllers\WebhookController::class, 'stripe'])
    ->name('webhook.stripe')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);
Route::post('/webhook/razorpay', [\App\Http\Controllers\WebhookController::class, 'razorpay'])
    ->name('webhook.razorpay')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

// Dashboard route - redirects based on user role
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();
        
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->hasRole('trainer')) {
            // TODO: Create trainer dashboard route
            return redirect()->route('admin.dashboard');
        } elseif ($user->hasRole('member')) {
            return redirect()->route('member.dashboard');
        }
        
        // Default fallback
        return redirect()->route('frontend.home');
    })->name('dashboard');
    
    // Profile routes (shared)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
