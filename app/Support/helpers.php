<?php

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

if (!function_exists('app_format_date')) {
    /**
     * Format any date value into a unified display string.
     * Uses the standard display format from config.
     */
    function app_format_date(
        DateTimeInterface|string|int|null $value,
        ?string $format = null
    ): string {
        if (is_null($value) || $value === '') {
            return '-';
        }

        $format = $format ?? config('date_formats.display', 'M d, Y');

        if ($value instanceof DateTimeInterface) {
            $date = Carbon::instance($value);
        } elseif (is_numeric($value)) {
            $date = Carbon::createFromTimestamp((int) $value);
        } else {
            $date = Carbon::parse($value);
        }

        return $date
            ->timezone(config('app.timezone'))
            ->format($format);
    }
}

if (!function_exists('format_date')) {
    /**
     * Format date using the standard display format.
     * Alias for app_format_date with default format.
     */
    function format_date(DateTimeInterface|string|int|null $value): string
    {
        return app_format_date($value);
    }
}

if (!function_exists('format_datetime')) {
    /**
     * Format date with time using the standard datetime format.
     */
    function format_datetime(DateTimeInterface|string|int|null $value): string
    {
        return app_format_date($value, config('date_formats.display_datetime', 'M d, Y · h:i A'));
    }
}

if (!function_exists('format_date_full')) {
    /**
     * Format date with full month name.
     */
    function format_date_full(DateTimeInterface|string|int|null $value): string
    {
        return app_format_date($value, config('date_formats.display_full', 'F d, Y'));
    }
}

if (!function_exists('format_time')) {
    /**
     * Format time only (12-hour format with AM/PM).
     */
    function format_time(DateTimeInterface|string|int|null $value): string
    {
        return app_format_date($value, config('date_formats.time', 'h:i A'));
    }
}

if (!function_exists('format_date_relative')) {
    /**
     * Format date with relative format for recent dates.
     * Shows "2 days ago" for recent dates, otherwise standard format.
     */
    function format_date_relative(DateTimeInterface|string|int|null $value): string
    {
        if (is_null($value) || $value === '') {
            return '-';
        }

        if ($value instanceof DateTimeInterface) {
            $date = Carbon::instance($value);
        } elseif (is_numeric($value)) {
            $date = Carbon::createFromTimestamp((int) $value);
        } else {
            $date = Carbon::parse($value);
        }

        $date = $date->timezone(config('app.timezone'));
        $threshold = config('date_formats.relative_threshold', 7);
        
        // Show relative format if within threshold
        if ($date->isAfter(Carbon::now()->subDays($threshold))) {
            return $date->diffForHumans();
        }
        
        // Otherwise use standard format
        return $date->format(config('date_formats.display', 'M d, Y'));
    }
}

if (!function_exists('format_date_input')) {
    /**
     * Format date for HTML input fields (Y-m-d format).
     */
    function format_date_input(DateTimeInterface|string|int|null $value): ?string
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            $date = Carbon::instance($value);
        } elseif (is_numeric($value)) {
            $date = Carbon::createFromTimestamp((int) $value);
        } else {
            $date = Carbon::parse($value);
        }

        return $date->format(config('date_formats.input', 'Y-m-d'));
    }
}

if (!function_exists('file_url')) {
    /**
     * Build a fully-qualified URL for a stored file path.
     */
    function file_url(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        // Already a full URL
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return url('storage/' . ltrim($path, '/'));
    }
}

if (!function_exists('render_rich_content')) {
    /**
     * Render HTML content safely for rich text editor content
     * Used for displaying content created with the rich text editor
     *
     * @param string|null $content
     * @return string
     */
    function render_rich_content(?string $content): string
    {
        if (empty($content)) {
            return '';
        }

        // For rich text editor content, render HTML directly
        // Content comes from trusted admin users via the rich text editor
        return $content;
    }
}

if (!function_exists('render_content')) {
    /**
     * Render content for display - auto-detects if it's rich text or plain text
     * Rich text (with HTML) is rendered as HTML, plain text gets line breaks converted
     *
     * @param string|null $content
     * @return string
     */
    function render_content(?string $content): string
    {
        if (empty($content)) {
            return '';
        }

        // Check if content contains HTML tags
        if (preg_match('/<[^>]+>/', $content)) {
            // Contains HTML - render as rich content
            return render_rich_content($content);
        } else {
            // Plain text - convert newlines to breaks and escape HTML
            return nl2br(e($content));
        }
    }
}

