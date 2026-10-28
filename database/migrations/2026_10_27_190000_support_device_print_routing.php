<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL needs a non-unique index to keep supporting the branch foreign key.
        Schema::table('print_agents', function (Blueprint $table) {
            $table->index('branch_id', 'print_agents_branch_lookup');
        });
        Schema::table('print_agents', function (Blueprint $table) {
            $table->dropUnique(['branch_id']);
            $table->string('printer_name')->nullable()->change();
            $table->json('installed_printers')->nullable();
            $table->softDeletes();
        });
        Schema::table('print_jobs', function (Blueprint $table) {
            $table->string('transport', 20)->default('server')->index();
        });
        DB::table('print_jobs')->whereNotNull('print_agent_id')->update(['transport' => 'agent']);

        Schema::create('print_agent_enrollments', function (Blueprint $table) {
            $table->foreignUuid('print_agent_id')->primary()->constrained('print_agents')->restrictOnDelete();
            $table->string('enrollment_hash', 64);
            $table->string('token_hash', 64);
            $table->uuid('device_id');
            $table->string('printer_name');
            $table->timestamp('expires_at');
        });

        Schema::create('kiosk_print_bindings', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('kiosk_device_id')->constrained('kiosk_devices')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('print_agent_id')->nullable()->constrained('print_agents')->restrictOnDelete();
            $table->string('transport', 20);
            $table->timestamps();
            $table->unique(['kiosk_device_id', 'branch_id'], 'kiosk_print_device_branch_unique');
        });
    }

    public function down(): void
    {
        if (DB::table('print_agents')->select('branch_id')->groupBy('branch_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Rollback ditolak: satu cabang sudah memiliki beberapa penghubung.');
        }
        if (DB::table('print_agents')->whereNull('printer_name')->exists()) {
            throw new RuntimeException('Rollback ditolak: lengkapi nama printer pada penghubung terlebih dahulu.');
        }
        Schema::dropIfExists('kiosk_print_bindings');
        Schema::dropIfExists('print_agent_enrollments');
        Schema::table('print_jobs', fn (Blueprint $table) => $table->dropColumn('transport'));
        Schema::table('print_agents', function (Blueprint $table) {
            $table->string('printer_name')->nullable(false)->change();
            $table->dropColumn(['installed_printers', 'deleted_at']);
            $table->unique('branch_id');
        });
        Schema::table('print_agents', fn (Blueprint $table) => $table->dropIndex('print_agents_branch_lookup'));
    }
};
