# PRD MVP — Aplikasi 5R: Temuan, WISPO & Booking Ruangan (Versi 3 Minggu)

**Versi:** MVP-2.0
**Diturunkan dari:** PRD Aplikasi 5R v1.1 (24 Sep 2026)
**Target deploy:** akhir minggu ke-3 (15 hari kerja)
**Skala:** satu kantor/pabrik, <100 pengguna, **internal saja** (tanpa akses pihak eksternal)

> Dokumen ini adalah **satu-satunya acuan scope** selama 3 minggu pengembangan. Fitur yang tidak tertulis di Bagian 2 **tidak dikerjakan** sampai MVP live. Permintaan fitur baru dicatat di Bagian 10 (Backlog), bukan langsung dikerjakan.

---

## 1. Tujuan MVP

Tiga modul internal yang langsung dipakai sehari-hari:

| Modul | Masalah yang diselesaikan |
|---|---|
| **A. Temuan & Tindak Lanjut** | Masalah 5R di lapangan dilaporkan siapa saja, punya PIC & deadline, dan terlacak sampai selesai |
| **B. WISPO (inti)** | Stok alat pendukung gudang tercatat rapi; permintaan & peminjaman barang lewat sistem dengan persetujuan |
| **C. Booking Ruangan (internal)** | Ruang kolaborasi dipesan lewat kalender tanpa bentrok jadwal |

**Definisi selesai (Definition of Done) MVP:**
1. Staff bisa login dengan email + password dari browser HP & laptop
2. Saat UAT, user asli berhasil menjalankan: 1 temuan sampai Selesai, 1 permintaan + 1 peminjaman barang sampai dikembalikan, 1 booking ruangan disetujui
3. Stok barang di sistem selalu sama dengan riwayat transaksinya
4. Backup database harian otomatis sudah aktif

> **Catatan:** Audit 5R terjadwal dengan checklist & skor **tidak** masuk MVP ini. Pastikan pemberi tugas sudah menyetujui hal ini (lihat Bagian 12).

---

## 2. Scope

### 2.1 Fondasi (WAJIB, dipakai ketiga modul)

| # | Fitur | Ringkasan |
|---|---|---|
| D1 | Login & Role | Login email + password (akun dibuat Admin, tanpa registrasi publik); role & flag di Bagian 3. Login Google menyusul jika perusahaan memakai Google Workspace |
| D2 | Admin: User & Area | CRUD user (role, flag Petugas Gudang, aktif/nonaktif); CRUD area/zona (kategori, PIC default, kapasitas & fasilitas untuk ruang kolaborasi) |
| D3 | Notifikasi | Email (via queue) + notifikasi in-app (ikon lonceng) |
| D4 | Beranda "Perlu Perhatian Saya" | Ringkasan tugas user: temuan saya, permintaan barang saya, booking saya, dan antrean persetujuan (sesuai hak akses) |

### 2.2 Modul A — Temuan & Tindak Lanjut (WAJIB)

| # | Fitur | Acuan PRD asli |
|---|---|---|
| A1 | **Lapor Cepat**: semua user kirim foto + area + deskripsi (+ prioritas usulan) → temuan `belum_ditugaskan` | §3.4 |
| A2 | **Penugasan PIC**: Koordinator 5R/Admin memilih PIC (default: PIC area) & prioritas → deadline otomatis | §3.4, §3.5 |
| A3 | **Tindak lanjut**: PIC upload foto "sesudah" + catatan → `menunggu_verifikasi` | §3.2 |
| A4 | **Verifikasi**: Koordinator 5R/Admin → Selesai, atau kembali ke PIC dengan catatan revisi. Bisa juga **tolak** laporan yang tidak valid | §3.2 |
| A5 | **Status Terlambat** otomatis + reminder H-1 + eskalasi ke Management | §3.5 |
| A6 | **Daftar temuan** dengan filter (status, area, prioritas, PIC) + export Excel | §3.7 (disederhanakan) |

### 2.3 Modul B — WISPO Inti (WAJIB)

