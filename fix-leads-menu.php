<?php

/**
 * Complete fix for Leads menu not showing
 * Run: php artisan tinker
 * Then copy-paste ALL of this code
 */

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

echo "=== FIXING LEADS MENU FOR TRAINERS ===\n\n";

// Step 1: Clear all caches
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
cache()->flush();
echo "✓ Step 1: Cleared all caches\n";

// Step 2: Ensure permissions exist
$viewLeads = Permission::firstOrCreate(['name' => 'view leads']);
$editLeads = Permission::firstOrCreate(['name' => 'edit leads']);
echo "✓ Step 2: Permissions exist\n";

// Step 3: Get trainer role
$trainerRole = Role::where('name', 'trainer')->first();
if (!$trainerRole) {
    echo "✗ ERROR: Trainer role not found!\n";
    exit;
}
echo "✓ Step 3: Trainer role found\n";

// Step 4: Assign permissions to trainer role
$trainerRole->givePermissionTo([$viewLeads, $editLeads]);
echo "✓ Step 4: Permissions assigned to trainer role\n";

// Step 5: Clear cache again
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
echo "✓ Step 5: Permission cache cleared\n\n";

// Step 6: Verify with all trainer users
$trainers = User::role('trainer')->get();
echo "Found " . $trainers->count() . " trainer user(s)\n\n";

foreach ($trainers as $trainer) {
    echo "Trainer: {$trainer->name} ({$trainer->email})\n";
    
    // Clear user's cache
    $trainer->forgetCachedPermissions();
    $trainer->refresh();
    $trainer->load('roles.permissions');
    
    // Check permission
    $canView = $trainer->can('view leads');
    echo "  can('view leads'): " . ($canView ? "✓ YES" : "✗ NO") . "\n";
    
    if (!$canView) {
        // Try direct assignment
        $trainer->givePermissionTo($viewLeads);
        $trainer->forgetCachedPermissions();
        $trainer->refresh();
        $canView = $trainer->can('view leads');
        echo "  After direct assignment: " . ($canView ? "✓ YES" : "✗ NO") . "\n";
    }
    echo "\n";
}

echo "=== FINAL STEPS ===\n";
echo "1. Log out completely\n";
echo "2. Clear browser cache (Ctrl+F5 / Cmd+Shift+R)\n";
echo "3. Log back in as trainer\n";
echo "4. The Leads menu should now appear!\n\n";

echo "Done!\n";
