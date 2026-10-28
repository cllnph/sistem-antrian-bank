<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_agents', function (Blueprint $table) {
            $table->string('paper_profile', 20)->nullable();
            $table->boolean('auto_name')->default(false);
            $table->boolean('enrollment_auto_enable')->default(false);
            $table->boolean('auto_enable_pending')->default(false);
            $table->string('inventory_status', 20)->nullable();
        });
        Schema::table('print_agent_enrollments', function (Blueprint $table) {
            $table->string('paper_profile', 20)->nullable();
            $table->string('machine_name', 100)->nullable();
            $table->boolean('auto_enable')->default(false);
        });
        Schema::table('kiosk_print_bindings', function (Blueprint $table) {
            // Existing manually assigned destinations need local verification once.
            $table->timestamp('verified_at')->nullable();
            $table->uuid('proof_device_id')->nullable();
            $table->string('binding_source', 30)->nullable();
            $table->string('paper_profile', 20)->nullable();
        });
        Schema::table('print_jobs', fn (Blueprint $table) => $table->string('owner_type', 20)->default('session'));
        Schema::create('print_agent_browser_pairings', function (Blueprint $table) {
            $table->string('token_hash', 64)->primary();
            $table->foreignUuid('print_agent_id')->constrained('print_agents')->restrictOnDelete();
            $table->uuid('device_id');
            $table->string('credential_hash', 64);
            $table->timestamp('expires_at')->index();
        });
    }

    public function down(): void
    {
        if (DB::table('print_jobs')->where('owner_type', 'device')->exists()) {
            throw new RuntimeException('Rollback ditolak: sudah ada tiket dengan identitas browser yang bertahan melewati pergantian sesi.');
        }
        Schema::dropIfExists('print_agent_browser_pairings');
        Schema::table('print_jobs', fn (Blueprint $table) => $table->dropColumn('owner_type'));
        Schema::table('kiosk_print_bindings', fn (Blueprint $table) => $table->dropColumn(['verified_at', 'proof_device_id', 'binding_source', 'paper_profile']));
        Schema::table('print_agent_enrollments', fn (Blueprint $table) => $table->dropColumn(['paper_profile', 'machine_name', 'auto_enable']));
        Schema::table('print_agents', fn (Blueprint $table) => $table->dropColumn(['paper_profile', 'auto_name', 'enrollment_auto_enable', 'auto_enable_pending', 'inventory_status']));
    }
};
