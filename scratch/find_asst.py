import os, re

fpath = os.path.expanduser('~') + r'/AppData/Local/Microsoft/Edge/User Data/Default/IndexedDB/https_claude.ai_0.indexeddb.leveldb/000019.log'
with open(fpath, 'rb') as f:
    data = f.read()

# search for assistant
matches = list(re.finditer(rb'" sender\:\assistant\', data))
print('Found assistant matches:', len(matches))
for i, m in enumerate(matches):
 start = m.start()
 end = min(len(data), start + 3000)
 print(f'=== MATCH {i} ===')
 with open(f'scratch/assistant_match_{i}.txt', 'w', encoding='utf-8') as out:
 out.write(data[start:end].decode('utf-8', 'replace'))
