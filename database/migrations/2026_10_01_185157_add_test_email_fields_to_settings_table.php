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
        Schema::table('settings', function (Blueprint $table) {
            $table->timestamp('test_email_sent_at')->nullable();
            $table->string('test_email_sent_to')->nullable();
            $table->timestamp('test_email_failed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['test_email_sent_at', 'test_email_sent_to', 'test_email_failed_at']);
        });
    }
};
