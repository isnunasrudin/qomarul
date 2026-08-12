# Rencana Implementasi — SIMQOH
### Sistem Informasi Manajemen Qomarul Hidayah · Modul Kepegawaian & Surat Keputusan

| | |
|---|---|
| **Versi** | 1.2 |
| **Tanggal** | 12 Agustus 2026 |
| **Acuan** | [`prd.md`](./prd.md) v1.7 — 22 keputusan kunci (§12) |
| **Stack** | **Laravel 13.25** (PHP 8.3) · Inertia.js · Vue 3 · Tailwind CSS 4 · MySQL/MariaDB · Redis |
| **Total estimasi** | 15–19 minggu, 9 fase |

---

## 0. Prinsip Kerja

Aturan yang berlaku di seluruh fase — pelanggarannya menimbulkan biaya perbaikan yang jauh lebih mahal di kemudian hari.

1. **Kode dan basis data Bahasa Inggris; antarmuka dan PDF Bahasa Indonesia.** Tidak ada teks Indonesia yang di-*hardcode* di controller, model, atau komponen Vue — semuanya lewat `lang/id/`. (PRD K19, §6.1)
2. **Otorisasi ditegakkan di server.** Setiap rute yang mengakses data GTK atau SK wajib punya Policy dan *feature test* yang membuktikan peran lain ditolak. Filter di frontend bukan pengamanan.
3. **Migration tidak pernah disunting setelah dijalankan di server mana pun.** Perubahan skema selalu lewat migration baru.
4. **Setiap fase berakhir dengan demo yang bisa dijalankan.** Tidak ada fase yang "selesai" hanya karena kodenya ada tetapi belum pernah dipakai dari antarmuka.
5. **Uji dulu untuk logika berisiko**: alokasi nomor, transisi status SK, tenancy, dan perhitungan masa kerja ditulis tesnya sebelum implementasi.
6. **Berkas sensitif tidak pernah masuk repositori**: `.p12`, gambar tanda tangan, `.env`, dan berkas unggahan. Pastikan `.gitignore` menutupnya sejak commit pertama.
7. **SK bukan satu-satunya jenis dokumen.** Modul surat keluar menyusul di v2. Empat komponen dibangun generik sejak awal — `NumberAllocator`, `SignerInterface`, halaman verifikasi publik, dan `settings` kop surat. Jangan menyisipkan asumsi "dokumen = SK" ke dalam keempatnya.

### Struktur Direktori

```
app/
├── Enums/                    UserRole, DecreeStatus, DocumentCategory, ...
├── Models/                   Employee, Decree, WorkUnit, ...
├── Models/Scopes/            TenantScope
├── Models/Concerns/          BelongsToTenant, Auditable
├── Policies/
├── Http/
│   ├── Controllers/          Admin/, Portal/, Public/
│   ├── Requests/
│   ├── Resources/            (Inertia payload shaping)
│   └── Middleware/
├── Services/
│   ├── Numbering/            NumberAllocator
│   ├── Nigy/                 NigyGenerator
│   ├── Decree/               WorkflowService, SnapshotBuilder, PdfRenderer, NumberService
│   ├── Signing/              SignerInterface, SelfSignedPkcs12Signer, CertificateManager
│   └── Verification/
├── Jobs/
├── Support/                  ServicePeriod, IndonesianDate, RomanMonth
resources/
├── js/Pages/                 Admin/, Portal/, Auth/
├── js/Components/
├── js/Layouts/
└── views/decrees/            template Blade PDF
lang/id/                      seluruh teks antarmuka
database/migrations/
database/seeders/
tests/Feature/  tests/Unit/
```

### Urutan Migration (mengikuti ketergantungan FK)

```
01 work_units
02 positions
03 employment_statuses
04 employees                 → work_units, positions, employment_statuses
05 users_add_relations       → work_unit_id, employee_id, role, is_active (hindari FK melingkar)
06 educations                → employees
07 documents                 → employees
08 additional_duties
09 decree_types
10 decree_batches            → decree_types
11 decrees                   → decree_types, employees, work_units, decree_batches, self
12 employee_additional_duties→ employees, additional_duties, work_units, decrees
13 decree_workflow_logs      → decrees, users
14 certificates
15 decree_signatures         → decrees, certificates
16 number_counters
17 audit_logs
18 settings
```

---

## Fase F1 — Fondasi

