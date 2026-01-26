<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Console\Kernel;

$app->make(Kernel::class)->bootstrap();

try {
    $user = User::create([
        'first_name' => 'Financial',
        'last_name' => 'Assistance',
        'email' => 'financial@gmail.com',
        'password' => Hash::make('financial@gmail.com'),
        'role' => 'user',
        'department' => 'Financial Assistance',
        'status' => 'active',
        'employee_id' => 'EMP-FIN-003',
        'middle_name' => '',
        'extension' => '',
    ]);
    echo "User created successfully: ID " . $user->id;
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