/*
|--------------------------------------------------------------------------
| Timezone-Aware Date Formatting Functions
|--------------------------------------------------------------------------
|
| Enhanced date formatting functions that support user timezones,
| different user types (admin/member/frontend), and regional preferences.
|
*/

if (!function_exists('get_user_timezone')) {
    /**
     * Get the appropriate timezone for the current user.
     * Priority: user preference > browser > session > default
     *
     * @param mixed $user User model or null for current user
     * @return string
     */
    function get_user_timezone($user = null): string
    {
        $user = $user ?? auth()->user();

        // 1. User preference (stored in user profile)
        //    If it's just the app default (e.g. seeded as UTC), still allow browser/session to override.
        if ($user && isset($user->timezone) && $user->timezone && $user->timezone !== config('app.timezone', 'UTC')) {
            return $user->timezone;
        }

        // 2. Browser timezone (if enabled and available)
        if (config('date_formats.browser_timezone_detection')) {
            $browserTimezone = session('browser_timezone');
            if ($browserTimezone && in_array($browserTimezone, array_keys(config('date_formats.available_timezones')))) {
                return $browserTimezone;
            }
        }

        // 3. Session timezone
        $sessionTimezone = session('user_timezone');
        if ($sessionTimezone && in_array($sessionTimezone, array_keys(config('date_formats.available_timezones')))) {
            return $sessionTimezone;
        }

        // 4. Fallback: user explicit timezone equal to app timezone (e.g. legacy data)
        if ($user && isset($user->timezone) && $user->timezone) {
            return $user->timezone;
        }

        // 5. Default application timezone
        return config('date_formats.default_timezone', config('app.timezone', 'UTC'));
    }
}

if (!function_exists('get_user_date_format')) {
    /**
     * Get the appropriate date format configuration for the current user type.
     *
     * @param string|null $type 'admin', 'member', 'frontend', or 'api'
     * @return array
     */
    function get_user_date_format(?string $type = null): array
    {
        if (!$type) {
            $user = auth()->user();
            if ($user && $user->hasRole('admin')) {
                $type = 'admin';
            } elseif ($user && $user->hasRole('member')) {
                $type = 'member';
            } else {
                $type = 'frontend';
            }
        }

        return config("date_formats.{$type}", config('date_formats.frontend', []));
    }
}

if (!function_exists('format_date_for_user')) {
    /**
     * Format date with timezone awareness for specific user type.
     * Enhanced version of app_format_date with user-specific formatting.
     *
     * @param DateTimeInterface|string|int|null $value
     * @param string|null $format Specific format override
     * @param string|null $userType 'admin', 'member', 'frontend', or 'api'
     * @param mixed $user Specific user or null for current user
     * @return string
     */
    function format_date_for_user(
        DateTimeInterface|string|int|null $value,
        ?string $format = null,
        ?string $userType = null,
        $user = null
    ): string {
        if (is_null($value) || $value === '') {
            return '-';
        }

        // Get user-specific timezone and format preferences
        $timezone = get_user_timezone($user);
        $userFormats = get_user_date_format($userType);
        $format = $format ?? ($userFormats['date'] ?? config('date_formats.display', 'M d, Y'));

        // Create Carbon instance with proper timezone
        if ($value instanceof DateTimeInterface) {
            $date = Carbon::instance($value);
        } elseif (is_numeric($value)) {
            $date = Carbon::createFromTimestamp((int) $value);
        } else {
            $date = Carbon::parse($value);
        }

        // Convert to user's timezone
        $date = $date->timezone($timezone);

        // Check if relative format should be used
        if ($userFormats['relative'] ?? config('date_formats.relative', false)) {
            $threshold = config('date_formats.relative_threshold', 7);
            if ($date->isAfter(Carbon::now($timezone)->subDays($threshold))) {
                return $date->diffForHumans();
            }
        }

        return $date->format($format);
    }
}

