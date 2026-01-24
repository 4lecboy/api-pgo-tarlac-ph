<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('receiving_records', function (Blueprint $table) {
      // Additional fields for department processing
      $table->string('district')->nullable()->after('status');
      $table->string('category')->nullable()->after('district');
      $table->string('type')->nullable()->after('category');
      $table->string('requisitioner')->nullable()->after('type');
      $table->string('served_request')->nullable()->after('requisitioner');
      $table->text('remarks')->nullable()->after('served_request');

      // Track which department is processing this
      $table->foreignId('processed_by_user_id')->nullable()->constrained('users')->after('remarks');
      $table->timestamp('processed_at')->nullable()->after('processed_by_user_id');
    });
  }

  public function down(): void
  {
    Schema::table('receiving_records', function (Blueprint $table) {
      $table->dropForeign(['processed_by_user_id']);
      $table->dropColumn([
        'district',
        'category',
        'type',
        'requisitioner',
        'served_request',
        'remarks',
        'processed_by_user_id',
        'processed_at'
      ]);
    });
  }
};
