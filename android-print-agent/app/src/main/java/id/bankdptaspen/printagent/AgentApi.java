package id.bankdptaspen.printagent;

import android.content.Context;
import org.json.*;
import java.io.*;
import java.net.*;
import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;
import java.util.Locale;
import id.bankdptaspen.printagent.core.PrintEngine;

final class AgentApi implements PrintEngine.Api {
    final JSONObject config; private final Context context;
    AgentApi(Context context,JSONObject config) throws Exception { this.context=context; this.config=config; validate(config); }
    static void validate(JSONObject config) throws Exception {
        URI uri=new URI(config.getString("server_url"));
        if(uri.getHost()==null||uri.getUserInfo()!=null||uri.getQuery()!=null||uri.getFragment()!=null||
            !("https".equals(uri.getScheme())||("http".equals(uri.getScheme())&&config.optBoolean("allow_http",false))))
            throw new IOException("Alamat aplikasi harus HTTPS; HTTP hanya untuk pengujian yang diizinkan admin.");
        java.util.UUID.fromString(config.getString("agent_id"));
    }
    JSONObject post(String path,JSONObject body,String claim) throws Exception {
        byte[] data=request(path,body,claim,false); return data.length==0?new JSONObject():new JSONObject(new String(data,StandardCharsets.UTF_8));
    }
    private byte[] request(String path,JSONObject body,String claim,boolean pdf) throws Exception {
        // API paths are constructed locally; PDF URL returned by server is not followed.
        URL url=new URL(config.getString("server_url").replaceAll("/+$","")+path);
        HttpURLConnection connection=(HttpURLConnection)url.openConnection();
        connection.setInstanceFollowRedirects(false); connection.setConnectTimeout(10000); connection.setReadTimeout(15000);
        connection.setRequestProperty("Accept",pdf?"application/pdf":"application/json");
        if(config.has("token")) {
            connection.setRequestProperty("Authorization","Bearer "+config.getString("token"));
            connection.setRequestProperty("X-Print-Agent",config.getString("agent_id")); connection.setRequestProperty("X-Print-Device",config.getString("device_id"));
        }
        if(claim!=null) connection.setRequestProperty("X-Print-Claim",claim);
        try {
            if(!pdf) {
                byte[] bytes=body.toString().getBytes(StandardCharsets.UTF_8);
                connection.setRequestMethod("POST"); connection.setDoOutput(true); connection.setFixedLengthStreamingMode(bytes.length);
                connection.setRequestProperty("Content-Type","application/json");
                try(OutputStream output=connection.getOutputStream()) { output.write(bytes); }
            }
            int status=connection.getResponseCode(); if(status<200||status>=300) throw new PrintEngine.Rejected(status);
            if(pdf&& !"application/pdf".equalsIgnoreCase(connection.getContentType())) throw new IOException("Respons bukan PDF tiket.");
            ByteArrayOutputStream out=new ByteArrayOutputStream();
            try(InputStream input=connection.getInputStream()) { byte[] b=new byte[8192]; int n; while((n=input.read(b))!=-1) { if(out.size()+n>2097152) throw new IOException("Respons aplikasi terlalu besar."); out.write(b,0,n); } }
            byte[] result=out.toByteArray();
            if(pdf) {
                String expected=connection.getHeaderField("X-Pdf-Sha256");
                if(result.length<5||!new String(result,0,5,StandardCharsets.US_ASCII).equals("%PDF-")||expected==null||!expected.matches("[a-f0-9]{64}")||!expected.equals(hash(result)))
                    throw new IOException("Checksum PDF tiket tidak cocok.");
            }
            return result;
        } finally { connection.disconnect(); }
    }
    static String hash(byte[] bytes) throws Exception { StringBuilder out=new StringBuilder(); for(byte b:MessageDigest.getInstance("SHA-256").digest(bytes)) out.append(String.format(Locale.ROOT,"%02x",b&255)); return out.toString(); }
    @Override public PrintEngine.Job claim() throws Exception {
        JSONObject value=post("/api/print-agent/claim",new JSONObject(),null).optJSONObject("job"); if(value==null) return null;
        PrintEngine.Job job=new PrintEngine.Job(); job.id=value.getString("id"); java.util.UUID.fromString(job.id);
        job.claim=value.getString("claim_token"); if(!job.claim.matches("[a-f0-9]{64}")) throw new IOException("Klaim tugas tidak valid.");
        job.ticket=value.getString("ticket"); return job;
    }
    @Override public File pdf(PrintEngine.Job job) throws Exception {
        byte[] bytes=request("/api/print-agent/jobs/"+job.id+"/pdf",null,job.claim,true); job.hash=hash(bytes);
        File file=File.createTempFile("ticket-",".pdf",context.getCacheDir());
        try(FileOutputStream output=new FileOutputStream(file)) { output.write(bytes); }
        return file;
    }
    @Override public void start(PrintEngine.Job job) throws Exception { post("/api/print-agent/jobs/"+job.id+"/start",new JSONObject().put("pdf_sha256",job.hash),job.claim); }
    @Override public void complete(PrintEngine.Job job) throws Exception { post("/api/print-agent/jobs/"+job.id+"/complete",new JSONObject().put("status",job.status).put("failure_code",job.failure==null?JSONObject.NULL:job.failure),job.claim); }
    boolean heartbeat(boolean ready,String error) throws Exception {
        return post("/api/print-agent/heartbeat",new JSONObject().put("printer_ready",ready).put("printer_name",config.getString("printer_name"))
            .put("machine_name","Android "+android.os.Build.MANUFACTURER+" "+android.os.Build.MODEL).put("version","android-1.3.0").put("error",error==null?JSONObject.NULL:error),null).getBoolean("enabled");
    }
}