if (!function_exists('format_datetime_for_user')) {
    /**
     * Format datetime with timezone for specific user type.
     *
     * @param DateTimeInterface|string|int|null $value
     * @param string|null $userType 'admin', 'member', 'frontend', or 'api'
     * @param mixed $user Specific user or null for current user
     * @return string
     */
    function format_datetime_for_user(
        DateTimeInterface|string|int|null $value,
        ?string $userType = null,
        $user = null
    ): string {
        if (is_null($value) || $value === '') {
            return '-';
        }

        $userFormats = get_user_date_format($userType);
        $format = $userFormats['datetime'] ?? config('date_formats.display_datetime', 'M d, Y · h:i A');

        return format_date_for_user($value, $format, $userType, $user);
    }
}

if (!function_exists('format_time_for_user')) {
    /**
     * Format time only with timezone for specific user type.
     *
     * @param DateTimeInterface|string|int|null $value
     * @param string|null $userType 'admin', 'member', 'frontend', or 'api'
     * @param mixed $user Specific user or null for current user
     * @return string
     */
    function format_time_for_user(
        DateTimeInterface|string|int|null $value,
        ?string $userType = null,
        $user = null
    ): string {
        if (is_null($value) || $value === '') {
            return '-';
        }

        $userFormats = get_user_date_format($userType);
        $format = $userFormats['time'] ?? config('date_formats.time', 'h:i A');

        return format_date_for_user($value, $format, $userType, $user);
    }
}

if (!function_exists('format_date_admin')) {
    /**
     * Format date for admin users (UTC/server timezone, exact times).
     *
     * @param DateTimeInterface|string|int|null $value
     * @return string
     */
    function format_date_admin(DateTimeInterface|string|int|null $value): string
    {
        return format_date_for_user($value, null, 'admin');
    }
}

if (!function_exists('format_datetime_admin')) {
    /**
     * Format datetime for admin users.
     *
     * @param DateTimeInterface|string|int|null $value
     * @return string
     */
    function format_datetime_admin(DateTimeInterface|string|int|null $value): string
    {
        return format_datetime_for_user($value, 'admin');
    }
}

if (!function_exists('format_time_admin')) {
    /**
     * Format time only for admin users.
     *
     * @param DateTimeInterface|string|int|null $value
     * @return string
     */
    function format_time_admin(DateTimeInterface|string|int|null $value): string
    {
        return format_time_for_user($value, 'admin');
    }
}

if (!function_exists('format_date_member')) {
    /**
     * Format date for member users (user's timezone, relative dates).
     *
     * @param DateTimeInterface|string|int|null $value
     * @return string
     */
    function format_date_member(DateTimeInterface|string|int|null $value): string
    {
        return format_date_for_user($value, null, 'member');
    }
}

if (!function_exists('format_datetime_member')) {
    /**
     * Format datetime for member users.
     *
     * @param DateTimeInterface|string|int|null $value
     * @return string
     */
    function format_datetime_member(DateTimeInterface|string|int|null $value): string
    {
        return format_datetime_for_user($value, 'member');
    }
}

if (!function_exists('format_time_member')) {
    /**
     * Format time only for member users.
     *
     * @param DateTimeInterface|string|int|null $value
     * @return string
     */
    function format_time_member(DateTimeInterface|string|int|null $value): string
    {
        return format_time_for_user($value, 'member');
    }
}

if (!function_exists('format_date_frontend')) {
    /**
     * Format date for frontend/public users.
     *
     * @param DateTimeInterface|string|int|null $value
     * @return string
     */
    function format_date_frontend(DateTimeInterface|string|int|null $value): string
    {
        return format_date_for_user($value, null, 'frontend');
    }
}

if (!function_exists('format_datetime_frontend')) {
    /**
     * Format datetime for frontend/public users.
     *
     * @param DateTimeInterface|string|int|null $value
     * @return string
     */
    function format_datetime_frontend(DateTimeInterface|string|int|null $value): string
    {
        return format_datetime_for_user($value, 'frontend');
    }
}

if (!function_exists('format_date_api')) {
    /**
     * Format date for API responses (ISO 8601).
     *
     * @param DateTimeInterface|string|int|null $value
     * @return string|null
     */
    function format_date_api(DateTimeInterface|string|int|null $value): ?string
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        return format_date_for_user($value, config('date_formats.api.date'), 'api');
    }
}

