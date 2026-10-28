<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ServiceSetting;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KioskInstantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-13 09:00:00', 'Asia/Jakarta'));
        $this->seed(RoleAndPermissionSeeder::class);
    }

    #[Test]
    public function test_kiosk_view_loads_clean_universal_design(): void
    {
        $branch = Branch::create([
            'code' => '001',
            'slug' => 'pusat',
            'name' => 'Kantor Pusat Operasional',
            'type' => 'Kantor Pusat',
            'is_active' => true,
        ]);

        ServiceSetting::create([
            'branch_id' => $branch->id,
            'label' => 'Teller',
            'service' => 'teller',
            'counter_name' => 'Loket Teller',
            'is_active' => true,
        ]);

        ServiceSetting::create([
            'branch_id' => $branch->id,
            'label' => 'Customer Service',
            'service' => 'cs',
            'counter_name' => 'Loket CS',
            'is_active' => true,
        ]);

        $response = $this->get('/ambil-nomor');
        $response->assertOk()
            ->assertSee('PENGAMBILAN TIKET ANTREAN')
            ->assertDontSee('#Mudah dan Nyaman')
            ->assertDontSee('<select onchange="window.location.href=this.value"', false);
    }

    #[Test]
    public function kiosk_hours_apply_to_ticket_requests_and_numbers_restart_each_day(): void
    {
        $branch = Branch::create([
            'code' => '002', 'slug' => 'bogor', 'name' => 'Bogor',
            'type' => 'Kantor Cabang', 'is_active' => true,
        ]);
        ServiceSetting::create([
            'branch_id' => $branch->id, 'label' => 'Teller', 'service' => 'teller',
            'counter_name' => 'Loket Teller', 'is_active' => true,
        ]);
        $data = ['service' => 'teller', 'branch_id' => $branch->id];

        $this->travelTo(Carbon::parse('2026-10-13 03:59:59', 'Asia/Jakarta'));
        $this->issueTicket('/ambil-nomor?open=1', $data)->assertStatus(422);
        $this->assertDatabaseCount('queues', 0);

        $this->travelTo(Carbon::parse('2026-10-13 04:00:00', 'Asia/Jakarta'));
        $this->get('/ambil-nomor/002')->assertOk()
            ->assertViewHas('operations', fn ($operations) => $operations['is_open'] && str_contains($operations['label'], '04.00'))
            ->assertDontSee('Pengambilan tiket dibuka Senin - Jumat');
        $this->issueTicket('/ambil-nomor', $data)->assertOk()->assertJsonPath('ticket', 'T-001');

        $this->travelTo(Carbon::parse('2026-10-13 17:59:59', 'Asia/Jakarta'));
        $this->issueTicket('/ambil-nomor', $data)->assertOk()->assertJsonPath('ticket', 'T-002');

        $this->travelTo(Carbon::parse('2026-10-13 18:00:00', 'Asia/Jakarta'));
        $this->issueTicket('/ambil-nomor', $data)->assertStatus(422);

        $this->travelTo(Carbon::parse('2026-10-14 00:00:00', 'Asia/Jakarta'));
        $this->get('/ambil-nomor/002')->assertOk()->assertViewHas('nextTellerNumber', 'T-001');
        $this->issueTicket('/ambil-nomor', $data)->assertStatus(422);

        $this->travelTo(Carbon::parse('2026-10-14 04:00:00', 'Asia/Jakarta'));
        $this->issueTicket('/ambil-nomor', $data)->assertOk()->assertJsonPath('ticket', 'T-001');
        $this->assertDatabaseCount('queues', 1);

        $this->travelTo(Carbon::parse('2026-10-17 09:00:00', 'Asia/Jakarta'));
        $this->issueTicket('/ambil-nomor', $data)->assertStatus(422);
    }

    #[Test]
    public function test_display_view_loads_clean_universal_design(): void
    {
        $branch = Branch::create([
            'code' => '001',
            'slug' => 'pusat',
            'name' => 'Kantor Pusat Operasional',
            'type' => 'Kantor Pusat',
            'is_active' => true,
        ]);

        ServiceSetting::create([
            'branch_id' => $branch->id,
            'label' => 'Teller',
            'service' => 'teller',
            'counter_name' => 'Loket Teller',
            'is_active' => true,
        ]);

        $response = $this->get('/display');
        $response->assertOk()
            ->assertSee('INFORMASI PEMANGGILAN ANTREAN')
            ->assertSee('PT BPR DP TASPEN')
            ->assertDontSee('<select onchange="window.location.href=this.value"', false);
    }

    #[Test]
    public function test_kiosk_instant_1_tap_ticket_issuance(): void
    {
        $branch = Branch::create([
            'code' => '001',
            'slug' => 'pusat',
            'name' => 'Kantor Pusat Operasional',
            'type' => 'Kantor Pusat',
            'is_active' => true,
        ]);

        $setting = ServiceSetting::create([
            'branch_id' => $branch->id,
            'label' => 'Teller',
            'service' => 'teller',
            'counter_name' => 'Loket Teller',
            'is_active' => true,
        ]);

        $response = $this->issueTicket('/ambil-nomor', [
            'service' => 'teller',
            'branch_id' => $branch->id,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'ticket' => 'T-001',
                'service' => 'teller',
            ]);

        $this->assertDatabaseHas('queues', [
            'number' => 'T-001',
            'service_setting_id' => $setting->id,
            'branch_id' => $branch->id,
            'status' => 'waiting',
        ]);
    }
}
