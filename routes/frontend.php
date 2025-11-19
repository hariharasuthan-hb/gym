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
    Route::get('/workout-plans/{workoutPlan}', [\App\Http\Controllers\Frontend\MemberController::class, 'showWorkoutPlan'])->name('workout-plans.show');
    Route::get('/workout-videos', [\App\Http\Controllers\Frontend\MemberController::class, 'workoutVideos'])->name('workout-videos');
    Route::post('/workout-plans/{workoutPlan}/upload-video', [\App\Http\Controllers\Frontend\MemberController::class, 'uploadWorkoutVideo'])->name('workout-plans.upload-video');
    Route::post('/workout-plans/{workoutPlan}/upload-video-chunk', [\App\Http\Controllers\Frontend\MemberController::class, 'uploadWorkoutVideoChunk'])->name('workout-plans.upload-video-chunk');
    Route::post('/workout-plans/{workoutPlan}/mark-attendance', [\App\Http\Controllers\Frontend\MemberController::class, 'markAttendance'])->name('workout-plans.mark-attendance');
    Route::post('/check-in', [\App\Http\Controllers\Frontend\MemberController::class, 'checkIn'])->name('check-in');
    Route::post('/check-out', [\App\Http\Controllers\Frontend\MemberController::class, 'checkOut'])->name('check-out');
    Route::get('/diet-plans', [\App\Http\Controllers\Frontend\MemberController::class, 'dietPlans'])->name('diet-plans');
    
    // Subscription routes
            Route::prefix('subscription')->name('subscription.')->group(function () {
                Route::get('/checkout/{plan}', [\App\Http\Controllers\Member\CheckoutController::class, 'checkout'])->name('checkout');
                Route::post('/create/{plan}', [\App\Http\Controllers\Member\CheckoutController::class, 'create'])->name('create');
                Route::get('/success', [\App\Http\Controllers\Member\SubscriptionController::class, 'success'])->name('success');
                Route::get('/', [\App\Http\Controllers\Member\SubscriptionController::class, 'index'])->name('index');
                Route::post('/cancel/{subscription}', [\App\Http\Controllers\Member\SubscriptionController::class, 'cancel'])->name('cancel');
                Route::post('/refresh/{subscription}', [\App\Http\Controllers\Member\SubscriptionController::class, 'refresh'])->name('refresh');
            });
});

// API Routes for CMS (for fetching dynamic content)
Route::prefix('api')->name('api.')->group(function () {
    Route::get('/pages/{slug}', [\App\Http\Controllers\Api\PageController::class, 'show'])->name('pages.show');
    Route::get('/content/{type}', [\App\Http\Controllers\Api\ContentController::class, 'index'])->name('content.index');
    
    // Member API Routes (requires authentication and member role)
    Route::prefix('member')->name('member.')->middleware(['auth', 'role:member'])->group(function () {
        // Profile endpoints
        Route::get('/profile', [\App\Http\Controllers\Api\MemberController::class, 'profile'])->name('profile');
        Route::put('/profile', [\App\Http\Controllers\Api\MemberController::class, 'updateProfile'])->name('profile.update');
        Route::put('/password', [\App\Http\Controllers\Api\MemberController::class, 'updatePassword'])->name('password.update');
        
        // Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\Api\MemberController::class, 'dashboard'])->name('dashboard');
        
        // Subscriptions
        Route::get('/subscriptions', [\App\Http\Controllers\Api\MemberController::class, 'subscriptions'])->name('subscriptions');
        
        // Activities
        Route::get('/activities', [\App\Http\Controllers\Api\MemberController::class, 'activities'])->name('activities');
        
        // Workout Plans
        Route::get('/workout-plans', [\App\Http\Controllers\Api\MemberController::class, 'workoutPlans'])->name('workout-plans');
        Route::get('/workout-plans/{id}', [\App\Http\Controllers\Api\MemberController::class, 'showWorkoutPlan'])->name('workout-plans.show');
        Route::post('/workout-plans/{id}/upload-video', [\App\Http\Controllers\Api\MemberController::class, 'uploadWorkoutVideo'])->name('workout-plans.upload-video');
        Route::post('/workout-plans/{id}/mark-attendance', [\App\Http\Controllers\Api\MemberController::class, 'markAttendance'])->name('workout-plans.mark-attendance');
        
        // Workout Videos
        Route::get('/workout-videos', [\App\Http\Controllers\Api\MemberController::class, 'workoutVideos'])->name('workout-videos');
        
        // Diet Plans
        Route::get('/diet-plans', [\App\Http\Controllers\Api\MemberController::class, 'dietPlans'])->name('diet-plans');
        
        // Check-in/Check-out
        Route::post('/check-in', [\App\Http\Controllers\Api\MemberController::class, 'checkIn'])->name('check-in');
        Route::post('/check-out', [\App\Http\Controllers\Api\MemberController::class, 'checkOut'])->name('check-out');
    });
});

