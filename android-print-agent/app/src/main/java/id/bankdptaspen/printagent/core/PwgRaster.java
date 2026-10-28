package id.bankdptaspen.printagent.core;

import java.io.*;
import java.nio.*;
import java.nio.charset.StandardCharsets;
import java.util.Arrays;

/** PWG 5102.4: one sRGB page, big-endian header and pixel/row RLE. */
public final class PwgRaster {
    public interface Rows { void read(int y,byte[] rgb) throws IOException; }
    public static void write(OutputStream out,int width,int height,int dpi,Rows rows) throws IOException {
        if(width<1||height<1||width>15000||height>15000||dpi<72||dpi>600) throw new IOException("Ukuran raster tidak didukung.");
        ByteBuffer header=ByteBuffer.allocate(1796).order(ByteOrder.BIG_ENDIAN);
        putText(header,0,"PwgRaster"); putText(header,128,"stationery"); putText(header,192,"Automatic");
        header.putInt(276,dpi); header.putInt(280,dpi); header.putInt(340,1);
        header.putInt(352,Math.round(width*72f/dpi)); header.putInt(356,Math.round(height*72f/dpi));
        header.putInt(372,width); header.putInt(376,height); header.putInt(384,8); header.putInt(388,24);
        header.putInt(392,width*3); header.putInt(396,0); header.putInt(400,19); header.putInt(420,3);
        header.putFloat(424,1); header.putFloat(428,width*72f/dpi); header.putFloat(432,height*72f/dpi);
        header.putInt(452,1); header.putInt(456,1); header.putInt(460,1); header.putInt(472,width); header.putInt(476,height);
        putText(header,1668,"relative"); putText(header,1732,"iso_a4_210x297mm");
        out.write(new byte[]{'R','a','S','2'}); out.write(header.array());
        byte[] previous=new byte[width*3],current=new byte[width*3]; int repeat=0;
        for(int y=0;y<height;y++) {
            rows.read(y,current);
            if(repeat>0&&repeat<256&&Arrays.equals(previous,current)) { repeat++; continue; }
            if(repeat>0) { out.write(repeat-1); encodeRow(out,previous); }
            byte[] swap=previous; previous=current; current=swap; repeat=1;
        }
        if(repeat>0) { out.write(repeat-1); encodeRow(out,previous); }
    }
    static void encodeRow(OutputStream out,byte[] rgb) throws IOException {
        int pixels=rgb.length/3,position=0;
        while(position<pixels) {
            int repeated=1;
            while(repeated<128&&position+repeated<pixels&&same(rgb,position,position+repeated)) repeated++;
            if(repeated>1) { out.write(repeated-1); out.write(rgb,position*3,3); position+=repeated; continue; }
            int start=position++;
            while(position<pixels&&position-start<128) {
                if(position+1<pixels&&same(rgb,position,position+1)) break;
                position++;
            }
            int count=position-start;
            out.write(count==1?0:257-count); out.write(rgb,start*3,count*3);
        }
    }
    private static boolean same(byte[] b,int a,int c) { return b[a*3]==b[c*3]&&b[a*3+1]==b[c*3+1]&&b[a*3+2]==b[c*3+2]; }
    private static void putText(ByteBuffer header,int offset,String value) { byte[] bytes=value.getBytes(StandardCharsets.US_ASCII); for(int i=0;i<Math.min(63,bytes.length);i++) header.put(offset+i,bytes[i]); }
}
