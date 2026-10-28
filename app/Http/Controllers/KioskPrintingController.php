<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Services\Printing\BrowserPairing;
use App\Services\Printing\KioskPrintRouting;
use Illuminate\Http\Request;

class KioskPrintingController extends Controller
{
    public function connect()
    {
        return response()->view('queue.kiosk-connect')->header('Cache-Control', 'no-store')
            ->header('Referrer-Policy', 'no-referrer')->header('Content-Security-Policy', "default-src 'self'; script-src 'unsafe-inline'; style-src 'unsafe-inline'; frame-ancestors 'none'; base-uri 'none'");
    }

    public function pair(Request $request, BrowserPairing $pairing)
    {
        $data = $request->validate(['token' => 'required|string|size:64|regex:/^[a-f0-9]+$/']);

        return response()->json(['redirect' => $pairing->consume($request, $data['token'])])->header('Cache-Control', 'no-store');
    }

    public function paper(Request $request, Branch $branch, KioskPrintRouting $routing)
    {
        abort_unless($branch->is_active, 404);
        $data = $request->validate(['paper_profile' => 'required|in:a4,thermal58,thermal80']);
        $routing->paper($request, $branch, $data['paper_profile']);

        return redirect()->route('queue.take-number', ['branch' => $branch->code]);
    }

    public function browser(Request $request, Branch $branch, KioskPrintRouting $routing)
    {
        abort_unless($branch->is_active, 404);
        $routing->browser($request, $branch);

        return redirect()->route('queue.take-number', ['branch' => $branch->code]);
    }
}
