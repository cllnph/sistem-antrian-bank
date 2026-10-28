from pathlib import Path
import json, sqlite3
from playwright.sync_api import sync_playwright

ROOT=Path(__file__).resolve().parents[1]
OUT=ROOT/'scratch/device-history-audit'
BASE='http://127.0.0.1:7102'
results={}
# This file is a dedicated disposable SQLite fixture, never the application DB.
with sqlite3.connect(OUT/'private/app.sqlite') as db:
    db.execute('PRAGMA foreign_keys=ON')
    for table in ['kiosk_device_events','kiosk_device_sessions','kiosk_devices','sessions']:
        db.execute('delete from '+table)
with sync_playwright() as p:
    browser=p.chromium.launch(channel='msedge',headless=True)
    ua='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/130.0.0.0 Safari/537.36 Edg/130.0'
    first=browser.new_context(user_agent=ua)
    second=browser.new_context(user_agent=ua)
    page=first.new_page();page.goto(BASE+'/ambil-nomor/002',wait_until='domcontentloaded')
    second.new_page().goto(BASE+'/ambil-nomor/003',wait_until='domcontentloaded')
    with sqlite3.connect(OUT/'private/app.sqlite') as db:
        assert db.execute('select count(*) from kiosk_devices').fetchone()[0]==2
        assert db.execute('select count(distinct last_ip) from kiosk_devices').fetchone()[0]==1
    cookie=next(x for x in first.cookies() if x['name']=='bank_kiosk_device')
    assert cookie['httpOnly'] and cookie['sameSite']=='Lax'
    first.clear_cookies();first.add_cookies([cookie])
    page.goto(BASE+'/ambil-nomor/003',wait_until='domcontentloaded')
    with sqlite3.connect(OUT/'private/app.sqlite') as db:
        assert db.execute('select count(*) from kiosk_devices').fetchone()[0]==2
    results['identity']='same IP/UA: separate browsers; same browser retained after session reset'
    mobile=browser.new_context(viewport={'width':390,'height':844},is_mobile=True,has_touch=True,
        user_agent='Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 Chrome/120.0 Mobile Safari/537.36')
    mobile.new_page().goto(BASE+'/ambil-nomor/002',wait_until='domcontentloaded')
    first.close();second.close();mobile.close()
    admin=browser.new_context(viewport={'width':1536,'height':960})
    admin.route('https://fonts.googleapis.com/**',lambda route:route.abort())
    admin.route('https://fonts.gstatic.com/**',lambda route:route.abort())
    ui=admin.new_page();errors=[];ui.on('pageerror',lambda e:errors.append(str(e)))
    ui.goto(BASE+'/admin/login',wait_until='domcontentloaded')
    ui.wait_for_function('window.Livewire && Livewire.all().length > 0')
    ui.locator('input[type=email]').fill('admin@example.com')
    ui.locator('input[type=password]').fill('password')
    ui.locator('button[type=submit]').click()
    try:
        ui.wait_for_function('!location.pathname.endsWith("/login")')
    except Exception:
        print('Login diagnostic:',ui.url,ui.locator('form').first.inner_text(),errors)
        raise
    ui.goto(BASE+'/admin/print-agents',wait_until='domcontentloaded')
    ui.get_by_text('Riwayat Perangkat Kiosk',exact=True).click()
    ui.wait_for_url('**/admin/kiosk-devices')
    assert ui.get_by_role('row').count()==4
    # Select the browser with two page opens using its persisted encrypted cookie ID mapping.
    with sqlite3.connect(OUT/'private/app.sqlite') as db:
        device_id=db.execute('select kiosk_device_id from kiosk_device_events group by kiosk_device_id having count(*)=2').fetchone()[0]
    row=ui.get_by_role('row').filter(has_text=device_id[:8])
    row.get_by_role('button',name='Beri Nama',exact=True).click()
    modal=ui.locator('.fi-modal-window').last
    modal.get_by_label('Nama Perangkat',exact=False).fill('Browser Uji Pertama')
    modal.get_by_role('button',name='Simpan',exact=True).click()
    row=ui.get_by_role('row').filter(has_text='Browser Uji Pertama');row.wait_for()
    ui.screenshot(path=str(OUT/'devices.png'),full_page=True)
    row.get_by_text('Riwayat Aktivitas',exact=True).click()
    ui.wait_for_url('**/admin/kiosk-device-events?**')
    assert ui.get_by_role('row').count()==3
    assert ui.get_by_role('row').filter(has_text='Browser Uji Pertama').count()==2
    ui.screenshot(path=str(OUT/'events.png'),full_page=True)
    assert not errors,errors
    with sqlite3.connect(OUT/'private/app.sqlite') as db:
        assert db.execute('select count(*) from kiosk_devices').fetchone()[0]==3
        assert db.execute('select count(*) from kiosk_device_events').fetchone()[0]==4
        assert db.execute('select count(*) from print_agents').fetchone()[0]==0
        assert db.execute('select count(*) from print_jobs').fetchone()[0]==0
    results.update({'native_navigation':'passed','rename':'passed','device_history_filter':'passed','devices':3,'events':4,'automatic_print_agent_enrollments':0,'physical_print':'not sent','javascript_errors':errors})
    browser.close()
(OUT/'browser-results.json').write_text(json.dumps(results,indent=2),encoding='utf8')
print(json.dumps(results,indent=2))
