<?php

namespace Tests\Feature;

use App\Filament\Resources\ServiceSettingResource;
use App\Filament\Widgets\QueueStatsWidget;
use App\Models\{Branch, Queue, User, ServiceSetting};
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AdditionalAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->travelTo(\Carbon\Carbon::parse('2026-10-14 09:00', 'Asia/Jakarta'));
    }

    private function branch(string $code): Branch
    {
        return Branch::create(['code' => $code, 'slug' => 'office-'.$code, 'name' => $code,
            'type' => 'Kantor Cabang', 'is_active' => true]);
    }

    private function counter(Branch $branch, string $name = 'Teller'): ServiceSetting
    {
        return ServiceSetting::create(['branch_id' => $branch->id, 'service' => 'teller',
            'label' => $name, 'counter_name' => $name, 'is_active' => true]);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);
        return $user;
    }

    public function test_staff_mode_rejects_nested_switches_without_overwriting_original_admin(): void
    {
        $admin = $this->admin();
        $staff = User::factory()->create();
        $staff->assignRole('teller');
        $target = User::factory()->create();
        $target->assignRole('admin');
        $this->actingAs($staff)->withSession([
            'impersonated_by' => $admin->id, 'impersonator_guard' => 'web', 'impersonator_guard_using' => null,
        ])->get(route('admin.impersonate', $target))->assertForbidden();
        $this->assertAuthenticatedAs($staff);
        $this->assertSame($admin->id, session('impersonated_by'));
    }

    public function test_staff_mode_rejects_inactive_target_and_own_account(): void
    {
        $admin = $this->admin();
        $inactive = User::factory()->create(['is_active' => false]);
        $this->get(route('admin.impersonate', $inactive))->assertForbidden();
        $this->get(route('admin.impersonate', $admin))->assertForbidden();
        $this->assertAuthenticatedAs($admin);
        $this->assertNull(session('impersonated_by'));
    }

    public function test_return_alias_is_not_swallowed_by_user_route(): void
    {
        $this->admin();
        $this->get('/admin/impersonate/leave')->assertRedirect(route('filament-impersonate.leave'));
    }

    public function test_valid_staff_mode_keeps_original_admin_and_can_return(): void
    {
        $admin = $this->admin();
        $staff = User::factory()->create();
        $staff->assignRole('teller');
        $this->get(route('admin.impersonate', $staff))->assertRedirect('/admin/queues');
        $this->assertSame($admin->id, session('impersonated_by'));
        $this->get('/filament-impersonate/leave')->assertRedirect();
        $this->assertAuthenticatedAs($admin);
    }

    public function test_ticket_office_cannot_override_kiosk_url(): void
    {
        $own = $this->branch('101');
        $other = $this->branch('102');
        $this->counter($own);
        $this->counter($other);
        $this->postJson('/ambil-nomor/101', ['branch_id' => $other->id, 'service' => 'teller'])
            ->assertUnprocessable()->assertJsonValidationErrors('branch_id');
        $this->assertDatabaseCount('queues', 0);
        $this->postJson('/ambil-nomor/101', ['branch_id' => $own->id, 'service' => 'teller'])
            ->assertOk()->assertJsonPath('branch_id', $own->id);
    }

    public function test_verification_counts_same_service_across_counters_only_on_ticket_day_even_for_foreign_session(): void
    {
        $branch = $this->branch('101');
        $first = $this->counter($branch);
        $second = $this->counter($branch, 'Teller 2');
        $old = Queue::create(['branch_id' => $branch->id, 'service_setting_id' => $first->id, 'number' => 'T-001']);
        $old->forceFill(['created_at' => now()->subDay()])->save();
        Queue::create(['branch_id' => $branch->id, 'service_setting_id' => $first->id, 'number' => 'T-001']);
        $ticket = Queue::create(['branch_id' => $branch->id, 'service_setting_id' => $second->id, 'number' => 'T-002']);
        $foreign = $this->branch('102');
        $user = User::factory()->create(['branch_id' => $foreign->id]);
        $user->assignRole('cs');
        $this->actingAs($user)->get('/verifikasi/'.$ticket->id)->assertOk()->assertViewHas('waitingBefore', 1);
    }

    public function test_officeless_account_cannot_read_counters_or_staff_totals_from_other_offices(): void
    {
        $branch = $this->branch('101');
        $this->counter($branch);
        User::factory()->create(['branch_id' => $branch->id])->assignRole('teller');
        $user = User::factory()->create(['branch_id' => null]);
        $user->givePermissionTo(['view_any_queue', 'view_any_service_setting']);
        $this->actingAs($user);
        $this->assertSame(0, ServiceSettingResource::getEloquentQuery()->count());
        $widget = app(QueueStatsWidget::class);
        $stats = (new \ReflectionMethod($widget, 'getStats'))->invoke($widget);
        $this->assertSame(0, $stats[3]->getValue());
    }

    public function test_role_api_rejects_permissions_from_another_guard_without_partial_role_creation(): void
    {
        $this->admin();
        Permission::create(['name' => 'api_only', 'guard_name' => 'api']);
        $this->postJson('/admin/api/roles', ['name' => 'Unsafe Role', 'permissions' => ['api_only']])
            ->assertUnprocessable();
        $this->assertDatabaseMissing('roles', ['name' => 'unsafe_role']);
    }

    public function test_counter_names_are_unique_within_an_office_and_reusable_in_other_offices(): void
    {
        $this->admin();
        $own = $this->branch('101');
        $other = $this->branch('102');
        $this->counter($own, 'Loket 1');
        $this->postJson('/admin/api/lokets', ['branch_id' => $other->id, 'counter_name' => 'Loket 1', 'service' => 'teller'])->assertOk();
        $this->postJson('/admin/api/lokets', ['branch_id' => $other->id, 'counter_name' => 'Loket 1', 'service' => 'teller'])->assertUnprocessable();
        $this->postJson('/admin/api/lokets', ['branch_id' => $own->id, 'counter_name' => 'Bad service', 'service' => 'invalid'])->assertUnprocessable();
        $this->assertDatabaseCount('service_settings', 2);
    }

    public function test_role_api_failure_rolls_back_and_hides_internal_exception_details(): void
    {
        $this->admin();
        \App\Models\Role::created(function ($role): void {
            if ($role->name === 'rollback_role') {
                throw new \RuntimeException('Internal database details must stay private');
            }
        });
        $this->postJson('/admin/api/roles', ['name' => 'Rollback Role', 'permissions' => ['view_any_queue']])
            ->assertStatus(500)->assertJsonPath('message', 'Terjadi kesalahan sistem. Silakan coba kembali.')
            ->assertDontSee('Internal database details');
        $this->assertDatabaseMissing('roles', ['name' => 'rollback_role']);
    }
}
