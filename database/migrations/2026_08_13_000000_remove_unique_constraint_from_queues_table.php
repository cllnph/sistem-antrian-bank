<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            try {
                $table->dropUnique('queues_number_unique');
            } catch (\Throwable $e) {
                // Index might already be dropped or named differently
            }
            try {
                $table->index(['number', 'created_at']);
            } catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            try {
                $table->unique('number');
            } catch (\Throwable $e) {}
        });
    }
};
