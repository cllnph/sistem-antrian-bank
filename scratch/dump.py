import os

path = os.path.expanduser('~') + '/AppData/Local/Microsoft/Edge/User Data/Default/IndexedDB/https_claude.ai_0.indexeddb.leveldb/000019.log'
with open(path, 'rb') as f:
    data = f.read()

pos = 0
idx = 0
while True:
    pos = data.find(b'"sender":"assistant"', pos)
    if pos == -1:
        break
    idx += 1
    chunk = data[pos:pos+6000].decode('utf-8', 'replace')
    open(f'scratch/asst_{idx}.txt', 'w', encoding='utf-8').write(chunk)
    pos += 1000

print('Extracted', idx)
