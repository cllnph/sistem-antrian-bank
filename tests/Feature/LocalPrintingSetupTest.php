<?php

namespace Tests\Feature;

use App\Filament\Resources\PrintAgentResource\Pages\ManagePrintAgents;
use App\Models\Branch;
use App\Models\PrintAgent;
use App\Models\PrintJob;
use App\Models\ServiceSetting;
use App\Models\User;
use App\Services\KioskDeviceHistory;
use App\Services\Printing\PrintAgentManagement;
use App\Services\Printing\PrinterGateway;
use App\Services\Printing\ReceiptPaper;
use App\Services\Printing\ReceiptRenderer;
use App\Services\Printing\TicketPrinting;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LocalPrintingSetupTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private PrintAgent $agent;

    private string $token;

    private string $browserId;

    protected function setUp(): void
    {
        parent::setUp();
        config(['kiosk.test_mode_enabled' => true]);
        $this->branch = Branch::create(['code' => '101', 'name' => 'Kantor Uji', 'slug' => 'local-setup', 'type' => 'Kantor Cabang', 'address' => str_repeat('Alamat kantor panjang ', 9), 'is_active' => true]);
        foreach (['cs', 'teller'] as $service) {
            ServiceSetting::create(['branch_id' => $this->branch->id, 'service' => $service, 'label' => $service, 'counter_name' => $service, 'is_active' => true]);
        }
        $this->token = str_repeat('a', 64);
        $this->agent = PrintAgent::create(['id' => (string) Str::uuid(), 'branch_id' => $this->branch->id, 'name' => 'PC cabang',
            'printer_name' => 'EPSON L3250 Series (Copy 2)', 'paper_profile' => 'a4', 'enabled' => true, 'revoked' => false,
            'device_id' => (string) Str::uuid(), 'token_hash' => hash('sha256', $this->token), 'printer_ready' => true, 'heartbeat_at' => now()]);
        $this->withSession(['_token' => 'local-setup']);
        $this->browserId = app(KioskDeviceHistory::class)->device($this->printingRequest())->id;
        $this->withCookie(KioskDeviceHistory::COOKIE, $this->browserId)->withCookie(config('session.cookie'), session()->getId())->withCredentials();
    }

    private function headers(): array
    {
        return ['Authorization' => 'Bearer '.$this->token, 'X-Print-Agent' => $this->agent->id, 'X-Print-Device' => $this->agent->device_id];
    }

    private function issue(): TestResponse
    {
        return $this->issueTicket('/ambil-nomor/101', ['service' => 'cs', 'request_id' => (string) Str::uuid()])->assertOk();
    }

    private function heartbeat(array $changes = []): TestResponse
    {
        return $this->postJson('/api/print-agent/heartbeat', [...[
            'printer_ready' => true, 'printer_name' => $this->agent->printer_name, 'paper_profile' => $this->agent->paper_profile,
            'machine_name' => 'PC-CABANG', 'version' => '1.3.0', 'inventory_status' => 'ok', 'installed_printers' => [$this->agent->printer_name],
        ], ...$changes], $this->headers());
    }

    private function pairingToken(): string
    {
        $reply = $this->postJson('/api/print-agent/browser-pairings', [], $this->headers())->assertOk();
        $reply->assertHeader('Cache-Control', 'no-store, private');

        return parse_url($reply->json('connect_url'), PHP_URL_FRAGMENT);
    }

    private function admin(): User
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');
        $this->actingAs($admin);

        return $admin;
    }

    public function test_external_pc_issues_locally_without_server_files_printer_or_admin(): void
    {
        config(['printing.config_path' => base_path('does-not-exist.json'), 'printing.require_worker' => true, 'printing.transport' => 'server']);
        app(PrinterGateway::class)->availabilityFailure = new \RuntimeException('Laptop printer must never be inspected');
        $response = $this->issue()->assertJsonPath('print_job.transport', 'browser');
        $job = PrintJob::findOrFail($response->json('request_id'));
        $this->assertSame(ReceiptPaper::dimensions('a4'), $job->snapshot['paper']);
        $this->get($response->json('print_job.receipt_url'))->assertOk()->assertSee('CS-001');
        $this->assertDatabaseCount('jobs', 0);
        $this->assertCount(0, app(PrinterGateway::class)->submissions);
        $this->assertStringStartsWith('/print-jobs/', $response->json('print_job.receipt_url'));
    }

    public function test_unverified_old_server_and_agent_bindings_use_local_printing(): void
    {
        foreach (['server', 'agent'] as $transport) {
            DB::table('kiosk_print_bindings')->updateOrInsert(['kiosk_device_id' => $this->browserId, 'branch_id' => $this->branch->id], [
                'transport' => $transport, 'print_agent_id' => $transport === 'agent' ? $this->agent->id : null, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->get('/ambil-nomor/101')->assertOk()->assertViewHas('printSelection', fn ($selection) => $selection['transport'] === 'browser' && filled($selection['notice']));
            $this->issue()->assertJsonPath('print_job.transport', 'browser');
        }
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_same_browser_can_replay_and_read_receipt_after_session_regeneration(): void
    {
        $response = $this->issue();
        $id = $response->json('request_id');
        session()->invalidate();
        $this->withSession(['_token' => 'new-session'])->withCookie(config('session.cookie'), session()->getId());
        $this->getJson($response->json('print_job.status_url'))->assertOk();
        $this->get($response->json('print_job.receipt_url'))->assertOk();
        $this->issueTicket('/ambil-nomor/101', ['service' => 'cs', 'request_id' => $id])->assertOk()->assertJsonPath('ticket', 'CS-001');
        $this->assertDatabaseCount('queues', 1);
        $this->assertDatabaseCount('print_jobs', 1);
    }

    public function test_another_browser_cannot_access_device_owned_ticket(): void
    {
        $response = $this->issue();
        $session = new Store('second-browser', new ArraySessionHandler(120));
        $session->setId(Str::random(40));
        $session->start();
        $request = Request::create('/ambil-nomor/101');
        $request->setLaravelSession($session);
        $other = app(KioskDeviceHistory::class)->device($request);
        $this->withCookie(KioskDeviceHistory::COOKIE, $other->id)->withCookie(config('session.cookie'), $session->getId());
        $this->getJson($response->json('print_job.status_url'))->assertForbidden()->assertJsonPath('code', 'access_denied');
        $this->get($response->json('print_job.receipt_url'))->assertForbidden();
        $this->issueTicket('/ambil-nomor/101', ['service' => 'cs', 'request_id' => $response->json('request_id')])->assertForbidden();
        $this->assertDatabaseCount('queues', 1);
    }

    public function test_legacy_session_owned_ticket_keeps_its_original_authorization(): void
    {
        $response = $this->issue();
        PrintJob::findOrFail($response->json('request_id'))->update(['owner_type' => 'session', 'owner_hash' => hash_hmac('sha256', session()->getId(), config('app.key'))]);
        $this->get($response->json('print_job.receipt_url'))->assertOk();
        session()->invalidate();
        $this->withSession(['_token' => 'new'])->withCookie(config('session.cookie'), session()->getId());
        $this->get($response->json('print_job.receipt_url'))->assertForbidden();
    }

    public function test_local_agent_proof_pairs_guest_browser_once_and_cannot_be_replayed(): void
    {
        $token = $this->pairingToken();
        $this->assertDatabaseHas('print_agent_browser_pairings', ['token_hash' => hash('sha256', $token)]);
        $this->postJson('/kiosk-connect', ['token' => $token])->assertOk()->assertJsonPath('redirect', '/ambil-nomor/101');
        $this->postJson('/kiosk-connect', ['token' => $token])->assertConflict();
        $this->issue()->assertJsonPath('print_job.transport', 'agent');
        $this->assertSame($this->agent->id, PrintJob::firstOrFail()->print_agent_id);
        $this->assertDatabaseHas('kiosk_print_bindings', ['proof_device_id' => $this->agent->device_id, 'binding_source' => 'local_launcher']);
        $this->get('/kiosk-connect')->assertOk()->assertDontSee($this->token)->assertHeader('Referrer-Policy', 'no-referrer');
    }

    public function test_pairing_requires_agent_auth_and_rejects_expired_or_rotated_proofs(): void
    {
        $this->postJson('/api/print-agent/browser-pairings')->assertUnauthorized();
        $expired = $this->pairingToken();
        $this->travel(121)->seconds();
        $this->postJson('/kiosk-connect', ['token' => $expired])->assertConflict();
        $token = $this->pairingToken();
        $this->agent->update(['token_hash' => hash('sha256', str_repeat('b', 64))]);
        $this->postJson('/kiosk-connect', ['token' => $token])->assertConflict();
        $this->assertDatabaseCount('kiosk_print_bindings', 0);
    }

    public function test_reinstallation_invalidates_old_pc_proof_without_selecting_another_printer(): void
    {
        $this->postJson('/kiosk-connect', ['token' => $this->pairingToken()])->assertOk();
        $this->agent->update(['device_id' => (string) Str::uuid()]);
        $this->issueTicket('/ambil-nomor/101', ['service' => 'cs'])->assertStatus(503)->assertJsonPath('success', false);
        $this->assertDatabaseCount('queues', 0);
        $this->post('/kiosk-printing/'.$this->branch->id.'/browser')->assertRedirect();
        $this->issue()->assertJsonPath('print_job.transport', 'browser');
    }

    public function test_paper_profiles_render_one_page_and_changes_do_not_modify_old_snapshot(): void
    {
        $first = null;
        foreach (['a4', 'thermal58', 'thermal80'] as $profile) {
            $this->post('/kiosk-printing/'.$this->branch->id.'/paper', ['paper_profile' => $profile])->assertRedirect();
            $response = $this->issue();
            $job = PrintJob::findOrFail($response->json('request_id'));
            $first ??= $job;
            $this->assertSame(ReceiptPaper::dimensions($profile), $job->snapshot['paper']);
            $pdf = app(ReceiptRenderer::class)->render($job->snapshot);
            $this->assertStringStartsWith('%PDF-', $pdf);
            $this->assertSame(1, preg_match_all('~/Type /Page\b~', $pdf));
            $paper = ReceiptPaper::dimensions($profile);
            $this->assertStringContainsString(sprintf('/MediaBox [0.000 0.000 %.3f %.3f]', $paper['width_mm'] * 72 / 25.4, $paper['height_mm'] * 72 / 25.4), $pdf);
        }
        $this->assertSame(ReceiptPaper::dimensions('a4'), $first->fresh()->snapshot['paper']);
    }

    public function test_printer_change_waits_for_processing_and_requires_a_new_heartbeat(): void
    {
        $this->postJson('/kiosk-connect', ['token' => $this->pairingToken()])->assertOk();
        $response = $this->issue();
        $job = PrintJob::findOrFail($response->json('request_id'));
        $choice = ['printer_name' => 'Panda USB', 'paper_profile' => 'thermal58', 'installed_printers' => [$this->agent->printer_name, 'Panda USB']];
        $job->update(['status' => 'processing', 'lease_expires_at' => now()->addSeconds(90)]);
        $this->postJson('/api/print-agent/printer-selection', $choice, $this->headers())->assertConflict();
        $job->update(['status' => 'queued']);
        $this->postJson('/api/print-agent/printer-selection', $choice, $this->headers())->assertOk();
        $this->assertSame('expired', $job->fresh()->status);
        $this->assertFalse($this->agent->fresh()->printer_ready);
        $this->heartbeat()->assertOk();
        $this->assertFalse($this->agent->fresh()->printer_ready);
        $this->heartbeat(['printer_name' => 'Panda USB', 'paper_profile' => 'thermal58', 'installed_printers' => ['Panda USB']])->assertOk();
        $this->assertTrue($this->agent->fresh()->printer_ready);
        $next = PrintJob::findOrFail($this->issue()->json('request_id'));
        $this->assertSame('Panda USB', $next->printer_name);
        $this->assertSame($this->agent->id, $next->print_agent_id);
        $this->assertSame(ReceiptPaper::dimensions('thermal58'), $next->snapshot['paper']);
    }

    public function test_failed_inventory_does_not_delete_printers_or_hide_heartbeat(): void
    {
        $this->agent->update(['installed_printers' => [$this->agent->printer_name]]);
        $this->heartbeat(['inventory_status' => 'unavailable', 'installed_printers' => []])->assertOk();
        $this->assertSame([$this->agent->printer_name], $this->agent->fresh()->installed_printers);
        $this->assertTrue($this->agent->fresh()->isReady());
        $this->assertSame('unavailable', $this->agent->fresh()->inventory_status);
    }

    public function test_delayed_request_cannot_enqueue_after_paper_or_installation_changes(): void
    {
        $this->usePrintTransport($this->branch, 'agent', $this->agent);
        $printing = app(TicketPrinting::class);
        $configuration = $printing->ready($this->branch, $this->printingRequest());
        $response = $this->issue();
        $ticket = PrintJob::findOrFail($response->json('request_id'))->ticket;
        $originalDevice = $this->agent->device_id;
        foreach ([['paper_profile' => 'thermal58'], ['device_id' => (string) Str::uuid()]] as $change) {
            $this->agent->update(['paper_profile' => 'a4', 'device_id' => $originalDevice, 'printer_ready' => true, 'heartbeat_at' => now(), ...$change]);
            try {
                $printing->enqueue($ticket, (string) Str::uuid(), $printing->owner($this->printingRequest()), $configuration);
                $this->fail('A delayed request used an outdated printer configuration.');
            } catch (HttpException $error) {
                $this->assertSame(409, $error->getStatusCode());
            }
            $this->assertDatabaseCount('print_jobs', 1);
        }
    }

    public function test_admin_package_activates_only_after_ready_and_explicit_disable_wins(): void
    {
        $key = str_repeat('c', 64);
        $this->agent->update(['enabled' => false, 'auto_name' => true, 'enrollment_auto_enable' => true, 'enrollment_hash' => hash('sha256', $key), 'enrollment_expires_at' => now()->addMinutes(15)]);
        $device = (string) Str::uuid();
        $token = $this->postJson('/api/print-agent/prepare', ['agent_id' => $this->agent->id, 'enrollment_key' => $key, 'device_id' => $device,
            'printer_name' => $this->agent->printer_name, 'machine_name' => 'PC-CABANG', 'paper_profile' => 'a4'])->assertOk()->json('token');
        $this->postJson('/api/print-agent/activate-enrollment', ['agent_id' => $this->agent->id, 'device_id' => $device, 'token' => $token])->assertOk();
        $this->token = $token;
        $this->agent->refresh();
        $this->assertFalse($this->agent->enabled);
        $this->heartbeat(['printer_ready' => false])->assertOk();
        $this->assertFalse($this->agent->fresh()->enabled);
        $this->heartbeat()->assertOk();
        $this->assertTrue($this->agent->fresh()->enabled);
        $this->assertSame('PC-CABANG', $this->agent->fresh()->name);
        $this->admin();
        app(PrintAgentManagement::class)->disable($this->agent);
        $this->heartbeat()->assertOk();
        $this->assertFalse($this->agent->fresh()->enabled);
    }

    public function test_install_new_pc_needs_only_branch_and_generates_versioned_package(): void
    {
        $this->admin();
        Livewire::test(ManagePrintAgents::class, ['branch' => $this->branch->id])->callAction('installNewPc')
            ->assertHasNoActionErrors()->assertFileDownloaded('Penghubung-PC-Baru-v1.3.0.zip');
        $new = PrintAgent::whereKeyNot($this->agent->id)->firstOrFail();
        $this->assertTrue($new->auto_name);
        $this->assertNull($new->printer_name);
        $this->assertTrue($new->enrollment_auto_enable);
        $this->assertFalse($new->enabled);
        $this->assertSame($this->branch->id, $new->branch_id);
    }
}
