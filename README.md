<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
</p>

<h1 align="center">SIMQOH — Sistem Informasi Manajemen Qomarul Hidayah</h1>

<p align="center">
  Aplikasi manajemen kepegawaian (GTK) dan Surat Keputusan (SK) untuk
  <strong>YPP Qomarul Hidayah</strong> — Gondang, Tugu, Trenggalek, Jawa Timur.
</p>

---

## Tentang

SIMQOH memusatkan data GTK seluruh satuan kerja (SD, SMP, SMK), menerbitkan SK
secara otomatis dengan penomoran aman, tanda tangan digital, dan verifikasi
publik melalui QR.

### Fitur utama

**Kepegawaian**
- CRUD GTK dengan normalisasi gelar otomatis (depan & belakang, bentuk baku EYD: `Drs. Ahmad Fauzi, S.Kom., M.Pd.`)
- NIGY otomatis & atomik (`2026SD1001`) — format dapat dikonfigurasi, direset per tahun per satker
- Impor massal Excel (pratinjau validasi per baris, all-or-nothing) + opsi membuat akun pengguna otomatis
- Ekspor daftar GTK / template impor
- Kelengkapan profil (persentase + daftar data kurang)
- Tugas tambahan, riwayat pendidikan, berkas kepegawaian
- Akun pengguna per GTK (username = NIGY, sandi sementara)

**Surat Keputusan**
- Jenis SK & batch (penerbitan massal), alur verifikasi → tanda tangan
- PDF SK (template Blade, kertas F4, kop & tembusan yayasan)
- Snapshot data pada saat terbit (immutable) + QR verifikasi publik
- Arsip SK lama (unggah pindaian oleh GTK, verifikasi admin)

**Akses & keamanan**
- 4 peran: Ketua Yayasan, Admin Yayasan, Admin Satuan Kerja, GTK
- Tenancy satuan kerja (admin satker hanya melihat GTK satkernya)
- 2FA (TOTP) opsional per pengguna — aktif hanya setelah setup selesai
- Impersonasi: admin yayasan → semua pengguna; admin satker → GTK satker sendiri
- Login Google (Socialite) — email harus cocok dengan akun terdaftar
- Audit log, notifikasi, laporan (PDF/Excel)

**Portal GTK**
- Beranda ringkasan, data pribadi (dengan sorotan data kosong), berkas, arsip SK lama
- Halaman Keamanan mandiri (2FA & ganti sandi)

## Teknologi

- Laravel **13** (PHP ^8.3)
- Inertia.js + Vue 3 + Tailwind CSS 4
- DomPDF, maatwebsite/excel, laravel/socialite, pragmarx/google2fa
- MySQL (produksi) / SQLite (pengujian)

## Instalasi

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

# isi kredensial Google OAuth di .env bila ingin login Google
# GOOGLE_CLIENT_ID=...
# GOOGLE_CLIENT_SECRET=...
# GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/login/google/callback

php artisan migrate --seed   # atau migrate:fresh --seed untuk data demo
npm run build                # atau npm run dev saat pengembangan
php artisan serve
```

### Akun demo

Semua akun demo memakai kata sandi `Qomarul123!` (wajib diganti pada masuk pertama):

| Username | Peran |
|---|---|
| `ketua` | Ketua Yayasan |
| `admin` | Admin Yayasan |
| `admin.sd1` | Admin Satuan Kerja (SD) |
| `2026SD1001` | GTK (Ahmad Fauzi) |

## Pengujian & kualitas

```bash
./vendor/bin/pest                # suite lengkap
./vendor/bin/phpstan analyse     # static analysis
./vendor/bin/pint                # code style
npm run build                    # build aset frontend
```

## Struktur penting

```
app/
├── Enums/                  # peran, status, kategori, jenjang
├── Models/
│   └── Concerns/           # Auditable, BelongsToTenant, FormatsDatesLocally
├── Policies/               # RBAC per model
├── Services/
│   ├── Nigy/               # pembangkit NIGY + allocator nomor atomik
│   ├── Numbering/          # NumberAllocator (NIGY & nomor SK)
│   ├── Decree/             # snapshot, render PDF
│   └── Employee/           # impor massal, kelengkapan profil
├── Support/                # IndonesianDate, TitleFormatter, PhotoProcessor
└── Http/Controllers/
    ├── Admin/              # manajemen (yayasan/satker)
    ├── Portal/             # portal mandiri GTK
    ├── Auth/               # login, 2FA, Socialite, impersonasi
    └── Security/           # keamanan akun mandiri
resources/js/
├── Layouts/                # AdminLayout (sidebar, notifikasi, banner impersonasi)
├── Pages/                  # halaman per modul
└── utils/ helpers/         # format tanggal Indonesia, gelar, dll.
```

## Lisensi

Dikembangkan untuk internal **YPP Qomarul Hidayah**. Dibangun di atas
[Laravel](https://laravel.com) yang dilisensikan di bawah
[MIT license](https://opensource.org/licenses/MIT).
