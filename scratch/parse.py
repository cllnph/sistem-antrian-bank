import re

with open('scratch/Session_13433830319908344', 'rb') as f:
    data = f.read()

urls = re.findall(b'https?://[a-zA-Z0-9./?=_&%#-]+', data)
for u in sorted(set(urls)):
    print(u.decode('latin1'))
