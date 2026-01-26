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
            $table->timestamp('approved_at')->after('processed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receiving_records', function (Blueprint $table) {
            $table->dropColumn('approved_at');
        });
    }
};
