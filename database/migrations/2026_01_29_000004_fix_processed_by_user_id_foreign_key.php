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
        Schema::table('receiving_records', function (Blueprint $table) {
            // Drop the old constraint
            $table->dropForeign(['processed_by_user_id']);
            
            // Re-add with cascade or set null. 
            // Since this is a "processed by" field, 'set null' might be safer if we want to keep the record metadata,
            // but the previous fix used 'cascade' for user_id. Let's stick with cascade for consistency unless set null is preferred.
            // Actually, for 'processed_by', if the user is deleted, we might still want to know the record existed.
            // But if the user is UPDATED (id change), we definitely want cascade.
            // The error happens on update OR delete.
            
            $table->foreign('processed_by_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receiving_records', function (Blueprint $table) {
            $table->dropForeign(['processed_by_user_id']);
            $table->foreign('processed_by_user_id')
                ->references('id')
                ->on('users');
        });
    }
};
