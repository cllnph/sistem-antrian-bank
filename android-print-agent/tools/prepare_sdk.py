"""Download pinned Android build tools from Google's SDK repository; verify hashes."""
from pathlib import Path
import hashlib, json, urllib.request, xml.etree.ElementTree as ET, zipfile

ROOT = Path(__file__).resolve().parents[1]
SDK = ROOT / '.sdk'
SDK.mkdir(exist_ok=True)
with urllib.request.urlopen('https://dl.google.com/android/repository/repository2-3.xml', timeout=45) as response:
    repository = ET.fromstring(response.read())

def tag(element):
    return element.tag.split('}')[-1]

def child(element, name):
    return next(x for x in element if tag(x) == name)

for package_path in ['platforms;android-35', 'build-tools;35.0.0']:
    package = next(x for x in repository if tag(x) == 'remotePackage' and x.attrib['path'] == package_path)
    target = SDK.joinpath(*package_path.split(';'))
    if (target / 'source.properties').exists():
        print('SDK already present:', package_path, flush=True)
        continue
    archives = child(package, 'archives')
    archive = next(x for x in archives if not any(tag(y) == 'host-os' for y in x) or child(x, 'host-os').text == 'windows')
    complete = child(archive, 'complete')
    remote = child(complete, 'url').text
    checksum = child(complete, 'checksum').text.strip()
    destination = SDK / remote
    print('Downloading official SDK:', package_path, flush=True)
    urllib.request.urlretrieve('https://dl.google.com/android/repository/' + remote, destination)
    actual = hashlib.sha1(destination.read_bytes()).hexdigest()
    if actual != checksum:
        raise RuntimeError('SDK checksum mismatch')
    target.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(destination) as bundle:
        for item in bundle.infolist():
            relative = Path(*Path(item.filename).parts[1:])
            if not relative.parts:
                continue
            path = target / relative
            if not path.resolve().is_relative_to(target.resolve()):
                raise RuntimeError('Invalid SDK archive path')
            if item.is_dir():
                path.mkdir(parents=True, exist_ok=True)
            else:
                path.parent.mkdir(parents=True, exist_ok=True)
                path.write_bytes(bundle.read(item))
    destination.unlink()
    print('SDK verified and extracted:', package_path, flush=True)
print('Android SDK ready.', flush=True)
