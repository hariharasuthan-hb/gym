<?php

/**
 * Test script to verify trainer permissions
 * Run: php artisan tinker
 * Then copy-paste this code
 */

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

echo "=== Testing Trainer Permissions ===\n\n";

// Clear permission cache
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
echo "✓ Permission cache cleared\n\n";

// Check trainer role
$trainerRole = Role::where('name', 'trainer')->first();
if (!$trainerRole) {
    echo "✗ ERROR: Trainer role not found!\n";
    exit;
}

echo "Trainer Role: {$trainerRole->name}\n";
echo "Role ID: {$trainerRole->id}\n\n";

// Check permissions
$permissions = $trainerRole->permissions->pluck('name')->toArray();
echo "Total permissions: " . count($permissions) . "\n";
echo "Has 'view leads': " . (in_array('view leads', $permissions) ? "✓ YES" : "✗ NO") . "\n";
echo "Has 'edit leads': " . (in_array('edit leads', $permissions) ? "✓ YES" : "✗ NO") . "\n\n";

// If missing, assign them
if (!in_array('view leads', $permissions) || !in_array('edit leads', $permissions)) {
    echo "Assigning missing permissions...\n";
    $viewLeads = Permission::firstOrCreate(['name' => 'view leads']);
    $editLeads = Permission::firstOrCreate(['name' => 'edit leads']);
    $trainerRole->givePermissionTo([$viewLeads, $editLeads]);
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    echo "✓ Permissions assigned\n\n";
}

// Test with a trainer user
$trainer = User::role('trainer')->first();
if ($trainer) {
    echo "Testing with trainer user:\n";
    echo "  Name: {$trainer->name}\n";
    echo "  Email: {$trainer->email}\n";
    echo "  ID: {$trainer->id}\n\n";
    
    // Refresh user to get latest permissions
    $trainer->refresh();
    $trainer->load('roles.permissions');
    
    // Clear user's permission cache
    $trainer->forgetCachedPermissions();
    
    echo "Permission checks:\n";
    $canView = $trainer->can('view leads');
    $canEdit = $trainer->can('edit leads');
    
    echo "  can('view leads'): " . ($canView ? "✓ TRUE" : "✗ FALSE") . "\n";
    echo "  can('edit leads'): " . ($canEdit ? "✓ TRUE" : "✗ FALSE") . "\n\n";
    
    if (!$canView) {
        echo "⚠ ISSUE: Trainer cannot view leads!\n";
        echo "Try:\n";
        echo "1. Log out and log back in\n";
        echo "2. Clear browser cache\n";
        echo "3. Run: php artisan cache:clear\n";
    } else {
        echo "✅ SUCCESS: Trainer has 'view leads' permission!\n";
        echo "The Leads menu should appear in the sidebar.\n";
    }
} else {
    echo "⚠ No trainer user found in database.\n";
}

echo "\nDone!\n";
