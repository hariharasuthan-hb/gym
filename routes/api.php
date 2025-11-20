<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\PageController;
use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function () {
    Route::get('/pages/{slug}', [PageController::class, 'show'])->name('pages.show');
    Route::get('/content/{type}', [ContentController::class, 'index'])->name('content.index');

    Route::prefix('member')->name('member.')->group(function () {
        Route::post('/login', [LoginController::class, 'login'])->name('login');

        Route::middleware(['auth:api', 'role:member'])->group(function () {
            Route::get('/profile', [MemberController::class, 'profile'])->name('profile');
            Route::put('/profile', [MemberController::class, 'updateProfile'])->name('profile.update');
            Route::put('/password', [MemberController::class, 'updatePassword'])->name('password.update');

            Route::get('/dashboard', [MemberController::class, 'dashboard'])->name('dashboard');
            Route::get('/subscriptions', [MemberController::class, 'subscriptions'])->name('subscriptions');
            Route::get('/activities', [MemberController::class, 'activities'])->name('activities');
            Route::get('/workout-plans', [MemberController::class, 'workoutPlans'])->name('workout-plans');
            Route::get('/workout-plans/{id}', [MemberController::class, 'showWorkoutPlan'])->name('workout-plans.show');
            Route::post('/workout-plans/{id}/upload-video', [MemberController::class, 'uploadWorkoutVideo'])->name('workout-plans.upload-video');
            Route::post('/workout-plans/{id}/mark-attendance', [MemberController::class, 'markAttendance'])->name('workout-plans.mark-attendance');
            Route::get('/workout-videos', [MemberController::class, 'workoutVideos'])->name('workout-videos');
            Route::get('/diet-plans', [MemberController::class, 'dietPlans'])->name('diet-plans');
            Route::post('/check-in', [MemberController::class, 'checkIn'])->name('check-in');
            Route::post('/check-out', [MemberController::class, 'checkOut'])->name('check-out');
        });
    });
});

