from pathlib import Path
import json
import os
import subprocess
import ctypes
from ctypes import wintypes
from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'scratch/silent-print-audit'
OUT.mkdir(exist_ok=True)
BASE = 'http://127.0.0.1:7102'
results = {'launcher': {}, 'browser': {}, 'physical_print': 'deferred by user until printer is available'}
kernel = ctypes.WinDLL('kernel32', use_last_error=True)
kernel.CreateMutexW.argtypes = [ctypes.c_void_p, wintypes.BOOL, wintypes.LPCWSTR]
kernel.CreateMutexW.restype = wintypes.HANDLE
kernel.ReleaseMutex.argtypes = [wintypes.HANDLE]
kernel.CloseHandle.argtypes = [wintypes.HANDLE]
for case in ['ready', 'preserve', 'missing', 'virtual', 'offline', 'error', 'running', 'invalid-url', 'busy']:
    mutex = kernel.CreateMutexW(None, True, 'Local\\BankDPTaspenKioskLauncher') if case == 'busy' else None
    try:
        run = subprocess.run(['powershell.exe', '-NoLogo', '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File',
                              str(ROOT / 'scratch/test_kiosk_launcher.ps1'), '-Case', case], capture_output=True, text=True, timeout=30)
    finally:
        if mutex:
            kernel.ReleaseMutex(mutex)
            kernel.CloseHandle(mutex)
    expected = 0 if case in ['ready', 'preserve'] else 1
    assert run.returncode == expected, (case, run.returncode, run.stdout, run.stderr)
    profile = OUT / 'launcher-tests' / case / 'BankDPTaspenKiosk/Default/Preferences'
    assert profile.exists() == (case in ['ready', 'preserve']), case
    if case == 'preserve':
        assert json.loads(profile.read_text(encoding='utf-8-sig'))['kiosk_audit_preserved_setting'] == 'keep'
    results['launcher'][case] = 'passed'
    print('PASS launcher', case, flush=True)

launch = json.loads((OUT / 'launcher-tests/launch.json').read_text(encoding='utf-8-sig'))
assert '--kiosk-printing' in launch['arguments']
assert '--app="http://127.0.0.1:7102/ambil-nomor"' in launch['arguments']
assert not any('remote-debugging' in arg for arg in launch['arguments'])
prefs = json.loads((OUT / 'launcher-tests/ready/BankDPTaspenKiosk/Default/Preferences').read_text())
state = json.loads(prefs['printing']['print_preview_sticky_settings']['appState'])
assert state['recentDestinations'][0]['id'] == 'ITPRINTER (L6290 Series)'
assert state['copies'] == 1 and state['isHeaderFooterEnabled'] is False
results['launcher']['printer_preferences'] = 'passed'
guard = json.loads((OUT / 'launcher-tests/guard-launch.json').read_text(encoding='utf-8-sig'))
assert guard['window_style'] == 'Hidden'
assert any('Keep-PrinterDefault.ps1' in arg for arg in guard['arguments'])
test_guard = subprocess.run(['powershell.exe', '-NoLogo', '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File',
                             str(ROOT / 'scratch/test_printer_default_guard.ps1')], capture_output=True, text=True, timeout=30)
assert test_guard.returncode == 0, (test_guard.stdout, test_guard.stderr)
print(test_guard.stdout.strip(), flush=True)
results['launcher']['preserve_windows_default'] = 'passed'

with sync_playwright() as p:
    browser = p.chromium.launch(executable_path=r'C:\Program Files\Google\Chrome\Application\chrome.exe', headless=True)
    context = browser.new_context(viewport={'width': 1366, 'height': 768})
    context.route('https://fonts.googleapis.com/**', lambda route: route.abort())
    context.route('https://fonts.gstatic.com/**', lambda route: route.abort())
    page = context.new_page()
    errors, calls = [], []
    page.on('pageerror', lambda error: errors.append(str(error)))
    page.on('dialog', lambda dialog: dialog.dismiss())
    page.goto(BASE + '/ambil-nomor', wait_until='domcontentloaded', timeout=60000)
    page.evaluate('() => { window.printCalls=0; window.print=()=>{window.printCalls++}; }')

    def issue(route):
        data = route.request.post_data_json
        calls.append(data)
        service = data['service']
        route.fulfill(json={'success': True, 'ticket': 'CS-901' if service == 'cs' else 'T-902',
                            'service_name': 'CUSTOMER SERVICE' if service == 'cs' else 'TELLER',
                            'branch_name': 'Kantor Pusat Operasional', 'branch_address': 'Alamat uji',
                            'branch_phone': '021-TEST', 'date_formatted': 'Kamis, 15 Oktober 2026'})

    page.route(BASE + '/ambil-nomor', issue)
    for count, service in enumerate(['cs', 'teller'], start=1):
        page.evaluate('''service => {
            const button=document.querySelector('.btn-take[data-service="'+service+'"]');
            button.click(); button.click();
        }''', service)
        page.wait_for_function('!isTakingTicket')
        assert len(calls) == count
        assert page.evaluate('window.printCalls') == count
        assert page.locator('#printReceiptNumber').text_content() == ('CS-901' if service == 'cs' else 'T-902')
        assert page.locator('#printReceiptServiceName').text_content() == ('CUSTOMER SERVICE' if service == 'cs' else 'TELLER')
        assert not page.locator('#ticketPrintArea').is_visible()
        assert page.locator('.thermal-bank-logo').evaluate('(e)=>e.complete && e.naturalWidth>0')
        results['browser'][service + '_single_print_and_double_click_guard'] = 'passed'
        print('PASS browser', service, flush=True)

    page.unroute(BASE + '/ambil-nomor', issue)
    rejected = []

    def reject(route):
        rejected.append(route.request.post_data_json)
        route.fulfill(status=422, json={'success': False, 'message': 'Audit: rejected'})

    page.route(BASE + '/ambil-nomor', reject)
    page.locator('.btn-take[data-service="cs"]').click()
    page.wait_for_function('!isTakingTicket')
    assert len(rejected) == 1 and page.evaluate('window.printCalls') == 2
    results['browser']['failed_issuance_does_not_print'] = 'passed'
    page.unroute(BASE + '/ambil-nomor', reject)
    page.route(BASE + '/ambil-nomor', issue)
    page.evaluate('() => {window.print=()=>{throw new Error("Audit print failure")};}')
    page.locator('.btn-take[data-service="teller"]').click()
    page.wait_for_function('!isTakingTicket')
    assert len(calls) == 3
    results['browser']['failed_print_does_not_reissue_ticket'] = 'passed'
    assert not errors, errors
    page.screenshot(path=str(OUT / 'kiosk-software-tests.png'))
    browser.close()
results['browser']['javascript_errors'] = errors
(OUT / 'software-results.json').write_text(json.dumps(results, indent=2))
print(json.dumps(results, indent=2), flush=True)