| # | Fitur | Acuan PRD asli |
|---|---|---|
| B1 | **Master Barang**: kode otomatis `SPO-XXXX`, nama, kategori, satuan, foto, lokasi simpan, safety stock (dikelola Petugas Gudang/Admin) | §3.11, §4.2 `wispo_items` |
| B2 | **Transaksi Masuk / Keluar / Reject** oleh Petugas Gudang → stok terupdate otomatis + riwayat | §3.11.2 |
| B3 | **Lihat Stok** (semua user, read-only): cari & filter, status Aman/Menipis | §3.11.4 |
| B4 | **Permintaan & Peminjaman internal**: pilih barang, jenis, kategori pemakaian, qty, tujuan, (tanggal kembali untuk peminjaman) → persetujuan Petugas Gudang | §3.11.3 (jalur internal) |
| B5 | **Pengembalian**: Petugas Gudang konfirmasi barang kembali → stok bertambah. Lewat tanggal → `terlambat` + reminder H-1 | §3.11.3 |
| B6 | **Notifikasi Stok Menipis** ke Petugas Gudang & Admin | §3.11.5 |
| B7 | **Ringkasan WISPO** sederhana: daftar stok menipis, permintaan menunggu, peminjaman berjalan/terlambat, 10 transaksi terakhir + export riwayat transaksi ke Excel | §3.11.7 (disederhanakan) |

### 2.4 Modul C — Booking Ruangan Internal (WAJIB)

| # | Fitur | Acuan PRD asli |
|---|---|---|
| C1 | **Kalender ketersediaan** per ruang kolaborasi (tampilan harian/mingguan) | §3.9.1 |
| C2 | **Form booking** (mode **Offline** saja): ruang, tanggal, jam mulai–selesai, tujuan, jumlah peserta. Sistem **menolak jadwal bentrok** | §3.9.1 |
| C3 | **Persetujuan**: ruang dengan `requires_approval` → `menunggu_persetujuan` (disetujui PIC area/Admin); selain itu langsung `disetujui` | §3.9.1 |
| C4 | **Booking Saya**: lihat & batalkan booking sendiri, status ditampilkan sebagai stepper | §3.9.3 |
| C5 | **Reminder** email H-1 ke pemesan | §3.9.1 |

### 2.5 Nice to have (hanya setelah semua di atas selesai & teruji)

| # | Fitur |
|---|---|
| N1 | Upload foto saat pengembalian barang / transaksi reject |
| N2 | Grafik tren transaksi masuk vs keluar 6 bulan (Chart.js) |
| N3 | Booking mode Hybrid/Online dengan **input link Zoom manual** (tanpa Zoom API) |
| N4 | Import Master Barang dari Excel/Google Sheets existing (jika data lama banyak) |

### 2.6 Di luar MVP (fase berikutnya)

Audit 5R terjadwal + checklist + skor · Dashboard tren skor 5R · Gamifikasi (poin, badge, leaderboard) · WISPO: stock opname, prediksi kehabisan stok, dashboard WISPO lengkap · Zoom API otomatis · **Landing page eksternal** (booking & permintaan barang oleh pihak luar, Cek Status) · Lost & Found · Inventaris Aset + Scan QR · Chatbot RAG · WhatsApp · Dashboard TV · Peta Isometrik · Super Admin & akun lokal · Mode gelap.

---

## 3. Role & Hak Akses

**Role** (`users.role`, satu per user): `staff`, `koordinator_5r`, `management`, `admin`
**Flag tambahan** (bisa digabung dengan role apa pun): `is_petugas_gudang`
**PIC** bukan role — PIC temuan = `findings.pic_id`; PIC area = `areas.default_pic_id`.

