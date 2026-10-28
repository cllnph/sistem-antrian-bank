<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Queue;
use App\Models\ServiceSetting;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QueueDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->travelTo(Carbon::parse('2026-10-14 09:00:00', 'Asia/Jakarta'));
        $this->branch = Branch::create([
            'code' => '001', 'slug' => 'pusat', 'name' => 'Kantor Pusat',
            'type' => 'Kantor Pusat', 'is_active' => true,
        ]);
    }

    #[Test]
    public function test_teller_can_call_the_next_teller_queue(): void
    {
        $tellerSetting = ServiceSetting::create(['branch_id' => $this->branch->id, 'label' => 'Loket Teller', 'counter_name' => 'Loket Teller', 'service' => 'teller', 'is_active' => true]);
        $teller = User::factory()->create(['is_active' => true, 'branch_id' => $this->branch->id, 'service_setting_id' => $tellerSetting->id]);
        $teller->assignRole('teller');

        $queue = Queue::create(['branch_id' => $this->branch->id, 'number' => 'T-001', 'service_setting_id' => $tellerSetting->id, 'status' => 'waiting']);

        $this->actingAs($teller)->post(route('queue.next.teller'))->assertRedirect();

        $this->assertDatabaseHas('queues', [
            'id' => $queue->id,
            'status' => 'called',
            'served_by' => $teller->id,
        ]);
    }

    #[Test]
    public function test_cs_cannot_open_teller_dashboard(): void
    {
        $cs = User::factory()->create(['is_active' => true, 'branch_id' => $this->branch->id]);
        $cs->assignRole('cs');

        $this->actingAs($cs)->get(route('teller.dashboard'))->assertForbidden();
    }

    #[Test]
    public function test_visitor_can_take_a_teller_number(): void
    {
        $tellerSetting = ServiceSetting::create(['branch_id' => $this->branch->id, 'label' => 'Loket Teller', 'counter_name' => 'Loket Teller', 'service' => 'teller', 'is_active' => true]);

        $this->issueTicket(route('queue.store-number'), ['service' => 'teller'])
            ->assertJsonPath('ticket', 'T-001');

        $this->assertDatabaseHas('queues', [
            'number' => 'T-001',
            'service_setting_id' => $tellerSetting->id,
            'status' => 'waiting',
        ]);
    }

    #[Test]
    public function test_queue_sequence_above_999_increments_correctly(): void
    {
        $tellerSetting = ServiceSetting::create(['branch_id' => $this->branch->id, 'label' => 'Loket Teller', 'counter_name' => 'Loket Teller', 'service' => 'teller', 'is_active' => true]);
        Queue::create(['branch_id' => $this->branch->id, 'number' => 'T-999', 'service_setting_id' => $tellerSetting->id, 'status' => 'waiting']);

        $this->issueTicket(route('queue.store-number'), ['service' => 'teller'])
            ->assertJsonPath('ticket', 'T-1000');

        $this->assertDatabaseHas('queues', [
            'number' => 'T-1000',
            'service_setting_id' => $tellerSetting->id,
        ]);
    }

    #[Test]
    public function test_display_api_data_returns_json_structure(): void
    {
        $response = $this->get('/api/display/data');

        $response->assertOk()
            ->assertJsonStructure(['cs', 'teller', 'waiting', 'settings']);
    }
}
