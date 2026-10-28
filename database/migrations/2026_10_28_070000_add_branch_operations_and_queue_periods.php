<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->json('weekly_schedule')->nullable();
            $table->string('reset_time', 5)->default('00:00');
        });
        Schema::create('branch_queue_states', function (Blueprint $table) {
            $table->foreignId('branch_id')->primary()->constrained('branches')->cascadeOnDelete();
            $table->uuid('period_id')->unique();
            $table->dateTime('period_started_at');
            $table->dateTime('next_reset_at')->index();
            $table->dateTime('last_reset_at')->nullable();
            $table->uuid('last_reset_request_id')->nullable();
            $table->string('last_error')->nullable();
        });
        Schema::create('queue_counters', function (Blueprint $table) {
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('service', 20);
            $table->uuid('period_id');
            $table->unsignedBigInteger('sequence')->default(0);
            $table->primary(['branch_id', 'service']);
        });
        Schema::table('queues', function (Blueprint $table) {
            $table->uuid('period_id')->nullable()->index();
            $table->index(['branch_id', 'period_id', 'status'], 'queues_branch_period_status');
            $table->unique(['branch_id', 'period_id', 'number'], 'queues_period_number_unique');
        });
        Schema::create('ticket_request_tombstones', function (Blueprint $table) {
            $table->string('request_hash', 64)->primary();
            $table->string('owner_hash', 64);
            $table->string('owner_type', 20);
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->string('service', 20);
            $table->timestamp('expires_at')->index();
        });
        Schema::create('operation_scheduler_status', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->timestamp('heartbeat_at')->nullable();
            $table->string('status', 20);
            $table->string('last_error')->nullable();
        });
        Schema::table('print_agents', fn (Blueprint $table) => $table->string('setup_request_hash', 64)->nullable()->unique());

        $weekly = [];
        for ($day = 1; $day <= 7; $day++) {
            $weekly[(string) $day] = ['enabled' => $day <= 5, 'opens' => '04:00', 'closes' => '18:00'];
        }
        // Upgrade is additive: historical rows and printing identities are retained until a reset.
        foreach (DB::table('branches')->orderBy('id')->get() as $branch) {
            $period = (string) Str::uuid();
            DB::table('branches')->where('id', $branch->id)->update(['weekly_schedule' => json_encode($weekly), 'operating_hours' => 'Senin–Jumat 04.00–18.00 WIB']);
            DB::table('branch_queue_states')->insert(['branch_id' => $branch->id, 'period_id' => $period,
                'period_started_at' => today(), 'next_reset_at' => today()->addDay()]);
            DB::table('queues')->where('branch_id', $branch->id)->whereDate('created_at', today())->update(['period_id' => $period]);
            foreach (['cs' => 'CS', 'teller' => 'T', 'umum' => 'U'] as $service => $prefix) {
                $sequence = 0;
                foreach (DB::table('queues')->where('branch_id', $branch->id)->where('period_id', $period)->where('number', 'like', $prefix.'-%')->pluck('number') as $number) {
                    $sequence = max($sequence, (int) substr($number, strlen($prefix) + 1));
                }
                DB::table('queue_counters')->insert(['branch_id' => $branch->id, 'service' => $service, 'period_id' => $period, 'sequence' => $sequence]);
            }
        }
    }

    public function down(): void
    {
        if (DB::table('branch_queue_states')->whereNotNull('last_reset_at')->exists()) {
            throw new RuntimeException('Rollback ditolak setelah periode antrean direset. Gunakan pemulihan backup bila diperlukan.');
        }
        Schema::table('print_agents', fn (Blueprint $table) => $table->dropColumn('setup_request_hash'));
        Schema::dropIfExists('operation_scheduler_status');
        Schema::dropIfExists('ticket_request_tombstones');
        Schema::table('queues', function (Blueprint $table) {
            $table->dropIndex('queues_branch_period_status');
            $table->dropUnique('queues_period_number_unique');
            $table->dropColumn('period_id');
        });
        Schema::dropIfExists('queue_counters');
        Schema::dropIfExists('branch_queue_states');
        Schema::table('branches', fn (Blueprint $table) => $table->dropColumn(['weekly_schedule', 'reset_time']));
    }
};
