<?php

namespace Tests;

use App\Models\Branch;
use App\Models\PrintAgent;
use App\Services\KioskDeviceHistory;
use App\Services\Printing\BrowserPairing;
use App\Services\Printing\FakePrinterGateway;
use App\Services\Printing\PrinterGateway;
use App\Services\QueuePeriods;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected function printingRequest(): Request
    {
        $request = Request::create('/ambil-nomor', 'GET', [], $this->defaultCookies);
        $request->setLaravelSession(session()->driver());

        return $request;
    }

    /** Existing feature scenarios emulate a fresh kiosk page. Stale/missing contexts use raw postJson in period tests. */
    protected function issueTicket($uri, array $data = [], array $headers = [])
    {
        $this->withCredentials();
        if (! isset($this->defaultCookies[config('session.cookie')])) {
            $this->withCookie(config('session.cookie'), session()->getId());
        }
        $path = parse_url($uri, PHP_URL_PATH);
        $identifier = substr($path, strlen('/ambil-nomor/'));
        $branch = isset($data['branch_id']) ? Branch::find($data['branch_id']) : null;
        if (! $branch && $identifier && $path !== '/ambil-nomor') {
            $branch = Branch::where('code', $identifier)->orWhere('slug', $identifier)->first();
        }
        $branch ??= auth()->user()?->branch ?? Branch::where('code', '001')->first() ?? Branch::orderBy('code')->first();
        $data['request_id'] ??= (string) Str::uuid();
        if ($branch) {
            if (! isset($data['issuance_context']) && $branch->is_active) {
                $request = $this->printingRequest();
                $data['issuance_context'] = app(QueuePeriods::class)->context($request, $branch);
                $this->withCookie(KioskDeviceHistory::COOKIE, app(KioskDeviceHistory::class)->device($request)->id);
            }
        }

        return $this->postJson($uri, $data, $headers);
    }

    /** Explicitly opt a test browser into a managed transport; new browsers stay local. */
    protected function usePrintTransport(Branch $branch, string $transport, ?PrintAgent $agent = null): void
    {
        $device = app(KioskDeviceHistory::class)->device($this->printingRequest());
        DB::table('kiosk_print_bindings')->updateOrInsert(['kiosk_device_id' => $device->id, 'branch_id' => $branch->id], [
            'transport' => $transport, 'print_agent_id' => $agent?->id, 'created_at' => now(), 'updated_at' => now(),
            'verified_at' => now(), 'proof_device_id' => $agent?->device_id,
            'binding_source' => $transport === 'server' ? 'admin_server' : 'local_launcher',
        ]);
    }

    protected function pairPrintAgent(Request $request, PrintAgent $agent): void
    {
        $pairing = app(BrowserPairing::class);
        $proof = $pairing->issue($agent);
        $pairing->consume($request, parse_url($proof['connect_url'], PHP_URL_FRAGMENT));
    }

    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $app->make('config')->set('database.default', 'sqlite');
        $app->make('config')->set('database.connections.sqlite.database', ':memory:');
        $app->instance(PrinterGateway::class, new FakePrinterGateway);
        $app->make('config')->set('printing.require_worker', false);

        return $app;
    }

    protected function connectionsToTransact()
    {
        return ['sqlite'];
    }
}
