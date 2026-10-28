<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\PrintAgent;
use App\Models\User;
use App\Services\Printing\AgentPackageBuilder;
use App\Services\Printing\NewPcInstaller;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NewPcInstallerTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_download_preserves_one_device_and_the_same_enrollment(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $branch = Branch::create(['code' => '901', 'slug' => 'setup', 'name' => 'Setup', 'type' => 'Kantor Cabang', 'is_active' => true]);
        $intent = (string) Str::uuid();
        $installer = app(NewPcInstaller::class);
        $path = $installer->build($branch, $admin, $intent, 'https://example.test/antrean');
        try {
            $agent = PrintAgent::firstOrFail();
            $hash = $agent->enrollment_hash;
            $zipHash = hash_file('sha256', $path);
            $this->assertSame($path, $installer->build($branch, $admin, $intent, 'https://example.test/antrean'));
            $this->assertDatabaseCount('print_agents', 1);
            $this->assertSame($hash, $agent->fresh()->enrollment_hash);
            $this->assertSame($zipHash, hash_file('sha256', $path));
            $this->assertTrue($agent->auto_name);
            $this->assertFalse($agent->enabled);
            $this->assertNull($agent->printer_name);
        } finally {
            @unlink($path);
        }
    }

    public function test_failed_package_does_not_leave_a_temporary_device(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $branch = Branch::create(['code' => '902', 'slug' => 'failure', 'name' => 'Failure', 'type' => 'Kantor Cabang', 'is_active' => true]);
        $this->mock(AgentPackageBuilder::class, fn ($mock) => $mock->shouldReceive('build')->once()->andThrow(new \RuntimeException('fixture package failure')));
        try {
            app(NewPcInstaller::class)->build($branch, $admin, (string) Str::uuid(), 'https://example.test');
            $this->fail('Failure must propagate.');
        } catch (\RuntimeException $error) {
            $this->assertSame('fixture package failure', $error->getMessage());
        }
        $this->assertDatabaseCount('print_agents', 0);
    }
}
