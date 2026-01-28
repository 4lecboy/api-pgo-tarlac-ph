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
        // Status updates are already handled by a previous migration that changed enum to string
        // We'll just ensure the status field is ready for 'completed'
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