> ### ✅ SELESAI — 12 Agustus 2026
> **Deviasi dari PRD v1.6:** memakai **Laravel 13.25** (rilis terbaru saat eksekusi, PHP ^8.3) — bukan Laravel 11. Tanpa perubahan arsitektur; semua dependensi plan kompatibel.
> **Bukti:** 35/35 tes hijau · PHPStan level 6: 0 error · Pint bersih · build Vite OK · `migrate:fresh --seed` bersih di MariaDB 11.8 · empat peran login terverifikasi via HTTP (termasuk alur 2FA TOTP untuk `foundation_head`).

**Estimasi 2 minggu · Prasyarat: tidak ada · Blocker: daftar satuan kerja riil (hanya untuk seeder)**

### Tujuan
Kerangka aplikasi berdiri, seluruh skema basis data ada, dan sistem peran + tenancy sudah terbukti aman lewat tes.

### Dependensi

```bash
composer require inertiajs/inertia-laravel tightenco/ziggy
composer require barryvdh/laravel-dompdf
composer require maatwebsite/excel
composer require laravel/horizon predis/predis
composer require --dev pestphp/pest pestphp/pest-plugin-laravel larastan/larastan laravel/pint

npm install @inertiajs/vue3 vue @vitejs/plugin-vue tailwindcss @tailwindcss/forms
```

> **Catatan dependensi terpasang:** `owen-it/laravel-auditing` **tidak** dipakai — diganti trait `Auditable` kustom (PRD §9 mengizinkan "observer kustom") yang menulis langsung ke tabel `audit_logs` sesuai skema PRD §6.3. Ditambah `pragmarx/google2fa-laravel` + `simplesoftwareio/simple-qrcode` untuk 2FA TOTP (F1.6).

### Pekerjaan

- [x] Inisialisasi proyek Laravel 13, konfigurasi `.env`, MySQL (MariaDB), Redis
- [x] Pasang Inertia (adapter server + klien), Vite, Vue 3, Tailwind 4, Ziggy
- [x] `.gitignore`: `.env`, `storage/app/private/**`, `*.p12`, `public/assets/img/signature-*`
- [x] Konfigurasi `config/app.php`: `timezone = Asia/Jakarta`, `locale = id`, `faker_locale = id_ID`
- [x] Buat 18 migration sesuai urutan di atas, kolom persis mengikuti PRD §6.3
- [x] Buat Enum PHP 8.1: `UserRole`, `WorkUnitLevel`, `PositionGroup`, `DecreeStatus`, `DecreeBatchStatus`, `DocumentCategory`, `EducationLevel`, `Gender`, `MaritalStatus`, `Religion`, `AdditionalDutyStatus`
  - Setiap enum punya method `label(): string` yang membaca `lang/id/enums.php`
- [x] Model + relasi + cast untuk seluruh tabel
- [x] `TenantScope` + trait `BelongsToTenant`:
  - `foundation_head`, `foundation_admin` → tanpa filter
  - `unit_admin` → `where work_unit_id = auth()->user()->work_unit_id`
  - `employee` → `where employee_id = auth()->user()->employee_id` (atau `id` pada model `Employee`)
  - Dipasang pada `Employee`, `Decree`, `Education`, `Document`, `EmployeeAdditionalDuty`
- [x] Autentikasi: login, logout, wajib ganti kata sandi saat pertama, *rate limit* 5/menit
- [x] 2FA (TOTP) untuk peran `foundation_head` — setup QR, challenge, disable
- [x] Policy untuk seluruh model (auto-discovery Laravel; tidak perlu daftar manual di `AppServiceProvider`)
- [x] Middleware `EnsureRole` + pengelompokan rute per peran
- [x] Layout dasar: sidebar admin, header, navigasi per peran, notifikasi *flash* (Inertia)
- [x] CRUD master: `WorkUnit`, `Position`, `EmploymentStatus`, `AdditionalDuty`, `DecreeType` (+ manajemen `User`)
- [x] Halaman Pengaturan Yayasan (tabel `settings`), termasuk field **format NIGY**
- [x] Seeder: `SettingSeeder`, `PositionSeeder`, `EmploymentStatusSeeder`, `AdditionalDutySeeder`, `DecreeTypeSeeder` (5 kode: `SK-PPT`, `SK-PPJ`, `SK-TT`, `SK-MUT`, `SK-BHT`), `WorkUnitSeeder` ⛔ *menunggu daftar riil* (data demo SD1/SMP/SMK hanya via `DemoSeeder` khusus `local`)
- [x] `lang/id/`: `enums.php`, `validation.php`, `auth.php`, `common.php`, `pagination.php` (validasi & pagination dari laravel-lang, dibundel offline)
- [x] Pint + Larastan di CI lokal (keduanya hijau)

