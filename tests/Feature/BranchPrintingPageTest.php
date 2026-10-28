<?php

namespace Tests\Feature;

use App\Filament\Pages\BranchDetail;
use App\Filament\Pages\BranchOperations;
use App\Filament\Resources\PrintAgentResource;
use App\Filament\Resources\PrintAgentResource\Pages\ManagePrintAgents;
use App\Models\Branch;
use App\Models\PrintAgent;
use App\Models\PrintJob;
use App\Models\Queue;
use App\Models\ServiceSetting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Filament\Actions\Exceptions\ActionNotResolvableException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class BranchPrintingPageTest extends TestCase
{
    use RefreshDatabase;

    private Branch $own;

    private Branch $other;

    private PrintAgent $agent;

    private PrintAgent $foreign;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');
        $this->actingAs($admin);
        $this->own = Branch::create(['code' => '151', 'slug' => 'print-own', 'name' => 'Kantor Bogor', 'type' => 'Kantor Cabang', 'is_active' => true]);
        $this->other = Branch::create(['code' => '152', 'slug' => 'print-other', 'name' => 'Kantor Depok', 'type' => 'Kantor Cabang', 'is_active' => true]);
        $this->agent = $this->makeAgent($this->own, 'PC-BOGOR');
        $this->foreign = $this->makeAgent($this->other, 'PC-DEPOK');
        $this->withSession(['_token' => 'branch-print-ui']);
    }

    private function makeAgent(Branch $branch, string $name): PrintAgent
    {
        return PrintAgent::create(['id' => (string) Str::uuid(), 'branch_id' => $branch->id, 'name' => $name,
            'device_id' => (string) Str::uuid(), 'printer_name' => 'EPSON L3250 Series', 'paper_profile' => 'a4',
            'installed_printers' => ['EPSON L3250 Series', 'Panda USB'], 'enabled' => true, 'revoked' => false,
            'printer_ready' => true, 'heartbeat_at' => now()]);
    }

    public function test_printing_is_opened_from_each_branch_and_legacy_list_redirects(): void
    {
        $this->assertFalse(PrintAgentResource::shouldRegisterNavigation());
        Livewire::test(BranchOperations::class)->assertSuccessful()
            ->assertSee(BranchDetail::getUrl(['branch' => $this->own->id]))
            ->assertSee(BranchDetail::getUrl(['branch' => $this->other->id]));
        $this->get('/admin/print-agents')->assertRedirect(BranchOperations::getUrl());
        $this->get(PrintAgentResource::forBranchUrl($this->own))->assertOk()->assertSee('Cetak &amp; Printer', false);
        $this->get('/admin/print-agents/999999/cetak')->assertNotFound();
    }

    public function test_branch_page_filters_records_and_rejects_foreign_record_actions(): void
    {
        Livewire::test(ManagePrintAgents::class, ['branch' => $this->own->id])
            ->assertCanSeeTableRecords([$this->agent])->assertCanNotSeeTableRecords([$this->foreign]);
        try {
            Livewire::test(ManagePrintAgents::class, ['branch' => $this->own->id])->callTableAction('disable', $this->foreign);
            $this->fail('A foreign record action should be rejected.');
        } catch (ActionNotResolvableException $error) {
            $this->assertStringContainsString('no longer exists', $error->getMessage());
        }
        $this->assertTrue($this->foreign->fresh()->enabled);
        $this->assertTrue($this->agent->fresh()->enabled);
        Livewire::test(ManagePrintAgents::class, ['branch' => $this->other->id])
            ->assertCanSeeTableRecords([$this->foreign])->assertCanNotSeeTableRecords([$this->agent]);
    }

    public function test_branch_context_cannot_be_changed_through_livewire(): void
    {
        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(ManagePrintAgents::class, ['branch' => $this->own->id])->set('branchId', $this->other->id);
    }

    public function test_creation_ignores_a_forged_branch_and_edit_cannot_move_devices(): void
    {
        Livewire::test(ManagePrintAgents::class, ['branch' => $this->own->id])
            ->callTableAction('edit', $this->agent, data: ['branch_id' => $this->other->id, 'name' => 'PC Bogor diganti label', 'printer_name' => $this->agent->printer_name])
            ->assertHasNoActionErrors();
        $this->assertSame($this->own->id, $this->agent->fresh()->branch_id);
    }

    public function test_local_paper_settings_only_change_the_current_branch_binding(): void
    {
        foreach ([[$this->own, 'thermal58'], [$this->other, 'thermal80']] as [$branch, $paper]) {
            Livewire::test(ManagePrintAgents::class, ['branch' => $branch->id])
                ->callAction('localPrinting', data: ['paper_profile' => $paper])->assertHasNoActionErrors();
        }
        foreach ([[$this->own, 'thermal58'], [$this->other, 'thermal80']] as [$branch, $paper]) {
            $this->assertDatabaseHas('kiosk_print_bindings', ['branch_id' => $branch->id, 'transport' => 'browser', 'paper_profile' => $paper]);
        }
        $this->assertSame('EPSON L3250 Series', $this->agent->fresh()->printer_name);
        $this->assertSame('a4', $this->foreign->fresh()->paper_profile);
    }

    public function test_changing_printer_requires_inventory_and_confirmation_and_blocks_during_processing(): void
    {
        $setting = ServiceSetting::create(['branch_id' => $this->own->id, 'service' => 'cs', 'label' => 'CS', 'counter_name' => 'CS', 'is_active' => true]);
        $ticket = Queue::create(['branch_id' => $this->own->id, 'service_setting_id' => $setting->id, 'number' => 'CS-001']);
        $job = PrintJob::create(['id' => (string) Str::uuid(), 'queue_id' => $ticket->id, 'print_agent_id' => $this->agent->id,
            'owner_hash' => 'test', 'owner_type' => 'staff', 'transport' => 'agent', 'printer_name' => $this->agent->printer_name,
            'status' => 'processing', 'lease_expires_at' => now()->addMinute(), 'snapshot' => []]);
        $choice = ['printer_name' => 'Panda USB', 'paper_profile' => 'thermal58'];
        Livewire::test(ManagePrintAgents::class, ['branch' => $this->own->id])
            ->callTableAction('changePrinter', $this->agent, data: $choice)->assertHasActionErrors(['printer_name']);
        $this->assertSame('a4', $this->agent->fresh()->paper_profile);
        $job->update(['status' => 'queued']);
        Livewire::test(ManagePrintAgents::class, ['branch' => $this->own->id])
            ->callTableAction('changePrinter', $this->agent, data: $choice)->assertHasNoActionErrors();
        $this->assertSame('expired', $job->fresh()->status);
        $this->assertSame('admin_printer_changed', $job->fresh()->failure_code);
        $this->assertSame('Panda USB', $this->agent->fresh()->printer_name);
        $this->assertFalse($this->agent->fresh()->printer_ready);
        $this->assertTrue($this->foreign->fresh()->printer_ready);
        Livewire::test(ManagePrintAgents::class, ['branch' => $this->own->id])
            ->callTableAction('changePrinter', $this->agent, data: ['printer_name' => 'Unknown printer', 'paper_profile' => 'a4'])
            ->assertHasActionErrors(['printer_name']);
        $this->assertSame('Panda USB', $this->agent->fresh()->printer_name);
        $token = str_repeat('d', 64);
        $this->agent->update(['token_hash' => hash('sha256', $token)]);
        $headers = ['Authorization' => 'Bearer '.$token, 'X-Print-Agent' => $this->agent->id, 'X-Print-Device' => $this->agent->device_id];
        $heartbeat = ['printer_ready' => true, 'printer_name' => 'EPSON L3250 Series', 'paper_profile' => 'a4',
            'machine_name' => 'PC-BOGOR', 'version' => '1.3.0', 'inventory_status' => 'ok', 'installed_printers' => $this->agent->installed_printers];
        $this->postJson('/api/print-agent/heartbeat', $heartbeat, $headers)->assertOk()
            ->assertJsonPath('printer_name', 'Panda USB')->assertJsonPath('paper_profile', 'thermal58');
        $this->assertFalse($this->agent->fresh()->printer_ready);
        $this->postJson('/api/print-agent/heartbeat', [...$heartbeat, ...$choice], $headers)->assertOk();
        $this->assertTrue($this->agent->fresh()->printer_ready);
    }

    public function test_print_configuration_is_not_exposed_to_guests_or_operators(): void
    {
        $operator = User::factory()->create(['branch_id' => $this->own->id, 'is_active' => true]);
        $operator->assignRole('cs');
        $this->actingAs($operator)->get(PrintAgentResource::forBranchUrl($this->own))->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->get(PrintAgentResource::forBranchUrl($this->own))->assertRedirect();
        $this->assertSame(0, DB::table('kiosk_print_bindings')->count());
    }
}
