<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            $table->index(['service', 'status', 'created_at'], 'queues_service_status_created_idx');
            $table->index(['status', 'called_at'], 'queues_status_called_idx');
        });
    }

    public function down(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            $table->dropIndex('queues_service_status_created_idx');
            $table->dropIndex('queues_status_called_idx');
        });
    }
};