### Pengujian

| Berkas | Yang dibuktikan |
|---|---|
| `tests/Feature/Auth/LoginTest.php` | Rate limit, akun nonaktif ditolak, wajib ganti kata sandi |
| `tests/Feature/Auth/TwoFactorTest.php` | Enroll TOTP, challenge setiap login, non-`foundation_head` tanpa 2FA |
| `tests/Feature/Tenancy/WorkUnitScopeTest.php` | `unit_admin` unit A tidak melihat data unit B |
| `tests/Feature/Tenancy/EmployeeScopeTest.php` | `employee` hanya melihat dirinya, termasuk lewat ID langsung di URL |
| `tests/Feature/Authorization/RoleMatrixTest.php` | Tiap kapabilitas §4.2 diuji untuk keempat peran |
| `tests/Feature/F1GuardrailsTest.php` | Keunikan kode satker, audit log append-only, pengaturan hanya `foundation_admin`, alur ganti sandi |

**Hasil: 35 tes, 35 hijau.**

### Definition of Done
`php artisan migrate:fresh --seed` berjalan bersih; empat peran dapat login dan melihat menu yang berbeda; menembus tenancy lewat manipulasi URL gagal dengan 403; seluruh tes hijau.

---

## Fase F2 — Data GTK

**Estimasi 3 minggu · Prasyarat: F1**

### Tujuan
Data GTK dapat dikelola penuh, NIGY dihasilkan otomatis, berkas tersimpan aman.

### Pekerjaan

- [ ] `NumberAllocator` — layanan bersama untuk `number_counters`
  - `allocate(string $key, int $year): int` di dalam `DB::transaction` + `lockForUpdate()`
  - Dipakai NIGY (F2) dan nomor SK (F4)
- [ ] `NigyGenerator`
  - Baca format dari `settings`, ganti token `{tahun_masuk}`, `{bulan_masuk}`, `{kode_satker}`, `{kode_jenjang}`, `{urut}`
  - `{tahun_masuk}` diambil dari `foundation_start_date`
  - Kunci penghitung `nigy:{kode_satker}:{tahun}`
  - Mode timpa manual: validasi unik, **tidak** menaikkan penghitung
- [ ] CRUD `Employee` — form bertab: Pribadi · Kepegawaian · Pendidikan · Berkas
- [ ] Aturan NIGY (PRD F3.2a–F3.2g):
  - Otomatis saat simpan; dapat ditimpa `foundation_admin`
  - Terkunci bila sudah ada `decrees` berstatus `issued` yang memuatnya
  - Tidak berubah saat `work_unit_id` diubah
  - Perubahan tercatat di audit log beserta alasan
- [ ] CRUD `Education`, dengan penegakan satu `is_highest` per GTK
- [ ] Unggah foto: validasi MIME asli, maks 2 MB, *crop* 3:4, kompres
- [ ] Unggah `Document`: PDF/JPG/PNG maks 5 MB, disk privat, akses lewat *signed URL* berbatas waktu
- [ ] `ProfileCompletenessService` — persentase + daftar field/berkas yang kurang
- [ ] `ServicePeriod` — hitung masa kerja (tahun & bulan) dari `foundation_start_date` ke tanggal acuan
- [ ] Pencarian: NIGY, nama (fulltext), NIK, NUPTK; filter satker/jabatan/status
- [ ] Impor Excel dengan pratinjau validasi baris per baris sebelum simpan
- [ ] Ekspor Excel mengikuti filter aktif
- [ ] Riwayat perubahan data GTK lewat trait `Auditable` kustom (keputusan F1: menggantikan `owen-it/laravel-auditing`; menulis ke `audit_logs` dengan old→new per kolom)

### Pengujian

