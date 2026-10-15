<?php

namespace Tests\Feature;

use App\Enums\QueueStatus;
use App\Models\{Branch, Queue, Role, ServiceSetting, User};
use App\Services\QueueResetService;
use Database\Seeders\{BranchOperatingHoursSeeder, RoleAndPermissionSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class BusinessIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function blocked(callable $operation): void
    {
        try {
            $operation();
            $this->fail('Operation should be rejected without changing stored data.');
        } catch (ValidationException $exception) {
            $this->assertNotEmpty($exception->errors());
        }
    }

    private function branch(string $code = '101'): Branch
    {
        return Branch::create(['code' => $code, 'slug' => 'office-'.$code, 'name' => 'Office '.$code,
            'type' => 'cabang', 'is_active' => true]);
    }

    private function counter(Branch $branch, string $service = 'teller'): ServiceSetting
    {
        return ServiceSetting::create(['branch_id' => $branch->id, 'service' => $service,
            'counter_name' => $service, 'label' => $service, 'is_active' => true]);
    }

    private function ticket(ServiceSetting $counter, string $number = 'T-001', string $status = 'waiting', ?User $operator = null): Queue
    {
        return Queue::create(['branch_id' => $counter->branch_id, 'service_setting_id' => $counter->id,
            'number' => $number, 'status' => $status, 'served_by' => $operator?->id]);
    }

    public function test_system_roles_cannot_be_deleted_or_renamed_or_change_guard(): void
    {
        foreach (['admin', 'admin_cabang', 'cs', 'teller'] as $name) {
            $role = Role::findByName($name);
            $this->blocked(fn () => $role->delete());
            $this->blocked(fn () => $role->update(['name' => 'changed']));
            $this->blocked(fn () => $role->refresh()->update(['guard_name' => 'api']));
            $this->assertSame($name, $role->fresh()->name);
            $this->assertSame('web', $role->fresh()->guard_name);
        }
    }

    public function test_failed_role_deletion_keeps_inactive_users_and_permissions_attached(): void
    {
        $role = Role::create(['name' => 'auditor', 'guard_name' => 'web']);
        $role->givePermissionTo('view_any_queue');
        $user = User::factory()->create(['is_active' => false]);
        $user->assignRole($role);
        $this->blocked(fn () => $role->delete());
        $this->assertTrue($user->fresh()->hasRole('auditor'));
        $this->assertTrue($role->fresh()->hasPermissionTo('view_any_queue'));
        $user->removeRole($role);
        $this->assertTrue($role->delete());
    }

    public function test_used_office_and_counter_are_protected_but_empty_records_can_be_deleted(): void
    {
        $branch = $this->branch();
        $counter = $this->counter($branch);
        $user = User::factory()->create(['branch_id' => $branch->id, 'service_setting_id' => $counter->id]);
        $this->blocked(fn () => $branch->delete());
        $this->blocked(fn () => $counter->delete());
        $this->blocked(fn () => $counter->update(['service' => 'cs']));
        $this->blocked(fn () => $branch->update(['code' => '999']));
        $this->assertSame($counter->id, $user->fresh()->service_setting_id);
        $user->delete();
        $this->assertTrue($counter->refresh()->delete());
        $this->assertTrue($branch->refresh()->delete());
    }

    public function test_last_active_administrator_cannot_be_disabled_deleted_or_demoted(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->blocked(fn () => $admin->update(['is_active' => false]));
        $this->blocked(fn () => $admin->refresh()->delete());
        $this->blocked(fn () => $admin->refresh()->syncRoles(['teller']));
        $this->assertTrue($admin->fresh()->is_active);
        $this->assertTrue($admin->fresh()->hasRole('admin'));
        $other = User::factory()->create();
        $other->assignRole('admin');
        $admin->refresh()->update(['is_active' => false]);
        $this->assertFalse($admin->fresh()->is_active);
    }

    public function test_current_admin_cannot_disable_or_demote_own_account_even_with_another_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        User::factory()->create()->assignRole('admin');
        $this->actingAs($admin);
        $this->blocked(fn () => $admin->update(['is_active' => false]));
        $this->blocked(fn () => $admin->refresh()->removeRole('admin'));
        $this->blocked(fn () => $admin->delete());
    }

    public function test_active_service_protects_operator_counter_and_office_until_reset(): void
    {
        $branch = $this->branch();
        $counter = $this->counter($branch);
        $user = User::factory()->create(['branch_id' => $branch->id, 'service_setting_id' => $counter->id]);
        $user->assignRole('teller');
        $this->ticket($counter, status: 'serving', operator: $user);
        $user->syncRoles(['teller']);
        $this->assertTrue($user->fresh()->hasRole('teller'));
        $this->blocked(fn () => $user->update(['is_active' => false]));
        $this->blocked(fn () => $user->refresh()->syncRoles(['cs']));
        $this->blocked(fn () => $counter->update(['is_active' => false]));
        $this->blocked(fn () => $branch->update(['is_active' => false]));
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->assertSame(1, QueueResetService::reset($admin, $branch->id));
        $user->refresh()->update(['is_active' => false]);
        $counter->refresh()->update(['is_active' => false]);
        $branch->refresh()->update(['is_active' => false]);
        $this->assertFalse($branch->fresh()->is_active);
        $this->blocked(fn () => $user->delete());
        $this->blocked(fn () => $counter->delete());
    }

    public function test_operator_roles_and_counter_branches_must_match(): void
    {
        $branch = $this->branch();
        $counter = $this->counter($branch);
        $other = $this->branch('102');
        $user = User::factory()->create(['branch_id' => $branch->id, 'service_setting_id' => $counter->id]);
        $this->blocked(fn () => $user->syncRoles(['cs']));
        $user->syncRoles(['teller']);
        $this->blocked(fn () => $user->update(['branch_id' => $other->id]));
        $this->assertSame($branch->id, $user->fresh()->branch_id);
        $this->assertTrue($user->fresh()->hasRole('teller'));
    }

    public function test_ticket_identity_duplicates_and_terminal_status_are_protected(): void
    {
        $counter = $this->counter($this->branch());
        $ticket = $this->ticket($counter);
        $this->blocked(fn () => $this->ticket($counter));
        $this->blocked(fn () => $ticket->update(['number' => 'T-999']));
        $this->blocked(fn () => $ticket->refresh()->update(['status' => 'completed']));
        $ticket->refresh()->update(['status' => 'called']);
        $ticket->update(['status' => 'completed']);
        $this->blocked(fn () => $ticket->update(['status' => 'waiting']));
        $this->blocked(fn () => $ticket->refresh()->delete());
        $this->assertSame(QueueStatus::COMPLETED, $ticket->fresh()->status);
        $this->assertSame('T-001', $ticket->fresh()->number);
    }

    public function test_cross_office_and_closed_counter_cannot_issue_tickets(): void
    {
        $counter = $this->counter($this->branch());
        $other = $this->branch('102');
        $this->blocked(fn () => Queue::create(['branch_id' => $other->id, 'service_setting_id' => $counter->id, 'number' => 'T-001']));
        $counter->update(['is_active' => false]);
        $this->blocked(fn () => $this->ticket($counter));
        $this->assertDatabaseCount('queues', 0);
    }

    public function test_reset_preserves_history_and_other_offices_and_services(): void
    {
        $branch = $this->branch();
        $teller = $this->counter($branch);
        $cs = $this->counter($branch, 'cs');
        $foreign = $this->counter($this->branch('102'));
        $waiting = $this->ticket($teller);
        $serving = $this->ticket($teller, 'T-002', 'serving');
        $done = $this->ticket($teller, 'T-003', 'completed');
        $otherService = $this->ticket($cs, 'CS-001');
        $otherOffice = $this->ticket($foreign);
        $actor = User::factory()->create(['branch_id' => $branch->id]);
        $actor->givePermissionTo('reset_queues');
        $this->assertSame(2, QueueResetService::reset($actor, service: 'teller'));
        $this->assertSame(QueueStatus::SKIPPED, $waiting->fresh()->status);
        $this->assertSame(QueueStatus::SKIPPED, $serving->fresh()->status);
        $this->assertSame(QueueStatus::COMPLETED, $done->fresh()->status);
        foreach ([$otherService, $otherOffice] as $ticket) {
            $this->assertSame(QueueStatus::WAITING, $ticket->fresh()->status);
        }
        $this->assertDatabaseCount('queues', 5);
        $this->assertDatabaseCount('queue_logs', 2);
        $this->assertDatabaseHas('queue_logs', ['queue_id' => $serving->id, 'action' => 'reset', 'actor_id' => $actor->id]);
        $this->assertSame(0, QueueResetService::reset($actor, service: 'teller'));
        $this->expectException(HttpException::class);
        QueueResetService::reset($actor, $foreign->branch_id);
    }

    public function test_reset_requires_permission_and_active_account(): void
    {
        $actor = User::factory()->create();
        try {
            QueueResetService::reset($actor);
            $this->fail('Permission is required.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $actor->givePermissionTo('reset_queues');
        $actor->update(['is_active' => false]);
        $this->expectException(HttpException::class);
        QueueResetService::reset($actor);
    }

    public function test_service_hours_update_does_not_change_kiosk_hours(): void
    {
        $branch = $this->branch();
        $this->seed(BranchOperatingHoursSeeder::class);
        $this->assertSame(BranchOperatingHoursSeeder::HOURS, $branch->fresh()->operating_hours);
        foreach (['04:00' => true, '07:59' => true, '15:01' => true, '17:59' => true, '18:00' => false] as $time => $open) {
            $this->travelTo(\Carbon\Carbon::parse('2026-10-14 '.$time, 'Asia/Jakarta'));
            $this->assertSame($open, \App\Services\KioskHours::isOpen());
        }
    }

    public function test_next_ticket_does_not_silently_complete_an_active_service(): void
    {
        $counter = $this->counter($this->branch());
        $user = User::factory()->create(['branch_id' => $counter->branch_id, 'service_setting_id' => $counter->id]);
        $user->assignRole('teller');
        $active = $this->ticket($counter, status: 'called', operator: $user);
        $waiting = $this->ticket($counter, 'T-002');
        $this->actingAs($user)->postJson(route('queue.next.teller'))->assertUnprocessable()->assertJsonValidationErrors('queue');
        $this->assertSame(QueueStatus::CALLED, $active->fresh()->status);
        $this->assertSame(QueueStatus::WAITING, $waiting->fresh()->status);
        $this->post(route('queue.complete.teller'))->assertRedirect();
        $this->post(route('queue.next.teller'))->assertRedirect();
        $this->assertSame(QueueStatus::CALLED, $waiting->fresh()->status);
        $this->assertSame($user->id, $waiting->fresh()->served_by);
    }

    public function test_read_only_operator_cannot_call_or_toggle_counter(): void
    {
        $counter = $this->counter($this->branch());
        $user = User::factory()->create(['branch_id' => $counter->branch_id, 'service_setting_id' => $counter->id]);
        $role = Role::create(['name' => 'viewer', 'guard_name' => 'web']);
        $role->givePermissionTo('view_any_queue');
        $user->assignRole($role);
        $ticket = $this->ticket($counter);
        $this->actingAs($user);
        \Livewire\Livewire::test(\App\Filament\Resources\QueueResource\Pages\ManageQueues::class)
            ->assertTableActionHidden('call', $ticket)->assertActionHidden('toggleCounterStatus');
        $this->assertFalse($user->can('update', $ticket));
        $this->assertSame(QueueStatus::WAITING, $ticket->fresh()->status);
    }

    public function test_another_operator_cannot_finish_or_recall_an_owned_ticket(): void
    {
        $counter = $this->counter($this->branch());
        $owner = User::factory()->create(['branch_id' => $counter->branch_id, 'service_setting_id' => $counter->id]);
        $other = User::factory()->create(['branch_id' => $counter->branch_id, 'service_setting_id' => $counter->id]);
        $owner->assignRole('teller');
        $other->assignRole('teller');
        $ticket = $this->ticket($counter, status: 'called', operator: $owner);
        $this->actingAs($other);
        \Livewire\Livewire::test(\App\Filament\Resources\QueueResource\Pages\ManageQueues::class)
            ->assertTableActionHidden('complete', $ticket)->assertTableActionHidden('recall', $ticket)
            ->assertTableActionHidden('skip', $ticket);
        $this->post(route('queue.complete.teller'))->assertRedirect();
        $this->assertSame(QueueStatus::CALLED, $ticket->fresh()->status);
        $this->assertSame($owner->id, $ticket->fresh()->served_by);
    }

    public function test_native_user_form_rolls_back_account_changes_when_role_counter_mismatch(): void
    {
        $counter = $this->counter($this->branch());
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $user = User::factory()->create(['branch_id' => $counter->branch_id, 'service_setting_id' => $counter->id]);
        $user->assignRole('teller');
        $name = $user->name;
        $this->actingAs($admin);
        \Livewire\Livewire::test(\App\Filament\Resources\UserResource\Pages\ManageUsers::class)
            ->callTableAction('edit', $user, data: ['name' => 'Should roll back', 'email' => $user->email,
                'branch_id' => $counter->branch_id, 'service_setting_id' => $counter->id,
                'roles' => Role::findByName('cs')->id, 'password' => ''])
            ->assertHasActionErrors(['service_setting_id']);
        $this->assertSame($name, $user->fresh()->name);
        $this->assertTrue($user->fresh()->hasRole('teller'));
    }

    public function test_manual_ticket_form_forces_waiting_and_rejects_foreign_counter(): void
    {
        $counter = $this->counter($this->branch());
        $other = $this->branch('102');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        \Livewire\Livewire::test(\App\Filament\Resources\QueueResource\Pages\ManageQueues::class)
            ->callAction('create', data: ['number' => 'T-001', 'branch_id' => $other->id,
                'service_setting_id' => $counter->id, 'status' => 'completed'])
            ->assertHasActionErrors(['service_setting_id']);
        \Livewire\Livewire::test(\App\Filament\Resources\QueueResource\Pages\ManageQueues::class)
            ->callAction('create', data: ['number' => 'T-001', 'branch_id' => $counter->branch_id,
                'service_setting_id' => $counter->id, 'status' => 'completed'])->assertHasNoActionErrors();
        $this->assertDatabaseHas('queues', ['number' => 'T-001', 'status' => 'waiting']);
        $this->assertDatabaseCount('queues', 1);
    }

    public function test_account_without_office_cannot_read_other_offices_queues(): void
    {
        $counter = $this->counter($this->branch());
        $this->ticket($counter);
        $user = User::factory()->create(['branch_id' => null]);
        $user->assignRole('teller');
        $this->actingAs($user);
        $this->assertSame(0, Queue::count());
    }

    public function test_report_cannot_be_redirected_to_another_office_by_non_admin(): void
    {
        $own = $this->branch();
        $other = $this->branch('102');
        $this->ticket($this->counter($other));
        $user = User::factory()->create(['branch_id' => $own->id]);
        $user->givePermissionTo('view_control_center');
        $this->actingAs($user);
        $page = app(\App\Filament\Pages\AdminControlCenter::class);
        $page->selectedBranchId = (string) $other->id;
        $data = (new \ReflectionMethod($page, 'getViewData'))->invoke($page);
        $this->assertSame(0, $data['summary']['teller']['waiting']);
        $this->assertSame([$own->id], array_column($data['branchStats'], 'id'));
        $this->assertCount(0, $data['recentQueues']);
    }

    public function test_read_only_view_does_not_expire_tickets_and_operator_expiry_is_logged(): void
    {
        $counter = $this->counter($this->branch());
        $ticket = $this->ticket($counter, status: 'serving');
        $ticket->forceFill(['created_at' => now()->subDay()])->save();
        $user = User::factory()->create(['branch_id' => $counter->branch_id, 'service_setting_id' => $counter->id]);
        $user->givePermissionTo('view_any_queue');
        $this->actingAs($user);
        \App\Filament\Resources\QueueResource::getEloquentQuery()->get();
        $this->assertSame(QueueStatus::SERVING, $ticket->fresh()->status);
        $user->givePermissionTo('edit_queue');
        \App\Filament\Resources\QueueResource::getEloquentQuery()->get();
        $this->assertSame(QueueStatus::SKIPPED, $ticket->fresh()->status);
        $this->assertDatabaseHas('queue_logs', ['queue_id' => $ticket->id, 'action' => 'expired']);
    }
}
