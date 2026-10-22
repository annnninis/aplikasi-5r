# Panduan Mulai — Membangun Aplikasi 5R dengan Claude Code

Panduan ini dimulai dari nol. Ikuti berurutan. Kotak `[ ]` bisa kamu centang sendiri.

**Kondisi laptopmu saat dicek (22 Okt 2026):**

| Tool | Status |
|---|---|
| Git 2.46 | ✅ sudah ada |
| Laragon + PHP 8.4 + Composer 2.8 | ✅ sudah ada |
| Node.js 24 + npm | ✅ sudah ada |
| Ekstensi PHP `pdo_pgsql`, `pgsql`, `zip` | ❌ belum aktif |
| PostgreSQL | ❌ belum terpasang |
| Docker | ❌ belum terpasang (tidak wajib untuk development, lihat Tahap 7) |
| RAM 15,7 GB, disk C kosong 46 GB | ✅ cukup |

---

## Tahap 0 — Urusan Non-Teknis (Hari 1, jangan dilewati)

Ini yang paling sering membuat proyek molor, jadi kerjakan **sebelum** coding.

- [ ] **Kirim PRD MVP ke pemberi tugas** dan minta persetujuan tertulis (cukup balasan chat/email "setuju"). Tanyakan khususnya:
  - Apakah audit 5R terjadwal boleh ditunda ke fase berikutnya?
  - Apakah fitur untuk pihak eksternal boleh ditunda?
- [ ] **Minta data awal** (lihat Bagian 11 PRD MVP): daftar user, daftar area + PIC, master barang gudang, siapa Petugas Gudang & Koordinator 5R.
- [ ] **Tanyakan ke IT kantor soal server** — tiga hal:
  1. Server Linux mana yang dipakai, dan siapa yang bisa memberi akses SSH?
  2. Bisakah server diberi **nama domain** (mis. `5r.namakantor.co.id`) yang mengarah ke IP server di jaringan kantor?
  3. Bisakah server itu memakai **HTTPS**?

> ⚠️ **Kenapa butuh domain & HTTPS?** Login Google **tidak mengizinkan** alamat IP seperti `http://192.168.1.10`. Google hanya menerima `localhost` (untuk di laptop) atau domain sungguhan dengan HTTPS. Kalau IT tidak bisa menyediakan domain, alternatifnya Cloudflare Tunnel dengan domain milik kantor. Pastikan ini terjawab sebelum Hari 3.

- [ ] **Akun email pengirim notifikasi (SMTP).** Minta IT membuatkan akun khusus, mis. `notifikasi5r@namakantor.co.id`, lalu tanyakan cara memakainya untuk SMTP (biasanya lewat *App Password* Google — butuh verifikasi 2 langkah aktif di akun itu).
- [ ] **Buat akun GitHub** (gratis) di <https://github.com> untuk menyimpan cadangan kode.

---

## Tahap 1 — Siapkan Laptop (±1 jam)

### 1.1 Aktifkan ekstensi PHP di Laragon
1. Buka **Laragon**.
2. Klik kanan di jendela Laragon → **PHP** → **Extensions**.
3. Centang: **`pdo_pgsql`**, **`pgsql`**, **`zip`**. (Laragon otomatis menyimpan setiap centang.)
4. Klik **Stop**, lalu **Start All**.
5. Cek di terminal (PowerShell):
   ```bash
   php -m
   ```
   Pastikan `pdo_pgsql`, `pgsql`, dan `zip` muncul di daftar.

### 1.2 Pasang PostgreSQL
1. Unduh installer PostgreSQL **versi 16 atau 17** untuk Windows dari <https://www.postgresql.org/download/windows/> (installer dari EDB).
2. Jalankan installer. Saat diminta:
   - Komponen: biarkan semua tercentang (PostgreSQL Server, pgAdmin, Command Line Tools). **Stack Builder** boleh di-uncheck.
   - **Password superuser `postgres`**: buat password, **catat di tempat aman** (jangan dikirim ke chat Claude).
   - Port: biarkan **5432**.
