# Aplikasi 5R — Temuan, WISPO & Booking Ruangan

Aplikasi internal satu departemen (<100 user). Target go-live 3 minggu (15 hari kerja).
**Acuan scope satu-satunya: `docs/PRD_MVP.md`.** Jangan membangun fitur yang tidak ada di Bagian 2 PRD tersebut; catat usulan fitur baru di Bagian 10 (Backlog).

## Modul MVP
- **A. Temuan & Tindak Lanjut** — Lapor Cepat → Koordinator 5R tunjuk PIC → PIC upload foto "sesudah" → verifikasi.
- **B. WISPO inti** — master barang, transaksi masuk/keluar/reject, lihat stok, permintaan & peminjaman internal dengan persetujuan Petugas Gudang.
- **C. Booking Ruangan internal** — kalender, mode offline, anti-bentrok, persetujuan untuk ruang tertentu.

Di luar MVP (jangan dikerjakan): audit 5R terjadwal, gamifikasi, landing page / akses eksternal, Zoom API, stock opname, Lost & Found, inventaris + QR, chatbot, WhatsApp, Dashboard TV, peta isometrik.

## Stack
- Laravel 12, PHP 8.4 (Laragon di Windows), PostgreSQL 17 (database `aplikasi_5r`)
- Frontend user: Livewire starter kit resmi (Livewire + Volt + Flux UI + Tailwind 4, Vite)
- Panel admin & gudang: Filament (panel `admin` di `/admin`, `app/Providers/Filament/AdminPanelProvider.php`)
- Tes: `php artisan test` (PHPUnit, `tests/Feature`) memakai database PostgreSQL terpisah **`aplikasi_5r_test`** (lihat `phpunit.xml`; password diambil dari `.env`). Developer baru harus membuat database ini dulu di pgAdmin.
- Queue & cache: driver `database` di server (butuh `php artisan queue:work`). Di laptop `.env` memakai `QUEUE_CONNECTION=sync` supaya tidak perlu worker; email `MAIL_MAILER=log` ditulis ke `storage/logs/laravel.log`.
- Zona waktu: `Asia/Jakarta` (`APP_TIMEZONE`). Job harian `findings:daily-check` jam 07:00 (`routes/console.php`); di server butuh cron `php artisan schedule:run` tiap menit.
- Notifikasi temuan: `FindingService` memanggil `FindingNotifier` di setiap transisi status → `FindingNotification` (mail + database).
- Hosting: belum diputuskan (server departemen atau hosting terkelola). Panduan kedua opsi: `docs/DEPLOY.md`. Jangan menambah Docker/infra deploy sebelum diputuskan.
- Setup server: `php artisan app:create-admin` membuat Admin pertama; `db:seed` di produksi hanya mengisi area awal (tanpa akun/barang contoh).

## Perintah
```bash
php artisan serve         # server (http://localhost:8000)
npm run dev               # vite (hot reload CSS/JS), di terminal terpisah
php artisan queue:listen  # proses email/job, di terminal terpisah
# `composer run dev` memakai `pail` yang butuh ekstensi pcntl (tidak ada di Windows) — pakai perintah terpisah di atas
php artisan migrate       # jalankan migrasi
php artisan migrate:fresh --seed   # reset DB lokal + data contoh
php artisan test          # semua tes
npm run build             # build aset produksi
```

## Aturan kode
- **Bahasa UI: Bahasa Indonesia.** Nama tabel/kolom mengikuti `docs/PRD_MVP.md` Bagian 5 (sebagian bahasa Indonesia, mis. status `belum_selesai`). Kode (kelas, method, variabel) dalam bahasa Inggris.
- **Primary key UUID** untuk semua tabel domain (`HasUuids`).
- **Hak akses wajib di backend** lewat Policy/Gate — jangan hanya menyembunyikan tombol di UI.
- **Setiap Filament Resource wajib punya Policy** (`app/Policies/{Model}Policy.php`). Tanpa policy, Filament mengizinkan semua user yang bisa masuk Panel Kerja.
- Data penting (user, area, barang) tidak dihapus — pakai `is_active`. Policy `delete` mengembalikan `false`.
- Status & kategori memakai enum di `app/Enums` (implement `HasLabel` + `HasColor` Filament untuk badge). Nilai enum di migration ditulis literal, bukan dari kelas enum.
- Role: `staff`, `koordinator_5r`, `management`, `admin` (`users.role`); flag `users.is_petugas_gudang`. PIC bukan role.
- **Stok hanya diubah lewat `StockService`** dalam `DB::transaction()` + `lockForUpdate()`. Tidak ada kode lain yang menulis `wispo_items.current_stock_qty`. Setiap perubahan stok mencatat baris `wispo_transactions`.
- **Anti-bentrok booking**: validasi di aplikasi + exclusion constraint PostgreSQL (`btree_gist`), lihat PRD 5.4.
- Transisi status (temuan, permintaan, booking) mengikuti diagram di PRD Bagian 4; taruh logikanya di satu tempat (service/model method), bukan tersebar di komponen UI.
- Email dikirim lewat queue (`ShouldQueue`).
- Foto disimpan di disk `public`; kompres di sisi client sebelum upload.
- **Feature test wajib** untuk: perubahan stok (termasuk stok tidak cukup), bentrok booking, transisi status temuan, hak akses per role.
- UI mobile-first: Lapor Cepat & tindak lanjut dipakai dari HP.

## Auth & akses (sudah dibangun, Hari 3)
- Satu pintu login di `/login` (starter kit). `/admin/login` diarahkan ke `/login`; panel Filament tidak punya login sendiri.
- Registrasi publik dimatikan; akun dibuat Admin. User `is_active = false` tidak bisa login dan dikeluarkan di request berikutnya (`EnsureUserIsActive`, dipasang di grup `web` dan `authMiddleware` panel).
- Akses Panel Kerja `/admin`: `User::canAccessWorkPanel()` (Admin, Koordinator 5R, Management, atau Petugas Gudang). Menu "Panel Kerja" di sidebar hanya tampil untuk mereka.
- Role ada di `App\Enums\Role`; state factory: `admin()`, `koordinator()`, `management()`, `petugasGudang()`, `inactive()`.
- Akun contoh lokal (dari `DatabaseSeeder`, password lihat docblock seeder): `admin@5r.test`, `koordinator@5r.test`, `manajemen@5r.test`, `gudang@5r.test`, `staff@5r.test`, `nonaktif@5r.test`.
- Terjemahan: `lang/id/*.php` & `lang/id.json` dari paket laravel-lang (`php artisan lang:update` menimpa perubahan manual di file itu). Teks UI baru ditulis langsung dalam Bahasa Indonesia.

## Rahasia
Jangan pernah menulis password/API key ke file yang di-commit atau ke chat. `.env` tidak di-commit; `.env.example` berisi nama variabel tanpa nilai rahasia. `DB_PASSWORD` diisi sendiri oleh pemilik proyek.

## Cara kerja
- Rencana harian ada di `docs/PRD_MVP.md` Bagian 8. Kerjakan satu hari/fitur per sesi: rencana → implementasi → tes → cek di browser → commit.
- Panduan setup untuk pemilik proyek (non-developer): `docs/PANDUAN_MULAI.md`.
