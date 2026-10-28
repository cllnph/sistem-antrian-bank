<?php

namespace Tests\Feature;

use App\Filament\Resources\KioskDeviceEventResource\Pages\ManageKioskDeviceEvents;
use App\Filament\Resources\KioskDeviceResource\Pages\ManageKioskDevices;
use App\Models\Branch;
use App\Models\KioskDevice;
use App\Models\KioskDeviceEvent;
use App\Models\PrintJob;
use App\Models\Queue;
use App\Models\QueueLog;
use App\Models\ServiceSetting;
use App\Models\User;
use App\Services\KioskDeviceHistory;
use App\Services\Printing\PrinterGateway;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class KioskDeviceHistoryTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private const DESKTOP = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/130.0 Safari/537.36 Edg/130.0';

    private const ANDROID = 'Mozilla/5.0 (Linux; Android 13) Chrome/120.0 Mobile Safari/537.36';

    protected function setUp(): void
    {
        parent::setUp();
        config(['kiosk.test_mode_enabled' => true]);
        $this->branch = $this->office('101');
        $this->withSession(['_token' => 'device-test-token']);
        $this->withCookie(config('session.cookie'), session()->getId())->withCredentials();
    }

    private function office(string $code): Branch
    {
        $branch = Branch::create(['code' => $code, 'slug' => 'devices-'.$code, 'name' => 'Kantor '.$code, 'type' => 'Kantor Cabang', 'is_active' => true]);
        foreach (['cs', 'teller'] as $service) {
            ServiceSetting::create(['branch_id' => $branch->id, 'service' => $service, 'label' => $service, 'counter_name' => $service, 'is_active' => true]);
        }

        return $branch;
    }

    private function deviceRequest(string $session, string $ua = self::DESKTOP, ?string $cookie = null, string $ip = '192.168.11.10'): Request
    {
        $store = new Store('device-test', new ArraySessionHandler(120));
        $store->setId(str_repeat($session, 40));
        $store->start();
        $request = Request::create('/ambil-nomor/101', 'GET', [], $cookie ? [KioskDeviceHistory::COOKIE => $cookie] : [], [], ['REMOTE_ADDR' => $ip, 'HTTP_USER_AGENT' => $ua]);
        $request->setLaravelSession($store);

        return $request;
    }

    public function test_opening_kiosk_automatically_records_a_browser_without_enrolling_a_print_agent(): void
    {
        $response = $this->withHeader('User-Agent', self::ANDROID)->get('/ambil-nomor/101')->assertOk();
        $response->assertCookie(KioskDeviceHistory::COOKIE);
        $this->assertDatabaseCount('kiosk_devices', 1);
        $this->assertDatabaseCount('kiosk_device_events', 1);
        $this->assertDatabaseCount('print_agents', 0);
        $device = KioskDevice::firstOrFail();
        $this->assertSame('HP', $device->device_type);
        $this->assertSame('Android', $device->platform);
        $this->assertSame('Chrome', $device->browser);
        $this->assertSame($this->branch->id, $device->last_branch_id);
    }

    public function test_same_ip_and_user_agent_are_not_used_as_a_device_identifier(): void
    {
        $history = app(KioskDeviceHistory::class);
        $history->opened($this->deviceRequest('a'), $this->branch);
        $history->opened($this->deviceRequest('b'), $this->branch);
        $this->assertDatabaseCount('kiosk_devices', 2);
        $this->assertDatabaseCount('kiosk_device_events', 2);
        $this->assertSame(1, KioskDevice::distinct()->count('last_ip'));
    }

    public function test_browser_cookie_survives_ip_and_session_changes_and_records_multiple_branches(): void
    {
        $history = app(KioskDeviceHistory::class);
        $history->opened($this->deviceRequest('a'), $this->branch);
        $device = KioskDevice::firstOrFail();
        $other = $this->office('102');
        $this->travel(2)->seconds();
        $history->opened($this->deviceRequest('b', self::DESKTOP, $device->id, '192.168.11.99'), $other);
        $this->assertDatabaseCount('kiosk_devices', 1);
        $this->assertSame('192.168.11.99', $device->fresh()->last_ip);
        $this->assertSame($other->id, $device->fresh()->last_branch_id);
        $this->assertSame(2, $device->events()->distinct()->count('branch_id'));
        $this->assertSame('Edge', $device->fresh()->browser);
    }

    public function test_uuid_replay_and_status_polling_do_not_duplicate_history_or_printing(): void
    {
        $uuid = (string) Str::uuid();
        $this->get('/ambil-nomor/101')->assertOk();
        $response = $this->issueTicket('/ambil-nomor/101', ['service' => 'cs', 'request_id' => $uuid])->assertOk();
        $this->issueTicket('/ambil-nomor/101', ['service' => 'cs', 'request_id' => $uuid])->assertOk();
        $this->getJson($response->json('print_job.status_url'))->assertOk();
        $this->assertDatabaseCount('kiosk_devices', 1);
        $this->assertSame(1, KioskDeviceEvent::where('event', 'ticket_issued')->count());
        $this->assertSame(1, KioskDeviceEvent::where('event', 'opened')->count());
        $this->assertDatabaseCount('queues', 1);
        $this->assertDatabaseCount('print_jobs', 1);
    }

    public function test_rejected_issuance_does_not_record_a_ticket_event(): void
    {
        $this->get('/ambil-nomor/101')->assertOk();
        $this->usePrintTransport($this->branch, 'server');
        app(PrinterGateway::class)->availabilityFailure = new \RuntimeException('Printer offline');
        $this->issueTicket('/ambil-nomor/101', ['service' => 'teller'])->assertStatus(503);
        $this->assertDatabaseCount('kiosk_device_events', 1);
        $this->assertDatabaseCount('queues', 0);
        $this->get('/ambil-nomor/missing')->assertNotFound();
        $this->assertDatabaseCount('kiosk_device_events', 1);
    }

    private function oldJob(string $owner = 'old-session'): PrintJob
    {
        $ticket = Queue::create(['branch_id' => $this->branch->id, 'number' => sprintf('CS-%03d', Queue::withoutGlobalScopes()->count() + 1), 'service_setting_id' => $this->branch->serviceSettings()->where('service', 'cs')->first()->id, 'status' => 'waiting']);
        QueueLog::create(['queue_id' => $ticket->id, 'branch_id' => $this->branch->id, 'action' => 'created',
            'payload' => ['ip' => '192.168.11.10', 'user_agent' => self::ANDROID], 'created_at' => now()->subDay()]);

        return PrintJob::create(['id' => (string) Str::uuid(), 'queue_id' => $ticket->id, 'owner_hash' => hash('sha256', $owner),
            'snapshot' => ['ticket' => $ticket->number, 'branch_id' => $this->branch->id], 'printer_name' => 'Office printer', 'status' => 'submitted']);
    }

    public function test_legacy_import_is_idempotent_uses_session_identity_and_keeps_original_event_time(): void
    {
        $job = $this->oldJob();
        $log = QueueLog::where('queue_id', $job->queue_id)->firstOrFail();
        $this->artisan('kiosk:import-device-history')->assertSuccessful();
        $this->artisan('kiosk:import-device-history')->assertSuccessful();
        $this->assertDatabaseCount('kiosk_devices', 1);
        $this->assertDatabaseCount('kiosk_device_events', 1);
        $this->assertSame('legacy_session', KioskDevice::first()->identity_source);
        $this->assertTrue($log->created_at->equalTo(KioskDeviceEvent::first()->observed_at));
        $this->assertTrue($log->created_at->equalTo(KioskDevice::first()->last_seen_at));
        $this->oldJob('another-session');
        $this->artisan('kiosk:import-device-history')->assertSuccessful();
        $this->assertDatabaseCount('kiosk_devices', 2);
    }

    public function test_missing_legacy_metadata_is_not_guessed_and_reprints_are_not_imported_as_new_tickets(): void
    {
        $job = $this->oldJob();
        QueueLog::where('queue_id', $job->queue_id)->update(['payload' => '{}']);
        $this->assertFalse(app(KioskDeviceHistory::class)->recover($job));
        $this->assertDatabaseCount('kiosk_devices', 0);
        $again = $this->oldJob();
        $again->update(['original_job_id' => $job->id]);
        $this->assertFalse(app(KioskDeviceHistory::class)->recover($again));
        $this->assertDatabaseCount('kiosk_device_events', 0);
    }

    public function test_native_history_pages_allow_only_active_central_admin_and_filter_per_device(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $history = app(KioskDeviceHistory::class);
        $history->opened($this->deviceRequest('a'), $this->branch);
        $first = KioskDevice::firstOrFail();
        $history->opened($this->deviceRequest('b'), $this->branch);
        $other = KioskDevice::whereKeyNot($first->id)->firstOrFail();
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');
        $this->actingAs($admin);
        Livewire::test(ManageKioskDevices::class)->assertSuccessful()->assertCanSeeTableRecords([$first, $other])
            ->callTableAction('edit', $first, data: ['label' => 'Laptop Rina'])->assertHasNoActionErrors();
        $this->assertSame('Laptop Rina', $first->fresh()->label);
        Livewire::test(ManageKioskDeviceEvents::class)->filterTable('kiosk_device_id', $first->id)
            ->assertCanSeeTableRecords($first->events)->assertCanNotSeeTableRecords($other->events);
        Livewire::withQueryParams(['filters' => ['kiosk_device_id' => ['value' => $first->id]]])
            ->test(ManageKioskDeviceEvents::class)
            ->assertCanSeeTableRecords($first->events)->assertCanNotSeeTableRecords($other->events);
        $officer = User::factory()->create(['is_active' => true]);
        $officer->assignRole('cs');
        $this->actingAs($officer);
        $this->get('/admin/kiosk-devices')->assertForbidden();
        $this->get('/admin/kiosk-device-events')->assertForbidden();
    }
}
