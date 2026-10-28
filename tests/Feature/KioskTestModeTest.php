<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ServiceSetting;
use App\Services\KioskHours;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class KioskTestModeTest extends TestCase
{
    use RefreshDatabase;

    public static function hourCases(): array
    {
        return [
            'local enabled at night' => ['local', true, '2026-10-14 23:28', true],
            'testing enabled on weekend' => ['testing', true, '2026-10-17 23:28', true],
            'local disabled at night' => ['local', false, '2026-10-14 23:28', false],
            'testing disabled on weekend' => ['testing', false, '2026-10-17 09:00', false],
            'production enabled at night' => ['production', true, '2026-10-14 23:28', false],
            'production enabled on weekend' => ['production', true, '2026-10-17 09:00', false],
            'production enabled in normal hours' => ['production', true, '2026-10-14 09:00', true],
            'staging enabled at night' => ['staging', true, '2026-10-14 23:28', false],
        ];
    }

    #[DataProvider('hourCases')]
    public function test_test_mode_is_limited_to_local_and_testing(string $environment, bool $enabled, string $date, bool $expected): void
    {
        $original = $this->app->environment();
        $this->app['env'] = $environment;
        config(['kiosk.test_mode_enabled' => $enabled]);
        $this->travelTo(Carbon::parse($date, 'Asia/Jakarta'));
        try {
            $this->assertSame($expected, KioskHours::isOpen());
        } finally {
            $this->app['env'] = $original;
        }
    }

    private function setupOffice(bool $active = true): Branch
    {
        $branch = Branch::create([
            'code' => '101', 'slug' => 'test-office', 'name' => 'Test Office',
            'type' => 'Kantor Cabang', 'is_active' => $active,
        ]);
        foreach (['cs', 'teller'] as $service) {
            ServiceSetting::create([
                'branch_id' => $branch->id, 'service' => $service,
                'label' => $service, 'counter_name' => $service, 'is_active' => true,
            ]);
        }

        return $branch;
    }

    public static function openCases(): array
    {
        return [
            ['local', '2026-10-14 23:28'],
            ['local', '2026-10-17 23:28'],
            ['testing', '2026-10-14 23:28'],
            ['testing', '2026-10-17 23:28'],
        ];
    }

    #[DataProvider('openCases')]
    public function test_kiosk_buttons_and_cs_and_teller_requests_are_open_for_testing(string $environment, string $date): void
    {
        $branch = $this->setupOffice();
        $original = $this->app->environment();
        $this->app['env'] = $environment;
        config(['kiosk.test_mode_enabled' => true]);
        $this->travelTo(Carbon::parse($date, 'Asia/Jakarta'));
        // Local environments enforce CSRF, so exercise the normal token path.
        $this->withSession(['_token' => 'kiosk-test-token']);
        try {
            $this->get('/ambil-nomor/101')->assertOk()->assertSee('Ambil Tiket')
                ->assertDontSee('>Layanan Tutup</span>', false)->assertDontSee('Pengambilan tiket dibuka Senin - Jumat');
            foreach (['cs' => 'CS-001', 'teller' => 'T-001'] as $service => $number) {
                $this->issueTicket('/ambil-nomor/101', ['branch_id' => $branch->id, 'service' => $service, '_token' => 'kiosk-test-token'])
                    ->assertOk()->assertJsonPath('ticket', $number);
                $this->assertDatabaseHas('queues', ['number' => $number, 'branch_id' => $branch->id, 'status' => 'waiting']);
            }
            $this->assertDatabaseCount('queues', 2);
        } finally {
            $this->app['env'] = $original;
        }
    }

    public function test_disabled_mode_keeps_buttons_and_requests_closed_at_night(): void
    {
        $this->setupOffice();
        config(['kiosk.test_mode_enabled' => false]);
        $this->travelTo(Carbon::parse('2026-10-14 23:28', 'Asia/Jakarta'));
        $this->get('/ambil-nomor/101')->assertOk()->assertSee('Layanan Tutup');
        foreach (['cs', 'teller'] as $service) {
            $this->issueTicket('/ambil-nomor/101', ['service' => $service])->assertUnprocessable();
        }
        $this->assertDatabaseCount('queues', 0);
    }

    public function test_test_mode_keeps_closed_counter_disabled(): void
    {
        $branch = $this->setupOffice();
        ServiceSetting::where('branch_id', $branch->id)->where('service', 'cs')->firstOrFail()->update(['is_active' => false]);
        config(['kiosk.test_mode_enabled' => true]);
        $this->travelTo(Carbon::parse('2026-10-14 23:28', 'Asia/Jakarta'));
        $this->get('/ambil-nomor/101')->assertOk()->assertSee('Layanan Customer Service sedang tidak aktif saat ini.');
        $this->issueTicket('/ambil-nomor/101', ['service' => 'cs'])->assertUnprocessable();
        $this->issueTicket('/ambil-nomor/101', ['service' => 'teller'])->assertOk();
        $this->assertDatabaseCount('queues', 1);
    }

    public function test_test_mode_keeps_inactive_office_closed(): void
    {
        $this->setupOffice(false);
        config(['kiosk.test_mode_enabled' => true]);
        $this->travelTo(Carbon::parse('2026-10-14 23:28', 'Asia/Jakarta'));
        $this->get('/ambil-nomor/101')->assertNotFound();
        foreach (['cs', 'teller'] as $service) {
            $this->issueTicket('/ambil-nomor/101', ['service' => $service])->assertNotFound();
        }
        $this->assertDatabaseCount('queues', 0);
    }

    public function test_production_buttons_and_requests_stay_closed_with_flag_enabled(): void
    {
        $this->setupOffice();
        $original = $this->app->environment();
        $this->app['env'] = 'production';
        config(['kiosk.test_mode_enabled' => true]);
        $this->withSession(['_token' => 'kiosk-test-token']);
        try {
            foreach (['2026-10-14 23:28', '2026-10-17 09:00'] as $date) {
                $this->travelTo(Carbon::parse($date, 'Asia/Jakarta'));
                $this->get('/ambil-nomor/101')->assertOk()->assertSee('Layanan Tutup');
                foreach (['cs', 'teller'] as $service) {
                    $this->issueTicket('/ambil-nomor/101', ['service' => $service, '_token' => 'kiosk-test-token'])
                        ->assertUnprocessable()->assertJsonPath('success', false);
                }
            }
            $this->assertDatabaseCount('queues', 0);
        } finally {
            $this->app['env'] = $original;
        }
    }
}
