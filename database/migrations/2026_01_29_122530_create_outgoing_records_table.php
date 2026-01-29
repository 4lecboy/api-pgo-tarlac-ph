<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('outgoing_records', function (Blueprint $table) {
            $table->id();
            $table->string('category'); // 'legal_docs', 'memo', 'fuel_requests'
            $table->date('date');
            $table->text('particulars')->nullable(); // Description or Subject
            
            // Specific fields (nullable)
            $table->string('type')->nullable(); // For Legal Docs
            $table->string('recipient')->nullable(); // For Memo
            $table->string('vehicle')->nullable(); // For Fuel Requests
            $table->string('driver')->nullable(); // For Fuel Requests
            $table->decimal('amount', 10, 2)->nullable(); // For Fuel Requests
            
            $table->string('file_path')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outgoing_records');
    }
};
