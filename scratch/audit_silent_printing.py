from pathlib import Path
import ctypes
from ctypes import wintypes
import hashlib
import json
import os
import sqlite3
import subprocess
import threading
import time
from urllib.request import urlopen

from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'scratch' / 'silent-print-audit'
OUT.mkdir(exist_ok=True)
HELPER = ROOT / 'scripts' / 'Start-Kiosk.ps1'
CONFIG = json.loads((ROOT / 'kiosk.config.json').read_text())
PRINTER = CONFIG['printer_name']
PROFILE = Path(os.environ['LOCALAPPDATA']) / 'BankDPTaspenKiosk'
PREFERENCES = PROFILE / 'Default' / 'Preferences'
BASE = 'http://127.0.0.1:7102'
DEBUG = 'http://127.0.0.1:9228'


def powershell(*args):
    return subprocess.run(['powershell.exe', '-NoLogo', '-NoProfile', '-ExecutionPolicy', 'Bypass', *map(str, args)],
                          capture_output=True, text=True, cwd=ROOT, timeout=45)


class SYSTEMTIME(ctypes.Structure):
    _fields_ = [(name, wintypes.WORD) for name in ['year', 'month', 'dayOfWeek', 'day', 'hour', 'minute', 'second', 'milliseconds']]


class JOB_INFO_1(ctypes.Structure):
    _fields_ = [('JobId', wintypes.DWORD)] + [(name, wintypes.LPWSTR) for name in
                ['pPrinterName', 'pMachineName', 'pUserName', 'pDocument', 'pDatatype', 'pStatus']] + [
                    (name, wintypes.DWORD) for name in ['Status', 'Priority', 'Position', 'TotalPages', 'PagesPrinted']
                ] + [('Submitted', SYSTEMTIME)]


spool = ctypes.WinDLL('winspool.drv', use_last_error=True)
spool.OpenPrinterW.argtypes = [wintypes.LPWSTR, ctypes.POINTER(wintypes.HANDLE), ctypes.c_void_p]
spool.OpenPrinterW.restype = wintypes.BOOL
spool.EnumJobsW.argtypes = [wintypes.HANDLE, wintypes.DWORD, wintypes.DWORD, wintypes.DWORD,
                           ctypes.c_void_p, wintypes.DWORD, ctypes.POINTER(wintypes.DWORD), ctypes.POINTER(wintypes.DWORD)]
spool.EnumJobsW.restype = wintypes.BOOL
spool.ClosePrinter.argtypes = [wintypes.HANDLE]


def list_jobs(handle):
    needed, count = wintypes.DWORD(), wintypes.DWORD()
    spool.EnumJobsW(handle, 0, 999, 1, None, 0, ctypes.byref(needed), ctypes.byref(count))
    if not needed.value:
        return []
    buffer = ctypes.create_string_buffer(needed.value)
    if not spool.EnumJobsW(handle, 0, 999, 1, buffer, needed.value, ctypes.byref(needed), ctypes.byref(count)):
        raise ctypes.WinError(ctypes.get_last_error())
    jobs = ctypes.cast(buffer, ctypes.POINTER(JOB_INFO_1))
    return [{'id': jobs[i].JobId, 'document': jobs[i].pDocument, 'status': jobs[i].Status,
             'pages': jobs[i].TotalPages, 'printed_pages': jobs[i].PagesPrinted} for i in range(count.value)]


def db_count():
    path = ROOT / 'scratch' / 'filament-native-ui.sqlite'
    with sqlite3.connect(path.as_uri() + '?mode=ro', uri=True) as db:
        return db.execute('select count(*) from queues').fetchone()[0]


results = {'printer': PRINTER, 'tickets': [], 'jobs': {}, 'checks': []}
default_before = powershell('-Command', '(Get-CimInstance Win32_Printer | Where-Object Default).Name').stdout.strip()
before_preferences = hashlib.sha256(PREFERENCES.read_bytes()).hexdigest()
for name, overrides in [('missing-printer', {'printer_name': 'NONEXISTENT KIOSK AUDIT PRINTER'}),
                        ('virtual-printer', {'printer_name': 'Microsoft Print to PDF'}),
                        ('invalid-url', {'url': 'javascript:alert(1)'})]:
    config = OUT / (name + '.json')
    config.write_text(json.dumps({**CONFIG, **overrides}))
    result = powershell('-File', HELPER, '-ConfigPath', config, '-PrepareOnly')
    assert result.returncode == 1, (name, result.stdout, result.stderr)
    assert hashlib.sha256(PREFERENCES.read_bytes()).hexdigest() == before_preferences
    results['checks'].append(name + ': rejected without changing profile')
    print('PASS', name, flush=True)

fixture_config = OUT / 'fixture.config.json'
fixture_config.write_text(json.dumps({**CONFIG, 'url': BASE + '/ambil-nomor'}))
handle = wintypes.HANDLE()
assert spool.OpenPrinterW(PRINTER, ctypes.byref(handle), None), ctypes.get_last_error()
initial_jobs = {job['id'] for job in list_jobs(handle)}
stop = threading.Event()


def monitor():
    while not stop.is_set():
        for job in list_jobs(handle):
            if job['id'] not in initial_jobs:
                results['jobs'][str(job['id'])] = job
        stop.wait(0.1)


