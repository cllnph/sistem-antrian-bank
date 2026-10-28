<?php

namespace Tests\Feature;

use App\Models\Branch;
use Carbon\Carbon;
use Database\Seeders\BranchContactSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReleaseAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-14 09:00:00', 'Asia/Jakarta'));
    }

    public function test_invalid_ticket_request_returns_json_validation_errors(): void
    {
        $this->issueTicket('/ambil-nomor', ['branch_id' => 999999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['service', 'branch_id']);
        $this->assertDatabaseCount('queues', 0);
    }

    public function test_inactive_branch_cannot_issue_ticket_and_returns_json(): void
    {
        $branch = Branch::create([
            'code' => '002', 'slug' => 'bogor', 'name' => 'Bogor',
            'type' => 'Kantor Cabang', 'is_active' => false,
        ]);

        $this->issueTicket('/ambil-nomor', ['branch_id' => $branch->id, 'service' => 'teller'])
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json');
        $this->assertDatabaseCount('queues', 0);
    }

    public function test_contact_update_preserves_office_identity_and_operational_settings(): void
    {
        $branch = Branch::create([
            'code' => '002', 'slug' => 'bogor', 'name' => 'Bogor Custom',
            'type' => 'Kantor Cabang', 'address' => 'Old address', 'phone' => '12345',
            'is_active' => false, 'operating_hours' => 'Custom hours',
        ]);

        $this->seed(BranchContactSeeder::class);
        $branch->refresh();

        $this->assertSame(BranchContactSeeder::CONTACTS['bogor']['address'], $branch->address);
        $this->assertNull($branch->phone);
        $this->assertSame('Bogor Custom', $branch->name);
        $this->assertSame('Custom hours', $branch->operating_hours);
        $this->assertFalse($branch->is_active);
        $this->assertDatabaseCount('branches', 1);
    }
}
