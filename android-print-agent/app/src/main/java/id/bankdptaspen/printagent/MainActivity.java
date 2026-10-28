package id.bankdptaspen.printagent;

import android.Manifest;
import android.app.*;
import android.content.*;
import android.graphics.Color;
import android.net.Uri;
import android.os.*;
import android.view.*;
import android.widget.*;
import java.io.*;
import java.net.URI;
import java.nio.charset.StandardCharsets;
import java.util.*;
import java.util.zip.*;
import org.json.JSONObject;
import id.bankdptaspen.printagent.core.Ipp;

public final class MainActivity extends Activity {
    private Store store; private TextView status; private EditText printerAddress;
    private JSONObject packageConfig; private boolean busy;
    private String approvedCertificate,approvedPrinter;
    private final Handler handler=new Handler(Looper.getMainLooper());
    private final Runnable refresh=new Runnable() { public void run() { if(!busy) status.setText(store.message()); handler.postDelayed(this,1500); } };
    @Override public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState); store=new Store(this);
        getWindow().addFlags(WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON);
        ScrollView scroll=new ScrollView(this); LinearLayout layout=new LinearLayout(this); layout.setOrientation(LinearLayout.VERTICAL);
        int pad=(int)(20*getResources().getDisplayMetrics().density); layout.setPadding(pad,pad,pad,pad); scroll.addView(layout); setContentView(scroll);
        // Target API 35 lays out behind system bars. Respect display/cutout insets.
        layout.setOnApplyWindowInsetsListener((view,insets)->{
            int top=insets.getSystemWindowInsetTop(),bottom=insets.getSystemWindowInsetBottom();
            view.setPadding(pad,pad+top,pad,pad+bottom); return insets;
        });
        text(layout,"Cetak Antrean Bank DP Taspen",24,true);
        text(layout,"Hubungkan Android dan Epson L6290 ke jaringan Wi-Fi yang sama. Gunakan kertas A4. Bluetooth tidak tersedia pada L6290.",15,false);
        status=text(layout,"Memeriksa penghubung…",16,true);
        button(layout,"1. Impor Paket Android",()->{
            if(!canConfigure()) return;
            Intent intent=new Intent(Intent.ACTION_OPEN_DOCUMENT).setType("*/*").addCategory(Intent.CATEGORY_OPENABLE);
            startActivityForResult(intent,100);
        });
        printerAddress=new EditText(this); printerAddress.setSingleLine(true); printerAddress.setHint("IP printer, contoh 192.168.11.100");
        printerAddress.setInputType(android.text.InputType.TYPE_CLASS_TEXT|android.text.InputType.TYPE_TEXT_VARIATION_URI); layout.addView(printerAddress);
        text(layout,"Lihat IP pada layar/pengaturan jaringan printer. Format lengkap juga diterima: ipps://IP:631/ipp/print",13,false);
        button(layout,"2. Periksa Printer & Pasangkan",this::enroll);
        button(layout,"3. Jalankan Penghubung",this::start);
        button(layout,"Buka Kiosk Cabang",()->{
            background(()->{
                JSONObject config=store.config(); if(config==null) throw new IOException("Pasangkan penghubung dahulu.");
                JSONObject reply=new AgentApi(this,config).post("/api/print-agent/browser-pairings",new JSONObject(),null);
                URI destination=new URI(reply.getString("connect_url")),base=new URI(config.getString("server_url"));
                if(!java.util.Objects.equals(destination.getScheme(),base.getScheme())||!java.util.Objects.equals(destination.getAuthority(),base.getAuthority())||
                    !destination.getPath().startsWith(base.getPath().replaceAll("/+$","")+"/")||destination.getFragment()==null||!destination.getFragment().matches("[a-f0-9]{64}"))
                    throw new IOException("Alamat pemasangan browser tidak valid.");
                handler.post(()->startActivity(new Intent(Intent.ACTION_VIEW,Uri.parse(destination.toString()))));
            });
        });
        button(layout,"Hentikan Penghubung",()->{ stopService(new Intent(this,AgentService.class)); store.message("Penghubung sedang dihentikan. Tunggu pengiriman yang sedang berlangsung selesai."); });
        text(layout,"Paket admin mengaktifkan cetak otomatis setelah printer siap. Gunakan Buka Kiosk Cabang untuk memasangkan browser. Setelah perangkat dimulai ulang, buka aplikasi ini dan Jalankan Penghubung kembali.",14,false);
        try { JSONObject config=store.config(); if(config!=null) printerAddress.setText(config.getString("printer_uri")); }
        catch(Exception failure) { error(failure); }
        if(Build.VERSION.SDK_INT>=33&&checkSelfPermission(Manifest.permission.POST_NOTIFICATIONS)!=android.content.pm.PackageManager.PERMISSION_GRANTED)
            requestPermissions(new String[]{Manifest.permission.POST_NOTIFICATIONS},101);
    }
    private TextView text(LinearLayout layout,String value,int size,boolean bold) {
        TextView view=new TextView(this); view.setText(value); view.setTextSize(size); view.setTextColor(Color.rgb(0,43,73)); view.setPadding(0,12,0,12);
        if(bold) view.setTypeface(null,android.graphics.Typeface.BOLD); layout.addView(view); return view;
    }
    private void button(LinearLayout layout,String label,Runnable action) {
        Button button=new Button(this); button.setText(label); button.setAllCaps(false); layout.addView(button,new LinearLayout.LayoutParams(-1,-2)); button.setOnClickListener(v->action.run());
    }
    private boolean canConfigure() {
        if(busy||AgentService.running) { error(new IOException("Hentikan penghubung dan tunggu proses selesai sebelum memasang ulang.")); return false; } return true;
    }
    @Override protected void onActivityResult(int request,int result,Intent data) {
        super.onActivityResult(request,result,data); if(request!=100||result!=RESULT_OK||data==null||!canConfigure()) return;
        background(()->{
            try(InputStream input=getContentResolver().openInputStream(data.getData())) {
                if(input==null) throw new IOException("Paket tidak dapat dibuka.");
                byte[] bytes=readBounded(input,1048576); byte[] setup=null;
                if(bytes.length>2&&bytes[0]=='P'&&bytes[1]=='K') {
                    try(ZipInputStream zip=new ZipInputStream(new ByteArrayInputStream(bytes))) {
                        for(int entries=0;entries<20;entries++) { ZipEntry entry=zip.getNextEntry(); if(entry==null) break; if("setup.json".equals(entry.getName())) { setup=readBounded(zip,32768); break; } }
                    }
                } else setup=bytes;
                if(setup==null||setup.length>32768) throw new IOException("Pilih ZIP Paket Android yang diunduh dari admin pusat.");
                JSONObject config=new JSONObject(new String(setup,StandardCharsets.UTF_8)); AgentApi.validate(config);
                if(!"android".equals(config.optString("platform"))||!config.optString("enrollment_key").matches("[a-f0-9]{64}")) throw new IOException("Paket Android tidak valid. Unduh paket baru melalui admin pusat.");
                packageConfig=config; store.message("Paket kantor "+config.getString("branch_code")+" dibaca. Isi IP printer lalu Periksa Printer & Pasangkan.");
            }
        });
    }
    private void enroll() {
        if(!canConfigure()) return;
        if(packageConfig==null) { error(new IOException("Impor Paket Android terbaru dahulu (berlaku 15 menit).")); return; }
        String address=printerAddress.getText().toString().trim();
        if(!address.contains("://")) address="ipps://"+address+":631/ipp/print";
        final String printer=address;
        background(()->{
            URI uri=Ipp.printerUri(printer);
            if("ipps".equals(uri.getScheme())&&!printer.equals(approvedPrinter)) {
                String fingerprint=Ipp.certificateFingerprint(uri);
                handler.post(()->new AlertDialog.Builder(this).setTitle("Konfirmasi Printer Wi-Fi")
                    .setMessage("Pastikan alamat ini milik Epson L6290 cabang: "+printer+"\n\nSidik jari sertifikat SHA-256:\n"+fingerprint+"\n\nCocokkan dengan informasi sertifikat printer/Web Config atau petugas jaringan sebelum mempercayainya. Sertifikat yang berubah akan ditolak.")
                    .setNegativeButton("Batal",null).setPositiveButton("Percayai Printer Ini",(dialog,which)->{
                        approvedCertificate=fingerprint; approvedPrinter=printer; printerAddress.setText(printer); enroll();
                    }).show());
                return;
            }
            new WifiPrinter(this,printer,approvedCertificate).check();
            JSONObject config=new JSONObject(packageConfig.toString()).put("device_id",UUID.randomUUID().toString()).put("printer_uri",printer);
            config.put("printer_name","Printer Wi-Fi "+uri.getHost()).put("paper_profile","a4");
            if("ipps".equals(uri.getScheme())) config.put("printer_certificate_sha256",approvedCertificate);
            AgentApi api=new AgentApi(this,config);
            JSONObject enrollment=api.post("/api/print-agent/enroll",new JSONObject().put("agent_id",config.getString("agent_id"))
                .put("enrollment_key",config.getString("enrollment_key")).put("device_id",config.getString("device_id")).put("printer_name",config.getString("printer_name")).put("paper_profile","a4"),null);
            String token=enrollment.getString("token"); if(!token.matches("[a-f0-9]{64}")) throw new IOException("Respons pemasangan tidak valid.");
            config.put("token",token); config.remove("enrollment_key"); store.config(config); store.clear(); packageConfig=null;
            store.message("Terpasang pada kantor "+config.getString("branch_code")+". Tekan Jalankan Penghubung, tunggu siap, lalu Buka Kiosk Cabang.");
        });
    }
    private void start() {
        try { if(busy||AgentService.running) return; if(store.config()==null) throw new IOException("Pasangkan penghubung dahulu."); startForegroundService(new Intent(this,AgentService.class)); }
        catch(Exception failure) { error(failure); }
    }
    private interface Task { void run() throws Exception; }
    private void background(Task task) {
        busy=true; status.setText("Sedang memeriksa. Tunggu sebentar…");
        new Thread(()->{try{task.run();}catch(Exception failure){store.message(AgentService.message(failure));}finally{handler.post(()->{busy=false;status.setText(store.message());});}},"AgentSetup").start();
    }
    private void error(Exception failure) { new AlertDialog.Builder(this).setMessage(AgentService.message(failure)).setPositiveButton("OK",null).show(); }
    private static byte[] readBounded(InputStream input,int max) throws IOException {
        ByteArrayOutputStream out=new ByteArrayOutputStream(); byte[] b=new byte[4096]; int n;
        while((n=input.read(b))!=-1) { if(out.size()+n>max) throw new IOException("Paket terlalu besar."); out.write(b,0,n); } return out.toByteArray();
    }
    @Override protected void onResume() { super.onResume(); handler.post(refresh); }
    @Override protected void onPause() { super.onPause(); handler.removeCallbacks(refresh); }
}
