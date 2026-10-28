<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kiosk_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('label', 100)->nullable();
            $table->string('device_type', 30);
            $table->string('platform', 30);
            $table->string('browser', 30);
            $table->string('identity_source', 30);
            $table->string('last_ip', 45)->nullable();
            $table->string('last_user_agent', 1024)->nullable();
            $table->foreignId('last_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->dateTime('first_seen_at');
            $table->dateTime('last_seen_at')->index();
            $table->timestamps();
        });
        Schema::create('kiosk_device_sessions', function (Blueprint $table) {
            $table->string('id', 64)->primary();
            $table->foreignUuid('kiosk_device_id')->constrained('kiosk_devices')->cascadeOnDelete();
        });
        Schema::create('kiosk_device_events', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('kiosk_device_id')->constrained('kiosk_devices')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('print_job_id')->nullable()->unique()->constrained('print_jobs')->restrictOnDelete();
            $table->string('event', 30);
            $table->string('source', 30);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 1024)->nullable();
            $table->string('ticket_number', 40)->nullable();
            $table->dateTime('observed_at')->index();
            $table->index(['kiosk_device_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kiosk_device_events');
        Schema::dropIfExists('kiosk_device_sessions');
        Schema::dropIfExists('kiosk_devices');
    }
};
