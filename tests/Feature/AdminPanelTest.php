<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use PHPUnit\Framework\Attributes\Test;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_teller_sees_queue_crud_inside_admin_panel(): void
    {
        $teller = User::factory()->create(['role' => 'teller']);

        $this->actingAs($teller)->get('/admin/queues')
            ->assertOk()
            ->assertSee('Antrean');
    }

    #[Test]
    public function test_only_admin_can_open_user_management(): void
    {
        $teller = User::factory()->create(['role' => 'teller']);

        $this->actingAs($teller)->get('/admin/users')->assertForbidden();
    }
}
