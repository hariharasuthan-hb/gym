<?php

/**
 * Quick script to check and assign lead permissions to trainers
 * 
 * Run via: php artisan tinker
 * Then copy-paste the code below
 */

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

// Clear permission cache
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
echo "Permission cache cleared.\n\n";

// Get trainer role
$trainerRole = Role::where('name', 'trainer')->first();

if (!$trainerRole) {
    echo "ERROR: Trainer role not found!\n";
    exit;
}

echo "Trainer role found: {$trainerRole->name}\n";

// Check current permissions
$currentPermissions = $trainerRole->permissions->pluck('name')->toArray();
echo "Current trainer permissions: " . count($currentPermissions) . "\n";
if (in_array('view leads', $currentPermissions)) {
    echo "✓ 'view leads' permission already assigned\n";
} else {
    echo "✗ 'view leads' permission NOT assigned\n";
}
if (in_array('edit leads', $currentPermissions)) {
    echo "✓ 'edit leads' permission already assigned\n";
} else {
    echo "✗ 'edit leads' permission NOT assigned\n";
}

// Get or create lead permissions
$viewLeadsPermission = Permission::firstOrCreate(['name' => 'view leads']);
$editLeadsPermission = Permission::firstOrCreate(['name' => 'edit leads']);

// Assign permissions to trainer role
$trainerRole->givePermissionTo([$viewLeadsPermission, $editLeadsPermission]);

// Clear cache again
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

echo "\n✓ Successfully assigned lead permissions to trainer role!\n";
echo "  - view leads\n";
echo "  - edit leads\n\n";

// Check a trainer user
$trainer = User::role('trainer')->first();
if ($trainer) {
    echo "Checking trainer user: {$trainer->name} ({$trainer->email})\n";
    $trainer->refresh();
    if ($trainer->can('view leads')) {
        echo "✓ Trainer CAN view leads\n";
    } else {
        echo "✗ Trainer CANNOT view leads\n";
    }
} else {
    echo "No trainer user found in database.\n";
}

echo "\nDone! Please refresh your browser or log out and log back in.\n";
