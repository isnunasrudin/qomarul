# PRD — SIMQOH
## Sistem Informasi Manajemen Qomarul Hidayah
### Yayasan Pondok Pesantren Qomarul Hidayah, Gondang Tugu Trenggalek

> **Modul v1: Kepegawaian & Surat Keputusan.** Nama sistem sengaja netral terhadap domain agar modul berikutnya — pencatatan surat keluar, dan apa pun setelahnya — dapat bergabung tanpa membuat namanya keliru. Setiap layar menampilkan nama modul yang sedang dibuka, mis. **SIMQOH — Kepegawaian**.

| | |
|---|---|
| **Versi** | 1.7 |
| **Tanggal** | 12 Agustus 2026 |
| **Status** | 22 keputusan kunci terkonfirmasi (§12); **F1 selesai** — eksekusi memakai **Laravel 13.25** (rilis terbaru saat mulai, bukan Laravel 11; tanpa perubahan arsitektur) |
| **Bahasa** | Kode & basis data: Inggris · Antarmuka & dokumen: Indonesia (§6.1) |
| **Pemilik Produk** | Bidang Personalia & SDM YPP Qomarul Hidayah |
| **Stack** | Laravel 13 + Inertia.js + Vue 3 + Tailwind CSS + MySQL/MariaDB |

---

## 1. Latar Belakang

Yayasan Pondok Pesantren Qomarul Hidayah menaungi beberapa satuan kerja (SD, SMP, SMA, SMK, TPQ, dan lainnya) dengan total ratusan Guru dan Tenaga Kependidikan (GTK). Saat ini pengelolaan data kepegawaian dan penerbitan Surat Keputusan (SK) pengangkatan dilakukan secara manual — pengetikan ulang per orang, penomoran tidak terpusat, arsip tersebar, dan tanda tangan basah membutuhkan kehadiran fisik Ketua Yayasan.

Masalah utama yang ingin diselesaikan:

1. **Pengetikan SK berulang.** Setiap awal tahun pelajaran, ratusan SK perpanjangan harus diketik satu per satu dari template Word.
2. **Data pegawai tidak terpusat.** Data pendidikan, berkas ijazah, dan foto tersimpan di masing-masing satuan kerja tanpa standar.
3. **Penomoran SK rawan bentrok.** Tidak ada register terpusat, nomor bisa terduplikasi antar satuan kerja.
4. **Tidak ada jaminan keaslian dokumen.** SK hasil scan mudah dipalsukan; tidak ada mekanisme verifikasi.
5. **Masa kerja dihitung manual.** Perhitungan masa kerja keseluruhan (TMT Yayasan → sekarang) rentan salah.

---

## 2. Tujuan & Metrik Keberhasilan

### 2.1 Tujuan Produk

| # | Tujuan |
|---|---|
| T1 | Menyediakan satu sumber kebenaran (*single source of truth*) data GTK seluruh satuan kerja |
| T2 | Mengotomatiskan penerbitan SK — dari draft, persetujuan, penomoran, hingga PDF bertanda tangan |
| T3 | Memungkinkan penerbitan SK massal (*batch*) untuk satu angkatan/periode sekaligus |
| T4 | Menjamin keaslian SK melalui tanda tangan digital dan verifikasi publik berbasis QR |
| T5 | Memberi Ketua Yayasan visibilitas real-time atas komposisi SDM seluruh satuan kerja |

### 2.2 Metrik Keberhasilan

| Metrik | Baseline | Target (6 bulan pasca-rilis) |
|---|---|---|
| Waktu penerbitan 1 SK (draft → PDF final) | ~25 menit | < 3 menit |
| Waktu penerbitan 200 SK perpanjangan tahunan | ~2 minggu | < 1 hari kerja |
| Kelengkapan profil GTK (semua field wajib + berkas) | tidak terukur | ≥ 90% |
| GTK yang pernah masuk ke portal mandiri | 0 | ≥ 80% |
| Insiden nomor SK duplikat | beberapa/tahun | 0 |
| SK terverifikasi lewat QR oleh pihak eksternal | 0 | tersedia & terpakai |

---

## 3. Ruang Lingkup

### 3.1 Termasuk dalam Lingkup (v1)

