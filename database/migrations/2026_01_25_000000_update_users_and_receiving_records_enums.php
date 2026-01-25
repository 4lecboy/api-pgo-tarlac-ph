<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $departments = [
            'Receiving',
            'Barangay Affairs',
            'Financial Assistance',
            'Use of Facilities',
            'Appointment Meeting',
            'Use of Vehicle',
            'Other Request'
        ];

        $roles = [
            'admin',
            'user',
            'super admin'
        ];

        // Normalize existing data for User
        DB::table('users')->whereRaw('LOWER(role) = ?', ['admin'])->update(['role' => 'admin']);
        DB::table('users')->whereRaw('LOWER(role) = ?', ['user'])->update(['role' => 'user']);
        DB::table('users')->whereRaw('LOWER(role) = ?', ['super admin'])->update(['role' => 'super admin']);
        
        DB::table('users')->whereRaw('LOWER(department) = ?', ['receiving'])->update(['department' => 'Receiving']);
        // Add more normalizations if needed

        // Normalize existing data for ReceivingRecord
        DB::table('receiving_records')->whereRaw('LOWER(department) = ?', ['receiving'])->update(['department' => 'Receiving']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->change();
            $table->string('department')->nullable()->change();
        });

        Schema::table('receiving_records', function (Blueprint $table) {
            $table->string('department')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 255)->default('user')->change();
            $table->string('department', 255)->nullable()->change();
        });

        Schema::table('receiving_records', function (Blueprint $table) {
            $table->string('department', 255)->change();
        });
    }
};
