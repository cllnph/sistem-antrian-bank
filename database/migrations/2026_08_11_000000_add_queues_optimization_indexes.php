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
                $table->index(['service_setting_id', 'status', 'called_at'], 'idx_queues_setting_status_called');
            } catch (\Throwable $e) {}

            try {
                $table->index(['served_by', 'status', 'called_at'], 'idx_queues_served_status_called');
            } catch (\Throwable $e) {}

            try {
                $table->index(['service_setting_id', 'status', 'id'], 'idx_queues_setting_status_id');
            } catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            try { $table->dropIndex('idx_queues_setting_status_called'); } catch (\Throwable $e) {}
            try { $table->dropIndex('idx_queues_served_status_called'); } catch (\Throwable $e) {}
            try { $table->dropIndex('idx_queues_setting_status_id'); } catch (\Throwable $e) {}
        });
    }
};
