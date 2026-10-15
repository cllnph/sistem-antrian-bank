<?php

namespace Tests\Feature;

use App\Filament\Resources\RoleResource;
use App\Models\Branch;
use App\Models\Queue;
use App\Models\ServiceSetting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('admin');
        $this->actingAs($user);

        return $user;
    }

    private function branch(string $code): Branch
    {
        return Branch::create(['code' => $code, 'slug' => 'branch-'.$code, 'name' => 'Branch '.$code, 'type' => 'Kantor Cabang', 'is_active' => true]);
    }

    public function test_user_view_permission_cannot_manage_roles(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(['view_any_user', 'view_control_center']);
        $this->actingAs($user);
        $role = Role::findByName('admin');

        $this->assertFalse(RoleResource::canViewAny());
        $this->assertFalse(RoleResource::canCreate());
        $this->assertFalse(RoleResource::canEdit($role));
        $this->assertFalse(RoleResource::canDelete($role));
        $this->postJson('/admin/api/roles', ['name' => 'intruder', 'permissions' => ['edit_user']])->assertForbidden();
    }

    public function test_normalized_role_collision_does_not_change_existing_permissions(): void
    {
        $this->admin();
        $role = Role::create(['name' => 'head_teller', 'guard_name' => 'web']);
        $role->givePermissionTo('view_any_queue');

        $this->postJson('/admin/api/roles', ['name' => 'Head Teller', 'permissions' => ['edit_user']])
            ->assertStatus(422);
        $this->assertEquals(['view_any_queue'], $role->fresh()->permissions->pluck('name')->all());
    }

    public function test_new_role_is_created_with_valid_permissions(): void
    {
        $this->admin();
        $this->postJson('/admin/api/roles', ['name' => 'Branch Auditor', 'permissions' => ['view_any_queue']])
            ->assertOk()->assertJsonPath('role.name', 'branch_auditor');
        $this->assertTrue(Role::findByName('branch_auditor')->hasPermissionTo('view_any_queue'));
    }

    public function test_api_counter_is_attached_to_selected_branch(): void
    {
        $this->admin();
        $branch = $this->branch('101');
        $this->postJson('/admin/api/lokets', ['branch_id' => $branch->id, 'counter_name' => 'New Teller', 'service' => 'teller'])
            ->assertOk();
        $this->assertDatabaseHas('service_settings', ['counter_name' => 'New Teller', 'branch_id' => $branch->id]);
        $this->get('/ambil-nomor/'.$branch->code)->assertOk()->assertViewHas('tellerActive', true);
    }

    public function test_operator_cannot_create_counter_in_another_branch(): void
    {
        $own = $this->branch('101');
        $other = $this->branch('102');
        $user = User::factory()->create(['branch_id' => $own->id, 'is_active' => true]);
        $user->assignRole('teller');
        $user->givePermissionTo('edit_service_setting');
        $this->actingAs($user)->postJson('/admin/api/lokets', ['branch_id' => $other->id, 'counter_name' => 'Foreign Teller', 'service' => 'teller'])
            ->assertForbidden();
        $this->assertDatabaseMissing('service_settings', ['counter_name' => 'Foreign Teller']);
    }

    public function test_ticket_verification_uses_identity_across_branches_and_days(): void
    {
        $first = $this->branch('101');
        $second = $this->branch('102');
        $setting = ServiceSetting::create(['branch_id' => $first->id, 'service' => 'teller', 'counter_name' => 'Teller', 'label' => 'Teller', 'is_active' => true]);
        $ticket = Queue::create(['branch_id' => $first->id, 'number' => 'T-001', 'service_setting_id' => $setting->id, 'status' => 'waiting']);
        $ticket->forceFill(['created_at' => now()->subDay()])->save();
        $secondSetting = ServiceSetting::create(['branch_id' => $second->id, 'service' => 'teller', 'counter_name' => 'Teller', 'label' => 'Teller', 'is_active' => true]);
        Queue::create(['branch_id' => $second->id, 'number' => 'T-001', 'service_setting_id' => $secondSetting->id, 'status' => 'waiting']);

        $this->get(route('queue.verify', ['queueId' => $ticket->id]))->assertOk()
            ->assertViewHas('queue', fn ($queue) => $queue->id === $ticket->id);
        $this->get('/verifikasi/T-001')->assertNotFound();
    }

    public function test_seeding_preserves_existing_account_credentials_and_status(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.com', 'is_active' => false]);
        $hash = $user->password;
        $user->assignRole('teller');
        $this->seed(DatabaseSeeder::class);

        $this->assertSame($hash, $user->fresh()->password);
        $this->assertFalse($user->fresh()->is_active);
        $this->assertTrue($user->fresh()->hasRole('teller'));
        $this->assertFalse($user->fresh()->hasRole('admin'));
    }

    public function test_logout_requires_post_and_get_preserves_session(): void
    {
        $user = $this->admin();
        foreach (['/logout', '/admin/logout-custom'] as $path) {
            $this->get($path)->assertStatus(405);
            $this->assertAuthenticatedAs($user);
        }
        $this->post('/logout')->assertRedirect('/admin/login');
        $this->assertGuest();
    }
}