3. Setelah selesai, buka **pgAdmin** → masukkan password tadi → klik kanan **Databases** → **Create** → **Database** → nama: `aplikasi_5r` → **Save**.

### 1.3 (Opsional tapi disarankan) Pasang editor kode
- **VS Code** dari <https://code.visualstudio.com> — untuk melihat-lihat kode yang ditulis Claude. Claude tetap yang menulis kodenya.

### 1.4 Atur identitas Git (sekali saja)
```bash
git config --global user.name "Nama Kamu"
```
```bash
git config --global user.email "email-github-kamu@contoh.com"
```

---

## Tahap 2 — Buat Kredensial Login Google (±20 menit)

> Lebih baik dilakukan oleh atau bersama admin Google Workspace kantor.

1. Buka <https://console.cloud.google.com> dengan akun Google kantor.
2. Klik pemilih project di atas → **New Project** → nama: `Aplikasi 5R` → **Create**.
3. Menu **APIs & Services** → **OAuth consent screen**:
   - User type: **Internal** (hanya akun organisasi yang bisa login — inilah yang kita mau).
   - Isi nama aplikasi `Aplikasi 5R` dan email support → simpan.
4. Menu **APIs & Services** → **Credentials** → **Create Credentials** → **OAuth client ID**:
   - Application type: **Web application**
   - Name: `Aplikasi 5R`
   - **Authorized redirect URIs** → tambahkan:
     - `http://localhost:8000/auth/google/callback` (untuk di laptop)
     - nanti tambahkan juga `https://<domain-server>/auth/google/callback` setelah domain server siap
   - **Create**.
5. Muncul **Client ID** dan **Client Secret**. Simpan di tempat aman. Nanti **kamu sendiri** yang menempelkannya ke file `.env` — **jangan ditempel ke chat Claude**.

---

## Tahap 3 — Buat Folder Proyek & Pindahkan Sesi Claude

1. Buat folder **di luar OneDrive**, misalnya:
   ```
   C:\Users\anisa fitria\projects\aplikasi-5r
   ```
   > Jangan di `Documents` karena folder itu disinkronkan OneDrive — ribuan file `vendor/` dan `node_modules/` akan membuat laptop lambat dan bisa bentrok.
2. Di dalamnya buat folder `docs`, lalu salin **`PRD_MVP_5R_3_Minggu.md`** ke sana dan ubah namanya menjadi `PRD_MVP.md`.
   (Atau minta Claude: *"pindahkan sesi ini ke folder C:\Users\anisa fitria\projects\aplikasi-5r"* — Claude akan menyalinkan file-filenya.)
3. Di aplikasi Claude (tab **Code**), mulai sesi baru dan **pilih folder** `aplikasi-5r` sebagai folder proyek.

---

## Tahap 4 — Atur Cara Kerja Claude

### 4.1 Mode izin (permission mode)
Di kotak chat ada pilihan mode. Rekomendasi:
- **Awal proyek (Hari 1–3):** mode default (Claude minta izin sebelum mengubah file/menjalankan perintah). Kamu jadi paham apa saja yang dilakukan.
- **Setelah terbiasa:** mode **auto-accept edits** supaya Claude tidak berhenti untuk setiap perubahan file. Kamu tetap bisa melihat semua perubahan di panel **diff**.

### 4.2 Plan mode
Untuk setiap fitur, minta Claude **membuat rencana dulu** (aktifkan plan mode, atau tulis "buat rencananya dulu, jangan coding"). Baca rencananya 2–3 menit, koreksi kalau ada yang tidak sesuai, baru setujui.

### 4.3 Model
- Fitur dengan logika penting (stok, bentrok booking, hak akses, alur status temuan): model paling kuat (Opus).
- Pekerjaan rutin (tampilan, teks, CRUD sederhana, perbaikan kecil): model lebih ringan (Sonnet) supaya kuota Max lebih awet.

