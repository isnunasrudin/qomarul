<?php

namespace App\Exports\Sheets;

use App\Enums\UserRole;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet data template impor pengguna.
 *
 * Hanya memuat baris judul kolom — tanpa contoh baris — supaya tidak ada data
 * contoh yang ikut terimpor. Nama kolom sengaja persis sama dengan kunci yang
 * dibaca UserImportService; jangan menambah tanda bintang atau imbuhan lain
 * pada judulnya (impor mencocokkan berdasarkan slug judul).
 */
class UserImportSheet implements FromArray, WithCustomStartCell, WithEvents, WithHeadings, WithTitle
{
    /** Banyak baris area isian yang diberi format & dropdown. */
    private const FILL_ROWS = 60;

    /** Baris judul kolom (baris 1-3 dipakai judul & keterangan). */
    private const HEADING_ROW = 4;

    public function title(): string
    {
        return 'Pengguna';
    }

    public function startCell(): string
    {
        return 'A'.self::HEADING_ROW;
    }

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        return [];
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return [
            'nama',
            'username',
            'email',
            'peran',
            'kode_satker',
            'nigy',
            'kata_sandi',
            'aktif',
        ];
    }

    /** @return array<string, callable> */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $this->styleTitle($sheet);
                $this->styleHeadings($sheet);
                $this->styleDataArea($sheet);
                $this->addDropdowns($sheet);

                // Baris judul kolom tetap terlihat saat menggulir.
                $sheet->freezePane('A'.(self::HEADING_ROW + 1));
            },
        ];
    }

    private function styleTitle(Worksheet $sheet): void
    {
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'TEMPLATE IMPOR PENGGUNA — SIMQOH');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF008000']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->mergeCells('A2:H2');
        $sheet->setCellValue(
            'A2',
            'Isi data mulai baris '.self::HEADING_ROW.' ke bawah. Kolom berlatar hijau tua wajib diisi. '
            .'Kolom "peran" dan "aktif" sudah berupa pilihan. Lihat sheet "Petunjuk" untuk jenis akun.',
        );
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10, 'color' => ['argb' => 'FF616161']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getRowDimension(3)->setRowHeight(8);
    }

    private function styleHeadings(Worksheet $sheet): void
    {
        $row = self::HEADING_ROW;

        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => ['allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => 'FF1B5E20'],
            ]],
        ]);

        // Empat kolom pertama wajib diisi; empat sisanya opsional/bersyarat.
        $sheet->getStyle("A{$row}:D{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF006400');
        $sheet->getStyle("E{$row}:H{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF43A047');

        $sheet->getRowDimension($row)->setRowHeight(22);

        foreach ([
            'A' => 'Wajib diisi.',
            'B' => 'Wajib diisi dan harus unik.',
            'C' => 'Wajib diisi dan harus unik.',
            'D' => 'Wajib diisi. Pilih dari daftar: '.implode(', ', UserRole::values()).'.',
            'E' => 'Wajib untuk peran unit_admin. Isi kode satuan kerja, mis. SD1 / SMP / SMK.',
            'F' => 'Wajib untuk peran employee. Isi NIGY GTK yang ditautkan.',
            'G' => 'Opsional. Kosongkan agar sistem membuat sandi acak (minimal 8 karakter bila diisi).',
            'H' => 'Opsional: ya / tidak. Kosong berarti ya.',
        ] as $column => $note) {
            $sheet->getComment("{$column}{$row}")->getText()->createTextRun($note);
        }
    }

    private function styleDataArea(Worksheet $sheet): void
    {
        $first = self::HEADING_ROW + 1;
        $last = self::HEADING_ROW + self::FILL_ROWS;

        foreach (['A' => 22, 'B' => 22, 'C' => 34, 'D' => 16, 'E' => 16, 'F' => 16, 'G' => 22, 'H' => 9] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        // Format teks supaya NIGY/username angka tidak diubah jadi tanggal atau bilangan.
        $sheet->getStyle("A{$first}:H{$last}")->getNumberFormat()->setFormatCode('@');

        $sheet->getStyle("A{$first}:H{$last}")->applyFromArray([
            'borders' => ['allBorders' => [
                'borderStyle' => Border::BORDER_HAIR,
                'color' => ['argb' => 'FFBDBDBD'],
            ]],
        ]);
    }

    private function addDropdowns(Worksheet $sheet): void
    {
        $first = self::HEADING_ROW + 1;

        $this->addListValidation($sheet, "D{$first}:D500", implode(',', UserRole::values()));
        $this->addListValidation($sheet, "H{$first}:H500", 'ya,tidak');
    }

    private function addListValidation(Worksheet $sheet, string $range, string $options): void
    {
        $validation = new DataValidation;
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle('Nilai tidak dikenal');
        $validation->setError('Pilih salah satu nilai dari daftar.');
        $validation->setFormula1('"'.$options.'"');

        // Catatan: setShowDropDown(true) justru menyembunyikan panah pilihan di
        // Excel, jadi sengaja tidak dipanggil.
        $sheet->setDataValidation($range, $validation);
    }
}
