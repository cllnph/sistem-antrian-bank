from pathlib import Path
import re
import base64
from playwright.sync_api import sync_playwright
import fitz
source=Path('resources/views/queue/take-number.blade.php').read_text(encoding='utf-8')
css=source.split('<style>',1)[1].split('</style>',1)[0]
receipt=source.split('    <!-- Hidden on screen;',1)[1].split('    <!-- Bank DP Taspen Footer',1)[0]
receipt=receipt[receipt.index('<div'):]
receipt=re.sub(r'@if\(.*?\).*?@endif','',receipt)
receipt=re.sub(r'\{\{.*?\}\}', 'BOGOR T-001',receipt)
receipt=re.sub(r'src="[^"]*"', 'src="'+'data:image/png;base64,'+base64.b64encode(Path('public/images/bank-dp-taspen-logo.png').read_bytes()).decode()+'"',receipt)
html='<html><head><style>'+css+'</style></head><body>'+receipt+'</body></html>'
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path=r'C:\Program Files\Google\Chrome\Application\chrome.exe',headless=True)
    page=browser.new_page()
    page.emulate_media(media='print')
    page.set_content(html,wait_until='load')
    print(page.evaluate('''() => {const e=document.querySelector('.thermal-header').parentElement.parentElement.parentElement;const s=getComputedStyle(e);return {display:s.display,opacity:s.opacity,animation:s.animationName};}'''))
    page.screenshot(path='scratch/ticket-print-check.png', full_page=True)
    page.pdf(path='scratch/ticket-print-check.pdf',width='80mm',height='160mm',print_background=True)
    browser.close()
pdf=fitz.open('scratch/ticket-print-check.pdf')
text=''.join(page.get_text() for page in pdf)
assert 'NOMOR ANTREAN' in text and 'T-001' in text
assert 'http://' not in text and 'https://' not in text
assert any(page.get_images() for page in pdf), 'Bank logo missing in PDF'
print('PASS: visible ticket, queue number, embedded bank logo, no URL.')
