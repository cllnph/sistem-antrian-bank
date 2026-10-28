<?php

namespace Tests\Feature;

use App\Filament\Resources\PrintAgentResource\Pages\ManagePrintAgents;
use App\Filament\Resources\QueueResource\Pages\ManageQueues;
use App\Jobs\SubmitTicketPrint;
use App\Models\Branch;
use App\Models\PrintAgent;
use App\Models\PrintJob;
use App\Models\ServiceSetting;
use App\Models\User;
use App\Services\KioskDeviceHistory;
use App\Services\Printing\BranchAgentPrinting;
use App\Services\Printing\KioskPrintRouting;
use App\Services\Printing\PrintAgentManagement;
use App\Services\Printing\PrinterGateway;
use App\Services\Printing\PrintWorkerHealth;
use App\Services\Printing\TicketPrinting;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class DevicePrintRoutingTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        config(['kiosk.test_mode_enabled' => true]);
        $this->branch = $this->office('001');
        $this->withSession(['_token' => 'routing-test']);
        $this->withCookie(config('session.cookie'), session()->getId())->withCredentials();
    }

    private function office(string $code): Branch
    {
        $branch = Branch::create(['code' => $code, 'slug' => 'routing-'.$code, 'name' => 'Kantor '.$code, 'type' => 'Kantor Pusat', 'address' => 'Alamat '.$code, 'is_active' => true]);
        foreach (['cs', 'teller'] as $service) {
            ServiceSetting::create(['branch_id' => $branch->id, 'service' => $service, 'label' => $service, 'counter_name' => $service, 'is_active' => true]);
        }

        return $branch;
    }

    private function agent(string $printer = 'EPSON L3250 Series', ?Branch $branch = null): PrintAgent
    {
        return PrintAgent::create(['id' => (string) Str::uuid(), 'branch_id' => ($branch ?? $this->branch)->id, 'name' => 'PC '.$printer,
            'printer_name' => $printer, 'enabled' => true, 'revoked' => false, 'printer_ready' => true, 'heartbeat_at' => now(),
            'device_id' => (string) Str::uuid(), 'token_hash' => hash('sha256', 'test-token')]);
    }

    private function admin(): User
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');
        $this->actingAs($admin);

        return $admin;
    }

    private function browser(string $letter, ?User $user = null): Request
    {
        $session = new Store('routing-browser', new ArraySessionHandler(120));
        $session->setId(str_repeat($letter, 40));
        $session->start();
        $request = Request::create('/ambil-nomor/001', 'GET', server: ['REMOTE_ADDR' => '192.168.11.20', 'HTTP_USER_AGENT' => 'Windows Chrome']);
        $request->setLaravelSession($session);
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    public function test_new_pc_prints_locally_even_when_branch_agents_and_server_are_configured(): void
    {
        $this->agent('ITPRINTER (L6290 Series)');
        $this->agent();
        config(['printing.transport' => 'server', 'printing.require_worker' => true]);
        app(PrinterGateway::class)->availabilityFailure = new \RuntimeException('Server printer unavailable');
        $id = (string) Str::uuid();
        $response = $this->issueTicket('/ambil-nomor/001', ['service' => 'cs', 'request_id' => $id])->assertOk()
            ->assertJsonPath('print_job.transport', 'browser')->assertJsonPath('print_job.status', 'browser_ready');
        $job = PrintJob::findOrFail($id);
        app()->call([new SubmitTicketPrint($id), 'handle']);
        app(PrintWorkerHealth::class)->recoverInterrupted();
        $this->assertSame('browser_ready', $job->fresh()->status);
        $this->assertNull($job->print_agent_id);
        $this->assertDatabaseCount('jobs', 0);
        $this->assertCount(0, app(PrinterGateway::class)->submissions);
        $this->get($response->json('print_job.receipt_url'))->assertOk()->assertSee('CS-001')->assertSee('Alamat 001');
    }

    public function test_duplicate_and_reopening_receipt_preserve_number_without_dispatch(): void
    {
        $id = (string) Str::uuid();
        $payload = ['service' => 'teller', 'request_id' => $id];
        $response = $this->issueTicket('/ambil-nomor/001', $payload)->assertOk();
        $this->issueTicket('/ambil-nomor/001', $payload)->assertOk()->assertJsonPath('ticket', 'T-001');
        $this->get($response->json('print_job.receipt_url'))->assertOk();
        $this->get($response->json('print_job.receipt_url'))->assertOk();
        $this->assertDatabaseCount('queues', 1);
        $this->assertDatabaseCount('print_jobs', 1);
        $this->assertDatabaseCount('jobs', 0);
        $this->assertSame('browser_ready', PrintJob::first()->status);
        $this->issueTicket('/ambil-nomor/001', [...$payload, 'service' => 'cs'])->assertConflict();
    }

    public function test_receipt_is_private_and_uses_original_snapshot(): void
    {
        $response = $this->issueTicket('/ambil-nomor/001', ['service' => 'cs'])->assertOk();
        $url = $response->json('print_job.receipt_url');
        $this->branch->update(['address' => 'CHANGED ADDRESS']);
        $this->get($url)->assertOk()->assertSee('Alamat 001')->assertDontSee('CHANGED ADDRESS');
        PrintJob::first()->update(['owner_hash' => hash('sha256', 'another-session')]);
        $this->get($url)->assertForbidden();
        $this->admin();
        $this->get($url)->assertOk();
    }

    public function test_staff_browser_reprint_opens_original_number_and_snapshot_without_dispatch(): void
    {
        $response = $this->issueTicket('/ambil-nomor/001', ['service' => 'cs'])->assertOk();
        $original = PrintJob::findOrFail($response->json('request_id'));
        $this->branch->update(['address' => 'New address']);
        $this->admin();
        $component = Livewire::test(ManageQueues::class)->callTableAction('reprint', $original->ticket)->assertHasNoActionErrors();
        $again = PrintJob::where('original_job_id', $original->id)->firstOrFail();
        $component->assertRedirect(route('print-jobs.receipt', ['printJob' => $again->id, 'autoprint' => 1]));
        $this->assertSame($original->queue_id, $again->queue_id);
        $this->assertSame($original->snapshot, $again->snapshot);
        $this->assertSame('browser_ready', $again->status);
        $this->assertDatabaseCount('queues', 1);
        $this->assertDatabaseCount('jobs', 0);
        $this->get(route('print-jobs.receipt', $again))->assertOk()->assertSee('Alamat 001')->assertSee('Cetak tiket ini');
    }

    public function test_two_pcs_on_same_wifi_use_distinct_agents_of_the_same_branch(): void
    {
        $admin = $this->admin();
        $first = $this->agent('EPSON L3250 Series (Copy 2)');
        $second = $this->agent('EPSON L3250 Series (Copy 2)');
        $a = $this->browser('a', $admin);
        $b = $this->browser('b', $admin);
        $routing = app(KioskPrintRouting::class);
        $this->pairPrintAgent($a, $first);
        $this->pairPrintAgent($b, $second);
        $this->assertSame($first->id, app(TicketPrinting::class)->ready($this->branch, $a)['agent_id']);
        $this->assertSame($second->id, app(TicketPrinting::class)->ready($this->branch, $b)['agent_id']);
        foreach ([[$a, $first], [$b, $second]] as [$request, $agent]) {
            $device = app(KioskDeviceHistory::class)->device($request);
            $response = $this->withCookie(KioskDeviceHistory::COOKIE, $device->id)
                ->issueTicket('/ambil-nomor/001', ['service' => 'cs'])->assertOk();
            $this->assertSame($agent->id, PrintJob::findOrFail($response->json('request_id'))->print_agent_id);
        }
        $this->assertSame('browser', $routing->selection($this->branch, $this->browser('c'))['transport']);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_disabled_deleted_or_revoked_pair_never_chooses_another_agent(): void
    {
        $admin = $this->admin();
        $agent = $this->agent();
        $this->agent('Other ready printer');
        $browser = $this->browser('a', $admin);
        $this->pairPrintAgent($browser, $agent);
        $device = app(KioskDeviceHistory::class)->device($browser);
        $this->withCookie(KioskDeviceHistory::COOKIE, $device->id);
        app(PrintAgentManagement::class)->disable($agent, delete: true);
        $this->issueTicket('/ambil-nomor/001', ['service' => 'cs'])->assertStatus(503);
        $this->assertDatabaseCount('queues', 0);
        $this->post(route('kiosk-printing.browser', $this->branch))->assertRedirect();
        $this->issueTicket('/ambil-nomor/001', ['service' => 'cs'])->assertOk()->assertJsonPath('print_job.transport', 'browser');
    }

    public function test_native_crud_allows_multiple_printers_and_keeps_each_in_its_branch(): void
    {
        $this->admin();
        $old = $this->agent('ITPRINTER (L6290 Series)');
        $this->agent('EPSON L3250 Series');
        $this->assertSame(2, $this->branch->printAgents()->count());
        $other = $this->office('002');
        $misplaced = PrintAgent::create(['id' => (string) Str::uuid(), 'branch_id' => $other->id, 'name' => 'Printer salah kantor', 'printer_name' => 'EPSON L3250 Series']);
        Livewire::test(ManagePrintAgents::class, ['branch' => $other->id])
            ->callTableAction('edit', $misplaced, data: ['branch_id' => $this->branch->id, 'name' => 'PC Pusat Baru', 'printer_name' => 'EPSON L3250 Series'])
            ->assertHasNoActionErrors();
        $this->assertSame($other->id, $misplaced->fresh()->branch_id);
        $this->assertFalse($misplaced->fresh()->revoked);
        Livewire::test(ManagePrintAgents::class, ['branch' => $other->id])->callTableAction('view', $misplaced)->assertHasNoActionErrors();
        Livewire::test(ManagePrintAgents::class, ['branch' => $other->id])->callTableAction('delete', $misplaced)->assertHasNoActionErrors();
        $this->assertSoftDeleted('print_agents', ['id' => $misplaced->id]);
        $this->assertNotNull($old->fresh());
    }

    public function test_admin_instructions_do_not_assign_a_remote_printer_to_the_admin_browser(): void
    {
        $this->admin();
        $agent = $this->agent();
        Livewire::test(ManagePrintAgents::class, ['branch' => $this->branch->id])->assertTableActionExists('useHere');
        $this->assertDatabaseCount('kiosk_print_bindings', 0);
        $this->actingAs(User::factory()->create(['is_active' => true]));
        $this->get('/admin/print-agents')->assertForbidden();
        $this->expectException(HttpException::class);
        app(KioskPrintRouting::class)->bindAgent($this->browser('z'), $agent);
    }

    public function test_printer_edit_cancels_waiting_jobs_and_requires_pc_confirmation(): void
    {
        $this->admin();
        $agent = $this->agent();
        $agent->update(['installed_printers' => ['EPSON L3250 Series', 'EPSON L6290 Series']]);
        $this->usePrintTransport($this->branch, 'agent', $agent);
        $response = $this->issueTicket('/ambil-nomor/001', ['service' => 'cs'])->assertOk();
        app(PrintAgentManagement::class)->selectFromAdmin($agent, ['printer_name' => 'EPSON L6290 Series', 'paper_profile' => 'thermal80']);
        $this->assertSame('expired', PrintJob::find($response->json('request_id'))->status);
        $this->assertFalse($agent->fresh()->isReady());
        $headers = ['Authorization' => 'Bearer test-token', 'X-Print-Agent' => $agent->id, 'X-Print-Device' => $agent->device_id];
        // Use a proper API token; the model fixture stores only its hash.
        $token = bin2hex(random_bytes(32));
        $agent->update(['token_hash' => hash('sha256', $token)]);
        $headers['Authorization'] = 'Bearer '.$token;
        $payload = ['printer_ready' => true, 'printer_name' => 'EPSON L3250 Series', 'machine_name' => 'PC-PUSAT', 'version' => '1.1.0', 'installed_printers' => $agent->installed_printers];
        $this->postJson('/api/print-agent/heartbeat', $payload, $headers)->assertOk()->assertJsonPath('printer_name', 'EPSON L6290 Series');
        $this->assertFalse($agent->fresh()->isReady());
        $this->postJson('/api/print-agent/heartbeat', [...$payload, 'printer_name' => 'EPSON L6290 Series'], $headers)->assertOk();
        $this->assertTrue($agent->fresh()->isReady());
    }

    public function test_delete_preserves_history_and_invalidates_authenticated_inflight_start(): void
    {
        $this->admin();
        $agent = $this->agent();
        $this->usePrintTransport($this->branch, 'agent', $agent);
        $response = $this->issueTicket('/ambil-nomor/001', ['service' => 'cs'])->assertOk();
        $job = PrintJob::findOrFail($response->json('request_id'));
        $claim = app(BranchAgentPrinting::class)->claim($agent);
        app(BranchAgentPrinting::class)->pdf($job->fresh());
        $snapshot = $job->snapshot;
        app(PrintAgentManagement::class)->disable($agent, delete: true);
        $this->assertSame('unknown', $job->fresh()->status);
        $this->assertSame($snapshot, $job->fresh()->snapshot);
        $this->expectException(HttpException::class);
        app(BranchAgentPrinting::class)->start($agent, $job->fresh(), $job->fresh()->pdf_sha256);
    }

    public function test_printer_change_is_rejected_while_processing(): void
    {
        $this->admin();
        $agent = $this->agent();
        $this->usePrintTransport($this->branch, 'agent', $agent);
        $this->issueTicket('/ambil-nomor/001', ['service' => 'cs'])->assertOk();
        app(BranchAgentPrinting::class)->claim($agent);
        $this->expectException(ValidationException::class);
        app(PrintAgentManagement::class)->selectFromAdmin($agent, ['printer_name' => $agent->printer_name, 'paper_profile' => 'thermal58']);
    }

    public function test_prepared_install_keeps_old_access_until_idempotent_activation(): void
    {
        $agent = $this->agent();
        $key = bin2hex(random_bytes(32));
        $oldHash = $agent->token_hash;
        $agent->update(['enrollment_hash' => hash('sha256', $key), 'enrollment_expires_at' => now()->addMinutes(15)]);
        $payload = ['agent_id' => $agent->id, 'enrollment_key' => $key, 'device_id' => (string) Str::uuid(), 'printer_name' => 'New printer'];
        $this->postJson('/api/print-agent/preflight', $payload)->assertOk()->assertJsonPath('application', 'bank-dp-taspen-print-agent');
        $token = $this->postJson('/api/print-agent/prepare', $payload)->assertOk()->json('token');
        $this->assertSame($oldHash, $agent->fresh()->token_hash);
        $activation = ['agent_id' => $agent->id, 'device_id' => $payload['device_id'], 'token' => $token];
        $this->postJson('/api/print-agent/activate-enrollment', $activation)->assertOk();
        $this->postJson('/api/print-agent/activate-enrollment', $activation)->assertOk();
        $this->assertSame(hash('sha256', $token), $agent->fresh()->token_hash);
        $this->assertSame('New printer', $agent->fresh()->printer_name);
        $this->assertDatabaseCount('print_agent_enrollments', 0);
        $this->assertNull($agent->fresh()->enrollment_hash);
    }

    public function test_expired_or_revoked_pending_install_cannot_replace_old_access(): void
    {
        $this->admin();
        $agent = $this->agent();
        $key = bin2hex(random_bytes(32));
        $agent->update(['enrollment_hash' => hash('sha256', $key), 'enrollment_expires_at' => now()->addMinutes(15)]);
        $payload = ['agent_id' => $agent->id, 'enrollment_key' => $key, 'device_id' => (string) Str::uuid(), 'printer_name' => 'New printer'];
        $token = $this->postJson('/api/print-agent/prepare', $payload)->assertOk()->json('token');
        app(PrintAgentManagement::class)->disable($agent, revoke: true);
        $this->postJson('/api/print-agent/activate-enrollment', ['agent_id' => $agent->id, 'device_id' => $payload['device_id'], 'token' => $token])->assertUnauthorized();
        $this->assertTrue($agent->fresh()->revoked);
    }
}
