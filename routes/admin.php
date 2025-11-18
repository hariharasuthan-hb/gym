<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Portal Routes
|--------------------------------------------------------------------------
|
| All routes for the admin portal/backend management system.
| These routes require authentication and admin role.
|
*/

Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    
    // Dashboard - accessible by both admin and trainer
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])
        ->middleware('role:admin,trainer')
        ->name('dashboard');

    // Admin-only routes
    Route::middleware(['role:admin'])->group(function () {
        // Users Management
        Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
        
        // Subscription Plans
        Route::resource('subscription-plans', \App\Http\Controllers\Admin\SubscriptionPlanController::class);
        
        // Subscriptions
        Route::post('subscriptions/{subscription}/cancel', [\App\Http\Controllers\Admin\SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
        Route::resource('subscriptions', \App\Http\Controllers\Admin\SubscriptionController::class);
        
        // Payments
        Route::resource('payments', \App\Http\Controllers\Admin\PaymentController::class);
        
        // Invoices
        Route::resource('invoices', \App\Http\Controllers\Admin\InvoiceController::class);
        
        // Expenses
        Route::resource('expenses', \App\Http\Controllers\Admin\ExpenseController::class);
        
        // Incomes
        Route::resource('incomes', \App\Http\Controllers\Admin\IncomeController::class);

        // Finances Overview
        Route::get('/finances', [\App\Http\Controllers\Admin\FinanceController::class, 'index'])
            ->name('finances.index');
        
        // Reports
        Route::get('/reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports.index');
        
        // CMS Management (for frontend content)
        Route::prefix('cms')->name('cms.')->group(function () {
            Route::resource('pages', \App\Http\Controllers\Admin\Cms\PageController::class);
            Route::resource('content', \App\Http\Controllers\Admin\Cms\ContentController::class);
        });
        
        // Landing Page Content Management
        Route::get('/landing-page', [\App\Http\Controllers\Admin\LandingPageController::class, 'index'])->name('landing-page.index');
        Route::put('/landing-page/{landingPage}', [\App\Http\Controllers\Admin\LandingPageController::class, 'update'])->name('landing-page.update');
        
        // Site Settings
        Route::get('/site-settings', [\App\Http\Controllers\Admin\SiteSettingsController::class, 'index'])
            ->middleware('permission:view site settings')
            ->name('site-settings.index');
        Route::put('/site-settings/{siteSetting}', [\App\Http\Controllers\Admin\SiteSettingsController::class, 'update'])
            ->middleware('permission:edit site settings')
            ->name('site-settings.update');
        
        // Banners Management
        Route::resource('banners', \App\Http\Controllers\Admin\BannerController::class);
        
        // Payment Settings
        Route::get('/payment-settings', [\App\Http\Controllers\Admin\PaymentSettingController::class, 'index'])
            ->name('payment-settings.index');
        Route::put('/payment-settings', [\App\Http\Controllers\Admin\PaymentSettingController::class, 'update'])
            ->name('payment-settings.update');
    });
    
    // Routes accessible by both admin and trainer (permission-based)
    Route::middleware(['role:admin,trainer'])->group(function () {
        // Activity Logs (accessible by admin and trainer with permission)
        Route::get('/activities', [\App\Http\Controllers\Admin\ActivityLogController::class, 'index'])
            ->middleware('permission:view activities')
            ->name('activities.index');

        // Workout video reviews
        Route::get('/trainer/workout-videos', [\App\Http\Controllers\Admin\WorkoutVideoReviewController::class, 'index'])
            ->name('trainer.workout-videos.index');
        Route::post('/trainer/workout-videos/{workoutVideo}/review', [\App\Http\Controllers\Admin\WorkoutVideoReviewController::class, 'review'])
            ->name('trainer.workout-videos.review');
        
        // Workout Plans - Full CRUD with permission checks
        Route::get('/workout-plans', [\App\Http\Controllers\Admin\WorkoutPlanController::class, 'index'])
            ->middleware('permission:view workout plans')
            ->name('workout-plans.index');
        Route::get('/workout-plans/create', [\App\Http\Controllers\Admin\WorkoutPlanController::class, 'create'])
            ->middleware('permission:create workout plans')
            ->name('workout-plans.create');
        Route::post('/workout-plans', [\App\Http\Controllers\Admin\WorkoutPlanController::class, 'store'])
            ->middleware('permission:create workout plans')
            ->name('workout-plans.store');
        
        // Demo video upload routes (chunked upload support) - MUST come before parameterized routes
        // Routes without workoutPlan parameter (for create)
        Route::post('/workout-plans/upload-demo-video', [\App\Http\Controllers\Admin\WorkoutPlanController::class, 'uploadDemoVideo'])
            ->middleware('permission:create workout plans|edit workout plans')
            ->name('workout-plans.upload-demo-video');
        Route::post('/workout-plans/upload-demo-video-chunk', [\App\Http\Controllers\Admin\WorkoutPlanController::class, 'uploadDemoVideoChunk'])
            ->middleware('permission:create workout plans|edit workout plans')
            ->name('workout-plans.upload-demo-video-chunk');
        
        // Parameterized routes (must come after non-parameterized routes)
        Route::get('/workout-plans/{workoutPlan}', [\App\Http\Controllers\Admin\WorkoutPlanController::class, 'show'])
            ->middleware('permission:view workout plans')
            ->name('workout-plans.show');
        Route::get('/workout-plans/{workoutPlan}/edit', [\App\Http\Controllers\Admin\WorkoutPlanController::class, 'edit'])
            ->middleware('permission:edit workout plans')
            ->name('workout-plans.edit');
        Route::put('/workout-plans/{workoutPlan}', [\App\Http\Controllers\Admin\WorkoutPlanController::class, 'update'])
            ->middleware('permission:edit workout plans')
            ->name('workout-plans.update');
        Route::delete('/workout-plans/{workoutPlan}', [\App\Http\Controllers\Admin\WorkoutPlanController::class, 'destroy'])
            ->middleware('permission:delete workout plans')
            ->name('workout-plans.destroy');
        // Routes with workoutPlan parameter (for edit - optional, uses same controller)
        Route::post('/workout-plans/{workoutPlan}/upload-demo-video', [\App\Http\Controllers\Admin\WorkoutPlanController::class, 'uploadDemoVideo'])
            ->middleware('permission:create workout plans|edit workout plans')
            ->name('workout-plans.upload-demo-video-existing');
        Route::post('/workout-plans/{workoutPlan}/upload-demo-video-chunk', [\App\Http\Controllers\Admin\WorkoutPlanController::class, 'uploadDemoVideoChunk'])
            ->middleware('permission:create workout plans|edit workout plans')
            ->name('workout-plans.upload-demo-video-chunk-existing');
        
        // Diet Plans - Full CRUD with permission checks
        Route::get('/diet-plans', [\App\Http\Controllers\Admin\DietPlanController::class, 'index'])
            ->middleware('permission:view diet plans')
            ->name('diet-plans.index');
        Route::get('/diet-plans/create', [\App\Http\Controllers\Admin\DietPlanController::class, 'create'])
            ->middleware('permission:create diet plans')
            ->name('diet-plans.create');
        Route::post('/diet-plans', [\App\Http\Controllers\Admin\DietPlanController::class, 'store'])
            ->middleware('permission:create diet plans')
            ->name('diet-plans.store');
        Route::get('/diet-plans/{dietPlan}', [\App\Http\Controllers\Admin\DietPlanController::class, 'show'])
            ->middleware('permission:view diet plans')
            ->name('diet-plans.show');
        Route::get('/diet-plans/{dietPlan}/edit', [\App\Http\Controllers\Admin\DietPlanController::class, 'edit'])
            ->middleware('permission:edit diet plans')
            ->name('diet-plans.edit');
        Route::put('/diet-plans/{dietPlan}', [\App\Http\Controllers\Admin\DietPlanController::class, 'update'])
            ->middleware('permission:edit diet plans')
            ->name('diet-plans.update');
        Route::delete('/diet-plans/{dietPlan}', [\App\Http\Controllers\Admin\DietPlanController::class, 'destroy'])
            ->middleware('permission:delete diet plans')
            ->name('diet-plans.destroy');
        
        // User Activity Overview (accessible by admin and trainer with permission)
        Route::get('/user-activity', [\App\Http\Controllers\Admin\UserActivityController::class, 'index'])
            ->middleware('permission:view activities')
            ->name('user-activity.index');
    });

    Route::middleware(['role:admin,trainer', 'permission:view reports|export reports'])->group(function () {
        Route::post('/exports/{type}', [\App\Http\Controllers\Admin\ExportController::class, 'export'])
            ->name('exports.export');
        Route::get('/exports/{export}/status', [\App\Http\Controllers\Admin\ExportController::class, 'status'])
            ->name('exports.status');
        Route::get('/exports/{export}/download', [\App\Http\Controllers\Admin\ExportController::class, 'download'])
            ->name('exports.download');
    });

});

