package id.bankdptaspen.printagent;

import android.content.Context;
import android.security.keystore.*;
import android.util.AtomicFile;
import java.io.*;
import java.nio.charset.StandardCharsets;
import java.security.KeyStore;
import javax.crypto.*;
import javax.crypto.spec.GCMParameterSpec;
import org.json.JSONObject;
import id.bankdptaspen.printagent.core.PrintEngine;

final class Store implements PrintEngine.Journal {
    private final Context context;
    Store(Context context) { this.context=context.getApplicationContext(); }
    synchronized JSONObject config() throws Exception { return read("config.enc",true); }
    synchronized void config(JSONObject config) throws Exception { write("config.enc",config,true); }
    private javax.crypto.SecretKey key() throws Exception {
        KeyStore store=KeyStore.getInstance("AndroidKeyStore"); store.load(null);
        String alias="bank-dp-taspen-print-agent";
        if(!store.containsAlias(alias)) {
            KeyGenerator generator=KeyGenerator.getInstance(KeyProperties.KEY_ALGORITHM_AES,"AndroidKeyStore");
            generator.init(new KeyGenParameterSpec.Builder(alias,KeyProperties.PURPOSE_ENCRYPT|KeyProperties.PURPOSE_DECRYPT)
                .setBlockModes(KeyProperties.BLOCK_MODE_GCM).setEncryptionPaddings(KeyProperties.ENCRYPTION_PADDING_NONE).build());
            generator.generateKey();
        }
        return ((KeyStore.SecretKeyEntry)store.getEntry(alias,null)).getSecretKey();
    }
    private JSONObject read(String name,boolean encrypted) throws Exception {
        AtomicFile file=new AtomicFile(new File(context.getFilesDir(),name));
        if(!file.getBaseFile().exists()&&!new File(file.getBaseFile()+".bak").exists()) return null;
        byte[] bytes=file.readFully();
        if(encrypted) {
            if(bytes.length<13) throw new IOException("Konfigurasi tersimpan tidak valid.");
            byte[] iv=java.util.Arrays.copyOfRange(bytes,0,12); Cipher cipher=Cipher.getInstance("AES/GCM/NoPadding");
            cipher.init(Cipher.DECRYPT_MODE,key(),new GCMParameterSpec(128,iv)); bytes=cipher.doFinal(bytes,12,bytes.length-12);
        }
        return new JSONObject(new String(bytes,StandardCharsets.UTF_8));
    }
    private void write(String name,JSONObject json,boolean encrypted) throws Exception {
        byte[] bytes=json.toString().getBytes(StandardCharsets.UTF_8);
        if(encrypted) {
            Cipher cipher=Cipher.getInstance("AES/GCM/NoPadding"); cipher.init(Cipher.ENCRYPT_MODE,key());
            ByteArrayOutputStream buffer=new ByteArrayOutputStream(); buffer.write(cipher.getIV()); buffer.write(cipher.doFinal(bytes)); bytes=buffer.toByteArray();
        }
        AtomicFile file=new AtomicFile(new File(context.getFilesDir(),name)); FileOutputStream stream=null;
        try { stream=file.startWrite(); stream.write(bytes); file.finishWrite(stream); }
        catch(Exception e) { if(stream!=null) file.failWrite(stream); throw e; }
    }
    @Override public synchronized PrintEngine.Job load() throws Exception {
        JSONObject value=read("journal.enc",true); if(value==null) return null;
        PrintEngine.Job job=new PrintEngine.Job(); job.id=value.getString("id"); job.claim=value.getString("claim"); job.ticket=value.getString("ticket");
        job.status=value.getString("status"); job.failure=value.isNull("failure")?null:value.getString("failure"); return job;
    }
    @Override public synchronized void save(PrintEngine.Job job) throws Exception {
        write("journal.enc",new JSONObject().put("id",job.id).put("claim",job.claim).put("ticket",job.ticket).put("status",job.status).put("failure",job.failure==null?JSONObject.NULL:job.failure),true);
    }
    @Override public synchronized void clear() { new AtomicFile(new File(context.getFilesDir(),"journal.enc")).delete(); }
    void message(String message) { context.getSharedPreferences("status",Context.MODE_PRIVATE).edit().putString("message",message).apply(); }
    String message() { return context.getSharedPreferences("status",Context.MODE_PRIVATE).getString("message","Belum dipasangkan."); }
}
