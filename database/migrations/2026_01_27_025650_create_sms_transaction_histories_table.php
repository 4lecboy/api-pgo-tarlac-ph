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
        Schema::create('sms_transaction_histories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // transaction ID
            $table->decimal('credit_amount', 10, 2);
            $table->string('status'); // Approved, Failed
            $table->string('initiated_by'); // email or username
            $table->text('message');
            $table->string('recipient');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_transaction_histories');
    }
};
