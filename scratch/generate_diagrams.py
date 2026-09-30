import os
import matplotlib.pyplot as plt
import matplotlib.patches as patches

os.makedirs('docs/images', exist_ok=True)

def draw_arrow(ax, x1, y1, x2, y2, color='#002B49'):
    ax.annotate('', xy=(x2, y2), xytext=(x1, y1),
                arrowprops=dict(facecolor=color, edgecolor=color, width=1.2, headwidth=6, headlength=7))

# 3. Activity Diagram: Pengambilan Tiket
fig, ax = plt.subplots(figsize=(9, 6.5), dpi=200)
ax.axis('off')

# Swimlanes
ax.plot([4.5, 4.5], [0.5, 6.2], color='#94A3B8', lw=1.5, linestyle='--')
ax.text(2.25, 6.0, 'NASABAH', ha='center', va='center', fontsize=11, weight='bold', color='#004B9C')
ax.text(6.75, 6.0, 'SISTEM KIOSK TIKET', ha='center', va='center', fontsize=11, weight='bold', color='#004B9C')

def draw_act(ax, text, x, y, w=3.4, h=0.55, bg='#F0FDF4', ec='#16A34A', fg='#14532D'):
    rect = patches.FancyBboxPatch((x - w/2, y - h/2), w, h, boxstyle='round,pad=0.02,rounding_size=0.08',
                                  facecolor=bg, edgecolor=ec, linewidth=1.5)
    ax.add_patch(rect)
    ax.text(x, y, text, ha='center', va='center', fontsize=8.5, weight='bold', color=fg, multialignment='center')

# Start node
start = plt.Circle((2.25, 5.4), 0.15, fill=True, color='#002B49')
ax.add_patch(start)

draw_act(ax, "Datang ke Bank &\nMenuju Mesin Kiosk", 2.25, 4.5, bg='#EFF6FF', ec='#0284C7', fg='#0369A1')
draw_act(ax, "Menampilkan Pilihan Layanan\n(Teller / CS)", 6.75, 4.5)
draw_act(ax, "Menekan Tombol Layanan\n(Layanan Kas / Layanan CS)", 2.25, 3.4, bg='#EFF6FF', ec='#0284C7', fg='#0369A1')
draw_act(ax, "Generate Nomor Antrean Baru\n(Prefix A / B, Status: waiting)", 6.75, 3.4)
draw_act(ax, "Cetak Struk Tiket Thermal\n(Barcode, QR, Jam, & No)", 6.75, 2.3)
draw_act(ax, "Mengambil Tiket Fisik &\nMenuju Ruang Tunggu", 2.25, 2.3, bg='#EFF6FF', ec='#0284C7', fg='#0369A1')

# End node
end_outer = plt.Circle((2.25, 1.2), 0.2, fill=False, edgecolor='#002B49', lw=1.5)
end_inner = plt.Circle((2.25, 1.2), 0.12, fill=True, color='#002B49')
ax.add_patch(end_outer)
ax.add_patch(end_inner)

draw_arrow(ax, 2.25, 5.25, 2.25, 4.8)
draw_arrow(ax, 3.95, 4.5, 5.05, 4.5)
draw_arrow(ax, 6.75, 4.2, 3.95, 3.4)
draw_arrow(ax, 3.95, 3.4, 5.05, 3.4)
draw_arrow(ax, 6.75, 3.1, 6.75, 2.6)
draw_arrow(ax, 5.05, 2.3, 3.95, 2.3)
draw_arrow(ax, 2.25, 2.0, 2.25, 1.42)

ax.set_xlim(0.2, 8.8)
ax.set_ylim(0.8, 6.4)
plt.tight_layout()
plt.savefig('docs/images/activity_diagram_ambil_nomor.png', dpi=200, bbox_inches='tight')
plt.close()
print('Generated activity_diagram_ambil_nomor.png')

# 4. Activity Diagram: Pemanggilan & Pelayanan
fig, ax = plt.subplots(figsize=(10, 7.5), dpi=200)
ax.axis('off')
ax.plot([3.4, 3.4], [0.5, 7.2], color='#94A3B8', lw=1.5, linestyle='--')
ax.plot([6.8, 6.8], [0.5, 7.2], color='#94A3B8', lw=1.5, linestyle='--')
ax.text(1.7, 7.0, 'PETUGAS (TELLER/CS)', ha='center', va='center', fontsize=10.5, weight='bold', color='#004B9C')
ax.text(5.1, 7.0, 'SISTEM APLIKASI', ha='center', va='center', fontsize=10.5, weight='bold', color='#004B9C')
ax.text(8.5, 7.0, 'DISPLAY & NASABAH', ha='center', va='center', fontsize=10.5, weight='bold', color='#004B9C')

start = plt.Circle((1.7, 6.4), 0.15, fill=True, color='#002B49')
ax.add_patch(start)

