<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

// Debug route to check payment data (only in development)
if (app()->environment('local')) {
    Route::get('/debug/payment-data', function () {
        $paymentData = session('payment_data', []);

        return response()->json([
            'has_payment_data' => !empty($paymentData),
            'payment_data' => $paymentData,
            'has_client_secret' => isset($paymentData['client_secret']),
            'client_secret' => $paymentData['client_secret'] ?? null,
            'gateway' => $paymentData['gateway'] ?? null,
            'subscription_id' => $paymentData['subscription_id'] ?? null,
            'customer_id' => $paymentData['customer_id'] ?? null,
            'status' => $paymentData['status'] ?? null,
        ]);
    })->middleware('auth');

    // Debug route to test timezone functionality
    Route::get('/debug/timezone-test', function () {
        $testDate = now();

        return response()->json([
            'server_timezone' => config('app.timezone'),
            'current_time' => $testDate->toISOString(),
            'user_timezone' => get_user_timezone(),
            'available_timezones' => array_slice(get_available_timezones(), 0, 5), // Show first 5
            'timezone_functions_working' => true,

            'formats' => [
                'admin' => [
                    'date' => format_date_admin($testDate),
                    'datetime' => format_datetime_admin($testDate),
                ],
                'member' => [
                    'date' => format_date_member($testDate),
                    'datetime' => format_datetime_member($testDate),
                ],
                'frontend' => [
                    'date' => format_date_frontend($testDate),
                    'datetime' => format_datetime_frontend($testDate),
                ],
                'api' => [
                    'datetime' => format_datetime_api($testDate),
                ],
            ],

            'relative_formats' => [
                'recent' => format_date_relative(now()->subHours(2)),
                'today' => format_date_relative(now()->subMinutes(30)),
                'old' => format_date_relative(now()->subDays(10)),
            ],
        ]);
    });

    // Debug route to test user timezone data
    Route::get('/debug/user-timezones', function () {
        $users = \App\Models\User::select('id', 'name', 'email', 'timezone')->take(10)->get();

        return response()->json([
            'total_users' => \App\Models\User::count(),
            'users_with_timezones' => \App\Models\User::whereNotNull('timezone')->count(),
            'sample_users' => $users,
            'timezone_distribution' => \App\Models\User::selectRaw('timezone, COUNT(*) as count')
                ->whereNotNull('timezone')
                ->groupBy('timezone')
                ->orderBy('count', 'desc')
                ->take(10)
                ->get(),
        ]);
    });

    // Debug route to test check-in time display
    Route::get('/debug/checkin-time-test', function () {
        $user = auth()->user() ?? \App\Models\User::first();
        $activity = \App\Models\ActivityLog::latest()->first();

        $testTime = $activity ? $activity->check_in_time : now();

        return response()->json([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'user_timezone' => $user?->timezone,
            'detected_user_timezone' => get_user_timezone($user),
            'app_timezone' => config('app.timezone'),
            'test_time_raw' => $testTime?->toISOString(),
            'test_time_utc' => $testTime?->utc()->format('h:i A T'),
            'test_time_user_tz' => $testTime?->timezone(get_user_timezone($user))->format('h:i A T'),
            'formatted_for_member' => format_time_member($testTime),
            'formatted_for_admin' => format_time_admin($testTime),
            'server_time_now' => now()->format('h:i A T'),
            'user_tz_time_now' => now()->timezone(get_user_timezone($user))->format('h:i A T'),
            'session_timezone' => session('user_timezone'),
            'browser_timezone' => session('browser_timezone'),
        ]);
    });

    // Quick timezone test
    Route::get('/debug/timezone-quick', function () {
        $user = auth()->user();
        $now = now();
        $activity = \App\Models\ActivityLog::latest()->first();

        return [
            'user' => $user?->name,
            'user_timezone' => $user?->timezone,
            'detected_timezone' => get_user_timezone($user),
            'current_time_utc' => $now->utc()->format('h:i A'),
            'current_time_user' => $now->timezone(get_user_timezone($user))->format('h:i A'),
            'formatted_member' => format_time_member($now),
            'formatted_admin' => format_time_admin($now),
            'sample_activity_checkin' => $activity ? [
                'raw_time' => $activity->check_in_time?->format('H:i'),
                'admin_formatted' => format_time_admin($activity->check_in_time),
                'member_formatted' => format_time_member($activity->check_in_time),
            ] : null,
            'functions_available' => [
                'format_time_member' => function_exists('format_time_member'),
                'format_time_admin' => function_exists('format_time_admin'),
                'get_user_timezone' => function_exists('get_user_timezone'),
            ],
        ];
    });
}

