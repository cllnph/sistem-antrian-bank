<?php

namespace Tests\Feature;

use App\Jobs\SubmitTicketPrint;
use App\Models\Branch;
use App\Models\PrintJob;
use App\Models\ServiceSetting;
use App\Models\User;
use App\Services\Printing\FakePrinterGateway;
use App\Services\Printing\PrinterConfiguration;
use App\Services\Printing\PrinterGateway;
use App\Services\Printing\PrintOutcomeUnknown;
use App\Services\Printing\PrintStorage;
use App\Services\Printing\PrintWorkerHealth;
use App\Services\Printing\ReceiptRenderer;
use App\Services\Printing\TicketPrinting;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ServerTicketPrintingTest extends TestCase
{
    use RefreshDatabase;

    private Branch $office;

    private FakePrinterGateway $printer;

    protected function setUp(): void
    {
        parent::setUp();
        config(['kiosk.test_mode_enabled' => true]);
        $this->office = Branch::create(['code' => '101', 'slug' => 'test-print-office', 'name' => 'Kantor Cabang Bogor', 'type' => 'Kantor Cabang', 'address' => 'Jl. Achmad Adnawijaya No.8, Bogor', 'phone' => 'SECRET-BRANCH-PHONE', 'is_active' => true]);
        foreach (['cs', 'teller'] as $service) {
            ServiceSetting::create(['branch_id' => $this->office->id, 'service' => $service, 'label' => $service, 'counter_name' => 'Loket '.$service, 'is_active' => true]);
        }
        $this->printer = app(PrinterGateway::class);
        $this->withSession(['_token' => 'print-test-token']);
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->withCredentials();
        $this->usePrintTransport($this->office, 'server');
    }

    private function issue(string $service = 'cs', ?string $uuid = null): TestResponse
    {
        return $this->issueTicket('/ambil-nomor/101', ['service' => $service, 'request_id' => $uuid ?? (string) Str::uuid(), 'branch_id' => $this->office->id]);
    }

    private function process(PrintJob $job): void
    {
        app()->call([new SubmitTicketPrint($job->id), 'handle']);
    }

    public function test_cs_and_teller_are_atomically_queued_on_the_dedicated_database_queue(): void
    {
        foreach (['cs' => 'CS-001', 'teller' => 'T-001'] as $service => $number) {
            $response = $this->issue($service)->assertOk()->assertJsonPath('ticket', $number)->assertJsonPath('print_job.status', 'queued');
            $job = PrintJob::findOrFail($response->json('request_id'));
            $this->assertEquals($response->json('id'), $job->queue_id);
            $this->assertNull($response->json('branch_phone'));
            $this->assertDatabaseHas('jobs', ['queue' => 'ticket-printing', 'attempts' => 0]);
            $this->process($job);
            $this->assertSame('submitted', $job->fresh()->status);
            $this->getJson($response->json('print_job.status_url'))->assertOk()->assertJsonPath('print_job.status', 'submitted');
            $this->assertFileDoesNotExist(PrintStorage::directory().'/'.$job->id.'.pdf');
        }
        $this->assertCount(2, $this->printer->submissions);
        $this->assertDatabaseCount('queues', 2);
        $this->assertDatabaseCount('print_jobs', 2);
    }

    public function test_duplicate_uuid_returns_the_same_ticket_and_job_even_when_printer_later_fails(): void
    {
        $id = (string) Str::uuid();
        $first = $this->issue('cs', $id)->assertOk();
        $this->printer->availabilityFailure = new \RuntimeException('Tidak siap');
        config(['kiosk.test_mode_enabled' => false]);
        $this->travelTo(now()->setTime(23, 28));
        $again = $this->issue('cs', $id)->assertOk();
        $this->assertSame($first->json('id'), $again->json('id'));
        $this->assertSame($id, $again->json('print_job.id'));
        $this->assertDatabaseCount('queues', 1);
        $this->assertDatabaseCount('print_jobs', 1);
        $this->assertDatabaseCount('jobs', 1);
    }

    public function test_reusing_uuid_for_a_different_service_is_rejected(): void
    {
        $id = (string) Str::uuid();
        $this->issue('cs', $id)->assertOk();
        $this->issue('teller', $id)->assertConflict();
        $this->assertDatabaseCount('queues', 1);
    }

    public function test_status_and_replay_are_restricted_to_the_requesting_session(): void
    {
        $response = $this->issue()->assertOk();
        $job = PrintJob::firstOrFail();
        $job->update(['owner_hash' => hash('sha256', 'different-session')]);
        $this->getJson($response->json('print_job.status_url'))->assertForbidden();
        $this->issue('cs', $job->id)->assertForbidden();
    }

    public function test_unavailable_printer_prevents_ticket_and_job_creation(): void
    {
        $this->printer->availabilityFailure = new \RuntimeException('Printer tidak siap.');
        $this->issue()->assertStatus(503)->assertJsonPath('success', false);
        $this->assertDatabaseCount('queues', 0);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_worker_requires_a_recent_heartbeat_before_issuance(): void
    {
        config(['printing.require_worker' => true]);
        $this->issue()->assertStatus(503);
        $this->assertDatabaseCount('queues', 0);
        app(PrintWorkerHealth::class)->beat();
        $this->issue()->assertOk();
        DB::table('printer_workers')->update(['heartbeat_at' => now()->subMinute()]);
        $this->issue()->assertStatus(503);
        $this->assertDatabaseCount('queues', 1);
    }

    public function test_redelivery_of_the_same_job_never_submits_twice(): void
    {
        $this->issue()->assertOk();
        $job = PrintJob::firstOrFail();
        $this->process($job);
        $this->process($job);
        $this->assertCount(1, $this->printer->submissions);
        $this->assertSame(1, (new SubmitTicketPrint($job->id))->tries);
    }

    public function test_render_failure_does_not_call_printer_and_keeps_original_number(): void
    {
        $this->issue()->assertOk();
        $renderer = $this->mock(ReceiptRenderer::class);
        $renderer->shouldReceive('render')->once()->andThrow(new \RuntimeException('Render failed'));
        $job = PrintJob::firstOrFail();
        $this->process($job);
        $this->assertSame('failed', $job->fresh()->status);
        $this->assertSame('CS-001', $job->snapshot['ticket']);
        $this->assertCount(0, $this->printer->submissions);
        $this->assertDatabaseCount('queues', 1);
    }

    public function test_printer_failure_after_issuance_does_not_create_a_replacement(): void
    {
        $response = $this->issue()->assertOk();
        $this->printer->availabilityFailure = new \RuntimeException('Disconnected');
        $job = PrintJob::firstOrFail();
        $this->process($job);
        $this->getJson($response->json('print_job.status_url'))->assertOk()->assertJsonPath('ticket', 'CS-001')->assertJsonPath('print_job.status', 'failed');
        $this->assertDatabaseCount('queues', 1);
        $this->assertCount(0, $this->printer->submissions);
    }

    public function test_timeout_becomes_unknown_and_is_never_automatically_retried(): void
    {
        $this->issue()->assertOk();
        $this->printer->submissionFailure = new PrintOutcomeUnknown('Timeout');
        $job = PrintJob::firstOrFail();
        $this->process($job);
        $this->assertSame('unknown', $job->fresh()->status);
        $this->process($job);
        $this->assertCount(1, $this->printer->submissions);
        $this->assertFileDoesNotExist(PrintStorage::directory().'/'.$job->id.'.pdf');
    }

    public function test_expired_jobs_never_reach_the_printer(): void
    {
        $this->issue()->assertOk();
        $job = PrintJob::firstOrFail();
        $this->travel(60)->seconds();
        $this->process($job);
        $this->assertSame('expired', $job->fresh()->status);
        $this->assertCount(0, $this->printer->submissions);
    }

    public function test_restart_marks_interrupted_job_unknown_without_resubmission(): void
    {
        $this->issue()->assertOk();
        $job = PrintJob::firstOrFail();
        $job->update(['status' => 'processing', 'started_at' => now()]);
        app(PrintWorkerHealth::class)->recoverInterrupted();
        $this->process($job);
        $this->assertSame('unknown', $job->fresh()->status);
        $this->assertCount(0, $this->printer->submissions);
    }

    public function test_pdf_is_one_page_80_by_150_mm_with_local_logo_and_without_branch_phone_or_url(): void
    {
        $this->issue()->assertOk();
        $snapshot = PrintJob::firstOrFail()->snapshot;
        $html = view('queue.receipt-pdf', ['receipt' => $snapshot])->render();
        $this->assertStringContainsString('data:image/png;base64,', $html);
        foreach (['Kantor Cabang Bogor', 'Jl. Achmad', 'CS-001', 'Terima kasih'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        foreach (['SECRET-BRANCH-PHONE', 'http://', 'https://', 'verifikasi'] as $text) {
            $this->assertStringNotContainsString($text, $html);
        }
        $pdf = app(ReceiptRenderer::class)->render($snapshot);
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertSame(1, preg_match_all('~/Type /Page\b~', $pdf));
        $this->assertMatchesRegularExpression('~/MediaBox \[0\.000 0\.000 226\.772 425\.197\]~', $pdf);
    }

    public function test_only_head_office_receipt_keeps_phone(): void
    {
        $this->office->update(['type' => 'Kantor Pusat', 'phone' => '021-8467944']);
        $this->issue()->assertOk()->assertJsonPath('branch_phone', '021-8467944');
        $html = view('queue.receipt-pdf', ['receipt' => PrintJob::firstOrFail()->snapshot])->render();
        $this->assertStringContainsString('Telp: 021-8467944', $html);
    }

    public function test_production_requires_explicit_branch_mapping_and_rejects_pdf_destination(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'printer-config');
        config(['printing.config_path' => $path]);
        $this->app['env'] = 'production';
        try {
            file_put_contents($path, json_encode(['printer_name' => 'ITPRINTER (L6290 Series)']));
            try {
                app(PrinterConfiguration::class)->forBranch($this->office);
                $this->fail('Production fallback accepted');
            } catch (\RuntimeException $e) {
                $this->assertStringContainsString('belum dikonfigurasi', $e->getMessage());
            }
            file_put_contents($path, json_encode(['branch_printers' => ['101' => 'ITPRINTER (L6290 Series)']]));
            $this->assertSame('ITPRINTER (L6290 Series)', app(PrinterConfiguration::class)->forBranch($this->office)['printer']);
            file_put_contents($path, json_encode(['branch_printers' => ['101' => 'Microsoft Print to PDF']]));
            $this->expectException(\RuntimeException::class);
            app(PrinterConfiguration::class)->forBranch($this->office);
        } finally {
            unlink($path);
            $this->app['env'] = 'testing';
        }
    }

    public function test_reprint_is_authorized_preserves_snapshot_and_records_a_new_attempt(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $setting = ServiceSetting::where('branch_id', $this->office->id)->where('service', 'cs')->firstOrFail();
        $officer = User::factory()->create(['branch_id' => $this->office->id, 'service_setting_id' => $setting->id, 'is_active' => true]);
        $officer->assignRole('cs');
        $this->issue()->assertOk();
        $original = PrintJob::firstOrFail();
        $this->process($original);
        $this->office->update(['address' => 'NEW ADDRESS']);
        $reprint = app(TicketPrinting::class)->reprint($original->ticket, $officer, $this->printingRequest());
        $this->assertSame($original->queue_id, $reprint->queue_id);
        $this->assertSame($original->snapshot, $reprint->snapshot);
        $this->assertSame($original->id, $reprint->original_job_id);
        $this->assertSame($officer->id, $reprint->actor_id);
        $this->process($reprint);
        $this->assertCount(2, $this->printer->submissions);
        $this->assertDatabaseCount('queues', 1);
        $this->assertDatabaseHas('queue_logs', ['action' => 'print_requested', 'actor_id' => $officer->id]);
        $this->actingAs($officer)->getJson(route('print-jobs.show', $original->id))->assertOk();
    }

    public function test_unprivileged_or_cross_branch_staff_cannot_reprint(): void
    {
        $this->issue()->assertOk();
        $user = User::factory()->create(['is_active' => true]);
        $this->expectException(AuthorizationException::class);
        app(TicketPrinting::class)->reprint(PrintJob::firstOrFail()->ticket, $user);
    }

    public function test_staff_with_permission_cannot_reprint_another_branch_ticket(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $other = Branch::create(['code' => '102', 'slug' => 'other-print-office', 'name' => 'Kantor Lain', 'type' => 'Kantor Cabang', 'is_active' => true]);
        $setting = ServiceSetting::create(['branch_id' => $other->id, 'service' => 'cs', 'label' => 'CS', 'counter_name' => 'CS Lain', 'is_active' => true]);
        $user = User::factory()->create(['branch_id' => $other->id, 'service_setting_id' => $setting->id, 'is_active' => true]);
        $user->assignRole('cs');
        $response = $this->issue()->assertOk();
        PrintJob::firstOrFail()->update(['owner_hash' => hash('sha256', 'another-session')]);
        $this->actingAs($user)->getJson($response->json('print_job.status_url'))->assertForbidden();
        $this->expectException(AuthorizationException::class);
        app(TicketPrinting::class)->reprint(PrintJob::firstOrFail()->ticket, $user);
    }

    public function test_pending_reprint_is_rejected_without_an_extra_job(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('admin');
        $this->issue()->assertOk();
        try {
            app(TicketPrinting::class)->reprint(PrintJob::firstOrFail()->ticket, $user);
            $this->fail('An active attempt was duplicated');
        } catch (HttpException $e) {
            $this->assertSame(409, $e->getStatusCode());
            $this->assertDatabaseCount('print_jobs', 1);
        }
    }

    public function test_browser_cannot_override_printer_file_or_receipt(): void
    {
        $this->issueTicket('/ambil-nomor/101', [
            'service' => 'cs', 'request_id' => (string) Str::uuid(), 'printer_name' => 'Microsoft Print to PDF',
            'executable' => 'evil.exe', 'file' => 'C:/private.txt', 'snapshot' => ['ticket' => 'FORGED'],
        ])->assertOk()->assertJsonPath('ticket', 'CS-001');
        $job = PrintJob::firstOrFail();
        $this->assertSame('ITPRINTER (L6290 Series)', $job->printer_name);
        $this->assertSame('CS-001', $job->snapshot['ticket']);
        $this->assertArrayNotHasKey('file', $job->snapshot);
    }

    public function test_kiosk_contains_no_browser_print_or_pdf_opening_call(): void
    {
        $html = $this->get('/ambil-nomor/101')->assertOk()->getContent();
        $this->assertStringNotContainsString('window.print(', $html);
        $this->assertStringNotContainsString('window.open(', $html);
        $this->assertStringContainsString('request_id: pending.request_id', $html);
        $this->assertStringContainsString('job.status_url', $html);
    }

    public function test_database_queue_insertion_failure_rolls_back_ticket_and_ledger(): void
    {
        DB::statement('DROP TABLE jobs');
        $this->withoutExceptionHandling();
        try {
            $this->issue();
            $this->fail('Queue insertion unexpectedly succeeded');
        } catch (QueryException) {
            $this->assertDatabaseCount('queues', 0);
            $this->assertDatabaseCount('print_jobs', 0);
        }
    }
}
