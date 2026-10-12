<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    #[Test]
    public function test_teller_sees_queue_crud_inside_admin_panel(): void
    {
        $teller = User::factory()->create();
        $teller->assignRole('teller');

        $this->actingAs($teller)->get('/admin/queues')
            ->assertOk()
            ->assertSee('Antrean');
    }

    #[Test]
    public function test_only_admin_can_open_user_management(): void
    {
        $teller = User::factory()->create();
        $teller->assignRole('teller');

        $this->actingAs($teller)->get('/admin/users')->assertForbidden();
    }

    #[Test]
    public function test_admin_can_access_dashboard(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->get('/admin')
            ->assertOk();
    }

    #[Test]
    public function test_non_admin_redirects_to_queues_when_accessing_admin_root(): void
    {
        $teller = User::factory()->create();
        $teller->assignRole('teller');

        $this->actingAs($teller)->get('/admin')
            ->assertRedirect('/admin/queues');
    }

    #[Test]
    public function test_staff_sees_only_single_next_waiting_queue_ticket(): void
    {
        $tellerService = \App\Models\ServiceSetting::create(['label' => 'Teller', 'service' => 'teller', 'counter_name' => 'Loket Teller', 'is_active' => true]);
        $teller = User::factory()->create(['service_setting_id' => $tellerService->id]);
        $teller->assignRole('teller');

        \App\Models\Queue::create(['number' => 'T-001', 'service_setting_id' => $tellerService->id, 'status' => 'waiting']);
        \App\Models\Queue::create(['number' => 'T-002', 'service_setting_id' => $tellerService->id, 'status' => 'waiting']);

        $response = $this->actingAs($teller)->get('/admin/queues');
        $response->assertOk()
            ->assertSee('T-001')
            ->assertDontSee('T-002');
    }

    #[Test]
    public function test_admin_has_impersonate_capabilities(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $teller = User::factory()->create();
        $teller->assignRole('teller');

        $this->assertTrue($admin->canImpersonate());
        $this->assertFalse($teller->canImpersonate());
        $this->assertTrue($teller->canBeImpersonated());
    }

    #[Test]
    public function test_admin_can_access_user_resource_table(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $teller = User::factory()->create(['name' => 'Teller Cabang Bogor']);
        $teller->assignRole('teller');

        $this->actingAs($admin)->get('/admin/users')
            ->assertOk()
            ->assertSee('Teller Cabang Bogor');
    }

    #[Test]
    public function test_admin_can_access_control_center_page(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->get('/admin/admin-control-center')
            ->assertOk();
    }

    #[Test]
    public function test_admin_can_access_roles_page(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->get('/admin/roles')
            ->assertOk();
    }
}
