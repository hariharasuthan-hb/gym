<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\{
    DashboardController,
    UserController,
    SubscriptionPlanController,
    SubscriptionController,
    PaymentController,
    InvoiceController,
    InAppNotificationController,
    ExpenseController,
    IncomeController,
    FinanceController,
    ReportController,
    LandingPageController,
    SiteSettingsController,
    BannerController,
    PaymentSettingController,
    OrphanedVideosController,
    LeadController,
    AnnouncementController,
    ActivityLogController,
    WorkoutVideoReviewController,
    WorkoutPlanController,
    DietPlanController,
    UserActivityController,
    NotificationCenterController,
    ExportController
};
use App\Http\Controllers\Admin\Cms\{
    PageController as CmsPageController,
    ContentController as CmsContentController
};
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Admin Portal Routes
|--------------------------------------------------------------------------
|
| All routes for the admin portal/backend management system.
| These routes require authentication and admin role.
|
*/

Route::prefix('admin')->name('admin.')->middleware(['auth', 'prevent-back-history'])->group(function () {
    
    // Dashboard - accessible by both admin and trainer
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('role:admin,trainer')
        ->name('dashboard');

    // ============================================
    // Users Management Routes
    // ============================================
    // IMPORTANT: /users/create must come BEFORE /users/{user} to avoid route conflicts
    Route::middleware(['role:admin,trainer'])->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
    });
    
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
    });
    
    Route::middleware(['role:admin,trainer'])->group(function () {
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    });
    
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // ============================================
    // Admin-Only Resource Routes
    // ============================================
    Route::middleware(['role:admin'])->group(function () {
        Route::resource('subscription-plans', SubscriptionPlanController::class);
        Route::resource('subscriptions', SubscriptionController::class);
        Route::resource('payments', PaymentController::class);
        Route::resource('invoices', InvoiceController::class);
        Route::resource('expenses', ExpenseController::class);
        Route::resource('incomes', IncomeController::class);
        Route::resource('banners', BannerController::class);
        
        // Leads Management - Create, Delete (Admin only)
        Route::get('/leads/create', [LeadController::class, 'create'])->name('leads.create');
        Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');
        Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');
        
        // Notifications (admin-only management of in-app notification templates)
        Route::resource('notifications', InAppNotificationController::class)->except(['show']);
        
        // Subscriptions - Custom routes
        Route::post('subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])
            ->name('subscriptions.cancel');
        
        // Finances Overview
        Route::get('/finances', [FinanceController::class, 'index'])->name('finances.index');
        
        // Reports
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        
        // CMS Management (for frontend content)
        Route::prefix('cms')->name('cms.')->group(function () {
            Route::resource('pages', CmsPageController::class);
            Route::resource('content', CmsContentController::class);
        });
        
        // Landing Page Content Management
        Route::get('/landing-page', [LandingPageController::class, 'index'])
            ->name('landing-page.index');
        Route::put('/landing-page/{landingPage}', [LandingPageController::class, 'update'])
            ->name('landing-page.update');
        
        // Site Settings
        Route::get('/site-settings', [SiteSettingsController::class, 'index'])
            ->middleware('permission:view site settings')
            ->name('site-settings.index');
        Route::put('/site-settings/{siteSetting}', [SiteSettingsController::class, 'update'])
            ->middleware('permission:edit site settings')
            ->name('site-settings.update');
        
        // Payment Settings
        Route::get('/payment-settings', [PaymentSettingController::class, 'index'])
            ->name('payment-settings.index');
        Route::put('/payment-settings', [PaymentSettingController::class, 'update'])
            ->name('payment-settings.update');

        // Orphaned Videos Management
        Route::get('/orphaned-videos', [OrphanedVideosController::class, 'index'])
            ->middleware('permission:view orphaned videos')
            ->name('orphaned-videos.index');
        Route::delete('/orphaned-videos', [OrphanedVideosController::class, 'destroy'])
            ->middleware('permission:delete orphaned videos')
            ->name('orphaned-videos.destroy');
        Route::delete('/orphaned-videos/multiple', [OrphanedVideosController::class, 'destroyMultiple'])
            ->middleware('permission:delete orphaned videos')
            ->name('orphaned-videos.destroy-multiple');
    });

    // ============================================
    // Admin & Trainer Routes (Permission-Based)
    // ============================================
    Route::middleware(['role:admin,trainer'])->group(function () {
        // Leads Management - View, Edit, Update (Trainers can view/edit their assigned leads)
        Route::get('/leads', [LeadController::class, 'index'])
            ->middleware('permission:view leads')
            ->name('leads.index');
        Route::get('/leads/{lead}', [LeadController::class, 'show'])
            ->middleware('permission:view leads')
            ->name('leads.show');
        Route::get('/leads/{lead}/edit', [LeadController::class, 'edit'])
            ->middleware('permission:edit leads')
            ->name('leads.edit');
        Route::put('/leads/{lead}', [LeadController::class, 'update'])
            ->middleware('permission:edit leads')
            ->name('leads.update');
        
        // Announcements management
        Route::resource('announcements', AnnouncementController::class)
            ->except(['show'])
            ->middleware('permission:view announcements|create announcements|edit announcements|delete announcements');

        // Activity Logs
        Route::get('/activities', [ActivityLogController::class, 'index'])
            ->middleware('permission:view activities')
            ->name('activities.index');

        // User Activity Overview
        Route::get('/user-activity', [UserActivityController::class, 'index'])
            ->middleware('permission:view activities')
            ->name('user-activity.index');

        // Workout video reviews
        Route::get('/trainer/workout-videos', [WorkoutVideoReviewController::class, 'index'])
            ->name('trainer.workout-videos.index');
        Route::post('/trainer/workout-videos/{workoutVideo}/review', [WorkoutVideoReviewController::class, 'review'])
            ->name('trainer.workout-videos.review');
        
        // Workout Plans - Full CRUD with permission checks
        // IMPORTANT: Non-parameterized routes must come before parameterized routes
        Route::get('/workout-plans', [WorkoutPlanController::class, 'index'])
            ->middleware('permission:view workout plans')
            ->name('workout-plans.index');
        Route::get('/workout-plans/create', [WorkoutPlanController::class, 'create'])
            ->middleware('permission:create workout plans')
            ->name('workout-plans.create');
        Route::post('/workout-plans', [WorkoutPlanController::class, 'store'])
            ->middleware('permission:create workout plans')
            ->name('workout-plans.store');
        
        // Demo video upload routes (chunked upload support) - MUST come before parameterized routes
        Route::post('/workout-plans/upload-demo-video', [WorkoutPlanController::class, 'uploadDemoVideo'])
            ->middleware('permission:create workout plans|edit workout plans')
            ->name('workout-plans.upload-demo-video');
        Route::post('/workout-plans/upload-demo-video-chunk', [WorkoutPlanController::class, 'uploadDemoVideoChunk'])
            ->middleware('permission:create workout plans|edit workout plans')
            ->name('workout-plans.upload-demo-video-chunk');
        
        // Parameterized routes (must come after non-parameterized routes)
        Route::get('/workout-plans/{workoutPlan}', [WorkoutPlanController::class, 'show'])
            ->middleware('permission:view workout plans')
            ->name('workout-plans.show');
        Route::get('/workout-plans/{workoutPlan}/edit', [WorkoutPlanController::class, 'edit'])
            ->middleware('permission:edit workout plans')
            ->name('workout-plans.edit');
        Route::put('/workout-plans/{workoutPlan}', [WorkoutPlanController::class, 'update'])
            ->middleware('permission:edit workout plans')
            ->name('workout-plans.update');
        Route::delete('/workout-plans/{workoutPlan}', [WorkoutPlanController::class, 'destroy'])
            ->middleware('permission:delete workout plans')
            ->name('workout-plans.destroy');
        
        // Routes with workoutPlan parameter (for edit - optional, uses same controller)
        Route::post('/workout-plans/{workoutPlan}/upload-demo-video', [WorkoutPlanController::class, 'uploadDemoVideo'])
            ->middleware('permission:create workout plans|edit workout plans')
            ->name('workout-plans.upload-demo-video-existing');
        Route::post('/workout-plans/{workoutPlan}/upload-demo-video-chunk', [WorkoutPlanController::class, 'uploadDemoVideoChunk'])
            ->middleware('permission:create workout plans|edit workout plans')
            ->name('workout-plans.upload-demo-video-chunk-existing');
        
        // Diet Plans - Full CRUD with permission checks
        Route::get('/diet-plans', [DietPlanController::class, 'index'])
            ->middleware('permission:view diet plans')
            ->name('diet-plans.index');
        Route::get('/diet-plans/create', [DietPlanController::class, 'create'])
            ->middleware('permission:create diet plans')
            ->name('diet-plans.create');
        Route::post('/diet-plans', [DietPlanController::class, 'store'])
            ->middleware('permission:create diet plans')
            ->name('diet-plans.store');
        Route::get('/diet-plans/{dietPlan}', [DietPlanController::class, 'show'])
            ->middleware('permission:view diet plans')
            ->name('diet-plans.show');
        Route::get('/diet-plans/{dietPlan}/edit', [DietPlanController::class, 'edit'])
            ->middleware('permission:edit diet plans')
            ->name('diet-plans.edit');
        Route::put('/diet-plans/{dietPlan}', [DietPlanController::class, 'update'])
            ->middleware('permission:edit diet plans')
            ->name('diet-plans.update');
        Route::delete('/diet-plans/{dietPlan}', [DietPlanController::class, 'destroy'])
            ->middleware('permission:delete diet plans')
            ->name('diet-plans.destroy');
    });

    // ============================================
    // Profile Routes
    // ============================================
    Route::middleware(['role:admin,trainer'])->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    });

    // ============================================
    // Notification Center Routes
    // ============================================
    Route::middleware(['role:admin,trainer,member', 'permission:view announcements|view notifications'])->group(function () {
        Route::get('/notification-center', [NotificationCenterController::class, 'index'])
            ->name('notification-center.index');
    });

    Route::middleware(['role:admin,trainer,member', 'permission:mark notifications read'])->group(function () {
        Route::post('/notification-center/{notification}/read', [NotificationCenterController::class, 'markAsRead'])
            ->name('notification-center.read');
        Route::post('/notification-center/db/{notificationId}/read', [NotificationCenterController::class, 'markDbAsRead'])
            ->name('notification-center.db.read');
        Route::post('/notification-center/read-all', [NotificationCenterController::class, 'markAllAsRead'])
            ->name('notification-center.read-all');
    });

    // ============================================
    // Export Routes
    // ============================================
    Route::middleware(['role:admin,trainer', 'permission:view reports|export reports'])->group(function () {
        Route::post('/exports/{type}', [ExportController::class, 'export'])
            ->name('exports.export');
        Route::get('/exports/{export}/status', [ExportController::class, 'status'])
            ->name('exports.status');
        Route::get('/exports/{export}/download', [ExportController::class, 'download'])
            ->name('exports.download');
    });

});