### 4.4 Aturan emas
1. **Satu sesi = satu fitur/hari.** Jangan satu sesi untuk seluruh aplikasi.
2. **Commit setiap fitur selesai** (minta Claude: "commit perubahan ini").
3. **Coba sendiri di browser** sebelum lanjut ke fitur berikutnya.
4. **Jangan pernah menempel password/API key di chat.** Isi sendiri ke `.env`.
5. Kalau Claude salah arah, langsung bilang — lebih cepat dikoreksi di awal.

---

## Tahap 5 — Prompt Sesi Pertama (Hari 2: Fondasi Proyek)

Salin prompt ini ke sesi pertama di folder `aplikasi-5r`:

```
Baca docs/PRD_MVP.md. Ini proyek aplikasi 5R yang harus live dalam 3 minggu.

Tugas hari ini (Hari 2 di rencana):
1. Buat proyek Laravel versi stabil terbaru di folder ini (PHP 8.4 dari Laragon,
   database PostgreSQL lokal: database aplikasi_5r, user postgres, port 5432).
2. Pasang Livewire, Tailwind, dan Filament versi stabil terbaru yang kompatibel.
3. Buat CLAUDE.md berisi: ringkasan scope MVP, tech stack, struktur folder,
   aturan kode penting dari PRD (StockService satu-satunya pengubah stok,
   semua hak akses lewat Policy, feature test wajib untuk stok/booking/temuan),
   dan perintah untuk menjalankan app & tes.
4. Siapkan .env.example lengkap (tanpa nilai rahasia). Jangan isi password
   di .env — beri tahu aku variabel apa saja yang harus kuisi sendiri.
5. Inisialisasi git, buat .gitignore yang benar, lalu commit.

Buat rencananya dulu sebelum mengerjakan.
```

Setelah Claude selesai:
- [ ] Buka file `.env`, isi sendiri `DB_PASSWORD` (password PostgreSQL dari Tahap 1.2).
- [ ] Minta Claude: *"jalankan migrasi dan nyalakan server, lalu buka di browser"*. Pastikan halaman Laravel muncul di `http://localhost:8000`.
- [ ] Buat repo **private** di GitHub bernama `aplikasi-5r`, lalu minta Claude: *"hubungkan ke remote GitHub <URL repo> dan push"*. (Saat pertama push, Windows akan membuka jendela login GitHub — login sendiri.)

---

## Tahap 6 — Siklus Harian (Hari 3–15)

### 6.1 Template prompt setiap hari
```
Baca CLAUDE.md dan docs/PRD_MVP.md.
Kerjakan Hari <N>: <tulis target hari itu dari tabel Bagian 8 PRD>.

Aturan:
- Buat rencana dulu, tunggu persetujuanku.
- Tulis feature test untuk logika penting, jalankan semua tes sampai lulus.
- Setelah selesai, buka halamannya di browser dan coba alurnya.
- Beri tahu aku apa yang harus kucoba sendiri, lalu commit setelah kusetujui.
```

### 6.2 Catatan khusus per hari