if (!function_exists('format_datetime_api')) {
    /**
     * Format datetime for API responses (ISO 8601).
     *
     * @param DateTimeInterface|string|int|null $value
     * @return string|null
     */
    function format_datetime_api(DateTimeInterface|string|int|null $value): ?string
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        return format_date_for_user($value, config('date_formats.api.datetime'), 'api');
    }
}

if (!function_exists('get_available_timezones')) {
    /**
     * Get list of available timezones for user selection.
     *
     * @return array
     */
    function get_available_timezones(): array
    {
        return config('date_formats.available_timezones', []);
    }
}

if (!function_exists('set_user_timezone')) {
    /**
     * Set user's timezone preference.
     *
     * @param string $timezone
     * @return bool
     */
    function set_user_timezone(string $timezone): bool
    {
        if (!in_array($timezone, array_keys(get_available_timezones()))) {
            return false;
        }

        $user = auth()->user();
        if ($user) {
            $user->update(['timezone' => $timezone]);
        }

        session(['user_timezone' => $timezone]);
        return true;
    }
}

if (!function_exists('detect_browser_timezone')) {
    /**
     * Detect and store browser timezone from JavaScript.
     *
     * @param string $timezone
     * @return bool
     */
    function detect_browser_timezone(string $timezone): bool
    {
        if (!config('date_formats.browser_timezone_detection')) {
            return false;
        }

        if (!in_array($timezone, array_keys(get_available_timezones()))) {
            return false;
        }

        session(['browser_timezone' => $timezone]);

        // Also save to user profile if user is logged in
        $user = auth()->user();
        if ($user && !$user->timezone) {
            $user->update(['timezone' => $timezone]);
        }

        return true;
    }
}

if (!function_exists('set_user_timezone_from_offset')) {
    /**
     * Set user timezone based on UTC offset (from JavaScript).
     *
     * @param int $offsetMinutes UTC offset in minutes
     * @return bool
     */
    function set_user_timezone_from_offset(int $offsetMinutes): bool
    {
        // Common timezone mappings based on UTC offset
        $timezoneMap = [
            -480 => 'America/Los_Angeles',    // PST/PDT
            -420 => 'America/Denver',          // MST/MDT
            -360 => 'America/Chicago',         // CST/CDT
            -300 => 'America/New_York',        // EST/EDT
            -240 => 'America/Halifax',         // AST/ADT
            0 => 'UTC',
            60 => 'Europe/London',             // GMT/BST
            120 => 'Europe/Paris',             // CET/CEST
            180 => 'Europe/Moscow',            // MSK
            330 => 'Asia/Kolkata',             // IST
            480 => 'Asia/Shanghai',            // CST
            540 => 'Asia/Tokyo',               // JST
            600 => 'Australia/Melbourne',      // AEDT/AEST
        ];

        $timezone = $timezoneMap[$offsetMinutes] ?? 'UTC';

        return set_user_timezone($timezone);
    }
}

if (!function_exists('get_system_timezone')) {
    /**
     * Get the system's detected timezone.
     * This tries to detect the timezone from various sources.
     *
     * @return string
     */
    function get_system_timezone(): string
    {
        // Try to get from system environment
        $systemTz = getenv('TZ') ?: date_default_timezone_get();

        // Validate it's a valid timezone
        if ($systemTz && in_array($systemTz, timezone_identifiers_list())) {
            return $systemTz;
        }

        // Fallback to application timezone
        return config('app.timezone', 'UTC');
    }
}

if (!function_exists('ensure_user_has_timezone')) {
    /**
     * Ensure the current user has a timezone set.
     * If not, try to detect and set one.
     *
     * @return string The user's timezone
     */
    function ensure_user_has_timezone(): string
    {
        $user = auth()->user();

        if (!$user) {
            return get_system_timezone();
        }

        $timezone = get_user_timezone($user);

        // If user doesn't have a timezone, try to set one
        if ($timezone === config('app.timezone', 'UTC')) {
            // Try browser timezone from session
            $browserTz = session('browser_timezone');
            if ($browserTz && in_array($browserTz, array_keys(get_available_timezones()))) {
                $user->update(['timezone' => $browserTz]);
                return $browserTz;
            }

            // Try to detect from system
            $systemTz = get_system_timezone();
            if ($systemTz !== config('app.timezone', 'UTC')) {
                $user->update(['timezone' => $systemTz]);
                return $systemTz;
            }
        }

        return $timezone;
    }
}

