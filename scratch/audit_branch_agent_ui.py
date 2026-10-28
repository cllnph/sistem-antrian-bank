from pathlib import Path
import json, re, zipfile
from playwright.sync_api import sync_playwright, expect

ROOT=Path(__file__).resolve().parents[1]
OUT=ROOT/'scratch/branch-agent-audit'
with sync_playwright() as p:
    browser=p.chromium.launch(channel='msedge',headless=True)
    context=browser.new_context(storage_state=str(OUT/'private/browser-session.json'),viewport={'width':1536,'height':960})
    context.route('https://fonts.googleapis.com/**',lambda r:r.abort())
    context.route('https://fonts.gstatic.com/**',lambda r:r.abort())
    page=context.new_page();errors=[]
    page.on('pageerror',lambda e:errors.append(str(e)))
    page.goto('http://127.0.0.1:7102/admin/print-agents',wait_until='domcontentloaded')
    page.get_by_role('button',name='Tambah Penghubung').click()
    modal=page.locator('.fi-modal-window').last
    modal.wait_for(state='visible')
    modal.get_by_label('Kantor Cabang',exact=False).select_option(label='Kantor Cabang Purwokerto')
    modal.get_by_label('Nama PC Penghubung',exact=False).fill('UAT Installer Purwokerto')
    modal.get_by_label('Nama Printer Windows',exact=False).fill('ITPRINTER (L6290 Series)')
    modal.get_by_role('button',name='Buat',exact=True).click()
    row=page.get_by_role('row').filter(has_text='UAT Installer Purwokerto')
    row.wait_for(state='visible')
    row.get_by_role('button',name='Unduh Installer',exact=True).click()
    modal=page.locator('.fi-modal-window').last
    modal.wait_for(state='visible')
    assert modal.get_by_label('Alamat Aplikasi Pusat',exact=False).input_value()=='http://127.0.0.1:7102'
    with page.expect_download() as event:
        modal.get_by_role('button',name='Unduh Installer',exact=True).click()
    download=event.value
    package=OUT/'private/ui-installer.zip';download.save_as(package)
    with zipfile.ZipFile(package) as z:
        assert {'Install-Agent.cmd','Install-Agent.ps1','Start-Agent.ps1','Agent.Common.ps1','CheckPrinter.cs','setup.json'}.issubset(z.namelist())
        z.extractall(OUT/'private/ui-installer')
    assert not errors,errors
    page.screenshot(path=str(OUT/'admin-agent-menu.png'),full_page=True)
    (OUT/'ui-results.json').write_text(json.dumps({'native_create':'passed','native_installer_download':'passed','javascript_errors':errors},indent=2))
    browser.close()
print('PASS native Filament create, installer download, no JavaScript errors')
