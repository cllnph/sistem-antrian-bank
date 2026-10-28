package id.bankdptaspen.printagent.core;

import java.io.*;
import java.net.*;
import java.nio.charset.StandardCharsets;
import java.util.*;
import java.util.concurrent.*;
import java.security.*;
import java.security.cert.*;
import javax.net.ssl.*;

/** IPP/1.1, RFC 8010. Uses one socket per operation: no redirect or POST replay. */
public final class Ipp {
    public static final int PRINT_JOB = 2, GET_ATTRIBUTES = 11;
    public static final class Value {
        public final int tag;
        public final byte[] bytes;
        public Value(int tag, byte[] bytes) { this.tag=tag; this.bytes=bytes; }
        public String text() { return new String(bytes, StandardCharsets.UTF_8); }
        public int number() { if (bytes.length!=4) return -1; return ((bytes[0]&255)<<24)|((bytes[1]&255)<<16)|((bytes[2]&255)<<8)|(bytes[3]&255); }
    }
    public static final class Response {
        public final int status, requestId;
        public final Map<String,List<Value>> attributes = new LinkedHashMap<>();
        Response(int status,int requestId) { this.status=status; this.requestId=requestId; }
        public String first(String key) { List<Value> list=attributes.get(key); return list==null||list.isEmpty()?"":list.get(0).text(); }
        public int number(String key) { List<Value> list=attributes.get(key); return list==null||list.isEmpty()?-1:list.get(0).number(); }
        public boolean contains(String key,String value) { for(Value item:attributes.getOrDefault(key,Collections.emptyList())) if(item.text().equals(value)) return true; return false; }
    }
    public static final class Request {
        final ByteArrayOutputStream buffer = new ByteArrayOutputStream();
        final DataOutputStream out = new DataOutputStream(buffer);
        public final int requestId;
        public Request(int operation,int requestId,String printerUri) throws IOException {
            this.requestId=requestId; out.writeShort(0x0101); out.writeShort(operation); out.writeInt(requestId); group(1);
            text(0x47,"attributes-charset","utf-8"); text(0x48,"attributes-natural-language","en"); text(0x45,"printer-uri",printerUri);
        }
        public Request group(int tag) throws IOException { out.writeByte(tag); return this; }
        public Request value(int tag,String name,byte[] bytes) throws IOException {
            byte[] key=name.getBytes(StandardCharsets.UTF_8);
            if(key.length>65535||bytes.length>65535) throw new IOException("IPP attribute too long");
            out.writeByte(tag); out.writeShort(key.length); out.write(key); out.writeShort(bytes.length); out.write(bytes); return this;
        }
        public Request text(int tag,String name,String text) throws IOException { return value(tag,name,text.getBytes(StandardCharsets.UTF_8)); }
        public Request integer(int tag,String name,int number) throws IOException {
            ByteArrayOutputStream b=new ByteArrayOutputStream(); new DataOutputStream(b).writeInt(number); return value(tag,name,b.toByteArray());
        }
        public Request bool(String name,boolean value) throws IOException { return value(0x22,name,new byte[]{(byte)(value?1:0)}); }
        public Request resolution(String name,int dpi) throws IOException {
            ByteArrayOutputStream b=new ByteArrayOutputStream(); DataOutputStream d=new DataOutputStream(b); d.writeInt(dpi); d.writeInt(dpi); d.writeByte(3); return value(0x32,name,b.toByteArray());
        }
        public byte[] finish() throws IOException { out.writeByte(3); return buffer.toByteArray(); }
    }
    public static Response decode(byte[] data) throws IOException {
        if(data.length<9||data.length>262144) throw new IOException("Respons IPP tidak valid.");
        DataInputStream in=new DataInputStream(new ByteArrayInputStream(data));
        int major=in.readUnsignedByte(); in.readUnsignedByte();
        if(major<1||major>2) throw new IOException("Versi IPP tidak didukung.");
        Response response=new Response(in.readUnsignedShort(),in.readInt()); String name="";
        while(true) {
            int tag=in.readUnsignedByte();
            if(tag==3) return response;
            if(tag<16) { name=""; continue; }
            int size=in.readUnsignedShort(); byte[] key=new byte[size]; in.readFully(key);
            if(size>0) name=new String(key,StandardCharsets.UTF_8);
            int length=in.readUnsignedShort(); byte[] bytes=new byte[length]; in.readFully(bytes);
            if(!name.isEmpty()) response.attributes.computeIfAbsent(name,k->new ArrayList<>()).add(new Value(tag,bytes));
        }
    }
    public static URI printerUri(String address) throws IOException {
        try {
            URI uri=new URI(address.trim());
            if(!Arrays.asList("ipp","ipps").contains(uri.getScheme())||uri.getHost()==null||uri.getUserInfo()!=null||uri.getQuery()!=null||uri.getFragment()!=null||uri.getPort()>65535||uri.getPort()==0)
                throw new IOException("Alamat printer harus seperti ipp://192.168.1.100:631/ipp/print.");
            for(InetAddress ip:InetAddress.getAllByName(uri.getHost())) {
                if(!(ip.isSiteLocalAddress()||ip.isLinkLocalAddress())||ip.isMulticastAddress()||ip.isLoopbackAddress())
                    throw new IOException("Gunakan alamat printer pada jaringan lokal Wi-Fi.");
            }
            return uri;
        } catch(URISyntaxException e) { throw new IOException("Alamat printer tidak valid."); }
    }
    public static Response send(URI uri,byte[] attributes,File document,int timeoutMs) throws IOException {
        return send(uri,attributes,document,timeoutMs,null);
    }
    public static Response send(URI uri,byte[] attributes,File document,int timeoutMs,String fingerprint) throws IOException {
        return exchange(uri, attributes, document, timeoutMs, false, fingerprint);
    }
    // Loopback is allowed only by the JVM test harness, never the Android settings UI.
    static Response exchange(URI uri,byte[] attributes,File document,int timeoutMs,boolean testLoopback) throws IOException {
        return exchange(uri,attributes,document,timeoutMs,testLoopback,null);
    }
    private static Response exchange(URI uri,byte[] attributes,File document,int timeoutMs,boolean testLoopback,String fingerprint) throws IOException {
        if(!testLoopback) printerUri(uri.toString());
        int port=uri.getPort()<0?631:uri.getPort();
        Socket socket=uri.getScheme().equals("ipps")?tlsFactory(fingerprint,false).createSocket():new Socket();
        ScheduledExecutorService watchdog=Executors.newSingleThreadScheduledExecutor();
        ScheduledFuture<?> deadline=watchdog.schedule(()->{try{socket.close();}catch(IOException ignored){}},timeoutMs,TimeUnit.MILLISECONDS);
        try {
            socket.connect(new InetSocketAddress(uri.getHost(),port),Math.min(5000,timeoutMs)); socket.setSoTimeout(timeoutMs);
            if(socket instanceof SSLSocket) {
                SSLParameters parameters=((SSLSocket)socket).getSSLParameters();
                if(fingerprint==null||fingerprint.isEmpty()) parameters.setEndpointIdentificationAlgorithm("HTTPS");
                ((SSLSocket)socket).setSSLParameters(parameters); ((SSLSocket)socket).startHandshake();
            }
            String path=uri.getRawPath(); if(path==null||path.isEmpty()) path="/ipp/print";
            long length=attributes.length+(document==null?0:document.length());
            OutputStream output=socket.getOutputStream();
            output.write(("POST "+path+" HTTP/1.1\r\nHost: "+uri.getHost()+":"+port+"\r\nContent-Type: application/ipp\r\nContent-Length: "+length+"\r\nConnection: close\r\n\r\n").getBytes(StandardCharsets.US_ASCII));
            output.write(attributes);
            if(document!=null) { try(InputStream file=new FileInputStream(document)) { byte[] block=new byte[8192]; int n; while((n=file.read(block))!=-1) output.write(block,0,n); } }
            output.flush();
            BufferedInputStream input=new BufferedInputStream(socket.getInputStream());
            String status=line(input);
            for(int interim=0;status.matches("HTTP/1\\.[01] 100(?: .*|)");interim++) {
                if(interim>=4) throw new IOException("Terlalu banyak respons HTTP sementara.");
                int size=0; String header;
                while(!(header=line(input)).isEmpty()) { size+=header.length(); if(size>16384) throw new IOException("HTTP header terlalu besar."); }
                status=line(input);
            }
            if(status.matches("HTTP/1\\.[01] 426(?: .*|)")) throw new IOException("Printer mewajibkan koneksi terenkripsi. Gunakan ipps://IP:631/ipp/print lalu pasangkan ulang.");
            if(!status.matches("HTTP/1\\.[01] 200(?: .*|)")) throw new IOException("Printer menolak koneksi HTTP.");
            Map<String,String> headers=new HashMap<>(); int headerBytes=0;
            for(String h;(h=line(input)).length()>0;) { headerBytes+=h.length(); if(headerBytes>16384) throw new IOException("HTTP header terlalu besar."); int colon=h.indexOf(':'); if(colon>0) headers.put(h.substring(0,colon).trim().toLowerCase(Locale.ROOT),h.substring(colon+1).trim()); }
            if(!headers.getOrDefault("content-type", "").toLowerCase(Locale.ROOT).startsWith("application/ipp")) throw new IOException("Printer tidak mengembalikan IPP.");
            ByteArrayOutputStream bytes=new ByteArrayOutputStream();
            if("chunked".equalsIgnoreCase(headers.get("transfer-encoding"))) {
                while(true) { String chunk=line(input).split(";",2)[0]; int count; try{count=Integer.parseInt(chunk,16);}catch(NumberFormatException e){throw new IOException("HTTP chunk tidak valid.");} if(count==0) break; copyExact(input,bytes,count); if(!line(input).isEmpty()) throw new IOException("HTTP chunk tidak valid."); }
            } else if(headers.containsKey("content-length")) {
                int count; try{count=Integer.parseInt(headers.get("content-length"));}catch(NumberFormatException e){throw new IOException("HTTP length tidak valid.");} copyExact(input,bytes,count);
            } else { int value; while((value=input.read())!=-1) { if(bytes.size()>=262144) throw new IOException("Respons printer terlalu besar."); bytes.write(value); } }
            Response response=decode(bytes.toByteArray());
            int requestId=new DataInputStream(new ByteArrayInputStream(attributes,4,4)).readInt();
            if(response.requestId!=requestId) throw new IOException("ID respons IPP tidak cocok.");
            if(response.status>255) throw new IOException("Printer menolak tugas (IPP "+Integer.toHexString(response.status)+").");
            return response;
        } finally { deadline.cancel(false); watchdog.shutdownNow(); socket.close(); }
    }
    /** Handshake only: reads the public certificate for explicit user approval.
     * No IPP/HTTP request, document, or API credential is transmitted here. */
    public static String certificateFingerprint(URI uri) throws IOException {
        printerUri(uri.toString()); if(!"ipps".equals(uri.getScheme())) throw new IOException("IPPS diperlukan untuk sertifikat printer.");
        try(SSLSocket socket=(SSLSocket)tlsFactory(null,true).createSocket()) {
            socket.connect(new InetSocketAddress(uri.getHost(),uri.getPort()<0?631:uri.getPort()),5000); socket.setSoTimeout(10000); socket.startHandshake();
            return fingerprint((X509Certificate)socket.getSession().getPeerCertificates()[0]);
        } catch(CertificateException e) { throw new IOException("Sertifikat printer tidak dapat dibaca.",e); }
    }
    private static SSLSocketFactory tlsFactory(String expected,boolean inspectOnly) throws IOException {
        if((expected==null||expected.isEmpty())&&!inspectOnly) return (SSLSocketFactory)SSLSocketFactory.getDefault();
        if(!inspectOnly&&!expected.matches("[a-f0-9]{64}")) throw new IOException("Sidik jari sertifikat printer tidak valid.");
        try {
            SSLContext context=SSLContext.getInstance("TLS");
            context.init(null,new TrustManager[]{new X509TrustManager(){
                public X509Certificate[] getAcceptedIssuers(){return new X509Certificate[0];}
                public void checkClientTrusted(X509Certificate[] chain,String type) throws CertificateException {throw new CertificateException("Client authentication unavailable");}
                public void checkServerTrusted(X509Certificate[] chain,String type) throws CertificateException {
                    if(chain==null||chain.length==0) throw new CertificateException("Missing printer certificate");
                    if(!inspectOnly&&!MessageDigest.isEqual(expected.getBytes(StandardCharsets.US_ASCII),fingerprint(chain[0]).getBytes(StandardCharsets.US_ASCII)))
                        throw new CertificateException("Sertifikat printer berubah. Periksa perangkat lalu pasangkan ulang.");
                }
            }},new SecureRandom()); return context.getSocketFactory();
        } catch(GeneralSecurityException e) {throw new IOException("TLS printer tidak tersedia.",e);}
    }
    private static String fingerprint(X509Certificate certificate) throws CertificateException {
        try {StringBuilder out=new StringBuilder();for(byte b:MessageDigest.getInstance("SHA-256").digest(certificate.getEncoded()))out.append(String.format(Locale.ROOT,"%02x",b&255));return out.toString();}
        catch(NoSuchAlgorithmException e){throw new CertificateException(e);}
    }
    private static String line(InputStream input) throws IOException {
        ByteArrayOutputStream bytes=new ByteArrayOutputStream(); int c;
        while((c=input.read())!=-1) { if(c==10) return new String(bytes.toByteArray(),StandardCharsets.US_ASCII).replace("\r",""); if(bytes.size()>=8192) throw new IOException("HTTP line terlalu panjang."); bytes.write(c); }
        throw new EOFException("Koneksi printer terputus.");
    }
    private static void copyExact(InputStream in,ByteArrayOutputStream out,int count) throws IOException {
        if(count<0||count>262144-out.size()) throw new IOException("Respons printer terlalu besar.");
        for(int i=0;i<count;i++) { int b=in.read(); if(b<0) throw new EOFException("Respons printer terputus."); out.write(b); }
    }
}