| Hari | Yang perlu kamu siapkan / perhatikan |
|---|---|
| 3 | Isi `GOOGLE_CLIENT_ID` & `GOOGLE_CLIENT_SECRET` di `.env` sendiri (dari Tahap 2). Uji login dengan akun kantor. Minta Claude menyiapkan deploy pertama jika server sudah siap |
| 4 | Siapkan file Excel data awal (area, user contoh). Bisa ditaruh di `docs/data/` agar dibaca Claude untuk seeder. **Jangan** taruh data pribadi sensitif di repo |
| 5–6 | Coba alur temuan dari HP: buka `http://<IP-laptop>:8000` dari HP di WiFi yang sama (minta Claude menjalankan server dengan `--host=0.0.0.0`). Catatan: login Google dari HP lewat IP tidak bisa — untuk uji di HP minta Claude membuat **login khusus development** yang hanya aktif saat `APP_ENV=local` |
| 7–10 | Siapkan data master barang asli. Uji: stok tidak bisa minus, permintaan mengurangi stok, pengembalian menambah stok |
| 10 | Isi pengaturan SMTP di `.env` sendiri. Uji email benar-benar masuk (cek folder spam) |
| 11–12 | Uji bentrok booking: coba pesan ruang yang sama di jam yang tumpang tindih — harus ditolak |
| 13 | Deploy final (lihat Tahap 7). Hitung fisik stok gudang untuk stok awal |
| 14 | UAT: siapkan daftar skenario, minta 5–7 user asli mencoba, catat semua masalah di satu dokumen |
| 15 | Perbaikan bug, uji **restore** backup, panduan pengguna, go-live |

### 6.3 Kalau ada masalah
- **Error di layar / tes gagal:** salin pesan error ke Claude, minta *"cari penyebabnya dan perbaiki"*.
- **Hasil tidak sesuai keinginan:** jelaskan yang diharapkan dengan contoh konkret ("seharusnya setelah PIC upload foto, status jadi Menunggu Verifikasi").
- **Ada yang rusak setelah perubahan:** minta *"kembalikan ke commit terakhir yang berjalan"*.
- **Sesi terasa lambat / mulai lupa konteks:** tutup, buka sesi baru — `CLAUDE.md` membuat Claude langsung paham proyeknya lagi.

---

## Tahap 7 — Persiapan Deploy ke Server Kantor

Development di laptop memakai **Laragon** (sudah ada, ringan). Server kantor memakai **Docker**. Claude yang menyiapkan semua file Docker-nya.

### 7.1 Yang dibutuhkan dari IT (sebelum Hari 3)
- [ ] Server Linux (Ubuntu Server LTS), minimal 4 core, 8 GB RAM, SSD
- [ ] Akses SSH untukmu
- [ ] Docker & Docker Compose terpasang di server (bisa minta Claude membuatkan perintah instalasinya untuk dijalankan IT)
- [ ] Domain internal + HTTPS (lihat Tahap 0)
- [ ] Lokasi kedua untuk backup (NAS/folder jaringan/cloud storage)

### 7.2 Prompt untuk menyiapkan deploy (Hari 3, diulang Hari 13)
```
Siapkan deploy ke server Linux kantor dengan Docker Compose:
app (PHP-FPM), nginx, postgres, queue worker, scheduler.
Buat Dockerfile, docker-compose.prod.yml, contoh .env produksi (tanpa rahasia),
script backup harian pg_dump + folder foto (simpan 14 hari),
dan docs/DEPLOY.md berisi langkah perintah demi perintah yang bisa
kujalankan di server via SSH.
```

### 7.3 (Opsional) Docker Desktop di laptop
Kalau ingin menguji file Docker di laptop sebelum ke server: pasang **Docker Desktop** dari <https://www.docker.com/products/docker-desktop/> (laptopmu sudah punya WSL2). Butuh ±4 GB RAM saat berjalan. Tidak wajib.

---

## Ringkasan Checklist

**Hari ini**
- [ ] Kirim PRD MVP ke pemberi tugas & minta persetujuan
- [ ] Minta data awal + tanyakan server, domain, HTTPS, akun SMTP ke IT
- [ ] Aktifkan `pdo_pgsql`, `pgsql`, `zip` di Laragon
- [ ] Pasang PostgreSQL + buat database `aplikasi_5r`
- [ ] Buat akun GitHub

**Besok (Hari 2)**
- [ ] Buat Google OAuth client
- [ ] Buat folder `C:\Users\anisa fitria\projects\aplikasi-5r` + `docs/PRD_MVP.md`
- [ ] Buka sesi Claude di folder itu, jalankan prompt Tahap 5
