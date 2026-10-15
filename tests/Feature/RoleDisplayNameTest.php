<?php

namespace Tests\Feature;

use App\Filament\Resources\RoleResource\Pages\ManageRoles;
use App\Filament\Resources\UserResource\Pages\ManageUsers;
use App\Filament\Widgets\ControlCenterStaffWidget;
use App\Models\{Branch, Role, Queue, ServiceSetting, User};
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RoleDisplayNameTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
    }

    public function test_native_form_can_rename_every_system_role_without_changing_code_or_permissions(): void
    {
        foreach (['admin', 'admin_cabang', 'cs', 'teller'] as $code) {
            $role = Role::findByName($code);
            $permissions = $role->permissions->pluck('id')->all();
            Livewire::test(ManageRoles::class)->callTableAction('edit', $role, data: [
                'display_name' => 'Nama Baru '.$code, 'permissions' => $permissions,
            ])->assertHasNoActionErrors();
            $this->assertSame($code, $role->fresh()->name);
            $this->assertSame('Nama Baru '.$code, $role->fresh()->display_name);
            $this->assertEqualsCanonicalizing($permissions, $role->fresh()->permissions->pluck('id')->all());
        }
    }

    public function test_renamed_cs_remains_authorized_and_can_call_cs_ticket(): void
    {
        $role = Role::findByName('cs');
        $role->update(['display_name' => 'Layanan Pelanggan']);
        $branch = Branch::create(['code' => '101', 'slug' => 'office', 'name' => 'Office', 'type' => 'Kantor Cabang', 'is_active' => true]);
        $counter = ServiceSetting::create(['branch_id' => $branch->id, 'service' => 'cs', 'label' => 'CS', 'counter_name' => 'CS', 'is_active' => true]);
        $user = User::factory()->create(['branch_id' => $branch->id, 'service_setting_id' => $counter->id]);
        $user->assignRole('cs');
        $ticket = Queue::create(['branch_id' => $branch->id, 'service_setting_id' => $counter->id, 'number' => 'CS-001']);
        $this->actingAs($user)->post(route('queue.next.cs'))->assertRedirect();
        $this->assertTrue($user->fresh()->hasRole('cs'));
        $this->assertTrue($user->fresh()->can('edit_queue'));
        $this->assertDatabaseHas('queues', ['id' => $ticket->id, 'status' => 'called', 'served_by' => $user->id]);
        $this->get('/admin/operasional-cabang')->assertOk()->assertSee('Layanan Pelanggan');
    }

    public function test_labels_are_consistent_in_role_user_and_staff_tables(): void
    {
        $role = Role::findByName('teller');
        $role->update(['display_name' => 'Petugas Kas']);
        User::factory()->create()->assignRole('teller');
        Livewire::test(ManageRoles::class)->assertSee('Petugas Kas');
        Livewire::test(ManageUsers::class)->assertSee('Petugas Kas');
        Livewire::test(ControlCenterStaffWidget::class)->assertSee('Petugas Kas');
    }

    public function test_new_role_gets_a_code_and_keeps_it_after_label_edit(): void
    {
        $permission = \Spatie\Permission\Models\Permission::findByName('view_any_queue');
        Livewire::test(ManageRoles::class)->callAction('create', data: [
            'display_name' => 'Pengawas Cabang', 'permissions' => [$permission->id],
        ])->assertHasNoActionErrors();
        $role = Role::where('name', 'pengawas_cabang')->firstOrFail();
        $role->update(['display_name' => 'Auditor Kantor']);
        $this->assertSame('pengawas_cabang', $role->fresh()->name);
        $this->assertTrue($role->fresh()->hasPermissionTo('view_any_queue'));
        $second = Role::create(['display_name' => 'Pengawas Cabang']);
        $this->assertSame('pengawas_cabang_2', $second->name);
    }

    public function test_role_label_cannot_be_blank_in_native_form(): void
    {
        $role = Role::findByName('cs');
        Livewire::test(ManageRoles::class)->callTableAction('edit', $role, data: [
            'display_name' => '', 'permissions' => $role->permissions->pluck('id')->all(),
        ])->assertHasActionErrors(['display_name' => 'required']);
        $this->assertSame('Customer Service', $role->fresh()->display_name);
    }

    public function test_api_preserves_original_display_name_separately_from_code(): void
    {
        $this->postJson('/admin/api/roles', ['name' => 'Pengawas Kantor', 'permissions' => ['view_any_queue']])
            ->assertOk()->assertJsonPath('role.name', 'pengawas_kantor')->assertJsonPath('role.display_name', 'Pengawas Kantor');
    }
}
