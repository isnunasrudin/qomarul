<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet petunjuk pengisian, termasuk penjelasan tiap jenis akun (peran).
 *
 * Berada di sheet kedua supaya impor — yang hanya membaca sheet pertama —
 * tidak pernah menyentuhnya, dan boleh dihapus oleh pengguna.
 */
class UserImportGuideSheet implements FromArray, WithEvents, WithTitle
{
    /** Baris judul seksi, diisi saat array() dibangun. */
    private array $sectionRows = [];

    /** Baris judul tabel, diisi saat array() dibangun. */
    private array $headerRows = [];

    /** Baris contoh pengisian, diisi saat array() dibangun. */
    private array $exampleRows = [];

    private int $lastRow = 1;

    public function title(): string
    {
        return 'Petunjuk';
    }

    /** @return array<int, array<int, string>> */
    public function array(): array
    {
        $rows = [];

        $row = function (array $cells) use (&$rows): void {
            $rows[] = $cells;
        };

        $section = function (string $title) use (&$rows): void {
            $rows[] = [$title];
            $this->sectionRows[] = count($rows);
        };

        $header = function (array $cells) use (&$rows): void {
            $rows[] = $cells;
            $this->headerRows[] = count($rows);
        };

        $example = function (array $cells) use (&$rows): void {
            $rows[] = $cells;
            $this->exampleRows[] = count($rows);
        };

        $row(['PANDUAN IMPOR PENGGUNA — SIMQOH']);
        $row(['Sistem Informasi Manajemen Qomarul Hidayah · Yayasan Pondok Pesantren Qomarul Hidayah']);
        $row([]);

        $section('1. JENIS AKUN — nilai untuk kolom "peran"');
        $header(['Kode', 'Nama Akun', 'Kewenangan Utama', 'Kolom Wajib Tambahan']);
        $row([
            'foundation_head',
            'Ketua Yayasan',
            'Melihat seluruh data GTK dan SK semua satuan kerja; menolak pengajuan SK; '
            .'menandatangani/menerbitkan SK; membatalkan SK. Tidak dapat membuat SK, '
            .'mengelola pengguna, master data, maupun pengaturan.',
            '—',
        ]);
        $row([
            'foundation_admin',
            'Admin Yayasan',
            'Mengelola seluruh master data (satuan kerja, jabatan, status kepegawaian, jenis SK), '
            .'seluruh GTK, dan akun pengguna; mengubah pengaturan yayasan (kop surat, tembusan, '
            .'format NIGY); membuat dan memverifikasi SK. Tidak dapat menandatangani SK.',
            '—',
        ]);
        $row([
            'unit_admin',
            'Admin Satuan Kerja',
            'Mengelola GTK pada satuan kerjanya saja dan membuat/mengajukan SK untuk satker '
            .'tersebut. Tidak dapat menghapus GTK, mengelola pengguna, master data, '
            .'maupun pengaturan yayasan.',
            'kode_satker (wajib)',
        ]);
        $row([
            'employee',
            'GTK',
            'Portal mandiri: melihat dan menyunting profilnya, mengunggah berkas, '
            .'mengunggah arsip SK lama, dan mengunduh SK miliknya.',
            'nigy (wajib)',
        ]);
        $row([]);

        $section('2. KETENTUAN TIAP KOLOM');
        $header(['Kolom', 'Wajib', 'Keterangan']);
        $row(['nama', 'Ya', 'Nama lengkap pengguna.']);
        $row(['username', 'Ya', 'Harus unik, tidak boleh sama dengan pengguna lain.']);
        $row(['email', 'Ya', 'Harus unik. Dipakai untuk masuk dan menerima notifikasi.']);
        $row([
            'peran', 'Ya',
            'Salah satu kode pada tabel di atas, atau nama akunnya '
            .'(mis. "unit_admin" atau "Admin Satuan Kerja").',
        ]);
        $row([
            'kode_satker', 'Untuk unit_admin',
            'Kode satuan kerja, lihat menu Satuan Kerja (mis. SD1, SMP, SMK). '
            .'Untuk peran employee boleh dikosongkan — satker mengikuti GTK yang ditautkan.',
        ]);
        $row([
            'nigy', 'Untuk employee',
            'NIGY GTK yang ditautkan, lihat menu GTK. GTK yang sudah punya akun tidak dapat '
            .'dibuatkan akun kedua.',
        ]);
        $row([
            'kata_sandi', 'Tidak',
            'Kosongkan agar sistem membuat sandi acak (minimal 8 karakter bila diisi). '
            .'Sandi awal selalu wajib diganti saat pengguna masuk pertama kali.',
        ]);
        $row(['aktif', 'Tidak', 'ya / tidak. Kosong berarti ya.']);
        $row([]);

        $section('3. CONTOH BARIS — sesuaikan dengan data Anda, jangan disalin mentah');
        $header(['nama', 'username', 'email', 'peran', 'kode_satker', 'nigy', 'kata_sandi', 'aktif']);
        $example(['Ahmad Fauzi, S.Pd.', 'ahmad.fauzi', 'ahmad.fauzi@contoh.sch.id', 'employee', '', '2026SD1001', '', 'ya']);
        $example(['Siti Nurhaliza, S.Pd.', 'admin.sd1', 'admin.sd1@contoh.sch.id', 'unit_admin', 'SD1', '', '', 'ya']);
        $example(['Hj. Zumrotun Nasihah', 'ketua', 'ketua@contoh.sch.id', 'foundation_head', '', '', '', 'ya']);
        $example(['Admin Yayasan', 'admin', 'admin@contoh.sch.id', 'foundation_admin', '', '', '', 'ya']);
        $row([]);

        $section('4. CATATAN');
        $row(['Hanya sheet "Pengguna" yang dibaca saat impor; sheet ini tidak ikut terbaca dan boleh dihapus.']);
        $row(['Baris judul kolom pada sheet "Pengguna" (baris 4) jangan diubah namanya.']);
        $row(['Baris yang seluruhnya kosong diabaikan.']);
        $row(['Sandi awal ditampilkan sekali setelah impor — catat dan sampaikan kepada pengguna terkait.']);
        $row(['Bila ada satu baris bermasalah, tidak ada data yang tersimpan sebelum Anda mengonfirmasi di halaman pratinjau.']);

        $this->lastRow = count($rows);

        return $rows;
    }