- Manajemen pengguna & multi-role (Ketua Yayasan, Admin Yayasan, Admin Satuan Kerja)
- Master data: satuan kerja, jabatan, jenis SK, referensi tugas tambahan, jenjang pendidikan, status kepegawaian
- Profil GTK lengkap: data pribadi, kepegawaian, riwayat pendidikan + unggah berkas, foto
- Manajemen tugas tambahan (relasi GTK ↔ referensi tugas tambahan, berperiode)
- Manajemen SK: draft, pengajuan, verifikasi, persetujuan, penerbitan, pembatalan, riwayat
- Penomoran SK terpusat dengan format terkonfigurasi
- Generate PDF SK (Barryvdh\DomPDF) dari template Blade
- Tanda tangan digital self-signed (PKCS#12) + QR code + halaman verifikasi publik
- Batch generate SK (multi-pegawai, satu template, satu periode)
- Dashboard & laporan dasar, ekspor Excel
- Audit log seluruh aksi kritis
- **Portal mandiri GTK**: login, melihat data & SK sendiri, menyunting data pribadi, mengunggah berkas dan arsip SK lama

### 3.2 Di Luar Lingkup (v1)

- Penggajian / honorarium (hanya referensi, tidak ada perhitungan)
- Presensi & jam mengajar
- Penilaian kinerja (PKG/SKP)
- **Pencatatan surat keluar** — modul berikutnya (v2); v1 hanya menyiapkan fondasi agar penambahannya murah (§9)
- Aplikasi mobile — direncanakan v2
- Integrasi Dapodik / SIMPATIKA — direncanakan v2
- Notifikasi email dan WhatsApp — v1 hanya notifikasi dalam aplikasi
- Delegasi tanda tangan / penanda tangan pengganti (Plt) — tidak direncanakan
- Tanda tangan digital tersertifikasi PSrE (BSrE/Privy) — v2, arsitektur disiapkan agar mudah diganti

---

## 4. Persona & Peran

### 4.1 Persona

| Persona | Deskripsi | Kebutuhan Utama |
|---|---|---|
| **Ketua Yayasan** | Penandatangan seluruh SK, jarang di kantor, butuh akses cepat | Antrean persetujuan ringkas, tanda tangan sekali klik (termasuk massal), ringkasan SDM |
| **Admin Yayasan** (Bid. Personalia & SDM) | Operator utama, memegang data seluruh satuan kerja | Verifikasi & penomoran SK, batch generate, master data, laporan |
| **Admin Satuan Kerja** | Operator di SD/SMP/SMA/SMK/TPQ | Kelola data GTK unit sendiri, ajukan draft SK, cetak SK yang sudah terbit |
| **GTK** (Guru & Tenaga Kependidikan) | Pemilik data, ratusan orang, melek teknologi bervariasi | Melengkapi data pribadi & berkasnya sendiri, mengunggah arsip SK lama, mengunduh SK yang sudah terbit |
| **Pihak Eksternal** (Dinas, bank, verifikator) | Menerima SK cetak/PDF | Memastikan SK asli via pemindaian QR |

### 4.2 Matriks Hak Akses

| Kapabilitas | Ketua Yayasan | Admin Yayasan | Admin Satker | GTK |
|---|:---:|:---:|:---:|:---:|
| Lihat data GTK seluruh satuan kerja | ✅ | ✅ | ❌ (hanya unitnya) | ❌ (hanya dirinya) |
| Tambah/ubah data GTK | ❌ | ✅ | ✅ (unitnya) | ⚠️ (data pribadi dirinya saja) |
| Ubah data kepegawaian (satker, jabatan, TMT, status) | ❌ | ✅ | ✅ (unitnya) | ❌ |
| Ubah NIGY | ❌ | ✅ | ⚠️ (usul ke Admin Yayasan) | ❌ **read-only** |
| Hapus data GTK | ❌ | ✅ | ❌ (ajukan hapus) | ❌ |
| Unggah berkas pribadi & ijazah | ❌ | ✅ | ✅ (unitnya) | ✅ (miliknya) |
| Unggah arsip SK lama | ❌ | ✅ | ✅ (unitnya) | ✅ (miliknya, perlu verifikasi) |
| Kelola master data (satker, jabatan, jenis SK) | 👁️ | ✅ | ❌ | ❌ |
| Kelola referensi tugas tambahan | 👁️ | ✅ | ❌ | ❌ |
| Tetapkan tugas tambahan ke GTK | 👁️ | ✅ | ✅ (unitnya, perlu verifikasi) | ❌ |
| Buat draft SK | ❌ | ✅ | ✅ (unitnya) | ❌ |
| Verifikasi & beri nomor SK | ❌ | ✅ | ❌ | ❌ |
| **Setujui & tanda tangani SK** | ✅ | ❌ | ❌ | ❌ |
| Batch generate SK | ❌ | ✅ | ⚠️ (ajukan batch unitnya) | ❌ |
| Batalkan / cabut SK terbit | ✅ | ⚠️ (ajukan) | ❌ | ❌ |
| Unduh PDF SK bertanda tangan | ✅ | ✅ | ✅ (unitnya) | ✅ (miliknya) |
| Kelola sertifikat penandatanganan | ❌ | ✅ | ❌ | ❌ |
| Kelola pengguna & peran | 👁️ | ✅ | ⚠️ (usul akun unitnya) | ❌ |
| Lihat audit log | ✅ | ✅ | ❌ | ❌ |
| Dashboard yayasan (agregat) | ✅ | ✅ | ❌ (agregat unitnya) | ❌ (ringkasan dirinya) |

Keterangan: ✅ penuh · 👁️ baca saja · ⚠️ terbatas/perlu persetujuan · ❌ tidak ada akses

**Aturan tenancy:** setiap query data GTK dan SK oleh Admin Satker otomatis difilter `work_unit_id` melalui *global scope* Eloquent, ditegakkan lagi di lapisan Policy. Tidak boleh mengandalkan filter di frontend.

**Nilai enum peran:** `foundation_head` (Ketua Yayasan), `foundation_admin` (Admin Yayasan), `unit_admin` (Admin Satuan Kerja), `employee` (GTK) — label Indonesia disediakan lewat berkas terjemahan.

**Tenancy peran `employee`:** seluruh query dibatasi pada `employees.id` miliknya sendiri, bukan pada satuan kerja. GTK tidak pernah dapat melihat data rekan sekerja, termasuk di unit yang sama.

---

## 5. Kebutuhan Fungsional

### 5.1 Modul Autentikasi & Manajemen Pengguna

| ID | Kebutuhan | Prioritas |
|---|---|---|
| F1.1 | Login dengan email/username + kata sandi, *rate limit* 5 percobaan/menit | Must |
| F1.2 | Satu pengguna memiliki satu peran utama; Admin Satker terikat pada satu satuan kerja, GTK terikat pada satu record `employees` | Must |
| F1.2a | Akun GTK dibuat massal oleh Admin Yayasan dari data GTK yang sudah ada (username = NIGY, kata sandi awal acak), bukan lewat pendaftaran mandiri | Must |
| F1.2b | Akun GTK otomatis dinonaktifkan ketika `employees.is_active = false` (pensiun, mutasi keluar, wafat) | Must |
| F1.3 | Admin Yayasan dapat membuat, menonaktifkan, dan mereset kata sandi pengguna | Must |
| F1.4 | Kebijakan kata sandi: min. 8 karakter, wajib ganti pada login pertama | Must |
| F1.5 | Sesi otomatis berakhir setelah 8 jam tidak aktif | Should |
| F1.6 | 2FA (TOTP) wajib untuk peran Ketua Yayasan | Should |
| F1.7 | Riwayat login (IP, perangkat, waktu) per pengguna | Could |

### 5.2 Modul Master Data

#### 5.2.1 Satuan Kerja
Field: kode, nama, jenjang (TPQ/SD/SMP/SMA/SMK/Pondok/Kantor Yayasan), NPSN, alamat, nama kepala satuan kerja, NIGY kepala, telepon, email, logo, status aktif.

| ID | Kebutuhan | Prioritas |
|---|---|---|
| F2.1 | CRUD satuan kerja oleh Admin Yayasan | Must |
| F2.2 | Satuan kerja tidak dapat dihapus bila masih memiliki GTK aktif — hanya dinonaktifkan | Must |
| F2.3 | Kode satuan kerja dipakai sebagai komponen nomor SK | Must |
| F2.3a | Kode mengikuti pola jenjang + angka urut bila ada lebih dari satu unit pada jenjang yang sama (mis. `SD1`, `SD2`, `TPQ1`, `TPQ2`); jenjang berunit tunggal cukup `SMP`, `SMK` | Must |
| F2.3b | Kode bersifat unik, huruf besar tanpa spasi, dan **tidak dapat diubah** setelah dipakai pada SK terbit — mengubahnya akan memutus konsistensi seri penomoran | Must |

#### 5.2.2 Jabatan
Field: kode, nama (Guru Kelas, Guru Mapel, Kepala Sekolah, Tenaga Administrasi, Pustakawan, Penjaga, dll), kelompok (Pendidik / Tenaga Kependidikan), status aktif.

> **Berlaku seragam untuk seluruh jenjang.** TPQ memakai master jabatan dan template SK yang sama persis dengan satuan kerja formal (SD/SMP/SMA/SMK) — tidak ada percabangan master data maupun template per jenjang. Ini menyederhanakan F1 dan F4 secara signifikan.

#### 5.2.3 Referensi Tugas Tambahan
Field: kode, nama (Wali Kelas, Kepala Perpustakaan, Kepala Laboratorium, Pembina OSIS, Bendahara, Operator Dapodik, Wakil Kepala Bidang Kurikulum, dll), jenjang yang berlaku (multi-select), ekuivalensi jam (opsional), kuota maksimal per satuan kerja (opsional, `null` = tanpa batas), butuh SK tersendiri (boolean), status aktif.

| ID | Kebutuhan | Prioritas |
|---|---|---|
| F2.4 | CRUD referensi tugas tambahan oleh Admin Yayasan | Must |
| F2.5 | Referensi terpakai tidak boleh dihapus — hanya dinonaktifkan (arsip relasi tetap utuh) | Must |
| F2.6 | Validasi kuota: sistem memperingatkan bila kuota per satuan kerja terlampaui pada periode aktif | Should |
| F2.7 | Batasi referensi yang tampil sesuai jenjang satuan kerja pengguna | Should |

#### 5.2.4 Jenis SK
Field: kode, nama, *view* template Blade, format nomor, konsideran (mengingat / menimbang / memperhatikan), wajib TMT (boolean), aktif.

**Kode yang ditetapkan:**

| Kode | Nama |
|---|---|
| `SK-PPT` | SK Pengangkatan |
| `SK-PPJ` | SK Perpanjangan |
| `SK-TT` | SK Tugas Tambahan |
| `SK-MUT` | SK Mutasi |
| `SK-BHT` | SK Pemberhentian |

| ID | Kebutuhan | Prioritas |
|---|---|---|
| F2.9 | Setiap jenis SK menyimpan teks konsideran sendiri: `mengingat` (teks), `menimbang` (daftar berurutan), `memperhatikan` (teks) | Must |
| F2.10 | Konsideran dapat disunting Admin Yayasan lewat antarmuka, tanpa mengubah berkas Blade | Must |
| F2.11 | Butir `menimbang` dapat ditambah, dihapus, dan diurutkan ulang; penomoran (1., 2., 3.) dihasilkan otomatis saat render | Must |
| F2.12 | Perubahan konsideran **tidak** memengaruhi SK yang sudah terbit — teksnya ikut masuk `snapshot_data` | Must |

#### 5.2.5 Referensi Lain
Jenjang pendidikan (SD s.d. S3), status kepegawaian (Tetap Yayasan, Kontrak, Honorer, PNS DPK), status pernikahan, agama, golongan darah.

#### 5.2.6 Pengaturan Yayasan
Nama yayasan, alamat, akte notaris, nomor SK Menkumham, logo, nama & jabatan Ketua Yayasan, berkas gambar tanda tangan, kota penetapan default, daftar tembusan default, **format NIGY** beserta panjang padding nomor urut.

| ID | Kebutuhan | Prioritas |
|---|---|---|
| F2.8 | Seluruh teks kop dan tembusan pada PDF diambil dari tabel `settings`, bukan *hard-code* di Blade | Must |

### 5.3 Modul Data GTK

#### 5.3.1 Data Pribadi
NIGY (unik, wajib), NIK, NUPTK, NIP (jika ada), nama lengkap, gelar depan, gelar belakang, jenis kelamin, tempat lahir, tanggal lahir, agama, status pernikahan, nama ibu kandung, alamat KTP, alamat domisili, RT/RW, desa, kecamatan, kabupaten, provinsi, kode pos, nomor HP, email, NPWP, nomor rekening + bank, golongan darah, foto.

#### 5.3.2 Data Kepegawaian
Satuan kerja, jabatan, status kepegawaian, TMT Yayasan, TMT Satuan Kerja, nomor SK pertama, mata pelajaran diampu, status aktif/nonaktif (pensiun, mengundurkan diri, mutasi, wafat), tanggal berhenti + alasan.

#### 5.3.3 Riwayat Pendidikan
Jenjang, nama institusi, jurusan/program studi, tahun masuk, tahun lulus, nomor ijazah, tanggal ijazah, IPK, penanda **pendidikan tertinggi** (satu per GTK), berkas ijazah, berkas transkrip.

#### 5.3.4 Berkas Kepegawaian
Kategori: KTP, KK, Ijazah, Transkrip, Sertifikat Pendidik, Sertifikat Pelatihan, SK Lama, Pas Foto, NPWP, Buku Rekening, Lainnya.

| ID | Kebutuhan | Prioritas |
|---|---|---|
| F3.1 | CRUD data GTK; Admin Satker hanya untuk unitnya sendiri | Must |
| F3.2 | NIGY unik se-yayasan, divalidasi di database dan aplikasi | Must |
| F3.2a | NIGY **dihasilkan otomatis** saat GTK baru disimpan, mengikuti format yang dapat diatur Admin Yayasan | Must |
| F3.2b | Format default: `{tahun_masuk}{kode_satker}{urut}` → `2026SMK001`, `2026SD1042`. Token tersedia: `{tahun_masuk}`, `{bulan_masuk}`, `{kode_satker}`, `{kode_jenjang}`, `{urut}` (padding dapat diatur) | Must |
| F3.2c | Nomor urut NIGY direset **per tahun per satuan kerja**, dialokasikan atomik lewat `number_counters` dengan kunci `nigy:{kode_satker}:{tahun}` | Must |
| F3.2d | Admin Yayasan **dapat menimpa NIGY secara manual** (mis. menyesuaikan NIGY lama saat migrasi); nilai manual tetap divalidasi unik dan tidak menaikkan penghitung | Must |
| F3.2e | Perubahan NIGY tercatat di audit log beserta nilai lama → baru dan alasannya | Must |
| F3.2f | NIGY yang sudah tercetak pada SK terbit **tidak boleh diubah** — sistem memblokir dengan pesan yang menyebutkan nomor SK terkait | Must |
| F3.2g | Mengubah satuan kerja GTK **tidak** mengubah NIGY-nya — NIGY melekat seumur hidup pada orangnya | Must |
| F3.3 | Unggah foto: JPG/PNG, maks 2 MB, otomatis *crop* rasio 3:4 dan dikompres | Must |
| F3.4 | Unggah berkas: PDF/JPG/PNG, maks 5 MB per berkas, validasi MIME asli (bukan hanya ekstensi) | Must |
| F3.5 | Berkas disimpan di disk privat; akses hanya lewat *signed URL* berbatas waktu | Must |
| F3.6 | Indikator kelengkapan profil (persentase + daftar field/berkas yang kurang) | Must |
| F3.7 | Masa kerja dihitung otomatis dari TMT Yayasan ke tanggal acuan, dalam tahun & bulan | Must |
| F3.8 | Pendidikan tertinggi otomatis mengisi `$education_level`/`$major` pada SK | Must |
| F3.9 | Impor massal GTK dari Excel dengan template & pratinjau validasi sebelum simpan | Should |
| F3.10 | Ekspor daftar GTK ke Excel dengan filter aktif | Should |
| F3.11 | Riwayat perubahan data GTK (siapa, kapan, field apa, nilai lama → baru) | Should |
| F3.12 | Pencarian cepat lintas NIGY, nama, NIK, NUPTK | Must |
| F3.13 | Mutasi antar satuan kerja: memindahkan GTK sekaligus mencatat riwayat & memicu SK Mutasi | Could |

#### 5.3.5 Portal Mandiri GTK

GTK dapat masuk ke sistem untuk melengkapi datanya sendiri. Ini memindahkan sebagian besar beban entri data dari admin ke pemilik datanya — sekaligus menjadi kunci tercapainya target kelengkapan profil 90%.

| ID | Kebutuhan | Prioritas |
|---|---|---|
| F3.14 | GTK melihat profil, riwayat pendidikan, tugas tambahan, dan seluruh SK miliknya sendiri | Must |
| F3.15 | GTK **dapat menyunting data pribadi**: alamat, kontak, status pernikahan, data bank, foto, dan riwayat pendidikan | Must |
| F3.16 | GTK **tidak dapat menyunting**: NIGY, satuan kerja, jabatan, status kepegawaian, TMT Yayasan, TMT Satuan Kerja — seluruhnya administratif dan menentukan isi SK | Must |
| F3.17 | GTK dapat mengunggah berkas miliknya (KTP, KK, ijazah, transkrip, sertifikat) | Must |
| F3.18 | GTK dapat mengunggah arsip SK lama miliknya; record masuk sebagai `is_legacy` berstatus **menunggu verifikasi** admin sebelum tampil di riwayat resmi | Must |
| F3.19 | GTK mengunduh PDF SK miliknya yang sudah terbit | Must |
| F3.20 | Indikator kelengkapan profil tampil menonjol beserta daftar yang masih kurang | Must |
| F3.21 | Perubahan oleh GTK ditandai "diubah oleh yang bersangkutan" pada tampilan admin, dengan penanda belum ditinjau | Must |
| F3.22 | Perubahan data pribadi berlaku langsung tanpa persetujuan; keamanannya bersandar pada snapshot SK (§5.5) dan audit log, bukan pada pemblokiran | Must |
| F3.23 | GTK tidak dapat melihat data GTK lain dalam bentuk apa pun, termasuk daftar dan laporan | Must |
| F3.24 | Halaman portal ramah ponsel — mayoritas GTK akan mengaksesnya dari HP | Must |

> **Batasan yang disengaja:** perubahan data pribadi tidak melalui persetujuan admin. Risikonya rendah karena nilai yang tercetak pada SK dibekukan saat penerbitan (§5.5, F5.3) dan Admin Yayasan tetap memverifikasi setiap SK sebelum penomoran — sehingga data yang keliru tertangkap sebelum menjadi dokumen resmi. Sebaliknya, mewajibkan persetujuan untuk tiap perubahan alamat justru mengembalikan beban ke admin, yang merupakan alasan utama portal ini ada.

### 5.4 Modul Tugas Tambahan

| ID | Kebutuhan | Prioritas |
|---|---|---|
| F4.1 | Tetapkan satu atau lebih tugas tambahan ke seorang GTK, berdasarkan referensi (§5.2.3) | Must |
| F4.2 | Setiap penetapan memiliki: referensi, satuan kerja, tahun pelajaran, TMT mulai, TMT selesai, keterangan (mis. "Wali Kelas VII-A"), status | Must |
| F4.3 | Cegah tumpang tindih: satu GTK tidak boleh memegang referensi tugas yang sama pada periode yang beririsan | Must |
| F4.4 | Penetapan dapat ditautkan ke SK penerbitnya (relasi ke tabel SK) | Must |
| F4.4a | Bila referensi bertanda `requires_decree = true`, sistem menawarkan pembuatan **SK Tugas Tambahan tersendiri**; bila `false`, tugas tambahan hanya tercatat sebagai data dan dapat ikut tercetak pada SK Pengangkatan | Must |
| F4.5 | Riwayat tugas tambahan per GTK, ditampilkan kronologis | Must |
| F4.6 | Daftar pemegang tugas tambahan per satuan kerja per tahun pelajaran | Must |
| F4.7 | Peringatan tugas tambahan yang akan berakhir dalam 30 hari | Should |
| F4.8 | Penetapan massal (mis. pilih 12 guru → tetapkan "Wali Kelas" TP 2026/2027) | Should |

### 5.5 Modul Manajemen SK

#### 5.5.1 Status & Alur Kerja

```
  draft ──ajukan──> submitted ──verifikasi + alokasi nomor──> verified ──ttd──> issued
    ^                   │                                        │                │
    │<──── rejected ────┘<──────────── rejected ─────────────────┘                │
    │                                                                             │
  (dapat diubah lagi)                                             cancelled / superseded
```

| Nilai enum | Label di antarmuka | Aktor yang dapat memicu | Keterangan |
|---|---|---|---|
| `draft` | Draft | Admin Satker / Admin Yayasan | Bebas diubah, belum bernomor |
| `submitted` | Diajukan | Admin Satker | Terkunci dari perubahan oleh pengaju |
| `rejected` | Ditolak | Admin Yayasan / Ketua Yayasan | Wajib disertai alasan; kembali dapat diubah |
| `verified` | Terverifikasi | Admin Yayasan | **Nomor SK dan nomor registrasi dialokasikan di sini** |
| `issued` | Diterbitkan | Ketua Yayasan | PDF final dibuat, ditandatangani digital, dikunci permanen |
| `cancelled` | Dibatalkan | Ketua Yayasan | Alasan wajib; PDF ditandai batal, verifikasi publik menampilkan status batal |
| `superseded` | Diganti | otomatis | Saat SK pengganti diterbitkan dan menunjuk SK ini |

| ID | Kebutuhan | Prioritas |
|---|---|---|
| F5.1 | Buat SK untuk satu GTK, memilih jenis SK; field otomatis terisi dari data GTK | Must |
| F5.2 | Field yang dapat ditimpa manual per SK (mis. jabatan pada SK berbeda dari jabatan saat ini) | Must |
| F5.3 | Snapshot data: nilai yang dicetak pada SK dibekukan saat penerbitan — perubahan data GTK kemudian **tidak** mengubah SK terbit | Must |
| F5.4 | Pratinjau PDF pada status draft (dengan *watermark* "DRAFT", tanpa tanda tangan) | Must |
| F5.5 | Alur persetujuan tiga tahap sesuai diagram di atas | Must |
| F5.5a | **Hanya Ketua Yayasan** yang dapat menerbitkan SK — tidak ada delegasi, Plt, maupun penanda tangan pendamping. Transisi ke `diterbitkan` ditutup bagi peran lain di lapisan Policy | Must |
| F5.6 | Penolakan wajib menyertakan alasan, dan memberi notifikasi ke pengaju | Must |
| F5.7 | SK berstatus `diterbitkan` bersifat *immutable* — koreksi hanya lewat pembatalan + penerbitan SK pengganti | Must |
| F5.8 | Riwayat alur (siapa, aksi, waktu, catatan) tampil pada halaman detail SK | Must |
| F5.9 | Unduh ulang PDF kapan pun; berkas disimpan, tidak digenerate ulang | Must |
| F5.10 | Daftar SK dengan filter: satuan kerja, jenis, status, tahun pelajaran, rentang tanggal, pencarian nama/NIGY | Must |
| F5.11 | Cetak lampiran daftar penerima untuk SK kolektif | Could |
| F5.12 | Masa berlaku SK = **satu tahun pelajaran**; sistem menandai SK yang berakhir pada akhir tahun pelajaran berjalan | Must |
| F5.12a | Dashboard menampilkan pengingat SK yang akan berakhir, menjadi umpan langsung untuk batch perpanjangan berikutnya | Should |

#### 5.5.2 Penomoran SK

Format **default yang ditetapkan**, dapat ditimpa per jenis SK:

```
{nomor}/{kode_jenis}/{kode_satker}/YPP-QH/{bulan_romawi}/{tahun}

Contoh hasil:
042/SK-PPT/SMK/YPP-QH/VII/2026     (SK Pengangkatan, SMK)
013/SK-TT/SD/YPP-QH/VII/2026       (SK Tugas Tambahan, SD)
```

Token yang tersedia: `{nomor}` (urut, padding dapat diatur), `{kode_jenis}`, `{kode_satker}`, `{bulan_romawi}`, `{bulan}`, `{tahun}`, `{tahun_pelajaran}`.

Karena format memuat `{kode_jenis}` dan `{kode_satker}`, **seri nomor terpisah per kombinasi jenis SK × satuan kerja × tahun kalender** — kunci penghitung berbentuk `decree:{kode_jenis}:{kode_satker}:{tahun}`.

> Token pada format nomor sengaja **tetap Bahasa Indonesia**, karena string format ini disunting Admin Yayasan lewat antarmuka — jadi tergolong teks yang dilihat pengguna, bukan identifier kode.

> **Konsekuensi reset per tahun kalender yang perlu disadari:** batch perpanjangan tahunan terbit Juli, sedangkan penghitung direset Januari. SK susulan untuk tahun pelajaran yang sama — misalnya guru yang masuk Januari 2027 — akan memakai seri nomor tahun berikutnya (`001/SK-PPJ/SMK/YPP-QH/I/2027`), terpisah dari batch Juli 2026. Ini konsisten dengan nomor yang tercetak, tetapi berarti **satu tahun pelajaran dapat memuat dua seri nomor**. Kolom `academic_year` tetap disimpan agar pengelompokan laporan tidak bergantung pada nomor.

| ID | Kebutuhan | Prioritas |
|---|---|---|
| F5.13 | Nomor urut dialokasikan atomik (`SELECT ... FOR UPDATE` dalam transaksi) — anti bentrok pada batch/konkuren | Must |
| F5.14 | Nomor urut **direset setiap 1 Januari** (tahun kalender), selaras dengan token `{tahun}` pada format nomor | Must |
| F5.15 | Nomor registrasi (`E-xxxx` pada pojok kanan bawah) berurutan global lintas seluruh SK | Must |
| F5.16 | Nomor tidak pernah digunakan ulang; SK batal tetap memegang nomornya | Must |
| F5.17 | Nomor manual diperbolehkan untuk migrasi SK lama, ditandai `is_legacy` | Should |

#### 5.5.4 Arsip SK Lama (Migrasi)

| ID | Kebutuhan | Prioritas |
|---|---|---|
| F5.27 | Unggah PDF hasil pindaian SK lama dan tautkan ke GTK; berkas PDF adalah **satu-satunya field wajib** | Must |
| F5.28 | Metadata (nomor SK, jenis, tanggal penetapan, TMT, tahun pelajaran) bersifat **opsional** dan dapat dilengkapi bertahap | Must |
| F5.29 | Record migrasi ditandai `is_legacy = true`, berstatus `issued`, tanpa tanda tangan digital maupun QR | Must |
| F5.30 | Record migrasi dikecualikan dari alokasi penomoran otomatis dan tidak menaikkan `number_counters` | Must |
| F5.31 | Daftar SK dapat difilter untuk memisahkan arsip migrasi dari SK terbitan sistem | Should |
| F5.32 | Unggah massal arsip: banyak PDF sekaligus, pemetaan ke GTK lewat NIGY pada nama berkas | Should |
| F5.33 | Arsip yang diunggah GTK sendiri (F3.18) masuk antrean verifikasi Admin Satker/Yayasan sebelum diakui sebagai riwayat resmi | Must |

#### 5.5.3 Batch Generate SK

| ID | Kebutuhan | Prioritas |
|---|---|---|
| F5.18 | Buat batch: pilih jenis SK, tahun pelajaran, TMT, tanggal penetapan, lalu pilih GTK penerima | Must |
| F5.19 | Seleksi penerima lewat filter (satuan kerja, jabatan, status kepegawaian) + centang manual | Must |
| F5.20 | Validasi pra-proses: tampilkan GTK yang datanya tidak lengkap dan blokir/lewati dengan pilihan pengguna | Must |
| F5.21 | Proses berjalan di *queue* dengan indikator progres real-time | Must |
| F5.22 | Ketua Yayasan menyetujui & menandatangani **seluruh batch sekali klik**, dengan pratinjau daftar | Must |
| F5.23 | Unduh hasil batch sebagai satu ZIP, atau satu PDF gabungan | Must |
| F5.24 | Kegagalan per item tidak menggagalkan batch; laporan ringkas berhasil/gagal + alasan | Must |
| F5.25 | Batch dapat dibatalkan selama belum ditandatangani | Should |
| F5.26 | Batas aman: maksimal 500 SK per batch | Should |

### 5.6 Modul Generate PDF

Berbasis `barryvdh/laravel-dompdf`, template Blade seperti `sk.blade.php` terlampir.

| ID | Kebutuhan | Prioritas |
|---|---|---|
| F6.1 | Satu template Blade per jenis SK, dipetakan lewat master Jenis SK | Must |
| F6.2 | Font Arial (reguler + bold) di-*embed*; ukuran kertas **F4/Folio (215 × 330 mm)** | Must |
| F6.3 | Gambar (logo, tanda tangan) dirujuk lewat path absolut server, bukan URL publik | Must |
| F6.4 | Gambar tanda tangan hanya disisipkan bila status `issued` (`$is_signed`) | Must |
| F6.5 | PDF draft diberi watermark diagonal "DRAFT — BUKAN DOKUMEN RESMI" | Must |
| F6.6 | Berkas final disimpan permanen dengan penamaan `decrees/{year}/{registration_number}-{nigy}.pdf` | Must |
| F6.7 | Waktu generate 1 PDF < 3 detik pada spesifikasi server target | Should |
| F6.8 | Konsideran dirender dari data jenis SK (§5.2.4), bukan dari teks statis di Blade | Must |
| F6.9 | Editor template berbasis web dengan daftar variabel yang tersedia | Could |

**Variabel wajib tersedia bagi template** (diturunkan dari `sk.blade.php`):

Nama variabel memakai Bahasa Inggris mengikuti konvensi §6.1; **isi yang tercetak tetap Bahasa Indonesia**, termasuk label statis di dalam template.

| Variabel | Nama lama di `sk.blade.php` | Sumber |
|---|---|---|
| `$decree_number` | `$nomor_sk` | alokasi penomoran (§5.5.2) |
| `$registration_number` | `$nomor_reg` | nomor registrasi global |
| `$effective_date` | `$tmt` | input SK — TMT pengangkatan |
| `$name` | `$nama` | GTK (gelar depan + nama + gelar belakang) |
| `$nigy` | `$nigy` | GTK |
| `$birth_place`, `$birth_date` | `$tempat_lahir`, `$tanggal_lahir` | GTK (tanggal diformat lokal Indonesia) |
| `$education_level`, `$major` | `$pendidikan`, `$jurusan` | riwayat pendidikan tertinggi |
| `$position` | `$jabatan` | jabatan GTK, dapat ditimpa per SK |
| `$appointed_as` | `$diangkat_sebagai` | input SK |
| `$work_unit` | `$satker` | nama satuan kerja |
| `$foundation_start_date`, `$unit_start_date` | `$tmt_yayasan`, `$tmt_satker` | data kepegawaian GTK |
| `$service_years`, `$service_months` | `$masa_kerja_tahun`, `$masa_kerja_bulan` | terhitung dari TMT Yayasan ke tanggal penetapan |
| `$issued_place`, `$issued_date` | `$ditetapkan_di`, `$ditetapkan_tanggal` | input SK / default pengaturan yayasan |
| `$is_signed` | `$sudah_ttd` | `true` bila status `issued` |
| `$chairman_name` | `$kepala_sekolah` ⚠️ | pengaturan yayasan — nama Ketua Yayasan |
| `$qr_data_uri` | — | QR verifikasi (baru) |
| `$consideration_recalling` | — | master jenis SK, "Mengingat" (baru) |
| `$consideration_weighing` | — | master jenis SK, "Menimbang" — larik, dinomori otomatis saat render (baru) |
| `$consideration_observing` | — | master jenis SK, "Memperhatikan" (baru) |

> **Catatan teknis untuk implementasi:** pada `sk.blade.php` saat ini, nama penanda tangan menggunakan variabel `$kepala_sekolah` padahal yang dicetak adalah Ketua Yayasan. Ganti menjadi `$ketua_yayasan` saat migrasi template agar tidak menyesatkan. Selain itu, `src` gambar `/public/assets/img/...` harus diubah ke path absolut filesystem (`public_path(...)`) agar DomPDF dapat memuatnya tanpa bergantung pada HTTP.

### 5.7 Modul Tanda Tangan Digital & Verifikasi

**Pendekatan:** tanda tangan kriptografis *self-signed* (PKCS#12 / X.509) ditanamkan ke PDF, **dilengkapi** QR code dan halaman verifikasi publik.

Alasan kombinasi: tanda tangan self-signed memberi jaminan integritas (dokumen berubah → tanda tangan rusak) dan terlihat di panel tanda tangan Adobe Reader, namun akan berstatus *"not trusted"* karena penerbit sertifikat bukan CA terpercaya. QR + halaman verifikasi menutup celah itu untuk verifikator awam: cukup pindai, tanpa perlu paham PKI.

| ID | Kebutuhan | Prioritas |
|---|---|---|
| F7.1 | Yayasan memiliki satu sertifikat penandatanganan `.p12` beserta kata sandinya | Must |
| F7.2 | Kata sandi `.p12` disimpan terenkripsi (`Crypt`), kunci enkripsi berasal dari env, tidak pernah masuk repositori | Must |
| F7.3 | Berkas `.p12` disimpan di luar `storage/app/public`, tidak dapat diakses via HTTP | Must |
| F7.4 | Setiap PDF berstatus `diterbitkan` ditandatangani secara kriptografis sebelum disimpan | Must |
| F7.5 | Metadata tanda tangan: nama penanda tangan, alasan ("Pengesahan SK"), lokasi, waktu | Must |
| F7.6 | Hash SHA-256 dari PDF final dihitung dan disimpan di basis data | Must |
| F7.7 | QR code menautkan ke `https://<domain>/verifikasi/{uuid}` dan tercetak pada PDF | Must |
| F7.8 | Halaman verifikasi publik (tanpa login) menampilkan: nomor SK, nama, NIGY, satuan kerja, jabatan, tanggal terbit, **status keabsahan** | Must |
| F7.9 | Halaman verifikasi hanya menampilkan data minimum — tidak menampilkan NIK, alamat, atau berkas pribadi | Must |
| F7.10 | Verifikasi mandiri: pengguna dapat mengunggah PDF, sistem membandingkan hash dan melaporkan cocok/tidak | Should |
| F7.11 | SK dibatalkan → halaman verifikasi menampilkan status "DIBATALKAN" beserta tanggal & nomor pengganti | Must |
| F7.12 | Rotasi sertifikat: sertifikat lama diarsipkan, SK lama tetap terverifikasi dengan sertifikat saat penerbitan | Should |
| F7.13 | Peringatan otomatis 60 hari sebelum sertifikat kedaluwarsa | Should |
| F7.14 | Arsitektur penandatanganan dibungkus antarmuka `SignerInterface` agar dapat diganti PSrE tersertifikasi di v2 | Should |
| F7.15 | Endpoint verifikasi dibatasi laju (*rate limit*) dan UUID tidak dapat ditebak | Must |

**Pengamanan gambar tanda tangan basah.** Gambar pindaian tanda tangan Ketua Yayasan disimpan di server agar penandatanganan (termasuk batch) dapat berjalan sekali klik. Konsekuensinya, berkas ini menjadi aset paling sensitif dalam sistem — siapa pun yang memperolehnya dapat menempelkannya ke dokumen apa pun di luar sistem, dan tanda tangan kriptografis tidak dapat mencegah hal itu. Pengamanan berikut bersifat wajib:

| ID | Kebutuhan | Prioritas |
|---|---|---|
| F7.16 | Berkas disimpan di luar *document root* (`storage/app/private/signature/`), tidak pernah dapat diakses via HTTP dalam bentuk apa pun | Must |
| F7.17 | Hanya *service* penandatangan yang membacanya; tidak ada rute, *controller*, maupun API yang mengembalikan berkas ini | Must |
| F7.18 | Izin berkas di server dibatasi (`0400`, pemilik = pengguna PHP-FPM); tidak ikut dalam cadangan yang dikirim ke pihak ketiga tanpa enkripsi | Must |
| F7.19 | Penggantian berkas tanda tangan hanya dapat dilakukan Admin Yayasan, tercatat di audit log, dan memerlukan konfirmasi ulang kata sandi | Must |
| F7.20 | Pratinjau draft **tidak pernah** memuat gambar tanda tangan — hanya PDF berstatus `diterbitkan` | Must |

**Pustaka kandidat:** `setasign/fpdi` + `tecnickcom/tcpdf` (TCPDF mendukung `setSignature()` untuk PKCS#12), alternatif `LSNepomuceno/laravel-a1-pdf-sign`. Alur teknis: DomPDF menghasilkan PDF → berkas dilewatkan ke lapisan penandatangan → PDF bertanda tangan disimpan sebagai artefak final.

### 5.8 Modul Dashboard & Laporan

| ID | Kebutuhan | Prioritas |
|---|---|---|
| F8.1 | Dashboard Ketua/Admin Yayasan: total GTK, sebaran per satuan kerja, per jabatan, per status kepegawaian, per jenjang pendidikan | Must |
| F8.2 | Kartu antrean: SK menunggu verifikasi, SK menunggu tanda tangan | Must |
| F8.3 | Dashboard Admin Satker: statistik unitnya sendiri, kelengkapan profil, status SK | Must |
| F8.3a | Beranda GTK: kelengkapan profil, SK terbaru, tugas tambahan berjalan, dan berkas yang masih kurang | Must |
| F8.3b | Laporan bagi admin: daftar GTK yang belum pernah login ke portal, sebagai alat pantau adopsi | Should |
| F8.4 | Laporan: daftar GTK, rekap SK terbit per periode, pemegang tugas tambahan, GTK dengan data belum lengkap, GTK mendekati usia pensiun | Should |
| F8.5 | Semua laporan dapat diekspor ke Excel dan PDF | Should |
| F8.6 | Grafik pertumbuhan jumlah GTK per tahun | Could |

### 5.9 Modul Notifikasi & Audit

| ID | Kebutuhan | Prioritas |
|---|---|---|
| F9.1 | Notifikasi dalam aplikasi: SK diajukan, diverifikasi, ditolak, diterbitkan | Must |
| F9.2 | Lonceng notifikasi dengan penghitung belum dibaca, tampil di seluruh halaman | Must |
| F9.2a | Kartu antrean pada dashboard berfungsi sebagai penanda utama pekerjaan tertunda (pengganti peran email) | Must |
| F9.2b | Notifikasi email/WhatsApp **tidak** termasuk lingkup v1; arsitektur memakai Laravel Notification agar kanal baru dapat ditambah tanpa mengubah pemicu | — |
| F9.3 | Audit log mencatat: pembuatan/perubahan/penghapusan data GTK, seluruh transisi status SK, login/logout, akses berkas, perubahan pengaturan sertifikat | Must |
| F9.4 | Audit log bersifat *append-only*, tidak dapat diubah/dihapus dari antarmuka | Must |
| F9.5 | Audit log dapat difilter per pengguna, per entitas, per rentang tanggal, dan diekspor | Should |

---

## 6. Model Data

### 6.1 Konvensi Penamaan

**Seluruh identifier teknis memakai Bahasa Inggris; Bahasa Indonesia hanya muncul di lapisan yang dilihat pengguna.**

| Lapisan | Bahasa | Contoh |
|---|---|---|
| Nama tabel & kolom | Inggris, `snake_case`, tabel jamak | `employees.birth_date` |
| Model, controller, service, job | Inggris, `PascalCase` | `Decree`, `DecreeSigningService` |
| Rute & nama rute | Inggris | `/decrees/{decree}`, `decrees.verify` |
| Nilai enum di basis data | Inggris | `status = 'issued'` |
| Komponen & prop Vue | Inggris | `DecreeStatusBadge`, `:employee="employee"` |
| Berkas terjemahan | Indonesia | `lang/id/decree.php` |
| Teks antarmuka | Indonesia | "Surat Keputusan", "Ajukan" |
| Isi dokumen PDF | Indonesia | teks SK, konsideran, diktum |
| Pesan galat validasi | Indonesia | via `lang/id/validation.php` |

**Pengecualian yang disengaja** — identifier resmi Indonesia yang tidak punya padanan dan tidak boleh diterjemahkan, dipertahankan apa adanya sebagai nama kolom: `nigy`, `nik`, `nuptk`, `nip`, `npwp`, `npsn`, `rt`, `rw`. Menerjemahkannya (mis. `national_id`) justru mengaburkan makna bagi pengembang lokal dan memutus kesesuaian dengan dokumen sumber.

| ID | Kebutuhan | Prioritas |
|---|---|---|
| F10.1 | Tidak ada teks Indonesia yang di-*hardcode* pada komponen Vue, controller, maupun model — semuanya melalui berkas terjemahan | Must |
| F10.2 | Setiap nilai enum memiliki label Indonesia terpusat di satu berkas terjemahan (mis. `lang/id/decree.php`) | Must |
| F10.3 | Tanggal dirender Bahasa Indonesia (`12 Agustus 2026`) lewat satu *helper*/`Carbon::setLocale('id')`, bukan pemformatan manual tersebar | Must |
| F10.4 | Struktur `lang/` memungkinkan penambahan bahasa lain tanpa mengubah kode | Could |

### 6.2 Diagram Relasi (ringkas)

```
work_units ──1:N── users
     │
     └──1:N── employees ──1:N── educations
                   │       └──1:N── documents
                   │       └──1:N── employee_additional_duties ──N:1── additional_duties
                   │
                   └──1:N── decrees ──N:1── decree_types
                                 ├──N:1── decree_batches
                                 ├──1:1── decree_signatures
                                 └──1:N── decree_workflow_logs
```

### 6.3 Tabel Utama

**`work_units`** (satuan kerja) — `id`, `code` (unik), `name`, `level`, `npsn`, `address`, `head_name`, `head_nigy`, `phone`, `email`, `logo_path`, `is_active`, timestamps

**`users`** — `id`, `name`, `email` (unik), `username` (unik), `password`, `role` (`foundation_head`/`foundation_admin`/`unit_admin`/`employee`), `work_unit_id` (nullable), `employee_id` (nullable, wajib untuk peran `employee`), `is_active`, `must_change_password`, `two_factor_secret`, timestamps

**`employees`** (GTK) — `id`, `nigy` (unik), `nik`, `nuptk`, `nip`, `title_prefix`, `name`, `title_suffix`, `gender`, `birth_place`, `birth_date`, `religion`, `marital_status`, `mother_name`, `address`, `rt`, `rw`, `village`, `district`, `regency`, `province`, `postal_code`, `phone`, `email`, `npwp`, `bank_account_number`, `bank_name`, `blood_type`, `photo_path`, `work_unit_id`, `position_id`, `employment_status_id`, `foundation_start_date` (TMT Yayasan), `unit_start_date` (TMT Satuan Kerja), `subject`, `is_active`, `termination_date`, `termination_reason`, timestamps, softDeletes
> Indeks: `nigy`, `nik`, `work_unit_id`, `is_active`, indeks *fulltext* pada `name`
> Relasi satuan kerja sengaja **1:1** (satu GTK tepat satu satuan kerja) — dikonfirmasi tidak ada penugasan rangkap, sehingga tenancy, kelayakan SK, dan laporan cukup bersandar pada satu kolom `work_unit_id`

**`educations`** — `id`, `employee_id`, `level`, `institution`, `major`, `start_year`, `end_year`, `certificate_number`, `certificate_date`, `gpa`, `is_highest`, `certificate_file_path`, `transcript_file_path`, timestamps
> Batasan: hanya satu baris `is_highest = true` per `employee_id` (ditegakkan di aplikasi + indeks unik parsial)

**`documents`** (berkas kepegawaian) — `id`, `employee_id`, `category`, `name`, `path`, `mime`, `size`, `uploaded_by`, timestamps

**`positions`** (jabatan) — `id`, `code` (unik), `name`, `group` (`educator`/`education_staff`), `is_active`, timestamps

**`employment_statuses`** — `id`, `code` (unik), `name`, `is_active`, timestamps

**`additional_duties`** (referensi tugas tambahan) — `id`, `code` (unik), `name`, `applicable_levels` (JSON), `hour_equivalence`, `quota_per_unit`, `requires_decree`, `is_active`, timestamps

**`employee_additional_duties`** (penetapan) — `id`, `employee_id`, `additional_duty_id`, `work_unit_id`, `academic_year`, `start_date`, `end_date`, `notes`, `decree_id` (nullable), `status`, `created_by`, timestamps
> Indeks unik gabungan `(employee_id, additional_duty_id, academic_year)` + validasi irisan periode di aplikasi

**`decree_types`** (jenis SK) — `id`, `code` (unik), `name`, `template_view`, `number_format`, `number_padding`, `consideration_recalling` (Mengingat, teks), `consideration_weighing` (Menimbang, JSON larik terurut), `consideration_observing` (Memperhatikan, teks), `requires_effective_date`, `is_active`, timestamps
> Nomor selalu direset per tahun kalender, sehingga tidak ada flag `resets_annually`

**`decrees`** (SK) — `id`, `uuid` (unik, untuk verifikasi publik), `decree_type_id`, `employee_id`, `work_unit_id`, `decree_batch_id` (nullable), `decree_number` (unik, nullable saat draft), `sequence_number`, `registration_number` (unik, nullable), `academic_year`, `effective_date` (TMT), `issued_date` (tanggal penetapan), `issued_place`, `appointed_as`, `position_snapshot`, `snapshot_data` (JSON — seluruh nilai tercetak), `status`, `pdf_path`, `pdf_hash`, `rejection_reason`, `cancellation_reason`, `replacement_decree_id`, `is_legacy`, `legacy_verified_at`, `legacy_verified_by`, `created_by`, `verified_by`, `verified_at`, `signed_by`, `signed_at`, timestamps
> Indeks: `decree_number`, `uuid`, `(work_unit_id, status)`, `(employee_id, academic_year)`
> Nilai `status`: `draft`, `submitted`, `rejected`, `verified`, `issued`, `cancelled`, `superseded`
> Catatan migrasi: agar arsip lama dapat masuk dengan PDF saja, kolom `decree_number`, `sequence_number`, `registration_number`, `academic_year`, `effective_date`, `issued_date`, `appointed_as`, dan `snapshot_data` harus *nullable*. Validasi kelengkapan ditegakkan di *Form Request* untuk SK terbitan sistem, bukan di skema.

**`decree_workflow_logs`** — `id`, `decree_id`, `from_status`, `to_status`, `user_id`, `notes`, `created_at`

**`decree_batches`** — `id`, `name`, `decree_type_id`, `academic_year`, `effective_date`, `issued_date`, `total`, `succeeded`, `failed`, `status`, `created_by`, `signed_by`, `signed_at`, timestamps

**`decree_signatures`** — `id`, `decree_id`, `certificate_id`, `signer_name`, `signed_at`, `hash_sha256`, `signature_meta` (JSON), `created_at`

**`certificates`** — `id`, `name`, `p12_path`, `password_encrypted`, `subject`, `issuer`, `serial`, `valid_from`, `valid_until`, `fingerprint`, `is_active`, timestamps

**`number_counters`** — `id`, `key`, `year`, `value`, timestamps
> Dipakai dua keperluan: nomor SK (`decree:SK-PPT:SMK:2026`) dan NIGY (`nigy:SMK:2026`)
> Dikunci baris (`lockForUpdate`) saat alokasi nomor

**`audit_logs`** — `id`, `user_id`, `action`, `auditable_type`, `auditable_id`, `old_values` (JSON), `new_values` (JSON), `ip`, `user_agent`, `created_at`

**`settings`** — `key`, `value` (JSON), `group`

---

## 7. Alur Pengguna Utama

### 7.1 Penerbitan SK Tunggal

1. Admin Satker membuka profil GTK → **Terbitkan SK** → memilih jenis SK.
2. Sistem memuat data GTK ke formulir; admin melengkapi TMT, "diangkat kembali sebagai", tanggal penetapan.
3. Admin melihat **Pratinjau PDF** (berwatermark DRAFT) → **Ajukan**.
4. Admin Yayasan menerima notifikasi, memeriksa isi. Bila keliru → **Tolak** dengan alasan (kembali ke Admin Satker). Bila benar → **Verifikasi**, sistem mengalokasikan nomor SK + nomor registrasi.
5. Ketua Yayasan membuka antrean tanda tangan, meninjau pratinjau → **Setujui & Tanda Tangani**.
6. Sistem membekukan snapshot data, menghasilkan PDF final beserta QR, menandatanganinya dengan sertifikat, menghitung hash, menyimpan berkas, dan mengunci SK.
7. Admin Satker & Admin Yayasan dapat mengunduh dan mencetak.

### 7.2 Batch Generate SK Perpanjangan Tahunan

1. Admin Yayasan → **SK** → **Batch Baru**: pilih jenis "SK Perpanjangan", tahun pelajaran 2026/2027, TMT 1 Juli 2026, tanggal penetapan.
2. Filter penerima: satuan kerja = semua, status kepegawaian = Tetap Yayasan, status = aktif → 213 GTK terjaring.
3. Sistem menampilkan pra-validasi: 7 GTK tidak memiliki data pendidikan tertinggi. Admin memilih **lewati** dan melengkapinya belakangan.
4. **Proses** → 206 draft SK dibuat via queue, progres tampil real-time. Nomor dialokasikan berurutan dan atomik.
5. Ketua Yayasan membuka batch, meninjau daftar penerima + memeriksa beberapa pratinjau → **Tanda Tangani Seluruh Batch**.
6. Sistem memproses 206 PDF di *queue*, masing-masing ditandatangani. Ringkasan: 206 berhasil, 0 gagal.
7. Admin Yayasan mengunduh ZIP terkelompok per satuan kerja untuk didistribusikan.

### 7.3 Verifikasi oleh Pihak Eksternal

1. Petugas Dinas memindai QR pada lembar SK.
2. Terbuka halaman `https://simqoh.qomarulhidayah.sch.id/verifikasi/{uuid}` tanpa perlu login.
3. Halaman menampilkan: ✅ **SK VALID** — nomor, nama, NIGY, satuan kerja, jabatan, tanggal terbit, penanda tangan, waktu penandatanganan.
4. Bila SK telah dicabut: ⛔ **DIBATALKAN** beserta tanggal pembatalan dan rujukan SK pengganti.

---

## 8. Kebutuhan Non-Fungsional

| Kategori | Kebutuhan |
|---|---|
| **Kinerja** | Halaman daftar (1.000 baris, terpaginasi) < 1,5 detik; generate 1 PDF < 3 detik; batch 200 SK selesai < 10 menit |
| **Skalabilitas** | Dirancang untuk 1.000 GTK dan 5.000 SK/tahun tanpa perubahan arsitektur |
| **Ketersediaan** | Target *uptime* 99% pada jam kerja (07.00–16.00 WIB) |
| **Keamanan** | HTTPS wajib; kata sandi Argon2id/bcrypt; perlindungan CSRF & XSS; unggahan divalidasi MIME; berkas privat via *signed URL*; sertifikat `.p12` di luar *document root*; *rate limit* login & endpoint verifikasi |
| **Privasi** | Data pribadi (NIK, alamat, NPWP, rekening) hanya terlihat oleh Admin Yayasan, Admin Satker terkait, dan GTK pemilik data; tidak pernah tampil di halaman verifikasi publik. GTK tidak dapat melihat data GTK lain sama sekali |
| **Cadangan** | Basis data dicadangkan harian, berkas mingguan, retensi 30 hari, uji pemulihan tiap kuartal |
| **Kompatibilitas** | Chrome/Edge/Firefox versi terkini; responsif hingga lebar 768px (tablet); pencetakan F4/Folio |
| **Bahasa** | Antarmuka dan dokumen PDF sepenuhnya Bahasa Indonesia; kode, skema basis data, dan nilai enum Bahasa Inggris (§6.1); format tanggal `d F Y` (mis. 12 Agustus 2026) |
| **Aksesibilitas** | Kontras memadai, dapat dinavigasi keyboard pada formulir utama |
| **Rekam jejak** | Seluruh aksi kritis tercatat pada audit log dan tidak dapat dihapus |

---

## 9. Arsitektur Teknis

| Lapisan | Pilihan |
|---|---|
| Backend | Laravel 13 (PHP 8.3) |
| Frontend | Inertia.js + Vue 3 (Composition API) + Tailwind CSS |
| Basis data | MySQL 8 (uji & dev berjalan di MariaDB 11.8) |
| Antrean | Redis + Laravel Horizon (batch generate, pengiriman email) |
| Penyimpanan berkas | Disk lokal privat (`storage/app/private`), siap dipindah ke S3-compatible |
| PDF | `barryvdh/laravel-dompdf`, kertas F4/Folio via *custom paper size* `[0, 0, 609.45, 935.43]` (pt) |
| Tanda tangan | `setasign/fpdi` + `tecnickcom/tcpdf` (PKCS#12), dibungkus `SignerInterface` |
| QR | `simplesoftwareio/simple-qrcode` (di-*embed* sebagai data URI) |
| Excel | `maatwebsite/excel` |
| Otorisasi | Laravel Policies + Gates, ditambah *global scope* tenancy |
| Audit | Observer kustom (trait `Auditable` + tabel `audit_logs`) — diputuskan saat F1 menggantikan `owen-it/laravel-auditing` |
| Pengujian | Pest (unit + feature), fokus pada alur SK, penomoran, dan otorisasi |

**Lingkungan penyebaran:** VPS dengan akses root, sehingga Redis, Horizon, Supervisor, dan cron tersedia penuh — rancangan antrean pada PRD ini dapat dijalankan apa adanya tanpa penyesuaian. Kebutuhan penyiapan server: PHP 8.3 + ekstensi `gd`, `openssl`, `mbstring`, `zip`; MySQL 8; Redis; Nginx; sertifikat TLS (Let's Encrypt); Supervisor untuk Horizon; cron untuk `schedule:run`; dan cadangan otomatis ke luar server.

**Prinsip arsitektur yang dipegang:**

1. **Snapshot, bukan referensi langsung.** SK terbit menyimpan salinan data tercetak (`snapshot_data`) sehingga perubahan data GTK di kemudian hari tidak mengubah dokumen yang sudah sah.
2. **Tenancy ditegakkan di server.** Filter satuan kerja dilakukan di *global scope* + Policy, tidak pernah bergantung pada parameter dari klien.
3. **Alokasi nomor bersifat transaksional.** Baris `number_counters` dikunci selama transaksi untuk menjamin nol duplikasi pada batch konkuren.
4. **Penandatanganan terisolasi.** Semua interaksi sertifikat melalui satu service class; kunci privat tidak pernah menyentuh lapisan HTTP.
5. **Berkas berat masuk queue.** Batch generate dan penandatanganan tidak pernah dijalankan pada siklus request.
6. **Modular sejak awal.** SK adalah *satu jenis dokumen*, bukan satu-satunya. Empat komponen berikut dibangun generik agar modul surat keluar (v2) dapat memakainya kembali tanpa dibongkar:
   - `NumberAllocator` menerima kunci penghitung sembarang — `decree:...`, `nigy:...`, kelak `letter:...`
   - `SignerInterface` + pipeline penandatanganan tidak mengetahui apa pun tentang SK
   - Halaman verifikasi publik dirancang untuk "dokumen ber-UUID", bukan khusus SK
   - Kop surat, tembusan, dan identitas yayasan berada di `settings`, dipakai bersama seluruh jenis dokumen

   Yang **tidak** digeneralisasi: tabel `decrees` tetap khusus SK. Surat keluar punya bentuk data sendiri (tujuan, perihal, lampiran, sifat, klasifikasi arsip) dan akan mendapat tabel `letters` tersendiri. Memaksakan satu tabel untuk keduanya akan menghasilkan puluhan kolom nullable yang saling tidak relevan.

---

## 10. Roadmap Rilis

| Fase | Lingkup | Estimasi | Status |
|---|---|---|---|
| **F1 — Fondasi** | Skema DB, autentikasi, RBAC + tenancy, master data (satker, jabatan, jenis SK, ref tugas tambahan), pengaturan yayasan | 2 minggu | ✅ **selesai 12 Agu 2026** |
| **F2 — Data GTK** | CRUD GTK, generator NIGY + format terkonfigurasi, riwayat pendidikan, unggah berkas & foto, indikator kelengkapan, impor/ekspor Excel, pencarian | 3 minggu | ⬜ |
| **F2b — Portal GTK** | Akun massal GTK, beranda mandiri, sunting data pribadi, unggah berkas & arsip SK lama, antrean verifikasi arsip, tampilan ponsel | 1–2 minggu | ⬜ |
| **F3 — Tugas Tambahan** | Penetapan berperiode, validasi irisan & kuota, riwayat, penetapan massal | 1 minggu | ⬜ |
| **F4 — SK Tunggal** | Alur draft→ajukan→verifikasi→ttd, penomoran atomik, snapshot, generate PDF DomPDF, pratinjau draft | 2–3 minggu | ⬜ |
| **F5 — Tanda Tangan & Verifikasi** | Manajemen sertifikat, penandatanganan PKCS#12, QR, halaman verifikasi publik, hash & unggah-verifikasi | 2 minggu | ⬜ |
| **F6 — Batch** | Batch generate via queue, progres real-time, tanda tangan massal, unduh ZIP/gabungan, laporan hasil | 1–2 minggu | ⬜ |
| **F7 — Dashboard & Audit** | Dashboard per peran, laporan + ekspor, notifikasi, audit log & penampilnya | 1–2 minggu | ⬜ |
| **F8 — Uji & Migrasi** | UAT bersama Admin Yayasan & Admin Satker, migrasi data GTK eksisting, unggah arsip PDF SK lama (metadata menyusul), pelatihan, *go-live* | 2 minggu | ⬜ |

Total estimasi: **15–19 minggu**.

> Portal GTK (F2b) sengaja ditempatkan setelah data dasar siap tetapi sebelum modul SK — begitu portal hidup, pengumpulan data berjalan paralel dengan pembangunan fitur SK, sehingga saat F4 selesai datanya sudah cukup matang untuk batch pertama.

---

## 11. Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Tanda tangan self-signed berstatus *not trusted* di Adobe Reader dan diragukan pihak eksternal | Sedang | QR + halaman verifikasi publik sebagai jalur verifikasi utama; sosialisasi ke Dinas; `SignerInterface` disiapkan agar dapat berpindah ke PSrE tersertifikasi |
| Kebocoran/kehilangan berkas `.p12` | **Tinggi** | Simpan di luar *document root*, kata sandi terenkripsi, akses dibatasi Admin Yayasan, cadangan *offline* terpisah, prosedur rotasi & pencabutan terdokumentasi |
| **Kebocoran gambar tanda tangan basah** — dapat ditempelkan ke dokumen palsu di luar sistem, dan tanda tangan kriptografis tidak dapat mencegahnya | **Tinggi** | F7.16–F7.20: di luar *document root*, izin `0400`, tanpa rute HTTP, tidak muncul pada pratinjau draft, penggantian tercatat di audit log. Pemulihan bila bocor: ganti berkas, dan andalkan QR + halaman verifikasi sebagai pembeda dokumen asli |
| Akses server (SSH/panel) jatuh ke pihak tidak berwenang → `.p12` dan gambar tanda tangan ikut terbuka | **Tinggi** | SSH hanya dengan kunci (nonaktifkan login kata sandi), firewall, pembaruan sistem berkala, akses root dibatasi, dan pemantauan login server |
| Data GTK eksisting berkualitas buruk saat migrasi | Tinggi | Impor Excel dengan pratinjau validasi; periode pembersihan data sebelum *go-live*; laporan kelengkapan sebagai alat pantau |
| Nomor SK bentrok pada batch besar | Tinggi | Alokasi nomor dalam transaksi ber-*lock*; uji beban konkuren pada F4 |
| Kinerja DomPDF menurun pada batch ratusan dokumen | Sedang | Proses via queue, batasi 500/batch, gambar dioptimasi, font di-*embed* sekali |
| Ketua Yayasan jarang membuka sistem sehingga antrean menumpuk | **Tinggi** | Tidak ada penanda tangan pengganti, sehingga risiko ini tidak dapat dialihkan. Mitigasi: tanda tangan massal sekali klik, antarmuka ramah tablet, kartu antrean menonjol di dashboard, dan 2FA yang mudah dipakai. Admin Yayasan memantau umur antrean dan mengingatkan secara langsung — email/WhatsApp di luar lingkup v1 |
| SK terbit ternyata salah data | Sedang | Snapshot + pratinjau wajib sebelum tanda tangan; mekanisme pembatalan + SK pengganti yang terlacak |
| Admin Satker mengakses data unit lain | Tinggi | Tenancy di *global scope* + Policy, ditutup dengan *feature test* otorisasi khusus |
| GTK mengakses data GTK lain lewat manipulasi URL/ID | **Tinggi** | Peran `employee` dibatasi pada `employees.id` miliknya di *global scope* + Policy; *feature test* wajib untuk setiap rute portal, termasuk unduh berkas dan PDF |
| Ratusan akun GTK memperluas permukaan serangan (kata sandi lemah, akun terbengkalai) | Sedang | Wajib ganti kata sandi saat login pertama, penonaktifan otomatis mengikuti status kepegawaian, *rate limit* login, dan laporan akun belum pernah login |
| GTK mengubah data pribadi tepat sebelum SK diterbitkan | Rendah | Nilai dibekukan ke `snapshot_data` saat penerbitan dan Admin Yayasan memverifikasi setiap SK sebelum penomoran; seluruh perubahan tercatat di audit log dengan penanda "diubah oleh yang bersangkutan" |
| Arsip SK palsu diunggah GTK ke riwayatnya sendiri | Sedang | Unggahan GTK berstatus menunggu verifikasi (F3.18, F5.33) dan tidak diakui sebagai riwayat resmi sebelum disetujui admin |

---

## 12. Keputusan Terkonfirmasi

Ditetapkan bersama pemilik produk pada 12 Agustus 2026:

| # | Keputusan | Konsekuensi teknis |
|---|---|---|
| K1 | **Stack:** Laravel + Inertia.js + Vue 3 + Tailwind CSS | Tanpa panel generator; seluruh UI dibangun sebagai komponen Vue |
| K2 | **Tanda tangan:** self-signed PKCS#12 + QR verifikasi + hash SHA-256 | Lapisan penandatangan terpisah setelah DomPDF; halaman verifikasi publik |
| K3 | **Alur SK tiga tahap:** Admin Satker → Admin Yayasan → Ketua Yayasan | Nomor dialokasikan pada transisi ke `terverifikasi` |
| K4 | **Kertas F4/Folio** (215 × 330 mm) | *Custom paper size* di DomPDF; margin template disesuaikan dan diuji cetak |
| K5 | **SK Tugas Tambahan bergantung jenis tugas** | Dikendalikan flag `requires_decree` pada `additional_duties` — tanpa percabangan kode |
| K6 | **Arsip lama: PDF wajib, metadata opsional** | Sebagian besar kolom `decrees` harus *nullable* untuk record `is_legacy` |
| K7 | **Hanya Ketua Yayasan yang menandatangani** — tanpa Plt maupun pendamping | Alur lebih sederhana; blok tanda tangan satu kolom; risiko antrean menumpuk naik |
| K8 | **TPQ seragam** dengan satuan kerja formal | Satu master jabatan, satu template SK untuk semua jenjang |
| K9 | **Notifikasi in-app saja** | Tanpa mailer maupun gateway WhatsApp; tetap memakai Laravel Notification |
| K10 | **Format nomor:** `{nomor}/{kode_jenis}/{kode_satker}/YPP-QH/{bulan_romawi}/{tahun}` | Seri terpisah per jenis × satker × tahun |
| K11 | **Kode jenis SK:** `SK-PPT`, `SK-PPJ`, `SK-TT`, `SK-MUT`, `SK-BHT` | Menjadi seeder master `decree_types` |
| K12 | **Kode satker:** jenjang + angka bila ada unit ganda (`SD1`, `SD2`, `TPQ1`, `TPQ2`) | Kode dikunci setelah dipakai pada SK terbit |
| K13 | **Reset nomor per tahun kalender** (1 Januari) | Satu tahun pelajaran dapat memuat dua seri nomor — laporan bersandar pada kolom `academic_year`, bukan nomor |
| K14 | **Konsideran berbeda per jenis SK**, dapat disunting Admin Yayasan | Tiga kolom baru pada `decree_types`; teks ikut dibekukan ke `snapshot_data` |
| K15 | **Satu GTK tepat satu satuan kerja** — tidak ada penugasan rangkap | Skema 1:1 dipertahankan; tenancy dan laporan cukup satu kolom |
| K16 | **VPS dengan akses root** | Redis + Horizon dipakai apa adanya; rancangan antrean pada PRD tidak perlu disesuaikan |
| K17 | **Gambar tanda tangan basah disimpan di server**, akses dibatasi | F7.16–F7.20; menjadi aset paling sensitif, masuk daftar risiko tinggi §11 |
| K18 | **Masa berlaku SK: satu tahun pelajaran** | Siklus perpanjangan massal tahunan tiap Juli; teks diktum Ketiga pada template perlu diperbaiki |
| K19 | **Kode & basis data Bahasa Inggris, antarmuka Bahasa Indonesia** | Konvensi §6.1; seluruh tabel, kolom, enum, rute, dan komponen berbahasa Inggris; teks Indonesia hanya di `lang/id/` dan template PDF. Identifier resmi Indonesia (`nigy`, `nik`, `nuptk`, `nip`, `npwp`, `npsn`, `rt`, `rw`) dipertahankan |
| K20 | **NIGY digenerate otomatis, format dapat diatur, dan dapat ditimpa manual oleh Admin Yayasan** | Format default `{tahun_masuk}{kode_satker}{urut}`; urut direset per tahun per satker lewat `number_counters` |
| K21 | **GTK menjadi peran keempat** (`employee`) dengan portal mandiri | NIGY read-only baginya; data pribadi, berkas, dan arsip SK lama dapat disuntingnya. Portal mandiri masuk lingkup v1, menambah fase F2b (1–2 minggu) |
| K22 | **Nama sistem: SIMQOH** (Sistem Informasi Manajemen Qomarul Hidayah), netral terhadap domain | Modul v1 "Kepegawaian & SK"; surat keluar menyusul sebagai modul v2. Empat komponen dibangun generik sejak awal (§9 poin 6) agar penambahannya murah |

### Pertanyaan Terbuka yang Tersisa

1. **Daftar satuan kerja sebenarnya** beserta kodenya — pola sudah disepakati (K12), tetapi jumlah unit riil per jenjang belum dipastikan. Diperlukan sebelum seeder `WorkUnitSeeder` F1 ditulis; saat ini pengembangan memakai data demo (`DemoSeeder`, khusus `local`).
2. **Teks konsideran untuk `SK-PPJ`, `SK-TT`, `SK-MUT`, `SK-BHT`** — struktur data sudah siap (K14), isinya dapat diisi Admin Yayasan sendiri setelah sistem berjalan, jadi ini tidak memblokir pengembangan.
3. **Nama & jabatan Ketua Yayasan** — sudah tidak memblokir seeder (diisi kosong + peringatan); wajib diisi via Pengaturan Yayasan sebelum fase SK (F4).

---

## Lampiran A — Pemetaan Template `sk.blade.php`

Template referensi yang ada sudah mencakup: kop yayasan, konsideran (Mengingat/Menimbang/Memperhatikan), diktum MEMUTUSKAN (Pertama s.d. Keempat), blok biodata, blok penetapan & tanda tangan, tembusan, dan nomor registrasi. Penyesuaian yang diperlukan saat implementasi:

| Bagian | Kondisi sekarang | Penyesuaian |
|---|---|---|
| Path gambar | `/public/assets/img/...` | Ubah ke `public_path('assets/img/...')` agar DomPDF memuat dari filesystem |
| Nama penanda tangan | `{{ $kepala_sekolah }}` | Ganti menjadi `{{ $chairman_name }}` — yang menandatangani adalah Ketua Yayasan, bukan kepala sekolah |
| Seluruh nama variabel | Bahasa Indonesia | Ubah ke Bahasa Inggris sesuai tabel pemetaan di §5.6; teks yang tercetak tetap Indonesia |
| Kop & tembusan | *hard-coded* di Blade | Ambil dari tabel `settings` |
| Blok tanda tangan | Hanya gambar tanda tangan basah | Tambahkan QR verifikasi di sisi kiri blok tanda tangan |
| Watermark draft | Belum ada | Tambahkan blok bersyarat saat `$is_signed === false` |
| Konsideran | Statis (4 butir "Menimbang" ditulis manual) | Render dari master jenis SK: `$consideration_recalling`, perulangan `$consideration_weighing` dengan nomor otomatis, `$consideration_observing`. Teks yang ada sekarang menjadi nilai awal untuk `SK-PPT` |
| Ukuran kertas | Tidak dideklarasikan (default A4) | Set F4/Folio: `Pdf::setPaper([0, 0, 609.45, 935.43])`. Margin `body` saat ini `.8cm 1.5cm .5cm 1.5cm` perlu diuji cetak ulang pada tinggi 330 mm |
| Diktum Ketiga | "berlaku sejak tanggal ditetapkan sampai **akhir tahun pelajaran berikutnya**" | Masa berlaku yang disepakati adalah **satu tahun pelajaran** — teks ini perlu diperbaiki agar tidak bertentangan dengan siklus perpanjangan tahunan. Ikut dipindah ke konfigurasi per jenis SK |
| Blok Reg. | `position: fixed; bottom: .8cm` | Verifikasi posisi setelah ganti ke F4 — DomPDF menghitung `fixed` relatif terhadap tinggi halaman |
