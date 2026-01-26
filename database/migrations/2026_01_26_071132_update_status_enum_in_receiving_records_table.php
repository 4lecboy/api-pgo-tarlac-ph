<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For PostgreSQL, we need to drop the check constraint created by the enum
        if (config('database.default') === 'pgsql') {
            DB::statement('ALTER TABLE receiving_records DROP CONSTRAINT IF EXISTS receiving_records_status_check');
        }

        Schema::table('receiving_records', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receiving_records', function (Blueprint $table) {
            // Note: Going back to enum might fail if existing data doesn't match
            $table->enum('status', ['pending', 'approved', 'disapproved'])->default('pending')->change();
        });
    }
};
