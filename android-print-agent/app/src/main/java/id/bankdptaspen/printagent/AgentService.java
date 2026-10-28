package id.bankdptaspen.printagent;

import android.app.*;
import android.content.*;
import android.os.*;
import org.json.JSONObject;
import java.io.File;
import id.bankdptaspen.printagent.core.PrintEngine;

public final class AgentService extends Service {
    private volatile boolean stopping;
    static volatile boolean running;
    private Thread worker; private Store store;
    @Override public void onCreate() {
        super.onCreate(); store=new Store(this);
        NotificationManager manager=getSystemService(NotificationManager.class);
        manager.createNotificationChannel(new NotificationChannel("printer","Penghubung printer cabang",NotificationManager.IMPORTANCE_LOW));
        PendingIntent open=PendingIntent.getActivity(this,0,new Intent(this,MainActivity.class),PendingIntent.FLAG_IMMUTABLE);
        startForeground(10,new Notification.Builder(this,"printer").setSmallIcon(android.R.drawable.ic_menu_upload)
            .setContentTitle("Cetak Antrean Bank DP Taspen").setContentText("Penghubung Wi-Fi aktif; ketuk untuk melihat status.").setContentIntent(open).setOngoing(true).build());
    }
    @Override public int onStartCommand(Intent intent,int flags,int startId) {
        if(worker==null&&!running) {
            running=true; worker=new Thread(this::loop,"BankTicketWifi"); worker.start();
        }
        return START_NOT_STICKY;
    }
    private void loop() {
        try {
            JSONObject config=store.config(); if(config==null) throw new IllegalStateException("Impor paket Android terlebih dahulu.");
            AgentApi api=new AgentApi(this,config); WifiPrinter printer=new WifiPrinter(this,config.getString("printer_uri"),config.optString("printer_certificate_sha256",""));
            PrintEngine engine=new PrintEngine(api,store,printer);
            // Cache files never confer permission to repeat a print after a restart.
            File[] leftovers=getCacheDir().listFiles();
            if(leftovers!=null) for(File file:leftovers) if(file.getName().startsWith("ticket-")) file.delete();
            while(!stopping) {
                try {
                    engine.recover();
                    boolean ready=true; String error=null;
                    try { printer.check(); } catch(Exception failure) { ready=false; error=message(failure); }
                    boolean enabled=api.heartbeat(ready,error);
                    if(stopping) break;
                    if(ready&&enabled) store.message(engine.once());
                    else store.message(ready?"Printer siap. Aktifkan penghubung melalui admin pusat.":error);
                } catch(PrintEngine.Rejected rejected) {
                    if(rejected.status==401||rejected.status==403) { store.message("Akses penghubung dicabut/kedaluwarsa. Unduh paket Android baru lalu pasangkan ulang."); break; }
                    store.message(message(rejected));
                } catch(Exception failure) { store.message(message(failure)); }
                try { Thread.sleep(4000); } catch(InterruptedException ignored) { break; }
            }
        } catch(Exception failure) { store.message(message(failure)); }
        finally { running=false; stopSelf(); }
    }
    static String message(Exception failure) {
        if(failure instanceof PrintEngine.Rejected) {
            int code=((PrintEngine.Rejected)failure).status;
            if(code==401) return "Kunci pemasangan atau akses tidak berlaku. Unduh Paket Android baru dari admin pusat.";
            if(code==403) return "Akses ditolak. Periksa alamat HTTPS dan izin penghubung pada admin pusat.";
            if(code==409) return "Status tugas berubah. Periksa nomor tiket dan status penghubung pada admin pusat.";
        }
        String message=failure.getMessage(); if(message==null||message.length()>200) return "Koneksi atau printer belum siap. Periksa Wi-Fi dan aplikasi pusat.";
        return message;
    }
    @Override public void onDestroy() {
        // Let a bounded in-flight transmission finish. Interrupting it must not
        // allow a second service thread or a configuration change to replay it.
        stopping=true; super.onDestroy();
    }
    @Override public IBinder onBind(Intent intent) { return null; }
}
