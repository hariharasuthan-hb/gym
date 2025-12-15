<?php

namespace App\Traits;

/**
 * Trait for models that need formatted date accessors.
 * 
 * To use this trait, add it to your model:
 * use HasFormattedDates;
 * 
 * Then define accessors in your model like:
 * 
 * public function getFormattedStartDateAttribute()
 * {
 *     return format_date($this->start_date);
 * }
 *
 * public function getFormattedStartDateForUserAttribute()
 * {
 *     return format_date_for_user($this->start_date);
 * }
 * 
 * Usage: $model->formatted_start_date
 * Usage: $model->formatted_start_date_for_user
 */
trait HasFormattedDates
{
    /**
     * Get formatted date in user's timezone.
     *
     * @param string $column
     * @return string
     */
    public function getFormattedDateForUser(string $column): string
    {
        return format_date_for_user($this->{$column});
    }

    /**
     * Get formatted datetime in user's timezone.
     *
     * @param string $column
     * @return string
     */
    public function getFormattedDateTimeForUser(string $column): string
    {
        return format_datetime_for_user($this->{$column});
    }

    /**
     * Get formatted time in user's timezone.
     *
     * @param string $column
     * @return string
     */
    public function getFormattedTimeForUser(string $column): string
    {
        return format_time_for_user($this->{$column});
    }

    /**
     * Get formatted date for admin (UTC/server timezone).
     *
     * @param string $column
     * @return string
     */
    public function getFormattedDateAdmin(string $column): string
    {
        return format_date_admin($this->{$column});
    }

    /**
     * Get formatted datetime for admin.
     *
     * @param string $column
     * @return string
     */
    public function getFormattedDateTimeAdmin(string $column): string
    {
        return format_datetime_admin($this->{$column});
    }

    /**
     * Get formatted date for member (user's timezone).
     *
     * @param string $column
     * @return string
     */
    public function getFormattedDateMember(string $column): string
    {
        return format_date_member($this->{$column});
    }

    /**
     * Get formatted datetime for member.
     *
     * @param string $column
     * @return string
     */
    public function getFormattedDateTimeMember(string $column): string
    {
        return format_datetime_member($this->{$column});
    }

    /**
     * Get formatted date for frontend.
     *
     * @param string $column
     * @return string
     */
    public function getFormattedDateFrontend(string $column): string
    {
        return format_date_frontend($this->{$column});
    }

    /**
     * Get formatted datetime for frontend.
     *
     * @param string $column
     * @return string
     */
    public function getFormattedDateTimeFrontend(string $column): string
    {
        return format_datetime_frontend($this->{$column});
    }

    /**
     * Get formatted date for API (ISO 8601).
     *
     * @param string $column
     * @return string|null
     */
    public function getFormattedDateApi(string $column): ?string
    {
        return format_date_api($this->{$column});
    }

    /**
     * Get formatted datetime for API (ISO 8601).
     *
     * @param string $column
     * @return string|null
     */
    public function getFormattedDateTimeApi(string $column): ?string
    {
        return format_datetime_api($this->{$column});
    }
}