| Berkas | Yang dibuktikan |
|---|---|
| `tests/Unit/NigyGeneratorTest.php` | Format, padding, reset per tahun per satker, mode manual |
| `tests/Feature/Nigy/NigyLockTest.php` | NIGY terkunci setelah SK terbit; mutasi satker tidak mengubah NIGY |
| `tests/Unit/ServicePeriodTest.php` | Batas bulan, tahun kabisat, TMT di masa depan |
| `tests/Feature/Employee/FileUploadTest.php` | MIME palsu ditolak, berkas tidak dapat diakses tanpa signed URL |
| `tests/Feature/Employee/ImportTest.php` | Baris tidak valid dilaporkan, tidak ada data separuh tersimpan |

### Definition of Done
GTK baru tersimpan dengan NIGY otomatis benar; impor 50 baris uji berhasil dengan laporan validasi; berkas tidak dapat diakses langsung lewat URL tebakan.

---

## Fase F2b — Portal Mandiri GTK

**Estimasi 1–2 minggu · Prasyarat: F2**

### Tujuan
GTK dapat masuk dan melengkapi datanya sendiri, memindahkan beban entri dari admin.

### Pekerjaan

- [ ] Pembuatan akun massal dari data `employees` (username = NIGY, kata sandi awal acak, `must_change_password = true`)
- [ ] Cetak/ekspor daftar kredensial awal untuk dibagikan per satuan kerja
- [ ] Observer: `employees.is_active = false` → akun `users` ikut dinonaktifkan
- [ ] Layout portal terpisah, **mobile-first**
- [ ] Beranda GTK: kelengkapan profil, SK terbaru, tugas tambahan berjalan, daftar berkas kurang
- [ ] Form sunting data pribadi — hanya field yang diizinkan (PRD F3.15/F3.16)
  - Ditegakkan di `Http/Requests/Portal/UpdateOwnProfileRequest.php`, bukan sekadar disembunyikan di UI
- [ ] Unggah berkas milik sendiri
- [ ] Unggah arsip SK lama → `decrees` dengan `is_legacy = true`, `legacy_verified_at = null`
- [ ] Antrean verifikasi arsip untuk Admin Satker/Yayasan
- [ ] Unduh PDF SK milik sendiri
- [ ] Penanda "diubah oleh yang bersangkutan" pada tampilan admin

### Pengujian

| Berkas | Yang dibuktikan |
|---|---|
| `tests/Feature/Portal/OwnDataOnlyTest.php` | Setiap rute portal ditolak untuk `employee_id` lain — termasuk unduh berkas dan PDF |
| `tests/Feature/Portal/FieldWhitelistTest.php` | Mengirim `work_unit_id`/`nigy`/`position_id` lewat POST diabaikan, bukan diterima |
| `tests/Feature/Portal/LegacyUploadTest.php` | Arsip unggahan GTK tidak muncul di riwayat resmi sebelum diverifikasi |

> **Perhatian khusus:** `FieldWhitelistTest` adalah pengaman terpenting fase ini. Menyembunyikan field di Vue tidak menghalangi siapa pun mengirim field itu lewat request langsung.

### Definition of Done
Akun GTK dapat dibuat massal; GTK dapat melengkapi profil dari ponsel; upaya menyunting field administratif atau data orang lain gagal di lapisan server.

---

## Fase F3 — Tugas Tambahan

**Estimasi 1 minggu · Prasyarat: F2**

### Tujuan
Penetapan tugas tambahan berperiode berjalan, dengan validasi irisan dan kuota.

### Pekerjaan

- [ ] CRUD penetapan `EmployeeAdditionalDuty`
- [ ] Validasi irisan periode: satu GTK tidak boleh memegang referensi yang sama pada rentang tanggal beririsan
- [ ] Validasi kuota per satuan kerja per tahun pelajaran (peringatan, bukan penolakan keras)
- [ ] Penyaringan referensi mengikuti `applicable_levels` terhadap jenjang satuan kerja
- [ ] Penetapan massal: pilih banyak GTK → satu referensi → satu periode
- [ ] Riwayat kronologis per GTK
- [ ] Daftar pemegang tugas per satuan kerja per tahun pelajaran
- [ ] Peringatan tugas berakhir dalam 30 hari
- [ ] Tautan ke SK penerbit (`decree_id`) — disiapkan sekarang, diisi di F4

### Pengujian

| Berkas | Yang dibuktikan |
|---|---|
| `tests/Unit/DutyOverlapTest.php` | Irisan penuh, irisan sebagian, bersinggungan di ujung tanggal, tidak beririsan |
| `tests/Feature/Duty/QuotaTest.php` | Peringatan muncul saat kuota terlampaui, penetapan tetap dapat dilanjutkan |

