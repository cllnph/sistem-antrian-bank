"""Browser integration audit on the isolated :7114 SQLite server; no printer output."""
from pathlib import Path
import hashlib
import json
import os
import sqlite3
import time
import zipfile

from playwright.sync_api import sync_playwright, expect

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'scratch/local-printing-v130'
PRIVATE = OUT / 'private'
BASE = 'http://127.0.0.1:7114'
fixture = json.loads((PRIVATE / 'fixture.json').read_text())
previous = json.loads((OUT / 'results.json').read_text(encoding='utf-8')) if (OUT / 'results.json').exists() else {}
results = {'physical_print': 'not sent; no physical printer available',
           'browsers': previous.get('browsers', {}) if os.environ.get('PRINT_AUDIT_BROWSERS') == 'none' else {}}
(OUT / 'results.json').write_text(json.dumps(results, indent=2),encoding='utf-8')


def wait_for_print(page, calls, before):
    try:
        deadline = time.monotonic() + 60
        while page.evaluate('window.__printCalls || 0') == before and time.monotonic() < deadline:
            page.wait_for_timeout(100)
        assert page.evaluate('window.__printCalls || 0') == before + 1, (before, page.evaluate('window.__printCalls || 0'))
    except Exception as error:
        page.screenshot(path=str(OUT / 'browser-print-failure.png'), full_page=True)
        diagnostic = page.evaluate('''() => ({calls:window.__printCalls,
            message:document.getElementById('browserReceiptMessage').textContent,
            receipt:!!document.getElementById('browserReceiptFrame').contentDocument?.querySelector('meta[name="bank-ticket"]'),
            disabled:document.getElementById('browserReceiptPrint').disabled})''')
        raise RuntimeError('Browser print diagnostic: ' + json.dumps(diagnostic)) from error


def headers(name):
    agent = fixture['agents'][name]
    return {'Authorization': 'Bearer ' + agent['token'], 'X-Print-Agent': agent['id'], 'X-Print-Device': agent['device_id']}


def heartbeat(context, name):
    response = context.request.post(BASE + '/api/print-agent/heartbeat', headers=headers(name), data={
        'printer_ready': True, 'printer_name': 'EPSON L3250 Series (Copy 2)', 'paper_profile': 'a4',
        'machine_name': name, 'version': '1.3.0', 'inventory_status': 'ok', 'installed_printers': ['EPSON L3250 Series (Copy 2)', 'Panda USB'],
    })
    assert response.ok, response.status


def arm_local_print(page):
    page.evaluate("""() => {
        if (window.__auditPrintHooked) return;
        const original = window.printBrowserReceipt;
        if (typeof original !== 'function') throw new Error('Missing browser receipt handler');
        window.printBrowserReceipt = () => {
            const frameWindow = document.getElementById('browserReceiptFrame').contentWindow;
            Object.defineProperty(frameWindow, 'print', {configurable:true,writable:true,value:()=>{window.__printCalls=(window.__printCalls||0)+1}});
            frameWindow.focus=()=>{};
            return original();
        };
        window.__auditPrintHooked=true;
    }""")


def issue(page, service='cs'):
    arm_local_print(page)
    with page.expect_response(lambda response: response.request.method == 'POST' and response.url.endswith('/ambil-nomor/130')) as event:
        page.locator(f'.btn-take[data-service={service}]').click()
    response = event.value
    assert response.ok, (response.status, response.text())
    return response.json()


def simulate_agent(context, job_id):
    for name in ['LAPTOP-SERVER', 'PC-CABANG']:
        heartbeat(context, name)
    other = context.request.post(BASE + '/api/print-agent/claim', headers=headers('LAPTOP-SERVER'), data={})
    assert other.ok and other.json()['job'] is None, 'Same queue name must not redirect a branch job to laptop'
    response = context.request.post(BASE + '/api/print-agent/claim', headers=headers('PC-CABANG'), data={})
    assert response.ok, response.text()
    job = response.json()['job']
    assert job['id'] == job_id
    claim_headers = {**headers('PC-CABANG'), 'X-Print-Claim': job['claim_token']}
    pdf = context.request.get(BASE + '/api/print-agent/jobs/' + job_id + '/pdf', headers=claim_headers)
    assert pdf.ok and pdf.body().startswith(b'%PDF-')
    digest = hashlib.sha256(pdf.body()).hexdigest()
    start = context.request.post(BASE + '/api/print-agent/jobs/' + job_id + '/start', headers=claim_headers, data={'pdf_sha256': digest})
    assert start.ok, start.text()
    # A simulated acceptance verifies routing/state only. No native print command is invoked.
    complete = context.request.post(BASE + '/api/print-agent/jobs/' + job_id + '/complete', headers=claim_headers, data={'status': 'submitted'})
    assert complete.ok, complete.text()


