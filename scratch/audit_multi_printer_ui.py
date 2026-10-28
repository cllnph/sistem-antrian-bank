from pathlib import Path
from datetime import datetime
import json
import re
import sqlite3
import zipfile
from playwright.sync_api import sync_playwright, expect

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'scratch/multi-printer-audit'
BASE = 'http://127.0.0.1:7110'
PRIVATE = OUT / 'private'
fixture = json.loads((PRIVATE / 'fixture.json').read_text())
results = {}

def database():
    connection = sqlite3.connect(PRIVATE / 'app.sqlite')
    connection.execute('PRAGMA foreign_keys=ON')
    return connection

with database() as db:
    db.execute("update print_agents set deleted_at=? where name='Printer Tambahan UAT' and deleted_at is null", (datetime.now().strftime('%Y-%m-%d %H:%M:%S'),))
    db.execute("update print_agents set name='Epson Salah Cabang', branch_id=(select id from branches where code='002') where id=?", (fixture['misplaced_agent_id'],))
    db.execute('delete from kiosk_print_bindings')
    before_queues = db.execute('select count(*) from queues').fetchone()[0]
    before_browser_jobs = db.execute("select count(*) from print_jobs where transport='browser'").fetchone()[0]

with sync_playwright() as p:
    browser = p.chromium.launch(channel='msedge', headless=True)
    admin = browser.new_context(storage_state=str(PRIVATE / 'admin-state.json'), viewport={'width': 1440, 'height': 1000})
    admin.route('https://fonts.googleapis.com/**', lambda route: route.abort())
    ui = admin.new_page()
    errors = []
    ui.on('pageerror', lambda error: errors.append(str(error)))
    ui.goto(BASE + '/admin/print-agents', wait_until='domcontentloaded')

    def manage(name, action):
        ui.get_by_role('row').filter(has_text=name).get_by_role('button', name='Kelola').click()
        ui.locator('[role=menu]:visible').get_by_role('menuitem', name=action, exact=True).click()
        return ui.locator('.fi-modal-window:visible').last

    def choose_office(modal):
        modal.get_by_role('combobox').click()
        ui.get_by_role('option', name='Kantor Pusat', exact=True).click()

    modal = manage('Epson Salah Cabang', 'Ubah')
    choose_office(modal)
    modal.get_by_label('Nama Perangkat Penghubung', exact=False).fill('PC Pusat L3250')
    modal.get_by_role('button', name='Simpan', exact=True).click()
    expect(ui.get_by_role('row').filter(has_text='PC Pusat L3250')).to_contain_text('Kantor Pusat')
    results['reassign_existing_l3250_to_occupied_head_office'] = 'passed'

    ui.get_by_role('button', name='Tambah Penghubung', exact=True).click()
    modal = ui.locator('.fi-modal-window:visible').last
    choose_office(modal)
    modal.get_by_label('Nama Perangkat Penghubung', exact=False).fill('Printer Tambahan UAT')
    # Printer is intentionally left empty until the installer selects it locally.
    modal.get_by_role('button', name='Buat', exact=True).click()
    expect(ui.get_by_role('row').filter(has_text='Printer Tambahan UAT')).to_be_visible()
    results['create_multiple_printers_with_optional_printer_name'] = 'passed'

    modal = manage('PC Pusat L3250', 'Detail')
    expect(modal.get_by_label('Nama Printer', exact=False)).to_have_value('EPSON L3250 Series')
    modal.get_by_role('button', name='Tutup', exact=True).last.click()
    modal = manage('Printer Tambahan UAT', 'Hapus')
    modal.get_by_role('button', name='Hapus', exact=True).click()
    expect(ui.get_by_role('row').filter(has_text='Printer Tambahan UAT')).to_have_count(0)
    results['native_detail_and_delete'] = 'passed'

    modal = manage('PC Pusat L3250', 'Unduh Installer')
    expect(modal.get_by_label('Alamat Aplikasi Pusat', exact=False)).to_have_value(BASE)
    with ui.expect_download() as download_event:
        modal.get_by_role('button', name='Unduh Installer', exact=True).click()
    download = download_event.value
    package = PRIVATE / 'downloaded-installer.zip'
    download.save_as(package)
    with zipfile.ZipFile(package) as archive:
        assert 'SumatraPDF.exe' in archive.namelist()
        assert archive.read('VERSION.txt').decode().strip() == '1.1.0'
        setup = json.loads(archive.read('setup.json'))
        assert setup['server_url'] == BASE
        assert setup['branch_code'] == '001'
        archive.extractall(PRIVATE / 'downloaded-installer')
    results['versioned_download_with_bundled_print_engine'] = 'passed'

    ui.screenshot(path=str(OUT / 'admin-multiple-printers.png'), full_page=True)
    admin.storage_state(path=str(PRIVATE / 'admin-state.json'))

    local = browser.new_context(viewport={'width': 1280, 'height': 1000})
    local.add_init_script('window.__printCalls = 0; window.print = () => { window.top.__printCalls += 1; };')
    page = local.new_page()
    page.on('pageerror', lambda error: errors.append(str(error)))
    page.goto(BASE + '/ambil-nomor/001', wait_until='domcontentloaded')
    expect(page.locator('#printDestination')).to_contain_text('Printer PC ini')
    with page.expect_response(lambda response: response.request.method == 'POST' and response.url.endswith('/ambil-nomor')) as issue:
        page.locator('.btn-take[data-service=cs]').click()
    receipt = issue.value.json()
    assert receipt['print_job']['transport'] == 'browser'
    page.wait_for_function('window.__printCalls === 1')
    expect(page.locator('#browserReceiptDialog')).to_be_visible()
    expect(page.frame_locator('#browserReceiptFrame').locator('.number')).to_have_text(receipt['ticket'])
    page.screenshot(path=str(OUT / 'local-printer-receipt.png'), full_page=True)
    page.get_by_role('button', name='Tutup', exact=True).click()
    page.get_by_role('button', name='Cetak tiket terakhir', exact=True).click()
    expect(page.locator('#browserReceiptPrint')).to_be_enabled()
    assert page.evaluate('window.__printCalls') == 1
    page.get_by_role('button', name='Cetak tiket ini', exact=True).click()
    page.wait_for_function('window.__printCalls === 2')
    results['local_dialog_and_reopen_same_ticket'] = 'passed'

    second = browser.new_context(viewport={'width': 1024, 'height': 768})
    second.add_init_script('window.__printCalls = 0; window.print = () => { window.top.__printCalls += 1; };')
    pc2 = second.new_page()
    pc2.goto(BASE + '/ambil-nomor/001')
    assert second.request.get(receipt['print_job']['receipt_url']).status == 403
    pc2.locator('.btn-take[data-service=teller]').click()
    pc2.wait_for_function('window.__printCalls === 1')
    with database() as db:
        assert db.execute('select count(*) from jobs').fetchone()[0] == 0
        assert db.execute("select count(*) from print_jobs where transport='browser'").fetchone()[0] == before_browser_jobs + 2
        assert db.execute('select count(*) from queues').fetchone()[0] == before_queues + 2
        assert db.execute("select count(*) from print_agents where deleted_at is null and branch_id=(select id from branches where code='001')").fetchone()[0] == 2
        assert db.execute('select count(*) from print_agents where deleted_at is not null').fetchone()[0] >= 1
    results['two_unpaired_browsers_and_private_receipt'] = 'passed'

    # Only the disposable audit DB is updated; no native worker is allowed to run here.
    with database() as db:
        db.execute('update print_agents set enabled=1, printer_ready=1, heartbeat_at=? where id=?', (datetime.now().strftime('%Y-%m-%d %H:%M:%S'), fixture['old_agent_id']))
    ui.goto(BASE + '/admin/print-agents')
    modal = manage('PC Pusat Lama', 'Gunakan pada PC ini')
    modal.get_by_role('button', name='Konfirmasi', exact=True).click()
    expect(ui.get_by_text('Printer dipasangkan ke browser PC ini.', exact=True)).to_be_visible()
    ui.goto(BASE + '/ambil-nomor/001')
    expect(ui.locator('#printDestination')).to_contain_text('PC Pusat Lama')
    ui.get_by_role('button', name='Pakai printer PC ini', exact=True).click()
    expect(ui.locator('#printDestination')).to_contain_text('Printer PC ini')
    results['native_pair_and_return_to_local'] = 'passed'

    assert not errors, errors
    results['javascript_errors'] = errors
    results['physical_print'] = 'not sent; browser print calls intercepted'
    browser.close()

(OUT / 'browser-results.json').write_text(json.dumps(results, indent=2), encoding='utf8')
print(json.dumps(results, indent=2))
