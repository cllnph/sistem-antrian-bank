<?php

namespace Tests\Feature;

use App\Filament\Resources\QueueResource;
use App\Models\Queue;
use App\Models\ServiceSetting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QueueSeparationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    #[Test]
    public function test_teller_query_never_contains_cs_queue(): void
    {
        $csSetting = ServiceSetting::create(['label' => 'CS', 'service' => 'cs', 'counter_name' => 'Loket CS', 'is_active' => true]);
        $tellerSetting = ServiceSetting::create(['label' => 'Teller', 'service' => 'teller', 'counter_name' => 'Loket Teller', 'is_active' => true]);

        $teller = User::factory()->create(['service_setting_id' => $tellerSetting->id]);
        $teller->assignRole('teller');

        Queue::create(['number' => 'CS-001', 'service_setting_id' => $csSetting->id, 'status' => 'waiting']);
        Queue::create(['number' => 'T-001', 'service_setting_id' => $tellerSetting->id, 'status' => 'waiting']);

        $this->actingAs($teller);

        $this->assertSame(['T-001'], QueueResource::getEloquentQuery()->pluck('number')->all());
    }
}
