<?php

use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$roles = DB::table('users')->distinct()->pluck('role');
echo "Distinct Roles in DB: " . json_encode($roles) . PHP_EOL;

try {
    $users = \App\Models\User::all();
    echo "User Model Hydration: SUCCESS. Count: " . $users->count() . PHP_EOL;
} catch (\Throwable $e) {
    echo "User Model Hydration: FAILED. Error: " . $e->getMessage() . PHP_EOL;
}
