<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Date Format Configuration
    |--------------------------------------------------------------------------
    |
    | Define standard date formats used throughout the application.
    | These formats ensure consistency across all pages.
    |
    */

    // Standard date formats
    'display' => 'M d, Y',           // Jan 15, 2024 - For general display
    'display_full' => 'F d, Y',     // January 15, 2024 - Full month name
    'display_short' => 'M d, Y',    // Jan 15, 2024 - Short month
    'display_time' => 'M d, Y h:i A', // Jan 15, 2024 02:30 PM - With time
    'display_datetime' => 'M d, Y · h:i A', // Jan 15, 2024 · 02:30 PM - With separator
    
    // Form input formats
    'input' => 'Y-m-d',             // 2024-01-15 - For date inputs
    'input_datetime' => 'Y-m-d H:i:s', // 2024-01-15 14:30:00 - For datetime inputs
    
    // Time only formats
    'time' => 'h:i A',              // 02:30 PM - 12 hour format
    'time_24' => 'H:i',             // 14:30 - 24 hour format
    
    // Table/list formats
    'table_date' => 'M d, Y',       // Jan 15, 2024 - For tables
    'table_datetime' => 'M d, Y h:i A', // Jan 15, 2024 02:30 PM - For tables with time
    
    // Relative formats (for recent dates)
    'relative' => true,             // Show "2 days ago" for recent dates
    'relative_threshold' => 7,      // Days threshold for relative format

    /*
    |--------------------------------------------------------------------------
    | Timezone Configuration
    |--------------------------------------------------------------------------
    |
    | Timezone settings for date formatting. Users can have their own timezone
    | preferences stored in their profile, otherwise fallback to app timezone.
    |
    */

    // Default application timezone (used when user timezone is not set)
    'default_timezone' => env('APP_TIMEZONE', 'UTC'),

    // Available timezones for user selection
    'available_timezones' => [
        'UTC' => 'UTC',
        'America/New_York' => 'Eastern Time (ET)',
        'America/Chicago' => 'Central Time (CT)',
        'America/Denver' => 'Mountain Time (MT)',
        'America/Los_Angeles' => 'Pacific Time (PT)',
        'America/Anchorage' => 'Alaska Time (AKT)',
        'Pacific/Honolulu' => 'Hawaii Time (HT)',
        'Europe/London' => 'London (GMT/BST)',
        'Europe/Paris' => 'Paris (CET/CEST)',
        'Europe/Berlin' => 'Berlin (CET/CEST)',
        'Europe/Rome' => 'Rome (CET/CEST)',
        'Europe/Madrid' => 'Madrid (CET/CEST)',
        'Europe/Amsterdam' => 'Amsterdam (CET/CEST)',
        'Asia/Tokyo' => 'Tokyo (JST)',
        'Asia/Shanghai' => 'Shanghai (CST)',
        'Asia/Hong_Kong' => 'Hong Kong (HKT)',
        'Asia/Singapore' => 'Singapore (SGT)',
        'Asia/Kolkata' => 'India (IST)',
        'Asia/Dubai' => 'Dubai (GST)',
        'Australia/Sydney' => 'Sydney (AEDT/AEST)',
        'Australia/Melbourne' => 'Melbourne (AEDT/AEST)',
        'Pacific/Auckland' => 'Auckland (NZDT/NZST)',
    ],

    // User timezone detection priority
    'timezone_detection' => [
        'user_preference',    // User's saved timezone preference
        'browser',           // Browser timezone (if enabled)
        'session',           // Session-stored timezone
        'default',           // Application default timezone
    ],

    // Enable browser timezone detection
    'browser_timezone_detection' => true,

    // Cache timezone conversions for performance
    'cache_timezone_conversions' => true,
    'timezone_cache_ttl' => 3600, // 1 hour in seconds

    /*
    |--------------------------------------------------------------------------
    | User-Specific Date Formatting
    |--------------------------------------------------------------------------
    |
    | Different date formats for different user types and contexts.
    | Supports timezone-aware formatting.
    |
    */

    // Admin-specific formats (admin/trainer). Use local time in simple format (no seconds, no TZ code).
    'admin' => [
        'date' => 'M d, Y',
        'datetime' => 'M d, Y h:i A',  // e.g. Dec 15, 2025 06:30 PM
        'time' => 'h:i A',             // e.g. 06:30 PM
        'relative' => false,  // Admins usually want exact times
    ],

    // Member-specific formats (user's timezone)
    'member' => [
        'date' => 'M d, Y',
        'datetime' => 'M d, Y h:i A',  // e.g. Dec 15, 2025 06:30 PM
        'time' => 'h:i A',
        'relative' => true,   // Members prefer relative dates
    ],

    // Frontend/public formats (user's timezone or default)
    'frontend' => [
        'date' => 'M d, Y',
        'datetime' => 'M d, Y h:i A',
        'time' => 'h:i A',
        'relative' => true,
    ],

    // API formats (ISO 8601 with timezone)
    'api' => [
        'datetime' => 'c',  // ISO 8601 format
        'date' => 'Y-m-d',
        'time' => 'H:i:s',
    ],

    /*
    |--------------------------------------------------------------------------
    | Date Display Preferences
    |--------------------------------------------------------------------------
    |
    | User preferences for date display that can be stored in user profiles.
    |
    */

    'user_preferences' => [
        'timezone' => null,           // User's preferred timezone
        'date_format' => 'default',   // 'default', 'us', 'eu', 'iso'
        'time_format' => '12h',       // '12h' or '24h'
        'relative_dates' => true,     // Show "2 days ago" for recent dates
        'week_starts_on' => 'monday', // 'monday' or 'sunday'
        'first_day_of_week' => 1,     // 0 = Sunday, 1 = Monday
    ],

    // Alternative date formats for different regions
    'regional_formats' => [
        'us' => [
            'date' => 'm/d/Y',
            'datetime' => 'm/d/Y g:i A',
        ],
        'eu' => [
            'date' => 'd/m/Y',
            'datetime' => 'd/m/Y H:i',
        ],
        'iso' => [
            'date' => 'Y-m-d',
            'datetime' => 'Y-m-d H:i:s',
        ],
    ],
];

