<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserTimezoneSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     * Updates existing users with timezone data if they don't have one set.
     */
    public function run(): void
    {
        $this->command->info('Updating existing users with timezone data...');

        // Common timezones for different regions
        $timezones = [
            'UTC',
            'America/New_York',
            'America/Chicago',
            'America/Denver',
            'America/Los_Angeles',
            'Europe/London',
            'Europe/Paris',
            'Europe/Berlin',
            'Europe/Rome',
            'Asia/Tokyo',
            'Asia/Shanghai',
            'Asia/Kolkata',
            'Australia/Sydney',
            'Pacific/Auckland',
        ];

        // Get users without timezone set
        $usersWithoutTimezone = User::whereNull('timezone')->get();

        if ($usersWithoutTimezone->isEmpty()) {
            $this->command->info('All users already have timezone data set.');
            return;
        }

        $this->command->info("Found {$usersWithoutTimezone->count()} users without timezone data.");

        // Assign timezones based on user roles or randomly
        foreach ($usersWithoutTimezone as $user) {
            $timezone = $this->getTimezoneForUser($user, $timezones);
            $user->update(['timezone' => $timezone]);
        }

        $this->command->info('Successfully updated timezone data for all users.');
    }

    /**
     * Determine appropriate timezone for a user.
     */
    private function getTimezoneForUser(User $user, array $timezones): string
    {
        // Admin users typically use UTC for consistency
        if ($user->hasRole('admin')) {
            return 'UTC';
        }

        // For demo purposes, assign some regional preferences based on user data
        // In a real application, you might use IP geolocation or user preferences

        // You can extend this logic based on user location data, IP, etc.
        // For now, we'll assign randomly but with some logic

        $name = strtolower($user->name ?? '');

        // Some basic heuristics (you can improve this)
        if (str_contains($name, 'john') || str_contains($name, 'mike') || str_contains($name, 'david')) {
            return 'America/New_York'; // East Coast US
        }

        if (str_contains($name, 'anna') || str_contains($name, 'hans') || str_contains($name, 'franz')) {
            return 'Europe/Berlin'; // German names
        }

        if (str_contains($name, 'yuki') || str_contains($name, 'taro') || str_contains($name, 'sato')) {
            return 'Asia/Tokyo'; // Japanese names
        }

        // Default: random selection
        return $timezones[array_rand($timezones)];
    }
}
