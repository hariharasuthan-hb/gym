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

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', function () {
        return view('admin.dashboard.index');
    })->name('dashboard');

    // Users Management
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
    
    // Subscription Plans
    Route::resource('subscription-plans', \App\Http\Controllers\Admin\SubscriptionPlanController::class);
    
    // Subscriptions
    Route::post('subscriptions/{subscription}/cancel', [\App\Http\Controllers\Admin\SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
    Route::resource('subscriptions', \App\Http\Controllers\Admin\SubscriptionController::class);
    
    // Activity Logs
    Route::resource('activities', \App\Http\Controllers\Admin\ActivityLogController::class);
    
    // Workout Plans
    Route::resource('workout-plans', \App\Http\Controllers\Admin\WorkoutPlanController::class);
    
    // Diet Plans
    Route::resource('diet-plans', \App\Http\Controllers\Admin\DietPlanController::class);
    
    // Payments
    Route::resource('payments', \App\Http\Controllers\Admin\PaymentController::class);
    
    // Invoices
    Route::resource('invoices', \App\Http\Controllers\Admin\InvoiceController::class);
    
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