    /** @return array<string, callable> */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                foreach (['A' => 20, 'B' => 20, 'C' => 48, 'D' => 22, 'E' => 16, 'F' => 18, 'G' => 24, 'H' => 10] as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                // Teks panjang dibungkus dan dirata-atas; tinggi baris dibiarkan
                // otomatis supaya aplikasi pembuka yang menyesuaikan.
                $sheet->getStyle('A1:H'.$this->lastRow)->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(Alignment::VERTICAL_TOP);

                $sheet->mergeCells('A1:H1');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF008000']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(30);

                $sheet->mergeCells('A2:H2');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['italic' => true, 'size' => 10, 'color' => ['argb' => 'FF616161']],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);

                foreach ($this->sectionRows as $rowNumber) {
                    $sheet->mergeCells("A{$rowNumber}:H{$rowNumber}");
                    $sheet->getStyle("A{$rowNumber}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FF1B5E20']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE8F5E9']],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => false],
                    ]);
                    $sheet->getRowDimension($rowNumber)->setRowHeight(24);
                }

                foreach ($this->headerRows as $rowNumber) {
                    $sheet->getStyle("A{$rowNumber}:H{$rowNumber}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2E7D32']],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_LEFT,
                            'vertical' => Alignment::VERTICAL_CENTER,
                            'wrapText' => true,
                        ],
                        'borders' => ['allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF1B5E20'],
                        ]],
                    ]);
                    $sheet->getRowDimension($rowNumber)->setRowHeight(20);
                }

                // Blok tabel jenis akun & ketentuan kolom diberi garis tipis.
                foreach ([['A5', 'D9'], ['A12', 'C20']] as [$start, $end]) {
                    $sheet->getStyle("{$start}:{$end}")->getBorders()->getAllBorders()
                        ->setBorderStyle(Border::BORDER_HAIR)
                        ->getColor()->setARGB('FFBDBDBD');
                }

                $this->styleExampleRows($sheet);
            },
        ];
    }

    private function styleExampleRows(Worksheet $sheet): void
    {
        foreach ($this->exampleRows as $rowNumber) {
            $sheet->getStyle("A{$rowNumber}:H{$rowNumber}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF5F5F5']],
                'borders' => ['allBorders' => [
                    'borderStyle' => Border::BORDER_HAIR,
                    'color' => ['argb' => 'FFBDBDBD'],
                ]],
                'font' => ['size' => 10],
            ]);
        }
    }
}