| Aksi | Staff | Koordinator 5R | Management | Petugas Gudang* | Admin |
|---|:-:|:-:|:-:|:-:|:-:|
| Lapor Cepat | ✅ | ✅ | ✅ | ✅ | ✅ |
| Tindak lanjut temuan (jika ditunjuk PIC) | ✅ | ✅ | – | ✅ | ✅ |
| Tunjuk PIC, verifikasi, tolak temuan | – | ✅ | – | – | ✅ |
| Lihat semua temuan & export | – | ✅ | ✅ | – | ✅ |
| Lihat stok WISPO | ✅ | ✅ | ✅ | ✅ | ✅ |
| Ajukan permintaan/peminjaman barang | ✅ | ✅ | ✅ | ✅ | ✅ |
| Kelola master barang, catat transaksi, setujui permintaan, konfirmasi pengembalian | – | – | – | ✅ | ✅ |
| Booking ruangan | ✅ | ✅ | ✅ | ✅ | ✅ |
| Setujui booking | PIC area tsb | PIC area tsb | – | – | ✅ |
| Kelola user & area | – | – | – | – | ✅ |

\* Petugas Gudang = user dengan `is_petugas_gudang = true`, ditetapkan Admin.

> Semua hak akses **wajib divalidasi di backend** (Laravel Policy/Gate), bukan hanya disembunyikan di UI.

---

## 4. Alur Aplikasi

### 4.1 Setup Awal (Admin, sekali)
```
Admin login
  → Tambah Area: nama, kategori (operasional/kolaborasi/publik), PIC default
      → untuk area kolaborasi: kapasitas, fasilitas, requires_approval
  → Tambah/import User, set role, centang Petugas Gudang bila perlu
Petugas Gudang
  → Input Master Barang + stok awal + safety stock (atau import, N4)
```

### 4.2 Modul A — Temuan
```
[Lapor Cepat — siapa saja]
  → Foto (wajib) + area + deskripsi + prioritas usulan → Submit
  → Temuan: status belum_ditugaskan → email ke Koordinator 5R

[Penugasan — Koordinator 5R/Admin]
  → Buka temuan → pilih PIC (default: PIC area) + tetapkan prioritas
  → deadline = waktu penugasan + (tinggi 1 hari / sedang 7 hari / rendah 14 hari)
  → status belum_selesai, assigned_at = now() → email ke PIC
  → (atau) Tolak: status ditolak + alasan → email ke pelapor

[Tindak lanjut — PIC]
  → Klik link email → detail temuan → upload foto "sesudah" + catatan
  → status menunggu_verifikasi → email ke Koordinator 5R

[Verifikasi — Koordinator 5R/Admin]
  → OK     → status selesai, resolved_at = now() → email ke PIC & pelapor
  → Revisi → status belum_selesai + revision_note → email ke PIC
```

**Status temuan:**
```
belum_ditugaskan ──→ belum_selesai ──→ menunggu_verifikasi ──→ selesai
       │                 ↑    │                 │
       └→ ditolak        │    └→ terlambat ─────┘ (tetap bisa submit)
                         └──── revisi ←─────────┘
```

### 4.3 Modul B — WISPO

**Transaksi gudang (Petugas Gudang):**
```
Pilih barang → jenis: masuk / keluar / reject → qty, tanggal, sumber/tujuan, catatan
  → Submit (dalam DB transaction + lockForUpdate pada baris barang):
      masuk  → current_stock_qty + qty
      keluar / reject → current_stock_qty − qty (DITOLAK jika stok tidak cukup)
  → Simpan baris wispo_transactions
  → Jika stok baru ≤ safety stock dan sebelumnya > safety stock → email "Stok Menipis"
```

**Permintaan & Peminjaman (semua user):**
```
Pilih barang (foto, stok tampil) → jenis: permintaan / peminjaman
  → kategori pemakaian (rutin / shutdown_water_cleaning / turn_around / crash_program)
  → qty, tujuan, [peminjaman: tanggal rencana kembali] → Submit
  → status menunggu_persetujuan → email ke semua Petugas Gudang

Petugas Gudang review (barang diserahkan saat disetujui):
  → Tolak  → status ditolak + alasan → email ke pemohon
  → Setujui (DB transaction, cek stok cukup):
      permintaan → status disetujui (final), transaksi "keluar" otomatis, stok −qty
      peminjaman → status dipinjam, transaksi "pinjam" otomatis, stok −qty
  → email ke pemohon

Pengembalian (khusus peminjaman):
  → Petugas Gudang klik "Konfirmasi Kembali" (+ catatan kondisi)
  → status dikembalikan, actual_return_at = now(), transaksi "kembali", stok +qty
```

