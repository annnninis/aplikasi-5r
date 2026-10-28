# Panduan Deploy — Aplikasi 5R

Dua pilihan tempat aplikasi dijalankan. **Pilih salah satu** setelah ada jawaban dari pemberi tugas / penanggung jawab IT departemen.

| | **A. Hosting terkelola** (mis. Laravel Cloud) | **B. Server milik departemen** |
|---|---|---|
| Cocok jika | Tidak ada yang siap merawat server; ingin cepat & aman | Server Linux siap pakai, menyala 24 jam, ada yang merawat |
| Biaya | Bulanan (dibayar departemen) | Tanpa biaya bulanan (listrik & waktu IT) |
| HTTPS, backup, update keamanan | Diurus penyedia | Diurus sendiri |
| Akses dari luar kantor | Bisa (internet) | Hanya jaringan kantor, kecuali ditambah tunnel/VPN |
| Waktu setup | ± 1–2 jam | ± setengah sampai satu hari |

> **Rekomendasi:** jika sampai Hari 13 belum ada kepastian server, pakai **A** untuk go-live. Kode yang sama bisa dipindah ke server departemen kapan saja nanti.

---

## 0. Persiapan yang sama untuk A dan B

- [ ] Kode terbaru sudah di GitHub (`main`) dan semua tes lulus: `php artisan test`
- [ ] **Akun email pengirim (SMTP)** dari kantor: host, port, username, password, alamat pengirim. Tanpa ini notifikasi email tidak terkirim (aplikasi tetap jalan, notifikasi lonceng tetap ada).
- [ ] Data awal dari departemen: daftar user (nama, email, unit, peran, Petugas Gudang?), PIC & kapasitas tiap ruang kolaborasi, master barang + stok fisik terkini.
- [ ] Tentukan **siapa Admin harian** (akun Admin pertama dibuat untuk orang ini).

### Pengaturan `.env` produksi (berlaku untuk A dan B)

Nilai rahasia **diisi langsung di server / dashboard hosting**, jangan di-commit.

```dotenv
APP_NAME="Aplikasi 5R"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<alamat-aplikasi>
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id

DB_CONNECTION=pgsql
DB_HOST=...
DB_PORT=5432
DB_DATABASE=aplikasi_5r
DB_USERNAME=...
DB_PASSWORD=...

QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database
FILESYSTEM_DISK=public

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="notifikasi5r@namakantor.co.id"
MAIL_FROM_NAME="Aplikasi 5R"
```

> `APP_KEY` dibuat sekali dengan `php artisan key:generate` di server (atau otomatis oleh hosting). Jangan pakai `APP_KEY` dari laptop.

### Perintah setup pertama kali (setelah kode & `.env` siap)

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan key:generate          # hanya jika APP_KEY belum ada
php artisan migrate --force       # buat semua tabel
php artisan db:seed --force       # di produksi HANYA mengisi 15 area awal (tanpa akun contoh)
php artisan storage:link          # agar foto bisa ditampilkan
php artisan app:create-admin      # buat akun Admin pertama (nama, email, kata sandi)
php artisan optimize              # cache config/route/view
```

Setelah itu Admin login, lalu lewat **Panel Kerja**: lengkapi area (PIC, kapasitas), tambah pengguna, dan input master barang beserta stok awal.

### Proses yang harus selalu berjalan

| Proses | Perintah | Fungsi |
|---|---|---|
| Web | (Nginx/PHP-FPM atau hosting) | Melayani halaman |
| Queue worker | `php artisan queue:work --tries=3` | Mengirim email notifikasi |
| Scheduler | `php artisan schedule:run` **setiap menit** | Pengingat H-1 (temuan, peminjaman, booking) jam 07:00 |

> Status **Terlambat** dan **Kedaluwarsa** juga diperbarui otomatis saat ada yang membuka website (maks. sekali per 5 menit), jadi tetap akurat meski scheduler belum aktif. Tanpa scheduler, yang tidak jalan hanya **pengingat H-1**.

### Setiap kali ada update kode

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize
php artisan queue:restart
```

---

## A. Hosting terkelola (mis. Laravel Cloud)

Langkah umum (nama menu bisa sedikit berbeda — ikuti dokumentasi terbaru penyedia):

1. Pemberi tugas/departemen membuat akun & menyiapkan pembayaran.
2. **Sambungkan repo GitHub** `annnninis/aplikasi-5r` (branch `main`).
3. Buat **database PostgreSQL** di hosting dan hubungkan ke aplikasi (variabel `DB_*` biasanya terisi otomatis).
   - Aplikasi butuh ekstensi PostgreSQL **`btree_gist`** (untuk anti-bentrok booking). Migration membuatnya otomatis; pastikan database mengizinkan `CREATE EXTENSION btree_gist`.
4. Isi **environment variables** sesuai bagian 0 (`MAIL_*`, `APP_TIMEZONE`, dst.).
5. Atur **build command**: `npm ci && npm run build` dan **deploy command**: `php artisan migrate --force && php artisan optimize`.
6. Aktifkan **queue worker** dan **scheduler** di pengaturan hosting.
7. Siapkan **penyimpanan file** permanen untuk foto (disk `public`). Jika hosting tidak punya disk permanen, gunakan object storage (S3-compatible) — beri tahu developer agar `FILESYSTEM_DISK` disesuaikan.
8. Deploy, lalu jalankan sekali lewat konsol hosting: `php artisan db:seed --force` dan `php artisan app:create-admin`.
9. Pastikan **backup database otomatis** aktif di hosting.

