<?php

use App\Models\User;
use App\Enums\UserRole;
use App\Enums\Department;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Current Users:\n";
$users = User::all();
foreach ($users as $user) {
    echo "ID: {$user->id}, Name: {$user->first_name} {$user->last_name}, Role: {$user->role->value}, Dept: {$user->department}\n";
}

// Attempt to promote the first user to Super Admin / IT if none exists
$superAdmin = User::where('role', UserRole::SUPER_ADMIN)->first();

if (!$superAdmin) {
    $targetUser = User::first();
    if ($targetUser) {
        try {
            echo "\nPromoting user ID {$targetUser->id} ({$targetUser->email}) to SUPER ADMIN / IT...\n";
            $targetUser->role = UserRole::SUPER_ADMIN;
            $targetUser->department = Department::IT->value;
            // Wait, $targetUser->department is NOT casted in the User model in the file snippet I saw earlier?
            // Let's check User.php cast again.
            $targetUser->save();
            echo "Success! Please log out and log back in.\n";
        } catch (\Throwable $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
            echo "Trace: " . $e->getTraceAsString() . "\n";
        }
    } else {
        echo "No users found to promote.\n";
    }
} else {
    echo "\nSuper Admin already exists: {$superAdmin->email}\n";
}