### Definition of Done
Penetapan ganda pada periode beririsan ditolak dengan pesan jelas; penetapan massal 12 wali kelas selesai dalam satu langkah.

---

## Fase F4 — SK Tunggal

**Estimasi 3 minggu · Prasyarat: F1–F3 · Fase paling berisiko**

### Tujuan
SK dapat diterbitkan dari draft hingga PDF final, dengan penomoran yang tidak mungkin bentrok.

### Pekerjaan

- [ ] `DecreeNumberService` di atas `NumberAllocator`
  - Kunci `decree:{kode_jenis}:{kode_satker}:{tahun}`
  - Render token `{nomor}`, `{kode_jenis}`, `{kode_satker}`, `{bulan_romawi}`, `{bulan}`, `{tahun}`, `{tahun_pelajaran}`
  - `registration_number` — seri global lintas seluruh SK
- [ ] `RomanMonth` + `IndonesianDate` di `app/Support/`
- [ ] `DecreeWorkflowService` — mesin status dengan transisi legal terdaftar eksplisit
  - `draft → submitted → verified → issued`, plus `rejected`, `cancelled`, `superseded`
  - Alokasi nomor **hanya** pada transisi ke `verified`
  - Setiap transisi menulis `decree_workflow_logs`
- [ ] `DecreeSnapshotBuilder` — bekukan seluruh nilai tercetak ke `snapshot_data`, termasuk teks konsideran
- [ ] Form buat SK: pilih jenis, prefill dari data GTK, field dapat ditimpa manual
- [ ] Migrasi template `sk.blade.php` → `resources/views/decrees/appointment.blade.php`
  - Ganti seluruh variabel ke Bahasa Inggris (PRD §5.6, tabel pemetaan)
  - `$kepala_sekolah` → `$chairman_name`
  - Path gambar → `public_path(...)`
  - Kop & tembusan dari `settings`
  - Konsideran dari `decree_types`
  - Diktum Ketiga diperbaiki: masa berlaku **satu tahun pelajaran**
- [ ] Kertas F4/Folio: `Pdf::setPaper([0, 0, 609.45, 935.43])`; uji cetak fisik
- [ ] Watermark "DRAFT — BUKAN DOKUMEN RESMI" saat `$is_signed === false`
- [ ] Pratinjau PDF di status draft
- [ ] Antrean verifikasi (Admin Yayasan) dan antrean tanda tangan (Ketua Yayasan)
- [ ] Penolakan wajib beralasan + notifikasi in-app
- [ ] Pembatalan + penerbitan SK pengganti (`replacement_decree_id`, status `superseded`)
- [ ] Daftar SK dengan seluruh filter PRD F5.10
- [ ] Penguncian SK `issued` — seluruh jalur edit ditutup di Policy dan Form Request

### Pengujian

| Berkas | Yang dibuktikan |
|---|---|
| `tests/Feature/Decree/NumberAllocationTest.php` | **Uji konkuren**: 50 alokasi paralel menghasilkan 50 nomor unik berurutan |
| `tests/Unit/DecreeNumberFormatTest.php` | Seluruh token, padding, bulan romawi |
| `tests/Feature/Decree/WorkflowTest.php` | Setiap transisi legal berhasil; setiap transisi ilegal ditolak; peran salah ditolak |
| `tests/Feature/Decree/SnapshotTest.php` | Mengubah data GTK setelah SK terbit tidak mengubah isi SK |
| `tests/Feature/Decree/ImmutabilityTest.php` | SK `issued` tidak dapat disunting lewat jalur mana pun |

> **Uji konkurensi wajib nyata**, bukan disimulasikan berurutan — jalankan proses paralel yang benar-benar berebut baris `number_counters`. Bug penomoran hanya muncul di bawah beban, dan akibatnya (dua SK bernomor sama) tidak dapat diperbaiki setelah dokumen beredar.

### Definition of Done
SK tunggal terbit end-to-end dengan PDF benar di kertas F4; uji konkurensi lolos; SK terbit terbukti tidak dapat diubah.

---

## Fase F5 — Tanda Tangan Digital & Verifikasi

**Estimasi 2 minggu · Prasyarat: F4**

