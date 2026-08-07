<?php

namespace Tests\Feature;

use App\Filament\Resources\QueueResource;
use App\Models\Queue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use PHPUnit\Framework\Attributes\Test;

class QueueSeparationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_teller_query_never_contains_cs_queue(): void
    {
        $teller = User::factory()->create(['role' => 'teller']);
        Queue::create(['number' => 'CS-20260730-001', 'service' => 'cs']);
        Queue::create(['number' => 'T-20260730-001', 'service' => 'teller']);

        $this->actingAs($teller);

        $this->assertSame(['T-20260730-001'], QueueResource::getEloquentQuery()->pluck('number')->all());
    }
}
