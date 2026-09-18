<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });

        Schema::table('queues', function (Blueprint $table) {
            // Drop old composite indexes if exists
            try { $table->dropIndex(['service', 'status']); } catch (\Throwable $e) {}
            try { $table->dropIndex('queues_service_status_created_idx'); } catch (\Throwable $e) {}

            if (Schema::hasColumn('queues', 'service')) {
                $table->dropColumn('service');
            }

            $table->foreignId('service_setting_id')
                ->nullable()
                ->after('number')
                ->constrained('service_settings')
                ->nullOnDelete();

            $table->enum('status', ['waiting', 'called', 'serving', 'completed', 'skipped'])
                ->default('waiting')
                ->change();

            $table->index(['service_setting_id', 'status', 'created_at'], 'queues_service_setting_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('teller')->after('email');
            }
        });

        Schema::table('queues', function (Blueprint $table) {
            $table->dropIndex('queues_service_setting_status_created_idx');
            $table->dropForeign(['service_setting_id']);
            $table->dropColumn('service_setting_id');
            $table->string('service')->after('number');
            $table->string('status')->default('waiting')->change();
            $table->index(['service', 'status', 'created_at'], 'queues_service_status_created_idx');
        });
    }
};