draw_act(ax, "Klik Tombol 'Panggil Antrean'\ndi Operator Console", 1.7, 5.6, w=2.9, bg='#EFF6FF', ec='#0284C7', fg='#0369A1')
draw_act(ax, "Ambil Antrean Terlama\n(Status: waiting -> called)\nCatat called_at & served_by", 5.1, 5.6, w=3.1)
draw_act(ax, "Display TV Update Nomor &\nAudio Panggilan Berbunyi", 8.5, 5.6, w=3.0, bg='#FEF3C7', ec='#D97706', fg='#92400E')
draw_act(ax, "Nasabah Hadir ke Loket?", 1.7, 4.3, w=2.8, bg='#EFF6FF', ec='#0284C7', fg='#0369A1')

# Decision Diamond
diamond = patches.Polygon([[1.7, 3.5], [2.2, 3.1], [1.7, 2.7], [1.2, 3.1]], closed=True,
                          facecolor='#FDE68A', edgecolor='#D97706', lw=1.5)
ax.add_patch(diamond)
ax.text(1.7, 3.1, 'Hadir?', ha='center', va='center', fontsize=8, weight='bold', color='#78350F')

draw_act(ax, "Klik Tombol 'Lewati'\n(Status: skipped)", 1.7, 1.8, w=2.6, bg='#FEE2E2', ec='#EF4444', fg='#991B1B')
draw_act(ax, "Layani Transaksi Nasabah\n(Status: serving)", 5.1, 3.1, w=2.9, bg='#EFF6FF', ec='#0284C7', fg='#0369A1')
draw_act(ax, "Klik 'Selesai Dilayani'\n(Status: completed)", 5.1, 1.8, w=3.1)
draw_act(ax, "Nasabah Selesai Transaksi\n& Tinggalkan Bank", 8.5, 1.8, w=2.9, bg='#FEF3C7', ec='#D97706', fg='#92400E')

end_outer = plt.Circle((5.1, 0.8), 0.2, fill=False, edgecolor='#002B49', lw=1.5)
end_inner = plt.Circle((5.1, 0.8), 0.12, fill=True, color='#002B49')
ax.add_patch(end_outer)
ax.add_patch(end_inner)

draw_arrow(ax, 1.7, 6.25, 1.7, 5.9)
draw_arrow(ax, 3.15, 5.6, 3.55, 5.6)
draw_arrow(ax, 6.65, 5.6, 7.0, 5.6)
draw_arrow(ax, 8.5, 5.25, 3.15, 4.3)
draw_arrow(ax, 1.7, 4.0, 1.7, 3.5)

# Branches
draw_arrow(ax, 1.7, 2.7, 1.7, 2.1)
ax.text(1.85, 2.4, '[Tidak]', fontsize=8, weight='bold', color='#991B1B')

draw_arrow(ax, 2.2, 3.1, 3.65, 3.1)
ax.text(2.8, 3.25, '[Ya]', fontsize=8, weight='bold', color='#16A34A')

draw_arrow(ax, 5.1, 2.8, 5.1, 2.1)
draw_arrow(ax, 6.65, 1.8, 7.05, 1.8)
draw_arrow(ax, 5.1, 1.5, 5.1, 1.02)
draw_arrow(ax, 3.0, 1.8, 4.9, 1.0)

ax.set_xlim(0, 10.2)
ax.set_ylim(0.4, 7.4)
plt.tight_layout()
plt.savefig('docs/images/activity_diagram_pelayanan.png', dpi=200, bbox_inches='tight')
plt.close()
print('Generated activity_diagram_pelayanan.png')

# 5. ERD Diagram
fig, ax = plt.subplots(figsize=(10, 7), dpi=200)
ax.axis('off')

def draw_table_card(ax, title, fields, x, y, w=3.4, h=2.5, header_bg='#004B9C'):
    rect = patches.FancyBboxPatch((x - w/2, y - h/2), w, h, boxstyle='round,pad=0.02,rounding_size=0.05',
                                  facecolor='#FFFFFF', edgecolor='#002B49', linewidth=1.5)
    ax.add_patch(rect)
    header_h = 0.45
    h_rect = patches.FancyBboxPatch((x - w/2, y + h/2 - header_h), w, header_h,
                                    boxstyle='round,pad=0.02,rounding_size=0.05',
                                    facecolor=header_bg, edgecolor='#002B49', linewidth=1.5)
    ax.add_patch(h_rect)
    ax.text(x, y + h/2 - header_h/2, title, ha='center', va='center', color='white', fontsize=9.5, weight='bold')
    
    start_y = y + h/2 - header_h - 0.22
    for field, ftype in fields:
        ax.text(x - w/2 + 0.15, start_y, field, ha='left', va='center', color='#0F172A', fontsize=8, weight='bold')
        ax.text(x + w/2 - 0.15, start_y, ftype, ha='right', va='center', color='#64748B', fontsize=7.5)
        start_y -= 0.26

users_fields = [
    ('id (PK)', 'BIGINT (Unsigned)'),
    ('name', 'VARCHAR(255)'),
    ('email', 'VARCHAR(255) [UNIQUE]'),
    ('password', 'VARCHAR(255)'),
    ('service_setting_id (FK)', 'BIGINT [NULLABLE]'),
    ('status', 'VARCHAR(20) [active]'),
    ('last_login_at', 'DATETIME [NULL]'),
    ('created_at / updated_at', 'TIMESTAMP')
]

