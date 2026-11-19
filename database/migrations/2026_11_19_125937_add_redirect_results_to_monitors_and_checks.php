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
        Schema::table('monitors', function (Blueprint $table): void {
            $table->text('last_final_url')->nullable();
            $table->unsignedTinyInteger('last_redirect_count')->nullable();
            //
        });
        Schema::table('monitor_checks', function (Blueprint $table): void {
            $table->text('final_url')->nullable();
            $table->unsignedTinyInteger('redirect_count')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitor_checks', function (Blueprint $table): void {
            $table->dropColumn(['final_url', 'redirect_count']);
            //
        });
        Schema::table('monitors', function (Blueprint $table): void {
            $table->dropColumn(['last_final_url', 'last_redirect_count']);
        });
    }
};
