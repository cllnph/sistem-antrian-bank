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

class ServiceSettingScopeTest extends TestCase
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

    /** @test */
    #[Test]
    public function test_non_admin_user_can_only_see_queues_of_assigned_service_setting(): void
    {
        $settingA = ServiceSetting::create(['branch_id' => $this->branch->id, 'label' => 'Loket A', 'counter_name' => 'Loket A', 'service' => 'teller', 'is_active' => true]);
        $settingB = ServiceSetting::create(['branch_id' => $this->branch->id, 'label' => 'Loket B', 'counter_name' => 'Loket B', 'service' => 'cs', 'is_active' => true]);

        $queueA = Queue::create(['branch_id' => $this->branch->id, 'number' => 'T-001', 'service_setting_id' => $settingA->id, 'status' => 'waiting']);
        $queueB = Queue::create(['branch_id' => $this->branch->id, 'number' => 'CS-001', 'service_setting_id' => $settingB->id, 'status' => 'waiting']);

        $userA = User::factory()->create(['is_active' => true, 'branch_id' => $this->branch->id, 'service_setting_id' => $settingA->id]);
        $userA->assignRole('teller');

        $this->actingAs($userA);

        $visibleQueues = Queue::all();
        $this->assertTrue($visibleQueues->contains('id', $queueA->id));
        $this->assertFalse($visibleQueues->contains('id', $queueB->id));
    }

    /** @test */
    #[Test]
    public function test_admin_user_can_see_all_queues_from_all_service_settings(): void
    {
        $settingA = ServiceSetting::create(['branch_id' => $this->branch->id, 'label' => 'Loket A', 'counter_name' => 'Loket A', 'service' => 'teller', 'is_active' => true]);
        $settingB = ServiceSetting::create(['branch_id' => $this->branch->id, 'label' => 'Loket B', 'counter_name' => 'Loket B', 'service' => 'cs', 'is_active' => true]);

        $queueA = Queue::create(['branch_id' => $this->branch->id, 'number' => 'T-001', 'service_setting_id' => $settingA->id, 'status' => 'waiting']);
        $queueB = Queue::create(['branch_id' => $this->branch->id, 'number' => 'CS-001', 'service_setting_id' => $settingB->id, 'status' => 'waiting']);

        $admin = User::factory()->create(['is_active' => true, 'branch_id' => $this->branch->id]);
        $admin->assignRole('admin');

        $this->actingAs($admin);

        $visibleQueues = Queue::all();
        $this->assertTrue($visibleQueues->contains('id', $queueA->id));
        $this->assertTrue($visibleQueues->contains('id', $queueB->id));
    }

    /** @test */
    #[Test]
    public function test_display_controller_bypasses_service_setting_scope_even_when_logged_in_as_staff(): void
    {
        $settingTeller = ServiceSetting::create(['branch_id' => $this->branch->id, 'label' => 'Loket Teller', 'counter_name' => 'Loket Teller', 'service' => 'teller', 'is_active' => true]);
        $settingCs = ServiceSetting::create(['branch_id' => $this->branch->id, 'label' => 'Loket CS', 'counter_name' => 'Loket CS', 'service' => 'cs', 'is_active' => true]);

        $queueTeller = Queue::create(['branch_id' => $this->branch->id,
            'number' => 'T-001',
            'service_setting_id' => $settingTeller->id,
            'status' => 'called',
            'called_at' => now(),
        ]);
        $queueCs = Queue::create(['branch_id' => $this->branch->id,
            'number' => 'CS-001',
            'service_setting_id' => $settingCs->id,
            'status' => 'called',
            'called_at' => now(),
        ]);

        $teller = User::factory()->create(['is_active' => true, 'branch_id' => $this->branch->id, 'service_setting_id' => $settingTeller->id]);
        $teller->assignRole('teller');

        $response = $this->actingAs($teller)->get('/api/display/data');

        $response->assertOk();
        $this->assertEquals('T-001', $response->json('teller.number'));
        $this->assertEquals('CS-001', $response->json('cs.number'));
    }
}