service_settings_fields = [
    ('id (PK)', 'BIGINT (Unsigned)'),
    ('service', 'VARCHAR(50) [teller/cs]'),
    ('counter_name', 'VARCHAR(100)'),
    ('counter_code', 'VARCHAR(10) [A/B]'),
    ('is_active', 'BOOLEAN [1]'),
    ('created_at / updated_at', 'TIMESTAMP')
]

queues_fields = [
    ('id (PK)', 'BIGINT (Unsigned)'),
    ('number', 'VARCHAR(20) [e.g. A001]'),
    ('service', 'VARCHAR(50)'),
    ('service_setting_id (FK)', 'BIGINT [NULLABLE]'),
    ('served_by (FK)', 'BIGINT [NULLABLE]'),
    ('status', 'ENUM [waiting..skipped]'),
    ('called_at', 'DATETIME [NULL]'),
    ('completed_at', 'DATETIME [NULL]'),
    ('created_at / updated_at', 'TIMESTAMP')
]

draw_table_card(ax, 'service_settings', service_settings_fields, 2.2, 5.0, w=3.4, h=2.2, header_bg='#0284C7')
draw_table_card(ax, 'users', users_fields, 2.2, 1.8, w=3.4, h=2.7, header_bg='#004B9C')
draw_table_card(ax, 'queues', queues_fields, 7.5, 3.4, w=3.6, h=3.0, header_bg='#0369A1')

# Relationships lines
ax.plot([2.2, 2.2], [3.9, 3.15], color='#002B49', lw=1.5)
ax.text(2.35, 3.7, '1', fontsize=9, weight='bold', color='#002B49')
ax.text(2.35, 3.3, 'N', fontsize=9, weight='bold', color='#002B49')

ax.plot([3.9, 5.7], [5.0, 4.2], color='#002B49', lw=1.5)
ax.text(4.1, 5.1, '1', fontsize=9, weight='bold', color='#002B49')
ax.text(5.5, 4.4, 'N', fontsize=9, weight='bold', color='#002B49')

ax.plot([3.9, 5.7], [1.8, 2.6], color='#002B49', lw=1.5)
ax.text(4.1, 1.9, '1', fontsize=9, weight='bold', color='#002B49')
ax.text(5.5, 2.4, 'N', fontsize=9, weight='bold', color='#002B49')

ax.set_xlim(0.2, 9.8)
ax.set_ylim(0.2, 6.6)
plt.tight_layout()
plt.savefig('docs/images/erd_sistem_antrian.png', dpi=200, bbox_inches='tight')
plt.close()
print('Generated erd_sistem_antrian.png')

# 6. Two-Layer Security Architecture
fig, ax = plt.subplots(figsize=(9.5, 5.5), dpi=200)
ax.axis('off')

l1_rect = patches.FancyBboxPatch((0.5, 3.0), 8.5, 1.9, boxstyle='round,pad=0.02,rounding_size=0.05',
                                 facecolor='#EFF6FF', edgecolor='#004B9C', lw=2)
ax.add_patch(l1_rect)
ax.text(4.75, 4.6, 'LAPISAN 1: HAK AKSES FITUR / RBAC (SPATIE LARAVEL-PERMISSION & FILAMENT SHIELD)',
        ha='center', va='center', fontsize=9.5, weight='bold', color='#004B9C')
ax.text(4.75, 3.8, 'Mengontrol otorisasi ke menu, tombol aksi, dan rute navigasi sistem.\nContoh: can("access_teller_console"), can("access_cs_console"), can("reset_queues").\nJika user tidak memiliki permission -> Ditolak (HTTP 403 Forbidden).',
        ha='center', va='center', fontsize=8.5, color='#1E293B', multialignment='center')

l2_rect = patches.FancyBboxPatch((0.5, 0.5), 8.5, 1.9, boxstyle='round,pad=0.02,rounding_size=0.05',
                                 facecolor='#F0FDF4', edgecolor='#16A34A', lw=2)
ax.add_patch(l2_rect)
ax.text(4.75, 2.1, 'LAPISAN 2: DATA ASSIGNMENT / LOKET OWNERSHIP POLICY',
        ha='center', va='center', fontsize=9.5, weight='bold', color='#16A34A')
ax.text(4.75, 1.3, 'Mengontrol lingkup data loket spesifik yang boleh dikelola oleh petugas operasional.\nPetugas terikat pada users.service_setting_id -> Hanya dapat memanggil tiket di loketnya sendiri.\nAdministrator memiliki bypass wewenang (super-admin) untuk mengelola semua loket.',
        ha='center', va='center', fontsize=8.5, color='#14532D', multialignment='center')

draw_arrow(ax, 4.75, 2.95, 4.75, 2.45)

ax.set_xlim(0, 9.5)
ax.set_ylim(0.2, 5.2)
plt.tight_layout()
plt.savefig('docs/images/arsitektur_keamanan.png', dpi=200, bbox_inches='tight')
plt.close()
print('Generated arsitektur_keamanan.png')