thread = threading.Thread(target=monitor, daemon=True)
thread.start()
browser = None
count_before = db_count()
try:
    launch = powershell('-File', HELPER, '-ConfigPath', fixture_config, '-DebugPort', '9228')
    assert launch.returncode == 0, (launch.stdout, launch.stderr)
    print(launch.stdout.strip(), flush=True)
    for attempt in range(50):
        try:
            json.loads(urlopen(DEBUG + '/json/version', timeout=1).read())
            break
        except Exception:
            time.sleep(0.2)
    else:
        raise RuntimeError('Kiosk browser debugging endpoint did not start.')
    with sync_playwright() as p:
        browser = p.chromium.connect_over_cdp(DEBUG)
        context = browser.contexts[0]
        page = next(page for page in context.pages if page.url.startswith(BASE))
        page.wait_for_load_state('domcontentloaded')
        page.locator('.btn-take[data-service="cs"]').wait_for(timeout=60000)
        page.evaluate('''() => {
            window.auditPrintCalls = 0;
            const nativePrint = window.print.bind(window);
            window.print = () => { window.auditPrintCalls++; return nativePrint(); };
        }''')
        rejected = powershell('-File', HELPER, '-ConfigPath', fixture_config, '-PrepareOnly')
        assert rejected.returncode == 1 and 'masih terbuka' in rejected.stderr, rejected.stderr
        results['checks'].append('running profile: preparation rejected')
        requests = []
        page.on('response', lambda response: requests.append(response.json())
                if response.request.method == 'POST' and response.url == BASE + '/ambil-nomor' else None)

        # Exactly two real print jobs are requested: CS, then Teller.
        for service in ['cs', 'teller']:
            previous = len(requests)
            page.evaluate('''service => {
                const button = document.querySelector('.btn-take[data-service="' + service + '"]');
                button.click(); button.click();
            }''', service)
            page.wait_for_function('!isTakingTicket', timeout=120000)
            assert len(requests) == previous + 1, requests
            ticket = requests[-1]
            assert ticket['success'] and ticket['service'] == service, ticket
            results['tickets'].append({'service': service, 'id': ticket['id'], 'number': ticket['ticket']})
            assert page.locator('#printReceiptNumber').text_content() == ticket['ticket']
            assert not page.locator('#ticketPrintArea').is_visible()
            for attempt in range(100):
                if len(results['jobs']) >= len(results['tickets']):
                    break
                page.wait_for_timeout(100)
            assert len(results['jobs']) >= len(results['tickets']), ('No physical-printer spool job observed', results)
            assert not any(tab.url.startswith('chrome://print') for tab in context.pages)
            print('PASS silent print', service, ticket['ticket'], 'jobs:', len(results['jobs']), flush=True)

        assert page.evaluate('window.auditPrintCalls') == 2
        assert db_count() == count_before + 2
        page.on('dialog', lambda dialog: dialog.dismiss())
        mock_calls = []

        def reject_issue(route):
            mock_calls.append(route.request.post_data_json)
            route.fulfill(status=422, json={'success': False, 'message': 'Audit: issuance rejected'})

        page.route(BASE + '/ambil-nomor', reject_issue)
        page.locator('.btn-take[data-service="cs"]').click()
        page.wait_for_function('!isTakingTicket')
        assert page.evaluate('window.auditPrintCalls') == 2
        assert len(mock_calls) == 1
        assert db_count() == count_before + 2
        results['checks'].append('failed issuance: no print and no new ticket')
        page.unroute(BASE + '/ambil-nomor', reject_issue)

        def fake_issue(route):
            mock_calls.append(route.request.post_data_json)
            route.fulfill(json={'success': True, 'ticket': 'T-AUDIT-FAILED-PRINT', 'service_name': 'TELLER'})

        page.route(BASE + '/ambil-nomor', fake_issue)
        page.evaluate('() => {window.print = () => { throw new Error("Audit: printer unavailable"); };}')
        page.locator('.btn-take[data-service="teller"]').click()
        page.wait_for_function('!isTakingTicket')
        assert len(mock_calls) == 2
        assert db_count() == count_before + 2
        results['checks'].append('failed printing: no automatic replacement ticket')
        page.screenshot(path=str(OUT / 'kiosk-after-tests.png'))
        browser.new_browser_cdp_session().send('Browser.close')
        browser = None
finally:
    stop.set()
    thread.join(timeout=2)
    spool.ClosePrinter(handle)
    if browser:
        try:
            with sync_playwright() as cleanup:
                owned_browser = cleanup.chromium.connect_over_cdp(DEBUG, timeout=5000)
                owned_browser.new_browser_cdp_session().send('Browser.close')
        except Exception as error:
            results['cleanup_error'] = type(error).__name__
    results['windows_default_before'] = default_before
    results['windows_default_after'] = powershell('-Command', '(Get-CimInstance Win32_Printer | Where-Object Default).Name').stdout.strip()
    results['physical_paper_confirmation'] = 'pending user observation; spool jobs do not prove paper output'
    (OUT / 'results.json').write_text(json.dumps(results, indent=2), encoding='utf-8')
assert results['windows_default_before'] == results['windows_default_after']
assert len(results['jobs']) == 2, results
print(json.dumps(results, indent=2), flush=True)
