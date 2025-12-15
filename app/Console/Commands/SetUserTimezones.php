<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SetUserTimezones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:set-timezones {--timezone=UTC : Default timezone to set} {--force : Force update even if timezone already set}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set timezone for users who don\'t have one set';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $defaultTimezone = $this->option('timezone');
        $force = $this->option('force');

        // Validate timezone
        if (!in_array($defaultTimezone, array_keys(config('date_formats.available_timezones', [])))) {
            $this->error("Invalid timezone: {$defaultTimezone}");
            return 1;
        }

        $query = User::query();

        if (!$force) {
            $query->whereNull('timezone');
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            $this->info('No users found that need timezone updates.');
            return 0;
        }

        $this->info("Updating timezone for {$users->count()} users...");

        $bar = $this->output->createProgressBar($users->count());

        foreach ($users as $user) {
            $user->update(['timezone' => $defaultTimezone]);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Successfully set timezone '{$defaultTimezone}' for {$users->count()} users.");

        return 0;
    }
}
