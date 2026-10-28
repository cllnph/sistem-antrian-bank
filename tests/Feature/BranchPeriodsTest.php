<?php

namespace Tests\Feature;

use App\Filament\Pages\BranchDetail;
use App\Filament\Pages\BranchOperations;
use App\Filament\Pages\OperatingHours;
use App\Jobs\SubmitTicketPrint;
use App\Models\Branch;
use App\Models\BranchQueueState;
use App\Models\PrintAgent;
use App\Models\PrintJob;
use App\Models\Queue;
use App\Models\ServiceSetting;
use App\Models\User;
use App\Services\BranchAdminRole;
use App\Services\BranchDashboard;
use App\Services\BranchSchedule;
use App\Services\QueuePeriods;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class BranchPeriodsTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 28)->setTime(9, 0));
        config(['kiosk.test_mode_enabled' => false]);
        $this->seed(RoleAndPermissionSeeder::class);
        $this->branch = Branch::create(['code' => '201', 'slug' => 'period', 'name' => 'Cabang Periode', 'type' => 'Kantor Cabang', 'is_active' => true]);
        foreach (['cs', 'teller'] as $service) {
            ServiceSetting::create(['branch_id' => $this->branch->id, 'service' => $service, 'label' => $service, 'counter_name' => $service, 'is_active' => true]);
        }
        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('admin');
        $this->withSession(['_token' => 'period-test'])->withCookie(config('session.cookie'), session()->getId())->withCredentials();
    }

    private function payload(string $service = 'cs'): array
    {
        return ['service' => $service, 'request_id' => (string) Str::uuid(),
            'issuance_context' => $this->get('/ambil-nomor/201')->assertOk()->viewData('issuanceContext')];
    }

    private function resetPeriod(?string $intent = null): void
    {
        $periods = app(QueuePeriods::class);
        $periods->reset($this->branch, $this->admin, $periods->ensure($this->branch)->period_id, $intent ?? (string) Str::uuid());
    }

    public function test_new_requests_require_uuid_and_browser_bound_context(): void
    {
        $this->postJson('/ambil-nomor/201', ['service' => 'cs'])->assertUnprocessable()->assertJsonValidationErrors('request_id');
        $this->postJson('/ambil-nomor/201', ['service' => 'cs', 'request_id' => (string) Str::uuid()])->assertConflict();
        $payload = $this->payload();
        $this->postJson('/ambil-nomor/201', [...$payload, 'issuance_context' => 'forged'])->assertConflict();
        $this->postJson('/ambil-nomor/201', $payload)->assertOk()->assertJsonPath('ticket', 'CS-001');
        $this->postJson('/ambil-nomor/201', $payload)->assertOk()->assertJsonPath('ticket', 'CS-001');
        $this->assertDatabaseCount('queues', 1);
    }

    public function test_old_context_and_uuid_cannot_issue_a_replacement_after_cleanup(): void
    {
        $payload = $this->payload();
        $issued = $this->postJson('/ambil-nomor/201', $payload)->assertOk();
        $this->resetPeriod();
        $this->assertDatabaseCount('queues', 0);
        $this->assertDatabaseCount('queue_logs', 0);
        $this->assertDatabaseCount('print_jobs', 0);
        $this->assertDatabaseCount('ticket_request_tombstones', 1);
        $proof = (array) DB::table('ticket_request_tombstones')->first();
        $this->assertSame(['branch_id', 'expires_at', 'owner_hash', 'owner_type', 'request_hash', 'service'], collect(array_keys($proof))->sort()->values()->all());
        $this->assertStringNotContainsString('CS-001', json_encode($proof));
        $this->getJson($issued->json('print_job.status_url'))->assertGone();
        $this->get($issued->json('print_job.receipt_url'))->assertGone();
        $this->postJson('/ambil-nomor/201', $payload)->assertGone();
        $this->postJson('/ambil-nomor/201', [...$payload, 'request_id' => (string) Str::uuid()])->assertGone();
        $this->postJson('/ambil-nomor/201', $this->payload())->assertOk()->assertJsonPath('ticket', 'CS-001');
    }

    public function test_foreign_browser_cannot_read_expired_receipts_or_use_old_context(): void
    {
        $payload = $this->payload();
        $issued = $this->postJson('/ambil-nomor/201', $payload)->assertOk();
        $this->resetPeriod();
        $this->flushSession();
        session()->setId(Str::random(40));
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->getJson($issued->json('print_job.status_url'))->assertForbidden();
        $this->postJson('/ambil-nomor/201', $payload)->assertForbidden();
        $this->postJson('/ambil-nomor/201', [...$payload, 'request_id' => (string) Str::uuid()])->assertForbidden();
        $this->assertDatabaseCount('queues', 0);
    }

    public function test_existing_legacy_receipt_remains_available_until_the_first_reset(): void
    {
        $payload = $this->payload();
        $issued = $this->postJson('/ambil-nomor/201', $payload)->assertOk();
        DB::table('queues')->where('id', $issued->json('id'))->update(['period_id' => null]);
        $this->get($issued->json('print_job.receipt_url'))->assertOk();
        $this->postJson('/ambil-nomor/201', ['service' => 'cs', 'request_id' => $payload['request_id']])->assertOk();
        $this->resetPeriod();
        $this->getJson($issued->json('print_job.status_url'))->assertGone();
        $this->assertDatabaseCount('queues', 0);
    }

    public function test_custom_reset_owns_numbering_across_midnight_and_is_branch_specific(): void
    {
        app(BranchSchedule::class)->save($this->branch, $this->admin, BranchSchedule::defaults(), '03:00');
        $this->branch->refresh();
        $this->postJson('/ambil-nomor/201', $this->payload())->assertOk()->assertJsonPath('ticket', 'CS-001');
        $state = BranchQueueState::findOrFail($this->branch->id);
        $period = $state->period_id;
        $this->travelTo(now()->addDay()->startOfDay());
        $this->getJson('/api/display/data/201')->assertOk()->assertJsonPath('next_cs_number', 'CS-002');
        $this->assertSame($period, $state->fresh()->period_id);
        $this->travelTo(now()->setTime(3, 0));
        $this->getJson('/api/display/data/201')->assertOk()->assertJsonPath('next_cs_number', 'CS-001');
        $this->assertNotSame($period, $state->fresh()->period_id);
        $this->assertDatabaseCount('queues', 0);
        $this->assertSame('2026-10-30 03:00:00', $state->fresh()->next_reset_at->format('Y-m-d H:i:s'));
    }

    public function test_manual_reset_is_idempotent_and_stale_confirmation_is_rejected(): void
    {
        $periods = app(QueuePeriods::class);
        $old = $periods->ensure($this->branch)->period_id;
        $intent = (string) Str::uuid();
        $this->assertSame(0, $periods->reset($this->branch, $this->admin, $old, $intent));
        $new = BranchQueueState::find($this->branch->id)->period_id;
        $this->assertSame(0, $periods->reset($this->branch, $this->admin, $old, $intent));
        $this->assertSame($new, BranchQueueState::find($this->branch->id)->period_id);
        try {
            $periods->reset($this->branch, $this->admin, $old, (string) Str::uuid());
            $this->fail('Stale reset confirmation must be rejected.');
        } catch (HttpException $error) {
            $this->assertSame(409, $error->getStatusCode());
        }
    }

    public function test_schedule_is_enforced_on_open_tabs_and_direct_post_requests(): void
    {
        $payload = $this->payload();
        $this->travelTo(now()->setTime(18, 0));
        $this->get('/ambil-nomor/201')->assertOk()->assertSee('Layanan Tutup');
        $this->getJson('/api/display/data/201')->assertJsonPath('operations.is_open', false);
        $this->postJson('/ambil-nomor/201', $payload)->assertUnprocessable();
        $this->assertDatabaseCount('queues', 0);
    }

    public function test_schedule_rejects_overnight_hours_and_reset_inside_services(): void
    {
        $schedule = BranchSchedule::defaults();
        $schedule[1]['closes'] = '03:00';
        foreach ([[$schedule, '00:00'], [BranchSchedule::defaults(), '09:00']] as [$weekly, $reset]) {
            try {
                app(BranchSchedule::class)->save($this->branch, $this->admin, $weekly, $reset);
                $this->fail('Invalid schedule accepted.');
            } catch (ValidationException $error) {
                $this->assertNotEmpty($error->errors());
            }
        }
        $this->assertNull($this->branch->fresh()->weekly_schedule);
    }

    public function test_changing_reset_never_clears_the_current_queue_immediately(): void
    {
        $this->postJson('/ambil-nomor/201', $this->payload())->assertOk();
        $before = BranchQueueState::find($this->branch->id)->period_id;
        app(BranchSchedule::class)->save($this->branch, $this->admin, BranchSchedule::defaults(), '02:00');
        $state = BranchQueueState::find($this->branch->id);
        $this->assertSame($before, $state->period_id);
        $this->assertSame('2026-10-29 02:00:00', $state->next_reset_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseCount('queues', 1);
    }

    public function test_running_submission_is_preserved_until_acknowledged_and_never_requeued(): void
    {
        $response = $this->postJson('/ambil-nomor/201', $this->payload())->assertOk();
        $job = PrintJob::findOrFail($response->json('request_id'));
        $job->update(['status' => 'processing', 'transport' => 'agent', 'submission_started_at' => now(), 'lease_expires_at' => now()->addMinute()]);
        $this->resetPeriod();
        $this->assertSame('processing', $job->fresh()->status);
        $this->assertDatabaseCount('queues', 1);
        $this->assertSame(0, Queue::withoutGlobalScopes()->currentPeriod()->count());
        $job->update(['status' => 'submitted']);
        app(QueuePeriods::class)->purge($this->branch->id);
        $this->assertDatabaseCount('queues', 0);
        $this->assertDatabaseCount('print_jobs', 0);
    }

    public function test_reset_cancels_pending_prints_and_keeps_device_configuration(): void
    {
        $agent = PrintAgent::create(['id' => (string) Str::uuid(), 'branch_id' => $this->branch->id,
            'name' => 'PC', 'printer_name' => 'Epson', 'device_id' => (string) Str::uuid(), 'token_hash' => 'preserved']);
        $response = $this->postJson('/ambil-nomor/201', $this->payload())->assertOk();
        PrintJob::findOrFail($response->json('request_id'))->update(['status' => 'queued', 'print_agent_id' => $agent->id, 'transport' => 'agent']);
        $this->usePrintTransport($this->branch, 'agent', $agent);
        $this->resetPeriod();
        $this->assertDatabaseCount('queues', 0);
        $this->assertDatabaseCount('print_jobs', 0);
        $this->assertDatabaseCount('kiosk_print_bindings', 1);
        $this->assertDatabaseCount('kiosk_devices', 1);
        $this->assertDatabaseMissing('kiosk_device_events', ['event' => 'ticket_issued']);
        $this->assertSame('preserved', $agent->fresh()->token_hash);
    }

    public function test_scheduler_catches_up_once_and_purges_proofs_after_seven_days(): void
    {
        $this->postJson('/ambil-nomor/201', $this->payload())->assertOk();
        $this->travelTo(now()->addDays(3)->startOfDay());
        $this->artisan('branches:process-operations')->assertSuccessful();
        $period = BranchQueueState::find($this->branch->id)->period_id;
        $this->artisan('branches:process-operations')->assertSuccessful();
        $this->assertSame($period, BranchQueueState::find($this->branch->id)->period_id);
        $this->assertDatabaseHas('operation_scheduler_status', ['id' => '1', 'status' => 'ok']);
        $this->assertDatabaseCount('ticket_request_tombstones', 1);
        $this->travelTo(now()->addDays(7)->addSecond());
        $this->artisan('branches:process-operations')->assertSuccessful();
        $this->assertDatabaseCount('ticket_request_tombstones', 0);
    }

    public function test_reset_also_removes_retired_worker_payloads_without_touching_other_jobs(): void
    {
        $issued = $this->postJson('/ambil-nomor/201', $this->payload())->assertOk();
        foreach ([$issued->json('request_id'), (string) Str::uuid()] as $id) {
            $payload = json_encode(['data' => ['command' => serialize(new SubmitTicketPrint($id))]]);
            DB::table('jobs')->insert(['queue' => 'ticket-printing', 'payload' => $payload, 'attempts' => 0, 'available_at' => time(), 'created_at' => time()]);
            DB::table('failed_jobs')->insert(['uuid' => (string) Str::uuid(), 'connection' => 'printing', 'queue' => 'ticket-printing', 'payload' => $payload, 'exception' => 'fixture']);
        }
        $this->resetPeriod();
        $this->assertDatabaseCount('jobs', 1);
        $this->assertDatabaseCount('failed_jobs', 1);
    }

    public function test_dashboard_filter_and_branch_detail_do_not_expose_other_branches(): void
    {
        $other = Branch::create(['code' => '202', 'slug' => 'foreign', 'name' => 'Cabang Rahasia', 'type' => 'Kantor Cabang', 'is_active' => true]);
        $user = User::factory()->create(['branch_id' => $this->branch->id]);
        $user->assignRole(BranchAdminRole::ensure());
        $this->actingAs($user)->get(BranchDetail::getUrl(['branch' => $other->id]))->assertNotFound();
        Livewire::test(BranchOperations::class)->assertSee($this->branch->name)->assertDontSee($other->name);
        Livewire::test(OperatingHours::class)->assertCanSeeTableRecords([$this->branch])->assertCanNotSeeTableRecords([$other])
            ->assertTableActionHidden('editSchedule', $this->branch)->assertTableActionHidden('resetNow', $this->branch);
        $this->actingAs($this->admin);
        $data = app(BranchDashboard::class)->data($this->admin, (string) $this->branch->id);
        $this->assertSame([$this->branch->id], $data['branches']->modelKeys());
    }
}
