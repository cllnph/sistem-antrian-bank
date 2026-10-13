<?php

namespace Tests\Feature;

use App\Filament\Pages\BranchOperations;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\ManageUsers;
use App\Models\Branch;
use App\Models\ServiceSetting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BranchAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->artisan('branches:create-admins')->assertSuccessful();
    }

    public function test_each_branch_gets_a_scoped_admin_and_rerunning_preserves_passwords(): void
    {
        foreach (Branch::where('is_active', true)->get() as $branch) {
            $admin = User::where('email', 'admin.'.$branch->slug.'@banktaspen.co.id')->firstOrFail();
            $this->assertEquals($branch->id, $admin->branch_id);
            $this->assertNull($admin->service_setting_id);
            $this->assertTrue($admin->hasRole('admin_cabang'));
            $this->assertFalse($admin->hasRole('admin'));
            $this->assertTrue($admin->can('create_user'));
            $this->assertFalse($admin->can('view_control_center'));
        }
        $hashes = User::role('admin_cabang')->pluck('password', 'id')->all();
        $this->artisan('branches:create-admins')->assertSuccessful();
        $this->assertSame($hashes, User::role('admin_cabang')->pluck('password', 'id')->all());
    }

    public function test_branch_admin_only_sees_and_manages_own_operators_and_counters(): void
    {
        $admin = User::where('email', 'admin.bogor@banktaspen.co.id')->firstOrFail();
        $own = User::where('email', 'cs.bogor@banktaspen.co.id')->firstOrFail();
        $foreign = User::where('email', 'cs.depok@banktaspen.co.id')->firstOrFail();
        $this->actingAs($admin);
        $this->assertTrue($admin->can('update', $own));
        $this->assertFalse($admin->can('update', $foreign));
        $this->assertFalse($admin->can('update', $admin));
        $this->assertTrue($admin->can('update', $own->serviceSetting));
        $this->assertFalse($admin->can('update', $foreign->serviceSetting));
        $this->assertSame(2, UserResource::getEloquentQuery()->count());
        Livewire::test(BranchOperations::class)->assertSuccessful()
            ->assertSee('cs.bogor@banktaspen.co.id')->assertDontSee('cs.depok@banktaspen.co.id')
            ->assertSee('Kelola Akun')->assertDontSee('Kelola Cabang');
        $this->get('/admin')->assertRedirect(BranchOperations::getUrl());
        $this->get('/admin/roles')->assertForbidden();
        $this->get('/admin/branches')->assertForbidden();
        Livewire::test(ManageUsers::class)->assertSuccessful()
            ->assertActionHidden('manage_roles_slideover');
    }

    public function test_branch_admin_can_create_operator_but_cannot_assign_central_role_or_foreign_branch(): void
    {
        $admin = User::where('email', 'admin.bogor@banktaspen.co.id')->firstOrFail();
        $this->actingAs($admin);
        $data = [
            'name' => 'Operator Baru', 'email' => 'baru@example.com',
            'branch_id' => $admin->branch_id,
            'roles' => Role::findByName('cs')->id,
            'service_setting_id' => ServiceSetting::where('branch_id', $admin->branch_id)->where('service', 'cs')->value('id'),
            'password' => 'OperatorBaru123!',
        ];
        Livewire::test(ManageUsers::class)->callAction('create', data: $data)->assertHasNoActionErrors();
        $created = User::where('email', 'baru@example.com')->firstOrFail();
        $this->assertTrue($created->hasRole('cs'));
        $this->assertEquals($admin->branch_id, $created->branch_id);

        $data['email'] = 'forbidden@example.com';
        $data['roles'] = Role::findByName('admin')->id;
        Livewire::test(ManageUsers::class)->callAction('create', data: $data)->assertHasActionErrors(['roles']);
        $data['roles'] = Role::findByName('cs')->id;
        $data['branch_id'] = Branch::where('slug', 'depok')->value('id');
        Livewire::test(ManageUsers::class)->callAction('create', data: $data)->assertHasActionErrors(['branch_id']);
        $this->assertDatabaseMissing('users', ['email' => 'forbidden@example.com']);
    }
}
