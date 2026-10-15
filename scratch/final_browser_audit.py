from pathlib import Path
import json
import pymupdf
from playwright.sync_api import sync_playwright
BASE='http://127.0.0.1:7101'
out=Path('scratch/final-audit'); out.mkdir(exist_ok=True)
results=[]
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path=r'C:\Program Files\Google\Chrome\Application\chrome.exe',headless=True)
    context=browser.new_context(viewport={'width':1366,'height':768})
    context.route('https://fonts.googleapis.com/**', lambda r:r.abort())
    context.route('https://fonts.gstatic.com/**', lambda r:r.abort())
    context.route('https://cdn.jsdelivr.net/**', lambda r:r.abort())
    # Keep page polling off the single-worker development server during navigation.
    live_data=context.request.get(BASE+'/api/display/data/002',timeout=120000).json()
    context.route(BASE+'/api/display/data/**', lambda r:r.fulfill(json=live_data))
    page=context.new_page()
    errors=[]
    page.on('pageerror',lambda error:errors.append(str(error)))
    for path in ['/admin/login','/ambil-nomor/002','/display/002']:
        response=page.goto(BASE+path,wait_until='domcontentloaded',timeout=120000)
        page.wait_for_timeout(400)
        assert response.status==200,(path,response.status)
        page.screenshot(path=str(out/(path.strip('/').replace('/','-')+'.png')),full_page=True)
        results.append({'path':path,'status':response.status})
    page.goto('about:blank')
    for code in range(1,10):
        response=context.request.get(BASE+f'/api/display/data/{code:03}',timeout=120000)
        assert response.status==200,(code,response.status)
        assert response.json()['branch']['code']==f'{code:03}'
    for path in ['/ambil-nomor/not-a-branch','/display/not-a-branch','/api/display/data/not-a-branch']:
        response=context.request.get(BASE+path,timeout=120000)
        assert response.status==404,(path,response.status)
    page.goto(BASE+'/ambil-nomor/002',wait_until='domcontentloaded',timeout=120000)
    page.evaluate('() => { window.printCalls=0; window.printTraces=[]; window.print=()=>{window.printCalls++; window.printTraces.push(new Error().stack)}; }')
    calls=[]
    def issue(route):
        calls.append(route.request.post_data_json)
        route.fulfill(json={'success':True,'ticket':'T-123','service_name':'TELLER','branch_name':'Kantor Cabang Bogor','branch_address':'Jl. Achmad Adnawijaya No.8, RT.07/RW.15, Bantarjati, Kec. Bogor Utara, Kota Bogor, Jawa Barat 16151','branch_phone':None,'date_formatted':'Rabu, 14 Oktober 2026 | 12:00 WIB'})
    page.route(BASE+'/ambil-nomor',issue)
    page.locator('.btn-take[data-service="teller"]').click(timeout=60000)
    page.wait_for_function('window.printCalls >= 1')
    assert page.evaluate('window.printCalls')==1
    assert len(calls)==1
    assert page.locator('#printReceiptNumber').text_content()=='T-123'
    assert not page.locator('#ticketPrintArea').is_visible()
    assert not page.locator('#receiptBranchPhone').is_visible()
    page.emulate_media(media='print')
    assert page.locator('#ticketPrintArea').is_visible()
    assert page.locator('.thermal-bank-logo').evaluate('(e)=>e.complete && e.naturalWidth>0')
    page.pdf(path=str(out/'ticket-bogor-80mm.pdf'),width='80mm',height='150mm',print_background=True)
    page.pdf(path=str(out/'ticket-bogor-58mm.pdf'),width='58mm',height='150mm',print_background=True)
    for width in ['80','58']:
        pdf=pymupdf.open(out/f'ticket-bogor-{width}mm.pdf')
        text=''.join(p.get_text() for p in pdf)
        assert 'T-123' in text and 'Bogor' in text
        assert 'http://' not in text and 'https://' not in text and 'Telp:' not in text
        assert any(p.get_images() for p in pdf)
    page.emulate_media(media='screen')
    page.set_viewport_size({'width':390,'height':844})
    page.screenshot(path=str(out/'kiosk-mobile.png'),full_page=True)
    assert page.evaluate('document.documentElement.scrollWidth <= window.innerWidth'), 'Mobile horizontal overflow'
    # Rapid repeated taps must submit and print only once while a request is pending.
    page.evaluate('() => { window.printCalls=0; }')
    before=len(calls)
    page.evaluate("() => {const b=document.querySelector('.btn-take[data-service=\"teller\"]');b.click();b.click();}")
    page.wait_for_function('window.printCalls === 1')
    assert len(calls)==before+1
    page.wait_for_function('!isTakingTicket')
    # Rejected issuance must not print, and the button must become usable again.
    page.route(BASE+'/ambil-nomor',lambda r:r.fulfill(json={'success':False,'message':'Test service closed'},status=422))
    page.on('dialog',lambda d:d.accept())
    page.evaluate('() => { window.printCalls=0; }')
    page.locator('.btn-take[data-service="teller"]').click()
    page.wait_for_function('!isTakingTicket')
    assert page.evaluate('window.printCalls')==0
    assert page.locator('.btn-take[data-service="teller"]').is_enabled()
    # Text returned by the API must be treated as text, never executable HTML.
    hostile='<img src=x onerror="window.auditInjection=true">'
    page.route(BASE+'/api/display/data/**',lambda r:r.fulfill(json={'counters':[{'id':1,'service':'teller','counter_name':hostile,'current_number':'T-123','next_waiting_number':'T-124','waiting_count':1,'called_at':None}]}))
    page.goto(BASE+'/display/002',wait_until='domcontentloaded',timeout=120000)
    page.locator('.counter-name').first.wait_for()
    page.wait_for_function('document.querySelector(".counter-name").textContent.includes("onerror")')
    assert page.locator('.counter-name').first.text_content()==hostile
    assert page.locator('.counter-name img').count()==0
    assert page.evaluate('window.auditInjection !== true')
    assert not errors,errors
    browser.close()
report={'pages':results,'nine_branch_apis':'passed','invalid_branches':'404','mocked_one_tap_print':'passed','thermal_pdf_58_and_80mm':'passed','mobile_overflow':'none','javascript_errors':errors,'rapid_double_tap':'one_request_one_print','rejected_ticket':'no_print_button_recovers','display_html_injection':'escaped','live_queue_mutations':0}
(out/'browser-results.json').write_text(json.dumps(report,indent=2),encoding='utf-8')
print(json.dumps(report))
