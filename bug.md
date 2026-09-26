# Laporan Bug — SIMQOH

Hasil simulasi nyata seluruh fitur lewat **Chromium non-headless** terhadap `prd.md` v1.15 dan `implementation-plan.md` v1.10.

| | |
|---|---|
| **Tanggal uji** | 13 Agustus 2026 |
| **Lingkungan** | Laravel 13.25 · PHP 8.3 · MariaDB (`simqoh`) · Redis · Vite dev server · `php artisan serve` di `127.0.0.1:8123` |
| **Basis data** | `migrate:fresh --seed` (DemoSeeder: 3 satuan kerja, 1 GTK, 4 akun) |
| **Metode** | Puppeteer mengendalikan Chromium yang terlihat — klik, isi form, unggah berkas, unduh; ditambah pemeriksaan basis data dan `storage/logs/laravel.log` |

**Status: sedang berjalan.** Dokumen ini ditulis bertahap setiap kali bug ditemukan.

---

## Ringkasan

| ID | Keparahan | Area | Judul |
|---|---|---|---|
| [BUG-01](#bug-01) | 🔴 Tinggi | F8.3 Dashboard | Dashboard Admin Satuan Kerja blank total (crash JavaScript) |
| [BUG-02](#bug-02) | 🟢 Rendah | F8.3 Dashboard | Prop `profileCompleteness` dikirim server tetapi tidak dipakai UI |
| [BUG-03](#bug-03) | ⛔ Kritis | F2.8 Pengaturan | Pengaturan Yayasan tidak pernah tersimpan, tetapi melaporkan "berhasil" |
| [BUG-04](#bug-04) | 🔴 Tinggi | F2.8 Pengaturan | Form Pengaturan menampilkan nilai default, bukan nilai tersimpan |
| [BUG-05](#bug-05) | 🔴 Tinggi | F7.16–F7.20 Tanda tangan | Unggah gambar tanda tangan basah selalu gagal HTTP 500 |
| [BUG-06](#bug-06) | ⛔ Kritis | F7.18 Berkas privat | Unggah tanda tangan kedua meng-`chmod 0400` seluruh `storage/app/private` |
| [BUG-07](#bug-07) | 🟡 Sedang | F10.1/F10.2 Bahasa | Nilai enum tampil mentah dalam Bahasa Inggris di profil GTK |
| [BUG-08](#bug-08) | 🟡 Sedang | F3.6 Kelengkapan profil | Daftar "Kurang" menampilkan kunci mentah, bukan Bahasa Indonesia |
| [BUG-09](#bug-09) | 🟡 Sedang | §5.3.3 Riwayat pendidikan | Field IPK, berkas ijazah, dan berkas transkrip tidak ada di antarmuka |
| [BUG-10](#bug-10) | 🟢 Rendah | F1.1 Rate limit | Throttle login mengembalikan 429 mentah tanpa pesan Indonesia |
| [BUG-11](#bug-11) | ⛔ Kritis | §4.2 Tenancy | `TenantScope` menyaring kolom yang tidak ada → Admin Satker mendapat HTTP 500 di profil GTK dan pratinjau PDF |

---

<a id="bug-01"></a>
## BUG-01 — Dashboard Admin Satuan Kerja blank total 🔴

**Area:** PRD F8.3 · **Berkas:** `resources/js/Pages/Dashboard.vue:82`, `app/Http/Controllers/DashboardController.php:76`

**Langkah:** Login sebagai `admin.sd1` (peran `unit_admin`) → sistem mengarahkan ke `/`.

**Diharapkan:** Dashboard agregat unit tampil — statistik unitnya, kelengkapan profil, status SK (PRD F8.3).

**Kenyataan:** Halaman **kosong sepenuhnya**. Console browser:

```
Uncaught (in promise) TypeError: Cannot read properties of undefined (reading 'length')
```

**Akar masalah:** `Dashboard.vue:82` memakai `v-if="employeesByUnit.length"` tanpa optional chaining. Prop dideklarasikan `employeesByUnit: Array` **tanpa `default`**, sedangkan `DashboardController::unitStats()` tidak pernah mengirim prop tersebut — hanya `foundationStats()` yang mengirimnya. Untuk `unit_admin` nilainya `undefined` sehingga render gagal total.

**Saran perbaikan:** `v-if="employeesByUnit?.length"`, atau `default: () => []` pada `defineProps`, atau kirim `employeesByUnit` dari `unitStats()`.

**Catatan:** Ini membatalkan klaim F7 pada `implementation-plan.md` ("Dashboard Admin Satker (agregat unitnya) ✅") — jalur peran `unit_admin` tampaknya tidak pernah dibuka lewat antarmuka.

---

<a id="bug-02"></a>
## BUG-02 — Prop `profileCompleteness` dihitung lalu dibuang 🟢

**Area:** PRD F8.3 · **Berkas:** `app/Http/Controllers/DashboardController.php:80`, `resources/js/Pages/Dashboard.vue:134`

`unitStats()` menghitung `profileCompleteness`, tetapi `Dashboard.vue` tidak mendeklarasikannya di `defineProps` dan tidak pernah merendernya. PRD F8.3 mensyaratkan kelengkapan profil tampil di dashboard Admin Satker.

**Saran perbaikan:** tambahkan prop + kartunya, atau hapus perhitungan yang tidak terpakai.

---

<a id="bug-03"></a>
## BUG-03 — Pengaturan Yayasan tidak pernah tersimpan ⛔

**Area:** PRD F2.8, §5.2.6 · **Berkas:** `app/Http/Controllers/Admin/SettingController.php:38-46`

**Langkah:** Login `admin` → `/admin/settings` → isi **Nama Ketua Yayasan**, **Alamat**, **Tempat Penetapan Default**, **Daftar Tembusan** → klik **Simpan Pengaturan**.

**Diharapkan:** nilai tersimpan di tabel `settings` dan tampil kembali setelah reload.

**Kenyataan:** flash **"berhasil"** muncul, tetapi tabel `settings` **tidak berubah sama sekali**. Setelah tiga kali percobaan simpan lewat GUI:

```
foundation.chairman_name = ""
foundation.default_issued_place = "Gondang"     ← input "Trenggalek" diabaikan
```

**Akar masalah:**

```php
$data = $request->validate($rules);          // kunci aturan bertitik → hasil BERSARANG

foreach ($this->schema() as $key => $field) {
    if (! array_key_exists($key, $data)) {   // $key = "foundation.chairman_name"
        continue;                            // ← SELALU true, semua field dilewati
    }
    Setting::set($key, $data[$key], $field['group']);
}
```

Laravel mengembalikan hasil `validate()` dalam bentuk bersarang ketika kunci aturan memakai titik. Diverifikasi langsung:

```
Validator::make(["foundation"=>["chairman_name"=>"Tes"]], ["foundation.chairman_name"=>["required"]])->validate()
→ keys hasil: foundation
→ array_key_exists("foundation.chairman_name", $d) = false
```

Akibatnya `Setting::set()` **tidak pernah dipanggil**, lalu controller tetap mengembalikan `back()->with('success', ...)`.

**Saran perbaikan:** pakai `Arr::has($data, $key)` + `data_get($data, $key)`, atau ratakan dengan `Arr::dot($data)` sebelum loop.

**Dampak:** **Nama Ketua Yayasan tidak dapat diisi lewat antarmuka sama sekali.** Karena nama penanda tangan SK diambil dari `foundation.chairman_name`, seluruh PDF SK mencetak blok tanda tangan tanpa nama. Ini membatalkan status "✅ tidak lagi memblokir — dapat diisi via UI Pengaturan Yayasan" pada `implementation-plan.md` §Yang Masih Dibutuhkan.

---

<a id="bug-04"></a>
## BUG-04 — Form Pengaturan menampilkan default, bukan nilai tersimpan 🔴

**Area:** PRD F2.8 · **Berkas:** `app/Http/Controllers/Admin/SettingController.php:140-145`

**Langkah:** `SettingSeeder` mengisi `foundation.address`, `foundation.notary_deed`, `foundation.sk_menkumham`, dan `letterhead.cc_list` (5 baris tembusan) → buka `/admin/settings`.

**Kenyataan:** field **Alamat**, **Akta Notaris**, **Nomor SK Menkumham**, dan **Daftar Tembusan Default** tampil **kosong**, padahal basis data berisi nilainya:

```
foundation.address     = "Gondang, Tugu, Trenggalek, Jawa Timur"
foundation.notary_deed = "KAYUN WIDIHARSONO, S.H, M.Kn — Nomor: 09 Tahun 2014"
letterhead.cc_list     = ["Kepala Dinas Pendidikan …", …5 baris]
```

**Akar masalah:**

```php
$defaults = ['foundation' => ['name' => …, 'address' => …, …]];   // kunci PENDEK

$stored = Setting::where('group', $group)->get()
    ->mapWithKeys(fn (Setting $s) => [$s->key => $s->value])      // kunci PENUH: "foundation.address"
    ->all();

return array_merge($defaults[$group], $stored);                   // dua ruang kunci bercampur
```

Komponen Vue membaca kunci pendek (`settings.foundation.address`), sehingga selalu memperoleh nilai default hard-coded, bukan yang tersimpan.

**Saran perbaikan:** petakan `$stored` dengan `Str::after($setting->key, $group.'.')` sebelum `array_merge`.

**Dampak berpasangan dengan BUG-03:** begitu BUG-03 diperbaiki tanpa BUG-04, menekan **Simpan Pengaturan** akan **menimpa alamat, akta notaris, nomor SK Menkumham, dan seluruh daftar tembusan dengan nilai kosong** — semuanya tercetak pada kop dan tembusan setiap SK.

---

<a id="bug-05"></a>
## BUG-05 — Unggah gambar tanda tangan basah selalu gagal HTTP 500 🔴

**Area:** PRD F7.16–F7.20, F7.19 · **Berkas:** `app/Http/Controllers/Admin/SettingController.php:78-86`

**Langkah:** Login `admin` → `/admin/settings` → pilih PNG pada **Gambar Tanda Tangan Basah** → isi **Kata Sandi Anda** dengan kata sandi yang benar → klik **Ganti Tanda Tangan**.

**Kenyataan:** HTTP **500**. `storage/logs/laravel.log`:

```
SQLSTATE[22007]: Invalid datetime format: 1366 Incorrect integer value:
'foundation.signature_path' for column `simqoh`.`audit_logs`.`auditable_id` at row 1
```

**Akar masalah:**

```php
AuditLog::create([
    'auditable_type' => Setting::class,
    'auditable_id'   => 'foundation.signature_path',   // ← string ke kolom integer
    …
]);
```

`audit_logs.auditable_id` bertipe integer; MySQL mode strict menolak string. Berkas **sudah terlanjur ditulis** ke disk sebelum baris audit gagal, sehingga berkas yatim tertinggal dan `foundation.signature_path` tidak pernah di-set.

**Sisi baik yang terverifikasi:** validasi `current_password` bekerja — unggah tanpa kata sandi memang ditolak (F7.19 terpenuhi sebagian).

**Saran perbaikan:** isi `auditable_id` dengan `null`/`0` dan simpan kunci setting di `new_values`, atau ubah kolom menjadi string. Bungkus penulisan berkas + audit + `Setting::set` dalam satu transaksi.

**Dampak:** gambar tanda tangan basah tidak dapat dipasang lewat antarmuka sama sekali.

---

<a id="bug-06"></a>
## BUG-06 — Unggah ulang tanda tangan mengunci seluruh `storage/app/private` ⛔

**Area:** PRD F7.18, §9 · **Berkas:** `app/Http/Controllers/Admin/SettingController.php:70-76`

**Langkah:** Ulangi unggah tanda tangan setelah percobaan pertama gagal (BUG-05). Berkas `signature-basah.png` sudah ada dengan izin `0400`, sehingga `storeAs()` gagal menimpanya dan mengembalikan `false`.

**Kenyataan terverifikasi di mesin uji:**

```
$ ls -la storage/app/private/
ls: cannot access 'storage/app/private/': Permission denied
```

Seluruh subdirektori — `decrees/`, `documents/`, `photos/`, `certificates/`, `legacy/` — menjadi tidak dapat dibaca. Semua unduhan berkas GTK dan PDF SK mati sampai izin dipulihkan manual lewat shell.

**Akar masalah:**

```php
$path = $file->storeAs('signature', 'signature-basah.png', 'private');   // false saat gagal

if (function_exists('chmod')) {
    @chmod(Storage::disk('private')->path($path), 0400);                 // path(false) = ROOT disk privat
}
```

Nilai balik `storeAs()` tidak diperiksa. Saat bernilai `false`, `Storage::disk('private')->path(false)` menghasilkan path **root disk privat**, lalu `chmod($root, 0400)` mengunci seluruhnya. Log membuktikan percobaan kedua tercatat `{"path":false}`:

```
insert into `audit_logs` (…, `new_values`, …) values (2, signature_replaced, App\Models\Setting,
foundation.signature_path, {"path":false}, …)
```

**Saran perbaikan:** periksa nilai balik `storeAs()` dan hentikan bila `false`; hapus atau `chmod 0600` berkas lama sebelum menimpa; jangan pernah memanggil `chmod` pada path yang tidak divalidasi.

**Dampak:** satu klik ulang oleh Admin Yayasan melumpuhkan seluruh akses berkas privat di produksi, dan hanya dapat dipulihkan lewat akses shell ke server.

---

<a id="bug-07"></a>
## BUG-07 — Nilai enum tampil mentah dalam Bahasa Inggris 🟡

**Area:** PRD F10.1, F10.2, §8 Bahasa · **Halaman:** `/admin/employees/{id}` (tab Informasi)

**Langkah:** Buka profil GTK yang agamanya Islam dan status pernikahannya "Belum Menikah".

**Kenyataan:**

```
Agama                 islam
Status Pernikahan     single
```

**Diharapkan:** `Islam` dan `Belum Menikah`.

**Akar masalah:** terjemahan **sudah tersedia** di `lang/id/enums.php` (`religion.islam => 'Islam'`, `marital_status.single => 'Belum Menikah'`), tetapi halaman Show merender nilai enum mentah tanpa memanggil `label()`/helper terjemahan. PRD F10.2 mewajibkan setiap nilai enum punya label Indonesia terpusat — datanya ada, pemakaiannya yang terlewat.

---

<a id="bug-08"></a>
## BUG-08 — Daftar "Kurang" pada kelengkapan profil menampilkan kunci mentah 🟡

**Area:** PRD F3.6, F10.1 · **Halaman:** `/admin/employees/{id}`

**Kenyataan:**

```
Kelengkapan Profil  83%
Kurang: pendidikan.tertinggi, berkas.ktp, berkas.diploma
```

**Diharapkan:** teks Indonesia yang terbaca, seperti yang **sudah benar** di Portal GTK (`/portal`):

```
Ibu Kandung · Email · Pas Foto · Mata Pelajaran · Pendidikan Tertinggi · Berkas KTP · Berkas Ijazah
```

**Akar masalah:** halaman admin merender kunci mentah dari `ProfileCompletenessService`, sedangkan halaman portal menerjemahkannya. Perilaku dua halaman tidak konsisten untuk data yang sama. Perhatikan juga `berkas.diploma` — campuran Indonesia/Inggris di dalam satu kunci.

---

<a id="bug-09"></a>
## BUG-09 — Riwayat pendidikan kehilangan IPK dan unggah berkas ijazah/transkrip 🟡

**Area:** PRD §5.3.3, §6.3 tabel `educations` · **Halaman:** `/admin/employees/{id}` → tab **Pendidikan** → **Tambah**

**Field yang tersedia di antarmuka:**

```
Jenjang · Tahun Masuk · Institusi · Jurusan · Tahun Lulus · Nomor Ijazah · Tanggal Ijazah · Pendidikan tertinggi
```

**Field yang disyaratkan PRD tetapi tidak ada:** **IPK**, **berkas ijazah**, **berkas transkrip**.

Ketiganya sudah ada sebagai kolom di basis data (`database/migrations/…_create_educations_table.php:21,23,24` — `gpa`, `certificate_file_path`, `transcript_file_path`), jadi hanya antarmukanya yang belum dibuat. PRD §5.3.3 mencantumkan ketiganya sebagai bagian riwayat pendidikan, dan `implementation-plan.md` F2 menandai "CRUD `Education`" sebagai selesai.

---

<a id="bug-10"></a>
## BUG-10 — Throttle login mengembalikan 429 mentah tanpa pesan Indonesia 🟢

**Area:** PRD F1.1 · **Berkas:** `routes/web.php:44`

**Langkah:** Kirim lebih dari 5 percobaan login gagal dalam satu menit.

**Kenyataan:** pembatasan laju **bekerja** (server mengembalikan `429 Too Many Requests`), tetapi respons Inertia tidak ditangani sehingga pengguna tidak melihat pesan apa pun — halaman tampak tidak merespons.

**Diharapkan:** pesan yang sudah disiapkan di `lang/id/auth.php` ditampilkan di form:

```
auth.throttle → "Terlalu banyak percobaan masuk. Silakan coba lagi dalam :seconds detik."
```

String terjemahan itu ada tetapi **tidak pernah dipakai** karena rute memakai middleware `throttle:5,1` generik, bukan pembatas yang melempar `ValidationException`.

**Saran perbaikan:** pakai `RateLimiter` di dalam `LoginController::store()` dan lempar `ValidationException::withMessages(['login' => __('auth.throttle', ['seconds' => $seconds])])`.

---

<a id="bug-11"></a>
## BUG-11 — `TenantScope` menyaring kolom yang tidak ada di tabel ⛔

**Area:** PRD §4.2 Aturan tenancy, §9 prinsip 2 · **Berkas:** `app/Models/Scopes/TenantScope.php:23`

**Langkah:** Login sebagai `admin.sd1` (peran `unit_admin`) → buka profil GTK mana pun, mis. `/admin/employees/2`.

**Diharapkan:** profil GTK unitnya sendiri tampil.

**Kenyataan:** **HTTP 500** pada setiap profil GTK. `storage/logs/laravel.log`:

```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'educations.work_unit_id' in 'WHERE'
SQL: select * from `educations`
     where `educations`.`employee_id` = 2 and `is_highest` = 1
       and `educations`.`work_unit_id` = 1
```

**Akar masalah:**

```php
if ($user->role === UserRole::UnitAdmin) {
    $builder->where($model->getTable().'.work_unit_id', $user->work_unit_id);
    return;
}
```

Scope ini memakai `work_unit_id` untuk **semua** model yang memakai trait `BelongsToTenant`, padahal dua di antaranya tidak punya kolom itu — sesuai rancangan PRD §6.3 memang tidak ada:

| Model | Punya `work_unit_id`? | Kolom sebenarnya |
|---|---|---|
| `Employee` | ✅ | `id, …, work_unit_id, …` |
| `Decree` | ✅ | `id, …, work_unit_id, …` |
| `EmployeeAdditionalDuty` | ✅ | `id, …, work_unit_id, …` |
| **`Education`** | ❌ | `id, employee_id, level, institution, major, start_year, end_year, certificate_number, certificate_date, gpa, is_highest, certificate_file_path, transcript_file_path` |
| **`Document`** | ❌ | `id, employee_id, category, name, path, mime, size, uploaded_by` |

Setiap query `Education` atau `Document` yang dijalankan oleh `unit_admin` langsung gagal di lapisan SQL.

**Dampak berantai yang terverifikasi:**

1. `/admin/employees/{id}` → **500** untuk seluruh GTK (Admin Satker tidak dapat membuka satu pun profil).
2. `/admin/decrees/{id}/preview-pdf` → **500**. Pratinjau PDF draft (PRD F5.4, wajib sebelum pengajuan) tidak dapat dibuka Admin Satker, karena renderer membaca pendidikan tertinggi untuk mengisi `$education_level`/`$major`.
3. Setiap unggah/unduh berkas kepegawaian oleh Admin Satker.

Ini melumpuhkan **persona Admin Satuan Kerja** — salah satu dari tiga persona operator pada PRD §4.1 — pada hampir seluruh alur kerjanya. Peran `foundation_admin` dan `foundation_head` tidak terkena karena scope-nya melewati filter.

**Mengapa lolos dari pengujian:** `implementation-plan.md` mencantumkan `tests/Feature/Tenancy/WorkUnitScopeTest.php` sebagai hijau. Tes itu tampaknya hanya menguji `Employee`/`Decree` (yang punya kolomnya), bukan `Education`/`Document`.

**Saran perbaikan:** buat scope memilih kolom sesuai model, mis. jika model tidak punya `work_unit_id`, saring lewat relasi:

```php
$column = $model->getTable().'.work_unit_id';
if (! Schema::hasColumn($model->getTable(), 'work_unit_id')) {
    $builder->whereHas('employee', fn ($q) => $q->where('work_unit_id', $user->work_unit_id));
    return;
}
```

atau beri properti `$tenantColumn` / `$tenantRelation` pada masing-masing model.

---

<a id="bug-12"></a>
## BUG-12 — Pratinjau PDF draft gagal 500 bagi Admin Satker 🔴

**Area:** PRD F5.4, F6.5, alur §7.1 langkah 3 · **Turunan dari BUG-11**

**Langkah:** Login `admin.sd1` → buat draft SK → buka **Pratinjau PDF**.

**Kenyataan:** `GET /admin/decrees/{id}/preview-pdf` → **HTTP 500** (respons `text/html`, halaman galat Laravel 1,1 MB), bukan PDF.

Alur pengguna utama PRD §7.1 mensyaratkan Admin Satker **melihat pratinjau berwatermark DRAFT sebelum menekan Ajukan**. Langkah itu tidak dapat dijalankan sama sekali oleh peran yang seharusnya menjalankannya.

Dicatat terpisah dari BUG-11 karena dampaknya berbeda: memperbaiki `TenantScope` akan menyelesaikannya, tetapi pratinjau PDF perlu diuji ulang tersendiri untuk memastikan watermark dan ketiadaan gambar tanda tangan (F7.20) benar-benar sesuai.

---

## Yang sudah terverifikasi berjalan benar ✅

Bagian ini dicatat agar cakupan uji terlihat jelas.

| Fitur | Bukti |
|---|---|
| Login gagal ditolak dengan pesan Indonesia | Pesan "Kredensial yang diberikan tidak cocok…" tampil |
| F1.4 wajib ganti kata sandi saat login pertama | Keempat peran diarahkan ke `/password/change`; akses ke `/admin/employees` sebelum ganti sandi **diblokir** |
| Pembatasan laju login | 429 aktif setelah 5 percobaan (tampilannya → BUG-10) |
| Dashboard Ketua & Admin Yayasan | Kartu statistik, sebaran per satuan kerja, dan kartu antrean render bersih tanpa error console |
| Portal GTK (`/portal`) | Peran `employee` diarahkan otomatis ke portal; kelengkapan profil 61% + daftar kekurangan (terterjemah) |
| F2.1–F2.3b CRUD Satuan Kerja | `SD2` tersimpan; kode duplikat ditolak dengan pesan validasi |
| CRUD Jabatan, Status Kepegawaian, Referensi Tugas Tambahan | Ketiganya tersimpan dan tampil di daftar |
| K11 lima kode jenis SK | `SK-PPT`, `SK-PPJ`, `SK-TT`, `SK-MUT`, `SK-BHT` tersedia |
| F2.9–F2.11 konsideran per jenis SK | Field Mengingat / Menimbang / Memperhatikan dapat disunting dari antarmuka |
| Matriks hak akses §4.2 — Pengaturan | `unit_admin` ditolak 403 saat membuka `/admin/settings` |
| Unggah logo yayasan | Berhasil, tersimpan ke `storage/logo/…` |
| F7.19 konfirmasi kata sandi | Unggah tanda tangan tanpa kata sandi ditolak |
| F7.16 gambar tanda tangan tidak terekspos HTTP | Lima jalur tebakan (`/storage/signature/…`, `/signature.png`, dll.) tidak ada yang mengembalikan 200 |
| F3.1 CRUD GTK + F3.2a NIGY otomatis | GTK "Siti Nurhaliza, S.Pd." tersimpan dengan NIGY **2021SD1001** (sesuai `{tahun_masuk}{kode_satker}{urut}` dari TMT Yayasan 2021) |
| F3.4 validasi MIME dari isi berkas | Berkas `.pdf` yang isinya teks biasa **ditolak** |
| F3.10 ekspor Excel | 200, `spreadsheetml.sheet`, 6.851 byte |
