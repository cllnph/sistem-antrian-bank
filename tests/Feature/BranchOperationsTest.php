<?php

namespace Tests\Feature;

use App\Filament\Pages\AdminControlCenter;
use App\Filament\Pages\BranchOperations;
use App\Models\Branch;
use App\Models\ServiceSetting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class BranchOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(\Carbon\Carbon::parse('2026-10-14 09:00:00', 'Asia/Jakarta'));
        $this->seed(DatabaseSeeder::class);
    }

    public function test_setup_creates_all_branches_counters_and_operator_accounts(): void
    {
        $this->assertDatabaseCount('branches', 9);
        $this->assertDatabaseCount('service_settings', 18);
        foreach (Branch::all() as $branch) {
            foreach (['cs', 'teller'] as $service) {
                $user = User::where('email', $service.'.'.$branch->slug.'@banktaspen.co.id')->firstOrFail();
                $this->assertEquals($branch->id, $user->branch_id);
                $this->assertEquals($branch->id, $user->serviceSetting->branch_id);
                $this->assertTrue($user->hasRole($service));
            }
        }
    }

    public function test_repeated_setup_preserves_credentials_and_closed_counters(): void
    {
        $user = User::where('email', 'teller.bogor@banktaspen.co.id')->firstOrFail();
        $user->update(['password' => 'ChangedPass123!', 'is_active' => false]);
        $hash = $user->password;
        $user->serviceSetting->update(['is_active' => false]);
        $user->branch->update(['name' => 'Nama Cabang Disesuaikan', 'is_active' => false]);
        $count = User::count();
        $this->seed(DatabaseSeeder::class);
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertFalse($user->fresh()->is_active);
        $this->assertFalse($user->serviceSetting->fresh()->is_active);
        $this->assertFalse($user->branch->fresh()->is_active);
        $this->assertEquals('Nama Cabang Disesuaikan', $user->branch->fresh()->name);
        $this->assertEquals($count, User::count());
        $this->assertDatabaseCount('service_settings', 18);
    }

    public function test_admin_sees_all_branch_accounts_and_device_links(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin);
        Livewire::test(BranchOperations::class)->assertSuccessful()
            ->assertSee('teller.bogor@banktaspen.co.id')->assertSee('cs.depok@banktaspen.co.id')
            ->assertSee(url('/ambil-nomor/002'))->assertSee(url('/display/003'));
        $this->get('/admin')->assertRedirect(BranchOperations::getUrl());
    }

    public function test_control_center_uses_filament_table_filter_and_confirmation_actions(): void
    {
        $this->actingAs(User::where('email', 'admin@example.com')->firstOrFail());
        $this->get('/admin/admin-control-center')->assertOk()
            ->assertSee('fi-ta', false)->assertSee('Pantau cabang')->assertSee('Reset Teller');
        Livewire::test(AdminControlCenter::class)
            ->assertSuccessful()->assertSee('Kantor Cabang Bogor')->assertSee('Kantor Cabang Depok')
            ->set('selectedBranchId', (string) Branch::where('slug', 'bogor')->value('id'))
            ->assertSuccessful()->assertSee('Kantor Cabang Bogor')
            ->mountAction('reset_teller')->assertActionMounted('reset_teller');
    }

    public function test_legacy_officer_consoles_redirect_to_filament(): void
    {
        $this->actingAs(User::where('email', 'admin@example.com')->firstOrFail());
        foreach (['csIndex', 'tellerIndex', 'umumIndex'] as $method) {
            $response = app(\App\Http\Controllers\QueueController::class)->{$method}();
            $this->assertEquals(url('/admin/queues'), $response->getTargetUrl());
        }
    }

    public function test_staff_only_sees_own_branch_accounts(): void
    {
        $user = User::where('email', 'teller.bogor@banktaspen.co.id')->firstOrFail();
        $this->actingAs($user);
        Livewire::test(BranchOperations::class)->assertSuccessful()
            ->assertSee('teller.bogor@banktaspen.co.id')->assertSee('cs.bogor@banktaspen.co.id')
            ->assertDontSee('teller.depok@banktaspen.co.id')->assertDontSee('Kelola Akun');
    }

    public function test_kiosk_tickets_and_display_remain_separated_by_branch(): void
    {
        $bogor = Branch::where('slug', 'bogor')->firstOrFail();
        $depok = Branch::where('slug', 'depok')->firstOrFail();
        $first = $this->postJson('/ambil-nomor/bogor', ['service' => 'teller'])->assertOk();
        $second = $this->postJson('/ambil-nomor/depok', ['service' => 'teller'])->assertOk();
        $this->assertEquals('T-001', $first->json('ticket'));
        $this->assertEquals('T-001', $second->json('ticket'));
        $this->assertNotEquals($first->json('id'), $second->json('id'));
        $this->assertDatabaseHas('queues', ['id' => $first->json('id'), 'branch_id' => $bogor->id]);
        $this->assertDatabaseHas('queues', ['id' => $second->json('id'), 'branch_id' => $depok->id]);
        $this->actingAs(User::where('email', 'teller.bogor@banktaspen.co.id')->firstOrFail())
            ->post('/queue/next/teller')->assertRedirect();
        $this->assertDatabaseHas('queues', ['id' => $first->json('id'), 'status' => 'called']);
        $this->assertDatabaseHas('queues', ['id' => $second->json('id'), 'status' => 'waiting']);
        $this->getJson('/api/display/data/bogor')->assertOk()->assertJsonPath('teller.number', 'T-001');
        $this->getJson('/api/display/data/depok')->assertOk()->assertJsonPath('teller', null);
    }

    public function test_invalid_or_inactive_branch_does_not_fall_back_to_head_office(): void
    {
        $this->get('/ambil-nomor/unknown')->assertNotFound();
        Branch::where('slug', 'bogor')->update(['is_active' => false]);
        $this->get('/display/bogor')->assertNotFound();
        $this->postJson('/ambil-nomor/bogor', ['service' => 'teller'])->assertNotFound();
        $this->assertDatabaseCount('queues', 0);
    }

    public function test_account_cannot_be_assigned_to_another_branch_counter(): void
    {
        $user = User::where('email', 'teller.bogor@banktaspen.co.id')->firstOrFail();
        $foreign = ServiceSetting::where('branch_id', Branch::where('slug', 'depok')->value('id'))->firstOrFail();
        $this->expectException(ValidationException::class);
        $user->update(['service_setting_id' => $foreign->id]);
    }

    public function test_staff_mode_banner_allows_returning_to_admin_with_default_guard(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $staff = User::where('email', 'teller.bogor@banktaspen.co.id')->firstOrFail();
        $this->actingAs($staff)->withSession([
            'impersonated_by' => $admin->id,
            'impersonator_guard' => 'web',
            'impersonator_guard_using' => null,
        ])->get('/admin/operasional-cabang')->assertOk()
            ->assertSee('Kembali ke admin')->assertSee('Mode petugas');
        $this->get('/filament-impersonate/leave')->assertRedirect();
        $this->assertAuthenticatedAs($admin);
    }
}
