<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop check constraints if they exist (PostgreSQL specific)
        try {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_department_check');
        } catch (\Exception $e) {}
        
        try {
            DB::statement('ALTER TABLE receiving_records DROP CONSTRAINT IF EXISTS receiving_records_department_check');
        } catch (\Exception $e) {}
    }

    public function down(): void
    {
        // No need to restore restrictive constraints
    }
};