**Stepper status (ditampilkan ke pemohon):**
- Permintaan: *Diajukan → Disetujui* (merah jika Ditolak)
- Peminjaman: *Diajukan → Dipinjam → Dikembalikan* (merah jika Ditolak / Terlambat)

### 4.4 Modul C — Booking Ruangan
```
User buka "Booking Ruangan" → pilih ruang kolaborasi → lihat kalender
  → pilih tanggal + jam mulai–selesai → isi tujuan & jumlah peserta → Submit
  → Validasi (DB transaction):
      - jam selesai > jam mulai, jumlah peserta ≤ kapasitas
      - TIDAK bentrok dengan booking lain berstatus menunggu_persetujuan/disetujui
        di ruang yang sama (dijaga juga oleh exclusion constraint PostgreSQL)
  → requires_approval = false → status disetujui → email konfirmasi
  → requires_approval = true  → status menunggu_persetujuan → email ke PIC area & Admin
       → PIC area/Admin: Setujui → disetujui / Tolak (+alasan) → ditolak → email ke pemesan
  → Pemesan bisa membatalkan booking miliknya sebelum jam mulai → dibatalkan (slot terbuka lagi)
  → Setelah jam selesai lewat, booking disetujui ditampilkan sebagai "Selesai" (dihitung, tidak disimpan)
```

### 4.5 Job Terjadwal (Laravel Scheduler)

| Jadwal | Job |
|---|---|
| Harian 07:00 | Temuan `belum_selesai` dengan deadline < hari ini → `terlambat` + email eskalasi ke Management & Koordinator 5R |
| Harian 07:00 | Temuan dengan deadline = besok → reminder ke PIC |
| Harian 07:00 | Peminjaman `dipinjam` dengan rencana kembali < hari ini → `terlambat` + email ke peminjam & Petugas Gudang |
| Harian 07:00 | Peminjaman dengan rencana kembali = besok → reminder ke peminjam |
| Harian 07:00 | Booking `disetujui` untuk besok → reminder ke pemesan |
| Harian 07:00 | Booking `menunggu_persetujuan` yang jam mulainya sudah lewat → `kedaluwarsa` |

---

## 5. Skema Database (PostgreSQL)

> Nama tabel & kolom mengikuti §4.2 PRD asli sebisa mungkin, agar fase berikutnya (audit, booking eksternal, stock opname) tinggal **menambah** tabel/kolom. Semua tabel: `id UUID PK` + `created_at/updated_at`.

### 5.1 Fondasi

**`users`**
| Kolom | Tipe | Keterangan |
|---|---|---|
| name | VARCHAR | |
| email | VARCHAR UNIQUE | dipakai untuk login & notifikasi |
| password | VARCHAR | hash (bawaan Laravel) |
| role | ENUM | `staff`, `koordinator_5r`, `management`, `admin` |
| is_petugas_gudang | BOOLEAN | default false |
| unit_kerja | VARCHAR NULL | |
| is_active | BOOLEAN | default true; nonaktif = tidak bisa login |

**`areas`**
| Kolom | Tipe | Keterangan |
|---|---|---|
| name | VARCHAR | data awal dari §4.4 PRD asli |
| zone_category | ENUM | `operasional`, `kolaborasi`, `publik` |
| default_pic_id | UUID FK → users NULL | PIC default temuan & penyetuju booking |
| capacity | INT NULL | hanya area kolaborasi |
| facilities | VARCHAR NULL | mis. "Proyektor, TV, Mic" |
| requires_approval | BOOLEAN | default false; hanya relevan untuk booking |
| is_active | BOOLEAN | |

**Bawaan Laravel:** `notifications`, `jobs`, `failed_jobs`, `sessions`, `cache`.

### 5.2 Modul A — Temuan

