"""Archive proven retired files outside the project; never delete unlisted paths."""
from pathlib import Path
import hashlib
import json
import os
import subprocess
import zipfile

root = Path(__file__).resolve().parents[2]
backup_parent = (Path(os.environ['LOCALAPPDATA']) / 'BankDPTaspenBackups').resolve()
archive_dir = backup_parent / 'operations-cleanup-20261028'
assert not archive_dir.is_relative_to(root)
archive_dir.mkdir(parents=True, exist_ok=True)
account = subprocess.check_output(['whoami'], text=True).strip()
subprocess.run(['icacls', str(archive_dir), '/inheritance:r', '/grant:r', account + ':(OI)(CI)F', '*S-1-5-18:(OI)(CI)F'], check=True, stdout=subprocess.DEVNULL)
groups = {
    'Retired widget; dashboard and branch detail now supply the supported summaries.': [
        'app/Filament/Widgets/ControlCenterStaffWidget.php', 'app/Filament/Widgets/RecentQueueActivityWidget.php',
        'app/Filament/Widgets/QueueStatsWidget.php', 'app/Filament/Widgets/QueueOperationsWidget.php',
        'resources/views/filament/widgets/queue-operations.blade.php'],
    'Unrouted legacy controller; authentication and account management use Filament.': [
        'app/Http/Controllers/AdminAuthController.php', 'app/Http/Controllers/AdminUserController.php',
        'app/Http/Controllers/AdminDashboardController.php'],
    'Unreferenced backup/preview image; active production assets are preserved.': [
        'public/images/bank-dp-taspen-logo-backup.png', 'public/images/bank-dp-taspen-logo-original.png',
        'public/images/bg-ambil-nomor-dark-backup.png', 'public/kiosk_preview.png'],
    'Superseded generated test report; retained in external archive, new acceptance reports replace it.': [
        'scratch/branch-print-full-tests.txt', 'scratch/branch-print-page-tests.txt',
        'scratch/branch-print-tests.txt', 'scratch/device-history-audit-regression.xml'],
    'Explicit transparent SVG now used; compatibility favicon route redirects to that asset.': ['public/favicon.ico'],
}
candidates = {path for paths in groups.values() for path in paths}
entries = []
for reason, paths in groups.items():
    for relative in paths:
        path = (root / relative).resolve()
        assert path.is_relative_to(root) and path.is_file(), relative
        pattern = path.stem if path.suffix == '.php' else path.name
        if path.name == 'queue-operations.blade.php':
            pattern = 'filament.widgets.queue-operations'
        scan = subprocess.run(['rg', '-l', '-F', pattern, 'app', 'bootstrap', 'config', 'resources', 'routes', 'scripts', 'tests'],
                              cwd=root, capture_output=True, text=True)
        assert scan.returncode in (0, 1), scan.stderr
        matches = [line.replace('\\', '/') for line in scan.stdout.splitlines()]
        active = [match for match in matches if match not in candidates]
        if path.name == 'favicon.ico':
            active = [match for match in active if match != 'routes/web.php']
        assert not active, (relative, active)
        entries.append({'path': relative, 'reason': reason, 'reference_scan': 'no active references outside retired candidates',
                        'matched_retired_files': matches, 'sha256': hashlib.sha256(path.read_bytes()).hexdigest(), 'bytes': path.stat().st_size})
manifest = {'date': '2026-10-28', 'archive': str(archive_dir / 'retired-files.zip'), 'files': entries,
            'preserved': ['all migrations', 'database', '.env and active configuration', 'vendor and node_modules',
                          'printing helpers', 'agent', 'android-print-agent including signing keys', 'active source test harnesses']}
# Manifest must exist before any removal.
manifest_path = root / 'docs/CLEANUP-MANIFEST-2026-10-28.json'
manifest_path.write_text(json.dumps(manifest, indent=2), encoding='utf-8')
(archive_dir / 'manifest.json').write_text(json.dumps(manifest, indent=2), encoding='utf-8')
archive_path = archive_dir / 'retired-files.zip'
assert not archive_path.exists(), 'Archive already exists; do not overwrite the recovery copy.'
with zipfile.ZipFile(archive_path, 'x', zipfile.ZIP_DEFLATED) as archive:
    for entry in entries:
        archive.write(root / entry['path'], entry['path'])
with zipfile.ZipFile(archive_path) as archive:
    for entry in entries:
        assert hashlib.sha256(archive.read(entry['path'])).hexdigest() == entry['sha256']
for entry in entries:
    path = (root / entry['path']).resolve()
    assert path.is_relative_to(root) and hashlib.sha256(path.read_bytes()).hexdigest() == entry['sha256']
    path.unlink()
print(json.dumps({'archived_files': len(entries), 'archive': str(archive_path), 'manifest': str(manifest_path)}))