/*
|--------------------------------------------------------------------------
| Centralized Date Formatting Functions (No Code Repetition)
|--------------------------------------------------------------------------
|
| These functions automatically detect user context and apply appropriate
| formatting without needing to manually specify admin/member/frontend.
|
*/

if (!function_exists('format_date_smart')) {
    /**
     * Smart date formatting - automatically detects context and applies appropriate formatting.
     * No need to manually specify admin/member/frontend - it detects automatically.
     *
     * @param DateTimeInterface|string|int|null $value
     * @param string|null $format Override specific format if needed
     * @return string
     */
    function format_date_smart(DateTimeInterface|string|int|null $value, ?string $format = null): string
    {
        if (is_null($value) || $value === '') {
            return '-';
        }

        $user = auth()->user();
        $context = 'frontend'; // Default

        // Auto-detect context based on authenticated user
        // Treat trainers the same as admins for date display (no relative text)
        if ($user) {
            if ($user->hasRole('admin') || $user->hasRole('trainer')) {
                $context = 'admin';
            } elseif ($user->hasRole('member')) {
                $context = 'member';
            }
        }

        // If specific format provided, use it with detected context
        if ($format) {
            return format_date_for_user($value, $format, $context);
        }

        // Use context-appropriate formatting
        return match($context) {
            'admin' => format_date_admin($value),
            'member' => format_date_member($value),
            'frontend' => format_date_frontend($value),
            default => format_date($value),
        };
    }
}

if (!function_exists('format_datetime_smart')) {
    /**
     * Smart datetime formatting - automatically detects context.
     *
     * @param DateTimeInterface|string|int|null $value
     * @param string|null $format Override specific format if needed
     * @return string
     */
    function format_datetime_smart(DateTimeInterface|string|int|null $value, ?string $format = null): string
    {
        if (is_null($value) || $value === '') {
            return '-';
        }

        $user = auth()->user();
        $context = 'frontend'; // Default

        // Auto-detect context based on authenticated user
        // Treat trainers the same as admins for datetime display
        if ($user) {
            if ($user->hasRole('admin') || $user->hasRole('trainer')) {
                $context = 'admin';
            } elseif ($user->hasRole('member')) {
                $context = 'member';
            }
        }

        // If specific format provided, use it with detected context
        if ($format) {
            return format_date_for_user($value, $format, $context);
        }

        // Use context-appropriate formatting
        return match($context) {
            'admin' => format_datetime_admin($value),
            'member' => format_datetime_member($value),
            'frontend' => format_datetime_frontend($value),
            default => format_datetime($value),
        };
    }
}

if (!function_exists('format_time_smart')) {
    /**
     * Smart time-only formatting - automatically detects context.
     *
     * @param DateTimeInterface|string|int|null $value
     * @param string|null $format Override specific format if needed
     * @return string
     */
    function format_time_smart(DateTimeInterface|string|int|null $value, ?string $format = null): string
    {
        if (is_null($value) || $value === '') {
            return '-';
        }

        $user = auth()->user();
        $context = 'frontend'; // Default

        // Auto-detect context based on authenticated user
        // Treat trainers the same as admins for time display
        if ($user) {
            if ($user->hasRole('admin') || $user->hasRole('trainer')) {
                $context = 'admin';
            } elseif ($user->hasRole('member')) {
                $context = 'member';
            }
        }

        // If specific format provided, use it with detected context
        if ($format) {
            return format_date_for_user($value, $format, $context);
        }

        // Use context-appropriate formatting
        return match($context) {
            'admin' => format_time_admin($value),
            'member' => format_time_member($value),
            'frontend' => format_time_frontend($value),
            default => format_time($value),
        };
    }
}

if (!function_exists('format_time_frontend')) {
    /**
     * Format time only for frontend/public users.
     *
     * @param DateTimeInterface|string|int|null $value
     * @return string
     */
    function format_time_frontend(DateTimeInterface|string|int|null $value): string
    {
        return format_time_for_user($value, 'frontend');
    }
}

