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
- Tes: `php artisan test` (Pest/PHPUnit, `tests/Feature`)
- Queue & cache: driver `database`; email: `MAIL_MAILER=log` di lokal
- Hosting: belum diputuskan (server departemen atau hosting terkelola). Jangan menambah Docker/infra deploy sebelum diputuskan.

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
- Role: `staff`, `koordinator_5r`, `management`, `admin` (`users.role`); flag `users.is_petugas_gudang`. PIC bukan role.
- **Stok hanya diubah lewat `StockService`** dalam `DB::transaction()` + `lockForUpdate()`. Tidak ada kode lain yang menulis `wispo_items.current_stock_qty`. Setiap perubahan stok mencatat baris `wispo_transactions`.
- **Anti-bentrok booking**: validasi di aplikasi + exclusion constraint PostgreSQL (`btree_gist`), lihat PRD 5.4.
- Transisi status (temuan, permintaan, booking) mengikuti diagram di PRD Bagian 4; taruh logikanya di satu tempat (service/model method), bukan tersebar di komponen UI.
- Email dikirim lewat queue (`ShouldQueue`).
- Foto disimpan di disk `public`; kompres di sisi client sebelum upload.
- **Feature test wajib** untuk: perubahan stok (termasuk stok tidak cukup), bentrok booking, transisi status temuan, hak akses per role.
- UI mobile-first: Lapor Cepat & tindak lanjut dipakai dari HP.

## Rahasia
Jangan pernah menulis password/API key ke file yang di-commit atau ke chat. `.env` tidak di-commit; `.env.example` berisi nama variabel tanpa nilai rahasia. `DB_PASSWORD` diisi sendiri oleh pemilik proyek.

## Cara kerja
- Rencana harian ada di `docs/PRD_MVP.md` Bagian 8. Kerjakan satu hari/fitur per sesi: rencana → implementasi → tes → cek di browser → commit.
- Panduan setup untuk pemilik proyek (non-developer): `docs/PANDUAN_MULAI.md`.