### Tujuan
PDF terbit tertandatangani kriptografis dan dapat diverifikasi publik lewat QR.

### Dependensi

```bash
composer require setasign/fpdi tecnickcom/tcpdf
composer require simplesoftwareio/simple-qrcode
```

### Pekerjaan

- [ ] Bangkitkan sertifikat self-signed:
  ```bash
  openssl req -x509 -newkey rsa:4096 -keyout key.pem -out cert.pem -days 3650 \
    -subj "/CN=Yayasan Pondok Pesantren Qomarul Hidayah/O=YPP Qomarul Hidayah/C=ID"
  openssl pkcs12 -export -out yayasan.p12 -inkey key.pem -in cert.pem
  ```
- [ ] `SignerInterface` + implementasi `SelfSignedPkcs12Signer` (TCPDF `setSignature()`)
- [ ] `CertificateManager`: unggah `.p12`, kata sandi via `Crypt`, baca metadata (subject, issuer, serial, masa berlaku, fingerprint)
- [ ] Simpan `.p12` di `storage/app/private/certificates/` — di luar *document root*
- [ ] Pengamanan gambar tanda tangan (PRD F7.16–F7.20): `storage/app/private/signature/`, izin `0400`, tanpa rute HTTP, tidak muncul di pratinjau draft
- [ ] Pipeline penerbitan: render DomPDF → sisipkan QR → tandatangani → hitung SHA-256 → simpan `decree_signatures`
- [ ] QR berisi URL `https://<domain>/verifikasi/{uuid}`, disisipkan sebagai data URI
- [ ] Halaman verifikasi publik (tanpa login, tanpa Inertia auth): nomor, nama, NIGY, satuan kerja, jabatan, tanggal terbit, status
  - Menampilkan data minimum saja — tanpa NIK, alamat, atau berkas
  - Status `cancelled` → tampilan ⛔ beserta tanggal dan rujukan pengganti
- [ ] Verifikasi mandiri: unggah PDF → bandingkan hash → laporkan cocok/tidak
- [ ] *Rate limit* endpoint verifikasi
- [ ] Rotasi sertifikat: arsipkan yang lama, SK lama tetap terverifikasi dengan sertifikat saat penerbitan
- [ ] Peringatan 60 hari sebelum sertifikat kedaluwarsa

### Pengujian

| Berkas | Yang dibuktikan |
|---|---|
| `tests/Feature/Signing/SignatureIntegrityTest.php` | PDF diubah 1 byte → verifikasi hash gagal |
| `tests/Feature/Verification/PublicPageTest.php` | Tanpa login berhasil; UUID tidak dikenal → 404; data sensitif tidak bocor |
| `tests/Feature/Signing/SignatureAssetTest.php` | Gambar tanda tangan tidak dapat diakses lewat HTTP dalam bentuk apa pun |
| `tests/Feature/Verification/CancelledTest.php` | SK dibatalkan → halaman menampilkan status batal |

### Definition of Done
PDF terbit menampilkan panel tanda tangan di Adobe Reader (berstatus *not trusted*, sesuai sifat self-signed); QR dipindai dari ponsel membuka halaman verifikasi yang benar; PDF yang diubah terdeteksi.

---

## Fase F6 — Batch Generate SK

**Estimasi 1–2 minggu · Prasyarat: F5**

### Tujuan
Menerbitkan ratusan SK dalam satu alur, ditandatangani sekali klik.

### Pekerjaan

- [ ] Wizard batch: jenis SK → tahun pelajaran → TMT → tanggal penetapan → seleksi penerima
- [ ] Seleksi lewat filter + centang manual, dengan penghitung terpilih
- [ ] Pra-validasi kelengkapan; GTK bermasalah ditampilkan dengan pilihan **lewati** atau **batalkan**
- [ ] `ProcessDecreeBatchJob` memakai `Bus::batch()`, progres real-time (polling atau *broadcast*)
- [ ] Alokasi nomor berurutan dan atomik selama batch
- [ ] Tanda tangan seluruh batch sekali klik oleh Ketua Yayasan, dengan pratinjau daftar penerima
- [ ] `SignDecreeJob` per SK; kegagalan satu item tidak menggagalkan batch
- [ ] Laporan hasil: berhasil / gagal + alasan per item
- [ ] Unduh ZIP dikelompokkan per satuan kerja, dan opsi PDF gabungan
- [ ] Pembatalan batch selama belum ditandatangani
- [ ] Batas aman 500 SK per batch
- [ ] Konfigurasi Horizon + Supervisor