**`findings`**
| Kolom | Tipe | Keterangan |
|---|---|---|
| source | ENUM | `quick_report` (fase berikut: `audit`) |
| area_id | UUID FK → areas | |
| reported_by | UUID FK → users | |
| pic_id | UUID FK → users NULL | |
| description | TEXT | |
| priority | ENUM | `tinggi`, `sedang`, `rendah` |
| deadline | DATE NULL | diisi saat penugasan |
| status | ENUM | `belum_ditugaskan`, `belum_selesai`, `menunggu_verifikasi`, `selesai`, `terlambat`, `ditolak` |
| resolution_note | TEXT NULL | catatan PIC |
| revision_note | TEXT NULL | catatan revisi |
| rejection_reason | TEXT NULL | |
| verified_by | UUID FK → users NULL | |
| assigned_at | TIMESTAMP NULL | basis waktu respons PIC |
| resolved_at | TIMESTAMP NULL | |

**`finding_photos`**
| Kolom | Tipe | Keterangan |
|---|---|---|
| finding_id | UUID FK → findings | |
| type | ENUM | `before`, `after` |
| photo_path | VARCHAR | |
| uploaded_by | UUID FK → users | |

### 5.3 Modul B — WISPO

**`wispo_items`** (Master Barang)
| Kolom | Tipe | Keterangan |
|---|---|---|
| item_code | VARCHAR UNIQUE | otomatis `SPO-0001`, `SPO-0002`, … |
| name | VARCHAR | |
| category | VARCHAR | Tools, Proyektor, HT, PH Meter, Sparepart, … |
| unit | VARCHAR | pcs / unit / box / set / … |
| photo_path | VARCHAR NULL | |
| current_stock_qty | INT | CHECK ≥ 0; hanya diubah lewat service transaksi |
| safety_stock_qty | INT | default 0 |
| storage_location | VARCHAR NULL | mis. "Rak A-3" |
| brand_model | VARCHAR NULL | |
| serial_number | VARCHAR NULL | |
| notes | TEXT NULL | |
| is_active | BOOLEAN | barang nonaktif tidak dihapus |
| added_by | UUID FK → users | |

**`wispo_transactions`** (riwayat — tidak pernah di-edit/hapus)
| Kolom | Tipe | Keterangan |
|---|---|---|
| item_id | UUID FK → wispo_items | |
| transaction_type | ENUM | `masuk`, `keluar`, `reject`, `pinjam`, `kembali` (fase berikut: `penyesuaian_opname`) |
| qty | INT | CHECK > 0 |
| stock_after | INT | stok setelah transaksi (memudahkan audit jejak) |
| transaction_date | DATE | |
| source_or_destination | VARCHAR NULL | |
| request_id | UUID FK → wispo_requests NULL | terisi jika berasal dari permintaan/peminjaman |
| notes | TEXT NULL | |
| recorded_by | UUID FK → users | Petugas Gudang/Admin |

**`wispo_requests`** (Permintaan & Peminjaman — internal)
| Kolom | Tipe | Keterangan |
|---|---|---|
| item_id | UUID FK → wispo_items | |
| request_type | ENUM | `permintaan`, `peminjaman` |
| usage_category | ENUM | `rutin`, `shutdown_water_cleaning`, `turn_around`, `crash_program` |
| qty | INT | CHECK > 0 |
| requester_type | ENUM | `internal` (fase berikut: `external`) |
| requester_user_id | UUID FK → users | |
| purpose | TEXT | |
| expected_return_at | DATE NULL | wajib jika peminjaman |
| actual_return_at | TIMESTAMP NULL | |
| status | ENUM | `menunggu_persetujuan`, `disetujui`, `dipinjam`, `dikembalikan`, `terlambat`, `ditolak` |
| approved_by | UUID FK → users NULL | |
| decided_at | TIMESTAMP NULL | |
| rejection_reason | TEXT NULL | |
| return_notes | TEXT NULL | kondisi barang saat kembali |

> Kolom `external_*` dan `reference_code` dari PRD asli **belum dibuat** — ditambahkan lewat migration baru saat landing page eksternal dikerjakan.

### 5.4 Modul C — Booking

