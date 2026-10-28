"""Run a bounded, private browser fixture and always stop its PHP server."""
from pathlib import Path
import base64
import os
import secrets
import socket
import subprocess
import sys
import time

ROOT = Path(__file__).resolve().parents[1]
PRIVATE = ROOT / 'scratch/local-printing-v130/private'
PHP = Path('D:/php/php.exe')
PRIVATE.mkdir(parents=True, exist_ok=True)
assert PRIVATE.resolve().is_relative_to(ROOT.resolve())
account = subprocess.check_output(['whoami'], text=True).strip()
subprocess.run(['icacls', str(PRIVATE), '/inheritance:r', '/grant:r', account + ':(OI)(CI)F', '*S-1-5-18:(OI)(CI)F'], check=True, stdout=subprocess.DEVNULL)
database = PRIVATE / ('app-' + secrets.token_hex(4) + '.sqlite')
database.touch(exist_ok=False)
env = {**os.environ, 'APP_KEY': 'base64:' + base64.b64encode(secrets.token_bytes(32)).decode(),
       'APP_ENV': 'testing', 'APP_DEBUG': 'false', 'APP_URL': 'http://127.0.0.1:7114',
       'DB_CONNECTION': 'sqlite', 'DB_DATABASE': str(database), 'DB_URL': '', 'SESSION_DRIVER': 'file',
       'SESSION_COOKIE': 'bank_print130_audit', 'CACHE_STORE': 'array', 'KIOSK_TEST_MODE_ENABLED': 'true', 'PRINT_TEST_FAKE': 'true'}
subprocess.run([str(PHP), str(ROOT / 'scratch/setup_local_printing_v130.php')], env=env, cwd=ROOT, check=True, timeout=90)
router = ROOT / 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'
with (PRIVATE / 'server-output.log').open('w') as output, (PRIVATE / 'server-error.log').open('w') as errors:
    server = subprocess.Popen([str(PHP), '-S', '127.0.0.1:7114', str(router)], env=env, cwd=ROOT / 'public',
                              stdout=output, stderr=errors, creationflags=subprocess.CREATE_NO_WINDOW)
    try:
        deadline = time.monotonic() + 20
        while True:
            try:
                with socket.create_connection(('127.0.0.1', 7114), timeout=1):
                    break
            except OSError:
                if server.poll() is not None or time.monotonic() > deadline:
                    raise RuntimeError('Isolated server failed to start')
                time.sleep(0.1)
        with (PRIVATE / 'browser-error.log').open('w',encoding='utf-8') as audit_errors:
            audit = subprocess.run([sys.executable, str(ROOT / 'scratch/audit_local_printing_v130.py')], cwd=ROOT, stderr=audit_errors, timeout=1200)
            if audit.returncode:
                raise RuntimeError('Browser audit failed. Inspect scratch/local-printing-v130/private/browser-error.log.')
    finally:
        server.terminate()
        try:
            server.wait(timeout=10)
        except subprocess.TimeoutExpired:
            server.kill()
            server.wait(timeout=5)
        print('Isolated audit server stopped.', flush=True)
