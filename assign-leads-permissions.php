<?php

/**
 * Quick script to assign lead permissions to trainers
 * Run this directly: php assign-leads-permissions.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

echo "Assigning lead permissions to trainers...\n\n";

// Clear permission cache
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
echo "✓ Permission cache cleared\n";

// Get trainer role
$trainerRole = Role::where('name', 'trainer')->first();

if (!$trainerRole) {
    echo "✗ ERROR: Trainer role not found!\n";
    exit(1);
}

echo "✓ Trainer role found\n";

// Get or create lead permissions
$viewLeadsPermission = Permission::firstOrCreate(['name' => 'view leads']);
$editLeadsPermission = Permission::firstOrCreate(['name' => 'edit leads']);

echo "✓ Lead permissions found/created\n";

// Assign permissions to trainer role
$trainerRole->givePermissionTo([$viewLeadsPermission, $editLeadsPermission]);

// Clear cache again
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

echo "\n✓ Successfully assigned lead permissions to trainer role!\n";
echo "  - view leads\n";
echo "  - edit leads\n\n";

// Verify with a trainer user
$trainer = User::role('trainer')->first();
if ($trainer) {
    $trainer->refresh();
    $canView = $trainer->can('view leads');
    $canEdit = $trainer->can('edit leads');
    
    echo "Verification:\n";
    echo "Trainer: {$trainer->name} ({$trainer->email})\n";
    echo "  Can view leads: " . ($canView ? "✓ YES" : "✗ NO") . "\n";
    echo "  Can edit leads: " . ($canEdit ? "✓ YES" : "✗ NO") . "\n";
} else {
    echo "⚠ No trainer user found in database.\n";
}

echo "\n✅ Done! Please refresh your browser or log out and log back in.\n";
