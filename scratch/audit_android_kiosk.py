"""Read-only real kiosk HTML; all issuance/status calls are mocked, no physical print."""
from pathlib import Path
import json
from playwright.sync_api import sync_playwright

ROOT=Path(__file__).resolve().parents[1]
OUT=ROOT/'scratch/android-print-audit'; OUT.mkdir(exist_ok=True)
BASE='http://192.168.11.60:7100'
results=[]
with sync_playwright() as p:
    browser=p.chromium.launch(channel='msedge',headless=True)
    for device,width,height in [('Android phone',360,800),('Android tablet landscape',1024,768),('Android tablet portrait',800,1280)]:
        context=browser.new_context(viewport={'width':width,'height':height},is_mobile=True,has_touch=True,
            user_agent='Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36')
        context.route('https://fonts.googleapis.com/**',lambda route:route.abort())
        context.route('https://fonts.gstatic.com/**',lambda route:route.abort())
        page=context.new_page(); errors=[]; issued=[]; dialogs=[]
        page.add_init_script("window.printCalls=0;window.print=()=>{window.printCalls++;throw new Error('Browser print forbidden')};Object.defineProperty(AbortSignal,'timeout',{value:undefined});")
        page.on('pageerror',lambda error:errors.append(str(error)))
        page.on('dialog',lambda dialog:(dialogs.append(dialog.message),dialog.dismiss()))
        def issue(route):
            data=route.request.post_data_json;issued.append(data)
            route.fulfill(json={'success':True,'ticket':'CS-TEST' if data['service']=='cs' else 'T-TEST',
                'print_job':{'id':data['request_id'],'status':'queued','status_url':BASE+'/print-jobs/'+data['request_id']}})
        page.route(BASE+'/ambil-nomor',issue)
        page.route(BASE+'/print-jobs/*',lambda route:route.fulfill(json={'print_job':{'status':'submitted','status_url':route.request.url}}))
        page.goto(BASE+'/ambil-nomor/002',wait_until='domcontentloaded',timeout=60000)
        for index,service in enumerate(['cs','teller'],1):
            button=page.locator('.btn-take[data-service="'+service+'"]');button.scroll_into_view_if_needed()
            box=button.bounding_box();assert box and box['height']>=44
            button.tap()
            page.evaluate("s=>document.querySelector('.btn-take[data-service='+s+']').click()",service)
            page.wait_for_function('!isTakingTicket',timeout=20000)
            assert len(issued)==index and issued[-1]['service']==service
        dimensions=page.evaluate('({width:innerWidth,scroll:document.documentElement.scrollWidth,body:document.body.scrollWidth})')
        assert max(dimensions['scroll'],dimensions['body'])<=dimensions['width']+1,dimensions
        assert page.evaluate('window.printCalls')==0 and len(context.pages)==1 and not dialogs and not errors,(dialogs,errors)
        page.screenshot(path=str(OUT/(str(width)+'x'+str(height)+'.png')),full_page=True)
        results.append({'device':device,'size':[width,height],'cs_teller':'passed','double_tap':'one issuance per service','without_AbortSignal_timeout':'passed','browser_print_calls':0,'page_errors':errors})
        context.close()
    browser.close()
(OUT/'browser-results.json').write_text(json.dumps(results,indent=2),encoding='utf8')
print(json.dumps(results,indent=2))