with sync_playwright() as p:
    for name, engine, options in [
        ('edge', p.chromium, {'channel': 'msedge'}),
        ('chrome', p.chromium, {'channel': 'chrome'}),
        ('firefox', p.firefox, {}),
    ]:
        if name not in os.environ.get('PRINT_AUDIT_BROWSERS', 'edge,chrome,firefox').split(','):
            continue
        browser = engine.launch(headless=True, **options)
        context = browser.new_context(viewport={'width': 1366, 'height': 900})
        context.set_default_timeout(90000)
        context.route('https://fonts.googleapis.com/**', lambda route: route.abort())
        context.route('https://fonts.gstatic.com/**', lambda route: route.abort())
        print_calls = []
        context.expose_binding('__auditPrint', lambda source: print_calls.append(source['frame'].url))
        context.add_init_script("window.print=()=>{window.top.__printCalls=(window.top.__printCalls||0)+1}")
        page = context.new_page()
        errors = []
        page.on('pageerror', lambda error: errors.append(str(error)))
        page.on('dialog', lambda dialog: dialog.accept())
        page.goto(BASE + '/ambil-nomor/130', wait_until='domcontentloaded')
        expect(page.locator('#printDestination')).to_have_count(0)
        expect(page.locator('#paperProfile')).to_have_count(0)
        expect(page.locator('#kioskRequestRecovery')).to_be_hidden()
        expect(page.locator('#lastBrowserTicket')).to_be_hidden()
        page.screenshot(path=str(OUT / f'{name}-kiosk-clean.png'), full_page=True)
        before = page.evaluate('window.__printCalls || 0')
        first = issue(page)
        assert first['print_job']['transport'] == 'browser'
        wait_for_print(page, print_calls, before)
        expect(page.locator('#browserReceiptDialog')).to_be_visible()
        receipt_url = first['print_job']['receipt_url']
        assert receipt_url.startswith('/print-jobs/')
        stranger = browser.new_context()
        assert stranger.request.get(BASE + receipt_url).status == 403
        stranger.close()
        page.locator('#browserReceiptDialog button', has_text='Tutup').click()
        csrf = page.locator('input[name=_token]').first.input_value()
        response = context.request.post(BASE + '/logout', form={'_token': csrf},max_redirects=0,timeout=90000)
        assert response.status == 302
        page.goto(BASE + '/ambil-nomor/130', wait_until='domcontentloaded')
        assert context.request.get(BASE + receipt_url).ok, 'Device cookie must preserve receipt after logout/session regeneration'
        replay = context.request.post(BASE + '/ambil-nomor/130', headers={
            'Accept': 'application/json', 'X-CSRF-TOKEN': page.locator('input[name=_token]').first.input_value(),
        }, data={'service': 'cs', 'request_id': first['request_id']})
        assert replay.ok and replay.json()['ticket'] == first['ticket']
        # A failed session check must preserve the original issuance UUID.
        def expire_once(route):
            route.fulfill(status=419, json={'message': 'Session expired'})
        page.route('**/ambil-nomor/130', expire_once)
        page.locator('.btn-take[data-service=cs]').click()
        expect(page.locator('#kioskRequestRecovery')).to_be_visible()
        pending = page.evaluate(f"JSON.parse(sessionStorage.getItem('ticket-request-{fixture['branch_id']}'))")
        assert pending and pending['request_id']
        page.unroute('**/ambil-nomor/130', expire_once)
        page.reload(wait_until='domcontentloaded')
        before = page.evaluate('window.__printCalls || 0')
        recovered = issue(page)
        assert recovered['request_id'] == pending['request_id']
        wait_for_print(page, print_calls, before)
        page.locator('#browserReceiptDialog button', has_text='Tutup').click()
        setting = context.request.post(BASE + f"/kiosk-printing/{fixture['branch_id']}/paper", form={
            '_token': page.locator('input[name=_token]').first.input_value(), 'paper_profile': 'thermal58',
        })
        assert setting.ok
        page.reload(wait_until='domcontentloaded')
        before = page.evaluate('window.__printCalls || 0')
        thermal = issue(page, 'teller')
        wait_for_print(page, print_calls, before)
        if name != 'firefox':
            receipt = context.new_page()
            receipt.goto(BASE + thermal['print_job']['receipt_url'])
            receipt.pdf(path=str(OUT / f'{name}-thermal58.pdf'), prefer_css_page_size=True, print_background=True)
            receipt.close()
        page.screenshot(path=str(OUT / f'{name}-local-ticket.png'), full_page=True)
        heartbeat(context, 'PC-CABANG')
        proof = context.request.post(BASE + '/api/print-agent/browser-pairings', headers=headers('PC-CABANG'), data={})
        assert proof.ok, proof.text()
        page.goto(proof.json()['connect_url'], wait_until='domcontentloaded')
        page.wait_for_url('**/ambil-nomor/130')
        expect(page.locator('#printDestination')).to_have_count(0)
        paired = issue(page)
        assert paired['print_job']['transport'] == 'agent'
        simulate_agent(context, paired['request_id'])
        expect(page.locator('.btn-take[data-service=cs]')).to_be_enabled(timeout=15000)
        page.screenshot(path=str(OUT / f'{name}-paired-pc.png'), full_page=True)
        assert not errors, errors
        results['browsers'][name] = {'local_without_registration': 'passed', 'session_regeneration': 'passed', 'csrf_uuid_recovery': 'passed',
            'paper58': 'passed', 'local_agent_pairing': 'passed', 'identical_names_stay_on_correct_pc': 'passed', 'javascript_errors': errors}
        (OUT / 'results.json').write_text(json.dumps(results, indent=2),encoding='utf-8')
        print('PASS browser', name, flush=True)
        context.close()
        browser.close()

    browser = p.chromium.launch(channel='msedge', headless=True)
    context = browser.new_context(viewport={'width': 1366, 'height': 900})
    context.set_default_timeout(60000)
    page = context.new_page()
    page.goto(BASE + '/admin/login', wait_until='domcontentloaded')
    page.locator('input[type=email]').fill('print130@example.test')
    page.locator('input[type=password]').fill('print130-fixture-only')
    page.locator('button[type=submit]').click()
    page.wait_for_function("location.pathname !== '/admin/login'")
    page.goto(BASE + '/admin/print-agents', wait_until='domcontentloaded')
    page.wait_for_url('**/admin/operasional-cabang')
    page.locator(f'a[href="{BASE}/admin/cabang/{fixture["branch_id"]}"]').first.click()
    page.wait_for_url(f'**/admin/cabang/{fixture["branch_id"]}',wait_until='domcontentloaded')
    printing_link = page.locator(f'a[href="{BASE}/admin/print-agents/{fixture["branch_id"]}/cetak"]').first
    printing_link.click()
    page.wait_for_url(f'**/admin/print-agents/{fixture["branch_id"]}/cetak', wait_until='domcontentloaded')
    page.screenshot(path=str(OUT / 'admin-branch-printing.png'), full_page=True)
    expect(page.locator('h1')).to_contain_text('Kantor Uji Cetak Lokal', timeout=30000)
    expect(page.locator('.fi-ta')).to_contain_text('PC-CABANG')
    expect(page.locator('.fi-ta')).not_to_contain_text('PC-CABANG-LAIN')
    expect(page.locator('.fi-sidebar')).not_to_contain_text('Penghubung Cetak Cabang')
    page.screenshot(path=str(OUT / 'admin-branch-printing.png'), full_page=True)
    page.get_by_role('button', name='Cetak lokal PC ini', exact=True).click()
    modal = page.locator('.fi-modal-window:visible').last
    modal.get_by_role('combobox').select_option('thermal80')
    modal.get_by_role('button', name='Gunakan cetak lokal', exact=True).click()
    expect(modal).to_be_hidden()
    expect(page.locator('.fi-header-subheading')).to_contain_text('Thermal 80 mm')
    row = page.locator('.fi-ta-record', has_text='PC-CABANG').first
    row.get_by_role('button', name='Kelola', exact=True).click()
    page.screenshot(path=str(OUT / 'admin-printer-menu.png'), full_page=True)
    page.locator('.fi-dropdown-panel:visible').get_by_text('Ganti printer & kertas', exact=True).click()
    modal = page.locator('.fi-modal-window:visible').last
    modal.get_by_role('combobox').nth(0).select_option('Panda USB')
    modal.get_by_role('combobox').nth(1).select_option('thermal58')
    page.screenshot(path=str(OUT / 'admin-change-printer.png'), full_page=True)
    modal.locator('button[type=submit]').click()
    expect(modal).to_be_hidden()
    expect(row).to_contain_text('Panda USB')
    page.goto(BASE + f"/admin/print-agents/{fixture['other_branch_id']}/cetak", wait_until='domcontentloaded')
    expect(page.locator('.fi-ta')).to_contain_text('PC-CABANG-LAIN')
    expect(page.locator('.fi-ta')).not_to_contain_text('LAPTOP-SERVER')
    page.goto(BASE + f"/admin/print-agents/{fixture['branch_id']}/cetak", wait_until='domcontentloaded')
    page.get_by_role('button', name='Pasang PC baru', exact=True).click()
    modal = page.locator('.fi-modal-window:visible').last
    expect(modal).to_contain_text('Kantor Uji Cetak Lokal')
    expect(modal.get_by_role('combobox')).to_have_count(0)
    with page.expect_download() as event:
        modal.get_by_role('button', name='Unduh Installer', exact=True).click()
    package = PRIVATE / 'downloaded-installer-v1.3.0.zip'
    event.value.save_as(package)
    with zipfile.ZipFile(package) as archive:
        assert archive.read('VERSION.txt').decode().strip() == '1.3.0'
        for filename in ['Watch-Agent.ps1', 'Open-Kiosk.ps1', 'Printer.Inventory.ps1', 'Printer.Setup.ps1', 'Agent.Common.ps1']:
            assert archive.read(filename) == (ROOT / 'agent' / filename).read_bytes()
        setup = json.loads(archive.read('setup.json'))
        assert setup['printer_name'] is None and 'token' not in setup
        assert setup['paper_profile'] == 'a4'
    page.screenshot(path=str(OUT / 'admin-new-pc-installer.png'), full_page=True)
    results['responsive'] = []
    def bounds():
        return page.evaluate("""() => ({width:innerWidth,scroll:document.documentElement.scrollWidth,overflow:[...document.querySelectorAll('.fi-main button:enabled,.fi-main a,.fi-dropdown-panel:popover-open')].filter(e=>e.getClientRects().length).map(e=>({text:e.textContent.trim(),r:e.getBoundingClientRect().right,l:e.getBoundingClientRect().left})).filter(x=>x.r>innerWidth+2||x.l< -2)})""")
    for width in [390,768,1366,1920]:
        page.set_viewport_size({'width':width,'height':960})
        for path,label in [('/admin/operasional-cabang','dashboard'),(f"/admin/cabang/{fixture['branch_id']}",'detail'),(f"/admin/print-agents/{fixture['branch_id']}/cetak",'printers'),('/admin/waktu-operasional','schedule')]:
            page.goto(BASE+path,wait_until='domcontentloaded')
            expect(page.locator('h1')).to_be_visible(timeout=30000)
            expect(page.locator('link[rel~="icon"], link[rel="apple-touch-icon"], link[rel="mask-icon"]')).to_have_count(0)
            assert not page.get_by_text('Kontrol & Laporan',exact=True).count()
            assert not page.get_by_text('Unduh Aplikasi Android',exact=True).count()
            info=bounds()
            assert info['scroll'] <= width+2, (label,width,info)
            assert not info['overflow'], (label,width,info)
            page.screenshot(path=str(OUT / f'operations-{label}-{width}.png'),full_page=True)
            if label=='printers':
                page.get_by_role('button',name='Kelola',exact=True).first.click()
                menu=page.locator('.fi-dropdown-panel:visible').last
                expect(menu).to_be_visible()
                rect=menu.bounding_box()
                assert rect['x']>=-2 and rect['x']+rect['width']<=width+2, rect
                menu.get_by_text('Ubah label perangkat',exact=True).click()
                modal=page.locator('.fi-modal-window:visible').last
                expect(modal).to_be_visible()
                label_input=modal.get_by_role('textbox').last
                label_input.fill('Label PC dipertahankan')
                page.wait_for_timeout(11000)
                expect(label_input).to_have_value('Label PC dipertahankan')
                page.screenshot(path=str(OUT / f'operations-printer-modal-{width}.png'),full_page=True)
                modal.get_by_role('button',name='Batal',exact=True).click()
            if label=='schedule':
                page.get_by_role('button',name='Atur Jadwal',exact=True).first.click()
                modal=page.locator('.fi-modal-window:visible').last
                expect(modal).to_contain_text('Senin')
                expect(modal).to_contain_text('Reset antrean harian')
                page.screenshot(path=str(OUT / f'operations-schedule-modal-{width}.png'),full_page=True)
                modal.get_by_role('button',name='Batal',exact=True).click()
            results['responsive'].append({'page':label,'width':width,'result':'passed'})
            (OUT / 'results.json').write_text(json.dumps(results, indent=2),encoding='utf-8')
            print(f'PASS responsive {label} {width}',flush=True)
    results['admin_installer_download'] = 'passed'
    results['per_branch_print_menu'] = 'passed'
    results['change_printer_and_paper_from_branch'] = 'passed'
    results['kiosk_settings_hidden'] = 'passed'
    legacy_context = browser.new_context(viewport={'width':1366,'height':900})
    legacy_context.route('https://fonts.googleapis.com/**',lambda route:route.abort())
    legacy_context.route('https://fonts.gstatic.com/**',lambda route:route.abort())
    legacy_context.add_init_script("window.print=()=>{window.top.__printCalls=(window.top.__printCalls||0)+1}")
    legacy_page = legacy_context.new_page()
    legacy_page.on('dialog',lambda dialog:dialog.accept())
    legacy_page.goto(BASE+'/ambil-nomor/130',wait_until='domcontentloaded')
    def legacy_csrf_failure(route):
        route.fulfill(status=419,json={'message':'Session expired before upgrade'})
    legacy_page.route('**/ambil-nomor/130',legacy_csrf_failure)
    legacy_page.locator('.btn-take[data-service=cs]').click()
    expect(legacy_page.locator('#kioskRequestRecovery')).to_be_visible()
    legacy_uuid = legacy_page.evaluate("""key => {
        const pending=JSON.parse(sessionStorage.getItem(key));
        delete pending.issuance_context; delete pending.period_id;
        sessionStorage.setItem(key,JSON.stringify(pending));
        return pending.request_id;
    }""",f"ticket-request-{fixture['branch_id']}")
    legacy_page.unroute('**/ambil-nomor/130',legacy_csrf_failure)
    legacy_page.reload(wait_until='domcontentloaded')
    recovered_legacy = issue(legacy_page)
    assert recovered_legacy['request_id'] == legacy_uuid
    wait_for_print(legacy_page,[],0)
    with sqlite3.connect(fixture['database']) as legacy_db:
        assert legacy_db.execute('select count(*) from print_jobs where id=?',(legacy_uuid,)).fetchone()[0] == 1
    results['legacy_pending_uuid_recovery']='passed'
    print('PASS legacy pending request retains UUID after reload',flush=True)
    legacy_context.close()

    browser.close()

with sqlite3.connect(fixture['database']) as db:
    assert db.execute('select count(*) from jobs').fetchone()[0] == 0
    results['isolated_ticket_count'] = db.execute('select count(*) from print_jobs').fetchone()[0]
(OUT / 'results.json').write_text(json.dumps(results, indent=2))
print(json.dumps(results, indent=2))