**`room_bookings`**
| Kolom | Tipe | Keterangan |
|---|---|---|
| area_id | UUID FK → areas | hanya area `kolaborasi` |
| booking_mode | ENUM | `offline` (fase berikut: `hybrid`, `online_only`) |
| requester_type | ENUM | `internal` |
| requester_user_id | UUID FK → users | |
| purpose | TEXT | |
| participant_count | INT | |
| start_time | TIMESTAMP | |
| end_time | TIMESTAMP | CHECK end_time > start_time |
| approval_status | ENUM | `menunggu_persetujuan`, `disetujui`, `ditolak`, `dibatalkan`, `kedaluwarsa` |
| approved_by | UUID FK → users NULL | |
| rejection_reason | TEXT NULL | |
| zoom_link | VARCHAR NULL | kosong di MVP (dipakai N3 / fase berikut) |

**Anti-bentrok (PostgreSQL):**
```sql
CREATE EXTENSION IF NOT EXISTS btree_gist;
ALTER TABLE room_bookings ADD CONSTRAINT no_overlap
  EXCLUDE USING gist (
    area_id WITH =,
    tsrange(start_time, end_time) WITH &&
  ) WHERE (approval_status IN ('menunggu_persetujuan', 'disetujui'));
```
Validasi tetap dilakukan di aplikasi agar pesan error ramah; constraint ini pengaman terakhir untuk race condition.

### 5.5 Relasi
```
users ─< findings (reported_by, pic_id, verified_by) >─ areas
findings ─< finding_photos
wispo_items ─< wispo_transactions >─ users (recorded_by)
wispo_items ─< wispo_requests >─ users (requester, approver)
wispo_requests ─< wispo_transactions (request_id)
areas (kolaborasi) ─< room_bookings >─ users
areas >─ users (default_pic_id)
```

### 5.6 Index penting
`findings(status, deadline)`, `findings(pic_id, status)`, `wispo_items(category)`, `wispo_items(name)`, `wispo_transactions(item_id, created_at)`, `wispo_requests(status)`, `room_bookings(area_id, start_time)`.

---

## 6. Halaman (Navigasi)

| Menu | Siapa | Isi |
|---|---|---|
| **Beranda** | semua | Kartu "Perlu Perhatian Saya": temuan saya (warna hijau/kuning/merah sesuai sisa waktu), permintaan & peminjaman saya, booking saya; antrean persetujuan untuk Koordinator/Petugas Gudang/PIC area |
| **Lapor Cepat** | semua | Form foto + area + deskripsi |
| **Temuan** | Koordinator, Management, Admin (semua); lainnya (milik sendiri) | Daftar + filter, detail, penugasan, verifikasi, export |
| **Stok Barang** | semua | Daftar barang (foto, kode, stok, status) + cari/filter + tombol "Ajukan" |
| **Permintaan Saya** | semua | Riwayat permintaan/peminjaman + stepper status |
| **Gudang** | Petugas Gudang, Admin | Ringkasan WISPO (B7), master barang, catat transaksi, antrean persetujuan, pengembalian, export |
| **Booking Ruangan** | semua | Kalender per ruang, form booking, Booking Saya |
| **Admin** | Admin | User, Area (panel Filament) |

**Prinsip UI:** mobile-first; kamera langsung (`<input type="file" accept="image/*" capture="environment">`); foto dikompres di client (maks ~1 MB); stepper status konsisten di WISPO & booking (hijau = lewat, biru = aktif, abu = belum, merah = ditolak/terlambat).

---

## 7. Tech Stack

| Komponen | Pilihan | Catatan |
|---|---|---|
| Backend | Laravel 11/12 (PHP 8.3) | Sesuai PRD asli |
| Database | PostgreSQL 16 | Butuh extension `btree_gist` untuk anti-bentrok booking |
| Frontend | Livewire starter kit resmi Laravel (Livewire + Volt + Flux UI + Tailwind) | Satu bahasa (PHP), tanpa SPA terpisah |
| Panel Admin & Gudang | Filament (versi stabil terbaru) | Master barang, user, area, antrean persetujuan |
| Kalender booking | FullCalendar (CDN) | Tampilan harian/mingguan |
| Auth | Email + password dari starter kit (registrasi publik dimatikan) | Google OAuth opsional di fase berikut |
| Export | `maatwebsite/excel` | |
| Email | Laravel Mail via SMTP + Queue (driver `database`) | |
| Scheduler | `schedule:run` via cron di container | |
| Storage foto | Disk `public` (`storage/app/public`) | |
| Deploy | **Belum diputuskan:** server milik departemen (jika spesifikasi memadai) atau hosting terkelola (Laravel Cloud/Railway) | Diputuskan paling lambat Hari 5 setelah info server dari penanggung jawab IT departemen |
| Backup | `pg_dump` harian + folder foto, disalin ke lokasi kedua, simpan 14 hari | Wajib sebelum go-live |

