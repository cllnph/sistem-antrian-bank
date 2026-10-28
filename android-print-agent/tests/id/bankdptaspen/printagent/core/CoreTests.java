package id.bankdptaspen.printagent.core;

import java.io.*;
import java.net.*;
import java.nio.*;
import java.nio.charset.StandardCharsets;
import java.util.*;
import java.util.concurrent.atomic.AtomicInteger;

public final class CoreTests {
    private static int tests;
    static void check(boolean value,String message) { if(!value) throw new AssertionError(message); }
    interface Test { void run() throws Exception; }
    static void test(String name,Test body) throws Exception { body.run(); tests++; System.out.println("PASS "+name); }
    static final class Fixture implements PrintEngine.Api,PrintEngine.Journal,PrintEngine.Printer {
        PrintEngine.Job pending,next=new PrintEngine.Job(); int starts,prints,acks; boolean prepareFail,startLost,printFail,ackLost,journalFail;
        Fixture() { next.id="test"; next.claim="claim"; next.ticket="CS-001"; }
        public PrintEngine.Job claim() { PrintEngine.Job value=next; next=null; return value; }
        public File pdf(PrintEngine.Job job) throws Exception { File file=File.createTempFile("agent-test-",".pdf"); try(FileOutputStream out=new FileOutputStream(file)){out.write("%PDF-test".getBytes());} job.hash="hash"; return file; }
        public void start(PrintEngine.Job job) throws Exception { starts++; if(startLost) throw new IOException("Lost start response"); }
        public void complete(PrintEngine.Job job) throws Exception { acks++; if(ackLost) throw new IOException("Lost ACK"); }
        public PrintEngine.Job load() { return pending; }
        public void save(PrintEngine.Job job) throws Exception { if(journalFail) throw new IOException("Disk full"); pending=job; }
        public void clear() { pending=null; }
        public File prepare(File pdf) throws Exception { if(prepareFail) throw new IOException("Render failed"); return pdf; }
        public void submit(PrintEngine.Job job,File pdf) throws Exception { prints++; if(printFail) throw new IOException("Timeout"); }
        PrintEngine engine() { return new PrintEngine(this,this,this); }
    }
    public static void main(String[] args) throws Exception {
        test("successful submission occurs once",()->{Fixture f=new Fixture(); f.engine().once(); f.engine().once(); check(f.starts==1&&f.prints==1&&f.acks==1&&f.pending==null,"Submission count");});
        test("render failure never starts printing",()->{Fixture f=new Fixture(); f.prepareFail=true; PrintEngine.Job job=f.next; f.engine().once(); check(f.starts==0&&f.prints==0&&job.status.equals("failed"),"Render failure");});
        test("native timeout remains unknown and does not replay",()->{Fixture f=new Fixture(); f.printFail=true; PrintEngine.Job job=f.next; f.engine().once(); f.engine().once(); check(f.prints==1&&job.status.equals("unknown"),"Timeout replay");});
        test("lost start response never grants native permission",()->{Fixture f=new Fixture(); f.startLost=true; PrintEngine.Job job=f.next; f.engine().once(); f.engine().once(); check(f.starts==1&&f.prints==0&&job.status.equals("unknown"),"Start replay");});
        test("lost acknowledgement retries only ACK",()->{Fixture f=new Fixture(); f.ackLost=true; try{f.engine().once();throw new AssertionError("Missing ACK error");}catch(IOException expected){} check(f.pending.status.equals("submitted"),"Durable submitted result"); f.ackLost=false; f.engine().once(); check(f.prints==1&&f.starts==1&&f.acks==2,"ACK replayed print");});
        test("restart with uncertain journal never prints it",()->{Fixture f=new Fixture(); f.pending=f.next; f.next=null; f.engine().once(); check(f.prints==0&&f.starts==0&&f.acks==1,"Crash replay");});
        test("journal failure prevents native start",()->{Fixture f=new Fixture(); f.journalFail=true; try{f.engine().once();throw new AssertionError("Missing disk error");}catch(IOException expected){} check(f.starts==0&&f.prints==0,"No durable journal");});
        test("IPP wire attributes and multivalued decoding",()->{
            Ipp.Request r=new Ipp.Request(0,17,"ipp://192.168.1.2:631/ipp/print"); r.group(4).text(0x49,"document-format-supported","application/pdf").text(0x49,"","image/pwg-raster").integer(0x23,"printer-state",3);
            Ipp.Response result=Ipp.decode(r.finish()); check(result.requestId==17&&result.number("printer-state")==3&&result.contains("document-format-supported","image/pwg-raster"),"IPP decoder");
        });
        test("malformed and truncated IPP rejected",()->{for(byte[] bytes:new byte[][]{{1,1,0},{1,1,0,0,0,0,0,1,0x44,0,10}}){try{Ipp.decode(bytes);throw new AssertionError("Malformed accepted");}catch(IOException expected){}}});
        test("printer URI only accepts local IPP",()->{
            for(String uri:new String[]{"https://192.168.1.2/ipp/print","ipp://127.0.0.1/ipp/print","ipp://8.8.8.8/ipp/print","ipp://user@192.168.1.2/ipp/print","ipp://192.168.1.2/ipp/print?x=1"}) { try{Ipp.printerUri(uri);throw new AssertionError("Unsafe URI accepted");}catch(IOException expected){} }
            check(Ipp.printerUri("ipp://192.168.1.2:631/ipp/print").getPort()==631,"Local printer URI");
        });
        test("PWG header and independent raster decoder",()->{
            int w=273,h=302; byte[][] rows=new byte[h][w*3]; Random random=new Random(77);
            for(int y=0;y<h;y++) { if(y<260) Arrays.fill(rows[y],(byte)255); else random.nextBytes(rows[y]); }
            Arrays.fill(rows[300],(byte)0); Arrays.fill(rows[301],(byte)0);
            ByteArrayOutputStream out=new ByteArrayOutputStream(); PwgRaster.write(out,w,h,300,(y,row)->System.arraycopy(rows[y],0,row,0,row.length)); byte[] data=out.toByteArray();
            check(new String(data,0,4,StandardCharsets.US_ASCII).equals("RaS2"),"Magic"); ByteBuffer header=ByteBuffer.wrap(data,4,1796).slice().order(ByteOrder.BIG_ENDIAN);
            check(header.getInt(372)==w&&header.getInt(376)==h&&header.getInt(392)==w*3&&header.getInt(400)==19,"Header");
            DataInputStream in=new DataInputStream(new ByteArrayInputStream(data,1800,data.length-1800)); int y=0;
            while(y<h) { int repeat=in.readUnsignedByte()+1; byte[] row=new byte[w*3]; int p=0;
                while(p<row.length) { int code=in.readUnsignedByte(); if(code<128) { byte[] pixel=new byte[3]; in.readFully(pixel); for(int i=0;i<=code;i++){System.arraycopy(pixel,0,row,p,3);p+=3;} }
                    else if(code>128) { int count=257-code;in.readFully(row,p,count*3);p+=count*3; } else throw new AssertionError("Reserved RLE code"); }
                for(int n=0;n<repeat;n++){check(y<h&&Arrays.equals(row,rows[y]),"Pixel mismatch at row "+y);y++;}
            } check(in.read()==-1,"Trailing raster bytes");
        });
        test("real socket sends once and honors deadline",()->{
            AtomicInteger connections=new AtomicInteger(); ServerSocket server=new ServerSocket(0,10,InetAddress.getLoopbackAddress());
            Thread receiver=new Thread(()->{try(Socket socket=server.accept()){connections.incrementAndGet();Thread.sleep(1000);}catch(Exception ignored){}}); receiver.start();
            URI uri=new URI("ipp://127.0.0.1:"+server.getLocalPort()+"/ipp/print"); byte[] request=new Ipp.Request(Ipp.PRINT_JOB,19,uri.toString()).finish(); long start=System.nanoTime();
            try{Ipp.exchange(uri,request,null,200,true);throw new AssertionError("Timeout expected");}catch(IOException expected){}
            check((System.nanoTime()-start)/1000000<900&&connections.get()==1,"Bounded one-shot socket"); server.close();receiver.join(1500);
        });
        test("real socket handles Continue and validates successful IPP response",()->{
            ServerSocket server=new ServerSocket(0,10,InetAddress.getLoopbackAddress());
            Thread receiver=new Thread(()->{try(Socket socket=server.accept()) {
                InputStream in=socket.getInputStream(); ByteArrayOutputStream head=new ByteArrayOutputStream(); int b;
                while((b=in.read())!=-1){head.write(b);byte[] h=head.toByteArray();int n=h.length;if(n>=4&&h[n-4]==13&&h[n-3]==10&&h[n-2]==13&&h[n-1]==10)break;}
                String header=new String(head.toByteArray(),StandardCharsets.US_ASCII); int length=0;
                for(String line:header.split("\r\n"))if(line.startsWith("Content-Length: "))length=Integer.parseInt(line.substring(16));
                byte[] body=new byte[length];new DataInputStream(in).readFully(body);check(body[3]==Ipp.PRINT_JOB,"Print operation");
                byte[] response=new Ipp.Request(0,21,"ipp://printer/ipp/print").group(2).integer(0x21,"job-id",123).finish();
                OutputStream out=socket.getOutputStream();out.write(("HTTP/1.1 100 Continue\r\n\r\nHTTP/1.1 200 OK\r\nContent-Type: application/ipp\r\nContent-Length: "+response.length+"\r\n\r\n").getBytes(StandardCharsets.US_ASCII));out.write(response);out.flush();
            }catch(Exception e){throw new RuntimeException(e);}});receiver.start();
            URI uri=new URI("ipp://127.0.0.1:"+server.getLocalPort()+"/ipp/print"); Ipp.Response response=Ipp.exchange(uri,new Ipp.Request(Ipp.PRINT_JOB,21,uri.toString()).finish(),null,2000,true);
            check(response.number("job-id")==123,"IPP job ID");server.close();receiver.join();
        });
        System.out.println(tests+" Android core tests passed; no physical printer used.");
    }
}