### Pengujian

| Berkas | Yang dibuktikan |
|---|---|
| `tests/Feature/Batch/ConcurrentNumberingTest.php` | 200 SK batch → 200 nomor unik berurutan, tanpa lompatan |
| `tests/Feature/Batch/PartialFailureTest.php` | Satu item gagal → sisanya tetap terbit, laporan akurat |
| `tests/Feature/Batch/AuthorizationTest.php` | Hanya `foundation_head` dapat menandatangani batch |

### Definition of Done
Batch 200 SK selesai < 10 menit di lingkungan uji; ZIP terunduh terkelompok per satuan kerja; laporan hasil cocok dengan isi ZIP.

---

## Fase F7 — Dashboard, Laporan & Audit

**Estimasi 1–2 minggu · Prasyarat: F4**

### Pekerjaan

- [ ] Dashboard Ketua/Admin Yayasan: total GTK, sebaran per satker/jabatan/status/jenjang pendidikan
- [ ] Kartu antrean menonjol: menunggu verifikasi, menunggu tanda tangan (umur antrean ditampilkan)
- [ ] Dashboard Admin Satker (agregat unitnya) dan beranda GTK
- [ ] Laporan: daftar GTK, rekap SK per periode, pemegang tugas tambahan, profil belum lengkap, mendekati pensiun, akun belum pernah login
- [ ] Ekspor seluruh laporan ke Excel dan PDF
- [ ] Notifikasi in-app + lonceng dengan penghitung belum dibaca
- [ ] Penampil audit log dengan filter pengguna/entitas/tanggal + ekspor
- [ ] Pastikan audit log *append-only* — tidak ada rute hapus/ubah

### Definition of Done
Ketua Yayasan melihat antrean dan komposisi SDM dalam satu layar; audit log memuat seluruh transisi SK dan perubahan data GTK.

---

## Fase F8 — Uji, Migrasi & Go-Live

**Estimasi 2 minggu · Prasyarat: seluruh fase**

### Pekerjaan

- [ ] Penyiapan server: PHP 8.3 + `gd`/`openssl`/`mbstring`/`zip`, MySQL 8, Redis, Nginx, TLS, Supervisor, cron
- [ ] SSH hanya dengan kunci, firewall, pembaruan sistem
- [ ] Cadangan otomatis: basis data harian, berkas mingguan, retensi 30 hari, ke luar server
- [ ] **Uji pemulihan cadangan** — cadangan yang belum pernah dipulihkan bukan cadangan
- [ ] Migrasi data GTK eksisting lewat impor Excel + periode pembersihan data
- [ ] Unggah arsip PDF SK lama (metadata opsional, dapat menyusul)
- [ ] UAT bersama Admin Yayasan, dua Admin Satker, dan Ketua Yayasan
- [ ] Uji cetak fisik pada kertas F4 di printer yayasan
- [ ] Pelatihan: satu sesi admin, satu sesi Ketua Yayasan, panduan singkat bergambar untuk GTK
- [ ] Sosialisasi halaman verifikasi QR ke Dinas Pendidikan
- [ ] Go-live + pendampingan dua minggu pertama

### Definition of Done
Batch perpanjangan pertama terbit dari sistem produksi; pemulihan cadangan terbukti berhasil; admin dapat bekerja tanpa pendampingan.

---

## Ringkasan Jadwal

| Fase | Lingkup | Estimasi | Prasyarat | Status |
|---|---|---|---|---|
| F1 | Fondasi, skema, RBAC, tenancy, master data | 2 mgg | — | ✅ **selesai 12 Agu 2026** |
| F2 | Data GTK, NIGY, berkas, impor/ekspor | 3 mgg | F1 | ⬜ |
| F2b | Portal mandiri GTK | 1–2 mgg | F2 | ⬜ |
| F3 | Tugas tambahan | 1 mgg | F2 | ⬜ |
| F4 | SK tunggal, penomoran, PDF | 3 mgg | F1–F3 | ⬜ |
| F5 | Tanda tangan digital & verifikasi | 2 mgg | F4 | ⬜ |
| F6 | Batch generate | 1–2 mgg | F5 | ⬜ |
| F7 | Dashboard, laporan, audit | 1–2 mgg | F4 | ⬜ |
| F8 | Uji, migrasi, go-live | 2 mgg | semua | ⬜ |
| | **Total** | **15–19 mgg** | | |

