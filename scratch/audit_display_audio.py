"""Run with python scratch/audit_display_audio.py (requires Playwright and Edge).

Render the real Blade view without saving models, then intercept display polling.
Speech is recorded instead of sent to speakers; the application database is untouched.
"""

import json
import subprocess
from pathlib import Path

from playwright.sync_api import sync_playwright


ROOT = Path(__file__).resolve().parents[1]
PHP_FIXTURES = r"""<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$branch = new App\Models\Branch(['code' => '001', 'name' => 'Uji Audio', 'is_active' => true]);
$empty = ['id' => 1, 'counter_name' => 'Loket Teller', 'current_number' => 'belum ada antrean', 'called_at' => null, 'waiting_count' => 1, 'next_waiting_number' => 'T-001'];
$called = array_merge($empty, ['current_number' => 'T-001', 'called_at' => '2026-10-27T09:00:00+07:00']);
$result = [];
foreach (['empty' => [$empty], 'existing' => [$called]] as $key => $counters) {
    $result[$key] = view('display', ['branch' => $branch, 'initialData' => ['counters' => $counters]])->render();
}
echo json_encode($result, JSON_THROW_ON_ERROR);
"""

MOCK_AUDIO = """
window.__spoken = [];
window.SpeechSynthesisUtterance = class { constructor(text) { this.text = text; } };
Object.defineProperty(window, 'speechSynthesis', { value: {
    getVoices: () => [{ name: 'Microsoft Gadis', lang: 'id-ID' }],
    addEventListener() {}, cancel() {},
    speak(u) { window.__spoken.push(u.text); queueMicrotask(() => u.onend?.()); }
} });
window.AudioContext = class {
    state = 'running'; currentTime = 0; destination = {};
    resume() { return Promise.resolve(); }
    createOscillator() { return { frequency: { setValueAtTime() {} }, connect() {}, start() {}, stop() {} }; }
    createGain() { return { gain: { setValueAtTime() {}, exponentialRampToValueAtTime() {} }, connect() {} }; }
};
"""


def main():
    rendered = subprocess.run(
        ['php'], input=PHP_FIXTURES, text=True, capture_output=True,
        check=True, timeout=120, cwd=ROOT,
    )
    fixtures = json.loads(rendered.stdout)
    empty = {
        'id': 1, 'counter_name': 'Loket Teller',
        'current_number': 'belum ada antrean', 'called_at': None,
        'waiting_count': 1, 'next_waiting_number': 'T-001',
    }
    called = {**empty, 'current_number': 'T-001', 'called_at': '2026-10-27T09:00:00+07:00'}
    recall = {**called, 'called_at': '2026-10-27T09:00:10+07:00'}
    next_ticket = {**called, 'current_number': 'T-002'}
    normal = 'Nomor antrean T 001, silakan menuju Loket Teller.'
    repeated = 'Panggilan ulang. Nomor antrean T 001, silakan menuju Loket Teller.'
    second = 'Nomor antrean T 002, silakan menuju Loket Teller.'
    results = []

    with sync_playwright() as playwright:
        browser = playwright.chromium.launch(channel='msedge', headless=True)

        def scenario(label, fixture, first_snapshot, steps):
            context = browser.new_context()
            page = context.new_page()
            snapshot = {'counters': first_snapshot}
            errors = []
            page.on('pageerror', lambda error: errors.append(str(error)))

            def intercept(route):
                if '/api/display/data/' in route.request.url:
                    route.fulfill(json=snapshot)
                elif route.request.url == 'http://display-audio.test/display':
                    route.fulfill(content_type='text/html', body=fixtures[fixture])
                else:
                    route.abort()

            page.route('**/*', intercept)
            page.add_init_script(MOCK_AUDIO)
            page.clock.install()
            page.goto('http://display-audio.test/display', wait_until='domcontentloaded')
            page.wait_for_function('isFetching === false')
            for counters, expected in steps:
                if counters is not None:
                    snapshot['counters'] = counters
                    page.wait_for_function('isFetching === false')
                    page.evaluate('refreshData()')
                # Allow both chimes and the gap between two simultaneous calls.
                page.clock.run_for(4200)
                actual = page.evaluate('window.__spoken')
                assert actual == expected, f'{label}: expected {expected!r}; got {actual!r}'
            assert not errors, f'{label}: browser errors {errors}'
            results.append(label)
            context.close()

        scenario('first call, duplicate polling, recall', 'empty', [empty], [
            (None, []), ([called], [normal]), ([called], [normal]), ([recall], [normal, repeated]),
        ])
        scenario('call before first poll completes', 'empty', [called], [(None, [normal])])
        scenario('existing ticket stays silent on load', 'existing', [called], [
            (None, []), ([recall], [repeated]),
        ])
        scenario('next number with same timestamp', 'existing', [called], [
            (None, []), ([next_ticket], [second]),
        ])
        scenario('idle state resets recall detection', 'existing', [called], [
            (None, []), ([empty], []), ([recall], [normal]),
        ])
        scenario('simultaneous calls stay sequential', 'empty', [empty], [
            (None, []),
            ([called, {**called, 'id': 2, 'current_number': 'CS-001', 'counter_name': 'Loket CS'}],
             [normal, 'Nomor antrean CS 001, silakan menuju Loket CS.']),
        ])
        browser.close()

    print(json.dumps({'result': 'passed', 'browser': 'Microsoft Edge', 'scenarios': results}))


if __name__ == '__main__':
    main()
