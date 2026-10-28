from pathlib import Path
import json
import sqlite3
from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'scratch/branch-agent-audit'
database = ROOT / 'scratch/device-history-audit/private/app.sqlite'
assert database.is_file(), 'Isolated SQLite fixture missing'
with sqlite3.connect(database) as db:
    db.row_factory = sqlite3.Row
    agent = db.execute("select * from print_agents where name='Audit installer Windows v1.0.2'").fetchone()
    assert agent is not None and agent['enabled'] == 0
    assert agent['printer_ready'] == 1 and agent['heartbeat_at'] is not None
    assert agent['enrollment_hash'] is None and agent['token_hash'] is not None
    assert db.execute('select count(*) from print_jobs').fetchone()[0] == 0

with sync_playwright() as p:
    browser = p.chromium.launch(channel='msedge', headless=True)
    context = browser.new_context(storage_state=str(OUT / 'private/windows-installer-session.json'))
    context.route('https://fonts.googleapis.com/**', lambda route: route.abort())
    context.route('https://fonts.gstatic.com/**', lambda route: route.abort())
    page = context.new_page()
    page.goto('http://127.0.0.1:7102/admin/print-agents', wait_until='domcontentloaded')
    row = page.get_by_role('row').filter(has_text='Audit installer Windows v1.0.2')
    row.get_by_text('Siap', exact=True).wait_for()
    assert row.get_by_text('Belum diaktifkan', exact=True).is_visible()
    page.screenshot(path=str(OUT / 'windows-installer-ready.png'), full_page=True)
    browser.close()

result = {'installer_version': '1.0.2', 'full_installation': 'passed', 'heartbeat': 'passed',
          'native_admin_connection_status': 'Siap', 'branch_routing_enabled': False,
          'physical_print': 'not requested', 'production_database': 'untouched'}
(OUT / 'windows-installer-runtime-results.json').write_text(json.dumps(result, indent=2), encoding='utf-8')
print(json.dumps(result, indent=2))
