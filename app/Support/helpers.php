<?php

use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Carbon;

if (!function_exists('app_format_date')) {
    /**
     * Format any date value into a unified display string.
     */
    function app_format_date(
        DateTimeInterface|string|int|null $value,
        string $format = 'd M Y · h:i A'
    ): string {
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

        return $date
            ->timezone(config('app.timezone'))
            ->format($format);
    }
}

