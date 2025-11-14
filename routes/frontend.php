<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Frontend Website Routes
|--------------------------------------------------------------------------
|
| Routes for the public-facing single page website.
| These are accessible to everyone.
|
*/

// Public Frontend Routes
Route::name('frontend.')->group(function () {
    
    // Single Page Website (all sections on one page)
    Route::get('/', [\App\Http\Controllers\Frontend\HomeController::class, 'index'])->name('home');
    
    // CMS Pages
    Route::get('/pages/{slug}', [\App\Http\Controllers\Frontend\PageController::class, 'show'])->name('pages.show');
    
    // Contact Form
    Route::post('/contact', [\App\Http\Controllers\Frontend\ContactController::class, 'store'])->name('contact.store');
    
    // Member Registration (public)
    Route::get('/register', [\App\Http\Controllers\Frontend\MemberController::class, 'register'])->name('register');
    Route::post('/register', [\App\Http\Controllers\Frontend\MemberController::class, 'store'])->name('register.store');
});

// Member Portal (requires authentication)
Route::prefix('member')->name('member.')->middleware(['auth', 'role:member'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Frontend\MemberController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [\App\Http\Controllers\Frontend\MemberController::class, 'profile'])->name('profile');
    Route::put('/profile', [\App\Http\Controllers\Frontend\MemberController::class, 'updateProfile'])->name('profile.update');
    Route::put('/password', [\App\Http\Controllers\Frontend\MemberController::class, 'updatePassword'])->name('password.update');
    Route::get('/subscriptions', [\App\Http\Controllers\Frontend\MemberController::class, 'subscriptions'])->name('subscriptions');
    Route::get('/activities', [\App\Http\Controllers\Frontend\MemberController::class, 'activities'])->name('activities');
    Route::get('/workout-plans', [\App\Http\Controllers\Frontend\MemberController::class, 'workoutPlans'])->name('workout-plans');
    Route::get('/diet-plans', [\App\Http\Controllers\Frontend\MemberController::class, 'dietPlans'])->name('diet-plans');
});

// API Routes for CMS (for fetching dynamic content)
Route::prefix('api')->name('api.')->group(function () {
    Route::get('/pages/{slug}', [\App\Http\Controllers\Api\PageController::class, 'show'])->name('pages.show');
    Route::get('/content/{type}', [\App\Http\Controllers\Api\ContentController::class, 'index'])->name('content.index');
});

