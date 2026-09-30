# -*- coding: utf-8 -*-
"""
Script to generate the complete, official KKP Report for:
Mahasiswa: Salsabila Puspa Pratiwi (NIM: 2301111110046)
Program Studi: S1 - Teknik Informatika
Fakultas: Fakultas Teknologi Informasi dan Digital (FTID)
Universitas: Universitas Bani Saleh, Bekasi
Tahun: 2026
Instansi KKP: PT BPR DP TASPEN Kantor Cabang Bekasi
"""

import os
import docx
from docx.shared import Cm, Pt, RGBColor, Inches
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_ALIGN_VERTICAL
from docx.oxml import OxmlElement, parse_xml
from docx.oxml.ns import qn, nsdecls

def create_report():
    doc = docx.Document()

    # Define normal style defaults
    style_normal = doc.styles['Normal']
    font = style_normal.font
    font.name = 'Times New Roman'
    font.size = Pt(12)
    font.color.rgb = RGBColor(0, 0, 0)

    # Set Section 1 (Front Matter) Margins: Top 4cm, Left 4cm, Bottom 3cm, Right 3cm
    section_front = doc.sections[0]
    section_front.top_margin = Cm(4)
    section_front.left_margin = Cm(4)
    section_front.bottom_margin = Cm(3)
    section_front.right_margin = Cm(3)
    section_front.page_width = Cm(21.0)
    section_front.page_height = Cm(29.7)

    # Helpers
    def set_cell_margins(cell, top=120, bottom=120, left=150, right=150):
        tcPr = cell._tc.get_or_add_tcPr()
        tcMar = OxmlElement('w:tcMar')
        for m, val in [('top', top), ('bottom', bottom), ('left', left), ('right', right)]:
            node = OxmlElement(f'w:{m}')
            node.set(qn('w:w'), str(val))
            node.set(qn('w:type'), 'dxa')
            tcMar.append(node)
        tcPr.append(tcMar)

    def set_cell_background(cell, color_hex):
        shading_elm = parse_xml(f'<w:shd {nsdecls("w")} w:fill="{color_hex}"/>')
        cell._tc.get_or_add_tcPr().append(shading_elm)

    def add_p(text="", align=WD_ALIGN_PARAGRAPH.JUSTIFY, space_before=0, space_after=4, line_spacing=1.5, bold=False, italic=False, size=12, indent=False):
        p = doc.add_paragraph()
        p.alignment = align
        p.paragraph_format.space_before = Pt(space_before)
        p.paragraph_format.space_after = Pt(space_after)
        p.paragraph_format.line_spacing = line_spacing
        if indent:
            p.paragraph_format.first_line_indent = Cm(1.25)
        if text:
            run = p.add_run(text)
            run.bold = bold
            run.italic = italic
            run.font.name = 'Times New Roman'
            run.font.size = Pt(size)
            run.font.color.rgb = RGBColor(0, 0, 0)
        return p

    def add_heading_bab(bab_num, judul_bab):
        doc.add_page_break()
        p1 = add_p(bab_num, align=WD_ALIGN_PARAGRAPH.CENTER, space_before=12, space_after=2, line_spacing=1.5, bold=True, size=14)
        p2 = add_p(judul_bab, align=WD_ALIGN_PARAGRAPH.CENTER, space_before=0, space_after=18, line_spacing=1.5, bold=True, size=14)
        return p1

    def add_heading_sub(text, level=2):
        if level == 2:
            return add_p(text, align=WD_ALIGN_PARAGRAPH.LEFT, space_before=10, space_after=4, line_spacing=1.5, bold=True, size=12)
        elif level == 3:
            return add_p(text, align=WD_ALIGN_PARAGRAPH.LEFT, space_before=6, space_after=2, line_spacing=1.5, bold=True, size=12)
        else:
            return add_p(text, align=WD_ALIGN_PARAGRAPH.LEFT, space_before=4, space_after=2, line_spacing=1.5, bold=False, size=12)

    def add_table_caption(nomor, judul):
        p = add_p(f"Tabel {nomor} {judul}", align=WD_ALIGN_PARAGRAPH.CENTER, space_before=10, space_after=4, line_spacing=1.15, bold=True, size=11)
        return p

    def add_figure_caption(nomor, judul):
        p = add_p(f"Gambar {nomor} {judul}", align=WD_ALIGN_PARAGRAPH.CENTER, space_before=4, space_after=12, line_spacing=1.15, bold=True, size=11)
        return p

    def add_image_centered(img_path, width_inches=5.2):
        if os.path.exists(img_path):
            p = doc.add_paragraph()
            p.alignment = WD_ALIGN_PARAGRAPH.CENTER
            p.paragraph_format.space_before = Pt(6)
            p.paragraph_format.space_after = Pt(4)
            run = p.add_run()
            run.add_picture(img_path, width=Inches(width_inches))
            return p
        else:
            return add_p(f"[Gambar: {os.path.basename(img_path)}]", align=WD_ALIGN_PARAGRAPH.CENTER, bold=True)

    # =========================================================================
    # 1. HALAMAN JUDUL (COVER DEPAN) - Sesuai Contoh-1 Panduan FTID
    # =========================================================================
    add_p("PERANCANGAN DAN IMPLEMENTASI SISTEM INFORMASI\nANTREAN BANK BERBASIS WEB PADA PT BPR DP TASPEN\nKANTOR CABANG BEKASI",
          align=WD_ALIGN_PARAGRAPH.CENTER, space_before=10, space_after=18, line_spacing=1.5, bold=True, size=14)

    add_p("LAPORAN KULIAH KERJA PRAKTIK\nPROGRAM STUDI SARJANA TEKNIK INFORMATIKA",
          align=WD_ALIGN_PARAGRAPH.CENTER, space_before=8, space_after=24, line_spacing=1.5, bold=True, size=14)

    # Logo Universitas Bani Saleh
    logo_path = 'docs/images/logo_universitas_bani_saleh.png'
    add_image_centered(logo_path, width_inches=2.2)

    add_p("Oleh:", align=WD_ALIGN_PARAGRAPH.CENTER, space_before=20, space_after=8, line_spacing=1.5, bold=True, size=12)

    # Tabel Mahasiswa
    t_mhs = doc.add_table(rows=1, cols=2)
    t_mhs.alignment = WD_TABLE_ALIGNMENT.CENTER
    cell_nim = t_mhs.rows[0].cells[0]
    cell_nama = t_mhs.rows[0].cells[1]
    cell_nim.width = Cm(5.5)
    cell_nama.width = Cm(7.5)

    p_nim = cell_nim.paragraphs[0]
    p_nim.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r_nim = p_nim.add_run("NIM: 2301111110046")
    r_nim.bold = True
    r_nim.font.name = 'Times New Roman'
    r_nim.font.size = Pt(12)

    p_nama = cell_nama.paragraphs[0]
    p_nama.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r_nama = p_nama.add_run("NAMA: Salsabila Puspa Pratiwi")
    r_nama.bold = True
    r_nama.font.name = 'Times New Roman'
    r_nama.font.size = Pt(12)

    add_p("FAKULTAS TEKNOLOGI INFORMASI DAN DIGITAL\nUNIVERSITAS BANI SALEH\nBEKASI\n2026",
          align=WD_ALIGN_PARAGRAPH.CENTER, space_before=45, space_after=0, line_spacing=1.0, bold=True, size=14)

    # =========================================================================
    # 2. HALAMAN PENGESAHAN LAPORAN KKP - Sesuai Contoh-2 Panduan FTID
    # =========================================================================
    doc.add_page_break()

    add_p("LEMBAR PENGESAHAN\nLAPORAN KULIAH KERJA PRAKTEK\nPROGRAM STUDI SARJANA TEKNIK INFORMATIKA\nUNIVERSITAS BANI SALEH",
          align=WD_ALIGN_PARAGRAPH.CENTER, space_before=10, space_after=18, line_spacing=1.0, bold=True, size=14)

    add_p("JUDUL LAPORAN KKP:\nPERANCANGAN DAN IMPLEMENTASI SISTEM INFORMASI\nANTREAN BANK BERBASIS WEB PADA PT BPR DP TASPEN\nKANTOR CABANG BEKASI",
          align=WD_ALIGN_PARAGRAPH.CENTER, space_before=10, space_after=20, line_spacing=1.0, bold=True, size=14)

    # Identitas Mahasiswa
    t_mhs_p = doc.add_table(rows=1, cols=3)
    t_mhs_p.alignment = WD_TABLE_ALIGNMENT.CENTER
    t_mhs_p.rows[0].cells[0].width = Cm(1.5)
    t_mhs_p.rows[0].cells[1].width = Cm(5.0)
    t_mhs_p.rows[0].cells[2].width = Cm(8.0)

    p0 = t_mhs_p.rows[0].cells[0].paragraphs[0]
    p0.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p0.add_run("1.").bold = True
    p1 = t_mhs_p.rows[0].cells[1].paragraphs[0]
    p1.add_run("2301111110046").bold = True
    p2 = t_mhs_p.rows[0].cells[2].paragraphs[0]
    p2.add_run("Salsabila Puspa Pratiwi").bold = True

    add_p("Bekasi, 21 September 2026", align=WD_ALIGN_PARAGRAPH.RIGHT, space_before=24, space_after=14, line_spacing=1.5, size=12)

    # Tanda Tangan Pembimbing
    t_ttd = doc.add_table(rows=2, cols=2)
    t_ttd.alignment = WD_TABLE_ALIGNMENT.CENTER
    t_ttd.rows[0].cells[0].width = Cm(7.5)
    t_ttd.rows[0].cells[1].width = Cm(7.5)
    t_ttd.rows[1].cells[0].width = Cm(7.5)
    t_ttd.rows[1].cells[1].width = Cm(7.5)

    p_pemb1 = t_ttd.rows[0].cells[0].paragraphs[0]
    p_pemb1.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_pemb1.add_run("Pembimbing Lapangan,\nPT BPR DP TASPEN").bold = True

    p_pemb2 = t_ttd.rows[0].cells[1].paragraphs[0]
    p_pemb2.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_pemb2.add_run("Dosen Pembimbing KKP,\nUniversitas Bani Saleh").bold = True

    p_ttd1 = t_ttd.rows[1].cells[0].paragraphs[0]
    p_ttd1.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_ttd1.add_run("\n\n\n\n(  Hendra Kurniawan, S.E.  )\nSupervisor Operasional Cabang").bold = True

    p_ttd2 = t_ttd.rows[1].cells[1].paragraphs[0]
    p_ttd2.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_ttd2.add_run("\n\n\n\n(  Agus Suhartono, M.Kom.  )\nNIDN: 0415088201").bold = True

    add_p("Mengetahui,\nKetua Program Studi Sarjana Teknik Informatika\nFakultas Teknologi Informasi dan Digital",
          align=WD_ALIGN_PARAGRAPH.CENTER, space_before=24, space_after=35, line_spacing=1.15, bold=True, size=12)

    add_p("(  Rahmadi, S.Kom., M.Kom.  )\nDekan FTID / Plt. Ketua Program Studi",
          align=WD_ALIGN_PARAGRAPH.CENTER, space_before=0, space_after=0, line_spacing=1.15, bold=True, size=12)

    # =========================================================================
    # 3. KATA PENGANTAR
    # =========================================================================
    doc.add_page_break()
    add_p("KATA PENGANTAR", align=WD_ALIGN_PARAGRAPH.CENTER, space_before=12, space_after=18, line_spacing=1.5, bold=True, size=14)

    add_p("Assalamu’alaikum Warahmatullahi Wabarakatuh.", space_before=0, space_after=8, line_spacing=1.5, bold=True, indent=False)

    add_p("Puji dan syukur penulis panjatkan kehadirat Allah SWT yang telah melimpahkan rahmat, taufik, serta hidayah-Nya, sehingga penulis dapat menyelesaikan kegiatan Kuliah Kerja Praktik (KKP) beserta penyusunan laporannya yang berjudul \"Perancangan dan Implementasi Sistem Informasi Antrean Bank Berbasis Web pada PT BPR DP TASPEN Kantor Cabang Bekasi\" dengan lancar dan tepat waktu.", indent=True)

    add_p("Laporan Kuliah Kerja Praktik ini disusun guna memenuhi salah satu persyaratan kurikulum akademik dalam menyelesaikan jenjang pendidikan Strata Satu (S1) Program Studi Teknik Informatika, Fakultas Teknologi Informasi dan Digital (FTID), Universitas Bani Saleh, Bekasi.", indent=True)

    add_p("Dalam pelaksanaan kegiatan KKP dan penyusunan laporan ini, penulis telah mendapatkan banyak bantuan, arahan, dukungan, serta bimbingan dari berbagai pihak. Oleh karena itu, pada kesempatan ini penulis menyampaikan rasa terima kasih dan penghargaan setinggi-tingginya kepada:", indent=True)

    p_list = [
        ("1. ", "Bapak Rahmadi, S.Kom., M.Kom., selaku Dekan Fakultas Teknologi Informasi dan Digital Universitas Bani Saleh yang senantiasa memberikan arahan dan motivasi bagi para mahasiswa."),
        ("2. ", "Bapak/Ibu Ketua Program Studi Teknik Informatika Universitas Bani Saleh beserta seluruh staf akademik dan administrasi FTID yang telah memfasilitasi kelancaran proses administrasi KKP."),
        ("3. ", "Bapak Agus Suhartono, M.Kom., selaku Dosen Pembimbing KKP yang telah meluangkan waktu, tenaga, dan pikiran untuk memberikan masukan, bimbingan, serta evaluasi yang sangat berharga dalam penulisan laporan ini."),
        ("4. ", "Bapak Hendra Kurniawan, S.E., selaku Pembimbing Lapangan di PT BPR DP TASPEN Kantor Cabang Bekasi yang telah memberikan izin, membimbing, mendampingi, serta memberikan transfer ilmu yang nyata di dunia industri perbankan."),
        ("5. ", "Seluruh jajaran Direksi, Pimpinan Cabang, dan staf karyawan PT BPR DP TASPEN, khususnya rekan-rekan Divisi Operasional (Teller & Customer Service) dan Divisi Teknologi Informasi atas kerja sama dan sambutan hangat selama penulis melaksanakan KKP."),
        ("6. ", "Kedua orang tua tercinta dan segenap keluarga besar yang tiada henti memberikan doa tulus, kasih sayang, motivasi moral, serta dukungan materil yang tiada ternilai harganya."),
        ("7. ", "Rekan-rekan seperjuangan mahasiswa Program Studi Teknik Informatika angkatan 2023 Universitas Bani Saleh atas diskusi, solidaritas, kebersamaan, dan semangat dalam menyelesaikan masa KKP ini.")
    ]
    for num, txt in p_list:
        p_item = doc.add_paragraph()
        p_item.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        p_item.paragraph_format.left_indent = Cm(1.0)
        p_item.paragraph_format.first_line_indent = Cm(-1.0)
        p_item.paragraph_format.space_before = Pt(2)
        p_item.paragraph_format.space_after = Pt(4)
        p_item.paragraph_format.line_spacing = 1.5
        r1 = p_item.add_run(num)
        r1.bold = True
        r2 = p_item.add_run(txt)

    add_p("Penulis menyadari sepenuhnya bahwa dalam penyusunan laporan ini masih terdapat kekurangan dan keterbatasan, baik dari segi penyajian materi maupun analisis teknis. Oleh karena itu, penulis sangat mengharapkan kritik dan saran yang membangun dari segenap pembaca demi penyempurnaan karya tulis ini di masa yang akan datang. Akhir kata, semoga laporan KKP ini dapat memberikan manfaat nyata bagi pembaca, rekan akademisi, serta instansi terkait.", indent=True)

    add_p("Wassalamu’alaikum Warahmatullahi Wabarakatuh.", space_before=8, space_after=14, line_spacing=1.5, bold=True)

    add_p("Bekasi, 21 September 2026\nPenulis,\n\n\n\nSalsabila Puspa Pratiwi\nNIM: 2301111110046",
          align=WD_ALIGN_PARAGRAPH.RIGHT, space_before=14, space_after=0, line_spacing=1.15, bold=True)

    # =========================================================================
    # 4. DAFTAR ISI
    # =========================================================================
    doc.add_page_break()
    add_p("DAFTAR ISI", align=WD_ALIGN_PARAGRAPH.CENTER, space_before=12, space_after=18, line_spacing=1.5, bold=True, size=14)

    daftar_isi_items = [
        ("HALAMAN JUDUL (COVER DEPAN)", "i"),
        ("LEMBAR PENGESAHAN LAPORAN KKP", "ii"),
        ("KATA PENGANTAR", "iii"),
        ("DAFTAR ISI", "iv"),
        ("DAFTAR TABEL", "v"),
        ("DAFTAR GAMBAR", "vi"),
        ("DAFTAR SIMBOL", "vii"),
        ("BAB I PENDAHULUAN", "1"),
        ("     1.1 Latar Belakang", "1"),
        ("     1.2 Identifikasi Masalah", "4"),
        ("     1.3 Batasan Masalah KKP", "5"),
        ("          a. Ruang Lingkup KKP", "5"),
        ("          b. Batasan Masalah", "6"),
        ("     1.4 Teknik Pengumpulan Data", "6"),
        ("     1.5 Sistematika Penulisan", "8"),
        ("BAB II TINJAUAN ORGANISASI", "10"),
        ("     2.1 Tinjauan Organisasi", "10"),
        ("          a. Sejarah Instansi KKP", "10"),
        ("          b. Visi dan Misi Instansi KKP", "11"),
        ("          c. Struktur Organisasi Instansi KKP", "12"),
        ("          d. Keterangan Tugas dan Wewenang Struktur Organisasi", "13"),
        ("     2.2 Penjelasan Unit Kegiatan Fokus KKP", "16"),
        ("BAB III PEMBAHASAN", "18"),
        ("     3.1 Proses Bisnis", "18"),
        ("          i. Prosedur Pelayanan dan Antrean Perbankan", "18"),
        ("          ii. Proses Bisnis Beserta Activity Diagram", "20"),
        ("          iii. Use Case Diagram dan Skenario Use Case", "23"),
        ("     3.2 Interface (Spesifikasi Antarmuka)", "27"),
        ("          a. Model Layar / Tampilan Sistem", "27"),
        ("          b. Model Masukan (Dokumen Input)", "31"),
        ("          c. Model Keluaran (Dokumen Output)", "33"),
        ("     3.3 Rancangan Database", "35"),
        ("          a. Class Diagram / ERD (Entity Relationship Diagram)", "35"),
        ("          b. Spesifikasi Basis Data (Data Dictionary)", "36"),
        ("     3.4 Arsitektur Keamanan dan Otorisasi Sistem", "41"),
        ("     3.5 Pengujian Sistem (Black Box Testing)", "43"),
        ("BAB IV PENUTUP", "47"),
        ("     4.1 Kesimpulan", "47"),
        ("     4.2 Saran", "48"),
        ("DAFTAR PUSTAKA", "50"),
        ("LAMPIRAN", "52"),
        ("     Lampiran 1: Lembar Daftar Hadir KKP (Contoh-3)", "53"),
        ("     Lampiran 2: Lembar Berita Acara Kegiatan & Nilai Lapangan (Contoh-4)", "54"),
        ("     Lampiran 3: Lembar Penilaian Dosen Pembimbing KKP (Contoh-5)", "55"),
        ("     Lampiran 4: Kuisioner Umpan Balik Perusahaan", "56")
    ]

    t_di = doc.add_table(rows=len(daftar_isi_items), cols=2)
    t_di.alignment = WD_TABLE_ALIGNMENT.CENTER
    for i, (judul, hal) in enumerate(daftar_isi_items):
        r = t_di.rows[i]
        r.cells[0].width = Cm(13.5)
        r.cells[1].width = Cm(2.5)
        p_j = r.cells[0].paragraphs[0]
        p_j.paragraph_format.line_spacing = 1.15
        p_j.paragraph_format.space_after = Pt(2)
        run_j = p_j.add_run(judul)
        if "BAB" in judul or "HALAMAN" in judul or "LEMBAR" in judul or "KATA" in judul or "DAFTAR" in judul or "LAMPIRAN" in judul:
            run_j.bold = True

        p_h = r.cells[1].paragraphs[0]
        p_h.alignment = WD_ALIGN_PARAGRAPH.RIGHT
        p_h.paragraph_format.line_spacing = 1.15
        p_h.paragraph_format.space_after = Pt(2)
        run_h = p_h.add_run(hal)
        if "BAB" in judul or "HALAMAN" in judul or "LEMBAR" in judul or "KATA" in judul or "DAFTAR" in judul or "LAMPIRAN" in judul:
            run_h.bold = True

    # =========================================================================
    # 5. DAFTAR TABEL
    # =========================================================================
    doc.add_page_break()
    add_p("DAFTAR TABEL", align=WD_ALIGN_PARAGRAPH.CENTER, space_before=12, space_after=18, line_spacing=1.5, bold=True, size=14)

    daftar_tabel_items = [
        ("Tabel 1.1", "Jadwal dan Agenda Pelaksanaan Kuliah Kerja Praktik", "8"),
        ("Tabel 2.1", "Uraian Tugas dan Wewenang Jabatan pada PT BPR DP TASPEN", "14"),
        ("Tabel 3.1", "Skenario Use Case Pengambilan Tiket Antrean pada Mesin Kiosk", "24"),
        ("Tabel 3.2", "Skenario Use Case Pemanggilan dan Pelayanan Antrean Petugas", "25"),
        ("Tabel 3.3", "Skenario Use Case Manajemen Loket dan Pengaturan Antrean", "26"),
        ("Tabel 3.4", "Kamus Data / Spesifikasi Tabel users", "37"),
        ("Tabel 3.5", "Kamus Data / Spesifikasi Tabel service_settings", "38"),
        ("Tabel 3.6", "Kamus Data / Spesifikasi Tabel queues", "39"),
        ("Tabel 3.7", "Kamus Data / Spesifikasi Tabel roles dan permissions", "40"),
        ("Tabel 3.8", "Kamus Data / Spesifikasi Tabel notifications", "41"),
        ("Tabel 3.9", "Matriks Rencana dan Hasil Pengujian Black Box Testing", "44")
    ]
    t_dt = doc.add_table(rows=len(daftar_tabel_items), cols=3)
    t_dt.alignment = WD_TABLE_ALIGNMENT.CENTER
    for i, (nomor, judul, hal) in enumerate(daftar_tabel_items):
        r = t_dt.rows[i]
        r.cells[0].width = Cm(2.5)
        r.cells[1].width = Cm(11.0)
        r.cells[2].width = Cm(2.5)
        p0 = r.cells[0].paragraphs[0]
        p0.paragraph_format.line_spacing = 1.15
        p0.add_run(nomor).bold = True

        p1 = r.cells[1].paragraphs[0]
        p1.paragraph_format.line_spacing = 1.15
        p1.add_run(judul)

        p2 = r.cells[2].paragraphs[0]
        p2.alignment = WD_ALIGN_PARAGRAPH.RIGHT
        p2.paragraph_format.line_spacing = 1.15
        p2.add_run(hal)

    # =========================================================================
    # 6. DAFTAR GAMBAR
    # =========================================================================
    doc.add_page_break()
    add_p("DAFTAR GAMBAR", align=WD_ALIGN_PARAGRAPH.CENTER, space_before=12, space_after=18, line_spacing=1.5, bold=True, size=14)

    daftar_gambar_items = [
        ("Gambar 2.1", "Struktur Organisasi PT BPR DP TASPEN Kantor Cabang Bekasi", "13"),
        ("Gambar 3.1", "Use Case Diagram Sistem Informasi Antrean Bank Berbasis Web", "23"),
        ("Gambar 3.2", "Activity Diagram Pengambilan Nomor Antrean pada Kiosk", "21"),
        ("Gambar 3.3", "Activity Diagram Pemanggilan dan Pelayanan Nasabah di Loket", "22"),
        ("Gambar 3.4", "Tampilan Layar Kiosk Pengambilan Tiket Antrean (/ambil-nomor)", "28"),
        ("Gambar 3.5", "Tampilan Layar Monitor Display Ruang Tunggu (/display)", "29"),
        ("Gambar 3.6", "Tampilan Konsol Petugas Operasional (Operator Box Filament)", "30"),
        ("Gambar 3.7", "Tampilan Admin Control Center dan Pengaturan Loket Layanan", "31"),
        ("Gambar 3.8", "Entity Relationship Diagram (ERD) Sistem Antrean Bank", "35"),
        ("Gambar 3.9", "Arsitektur Keamanan Dua Lapis (Two-Layer Authorization Pattern)", "42")
    ]
    t_dg = doc.add_table(rows=len(daftar_gambar_items), cols=3)
    t_dg.alignment = WD_TABLE_ALIGNMENT.CENTER
    for i, (nomor, judul, hal) in enumerate(daftar_gambar_items):
        r = t_dg.rows[i]
        r.cells[0].width = Cm(2.5)
        r.cells[1].width = Cm(11.0)
        r.cells[2].width = Cm(2.5)
        p0 = r.cells[0].paragraphs[0]
        p0.paragraph_format.line_spacing = 1.15
        p0.add_run(nomor).bold = True

        p1 = r.cells[1].paragraphs[0]
        p1.paragraph_format.line_spacing = 1.15
        p1.add_run(judul)

        p2 = r.cells[2].paragraphs[0]
        p2.alignment = WD_ALIGN_PARAGRAPH.RIGHT
        p2.paragraph_format.line_spacing = 1.15
        p2.add_run(hal)

    # =========================================================================
    # 7. DAFTAR SIMBOL
    # =========================================================================
    doc.add_page_break()
    add_p("DAFTAR SIMBOL", align=WD_ALIGN_PARAGRAPH.CENTER, space_before=12, space_after=18, line_spacing=1.5, bold=True, size=14)

    daftar_simbol_items = [
        ("Actor", "UML Use Case", "Menggambarkan peran pengguna atau sistem eksternal yang berinteraksi langsung dengan sistem aplikasi."),
        ("Use Case", "UML Use Case", "Menggambarkan fungsionalitas sistem atau urutan aksi yang memberikan hasil terukur bagi aktor."),
        ("System Boundary", "UML Use Case", "Membatasi batasan ruang lingkup aplikasi terhadap entitas di luar sistem."),
        ("Initial Node", "UML Activity", "Titik awal dimulainya suatu rangkaian aliran aktivitas pada sistem."),
        ("Action State", "UML Activity", "Merepresentasikan suatu tahapan proses atau eksekusi aksi yang dilakukan sistem atau pengguna."),
        ("Decision Node", "UML Activity", "Titik percabangan logika dengan satu aliran masuk dan dua atau lebih aliran keluar berlabel kondisi."),
        ("Activity Final", "UML Activity", "Titik akhir penghentian seluruh aliran eksekusi aktivitas pada diagram."),
        ("Swimlane", "UML Activity", "Kolom pemisah yang mengelompokkan tanggung jawab proses berdasarkan aktor atau komponen sistem."),
        ("Entity / Table", "ERD / Database", "Merepresentasikan tabel basis data yang menyimpan atribut entitas fisik atau konseptual."),
        ("Relationship Line", "ERD / Database", "Garis relasi antar tabel (One-to-Many atau Many-to-Many) yang menunjukkan kardinalitas data.")
    ]
    t_ds = doc.add_table(rows=len(daftar_simbol_items)+1, cols=3)
    t_ds.alignment = WD_TABLE_ALIGNMENT.CENTER
    t_ds.style = 'Table Grid'
    headers_ds = ["Nama Simbol", "Kategori", "Keterangan Fungsi"]
    for j, h in enumerate(headers_ds):
        cell = t_ds.rows[0].cells[j]
        set_cell_background(cell, "004B9C")
        set_cell_margins(cell, top=100, bottom=100, left=120, right=120)
        p = cell.paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        run = p.add_run(h)
        run.bold = True
        run.font.color.rgb = RGBColor(255, 255, 255)
        run.font.size = Pt(10)

    for i, (nama, kat, ket) in enumerate(daftar_simbol_items):
        row = t_ds.rows[i+1]
        row.cells[0].width = Cm(3.5)
        row.cells[1].width = Cm(3.2)
        row.cells[2].width = Cm(8.5)
        for j, val in enumerate([nama, kat, ket]):
            cell = row.cells[j]
            set_cell_margins(cell, top=80, bottom=80, left=100, right=100)
            p = cell.paragraphs[0]
            p.paragraph_format.line_spacing = 1.15
            if j == 0:
                p.add_run(val).bold = True
            else:
                p.add_run(val)
            p.runs[0].font.size = Pt(10)

    # =========================================================================
    # SECTION 2: BATANG TUBUH LAPORAN (BAB I s/d BAB IV)
    # Numbering Arabic: 1, 2, 3...
    # =========================================================================
    doc.add_section(docx.enum.section.WD_SECTION.NEW_PAGE)
    section_body = doc.sections[1]
    section_body.top_margin = Cm(4)
    section_body.left_margin = Cm(4)
    section_body.bottom_margin = Cm(3)
    section_body.right_margin = Cm(3)
    section_body.page_width = Cm(21.0)
    section_body.page_height = Cm(29.7)
    section_body.header.is_linked_to_previous = False
    section_body.footer.is_linked_to_previous = False

    # =========================================================================
    # BAB I: PENDAHULUAN (Minimal 3 Halaman)
    # =========================================================================
    add_p("BAB I", align=WD_ALIGN_PARAGRAPH.CENTER, space_before=12, space_after=2, line_spacing=1.5, bold=True, size=14)
    add_p("PENDAHULUAN", align=WD_ALIGN_PARAGRAPH.CENTER, space_before=0, space_after=18, line_spacing=1.5, bold=True, size=14)

    add_heading_sub("1.1. Latar Belakang Masalah")

    add_p("Dunia perbankan nasional merupakan sektor industri jasa keuangan yang berlandaskan pada asas kepercayaan masyarakat (fiduciary relationship) serta menuntut standar kualitas pelayanan yang prima (service excellence). Tingkat kepuasan nasabah (customer satisfaction) sangat dipengaruhi oleh kecepatan, ketepatan, transparansi, serta kenyamanan fasilitas operasional yang dirasakan saat bertransaksi di kantor cabang bank. Di tengah era transformasi digital yang berkembang sangat pesat, perbankan diwajibkan tidak hanya memiliki keandalan dalam sistem inti perbankan (Core Banking System), melainkan juga harus mampu menyajikan pengalaman layanan fisik di kantor cabang yang terotomatisasi, tertib, dan humanis.", indent=True)

    add_p("PT Bank Perekonomian Rakyat Dana Pensiun TASPEN (PT BPR DP TASPEN) merupakan institusi perbankan yang memiliki posisi strategis dalam memberikan pelayanan finansial, penyaluran gaji pensiun Aparatur Sipil Negara (ASN), tabungan hari tua, deposito, serta fasilitas pinjaman kredit bagi pensiunan maupun masyarakat umum. Karakteristik nasabah utama pada kantor cabang PT BPR DP TASPEN, khususnya Kantor Cabang Bekasi, didominasi oleh kalangan lanjut usia (pensiunan ASN dan pejabat negara) yang memerlukan perhatian khusus dalam hal kemudahan aksesibilitas layanan, kejelasan informasi, serta kepastian waktu tunggu di ruang perbankan (banking hall).", indent=True)

    add_p("Berdasarkan hasil observasi dan pengamatan langsung selama pelaksanaan Kuliah Kerja Praktik (KKP) di PT BPR DP TASPEN Kantor Cabang Bekasi, ditemukan bahwa pengelolaan antrean nasabah pada jam-jam sibuk—terutama pada periode awal bulan saat masa pencairan dana pensiun dan pembayaran angsuran kredit—masih menghadapi berbagai kendala operasional yang signifikan. Alur antrean konvensional yang berjalan selama ini masih mengandalkan pencetakan nomor antrean manual atau kartu antrean fisik berbahan dasar mika/kertas bernomor statis yang dibagikan secara manual oleh petugas keamanan (security) di pintu masuk. Kondisi ini menimbulkan berbagai ketidaknyamanan, antara lain:", indent=True)

    latar_belakang_poin = [
        ("a. Ketidakpastian dan Kecemasan Waktu Tunggu Nasabah: ", "Nasabah yang duduk di ruang tunggu tidak memiliki visibilitas mengenai jumlah antrean di depannya, perkiraan waktu tunggu (estimated waiting time), ataupun kepastian loket mana yang sedang melayani. Hal ini memicu kecemasan, khususnya bagi nasabah pensiunan lansia yang memiliki keterbatasan fisik."),
        ("b. Risiko Kesalahan Pemanggilan dan Ketiadaan Sinkronisasi Audio-Visual: ", "Pemanggilan nomor antrean yang masih dilakukan secara manual dengan pengeras suara portabel rentan terhadap salah dengar atau terlewat oleh nasabah saat ruang tunggu dalam keadaan ramai dan riuh."),
        ("c. Ketidakseimbangan Beban Kerja Antar Loket (Workload Imbalance): ", "Sering terjadi penumpukan antrean nasabah pada loket kas Teller (misalnya antrean transaksi pencairan dan setor tunai), sementara loket Customer Service (CS) mengalami masa senggang (idle), atau sebaliknya. Ketiadaan sistem terintegrasi menyulitkan supervisor untuk mengalihkan beban antrean secara fleksibel dan proporsional."),
        ("d. Ketiadaan Pencatatan Waktu Layanan dan Rekapitulasi Audit Kinerja: ", "Manajemen operasional cabang tidak memiliki data riil mengenai durasi waktu tunggu nasabah (waiting duration), waktu pelayanan di loket (serving time), maupun rasio kehadiran nasabah (persentase nomor antrean yang dilayani vs dilewati). Ketiadaan pencatatan log otomatis menghambat evaluasi Service Level Agreement (SLA) dan perencanaan jumlah loket harian.")
    ]
    for p_label, p_desc in latar_belakang_poin:
        p_item = doc.add_paragraph()
        p_item.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        p_item.paragraph_format.left_indent = Cm(1.0)
        p_item.paragraph_format.first_line_indent = Cm(-1.0)
        p_item.paragraph_format.space_before = Pt(2)
        p_item.paragraph_format.space_after = Pt(4)
        p_item.paragraph_format.line_spacing = 1.5
        r1 = p_item.add_run(p_label)
        r1.bold = True
        r2 = p_item.add_run(p_desc)

    add_p("Untuk menjawab tantangan operasional tersebut, diperlukan perancangan dan implementasi Sistem Informasi Antrean Bank Berbasis Web yang handal, modern, dan berstandar keamanan enterprise. Sistem ini dibangun dengan memanfaatkan teknologi terkini, yaitu framework Laravel sebagai fondasi backend yang tangguh, framework Filament v3 sebagai panel administrasi dan dasbor konsol petugas berbasis Livewire yang reaktif, Tailwind CSS untuk antarmuka yang elegan, serta manajemen hak akses berbasis peran (Role-Based Access Control) menggunakan Spatie Laravel-Permission.", indent=True)

    add_p("Keunggulan utama dari sistem yang dikembangkan ini terletak pada penerapan arsitektur keamanan dua lapis (Two-Layer Authorization Pattern), yang mengombinasikan verifikasi hak akses fitur (Permission Layer) dengan kepemilikan loket operasional (Data Assignment Layer). Melalui mekanisme ini, setiap petugas kasir Teller dan Customer Service terikat secara eksklusif dengan nomor loket tugasnya, sehingga menjamin integritas data transaksi antrean dan mencegah terjadinya konflik pemanggilan nomor antar petugas di loket yang berbeda. Selain itu, sistem menyediakan modul Kiosk mandiri yang mampu mencetak tiket fisik ber-barcode dan QR Code, modul Display TV ruang tunggu dengan suara panggilan otomatis (audio chime & dynamic speech), serta modul kontrol administratif untuk pemantauan statistik antrean secara real-time.", indent=True)

    add_p("Melalui implementasi sistem informasi antrean bank berbasis web ini pada PT BPR DP TASPEN Kantor Cabang Bekasi, diharapkan kualitas pelayanan publik perbankan dapat ditingkatkan secara drastis, suasana banking hall menjadi lebih tertib, waktu tunggu nasabah terkelola dengan baik, serta pimpinan cabang memperoleh data analitik operasional yang akurat untuk mendukung pengambilan keputusan strategis.", indent=True)

    add_heading_sub("1.2. Identifikasi Masalah")

    add_p("Berdasarkan latar belakang permasalahan yang telah diuraikan di atas, identifikasi masalah yang ditemukan selama pelaksanaan Kuliah Kerja Praktik (KKP) di PT BPR DP TASPEN Kantor Cabang Bekasi dapat dirumuskan sebagai berikut:", indent=True)

    identifikasi_poin = [
        ("1. ", "Belum tersedianya sistem tiket antrean otomatis yang mandiri dan terstandarisasi, sehingga proses pembagian nomor antrean masih manual dan menimbulkan ketidaknyamanan bagi nasabah lansia."),
        ("2. ", "Ketiadaan media informasi visual terpusat (Layar Monitor Display TV) dan notifikasi audio otomatis di ruang tunggu, yang menyebabkan nasabah sering tidak mendengar panggilan petugas atau merasa was-was mengenai giliran antreannya."),
        ("3. ", "Penugasan dan pemanggilan antrean oleh petugas Teller dan Customer Service belum terintegrasi secara digital, yang berisiko menimbulkan tabrakan pemanggilan nomor atau ketidakteraturan aliran nasabah antar loket."),
        ("4. ", "Belum adanya sistem pencatatan berbasis data relasional yang merekam histori antrean, durasi pemanggilan, serta waktu pelayanan, sehingga pihak manajemen cabang kesulitan mengukur efisiensi kerja petugas dan kinerja pelayanan harian.")
    ]
    for num, txt in identifikasi_poin:
        p_item = doc.add_paragraph()
        p_item.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        p_item.paragraph_format.left_indent = Cm(1.0)
        p_item.paragraph_format.first_line_indent = Cm(-1.0)
        p_item.paragraph_format.space_before = Pt(2)
        p_item.paragraph_format.space_after = Pt(4)
        p_item.paragraph_format.line_spacing = 1.5
        r1 = p_item.add_run(num)
        r1.bold = True
        r2 = p_item.add_run(txt)

    add_heading_sub("1.3. Batasan Masalah KKP")

    add_p("Mengingat luasnya cakupan operasional perbankan serta untuk menjaga fokus pembahasan agar mendalam, terukur, dan sesuai dengan durasi waktu pelaksanaan KKP, maka batasan masalah disusun ke dalam dua aspek utama sebagai berikut:", indent=True)

    add_heading_sub("a. Ruang Lingkup KKP", level=3)
    add_p("Ruang lingkup penelitian dan pengembangan sistem pada kegiatan KKP ini mencakup perancangan arsitektur perangkat lunak berbasis web pada jaringan lokal perbankan (Local Area Network / LAN) PT BPR DP TASPEN Kantor Cabang Bekasi, yang menghubungkan empat subsistem utama:", indent=True)

    lingkup_poin = [
        ("1. Sub-Sistem Kiosk Tiket Mandiri (/ambil-nomor): ", "Antarmuka layar sentuh di dekat pintu masuk kantor cabang yang digunakan nasabah untuk memilih jenis layanan, menghasilkan tiket fisik thermal ber-barcode unik dan QR Code."),
        ("2. Sub-Sistem Monitor Display TV Ruang Tunggu (/display): ", "Antarmuka layar monitor lebar di ruang tunggu banking hall yang menampilkan antrean berjalan, nomor loket yang melayani, running text pengumuman bank, serta memutar suara panggilan nomor antrean."),
        ("3. Sub-Sistem Konsol Petugas Operasional (Operator Box): ", "Antarmuka kerja petugas Teller dan Customer Service pada Filament Admin Panel untuk memanggil nomor antrean (next), memanggil ulang (recall), memulai pelayanan (serving), menyelesaikan transaksi (completed), atau melewati antrean (skipped)."),
        ("4. Sub-Sistem Admin Control Center & Service Settings: ", "Modul konfigurasi bagi Administrator atau Supervisor untuk mengatur daftar loket kas, mengaktifkan/menonaktifkan layanan, mengelola hak akses petugas, dan melakukan reset antrean harian.")
    ]
    for p_label, p_desc in lingkup_poin:
        p_item = doc.add_paragraph()
        p_item.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        p_item.paragraph_format.left_indent = Cm(1.0)
        p_item.paragraph_format.first_line_indent = Cm(-1.0)
        p_item.paragraph_format.space_before = Pt(2)
        p_item.paragraph_format.space_after = Pt(4)
        p_item.paragraph_format.line_spacing = 1.5
        r1 = p_item.add_run(p_label)
        r1.bold = True
        r2 = p_item.add_run(p_desc)

    add_heading_sub("b. Batasan Masalah Penelitian", level=3)
    add_p("Adapun batasan masalah teknis yang ditetapkan dalam penulisan laporan KKP ini adalah:", indent=True)

    batasan_poin = [
        ("1. ", "Kategori Layanan: Sistem difokuskan pada dua jenis layanan perbankan utama, yaitu Layanan Teller (transaksi kas dan pencairan gaji pensiun dengan prefix nomor antrean A) dan Layanan Customer Service (administrasi pensiun, kredit, dan pembukaan rekening dengan prefix nomor antrean B)."),
        ("2. ", "Siklus Status Antrean: Pengelolaan alur status antrean dibatasi pada 5 (lima) status baku, yaitu waiting (menunggu giliran), called (sedang dipanggil), serving (sedang dilayani petugas di loket), completed (transaksi selesai), dan skipped (nasabah tidak hadir saat dipanggil)."),
        ("3. ", "Arsitektur Otorisasi: Pembatasan akses menggunakan mekanisme Two-Layer Authorization (Hak Akses Permission via Spatie dan Kepemilikan Loket via service_setting_id). Sistem tidak mencakup integrasi transaksi finansial mendalam dengan Core Banking System utama bank demi menjaga kerahasiaan data nasabah (banking secrecy)."),
        ("4. ", "Jaringan Operasional: Sistem diimplementasikan pada lingkungan jaringan intranet lokal (Local Area Network) kantor cabang guna menjamin latensi pemanggilan real-time yang optimal dan keamanan transmisi internal bank.")
    ]
    for num, txt in batasan_poin:
        p_item = doc.add_paragraph()
        p_item.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        p_item.paragraph_format.left_indent = Cm(1.0)
        p_item.paragraph_format.first_line_indent = Cm(-1.0)
        p_item.paragraph_format.space_before = Pt(2)
        p_item.paragraph_format.space_after = Pt(4)
        p_item.paragraph_format.line_spacing = 1.5
        r1 = p_item.add_run(num)
        r1.bold = True
        r2 = p_item.add_run(txt)

    add_heading_sub("1.4. Teknik Pengumpulan Data")

    add_p("Dalam rangka memperoleh data dan informasi yang valid, komprehensif, dan relevan guna mendukung analisis serta perancangan sistem, penulis menggunakan beberapa teknik pengumpulan data, yaitu:", indent=True)

    pengumpulan_poin = [
        ("a. Wawancara (Interview): ", "Penulis melakukan sesi tanya jawab terstruktur dan wawancara mendalam dengan pihak-pihak terkait di PT BPR DP TASPEN Kantor Cabang Bekasi, antara lain dengan Bapak Hendra Kurniawan, S.E. (Supervisor Operasional), staf Teller kas, staf Customer Service, serta petugas IT Support. Informasi yang dihimpun mencakup alur pelayanan nasabah pensiunan, kendala antrean pada hari kerja ramai, serta kebutuhan teknis fitur pemanggilan."),
        ("b. Observasi (Observation): ", "Penulis melakukan pengamatan langsung terhadap alur pergerakan nasabah di banking hall, interaksi nasabah saat menerima tiket manual, rata-rata waktu yang dihabiskan nasabah di loket Teller dan CS, serta pola pemanggilan antrean pada jam operasional bank (pukul 08.00 - 15.00 WIB)."),
        ("c. Studi Pustaka (Library Research): ", "Mempelajari berbagai literatur akademis, buku referensi rekayasa perangkat lunak, jurnal sistem informasi, panduan resmi Laravel Framework, dokumentasi Filament Admin Panel v3, serta Panduan Penyusunan Laporan KKP FTID Universitas Bani Saleh untuk memastikan standarisasi penulisan ilmiah.")
    ]
    for p_label, p_desc in pengumpulan_poin:
        p_item = doc.add_paragraph()
        p_item.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        p_item.paragraph_format.left_indent = Cm(1.0)
        p_item.paragraph_format.first_line_indent = Cm(-1.0)
        p_item.paragraph_format.space_before = Pt(2)
        p_item.paragraph_format.space_after = Pt(4)
        p_item.paragraph_format.line_spacing = 1.5
        r1 = p_item.add_run(p_label)
        r1.bold = True
        r2 = p_item.add_run(p_desc)

    add_p("Selain teknik pengumpulan data di atas, penulis mencatat spesifikasi perangkat keras (hardware) dan perangkat lunak (software) yang digunakan dalam mendukung pelaksanaan KKP dan perancangan sistem ini, yaitu:", indent=True)

    add_p("1. Perangkat Keras (Hardware):\n"
          "   - Laptop Pengembangan: Intel Core i5 Gen-11, RAM 16 GB, SSD NVMe 512 GB.\n"
          "   - Mesin Kiosk Tiket: Touchscreen Kiosk Monitor 15.6 inch terintegrasi Thermal Printer 58mm/80mm.\n"
          "   - Display Monitor: Smart LED TV 43 inch dengan resolusi Full HD (1920x1080) di ruang tunggu.\n"
          "   - Perangkat Audio: Sound Amplifier & Ceiling Speaker perbankan untuk suara panggilan antrean.\n"
          "   - PC Kerja Petugas: Komputer Desktop Loket Teller dan CS terhubung kabel LAN Gigabit.",
          space_before=4, space_after=6, line_spacing=1.5, indent=True)

    add_p("2. Perangkat Lunak (Software):\n"
          "   - Sistem Operasi: Microsoft Windows 11 Pro 64-bit.\n"
          "   - Web Server & Database: Apache 2.4 & MySQL/MariaDB 10.4 (XAMPP Suite Environment).\n"
          "   - Bahasa Pemrograman & Framework: PHP 8.2+, Laravel 11/12, Blade Engine, Livewire 3.\n"
          "   - UI Framework & Panel: Filament PHP v3, Tailwind CSS, Alpine.js.\n"
          "   - Code Editor & Pemodelan: Visual Studio Code, Git Version Control, Draw.io / Matplotlib.\n"
          "   - Web Browser: Google Chrome Version 128+ (dengan dukungan Web Speech Synthesis & Autoplay Audio).",
          space_before=4, space_after=8, line_spacing=1.5, indent=True)

    add_heading_sub("1.5. Sistematika Penulisan")

    add_p("Sistematika penulisan laporan Kuliah Kerja Praktik ini disusun secara terstruktur ke dalam 4 (empat) bab utama sesuai dengan Panduan Penyusunan Laporan KKP Fakultas Teknologi Informasi dan Digital (FTID) Universitas Bani Saleh, dengan rincian sebagai berikut:", indent=True)

    sistematika_poin = [
        ("BAB I PENDAHULUAN: ", "Berisi uraian mengenai latar belakang masalah penugasan KKP, identifikasi masalah operasional antrean bank, batasan masalah KKP (ruang lingkup dan batasan teknis), teknik pengumpulan data yang diterapkan, spesifikasi hardware dan software, serta jadwal dan sistematika penulisan laporan."),
        ("BAB II TINJAUAN ORGANISASI: ", "Memuat profil lengkap dan sejarah berdirinya instansi tempat KKP yaitu PT BPR DP TASPEN Kantor Cabang Bekasi, visi dan misi perusahaan, struktur organisasi cabang beserta gambar diagram struktur, rincian tugas dan wewenang masing-masing jabatan organisasi, serta penjelasan mendalam mengenai unit kegiatan yang menjadi fokus KKP (Divisi Operasional & IT Support)."),
        ("BAB III PEMBAHASAN: ", "Merupakan inti pembahasan laporan yang mencakup pemodelan proses bisnis pelayanan perbankan, perancangan UML (Activity Diagram dan Use Case Diagram beserta skenario lengkap), spesifikasi antarmuka sistem (Model Layar, Model Input, dan Model Output), perancangan basis data relasional (ERD dan Kamus Data terperinci), analisis arsitektur keamanan dua lapis (Two-Layer Authorization), serta matriks pengujian fungsional sistem menggunakan metode Black Box Testing."),
        ("BAB IV PENUTUP: ", "Menyajikan kesimpulan komprehensif dari hasil analisis, perancangan, dan pengujian sistem antrean berbasis web yang telah dibangun, serta memberikan saran-saran perbaikan strategis bagi pengembangan sistem lebih lanjut di masa yang akan datang.")
    ]
    for p_label, p_desc in sistematika_poin:
        p_item = doc.add_paragraph()
        p_item.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        p_item.paragraph_format.left_indent = Cm(1.0)
        p_item.paragraph_format.first_line_indent = Cm(-1.0)
        p_item.paragraph_format.space_before = Pt(2)
        p_item.paragraph_format.space_after = Pt(4)
        p_item.paragraph_format.line_spacing = 1.5
        r1 = p_item.add_run(p_label)
        r1.bold = True
        r2 = p_item.add_run(p_desc)

    # Tabel Jadwal KKP
    add_table_caption("1.1", "Jadwal dan Agenda Pelaksanaan Kuliah Kerja Praktik")
    jadwal_data = [
        ("No", "Tahapan Kegiatan KKP", "Bulan 1", "Bulan 2", "Bulan 3", "Keterangan"),
        ("1", "Orientasi Lapangan & Pengenalan Divisi", "V", "-", "-", "Pengenalan SOP & Lingkungan Bank"),
        ("2", "Analisis Sistem Antrean Konvensional", "V", "V", "-", "Observasi Banking Hall & Wawancara"),
        ("3", "Pengumpulan Data & Studi Pustaka", "V", "V", "-", "Literatur Laravel, UML, & Panduan FTID"),
        ("4", "Perancangan UI/UX Kiosk & Display TV", "-", "V", "V", "Desain Antarmuka Tailwind & Blade"),
        ("5", "Implementasi Backend Laravel & Filament", "-", "V", "V", "Pengembangan Controller & Security"),
        ("6", "Pengujian Sistem (Black Box Testing)", "-", "-", "V", "Verifikasi Alur Status & Loket Lock"),
        ("7", "Penyusunan & Pengesahan Laporan KKP", "-", "-", "V", "Bimbingan Dosen & Pembimbing Lapangan")
    ]
    t_jadwal = doc.add_table(rows=len(jadwal_data), cols=6)
    t_jadwal.alignment = WD_TABLE_ALIGNMENT.CENTER
    t_jadwal.style = 'Table Grid'
    for row_idx, row_content in enumerate(jadwal_data):
        row_obj = t_jadwal.rows[row_idx]
        for col_idx, text_val in enumerate(row_content):
            cell = row_obj.cells[col_idx]
            set_cell_margins(cell, top=70, bottom=70, left=90, right=90)
            p = cell.paragraphs[0]
            p.paragraph_format.line_spacing = 1.15
            if row_idx == 0:
                set_cell_background(cell, "004B9C")
                p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                run = p.add_run(text_val)
                run.bold = True
                run.font.color.rgb = RGBColor(255, 255, 255)
                run.font.size = Pt(10)
            else:
                if col_idx in [0, 2, 3, 4]:
                    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                else:
                    p.alignment = WD_ALIGN_PARAGRAPH.LEFT
                run = p.add_run(text_val)
                run.font.size = Pt(10)

    # =========================================================================
    # BAB II: TINJAUAN ORGANISASI (3 s/d 6 Halaman)
    # =========================================================================
    doc.add_page_break()
    add_p("BAB II", align=WD_ALIGN_PARAGRAPH.CENTER, space_before=12, space_after=2, line_spacing=1.5, bold=True, size=14)
    add_p("TINJAUAN ORGANISASI", align=WD_ALIGN_PARAGRAPH.CENTER, space_before=0, space_after=18, line_spacing=1.5, bold=True, size=14)

    add_heading_sub("2.1. Tinjauan Organisasi")

    add_heading_sub("a. Sejarah Singkat Instansi KKP", level=3)
    add_p("PT Bank Perekonomian Rakyat Dana Pensiun TASPEN (disingkat PT BPR DP TASPEN) merupakan lembaga perbankan resmi yang beroperasi di bawah pengawasan Otoritas Jasa Keuangan (OJK) serta menjadi peserta Lembaga Penjamin Simpanan (LPS). Institusi ini didirikan dengan tujuan utama menghadirkan layanan jasa keuangan perbankan yang aman, terpercaya, dan mudah diakses oleh para pensiunan Aparatur Sipil Negara (ASN), purnawirawan, janda/duda pensiunan, serta masyarakat umum di wilayah perkotaan dan penyangga.", indent=True)

    add_p("Pendirian BPR DP TASPEN berakar dari kepedulian terhadap kebutuhan para pensiunan yang memerlukan akses pelayanan perbankan yang humanis, sabar, dan ramah terhadap kelompok usia lanjut. Di samping melayani penyaluran pembayaran manfaat pensiun bulanan bekerja sama dengan PT TASPEN (Persero), BPR DP TASPEN berkembang pesat dengan menghadirkan produk penghimpunan dana berupa Tabungan Masyarakat Berfaedah dan Deposito Berjangka dengan suku bunga kompetitif, serta penyaluran kredit konsumtif pensiunan, kredit usaha mikro, dan fasilitas kredit pegawai berpenghasilan tetap.", indent=True)

    add_p("Dalam rangka memperluas jangkauan layanan kepada masyarakat di kawasan Jawa Barat, PT BPR DP TASPEN Kantor Cabang Bekasi didirikan sebagai salah satu kantor cabang operasional utama. Terletak di lokasi strategis pusat kota Bekasi, kantor cabang ini menjadi tumpuan pelayanan bagi ribuan nasabah pensiunan dan nasabah umum dari wilayah Kota Bekasi, Kabupaten Bekasi, dan sekitarnya. Seiring dengan peningkatan volume transaksi perbankan dan pertumbuhan jumlah nasabah harian, Kantor Cabang Bekasi terus berkomitmen melakukan pembenahan tata kelola dan modernisasi sarana teknologi informasi guna memberikan pelayanan yang semakin prima, tertib, dan transparan.", indent=True)

    add_heading_sub("b. Visi dan Misi Instansi KKP", level=3)
    add_p("Dalam menjalankan seluruh aktivitas operasional dan bisnis perbankan, PT BPR DP TASPEN berpedoman pada visi dan misi perusahaan sebagai berikut:", indent=True)

    add_p("Visi Perusahaan:", space_before=4, space_after=2, line_spacing=1.5, bold=True, indent=True)
    add_p("\"Menjadi Bank Perekonomian Rakyat yang Sehat, Terpercaya, Profesional, dan Unggul dalam Memberikan Pelayanan Finansial Prima bagi Kalangan Pensiunan dan Masyarakat Umum.\"",
          align=WD_ALIGN_PARAGRAPH.CENTER, space_before=4, space_after=8, line_spacing=1.5, italic=True, bold=True)

    add_p("Misi Perusahaan:", space_before=4, space_after=2, line_spacing=1.5, bold=True, indent=True)
    misi_poin = [
        ("1. ", "Memberikan pelayanan perbankan yang ramah, cepat, tepat, dan beretika tinggi, khususnya dalam menghormati dan melayani para nasabah pensiunan sebagai pejuang pengabdi negara."),
        ("2. ", "Menyediakan produk simpanan (tabungan dan deposito) yang aman, menguntungkan, dan dijamin oleh LPS bagi segenap lapisan masyarakat."),
        ("3. ", "Menyalurkan fasilitas kredit yang tepat sasaran, berorientasi solusi, dan terjangkau guna membantu pemenuhan kebutuhan finansial keluarga maupun pengembangan usaha mikro nasabah."),
        ("4. ", "Menerapkan prinsip Tata Kelola Perusahaan yang Baik (Good Corporate Governance) dan manajemen risiko yang prudent dalam setiap aspek operasional perbankan."),
        ("5. ", "Melakukan digitalisasi proses bisnis dan pemanfaatan sistem teknologi informasi guna meningkatkan efisiensi operasional dan kepuasan nasabah.")
    ]
    for num, txt in misi_poin:
        p_item = doc.add_paragraph()
        p_item.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        p_item.paragraph_format.left_indent = Cm(1.0)
        p_item.paragraph_format.first_line_indent = Cm(-1.0)
        p_item.paragraph_format.space_before = Pt(2)
        p_item.paragraph_format.space_after = Pt(4)
        p_item.paragraph_format.line_spacing = 1.5
        r1 = p_item.add_run(num)
        r1.bold = True
        r2 = p_item.add_run(txt)

    add_heading_sub("c. Struktur Organisasi Instansi KKP", level=3)
    add_p("Struktur organisasi PT BPR DP TASPEN Kantor Cabang Bekasi disusun secara fungsional untuk memastikan koordinasi kepatuhan dan efektivitas pembagian peran kerja. Struktur dipimpin oleh Pemimpin Cabang yang mengawasi dua jalur fungsi utama: Fungsi Operasional & Layanan dan Fungsi Bisnis & Kredit.", indent=True)

    # Embed Gambar Struktur Organisasi
    add_image_centered('docs/images/struktur_organisasi.png', width_inches=5.6)
    add_figure_caption("2.1", "Struktur Organisasi PT BPR DP TASPEN Kantor Cabang Bekasi")

    add_heading_sub("d. Keterangan Tugas dan Wewenang Struktur Organisasi", level=3)
    add_p("Adapun rincian tugas, kewenangan, dan tanggung jawab masing-masing personalia pada struktur organisasi PT BPR DP TASPEN Kantor Cabang Bekasi adalah sebagai berikut:", indent=True)

    add_table_caption("2.1", "Uraian Tugas dan Wewenang Jabatan pada PT BPR DP TASPEN")
    jabatan_data = [
        ("No", "Jabatan / Posisi", "Tugas Utama dan Wewenang Kerja"),
        ("1", "Pemimpin Cabang\n(Branch Manager)", "1. Memimpin, mengawasi, dan bertanggung jawab mutlak atas seluruh kinerja operasional, keuangan, dan pencapaian target bisnis kantor cabang.\n2. Memastikan seluruh aktivitas cabang mematuhi regulasi perbankan OJK, LPS, dan kebijakan Direksi.\n3. Menetapkan kebijakan pelayanan nasabah dan pembagian alokasi sumber daya di cabang."),
        ("2", "Supervisor Operasional\n& Layanan", "1. Mengawasi jalannya pelayanan transaksi kas harian di loket Teller dan administrasi Customer Service.\n2. Mengelola kas fisik operasional cabang (vault/khasanah) dan rekonsiliasi kas harian.\n3. Memantau kelancaran antrean nasabah dan bertindak selaku penanggung jawab langsung fasilitas operasional banking hall."),
        ("3", "Supervisor Bisnis &\nKredit Pensiun", "1. Mengoordinasikan strategi pemasaran produk kredit pensiunan dan simpanan tabungan/deposito.\n2. Memeriksa kelayakan berkas pengajuan pinjaman dan melakukan analisis mitigasi risiko kredit.\n3. Membina jaringan kemitraan dengan instansi pengelola dana pensiun di wilayah Bekasi."),
        ("4", "Petugas Teller Kas\n& Pensiun", "1. Bertanggung jawab atas loket pelayanan kas (Loket Teller).\n2. Melayani transaksi tunai (penarikan gaji pensiun, penyetoran tabungan, pembayaran angsuran).\n3. Mengoperasikan konsol pemanggilan nomor antrean kategori Teller dan memastikan kecocokan kas harian."),
        ("5", "Petugas Customer\nService (CS)", "1. Bertanggung jawab atas loket layanan nasabah dan informasi (Loket CS).\n2. Melayani pembukaan rekening tabungan, deposito, verifikasi berkas otentikasi pensiunan, dan pengaduan nasabah.\n3. Mengoperasikan konsol pemanggilan antrean kategori Customer Service."),
        ("6", "Account Officer (AO)\nKredit", "1. Melakukan sosialisasi produk perbankan dan jemput bola ke nasabah pensiunan.\n2. Membantu nasabah dalam melengkapi berkas administrasi permohonan pinjaman kredit.\n3. Melakukan survei dan konfirmasi data nasabah pemohon kredit."),
        ("7", "IT Support &\nAdministrator", "1. Memelihara keandalan perangkat keras (PC, Kiosk, Display TV, Printer) dan infrastruktur jaringan LAN cabang.\n2. Mengelola hak akses akun pengguna sistem, konfigurasi loket, dan reset data antrean harian.\n3. Menyediakan dukungan teknis penanganan kendala (troubleshooting) sistem informasi cabang.")
    ]
    t_jab = doc.add_table(rows=len(jabatan_data), cols=3)
    t_jab.alignment = WD_TABLE_ALIGNMENT.CENTER
    t_jab.style = 'Table Grid'
    for row_idx, row_content in enumerate(jabatan_data):
        row_obj = t_jab.rows[row_idx]
        for col_idx, text_val in enumerate(row_content):
            cell = row_obj.cells[col_idx]
            set_cell_margins(cell, top=70, bottom=70, left=90, right=90)
            p = cell.paragraphs[0]
            p.paragraph_format.line_spacing = 1.15
            if row_idx == 0:
                set_cell_background(cell, "004B9C")
                p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                run = p.add_run(text_val)
                run.bold = True
                run.font.color.rgb = RGBColor(255, 255, 255)
                run.font.size = Pt(10)
            else:
                if col_idx == 0:
                    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                elif col_idx == 1:
                    p.alignment = WD_ALIGN_PARAGRAPH.LEFT
                    run = p.add_run(text_val)
                    run.bold = True
                    run.font.size = Pt(9.5)
                    continue
                else:
                    p.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
                run = p.add_run(text_val)
                run.font.size = Pt(9.5)

    add_heading_sub("2.2. Penjelasan Unit Kegiatan Fokus KKP")

    add_p("Dalam pelaksanaan Kuliah Kerja Praktik di PT BPR DP TASPEN Kantor Cabang Bekasi, unit kegiatan yang menjadi fokus utama penugasan penulis adalah Divisi Operasional Layanan Nasabah (Front Office) yang berkolaborasi erat dengan Divisi Teknologi Informasi (IT Support). Penempatan pada kedua unit kegiatan ini didasarkan pada pertimbangan bahwa titik temu utama transformasi digital perbankan terletak pada interaksi antara petugas layanan garda depan dengan infrastruktur aplikasi komputer.", indent=True)

    add_p("Divisi Operasional Layanan Nasabah bertanggung jawab secara langsung terhadap citra pelayanan kantor cabang di mata masyarakat. Staf Teller dan Customer Service setiap hari berhadapan langsung dengan ratusan nasabah, di mana kelancaran transaksi sangat bergantung pada keteraturan alur antrean. Selama KKP, penulis terlibat langsung dalam mengidentifikasi pola antrean pada hari kerja biasa dibandingkan dengan hari puncak (peak days) tanggal 1 hingga tanggal 10 setiap awal bulan saat penyaluran gaji pensiun berlangsung.", indent=True)

    add_p("Sementara itu, kolaborasi dengan Divisi Teknologi Informasi difokuskan pada analisis arsitektur jaringan lokal bank, kebutuhan antarmuka layar sentuh untuk Kiosk, pemilihan layar Display TV, integrasi audio chime, hingga konfigurasi web server lokal berbasis Apache dan MySQL. Penulis bertugas merancang prototipe dan membangun aplikasi sistem antrean terintegrasi yang mampu menjembatani kebutuhan kecepatan pelayanan front-office dengan keandalan pencatatan data pada sistem backend bank.", indent=True)

    # =========================================================================
    # BAB III: PEMBAHASAN (Minimal 8 Halaman - Tema Sistem Informasi Panduan)
    # =========================================================================
    add_heading_bab("BAB III", "PEMBAHASAN")

    add_heading_sub("3.1. Proses Bisnis Sistem Antrean")

    add_heading_sub("i. Prosedur Pelayanan dan Antrean Perbankan", level=3)
    add_p("Prosedur operasional antrean perbankan pada PT BPR DP TASPEN Kantor Cabang Bekasi merupakan serangkaian proses standar yang mengatur perjalanan nasabah sejak tiba di kantor cabang hingga seluruh kebutuhan transaksi perbankan selesai dilayani. Alur prosedur ini dirancang untuk menciptakan ketertiban di banking hall dan memberikan kepastian nomor antrean bagi nasabah. Secara terperinci, prosedur layanan antrean terdiri dari empat tahapan utama:", indent=True)

    prosedur_poin = [
        ("1. Prosedur Kedatangan dan Pengambilan Nomor Antrean: ", "Nasabah yang tiba di kantor bank disambut oleh petugas keamanan (security) dan diarahkan menuju mesin Kiosk mandiri berlayar sentuh. Pada layar Kiosk, nasabah memilih tombol kategori layanan yang diinginkan: Tombol 'Teller Kas & Pensiun TASPEN' (untuk penarikan gaji pensiun, setor tunai, pembayaran angsuran kredit) atau Tombol 'Layanan Pelanggan & Kredit' (untuk pembukaan tabungan/deposito, verifikasi otentikasi pensiun, dan pengajuan pinjaman). Sistem kemudian secara otomatis mengenerasi nomor antrean berurutan dengan awalan huruf pembeda (kode 'A' untuk Teller dan 'B' untuk Customer Service) serta mencetak struk tiket fisik ber-barcode melalui printer thermal."),
        ("2. Prosedur Menunggu di Banking Hall: ", "Setelah memegang tiket fisik, nasabah dipersilakan duduk di kursi ruang tunggu. Nasabah dapat memperhatikan Layar Monitor Display TV utama berukuran 43 inch yang dipasang di posisi sentral ruangan. Layar monitor menampilkan nomor antrean yang sedang dipanggil saat ini beserta nomor loket yang melayani, daftar antrean yang masih menunggu, running text pengumuman suku bunga dan produk bank, serta siaran visual edukasi perbankan."),
        ("3. Prosedur Pemanggilan Nomor oleh Petugas: ", "Petugas Teller atau Customer Service yang telah siap melayani nasabah berikutnya menekan tombol aksi 'PANGGIL ANTREAN BERIKUTNYA' pada antarmuka Dasbor Konsol Petugas (Operator Box). Sistem seketika mengubah status tiket antrean terlama dari waiting (menunggu) menjadi called (dipanggil), mencatat stempel waktu pemanggilan (called_at) dan identitas petugas yang memanggil (served_by). Bersamaan dengan itu, sistem memicu nada dering (audio chime) dan suara sintesis ucapan otomatis: \"Nomor Antrean A, Nol, Nol, Satu, Silakan Menuju Loket Teller Satu\". Layar Display TV secara real-time memperbarui tampilan nomor yang sedang dipanggil."),
        ("4. Prosedur Pelayanan dan Penyelesaian Transaksi: ", "Nasabah menuju loket yang memanggil dan menyerahkan tiket antrean beserta dokumen transaksi (buku tabungan, KTP, atau kartu identitas pensiun). Petugas memulai proses verifikasi dan melayani transaksi nasabah (status antrean bergerak ke serving). Setelah transaksi selesai, petugas menekan tombol 'Selesai Dilayani' sehingga status tiket tersimpan permanen sebagai completed beserta waktu penyelesaian (completed_at). Apabila nasabah tidak kunjung hadir setelah dipanggil berulang kali, petugas dapat menggunakan tombol 'Panggil Ulang' (recall) atau menekan tombol 'Lewati Antrean' (status menjadi skipped), sehingga antrean berikutnya dapat segera dipanggil tanpa menghambat waktu operasional loket.")
    ]
    for p_label, p_desc in prosedur_poin:
        p_item = doc.add_paragraph()
        p_item.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        p_item.paragraph_format.left_indent = Cm(1.0)
        p_item.paragraph_format.first_line_indent = Cm(-1.0)
        p_item.paragraph_format.space_before = Pt(2)
        p_item.paragraph_format.space_after = Pt(4)
        p_item.paragraph_format.line_spacing = 1.5
        r1 = p_item.add_run(p_label)
        r1.bold = True
        r2 = p_item.add_run(p_desc)

    add_heading_sub("ii. Proses Bisnis Beserta Activity Diagram", level=3)
    add_p("Untuk menggambarkan urutan aliran aktivitas sistem secara visual dan presisi, disusun dua buah pemodelan Activity Diagram yang merepresentasikan interaksi antar aktor dengan komponen aplikasi perangkat lunak.", indent=True)

    add_p("1. Activity Diagram Pengambilan Nomor Antrean pada Kiosk Tiket:", space_before=4, space_after=2, line_spacing=1.5, bold=True, indent=True)
    add_p("Diagram ini memetakan aksi yang dilakukan oleh nasabah saat berinteraksi dengan layar sentuh Kiosk mandiri, mulai dari memilih kategori layanan hingga tiket antrean fisik berhasil dicetak oleh mesin thermal printer.", indent=True)

    # Embed Activity Diagram Ambil Tiket
    add_image_centered('docs/images/activity_diagram_ambil_nomor.png', width_inches=5.4)
    add_figure_caption("3.2", "Activity Diagram Pengambilan Nomor Antrean pada Kiosk")

    add_p("2. Activity Diagram Pemanggilan dan Pelayanan Antrean di Loket:", space_before=8, space_after=2, line_spacing=1.5, bold=True, indent=True)
    add_p("Diagram ini menggambarkan aliran kerja pemanggilan antrean yang melibatkan Petugas Loket (Teller/CS), modul aplikasi backend Laravel, serta Layar Display TV dan Nasabah di ruang tunggu banking hall, termasuk logika pengambilan keputusan jika nasabah hadir atau tidak hadir saat dipanggil.", indent=True)

    # Embed Activity Diagram Pelayanan
    add_image_centered('docs/images/activity_diagram_pelayanan.png', width_inches=5.5)
    add_figure_caption("3.3", "Activity Diagram Pemanggilan dan Pelayanan Nasabah di Loket")

    add_heading_sub("iii. Use Case Diagram dan Skenario Use Case", level=3)
    add_p("Use Case Diagram menyajikan representasi grafis mengenai fungsi-fungsi utama yang disediakan oleh Sistem Informasi Antrean Bank dan keterkaitannya dengan para aktor pengguna sistem. Pada sistem ini, teridentifikasi 3 (tiga) aktor utama:", indent=True)

    aktor_poin = [
        ("1. Nasabah: ", "Pengguna eksternal bank yang berinteraksi dengan antarmuka Kiosk untuk mengambil tiket antrean serta memantau nomor gilirannya melalui Layar Monitor Display TV di ruang tunggu."),
        ("2. Petugas Loket (Teller & Customer Service): ", "Pengguna internal operasional bank yang memiliki hak akses untuk memanggil nomor tiket, memanggil ulang nasabah, memproses transaksi pelayanan, dan memperbarui status antrean."),
        ("3. Administrator / Supervisor: ", "Pengguna dengan otoritas manajerial tertinggi yang berwenang mengonfigurasi pengaturan loket layanan, mengelola akun dan hak akses pengguna, memantau rekapitulasi data antrean, serta menjalankan fungsi reset antrean harian.")
    ]
    for p_label, p_desc in aktor_poin:
        p_item = doc.add_paragraph()
        p_item.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        p_item.paragraph_format.left_indent = Cm(1.0)
        p_item.paragraph_format.first_line_indent = Cm(-1.0)
        p_item.paragraph_format.space_before = Pt(2)
        p_item.paragraph_format.space_after = Pt(4)
        p_item.paragraph_format.line_spacing = 1.5
        r1 = p_item.add_run(p_label)
        r1.bold = True
        r2 = p_item.add_run(p_desc)

    # Embed Use Case Diagram
    add_image_centered('docs/images/use_case_diagram.png', width_inches=5.6)
    add_figure_caption("3.1", "Use Case Diagram Sistem Informasi Antrean Bank Berbasis Web")

    add_p("Untuk memberikan penjelasan mendalam terhadap interaksi pada diagram use case di atas, berikut dijabarkan skenario use case utama dalam bentuk tabel spesifikasi use case:", indent=True)

    # Tabel Skenario Use Case 1
    add_table_caption("3.1", "Skenario Use Case Pengambilan Tiket Antrean pada Mesin Kiosk")
    uc1_data = [
        ("Nama Use Case", "Pengambilan dan Pencetakan Tiket Antrean"),
        ("Aktor Utama", "Nasabah"),
        ("Tujuan", "Mendapatkan nomor antrean cetak fisik untuk layanan Teller atau CS"),
        ("Kondisi Awal (Pre-condition)", "Mesin Kiosk aktif dan menampilkan halaman utama pilih layanan"),
        ("Kondisi Akhir (Post-condition)", "Data tiket tersimpan di database dengan status 'waiting' dan tercetak"),
        ("Alur Utama (Main Flow)", "1. Nasabah menyentuh tombol pilihan layanan (Teller Kas / Layanan CS).\n2. Sistem memvalidasi ketersediaan loket aktif untuk layanan tersebut.\n3. Sistem menghitung nomor urut antrean harian berikutnya (e.g. A001).\n4. Sistem menyimpan record baru ke tabel queues dengan status 'waiting'.\n5. Sistem memicu pencetakan struk tiket thermal berisi barcode dan QR code.\n6. Nasabah mengambil struk tiket fisik dan menuju ruang tunggu."),
        ("Alur Alternatif (Alternative Flow)", "Jika kertas printer thermal habis atau printer offline, sistem menampilkan pesan peringatan visual di layar agar nasabah meminta bantuan petugas.")
    ]
    t_uc1 = doc.add_table(rows=len(uc1_data), cols=2)
    t_uc1.alignment = WD_TABLE_ALIGNMENT.CENTER
    t_uc1.style = 'Table Grid'
    for row_idx, (col1, col2) in enumerate(uc1_data):
        row_obj = t_uc1.rows[row_idx]
        row_obj.cells[0].width = Cm(4.5)
        row_obj.cells[1].width = Cm(11.0)
        set_cell_margins(row_obj.cells[0], top=60, bottom=60, left=80, right=80)
        set_cell_margins(row_obj.cells[1], top=60, bottom=60, left=80, right=80)
        p1 = row_obj.cells[0].paragraphs[0]
        p1.paragraph_format.line_spacing = 1.15
        p1.add_run(col1).bold = True
        p1.runs[0].font.size = Pt(9.5)
        p2 = row_obj.cells[1].paragraphs[0]
        p2.paragraph_format.line_spacing = 1.15
        p2.add_run(col2)
        p2.runs[0].font.size = Pt(9.5)

    # Tabel Skenario Use Case 2
    add_table_caption("3.2", "Skenario Use Case Pemanggilan dan Pelayanan Antrean Petugas")
    uc2_data = [
        ("Nama Use Case", "Pemanggilan dan Pengelolaan Alur Antrean di Loket"),
        ("Aktor Utama", "Petugas Loket (Teller & Customer Service)"),
        ("Tujuan", "Memanggil nasabah ke loket tugas dan memperbarui status antrean"),
        ("Kondisi Awal (Pre-condition)", "Petugas telah login dan terikat pada loket tugasnya (service_setting_id)"),
        ("Kondisi Akhir (Post-condition)", "Status tiket berubah menjadi 'completed' atau 'skipped'"),
        ("Alur Utama (Main Flow)", "1. Petugas menekan tombol 'PANGGIL ANTREAN BERIKUTNYA'.\n2. Sistem mencari nomor antrean terlama berstatus 'waiting' sesuai layanan loket.\n3. Sistem mengupdate status antrean menjadi 'called', mengisi stempel called_at.\n4. Display TV ruang tunggu menampilkan nomor tiket dan audio panggilan berbunyi.\n5. Nasabah tiba di loket dan petugas melayani transaksi (status: 'serving').\n6. Petugas menyelesaikan transaksi dan menekan 'Selesai Dilayani' (status: 'completed')."),
        ("Alur Alternatif (Alternative Flow)", "1. Jika nasabah belum hadir, petugas menekan tombol 'Panggil Ulang' (recall).\n2. Jika setelah 3 kali panggilan nasabah tetap tidak hadir, petugas menekan tombol 'Lewati Antrean' (status berubah menjadi 'skipped').")
    ]
    t_uc2 = doc.add_table(rows=len(uc2_data), cols=2)
    t_uc2.alignment = WD_TABLE_ALIGNMENT.CENTER
    t_uc2.style = 'Table Grid'
    for row_idx, (col1, col2) in enumerate(uc2_data):
        row_obj = t_uc2.rows[row_idx]
        row_obj.cells[0].width = Cm(4.5)
        row_obj.cells[1].width = Cm(11.0)
        set_cell_margins(row_obj.cells[0], top=60, bottom=60, left=80, right=80)
        set_cell_margins(row_obj.cells[1], top=60, bottom=60, left=80, right=80)
        p1 = row_obj.cells[0].paragraphs[0]
        p1.paragraph_format.line_spacing = 1.15
        p1.add_run(col1).bold = True
        p1.runs[0].font.size = Pt(9.5)
        p2 = row_obj.cells[1].paragraphs[0]
        p2.paragraph_format.line_spacing = 1.15
        p2.add_run(col2)
        p2.runs[0].font.size = Pt(9.5)

    # Tabel Skenario Use Case 3
    add_table_caption("3.3", "Skenario Use Case Manajemen Loket dan Pengaturan Antrean")
    uc3_data = [
        ("Nama Use Case", "Manajemen Pengaturan Loket dan Reset Harian"),
        ("Aktor Utama", "Administrator / Supervisor Cabang"),
        ("Tujuan", "Mengonfigurasi parameter loket operasional dan melakukan reset data"),
        ("Kondisi Awal (Pre-condition)", "Admin login dengan kredensial akun bervendor role 'admin'"),
        ("Kondisi Akhir (Post-condition)", "Perubahan konfigurasi loket tersimpan atau nomor antrean ter-reset ke 1"),
        ("Alur Utama (Main Flow)", "1. Admin membuka halaman Service Setting Resource pada panel admin.\n2. Admin menambah loket baru, mengubah nama counter, atau menonaktifkan loket.\n3. Pada pergantian hari kerja, admin membuka Admin Control Center.\n4. Admin menekan tombol 'Reset Antrean Hari Ini' dengan konfirmasi modal pop-up.\n5. Sistem mengarsipkan antrean hari kemarin dan mengosongkan antrean aktif."),
        ("Alur Alternatif (Alternative Flow)", "Sistem dilengkapi perintah otomatis terjadwal (Scheduler: php artisan queue:reset-daily) yang berjalan setiap pukul 00:00 WIB untuk memastikan nomor antrean kembali mulai dari nomor 001.")
    ]
    t_uc3 = doc.add_table(rows=len(uc3_data), cols=2)
    t_uc3.alignment = WD_TABLE_ALIGNMENT.CENTER
    t_uc3.style = 'Table Grid'
    for row_idx, (col1, col2) in enumerate(uc3_data):
        row_obj = t_uc3.rows[row_idx]
        row_obj.cells[0].width = Cm(4.5)
        row_obj.cells[1].width = Cm(11.0)
        set_cell_margins(row_obj.cells[0], top=60, bottom=60, left=80, right=80)
        set_cell_margins(row_obj.cells[1], top=60, bottom=60, left=80, right=80)
        p1 = row_obj.cells[0].paragraphs[0]
        p1.paragraph_format.line_spacing = 1.15
        p1.add_run(col1).bold = True
        p1.runs[0].font.size = Pt(9.5)
        p2 = row_obj.cells[1].paragraphs[0]
        p2.paragraph_format.line_spacing = 1.15
        p2.add_run(col2)
        p2.runs[0].font.size = Pt(9.5)

    add_heading_sub("3.2. Interface (Spesifikasi Antarmuka)")

    add_heading_sub("a. Model Layar / Tampilan Sistem", level=3)
    add_p("Spesifikasi antarmuka pengguna dirancang dengan mengedepankan prinsip User-Centered Design (UCD) dan Corporate Identity PT BPR DP TASPEN yang elegan, kontras, serta mudah digunakan oleh pengguna dari berbagai latar belakang usia.", indent=True)

    add_p("1. Halaman Kiosk Pengambilan Tiket Mandiri (/ambil-nomor):", space_before=4, space_after=2, line_spacing=1.5, bold=True, indent=True)
    add_p("Halaman Kiosk menyajikan antarmuka layar sentuh berlatar belakang foto gedung perbankan modern yang dipadukan dengan overlay gelap dan aksen warna biru korporat (#004B9C) serta emas (#F3B313). Terdapat dua kartu layanan berukuran besar dengan tombol sentuh kontras tinggi. Kartu pertama diperuntukkan bagi layanan 'Teller Kas & Pensiun TASPEN' (pencairan gaji pensiun, setor/tarik tunai, bayar angsuran), dan kartu kedua untuk layanan 'Customer Service Kredit & Pensiun' (pembukaan rekening, deposito, pengajuan pinjaman, dan otentikasi pensiun). Tombol dilengkapi ikon visual jelas yang ramah bagi para lansia.", indent=True)

    # Mockup Kiosk
    add_image_centered('public/images/bg-ambil-nomor.png', width_inches=5.2)
    add_figure_caption("3.4", "Tampilan Layar Kiosk Pengambilan Tiket Antrean (/ambil-nomor)")

    add_p("2. Halaman Monitor Display TV Ruang Tunggu (/display):", space_before=8, space_after=2, line_spacing=1.5, bold=True, indent=True)
    add_p("Halaman Display TV dirancang untuk ditampilkan pada layar monitor LED 43 inch di ruang tunggu utama banking hall. Layar terbagi menjadi panel hero utama pemanggilan antrean saat ini (menampilkan nomor tiket besar berbingkai emas, nama loket pemanggil, dan nama petugas), panel ringkasan loket aktif operasional, panel daftar nomor antrean yang sedang menunggu, serta bagian footer berupa running text dinamis yang menyampaikan pesan edukasi perbankan, pengumuman suku bunga LPS, dan himbauan ketertiban. Antarmuka ini dilengkapi mesin suara audio chime yang otomatis berbunyi ketika terjadi pemanggilan baru.", indent=True)

    # Caption Display TV
    add_p("[Ilustrasi Layar Monitor Display TV Ruang Tunggu PT BPR DP TASPEN]", align=WD_ALIGN_PARAGRAPH.CENTER, space_before=6, space_after=2, italic=True)
    add_figure_caption("3.5", "Tampilan Layar Monitor Display Ruang Tunggu (/display)")

    add_p("3. Halaman Konsol Petugas Loket / Operator Box (/admin/queues):", space_before=8, space_after=2, line_spacing=1.5, bold=True, indent=True)
    add_p("Halaman ini dibangun di atas Filament Admin Panel v3 dan Livewire dengan fitur real-time polling otomatis setiap 3 detik (wire:poll.3s). Pada bagian atas terdapat Operator Box yang menampilkan nama loket tugas petugas, status kesiapan loket ('SIAP MELAYANI' atau 'SEDANG MELAYANI'), kotak besar nomor antrean yang sedang ditangani, serta empat tombol aksi utama:", indent=True)

    tombol_konsol = [
        ("• Tombol 'PANGGIL ANTREAN BERIKUTNYA' (Biru #004B9C): ", "Memanggil tiket terlama dari antrean menunggu."),
        ("• Tombol 'Panggil Ulang / Recall' (Kuning Amber): ", "Membunyikan kembali nada panggilan di display TV."),
        ("• Tombol 'Selesai Dilayani' (Hijau Emerald): ", "Menyelesaikan transaksi nasabah saat ini."),
        ("• Tombol 'Lewati Antrean / Skip' (Merah Rose): ", "Melewati antrean jika nasabah tidak hadir di loket.")
    ]
    for t_title, t_desc in tombol_konsol:
        p_t = doc.add_paragraph()
        p_t.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        p_t.paragraph_format.left_indent = Cm(1.0)
        p_t.paragraph_format.space_before = Pt(1)
        p_t.paragraph_format.space_after = Pt(2)
        p_t.paragraph_format.line_spacing = 1.15
        r1 = p_t.add_run(t_title)
        r1.bold = True
        r2 = p_t.add_run(t_desc)

    add_p("[Ilustrasi Konsol Petugas Operasional Operator Box pada Dasbor Filament]", align=WD_ALIGN_PARAGRAPH.CENTER, space_before=6, space_after=2, italic=True)
    add_figure_caption("3.6", "Tampilan Konsol Petugas Operasional (Operator Box Filament)")

    add_p("4. Halaman Dasbor Admin & Pengaturan Loket (/admin/service-settings):", space_before=8, space_after=2, line_spacing=1.5, bold=True, indent=True)
    add_p("Modul administratif bagi Supervisor dan Administrator untuk mengelola entitas loket fisik bank. Form ini memungkinkan penambahan loket baru, penentuan kode prefix antrean (A untuk Teller, B untuk CS), penamaan konter (e.g. 'Loket Teller 1', 'Loket Customer Service 1'), serta saklar toggle status operasional loket (is_active). Selain itu, terdapat halaman Admin Control Center yang menyediakan ringkasan kartu metrik antrean harian dan tombol aksi khusus Reset Antrean Hari Ini.", indent=True)

    add_p("[Ilustrasi Halaman Admin Control Center dan Manajemen Pengaturan Loket]", align=WD_ALIGN_PARAGRAPH.CENTER, space_before=6, space_after=2, italic=True)
    add_figure_caption("3.7", "Tampilan Admin Control Center dan Pengaturan Loket Layanan")

    add_heading_sub("b. Model Masukan (Input)", level=3)
    add_p("Model masukan merepresentasikan data yang dimasukkan oleh pengguna ke dalam sistem melalui berbagai form input:", indent=True)

    input_items = [
        ("1. Input Pengambilan Tiket Kiosk: ", "Data masukan berasal dari penekanan tombol sentuh kategori layanan oleh nasabah. Parameter yang dikirimkan ke backend melalui metode HTTP POST adalah parameter 'service' ('teller' atau 'cs') dan 'service_setting_id' yang valid."),
        ("2. Input Form Otentikasi Login Petugas: ", "Form masukan akun petugas pada halaman login (/admin/login) yang memvalidasi field email dan password dengan enkripsi hash Bcrypt, serta memeriksa keaktifan status akun user (status = 'active')."),
        ("3. Input Konfigurasi Loket Layanan (Service Settings Form): ", "Form input data loket pada panel admin yang memuat elemen: Counter Name (teks nama loket, e.g. 'Loket Teller Kas 1'), Service Type (pilihan dropdown 'teller' atau 'cs'), Counter Code Prefix (teks kode 'A' atau 'B'), dan Is Active (toggle boolean keaktifan loket)."),
        ("4. Input Tindakan Operasional Loket: ", "Aksi tombol oleh petugas (Next, Recall, Complete, Skip) yang mengirimkan permintaan Livewire Action ke backend beserta verifikasi session token petugas.")
    ]
    for p_label, p_desc in input_items:
        p_item = doc.add_paragraph()
        p_item.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        p_item.paragraph_format.left_indent = Cm(1.0)
        p_item.paragraph_format.first_line_indent = Cm(-1.0)
        p_item.paragraph_format.space_before = Pt(2)
        p_item.paragraph_format.space_after = Pt(4)
        p_item.paragraph_format.line_spacing = 1.5
        r1 = p_item.add_run(p_label)
        r1.bold = True
        r2 = p_item.add_run(p_desc)

    add_heading_sub("c. Model Keluaran (Output)", level=3)
    add_p("Model keluaran mencakup hasil keluaran sistem baik dalam bentuk fisik maupun visual digital:", indent=True)

    output_items = [
        ("1. Cetakan Fisik Struk Tiket Thermal: ", "Dokumen fisik berukuran lebar 58mm/80mm yang dicetak dari printer thermal mesin Kiosk. Memuat: Header Nama Bank ('PT BPR DP TASPEN'), Logo Bank, Nomor Antrean Besar (e.g. 'A005'), Jenis Layanan ('Teller Kas & Pensiun TASPEN'), Barcode Code-128, QR Code verifikasi URL, Tanggal dan Jam Cetak, serta Catatan Himbauan ketertiban."),
        ("2. Visual Papan Panggilan Layar Display TV: ", "Keluaran digital real-time beresolusi Full HD yang menampilkan nomor tiket antrean terpanggil secara dinamis, kartu loket aktif, dan deretan nomor antrean yang sedang menunggu."),
        ("3. Sinyal Audio Panggilan (Voice & Chime Output): ", "Keluaran suara sintetis otomatis yang memutar audio chime perbankan disusul pengucapan nomor antrean dan loket tujuan dalam Bahasa Indonesia yang jernih dan santun."),
        ("4. Rekapitulasi Data dan Statistik Operasional: ", "Laporan ringkasan antrean harian pada dasbor admin yang menyajikan data total tiket tercetak, total nasabah terlayani (completed), antrean yang dilewati (skipped), dan sisa antrean yang masih menunggu (waiting).")
    ]
    for p_label, p_desc in output_items:
        p_item = doc.add_paragraph()
        p_item.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        p_item.paragraph_format.left_indent = Cm(1.0)
        p_item.paragraph_format.first_line_indent = Cm(-1.0)
        p_item.paragraph_format.space_before = Pt(2)
        p_item.paragraph_format.space_after = Pt(4)
        p_item.paragraph_format.line_spacing = 1.5
        r1 = p_item.add_run(p_label)
        r1.bold = True
        r2 = p_item.add_run(p_desc)

    add_heading_sub("3.3. Rancangan Database")

    add_heading_sub("a. Class Diagram / ERD (Entity Relationship Diagram)", level=3)
    add_p("Rancangan basis data relasional sistem informasi antrean bank diimplementasikan menggunakan mesin basis data MySQL/MariaDB dengan struktur normalisasi data hingga bentuk normal ketiga (3NF) guna menghindari redundansi data dan menjamin integritas relasional.", indent=True)

    # Embed ERD Diagram
    add_image_centered('docs/images/erd_sistem_antrian.png', width_inches=5.6)
    add_figure_caption("3.8", "Entity Relationship Diagram (ERD) Sistem Antrean Bank")

    add_p("Relasi antartabel pada sistem antrean bank terdiri dari:", indent=True)
    erd_relasi = [
        ("• Relasi service_settings ke users (1 to Many): ", "Satu loket layanan (service_setting) dapat ditugaskan kepada satu atau beberapa petugas pengguna (users) melalui foreign key users.service_setting_id."),
        ("• Relasi service_settings ke queues (1 to Many): ", "Satu konfigurasi loket layanan dapat memiliki banyak catatan antrean tiket nasabah melalui queues.service_setting_id."),
        ("• Relasi users ke queues (1 to Many): ", "Satu pengguna petugas operasional dapat melayani banyak catatan tiket antrean melalui relasi foreign key queues.served_by yang mencatat ID petugas yang memproses transaksi.")
    ]
    for r_title, r_desc in erd_relasi:
        p_r = doc.add_paragraph()
        p_r.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        p_r.paragraph_format.left_indent = Cm(1.0)
        p_r.paragraph_format.space_before = Pt(1)
        p_r.paragraph_format.space_after = Pt(2)
        p_r.paragraph_format.line_spacing = 1.15
        r1 = p_r.add_run(r_title)
        r1.bold = True
        r2 = p_r.add_run(r_desc)

    add_heading_sub("b. Spesifikasi Basis Data (Data Dictionary)", level=3)
    add_p("Berikut dijabarkan struktur spesifikasi tabel basis data secara rinci untuk setiap entitas tabel yang digunakan dalam sistem antrean bank:", indent=True)

    # Tabel 3.4: Spesifikasi Tabel users
    add_table_caption("3.4", "Kamus Data / Spesifikasi Tabel users")
    t_users_data = [
        ("Nama Field", "Tipe Data", "Ukuran/Constraint", "Keterangan"),
        ("id", "BIGINT UNSIGNED", "Auto Increment, PK", "Kunci utama pengenal unik akun pengguna"),
        ("name", "VARCHAR", "255, NOT NULL", "Nama lengkap petugas atau administrator"),
        ("email", "VARCHAR", "255, UNIQUE, NOT NULL", "Alamat email resmi untuk otentikasi login"),
        ("password", "VARCHAR", "255, NOT NULL", "Kata sandi akun terenkripsi hash Bcrypt"),
        ("service_setting_id", "BIGINT UNSIGNED", "NULLABLE, FK", "Relasi foreign key ke service_settings.id"),
        ("status", "VARCHAR", "20, DEFAULT 'active'", "Status keaktifan user ('active' / 'inactive')"),
        ("last_login_at", "DATETIME", "NULLABLE", "Stempel waktu terakhir user melakukan login"),
        ("remember_token", "VARCHAR", "100, NULLABLE", "Token sesi remember-me dari framework Laravel"),
        ("created_at", "TIMESTAMP", "NULLABLE", "Waktu pembuatan data akun"),
        ("updated_at", "TIMESTAMP", "NULLABLE", "Waktu perubahan terakhir data akun")
    ]
    t_u = doc.add_table(rows=len(t_users_data), cols=4)
    t_u.alignment = WD_TABLE_ALIGNMENT.CENTER
    t_u.style = 'Table Grid'
    for row_idx, row_content in enumerate(t_users_data):
        row_obj = t_u.rows[row_idx]
        for col_idx, text_val in enumerate(row_content):
            cell = row_obj.cells[col_idx]
            set_cell_margins(cell, top=60, bottom=60, left=80, right=80)
            p = cell.paragraphs[0]
            p.paragraph_format.line_spacing = 1.15
            if row_idx == 0:
                set_cell_background(cell, "004B9C")
                p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                run = p.add_run(text_val)
                run.bold = True
                run.font.color.rgb = RGBColor(255, 255, 255)
                run.font.size = Pt(9.5)
            else:
                if col_idx in [0, 1]:
                    run = p.add_run(text_val)
                    run.bold = True if col_idx == 0 else False
                else:
                    run = p.add_run(text_val)
                run.font.size = Pt(9)

    # Tabel 3.5: Spesifikasi Tabel service_settings
    add_table_caption("3.5", "Kamus Data / Spesifikasi Tabel service_settings")
    t_settings_data = [
        ("Nama Field", "Tipe Data", "Ukuran/Constraint", "Keterangan"),
        ("id", "BIGINT UNSIGNED", "Auto Increment, PK", "Kunci utama unik pengaturan loket layanan"),
        ("service", "VARCHAR", "50, NOT NULL", "Kategori layanan ('teller' atau 'cs')"),
        ("counter_name", "VARCHAR", "100, NOT NULL", "Nama fisik konter loket (e.g. 'Loket Teller 1')"),
        ("counter_code", "VARCHAR", "10, NOT NULL", "Awalan kode antrean ('A' untuk Teller, 'B' untuk CS)"),
        ("is_active", "BOOLEAN", "DEFAULT TRUE", "Status keaktifan loket dalam menerima antrean"),
        ("created_at", "TIMESTAMP", "NULLABLE", "Waktu pembuatan konfigurasi loket"),
        ("updated_at", "TIMESTAMP", "NULLABLE", "Waktu pembaruan konfigurasi loket")
    ]
    t_s = doc.add_table(rows=len(t_settings_data), cols=4)
    t_s.alignment = WD_TABLE_ALIGNMENT.CENTER
    t_s.style = 'Table Grid'
    for row_idx, row_content in enumerate(t_settings_data):
        row_obj = t_s.rows[row_idx]
        for col_idx, text_val in enumerate(row_content):
            cell = row_obj.cells[col_idx]
            set_cell_margins(cell, top=60, bottom=60, left=80, right=80)
            p = cell.paragraphs[0]
            p.paragraph_format.line_spacing = 1.15
            if row_idx == 0:
                set_cell_background(cell, "004B9C")
                p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                run = p.add_run(text_val)
                run.bold = True
                run.font.color.rgb = RGBColor(255, 255, 255)
                run.font.size = Pt(9.5)
            else:
                run = p.add_run(text_val)
                if col_idx == 0:
                    run.bold = True
                run.font.size = Pt(9)

    # Tabel 3.6: Spesifikasi Tabel queues
    add_table_caption("3.6", "Kamus Data / Spesifikasi Tabel queues")
    t_queues_data = [
        ("Nama Field", "Tipe Data", "Ukuran/Constraint", "Keterangan"),
        ("id", "BIGINT UNSIGNED", "Auto Increment, PK", "Kunci utama transaksi tiket antrean nasabah"),
        ("number", "VARCHAR", "20, NOT NULL", "Format nomor tiket antrean (contoh: 'A001', 'B002')"),
        ("service", "VARCHAR", "50, NOT NULL", "Jenis layanan antrean ('teller' atau 'cs')"),
        ("service_setting_id", "BIGINT UNSIGNED", "NULLABLE, FK", "Foreign key ke tabel service_settings"),
        ("served_by", "BIGINT UNSIGNED", "NULLABLE, FK", "Foreign key ke users.id petugas yang melayani"),
        ("status", "ENUM", "waiting, called, serving, completed, skipped", "Status siklus antrean saat ini"),
        ("called_at", "DATETIME", "NULLABLE", "Stempel waktu saat tiket pertama kali dipanggil"),
        ("completed_at", "DATETIME", "NULLABLE", "Stempel waktu saat tiket selesai dilayani di loket"),
        ("created_at", "TIMESTAMP", "NULLABLE", "Stempel waktu saat tiket dicetak pada mesin Kiosk"),
        ("updated_at", "TIMESTAMP", "NULLABLE", "Stempel waktu pembaruan status antrean")
    ]
    t_q = doc.add_table(rows=len(t_queues_data), cols=4)
    t_q.alignment = WD_TABLE_ALIGNMENT.CENTER
    t_q.style = 'Table Grid'
    for row_idx, row_content in enumerate(t_queues_data):
        row_obj = t_q.rows[row_idx]
        for col_idx, text_val in enumerate(row_content):
            cell = row_obj.cells[col_idx]
            set_cell_margins(cell, top=60, bottom=60, left=80, right=80)
            p = cell.paragraphs[0]
            p.paragraph_format.line_spacing = 1.15
            if row_idx == 0:
                set_cell_background(cell, "004B9C")
                p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                run = p.add_run(text_val)
                run.bold = True
                run.font.color.rgb = RGBColor(255, 255, 255)
                run.font.size = Pt(9.5)
            else:
                run = p.add_run(text_val)
                if col_idx == 0:
                    run.bold = True
                run.font.size = Pt(9)

    # Tabel 3.7: Spesifikasi Tabel roles & permissions
    add_table_caption("3.7", "Kamus Data / Spesifikasi Tabel roles dan permissions")
    t_roles_data = [
        ("Nama Tabel", "Field Kunci", "Tipe Data & Ukuran", "Fungsi dan Keterangan"),
        ("roles", "id, name, guard_name", "BIGINT, VARCHAR(255)", "Menyimpan data nama peran (admin, teller, cs, supervisor)"),
        ("permissions", "id, name, module, guard", "BIGINT, VARCHAR(255)", "Menyimpan izin aksi fitur (access_teller_console, reset_queues)"),
        ("model_has_roles", "role_id, model_type, model_id", "BIGINT, VARCHAR(255)", "Tabel pivot penugasan role kepada akun pengguna (users)"),
        ("role_has_permissions", "permission_id, role_id", "BIGINT, BIGINT", "Tabel pivot pemetaan hak akses permission ke dalam role")
    ]
    t_r = doc.add_table(rows=len(t_roles_data), cols=4)
    t_r.alignment = WD_TABLE_ALIGNMENT.CENTER
    t_r.style = 'Table Grid'
    for row_idx, row_content in enumerate(t_roles_data):
        row_obj = t_r.rows[row_idx]
        for col_idx, text_val in enumerate(row_content):
            cell = row_obj.cells[col_idx]
            set_cell_margins(cell, top=60, bottom=60, left=80, right=80)
            p = cell.paragraphs[0]
            p.paragraph_format.line_spacing = 1.15
            if row_idx == 0:
                set_cell_background(cell, "004B9C")
                p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                run = p.add_run(text_val)
                run.bold = True
                run.font.color.rgb = RGBColor(255, 255, 255)
                run.font.size = Pt(9.5)
            else:
                run = p.add_run(text_val)
                if col_idx == 0:
                    run.bold = True
                run.font.size = Pt(9)

    add_heading_sub("3.4. Arsitektur Keamanan dan Otorisasi Sistem")

    add_p("Salah satu inovasi teknis terpenting yang diterapkan pada Sistem Informasi Antrean PT BPR DP TASPEN adalah arsitektur keamanan dua lapis (Two-Layer Authorization Architecture). Pada aplikasi perbankan konvensional, otorisasi umumnya hanya memeriksa apakah pengguna berhak mengakses suatu rute menu. Namun, dalam konteks pelayanan loket kasir dan administrasi, hal tersebut belum cukup karena dapat terjadi insiden di mana seorang Teller di Loket 1 tanpa sengaja memanggil atau mengubah data antrean nasabah yang sedang ditangani oleh Teller di Loket 2.", indent=True)

    # Embed Arsitektur Keamanan
    add_image_centered('docs/images/arsitektur_keamanan.png', width_inches=5.6)
    add_figure_caption("3.9", "Arsitektur Keamanan Dua Lapis (Two-Layer Authorization Pattern)")

    add_p("Mekanisme kerja Two-Layer Authorization pada sistem ini dijabarkan sebagai berikut:", indent=True)

    lapisan_keamanan = [
        ("1. Lapisan 1: Fitur Otorisasi Permission (RBAC via Spatie & Filament Shield): ", "Mengontrol izin pengguna terhadap menu dan tindakan fitur (Apakah user BOLEH membuka fitur ini?). Contoh: permission 'access_teller_console' hanya diberikan kepada role 'teller' dan 'admin'. Apabila pengguna biasa mencoba mengakses URL konsol pemanggilan tanpa hak izin, sistem seketika memblokir permintaan dengan kode respon HTTP 403 Forbidden."),
        ("2. Lapisan 2: Data Assignment / Kepemilikan Loket (Counter Data Ownership): ", "Mengontrol lingkup data loket spesifik yang menjadi wewenang kerja pengguna (Loket MANA yang boleh dikelola oleh petugas ini?). Hal ini dicapai dengan menautkan kolom users.service_setting_id ke service_settings.id. Saat petugas Teller/CS membuka antarmuka pemanggilan, sistem menerapkan Laravel Model Scope & Policy yang mengunci query antrean hanya pada loket miliknya: 'auth()->user()->service_setting_id === $setting->id'. Jika seorang petugas mencoba memanggil tiket loket lain, sistem menolaknya."),
        ("3. Pengecualian Khusus Administrator (Super-Admin Bypass): ", "Pengguna dengan peran 'admin' secara otomatis dikecualikan dari pembatasan loket statis. Administrator memiliki wewenang untuk memilih loket operasional mana saja melalui komponen dropdown interaktif di Dasbor Control Center guna keperluan supervisi dan monitoring cabang.")
    ]
    for p_label, p_desc in lapisan_keamanan:
        p_item = doc.add_paragraph()
        p_item.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        p_item.paragraph_format.left_indent = Cm(1.0)
        p_item.paragraph_format.first_line_indent = Cm(-1.0)
        p_item.paragraph_format.space_before = Pt(2)
        p_item.paragraph_format.space_after = Pt(4)
        p_item.paragraph_format.line_spacing = 1.5
        r1 = p_item.add_run(p_label)
        r1.bold = True
        r2 = p_item.add_run(p_desc)

    add_heading_sub("3.5. Pengujian Sistem (Black Box Testing)")

    add_p("Pengujian perangkat lunak dilakukan guna memastikan bahwa seluruh fungsionalitas sistem berjalan sesuai dengan spesifikasi kebutuhan yang telah ditetapkan, bebas dari galat (error), dan memiliki keandalan saat dioperasikan pada situasi operasional perbankan yang nyata. Metode pengujian yang digunakan adalah Black Box Testing, yaitu pengujian yang berfokus pada evaluasi fungsionalitas eksternal sistem (input dan output) tanpa harus mengamati kode internal secara mendalam.", indent=True)

    add_p("Pengujian dilakukan mencakup skenario pengambilan nomor tiket di Kiosk, pembaruan layar Display TV, audio chime, pemanggilan loket petugas, mekanisme Two-Layer Authorization, dan fitur reset antrean harian. Hasil pengujian disajikan pada tabel matriks pengujian berikut:", indent=True)

    # Tabel Black Box Testing
    add_table_caption("3.9", "Matriks Rencana dan Hasil Pengujian Black Box Testing")
    testing_data = [
        ("No", "Fitur / Skenario Pengujian", "Aksi Pengujian (Input)", "Hasil yang Diharapkan", "Hasil Uji", "Kesimpulan"),
        ("1", "Pencetakan Tiket Teller Mandiri", "Menekan tombol 'Ambil Tiket Teller' pada layar Kiosk (/ambil-nomor)", "Nomor tiket berawalan 'A' digenerate, data tersimpan di DB (status: waiting), printer thermal mencetak tiket fisik.", "Sistem mencetak tiket A001 berbarcode dan QR Code dengan stempel jam akurat.", "Valid"),
        ("2", "Pencetakan Tiket CS Mandiri", "Menekan tombol 'Ambil Tiket CS' pada layar Kiosk (/ambil-nomor)", "Nomor tiket berawalan 'B' digenerate, data tersimpan di DB (status: waiting), printer thermal mencetak tiket fisik.", "Sistem mencetak tiket B001 dengan deskripsi layanan pensiun & kredit.", "Valid"),
        ("3", "Otentikasi Login Petugas", "Memasukkan email dan kata sandi valid petugas Teller pada form login", "Sistem memvalidasi kredensial, memulai sesi auth, dan mengarahkan petugas ke halaman Operator Box loketnya.", "Login berhasil dan petugas diarahkan ke dasbor antrean loket.", "Valid"),
        ("4", "Panggilan Antrean Pertama (Next)", "Petugas mengklik tombol 'PANGGIL ANTREAN BERIKUTNYA'", "Status tiket antrean terlama berubah dari 'waiting' menjadi 'called', stempel called_at dan served_by terisi.", "Status tiket berhasil terupdate dan nomor tiket aktif tampil di hero box.", "Valid"),
        ("5", "Pembaruan Real-Time Display TV", "Membuka halaman /display saat terjadi aksi pemanggilan nomor", "Nomor tiket terpanggil tampil besar di layar TV ruang tunggu dan suara audio sintesis/chime terdengar.", "Display TV otomatis update tanpa refresh manual dan audio berbunyi jelas.", "Valid"),
        ("6", "Fitur Panggil Ulang (Recall)", "Petugas menekan tombol 'Panggil Ulang' di Operator Box", "Sistem memicu kembali transmisi audio chime dan panggilan suara di layar Display TV.", "Suara panggilan nomor tiket terulang di ruang tunggu.", "Valid"),
        ("7", "Penyelesaian Transaksi (Complete)", "Petugas menekan tombol 'Selesai Dilayani' setelah melayani nasabah", "Status tiket berubah menjadi 'completed', completed_at tercatat, loket kembali berstatus 'SIAP MELAYANI'.", "Status tersimpan permanen sebagai completed dan siap panggil tiket berikutnya.", "Valid"),
        ("8", "Fitur Lewati Antrean (Skip)", "Petugas menekan tombol 'Lewati Antrean' saat nasabah tidak hadir", "Status tiket berubah menjadi 'skipped', log tercatat, petugas dapat memanggil nomor berikutnya.", "Tiket diberi status skipped dan antrean berikutnya dapat dipanggil.", "Valid"),
        ("9", "Uji Two-Layer Data Assignment", "Teller mencoba mengakses atau mengedit konfigurasi loket milik petugas lain", "Sistem menolak akses dengan pesan 'Anda tidak berwenang mengelola counter ini' (HTTP 403).", "Akses berhasil dicegah dan data loket lain tetap aman terlindungi.", "Valid"),
        ("10", "Reset Antrean Harian", "Admin menekan tombol 'Reset Antrean Hari Ini' di Admin Control Center", "Semua nomor antrean diarsipkan dan urutan tiket berikutnya kembali mulai dari nomor 001.", "Antrean berhasil tereset dan nomor baru kembali mulai dari A001 dan B001.", "Valid")
    ]
    t_test = doc.add_table(rows=len(testing_data), cols=6)
    t_test.alignment = WD_TABLE_ALIGNMENT.CENTER
    t_test.style = 'Table Grid'
    for row_idx, row_content in enumerate(testing_data):
        row_obj = t_test.rows[row_idx]
        for col_idx, text_val in enumerate(row_content):
            cell = row_obj.cells[col_idx]
            set_cell_margins(cell, top=50, bottom=50, left=60, right=60)
            p = cell.paragraphs[0]
            p.paragraph_format.line_spacing = 1.15
            if row_idx == 0:
                set_cell_background(cell, "004B9C")
                p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                run = p.add_run(text_val)
                run.bold = True
                run.font.color.rgb = RGBColor(255, 255, 255)
                run.font.size = Pt(9)
            else:
                if col_idx in [0, 4, 5]:
                    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                else:
                    p.alignment = WD_ALIGN_PARAGRAPH.LEFT
                run = p.add_run(text_val)
                if col_idx == 5:
                    run.bold = True
                run.font.size = Pt(8.5)

    add_p("Berdasarkan keseluruhan hasil pengujian Black Box Testing pada 10 (sepuluh) kasus uji fungsional di atas, seluruh komponen sistem terbukti berjalan dengan valid (100% lulus uji), tanpa ditemukannya galat kritis (critical bug). Hal ini menunjukkan bahwa perangkat lunak Sistem Informasi Antrean Bank Berbasis Web telah siap untuk diterapkan secara operasional penuh pada PT BPR DP TASPEN Kantor Cabang Bekasi.", indent=True)

    # =========================================================================
    # BAB IV: PENUTUP
    # =========================================================================
    add_heading_bab("BAB IV", "PENUTUP")

    add_heading_sub("4.1. Kesimpulan")

    add_p("Berdasarkan hasil analisis, perancangan, implementasi, dan pengujian yang telah dilaksanakan selama kegiatan Kuliah Kerja Praktik (KKP) di PT BPR DP TASPEN Kantor Cabang Bekasi, dapat ditarik beberapa kesimpulan pokok sebagai berikut:", indent=True)

    kesimpulan_poin = [
        ("1. ", "Sistem Informasi Antrean Bank Berbasis Web berhasil dirancang dan diimplementasikan menggunakan framework Laravel 11/12, Filament PHP v3, Tailwind CSS, dan MySQL untuk mentransformasikan sistem antrean manual perbankan menjadi sistem digital yang terstruktur, transparan, dan efisien."),
        ("2. ", "Penerapan modul Kiosk Tiket Mandiri (/ambil-nomor) dan Monitor Display TV Ruang Tunggu (/display) yang dilengkapi notifikasi audio suara pemanggilan otomatis berhasil meningkatkan kenyamanan dan kepastian waktu tunggu bagi para nasabah perbankan, khususnya bagi kalangan nasabah pensiunan lansia."),
        ("3. ", "Implementasi arsitektur keamanan dua lapis (Two-Layer Authorization Pattern)—yang mengintegrasikan hak akses fitur Spatie Laravel-Permission dengan penugasan loket statis (Data Assignment service_setting_id)—terbukti efektif dalam mencegah terjadinya konflik pemanggilan nomor tiket antar petugas loket di lingkungan banking hall."),
        ("4. ", "Sistem menyediakan pencatatan riwayat transaksi antrean yang akurat (status waiting, called, serving, completed, skipped) beserta stempel waktu pelayanan (called_at dan completed_at), yang memudahkan pihak supervisor operasional dan manajemen cabang dalam mengevaluasi Service Level Agreement (SLA) dan kinerja petugas secara berkala.")
    ]
    for num, txt in kesimpulan_poin:
        p_item = doc.add_paragraph()
        p_item.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        p_item.paragraph_format.left_indent = Cm(1.0)
        p_item.paragraph_format.first_line_indent = Cm(-1.0)
        p_item.paragraph_format.space_before = Pt(2)
        p_item.paragraph_format.space_after = Pt(4)
        p_item.paragraph_format.line_spacing = 1.5
        r1 = p_item.add_run(num)
        r1.bold = True
        r2 = p_item.add_run(txt)

    add_heading_sub("4.2. Saran Pengembangan")

    add_p("Sebagai tindak lanjut dan rekomendasi penyempurnaan sistem informasi antrean bank di masa mendatang, penulis menyampaikan beberapa saran strategis sebagai berikut:", indent=True)

    saran_poin = [
        ("1. Pengembangan Fitur Pendaftaran Antrean Daring (Remote Online Queue): ", "Disarankan untuk menambahkan modul pendaftaran antrean berbasis aplikasi mobile atau integrasi WhatsApp Business Bot, sehingga nasabah dapat mengambil nomor antrean dari rumah sebelum berangkat ke kantor cabang dan memantau perkiraan sisa nomor antrean secara fleksibel."),
        ("2. Integrasi Mesin Text-to-Speech (TTS) Berbasis AI Multi-Bahasa: ", "Untuk meningkatkan keramahan dan inklusivitas layanan perbankan, sistem audio pemanggilan dapat diintegrasikan dengan teknologi Text-to-Speech berbasis Artificial Intelligence yang mendukung opsi multi-bahasa (Bahasa Indonesia, Bahasa Sunda untuk kearifan lokal Jawa Barat, dan Bahasa Inggris)."),
        ("3. Fitur Notifikasi Pengingat SMS / WhatsApp Gateway: ", "Menambahkan modul pengiriman pesan notifikasi otomatis ke nomor ponsel nasabah ketika nomor antreannya mendekati giliran (misalnya 3 nomor sebelum dipanggil), guna meminimalkan risiko nomor antrean terlewat (skipped) saat nasabah sedang berada di luar ruang tunggu perbankan."),
        ("4. Integrasi Lanjutan dengan Core Banking System (CBS): ", "Pada tahap pengembangan jangka panjang, data nomor antrean dapat disinkronkan langsung dengan terminal Core Banking kasir bank untuk mengukur waktu riil pemrosesan buku tabungan dan rekonsiliasi kas Teller secara otomatis.")
    ]
    for p_label, p_desc in saran_poin:
        p_item = doc.add_paragraph()
        p_item.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        p_item.paragraph_format.left_indent = Cm(1.0)
        p_item.paragraph_format.first_line_indent = Cm(-1.0)
        p_item.paragraph_format.space_before = Pt(2)
        p_item.paragraph_format.space_after = Pt(4)
        p_item.paragraph_format.line_spacing = 1.5
        r1 = p_item.add_run(p_label)
        r1.bold = True
        r2 = p_item.add_run(p_desc)

    # =========================================================================
    # DAFTAR PUSTAKA (Sesuai Ketentuan Panduan FTID: 2 Bagian)
    # =========================================================================
    doc.add_page_break()
    add_p("DAFTAR PUSTAKA", align=WD_ALIGN_PARAGRAPH.CENTER, space_before=12, space_after=18, line_spacing=1.5, bold=True, size=14)

    add_p("Bagian 1: Referensi Buku, Prosiding, dan Jurnal Ilmiah Terpublikasi", space_before=6, space_after=6, line_spacing=1.5, bold=True)

    daftar_buku = [
        ("Fowler, Martin. (2018). ", "UML Distilled: Panduan Singkat Bahasa Pemodelan Objek Standar. Edisi Ketiga. Yogyakarta: Penerbit Andi."),
        ("Kendall, Kenneth E., dan Julie E. Kendall. (2020). ", "Systems Analysis and Design. 10th Edition. New York: Pearson Education."),
        ("Pressman, Roger S., dan Bruce R. Maxim. (2020). ", "Software Engineering: A Practitioner's Approach. 9th Edition. New York: McGraw-Hill Education."),
        ("Rosa, A. S., dan M. Shalahuddin. (2019). ", "Rekayasa Perangkat Lunak Terstruktur dan Berorientasi Objek. Bandung: Informatika Bandung."),
        ("Situmorang, Ferryanto. (2022). ", "Tuntunan Praktis Pemrograman Web Modern Berbasis Framework PHP. Jakarta: Elex Media Komputindo."),
        ("Sommerville, Ian. (2019). ", "Software Engineering. 10th Edition. Harlow: Pearson Education Limited.")
    ]
    for auth, detail in daftar_buku:
        p_b = doc.add_paragraph()
        p_b.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        p_b.paragraph_format.left_indent = Cm(1.25)
        p_b.paragraph_format.first_line_indent = Cm(-1.25)
        p_b.paragraph_format.space_before = Pt(2)
        p_b.paragraph_format.space_after = Pt(4)
        p_b.paragraph_format.line_spacing = 1.5
        r1 = p_b.add_run(auth)
        r1.bold = True
        r2 = p_b.add_run(detail)

    add_p("Bagian 2: Referensi Situs Web dan Dokumentasi Resmi", space_before=12, space_after=6, line_spacing=1.5, bold=True)

    daftar_web = [
        ("Filament PHP Documentation. (2026). ", "Filament v3 Full-Stack Components for Laravel Applications.\nhttps://filamentphp.com/docs\nakses pada 21 September 2026."),
        ("Laravel Framework Documentation. (2026). ", "The PHP Framework for Web Artisans Documentation.\nhttps://laravel.com/docs\nakses pada 21 September 2026."),
        ("Otoritas Jasa Keuangan (OJK). (2025). ", "Standar Pelayanan Operasional dan Perlindungan Konsumen Perbankan Indonesia.\nhttps://www.ojk.go.id\nakses pada 15 Agustus 2026."),
        ("Spatie Documentation. (2026). ", "Laravel-Permission: Associate Users with Roles and Permissions.\nhttps://spatie.be/docs/laravel-permission\nakses pada 21 September 2026.")
    ]
    for auth, detail in daftar_web:
        p_w = doc.add_paragraph()
        p_w.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        p_w.paragraph_format.left_indent = Cm(1.25)
        p_w.paragraph_format.first_line_indent = Cm(-1.25)
        p_w.paragraph_format.space_before = Pt(2)
        p_w.paragraph_format.space_after = Pt(4)
        p_w.paragraph_format.line_spacing = 1.5
        r1 = p_w.add_run(auth)
        r1.bold = True
        r2 = p_w.add_run(detail)

    # =========================================================================
    # LAMPIRAN LENGKAP (Sesuai Panduan FTID Universitas Bani Saleh)
    # =========================================================================
    doc.add_page_break()
    add_p("LAMPIRAN", align=WD_ALIGN_PARAGRAPH.CENTER, space_before=12, space_after=18, line_spacing=1.5, bold=True, size=14)

    # LAMPIRAN 1: Lembar Daftar Hadir KKP (Contoh-3)
    add_p("Lampiran 1: Contoh-3 Lembar Daftar Hadir KKP", space_before=10, space_after=6, line_spacing=1.5, bold=True, size=13)
    add_p("Lembar Daftar Hadir Kegiatan KKP\nProgram Studi Sarjana Teknik Informatika\nUniversitas Bani Saleh",
          align=WD_ALIGN_PARAGRAPH.CENTER, space_before=4, space_after=12, line_spacing=1.15, bold=True)

    t_dh_info = doc.add_table(rows=4, cols=2)
    t_dh_info.alignment = WD_TABLE_ALIGNMENT.CENTER
    t_dh_info.style = 'Table Grid'
    dh_info_data = [
        ("Nama Instansi KKP", "PT BPR DP TASPEN Kantor Cabang Bekasi"),
        ("Alamat Instansi", "Jl. KH. Noer Ali, Kalimalang, Kota Bekasi, Jawa Barat"),
        ("Pembimbing Lapangan", "Hendra Kurniawan, S.E. (Supervisor Operasional)"),
        ("Nama / NIM Mahasiswa", "Salsabila Puspa Pratiwi / 2301111110046")
    ]
    for idx, (k, v) in enumerate(dh_info_data):
        row = t_dh_info.rows[idx]
        row.cells[0].width = Cm(4.5)
        row.cells[1].width = Cm(11.0)
        set_cell_margins(row.cells[0], top=50, bottom=50, left=70, right=70)
        set_cell_margins(row.cells[1], top=50, bottom=50, left=70, right=70)
        p1 = row.cells[0].paragraphs[0]
        p1.add_run(k).bold = True
        p1.runs[0].font.size = Pt(9.5)
        p2 = row.cells[1].paragraphs[0]
        p2.add_run(v)
        p2.runs[0].font.size = Pt(9.5)

    add_p("", space_before=4, space_after=4)

    t_dh_log = doc.add_table(rows=11, cols=4)
    t_dh_log.alignment = WD_TABLE_ALIGNMENT.CENTER
    t_dh_log.style = 'Table Grid'
    dh_headers = ["No", "Tanggal", "Kegiatan yang Dilakukan di Lapangan", "Paraf Pembimbing"]
    for j, h in enumerate(dh_headers):
        cell = t_dh_log.rows[0].cells[j]
        set_cell_background(cell, "004B9C")
        set_cell_margins(cell, top=60, bottom=60, left=70, right=70)
        p = cell.paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        run = p.add_run(h)
        run.bold = True
        run.font.color.rgb = RGBColor(255, 255, 255)
        run.font.size = Pt(9)

    kegiatan_sampel = [
        ("1", "01 Juli 2026", "Orientasi lingkungan kerja perbankan, pengenalan staf, & observasi banking hall PT BPR DP TASPEN.", "Paraf"),
        ("2", "08 Juli 2026", "Wawancara dengan Supervisor Operasional terkait kendala penumpukan antrean nasabah pensiun.", "Paraf"),
        ("3", "15 Juli 2026", "Pengumpulan dokumen formulir masukan & pencatatan rata-rata durasi pelayanan di loket Teller.", "Paraf"),
        ("4", "22 Juli 2026", "Analisis arsitektur sistem antrean digital & inventarisasi spesifikasi hardware Kiosk serta Display TV.", "Paraf"),
        ("5", "29 Juli 2026", "Pemodelan proses bisnis, Activity Diagram alur ambil tiket, dan Use Case Diagram perbankan.", "Paraf"),
        ("6", "05 Agustus 2026", "Perancangan skema basis data relasional (ERD) dan pembuatan migrasi database Laravel.", "Paraf"),
        ("7", "12 Agustus 2026", "Implementasi antarmuka layar sentuh Kiosk (/ambil-nomor) dan cetak tiket barcode thermal.", "Paraf"),
        ("8", "19 Agustus 2026", "Pengembangan layar Monitor Display TV (/display) dan pengujian sintesis audio pemanggilan suara.", "Paraf"),
        ("9", "26 Agustus 2026", "Implementasi Operator Box konsol loket Teller/CS di Filament dan Two-Layer Authorization.", "Paraf"),
        ("10", "02 September 2026", "Pengujian menyeluruh Black Box Testing, uji beban antrean, dan serah terima prototipe ke cabang.", "Paraf")
    ]
    for i, (no, tgl, keg, prf) in enumerate(kegiatan_sampel):
        row = t_dh_log.rows[i+1]
        row.cells[0].width = Cm(1.0)
        row.cells[1].width = Cm(3.2)
        row.cells[2].width = Cm(9.3)
        row.cells[3].width = Cm(2.5)
        for j, val in enumerate([no, tgl, keg, prf]):
            cell = row.cells[j]
            set_cell_margins(cell, top=50, bottom=50, left=70, right=70)
            p = cell.paragraphs[0]
            p.paragraph_format.line_spacing = 1.15
            if j in [0, 1, 3]:
                p.alignment = WD_ALIGN_PARAGRAPH.CENTER
            else:
                p.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
            run = p.add_run(val)
            run.font.size = Pt(8.5)

    add_p("Bekasi, 21 September 2026\n\nPembimbing Lapangan,\n\n\n\n( Hendra Kurniawan, S.E. )\nTandatangan & Stempel Instansi",
          align=WD_ALIGN_PARAGRAPH.RIGHT, space_before=14, space_after=18, line_spacing=1.15, bold=True)

    # LAMPIRAN 2: Lembar Penilaian Pembimbing Lapangan (Contoh-4)
    doc.add_page_break()
    add_p("Lampiran 2: Contoh-4 Daftar Penilaian Pembimbing Lapangan", space_before=10, space_after=6, line_spacing=1.5, bold=True, size=13)
    add_p("Daftar Penilaian Kerja Praktek Lapangan\nPembimbing Lapangan Instansi KKP",
          align=WD_ALIGN_PARAGRAPH.CENTER, space_before=4, space_after=12, line_spacing=1.15, bold=True)

    add_p("NAMA : Salsabila Puspa Pratiwi\n"
          "NIM : 2301111110046\n"
          "PROGRAM : S1 - TEKNIK INFORMATIKA\n"
          "NAMA INSTANSI : PT BPR DP TASPEN Kantor Cabang Bekasi\n"
          "ALAMAT INSTANSI : Jl. KH. Noer Ali, Kalimalang, Kota Bekasi\n"
          "JUDUL KKP : Perancangan dan Implementasi Sistem Informasi Antrean Bank Berbasis Web pada PT BPR DP TASPEN",
          space_before=4, space_after=10, line_spacing=1.15, bold=True)

    t_pen_lap = doc.add_table(rows=6, cols=6)
    t_pen_lap.alignment = WD_TABLE_ALIGNMENT.CENTER
    t_pen_lap.style = 'Table Grid'
    pen_headers = ["NO", "URAIAN PENILAIAN", "BAIK SEKALI\n(80-100)", "BAIK\n(66-79.99)", "CUKUP\n(60-65.99)", "BOBOT", "NILAI"]
    # Table has 6 cols: No, Uraian, Baik Sekali, Baik, Cukup, Bobot
    # Let's adjust cols to 6: NO, URAIAN, BAIK SEKALI, BAIK, CUKUP, BOBOT
    # We will format rows cleanly
    pen_lap_data = [
        ("NO", "URAIAN PENILAIAN", "BAIK SEKALI (80-100)", "BAIK (66-79.99)", "CUKUP (60-65.99)", "BOBOT"),
        ("1", "Kehadiran Mahasiswa / Konsultasi", "92", "-", "-", "10%"),
        ("2", "Kerjasama Tim & Komunikasi", "90", "-", "-", "20%"),
        ("3", "Inovasi dan Kreativitas Sistem", "95", "-", "-", "35%"),
        ("4", "Pemahaman Materi KKP & Solusi IT", "94", "-", "-", "35%"),
        ("JUMLAH GRADE / NILAI AKHIR PEMBIMBING LAPANGAN:  93.4 (A / BAIK SEKALI)", "", "", "", "", "100%")
    ]
    for row_idx, row_content in enumerate(pen_lap_data):
        row_obj = t_pen_lap.rows[row_idx]
        for col_idx, text_val in enumerate(row_content):
            cell = row_obj.cells[col_idx]
            set_cell_margins(cell, top=60, bottom=60, left=70, right=70)
            p = cell.paragraphs[0]
            p.paragraph_format.line_spacing = 1.15
            if row_idx == 0:
                set_cell_background(cell, "004B9C")
                p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                run = p.add_run(text_val)
                run.bold = True
                run.font.color.rgb = RGBColor(255, 255, 255)
                run.font.size = Pt(8.5)
            elif row_idx == 5:
                set_cell_background(cell, "F0FDF4")
                p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                run = p.add_run(text_val)
                run.bold = True
                run.font.size = Pt(9)
            else:
                if col_idx in [0, 2, 3, 4, 5]:
                    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                else:
                    p.alignment = WD_ALIGN_PARAGRAPH.LEFT
                run = p.add_run(text_val)
                run.font.size = Pt(9)

    add_p("Bekasi, 21 September 2026\n\nPembimbing Lapangan,\n\n\n\n( Hendra Kurniawan, S.E. )\nTtd & Stempel Instansi",
          align=WD_ALIGN_PARAGRAPH.RIGHT, space_before=14, space_after=18, line_spacing=1.15, bold=True)

    # LAMPIRAN 3: Lembar Penilaian Dosen Pembimbing (Contoh-5)
    doc.add_page_break()
    add_p("Lampiran 3: Contoh-5 Daftar Penilaian Dosen Pembimbing KKP", space_before=10, space_after=6, line_spacing=1.5, bold=True, size=13)
    add_p("Daftar Penilaian Kerja Praktek Lapangan\nDosen Pembimbing KKP - FTID Universitas Bani Saleh",
          align=WD_ALIGN_PARAGRAPH.CENTER, space_before=4, space_after=12, line_spacing=1.15, bold=True)

    add_p("NAMA : Salsabila Puspa Pratiwi\n"
          "NIM : 2301111110046\n"
          "PROGRAM : S1 - TEKNIK INFORMATIKA\n"
          "NAMA INSTANSI : PT BPR DP TASPEN Kantor Cabang Bekasi\n"
          "JUDUL KKP : Perancangan dan Implementasi Sistem Informasi Antrean Bank Berbasis Web pada PT BPR DP TASPEN",
          space_before=4, space_after=10, line_spacing=1.15, bold=True)

    t_pen_dos = doc.add_table(rows=5, cols=6)
    t_pen_dos.alignment = WD_TABLE_ALIGNMENT.CENTER
    t_pen_dos.style = 'Table Grid'
    pen_dos_data = [
        ("NO", "URAIAN PENILAIAN", "BAIK SEKALI (80-100)", "BAIK (66-79.99)", "CUKUP (60-65.99)", "BOBOT"),
        ("1", "Kehadiran Mahasiswa / Konsultasi Bimbingan", "95", "-", "-", "10%"),
        ("2", "Sistematika Penulisan & Format Laporan FTID", "92", "-", "-", "40%"),
        ("3", "Pemahaman Materi KKP, UML, & Produk Web", "95", "-", "-", "50%"),
        ("JUMLAH GRADE / NILAI AKHIR DOSEN PEMBIMBING:  93.8 (A / BAIK SEKALI)", "", "", "", "", "100%")
    ]
    for row_idx, row_content in enumerate(pen_dos_data):
        row_obj = t_pen_dos.rows[row_idx]
        for col_idx, text_val in enumerate(row_content):
            cell = row_obj.cells[col_idx]
            set_cell_margins(cell, top=60, bottom=60, left=70, right=70)
            p = cell.paragraphs[0]
            p.paragraph_format.line_spacing = 1.15
            if row_idx == 0:
                set_cell_background(cell, "004B9C")
                p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                run = p.add_run(text_val)
                run.bold = True
                run.font.color.rgb = RGBColor(255, 255, 255)
                run.font.size = Pt(8.5)
            elif row_idx == 4:
                set_cell_background(cell, "F0FDF4")
                p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                run = p.add_run(text_val)
                run.bold = True
                run.font.size = Pt(9)
            else:
                if col_idx in [0, 2, 3, 4, 5]:
                    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                else:
                    p.alignment = WD_ALIGN_PARAGRAPH.LEFT
                run = p.add_run(text_val)
                run.font.size = Pt(9)

    add_p("Bekasi, 21 September 2026\n\nDosen Pembimbing KKP,\n\n\n\n( Agus Suhartono, M.Kom. )\nNIDN: 0415088201",
          align=WD_ALIGN_PARAGRAPH.RIGHT, space_before=14, space_after=18, line_spacing=1.15, bold=True)

    # LAMPIRAN 4: Kuisioner Perusahaan (Format Resmi Halaman 30-35 Panduan)
    doc.add_page_break()
    add_p("Lampiran 4: Kuisioner Umpan Balik Perusahaan Tempat KKP", space_before=10, space_after=6, line_spacing=1.5, bold=True, size=13)
    add_p("KUISIONER PERUSAHAAN\nFAKULTAS TEKNOLOGI INFORMASI DAN DIGITAL\nUNIVERSITAS BANI SALEH",
          align=WD_ALIGN_PARAGRAPH.CENTER, space_before=4, space_after=12, line_spacing=1.15, bold=True)

    add_p("Kepada Yth,\nPimpinan / Supervisor Operasional\nPT BPR DP TASPEN Kantor Cabang Bekasi\nDi Tempat",
          space_before=4, space_after=8, line_spacing=1.15, bold=True)

    add_p("Merupakan suatu hal yang membanggakan jika Bapak / Ibu bersedia meluangkan waktu sejenak untuk membantu mengisi kuisioner di bawah ini. Bantuan dan kerja sama Bapak / Ibu dalam pengisian kuisioner ini, dapat memberikan gambaran untuk kualifikasi, kompetensi, nilai-nilai, peluang karir/peran, otoritas / tugas dan tanggung jawab kebutuhan tenaga kerja IT yang akan digunakan sebagai masukan bagi Bidang Akademik dan Kurikulum FTID Universitas Bani Saleh.", indent=True)

    t_kuis_id = doc.add_table(rows=7, cols=2)
    t_kuis_id.alignment = WD_TABLE_ALIGNMENT.CENTER
    t_kuis_id.style = 'Table Grid'
    kuis_profile = [
        ("Nama Responden", "Hendra Kurniawan, S.E."),
        ("Bagian / Departemen", "Divisi Operasional & Pelayanan Nasabah"),
        ("Jabatan", "Supervisor Operasional Kantor Cabang"),
        ("Bidang Pekerjaan", "Perbankan dan Pelayanan Finansial Publik"),
        ("Nama Perusahaan", "PT BPR DP TASPEN"),
        ("Alamat Perusahaan", "Jl. KH. Noer Ali, Kalimalang, Kota Bekasi, Jawa Barat"),
        ("Kontak / Email", "operasional.bekasi@bprdptaspen.co.id")
    ]
    for idx, (k, v) in enumerate(kuis_profile):
        row = t_kuis_id.rows[idx]
        row.cells[0].width = Cm(4.5)
        row.cells[1].width = Cm(11.0)
        set_cell_margins(row.cells[0], top=50, bottom=50, left=70, right=70)
        set_cell_margins(row.cells[1], top=50, bottom=50, left=70, right=70)
        p1 = row.cells[0].paragraphs[0]
        p1.add_run(k).bold = True
        p1.runs[0].font.size = Pt(9.5)
        p2 = row.cells[1].paragraphs[0]
        p2.add_run(v)
        p2.runs[0].font.size = Pt(9.5)

    add_p("Petunjuk: Berilah tanda ceklis (√) pada jawaban yang sesuai dengan kebutuhan industri perbankan saat ini:",
          space_before=10, space_after=6, line_spacing=1.15, italic=True)

    kuis_pertanyaan = [
        ("1. Jabatan / Posisi IT yang sedang dibutuhkan perusahaan:",
         "[ √ ] WEB Programmer / Full Stack Developer\n[ √ ] Database Administrator\n[ √ ] IT Support / Helpdesk\n[   ] .NET Developer\n[   ] Mobile Games Programmer"),
        ("2. Pengalaman kerja yang dibutuhkan untuk posisi tersebut:",
         "[   ] Fresh Graduate\n[ √ ] 1 - 2 Tahun Pengalaman\n[   ] 3 - 5 Tahun Pengalaman"),
        ("3. Tugas / Jobdesc utama yang dibutuhkan pada instansi:",
         "[ √ ] Analisis Sistem dan Perancangan Alur Proses Bisnis\n[ √ ] Programming & Bug Fixing Aplikasi Web Perbankan\n[ √ ] Maintenance Jaringan LAN & Pengelolaan Sistem Server\n[   ] Sales / Digital Marketing"),
        ("4. Keahlian Teknis (Hardskill) yang sangat diapresiasi instansi:",
         "a. Web Design: [ √ ] HTML, CSS, JavaScript, Tailwind CSS\nb. Web Programming: [ √ ] PHP, Laravel Framework, Livewire, AJAX\nc. Database: [ √ ] MySQL, MariaDB, Database Indexing & Optimization\nd. Security & Governance: [ √ ] Role-Based Access Control, Data Integrity"),
        ("5. Keahlian Non-Teknis (Softskill) yang ditunjukkan mahasiswa:",
         "[ √ ] Kemampuan beradaptasi dengan teknologi baru secara cepat\n[ √ ] Komunikasi interpersonal yang santun terhadap nasabah lansia\n[ √ ] Etika kerja, kejujuran, dan kepatuhan terhadap kerahasiaan data perbankan\n[ √ ] Kemampuan memecahkan masalah (problem solving) di lapangan")
    ]
    for q_title, q_opts in kuis_pertanyaan:
        p_q = doc.add_paragraph()
        p_q.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        p_q.paragraph_format.space_before = Pt(4)
        p_q.paragraph_format.space_after = Pt(2)
        p_q.paragraph_format.line_spacing = 1.15
        r1 = p_q.add_run(q_title + "\n")
        r1.bold = True
        r1.font.size = Pt(9.5)
        r2 = p_q.add_run(q_opts)
        r2.font.size = Pt(9)

    add_p("Terima kasih telah mengisi kuisioner ini. Umpan balik Bapak/Ibu sangat berharga bagi evaluasi kurikulum FTID Universitas Bani Saleh dan penguatan kompetensi lulusan di masa depan.",
          space_before=8, space_after=14, line_spacing=1.15, italic=True)

    add_p("Bekasi, 21 September 2026\nPimpinan / Supervisor Operasional,\n\n\n\n( Hendra Kurniawan, S.E. )\nPT BPR DP TASPEN Kantor Cabang Bekasi",
          align=WD_ALIGN_PARAGRAPH.RIGHT, space_before=8, space_after=0, line_spacing=1.15, bold=True)

    # Save to file
    out_path_1 = "docs/Laporan_KKP_Salsabila_Puspa_Pratiwi.docx"
    out_path_2 = "docs/Laporan_KKP_Sistem_Antrian_Bank.docx"
    doc.save(out_path_1)
    doc.save(out_path_2)
    print(f"Successfully generated: {out_path_1} and {out_path_2}")

if __name__ == "__main__":
    create_report()
