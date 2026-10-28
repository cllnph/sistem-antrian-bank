<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_agents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('branch_id')->unique()->constrained('branches')->restrictOnDelete();
            $table->string('name');
            $table->string('printer_name');
            $table->boolean('enabled')->default(false);
            $table->boolean('revoked')->default(false);
            $table->string('token_hash', 64)->nullable();
            $table->string('enrollment_hash', 64)->nullable();
            $table->timestamp('enrollment_expires_at')->nullable();
            $table->uuid('device_id')->nullable();
            $table->timestamp('heartbeat_at')->nullable();
            $table->boolean('printer_ready')->default(false);
            $table->string('last_error')->nullable();
            $table->string('machine_name')->nullable();
            $table->string('version', 30)->nullable();
            $table->timestamps();
        });
        Schema::table('print_jobs', function (Blueprint $table) {
            $table->foreignUuid('print_agent_id')->nullable()->constrained('print_agents')->restrictOnDelete();
            $table->string('claim_hash', 64)->nullable();
            $table->timestamp('lease_expires_at')->nullable()->index();
            $table->string('pdf_sha256', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('print_jobs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('print_agent_id');
            $table->dropColumn(['claim_hash', 'lease_expires_at', 'pdf_sha256']);
        });
        Schema::dropIfExists('print_agents');
    }
};
