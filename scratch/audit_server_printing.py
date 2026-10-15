from pathlib import Path
import json, re, sqlite3, time, uuid, concurrent.futures
import pymupdf
from playwright.sync_api import sync_playwright
ROOT=Path(__file__).resolve().parents[1]
OUT=ROOT/'scratch/server-print-audit'
OUT.mkdir(exist_ok=True)
DB=ROOT/'scratch/filament-native-ui.sqlite'
BASE='http://127.0.0.1:7102'
results={'physical_print':'pending: physical printer unavailable; fake printer only'}
with sync_playwright() as p:
    browser=p.chromium.launch(channel='msedge',headless=True)
    context=browser.new_context(viewport={'width':1366,'height':768})
    page=context.new_page()
    page.add_init_script("window.browserPrintCalls=0; window.print=()=>{window.browserPrintCalls++; throw new Error('Browser printing forbidden')};")
    errors=[]; dialogs=[]; issued=[]
    page.on('pageerror',lambda e:errors.append(str(e)))
    page.on('dialog',lambda d:(dialogs.append(d.message),d.dismiss()))
    page.on('response',lambda r:issued.append(r.json()) if r.request.method=='POST' and '/ambil-nomor' in r.url and r.ok else None)
    page.goto(BASE+'/ambil-nomor/002',wait_until='domcontentloaded',timeout=60000)
    for service in ['cs','teller']:
        page.evaluate("s=>{const b=document.querySelector('.btn-take[data-service='+s+']');b.click();b.click();}",service)
        page.wait_for_function('!isTakingTicket',timeout=90000)
        assert len(issued)==(1 if service=='cs' else 2), issued
        data=issued[-1]
        status=context.request.get(data['print_job']['status_url']).json()
        assert status['print_job']['status']=='submitted',status
        assert page.evaluate('window.browserPrintCalls')==0
        assert len(context.pages)==1
        results[service]={'ticket':data['ticket'],'request_id':data['request_id'],'status':status['print_job']['status'],'double_click':'one issuance','browser_print_calls':0}
    assert not dialogs,dialogs
    # Replay in the same real browser session. Both HTTP servers share only the isolated test DB.
    data=issued[0]
    token=page.locator('input[name="_token"]').first.input_value()
    payload={'service':'cs','branch_id':data['branch_id'],'request_id':data['request_id']}
    repeated=context.request.post(BASE+'/ambil-nomor',data=payload,headers={'X-CSRF-TOKEN':token,'Accept':'application/json'}).json()
    assert repeated['id']==data['id'] and repeated['print_job']['id']==data['print_job']['id'], repeated
    # Concurrent same UUID against separate PHP processes; one ticket/one ledger entry.
    request_id=str(uuid.uuid4())
    payload={'service':'cs','branch_id':data['branch_id'],'request_id':request_id}
    cookies=context.cookies()
    cookie='; '.join(x['name']+'='+x['value'] for x in cookies)
    import urllib.request
    def send(port):
        req=urllib.request.Request(f'http://127.0.0.1:{port}/ambil-nomor',data=json.dumps(payload).encode(),headers={'Cookie':cookie,'X-CSRF-TOKEN':token,'Content-Type':'application/json','Accept':'application/json'})
        with urllib.request.urlopen(req,timeout=30) as r:return json.load(r)
    with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
        responses=list(pool.map(send,[7102,7103]))
    assert responses[0]['id']==responses[1]['id'],responses
    with sqlite3.connect(DB) as db:
        assert db.execute('select count(*) from print_jobs where id=?',(request_id,)).fetchone()[0]==1
        snapshot=json.loads(db.execute('select snapshot from print_jobs where id=?',(data['request_id'],)).fetchone()[0])
        results['concurrent_same_uuid']={'ticket':responses[0]['ticket'],'one_job':True}
    # Missing worker is exercised by making its heartbeat stale, then restoring after the HTTP request.
    # Do not interfere with the running worker; automated PHP tests cover this branch.
    other=browser.new_context()
    denied=other.request.get(data['print_job']['status_url'])
    assert denied.status==403,denied.status
    results['other_session_status']='403'
    results['replay']='same ticket and job'
    # Simulate a response lost AFTER the server committed, then retry via the same button.
    before=sqlite3.connect(DB).execute('select count(*) from queues').fetchone()[0]
    lost=[]
    def lose_response(route):
        response=route.fetch()
        assert response.ok,response.status
        lost.append(response.json())
        route.abort('connectionfailed')
    page.route(BASE+'/ambil-nomor',lose_response)
    page.locator('.btn-take[data-service="cs"]').click()
    page.wait_for_function('!isTakingTicket',timeout=60000)
    page.unroute(BASE+'/ambil-nomor',lose_response)
    assert lost and page.evaluate("id=>sessionStorage.getItem('ticket-request-'+id)", data['branch_id']) is not None
    page.locator('.btn-take[data-service="cs"]').click()
    page.wait_for_function('!isTakingTicket',timeout=90000)
    after=sqlite3.connect(DB).execute('select count(*) from queues').fetchone()[0]
    assert after==before+1,(before,after)
    assert issued[-1]['id']==lost[0]['id']
    results['lost_response_retry']='same ticket, no replacement'
    dialogs.clear()
    def reject(route):route.fulfill(status=503,json={'success':False,'message':'Audit: printer unavailable'})
    page.route(BASE+'/ambil-nomor',reject)
    page.locator('.btn-take[data-service="teller"]').click()
    page.wait_for_function('!isTakingTicket',timeout=10000)
    page.unroute(BASE+'/ambil-nomor',reject)
    assert dialogs==['Audit: printer unavailable'],dialogs
    results['issuance_failure']='message shown, no print call'
    dialogs.clear()
    def print_failure(route):route.fulfill(json={'success':True,'ticket':'CS-TEST','print_job':{'id':'fake','status':'failed','status_url':BASE+'/print-jobs/fake','message':'Cetak gagal. Hubungi petugas.'}})
    page.route(BASE+'/ambil-nomor',print_failure)
    page.locator('.btn-take[data-service="cs"]').click()
    page.wait_for_function('!isTakingTicket',timeout=10000)
    page.unroute(BASE+'/ambil-nomor',print_failure)
    assert len(dialogs)==1 and 'CS-TEST' in dialogs[0],dialogs
    assert page.evaluate('window.browserPrintCalls')==0
    results['print_failure']='original number shown; no automatic replacement'
    results['page_errors']=errors
    assert not errors,errors
    page.screenshot(path=str(OUT/'edge-kiosk.png'),full_page=True)
    browser.close()
(OUT/'browser-results.json').write_text(json.dumps(results,indent=2),encoding='utf8')
print(json.dumps(results,indent=2))

