<?php

namespace Tests\Feature;

use App\Filament\Resources\PrintAgentResource;
use App\Filament\Resources\PrintAgentResource\Pages\ManagePrintAgents;
use App\Jobs\SubmitTicketPrint;
use App\Models\Branch;
use App\Models\PrintAgent;
use App\Models\PrintJob;
use App\Models\ServiceSetting;
use App\Models\User;
use App\Services\Printing\AgentPackageBuilder;
use App\Services\Printing\BranchAgentPrinting;
use App\Services\Printing\PrinterGateway;
use App\Services\Printing\PrintStorage;
use App\Services\Printing\PrintWorkerHealth;
use App\Services\Printing\ReceiptRenderer;
use App\Services\Printing\TicketPrinting;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class BranchPrintAgentTest extends TestCase
{
    use RefreshDatabase;

    private array $tokens = [];

    private Branch $branch;

    private PrintAgent $agent;

    protected function setUp(): void
    {
        parent::setUp();
        config(['kiosk.test_mode_enabled' => true, 'printing.transport' => 'agent']);
        $this->travelTo(Carbon::parse('2026-10-15 09:00', 'Asia/Jakarta'));
        $this->branch = $this->office('101');
        $this->agent = $this->agent($this->branch);
        $this->withSession(['_token' => 'agent-test']);
        $this->withCookie(config('session.cookie'), session()->getId())->withCredentials();
        $this->usePrintTransport($this->branch, 'agent', $this->agent);
    }

    private function office(string $code): Branch
    {
        $branch = Branch::create(['code' => $code, 'slug' => 'agent-office-'.$code, 'name' => 'Kantor '.$code, 'type' => 'Kantor Cabang', 'address' => 'Alamat '.$code, 'is_active' => true]);
        foreach (['cs', 'teller'] as $service) {
            ServiceSetting::create(['branch_id' => $branch->id, 'service' => $service, 'label' => $service, 'counter_name' => $service, 'is_active' => true]);
        }

        return $branch;
    }

    private function agent(Branch $branch): PrintAgent
    {
        $token = bin2hex(random_bytes(32));
        $agent = PrintAgent::create(['id' => (string) Str::uuid(), 'branch_id' => $branch->id, 'name' => 'PC '.$branch->code, 'printer_name' => 'Printer '.$branch->code,
            'enabled' => true, 'revoked' => false, 'token_hash' => hash('sha256', $token), 'device_id' => (string) Str::uuid(), 'heartbeat_at' => now(), 'printer_ready' => true]);
        $this->tokens[$agent->id] = $token;

        return $agent;
    }

    private function headers(?PrintAgent $agent = null, ?string $claim = null): array
    {
        $agent ??= $this->agent;

        return ['Authorization' => 'Bearer '.$this->tokens[$agent->id], 'X-Print-Agent' => $agent->id, 'X-Print-Device' => $agent->device_id, 'X-Print-Claim' => $claim ?? ''];
    }

    private function issue(string $service = 'cs', ?Branch $branch = null): PrintJob
    {
        $branch ??= $this->branch;
        $response = $this->issueTicket('/ambil-nomor/'.$branch->code, ['service' => $service, 'request_id' => (string) Str::uuid()])->assertOk();

        return PrintJob::findOrFail($response->json('request_id'));
    }

    private function claim(?PrintAgent $agent = null): array
    {
        return $this->postJson('/api/print-agent/claim', [], $this->headers($agent))->assertOk()->json('job');
    }

    private function start(PrintJob $job, array $claim): void
    {
        $this->getJson('/api/print-agent/jobs/'.$job->id.'/pdf', $this->headers(claim: $claim['claim_token']))->assertOk();
        $this->postJson('/api/print-agent/jobs/'.$job->id.'/start', ['pdf_sha256' => $job->fresh()->pdf_sha256], $this->headers(claim: $claim['claim_token']))->assertOk();
    }

    public function test_cs_and_teller_route_to_branch_agent_without_native_server_or_database_worker(): void
    {
        app(PrinterGateway::class)->availabilityFailure = new \RuntimeException('Native server printing must not run');
        config(['printing.require_worker' => true]);
        foreach (['cs', 'teller'] as $service) {
            $job = $this->issue($service);
            $this->assertSame($this->agent->id, $job->print_agent_id);
            $this->assertSame('Printer 101', $job->printer_name);
            app()->call([new SubmitTicketPrint($job->id), 'handle']);
            $this->assertSame('queued', $job->fresh()->status);
        }
        $this->assertDatabaseCount('jobs', 0);
        $this->assertCount(0, app(PrinterGateway::class)->submissions);
    }

    public function test_missing_offline_stale_or_revoked_agent_does_not_issue_or_fall_back(): void
    {
        foreach ([['printer_ready' => false], ['heartbeat_at' => now()->subMinute()], ['revoked' => true]] as $changes) {
            $this->agent->update(['printer_ready' => true, 'heartbeat_at' => now(), 'revoked' => false, ...$changes]);
            $this->issueTicket('/ambil-nomor/101', ['service' => 'cs'])->assertStatus(503);
        }
        $other = $this->office('102');
        $this->assertDatabaseCount('queues', 0);
        $this->issueTicket('/ambil-nomor/102', ['service' => 'cs'])->assertOk()->assertJsonPath('print_job.transport', 'browser');
    }

    public function test_api_requires_secret_and_device_even_for_logged_in_admin(): void
    {
        $this->postJson('/api/print-agent/claim')->assertUnauthorized();
        $this->postJson('/api/print-agent/claim', [], [...$this->headers(), 'Authorization' => 'Bearer '.str_repeat('0', 64)])->assertUnauthorized();
        $this->postJson('/api/print-agent/claim', [], [...$this->headers(), 'X-Print-Device' => (string) Str::uuid()])->assertUnauthorized();
        $this->assertArrayNotHasKey('token_hash', $this->agent->toArray());
    }

    public function test_agent_can_only_claim_and_download_its_own_branch_jobs(): void
    {
        $job = $this->issue();
        $other = $this->agent($this->office('102'));
        $this->postJson('/api/print-agent/claim', ['branch_id' => $this->branch->id], $this->headers($other))->assertOk()->assertJsonPath('job', null);
        $claim = $this->claim();
        $this->assertSame($job->id, $claim['id']);
        $this->getJson('/api/print-agent/jobs/'.$job->id.'/pdf', $this->headers($other, $claim['claim_token']))->assertForbidden();
        $this->getJson('/api/print-agent/jobs/'.$job->id.'/pdf', $this->headers(claim: str_repeat('a', 64)))->assertForbidden();
    }

    public function test_second_claim_cannot_redeliver_an_in_flight_job(): void
    {
        $job = $this->issue();
        $claim = $this->claim();
        $this->postJson('/api/print-agent/claim', [], $this->headers())->assertOk()->assertJsonPath('job', null);
        $this->assertSame('processing', $job->fresh()->status);
        $this->assertSame(hash('sha256', $claim['claim_token']), $job->fresh()->claim_hash);
    }

    public function test_pdf_and_one_time_start_use_the_immutable_receipt_hash(): void
    {
        $job = $this->issue();
        $claim = $this->claim();
        $this->branch->update(['address' => 'CHANGED']);
        $response = $this->getJson('/api/print-agent/jobs/'.$job->id.'/pdf', $this->headers(claim: $claim['claim_token']))->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertSame($job->fresh()->pdf_sha256, $response->headers->get('X-Pdf-Sha256'));
        $this->postJson('/api/print-agent/jobs/'.$job->id.'/start', ['pdf_sha256' => str_repeat('0', 64)], $this->headers(claim: $claim['claim_token']))->assertConflict();
        $this->start($job, $claim);
        $this->postJson('/api/print-agent/jobs/'.$job->id.'/start', ['pdf_sha256' => $job->fresh()->pdf_sha256], $this->headers(claim: $claim['claim_token']))->assertConflict();
        $this->assertSame('Alamat 101', $job->snapshot['branch_address']);
    }

    public function test_completion_is_idempotent_and_cannot_claim_success_before_start(): void
    {
        $job = $this->issue();
        $claim = $this->claim();
        $url = '/api/print-agent/jobs/'.$job->id.'/complete';
        $this->postJson($url, ['status' => 'submitted'], $this->headers(claim: $claim['claim_token']))->assertConflict();
        $this->start($job, $claim);
        foreach ([1, 2] as $_) {
            $this->postJson($url, ['status' => 'submitted'], $this->headers(claim: $claim['claim_token']))->assertOk();
        }
        $this->postJson($url, ['status' => 'unknown'], $this->headers(claim: $claim['claim_token']))->assertConflict();
        $this->assertSame('submitted', $job->fresh()->status);
        $this->assertFileDoesNotExist(PrintStorage::directory().'/'.$job->id.'.pdf');
    }

    public function test_failure_after_start_is_unknown_and_never_resubmitted(): void
    {
        $job = $this->issue();
        $claim = $this->claim();
        $this->start($job, $claim);
        $url = '/api/print-agent/jobs/'.$job->id.'/complete';
        $this->postJson($url, ['status' => 'failed'], $this->headers(claim: $claim['claim_token']))->assertConflict();
        $this->postJson($url, ['status' => 'unknown', 'failure_code' => 'native_timeout'], $this->headers(claim: $claim['claim_token']))->assertOk();
        $this->postJson('/api/print-agent/claim', [], $this->headers())->assertOk()->assertJsonPath('job', null);
        $this->assertDatabaseCount('queues', 1);
    }

    public function test_lost_agent_lease_becomes_unknown_and_queued_job_expires(): void
    {
        $inFlight = $this->issue();
        $this->claim();
        $waiting = $this->issue('teller');
        $this->travel(91)->seconds();
        app(PrintWorkerHealth::class)->expireWaiting();
        $this->assertSame('unknown', $inFlight->fresh()->status);
        $this->assertSame('expired', $waiting->fresh()->status);
        $this->agent->update(['heartbeat_at' => now()]);
        $this->postJson('/api/print-agent/claim', [], $this->headers())->assertOk()->assertJsonPath('job', null);
    }

    public function test_local_worker_restart_does_not_interrupt_remote_agent_job_or_delete_its_pdf(): void
    {
        $job = $this->issue();
        $claim = $this->claim();
        $this->getJson('/api/print-agent/jobs/'.$job->id.'/pdf', $this->headers(claim: $claim['claim_token']))->assertOk();
        app(PrintWorkerHealth::class)->recoverInterrupted();
        $this->assertSame('processing', $job->fresh()->status);
        $this->assertFileExists(PrintStorage::directory().'/'.$job->id.'.pdf');
        app(BranchAgentPrinting::class)->complete($job, 'failed', 'test_cleanup');
    }

    public function test_render_failure_is_recorded_without_granting_native_submission(): void
    {
        $job = $this->issue();
        $claim = $this->claim();
        $this->mock(ReceiptRenderer::class)->shouldReceive('render')->once()->andThrow(new \RuntimeException('Failed render'));
        $this->getJson('/api/print-agent/jobs/'.$job->id.'/pdf', $this->headers(claim: $claim['claim_token']))->assertUnprocessable();
        $this->assertSame('failed', $job->fresh()->status);
        $this->assertNull($job->fresh()->submission_started_at);
    }

    public function test_heartbeat_is_scoped_and_does_not_allow_remote_printer_override(): void
    {
        $payload = ['printer_ready' => true, 'printer_name' => 'Printer 101', 'machine_name' => 'TEST-PC', 'version' => '1.0.0'];
        $this->postJson('/api/print-agent/heartbeat', $payload, $this->headers())->assertOk();
        $this->postJson('/api/print-agent/heartbeat', [...$payload, 'printer_name' => 'Microsoft Print to PDF'], $this->headers())->assertConflict();
        $this->assertSame('TEST-PC', $this->agent->fresh()->machine_name);
    }

    public function test_enrollment_is_short_lived_one_time_and_rotates_old_credentials(): void
    {
        $key = bin2hex(random_bytes(32));
        $this->agent->update(['enrollment_hash' => hash('sha256', $key), 'enrollment_expires_at' => now()->addMinutes(15)]);
        $payload = ['agent_id' => $this->agent->id, 'enrollment_key' => $key, 'device_id' => (string) Str::uuid(), 'printer_name' => 'Printer Baru'];
        $response = $this->postJson('/api/print-agent/enroll', $payload)->assertOk();
        $this->assertNotSame($response->json('token'), $this->agent->fresh()->token_hash);
        $this->assertNull($this->agent->fresh()->enrollment_hash);
        $this->postJson('/api/print-agent/enroll', $payload)->assertUnauthorized();
        $this->postJson('/api/print-agent/claim', [], $this->headers())->assertUnauthorized();
    }

    public function test_expired_enrollment_and_production_http_are_rejected(): void
    {
        $key = bin2hex(random_bytes(32));
        $this->agent->update(['enrollment_hash' => hash('sha256', $key), 'enrollment_expires_at' => now()->subSecond()]);
        $payload = ['agent_id' => $this->agent->id, 'enrollment_key' => $key, 'device_id' => (string) Str::uuid(), 'printer_name' => 'Printer 101'];
        $this->postJson('/api/print-agent/enroll', $payload)->assertUnauthorized();
        $this->app['env'] = 'production';
        try {
            $this->postJson('/api/print-agent/claim', [], $this->headers())->assertForbidden();
            $this->postJson('/api/print-agent/enroll', $payload)->assertForbidden();
            $this->postJson('https://localhost/api/print-agent/claim', [], $this->headers())->assertOk();
        } finally {
            $this->app['env'] = 'testing';
        }
    }

    public function test_reprint_uses_old_number_and_snapshot_with_a_new_agent_attempt(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');
        $job = $this->issue();
        $claim = $this->claim();
        $this->start($job, $claim);
        app(BranchAgentPrinting::class)->complete($job, 'submitted', null);
        $this->branch->update(['address' => 'NEW']);
        $again = app(TicketPrinting::class)->reprint($job->ticket, $admin, $this->printingRequest());
        $this->assertSame($job->snapshot, $again->snapshot);
        $this->assertSame($this->agent->id, $again->print_agent_id);
        $this->assertSame($job->queue_id, $again->queue_id);
        $this->assertNotSame($job->id, $again->id);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_installer_requires_admin_and_contains_only_a_one_time_enrollment_secret(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');
        $serverUrl = 'https://bank.example.test/sistem-antrian-bank/public';
        $path = app(AgentPackageBuilder::class)->build($this->agent, $serverUrl, $admin);
        try {
            $zip = new \ZipArchive;
            $this->assertTrue($zip->open($path));
            $setup = json_decode($zip->getFromName('setup.json'), true);
            $this->assertSame($this->agent->id, $setup['agent_id']);
            $this->assertSame($serverUrl, $setup['server_url']);
            $this->assertSame(app(AgentPackageBuilder::class)->windowsInstallerVersion(), $setup['installer_version']);
            $this->assertSame($setup['installer_version'], trim($zip->getFromName('VERSION.txt')));
            $this->assertSame(hash('sha256', $setup['enrollment_key']), $this->agent->fresh()->enrollment_hash);
            $this->assertArrayNotHasKey('token', $setup);
            foreach (['Install-Agent.cmd', 'Install-Agent.ps1', 'Start-Agent.ps1', 'Agent.Common.ps1', 'Printer.Setup.ps1', 'VERSION.txt', 'CheckPrinter.cs', 'Print-TicketPdf.ps1', 'Keep-PrinterDefault.ps1'] as $file) {
                $this->assertNotFalse($zip->getFromName($file));
                if (is_file(base_path('agent/'.$file))) {
                    $this->assertSame(file_get_contents(base_path('agent/'.$file)), $zip->getFromName($file));
                }
            }
            $zip->close();
        } finally {
            @unlink($path);
        }
        $user = User::factory()->create(['is_active' => true]);
        $this->expectException(HttpException::class);
        app(AgentPackageBuilder::class)->build($this->agent, 'https://bank.example.test', $user);
    }

    public function test_admin_management_page_is_native_and_denies_officers(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');
        $this->actingAs($admin);
        Livewire::test(ManagePrintAgents::class, ['branch' => $this->branch->id])->assertSuccessful()->assertCanSeeTableRecords([$this->agent]);
        $this->assertTrue(PrintAgentResource::canViewAny());
        $this->actingAs(User::factory()->create(['is_active' => true]));
        $this->assertFalse(PrintAgentResource::canViewAny());
        $this->get('/admin/print-agents')->assertForbidden();
    }

    public function test_android_package_uses_one_time_enrollment_and_contains_no_windows_installer_or_permanent_secret(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');
        $path = app(AgentPackageBuilder::class)->build($this->agent, 'https://bank.example.test', $admin, 'android');
        try {
            $zip = new \ZipArchive;
            $this->assertTrue($zip->open($path));
            $this->assertSame(2, $zip->numFiles);
            $setup = json_decode($zip->getFromName('setup.json'), true);
            $this->assertSame('android', $setup['platform']);
            $this->assertSame($this->branch->code, $setup['branch_code']);
            $this->assertSame(hash('sha256', $setup['enrollment_key']), $this->agent->fresh()->enrollment_hash);
            $this->assertArrayNotHasKey('token', $setup);
            $this->assertFalse($zip->getFromName('Install-Agent.cmd'));
            $this->assertStringContainsString('Wi-Fi', $zip->getFromName('README.txt'));
            $zip->close();
            $this->postJson('/api/print-agent/enroll', [
                'agent_id' => $setup['agent_id'], 'enrollment_key' => $setup['enrollment_key'],
                'device_id' => (string) Str::uuid(), 'printer_name' => 'Epson L6290 Wi-Fi',
            ])->assertOk();
            $this->assertNull($this->agent->fresh()->enrollment_hash);
        } finally {
            @unlink($path);
        }
    }

    public function test_android_heartbeat_and_cs_teller_jobs_use_the_same_branch_guards(): void
    {
        $this->postJson('/api/print-agent/heartbeat', [
            'printer_ready' => true, 'printer_name' => $this->agent->printer_name,
            'machine_name' => 'Android tablet', 'version' => 'android-1.0.0',
        ], $this->headers())->assertOk();
        foreach (['cs', 'teller'] as $service) {
            $job = $this->issue($service);
            $claim = $this->claim();
            $this->start($job, $claim);
            $this->postJson('/api/print-agent/jobs/'.$job->id.'/complete', ['status' => 'submitted'], $this->headers(claim: $claim['claim_token']))->assertOk();
            $this->assertSame('submitted', $job->fresh()->status);
            $this->assertSame($this->agent->id, $job->print_agent_id);
        }
        $this->assertDatabaseCount('jobs', 0);
        $this->assertSame('Android tablet', $this->agent->fresh()->machine_name);
    }

    public function test_native_admin_action_downloads_the_android_branch_package(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');
        $this->actingAs($admin);
        Livewire::test(ManagePrintAgents::class, ['branch' => $this->branch->id])->assertTableActionDoesNotExist('androidPackage');
        $this->get(PrintAgentResource::forBranchUrl($this->branch))->assertOk()->assertDontSee('Unduh Aplikasi Android')->assertDontSee('Unduh Paket Android');
    }

    public function test_native_admin_action_downloads_the_versioned_windows_package(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');
        $this->actingAs($admin);
        $version = app(AgentPackageBuilder::class)->windowsInstallerVersion();
        Livewire::test(ManagePrintAgents::class, ['branch' => $this->branch->id])
            ->callTableAction('installer', $this->agent, data: ['server_url' => 'https://bank.example.test'])
            ->assertHasNoActionErrors()
            ->assertFileDownloaded('Penghubung-Cetak-101-v'.$version.'-20261015-090000.zip');
    }

    public static function installerBaseUrls(): array
    {
        return [
            'standalone server' => ['http://192.168.11.60:7100'],
            'application in Apache subdirectory' => ['http://192.168.11.60/sistem-antrian-bank/public'],
            'hosted application in subdirectory' => ['https://bank.example.test/antrean'],
        ];
    }

    #[DataProvider('installerBaseUrls')]
    public function test_package_download_defaults_to_the_full_application_base_url(string $baseUrl): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');
        $this->actingAs($admin);
        $component = Livewire::test(ManagePrintAgents::class, ['branch' => $this->branch->id])->instance();
        $originalRequest = request();
        $basePath = parse_url($baseUrl, PHP_URL_PATH) ?? '';
        $request = Request::create($baseUrl.'/admin/print-agents', server: [
            'SCRIPT_NAME' => $basePath.'/index.php',
            'PHP_SELF' => $basePath.'/index.php',
            'SCRIPT_FILENAME' => base_path('public/index.php'),
        ]);
        $this->app->instance('request', $request);
        URL::setRequest($request);

        try {
            $this->assertSame($baseUrl, $request->root());
            foreach (['installer'] as $action) {
                $component->mountAction($action, context: [
                    'table' => true,
                    'recordKey' => $this->agent->getKey(),
                ]);
                $this->assertSame($baseUrl, $component->mountedActions[0]['data']['server_url']);
                $component->unmountAction();
            }
        } finally {
            $this->app->instance('request', $originalRequest);
            URL::setRequest($originalRequest);
        }
    }
}
