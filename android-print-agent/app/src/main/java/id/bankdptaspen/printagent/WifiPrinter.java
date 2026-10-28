package id.bankdptaspen.printagent;

import android.content.Context;
import android.graphics.*;
import android.graphics.pdf.*;
import android.os.ParcelFileDescriptor;
import java.io.*;
import java.net.URI;
import java.nio.*;
import java.util.*;
import id.bankdptaspen.printagent.core.*;

final class WifiPrinter implements PrintEngine.Printer {
    private final Context context; private final URI uri; private final String certificate;
    private int dpi=300,requestId=1; private boolean pwg;
    WifiPrinter(Context context,String address,String certificate) throws Exception { this.context=context; this.uri=Ipp.printerUri(address); this.certificate=certificate; }
    String check() throws Exception {
        Ipp.Request request=new Ipp.Request(Ipp.GET_ATTRIBUTES,requestId++,uri.toString());
        String[] keys={"printer-make-and-model","printer-state","printer-state-reasons","printer-is-accepting-jobs","document-format-supported","media-supported","pwg-raster-document-type-supported","pwg-raster-document-resolution-supported","printer-resolution-supported"};
        for(int i=0;i<keys.length;i++) request.text(0x44,i==0?"requested-attributes":"",keys[i]);
        Ipp.Response response=Ipp.send(uri,request.finish(),null,10000,certificate);
        List<Ipp.Value> accepting=response.attributes.get("printer-is-accepting-jobs");
        if(accepting==null||accepting.isEmpty()||accepting.get(0).bytes.length!=1||accepting.get(0).bytes[0]!=1||response.number("printer-state")==5)
            throw new IOException("Printer belum siap menerima tiket. Periksa daya, kertas, dan koneksi Wi-Fi.");
        if(!response.contains("media-supported","iso_a4_210x297mm")) throw new IOException("Printer harus mendukung kertas A4 untuk pengujian L6290.");
        pwg=response.contains("document-format-supported","image/pwg-raster");
        if(!pwg&&!response.contains("document-format-supported","application/pdf")) throw new IOException("Format IPP printer belum didukung: perlu PWG Raster atau PDF.");
        if(pwg) {
            if(!response.contains("pwg-raster-document-type-supported","srgb_8")) throw new IOException("Printer belum menyediakan PWG Raster sRGB 8-bit.");
            SortedSet<Integer> resolutions=new TreeSet<>();
            for(Ipp.Value value:response.attributes.getOrDefault("pwg-raster-document-resolution-supported",Collections.emptyList())) {
                if(value.bytes.length==9) { ByteBuffer b=ByteBuffer.wrap(value.bytes).order(ByteOrder.BIG_ENDIAN); int x=b.getInt(),y=b.getInt(); int units=b.get()&255;
                    if(units==3&&x==y&&x>=150&&x<=600) resolutions.add(x); }
            }
            if(resolutions.isEmpty()) throw new IOException("Resolusi raster printer belum didukung.");
            dpi=resolutions.contains(300)?300:resolutions.first();
        }
        String model=response.first("printer-make-and-model"); return model.isEmpty()?"Printer Wi-Fi siap.":model+" siap melalui Wi-Fi.";
    }
    @Override public File prepare(File pdf) throws Exception {
        check();
        File output=File.createTempFile("ticket-wifi-",pwg?".pwg":".pdf",context.getCacheDir()); boolean completed=false;
        try(ParcelFileDescriptor descriptor=ParcelFileDescriptor.open(pdf,ParcelFileDescriptor.MODE_READ_ONLY); PdfRenderer renderer=new PdfRenderer(descriptor)) {
            if(renderer.getPageCount()!=1) throw new IOException("PDF tiket harus tepat satu halaman.");
            try(PdfRenderer.Page page=renderer.openPage(0)) {
                if(page.getWidth()>600||page.getHeight()>850||page.getWidth()<1||page.getHeight()<1) throw new IOException("Ukuran PDF tiket tidak valid.");
                int width=Math.min((int)Math.round(210*dpi/25.4),(int)Math.round(page.getWidth()*dpi/72.0)),height=Math.min((int)Math.round(297*dpi/25.4),(int)Math.round(page.getHeight()*dpi/72.0));
                Bitmap bitmap=Bitmap.createBitmap(width,height,Bitmap.Config.ARGB_8888);
                try {
                    bitmap.eraseColor(Color.WHITE); page.render(bitmap,null,null,PdfRenderer.Page.RENDER_MODE_FOR_PRINT);
                    try(FileOutputStream stream=new FileOutputStream(output)) {
                        if(pwg) {
                            int sheetWidth=(int)Math.round(210*dpi/25.4),sheetHeight=(int)Math.round(297*dpi/25.4);
                            int left=(sheetWidth-width)/2,top=height+(int)Math.round(12*dpi/25.4)<=sheetHeight?(int)Math.round(12*dpi/25.4):0; int[] pixels=new int[width];
                            if(left<0||top+height>sheetHeight) throw new IOException("Tiket tidak muat pada kertas A4.");
                            PwgRaster.write(stream,sheetWidth,sheetHeight,dpi,(y,row)->{
                                Arrays.fill(row,(byte)255);
                                if(y>=top&&y<top+height) {
                                    bitmap.getPixels(pixels,0,width,0,y-top,width,1);
                                    for(int x=0;x<width;x++) { int value=pixels[x],offset=(left+x)*3; row[offset]=(byte)(value>>16); row[offset+1]=(byte)(value>>8); row[offset+2]=(byte)value; }
                                }
                            });
                        } else {
                            PdfDocument document=new PdfDocument();
                            try {
                                PdfDocument.Page sheet=document.startPage(new PdfDocument.PageInfo.Builder(595,842,1).create());
                                float pageWidth=Math.min(595,page.getWidth()),pageHeight=Math.min(842,page.getHeight());
                                float left=(595-pageWidth)/2f,top=pageHeight+34<=842?34:0;
                                sheet.getCanvas().drawColor(Color.WHITE);
                                sheet.getCanvas().drawBitmap(bitmap,null,new RectF(left,top,left+pageWidth,top+pageHeight),null);
                                document.finishPage(sheet); document.writeTo(stream);
                            } finally { document.close(); }
                        }
                    }
                } finally { bitmap.recycle(); }
            }
            completed=true; return output;
        } finally { if(!completed) output.delete(); }
    }
    @Override public void submit(PrintEngine.Job job,File document) throws Exception {
        Ipp.Request request=new Ipp.Request(Ipp.PRINT_JOB,requestId++,uri.toString());
        request.text(0x42,"requesting-user-name","Bank DP Taspen").text(0x42,"job-name","Tiket "+job.ticket);
        request.bool("ipp-attribute-fidelity",true).text(0x49,"document-format",pwg?"image/pwg-raster":"application/pdf");
        request.group(2).integer(0x21,"copies",1).text(0x44,"media","iso_a4_210x297mm").text(0x44,"sides","one-sided");
        if(pwg) request.resolution("printer-resolution",dpi);
        Ipp.Response response=Ipp.send(uri,request.finish(),document,20000,certificate);
        // Require an unambiguous acceptance, not a substitution of fidelity settings.
        if(response.status!=0||response.number("job-id")<=0) throw new IOException("Penerimaan cetak belum dapat dipastikan.");
    }
}
