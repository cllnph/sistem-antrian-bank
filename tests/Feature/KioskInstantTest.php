<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Queue;
use App\Models\ServiceSetting;
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
            ->assertSee('#Mudah dan Nyaman')
            ->assertDontSee('<select onchange="window.location.href=this.value"', false);
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

        $response = $this->postJson('/ambil-nomor', [
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
