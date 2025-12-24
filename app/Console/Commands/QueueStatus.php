<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class QueueStatus extends Command
{
    protected $signature = 'queue:status';
    protected $description = 'Check queue worker status and pending jobs';

    public function handle(): int
    {
        try {
            $pendingJobs = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();

            $this->info("Queue Status:");
            $this->line("Pending jobs: {$pendingJobs}");
            $this->line("Failed jobs: {$failedJobs}");

            if ($pendingJobs > 0) {
                $this->warn("⚠️  There are {$pendingJobs} pending jobs. Make sure queue worker is running!");
                $this->line("Start queue worker: ./start-queue.sh");
            } else {
                $this->info("✓ No pending jobs");
            }

            if ($failedJobs > 0) {
                $this->error("⚠️  There are {$failedJobs} failed jobs.");
                $this->line("View failed jobs: php artisan queue:failed");
                $this->line("Retry failed jobs: php artisan queue:retry all");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error checking queue status: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

