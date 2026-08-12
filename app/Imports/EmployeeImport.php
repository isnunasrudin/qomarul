<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EmployeeImport implements WithHeadingRow
{
    public function __construct(public readonly bool $withLegacyNigy = false) {}

    /**
     * Seluruh baris dibaca sebagai koleksi; validasi & penyimpanan
     * ditangani EmployeeImportService agar pratinjau tidak menyimpan apa pun.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    public function collection(Collection $rows): void {}
}