F3 dan F7 dapat berjalan paralel dengan fase lain bila ada lebih dari satu pengembang. F2b sengaja ditempatkan sebelum F4 agar pengumpulan data berjalan bersamaan dengan pembangunan modul SK.

---

## Yang Masih Dibutuhkan

| Kebutuhan | Dibutuhkan pada | Status |
|---|---|---|
| Daftar satuan kerja riil + kode | F1 (seeder) | ⛔ **memblokir** — `WorkUnitSeeder` tetap kosong; demo pakai `DemoSeeder` |
| Nama & jabatan Ketua Yayasan | F1 (settings) | ✅ tidak lagi memblokir — `SettingSeeder` jalan dengan nilai kosong + peringatan; dapat diisi via UI Pengaturan Yayasan |
| `qomarul.png`, `signature-basah.png` | F4/F5 | ⚠️ belum ada |
| Font `arial.ttf`, `arialbd.ttf` (atau Liberation Sans) | F4 | ⚠️ belum ada |
| Contoh SK asli yang sudah terbit | F4 | ⚠️ untuk mencocokkan tata letak |
| Teks konsideran `SK-PPJ`/`TT`/`MUT`/`BHT` | F4 | dapat diisi admin sendiri |
| Sertifikat `.p12` | F5 | dibangkitkan saat F5 |
| VPS, domain, TLS | F8 | menjelang go-live |

---

## Persiapan Modul Surat Keluar (v2)

Modul ini **tidak dibangun di v1**, tetapi empat keputusan berikut diambil sekarang karena mengubahnya belakangan jauh lebih mahal.

| Komponen v1 | Cara membangunnya agar dapat dipakai ulang | Fase |
|---|---|---|
| `NumberAllocator` | Terima kunci penghitung sembarang (`decree:…`, `nigy:…`, kelak `letter:…`). Jangan menanam string `decree` di dalam layanannya | F2 |
| `SignerInterface` | Antarmuka menerima *path* PDF + metadata penanda tangan, tanpa mengetahui jenis dokumen | F5 |
| Halaman verifikasi publik | Rutekan sebagai `/verifikasi/{uuid}` untuk "dokumen", dengan penyelesaian jenis dokumen di dalam service — bukan `/verifikasi/sk/{uuid}` | F5 |
| `settings` | Kop surat, identitas yayasan, dan daftar tembusan disimpan terpisah dari `decree_types` agar jenis dokumen lain ikut memakainya | F1 |

**Yang sengaja tidak digeneralisasi:** tabel `decrees` tetap khusus SK. Surat keluar punya bentuk data sendiri — tujuan, perihal, sifat, klasifikasi arsip, lampiran — dan akan mendapat tabel `letters` tersendiri di v2. Memaksakan satu tabel untuk keduanya menghasilkan puluhan kolom nullable yang saling tidak relevan, dan itu justru mempersulit kedua modul.

Perkiraan kasar modul surat keluar bila fondasi di atas dipatuhi: **2–3 minggu** (CRUD surat, penomoran, template, disposisi sederhana, arsip).

---

## Titik Rawan yang Perlu Diawasi

1. **Penomoran di bawah konkurensi (F4/F6).** Satu-satunya bug pada rencana ini yang akibatnya tidak dapat diperbaiki setelah dokumen fisik beredar. Uji paralel nyata, bukan simulasi berurutan.
2. **Kebocoran field di portal GTK (F2b).** Menyembunyikan input di Vue bukan pengamanan — daftar putih field wajib ada di Form Request dan diuji.
3. **Gambar tanda tangan (F5).** Aset paling sensitif; kebocorannya tidak dapat dicegah oleh tanda tangan kriptografis. QR + halaman verifikasi adalah pertahanan sebenarnya.
4. **Tata letak F4/Folio (F4).** Blok `Reg.` memakai `position: fixed` yang dihitung relatif terhadap tinggi halaman — wajib uji cetak fisik, bukan sekadar pratinjau layar.
5. **Kualitas data migrasi (F8).** Batch pertama akan mengungkap seluruh data yang kurang. Portal GTK (F2b) adalah mitigasi utamanya, karena itu ia didahulukan.