**Aturan kode penting:**
- Semua perubahan stok lewat **satu service** (`StockService`) dengan `DB::transaction()` + `lockForUpdate()`; tidak ada kode lain yang mengubah `current_stock_qty`.
- Feature test wajib untuk: perubahan stok (termasuk stok tidak cukup), bentrok booking, transisi status temuan, hak akses per role.

---

## 8. Rencana Harian (15 Hari Kerja)

> Setiap hari diakhiri **commit + push** dan uji di browser HP. Kerjakan dengan Claude Code **per fitur**: plan → implementasi → tes → commit.

### Minggu 1 — Fondasi + Modul Temuan

| Hari | Target | Output yang bisa dicek |
|---|---|---|
| 1 | Kunci scope: dokumen ini disetujui pemberi tugas. Tanyakan info server ke penanggung jawab IT departemen, siapkan akun SMTP. Minta data: daftar user, area, master barang existing | Persetujuan tertulis + data awal |
| 2 | Init repo Git, Laravel (Livewire starter kit) + Filament + PostgreSQL, `CLAUDE.md` ringkasan dokumen ini | App jalan di `http://localhost:8000` |
| 3 | Login email + password (matikan registrasi publik) + role/flag + Policy dasar + layout & navigasi | Login dengan akun contoh dari tiap role |
| 4 | Semua migration Bagian 5 + model + relasi + seeder (area §4.4, user contoh, barang contoh); Filament: User & Area | `migrate --seed` sukses; Admin kelola user & area |
| 5 | Modul A: Lapor Cepat + penugasan PIC + tolak + daftar temuan & filter | Staff lapor → Koordinator tunjuk PIC |

### Minggu 2 — Selesaikan Temuan + WISPO

| Hari | Target | Output yang bisa dicek |
|---|---|---|
| 6 | Modul A: tindak lanjut PIC + verifikasi/revisi + email notifikasi temuan + job terlambat/reminder + feature test | Satu temuan sampai Selesai; job mengubah status |
| 7 | Modul B: Master Barang (kode otomatis, foto, safety stock) + halaman Stok Barang (cari/filter, Aman/Menipis) | Staff lihat stok; Petugas Gudang tambah barang |
| 8 | Modul B: `StockService` + catat transaksi masuk/keluar/reject + riwayat + notifikasi stok menipis + feature test | Stok berubah benar; stok minus ditolak |
| 9 | Modul B: permintaan & peminjaman + persetujuan/tolak + stepper + Permintaan Saya | Permintaan disetujui → stok berkurang |
| 10 | Modul B: pengembalian + job terlambat/reminder peminjaman + Ringkasan WISPO (B7) + export transaksi | Peminjaman sampai Dikembalikan |

### Minggu 3 — Booking, Beranda, Go-Live

| Hari | Target | Output yang bisa dicek |
|---|---|---|
| 11 | Modul C: kalender ketersediaan + form booking + anti-bentrok (validasi + exclusion constraint) + feature test | Booking bentrok ditolak |
| 12 | Modul C: persetujuan PIC area/Admin + Booking Saya + batal + stepper + reminder H-1 + job kedaluwarsa | Booking butuh approval berjalan penuh |
| 13 | Beranda "Perlu Perhatian Saya" + export temuan. **Code freeze fitur.** Deploy final: queue worker, scheduler, backup harian, HTTPS. Input data asli | App jalan di server dengan data asli |
| 14 | UAT dengan 5–7 user asli (Admin, Koordinator 5R, Petugas Gudang, PIC area, 2 staff, Management) menjalankan skenario DoD Bagian 1. Catat bug | Daftar bug + prioritas |
| 15 | Perbaiki bug kritis, uji restore backup, panduan pengguna 1–2 halaman per modul, sosialisasi | **GO-LIVE** |

