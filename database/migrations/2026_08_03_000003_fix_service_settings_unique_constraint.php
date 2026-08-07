<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to drop unique constraint on 'service' and allow multiple lokets per service type.
     */
    public function up(): void
    {
        Schema::table('service_settings', function (Blueprint $table) {
            $table->dropUnique('service_settings_service_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_settings', function (Blueprint $table) {
            $table->unique('service');
        });
    }
};
