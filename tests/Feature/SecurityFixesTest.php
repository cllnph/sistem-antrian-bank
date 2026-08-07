<?php

namespace Tests\Feature;

use App\Models\Loket;
use App\Models\Queue;
use App\Models\ServiceSetting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecurityFixesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    #[Test]
    public function test_route_authorization_middleware_blocks_unauthorized_roles(): void
    {
        $teller = User::factory()->create(['role' => 'teller']);
        $teller->assignRole('teller');

        $this->actingAs($teller)->get('/admin/users')->assertForbidden();
        $this->actingAs($teller)->get('/admin/roles')->assertForbidden();
        $this->actingAs($teller)->get('/admin/lokets')->assertForbidden();
        $this->actingAs($teller)->get('/admin')->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/admin/roles')->assertOk();
        $this->actingAs($admin)->get('/admin/lokets')->assertOk();
        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    #[Test]
    public function test_seeder_and_panel_access_logic(): void
    {
        $tellerRole = Role::findByName('teller');
        $this->assertFalse($tellerRole->hasPermissionTo('access_admin_panel'));
        $this->assertTrue($tellerRole->hasPermissionTo('view_any_queue'));

        $csRole = Role::findByName('cs');
        $this->assertFalse($csRole->hasPermissionTo('access_admin_panel'));
        $this->assertTrue($csRole->hasPermissionTo('view_any_queue'));

        $teller = User::factory()->create(['role' => 'teller']);
        $teller->assignRole('teller');

        $this->assertTrue($teller->canAccessPanel(new \Filament\Panel()));
    }

    #[Test]
    public function test_two_layer_authorization_on_loket_controller(): void
    {
        $loket1 = ServiceSetting::create(['counter_name' => 'Loket 1', 'service' => 'teller']);
        $loket2 = ServiceSetting::create(['counter_name' => 'Loket 2', 'service' => 'teller']);

        $user = User::factory()->create([
            'role' => 'teller',
            'service_setting_id' => $loket1->id,
        ]);
        $user->givePermissionTo('edit_service_setting');

        $this->actingAs($user)
            ->put(route('admin.lokets.update', $loket2->id), [
                'counter_name' => 'Loket 2 Updated',
                'service' => 'teller',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('admin.lokets.update', $loket1->id), [
                'counter_name' => 'Loket 1 Updated',
                'service' => 'teller',
            ])
            ->assertRedirect();
    }

    #[Test]
    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@bank.com',
            'password' => bcrypt('password123'),
            'is_active' => false,
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'inactive@bank.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('login_error');
        $this->assertGuest();
    }

    #[Test]
    public function test_password_reset_flash_message_does_not_leak_plaintext_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $targetUser = User::factory()->create(['role' => 'teller']);

        $response = $this->actingAs($admin)
            ->post(route('admin.users.reset-password', $targetUser->id), [
                'new_password' => 'SecretPass123!',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', "Kata sandi pengguna '{$targetUser->name}' berhasil di-reset.");
        $this->assertStringNotContainsString('SecretPass123!', session('status'));
    }

    #[Test]
    public function test_queue_store_number_prevents_race_conditions(): void
    {
        $response = $this->post(route('queue.store-number'), ['service' => 'teller']);
        $response->assertSessionHas('ticket');
        $ticket1 = session('ticket');

        $response2 = $this->post(route('queue.store-number'), ['service' => 'teller']);
        $response2->assertSessionHas('ticket');
        $ticket2 = session('ticket');

        $this->assertNotEquals($ticket1, $ticket2);
    }

    #[Test]
    public function test_display_tv_multi_counter_returns_all_called_queues(): void
    {
        $teller1 = User::factory()->create(['role' => 'teller']);
        $teller2 = User::factory()->create(['role' => 'teller']);

        Queue::create([
            'number' => 'T-001',
            'service' => 'teller',
            'status' => 'called',
            'served_by' => $teller1->id,
            'called_at' => now()->subMinute(),
        ]);

        Queue::create([
            'number' => 'T-002',
            'service' => 'teller',
            'status' => 'called',
            'served_by' => $teller2->id,
            'called_at' => now(),
        ]);

        $response = $this->get('/api/display/data');

        $response->assertOk()
            ->assertJsonStructure([
                'teller' => ['number', 'all_called'],
            ]);

        $this->assertCount(2, $response->json('teller.all_called'));
    }

    #[Test]
    public function test_service_type_umum_integration(): void
    {
        $this->post(route('queue.store-number'), ['service' => 'umum'])
            ->assertSessionHas('ticket');

        $this->assertDatabaseHas('queues', [
            'service' => 'umum',
        ]);
    }

    #[Test]
    public function test_user_role_validation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Invalid Role User',
                'email' => 'invalidrole@bank.com',
                'role' => 'super_hacker',
                'password' => 'password123',
            ])
            ->assertSessionHasErrors('role');
    }

    #[Test]
    public function test_reset_queues_permission_action(): void
    {
        Queue::create(['number' => 'T-001', 'service' => 'teller']);
        $this->assertDatabaseCount('queues', 1);

        $teller = User::factory()->create(['role' => 'teller']);
        $teller->assignRole('teller');

        $this->actingAs($teller)
            ->post(route('admin.queues.reset'))
            ->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('admin.queues.reset'))
            ->assertRedirect();

        $this->assertDatabaseCount('queues', 0);
    }
}
