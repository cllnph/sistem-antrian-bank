"""Download the current native Filament installer from the isolated test server."""
from pathlib import Path
import hashlib
import json
import zipfile
from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'scratch/branch-agent-audit'
PRIVATE = OUT / 'private'
BASE = 'http://127.0.0.1:7102'
VERSION = (ROOT / 'agent/VERSION.txt').read_text().strip()
PRIVATE.mkdir(parents=True, exist_ok=True)

with sync_playwright() as p:
    browser = p.chromium.launch(channel='msedge', headless=True)
    context = browser.new_context(viewport={'width': 1536, 'height': 960})
    context.route('https://fonts.googleapis.com/**', lambda route: route.abort())
    context.route('https://fonts.gstatic.com/**', lambda route: route.abort())
    page = context.new_page()
    errors = []
    page.on('pageerror', lambda e: errors.append(str(e)))
    page.goto(BASE + '/admin/login', wait_until='domcontentloaded')
    page.wait_for_function('window.Livewire && Livewire.all().length > 0')
    page.locator('input[type=email]').fill('admin@example.com')
    page.locator('input[type=password]').fill('password')
    page.locator('button[type=submit]').click()
    page.wait_for_function('!location.pathname.endsWith("/login")')
    page.goto(BASE + '/admin/print-agents', wait_until='domcontentloaded')
    name = 'Audit installer Windows v' + VERSION
    row = page.get_by_role('row').filter(has_text=name)
    if row.count() == 0:
        page.get_by_role('button', name='Tambah Penghubung', exact=True).click()
        modal = page.locator('.fi-modal-window').last
        modal.wait_for(state='visible')
        modal.get_by_label('Kantor Cabang', exact=False).select_option(label='Kantor Cabang Bogor')
        modal.get_by_label('Nama Perangkat Penghubung', exact=False).fill(name)
        modal.get_by_label('Nama Printer', exact=False).fill('ITPRINTER (L6290 Series)')
        modal.get_by_role('button', name='Buat', exact=True).click()
        row.wait_for(state='visible')
    row.get_by_role('button', name='Unduh Installer', exact=True).click()
    modal = page.locator('.fi-modal-window').last
    modal.wait_for(state='visible')
    assert modal.get_by_label('Alamat Aplikasi Pusat', exact=False).input_value() == BASE
    with page.expect_download() as event:
        modal.get_by_role('button', name='Unduh Installer', exact=True).click()
    download = event.value
    assert download.suggested_filename.startswith('Penghubung-Cetak-002-v' + VERSION + '-')
    package = PRIVATE / ('windows-v' + VERSION + '.zip')
    download.save_as(package)
    expected = {f: ROOT / 'agent' / f for f in [
        'Install-Agent.cmd', 'Install-Agent.ps1', 'Start-Agent.ps1', 'Agent.Common.ps1', 'README.txt', 'VERSION.txt'
    ]}
    expected.update({f: ROOT / 'scripts' / f for f in ['Print-TicketPdf.ps1', 'Keep-PrinterDefault.ps1']})
    expected['CheckPrinter.cs'] = ROOT / 'scripts/native/CheckPrinter.cs'
    with zipfile.ZipFile(package) as z:
        assert set(z.namelist()) == set(expected) | {'setup.json'}
        for filename, source in expected.items():
            assert z.read(filename) == source.read_bytes(), filename + ' differs from current source'
        setup = json.loads(z.read('setup.json'))
        assert setup['installer_version'] == VERSION and setup['server_url'] == BASE
        assert setup['platform'] == 'windows' and setup['branch_code'] == '002'
        assert 'token' not in setup
        assert b'Set-Acl -' not in z.read('Install-Agent.ps1')
        assert b'AccessControlSections]::Access' in z.read('Agent.Common.ps1')
        z.extractall(PRIVATE / ('windows-v' + VERSION))
    context.storage_state(path=str(PRIVATE / 'windows-installer-session.json'))
    page.screenshot(path=str(OUT / 'windows-installer-v1.0.2.png'), full_page=True)
    assert not errors, errors
    result = {
        'installer_version': VERSION,
        'native_menu_download': 'passed',
        'download_filename': download.suggested_filename,
        'all_packaged_files_match_current_source': True,
        'zip_sha256': hashlib.sha256(package.read_bytes()).hexdigest(),
        'javascript_errors': errors,
        'physical_print': 'not requested',
    }
    (OUT / 'windows-installer-ui-results.json').write_text(json.dumps(result, indent=2), encoding='utf-8')
    browser.close()
print(json.dumps(result, indent=2))
