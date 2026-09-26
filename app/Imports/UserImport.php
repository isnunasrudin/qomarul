<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Membaca sheet pertama berkas impor pengguna.
 *
 * Seluruh baris dibaca sebagai koleksi; validasi & penyimpanan ditangani
 * UserImportService agar pratinjau tidak menyimpan apa pun.
 */
class UserImport implements WithHeadingRow
{
    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    public function collection(Collection $rows): void {}
}
