<?php

namespace App\Console\Commands;

use App\Jobs\CleanupWorkoutPlanVideosJob;
use Illuminate\Console\Command;

class CleanupWorkoutPlanVideos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workout-plans:cleanup-videos {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up workout videos for ended workout plans';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('This will delete all workout videos for ended workout plans. Continue?')) {
            $this->info('Cleanup cancelled.');
            return;
        }

        $this->info('Dispatching cleanup job...');

        CleanupWorkoutPlanVideosJob::dispatch();

        $this->info('Cleanup job has been dispatched successfully.');
        $this->info('The job will run in the background and clean up videos for ended workout plans.');
    }
}
