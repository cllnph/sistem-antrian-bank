<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('queue_id')->constrained('queues')->restrictOnDelete();
            $table->uuid('original_job_id')->nullable()->index();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('owner_hash', 64);
            $table->json('snapshot');
            $table->string('printer_name');
            $table->string('status', 20)->default('queued')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submission_started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('failure_code')->nullable();
            $table->text('failure_detail')->nullable();
            $table->timestamps();
            $table->foreign('original_job_id')->references('id')->on('print_jobs')->restrictOnDelete();
        });
        Schema::create('printer_workers', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedInteger('process_id');
            $table->timestamp('heartbeat_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('printer_workers');
        Schema::dropIfExists('print_jobs');
    }
};
