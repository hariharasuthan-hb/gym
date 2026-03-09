<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AssignLeadPermissionsToTrainers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trainers:assign-lead-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign view leads and edit leads permissions to trainer role';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Clear permission cache first
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->info('Permission cache cleared.');

        // Get trainer role
        $trainerRole = Role::where('name', 'trainer')->first();

        if (!$trainerRole) {
            $this->error('Trainer role not found!');
            return Command::FAILURE;
        }

        // Get or create lead permissions
        $viewLeadsPermission = Permission::firstOrCreate(['name' => 'view leads']);
        $editLeadsPermission = Permission::firstOrCreate(['name' => 'edit leads']);

        // Assign permissions to trainer role (without removing existing permissions)
        $trainerRole->givePermissionTo([$viewLeadsPermission, $editLeadsPermission]);

        // Clear cache again after assigning
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->info('Successfully assigned lead permissions to trainer role!');
        $this->info('- view leads');
        $this->info('- edit leads');
        $this->newLine();
        $this->info('Trainers can now see and manage the Leads menu.');
        $this->info('Please refresh the page or log out and log back in to see the changes.');

        return Command::SUCCESS;
    }
}
