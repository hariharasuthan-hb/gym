<?php

/**
 * Debug script to check why Leads menu is not showing
 * Run: php artisan tinker
 * Then copy-paste this code
 */

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

echo "=== DEBUGGING LEADS PERMISSION ===\n\n";

// Clear all caches
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
cache()->flush();
echo "✓ All caches cleared\n\n";

// Check permission exists
$viewLeadsPermission = Permission::where('name', 'view leads')->first();
if (!$viewLeadsPermission) {
    echo "✗ ERROR: 'view leads' permission does not exist!\n";
    echo "Creating it...\n";
    $viewLeadsPermission = Permission::create(['name' => 'view leads']);
    echo "✓ Created 'view leads' permission\n\n";
} else {
    echo "✓ 'view leads' permission exists (ID: {$viewLeadsPermission->id})\n\n";
}

// Check trainer role
$trainerRole = Role::where('name', 'trainer')->first();
if (!$trainerRole) {
    echo "✗ ERROR: Trainer role not found!\n";
    exit;
}

echo "Trainer Role: {$trainerRole->name} (ID: {$trainerRole->id})\n";

// Check if trainer has the permission
$hasPermission = $trainerRole->hasPermissionTo('view leads');
echo "Trainer role has 'view leads': " . ($hasPermission ? "✓ YES" : "✗ NO") . "\n\n";

if (!$hasPermission) {
    echo "Assigning permission to trainer role...\n";
    $trainerRole->givePermissionTo($viewLeadsPermission);
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    echo "✓ Permission assigned\n\n";
}

// Test with actual trainer user
$trainer = User::role('trainer')->first();
if ($trainer) {
    echo "Testing with trainer user:\n";
    echo "  Name: {$trainer->name}\n";
    echo "  Email: {$trainer->email}\n";
    echo "  ID: {$trainer->id}\n\n";
    
    // Clear user's permission cache
    $trainer->forgetCachedPermissions();
    $trainer->refresh();
    $trainer->load('roles.permissions');
    
    // Check permissions
    $canView = $trainer->can('view leads');
    $hasRole = $trainer->hasRole('trainer');
    
    echo "User checks:\n";
    echo "  hasRole('trainer'): " . ($hasRole ? "✓ YES" : "✗ NO") . "\n";
    echo "  can('view leads'): " . ($canView ? "✓ YES" : "✗ FALSE") . "\n\n";
    
    // Direct permission check
    $directCheck = $trainer->hasPermissionTo('view leads');
    echo "  hasPermissionTo('view leads'): " . ($directCheck ? "✓ YES" : "✗ NO") . "\n\n";
    
    // List all permissions
    $allPermissions = $trainer->getAllPermissions()->pluck('name')->toArray();
    echo "All user permissions (" . count($allPermissions) . "):\n";
    foreach ($allPermissions as $perm) {
        echo "  - $perm\n";
    }
    echo "\n";
    
    if (!$canView) {
        echo "⚠ ISSUE FOUND: User cannot view leads!\n";
        echo "\nTroubleshooting:\n";
        echo "1. Make sure user is logged out and logged back in\n";
        echo "2. Clear browser cache\n";
        echo "3. Run: php artisan cache:clear\n";
        echo "4. Check if user has 'trainer' role assigned\n";
    } else {
        echo "✅ SUCCESS: User CAN view leads!\n";
        echo "The menu should appear. If not, try:\n";
        echo "1. Hard refresh browser (Ctrl+F5 / Cmd+Shift+R)\n";
        echo "2. Clear browser cache\n";
        echo "3. Log out and log back in\n";
    }
} else {
    echo "⚠ No trainer user found.\n";
}

echo "\nDone!\n";
