package id.bankdptaspen.printagent.core;

import java.io.*;

/** Durable one-shot print state machine. A restart acknowledges uncertainty, never replays printing. */
public final class PrintEngine {
    public static final class Job {
        public String id,claim,ticket,hash,status="unknown",failure="android_interrupted";
    }
    public interface Api {
        Job claim() throws Exception;
        File pdf(Job job) throws Exception;
        void start(Job job) throws Exception;
        void complete(Job job) throws Exception;
    }
    public interface Journal {
        Job load() throws Exception;
        void save(Job job) throws Exception;
        void clear() throws Exception;
    }
    public interface Printer {
        File prepare(File pdf) throws Exception;
        void submit(Job job,File document) throws Exception;
    }
    public static final class Rejected extends IOException {
        public final int status;
        public Rejected(int status) { super("Akses/status berubah (HTTP "+status+")."); this.status=status; }
    }
    private final Api api; private final Journal journal; private final Printer printer;
    public PrintEngine(Api api,Journal journal,Printer printer) { this.api=api; this.journal=journal; this.printer=printer; }
    public void recover() throws Exception {
        Job pending=journal.load();
        if(pending!=null) acknowledge(pending);
    }
    public String once() throws Exception {
        recover();
        Job job=api.claim(); if(job==null) return "Siap menerima tiket.";
        File pdf=null,document=null; boolean starting=false;
        // Persist uncertainty before any possibility of native transmission.
        journal.save(job);
        try {
            pdf=api.pdf(job); document=printer.prepare(pdf);
            starting=true; api.start(job);
            printer.submit(job,document); job.status="submitted"; job.failure=null;
        } catch(Exception failure) {
            job.status=starting?"unknown":"failed"; job.failure=starting?"android_print_uncertain":"android_prepare_failed";
        } finally {
            if(pdf!=null) pdf.delete(); if(document!=null&&!document.equals(pdf)) document.delete();
        }
        journal.save(job); acknowledge(job);
        return "Tiket "+job.ticket+": "+(job.status.equals("submitted")?"dikirim ke printer.":"periksa printer dan gunakan Cetak Ulang bila diperlukan.");
    }
    private void acknowledge(Job job) throws Exception {
        try { api.complete(job); journal.clear(); }
        catch(Rejected rejection) { if(rejection.status==409||rejection.status==404) journal.clear(); else throw rejection; }
    }
}
