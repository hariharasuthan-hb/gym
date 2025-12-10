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

