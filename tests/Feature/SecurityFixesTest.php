<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Queue;
use App\Models\ServiceSetting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Livewire\Livewire;
use App\Filament\Resources\UserResource\Pages\ManageUsers;
use App\Filament\Resources\ServiceSettingResource\Pages\ManageServiceSettings;

class SecurityFixesTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->travelTo(\Carbon\Carbon::parse('2026-10-14 09:00:00', 'Asia/Jakarta'));
        $this->branch = Branch::create([
            'code' => '001', 'slug' => 'pusat', 'name' => 'Kantor Pusat',
            'type' => 'Kantor Pusat', 'is_active' => true,
        ]);
    }

    #[Test]
    public function test_route_authorization_middleware_blocks_unauthorized_roles(): void
    {
        $teller = User::factory()->create(['is_active' => true, 'branch_id' => $this->branch->id]);
        $teller->assignRole('teller');

        $this->actingAs($teller)->get('/admin/users')->assertForbidden();
        $this->actingAs($teller)->get('/admin/roles')->assertForbidden();
        $this->actingAs($teller)->get('/admin/lokets')->assertRedirect('/admin/service-settings');
        $this->actingAs($teller)->get('/admin/service-settings')->assertForbidden();
        $this->actingAs($teller)->get('/admin')->assertRedirect('/admin/queues');

        $admin = User::factory()->create(['is_active' => true, 'branch_id' => $this->branch->id]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/admin/roles')->assertOk();
        $this->actingAs($admin)->get('/admin/lokets')->assertRedirect('/admin/service-settings');
        $this->actingAs($admin)->get('/admin/service-settings')->assertOk();
        $this->actingAs($admin)->get('/admin')->assertRedirect('/admin/operasional-cabang');
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

        $teller = User::factory()->create(['is_active' => true, 'branch_id' => $this->branch->id]);
        $teller->assignRole('teller');

        $this->assertTrue($teller->canAccessPanel(new \Filament\Panel()));
    }

    #[Test]
    public function test_two_layer_authorization_on_loket_controller(): void
    {
        $loket1 = ServiceSetting::create(['branch_id' => $this->branch->id, 'label' => 'Loket 1', 'counter_name' => 'Loket 1', 'service' => 'teller']);
        $loket2 = ServiceSetting::create(['branch_id' => $this->branch->id, 'label' => 'Loket 2', 'counter_name' => 'Loket 2', 'service' => 'teller']);

        $user = User::factory()->create(['is_active' => true, 'branch_id' => $this->branch->id,
            'service_setting_id' => $loket1->id,
        ]);
        $user->assignRole('teller');
        $user->givePermissionTo(['edit_service_setting', 'view_any_service_setting']);

        $this->actingAs($user);
        $this->assertFalse($user->can('update', $loket2));
        Livewire::test(ManageServiceSettings::class)
            ->assertCanNotSeeTableRecords([$loket2])
            ->callTableAction('edit', $loket1, data: [
                'branch_id' => $this->branch->id,
                'counter_name' => 'Loket 1 Updated',
                'service' => 'teller',
                'label' => 'Loket 1',
                'is_active' => true,
            ])->assertHasNoActionErrors();
        $this->assertSame('Loket 1 Updated', $loket1->fresh()->counter_name);
        $this->assertSame('Loket 2', $loket2->fresh()->counter_name);
    }

    #[Test]
    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create(['is_active' => true, 'branch_id' => $this->branch->id,
            'email' => 'inactive@bank.com',
            'password' => bcrypt('password123'),
            'is_active' => false,
        ]);

        Livewire::test(\Filament\Auth\Pages\Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'password123'])
            ->call('authenticate')
            ->assertHasErrors(['data.email']);
        $this->assertGuest();
    }

    #[Test]
    public function test_inactive_logged_in_user_is_automatically_logged_out_by_middleware(): void
    {
        $user = User::factory()->create(['is_active' => true, 'branch_id' => $this->branch->id,
            'email' => 'disabled@bank.com',
            'password' => bcrypt('password123'),
            'is_active' => false,
        ]);
        $user->assignRole('teller');

        $response = $this->actingAs($user)->get('/admin/queues');

        $response->assertForbidden();
        $this->assertGuest();
    }

    #[Test]
    public function test_password_reset_flash_message_does_not_leak_plaintext_password(): void
    {
        $admin = User::factory()->create(['is_active' => true, 'branch_id' => $this->branch->id]);
        $admin->assignRole('admin');
        $targetUser = User::factory()->create(['is_active' => true, 'branch_id' => $this->branch->id]);
        $targetUser->assignRole('teller');

        $this->actingAs($admin);
        $component = Livewire::test(ManageUsers::class)
            ->callTableAction('edit', $targetUser, data: [
                'name' => $targetUser->name, 'email' => $targetUser->email,
                'branch_id' => $this->branch->id,
                'roles' => Role::findByName('teller')->id,
                'password' => 'SecretPass123!',
            ])->assertHasNoActionErrors();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('SecretPass123!', $targetUser->fresh()->password));
        $this->assertStringNotContainsString('SecretPass123!', json_encode(session()->all()));
        $this->assertStringNotContainsString('SecretPass123!', $component->html());
    }

    #[Test]
    public function test_sequential_ticket_requests_receive_distinct_numbers(): void
    {
        ServiceSetting::create(['branch_id' => $this->branch->id, 'label' => 'Loket Teller', 'counter_name' => 'Loket Teller', 'service' => 'teller', 'is_active' => true]);

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
        $tellerSetting = ServiceSetting::create(['branch_id' => $this->branch->id, 'label' => 'Loket Teller', 'counter_name' => 'Loket Teller', 'service' => 'teller', 'is_active' => true]);

        $teller1 = User::factory()->create(['is_active' => true, 'branch_id' => $this->branch->id, 'service_setting_id' => $tellerSetting->id]);
        $teller1->assignRole('teller');
        $teller2 = User::factory()->create(['is_active' => true, 'branch_id' => $this->branch->id, 'service_setting_id' => $tellerSetting->id]);
        $teller2->assignRole('teller');

        Queue::create(['branch_id' => $this->branch->id,
            'number' => 'T-001',
            'service_setting_id' => $tellerSetting->id,
            'status' => 'called',
            'served_by' => $teller1->id,
            'called_at' => now()->subMinute(),
        ]);

        Queue::create(['branch_id' => $this->branch->id,
            'number' => 'T-002',
            'service_setting_id' => $tellerSetting->id,
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
        $umumSetting = ServiceSetting::create(['branch_id' => $this->branch->id, 'label' => 'Loket Umum', 'counter_name' => 'Loket Umum', 'service' => 'umum', 'is_active' => true]);

        $this->post(route('queue.store-number'), ['service' => 'umum'])
            ->assertSessionHas('ticket');

        $this->assertDatabaseHas('queues', [
            'service_setting_id' => $umumSetting->id,
        ]);
    }

    #[Test]
    public function test_user_role_validation(): void
    {
        $admin = User::factory()->create(['is_active' => true, 'branch_id' => $this->branch->id]);
        $admin->assignRole('admin');

        $this->actingAs($admin);
        Livewire::test(ManageUsers::class)
            ->callAction('create', data: [
                'name' => 'Invalid Role User', 'email' => 'invalidrole@bank.com',
                'branch_id' => $this->branch->id, 'roles' => 999999,
                'password' => 'password123',
            ])->assertHasActionErrors(['roles']);
        $this->assertDatabaseMissing('users', ['email' => 'invalidrole@bank.com']);
    }

    #[Test]
    public function test_reset_queues_permission_action(): void
    {
        $tellerSetting = ServiceSetting::create(['branch_id' => $this->branch->id, 'label' => 'Loket Teller', 'counter_name' => 'Loket Teller', 'service' => 'teller', 'is_active' => true]);
        Queue::create(['branch_id' => $this->branch->id, 'number' => 'T-001', 'service_setting_id' => $tellerSetting->id]);
        $this->assertDatabaseCount('queues', 1);

        $teller = User::factory()->create(['is_active' => true, 'branch_id' => $this->branch->id]);
        $teller->assignRole('teller');

        $this->actingAs($teller)
            ->post(route('admin.queues.reset'))
            ->assertForbidden();

        $admin = User::factory()->create(['is_active' => true, 'branch_id' => $this->branch->id]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('admin.queues.reset'))
            ->assertRedirect();

        $this->assertDatabaseCount('queues', 1);
        $this->assertDatabaseHas('queues', ['number' => 'T-001', 'status' => 'skipped']);
        $this->assertDatabaseHas('queue_logs', ['action' => 'reset', 'actor_id' => $admin->id]);
    }
}