---

## B. Server milik departemen (Ubuntu Server 22.04/24.04)

> Jika server ternyata **Windows Server** atau **NAS**, hentikan dan diskusikan dulu — langkah di bawah untuk Ubuntu. Opsi termudah biasanya kembali ke pilihan A.

**Syarat minimum:** 2 core CPU, 4 GB RAM (disarankan 8 GB), SSD, menyala 24 jam (idealnya dengan UPS), terhubung ke jaringan kantor. **Jangan pasang di server yang menyimpan data penting tanpa backup.**

Perintah dijalankan oleh penanggung jawab IT lewat SSH (`sudo` dibutuhkan).

### B1. Pasang paket
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx postgresql postgresql-contrib git unzip supervisor \
  php8.3-fpm php8.3-cli php8.3-pgsql php8.3-mbstring php8.3-xml php8.3-curl \
  php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash - && sudo apt install -y nodejs
```
> Ubuntu 22.04 belum punya PHP 8.3 bawaan; tambahkan dulu `sudo add-apt-repository ppa:ondrej/php`.

### B2. Database
```bash
sudo -u postgres psql -c "CREATE USER app5r WITH PASSWORD 'GANTI_PASSWORD_KUAT';"
sudo -u postgres psql -c "CREATE DATABASE aplikasi_5r OWNER app5r;"
sudo -u postgres psql -d aplikasi_5r -c "CREATE EXTENSION IF NOT EXISTS btree_gist;"
```

### B3. Kode aplikasi
```bash
sudo mkdir -p /var/www && cd /var/www
sudo git clone https://github.com/annnninis/aplikasi-5r.git
sudo chown -R $USER:www-data aplikasi-5r && cd aplikasi-5r
cp .env.example .env && nano .env      # isi sesuai bagian 0 (DB_USERNAME=app5r, dst.)
# lalu jalankan "Perintah setup pertama kali" di bagian 0
sudo chown -R www-data:www-data storage bootstrap/cache
```
> Repo private: buat **deploy key** atau personal access token GitHub untuk clone.

### B4. Nginx
`/etc/nginx/sites-available/aplikasi-5r`:
```nginx
server {
    listen 80;
    server_name 5r.namakantor.local;      # atau IP server
    root /var/www/aplikasi-5r/public;
    index index.php;
    client_max_body_size 10M;             # upload foto

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
    location ~ /\.(?!well-known) { deny all; }
}
```
```bash
sudo ln -s /etc/nginx/sites-available/aplikasi-5r /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```
> Login & upload foto lebih aman lewat **HTTPS**. Di jaringan internal, minta IT memasang sertifikat (mis. sertifikat internal kantor) atau gunakan Cloudflare Tunnel jika butuh akses dari luar.

### B5. Queue worker (Supervisor)
`/etc/supervisor/conf.d/aplikasi-5r-worker.conf`:
```ini
[program:aplikasi-5r-worker]
command=php /var/www/aplikasi-5r/artisan queue:work --tries=3 --max-time=3600
user=www-data
autostart=true
autorestart=true
stdout_logfile=/var/www/aplikasi-5r/storage/logs/worker.log
```
```bash
sudo supervisorctl reread && sudo supervisorctl update
```

### B6. Scheduler (cron)
```bash
sudo crontab -u www-data -e
# tambahkan baris:
* * * * * cd /var/www/aplikasi-5r && php artisan schedule:run >> /dev/null 2>&1
```

### B7. Backup harian (WAJIB)
`/usr/local/bin/backup-aplikasi-5r.sh`:
```bash
#!/bin/bash
set -e
DIR=/var/backups/aplikasi-5r; mkdir -p $DIR
STAMP=$(date +%Y%m%d-%H%M)
sudo -u postgres pg_dump -Fc aplikasi_5r > $DIR/db-$STAMP.dump
tar -czf $DIR/foto-$STAMP.tar.gz -C /var/www/aplikasi-5r/storage/app public
find $DIR -type f -mtime +14 -delete      # simpan 14 hari
# SALIN ke lokasi kedua (NAS/folder jaringan), contoh:
# rsync -a $DIR/ /mnt/nas/backup-aplikasi-5r/
```
```bash
sudo chmod +x /usr/local/bin/backup-aplikasi-5r.sh
sudo crontab -e        # tambahkan: 30 1 * * * /usr/local/bin/backup-aplikasi-5r.sh
```
**Uji restore minimal sekali sebelum go-live:**
```bash
sudo -u postgres createdb aplikasi_5r_restore_test
sudo -u postgres pg_restore -d aplikasi_5r_restore_test /var/backups/aplikasi-5r/db-<STAMP>.dump
```

---

## Checklist go-live (Hari 13–15)

- [ ] Server/hosting dipilih & disetujui
- [ ] `.env` produksi terisi, `APP_DEBUG=false`
- [ ] Migrasi + area awal + Admin pertama dibuat
- [ ] Queue worker & scheduler berjalan (cek: buat temuan → email terkirim)
- [ ] Backup otomatis aktif & sudah diuji restore
- [ ] Data asli diinput: user, PIC & kapasitas ruang, master barang + stok fisik
- [ ] UAT dengan 5–7 user asli (Hari 14) — skenario di `docs/PRD_MVP.md` Bagian 1
- [ ] Panduan singkat untuk pengguna & serah terima ke Admin harian
