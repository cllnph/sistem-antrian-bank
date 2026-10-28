from pathlib import Path
import concurrent.futures, http.cookiejar, json, re, sqlite3, subprocess, time, urllib.request, uuid

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'scratch/branch-agent-audit'
PRIVATE = OUT / 'private'
DATABASE = ROOT / 'scratch/filament-native-ui.sqlite'
FIXTURES = json.loads((PRIVATE / 'fixtures.json').read_text())
BASE = 'http://127.0.0.1:7102'
WEB = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
WEB_TOKEN = ''
results = {'physical_print': 'not sent: both local Sumatra executables are mocks'}

def api(config, path, body=None, claim=None):
    headers = {'Content-Type':'application/json', 'Accept':'application/json', 'X-Print-Agent':config['agent_id'],
               'X-Print-Device':config['device_id'], 'Authorization':'Bearer '+config['token']}
    if claim: headers['X-Print-Claim'] = claim
    request = urllib.request.Request(BASE+path, data=json.dumps(body or {}).encode(), headers=headers)
    with urllib.request.urlopen(request, timeout=25) as response: return json.load(response)

def heartbeat(config):
    api(config, '/api/print-agent/heartbeat', {'printer_ready':True,'printer_name':config['printer_name'],'machine_name':'AUDIT','version':'1.0.0'})

def issue(config, service='cs'):
    global WEB_TOKEN
    heartbeat(config)
    with WEB.open(BASE+'/ambil-nomor/'+config['branch_code'],timeout=25) as page:
        WEB_TOKEN=re.search(r'name="_token"\s+value="([^"]+)"',page.read().decode()).group(1)
    request = urllib.request.Request(BASE+'/ambil-nomor/'+config['branch_code'], data=json.dumps({'service':service,'request_id':str(uuid.uuid4())}).encode(),
                                     headers={'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':WEB_TOKEN})
    with WEB.open(request,timeout=25) as response: return json.load(response)

def run_agent(slug):
    directory = PRIVATE / slug
    # The real program is NEVER copied here; mock executable writes an invocation record only.
    assert (directory/'SumatraPDF.exe').read_bytes() == (PRIVATE/'MockSumatra.exe').read_bytes()
    run = subprocess.run(['powershell.exe','-NoProfile','-NonInteractive','-ExecutionPolicy','Bypass','-File',str(directory/'Start-Agent.ps1'),
                          '-DataDirectory',str(directory),'-Once'],capture_output=True,text=True,timeout=70)
    assert run.returncode == 0, (slug,run.stdout,run.stderr)
    return run

def job_status(job_id):
    with sqlite3.connect(DATABASE) as db:
        return db.execute('select status,failure_code from print_jobs where id=?',(job_id,)).fetchone()

def calls(slug):
    path=PRIVATE/slug/'native-calls.txt'
    return len(path.read_text().splitlines()) if path.exists() else 0

before={slug:calls(slug) for slug in FIXTURES}
issued={slug:issue(config,service) for slug,config,service in [('bogor',FIXTURES['bogor'],'cs'),('depok',FIXTURES['depok'],'teller')]}
with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool: list(pool.map(run_agent,FIXTURES))
for slug,data in issued.items():
    assert job_status(data['request_id'])[0]=='submitted',(slug,job_status(data['request_id']), (PRIVATE/slug/'agent.log').read_text() if (PRIVATE/slug/'agent.log').exists() else '')
    assert calls(slug)==before[slug]+1
    results[slug]={'number':data['ticket'],'status':'submitted','one_native_call':True}
    run_agent(slug)
    assert calls(slug)==before[slug]+1
print('PASS two real PowerShell clients: isolated routing and one native call each',flush=True)

# Crash after receiving one-time start permission: journal recovery must ACK unknown, never print.
config=FIXTURES['bogor']; issued_crash=issue(config)
claimed=api(config,'/api/print-agent/claim')['job']
assert claimed['id']==issued_crash['request_id']
headers={'X-Print-Agent':config['agent_id'],'X-Print-Device':config['device_id'],'Authorization':'Bearer '+config['token'],'X-Print-Claim':claimed['claim_token']}
with urllib.request.urlopen(urllib.request.Request(BASE+'/api/print-agent/jobs/'+claimed['id']+'/pdf',headers=headers),timeout=25) as response:
    pdf_hash=response.headers['X-Pdf-Sha256']
api(config,'/api/print-agent/jobs/'+claimed['id']+'/start',{'pdf_sha256':pdf_hash},claimed['claim_token'])
journal=PRIVATE/'bogor'/'journal';journal.mkdir(exist_ok=True)
(journal/(claimed['id']+'.json')).write_text(json.dumps({'id':claimed['id'],'claim_token':claimed['claim_token'],'phase':'started','result':'unknown','failure_code':None}))
count=calls('bogor');run_agent('bogor')
assert job_status(claimed['id'])[0]=='unknown' and calls('bogor')==count
results['restart_after_start']='unknown, no native replay'
print('PASS client restart after start: no duplicate print',flush=True)

# Native helper timeout: one call, unknown status, no automatic replay.
timeout_job=issue(config)
(PRIVATE/'bogor'/'mock-timeout').touch()
try:run_agent('bogor')
finally:(PRIVATE/'bogor'/'mock-timeout').unlink()
assert job_status(timeout_job['request_id'])[0]=='unknown',job_status(timeout_job['request_id'])
count=calls('bogor');run_agent('bogor');assert calls('bogor')==count
results['native_timeout']='unknown, no automatic replay'
print('PASS native timeout and no automatic replay',flush=True)

# Agent outage preflight must not issue a ticket.
with sqlite3.connect(DATABASE) as db:
    before=db.execute('select count(*) from queues').fetchone()[0]
    db.execute("update print_agents set heartbeat_at='2000-01-01 00:00:00' where id=?",(config['agent_id'],))
request=urllib.request.Request(BASE+'/ambil-nomor/'+config['branch_code'], data=json.dumps({'service':'cs','request_id':str(uuid.uuid4())}).encode(),headers={'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':WEB_TOKEN})
try:WEB.open(request,timeout=25);raise AssertionError('Offline agent accepted issuance')
except urllib.error.HTTPError as error:assert error.code==503,error.code
with sqlite3.connect(DATABASE) as db:assert db.execute('select count(*) from queues').fetchone()[0]==before
results['agent_outage']='503, no ticket issued'
(OUT/'client-results.json').write_text(json.dumps(results,indent=2),encoding='utf8')
print(json.dumps(results,indent=2))
