<?php

/**
 * Verify trainer permissions
 * Run: php artisan tinker
 * Then copy-paste the code below
 */

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

// Clear permission cache
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
echo "Permission cache cleared.\n\n";

// Check trainer role permissions
$trainerRole = Role::where('name', 'trainer')->first();
$permissions = $trainerRole->permissions->pluck('name')->toArray();

echo "Trainer role permissions (" . count($permissions) . " total):\n";
echo in_array('view leads', $permissions) ? "✓ view leads\n" : "✗ view leads (MISSING)\n";
echo in_array('edit leads', $permissions) ? "✓ edit leads\n" : "✗ edit leads (MISSING)\n";

// Check a trainer user
$trainer = User::role('trainer')->first();
if ($trainer) {
    echo "\nChecking trainer user: {$trainer->name} ({$trainer->email})\n";
    
    // Refresh the user to get latest permissions
    $trainer->refresh();
    $trainer->load('roles.permissions');
    
    $canView = $trainer->can('view leads');
    $canEdit = $trainer->can('edit leads');
    
    echo "Can view leads: " . ($canView ? "✓ YES" : "✗ NO") . "\n";
    echo "Can edit leads: " . ($canEdit ? "✓ YES" : "✗ NO") . "\n";
    
    if (!$canView) {
        echo "\n⚠ Permission check failed. Try:\n";
        echo "1. Log out and log back in\n";
        echo "2. Clear browser cache\n";
        echo "3. Run: php artisan cache:clear\n";
    }
} else {
    echo "\n⚠ No trainer user found.\n";
}

echo "\nDone!\n";
