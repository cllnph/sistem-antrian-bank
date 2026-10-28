package id.bankdptaspen.printagent.core;

import java.net.URI;
import java.nio.*;
import java.util.*;

/** Optional read-only hardware diagnostic: never invokes Print-Job. */
public final class PrinterProbe {
    public static void main(String[] args) throws Exception {
        URI uri=Ipp.printerUri(args[0]);
        Ipp.Request request=new Ipp.Request(Ipp.GET_ATTRIBUTES,901,uri.toString());
        String[] keys={"printer-make-and-model","printer-state","printer-state-reasons","printer-is-accepting-jobs","document-format-supported","media-supported","pwg-raster-document-type-supported","pwg-raster-document-resolution-supported"};
        for(int i=0;i<keys.length;i++) request.text(0x44,i==0?"requested-attributes":"",keys[i]);
        Ipp.Response response=Ipp.send(uri,request.finish(),null,10000,args.length>1?args[1]:null);
        for(Map.Entry<String,List<Ipp.Value>> entry:response.attributes.entrySet()) {
            List<String> values=new ArrayList<>();
            for(Ipp.Value value:entry.getValue()) {
                if(value.tag==0x32&&value.bytes.length==9) { ByteBuffer b=ByteBuffer.wrap(value.bytes).order(ByteOrder.BIG_ENDIAN); values.add(b.getInt()+"x"+b.getInt()+" units="+(b.get()&255)); }
                else if(value.tag==0x21||value.tag==0x23) values.add(Integer.toString(value.number()));
                else if(value.tag==0x22&&value.bytes.length==1) values.add(Boolean.toString(value.bytes[0]!=0));
                else values.add(value.text());
            }
            System.out.println(entry.getKey()+": "+values);
        }
        System.out.println("Read-only IPP check completed. No print job sent.");
    }
}
