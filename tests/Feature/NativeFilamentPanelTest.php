<?php

namespace Tests\Feature;

use App\Filament\Pages\BranchDetail;
use App\Filament\Pages\BranchOperations;
use App\Models\Branch;
use App\Models\User;
use App\Services\BranchAdminRole;
use Database\Seeders\DatabaseSeeder;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class NativeFilamentPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('email', 'admin@example.com')->firstOrFail());
    }

    public function test_dashboard_uses_panel_components_and_branch_details_are_separate(): void
    {
        Livewire::test(BranchOperations::class)->assertSuccessful()->assertSee('Customer Service')->assertSee('Ringkasan Antrean per Cabang')
            ->assertDontSee('Akun petugas')->assertDontSee('Loket layanan')->assertDontSee('teller.bogor@banktaspen.co.id');
        $branch = Branch::where('slug', 'bogor')->firstOrFail();
        Livewire::test(BranchDetail::class, ['branch' => $branch->id])->assertSuccessful()->assertSee('Loket layanan')->assertSee('Waktu operasional')->assertDontSee('Akun petugas');
        $this->get('/admin/admin-control-center')->assertRedirect(BranchOperations::getUrl());
    }

    public function test_branch_admin_can_log_in_without_bypassing_user_management_permissions(): void
    {
        $branch = Branch::where('slug', 'bogor')->firstOrFail();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->assignRole(BranchAdminRole::ensure());
        auth()->logout();
        app('auth')->forgetGuards();

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'password'])
            ->call('authenticate')->assertSuccessful()->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertFalse($user->can('update', $user));
    }

    public function test_operational_pages_and_widgets_work_when_staff_roles_are_missing(): void
    {
        $tellerCount = User::withRoleNames(['teller'])->count();
        foreach (['cs', 'teller'] as $name) {
            Role::findByName($name, 'web')->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $this->get('/admin/operasional-cabang')->assertOk();
            $this->get('/admin/admin-control-center')->assertRedirect(BranchOperations::getUrl());
            Livewire::test(BranchOperations::class)->assertSuccessful();
            $this->assertSame($name === 'cs' ? $tellerCount : 0, User::withRoleNames(['cs', 'teller'])->count());
        }
    }
}
