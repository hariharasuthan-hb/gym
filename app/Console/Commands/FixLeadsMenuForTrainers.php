<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class FixLeadsMenuForTrainers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:leads-menu';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix Leads menu visibility for trainers by ensuring permissions are properly assigned';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== FIXING LEADS MENU FOR TRAINERS ===');
        $this->newLine();

        // Step 1: Clear all caches
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        cache()->flush();
        $this->info('✓ Step 1: Cleared all caches');

        // Step 2: Ensure permissions exist
        $viewLeads = Permission::firstOrCreate(['name' => 'view leads']);
        $editLeads = Permission::firstOrCreate(['name' => 'edit leads']);
        $this->info('✓ Step 2: Permissions exist');

        // Step 3: Get trainer role
        $trainerRole = Role::where('name', 'trainer')->first();
        if (!$trainerRole) {
            $this->error('✗ ERROR: Trainer role not found!');
            return Command::FAILURE;
        }
        $this->info('✓ Step 3: Trainer role found');

        // Step 4: Assign permissions to trainer role
        $trainerRole->givePermissionTo([$viewLeads, $editLeads]);
        $this->info('✓ Step 4: Permissions assigned to trainer role');

        // Step 5: Clear cache again
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->info('✓ Step 5: Permission cache cleared');
        $this->newLine();

        // Step 6: Verify with all trainer users
        $trainers = User::role('trainer')->get();
        $this->info("Found {$trainers->count()} trainer user(s)");
        $this->newLine();

        $allCanView = true;
        foreach ($trainers as $trainer) {
            $this->line("Trainer: {$trainer->name} ({$trainer->email})");
            
            // Clear user's cache
            $trainer->forgetCachedPermissions();
            $trainer->refresh();
            $trainer->load('roles.permissions');
            
            // Check permission
            $canView = $trainer->can('view leads');
            $status = $canView ? '✓ YES' : '✗ NO';
            $this->line("  can('view leads'): {$status}");
            
            if (!$canView) {
                $allCanView = false;
                // Try direct assignment
                $trainer->givePermissionTo($viewLeads);
                $trainer->forgetCachedPermissions();
                $trainer->refresh();
                $canView = $trainer->can('view leads');
                $status = $canView ? '✓ YES' : '✗ NO';
                $this->line("  After direct assignment: {$status}");
            }
            $this->newLine();
        }

        $this->info('=== FINAL STEPS ===');
        $this->line('1. Log out completely from your browser');
        $this->line('2. Clear browser cache (Ctrl+F5 / Cmd+Shift+R)');
        $this->line('3. Log back in as trainer');
        $this->line('4. The Leads menu should now appear!');
        $this->newLine();

        if ($allCanView) {
            $this->info('✅ All trainers can now view leads!');
        } else {
            $this->warn('⚠ Some trainers may need to log out and log back in.');
        }

        return Command::SUCCESS;
    }
}
