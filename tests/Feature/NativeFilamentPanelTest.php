<?php

namespace Tests\Feature;

use App\Filament\Pages\AdminControlCenter;
use App\Filament\Pages\BranchOperations;
use App\Filament\Widgets\ControlCenterStaffWidget;
use App\Filament\Widgets\QueueOperationsWidget;
use App\Filament\Widgets\RecentQueueActivityWidget;
use App\Filament\Widgets\QueueStatsWidget;
use App\Models\Branch;
use App\Models\Queue;
use App\Models\ServiceSetting;
use App\Models\User;
use App\Services\BranchAdminRole;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_operational_pages_use_native_panel_view_and_keep_branch_information(): void
    {
        $this->assertSame('filament-panels::pages.page', app(BranchOperations::class)->getView());
        $this->assertSame('filament-panels::pages.page', app(AdminControlCenter::class)->getView());

        Livewire::test(BranchOperations::class)->assertSuccessful()
            ->assertSee('Loket layanan')->assertSee('Akun petugas')
            ->assertSee('teller.bogor@banktaspen.co.id')
            ->assertDontSee('bank-page', false);
        Livewire::test(QueueOperationsWidget::class)->assertSuccessful()
            ->assertSee('Akses Operasional')->assertSee('Daftar Antrean');
    }

    public function test_native_staff_table_filters_branch_and_hides_impersonation_for_inactive_account(): void
    {
        $bogor = Branch::where('slug', 'bogor')->firstOrFail();
        $own = User::where('email', 'teller.bogor@banktaspen.co.id')->firstOrFail();
        $foreign = User::where('email', 'teller.depok@banktaspen.co.id')->firstOrFail();
        $own->update(['is_active' => false]);

        Livewire::test(ControlCenterStaffWidget::class, ['selectedBranchId' => (string) $bogor->id])
            ->assertSuccessful()->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$foreign])
            ->assertTableActionHidden('staff_mode', $own);
    }

    public function test_native_recent_activity_table_preserves_branch_scope_and_status(): void
    {
        $tickets = [];
        foreach (['bogor', 'depok'] as $slug) {
            $branch = Branch::where('slug', $slug)->firstOrFail();
            $setting = ServiceSetting::where('branch_id', $branch->id)->where('service', 'teller')->firstOrFail();
            $tickets[$slug] = Queue::create([
                'branch_id' => $branch->id, 'service_setting_id' => $setting->id,
                'number' => 'T-001', 'status' => 'waiting',
            ]);
        }

        Livewire::test(RecentQueueActivityWidget::class, ['selectedBranchId' => (string) $tickets['bogor']->branch_id])
            ->assertSuccessful()->assertCanSeeTableRecords([$tickets['bogor']])
            ->assertCanNotSeeTableRecords([$tickets['depok']]);
    }

    public function test_branch_admin_can_log_in_without_bypassing_user_management_permissions(): void
    {
        $branch = Branch::where('slug', 'bogor')->firstOrFail();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->assignRole(BranchAdminRole::ensure());
        auth()->logout();
        app('auth')->forgetGuards();

        Livewire::test(\Filament\Auth\Pages\Login::class)
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
            \Spatie\Permission\Models\Role::findByName($name, 'web')->delete();
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            $this->get('/admin/operasional-cabang')->assertOk();
            $this->get('/admin/admin-control-center')->assertOk();
            Livewire::test(QueueStatsWidget::class)->assertSuccessful();
            Livewire::test(ControlCenterStaffWidget::class)->assertSuccessful();
            $this->assertSame($name === 'cs' ? $tellerCount : 0, User::withRoleNames(['cs', 'teller'])->count());
        }
    }
}
