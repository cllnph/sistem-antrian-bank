"""Read-only mDNS discovery for IPP printers; never submits a print job."""
import json, socket, struct, time

def name(packet, offset):
    labels=[]; end=None
    for _ in range(100):
        size=packet[offset]; offset+=1
        if size==0:
            return '.'.join(labels), end or offset
        if size&0xc0==0xc0:
            pointer=((size&63)<<8)|packet[offset]; end=end or offset+1; offset=pointer
        else:
            labels.append(packet[offset:offset+size].decode('utf-8',errors='replace')); offset+=size
    raise ValueError('DNS compression loop')

question=b''.join(bytes([len(s)])+s.encode() for s in '_ipp._tcp.local'.split('.'))+b'\0'+struct.pack('!HH',12,1)
query=struct.pack('!6H',0,0,1,0,0,0)+question
records=[]
with socket.socket(socket.AF_INET,socket.SOCK_DGRAM) as client:
    client.settimeout(.5); client.sendto(query,('224.0.0.251',5353)); deadline=time.monotonic()+5
    while time.monotonic()<deadline:
        try: packet,origin=client.recvfrom(65535)
        except socket.timeout: continue
        try:
            _,_,questions,answers,authority,additional=struct.unpack_from('!6H',packet); offset=12
            for _ in range(questions): _,offset=name(packet,offset); offset+=4
            for _ in range(answers+authority+additional):
                owner,offset=name(packet,offset); kind,cls,ttl,size=struct.unpack_from('!HHIH',packet,offset); offset+=10
                value=None
                if kind==1: value=socket.inet_ntoa(packet[offset:offset+4])
                elif kind==33: value={'port':struct.unpack_from('!H',packet,offset+4)[0],'host':name(packet,offset+6)[0]}
                elif kind==16:
                    parts=[]; p=offset
                    while p<offset+size: n=packet[p];p+=1;parts.append(packet[p:p+n].decode(errors='replace'));p+=n
                    value=parts
                if value is not None and (kind==1 or 'epson' in owner.lower() or 'l6290' in owner.lower() or 'itprinter' in owner.lower()):
                    record={'name':owner,'type':kind,'value':value,'source':origin[0]}
                    if record not in records: records.append(record)
                offset+=size
        except (ValueError,IndexError,struct.error): pass
print(json.dumps(records,indent=2))
