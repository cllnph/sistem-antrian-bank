from pathlib import Path
import json
from playwright.sync_api import sync_playwright
BASE='http://127.0.0.1:7102'
out=Path('scratch/filament-native-audit'); out.mkdir(exist_ok=True)
results=[]
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path=r'C:\Program Files\Google\Chrome\Application\chrome.exe',headless=True)
    for role,email,paths in [
        ('admin','admin@example.com',['operasional-cabang','admin-control-center','queues','branches','service-settings','users','roles']),
        ('branch_admin','ui.branch@example.test',['operasional-cabang','queues','service-settings','users']),
        ('teller','teller.bogor@banktaspen.co.id',['operasional-cabang','queues']),
        ('cs','cs.bogor@banktaspen.co.id',['operasional-cabang','queues']),
    ]:
        context=browser.new_context(viewport={'width':1440,'height':900})
        page=context.new_page()
        errors=[]
        page.on('pageerror',lambda e:errors.append(str(e)))
        page.goto(BASE+'/admin/login',wait_until='load',timeout=120000)
        page.locator('input[type=email]').fill(email)
        page.locator('input[type=password]').fill('password')
        page.locator('button[type=submit]').click()
        page.wait_for_function('!location.pathname.endsWith("/login")',timeout=120000)
        for path in paths:
            response=page.goto(BASE+'/admin/'+path,wait_until='load',timeout=120000)
            assert response.status==200,(role,path,response.status)
            page.locator('.fi-main').wait_for(timeout=60000)
            page.screenshot(path=str(out/f'{role}-{path}.png'),full_page=True)
            assert page.locator('[class*="bank-"]').count()==0,(role,path,'custom bank layout remains')
            if path in ['queues','branches','service-settings','users','roles','admin-control-center']:
                assert page.locator('.fi-ta').count()>0,(role,path,'native table missing')
            results.append({'role':role,'menu':path,'status':response.status})
            print('PASS',role,path,flush=True)
        if role=='admin':
            for path,label in [('branches','Tambah Kantor Cabang'),('roles','Tambah Role / Aktor Baru'),('users','Kelola Peran & Hak Akses')]:
                page.goto(BASE+'/admin/'+path,wait_until='load',timeout=120000)
                page.wait_for_function('window.Livewire && window.Alpine',timeout=60000)
                page.get_by_role('button',name=label,exact=True).click()
                try:
                    page.locator('.fi-modal-window:visible').first.wait_for(timeout=120000)
                except Exception:
                    page.screenshot(path=str(out/f'modal-failure-{path}.png'),full_page=True)
                    print('MODAL ERRORS',errors,flush=True)
                    raise
                assert page.locator('.fi-modal').count()>0
                page.screenshot(path=str(out/f'modal-{path}.png'),full_page=True)
                print('PASS modal',path,flush=True)
            page.goto(BASE+'/admin/operasional-cabang',wait_until='load',timeout=120000)
            page.evaluate("() => { document.documentElement.classList.add('dark'); }")
            page.screenshot(path=str(out/'admin-dashboard-dark.png'),full_page=True)
            page.evaluate("() => { document.documentElement.classList.remove('dark'); }")
            page.set_viewport_size({'width':390,'height':844})
            for path in ['operasional-cabang','admin-control-center','users']:
                page.goto(BASE+'/admin/'+path,wait_until='load',timeout=120000)
                page.screenshot(path=str(out/f'mobile-{path}.png'),full_page=True)
                assert page.evaluate('document.documentElement.scrollWidth <= innerWidth'),('mobile overflow',path)
        assert not errors,(role,errors)
        context.close()
    browser.close()
report={'menu_checks':results,'native_modals':'passed','dark_mode':'rendered','mobile_horizontal_overflow':'none','javascript_errors':[],'data_source':'isolated SQLite fixtures; live application unchanged'}
(out/'results.json').write_text(json.dumps(report,indent=2),encoding='utf-8')
print(json.dumps(report))
