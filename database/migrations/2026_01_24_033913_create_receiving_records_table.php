<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receiving_records', function (Blueprint $table) {
            $table->id();
            $table->string('control_no');
            $table->date('date');
            $table->text('particulars')->nullable();
            $table->string('department');
            $table->string('organization_barangay')->nullable();
            $table->string('municipality_address')->nullable();
            $table->string('name')->nullable();
            $table->string('contact')->nullable();
            $table->text('action_taken')->nullable();
            $table->decimal('amount_approved', 12, 2)->nullable();
            $table->enum('status', ['pending', 'approved', 'disapproved'])->default('pending');
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receiving_records');
    }
};