**Buffer:** akhir pekan minggu 2 & 3 hanya untuk mengejar keterlambatan — bukan untuk menambah fitur. N1–N4 hanya dikerjakan jika Hari 12 selesai lebih cepat.

### Aturan jika terlambat (urutan yang dikorbankan)
1. N1–N4 dibatalkan
2. Ringkasan WISPO (B7) → cukup daftar stok menipis + antrean persetujuan
3. Export Excel → export CSV sederhana
4. Kalender booking → daftar slot per hari (tanpa FullCalendar)
5. Persetujuan booking → semua ruang langsung disetujui (anti-bentrok tetap wajib)
6. **Tidak boleh dikorbankan:** login & hak akses, alur temuan lengkap, transaksi stok yang konsisten, anti-bentrok booking, backup

---

## 9. Risiko & Mitigasi

| Risiko | Mitigasi |
|---|---|
| Server departemen tidak memadai / tidak ada yang mengurus | Putuskan paling lambat Hari 5; jika ragu, pakai hosting terkelola (kode sama, tanpa perubahan) |
| Data master barang lama berantakan / banyak | Minta data di Hari 1; jika >100 barang, kerjakan N4 (import Excel) lebih awal sebagai ganti fitur lain |
| Stok di sistem tidak cocok dengan fisik saat go-live | Lakukan hitung fisik sekali sebelum Hari 13, masukkan sebagai stok awal |
| Scope bertambah (mis. "tambah booking eksternal", "tambah Zoom") | Masuk Backlog, dibahas setelah go-live |
| Email masuk spam / SMTP diblokir | Uji SMTP di Hari 1; pakai akun Google Workspace organisasi |
| Foto dari HP terlalu besar | Kompres di client + batas upload Nginx/PHP 5 MB |
| Tidak ada yang mengurus server setelah live | Tentukan penanggung jawab backup & maintenance sebelum Hari 13 |

---

## 10. Backlog Fase Berikutnya (urutan usulan)

1. Audit 5R terjadwal + checklist + skor + dashboard tren (inti PRD asli — `source = audit` sudah disiapkan di `findings`)
2. Landing page eksternal: booking ruangan & permintaan barang oleh pihak luar + Cek Status (Kode Referensi + No. WA)
3. Zoom API untuk booking Hybrid/Online
4. WISPO: stock opname, dashboard lengkap, prediksi kehabisan stok
5. Gamifikasi: poin, badge, leaderboard
6. Lost & Found, Inventaris Aset + Scan QR
7. WhatsApp, Dashboard TV, Chatbot RAG, Peta Isometrik

---

## 11. Data yang Harus Disiapkan Sebelum Hari 4

| Data | Dari siapa | Format |
|---|---|---|
| Daftar user (nama, email, unit kerja, role) | Admin / HR | Excel |
| Daftar area/zona + PIC default + kapasitas ruang kolaborasi | Tim 5R | Excel (bisa mulai dari §4.4 PRD asli) |
| Ruang mana yang butuh persetujuan booking | Pemilik ruangan | Daftar |
| Master barang WISPO (nama, kategori, satuan, stok, safety stock, lokasi) | Petugas Gudang | Excel / export AppSheet |
| Siapa saja Petugas Gudang & Koordinator 5R | Pemberi tugas | Daftar |

---

## 12. Pertanyaan yang Harus Dijawab di Hari 1

1. Apakah pemberi tugas setuju **audit 5R terjadwal** ditunda ke fase berikutnya?
2. Apakah pemberi tugas setuju **booking & permintaan barang oleh pihak eksternal** ditunda (MVP internal saja)?
3. Server mana yang dipakai, dan siapa yang memberi akses?
4. Siapa Admin harian, Koordinator 5R, dan Petugas Gudang?
5. Domain email organisasi apa yang diizinkan login?
6. Apakah ada staff tanpa akun Google organisasi yang tetap harus bisa login?
