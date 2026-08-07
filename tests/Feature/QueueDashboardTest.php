<?php

namespace Tests\Feature;

use App\Models\Queue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use PHPUnit\Framework\Attributes\Test;

class QueueDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_teller_can_call_the_next_teller_queue(): void
    {
        $teller = User::factory()->create(['role' => 'teller']);
        $queue = Queue::create(['number' => 'T-001', 'service' => 'teller']);

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
        $cs = User::factory()->create(['role' => 'cs']);

        $this->actingAs($cs)->get(route('teller.dashboard'))->assertForbidden();
    }

    #[Test]
    public function test_visitor_can_take_a_teller_number(): void
    {
        $this->post(route('queue.store-number'), ['service' => 'teller'])
            ->assertSessionHas('ticket', 'T-001');

        $this->assertDatabaseHas('queues', [
            'number' => 'T-001',
            'service' => 'teller',
            'status' => 'waiting',
        ]);
    }

    #[Test]
    public function test_queue_sequence_above_999_increments_correctly(): void
    {
        Queue::create(['number' => "T-999", 'service' => 'teller']);

        $this->post(route('queue.store-number'), ['service' => 'teller'])
            ->assertSessionHas('ticket', "T-1000");

        $this->assertDatabaseHas('queues', [
            'number' => "T-1000",
            'service' => 'teller',
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
